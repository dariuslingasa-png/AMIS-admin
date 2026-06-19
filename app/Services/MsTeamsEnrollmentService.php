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

        // Find or auto-create section — DB lock prevents duplicate creation
        $section = DB::transaction(function () use ($student, $modeBase, $shift, $gender, &$results) {
            $found = Section::where('grade_level', $student->grade_level)
                ->where('gender', $gender)
                ->where('learning_mode', $modeBase)
                ->where('shift', $shift)
                ->lockForUpdate()
                ->first();

            if ($found) return $found;

            try {
                return $this->autoCreateSection($student, $modeBase, $shift, $gender);
            } catch (\Exception $e) {
                Log::error("Failed to auto-create section for {$student->student_number}: " . $e->getMessage());
                $results['failed']++;
                $results['errors'][] = 'Auto-create section failed: ' . $e->getMessage();
                return null;
            }
        });

        if (!$section) return $results;

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

    /**
     * Auto-create a section + MS Team.
     * Uses the exact same naming convention as AdminMsTeamsController::storeSingle().
     * Posts welcome card to General channel after creation.
     * Creates the DB record even if MS Team API fails (admin can retry).
     */
    private function autoCreateSection(Student $student, string $modeBase, ?string $shift, string $gender): Section
    {
        $grade       = $student->grade_level;
        $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
        $shiftLabel  = $shift ? ($shift === '1st Shift' ? '1st Shift' : '2nd Shift') : 'F2F';

        if ($grade === 'Kinder 1') $prefix = 'K1';
        elseif ($grade === 'Kinder 2') $prefix = 'K2';
        else $prefix = 'G' . str_replace('Grade ', '', $grade);

        $teamName = "{$prefix} [{$genderLabel} & {$shiftLabel}]";

        // Race-condition guard — check once more inside the transaction
        $existing = Section::where('grade_level', $grade)
            ->where('gender', $gender)
            ->where('learning_mode', $modeBase)
            ->where('shift', $shift)
            ->first();

        if ($existing) {
            Log::info("Section already exists (race condition avoided): {$teamName}");
            return $existing;
        }

        Log::info("Auto-creating MS Team: {$teamName}");

        $msTeamId  = null;
        $msTeamUrl = null;

        try {
            $result    = $this->graph->createTeam($teamName, "AMIS auto-created team for {$grade}");
            $msTeamId  = $result['id'];
            $msTeamUrl = "https://teams.microsoft.com/l/team/{$msTeamId}";

            // Wait for team to be ready in Azure (same as storeSingle)
            $this->graph->waitForTeam($msTeamId);

            // Post welcome card to General channel (same as storeSingle)
            $generalChannelId = $this->graph->getGeneralChannelId($msTeamId);
            if ($generalChannelId) {
                $this->graph->postWelcomeCard($msTeamId, $generalChannelId, [
                    'grade_level'   => $grade,
                    'learning_mode' => $modeBase,
                    'shift'         => $shift,
                    'gender'        => $gender,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("MS Team creation failed [{$teamName}]: " . $e->getMessage());
            // Fall through — still save the section so admin can retry team creation
        }

        $section = Section::create([
            'name'          => null,
            'grade_level'   => $grade,
            'learning_mode' => $modeBase,
            'shift'         => $shift,
            'gender'        => $gender,
            'ms_team_id'    => $msTeamId,
            'ms_team_url'   => $msTeamUrl,
        ]);

        Log::info("Section {$section->id} created" . ($msTeamId ? " with MS Team {$msTeamId}" : " (no MS Team yet — retry via admin)"));

        return $section;
    }
}
