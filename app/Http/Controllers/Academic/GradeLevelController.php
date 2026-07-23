<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreGradeLevelRequest;
use App\Models\Academic\GradeLevel;
use App\Models\AdminAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GradeLevelController extends Controller
{
    public function index(): View
    {
        $gradeLevels = GradeLevel::query()->orderBy('sort_order')->get();

        return view('admin.academic.grade-levels.index', compact('gradeLevels'));
    }

    public function create(): View
    {
        return view('admin.academic.grade-levels.create');
    }

    public function store(StoreGradeLevelRequest $request): RedirectResponse
    {
        $gradeLevel = GradeLevel::create($request->validated());

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'grade_level_created',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => "Created grade level: {$gradeLevel->name}",
        ]);

        return redirect()->route('admin.academic.grade-levels.index')
            ->with('success', 'Grade level created successfully.');
    }

    public function edit(GradeLevel $gradeLevel): View
    {
        return view('admin.academic.grade-levels.edit', compact('gradeLevel'));
    }

    public function update(StoreGradeLevelRequest $request, GradeLevel $gradeLevel): RedirectResponse
    {
        $gradeLevel->update($request->validated());

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'grade_level_updated',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => "Updated grade level: {$gradeLevel->name}",
        ]);

        return redirect()->route('admin.academic.grade-levels.index')
            ->with('success', 'Grade level updated successfully.');
    }

    public function toggleActive(GradeLevel $gradeLevel): RedirectResponse
    {
        $gradeLevel->update(['is_active' => ! $gradeLevel->is_active]);

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'grade_level_active_toggled',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => "Toggled active status for grade level: {$gradeLevel->name}",
        ]);

        return back()->with('success', 'Grade level status updated.');
    }
}
