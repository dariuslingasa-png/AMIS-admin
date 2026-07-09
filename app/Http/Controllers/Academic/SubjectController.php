<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSubjectRequest;
use App\Models\Academic\Subject;
use App\Services\Academic\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(
        protected SubjectService $subjectService
    ) {}

    public function index(): View
    {
        $subjects = Subject::query()->orderByDesc('created_at')->get();
        return view('admin.academic.subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('admin.academic.subjects.create');
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $this->subjectService->create($request->validated());
        return redirect()->route('admin.academic.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.academic.subjects.edit', compact('subject'));
    }

    public function update(StoreSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->subjectService->update($subject, $request->validated());
        return redirect()->route('admin.academic.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function toggleStatus(Subject $subject): RedirectResponse
    {
        $this->subjectService->toggleStatus($subject);
        return back()->with('success', 'Subject status updated.');
    }
}
