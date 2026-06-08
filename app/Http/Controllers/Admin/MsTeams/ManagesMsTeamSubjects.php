<?php

namespace App\Http\Controllers\Admin\MsTeams;

use App\Models\Section;
use App\Models\SectionSubject;
use App\Services\MicrosoftGraphService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ManagesMsTeamSubjects
{
    public function storeSubject(Request $request, Section $section)
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
            'teacher_name' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
        ]);

        $channelId = null;
        $graph = new MicrosoftGraphService;

        if ($section->ms_team_id) {
            try {
                $graph->waitForTeam($section->ms_team_id, 10);
                $result = $graph->createPrivateChannel(
                    $section->ms_team_id,
                    $request->subject_name,
                    config('services.microsoft.admin_upn')
                );
                $channelId = $result['id'] ?? null;
                $this->postSubjectWelcomeCard($graph, $section, $channelId, $request);
            } catch (Exception $exception) {
                Log::error("Failed to create channel [{$request->subject_name}]: ".$exception->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Channel creation failed: '.$exception->getMessage(),
                ], 422);
            }
        }

        $subject = SectionSubject::create([
            'section_id' => $section->id,
            'subject_name' => $request->subject_name,
            'teacher_name' => $request->teacher_name,
            'schedule' => $request->schedule,
            'ms_channel_id' => $channelId,
        ]);

        $teacherInvited = false;
        if ($channelId && $request->teacher_upn) {
            try {
                $graph->addTeamOwner($section->ms_team_id, $request->teacher_upn);
                $graph->addChannelOwner($section->ms_team_id, $channelId, $request->teacher_upn);
                $teacherInvited = true;
            } catch (Exception $exception) {
                Log::warning("Could not invite teacher [{$request->teacher_upn}] as owner: ".$exception->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'subject' => $subject,
            'has_channel' => ! is_null($channelId),
            'teacher_invited' => $teacherInvited,
        ]);
    }

    public function updateSubject(Request $request, SectionSubject $subject)
    {
        $request->validate([
            'subject_name' => 'required|string|max:255',
            'teacher_name' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
        ]);

        if ($subject->ms_channel_id && $subject->section->ms_team_id) {
            try {
                (new MicrosoftGraphService)->renameChannel(
                    $subject->section->ms_team_id,
                    $subject->ms_channel_id,
                    $request->subject_name
                );
            } catch (Exception $exception) {
                Log::warning("Could not rename channel [{$subject->ms_channel_id}]: ".$exception->getMessage());
            }
        }

        $subject->update([
            'subject_name' => $request->subject_name,
            'teacher_name' => $request->teacher_name,
            'schedule' => $request->schedule,
        ]);

        return response()->json(['success' => true]);
    }

    public function inviteTeacher(Request $request, SectionSubject $subject)
    {
        $request->validate(['teacher_upn' => 'required|email']);

        $section = $subject->section;
        if (! $section?->ms_team_id || ! $subject->ms_channel_id) {
            return response()->json(['success' => false, 'message' => 'Team or channel not created yet.'], 422);
        }

        try {
            $graph = new MicrosoftGraphService;
            $graph->addTeamOwner($section->ms_team_id, $request->teacher_upn);
            $graph->addChannelOwner($section->ms_team_id, $subject->ms_channel_id, $request->teacher_upn);

            return response()->json([
                'success' => true,
                'message' => "{$request->teacher_upn} added as Owner to Team + Channel.",
            ]);
        } catch (Exception $exception) {
            Log::error("inviteTeacher failed [{$request->teacher_upn}]: ".$exception->getMessage());

            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function destroySubject(SectionSubject $subject)
    {
        $subject->delete();

        return back()->with('success', 'Subject removed.');
    }

    private function postSubjectWelcomeCard(
        MicrosoftGraphService $graph,
        Section $section,
        ?string $channelId,
        Request $request
    ): void {
        if (! $channelId) {
            return;
        }

        try {
            $graph->postWelcomeCard($section->ms_team_id, $channelId, [
                'grade_level' => $section->grade_level,
                'learning_mode' => $section->learning_mode,
                'shift' => $section->shift,
                'gender' => $section->gender,
                'subject' => $request->subject_name,
                'teacher' => $request->teacher_name,
                'schedule' => $request->schedule,
            ]);
        } catch (Exception $exception) {
            Log::warning("Could not post welcome card to channel [{$request->subject_name}]: ".$exception->getMessage());
        }
    }
}
