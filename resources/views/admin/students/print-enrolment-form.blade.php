<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolment Application Form - {{ $student->student_number }} - {{ $student->full_name }}</title>
    
    <!-- Google Fonts for authentic serif typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Times+New+Roman&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            background-color: #f1f5f9;
            color: #000;
            line-height: 1.25;
            padding: 20px 0;
        }

        /* Top Action Bar (Screen Only) */
        .action-bar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            font-family: 'Inter', sans-serif;
            position: sticky;
            top: 10px;
            z-index: 100;
        }

        .action-bar h2 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: #059669;
            color: white;
        }
        .btn-primary:hover {
            background-color: #047857;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        /* Paper Document Layout (A4 Scale) */
        .paper-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            padding: 14mm 16mm 14mm 16mm;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .paper-page-break {
            page-break-after: always;
            break-after: page;
        }

        /* PAGE 1: Header Layout */
        .top-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            gap: 10px;
        }

        .header-left-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
        }

        .header-logo-amis {
            width: 88px;
            height: 88px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .header-school-text {
            text-align: center;
            flex-grow: 1;
            padding: 0 5px;
        }

        .school-name {
            font-size: 1.25rem;
            font-weight: bold;
            letter-spacing: 0.2px;
            color: #000;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            line-height: 1.2;
        }

        .school-address {
            font-size: 0.95rem;
            margin-top: 3px;
            color: #000;
            white-space: nowrap;
            text-align: center;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .header-logo-deped {
            width: 82px;
            height: 82px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .refund-notice-box {
            border: 2px solid #dc2626;
            padding: 6px 10px;
            text-align: center;
            font-weight: bold;
            font-size: 0.9rem;
            line-height: 1.2;
            color: #dc2626;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* Middle Header Row: Form Title, Checkboxes, 2x2 Photo Box */
        .form-middle-grid {
            display: grid;
            grid-template-columns: 1fr 90px 135px;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 12px;
        }

        .form-title-area {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .form-title {
            font-size: 1.35rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .sy-title {
            font-size: 1.15rem;
            font-weight: bold;
            margin-top: 3px;
            margin-bottom: 16px;
        }

        /* Student Info Header & LRN */
        .student-info-bar {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-top: 4px;
        }

        .section-title {
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .lrn-container {
            font-size: 1rem;
            display: flex;
            align-items: baseline;
            gap: 4px;
            white-space: nowrap;
        }

        .lrn-input {
            border: none;
            border-bottom: 1.5px solid #000;
            font-family: 'Times New Roman', Times, serif;
            font-size: 1.05rem;
            font-weight: bold;
            width: 200px;
            outline: none;
            padding: 0 4px;
        }

        /* OLD / NEW Checkboxes Vertically Stacked */
        .checkbox-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
            align-items: flex-start;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            font-weight: bold;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 1.5px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            background: #fff;
        }

        /* 2x2 Photo Box */
        .photo-box {
            width: 135px;
            height: 135px;
            border: 1.5px solid #000;
            background: #fff;
            justify-self: end;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Section Header Divider */
        .section-header-row {
            font-size: 1.05rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 18px;
            margin-bottom: 10px;
            white-space: nowrap;
        }

        /* Fillable Text Lines & Input Fields */
        .field-container {
            margin-bottom: 14px;
            width: 100%;
        }

        .input-line {
            border: none;
            border-bottom: 1.5px solid #000;
            font-family: 'Times New Roman', Times, serif;
            font-size: 1.05rem;
            font-weight: bold;
            outline: none;
            padding: 1px 4px;
            width: 100%;
            background: transparent;
        }

        .label-text {
            font-size: 0.95rem;
            font-weight: normal;
            margin-top: 2px;
            display: block;
        }

        .grid-5-col {
            display: grid;
            grid-template-columns: 2.5fr 2fr 2fr 1.2fr 1.5fr;
            gap: 15px;
        }

        .grid-3-col-birth {
            display: grid;
            grid-template-columns: 2.5fr 3.5fr 2fr;
            gap: 15px;
        }

        .grid-2-col-school {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 15px;
        }

        .grid-parent-row {
            display: grid;
            grid-template-columns: 3fr 2.5fr 3fr;
            gap: 15px;
        }

        .grid-children-row {
            display: grid;
            grid-template-columns: 4.5fr 1.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 10px;
        }

        /* Bottom Section: Applicant Lives With */
        .lives-with-row {
            margin-top: 22px;
            display: flex;
            align-items: center;
            gap: 25px;
            font-size: 1rem;
        }

        .radio-option {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .radio-line {
            display: inline-block;
            width: 40px;
            border-bottom: 1.5px solid #000;
            text-align: center;
            font-weight: bold;
            height: 18px;
            line-height: 18px;
        }

        /* PAGE 2 STYLES */
        .p2-question-row {
            margin-top: 18px;
            font-size: 1.05rem;
            line-height: 1.4;
        }

        .p2-inline-line {
            display: inline-block;
            border-bottom: 1.5px solid #000;
            width: 70px;
            height: 18px;
            vertical-align: bottom;
            text-align: center;
            font-weight: bold;
        }

        .p2-explain-block {
            margin-top: 10px;
            margin-bottom: 16px;
        }

        .p2-explain-label {
            font-size: 1.05rem;
            display: inline-block;
            margin-bottom: 4px;
        }

        .p2-full-line {
            border: none;
            border-bottom: 1.5px solid #000;
            width: 100%;
            font-family: 'Times New Roman', Times, serif;
            font-size: 1.05rem;
            font-weight: bold;
            outline: none;
            padding: 1px 4px;
            margin-bottom: 8px;
            background: transparent;
        }

        .grid-physician-row {
            display: grid;
            grid-template-columns: 4fr 3fr;
            gap: 20px;
            margin-top: 20px;
            margin-bottom: 25px;
        }

        .p2-emergency-grid {
            display: grid;
            grid-template-columns: 4.5fr 3.5fr 3fr;
            gap: 15px;
            margin-top: 10px;
            margin-bottom: 25px;
        }

        .p2-policy-text {
            font-size: 1rem;
            line-height: 1.45;
            margin-top: 14px;
            text-align: justify;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 30px;
            margin-top: 40px;
            margin-bottom: 8px;
        }

        .signature-disclaimer {
            font-size: 0.85rem;
            font-style: italic;
            margin-bottom: 30px;
        }

        .office-perforated-line {
            border: none;
            border-top: 1.5px dashed #000;
            margin: 20px 0 15px 0;
        }

        .grid-office-row {
            display: grid;
            grid-template-columns: 3.5fr 2.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 18px;
            font-size: 1rem;
        }

        .date-slash-inputs {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
        }

        .date-slash-input {
            border: none;
            border-bottom: 1.5px solid #000;
            width: 45px;
            text-align: center;
            font-family: 'Times New Roman', Times, serif;
            font-size: 1rem;
            font-weight: bold;
            outline: none;
        }

        .attachments-list {
            font-size: 0.95rem;
            line-height: 1.5;
            margin-left: 20px;
        }

        .attachments-title {
            font-size: 1rem;
            font-style: italic;
            margin-bottom: 4px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: none;
                padding: 0;
            }

            .action-bar {
                display: none !important;
            }

            .paper-container {
                box-shadow: none;
                padding: 10mm 12mm 10mm 12mm;
                width: 100%;
                margin: 0;
            }

            .input-line, .p2-full-line, .lrn-input {
                border-bottom: 1.5px solid #000 !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar for Screen Viewing -->
    <div class="action-bar">
        <h2>📄 Official Enrolment Application Form - {{ $student->student_number }}</h2>
        <div class="btn-group">
            <button class="btn btn-secondary" onclick="window.close()">Close</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Save as PDF</button>
        </div>
    </div>

    @php
        $app = $applicant;
        $studentType = strtoupper($app->student_type ?? 'OLD');
        $isOld = str_contains($studentType, 'OLD');
        $isNew = str_contains($studentType, 'NEW') || !$isOld;

        $fatherFull = trim(($app->father_first_name ?? '') . ' ' . ($app->father_middle_name ?? '') . ' ' . ($app->father_last_name ?? ''));
        $motherFull = trim(($app->mother_first_name ?? '') . ' ' . ($app->mother_middle_name ?? '') . ' ' . ($app->mother_last_name ?? ''));

        $fatherPresent = !empty($app->father_first_name);
        $motherPresent = !empty($app->mother_first_name);
        $bothParents = $fatherPresent && $motherPresent;
        $singleParent = ($fatherPresent || $motherPresent) && !$bothParents;
        $guardianPresent = !$fatherPresent && !$motherPresent;

        $fullAddress = $app->address ?? $app->street_address ?? $app->home_address ?? '';
        if (empty($fullAddress)) {
            $fullAddress = implode(', ', array_filter([
                $app->street_address ?? null,
                $app->city ?? null,
                $app->state_province ?? null,
                $app->country ?? null
            ]));
        }

        $photoUrl = null;
        if ($app && $app->photo_2x2_url) {
            $photoUrl = \App\Support\EnrollmentStorage::url($app->photo_2x2_url);
        }
    @endphp

    <!-- =================================================================== -->
    <!-- PAGE 1: ENROLMENT APPLICATION FORM -->
    <!-- =================================================================== -->
    <div class="paper-container paper-page-break">
        
        <!-- Top Header Row -->
        <div class="top-header-row">
            <div class="header-left-group">
                <img src="/images/AMIS_Logo.png" alt="AMIS Logo" class="header-logo-amis" onerror="this.src='https://via.placeholder.com/88?text=AMIS'">
                <div class="header-school-text">
                    <h1 class="school-name">AL MUNAWWARA ISLAMIC SCHOOL</h1>
                    <p class="school-address">Bugac Ma-a Road, Davao City Philippines</p>
                </div>
            </div>

            <div class="header-right-group">
                <img src="/images/deped_logo.png" alt="DepEd Logo" class="header-logo-deped" onerror="this.src='https://via.placeholder.com/82?text=DepEd'">
                <div class="refund-notice-box">
                    NO REFUND OF<br>ENROLLMENT FEE
                </div>
            </div>
        </div>

        <!-- Middle Header Grid -->
        <div class="form-middle-grid">
            <div class="form-title-area">
                <h2 class="form-title">ENROLMENT APPLICATION FORM</h2>
                <p class="sy-title">SY {{ $app->school_year ?? '2026-2027' }}</p>
                
                <div class="student-info-bar">
                    <span class="section-title">STUDENT INFORMATION</span>
                    <div class="lrn-container">
                        <span>LRN:</span>
                        <input type="text" class="lrn-input" value="{{ $app->lrn ?? $student->student_number }}">
                    </div>
                </div>
            </div>

            <!-- Vertically Stacked OLD / NEW Checkboxes -->
            <div class="checkbox-stack">
                <div class="checkbox-item">
                    <span class="custom-checkbox">{{ $isOld ? '✓' : '' }}</span>
                    <span>OLD</span>
                </div>
                <div class="checkbox-item">
                    <span class="custom-checkbox">{{ $isNew ? '✓' : '' }}</span>
                    <span>NEW</span>
                </div>
            </div>

            <!-- 2x2 Photo Square Box -->
            <div class="photo-box">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="Student 2x2 Photo">
                @endif
            </div>
        </div>

        <!-- SECTION 1: STUDENT INFORMATION FIELDS -->
        <div class="field-container">
            <div class="grid-5-col">
                <div>
                    <input type="text" class="input-line" value="{{ $app->last_name ?? '' }}">
                    <span class="label-text">Last</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->first_name ?? '' }}">
                    <span class="label-text">First</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->middle_name ?? '' }}">
                    <span class="label-text">Middle</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ ucfirst(strtolower($app->gender ?? '')) }}">
                    <span class="label-text">Sex</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $student->grade_level ?? $app->grade_level ?? '' }}">
                    <span class="label-text">Grade Level</span>
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="field-container" style="margin-top: 14px;">
            <input type="text" class="input-line" value="{{ $fullAddress }}">
            <span class="label-text">Address</span>
        </div>

        <!-- Birth Details & Religion -->
        <div class="field-container" style="margin-top: 14px;">
            <div class="grid-3-col-birth">
                <div>
                    <input type="text" class="input-line" value="{{ $app?->date_of_birth ? $app->date_of_birth->format('M d, Y') : '' }}">
                    <span class="label-text">Date of Birth</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->place_of_birth ?? '' }}">
                    <span class="label-text">Place of Birth</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->religion ?? 'Islam' }}">
                    <span class="label-text">Religion</span>
                </div>
            </div>
        </div>

        <!-- Previous Attended School Name -->
        <div class="field-container" style="margin-top: 14px;">
            <input type="text" class="input-line" value="{{ $app->previous_school_name ?? '' }}">
            <span class="label-text">Previous Attended School Name</span>
        </div>

        <!-- Previous School Address & Telephone -->
        <div class="field-container" style="margin-top: 14px;">
            <div class="grid-2-col-school">
                <div>
                    <input type="text" class="input-line" value="{{ $app->previous_school_address ?? '' }}">
                    <span class="label-text">Previous School Address</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->mobile_number ?? $app->parent_mobile ?? '' }}">
                    <span class="label-text">Telephone No.</span>
                </div>
            </div>
        </div>

        <!-- SECTION 2: PARENT INFORMATION -->
        <div class="section-header-row" style="margin-top: 22px;">
            PARENT INFORMATION
        </div>

        <!-- Father's Details -->
        <div class="field-container" style="margin-top: 10px;">
            <div class="grid-parent-row">
                <div>
                    <input type="text" class="input-line" value="{{ $fatherFull }}">
                    <span class="label-text">Father's Full Name</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->father_occupation ?? '' }}">
                    <span class="label-text">Occupation</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ implode(' / ', array_filter([$app->parent_mobile ?? null, $app->parent_email ?? null])) }}">
                    <span class="label-text">Tel./Email address</span>
                </div>
            </div>
        </div>

        <!-- Mother's Details -->
        <div class="field-container" style="margin-top: 14px;">
            <div class="grid-parent-row">
                <div>
                    <input type="text" class="input-line" value="{{ $motherFull }}">
                    <span class="label-text">Mother's Full Name</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app->mother_occupation ?? '' }}">
                    <span class="label-text">Occupation</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ implode(' / ', array_filter([$app->parent_mobile ?? null, $app->parent_email ?? null])) }}">
                    <span class="label-text">Tel./Email address</span>
                </div>
            </div>
        </div>

        <!-- Home Address -->
        <div class="field-container" style="margin-top: 14px;">
            <input type="text" class="input-line" value="{{ $app->home_address ?? $fullAddress }}">
            <span class="label-text">Home Address</span>
        </div>

        <!-- SECTION 3: OTHER CHILDREN INFORMATION -->
        <div class="section-header-row" style="margin-top: 22px;">
            OTHER CHILDREN INFORMATION
        </div>

        @php
            $siblingList = collect($siblings ?? [])->take(3)->values();
        @endphp

        @for($i = 0; $i < 3; $i++)
            @php
                $sib = $siblingList->get($i);
                $sibName = $sib ? trim(($sib->first_name ?? '') . ' ' . ($sib->last_name ?? '')) : '';
                $sibGrade = $sib->grade_level ?? '';
            @endphp
            <div class="field-container" style="{{ $i === 0 ? 'margin-top: 10px;' : '' }}">
                <div class="grid-children-row">
                    <div>
                        <input type="text" class="input-line" value="{{ $sibName }}">
                        <span class="label-text">Name</span>
                    </div>
                    <div>
                        <input type="text" class="input-line" value="">
                        <span class="label-text">Age</span>
                    </div>
                    <div>
                        <input type="text" class="input-line" value="{{ $sibGrade }}">
                        <span class="label-text">Grade Level</span>
                    </div>
                </div>
            </div>
        @endfor

        <!-- SECTION 4: APPLICANT LIVES WITH -->
        <div class="lives-with-row">
            <span>Applicant lives with:</span>
            
            <div class="radio-option">
                <span class="radio-line">{{ $bothParents ? '✓' : '' }}</span>
                <span>Both Parents</span>
            </div>

            <div class="radio-option">
                <span class="radio-line">{{ $singleParent ? '✓' : '' }}</span>
                <span>Mother/Father</span>
            </div>

            <div class="radio-option">
                <span class="radio-line">{{ $guardianPresent ? '✓' : '' }}</span>
                <span>Guardian</span>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- PAGE 2: MEDICAL INFORMATION, EMERGENCY CONTACTS, REFERRAL & POLICIES -->
    <!-- =================================================================== -->
    <div class="paper-container">
        
        <div class="section-header-row" style="margin-top: 5px; margin-bottom: 15px;">
            MEDICAL INFORMATION
        </div>

        @php
            $hasPsych = !empty($app->psych_testing) && $app->psych_testing !== 'no';
            $hasMed   = !empty($app->prescription_med) && $app->prescription_med !== 'no';
        @endphp

        <!-- Question 1: Psychological Testing / Learning Disabilities -->
        <div class="p2-question-row">
            Has the student ever had psychological testing or been screened for academic difficulties or learning disabilities? 
            &nbsp; YES <span class="p2-inline-line">{{ $hasPsych ? '✓' : '' }}</span> 
            &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasPsych ? '✓' : '' }}</span>
        </div>

        <div class="p2-explain-block">
            <span class="p2-explain-label">If yes, please explain:</span>
            <input type="text" class="p2-full-line" value="{{ $app->med_explanation ?? '' }}">
            <input type="text" class="p2-full-line">
        </div>

        <!-- Question 2: Prescription Medication -->
        <div class="p2-question-row" style="margin-top: 20px;">
            Prescription Medication: 
            &nbsp; YES <span class="p2-inline-line">{{ $hasMed ? '✓' : '' }}</span> 
            &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasMed ? '✓' : '' }}</span>
        </div>

        <div class="p2-explain-block">
            <span class="p2-explain-label">If yes, please explain:</span>
            <input type="text" class="p2-full-line" value="{{ $app->current_medications ?? '' }}">
            <input type="text" class="p2-full-line">
        </div>

        <!-- Family Physician & Phone -->
        <div class="grid-physician-row">
            <div>
                <input type="text" class="input-line" value="{{ $app->family_physician ?? '' }}">
                <span class="label-text">Family Physician:</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ $app->physician_phone ?? '' }}">
                <span class="label-text">Phone:</span>
            </div>
        </div>

        <!-- EMERGENCY CONTACTS SECTION -->
        <div class="section-header-row" style="margin-top: 25px;">
            EMERGENCY CONTACTS <span style="font-size: 0.95rem; font-weight: normal; text-transform: none;">(Other than above names)</span>
        </div>

        <div class="p2-emergency-grid">
            <div>
                <input type="text" class="input-line" value="{{ $app->emergency_name ?? '' }}">
                <span class="label-text">Name</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ $app->emergency_relationship ?? '' }}">
                <span class="label-text">Relationship</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ $app->emergency_phone ?? '' }}">
                <span class="label-text">Phone</span>
            </div>
        </div>

        <!-- REFERRAL SECTION -->
        <div class="section-header-row" style="margin-top: 25px;">
            REFERRAL
        </div>

        <div class="field-container" style="margin-top: 10px;">
            <input type="text" class="input-line" value="{{ $app->referral_source ?? '' }}">
            <span class="label-text">I heard about AMIS from</span>
        </div>

        <!-- Policy Agreements & Terms -->
        <p class="p2-policy-text">
            I understand that if and when the applicant is enrolled, I agree to comply with the rules, regulations and policies of Al Munawwara Islamic School as outlined in the Parent Student Handbook and other official communications.
        </p>

        <p class="p2-policy-text">
            It is further understood that Al Munawwara Islamic School reserves the right to dismiss any student for any reason deemed to be in the best interest of the school. Dismissal of the student does not release the parent from the financial obligations related to the school fees and other fees thereat.
        </p>

        <!-- SIGNATURE SECTION -->
        <div class="section-header-row" style="margin-top: 25px;">
            SIGNATURE
        </div>

        <div class="signature-grid">
            <div>
                <input type="text" class="input-line" value="{{ $fatherFull ?: $motherFull }}">
                <span class="label-text">Parent/Guardian</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ $student->created_at->format('M d, Y') }}">
                <span class="label-text">Date</span>
            </div>
        </div>

        <p class="signature-disclaimer">
            *Only completed application will be accepted. Submission of an application does not guarantee admission
        </p>

        <!-- Cutoff Dashed Perforated Line for Office Receipt -->
        <hr class="office-perforated-line">

        <!-- Application Submitted On / Paid / OR No -->
        <div class="grid-office-row">
            <div>
                <span>Application submitted on:</span>
                <div class="date-slash-inputs">
                    <input type="text" class="date-slash-input" value="{{ $student->created_at->format('m') }}"> /
                    <input type="text" class="date-slash-input" value="{{ $student->created_at->format('d') }}"> /
                    <input type="text" class="date-slash-input" value="{{ $student->created_at->format('Y') }}">
                </div>
            </div>
            <div>
                <span>Paid:</span>
                <input type="text" class="input-line" style="width: 110px; display: inline-block;" value="{{ $app?->payment?->amount_paid ? '₱' . number_format($app->payment->amount_paid, 2) : '' }}">
            </div>
            <div>
                <span>OR No.:</span>
                <input type="text" class="input-line" style="width: 110px; display: inline-block;" value="{{ $app?->payment?->reference_number ?? '' }}">
            </div>
        </div>

        <!-- Attachments Checklist -->
        <div class="attachments-title">To be attached:</div>
        <ol class="attachments-list">
            <li>Photo copy of Birth Certificate</li>
            <li>Official Transcript from Previous School (Report Card)</li>
            <li>Medical Record (If any)</li>
            <li>Photo copy of Marriage Contract of Parents</li>
            <li>Picture 2 x 2</li>
        </ol>

    </div>

</body>
</html>
