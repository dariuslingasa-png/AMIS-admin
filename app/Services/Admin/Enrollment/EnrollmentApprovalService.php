<?php

namespace App\Services\Admin\Enrollment;

use App\Mail\EnrollmentOnboardingMail;
use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Models\Payment;
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

        $settings = EnrollmentSetting::current();

        if ($applicant->student) {
            $this->backfillMicrosoftPhoto($applicant);

            if (! $applicant->student->account && $this->shouldGenerateSoa($applicant)) {
                $this->generateSoa($applicant->student, $applicant);

                return 'Student already onboarded. Missing SOA was generated. Microsoft profile photo sync was retried.';
            }

            return 'Student already onboarded. Microsoft profile photo sync was retried.';
        }

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

                    $this->uploadApplicantPhotoToMicrosoft($applicant, $student, $graph, $msUserId);
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

            $onboardingStatus = $this->sendOnboardingIfPossible($settings, $applicant, $student, $tempPassword, $msError);

            if ($msError) {
                return 'Application approved. Student number generated. Note: Microsoft account creation failed. Please create it manually. Error: '.$msError;
            }

            return match ($onboardingStatus) {
                'sent' => 'Application approved. Student credentials were generated and sent to the parent.',
                'missing_payment_proof' => 'Application approved. Student credentials were generated. Welcome email was not sent because no payment proof is uploaded yet.',
                'missing_recipient' => 'Application approved. Student credentials were generated. Welcome email was not sent because no valid recipient email was found.',
                'failed' => 'Application approved. Student credentials were generated. Welcome email failed to send; please check the mail logs.',
                default => 'Application approved. Student credentials were generated. Welcome email auto-send is currently disabled.',
            };
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
        $firstGivenName = preg_split('/\s+/', trim((string) $applicant->first_name))[0] ?? '';
        $firstName = strtolower(preg_replace('/[^a-zA-Z]/', '', $firstGivenName));

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

    public function backfillMicrosoftPhoto(EnrollmentApplicant $applicant): void
    {
        $applicant->loadMissing('student');

        if (! $applicant->student) {
            return;
        }

        $identifier = $applicant->student->ms_user_id ?: $applicant->student->school_email;

        if (blank($identifier)) {
            return;
        }

        $this->uploadApplicantPhotoToMicrosoft($applicant, $applicant->student, new MicrosoftGraphService, $identifier);
    }

    private function uploadApplicantPhotoToMicrosoft(
        EnrollmentApplicant $applicant,
        Student $student,
        MicrosoftGraphService $graph,
        string $msUserId,
    ): void {
        $photo = $this->applicantPhotoForMicrosoft($applicant);

        if (! $photo) {
            return;
        }

        try {
            $graph->uploadUserPhoto($msUserId, $photo['bytes'], $photo['content_type']);

            AdminAuditLog::record('student_photo_uploaded', true, "Uploaded 2x2 photo to Microsoft profile for {$student->school_email}", [
                'student_id' => $student->id,
                'applicant_id' => $applicant->id,
                'email' => $student->school_email,
                'photo_path' => $photo['path'],
                'content_type' => $photo['content_type'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Microsoft profile photo upload failed for '.$student->school_email.': '.$exception->getMessage());

            AdminAuditLog::record('student_photo_uploaded', false, "Failed to upload 2x2 photo to Microsoft profile for {$student->school_email}", [
                'student_id' => $student->id,
                'applicant_id' => $applicant->id,
                'email' => $student->school_email,
                'photo_path' => $photo['path'],
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function applicantPhotoForMicrosoft(EnrollmentApplicant $applicant): ?array
    {
        $photoPath = $this->resolveApplicantPhotoPath($applicant->photo_2x2_url);

        if (! $photoPath) {
            return null;
        }

        $bytes = @file_get_contents($photoPath);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        return [
            'path' => $photoPath,
            'bytes' => $bytes,
            'content_type' => $this->imageContentType($photoPath),
        ];
    }

    private function resolveApplicantPhotoPath(?string $path): ?string
    {
        if (blank($path) || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = ltrim((string) $path, '/');
        $candidates = [];

        $optimizedPath = preg_replace('#thumbnails/(small|medium|large)/#', 'optimized/', $path);

        if (str_contains($optimizedPath, 'optimized/')) {
            $originalDirectory = dirname(str_replace('optimized/', 'original/', $optimizedPath));
            $filename = pathinfo($optimizedPath, PATHINFO_FILENAME);

            foreach ($this->enrollmentStorageRoots() as $root) {
                $directory = rtrim($root, '/').'/'.$originalDirectory;
                if (! is_dir($directory)) {
                    continue;
                }

                foreach (glob($directory.'/'.$filename.'.*') ?: [] as $file) {
                    $candidates[] = $file;
                }
            }
        }

        $variantPaths = collect([
            str_replace('optimized/', 'thumbnails/large/', $optimizedPath),
            str_replace('optimized/', 'thumbnails/medium/', $optimizedPath),
            $optimizedPath,
            $path,
        ])->unique();

        foreach ($this->enrollmentStorageRoots() as $root) {
            foreach ($variantPaths as $variantPath) {
                $candidates[] = rtrim($root, '/').'/'.ltrim($variantPath, '/');
            }
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && filesize($candidate) > 0) {
                return $candidate;
            }
        }

        return null;
    }

    private function enrollmentStorageRoots(): array
    {
        return [
            base_path('../amis_enrollment/storage/app/public'),
            base_path('../amis_enrollment/public/storage'),
            base_path('../enrollment.amis.edu.ph/storage/app/public'),
            base_path('../enrollment.amis.edu.ph/public/storage'),
            base_path('../enrollment/storage/app/public'),
            base_path('../enrollment/public/storage'),
            base_path('../../amis_enrollment/storage/app/public'),
            base_path('../../enrollment.amis.edu.ph/storage/app/public'),
            base_path('../../enrollment.amis.edu.ph/public/storage'),
            base_path('../../public_html/amis_enrollment/storage/app/public'),
            base_path('../../public_html/storage'),
            storage_path('app/public'),
            public_path('storage'),
        ];
    }

    private function imageContentType(string $path): string
    {
        $mime = function_exists('mime_content_type') ? mime_content_type($path) : null;

        if ($mime && str_starts_with(strtolower($mime), 'image/')) {
            return $mime;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
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
    ): string {
        if (! ($settings->send_onboarding_email ?? false)) {
            $this->markOnboardingEmail($applicant, 'disabled');

            return 'disabled';
        }

        if (! $this->hasUploadedPaymentProof($applicant)) {
            $this->markOnboardingEmail($applicant, 'missing_payment_proof');

            return 'missing_payment_proof';
        }

        $recipients = $this->onboardingRecipients($applicant);

        if ($recipients->isEmpty()) {
            $this->markOnboardingEmail($applicant, 'missing_recipient');

            return 'missing_recipient';
        }

        [$sent, $error] = $this->sendOnboardingEmail($applicant, $student, $tempPassword, $msError, $recipients->all());

        if (! $sent) {
            $this->markOnboardingEmail($applicant, 'failed', $error);

            return 'failed';
        }

        $student->update(['credentials_sent_at' => now()]);
        $this->markOnboardingEmail($applicant, 'sent', sentAt: now());

        return 'sent';
    }

    public function resendOnboardingInbox(EnrollmentApplicant $applicant): string
    {
        $applicant->loadMissing('student');

        if ($applicant->status !== 'approved' || ! $applicant->student) {
            throw ValidationException::withMessages([
                'onboarding_email' => 'Only approved applicants with generated student credentials can receive the onboarding inbox email.',
            ]);
        }

        $recipients = $this->onboardingRecipients($applicant);

        if ($recipients->isEmpty()) {
            $this->markOnboardingEmail($applicant, 'missing_recipient');

            return 'No valid parent or applicant email was found.';
        }

        $student = $applicant->student;
        $tempPassword = 'Amis@'.strtoupper(Str::random(5)).rand(10, 99);

        try {
            if (filled($student->school_email)) {
                (new MicrosoftGraphService)->resetPassword($student->school_email, $tempPassword);
            }
        } catch (\Throwable $exception) {
            $msError = 'Microsoft password reset failed: '.$exception->getMessage();

            Log::error('Failed to reset Microsoft password before onboarding resend: '.$exception->getMessage(), [
                'applicant_id' => $applicant->id,
                'student_id' => $student->id,
                'school_email' => $student->school_email,
            ]);

            [$sent, $error] = $this->sendOnboardingEmail($applicant, $student, '', $msError, $recipients->all());

            if (! $sent) {
                $this->markOnboardingEmail($applicant, 'failed', $error);

                return 'Inbox email failed to send after Microsoft password reset failed. Please check mail logs.';
            }

            $this->markOnboardingEmail($applicant, 'sent_reset_pending', $msError, now());

            return 'Inbox email sent to '.$recipients->implode(', ').', but Microsoft password reset is still blocked. Fix Microsoft permissions before resending credentials.';
        }

        $student->update(['temp_password' => Hash::make($tempPassword)]);

        [$sent, $error] = $this->sendOnboardingEmail($applicant, $student, $tempPassword, null, $recipients->all());

        if (! $sent) {
            $this->markOnboardingEmail($applicant, 'failed', $error);

            return 'Inbox email failed to resend. Please check mail logs.';
        }

        $student->update(['credentials_sent_at' => now()]);
        $this->markOnboardingEmail($applicant, 'sent', sentAt: now());

        return 'Inbox email resent to '.$recipients->implode(', ').'.';
    }

    private function hasUploadedPaymentProof(EnrollmentApplicant $applicant): bool
    {
        $applicant->loadMissing('payment');

        if (filled($applicant->payment?->receipt_url)) {
            return true;
        }

        return Payment::whereHas('applicant', function ($query) use ($applicant) {
            if ($applicant->family_application_id) {
                $query->where('family_application_id', $applicant->family_application_id);
            } elseif ($applicant->user_id) {
                $query->where('user_id', $applicant->user_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        })
            ->whereNotNull('receipt_url')
            ->where('receipt_url', '!=', '')
            ->exists();
    }

    private function sendOnboardingEmail(
        EnrollmentApplicant $applicant,
        Student $student,
        string $tempPassword,
        ?string $msError,
        array $recipients,
    ): array {
        try {
            $mailer = $this->onboardingMailer();
            Mail::mailer($mailer)->to($recipients)->send(new EnrollmentOnboardingMail($applicant, $student, $tempPassword, $msError));

            Log::info('Onboarding email sent', [
                'applicant_id' => $applicant->id,
                'student_id' => $student->id,
                'recipients' => $recipients,
                'mailer' => $mailer,
            ]);

            return [true, null];
        } catch (\Throwable $exception) {
            Log::error('Failed to send onboarding email: '.$exception->getMessage(), [
                'applicant_id' => $applicant->id,
                'student_id' => $student->id,
                'recipients' => $recipients,
                'mailer' => $this->onboardingMailer(),
            ]);

            return [false, $exception->getMessage()];
        }
    }

    private function onboardingMailer(): string
    {
        $default = (string) config('mail.default', 'log');

        if (in_array($default, ['log', 'array'], true) && array_key_exists('sendmail', config('mail.mailers', []))) {
            return 'sendmail';
        }

        return $default;
    }

    private function onboardingRecipients(EnrollmentApplicant $applicant): \Illuminate\Support\Collection
    {
        return collect([$applicant->parent_email ?: null, $applicant->email ?: null])
            ->filter(fn ($email) => $email && $email !== 'NA' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();
    }

    private function markOnboardingEmail(
        EnrollmentApplicant $applicant,
        string $status,
        ?string $error = null,
        mixed $sentAt = null,
    ): void {
        $applicant->forceFill([
            'onboarding_email_status' => $status,
            'onboarding_email_sent_at' => $sentAt,
            'onboarding_email_error' => $error ? Str::limit($error, 1000, '') : null,
        ])->save();
    }
}
