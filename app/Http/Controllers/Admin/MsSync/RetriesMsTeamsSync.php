<?php

namespace App\Http\Controllers\Admin\MsSync;

use App\Models\Student;
use App\Models\StudentSection;
use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsEnrollmentService;
use Exception;
use Illuminate\Support\Facades\Log;

trait RetriesMsTeamsSync
{
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

    public function syncStudent(Student $student)
    {
        if (! $student->ms_user_id) {
            return back()->withErrors(['error' => 'Student has no Microsoft account.']);
        }

        try {
            $result = (new MsTeamsEnrollmentService(new MicrosoftGraphService))->enrollStudent($student);
            $message = "Synced {$student->student_number}: {$result['enrolled']} enrolled.";

            if ($result['failed'] > 0) {
                $message .= " {$result['failed']} failed.";
            }

            return back()->with('success', $message);
        } catch (Exception $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }
}
