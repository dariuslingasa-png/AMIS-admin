<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    public function verifyStudent(string $studentNumberOrHash)
    {
        $studentNumber = $studentNumberOrHash;
        if (!is_numeric($studentNumberOrHash)) {
            $studentNumber = Student::deobfuscateStudentNumber($studentNumberOrHash) ?: $studentNumberOrHash;
        }

        $student = Student::with(['applicant.user'])->where('student_number', $studentNumber)->first();

        return view('public.verify_student', [
            'student' => $student,
            'studentNumber' => $studentNumberOrHash,
        ]);
    }
}
