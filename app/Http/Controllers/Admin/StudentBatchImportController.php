<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplicant;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentBatchImportController extends Controller
{
    public function bulkJsonImport(Request $request)
    {
        $request->validate([
            'json_data' => 'nullable|string',
            'json_file' => 'nullable|file',
            'target_section_id' => 'nullable|integer|exists:sections,id',
        ]);

        $rawJson = '';
        if ($request->hasFile('json_file')) {
            $rawJson = file_get_contents($request->file('json_file')->getRealPath());
        } else {
            $rawJson = $request->input('json_data', '');
        }

        $rawJson = trim((string)$rawJson);

        if (empty($rawJson)) {
            return back()->with('error', 'Please paste JSON payload text or upload a JSON file.');
        }

        $items = json_decode($rawJson, true);

        if (!is_array($items)) {
            return back()->with('error', 'Invalid JSON payload format. Please ensure it is a valid JSON array of student objects.');
        }

        if (isset($items['first_name']) || isset($items['last_name']) || isset($items['name']) || isset($items['lrn'])) {
            $items = [$items];
        }

        $updatedCount = 0;
        $createdCount = 0;
        $assignedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($items as $index => $item) {
                if (!is_array($item)) continue;

                $lrn = trim((string)($item['lrn'] ?? $item['LRN'] ?? $item['student_number'] ?? $item['student_id'] ?? ''));
                $firstName = trim((string)($item['first_name'] ?? $item['fname'] ?? $item['first'] ?? ''));
                $lastName = trim((string)($item['last_name'] ?? $item['lname'] ?? $item['last'] ?? ''));
                $middleName = trim((string)($item['middle_name'] ?? $item['mname'] ?? $item['middle'] ?? ''));
                $fullNameRaw = trim((string)($item['full_name'] ?? $item['name'] ?? ''));

                if (empty($firstName) && empty($lastName) && !empty($fullNameRaw)) {
                    if (str_contains($fullNameRaw, ',')) {
                        $parts = explode(',', $fullNameRaw, 2);
                        $lastName = trim($parts[0]);
                        $subParts = preg_split('/\s+/', trim($parts[1] ?? ''));
                        $firstName = $subParts[0] ?? '';
                        $middleName = implode(' ', array_slice($subParts, 1));
                    } else {
                        $parts = preg_split('/\s+/', $fullNameRaw);
                        $countP = count($parts);
                        if ($countP >= 2) {
                            $firstName = $parts[0];
                            $lastName = $parts[$countP - 1];
                            $middleName = implode(' ', array_slice($parts, 1, $countP - 2));
                        } else {
                            $firstName = $fullNameRaw;
                        }
                    }
                }

                $address = trim((string)($item['address'] ?? $item['street_address'] ?? $item['home_address'] ?? ''));
                $gradeLevel = trim((string)($item['grade_level'] ?? $item['grade'] ?? ''));
                $gender = strtolower(trim((string)($item['gender'] ?? $item['sex'] ?? '')));
                $dob = trim((string)($item['date_of_birth'] ?? $item['dob'] ?? $item['birthdate'] ?? ''));
                $pob = trim((string)($item['place_of_birth'] ?? $item['birthplace'] ?? ''));
                $religion = trim((string)($item['religion'] ?? ''));
                $parentName = trim((string)($item['parent_name'] ?? $item['father_name'] ?? $item['mother_name'] ?? $item['guardian'] ?? ''));
                $parentMobile = trim((string)($item['parent_mobile'] ?? $item['phone'] ?? $item['mobile_number'] ?? $item['mobile'] ?? ''));
                $parentEmail = strtolower(trim((string)($item['parent_email'] ?? $item['email'] ?? '')));
                $sectionName = trim((string)($item['section'] ?? $item['section_name'] ?? ''));

                if (empty($firstName) && empty($lastName) && empty($lrn)) {
                    $errors[] = "Item #" . ($index + 1) . ": Skipped due to missing name and LRN.";
                    continue;
                }

                $applicant = null;
                $student = null;

                if (!empty($lrn)) {
                    $applicant = EnrollmentApplicant::where('lrn', $lrn)->first();
                    if (!$applicant) {
                        $student = Student::where('student_number', $lrn)->first();
                        if ($student) {
                            $applicant = $student->applicant;
                        }
                    }
                }

                if (!$applicant && (!empty($firstName) || !empty($lastName))) {
                    $cleanFirstName = trim(preg_replace('/\s+[A-Za-z]\.?$/i', '', $firstName));
                    $cleanLastName = trim($lastName);

                    $query = EnrollmentApplicant::query();
                    if (!empty($cleanFirstName)) {
                        $query->where('first_name', 'like', '%' . $cleanFirstName . '%');
                    }
                    if (!empty($cleanLastName)) {
                        $query->where('last_name', 'like', '%' . $cleanLastName . '%');
                    }
                    $applicant = $query->first();

                    if (!$applicant && (!empty($cleanLastName) || !empty($cleanFirstName))) {
                        $searchStr = trim($cleanFirstName . ' ' . $cleanLastName);
                        $user = User::where('name', 'like', "%{$searchStr}%")->first();
                        if ($user && $user->student) {
                            $student = $user->student;
                            $applicant = $student->applicant;
                        }
                    }
                }

                if ($applicant) {
                    if (!empty($lrn)) $applicant->lrn = $lrn;
                    if (!empty($firstName)) $applicant->first_name = mb_strtoupper($firstName);
                    if (!empty($lastName)) $applicant->last_name = mb_strtoupper($lastName);
                    if (!empty($middleName)) $applicant->middle_name = mb_strtoupper($middleName);
                    if (!empty($address)) {
                        $applicant->address = mb_strtoupper($address);
                        $applicant->street_address = mb_strtoupper($address);
                        $applicant->home_address = mb_strtoupper($address);
                    }
                    if (!empty($gradeLevel)) $applicant->grade_level = $gradeLevel;
                    if (!empty($gender)) $applicant->gender = strtolower($gender);
                    if (!empty($dob)) {
                        try {
                            $applicant->date_of_birth = Carbon::parse($dob);
                        } catch (\Exception $e) {}
                    }
                    if (!empty($pob)) $applicant->place_of_birth = mb_strtoupper($pob);
                    if (!empty($religion)) $applicant->religion = mb_strtoupper($religion);
                    if (!empty($parentName)) {
                        if (empty($applicant->father_first_name)) {
                            $applicant->father_first_name = mb_strtoupper($parentName);
                        }
                    }
                    if (!empty($parentMobile)) $applicant->parent_mobile = $parentMobile;
                    if (!empty($parentEmail)) $applicant->parent_email = strtolower($parentEmail);

                    $applicant->save();

                    if ($applicant->student) {
                        $student = $applicant->student;
                        if (!empty($gradeLevel)) {
                            $student->grade_level = $gradeLevel;
                            $student->save();
                        }
                    }

                    $updatedCount++;
                } else {
                    $createdApplicant = EnrollmentApplicant::create([
                        'student_type' => 'NEW',
                        'lrn' => $lrn ?: null,
                        'first_name' => mb_strtoupper($firstName ?: 'STUDENT'),
                        'last_name' => mb_strtoupper($lastName ?: 'RECORD'),
                        'middle_name' => mb_strtoupper($middleName),
                        'grade_level' => $gradeLevel ?: 'Grade 7',
                        'gender' => $gender ? strtolower($gender) : 'male',
                        'address' => mb_strtoupper($address),
                        'street_address' => mb_strtoupper($address),
                        'home_address' => mb_strtoupper($address),
                        'date_of_birth' => !empty($dob) ? date('Y-m-d', strtotime($dob)) : null,
                        'place_of_birth' => mb_strtoupper($pob),
                        'religion' => mb_strtoupper($religion ?: 'Islam'),
                        'father_first_name' => mb_strtoupper($parentName),
                        'parent_mobile' => $parentMobile,
                        'parent_email' => strtolower($parentEmail),
                    ]);

                    $dispName = trim(mb_strtoupper($firstName . ' ' . $lastName));
                    $user = User::create([
                        'name' => $dispName,
                        'email' => !empty($parentEmail) ? $parentEmail : strtolower(str_replace(' ', '', $dispName) . rand(100, 999) . '@amis.edu.ph'),
                        'password' => bcrypt('AMIS2026!'),
                        'role' => 'student',
                        'account_status' => 'verified',
                    ]);

                    $studentNumber = $lrn ?: (string)rand(100000000000, 999999999999);
                    $student = Student::create([
                        'user_id' => $user->id,
                        'enrollment_applicant_id' => $createdApplicant->id,
                        'student_number' => $studentNumber,
                        'grade_level' => $gradeLevel ?: 'Grade 7',
                        'school_year' => '2026-2027',
                    ]);

                    $createdCount++;
                }

                $targetSecId = $request->input('target_section_id');
                if (!$targetSecId && !empty($sectionName)) {
                    $secObj = Section::where('name', 'like', "%{$sectionName}%")->first();
                    if ($secObj) {
                        $targetSecId = $secObj->id;
                    }
                }

                if ($targetSecId && $student) {
                    StudentSection::updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'section_id' => $targetSecId,
                            'ms_status' => 'enrolled',
                            'ms_enrolled_at' => now(),
                        ]
                    );
                    $assignedCount++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing JSON batch: ' . $e->getMessage());
        }

        $msg = "Batch JSON Processing Complete! (Updated: {$updatedCount} existing students, Auto-Inserted: {$createdCount} new students" . ($assignedCount > 0 ? ", Assigned to Section: {$assignedCount}" : "") . ")";
        return back()->with('success', $msg);
    }

    public function previewJsonImport(Request $request)
    {
        $jsonData = trim($request->input('json_data', ''));

        if ($request->hasFile('json_file')) {
            $file = $request->file('json_file');
            $jsonData = file_get_contents($file->getRealPath());
        }

        if (empty($jsonData)) {
            return response()->json([
                'success' => false,
                'message' => 'JSON payload or file is empty.',
            ], 422);
        }

        $jsonDataClean = preg_replace('/,\s*([\]}])/', '$1', $jsonData);
        $items = json_decode($jsonDataClean, true);

        if (!is_array($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON structure. Please check for missing brackets or quotation marks.',
            ], 422);
        }

        if (isset($items['students']) && is_array($items['students'])) {
            $items = $items['students'];
        }

        $previewList = [];
        $updateCount = 0;
        $createCount = 0;

        foreach ($items as $index => $item) {
            if (!is_array($item)) continue;

            $getVal = function ($keys, $default = '') use ($item) {
                foreach ((array)$keys as $k) {
                    if (isset($item[$k]) && filled($item[$k])) return trim((string)$item[$k]);
                }
                return $default;
            };

            $lrn = $getVal(['lrn', 'student_number', 'LRN', 'student_id']);
            $firstName = $getVal(['first_name', 'fname', 'first', 'given_name']);
            $lastName = $getVal(['last_name', 'lname', 'last', 'surname']);
            $middleName = $getVal(['middle_name', 'mname', 'middle']);
            $fullName = $getVal(['name', 'full_name', 'student_name']);

            if (empty($firstName) && empty($lastName) && !empty($fullName)) {
                $parts = explode(' ', $fullName);
                if (count($parts) >= 2) {
                    $lastName = array_pop($parts);
                    $firstName = implode(' ', $parts);
                } else {
                    $firstName = $fullName;
                }
            }

            $address = $getVal(['address', 'home_address', 'street_address', 'location']);
            $gradeLevel = $getVal(['grade_level', 'grade', 'level']);
            $gender = $getVal(['gender', 'sex']);
            $dob = $getVal(['date_of_birth', 'dob', 'birthdate']);
            $parentName = $getVal(['parent_name', 'guardian_name', 'father_name', 'mother_name', 'parent']);
            $parentMobile = $getVal(['parent_mobile', 'parent_phone', 'mobile_number', 'contact']);

            $applicant = null;

            if (!empty($lrn)) {
                $applicant = EnrollmentApplicant::where('lrn', $lrn)->first();
                if (!$applicant) {
                    $student = Student::where('student_number', $lrn)->first();
                    if ($student) $applicant = $student->applicant;
                }
            }

            if (!$applicant && (!empty($firstName) || !empty($lastName))) {
                $cleanFirstName = trim(preg_replace('/\s+[A-Za-z]\.?$/i', '', $firstName));
                $cleanLastName = trim($lastName);

                $query = EnrollmentApplicant::query();
                if (!empty($cleanFirstName)) $query->where('first_name', 'like', '%' . $cleanFirstName . '%');
                if (!empty($cleanLastName)) $query->where('last_name', 'like', '%' . $cleanLastName . '%');
                $applicant = $query->first();

                if (!$applicant && (!empty($cleanLastName) || !empty($cleanFirstName))) {
                    $searchStr = trim($cleanFirstName . ' ' . $cleanLastName);
                    $user = User::where('name', 'like', "%{$searchStr}%")->first();
                    if ($user && $user->student) {
                        $applicant = $user->student->applicant;
                    }
                }
            }

            $status = $applicant ? 'UPDATE' : 'CREATE';
            if ($applicant) {
                $updateCount++;
            } else {
                $createCount++;
            }

            $previewList[] = [
                'index' => $index + 1,
                'status' => $status,
                'matched_student_id' => $applicant?->student?->student_number ?? $applicant?->amis_student_id ?? null,
                'matched_name' => $applicant ? ($applicant->last_name . ', ' . $applicant->first_name) : null,
                'lrn' => $lrn ?: ($applicant?->lrn ?? 'Auto-Generated'),
                'name' => mb_strtoupper(trim($lastName . ', ' . $firstName . ' ' . $middleName)),
                'grade_level' => $gradeLevel ?: 'Grade 1',
                'gender' => !empty($gender) ? ucfirst(strtolower($gender)) : 'Male',
                'address' => mb_strtoupper($address ?: 'N/A'),
                'parent' => mb_strtoupper($parentName ?: 'N/A') . ($parentMobile ? " ({$parentMobile})" : ''),
            ];
        }

        return response()->json([
            'success' => true,
            'total' => count($previewList),
            'update_count' => $updateCount,
            'create_count' => $createCount,
            'students' => $previewList,
        ]);
    }
}
