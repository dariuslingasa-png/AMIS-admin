<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentComparisonController extends Controller
{
    public function auditLogs(Request $request)
    {
        $search = trim($request->input('search', ''));
        $eventFilter = $request->input('event', 'all');

        $query = AdminAuditLog::with('user')
            ->where(function ($q) {
                $q->where('event', 'like', '%student%')
                  ->orWhere('event', 'like', '%photo%')
                  ->orWhere('event', 'like', '%application%')
                  ->orWhere('event', 'like', '%document%')
                  ->orWhere('event', 'like', '%license%');
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($eventFilter !== 'all') {
            if ($eventFilter === 'photo') {
                $query->where('event', 'like', '%photo%');
            } elseif ($eventFilter === 'profile') {
                $query->where('event', 'like', '%profile%');
            } elseif ($eventFilter === 'section') {
                $query->where('event', 'like', '%section%');
            } elseif ($eventFilter === 'approval') {
                $query->where(function($b) {
                    $b->where('event', 'like', '%application%')->orWhere('event', 'like', '%approval%');
                });
            } elseif ($eventFilter === 'delete') {
                $query->where('event', 'like', '%delete%');
            }
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        return view('admin.students.audit-logs', [
            'logs' => $logs,
            'search' => $search,
            'eventFilter' => $eventFilter,
        ]);
    }

    public function comparison(Request $request)
    {
        $csvPaths = [
            'f2f' => base_path('../AMIS_F2F_Verification_Database_Latest.csv'),
            'main' => base_path('../AMIS_Verification_Database_Latest.csv'),
        ];

        $csvNumbers = [];
        foreach ($csvPaths as $key => $path) {
            if (file_exists($path) && ($handle = fopen($path, 'r')) !== false) {
                $headers = fgetcsv($handle);
                $studentIdIdx = array_search('Student_ID', $headers);
                if ($studentIdIdx !== false) {
                    while (($row = fgetcsv($handle)) !== false) {
                        if (isset($row[$studentIdIdx]) && trim($row[$studentIdIdx]) !== '') {
                            $csvNumbers[trim($row[$studentIdIdx])] = $key;
                        }
                    }
                }
                fclose($handle);
            }
        }

        $officialList = $request->input('official_list', '');
        $matchedStudentNumbers = [];
        $hasPastedList = !empty(trim($officialList));

        if ($hasPastedList) {
            $lines = explode("\n", $officialList);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $matches = collect();

                if (str_contains($line, ',')) {
                    $parts = explode(',', $line);
                    $lastNamePart = trim($parts[0]);
                    $firstNamePart = trim($parts[1]);

                    $firstNames = array_filter(explode(' ', $firstNamePart));
                    $firstNameWord = count($firstNames) > 0 ? $firstNames[0] : '';

                    if (!empty($lastNamePart) && !empty($firstNameWord)) {
                        $matches = Student::with('applicant')
                            ->whereHas('applicant', function($q) use ($lastNamePart, $firstNameWord) {
                                $q->where('last_name', 'like', "%{$lastNamePart}%")
                                  ->where('first_name', 'like', "%{$firstNameWord}%");
                            })
                            ->get();
                    }
                }

                if ($matches->isEmpty()) {
                    $cleanName = str_replace([',', '.'], ' ', $line);
                    $terms = array_filter(explode(' ', $cleanName));

                    $query = Student::with('applicant');
                    if (count($terms) > 0) {
                        $query->whereHas('applicant', function($q) use ($terms) {
                            $q->where(function($sub) use ($terms) {
                                foreach ($terms as $term) {
                                    $sub->where(function($sub2) use ($term) {
                                        $sub2->where('first_name', 'like', "%{$term}%")
                                             ->orWhere('last_name', 'like', "%{$term}%")
                                             ->orWhere('middle_name', 'like', "%{$term}%");
                                    });
                                }
                            });
                        });
                        $matches = $query->get();
                    }
                }

                foreach ($matches as $match) {
                    $matchedStudentNumbers[] = $match->student_number;
                }
            }
        }

        $studentsQuery = Student::with('applicant');
        if ($hasPastedList) {
            $studentsQuery->whereIn('student_number', $matchedStudentNumbers);
        }
        $students = $studentsQuery->get();

        $comparisonList = [];
        foreach ($students as $student) {
            $studentNumber = $student->student_number;
            $applicant = $student->applicant;
            if (!$applicant) continue;

            $fullName = trim($applicant->first_name . ' ' . ($applicant->middle_name ?? '') . ' ' . $applicant->last_name . ($applicant->suffix ? ' ' . $applicant->suffix : ''));
            $learningMode = $applicant->learning_mode ?? 'Face-to-Face';
            $grade = $student->grade_level;

            $foundInCsv = isset($csvNumbers[$studentNumber]);
            $csvType = $foundInCsv ? $csvNumbers[$studentNumber] : null;

            $comparisonList[] = [
                'id' => $student->id,
                'student_number' => $studentNumber,
                'full_name' => mb_strtoupper($fullName),
                'grade_level' => $grade,
                'learning_mode' => $learningMode,
                'found_in_csv' => $foundInCsv,
                'csv_type' => $csvType,
                'remarks' => $this->cleanReviewRemarks($applicant->review_remarks),
            ];
        }

        $search = trim($request->input('search'));
        if ($search !== '') {
            $comparisonList = array_filter($comparisonList, function($item) use ($search) {
                return str_contains(strtolower($item['full_name']), strtolower($search)) || 
                       str_contains(strtolower($item['student_number']), strtolower($search));
            });
        }

        $filter = $request->input('filter', 'all');
        if ($filter === 'missing') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return !$item['found_in_csv'];
            });
        } elseif ($filter === 'insync') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return $item['found_in_csv'];
            });
        }

        $modeFilter = $request->input('mode', 'all');
        if ($modeFilter === 'f2f') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return str_contains(strtolower($item['learning_mode']), 'face') || str_contains(strtolower($item['learning_mode']), 'f2f');
            });
        } elseif ($modeFilter === 'online') {
            $comparisonList = array_filter($comparisonList, function($item) {
                return !str_contains(strtolower($item['learning_mode']), 'face') && !str_contains(strtolower($item['learning_mode']), 'f2f');
            });
        }

        $gradeOrder = [
            'Kinder 1' => 1, 'Kinder 2' => 2,
            'Grade 1' => 3, 'Grade 2' => 4, 'Grade 3' => 5, 'Grade 4' => 6, 'Grade 5' => 7, 'Grade 6' => 8,
            'Grade 7' => 9, 'Grade 8' => 10, 'Grade 9' => 11, 'Grade 10' => 12, 'Grade 11' => 13, 'Grade 12' => 14
        ];
        usort($comparisonList, function($a, $b) use ($gradeOrder) {
            $gradeA = $gradeOrder[$a['grade_level']] ?? 99;
            $gradeB = $gradeOrder[$b['grade_level']] ?? 99;
            if ($gradeA !== $gradeB) {
                return $gradeA - $gradeB;
            }
            return strcmp($a['full_name'], $b['full_name']);
        });

        $totalDb = Student::count();
        $totalInCsv = Student::whereIn('student_number', array_keys($csvNumbers))->count();
        $missingCount = $totalDb - $totalInCsv;

        $trackedStudents = [];

        if (!empty(trim($officialList))) {
            $lines = explode("\n", $officialList);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $matches = collect();

                if (str_contains($line, ',')) {
                    $parts = explode(',', $line);
                    $lastNamePart = trim($parts[0]);
                    $firstNamePart = trim($parts[1]);

                    $firstNames = array_filter(explode(' ', $firstNamePart));
                    $firstNameWord = count($firstNames) > 0 ? $firstNames[0] : '';

                    if (!empty($lastNamePart) && !empty($firstNameWord)) {
                        $matches = Student::with('applicant')
                            ->whereHas('applicant', function($q) use ($lastNamePart, $firstNameWord) {
                                $q->where('last_name', 'like', "%{$lastNamePart}%")
                                  ->where('first_name', 'like', "%{$firstNameWord}%");
                            })
                            ->get();
                    }
                }

                if ($matches->isEmpty()) {
                    $cleanName = str_replace([',', '.'], ' ', $line);
                    $terms = array_filter(explode(' ', $cleanName));

                    $query = Student::with('applicant');
                    if (count($terms) > 0) {
                        $query->whereHas('applicant', function($q) use ($terms) {
                            $q->where(function($sub) use ($terms) {
                                foreach ($terms as $term) {
                                    $sub->where(function($sub2) use ($term) {
                                        $sub2->where('first_name', 'like', "%{$term}%")
                                             ->orWhere('last_name', 'like', "%{$term}%")
                                             ->orWhere('middle_name', 'like', "%{$term}%");
                                    });
                                }
                            });
                        });
                        $matches = $query->get();
                    }
                }

                if ($matches->isEmpty()) {
                    $trackedStudents[] = [
                        'input_name' => $line,
                        'found' => false,
                        'student_id' => null,
                        'full_name' => null,
                        'grade_level' => null,
                        'learning_mode' => null,
                        'has_lrn' => false,
                        'has_photo' => false,
                        'has_parents' => false,
                        'has_address' => false,
                        'has_documents' => false,
                        'details_url' => null,
                    ];
                } else {
                    foreach ($matches as $match) {
                        $appl = $match->applicant;
                        
                        $hasLrn = !empty($appl->lrn) && strtoupper($appl->lrn) !== 'N/A' && strtoupper($appl->lrn) !== 'NA';
                        $hasPhoto = !empty($appl->photo_2x2_url);
                        
                        $fatherFull = trim(($appl->father_first_name ?? '') . ' ' . ($appl->father_last_name ?? ''));
                        $motherFull = trim(($appl->mother_first_name ?? '') . ' ' . ($appl->mother_last_name ?? ''));
                        $hasParents = !empty($fatherFull) || !empty($motherFull) || (!empty($appl->emergency_name) && strtolower(trim($appl->emergency_name)) !== 'emergency contact');

                        $hasAddress = !empty($appl->street_address) || !empty($appl->home_address) || !empty($appl->address);
                        $hasDocs = !empty($appl->birth_cert_url) || !empty($appl->report_card_url) || !empty($appl->marriage_contract_url) || !empty($appl->medical_record_url) || !empty($appl->affidavit_url);

                        $fullName = trim($appl->first_name . ' ' . ($appl->middle_name ?? '') . ' ' . $appl->last_name . ($appl->suffix ? ' ' . $appl->suffix : ''));

                        $trackedStudents[] = [
                            'input_name' => $line,
                            'found' => true,
                            'student_id' => $match->student_number,
                            'full_name' => mb_strtoupper($fullName),
                            'grade_level' => $match->grade_level,
                            'learning_mode' => $appl->learning_mode ?? 'Face-to-Face',
                            'has_lrn' => $hasLrn,
                            'has_photo' => $hasPhoto,
                            'has_parents' => $hasParents,
                            'has_address' => $hasAddress,
                            'has_documents' => $hasDocs,
                            'remarks' => $this->cleanReviewRemarks($appl->review_remarks),
                            'details_url' => route('admin.students.show', $match->id),
                        ];
                    }
                }
            }
        }

        $remindersList = [];
        foreach ($trackedStudents as $tracked) {
            if ($tracked['found'] && !empty(trim($tracked['remarks']))) {
                $remindersList[] = [
                    'student_number' => $tracked['student_id'],
                    'full_name' => $tracked['full_name'],
                    'grade_level' => $tracked['grade_level'],
                    'learning_mode' => $tracked['learning_mode'],
                    'remarks' => $tracked['remarks'],
                    'details_url' => $tracked['details_url'],
                    'has_photo' => $tracked['has_photo'],
                    'has_lrn' => $tracked['has_lrn'],
                    'has_parents' => $tracked['has_parents'],
                    'has_address' => $tracked['has_address'],
                    'has_documents' => $tracked['has_documents'],
                ];
            }
        }

        return view('admin.students.comparison', [
            'comparisonList' => $comparisonList,
            'totalDb' => $totalDb,
            'totalInCsv' => $totalInCsv,
            'missingCount' => $missingCount,
            'filter' => $filter,
            'search' => $search,
            'modeFilter' => $modeFilter,
            'officialList' => $officialList,
            'trackedStudents' => $trackedStudents,
            'remindersList' => $remindersList,
        ]);
    }

    public function syncComparisonCsv(Request $request)
    {
        $gradeOrder = [
            'Kinder 1', 'Kinder 2',
            'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
            'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
        ];

        $students = Student::with('applicant')
            ->whereHas('applicant')
            ->leftJoin('enrollment_applicants as sort_ea', 'sort_ea.id', '=', 'students.enrollment_applicant_id')
            ->select('students.*')
            ->orderByRaw("FIELD(students.grade_level, " . implode(',', array_fill(0, count($gradeOrder), '?')) . ")", $gradeOrder)
            ->orderBy('sort_ea.last_name', 'asc')
            ->orderBy('sort_ea.first_name', 'asc')
            ->get();

        $f2fPath = base_path('../AMIS_F2F_Verification_Database_Latest.csv');
        $mainPath = base_path('../AMIS_Verification_Database_Latest.csv');

        $f2fFile = fopen($f2fPath, 'w');
        $mainFile = fopen($mainPath, 'w');

        $headers = [
            'Photo_URL',
            'Student_ID',
            'Last_Name',
            'Full_Name',
            'QR_Code_URL',
            'LRN',
            'Grade_Level',
            'Parent_Full_Name',
            'Contact_No',
            'Address',
        ];

        fputcsv($f2fFile, $headers);
        fputcsv($mainFile, $headers);

        foreach ($students as $student) {
            $applicant = $student->applicant;
            if (!$applicant) continue;

            $learningMode = strtolower($applicant->learning_mode ?? 'Face-to-Face');
            $isF2f = str_contains($learningMode, 'face') || str_contains($learningMode, 'f2f');

            $lastName   = mb_strtoupper(trim($applicant->last_name   ?? ''));
            $fullName   = mb_strtoupper(trim($applicant->first_name . ' ' . ($applicant->middle_name ?? '') . ' ' . $applicant->last_name . ($applicant->suffix ? ' ' . $applicant->suffix : '')));
            
            $lrn         = trim($applicant->lrn ?? '');
            if (empty($lrn) || strtoupper($lrn) === 'N/A' || strtoupper($lrn) === 'NA') {
                $lrn = 'NA';
            }

            $fatherFirst  = mb_strtoupper(trim($applicant->father_first_name  ?? ''));
            $fatherMiddle = mb_strtoupper(trim($applicant->father_middle_name ?? ''));
            $fatherLast   = mb_strtoupper(trim($applicant->father_last_name   ?? ''));
            $fatherMI     = $fatherMiddle !== '' ? mb_substr($fatherMiddle, 0, 1) . '.' : '';
            $fatherFull   = trim(implode(' ', array_filter([$fatherFirst, $fatherMI, $fatherLast])));

            $motherFirst  = mb_strtoupper(trim($applicant->mother_first_name  ?? ''));
            $motherMiddle = mb_strtoupper(trim($applicant->mother_middle_name ?? ''));
            $motherLast   = mb_strtoupper(trim($applicant->mother_last_name   ?? ''));
            $motherMI     = $motherMiddle !== '' ? mb_substr($motherMiddle, 0, 1) . '.' : '';
            $motherFull   = trim(implode(' ', array_filter([$motherFirst, $motherMI, $motherLast])));

            $guardianName = $fatherFull ?: $motherFull;
            if (empty($guardianName) && !empty($applicant->emergency_name) && strtolower(trim($applicant->emergency_name)) !== 'emergency contact') {
                $guardianName = trim($applicant->emergency_name);
            }
            $guardianName = mb_strtoupper($guardianName);

            $contactNo   = ($applicant->parent_mobile ?? null) ?: (($applicant->mobile_number ?? null) ?: ($applicant->emergency_phone ?? null));
            $address = trim($applicant->address ?? $applicant->home_address ?? '');

            $studentNumber = $student->student_number;
            $hash = base64_encode((int)$studentNumber + 987654);

            $photoUrl = $student->photo_2x2_url ? route('public.student.photo', ['hash' => $hash]) : 'https://amis.edu.ph/student-photo/' . $hash . '.jpg';
            $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';

            $rowData = [
                $photoUrl,
                $studentNumber,
                $lastName,
                $fullName,
                $qrCodeUrl,
                $lrn,
                $student->grade_level,
                $guardianName,
                $contactNo,
                $address,
            ];

            if ($isF2f) {
                fputcsv($f2fFile, $rowData);
            } else {
                fputcsv($mainFile, $rowData);
            }
        }

        fclose($f2fFile);
        fclose($mainFile);

        return redirect()->route('admin.students.comparison')
            ->with('success', 'Verification CSV Fallback databases generated and synced successfully!');
    }

    private function cleanReviewRemarks(?string $remarks): string
    {
        if (blank($remarks)) {
            return '';
        }
        
        $cleaned = str_replace('Approved with missing/pending documents: ', '', $remarks);
        $cleaned = str_replace('. Please follow up and complete document verification.', '', $cleaned);
        $cleaned = str_replace('Please follow up and complete document verification.', '', $cleaned);
        return rtrim(trim($cleaned), '.');
    }
}
