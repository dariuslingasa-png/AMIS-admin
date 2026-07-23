<?php

namespace App\Console\Commands;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;

class SyncTeamMemberships extends Command
{
    protected $signature = 'ms-teams:sync-memberships {--apply : Apply changes to the database}';

    protected $description = 'Sync student section memberships from Microsoft Graph Team members to local database';

    public function handle(MicrosoftGraphService $graph): int
    {
        $apply = $this->option('apply');
        $this->info($apply ? 'RUNNING IN APPLY MODE - Writing changes to database...' : 'RUNNING IN DRY-RUN MODE (Default) - No database writes.');
        $this->newLine();

        $sections = Section::whereNotNull('ms_team_id')->get()->filter(function ($section) {
            return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $section->ms_team_id);
        });

        $this->info('Found '.$sections->count().' sections with valid MS Team IDs.');

        $totalUpdated = 0;

        foreach ($sections as $section) {
            $this->info("Checking Section: {$section->grade_level} - {$section->gender} ({$section->shift}) [Team: {$section->ms_team_id}]");

            try {
                $members = $graph->listTeamMembers($section->ms_team_id);
            } catch (\Exception $e) {
                $this->error('  Failed to fetch members: '.$e->getMessage());

                continue;
            }

            // Extract student MS IDs and emails (non-owner members)
            $studentMsIds = [];
            $studentEmails = [];
            foreach ($members as $member) {
                $roles = $member['roles'] ?? [];
                if (! in_array('owner', $roles, true)) {
                    if (! empty($member['userId'])) {
                        $studentMsIds[] = $member['userId'];
                    }
                    if (! empty($member['email'])) {
                        $studentEmails[] = strtolower($member['email']);
                    }
                }
            }

            $this->line('  Graph members: '.count($studentMsIds));

            // Find students in database that have these MS IDs or emails
            $studentsInGraph = Student::whereIn('ms_user_id', $studentMsIds)
                ->orWhere(function ($q) use ($studentEmails) {
                    $q->whereIn('school_email', $studentEmails)
                        ->orWhereIn('ms_email', $studentEmails);
                })
                ->get();
            $studentsInGraphIds = $studentsInGraph->pluck('id')->toArray();

            // Also keep track of found ms_user_ids and emails to verify local enrolled students
            $studentsInGraphMsIds = $studentsInGraph->pluck('ms_user_id')->filter()->toArray();
            $studentsInGraphEmails = $studentsInGraph->map(fn ($s) => strtolower($s->school_email ?? $s->ms_email ?? ''))->filter()->toArray();

            // 1. Update/Create student_sections for students in the MS Team
            foreach ($studentsInGraph as $student) {
                $studentSection = StudentSection::where('student_id', $student->id)
                    ->where('section_id', $section->id)
                    ->first();

                if (! $studentSection) {
                    $this->warn("  [MISMATCH] Student {$student->student_number} ({$student->ms_email}) is in MS Team but NOT in DB section!");
                    if ($apply) {
                        // Check if they are already in another section and remove them
                        StudentSection::where('student_id', $student->id)->delete();
                        StudentSection::create([
                            'student_id' => $student->id,
                            'section_id' => $section->id,
                            'ms_status' => 'enrolled',
                            'ms_enrolled_at' => now(),
                        ]);
                        $totalUpdated++;
                    }
                } elseif ($studentSection->ms_status !== 'enrolled') {
                    $this->info("  [UPDATE] Student {$student->student_number} marked as {$studentSection->ms_status} locally, but enrolled in MS Team. Updating local status to enrolled.");
                    if ($apply) {
                        $studentSection->update([
                            'ms_status' => 'enrolled',
                            'ms_enrolled_at' => $studentSection->ms_enrolled_at ?? now(),
                        ]);
                        $totalUpdated++;
                    }
                }
            }

            // 2. Mark students as pending if they are enrolled locally but NOT in the MS Team
            $localEnrolled = StudentSection::where('section_id', $section->id)
                ->where('ms_status', 'enrolled')
                ->get();

            foreach ($localEnrolled as $dbSec) {
                $student = $dbSec->student;
                $isEnrolledInGraph = false;

                if ($student) {
                    $studentEmail = strtolower($student->school_email ?? $student->ms_email ?? '');
                    if ($student->ms_user_id && in_array($student->ms_user_id, $studentMsIds)) {
                        $isEnrolledInGraph = true;
                    } elseif ($studentEmail && in_array($studentEmail, $studentEmails)) {
                        $isEnrolledInGraph = true;
                    }
                }

                if (! $isEnrolledInGraph) {
                    $studentNum = $student ? $student->student_number : 'Unknown';
                    $this->warn("  [MISMATCH] Student {$studentNum} marked as enrolled locally but NOT in MS Team!");
                    if ($apply) {
                        $dbSec->update(['ms_status' => 'pending']);
                        $totalUpdated++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Sync completed. Total records updated: {$totalUpdated}");

        return Command::SUCCESS;
    }
}
