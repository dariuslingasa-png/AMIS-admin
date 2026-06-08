<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\ClassScheduleRequest;
use App\Models\SectionSubject;
use App\Services\Admin\Academic\ClassScheduleService;
use Illuminate\Support\Facades\Gate;

class AdminClassScheduleController extends Controller
{
    public function __construct(private readonly ClassScheduleService $schedules)
    {
    }

    public function index()
    {
        Gate::authorize('manage-academic');

        $sections = $this->schedules->sections();
        $advisories = $this->schedules->advisories();
        $advisoryByGrade = $advisories->keyBy('grade_level');
        $teachers = $advisories->pluck('teacher')->unique()->values();
        $schedulesBySection = $this->schedules->schedulesBySection($sections);
        $days = $this->schedules->days();
        $timeOptions = $this->schedules->timeOptions();

        return view('admin.academic.schedules', compact(
            'sections',
            'teachers',
            'advisories',
            'advisoryByGrade',
            'schedulesBySection',
            'days',
            'timeOptions'
        ));
    }

    public function store(ClassScheduleRequest $request)
    {
        $this->schedules->store($request->validated());

        return back()
            ->with('status', 'Class schedule saved.')
            ->with('schedule_workspace', 'schedule');
    }

    public function update(ClassScheduleRequest $request, SectionSubject $schedule)
    {
        $this->schedules->update($schedule, $request->validated());

        return back()
            ->with('status', 'Class schedule updated.')
            ->with('schedule_workspace', 'schedule');
    }

    public function destroy(SectionSubject $schedule)
    {
        Gate::authorize('manage-academic');

        $schedule->delete();

        return back()
            ->with('status', 'Class schedule deleted.')
            ->with('schedule_workspace', 'schedule');
    }

}
