<?php

namespace App\Http\Controllers;

use App\Models\StudentSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentTeacherController extends Controller
{
    public function teachers()
    {
        $user    = Auth::user();
        $student = $user->student?->load('applicant');
        $subjects = collect();
        $section = null;
        $adviser = null;

        if ($student) {
            $studentSection = StudentSection::where('student_id', $student->id)
                ->with(['section.subjects'])
                ->first();

            if ($studentSection?->section) {
                $section = $studentSection->section;
                $subjects = $studentSection->section->subjects;
            }

            if ($section) {
                $dbAdviser = DB::table('class_advisory_assignments')
                    ->where('section_id', $section->id)
                    ->where('status', 'active')
                    ->first();

                if ($dbAdviser) {
                    $adviser = [
                        'name' => $dbAdviser->teacher_name,
                        'email' => $dbAdviser->teacher_email,
                        'photo' => $this->getTeacherPhotoPath($dbAdviser->teacher_name),
                        'team_url' => $section->ms_team_url ?? 'https://teams.microsoft.com',
                    ];
                }
            }

            if (!$adviser) {
                $grade = $section ? $section->grade_level : $student->grade_level;
                if ($grade) {
                    $normalizedGrade = $grade;
                    if (preg_match('/^G(\d{1,2})$/i', $grade, $matches)) {
                        $normalizedGrade = 'Grade ' . $matches[1];
                    }

                    $allAdvisors = array_merge(
                        config('class_advisories.elementary', []),
                        config('class_advisories.high_school', [])
                    );
                    foreach ($allAdvisors as $adv) {
                        if (strtolower(trim($adv['grade_level'])) === strtolower(trim($normalizedGrade))) {
                            $teacherName = $adv['teacher'];
                            $cleanName = trim(str_ireplace('TEACHER ', '', $teacherName));
                            $teacherUser = DB::table('users')
                                ->where('role', 'teacher')
                                ->where(function($query) use ($cleanName) {
                                    $query->where('name', $cleanName)
                                          ->orWhere('name', 'like', '%' . $cleanName . '%');
                                })
                                ->first();

                            $adviser = [
                                'name' => $teacherName,
                                'email' => $teacherUser ? $teacherUser->email : strtolower(str_replace([' ', '.'], '', $cleanName)) . '@amis.edu.ph',
                                'photo' => $adv['photo'] ?? null,
                                'team_url' => $section ? ($section->ms_team_url ?? 'https://teams.microsoft.com') : 'https://teams.microsoft.com',
                            ];
                            break;
                        }
                    }
                }
            }
            if ($adviser && str_contains(strtolower($adviser['name']), 'ethel') && str_contains(strtolower($adviser['name']), 'lorraine')) {
                $adviser['fb_url'] = 'https://www.facebook.com/elijstnn';
                $adviser['gmail'] = 'eljustiniane.amis@gmail.com';
                $adviser['whatsapp'] = '09451075043';
            }
        }
        return view('student.teachers', compact('user', 'student', 'section', 'subjects', 'adviser'));
    }

    private function getTeacherPhotoPath($teacherName)
    {
        $teacherKey = \Illuminate\Support\Str::slug(str_ireplace('TEACHER ', '', $teacherName));
        $possiblePaths = [
            "images/teachers/{$teacherKey}.png",
            "images/teachers/teacher-{$teacherKey}.png",
            "images/teachers/{$teacherKey}.jpg",
            "images/teachers/teacher-{$teacherKey}.jpg",
            "images/teachers/{$teacherKey}.jpeg",
            "images/teachers/teacher-{$teacherKey}.jpeg",
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists(public_path($path))) {
                return $path;
            }
        }
        return null;
    }
}
