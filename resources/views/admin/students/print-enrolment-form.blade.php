<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrolment Application Form - {{ $student->student_number }} - {{ $student->full_name }}</title>
    
    <!-- Premium Google Fonts: Merriweather for Headers & Inter for Fillable Data -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Merriweather:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Merriweather', Georgia, serif;
            background-color: #f1f5f9;
            color: #0f172a;
            line-height: 1.3;
            padding: 20px 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Bar (Screen Only) */
        .action-bar {
            max-width: 840px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            font-family: 'Inter', sans-serif;
            position: sticky;
            top: 10px;
            z-index: 100;
            border: 1px solid #e2e8f0;
        }

        .action-bar h2 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-primary {
            background-color: #059669;
            color: white;
        }
        .btn-primary:hover {
            background-color: #047857;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        /* Paper Document Layout (A4 Scale) */
        .paper-container {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            padding: 14mm 16mm 14mm 16mm;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
            border-radius: 2px;
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
            margin-bottom: 16px;
            gap: 10px;
        }

        .header-left-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .header-logo-amis {
            width: 72px;
            height: 72px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .header-school-text {
            text-align: center;
            flex-grow: 1;
            padding: 0 5px;
        }

        .school-name {
            font-family: 'Merriweather', serif;
            font-size: 1.18rem;
            font-weight: 900;
            letter-spacing: 0.3px;
            color: #0f172a;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
            line-height: 1.2;
        }

        .school-address {
            font-family: 'Merriweather', serif;
            font-size: 0.88rem;
            margin-top: 2px;
            color: #334155;
            white-space: nowrap;
            text-align: center;
        }

        .header-right-group {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .header-logo-deped {
            width: 66px;
            height: 66px;
            object-fit: contain;
            flex-shrink: 0;
        }

        /* NO REFUND OF ENROLLMENT FEE Box styling */
        .refund-notice-box {
            border: 2px solid #dc2626;
            padding: 6px 12px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 0.88rem;
            line-height: 1.25;
            color: #dc2626;
            text-transform: uppercase;
            white-space: nowrap;
            border-radius: 5px;
            margin: 0;
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-self: center;
        }

        /* Middle Header Row: Form Title, Checkboxes, 2x2 Photo Box */
        .form-middle-grid {
            display: grid;
            grid-template-columns: 1fr auto 125px;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .form-title-area {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .form-title {
            font-family: 'Merriweather', serif;
            font-size: 1.35rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            color: #0f172a;
        }

        .sy-title {
            font-family: 'Merriweather', serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin-top: 3px;
            margin-bottom: 14px;
            color: #1e293b;
        }

        /* Student Info Header & LRN */
        .student-info-bar {
            display: flex;
            align-items: baseline;
            gap: 15px;
            margin-top: 4px;
        }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .lrn-container {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: baseline;
            gap: 6px;
            white-space: nowrap;
        }

        .lrn-input {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            width: 200px;
            outline: none;
            padding: 0 4px;
            text-transform: uppercase;
        }

        /* OLD / NEW Checkboxes Vertically Stacked */
        .checkbox-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 25px;
            align-items: flex-start;
            font-family: 'Inter', sans-serif;
            padding-right: 5px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
            cursor: pointer;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 900;
            background: #fff;
            line-height: 1;
            flex-shrink: 0;
            border-radius: 3px;
        }

        /* 2x2 Photo Square Box */
        .photo-box {
            width: 125px;
            height: 125px;
            border: 2px solid #0f172a;
            background: #fafafa;
            justify-self: end;
            align-self: flex-start;
            margin-top: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 3px;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Section Header Divider */
        .section-header-row {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-top: 18px;
            margin-bottom: 10px;
            white-space: nowrap;
        }

        /* Fillable Text Lines & Input Fields with Auto-Truncate Safety */
        .field-container {
            margin-bottom: 12px;
            width: 100%;
        }

        .input-line {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 4px;
            width: 100%;
            background: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            text-transform: uppercase;
        }

        .label-text {
            font-family: 'Inter', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #475569;
            margin-top: 3px;
            display: block;
        }

        .grid-5-col {
            display: grid;
            grid-template-columns: 2.5fr 2fr 2fr 1.2fr 1.5fr;
            gap: 15px;
        }

        .grid-4-col-birth {
            display: grid;
            grid-template-columns: 1.2fr 2.5fr 3.5fr 2fr;
            gap: 15px;
        }

        .grid-2-col-school {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 15px;
        }

        .grid-parent-row {
            display: grid;
            grid-template-columns: 3.8fr 2.1fr 2.9fr;
            gap: 15px;
            align-items: flex-end;
        }

        .grid-children-row {
            display: grid;
            grid-template-columns: 4.5fr 1.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 8px;
        }

        /* Bottom Section: Applicant Lives With */
        .lives-with-row {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 25px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .radio-option {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .radio-line {
            display: inline-block;
            width: 40px;
            border-bottom: 1.5px solid #0f172a;
            text-align: center;
            font-weight: 800;
            height: 18px;
            line-height: 18px;
        }

        /* PAGE 2 STYLES */
        .p2-question-row {
            margin-top: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            line-height: 1.45;
            color: #0f172a;
        }

        .p2-inline-line {
            display: inline-block;
            border-bottom: 1.5px solid #0f172a;
            width: 70px;
            height: 18px;
            vertical-align: bottom;
            text-align: center;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
        }

        .p2-explain-block {
            margin-top: 8px;
            margin-bottom: 14px;
        }

        .p2-explain-label {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            display: inline-block;
            margin-bottom: 4px;
        }

        .p2-full-line {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            width: 100%;
            font-family: 'Inter', sans-serif;
            font-size: 0.98rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
            padding: 2px 4px;
            margin-bottom: 6px;
            background: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
            text-transform: uppercase;
        }

        .grid-physician-row {
            display: grid;
            grid-template-columns: 4fr 3fr;
            gap: 20px;
            margin-top: 16px;
            margin-bottom: 20px;
        }

        .p2-emergency-grid {
            display: grid;
            grid-template-columns: 4.5fr 3.5fr 3fr;
            gap: 15px;
            margin-top: 8px;
            margin-bottom: 20px;
        }

        .p2-policy-text {
            font-family: 'Merriweather', serif;
            font-size: 0.92rem;
            line-height: 1.5;
            margin-top: 12px;
            text-align: justify;
            color: #1e293b;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 5fr 2.5fr;
            gap: 30px;
            margin-top: 35px;
            margin-bottom: 8px;
        }

        .signature-disclaimer {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-style: italic;
            color: #64748b;
            margin-bottom: 25px;
        }

        .office-perforated-line {
            border: none;
            border-top: 1.5px dashed #64748b;
            margin: 18px 0 14px 0;
        }

        .grid-office-row {
            display: grid;
            grid-template-columns: 3.5fr 2.5fr 2.5fr;
            gap: 15px;
            margin-bottom: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .date-slash-inputs {
            display: inline-flex;
            align-items: baseline;
            gap: 3px;
        }

        .date-slash-input {
            border: none;
            border-bottom: 1.5px solid #0f172a;
            width: 45px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            outline: none;
        }

        /* ATTACHMENTS CHECKLIST: Reduced font size by -0.5 specifically for checklist items */
        .attachments-title {
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-style: italic;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .attachments-list {
            font-family: 'Inter', sans-serif;
            font-size: 0.72rem;
            line-height: 1.4;
            margin-left: 20px;
            color: #334155;
        }

        /* Print Media Styles for Perfect PDF Save */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

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

            img {
                max-width: 100% !important;
                display: block !important;
                visibility: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
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
            <button class="btn btn-secondary" onclick="window.close()">Close Window</button>
            <button class="btn btn-primary" onclick="triggerPrintPDF()">🖨️ Print / Save as PDF</button>
        </div>
    </div>

    @php
        $app = $applicant;
        $studentType = strtoupper($app->student_type ?? 'OLD');
        $isOld = str_contains($studentType, 'OLD');
        $isNew = str_contains($studentType, 'NEW') || !$isOld;

        // Base64 logo encoder helper
        $getLogoBase64 = function($relativePath) {
            $path = public_path($relativePath);
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . ($type === 'svg' ? 'svg+xml' : $type) . ';base64,' . base64_encode($data);
            }
            return asset($relativePath);
        };

        $amisLogoSrc = $getLogoBase64('images/AMIS_Logo.png');
        $depedLogoSrc = $getLogoBase64('images/logo/deped_logo.png');

        // Parent Name formatting helper: Format middle name to Middle Initial (e.g. SAHARODIN G. SALINDAWAN)
        $formatParentName = function($first, $middle, $last) {
            $first = mb_strtoupper(trim($first ?? ''));
            $middle = mb_strtoupper(trim($middle ?? ''));
            $last = mb_strtoupper(trim($last ?? ''));
            
            $mInitial = '';
            if ($middle !== '') {
                $firstChar = mb_substr($middle, 0, 1);
                $mInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
            }
            
            return implode(' ', array_filter([$first, $mInitial, $last]));
        };

        $fatherFull = mb_strtoupper($formatParentName($app->father_first_name ?? '', $app->father_middle_name ?? '', $app->father_last_name ?? ''));
        $motherFull = mb_strtoupper($formatParentName($app->mother_first_name ?? '', $app->mother_middle_name ?? '', $app->mother_last_name ?? ''));

        // Fallback if individual father/mother name fields were empty
        if (empty($fatherFull) && !empty($app->father_name)) {
            $fatherFull = mb_strtoupper(trim($app->father_name));
        }
        if (empty($motherFull) && !empty($app->mother_name)) {
            $motherFull = mb_strtoupper(trim($app->mother_name));
        }

        $parentPhone = trim($app->parent_mobile ?? $app->mobile_number ?? '');
        $parentEmail = strtolower(trim($app->parent_email ?? ''));

        $fatherPresent = !empty($fatherFull);
        $motherPresent = !empty($motherFull);
        $bothParents = $fatherPresent && $motherPresent;
        $singleParent = ($fatherPresent || $motherPresent) && !$bothParents;
        $guardianPresent = !$fatherPresent && !$motherPresent;

        $rawAddress = $app->address ?? $app->street_address ?? $app->home_address ?? '';
        if (empty($rawAddress)) {
            $rawAddress = implode(', ', array_filter([
                $app->street_address ?? null,
                $app->city ?? null,
                $app->state_province ?? null,
                $app->country ?? null
            ]));
        }
        $fullAddress = mb_strtoupper($rawAddress);

        // Student Age Calculation
        $studentAge = '';
        if ($app && $app->date_of_birth) {
            $studentAge = \Carbon\Carbon::parse($app->date_of_birth)->age;
        }

        // Student 2x2 Photo Base64 / Remote URL resolver
        $photoSrc = null;
        if ($app && !empty($app->photo_2x2_url)) {
            $storagePath = storage_path('app/public/' . ltrim($app->photo_2x2_url, '/'));
            if (file_exists($storagePath)) {
                $type = pathinfo($storagePath, PATHINFO_EXTENSION);
                $data = file_get_contents($storagePath);
                $photoSrc = 'data:image/' . ($type ?: 'jpeg') . ';base64,' . base64_encode($data);
            } else {
                $photoSrc = \App\Support\EnrollmentStorage::url($app->photo_2x2_url);
            }
        }
        if (!$photoSrc && !empty($student->photo_url)) {
            $storagePath = storage_path('app/public/' . ltrim($student->photo_url, '/'));
            if (file_exists($storagePath)) {
                $type = pathinfo($storagePath, PATHINFO_EXTENSION);
                $data = file_get_contents($storagePath);
                $photoSrc = 'data:image/' . ($type ?: 'jpeg') . ';base64,' . base64_encode($data);
            } else {
                $photoSrc = \App\Support\EnrollmentStorage::url($student->photo_url);
            }
        }
        if (!$photoSrc && $student && !empty($student->obfuscated_id)) {
            $photoSrc = 'https://amis.edu.ph/student-photo/' . $student->obfuscated_id . '.jpg';
        }

        // Multi-tier Helper function for dynamic font-size calculation on long text
        $getDynamicStyle = function($text, $baseSize = '0.98rem', $mediumSize = '0.80rem', $smallSize = '0.68rem', $xsmallSize = '0.58rem', $t1 = 18, $t2 = 25, $t3 = 32) {
            $len = mb_strlen(trim($text ?? ''));
            if ($len > $t3) {
                return "font-size: {$xsmallSize}; font-weight: 800;";
            }
            if ($len > $t2) {
                return "font-size: {$smallSize}; font-weight: 800;";
            }
            if ($len > $t1) {
                return "font-size: {$mediumSize}; font-weight: 750;";
            }
            return "font-size: {$baseSize};";
        };
    @endphp

    <!-- =================================================================== -->
    <!-- PAGE 1: ENROLMENT APPLICATION FORM -->
    <!-- =================================================================== -->
    <div class="paper-container paper-page-break">
        
        <!-- Top Header Row -->
        <div class="top-header-row">
            <div class="header-left-group">
                <img src="{{ $amisLogoSrc }}" alt="AMIS Logo" class="header-logo-amis">
                <div class="header-school-text">
                    <h1 class="school-name">AL MUNAWWARA ISLAMIC SCHOOL</h1>
                    <p class="school-address">Bugac Ma-a Road, Davao City Philippines</p>
                </div>
            </div>

            <div class="header-right-group">
                <img src="{{ $depedLogoSrc }}" alt="DepEd Logo" class="header-logo-deped">
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
                        <input type="text" class="lrn-input" value="{{ mb_strtoupper($app->lrn ?? $student->student_number) }}" style="{{ $getDynamicStyle($app->lrn ?? $student->student_number, '1rem', '0.88rem', '0.78rem', '0.68rem', 12, 18, 24) }}">
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
                @if($photoSrc)
                    <img src="{{ $photoSrc }}" alt="Student 2x2 Photo">
                @endif
            </div>
        </div>

        <!-- SECTION 1: STUDENT INFORMATION FIELDS -->
        <div class="field-container">
            <div class="grid-5-col">
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->last_name ?? '') }}" style="{{ $getDynamicStyle($app->last_name ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 14, 20, 26) }}">
                    <span class="label-text">Last</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->first_name ?? '') }}" style="{{ $getDynamicStyle($app->first_name ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 14, 20, 26) }}">
                    <span class="label-text">First</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->middle_name ?? '') }}" style="{{ $getDynamicStyle($app->middle_name ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 14, 20, 26) }}">
                    <span class="label-text">Middle</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->gender ?? '') }}">
                    <span class="label-text">Sex</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($student->grade_level ?? $app->grade_level ?? '') }}">
                    <span class="label-text">Grade Level</span>
                </div>
            </div>
        </div>

        <!-- Address -->
        <div class="field-container" style="margin-top: 14px;">
            <input type="text" class="input-line" value="{{ $fullAddress }}" style="{{ $getDynamicStyle($fullAddress, '0.98rem', '0.82rem', '0.70rem', '0.60rem', 45, 65, 85) }}">
            <span class="label-text">Address</span>
        </div>

        <!-- Age, Birth Details & Religion Row -->
        <div class="field-container" style="margin-top: 14px;">
            <div class="grid-4-col-birth">
                <div>
                    <input type="text" class="input-line" value="{{ $studentAge }}">
                    <span class="label-text">Age</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ $app?->date_of_birth ? mb_strtoupper($app->date_of_birth->format('M d, Y')) : '' }}">
                    <span class="label-text">Date of Birth</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->place_of_birth ?? '') }}" style="{{ $getDynamicStyle($app->place_of_birth ?? '', '0.98rem', '0.82rem', '0.70rem', '0.60rem', 22, 35, 45) }}">
                    <span class="label-text">Place of Birth</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->religion ?? 'Islam') }}">
                    <span class="label-text">Religion</span>
                </div>
            </div>
        </div>

        <!-- Previous Attended School Name -->
        <div class="field-container" style="margin-top: 14px;">
            <input type="text" class="input-line" value="{{ mb_strtoupper($app->previous_school_name ?? '') }}" style="{{ $getDynamicStyle($app->previous_school_name ?? '', '0.98rem', '0.82rem', '0.70rem', '0.60rem', 35, 55, 75) }}">
            <span class="label-text">Previous Attended School Name</span>
        </div>

        <!-- Previous School Address & Telephone -->
        <div class="field-container" style="margin-top: 14px;">
            <div class="grid-2-col-school">
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->previous_school_address ?? '') }}" style="{{ $getDynamicStyle($app->previous_school_address ?? '', '0.98rem', '0.82rem', '0.70rem', '0.60rem', 35, 55, 75) }}">
                    <span class="label-text">Previous School Address</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->mobile_number ?? $app->parent_mobile ?? '') }}">
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
                    <input type="text" class="input-line" value="{{ $fatherFull }}" style="{{ $getDynamicStyle($fatherFull, '0.98rem', '0.80rem', '0.68rem', '0.58rem', 18, 25, 32) }}">
                    <span class="label-text">Father's Full Name</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->father_occupation ?? '') }}" style="{{ $getDynamicStyle($app->father_occupation ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 16, 24, 30) }}">
                    <span class="label-text">Occupation</span>
                </div>
                <div>
                    <div style="border-bottom: 1.5px solid #0f172a; padding: 1px 3px; min-height: 34px; display: flex; flex-direction: column; justify-content: flex-end;">
                        <div style="font-family: 'Inter', sans-serif; font-size: 0.88rem; font-weight: 750; color: #0f172a; line-height: 1.2;">
                            {{ $parentPhone }}
                        </div>
                        @if(!empty($parentEmail))
                            <div style="font-family: 'Inter', sans-serif; font-size: 0.78rem; font-weight: 600; color: #0f172a; line-height: 1.2; text-transform: lowercase;">
                                {{ $parentEmail }}
                            </div>
                        @endif
                    </div>
                    <span class="label-text">Tel./Email address</span>
                </div>
            </div>
        </div>

        <!-- Mother's Details -->
        <div class="field-container" style="margin-top: 14px;">
            <div class="grid-parent-row">
                <div>
                    <input type="text" class="input-line" value="{{ $motherFull }}" style="{{ $getDynamicStyle($motherFull, '0.98rem', '0.80rem', '0.68rem', '0.58rem', 18, 25, 32) }}">
                    <span class="label-text">Mother's Full Name</span>
                </div>
                <div>
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->mother_occupation ?? '') }}" style="{{ $getDynamicStyle($app->mother_occupation ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 16, 24, 30) }}">
                    <span class="label-text">Occupation</span>
                </div>
                <div>
                    <div style="border-bottom: 1.5px solid #0f172a; padding: 1px 3px; min-height: 34px; display: flex; flex-direction: column; justify-content: flex-end;">
                        <div style="font-family: 'Inter', sans-serif; font-size: 0.88rem; font-weight: 750; color: #0f172a; line-height: 1.2;">
                            {{ $parentPhone }}
                        </div>
                        @if(!empty($parentEmail))
                            <div style="font-family: 'Inter', sans-serif; font-size: 0.78rem; font-weight: 600; color: #0f172a; line-height: 1.2; text-transform: lowercase;">
                                {{ $parentEmail }}
                            </div>
                        @endif
                    </div>
                    <span class="label-text">Tel./Email address</span>
                </div>
            </div>
        </div>

        <!-- Home Address -->
        <div class="field-container" style="margin-top: 14px;">
            <input type="text" class="input-line" value="{{ mb_strtoupper($app->home_address ?? $fullAddress) }}" style="{{ $getDynamicStyle($app->home_address ?? $fullAddress, '0.98rem', '0.82rem', '0.70rem', '0.60rem', 45, 65, 85) }}">
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
                $sibName = $sib ? mb_strtoupper(trim(($sib->first_name ?? '') . ' ' . ($sib->last_name ?? ''))) : '';
                $sibGrade = $sib ? mb_strtoupper($sib->grade_level ?? '') : '';
                $sibAge = ($sib && !empty($sib->date_of_birth)) ? \Carbon\Carbon::parse($sib->date_of_birth)->age : '';
            @endphp
            <div class="field-container" style="{{ $i === 0 ? 'margin-top: 10px;' : '' }}">
                <div class="grid-children-row">
                    <div>
                        <input type="text" class="input-line" value="{{ $sibName }}" style="{{ $getDynamicStyle($sibName, '0.98rem', '0.82rem', '0.68rem', '0.58rem', 22, 32, 40) }}">
                        <span class="label-text">Name</span>
                    </div>
                    <div>
                        <input type="text" class="input-line" value="{{ $sibAge }}">
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
            <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->med_explanation ?? '') }}">
        </div>

        <!-- Question 2: Prescription Medication -->
        <div class="p2-question-row" style="margin-top: 20px;">
            Prescription Medication: 
            &nbsp; YES <span class="p2-inline-line">{{ $hasMed ? '✓' : '' }}</span> 
            &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasMed ? '✓' : '' }}</span>
        </div>

        <div class="p2-explain-block">
            <span class="p2-explain-label">If yes, please explain:</span>
            <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->current_medications ?? '') }}">
        </div>

        <!-- Family Physician & Phone -->
        <div class="grid-physician-row">
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->family_physician ?? '') }}">
                <span class="label-text">Family Physician:</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->physician_phone ?? '') }}">
                <span class="label-text">Phone:</span>
            </div>
        </div>

        <!-- EMERGENCY CONTACTS SECTION -->
        <div class="section-header-row" style="margin-top: 25px;">
            EMERGENCY CONTACTS <span style="font-size: 0.9rem; font-weight: normal; text-transform: none; color: #475569;">(Other than above names)</span>
        </div>

        <div class="p2-emergency-grid">
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->emergency_name ?? '') }}" style="{{ $getDynamicStyle($app->emergency_name ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 20, 28, 35) }}">
                <span class="label-text">Name</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->emergency_relationship ?? '') }}">
                <span class="label-text">Relationship</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->emergency_phone ?? '') }}">
                <span class="label-text">Phone</span>
            </div>
        </div>

        <!-- REFERRAL SECTION -->
        <div class="section-header-row" style="margin-top: 25px;">
            REFERRAL
        </div>

        <div class="field-container" style="margin-top: 10px;">
            <input type="text" class="input-line" value="{{ mb_strtoupper($app->referral_source ?? '') }}">
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
                <input type="text" class="input-line" value="{{ $fatherFull ?: $motherFull }}" style="{{ $getDynamicStyle($fatherFull ?: $motherFull, '0.98rem', '0.80rem', '0.68rem', '0.58rem', 22, 32, 40) }}">
                <span class="label-text">Parent/Guardian</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($student->created_at->format('M d, Y')) }}">
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
                <input type="text" class="input-line" style="width: 110px; display: inline-block;" value="{{ mb_strtoupper($app?->payment?->reference_number ?? '') }}">
            </div>
        </div>

        <!-- Attachments Checklist: Reduced font size by -0.5 specifically for checklist items -->
        <div class="attachments-title">To be attached:</div>
        <ol class="attachments-list">
            <li>Photo copy of Birth Certificate</li>
            <li>Official Transcript from Previous School (Report Card)</li>
            <li>Medical Record (If any)</li>
            <li>Photo copy of Marriage Contract of Parents</li>
            <li>Picture 2 x 2</li>
        </ol>

    </div>

    <script>
        function triggerPrintPDF() {
            // Trigger browser print dialog where user selects "Save as PDF"
            window.print();
        }

        // Auto trigger print dialog if ?auto_print=1 in URL
        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 500);
            });
        }
    </script>
</body>
</html>
