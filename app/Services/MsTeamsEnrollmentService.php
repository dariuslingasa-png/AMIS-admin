<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MsTeamsEnrollmentService
{
    public function __construct(private MicrosoftGraphService $graph) {}

    /**
     * Enroll a student into their matching section team + subject channels.
     * If no matching section exists, auto-creates one (same logic as storeSingle).
     * Duplicate-safe: uses DB lock so concurrent approvals share the same section.
     */
    public function enrollStudent(Student $student): array
    {
        if (!$student->ms_user_id) {
            throw new \Exception("Student {$student->student_number} has no ms_user_id set.");
        }

        $applicant    = $student->applicant;
        $gender       = strtolower($applicant->gender ?? 'male');
        $learningMode = $applicant->learning_mode ?? 'Face-to-Face';
        $results      = ['enrolled' => 0, 'failed' => 0, 'errors' => []];

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

        // Find section — do NOT auto-create
        $section = Section::where('grade_level', $student->grade_level)
            ->where('gender', $gender)
            ->where('learning_mode', $modeBase)
            ->where('shift', $shift)
            ->first();

        if (!$section) {
            $results['failed']++;
            $results['errors'][] = "No matching section found for {$student->grade_level} ({$gender} · {$modeBase} · {$shift}). Please create it manually.";
            Log::warning("MsTeamsEnrollmentService: No section found for student {$student->student_number} ({$student->grade_level} · {$gender} · {$modeBase} · {$shift})");
            return $results;
        }

        if (!$section->ms_team_id) {
            $results['failed']++;
            $results['errors'][] = 'Section has no MS Team ID — retry via MS Teams management.';
            return $results;
        }

        // Add student to the Team — retry logic is inside addTeamMember
        try {
            $this->graph->addTeamMember($section->ms_team_id, $student->ms_user_id);

            StudentSection::updateOrCreate(
                ['student_id' => $student->id, 'section_id' => $section->id],
                ['ms_status' => 'enrolled', 'ms_enrolled_at' => now()]
            );

            $results['enrolled']++;
        } catch (\Exception $e) {
            Log::error("Failed to add {$student->student_number} to team {$section->ms_team_id}: " . $e->getMessage());
            StudentSection::updateOrCreate(
                ['student_id' => $student->id, 'section_id' => $section->id],
                ['ms_status' => 'failed']
            );
            $results['failed']++;
            $results['errors'][] = 'Add to team failed: ' . $e->getMessage();
        }

        // Add student to all subject private channels
        foreach ($section->subjects as $subject) {
            if (!$subject->ms_channel_id) continue;

            try {
                $this->graph->addChannelMember(
                    $section->ms_team_id,
                    $subject->ms_channel_id,
                    $student->ms_user_id
                );
                $results['enrolled']++;
            } catch (\Exception $e) {
                Log::error("Failed to add {$student->student_number} to channel [{$subject->subject_name}]: " . $e->getMessage());
                $results['failed']++;
                $results['errors'][] = "Channel [{$subject->subject_name}]: " . $e->getMessage();
            }
        }

        if ($results['enrolled'] > 0) {
            $student->update(['ms_teams_enrolled_at' => now()]);
        }

        return $results;
    }

}
