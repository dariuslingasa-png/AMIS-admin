<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSchoolYearRequest;
use App\Models\Academic\SchoolYear;
use App\Services\Academic\SchoolYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    public function __construct(
        protected SchoolYearService $schoolYearService
    ) {}

    public function index(): View
    {
        $schoolYears = SchoolYear::query()->orderByDesc('created_at')->get();

        return view('admin.academic.school-years.index', compact('schoolYears'));
    }

    public function create(): View
    {
        return view('admin.academic.school-years.create');
    }

    public function store(StoreSchoolYearRequest $request): RedirectResponse
    {
        $this->schoolYearService->create($request->validated());

        return redirect()->route('admin.academic.school-years.index')
            ->with('success', 'School year created successfully.');
    }

    public function edit(SchoolYear $schoolYear): View
    {
        return view('admin.academic.school-years.edit', compact('schoolYear'));
    }

    public function update(StoreSchoolYearRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $this->schoolYearService->update($schoolYear, $request->validated());

        return redirect()->route('admin.academic.school-years.index')
            ->with('success', 'School year updated successfully.');
    }

    public function toggleActive(SchoolYear $schoolYear): RedirectResponse
    {
        $this->schoolYearService->toggleActive($schoolYear);

        return back()->with('success', 'School year activation status updated.');
    }

    public function toggleStatus(SchoolYear $schoolYear): RedirectResponse
    {
        $this->schoolYearService->toggleStatus($schoolYear);

        return back()->with('success', 'School year status toggled.');
    }
}
