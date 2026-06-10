<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Student;
use App\Models\StudentSection;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminMsTeamsController extends Controller
{
    public function index(Request $request)
    {
        $sections = Section::withCount(['students as enrolled_count' => fn($q) => $q->where('ms_status', 'enrolled')])
            ->withCount('subjects')
            ->orderBy('grade_level')
            ->orderBy('learning_mode')
            ->orderBy('shift')
            ->orderBy('gender')
            ->get();

        $stats = [
            'total_sections' => $sections->count(),
            'with_team'      => $sections->whereNotNull('ms_team_id')->count(),
            'without_team'   => $sections->whereNull('ms_team_id')->count(),
            'total_enrolled' => StudentSection::where('ms_status', 'enrolled')->count(),
            'total_failed'   => StudentSection::where('ms_status', 'failed')->count(),
        ];

        return view('admin.ms-teams.index', compact('sections', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grade_level'   => 'required|string',
            'learning_mode' => 'required|string',
            'name'          => 'nullable|string|max:255',
            'school_year'   => 'required|string',
            // Flexible: arrays of shifts + genders
            'shifts'        => 'nullable|array',
            'shifts.*'      => 'string',
            'genders'       => 'nullable|array',
            'genders.*'     => 'in:male,female',
            // F2F: single gender
            'gender_single' => 'nullable|in:male,female',
        ]);

        $isFlexible  = $request->learning_mode === 'Flexible Online Learning';
        $sectionName = $request->name ?: null;
        $graph       = new MicrosoftGraphService();
        $created     = 0;

        if ($isFlexible) {
            $shifts  = $request->input('shifts', []);
            $genders = $request->input('genders', []);

            if (empty($shifts) || empty($genders)) {
                return back()->withErrors(['ms' => 'Select at least one shift and one gender.'])->withInput();
            }

            foreach ($shifts as $shift) {
                foreach ($genders as $gender) {
                    $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
                    $teamName    = $request->grade_level
                        . ($sectionName ? " - {$sectionName}" : '')
                        . " {$shift} {$genderLabel} {$request->school_year}";

                    $msTeamId = null; $msTeamUrl = null;
                    try {
                        $result   = $graph->createTeam($teamName);
                        $msTeamId = $graph->waitForTeam($result['id']);
                        $msTeamUrl = "https://teams.microsoft.com/l/team/{$msTeamId}";
                    } catch (\Exception $e) {
                        Log::error("Failed to create MS Team [{$teamName}]: " . $e->getMessage());
                    }

                    Section::create([
                        'name'          => $sectionName,
                        'grade_level'   => $request->grade_level,
                        'learning_mode' => $request->learning_mode,
                        'shift'         => $shift,
                        'gender'        => $gender,
                        'school_year'   => $request->school_year,
                        'ms_team_id'    => $msTeamId,
                        'ms_team_url'   => $msTeamUrl,
                    ]);
                    $created++;
                }
            }
        } else {
            // Face-to-Face — single section
            $gender      = $request->gender_single ?? 'male';
            $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
            $teamName    = $request->grade_level
                . ($sectionName ? " - {$sectionName}" : '')
                . " F2F {$genderLabel} {$request->school_year}";

            $msTeamId = null; $msTeamUrl = null;
            try {
                $result   = $graph->createTeam($teamName);
                $msTeamId = $graph->waitForTeam($result['id']);
                $msTeamUrl = "https://teams.microsoft.com/l/team/{$msTeamId}";
            } catch (\Exception $e) {
                Log::error("Failed to create MS Team [{$teamName}]: " . $e->getMessage());
            }

            Section::create([
                'name'          => $sectionName,
                'grade_level'   => $request->grade_level,
                'learning_mode' => $request->learning_mode,
                'shift'         => null,
                'gender'        => $gender,
                'school_year'   => $request->school_year,
                'ms_team_id'    => $msTeamId,
                'ms_team_url'   => $msTeamUrl,
            ]);
            $created = 1;
        }

        return redirect()->route('admin.ms-teams.index')
            ->with('success', "{$created} section(s) created for {$request->grade_level}.");
    }

    /**
     * Create a single section + MS Team via AJAX (called one at a time from the progress modal).
     */
    public function storeSingle(Request $request)
    {
        $request->validate([
            'grade_level'   => 'required|string',
            'learning_mode' => 'required|string',
            'shift'         => 'nullable|string',
            'gender'        => 'required|in:male,female',
            'name'          => 'nullable|string|max:255',
        ]);

        $sectionName = $request->name ?: null;
        $shift       = $request->learning_mode === 'Flexible Online Learning' ? $request->shift : null;
        $genderLabel = $request->gender === 'male' ? 'Boys' : 'Girls';

        // Grade prefix: Kinder 1 → K1, Grade 2 → G2, etc.
        $grade = $request->grade_level;
        if ($grade === 'Kinder 1') $prefix = 'K1';
        elseif ($grade === 'Kinder 2') $prefix = 'K2';
        else $prefix = 'G' . str_replace('Grade ', '', $grade);

        $shiftLabel = $shift ? ($shift === '1st Shift' ? '1st Shift' : '2nd Shift') : 'F2F';
        $namePart   = $sectionName ? " - {$sectionName}" : '';
        $teamName   = "{$prefix}{$namePart} [{$genderLabel} & {$shiftLabel}]";

        $msTeamId = null; $msTeamUrl = null;
        try {
            $graph    = new MicrosoftGraphService();
            $result   = $graph->createTeam($teamName);
            $msTeamId  = $result['id'];
            $msTeamUrl = "https://teams.microsoft.com/l/team/{$msTeamId}";

            // Wait for team to be ready, then post welcome card to General channel
            $graph->waitForTeam($msTeamId);
            $generalChannelId = $graph->getGeneralChannelId($msTeamId);
            if ($generalChannelId) {
                $graph->postWelcomeCard($msTeamId, $generalChannelId, [
                    'grade_level'   => $request->grade_level,
                    'learning_mode' => $request->learning_mode,
                    'shift'         => $shift,
                    'gender'        => $request->gender,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("storeSingle: Failed to create MS Team [{$teamName}]: " . $e->getMessage());
        }

        Section::create([
            'name'          => $sectionName,
            'grade_level'   => $request->grade_level,
            'learning_mode' => $request->learning_mode,
            'shift'         => $shift,
            'gender'        => $request->gender,
            'ms_team_id'    => $msTeamId,
            'ms_team_url'   => $msTeamUrl,
        ]);

        return response()->json([
            'success'   => true,
            'team_name' => $teamName,
            'has_team'  => !is_null($msTeamId),
        ]);
    }

    /**
     * Retry creating the MS Team for a section that failed previously.
     */
    public function retryTeam(Section $section)
    {
        $grade = $section->grade_level;
        if ($grade === 'Kinder 1') $prefix = 'K1';
        elseif ($grade === 'Kinder 2') $prefix = 'K2';
        else $prefix = 'G' . str_replace('Grade ', '', $grade);

        $genderLabel = $section->gender === 'male' ? 'Boys' : 'Girls';
        $shiftLabel  = $section->shift ? ($section->shift === '1st Shift' ? '1st Shift' : '2nd Shift') : 'F2F';
        $namePart    = $section->name ? " - {$section->name}" : '';
        $teamName    = "{$prefix}{$namePart} [{$genderLabel} & {$shiftLabel}]";

        try {
            $graph    = new MicrosoftGraphService();
            $result   = $graph->createTeam($teamName);
            $msTeamId = $result['id'];
            $section->update([
                'ms_team_id'  => $msTeamId,
                'ms_team_url' => "https://teams.microsoft.com/l/team/{$msTeamId}",
            ]);

            // Auto-invite active advisor if assigned
            $advisor = $section->grade_advisor;
            if ($advisor && !empty($advisor->teacher_email)) {
                try {
                    $graph->addTeamOwner($msTeamId, $advisor->teacher_email);
                } catch (\Exception $e) {
                    Log::warning("Could not add advisor {$advisor->teacher_email} to Team: " . $e->getMessage());
                }
            }

            return back()->with('success', "MS Team created: {$teamName}");
        } catch (\Exception $e) {
            Log::error("retryTeam failed [{$teamName}]: " . $e->getMessage());
            return back()->withErrors(['ms' => 'Failed: ' . $e->getMessage()]);
        }
    }

    public function show(Section $section)
    {
        $section->load('subjects');
        $enrollments = StudentSection::where('section_id', $section->id)
            ->with('student.applicant')
            ->latest()
            ->get();

        $teachers = \App\Models\User::where('role', 'teacher')
            ->orderBy('name')
            ->get()
            ->map(function($user) {
                $loadCount = \App\Models\SectionSubject::where('teacher_name', $user->name)->count();
                $user->load_count = $loadCount;
                return $user;
            });

        return view('admin.ms-teams.show', compact('section', 'enrollments', 'teachers'));
    }

    /**
     * Update a section's display name (also renames the MS Team).
     */
    public function update(Request $request, Section $section)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $sectionName = $request->name ?: null;
        $genderLabel = $section->gender === 'male' ? 'Boys' : 'Girls';

        $grade = $section->grade_level;
        if ($grade === 'Kinder 1') $prefix = 'K1';
        elseif ($grade === 'Kinder 2') $prefix = 'K2';
        else $prefix = 'G' . str_replace('Grade ', '', $grade);

        $shiftLabel  = $section->shift ? ($section->shift === '1st Shift' ? '1st Shift' : '2nd Shift') : 'F2F';
        $namePart    = $sectionName ? " - {$sectionName}" : '';
        $newTeamName = "{$prefix}{$namePart} [{$genderLabel} & {$shiftLabel}]";

        if ($section->ms_team_id) {
            try {
                $graph = new MicrosoftGraphService();
                $graph->renameTeam($section->ms_team_id, $newTeamName);
            } catch (\Exception $e) {
                Log::warning("Could not rename MS Team [{$section->ms_team_id}]: " . $e->getMessage());
            }
        }

        $section->update(['name' => $sectionName]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a section (and optionally its MS Team record).
     */
    public function destroy(Section $section)
    {
        $msTeamId = $section->ms_team_id;

        // Delete related subjects first
        $section->subjects()->delete();
        $section->delete();

        // Also delete the MS Team from Azure if it exists
        if ($msTeamId) {
            try {
                $graph = new MicrosoftGraphService();
                $graph->deleteTeam($msTeamId);
            } catch (\Exception $e) {
                Log::warning("Could not delete MS Team [{$msTeamId}] from Azure: " . $e->getMessage());
                // Don't block — DB record is already gone
            }
        }

        return redirect()->route('admin.ms-teams.index')
            ->with('success', "Section \"{$section->grade_level}\" deleted." . ($msTeamId ? ' MS Team also removed from Azure.' : ''));
    }

    /**
     * Store a new subject (private channel) for a section — via AJAX modal.
     */
    public function storeSubject(Request $request, Section $section)
    {
        $request->validate([
            'subject_name'   => 'required|string|max:255',
            'teacher_name'   => 'nullable|string|max:255',
            'schedule'       => 'nullable|string|max:255',
            'teacher_upn'    => 'nullable|email',
            'create_channel' => 'nullable|boolean',
        ]);

        $createChannel = $request->input('create_channel', true);

        // Find existing or create new
        $subject = SectionSubject::where('section_id', $section->id)
            ->where('subject_name', $request->subject_name)
            ->first();

        $channelId = $subject ? $subject->ms_channel_id : null;
        $teacherInvited = false;

        // Create private channel in MS Teams if team exists, channel is not created yet, and create_channel is true
        if ($section->ms_team_id && !$channelId && $createChannel) {
            try {
                $graph    = new MicrosoftGraphService();
                $adminUpn = config('services.microsoft.admin_upn');

                // Team may still be provisioning — wait up to 10s before attempting channel creation
                $graph->waitForTeam($section->ms_team_id, 10);

                $result    = $graph->createPrivateChannel(
                    $section->ms_team_id,
                    $request->subject_name,
                    $adminUpn
                );
                $channelId = $result['id'] ?? null;

                // Post a welcome card to the new private channel
                if ($channelId) {
                    try {
                        $graph->postWelcomeCard($section->ms_team_id, $channelId, [
                            'grade_level'   => $section->grade_level,
                            'learning_mode' => $section->learning_mode,
                            'shift'         => $section->shift,
                            'gender'        => $section->gender,
                            'subject'       => $request->subject_name,
                            'teacher'       => $request->teacher_name,
                            'schedule'      => $request->schedule,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("Could not post welcome card to channel [{$request->subject_name}]: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to create channel [{$request->subject_name}]: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Channel creation failed: ' . $e->getMessage(),
                ], 422);
            }
        }

        if ($subject) {
            $subject->update([
                'teacher_name'  => $request->teacher_name ?? $subject->teacher_name,
                'schedule'      => $request->schedule ?? $subject->schedule,
                'ms_channel_id' => $channelId ?? $subject->ms_channel_id,
            ]);
        } else {
            $subject = SectionSubject::create([
                'section_id'    => $section->id,
                'subject_name'  => $request->subject_name,
                'teacher_name'  => $request->teacher_name,
                'schedule'      => $request->schedule,
                'ms_channel_id' => $channelId,
            ]);
        }

        // If a teacher UPN is provided and channel is created, invite teacher as Owner
        $activeChannelId = $channelId ?? $subject->ms_channel_id;
        if ($activeChannelId && $request->teacher_upn) {
            try {
                $graph = new MicrosoftGraphService();
                $graph->addTeamOwner($section->ms_team_id, $request->teacher_upn);
                $graph->addChannelOwner($section->ms_team_id, $activeChannelId, $request->teacher_upn);
                $teacherInvited = true;
            } catch (\Exception $e) {
                Log::warning("Could not invite teacher [{$request->teacher_upn}] as owner: " . $e->getMessage());
            }
        }

        return response()->json([
            'success'         => true,
            'subject'         => $subject,
            'has_channel'     => !is_null($activeChannelId),
            'teacher_invited' => $teacherInvited,
        ]);
    }

    /**
     * Update a subject's name and teacher (also renames the MS Teams channel if it exists).
     */
    public function updateSubject(Request $request, SectionSubject $subject)
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
            'teacher_name' => 'nullable|string|max:255',
            'schedule'     => 'nullable|string|max:255',
        ]);

        // Rename the channel in MS Teams if it exists
        if ($subject->ms_channel_id && $subject->section->ms_team_id) {
            try {
                $graph = new MicrosoftGraphService();
                $graph->renameChannel(
                    $subject->section->ms_team_id,
                    $subject->ms_channel_id,
                    $request->subject_name
                );
            } catch (\Exception $e) {
                Log::warning("Could not rename channel [{$subject->ms_channel_id}]: " . $e->getMessage());
            }
        }

        $subject->update([
            'subject_name' => $request->subject_name,
            'teacher_name' => $request->teacher_name,
            'schedule'     => $request->schedule,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Invite a teacher as Owner to a subject's Team + Channel.
     */
    public function inviteTeacher(Request $request, SectionSubject $subject)
    {
        $request->validate([
            'teacher_upn' => 'required|email',
        ]);

        $section = $subject->section;
        if (!$section?->ms_team_id || !$subject->ms_channel_id) {
            return response()->json(['success' => false, 'message' => 'Team or channel not created yet.'], 422);
        }

        try {
            $graph = new MicrosoftGraphService();
            $graph->addTeamOwner($section->ms_team_id, $request->teacher_upn);
            $graph->addChannelOwner($section->ms_team_id, $subject->ms_channel_id, $request->teacher_upn);

            return response()->json([
                'success' => true,
                'message' => "{$request->teacher_upn} added as Owner to Team + Channel.",
            ]);
        } catch (\Exception $e) {
            Log::error("inviteTeacher failed [{$request->teacher_upn}]: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a subject/channel.
     */
    public function destroySubject(SectionSubject $subject)
    {
        $subject->delete();
        return back()->with('success', 'Subject removed.');
    }

    /**
     * Fix admin access to all private channels.
     */
    public function fixGuestStudents()
    {
        $students = \App\Models\Student::whereNotNull('ms_user_id')->get();
        $fixed = 0; $failed = 0;
        $graph = new MicrosoftGraphService();

        foreach ($students as $student) {
            try {
                $graph->convertGuestToMember($student->ms_user_id);
                $fixed++;
                Log::info("Converted {$student->student_number} from Guest to Member");
            } catch (\Exception $e) {
                Log::warning("Could not convert {$student->student_number}: " . $e->getMessage());
                $failed++;
            }
            sleep(1);
        }

        return back()->with('success', "Fixed {$fixed} student(s) from Guest → Member. {$failed} failed.");
    }

    public function fixAdminAccess()
    {
        try {
            $graph   = new MicrosoftGraphService();
            $results = $graph->addAdminToAllChannels();
            return back()->with('success',
                "Admin access fixed: {$results['added']} added, {$results['skipped']} already member, {$results['failed']} failed."
            );
        } catch (\Exception $e) {
            return back()->withErrors(['ms' => 'Failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Add the current admin UPN as owner to all existing MS Teams.
     */
    public function fixTeamOwnership()
    {
        $sections = Section::whereNotNull('ms_team_id')->get();
        $added = 0; $failed = 0;

        $graph = new MicrosoftGraphService();
        foreach ($sections as $section) {
            try {
                $graph->addAdminAsTeamOwner($section->ms_team_id);
                $added++;
            } catch (\Exception $e) {
                Log::warning("fixTeamOwnership failed for [{$section->ms_team_id}]: " . $e->getMessage());
                $failed++;
            }
            sleep(1);
        }

        return back()->with('success', "Team ownership fixed: {$added} added, {$failed} failed.");
    }

    /**
     * Manually enroll a student into their section team.
     */
    public function enrollStudent(Request $request, Student $student)
    {
        if (!$student->ms_user_id) {
            return back()->withErrors(['ms' => 'Student has no Microsoft account yet.']);
        }
        try {
            $service = new MsTeamsEnrollmentService(new MicrosoftGraphService());
            $result  = $service->enrollStudent($student);
            $msg = "Enrolled in {$result['enrolled']} team/channel(s).";
            if ($result['failed'] > 0) $msg .= " {$result['failed']} failed — check logs.";
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors(['ms' => $e->getMessage()]);
        }
    }

    public function syncAdvisor(Request $request, Section $section)
    {
        $advisor = $section->grade_advisor;
        if (!$advisor || empty($advisor->teacher_email)) {
            return response()->json(['success' => false, 'message' => 'No active advisor assigned or advisor email not found.'], 422);
        }

        if (!$section->ms_team_id) {
            return response()->json(['success' => false, 'message' => 'MS Team not created yet.'], 422);
        }

        try {
            $graph = new MicrosoftGraphService();
            $graph->addTeamOwner($section->ms_team_id, $advisor->teacher_email);
            return response()->json(['success' => true, 'message' => 'Advisor successfully synced as Team Owner.']);
        } catch (\Exception $e) {
            Log::error("syncAdvisor failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
