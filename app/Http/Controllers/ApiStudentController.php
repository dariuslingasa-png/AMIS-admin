<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiStudentController extends Controller
{
    /**
     * Handle incoming scanned student details from Flutter mobile app.
     */
    public function scanOnboard(Request $request)
    {
        // 1. Validate request payload
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:50',
            'lrn' => 'nullable|string|max:50',
            'gender' => 'nullable|string|in:MALE,FEMALE',
            'student_type' => 'nullable|string|max:100',
            'learning_mode' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'date_of_birth' => 'nullable|string', // Date format varies from OCR scans
        ]);

        $schoolYear = config('services.school.year', '2026-2027');

        try {
            return DB::transaction(function () use ($request, $schoolYear) {
                $lrn = $request->lrn ? trim($request->lrn) : null;
                $firstName = mb_strtoupper(trim($request->first_name), 'UTF-8');
                $lastName = mb_strtoupper(trim($request->last_name), 'UTF-8');
                $middleName = $request->middle_name ? mb_strtoupper(trim($request->middle_name), 'UTF-8') : null;
                $suffix = $request->suffix ? mb_strtoupper(trim($request->suffix), 'UTF-8') : null;

                $applicant = null;

                // 2. Lookup existing applicant by LRN first (if available and valid)
                if ($lrn && $lrn !== 'NA') {
                    $applicant = EnrollmentApplicant::where('lrn', $lrn)->first();
                }

                // 3. Fallback: Lookup by Name
                if (! $applicant) {
                    $applicant = EnrollmentApplicant::where('first_name', $firstName)
                        ->where('last_name', $lastName)
                        ->first();
                }

                // 4. Determine Date format safely
                $dob = null;
                if ($request->filled('date_of_birth')) {
                    try {
                        $dob = Carbon::parse($request->date_of_birth)->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning('Could not parse scanned date of birth: '.$request->date_of_birth);
                    }
                }

                $applicantData = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => $middleName,
                    'suffix' => $suffix,
                    'lrn' => $lrn ?: 'NA',
                    'gender' => $request->gender ? mb_strtoupper($request->gender) : null,
                    'student_type' => $request->student_type ? mb_strtoupper($request->student_type) : 'NEW',
                    'learning_mode' => $request->learning_mode ? mb_strtoupper($request->learning_mode) : 'FACE-TO-FACE',
                    'email' => $request->email ? strtolower($request->email) : null,
                    'mobile_number' => $request->mobile,
                    'address' => $request->address ? mb_strtoupper($request->address) : null,
                    'street_address' => $request->address ? mb_strtoupper($request->address) : null,
                    'home_address' => $request->address ? mb_strtoupper($request->address) : null,
                    'school_year' => $schoolYear,
                ];

                if ($dob) {
                    $applicantData['date_of_birth'] = $dob;
                }

                if ($applicant) {
                    // Update existing record
                    $applicant->update($applicantData);
                    $message = 'Scanned student details successfully updated in AMIS.';
                } else {
                    // Create new applicant
                    $applicantData['status'] = 'Pending';
                    $applicant = EnrollmentApplicant::create($applicantData);
                    $message = 'New student details scanned and successfully registered in AMIS.';
                }

                // 5. If there is an associated Student record, sync student status
                $studentNumber = null;
                if ($applicant->student) {
                    $student = $applicant->student;
                    $studentNumber = $student->student_number;

                    // Sync email if updated
                    if ($applicant->email && ! $student->school_email) {
                        $student->update(['school_email' => $applicant->email]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'applicant_id' => $applicant->id,
                    'student_number' => $studentNumber,
                ], 200);
            });

        } catch (\Exception $e) {
            Log::error('API Scan Onboarding failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to process scanned record: '.$e->getMessage(),
            ], 500);
        }
    }
}
