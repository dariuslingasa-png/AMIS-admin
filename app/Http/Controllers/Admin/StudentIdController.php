<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\EnrollmentStorage;
use Illuminate\Http\Request;

class StudentIdController extends Controller
{
    public function idEditor(Student $student)
    {
        abort_unless(auth()->user()?->canViewAdminGrade($student->grade_level), 403);

        $student->load([
            'applicant.user',
            'studentSection.section',
        ]);

        $applicant = $student->applicant;
        $lastName = $applicant ? trim($applicant->last_name) : 'STUDENT';
        $firstName = $applicant ? trim($applicant->first_name) : 'PROFILE';
        $middleName = $applicant ? trim($applicant->middle_name) : '';
        $suffix = $applicant ? trim($applicant->suffix ?? '') : '';
        $middleInitial = EnrollmentApplicant::formatMiddleInitial($middleName) ?? '';

        $displayGrade = $student->grade_level;
        if ($student->studentSection?->section) {
            $sec = $student->studentSection->section;
            if (str_contains(strtolower($sec->learning_mode), 'online') || str_contains(strtolower($sec->learning_mode), 'odl')) {
                $displayGrade = $student->grade_level.' - '.($sec->official_name ?: $sec->name);
            }
        }

        $studentNumber = $student->student_number;
        $hash = base64_encode((int) $studentNumber + 987654);

        $photoUrl = $student->photo_2x2_url ? route('public.student.photo', ['hash' => $hash]) : ($applicant?->photo_2x2_url ? EnrollmentStorage::url($applicant->photo_2x2_url) : '');
        $qrCodeUrl = 'https://quickchart.io/qr?text='.urlencode('https://amis.edu.ph/v/'.$hash).'&dark=000000&light=ffffff&margin=1&format=png&size=300';

        $emergencyName = $applicant?->emergency_name ?: 'Emergency Contact';
        $emergencyPhone = $applicant?->emergency_phone ?: ($applicant?->parent_mobile ?: ($applicant?->mobile_number ?: '+63 900 000 0000'));
        $homeAddress = trim($applicant?->home_address ?: ($applicant?->address ?: 'Davao City, Philippines'));

        $sectionId = $student->studentSection?->section_id;
        if ($sectionId) {
            $siblingsQuery = Student::whereHas('studentSection', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        } else {
            $siblingsQuery = Student::where('grade_level', $student->grade_level);
        }

        $orderedStudents = $siblingsQuery->orderBy('id', 'asc')->pluck('id')->toArray();
        $currentIndex = array_search($student->id, $orderedStudents);

        $prevStudentId = ($currentIndex !== false && isset($orderedStudents[$currentIndex - 1])) ? $orderedStudents[$currentIndex - 1] : null;
        $nextStudentId = ($currentIndex !== false && isset($orderedStudents[$currentIndex + 1])) ? $orderedStudents[$currentIndex + 1] : null;

        $getInlineBase64 = function ($url) {
            if (! $url) {
                return '';
            }
            try {
                if (str_starts_with($url, 'http')) {
                    $arrContextOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ];
                    $data = file_get_contents($url, false, stream_context_create($arrContextOptions));
                } else {
                    $data = file_get_contents(public_path(ltrim($url, '/')));
                }
                if ($data) {
                    $type = 'image/png';
                    if (str_contains($url, '.jpg') || str_contains($url, '.jpeg')) {
                        $type = 'image/jpeg';
                    }

                    return 'data:'.$type.';base64,'.base64_encode($data);
                }
            } catch (\Throwable $e) {
            }

            return '';
        };

        $qrCodeBase64 = $getInlineBase64($qrCodeUrl);
        $photoBase64 = $photoUrl ? $getInlineBase64($photoUrl) : '';

        $signatureRawUrl = 'https://quickchart.io/qr?text='.urlencode('https://amis.edu.ph/signature').'&dark=000000&light=ffffff&margin=1&format=png&size=200';
        $signatureQrBase64 = $getInlineBase64($signatureRawUrl);

        $lastNameLen = strlen($lastName);
        if ($lastNameLen <= 8) {
            $lastNameFontSize = 36;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 12) {
            $lastNameFontSize = 28;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 15) {
            $lastNameFontSize = 22;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 18) {
            $lastNameFontSize = 17;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 21) {
            $lastNameFontSize = 12.5;
            $lastNameStyle = 'white-space: nowrap;';
        } elseif ($lastNameLen <= 25) {
            $lastNameFontSize = 11;
            $lastNameStyle = 'white-space: nowrap;';
        } else {
            $lastNameFontSize = 9.5;
            $lastNameStyle = 'white-space: nowrap;';
        }

        $displayFirstName = trim(implode(' ', array_filter([$firstName, $middleInitial, $suffix])));
        $firstNameLen = strlen($displayFirstName);
        $displayFirstNameFontSize = $firstNameLen > 25 ? 14 : ($firstNameLen > 18 ? 16 : 18);

        return view('admin.students.id-editor', [
            'student' => $student,
            'prevStudentId' => $prevStudentId,
            'nextStudentId' => $nextStudentId,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'suffix' => $suffix,
            'displayFirstName' => $displayFirstName,
            'lastNameFontSize' => $lastNameFontSize,
            'lastNameStyle' => $lastNameStyle,
            'displayFirstNameFontSize' => $displayFirstNameFontSize,
            'displayGrade' => $displayGrade,
            'studentNumber' => $studentNumber,
            'photoUrl' => $photoUrl,
            'photoBase64' => $photoBase64,
            'qrCodeBase64' => $qrCodeBase64,
            'signatureRawUrl' => $signatureRawUrl,
            'signatureQrBase64' => $signatureQrBase64,
            'emergencyName' => $emergencyName,
            'emergencyPhone' => $emergencyPhone,
            'homeAddress' => $homeAddress,
            'applicant' => $applicant,
        ]);
    }

    public function updateIdFontSizes(Request $request, Student $student)
    {
        abort_if(auth()->user()?->isTeacherAdminViewer(), 403);

        $validated = $request->validate([
            'id_last_name_font_size' => 'nullable|numeric|min:5|max:100',
            'id_first_name_font_size' => 'nullable|numeric|min:5|max:100',
            'id_grade_font_size' => 'nullable|numeric|min:5|max:100',
            'id_num_font_size' => 'nullable|numeric|min:5|max:100',
        ]);

        $student->update([
            'id_last_name_font_size' => $validated['id_last_name_font_size'],
            'id_first_name_font_size' => $validated['id_first_name_font_size'],
            'id_grade_font_size' => $validated['id_grade_font_size'],
            'id_num_font_size' => $validated['id_num_font_size'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ID card font sizes saved successfully!',
        ]);
    }
}
