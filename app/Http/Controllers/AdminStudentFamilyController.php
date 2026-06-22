<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminStudentFamilyController extends Controller
{
    public function families(Request $request)
    {
        $query = User::where('role', 'applicant')
            ->whereHas('students');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $sl = mb_strtolower($s);
            $query->where(function ($q) use ($s, $sl) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$sl}%"])
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhereHas('students', fn($st) =>
                      $st->where('student_number', 'like', "%{$s}%")
                  )
                  ->orWhereHas('students.applicant', function ($sa) use ($sl) {
                      $sa->whereRaw('LOWER(first_name) LIKE ?', ["%{$sl}%"])
                        ->orWhereRaw('LOWER(middle_name) LIKE ?', ["%{$sl}%"])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$sl}%"])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE ?", ["%{$sl}%"])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name)) LIKE ?", ["%{$sl}%"])
                        ->orWhereRaw("LOWER(CONCAT(first_name, ' ', LEFT(IFNULL(middle_name, ''), 1), '. ', last_name)) LIKE ?", ["%{$sl}%"]);
                  });
            });
        }

        // Filter by number of children
        if ($request->filled('children_filter')) {
            $filter = $request->children_filter;
            if ($filter === '1') {
                $query->has('students', '=', 1);
            } elseif ($filter === '2') {
                $query->has('students', '=', 2);
            } elseif ($filter === '3+') {
                $query->has('students', '>=', 3);
            }
        }

        // Sorting
        $sort = $request->input('sort', 'latest');
        if ($sort === 'children_desc') {
            $query->withCount('students')->orderBy('students_count', 'desc');
        } elseif ($sort === 'children_asc') {
            $query->withCount('students')->orderBy('students_count', 'asc');
        } elseif ($sort === 'name_asc') {
            $query->orderBy('name', 'asc');
        } else {
            $query->latest();
        }

        $families = $query->with(['students.applicant', 'students.account', 'students.studentSection.section'])
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.families', compact('families'));
    }
}
