<?php

// bootstrap laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Support\EnrollmentStorage;
use Illuminate\Contracts\Console\Kernel;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

echo "Starting ZIP generation for all students...\n";

// Get all students
$students = Student::with(['applicant', 'studentSection.section.subjects'])->get();
echo 'Found '.$students->count()." students.\n";

$zip = new ZipArchive;
$outputPath = '/home2/amisdavc/admin.amis.edu.ph/public/Official_Student_Records_SY_2026-2027_All_Grades.zip';

if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    exit("Could not create ZIP file\n");
}

$filesAdded = 0;
$rootFolder = 'Official Student Records - SY 2026-2027';

$getGradeColor = function ($grade) {
    if (! $grade) {
        return '#6d28d9';
    }
    $g = strtoupper($grade);
    if (str_contains($g, 'NURSERY') || str_contains($g, 'KINDER') || str_contains($g, 'PRE-')) {
        return '#ea580c';
    }
    if (str_contains($g, 'GRADE 1') || str_contains($g, 'GRADE 2') || str_contains($g, 'GRADE 3')) {
        return '#0284c7';
    }
    if (str_contains($g, 'GRADE 4') || str_contains($g, 'GRADE 5') || str_contains($g, 'GRADE 6')) {
        return '#7c3aed';
    }
    if (str_contains($g, 'GRADE 7') || str_contains($g, 'GRADE 8') || str_contains($g, 'GRADE 9') || str_contains($g, 'GRADE 10')) {
        return '#dc2626';
    }
    if (str_contains($g, 'GRADE 11') || str_contains($g, 'GRADE 12') || str_contains($g, 'GRADE XI') || str_contains($g, 'GRADE XII')) {
        return '#4f46e5';
    }

    return '#6d28d9';
};

