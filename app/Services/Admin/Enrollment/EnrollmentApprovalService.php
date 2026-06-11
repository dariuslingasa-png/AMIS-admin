<?php

namespace App\Services\Admin\Enrollment;

use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Models\SchoolFee;
use App\Models\Student;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use App\Services\SoaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollmentApprovalService
{
    public function __construct(
        private readonly EnrollmentReviewService $reviewService,
    ) {}

    public function approve(EnrollmentApplicant $applicant): string
    {
        $applicant->loadMissing('payment', 'student.account');
        $this->reviewService->assertReadyForApproval($applicant);

        if ($applicant->student) {
            if (! $applicant->student->account && $this->shouldGenerateSoa($applicant)) {
                $this->generateSoa($applicant->student, $applicant);

                return 'Student already onboarded. Missing SOA was generated.';
            }

            return 'Student already onboarded.';
        }

        $settings = EnrollmentSetting::current();

        if ($settings->generate_soa ?? true) {
            if ($this->shouldGenerateSoa($applicant) && ! SchoolFee::forGrade($applicant->grade_level, $applicant->school_year)) {
                throw ValidationException::withMessages([
                    'status' => "No school fees found for {$applicant->grade_level} SY {$applicant->school_year}. Add the fee first, then approve again.",
                ]);
            }
        }

        return DB::transaction(function () use ($applicant, $settings) {
            $studentNumber = $this->generateStudentNumber($applicant);
            [$mailNick, $schoolEmail] = $this->generateSchoolEmail($applicant, $studentNumber);
            $tempPassword = 'Amis@'.strtoupper(Str::random(5)).rand(10, 99);

            if ($settings->generate_microsoft_account ?? true) {
                [$msUserId, $msError, $graph] = $this->createMicrosoftAccount($applicant, $mailNick, $schoolEmail, $tempPassword);
            } else {
                $msUserId = null;
                $msError = null;
                $graph = new MicrosoftGraphService();
            }
            $student = $this->createStudent($applicant, $studentNumber, $schoolEmail, $msUserId, $tempPassword);

            $this->enrollInTeams($student, $msUserId, $graph);

            if ($settings->generate_soa ?? true) {
                $this->generateSoa($student, $applicant);
            }

            $documentRemarks = $this->reviewService->missingDocumentRemarks($applicant);

            $applicant->update([
                'status' => 'approved',
                'review_remarks' => $documentRemarks,
            ]);

            $onboardingSent = $this->sendOnboardingIfPossible($settings, $applicant, $student, $tempPassword, $msError);

            if ($msError) {
                return 'Application approved. Student number generated. Note: Microsoft account creation failed. Please create it manually. Error: '.$msError;
            }

            return $onboardingSent
                ? 'Application approved. Student credentials were generated and sent to the parent.'
                : 'Application approved. Student credentials were generated. Welcome email auto-send is currently disabled.';
        });
    }
 
    private function generateStudentNumber(EnrollmentApplicant $applicant): string
    {
        $schoolYear = $applicant->school_year ?? config('services.school.year') ?? date('Y');
        $startYear = substr(preg_replace('/\D+/', '', $schoolYear), 0, 4);
        if (strlen($startYear) < 4) {
            $startYear = date('Y');
        }
        $yearSuffix = substr($startYear, 2, 2);

        $studentNumber = DB::transaction(function () use ($yearSuffix) {
            $latest = Student::where('student_number', 'like', $yearSuffix.'%')
                ->lockForUpdate()
                ->orderByRaw('CAST(student_number AS UNSIGNED) DESC')
                ->value('student_number');

            if ($latest) {
                $num = (int) substr($latest, 2) + 1;
            } else {
                $num = 1;
            }

            $candidate = $yearSuffix.str_pad($num, 4, '0', STR_PAD_LEFT);

            while (Student::where('student_number', $candidate)->exists()) {
                $num++;
                $candidate = $yearSuffix.str_pad($num, 4, '0', STR_PAD_LEFT);
            }

            return $candidate;
        });

        return $studentNumber;
    }
 
    private function generateSchoolEmail(EnrollmentApplicant $applicant, string $studentNumber): array
    {
        $firstLetterOfLastName = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', (string) $applicant->last_name), 0, 1));
        $firstName = strtolower(preg_replace('/[^a-zA-Z]/', '', (string) $applicant->first_name));
        $mailNick = $studentNumber.$firstLetterOfLastName.$firstName;
        $schoolEmail = $mailNick.'@amis.edu.ph';
        $suffix = 1;

        while (Student::where('school_email', $schoolEmail)->exists()) {
            $mailNick = $studentNumber.$firstLetterOfLastName.$firstName.$suffix;
            $schoolEmail = $mailNick.'@amis.edu.ph';
            $suffix++;
        }

        return [$mailNick, $schoolEmail];
    }

    private function createMicrosoftAccount(
        EnrollmentApplicant $applicant,
        string $mailNick,
        string $schoolEmail,
        string $tempPassword,
    ): array {
        $graph = new MicrosoftGraphService();

        try {
            $displayName = trim($applicant->first_name.' '.$applicant->last_name);

            if ($graph->userExists($schoolEmail)) {
                $msUserId = $graph->resolveUserId($schoolEmail);
                Log::info("Microsoft account already exists for {$schoolEmail}, reusing existing user {$msUserId}");
            } else {
                $msUser = $graph->createUser($displayName, $mailNick, $schoolEmail, $tempPassword);
                $msUserId = $msUser['id'] ?? null;
            }

            if ($msUserId) {
                $studentSkuId = config('services.microsoft.student_sku_id');
                if ($studentSkuId) {
                    try {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        \App\Models\AdminAuditLog::record('license_assigned', true, "Automatically assigned student license to {$schoolEmail}", [
                            'email' => $schoolEmail,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    } catch (\Throwable $licenseEx) {
                        Log::error("Failed to assign license to {$schoolEmail}: " . $licenseEx->getMessage());
                        \App\Models\AdminAuditLog::record('license_assigned', false, "Failed to automatically assign student license to {$schoolEmail}: " . $licenseEx->getMessage(), [
                            'email' => $schoolEmail,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    }
                }
            }

            return [$msUserId, null, $graph];
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            Log::error('Microsoft Graph error for applicant '.$applicant->id.': '.$message);

            return [null, $message, $graph];
        }
    }

    private function createStudent(
        EnrollmentApplicant $applicant,
        string $studentNumber,
        string $schoolEmail,
        ?string $msUserId,
        string $tempPassword,
    ): Student {
        return Student::create([
            'user_id' => $applicant->user_id,
            'enrollment_applicant_id' => $applicant->id,
            'student_number' => $studentNumber,
            'school_email' => $schoolEmail,
            'ms_email' => $schoolEmail,
            'ms_user_id' => $msUserId,
            'ms_account_created_at' => $msUserId ? now() : null,
            'temp_password' => Hash::make($tempPassword),
            'grade_level' => $applicant->grade_level,
            'school_year' => $applicant->school_year,
        ]);
    }

    private function enrollInTeams(Student $student, ?string $msUserId, MicrosoftGraphService $graph): void
    {
        if (! $msUserId) {
            return;
        }

        try {
            $teamsResult = (new MsTeamsEnrollmentService($graph))->enrollStudent($student);

            if (($teamsResult['failed'] ?? 0) > 0) {
                Log::warning("Teams enrollment partial failure for {$student->student_number}", $teamsResult['errors'] ?? []);
            }
        } catch (\Throwable $exception) {
            Log::error('Teams enrollment failed for '.$student->student_number.': '.$exception->getMessage());
        }
    }

    private function generateSoa(Student $student, EnrollmentApplicant $applicant): void
    {
        if (! $this->shouldGenerateSoa($applicant)) {
            Log::info('SOA generation skipped for non-new student applicant '.$applicant->id, [
                'student_type' => $applicant->student_type,
            ]);

            return;
        }

        try {
            (new SoaService())->generate($student, $applicant);
        } catch (\Throwable $exception) {
            Log::error('SOA generation failed: '.$exception->getMessage());

            throw ValidationException::withMessages([
                'status' => 'Application approval created the student record, but SOA generation failed: '.$exception->getMessage(),
            ]);
        }
    }

    private function shouldGenerateSoa(EnrollmentApplicant $applicant): bool
    {
        $studentType = Str::of((string) $applicant->student_type)
            ->lower()
            ->replace(['_', '-'], ' ')
            ->squish()
            ->toString();

        return $studentType === 'new' || $studentType === 'new student';
    }

    private function sendOnboardingIfPossible(
        EnrollmentSetting $settings,
        EnrollmentApplicant $applicant,
        Student $student,
        string $tempPassword,
        ?string $msError,
    ): bool {
        if (! ($settings->send_onboarding_email ?? false)) {
            return false;
        }

        $recipients = collect([$applicant->parent_email ?: null, $applicant->email ?: null])
            ->filter(fn ($email) => $email && $email !== 'NA' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return false;
        }

        if (! $this->sendOnboardingEmail($applicant, $student, $tempPassword, $msError, $recipients->all())) {
            return false;
        }

        $student->update(['credentials_sent_at' => now()]);

        return true;
    }

    private function sendOnboardingEmail(
        EnrollmentApplicant $applicant,
        Student $student,
        string $tempPassword,
        ?string $msError,
        array $recipients,
    ): bool {
        $studentName = trim($applicant->first_name.' '.$applicant->last_name);
        $genderWord = strtolower((string) ($applicant->gender ?? 'male')) === 'female' ? 'daughter' : 'son';
        $pronoun = $genderWord === 'son' ? 'him' : 'her';

        $html = '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px;">
<tr><td align="center">
<table width="540" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
    <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:36px 40px;text-align:center;">
        <img src="'.asset('images/AMIS_Logo.png').'" alt="AMIS" width="64" height="64" style="margin-bottom:14px;border-radius:12px;">
        <h1 style="color:#fff;font-size:22px;margin:0 0 4px;font-weight:800;">Al Munawwara Islamic School</h1>
        <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">AMIS Enrollment Office</p>
    </td></tr>
    <tr><td style="padding:36px 40px;">
        <p style="font-size:18px;font-weight:700;color:#059669;margin:0 0 6px;">Assalamualaikum Warahmatullahi Wabarakatuh,</p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;">Dear Parent/Guardian of <strong>'.$studentName.'</strong>,</p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;line-height:1.7;">
            Alhamdulillah, the enrollment application of your <strong>'.$genderWord.'</strong>, <strong>'.$studentName.'</strong>, has been
            <span style="color:#059669;font-weight:700;">officially approved</span> for <strong>School Year '.$applicant->school_year.'</strong>.
            We warmly welcome '.$pronoun.' to the AMIS family.
        </p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;line-height:1.7;">Below are the school credentials for Microsoft 365 and the Student Portal:</p>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;width:160px;">Student Number</td><td style="padding:7px 0;font-size:15px;font-weight:800;color:#059669;">'.$student->student_number.'</td></tr>
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">Grade Level</td><td style="padding:7px 0;font-size:14px;font-weight:600;color:#111827;">'.$student->grade_level.'</td></tr>
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">School Email</td><td style="padding:7px 0;font-size:14px;font-weight:600;color:#111827;">'.$student->school_email.'</td></tr>
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">Temp Password</td><td style="padding:7px 0;font-size:14px;font-weight:700;color:#111827;letter-spacing:0.08em;background:#fef9c3;padding:4px 8px;border-radius:6px;">'.$tempPassword.'</td></tr>
            </table>
        </div>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin-bottom:20px;">
            <p style="font-size:13px;color:#9a3412;margin:0;font-weight:600;">Important reminders:</p>
            <ul style="font-size:13px;color:#9a3412;margin:8px 0 0;padding-left:18px;line-height:1.8;">
                <li>Please change the temporary password upon first login.</li>
                <li>Use the school email to sign in to Microsoft Teams for online classes.</li>
                <li>Keep these credentials safe and do not share them.</li>
            </ul>
        </div>
        <p style="font-size:13px;color:#6b7280;margin:0 0 6px;">Sign in at: <a href="https://portal.office.com" style="color:#059669;font-weight:600;">portal.office.com</a></p>
        '.($msError ? '<p style="color:#dc2626;font-size:12px;background:#fff1f2;padding:10px 14px;border-radius:8px;margin-top:12px;">Note: Microsoft account setup is still in progress. The school will notify you once it is ready.</p>' : '').'
        <p style="font-size:14px;color:#374151;margin:24px 0 0;line-height:1.7;">May Allah bless your '.$genderWord.'\'s journey of learning. We look forward to a fruitful school year together.</p>
        <p style="font-size:14px;color:#374151;margin:8px 0 0;font-weight:600;">Wassalamualaikum Warahmatullahi Wabarakatuh.</p>
    </td></tr>
    <tr><td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="color:#9ca3af;font-size:11px;margin:0 0 4px;font-weight:600;">Al Munawwara Islamic School</p>
        <p style="color:#9ca3af;font-size:11px;margin:0;">&copy; '.date('Y').' All rights reserved.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>';

        try {
            Mail::html($html, function ($message) use ($recipients, $studentName) {
                $message->to($recipients)
                    ->subject('AMIS Enrollment Approved for '.$studentName);
            });

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to send onboarding email: '.$exception->getMessage());

            return false;
        }
    }
}
