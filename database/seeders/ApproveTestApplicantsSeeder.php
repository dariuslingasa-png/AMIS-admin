<?php

namespace Database\Seeders;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use App\Services\SoaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApproveTestApplicantsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1. Fetch first 100 submitted seeded applicants
        $applicants = EnrollmentApplicant::whereNotIn('status', ['approved'])
            ->where('email', 'like', 'seed-applicant-%@amis.test')
            ->limit(100)
            ->get();

        if ($applicants->isEmpty()) {
            $this->command->warn('No seeded applicants found. Please run TestEnrollmentAnalyticsSeeder first.');

            return;
        }

        $this->command->info('Approving '.$applicants->count().' test applicants...');

        $approvedCount = 0;
        $year = substr(date('Y'), 2); // 26 for 2026

        // Initialize unique student number index based on existing count
        $sequence = Student::count() + 1;

        foreach ($applicants as $applicant) {
            DB::transaction(function () use ($applicant, $now, $year, &$sequence, &$approvedCount) {
                // A. Ensure applicant documents are flagged as approved
                $docStatuses = $applicant->document_statuses ?? [];
                foreach (['photo_2x2', 'birth_cert', 'report_card', 'marriage_contract', 'medical_record'] as $doc) {
                    $docStatuses[$doc] = 'approved';
                }
                $applicant->update([
                    'document_statuses' => $docStatuses,
                ]);

                // B. Ensure a verified downpayment exists (required by the approval guard)
                $payment = Payment::updateOrCreate(
                    ['enrollment_applicant_id' => $applicant->id],
                    [
                        'user_id' => $applicant->user_id,
                        'amount' => 4000.00,
                        'method' => 'gcash',
                        'status' => 'verified',
                        'verified_at' => $now,
                        'paid_at' => $now,
                        'receipt_url' => 'receipts/test_downpayment.png',
                        'reference_no' => 'SEED'.strtoupper(Str::random(10)),
                    ]
                );

                // C. Generate student credentials
                do {
                    $studentNumber = $year.str_pad($sequence, 4, '0', STR_PAD_LEFT);
                    $sequence++;
                } while (Student::where('student_number', $studentNumber)->exists());

                $lastName = strtolower(preg_replace('/\s+/', '', (string) $applicant->last_name));
                $mailNick = $studentNumber.$lastName;
                $schoolEmail = $mailNick.'@amis.edu.ph';
                $suffix = 1;

                while (Student::where('school_email', $schoolEmail)->exists()) {
                    $mailNick = $studentNumber.$lastName.$suffix;
                    $schoolEmail = $mailNick.'@amis.edu.ph';
                    $suffix++;
                }

                $tempPassword = 'Amis@'.strtoupper(Str::random(5)).rand(10, 99);

                // E. Create Student Profile record
                $student = Student::create([
                    'user_id' => $applicant->user_id,
                    'enrollment_applicant_id' => $applicant->id,
                    'student_number' => $studentNumber,
                    'school_email' => $schoolEmail,
                    'ms_email' => $schoolEmail,
                    'ms_user_id' => 'test-ms-user-'.Str::uuid(),
                    'ms_account_created_at' => $now,
                    'temp_password' => Hash::make($tempPassword),
                    'grade_level' => $applicant->grade_level,
                    'school_year' => $applicant->school_year ?? '2026-2027',
                    'credentials_sent_at' => $now,
                ]);

                // F. Generate financial Statement of Account (SOA Ledger + 10 monthly billings)
                (new SoaService)->generate($student, $applicant);

                // G. Match and link the student to a classroom section group
                $gender = strtolower($applicant->gender ?? 'male');
                $learningMode = $applicant->learning_mode ?? 'Face-to-Face';
                $isFlexible = str_contains(strtolower($learningMode), 'flexible') || str_contains(strtolower($learningMode), 'online');
                $shift = null;
                if (str_contains($learningMode, '1st Shift')) {
                    $shift = '1st Shift';
                } elseif (str_contains($learningMode, '2nd Shift')) {
                    $shift = '2nd Shift';
                } elseif ($isFlexible) {
                    $shift = '1st Shift';
                }
                $modeBase = $isFlexible ? 'Flexible Online Learning' : 'Face-to-Face';

                // Find or create test section
                $section = Section::where('grade_level', $student->grade_level)
                    ->where('gender', $gender)
                    ->where('learning_mode', $modeBase)
                    ->where('shift', $shift)
                    ->first();

                if (! $section) {
                    $prefix = str_replace('Grade ', 'G', $student->grade_level);
                    $prefix = str_replace('Kinder ', 'K', $prefix);
                    $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
                    $shiftLabel = $shift ? ($shift === '1st Shift' ? '1st Shift' : '2nd Shift') : 'F2F';
                    $teamName = "{$prefix} - [{$genderLabel} & {$shiftLabel}]";

                    $section = Section::create([
                        'name' => 'A',
                        'grade_level' => $student->grade_level,
                        'learning_mode' => $modeBase,
                        'shift' => $shift,
                        'gender' => $gender,
                        'ms_team_id' => 'test-ms-team-'.Str::uuid(),
                        'ms_team_url' => 'https://teams.microsoft.com/l/team/test',
                    ]);
                }

                // Create link in student_sections
                StudentSection::updateOrCreate(
                    ['student_id' => $student->id, 'section_id' => $section->id],
                    [
                        'ms_status' => 'enrolled',
                        'ms_enrolled_at' => $now,
                    ]
                );

                // H. Update applicant status to approved
                $applicant->update([
                    'status' => 'approved',
                    'review_remarks' => null,
                ]);

                $approvedCount++;
            });
        }

        $this->command->info("Success: Approved {$approvedCount} applicants for Student Management testing!");
    }
}
