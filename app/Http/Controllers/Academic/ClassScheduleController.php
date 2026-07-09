<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreClassScheduleRequest;
use App\Models\Academic\ClassSchedule;
use App\Models\Academic\Section;
use App\Services\Academic\ClassScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    public function __construct(
        protected ClassScheduleService $classScheduleService
    ) {}

    public function index(Section $section): View
    {
        $schedules = ClassSchedule::query()
            ->where('section_id', $section->id)
            ->ordered()
            ->get();

        return view('admin.academic.schedules.index', compact('section', 'schedules'));
    }

    public function store(StoreClassScheduleRequest $request): RedirectResponse
    {
        $this->classScheduleService->create($request->validated());
        return back()->with('success', 'Class schedule created successfully.');
    }

    public function update(StoreClassScheduleRequest $request, ClassSchedule $schedule): RedirectResponse
    {
        $this->classScheduleService->update($schedule, $request->validated());
        return back()->with('success', 'Class schedule updated successfully.');
    }

    public function destroy(ClassSchedule $schedule): RedirectResponse
    {
        $this->classScheduleService->delete($schedule);
        return back()->with('success', 'Class schedule deleted successfully.');
    }
}
