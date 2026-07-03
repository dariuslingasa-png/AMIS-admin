<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class StudentSoaController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant', 'account.monthlyBillings', 'account.payments');

        if (!$student || !$student->account) {
            return view('student.soa', ['student' => $student, 'account' => null]);
        }

        return view('student.soa', [
            'student' => $student,
            'account' => $student->account,
        ]);
    }
}