foreach ($students as $index => $student) {
    $appl = $student->applicant;
    if (! $appl) {
        continue;
    }

    $firstName = trim($appl->first_name ?? '');
    $middleName = trim($appl->middle_name ?? '');
    $lastName = trim($appl->last_name ?? '');

    $middleInitial = '';
    if ($middleName !== '') {
        $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
        $middleInitial = ($firstChar === '.') ? '.' : $firstChar.'.';
    }

    $fullNameParts = array_filter([$firstName, $middleInitial, $lastName], function ($val) {
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
        : 'AMIS-'.str_pad($student->student_number, 6, '0', STR_PAD_LEFT);

    $studentFolder = $formattedId.' - '.$fullName;

    if ($isArchived) {
        $gradeFolder = trim($student->grade_level ?: 'Unassigned Grade');
        $sectionFolder = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
        $basePath = $rootFolder.'/Archived or Inactive Students/'.$gradeFolder.'/'.$sectionFolder.'/'.$studentFolder;
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
        $basePath = $rootFolder.'/'.$modeFolder.'/'.$shiftFolder.'/'.$gradeFolder.'/'.$sectionFolder.'/'.$studentFolder;
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
        if (empty($relativeUrl)) {
            continue;
        }

        $absolutePath = EnrollmentStorage::getAbsolutePath($relativeUrl);
        if ($absolutePath && file_exists($absolutePath)) {
            $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
            $zipPath = $basePath.'/01 - Student Documents/'.$label.($ext ? '.'.$ext : '');
            $zip->addFile($absolutePath, $zipPath);
            $filesAdded++;
        }
    }

    // 2. Student ID
    try {
        $htmlId = view('admin.students.partials.index.print_id', ['students' => [$student]])->render();
        $zip->addFromString($basePath.'/02 - Student ID/AMIS_'.$student->student_number.'_ID.html', $htmlId);
        $filesAdded++;
    } catch (Exception $e) {
        $fallbackIdText = "Student ID Card Details\n====================\nID: ".$student->student_number."\nName: ".$fullName;
        $zip->addFromString($basePath.'/02 - Student ID/AMIS_'.$student->student_number.'_ID_Details.txt', $fallbackIdText);
        $filesAdded++;
    }

    // Texts variables
    $homeAddress = implode(', ', array_filter([$appl->home_street_address, $appl->home_city, $appl->home_state_province]));
    if (empty($homeAddress)) {
        $homeAddress = $appl->home_address ?: '-';
    }

    $emergencyName = $appl->emergency_name ?: '-';
    if (empty($emergencyName) || strtolower($emergencyName) === 'emergency contact') {
        $emergencyName = trim(($appl->father_first_name ?? '').' '.($appl->father_last_name ?? '')) ?: (trim(($appl->mother_first_name ?? '').' '.($appl->mother_last_name ?? '')) ?: 'Registrar Office');
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
    $credentialsHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <title>Student Account Credentials</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #334155; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 30px; }
        .school-name { font-size: 16pt; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .doc-title { font-size: 12pt; font-weight: bold; color: #059669; text-transform: uppercase; margin: 5px 0 0 0; }
        .card { border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; background-color: #f8fafc; }
        .field { margin-bottom: 12px; }
        .label { font-weight: bold; color: #64748b; font-size: 9pt; text-transform: uppercase; }
        .value { font-size: 11pt; color: #0f172a; font-weight: bold; }
        .highlight { background-color: #fef08a; padding: 2px 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class=\"header\">
        <div class=\"school-name\">Al Munawwara Islamic School</div>
        <div class=\"doc-title\">Student Account Credentials</div>
    </div>
    <div class=\"card\">
        <div class=\"field\"><span class=\"label\">Student ID:</span><br><span class=\"value\">".htmlspecialchars($student->student_number).'</span></div>
        <div class="field"><span class="label">Student Name:</span><br><span class="value">'.htmlspecialchars($fullName).'</span></div>
        <div class="field"><span class="label">Grade & Section:</span><br><span class="value">'.htmlspecialchars($student->grade_level.' - '.$sectionFolder).'</span></div>
        <div class="field"><span class="label">School Email:</span><br><span class="value">'.htmlspecialchars($student->school_email ?: 'N/A').'</span></div>
        <div class="field"><span class="label">Temporary Password:</span><br><span class="value highlight">'.htmlspecialchars($student->temp_password ?: 'Password already changed or set').'</span></div>
        <div class="field"><span class="label">Microsoft Teams Email:</span><br><span class="value">'.htmlspecialchars($student->ms_email ?: 'N/A').'</span></div>
        <div class="field"><span class="label">Teams Sync Status:</span><br><span class="value">'.htmlspecialchars($student->ms_license_active ? 'Active' : 'Inactive').'</span></div>
    </div>
</body>
</html>';
    $zip->addFromString($basePath.'/03 - Account Credentials/AMIS_'.$student->student_number.'_Credentials.doc', $credentialsHtml);
    $filesAdded++;

    // 4. Enrollment Record
    $medicalHistoryHtml = '';
    if ($appl->medical_has_concern) {
        $medicalHistoryHtml = '<tr><td class="label">Medical History/Concerns</td><td class="value">'.htmlspecialchars($appl->health_conditions ?: 'Has documented concern').'</td></tr>';
    }

    $enrollmentHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <title>Official Enrollment Record Sheet</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #334155; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 25px; }
        .school-name { font-size: 16pt; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .doc-title { font-size: 12pt; font-weight: bold; color: #059669; text-transform: uppercase; margin: 5px 0 0 0; }
        .section-header { font-size: 11pt; font-weight: bold; color: #ffffff; background-color: #059669; padding: 6px 12px; margin-top: 20px; margin-bottom: 10px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        td { padding: 6px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .label { font-weight: bold; color: #64748b; width: 35%; }
        .value { color: #0f172a; }
    </style>
</head>
<body>
    <div class=\"header\">
        <div class=\"school-name\">Al Munawwara Islamic School</div>
        <div class=\"doc-title\">Official Student Enrollment Record Sheet</div>
    </div>
    
    <div class=\"section-header\">Student Information</div>
    <table>
        <tr><td class=\"label\">Student ID</td><td class=\"value\">".htmlspecialchars($student->student_number).'</td></tr>
        <tr><td class="label">LRN</td><td class="value">'.htmlspecialchars($appl->lrn ?: 'N/A').'</td></tr>
        <tr><td class="label">Full Name</td><td class="value">'.htmlspecialchars($fullName).'</td></tr>
        <tr><td class="label">Grade Level</td><td class="value">'.htmlspecialchars($student->grade_level).'</td></tr>
        <tr><td class="label">Section</td><td class="value">'.htmlspecialchars($sectionFolder).'</td></tr>
        <tr><td class="label">Grade Advisor</td><td class="value">'.htmlspecialchars($advisorName).'</td></tr>
        <tr><td class="label">School Year</td><td class="value">'.htmlspecialchars($student->school_year).'</td></tr>
        <tr><td class="label">Learning Mode</td><td class="value">'.htmlspecialchars($appl->learning_mode ?: 'N/A').'</td></tr>
        <tr><td class="label">Student Type</td><td class="value">'.htmlspecialchars($appl->student_type ?: 'N/A').'</td></tr>
        <tr><td class="label">Gender</td><td class="value">'.htmlspecialchars($appl->gender ?: 'N/A').'</td></tr>
        <tr><td class="label">Date of Birth</td><td class="value">'.htmlspecialchars($appl->date_of_birth ?: 'N/A').'</td></tr>
        <tr><td class="label">Place of Birth</td><td class="value">'.htmlspecialchars($appl->place_of_birth ?: 'N/A').'</td></tr>
        <tr><td class="label">Religion</td><td class="value">'.htmlspecialchars($appl->religion ?: 'N/A').'</td></tr>
        <tr><td class="label">Nationality/Ethnicity</td><td class="value">'.htmlspecialchars($appl->ethnicity ?: 'N/A').'</td></tr>
        <tr><td class="label">Student Mobile</td><td class="value">'.htmlspecialchars($studentMobile).'</td></tr>
        <tr><td class="label">School Email</td><td class="value">'.htmlspecialchars($student->school_email ?: 'N/A').'</td></tr>
        <tr><td class="label">Residence Address</td><td class="value">'.htmlspecialchars($appl->address ?: $appl->home_address ?: 'N/A')."</td></tr>
    </table>

    <div class=\"section-header\">Parent & Guardian Information</div>
    <table>
        <tr><td class=\"label\">Father's Name</td><td class=\"value\">".htmlspecialchars($fatherName)."</td></tr>
        <tr><td class=\"label\">Father's Occupation</td><td class=\"value\">".htmlspecialchars($appl->father_occupation ?: 'N/A')."</td></tr>
        <tr><td class=\"label\">Mother's Name</td><td class=\"value\">".htmlspecialchars($motherName)."</td></tr>
        <tr><td class=\"label\">Mother's Occupation</td><td class=\"value\">".htmlspecialchars($appl->mother_occupation ?: 'N/A').'</td></tr>
        <tr><td class="label">Parent Email</td><td class="value">'.htmlspecialchars($appl->parent_email ?: 'N/A').'</td></tr>
        <tr><td class="label">Parent Mobile</td><td class="value">'.htmlspecialchars($parentMobile).'</td></tr>
        <tr><td class="label">Home Address</td><td class="value">'.htmlspecialchars($homeAddress).'</td></tr>
    </table>

    <div class="section-header">Emergency Contact Details</div>
    <table>
        <tr><td class="label">Contact Person</td><td class="value">'.htmlspecialchars($emergencyName).'</td></tr>
        <tr><td class="label">Relationship</td><td class="value">'.htmlspecialchars($appl->emergency_relationship ?: 'N/A').'</td></tr>
        <tr><td class="label">Contact Number</td><td class="value">'.htmlspecialchars($emergencyPhone).'</td></tr>
        '.$medicalHistoryHtml.'
    </table>
</body>
</html>';
    $zip->addFromString($basePath.'/04 - Enrollment Records/AMIS_'.$student->student_number.'_Enrollment_Record.doc', $enrollmentHtml);
    $filesAdded++;

    // 5. Academic Subjects
    $subjectsRowsHtml = '';
    if ($student->studentSection && $student->studentSection->section && $student->studentSection->section->subjects && $student->studentSection->section->subjects->isNotEmpty()) {
        foreach ($student->studentSection->section->subjects as $secSubject) {
            $subjectsRowsHtml .= '<tr><td>'.htmlspecialchars($secSubject->subject_name).'</td><td>'.htmlspecialchars($secSubject->teacher_name ?: 'N/A').'</td></tr>';
        }
    } else {
        $subjectsRowsHtml = '<tr><td colspan="2" style="text-align: center; color: #64748b;">No subjects currently enrolled or assigned.</td></tr>';
    }

    $academicHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <title>Student Academic Subject List</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #334155; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 30px; }
        .school-name { font-size: 16pt; font-weight: bold; color: #0f172a; margin: 0; text-transform: uppercase; }
        .doc-title { font-size: 12pt; font-weight: bold; color: #059669; text-transform: uppercase; margin: 5px 0 0 0; }
        .student-info { margin-bottom: 20px; font-size: 11pt; }
        .info-row { margin-bottom: 5px; }
        .info-label { font-weight: bold; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #059669; color: #ffffff; text-align: left; padding: 8px 12px; font-weight: bold; font-size: 10pt; text-transform: uppercase; }
        td { padding: 10px 12px; border-bottom: 1px solid #cbd5e1; font-size: 10pt; color: #0f172a; }
        tr:nth-child(even) { background-color: #f8fafc; }
    </style>
</head>
<body>
    <div class=\"header\">
        <div class=\"school-name\">Al Munawwara Islamic School</div>
        <div class=\"doc-title\">Student Academic Subject List</div>
    </div>
    
    <div class=\"student-info\">
        <div class=\"info-row\"><span class=\"info-label\">Student ID:</span> <span>".htmlspecialchars($student->student_number).'</span></div>
        <div class="info-row"><span class="info-label">Student Name:</span> <span>'.htmlspecialchars($fullName).'</span></div>
        <div class="info-row"><span class="info-label">Grade & Section:</span> <span>'.htmlspecialchars($student->grade_level.' - '.$sectionFolder).'</span></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Subject Name</th>
                <th style="width: 50%;">Subject Teacher</th>
            </tr>
        </thead>
        <tbody>
            '.$subjectsRowsHtml.'
        </tbody>
    </table>
</body>
</html>';
    $zip->addFromString($basePath.'/05 - Academic Records/AMIS_'.$student->student_number.'_Academic_Records.doc', $academicHtml);
    $filesAdded++;

    if (($index + 1) % 100 === 0) {
        echo 'Processed '.($index + 1)." students...\n";
    }
}

$zip->close();
echo 'Finished! Total files added: '.$filesAdded."\n";
