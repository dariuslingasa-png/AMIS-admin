<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicSectionRequest;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AcademicSectionController extends Controller
{
    public function store(AcademicSectionRequest $request)
    {
        DB::transaction(fn () => Section::create($request->validated()));

        return back()->with('status', 'Section created successfully.');
    }

    public function update(AcademicSectionRequest $request, Section $section)
    {
        DB::transaction(fn () => $section->update($request->validated()));

        return back()->with('status', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        Gate::authorize('manage-academic');
        DB::transaction(fn () => $section->update(['academic_status' => 'inactive']));

        return back()->with('status', 'Section archived. Students and schedules were preserved.');
    }
}
