<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student Record Sheets</title>
    <style>
        @page { size: A4; margin: 10mm 10mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 10px 15px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; z-index: 1000; }
        .toolbar button { border: 0; border-radius: 8px; background: #059669; color: #fff; cursor: pointer; font-weight: 800; padding: 9px 14px; font-size: 11px; display: inline-flex; align-items: center; gap: 6px; }
        .page { width: 210mm; min-height: 277mm; margin: 20px auto; background: #fff; padding: 12mm 10mm; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border-radius: 8px; page-break-after: always; display: flex; flex-direction: column; justify-content: space-between; }
        .page:last-of-type { page-break-after: avoid !important; }
        
        .header { display: table; width: 100%; border-bottom: 2px solid #059669; padding-bottom: 12px; margin-bottom: 15px; }
        .brand-mark, .brand-text { display: table-cell; vertical-align: middle; }
        .brand-mark { width: 54px; }
        .brand-logo { display: block; width: 44px; height: 44px; object-fit: contain; }
        h1 { margin: 0; font-size: 14px; font-weight: 900; letter-spacing: .02em; color: #0f172a; }
        .subtitle { margin-top: 2px; color: #059669; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .section-title { font-size: 10px; font-weight: 900; color: #059669; border-bottom: 1.5px solid #059669; padding-bottom: 3px; margin: 15px 0 8px 0; text-transform: uppercase; letter-spacing: 0.03em; }
        
        /* Grid layout for two-column details */
        .info-grid { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 20px; margin-bottom: 10px; }
        .info-column { display: flex; flex-direction: column; }
        
        /* Table styles for structured details */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-table td { padding: 4px 6px; font-size: 10px; vertical-align: top; border: none; }
        .info-table td.label { font-weight: 850; color: #64748b; width: 120px; text-transform: uppercase; font-size: 8px; letter-spacing: 0.02em; padding-left: 0; }
        .info-table td.value { font-weight: bold; color: #1e293b; }
        .info-table tr { border-bottom: 1px solid #f1f5f9; }
        .info-table tr:last-child { border-bottom: none; }
        
        /* Profile Header Block with Photo */
        .profile-summary-card { display: flex; align-items: center; gap: 15px; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 15px; }
        .profile-photo-box { width: 56px; height: 56px; border-radius: 8px; background: #e2e8f0; overflow: hidden; flex-shrink: 0; border: 1.5px solid #cbd5e1; display: flex; align-items: center; justify-content: center; }
        .profile-photo { width: 100%; height: 100%; object-fit: cover; }
        .profile-summary-details { flex: 1; min-width: 0; }
        .profile-summary-name { font-size: 15px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 0; letter-spacing: -0.3px; }
        .profile-summary-meta { font-size: 9px; font-weight: bold; color: #64748b; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* AMIS ID Card Component Styles */
        .id-cards-workspace { margin-top: auto; padding-top: 15px; border-top: 1px solid #e2e8f0; }
        .id-cards-title-block { text-align: center; margin-bottom: 10px; }
        .id-cards-title { font-size: 9px; font-weight: 950; color: #059669; text-transform: uppercase; letter-spacing: 0.08em; margin: 0; }
        .id-cards-subtitle { font-size: 7px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-top: 2px; }
        
        .id-cards-row { display: flex; justify-content: center; gap: 30px; }
        .id-card-item { display: flex; flex-direction: column; align-items: center; }
        .id-card-side-label { font-size: 7px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.05em; }
        
        .id-card-print-wrapper {
            width: 54mm;
            height: 85.6mm;
            position: relative;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            border-radius: 3.2mm;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
            background: #ffffff;
        }
        
        .id-card-scaler {
            width: 340px;
            height: 538px;
            position: absolute;
            top: 0;
            left: 0;
            transform: scale(0.6);
            transform-origin: top left;
        }
        
        .id-card-container {
            width: 340px;
            height: 538px;
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: #064e3b;
        }
        
        .photo-clip {
            position: absolute;
            left: 81px;
            top: 114px;
            width: 178px;
            height: 172px;
            overflow: hidden;
            background: transparent;
            border-radius: 14px;
        }
        
        .photo-clip img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            z-index: 1;
        }
        
        .green-frame-overlay {
            position: absolute;
            left: 81px;
            top: 114px;
            width: 178px;
            height: 172px;
            border: 4.5px solid #054f3b;
            border-radius: 14px;
            z-index: 2;
            pointer-events: none;
            box-sizing: border-box;
        }
        
        .photo-placeholder {
            position: absolute;
            left: 81px;
            top: 114px;
            width: 178px;
            height: 172px;
            z-index: 1;
            border-radius: 14px;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #64748b;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .student-id {
            position: absolute;
            left: 0;
            top: 295px;
            width: 340px;
            height: 15px;
            z-index: 20;
            background: transparent;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            letter-spacing: 0.05em;
            font-size: 12.5px;
            color: white;
            text-align: center;
            line-height: 15px;
        }
        
        .student-last-name {
            position: absolute;
            left: 15px;
            top: 334px;
            width: 310px;
            height: 32px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .student-last-name h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
            line-height: 1;
            letter-spacing: -0.5px;
        }
        
        .student-first-name {
            position: absolute;
            left: 15px;
            top: 366px;
            width: 310px;
            height: 22px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .student-first-name h4 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            margin: 0;
            line-height: 1;
        }
        
        .student-grade {
            position: absolute;
            left: 15px;
            top: 406px;
            width: 310px;
            height: 30px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .student-grade span {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-shadow: 0 1px 1px rgba(0,0,0,0.05);
        }
        
        .student-lrn {
            position: absolute;
            font-family: 'Outfit', sans-serif;
            font-size: 15.5px;
            font-weight: 700;
            z-index: 20;
            right: 8px;
            top: 405px;
            width: 22px;
            height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-90deg);
            transform-origin: center;
            white-space: nowrap;
            letter-spacing: 0.05em;
            color: #1e293b;
        }
        
        .student-qr {
            position: absolute;
            left: 134.5px;
            top: 458px;
            width: 71px;
            height: 71px;
            z-index: 20;
            padding: 2.5px;
            border-radius: 2px;
            background: white;
        }
        
        .student-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        
        /* Back Card Styles */
        .parent-name {
            position: absolute;
            left: 15px;
            top: 85px;
            width: 310px;
            height: 28px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .parent-name h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
            line-height: 1.1;
        }
        
        .contact-number {
            position: absolute;
            left: 15px;
            top: 118px;
            width: 310px;
            height: 20px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .contact-number h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1;
        }
        
        .address-box {
            position: absolute;
            left: 20px;
            top: 144px;
            width: 300px;
            height: 42px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 20px;
        }
        
        .address-box p {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin: 0;
            line-height: 1.25;
        }
        
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { width: 100%; min-height: 100%; margin: 0; padding: 0; box-shadow: none; border-radius: 0; }
            .id-cards-workspace { padding-top: 25px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    @php
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
    @endphp

    @foreach ($students as $student)
        @php
            $applicant = $student->applicant;
            if (!$applicant) continue;

            $firstName = trim($applicant->first_name ?? '');
            $middleName = trim($applicant->middle_name ?? '');
            $lastName = trim($applicant->last_name ?? '');
            
            $middleInitial = '';
            if ($middleName !== '') {
                $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
                $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
            }
            
            $fullNameParts = array_filter([$firstName, $middleInitial, $lastName], function($val) {
                return $val !== '';
            });
            $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');
            $displayName = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT RECORD';
            
            $nameLength = mb_strlen($displayName);
            $nameFontSize = '11px';
            if ($nameLength > 25) {
                $nameFontSize = '8px';
            } elseif ($nameLength > 20) {
                $nameFontSize = '9.5px';
            } elseif ($nameLength > 16) {
                $nameFontSize = '10.5px';
            }
            
            $photoUrl = \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url);
            
            $studentAddress = implode(', ', array_filter([$applicant->street_address, $applicant->city, $applicant->state_province, $applicant->country]));
            if (empty($studentAddress)) {
                $studentAddress = $applicant->address ?: '-';
            }
            
            $homeAddress = implode(', ', array_filter([$applicant->home_street_address, $applicant->home_city, $applicant->home_state_province]));
            if (empty($homeAddress)) {
                $homeAddress = $applicant->home_address ?: '-';
            }
            
            $studentMobile = trim(($applicant->mobile_country_code ?? '').' '.($applicant->mobile_number ?? '')) ?: '-';
            $parentMobile = trim(($applicant->parent_country_code ?? '').' '.($applicant->parent_mobile ?? '')) ?: '-';
            
            $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_last_name ?? '')) ?: '-';
            $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_last_name ?? '')) ?: '-';
            
            $emergencyName = $applicant->emergency_name ?: '-';
            $emergencyRelationship = $applicant->emergency_relationship ?: '-';
            $emergencyPhone = $applicant->emergency_phone ?: '-';
            
            $sectionName = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
            $hasSection = !empty($sectionName) && strtolower(trim($sectionName)) !== 'no section';
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

            $isOdl = false;
            $learningMode = strtolower($applicant->learning_mode ?? '');
            if (str_contains($learningMode, 'online') || str_contains($learningMode, 'odl') || str_contains($learningMode, 'distance')) {
                $isOdl = true;
            }
            $cardNotice = $isOdl 
                ? 'This card is non-transferable and remains property of Al Munawwara Islamic School. Must be presented for official transactions.'
                : 'This card is non-transferable and remains property of Al Munawwara Islamic School. Must be worn inside campus at all times.';
            
            $badgeStudentId = $student->student_number;
            $displayGrade = $student->grade_level;
            $hash = base64_encode((int)$student->student_number + 987654);
            $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
        @endphp
        <main class="page">
            <div>
                <!-- School Letterhead Header -->
                <header class="header">
                    <div style="display: table; width: 100%; border-collapse: collapse;">
                        <div style="display: table-row;">
                            <!-- Left: English Name -->
                            <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: left;">
                                <h1 style="font-family: Arial, sans-serif; font-weight: 900; font-size: 13px; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">
                                    AL MUNAWWARA ISLAMIC SCHOOL
                                </h1>
                                <div class="subtitle">
                                    Official Student Record Sheet
                                </div>
                            </div>
                            <!-- Center: Logo -->
                            <div style="display: table-cell; vertical-align: middle; width: 20%; text-align: center;">
                                <img class="brand-logo" src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 40px; width: auto; display: inline-block;">
                            </div>
                            <!-- Right: Arabic Name -->
                            <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: right; direction: rtl;">
                                <h1 style="font-family: 'Times New Roman', serif; font-weight: 900; font-size: 16px; margin: 0; color: #059669; letter-spacing: 0.03em;">
                                    المدرسة المنورة الإسلامية
                                </h1>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Student summary block with 2x2 Photo -->
                <div class="profile-summary-card">
                    <div class="profile-photo-box" style="display: flex; align-items: center; justify-content: center;">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Student Photo" class="profile-photo">
                        @else
                            <span style="font-size: 8px; font-weight: bold; color: #ef4444; text-align: center; text-transform: uppercase; line-height: 1.2; padding: 2px;">MISSING PHOTO</span>
                        @endif
                    </div>
                    <div class="profile-summary-details">
                        <h2 class="profile-summary-name">{{ $displayName }}</h2>
                        <div class="profile-summary-meta">
                            Student ID: <span style="color: #0f172a; font-weight: 900;">{{ $student->student_number }}</span> &middot; 
                            Grade: <span style="color: #0f172a; font-weight: 900;">{{ $student->grade_level }}</span>
                            @if($hasSection)
                                &middot; Section: <span style="color: #0f172a; font-weight: 900;">{{ $sectionName }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Two-Column Information Details -->
                <div class="info-grid">
                    <!-- Column 1: Academic & Personal Info -->
                    <div class="info-column">
                        <div class="section-title">Student Information</div>
                        <table class="info-table">
                            <tr>
                                <td class="label">AMIS ID Number</td>
                                <td class="value font-mono">{{ $student->student_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Official LRN</td>
                                <td class="value">{{ $applicant->lrn ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Grade Level</td>
                                <td class="value">{{ $student->grade_level }}</td>
                            </tr>
                            @if($hasSection)
                            <tr>
                                <td class="label">Section</td>
                                <td class="value">{{ $sectionName }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="label">School Year</td>
                                <td class="value">S.Y. {{ $student->school_year }}</td>
                            </tr>
                            <tr>
                                <td class="label">Learning Mode</td>
                                <td class="value">{{ $applicant->learning_mode ?: 'Face-to-Face' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Student Type</td>
                                <td class="value" style="text-transform: uppercase;">{{ $applicant->student_type ?: 'NEW' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Gender</td>
                                <td class="value" style="text-transform: uppercase;">{{ $applicant->gender ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Date of Birth</td>
                                <td class="value">{{ $applicant->date_of_birth ? $applicant->date_of_birth->format('M d, Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Place of Birth</td>
                                <td class="value">{{ $applicant->place_of_birth ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Religion</td>
                                <td class="value">{{ $applicant->religion ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Ethnicity</td>
                                <td class="value">{{ $applicant->ethnicity ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">School Email</td>
                                <td class="value font-mono" style="font-size: 9px;">{{ $student->school_email ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Student Mobile</td>
                                <td class="value">{{ $studentMobile }}</td>
                            </tr>
                            <tr>
                                <td class="label">Residence Address</td>
                                <td class="value" style="font-size: 9px; line-height: 1.25;">{{ $studentAddress }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Column 2: Parent Contacts & Emergency Details -->
                    <div class="info-column">
                        <div class="section-title">Parent & Guardian Information</div>
                        <table class="info-table">
                            <tr>
                                <td class="label">Father's Name</td>
                                <td class="value">{{ $fatherName }}</td>
                            </tr>
                            <tr>
                                <td class="label">Father's Occupation</td>
                                <td class="value">{{ $applicant->father_occupation ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Mother's Name</td>
                                <td class="value">{{ $motherName }}</td>
                            </tr>
                            <tr>
                                <td class="label">Mother's Occupation</td>
                                <td class="value">{{ $applicant->mother_occupation ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Parent Email</td>
                                <td class="value" style="font-size: 9px;">{{ $applicant->parent_email ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Parent Mobile</td>
                                <td class="value">{{ $parentMobile }}</td>
                            </tr>
                            <tr>
                                <td class="label">Home Address</td>
                                <td class="value" style="font-size: 9px; line-height: 1.25;">{{ $homeAddress }}</td>
                            </tr>
                        </table>

                        <div class="section-title" style="margin-top: 10px;">Emergency Contact Details</div>
                        <table class="info-table">
                            <tr>
                                <td class="label">Contact Person</td>
                                <td class="value">{{ $emergencyName }}</td>
                            </tr>
                            <tr>
                                <td class="label">Relationship</td>
                                <td class="value">{{ $emergencyRelationship }}</td>
                            </tr>
                            <tr>
                                <td class="label">Contact Number</td>
                                <td class="value font-mono">{{ $emergencyPhone }}</td>
                            </tr>
                            @if ($applicant->medical_has_concern)
                                <tr>
                                    <td class="label" style="color: #ef4444;">Medical History</td>
                                    <td class="value" style="color: #b91c1c; font-size: 9px; line-height: 1.25;">{{ $applicant->health_conditions ?: 'Has documented concern' }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Premium AMIS ID Card front/back layout -->
            <div class="id-cards-workspace">
                <div class="id-cards-title-block">
                    <h3 class="id-cards-title">OFFICIAL STUDENT ID CARD LAYOUT</h3>
                    <div class="id-cards-subtitle">Printable S.Y. 2026-2027 Student ID Card</div>
                </div>
                
                <div class="id-cards-row">
                    <!-- ID Front -->
                    <div class="id-card-item">
                        <span class="id-card-side-label">Front Side</span>
                        <div class="id-card-print-wrapper">
                            <div class="id-card-scaler">
                                <div class="id-card-container">
                                    <!-- Background Template Image -->
                                    <img src="{{ asset('assets/amis-id-template.png') }}?v={{ filemtime(public_path('assets/amis-id-template.png')) }}" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 10; pointer-events: none;" alt="AMIS ID Template">
                                    
                                    <!-- Student Photo Container (Middle Layer) -->
                                    @if($photoUrl)
                                        <div class="photo-clip">
                                            <img src="{{ $photoUrl }}" alt="Student Photo">
                                        </div>
                                    @else
                                        <div class="photo-placeholder">Photo Missing</div>
                                    @endif

                                    <!-- Student ID Badge text -->
                                    <div class="student-id">{{ $badgeStudentId }}</div>

                                    <!-- Last Name -->
                                    @php
                                        $lastNameLen = strlen($lastName);
                                        $lastNameFontSize = $lastNameLen > 20 ? '16px' : ($lastNameLen > 15 ? '19px' : ($lastNameLen > 10 ? '23px' : '26px'));
                                    @endphp
                                    <div class="student-last-name">
                                        <h3 style="font-size: {{ $lastNameFontSize }};">{{ $lastName }}</h3>
                                    </div>

                                    <!-- First Name -->
                                    @php
                                        $firstNameLen = strlen($firstName);
                                        $firstNameFontSize = $firstNameLen > 25 ? '11px' : ($firstNameLen > 18 ? '13px' : '15px');
                                    @endphp
                                    <div class="student-first-name">
                                        <h4 style="font-size: {{ $firstNameFontSize }};">{{ $firstName }}</h4>
                                    </div>

                                    <!-- Grade Level -->
                                    <div class="student-grade">
                                        <span style="color: {{ $getGradeColor($displayGrade) }};">{{ $displayGrade }}</span>
                                    </div>

                                    <!-- LRN -->
                                    @if($applicant->lrn && !in_array(strtoupper($applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                        <div class="student-lrn">
                                            LRN: <span style="margin-left: 4px;">{{ $applicant->lrn }}</span>
                                        </div>
                                    @endif

                                    <!-- QR Code -->
                                    <div class="student-qr">
                                        <img src="{{ $qrCodeUrl }}" alt="QR Verification">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ID Back -->
                    <div class="id-card-item">
                        <span class="id-card-side-label">Back Side</span>
                        <div class="id-card-print-wrapper">
                            <div class="id-card-scaler">
                                <div class="id-card-container">
                                    <!-- Background Template Image -->
                                    <img src="{{ asset('assets/amis-id-template-back.png') }}?v=3" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;" alt="AMIS ID Template Back">

                                    <!-- Parent Name -->
                                    @php
                                        $parentNameLen = strlen($emergencyName);
                                        $parentNameFontSize = $parentNameLen > 20 ? '18px' : ($parentNameLen > 14 ? '21px' : '25px');
                                    @endphp
                                    <div class="parent-name">
                                        <h3 style="font-size: {{ $parentNameFontSize }};">{{ $emergencyName }}</h3>
                                    </div>

                                    <!-- Contact Number -->
                                    <div class="contact-number">
                                        <h4>{{ $emergencyPhone }}</h4>
                                    </div>

                                    <!-- Address -->
                                    @php
                                        $addressLen = strlen($homeAddress);
                                        $addressFontSize = $addressLen > 60 ? '10.5px' : ($addressLen > 40 ? '12px' : '13.5px');
                                    @endphp
                                    <div class="address-box">
                                        <p style="font-size: {{ $addressFontSize }};">{{ $homeAddress }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    @endforeach

    <script>
        let hasPrinted = false;
        function doPrint() {
            if (hasPrinted) return;
            hasPrinted = true;
            window.print();
        }
        window.addEventListener('load', () => {
            setTimeout(doPrint, 1000);
        });
        window.addEventListener('focus', () => {
            setTimeout(doPrint, 200);
        });
    </script>
</body>
</html>
