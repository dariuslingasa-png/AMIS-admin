<?php

namespace App\Http\Controllers\Academic;

use App\Actions\Academic\AssignTeacherToSection;
use App\Http\Controllers\Controller;
use App\Models\Academic\Advisory;
use App\Models\Academic\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvisoryController extends Controller
{
    public function index(): View
    {
        $advisories = Advisory::query()->with('section')->orderByDesc('assigned_at')->get();

        return view('admin.academic.advisory.index', compact('advisories'));
    }

    public function assignForm(Section $section): View
    {
        return view('admin.academic.advisory.assign', compact('section'));
    }

    public function assign(Request $request, Section $section, AssignTeacherToSection $action): RedirectResponse
    {
        $request->validate([
            'teacher_key' => 'required|string',
            'teacher_name' => 'required|string',
            'teacher_email' => 'required|email',
            'school_year' => 'required|string',
        ]);

        $action->execute(
            $section,
            $request->only(['teacher_key', 'teacher_name', 'teacher_email']),
            $request->input('school_year')
        );

        return redirect()->route('admin.academic.class-advisory')
            ->with('success', 'Teacher assigned as adviser successfully.');
    }
}
