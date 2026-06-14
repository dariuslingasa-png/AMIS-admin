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
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhereHas('students.applicant', function ($sa) use ($s) {
                      $sa->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$s}%"]);
                  });
            });
        }

        $families = $query->with(['students.applicant', 'students.account', 'students.studentSection.section'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.families', compact('families'));
    }
}
