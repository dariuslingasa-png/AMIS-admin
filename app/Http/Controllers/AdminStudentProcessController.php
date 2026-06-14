<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class AdminStudentProcessController extends Controller
{
    public function accounts(Request $request)
    {
        $statusFilter = $request->input('status_filter', 'all');

        $query = Student::with(['applicant.payment', 'studentSection.section']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_number', 'like', "%{$s}%")
                  ->orWhere('school_email', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) =>
                      $a->where('first_name', 'like', "%{$s}%")
                        ->orWhere('middle_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                  );
            });
        }

        $paymentUserIds = \App\Models\Payment::whereNotNull('receipt_url')
            ->whereNotIn('receipt_url', ['', '[]', '[""]'])
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $allStudents = $query->latest('students.created_at')->get();

        $evaluatedStudents = $allStudents->map(function ($student) use ($paymentUserIds) {
            $applicant = $student->applicant;
            
            $isFilled = $applicant && $applicant->completion_percentage === 100;
            $hasPayment = $applicant && in_array($applicant->user_id, $paymentUserIds);
            $hasPic = $applicant && filled($applicant->photo_2x2_url);
            $hasMsAccount = filled($student->ms_user_id);
            $isTeamsEnrolled = filled($student->ms_teams_enrolled_at);

            $isCompleted = $isFilled && $hasPayment && $hasPic && $hasMsAccount && $isTeamsEnrolled;

            return [
                'student' => $student,
                'is_completed' => $isCompleted,
            ];
        });

        if ($statusFilter === 'completed') {
            $filtered = $evaluatedStudents->filter(fn($item) => $item['is_completed']);
        } elseif ($statusFilter === 'pending') {
            $filtered = $evaluatedStudents->filter(fn($item) => !$item['is_completed']);
        } else {
            $filtered = $evaluatedStudents;
        }

        $page = $request->input('page', 1);
        $perPage = 20;
        $sliced = $filtered->slice(($page - 1) * $perPage, $perPage)->map(fn($item) => $item['student']);

        $students = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $totalCount = Student::count();
        
        $allSystemStudents = Student::with('applicant')->get();
        $completedCount = $allSystemStudents->filter(function ($student) use ($paymentUserIds) {
            $applicant = $student->applicant;
            
            $isFilled = $applicant && $applicant->completion_percentage === 100;
            $hasPayment = $applicant && in_array($applicant->user_id, $paymentUserIds);
            $hasPic = $applicant && filled($applicant->photo_2x2_url);
            $hasMsAccount = filled($student->ms_user_id);
            $isTeamsEnrolled = filled($student->ms_teams_enrolled_at);

            return $isFilled && $hasPayment && $hasPic && $hasMsAccount && $isTeamsEnrolled;
        })->count();

        $stats = [
            'total' => $totalCount,
            'pending' => $totalCount - $completedCount,
            'completed' => $completedCount,
        ];

        return view('admin.students.accounts', compact('students', 'stats', 'statusFilter', 'paymentUserIds'));
    }

    public function documents(Request $request)
    {
        $query = Student::with('applicant');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_number', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) =>
                      $a->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                  );
            });
        }
        $students = $query->paginate(20);
        return view('admin.students.documents', compact('students'));
    }

    public function verification(Request $request)
    {
        $query = Student::with('applicant');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_number', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) =>
                      $a->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                  );
            });
        }
        $students = $query->paginate(20);
        return view('admin.students.verification', compact('students'));
    }

    public function promotions(Request $request)
    {
        $query = Student::with('applicant');
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_number', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) =>
                      $a->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                  );
            });
        }
        $students = $query->paginate(20);
        return view('admin.students.promotions', compact('students'));
    }

    public function history(Request $request)
    {
        $query = Student::with(['applicant.payment', 'studentSection.section'])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('student_number', 'like', "%{$s}%")
                  ->orWhere('school_email', 'like', "%{$s}%")
                  ->orWhereHas('applicant', fn($a) =>
                      $a->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                  );
            });
        }

        $logs = $query->paginate(15);

        return view('admin.students.history', compact('logs'));
    }
}
