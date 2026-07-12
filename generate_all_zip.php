<?php
// bootstrap laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

echo "Starting ZIP generation for all students...\n";

// Get all students
$students = Student::with(['applicant', 'studentSection.section.subjects'])->get();
echo "Found " . $students->count() . " students.\n";

$zip = new \ZipArchive();
$outputPath = '/home2/amisdavc/admin.amis.edu.ph/public/Official_Student_Records_SY_2026-2027_All_Grades.zip';

if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
    die("Could not create ZIP file\n");
}

$filesAdded = 0;
$rootFolder = 'Official Student Records - SY 2026-2027';

$getGradeColor = function($grade) {
    if (!$grade) return '#6d28d9';
    $g = strtoupper($grade);
    if (str_contains($g, 'NURSERY') || str_contains($g, 'KINDER') || str_contains($g, 'PRE-')) return '#ea580c';
    if (str_contains($g, 'GRADE 1') || str_contains($g, 'GRADE 2') || str_contains($g, 'GRADE 3')) return '#0284c7';
    if (str_contains($g, 'GRADE 4') || str_contains($g, 'GRADE 5') || str_contains($g, 'GRADE 6')) return '#7c3aed';
    if (str_contains($g, 'GRADE 7') || str_contains($g, 'GRADE 8') || str_contains($g, 'GRADE 9') || str_contains($g, 'GRADE 10')) return '#dc2626';
    if (str_contains($g, 'GRADE 11') || str_contains($g, 'GRADE 12') || str_contains($g, 'GRADE XI') || str_contains($g, 'GRADE XII')) return '#4f46e5';
    return '#6d28d9';
};

