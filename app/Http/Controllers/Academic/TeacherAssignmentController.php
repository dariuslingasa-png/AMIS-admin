<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject;
use App\Models\Academic\TeacherAssignment;
use App\Models\AdminAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function index(Subject $subject): View
    {
        $assignments = TeacherAssignment::query()
            ->where('subject_id', $subject->id)
            ->orderByDesc('assigned_at')
            ->get();

        return view('admin.academic.teachers.assignments', compact('subject', 'assignments'));
    }

    public function assign(Request $request, Subject $subject): RedirectResponse
    {
        $request->validate([
            'teacher_key' => 'required|string',
            'teacher_name' => 'required|string',
            'teacher_email' => 'required|email',
        ]);

        $assignment = TeacherAssignment::create([
            'teacher_key' => $request->input('teacher_key'),
            'teacher_name' => $request->input('teacher_name'),
            'teacher_email' => $request->input('teacher_email'),
            'subject_id' => $subject->id,
            'status' => 'active',
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'teacher_assigned_to_subject',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => "Assigned teacher {$assignment->teacher_name} to subject {$subject->name}",
        ]);

        return back()->with('success', 'Teacher assigned successfully.');
    }

    public function endAssignment(TeacherAssignment $assignment): RedirectResponse
    {
        $assignment->update([
            'status' => 'inactive',
            'ended_at' => now(),
        ]);

        AdminAuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'teacher_subject_assignment_ended',
            'email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => true,
            'message' => "Ended assignment for teacher {$assignment->teacher_name} on subject ID {$assignment->subject_id}",
        ]);

        return back()->with('success', 'Assignment ended.');
    }
}
