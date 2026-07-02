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
        
        /* ID-1 Standard Plastic Card Size in CSS */
        .id-card {
            width: 85.6mm;
            height: 54mm;
            border-radius: 3.2mm;
            border: 1px solid #cbd5e1;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.04);
            background: #fff;
            text-align: left;
            font-family: Arial, sans-serif;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        /* Front Card Graphic Design */
        .id-card-front {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 45%, #059669 75%, #022c22 100%);
            color: #fff;
        }
        .id-card-front::after {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60%;
            height: 100%;
            background: radial-gradient(circle at bottom right, rgba(234, 179, 8, 0.22) 0%, transparent 70%);
            pointer-events: none;
            z-index: 1;
        }
        .id-card-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.06);
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.1);
            height: 32px;
            position: relative;
            z-index: 2;
        }
        .id-card-logo {
            width: 22px;
            height: 22px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .id-card-school-name {
            font-size: 6.5px;
            font-weight: 900;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            line-height: 1.1;
            color: #ffffff;
        }
        .id-card-school-sub {
            font-size: 4.5px;
            font-weight: 700;
            color: #eab308;
            text-transform: uppercase;
            line-height: 1;
            margin-top: 0.5px;
        }
        .id-card-body {
            display: flex;
            padding: 8px 10px;
            gap: 10px;
            height: calc(54mm - 44px);
            align-items: center;
            position: relative;
            z-index: 2;
        }
        .id-card-photo-box {
            width: 20mm;
            height: 26mm;
            border-radius: 4px;
            border: 1.5px solid #eab308;
            background: #022c22;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .id-card-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .id-card-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            gap: 3.5px;
        }
        .id-card-name {
            font-size: 11px;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-bottom: 0.5px solid rgba(234, 179, 8, 0.4);
            padding-bottom: 2.5px;
            margin-bottom: 2px;
        }
        .id-card-field {
            display: flex;
            font-size: 8.5px;
            line-height: 1.2;
        }
        .id-card-label {
            width: 46px;
            color: #a7f3d0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .id-card-value {
            flex: 1;
            font-weight: bold;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .id-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 8px;
            background: #ffffff;
            color: #064e3b;
            font-size: 7px;
            font-weight: 950;
            height: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 2;
            border-top: 0.5px solid rgba(6, 78, 59, 0.15);
        }
        
        /* Back Card Rules & Contacts */
        .id-card-back {
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .id-card-back-header {
            background: #064e3b;
            color: #fff;
            font-size: 6px;
            font-weight: bold;
            text-align: center;
            padding: 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            height: 12px;
        }
        .id-card-back-body {
            padding: 6px 8px;
            font-size: 5px;
            line-height: 1.3;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .id-card-back-title {
            font-weight: bold;
            color: #064e3b;
            margin-bottom: 2px;
            text-transform: uppercase;
            font-size: 5px;
        }
        .id-card-back-terms {
            color: #64748b;
            margin-bottom: 4px;
        }
        .id-card-back-contact-box {
            background: #f1f5f9;
            border: 0.5px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 5px;
        }
        .id-card-back-row {
            display: flex;
            margin-bottom: 1px;
        }
        .id-card-back-row:last-child {
            margin-bottom: 0;
        }
        .id-card-back-label {
            width: 32px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .id-card-back-value {
            flex: 1;
            font-weight: bold;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .id-card-back-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 4px 8px;
            border-top: 0.5px solid #e2e8f0;
            height: 18px;
            background: #ffffff;
        }
        .barcode-mock {
            display: flex;
            align-items: flex-end;
            height: 10px;
            gap: 0.75px;
        }
        .barcode-line {
            background: #000;
            height: 100%;
        }
        .barcode-line.thin { width: 0.4px; }
        .barcode-line.med { width: 0.9px; }
        .barcode-line.thick { width: 1.8px; }
        .id-card-back-sig-line {
            text-align: center;
            font-size: 3.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .sig-placeholder {
            width: 50px;
            height: 8px;
            border-bottom: 0.5px solid #94a3b8;
            margin-bottom: 1.5px;
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
                        <div class="id-card id-card-front">
                            <!-- Premium Background Design Shapes -->
                            <!-- Large semi-transparent watermark logo half-cropped -->
                            <img src="{{ asset('images/AMIS_Logo.png') }}" style="position: absolute; right: -15mm; top: 3mm; width: 48mm; height: 48mm; object-fit: contain; opacity: 0.08; pointer-events: none; z-index: 1;" alt="AMIS Watermark Logo">
                            
                            <!-- Gold Glowing Orbs -->
                            <div style="position: absolute; top: -5mm; right: -5mm; width: 35mm; height: 35mm; border-radius: 50%; background: radial-gradient(circle, rgba(234, 179, 8, 0.12) 0%, transparent 70%); pointer-events: none; z-index: 1;"></div>
                            <div style="position: absolute; bottom: -10mm; left: -10mm; width: 45mm; height: 45mm; border-radius: 50%; background: radial-gradient(circle, rgba(234, 179, 8, 0.1) 0%, transparent 70%); pointer-events: none; z-index: 1;"></div>
                            
                            <!-- Elegant Abstract Curved Waves -->
                            <svg style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; opacity: 0.15;" viewBox="0 0 85.6 54">
                                <path d="M -10 54 L -10 40 C 15 35, 30 50, 50 42 C 68 34, 75 46, 95 36 L 95 54 Z" fill="url(#frontGoldGradient)" />
                                <path d="M -10 54 L -10 43 C 18 38, 32 46, 52 38 C 70 31, 78 41, 95 32 L 95 54 Z" fill="#022c22" opacity="0.6" />
                                <defs>
                                    <linearGradient id="frontGoldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#eab308" />
                                        <stop offset="50%" stop-color="#ca8a04" />
                                        <stop offset="100%" stop-color="#fef08a" />
                                    </linearGradient>
                                </defs>
                            </svg>
                            
                            <!-- Subtle geometric pattern lines overlay -->
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: repeating-linear-gradient(30deg, transparent, transparent 4px, rgba(255, 255, 255, 0.015) 4px, rgba(255, 255, 255, 0.015) 8px); pointer-events: none; z-index: 1;"></div>



                            <div class="id-card-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <img class="id-card-logo" src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                                    <div>
                                        <div class="id-card-school-name">Al Munawwara Islamic School</div>
                                        <div class="id-card-school-sub">OFFICIAL AMIS STUDENT ID</div>
                                    </div>
                                </div>
                                <div style="font-family: 'Times New Roman', serif; font-size: 7px; font-weight: bold; color: #ffffff; direction: rtl; text-align: right; margin-right: 4px; line-height: 1;">
                                    المدرسة المنورة الإسلامية
                                </div>
                            </div>
                            
                            <div class="id-card-body" style="display: flex; padding: 6px 8px; gap: 6px; height: calc(54mm - 44px); align-items: center; justify-content: space-between;">
                                <div class="id-card-photo-box" style="width: 18mm; height: 23.4mm; border-radius: 3px; border: 1.2px solid #eab308; background: #022c22; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="Photo" class="id-card-photo">
                                    @else
                                        <span style="font-size: 8px; font-weight: bold; color: #ef4444; text-align: center; text-transform: uppercase; line-height: 1.2; padding: 2px;">MISSING PHOTO</span>
                                    @endif
                                </div>
                                <div class="id-card-details" style="flex: 1; display: flex; flex-direction: column; justify-content: center; min-width: 0; gap: 3px;">
                                    <div class="id-card-name" title="{{ $fullName }}" style="font-size: {{ $nameFontSize }}; font-weight: 900; color: #ffffff; text-transform: uppercase; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-bottom: 0.5px solid rgba(234, 179, 8, 0.4); padding-bottom: 1.5px; margin-bottom: 1px;">{{ $displayName }}</div>
                                    <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                                        <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">ID No:</div>
                                        <div class="id-card-value font-mono" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $student->student_number }}</div>
                                    </div>
                                    <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                                        <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">LRN:</div>
                                        <div class="id-card-value font-mono" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $applicant->lrn ?: 'N/A' }}</div>
                                    </div>
                                    <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                                        <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">Grade:</div>
                                        <div class="id-card-value" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $student->grade_level }}</div>
                                    </div>
                                    <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                                        <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">Adviser:</div>
                                        <div class="id-card-value" title="{{ $advisorName }}" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $advisorName }}</div>
                                    </div>
                                </div>
                                <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; position: relative; padding: 2.5px; width: 13.5mm; height: 13.5mm; background: transparent;">
                                    <!-- Corner brackets -->
                                    <div style="position: absolute; top: 0; left: 0; width: 3.5mm; height: 3.5mm; border-top: 1.2px solid #ffffff; border-left: 1.2px solid #ffffff; border-top-left-radius: 2px;"></div>
                                    <div style="position: absolute; top: 0; right: 0; width: 3.5mm; height: 3.5mm; border-top: 1.2px solid #ffffff; border-right: 1.2px solid #ffffff; border-top-right-radius: 2px;"></div>
                                    <div style="position: absolute; bottom: 0; left: 0; width: 3.5mm; height: 3.5mm; border-bottom: 1.2px solid #ffffff; border-left: 1.2px solid #ffffff; border-bottom-left-radius: 2px;"></div>
                                    <div style="position: absolute; bottom: 0; right: 0; width: 3.5mm; height: 3.5mm; border-bottom: 1.2px solid #ffffff; border-right: 1.2px solid #ffffff; border-bottom-right-radius: 2px;"></div>
                                    
                                    <!-- QR Code Image -->
                                    <img src="https://quickchart.io/qr?text={{ urlencode('https://amis.edu.ph/v/' . $student->obfuscated_id) }}&dark=ffffff&light=0000&margin=1&format=svg" style="width: 100%; height: 100%; object-fit: contain; display: block;" alt="QR Code">
                                </div>
                            </div>
                            
                            <div class="id-card-footer" style="background: #ffffff; color: #064e3b;">
                                <span>Student</span>
                                <span>S.Y. {{ $student->school_year }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ID Back -->
                    <div class="id-card-item">
                        <span class="id-card-side-label">Back Side</span>
                        <div class="id-card id-card-back">
                            <div class="id-card-back-header">
                                Important School Notice
                            </div>
                            
                            <div class="id-card-back-body">
                                <div class="id-card-back-title">Rules & Regulations</div>
                                <div class="id-card-back-terms" style="margin-bottom: 2px;">
                                    {{ $cardNotice }}
                                </div>
                                
                                <div class="id-card-back-contact-box" style="margin-bottom: 3px; padding: 3px 5px;">
                                    <table style="width: 100%; border-collapse: collapse; border: none; table-layout: auto;">
                                        <tr style="line-height: 1.25;">
                                            <td style="width: 1%; white-space: nowrap; font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 6px 0.5px 0; vertical-align: top;">IN CASE OF EMERGENCY:</td>
                                            <td style="font-weight: 900; color: #064e3b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 0; vertical-align: top;">{{ $emergencyName }}</td>
                                        </tr>
                                        <tr style="line-height: 1.25;">
                                            <td style="width: 1%; white-space: nowrap; font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 6px 0.5px 0; vertical-align: top;">CONTACT NO:</td>
                                            <td class="font-mono" style="font-weight: 900; color: #064e3b; font-size: 7.5px; padding: 0.5px 0; vertical-align: top; white-space: nowrap;">{{ $emergencyPhone }}</td>
                                        </tr>
                                        <tr style="line-height: 1.25;">
                                            <td style="width: 1%; white-space: nowrap; font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 6px 0.5px 0; vertical-align: top;">ADDRESS:</td>
                                            <td style="white-space: normal; line-height: 1.15; font-size: 6.5px; text-transform: uppercase; padding: 0.5px 0; vertical-align: top; color: #1e293b; font-weight: bold;" title="{{ $homeAddress }}">{{ \Illuminate\Support\Str::upper($homeAddress) }}</td>
                                        </tr>
                                    </table>
                                </div>

                                <!-- School Details & Quote -->
                                <div style="border-top: 0.5px solid #cbd5e1; padding-top: 2.5px; text-align: center; line-height: 1.2;">
                                    <div style="font-size: 4.5px; color: #475569; font-weight: bold;">
                                        Don Julian Rodriguez Avenue, Ma-a, Davao City, Philippines, 8000
                                    </div>
                                    <div style="font-size: 4.5px; font-style: italic; font-weight: 800; color: #059669; margin-top: 1px;">
                                        "Enabling Our Students To Learn in Fid Dunya Wal Akhira"
                                    </div>
                                    <div style="font-size: 5px; font-weight: bold; color: #064e3b; text-transform: uppercase; margin-top: 2px; letter-spacing: 0.3px;">
                                        Valid until March 31, 2027
                                    </div>
                                    <div style="margin-top: 2.5px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5px;">
                                        <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text={{ urlencode($student->student_number) }}&scale=2&height=6" style="height: 5.5mm; max-width: 45mm; object-fit: contain;" alt="Barcode">
                                        <span style="font-size: 4px; font-family: monospace; font-weight: bold; color: #1e293b; letter-spacing: 0.5px;">{{ $student->student_number }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="id-card-back-footer" style="padding: 2px 8px; height: 20px; display: flex; justify-content: center; align-items: center; background: #ffffff;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 15px; width: auto; object-fit: contain;">
                                    <div style="display: flex; flex-direction: column; line-height: 1.1; text-align: left;">
                                        <span style="font-size: 6px; font-weight: 900; color: #064e3b; font-family: 'Times New Roman', serif; direction: rtl;">المدرسة المنورة الإسلامية</span>
                                        <span style="font-size: 5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Al Munawwara Islamic School</span>
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
