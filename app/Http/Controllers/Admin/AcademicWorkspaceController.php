<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Academic\AcademicWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcademicWorkspaceController extends Controller
{
    public function __construct(private readonly AcademicWorkspaceService $workspace) {}

    public function dashboard()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.dashboard', $this->workspace->dashboard());
    }

    public function scheduleCopy(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.schedule-copy', $this->workspace->builder($request));
    }

    public function builder(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.builder', $this->workspace->builder($request));
    }

    public function teachers(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.teachers', $this->workspace->teachers($request));
    }

    public function subjects(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.subjects', $this->workspace->subjects($request));
    }

    public function sections(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.sections', $this->workspace->sections($request));
    }

    public function rooms(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.rooms', $this->workspace->rooms($request));
    }

    public function workload(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.workload', $this->workspace->workload($request));
    }

    public function reports(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.workspace.reports', $this->workspace->reports($request));
    }

    public function export(Request $request)
    {
        Gate::authorize('manage-academic');
        $payload = $this->workspace->reports($request);
        $filename = 'academic-schedule-'.$payload['type'].'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($payload) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Day', 'Start', 'End', 'Section', 'Subject', 'Teacher', 'Room', 'School Year']);
            foreach ($payload['schedules'] as $schedule) {
                fputcsv($handle, [
                    $schedule->day,
                    substr($schedule->start_time, 0, 5),
                    substr($schedule->end_time, 0, 5),
                    $schedule->section?->section_title,
                    $schedule->subject_name,
                    $schedule->teacher_display,
                    $schedule->room?->name,
                    $schedule->school_year,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
