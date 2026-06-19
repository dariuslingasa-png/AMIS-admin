<?php

namespace App\Console\Commands;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixMismatchedSections extends Command
{
    protected $signature = 'student:fix-mismatched-sections {--dry-run : Only show changes without applying them}';
    protected $description = 'Find and move students with Flexible Online Learning modes who are incorrectly assigned to Face-to-Face sections';

    public function handle(): void
    {
        $dryRun = $this->option('dry-run');

        // Find students in Face-to-Face sections who actually have a Flexible Online Learning mode
        $students = Student::with(['applicant', 'studentSection.section'])
            ->whereHas('studentSection.section', function ($q) {
                $q->where('learning_mode', 'Face-to-Face');
            })
            ->whereHas('applicant', function ($q) {
                $q->where('learning_mode', 'like', '%flexible%')
                  ->orWhere('learning_mode', 'like', '%online%');
            })
            ->get();

        if ($students->isEmpty()) {
            $this->info('No mismatched student-section assignments found.');
            return;
        }

        $this->info("Found {$students->count()} mismatched student(s).");

        $reassignedCount = 0;

        foreach ($students as $student) {
            $applicant = $student->applicant;
            $currentSection = $student->studentSection->section;

            // Determine correct mode and shift
            $applicantMode = $applicant->learning_mode;
            $gender = strtolower($applicant->gender ?? 'male');
            if (str_contains($gender, 'girl') || str_contains($gender, 'female')) {
                $gender = 'female';
            } else {
                $gender = 'male';
            }

            $shift = '1st Shift';
            if (str_contains($applicantMode, '2nd Shift')) {
                $shift = '2nd Shift';
            }

            $targetMode = 'Flexible Online Learning';

            $this->info("Student #{$student->student_number} ({$applicant->last_name}, {$applicant->first_name}):");
            $this->info("  Applicant Mode: {$applicantMode}");
            $this->info("  Current Section: [ID {$currentSection->id}] {$currentSection->grade_level} - {$currentSection->learning_mode} ({$currentSection->gender})");
            $this->info("  Target Assignment: Mode: {$targetMode}, Shift: {$shift}, Gender: {$gender}");

            if ($dryRun) {
                $this->info("  [Dry Run] Would move to {$student->grade_level} {$targetMode} - {$shift} ({$gender})");
                continue;
            }

            // Find or create correct section in DB
            $targetSection = Section::where('grade_level', $student->grade_level)
                ->where('gender', $gender)
                ->where('learning_mode', $targetMode)
                ->where('shift', $shift)
                ->first();

            if (!$targetSection) {
                $grade = $student->grade_level;
                $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
                $shiftLabel = $shift === '1st Shift' ? '1st Shift' : '2nd Shift';
                
                if ($grade === 'Kinder 1') $prefix = 'K1';
                elseif ($grade === 'Kinder 2') $prefix = 'K2';
                else $prefix = 'G' . str_replace('Grade ', '', $grade);

                $teamName = "{$prefix} [{$genderLabel} & {$shiftLabel}]";

                $this->info("  Target section not found. Creating section: {$teamName}...");

                $targetSection = Section::create([
                    'name'          => null,
                    'grade_level'   => $grade,
                    'learning_mode' => $targetMode,
                    'shift'         => $shift,
                    'gender'        => $gender,
                    'ms_team_id'    => null, // Admin can provision Team later
                    'ms_team_url'   => null,
                ]);
            }

            DB::transaction(function () use ($student, $targetSection) {
                StudentSection::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'section_id' => $targetSection->id,
                        'ms_status' => 'pending', // Mark as pending so they get synced to teams correctly
                    ]
                );
            });

            $this->info("  ✓ Successfully reassigned to [ID {$targetSection->id}] {$targetSection->grade_level} - {$targetSection->learning_mode} - {$targetSection->shift}");
            $reassignedCount++;
        }

        $this->info("\nDone. {$reassignedCount} student(s) reassigned.");
    }
}
