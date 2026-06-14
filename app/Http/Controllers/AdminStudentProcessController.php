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

        $paymentUserIdsQuery = \App\Models\Payment::whereNotNull('receipt_url')
            ->whereNotIn('receipt_url', ['', '[]', '[""]'])
            ->select('user_id');

        $completedScope = function($q) use ($paymentUserIdsQuery) {
            $q->whereNotNull('ms_user_id')
              ->where('ms_user_id', '!=', '')
              ->whereNotNull('ms_teams_enrolled_at')
              ->whereHas('applicant', function($a) use ($paymentUserIdsQuery) {
                  $a->where('completion_percentage', 100)
                    ->whereNotNull('photo_2x2_url')
                    ->where('photo_2x2_url', '!=', '')
                    ->whereIn('user_id', $paymentUserIdsQuery);
              });
        };

        $pendingScope = function($q) use ($paymentUserIdsQuery) {
            $q->where(function($sub) use ($paymentUserIdsQuery) {
                $sub->whereNull('ms_user_id')
                    ->orWhere('ms_user_id', '')
                    ->orWhereNull('ms_teams_enrolled_at')
                    ->orWhereDoesntHave('applicant', function($a) use ($paymentUserIdsQuery) {
                        $a->where('completion_percentage', 100)
                          ->whereNotNull('photo_2x2_url')
                          ->where('photo_2x2_url', '!=', '')
                          ->whereIn('user_id', $paymentUserIdsQuery);
                    });
            });
        };

        if ($statusFilter === 'completed') {
            $query->where($completedScope);
        } elseif ($statusFilter === 'pending') {
            $query->where($pendingScope);
        }

        $students = $query->latest('students.created_at')->paginate(20);

        $totalCount = Student::count();
        $completedCount = Student::where($completedScope)->count();

        $stats = [
            'total' => $totalCount,
            'pending' => $totalCount - $completedCount,
            'completed' => $completedCount,
        ];

        $paginatedUserIds = $students->pluck('applicant.user_id')->filter()->toArray();
        $paymentUserIds = \App\Models\Payment::whereIn('user_id', $paginatedUserIds)
            ->whereNotNull('receipt_url')
            ->whereNotIn('receipt_url', ['', '[]', '[""]'])
            ->pluck('user_id')
            ->toArray();

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