foreach ($students as $index => $student) {
    $appl = $student->applicant;
    if (!$appl) continue;

    $firstName = trim($appl->first_name ?? '');
    $middleName = trim($appl->middle_name ?? '');
    $lastName = trim($appl->last_name ?? '');
    
    $middleInitial = '';
    if ($middleName !== '') {
        $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
        $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
    }
    
    $fullNameParts = array_filter([$firstName, $middleInitial, $lastName], function($val) {
        return $val !== '';
    });
    $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');
    if (empty($fullName)) {
        $fullName = 'Unnamed Student';
    }

    $schoolYear = trim($student->school_year ?? '');
    $isArchived = ($schoolYear !== '' && $schoolYear !== '2026-2027');

    $formattedId = str_starts_with($student->student_number, 'AMIS-') 
        ? $student->student_number 
        : 'AMIS-' . str_pad($student->student_number, 6, '0', STR_PAD_LEFT);

    $studentFolder = $formattedId . ' - ' . $fullName;
    
    if ($isArchived) {
        $gradeFolder = trim($student->grade_level ?: 'Unassigned Grade');
        $sectionFolder = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
        $basePath = $rootFolder . '/Archived or Inactive Students/' . $gradeFolder . '/' . $sectionFolder . '/' . $studentFolder;
    } else {
        $learningMode = strtolower($appl->learning_mode ?? '');
        $modeFolder = 'F2F';
        if (str_contains($learningMode, 'online') || str_contains($learningMode, 'odl') || str_contains($learningMode, 'distance')) {
            $modeFolder = 'ODL';
        }

        $shiftFolder = '1st Shift';
        if (str_contains($learningMode, '2nd') || str_contains($learningMode, 'second') || str_contains($learningMode, 'shift 2')) {
            $shiftFolder = '2nd Shift';
        }

        $gradeFolder = trim($student->grade_level ?: 'Unassigned Grade');
        $sectionFolder = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
        $basePath = $rootFolder . '/' . $modeFolder . '/' . $shiftFolder . '/' . $gradeFolder . '/' . $sectionFolder . '/' . $studentFolder;
    }

    // 1. Documents
    $docTypes = [
        '2x2_Photo' => $appl->photo_2x2_url,
        'Birth_Certificate' => $appl->birth_cert_url,
        'Report_Card' => $appl->report_card_url,
        'Marriage_Contract' => $appl->marriage_contract_url,
        'Medical_Record' => $appl->medical_record_url,
        'Affidavit' => $appl->affidavit_url,
    ];

    foreach ($docTypes as $label => $relativeUrl) {
        if (empty($relativeUrl)) continue;

        $absolutePath = \App\Support\EnrollmentStorage::getAbsolutePath($relativeUrl);
        if ($absolutePath && file_exists($absolutePath)) {
            $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
            $zipPath = $basePath . '/01 - Student Documents/' . $label . ($ext ? '.' . $ext : '');
            $zip->addFile($absolutePath, $zipPath);
            $filesAdded++;
        }
    }

    // 2. Student ID
    try {
        $htmlId = view('admin.students.partials.index.print_id', ['students' => [$student]])->render();
        $zip->addFromString($basePath . '/02 - Student ID/AMIS_' . $student->student_number . '_ID.html', $htmlId);
        $filesAdded++;
    } catch (\Exception $e) {
        $fallbackIdText = "Student ID Card Details\n====================\nID: " . $student->student_number . "\nName: " . $fullName;
        $zip->addFromString($basePath . '/02 - Student ID/AMIS_' . $student->student_number . '_ID_Details.txt', $fallbackIdText);
        $filesAdded++;
    }

    // Texts variables
    $homeAddress = implode(', ', array_filter([$appl->home_street_address, $appl->home_city, $appl->home_state_province]));
    if (empty($homeAddress)) {
        $homeAddress = $appl->home_address ?: '-';
    }
    
    $emergencyName = $appl->emergency_name ?: '-';
    if (empty($emergencyName) || strtolower($emergencyName) === 'emergency contact') {
        $emergencyName = trim(($appl->father_first_name ?? '') . ' ' . ($appl->father_last_name ?? '')) ?: (trim(($appl->mother_first_name ?? '') . ' ' . ($appl->mother_last_name ?? '')) ?: 'Registrar Office');
    }
    
    $emergencyPhone = $appl->emergency_phone ?: '-';
    if (empty($emergencyPhone)) {
        $emergencyPhone = $appl->parent_mobile ?: ($appl->mobile_number ?: '+63 900 000 0000');
    }

    $studentMobile = trim(($appl->mobile_country_code ?? '').' '.($appl->mobile_number ?? '')) ?: '-';
    $parentMobile = trim(($appl->parent_country_code ?? '').' '.($appl->parent_mobile ?? '')) ?: '-';
    
    $fatherName = trim(($appl->father_first_name ?? '').' '.($appl->father_last_name ?? '')) ?: '-';
    $motherName = trim(($appl->mother_first_name ?? '').' '.($appl->mother_last_name ?? '')) ?: '-';
    
    $advisorObj = $student->studentSection->section?->grade_advisor ?? null;
    $advisorName = $advisorObj ? html_entity_decode(trim($advisorObj->teacher_name), ENT_QUOTES, 'UTF-8') : 'N/A';
    if (empty($advisorName) || $advisorName === 'N/A') {
        $advisories = config('class_advisories') ?? [];
        $allAdvisories = array_merge($advisories['elementary'] ?? [], $advisories['high_school'] ?? []);
        $targetGrade = strtolower(trim($student->grade_level ?? ''));
        foreach ($allAdvisories as $adv) {
            $advGradeLower = strtolower($adv['grade_level'] ?? '');
            $advKeyLower = strtolower($adv['grade'] ?? '');
            if ($targetGrade !== '' && (
                str_contains($targetGrade, $advGradeLower) || 
                str_contains($advGradeLower, $targetGrade) || 
                $targetGrade === $advKeyLower
            )) {
                $advisorName = $adv['teacher'];
                break;
            }
        }
    }
    if (empty($advisorName)) {
        $advisorName = 'N/A';
    }

    // 3. Credentials
    $credentialsContent = "AL MUNAWWARA ISLAMIC SCHOOL\nSTUDENT ACCOUNT CREDENTIALS\n===========================\n\n";
    $credentialsContent .= "Student ID: " . $student->student_number . "\n";
    $credentialsContent .= "Student Name: " . $fullName . "\n";
    $credentialsContent .= "Grade & Section: " . $student->grade_level . " - " . $sectionFolder . "\n";
    $credentialsContent .= "School Email: " . ($student->school_email ?: 'N/A') . "\n";
    $credentialsContent .= "Temporary Password: " . ($student->temp_password ?: 'Password already changed or set') . "\n";
    $credentialsContent .= "Microsoft Teams Email: " . ($student->ms_email ?: 'N/A') . "\n";
    $credentialsContent .= "Teams Sync Status: " . ($student->ms_license_active ? 'Active' : 'Inactive') . "\n";
    $zip->addFromString($basePath . '/03 - Account Credentials/AMIS_' . $student->student_number . '_Credentials.txt', $credentialsContent);
    $filesAdded++;

    // 4. Enrollment Record
    $enrollmentContent = "AL MUNAWWARA ISLAMIC SCHOOL\nOFFICIAL STUDENT ENROLLMENT RECORD SHEET\n=======================================\n\n";
    $enrollmentContent .= "Student ID: " . $student->student_number . "\nLRN: " . ($appl->lrn ?: 'N/A') . "\nFull Name: " . $fullName . "\nGrade Level: " . $student->grade_level . "\nSection: " . $sectionFolder . "\nGrade Adviser: " . $advisorName . "\n";
    $enrollmentContent .= "Home Address: " . $homeAddress . "\nParent Mobile: " . $parentMobile . "\nEmergency Contact: " . $emergencyName . " (" . $emergencyPhone . ")\n";
    $zip->addFromString($basePath . '/04 - Enrollment Records/AMIS_' . $student->student_number . '_Enrollment_Record.txt', $enrollmentContent);
    $filesAdded++;

    // 5. Academic Subjects
    $academicContent = "AL MUNAWWARA ISLAMIC SCHOOL\nSTUDENT ACADEMIC SUBJECT LIST\n=============================\n\n";
    $academicContent .= "Student ID: " . $student->student_number . "\nStudent Name: " . $fullName . "\n\nSubjects:\n";
    if ($student->studentSection && $student->studentSection->section && $student->studentSection->section->subjects && $student->studentSection->section->subjects->isNotEmpty()) {
        foreach ($student->studentSection->section->subjects as $secSubject) {
            $academicContent .= "- " . $secSubject->subject_name . " (Teacher: " . ($secSubject->teacher_name ?: 'N/A') . ")\n";
        }
    } else {
        $academicContent .= "No subjects assigned.\n";
    }
    $zip->addFromString($basePath . '/05 - Academic Records/AMIS_' . $student->student_number . '_Academic_Records.txt', $academicContent);
    $filesAdded++;

    if (($index + 1) % 100 === 0) {
        echo "Processed " . ($index + 1) . " students...\n";
    }
}

$zip->close();
echo "Finished! Total files added: " . $filesAdded . "\n";
