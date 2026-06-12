<?php

namespace App\Services\Admin\Enrollment;

use App\Mail\EnrollmentOnboardingMail;
use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Models\SchoolFee;
use App\Models\Student;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use App\Services\SoaService;
use Illuminate\Database\UniqueConstraintViolationException;
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
            $shouldGenerateMicrosoftAccount = $settings->generate_microsoft_account ?? true;
            $graph = $shouldGenerateMicrosoftAccount ? new MicrosoftGraphService : null;
            $studentNumber = $this->generateStudentNumber($applicant);
            [$mailNick, $schoolEmail] = $this->generateSchoolEmail($applicant, $studentNumber, $graph);
            $tempPassword = 'Amis@'.strtoupper(Str::random(5)).rand(10, 99);
            $student = $this->createStudent($applicant, $studentNumber, $schoolEmail, null, $tempPassword);

            if ($shouldGenerateMicrosoftAccount) {
                [$msUserId, $msError] = $this->createMicrosoftAccount($graph, $applicant, $mailNick, $schoolEmail, $tempPassword);
                if ($msUserId) {
                    $student->update([
                        'ms_user_id' => $msUserId,
                        'ms_account_created_at' => now(),
                    ]);
                }
            } else {
                $msUserId = null;
                $msError = null;
                $graph = new MicrosoftGraphService;
            }

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

    private function generateSchoolEmail(
        EnrollmentApplicant $applicant,
        string $studentNumber,
        ?MicrosoftGraphService $graph = null,
    ): array {
        $baseMailNick = $this->baseMailNickname($applicant, $studentNumber);
        $mailNick = $baseMailNick;
        $schoolEmail = $mailNick.'@amis.edu.ph';
        $suffix = 1;
        $maxAttempts = 500;

        while ($this->schoolEmailIsReserved($schoolEmail, $graph)) {
            if ($suffix > $maxAttempts) {
                throw ValidationException::withMessages([
                    'status' => 'Unable to generate a unique Microsoft school email after '.$maxAttempts.' attempts. Please contact IT before approving this applicant.',
                ]);
            }

            $mailNick = $baseMailNick.$suffix;
            $schoolEmail = $mailNick.'@amis.edu.ph';
            $suffix++;
        }

        return [$mailNick, $schoolEmail];
    }

    private function baseMailNickname(EnrollmentApplicant $applicant, string $studentNumber): string
    {
        $firstLetterOfLastName = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', (string) $applicant->last_name), 0, 1));
        $firstName = strtolower(preg_replace('/[^a-zA-Z]/', '', (string) $applicant->first_name));

        return $studentNumber.$firstLetterOfLastName.($firstName ?: 'student');
    }

    private function schoolEmailIsReserved(string $schoolEmail, ?MicrosoftGraphService $graph = null): bool
    {
        $existsInPortal = Student::where(function ($query) use ($schoolEmail) {
            $query->where('school_email', $schoolEmail)
                ->orWhere('ms_email', $schoolEmail);
        })->exists();

        if ($existsInPortal) {
            return true;
        }

        if (! $graph) {
            return false;
        }

        try {
            return $graph->userExistsOrFail($schoolEmail);
        } catch (\Throwable $exception) {
            Log::error('Microsoft email availability check failed for '.$schoolEmail.': '.$exception->getMessage());

            throw ValidationException::withMessages([
                'status' => 'Unable to verify Microsoft email availability in Azure AD. Approval stopped before creating official student records. Please try again once Microsoft Graph is reachable.',
            ]);
        }
    }

    private function createMicrosoftAccount(
        MicrosoftGraphService $graph,
        EnrollmentApplicant $applicant,
        string $mailNick,
        string $schoolEmail,
        string $tempPassword,
    ): array {
        try {
            $displayName = trim($applicant->first_name.' '.$applicant->last_name);

            if ($graph->userExistsOrFail($schoolEmail)) {
                throw ValidationException::withMessages([
                    'status' => "Microsoft account {$schoolEmail} already exists in Azure AD. Approval stopped to prevent assigning a duplicate official email.",
                ]);
            }

            $msUser = $graph->createUser($displayName, $mailNick, $schoolEmail, $tempPassword, reuseExisting: false);
            $msUserId = $msUser['id'] ?? null;

            if ($msUserId) {
                $studentSkuId = config('services.microsoft.student_sku_id');
                if ($studentSkuId) {
                    try {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        AdminAuditLog::record('license_assigned', true, "Automatically assigned student license to {$schoolEmail}", [
                            'email' => $schoolEmail,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    } catch (\Throwable $licenseEx) {
                        Log::error("Failed to assign license to {$schoolEmail}: ".$licenseEx->getMessage());
                        AdminAuditLog::record('license_assigned', false, "Failed to automatically assign student license to {$schoolEmail}: ".$licenseEx->getMessage(), [
                            'email' => $schoolEmail,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    }
                }
            }

            return [$msUserId, null];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            Log::error('Microsoft Graph error for applicant '.$applicant->id.': '.$message);

            return [null, $message];
        }
    }

    private function createStudent(
        EnrollmentApplicant $applicant,
        string $studentNumber,
        string $schoolEmail,
        ?string $msUserId,
        string $tempPassword,
    ): Student {
        try {
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
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'status' => 'Generated AMIS Student ID or school email already exists. Approval stopped before creating duplicate official records. Please try again.',
            ]);
        }
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
            (new SoaService)->generate($student, $applicant);
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
        try {
            Mail::to($recipients)->send(new EnrollmentOnboardingMail($applicant, $student, $tempPassword, $msError));

            return true;
        } catch (\Throwable $exception) {
            Log::error('Failed to send onboarding email: '.$exception->getMessage());

            return false;
        }
    }
}
