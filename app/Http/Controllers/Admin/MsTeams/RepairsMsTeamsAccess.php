<?php

namespace App\Http\Controllers\Admin\MsTeams;

use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait RepairsMsTeamsAccess
{
    public function fixGuestStudents()
    {
        $students = Student::whereNotNull('ms_user_id')->get();
        $fixed = 0;
        $failed = 0;
        $graph = new MicrosoftGraphService;

        foreach ($students as $student) {
            try {
                $graph->convertGuestToMember($student->ms_user_id);
                $fixed++;
                Log::info("Converted {$student->student_number} from Guest to Member");
            } catch (Exception $exception) {
                Log::warning("Could not convert {$student->student_number}: ".$exception->getMessage());
                $failed++;
            }

            sleep(1);
        }

        return back()->with('success', "Fixed {$fixed} student(s) from Guest -> Member. {$failed} failed.");
    }

    public function fixAdminAccess()
    {
        try {
            $results = (new MicrosoftGraphService)->addAdminToAllChannels();

            return back()->with(
                'success',
                "Admin access fixed: {$results['added']} added, {$results['skipped']} already member, {$results['failed']} failed."
            );
        } catch (Exception $exception) {
            return back()->withErrors(['ms' => 'Failed: '.$exception->getMessage()]);
        }
    }

    public function fixTeamOwnership()
    {
        $sections = Section::whereNotNull('ms_team_id')->get();
        $added = 0;
        $failed = 0;
        $graph = new MicrosoftGraphService;

        foreach ($sections as $section) {
            try {
                $graph->addAdminAsTeamOwner($section->ms_team_id);
                $added++;
            } catch (Exception $exception) {
                Log::warning("fixTeamOwnership failed for [{$section->ms_team_id}]: ".$exception->getMessage());
                $failed++;
            }

            sleep(1);
        }

        return back()->with('success', "Team ownership fixed: {$added} added, {$failed} failed.");
    }

    public function enrollStudent(Request $request, Student $student)
    {
        if (! $student->ms_user_id) {
            return back()->withErrors(['ms' => 'Student has no Microsoft account yet.']);
        }

        try {
            $service = new MsTeamsEnrollmentService(new MicrosoftGraphService);
            $result = $service->enrollStudent($student);
            $message = "Enrolled in {$result['enrolled']} team/channel(s).";

            if ($result['failed'] > 0) {
                $message .= " {$result['failed']} failed - check logs.";
            }

            return back()->with('success', $message);
        } catch (Exception $exception) {
            return back()->withErrors(['ms' => $exception->getMessage()]);
        }
    }

    public function retryFailed()
    {
        $failed = StudentSection::where('ms_status', 'failed')->with('student')->get();
        $service = new MsTeamsEnrollmentService(new MicrosoftGraphService);
        $ok = 0;
        $err = 0;

        foreach ($failed as $studentSection) {
            try {
                $result = $service->enrollStudent($studentSection->student);
                $result['enrolled'] > 0 ? $ok++ : $err++;
            } catch (Exception $exception) {
                Log::error("Retry failed for {$studentSection->student->student_number}: ".$exception->getMessage());
                $err++;
            }
        }

        return back()->with('success', "Retry complete: {$ok} enrolled, {$err} still failed.");
    }
}
