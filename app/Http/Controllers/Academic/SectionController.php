<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSectionRequest;
use App\Models\Academic\Section;
use App\Services\Academic\SectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(
        protected SectionService $sectionService
    ) {}

    public function index(): View
    {
        $sections = Section::query()->orderByDesc('created_at')->get();

        return view('admin.academic.sections.index', compact('sections'));
    }

    public function create(): View
    {
        return view('admin.academic.sections.create');
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $this->sectionService->create($request->validated());

        return redirect()->route('admin.academic.sections.index')
            ->with('success', 'Section created successfully.');
    }

    public function edit(Section $section): View
    {
        return view('admin.academic.sections.edit', compact('section'));
    }

    public function update(StoreSectionRequest $request, Section $section): RedirectResponse
    {
        $this->sectionService->update($section, $request->validated());

        return redirect()->route('admin.academic.sections.index')
            ->with('success', 'Section updated successfully.');
    }

    public function toggleStatus(Section $section): RedirectResponse
    {
        $this->sectionService->toggleStatus($section);

        return back()->with('success', 'Section status updated.');
    }
}
