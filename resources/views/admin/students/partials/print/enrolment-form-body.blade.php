@php
    $app = $applicant ?? $student->applicant;
    $siblings = $siblings ?? [];
    
    $studentType = strtoupper($app->student_type ?? 'OLD');
    $isOld = str_contains($studentType, 'OLD');
    $isNew = str_contains($studentType, 'NEW') || !$isOld;

    if (!isset($GLOBALS['AMIS_LOGO_BASE64'])) {
        $path = public_path('images/AMIS_Logo.png');
        $GLOBALS['AMIS_LOGO_BASE64'] = file_exists($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : asset('images/AMIS_Logo.png');
    }

    if (!isset($GLOBALS['DEPED_LOGO_BASE64'])) {
        $path = public_path('images/logo/deped_logo.png');
        $GLOBALS['DEPED_LOGO_BASE64'] = file_exists($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : asset('images/logo/deped_logo.png');
    }

    if (!isset($GLOBALS['ARABIC_WORDMARK_PNG_BASE64'])) {
        $path = public_path('images/amis-arabic-wordmark.png');
        $GLOBALS['ARABIC_WORDMARK_PNG_BASE64'] = file_exists($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : null;
    }

    if (!isset($GLOBALS['ARABIC_WORDMARK_BASE64'])) {
        $path = public_path('images/amis-arabic-wordmark.svg');
        $GLOBALS['ARABIC_WORDMARK_BASE64'] = file_exists($path) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($path)) : null;
    }

    $amisLogoSrc = $GLOBALS['AMIS_LOGO_BASE64'];
    $depedLogoSrc = $GLOBALS['DEPED_LOGO_BASE64'];
    $arabicWordmarkSrc = $GLOBALS['ARABIC_WORDMARK_PNG_BASE64'] ?? $GLOBALS['ARABIC_WORDMARK_BASE64'] ?? null;

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
    // Fix missing spaces between numbers and letters (e.g. "68UMMAH" -> "68 UMMAH")
    $rawAddress = preg_replace('/(\d+)([A-Za-z])/', '$1 $2', $rawAddress);
    $fullAddress = mb_strtoupper($rawAddress);

    // Student Age Calculation
    $studentAge = '';
    if ($app && $app->date_of_birth) {
        $studentAge = \Carbon\Carbon::parse($app->date_of_birth)->age;
    }

    // Robust Student 2x2 Photo URL / Base64 resolver
    $photoBase64 = null;
    if ($app && !empty($app->photo_2x2_url)) {
        $candidate = storage_path('app/public/' . ltrim(str_replace('storage/', '', $app->photo_2x2_url), '/'));
        if (file_exists($candidate)) {
            $photoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($candidate));
        }
    }
    if (!$photoBase64 && $student && !empty($student->photo_url)) {
        $candidate = storage_path('app/public/' . ltrim(str_replace('storage/', '', $student->photo_url), '/'));
        if (file_exists($candidate)) {
            $photoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($candidate));
        }
    }
    if (!$photoBase64 && !($isPdf ?? false)) {
        if ($app && !empty($app->photo_2x2_url)) {
            $photoBase64 = \App\Support\EnrollmentStorage::url($app->photo_2x2_url);
        } elseif ($student && !empty($student->photo_url)) {
            $photoBase64 = \App\Support\EnrollmentStorage::url($student->photo_url);
        } elseif ($student && !empty($student->obfuscated_id)) {
            $photoBase64 = 'https://amis.edu.ph/student-photo/' . $student->obfuscated_id . '.jpg';
        }
    }

    // Multi-tier Helper function for dynamic font-size calculation on long text (keeps normal text large)
    $getDynamicStyle = function($text, $baseSize = '0.96rem', $mediumSize = '0.84rem', $smallSize = '0.72rem', $xsmallSize = '0.60rem', $t1 = 26, $t2 = 36, $t3 = 48) {
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
        return "font-size: {$baseSize}; font-weight: 750;";
    };

    // Specialized Auto Font-Size Helper for Address & Home Address (14px default, scales only when overflowing full width)
    $getAddressStyle = function($text) {
        $len = mb_strlen(trim($text ?? ''));
        if ($len > 120) {
            return "font-size: 8.5px; font-weight: 700; white-space: nowrap;";
        }
        if ($len > 105) {
            return "font-size: 9.5px; font-weight: 700; white-space: nowrap;";
        }
        if ($len > 92) {
            return "font-size: 11px; font-weight: 700; white-space: nowrap;";
        }
        if ($len > 82) {
            return "font-size: 12.5px; font-weight: 700; white-space: nowrap;";
        }
        return "font-size: 14px; font-weight: 700; white-space: nowrap;";
    };

    // Grade Level Shortener Helper (e.g., "GRADE 1" -> "G1", "KINDER 1" -> "K1")
    $formatGradeLevelShort = function($gradeStr) {
        $g = mb_strtoupper(trim($gradeStr ?? ''));
        if (preg_match('/GRADE\s*(\d+)/i', $g, $m)) {
            return 'G' . $m[1];
        }
        if (preg_match('/KINDER\s*(\d+)/i', $g, $m)) {
            return 'K' . $m[1];
        }
        if (str_contains($g, 'NURSERY')) {
            return 'N1';
        }
        if (str_contains($g, 'KINDER')) {
            return 'K1';
        }
        return $g;
    };
@endphp

@if($isPdf ?? false)
<style>
    @page {
        size: 210mm 297mm;
        margin: 6mm 10mm 6mm 10mm !important;
    }
    * {
        box-sizing: border-box !important;
        margin: 0;
        padding: 0;
    }
    html, body {
        background-color: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        color: #0f172a !important;
        line-height: 1.15 !important;
        font-size: 10px !important;
        width: 100% !important;
    }
    .paper-container {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        border: none !important;
    }
    .paper-page-break {
        page-break-after: always !important;
        break-after: page !important;
    }
    .page-number-badge {
        display: none !important;
    }
    .input-line, .p2-full-line {
        display: block !important;
        width: 100% !important;
        border: none !important;
        border-bottom: 1.1px solid #0f172a !important;
        background: transparent !important;
        padding: 0 2px !important;
        margin: 0 !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-weight: bold !important;
        font-size: 9.5px !important;
        color: #0f172a !important;
        line-height: 1.1 !important;
        height: 15px !important;
        box-sizing: border-box !important;
    }
    .label-text {
        display: block !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-size: 7px !important;
        font-weight: bold !important;
        color: #475569 !important;
        text-transform: uppercase !important;
        margin-top: 1px !important;
        line-height: 1 !important;
    }
    .section-header-row {
        background-color: #f1f5f9 !important;
        border-left: 3px solid #059669 !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-size: 8.8px !important;
        font-weight: bold !important;
        color: #0f172a !important;
        padding: 2px 4px !important;
        margin-top: 6px !important;
        margin-bottom: 3px !important;
        text-transform: uppercase !important;
    }
    .top-header-row {
        display: table !important;
        width: 100% !important;
        margin-bottom: 4px !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }
    .header-left-group { display: table-cell !important; vertical-align: middle !important; width: 73% !important; }
    .header-right-group { display: table-cell !important; vertical-align: middle !important; width: 27% !important; text-align: right !important; }
    
    .form-middle-grid {
        display: table !important;
        width: 100% !important;
        margin-bottom: 4px !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }
    .form-title-area { display: table-cell !important; vertical-align: top !important; width: 58% !important; }
    .checkbox-stack { display: table-cell !important; vertical-align: top !important; width: 17% !important; text-align: left !important; padding-top: 4px !important; }
    .photo-box { display: table-cell !important; vertical-align: top !important; width: 25% !important; text-align: right !important; }

    .student-info-bar {
        background-color: #f1f5f9 !important;
        border-left: 3px solid #059669 !important;
        padding: 2px 4px !important;
        margin-top: 4px !important;
        margin-bottom: 2px !important;
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
    }
    .student-info-bar .section-title {
        display: table-cell !important;
        vertical-align: middle !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-size: 8.5px !important;
        font-weight: bold !important;
        color: #0f172a !important;
        text-transform: uppercase !important;
    }
    .student-info-bar .lrn-container {
        display: table-cell !important;
        vertical-align: middle !important;
        text-align: right !important;
        font-size: 8px !important;
        font-weight: bold !important;
        color: #0f172a !important;
    }
    .lrn-input {
        display: inline-block !important;
        width: 85px !important;
        border: none !important;
        border-bottom: 1.1px solid #0f172a !important;
        background: transparent !important;
        padding: 0 2px !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-weight: bold !important;
        font-size: 8.5px !important;
        text-align: center !important;
        height: 13px !important;
    }

    .grid-5-col, .grid-4-col-birth, .grid-2-col-school, .grid-parent-row, .grid-children-row, .grid-physician-row, .p2-emergency-grid, .signature-grid, .grid-office-row {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        margin-bottom: 2px !important;
    }
    .grid-5-col > div, .grid-4-col-birth > div, .grid-2-col-school > div, .grid-parent-row > div, .grid-children-row > div, .grid-physician-row > div, .p2-emergency-grid > div, .signature-grid > div, .grid-office-row > div {
        display: table-cell !important;
        vertical-align: bottom !important;
        padding: 0 2px !important;
        box-sizing: border-box !important;
    }
    .field-container {
        margin-top: 3.5px !important;
    }
    .refund-notice-box {
        border: 1.2px solid #dc2626 !important;
        padding: 2px 4px !important;
        font-size: 7px !important;
        font-weight: bold !important;
        color: #dc2626 !important;
        text-align: center !important;
        display: inline-block !important;
        line-height: 1.1 !important;
        box-sizing: border-box !important;
    }
    .lives-with-row {
        margin-top: 5px !important;
        font-size: 8px !important;
        font-weight: bold !important;
    }
    .radio-option {
        display: inline-block !important;
        margin-left: 8px !important;
    }
    .radio-line {
        display: inline-block !important;
        width: 14px !important;
        border-bottom: 1.1px solid #0f172a !important;
        text-align: center !important;
        font-weight: bold !important;
        margin-right: 2px !important;
    }
    .p2-question-row {
        font-size: 8px !important;
        font-weight: bold !important;
        margin-top: 4px !important;
        margin-bottom: 2px !important;
        line-height: 1.2 !important;
    }
    .p2-inline-line {
        display: inline-block !important;
        width: 14px !important;
        border-bottom: 1.1px solid #0f172a !important;
        text-align: center !important;
        font-weight: bold !important;
    }
    .p2-explain-block {
        margin-top: 1px !important;
        margin-bottom: 2px !important;
    }
    .p2-explain-label {
        display: block !important;
        font-size: 7px !important;
        color: #475569 !important;
    }
    .p2-policy-text {
        font-size: 7.8px !important;
        line-height: 1.3 !important;
        margin-top: 8px !important;
        color: #334155 !important;
        text-align: justify !important;
    }
    .signature-disclaimer {
        font-size: 7px !important;
        color: #64748b !important;
        margin-top: 6px !important;
        font-style: italic !important;
    }
    .office-perforated-line {
        border: none !important;
        border-top: 1px dashed #94a3b8 !important;
        margin: 14px 0 10px 0 !important;
    }
    .date-slash-inputs {
        display: inline-block !important;
    }
    .date-slash-input {
        width: 22px !important;
        display: inline-block !important;
        border: none !important;
        border-bottom: 1.1px solid #0f172a !important;
        text-align: center !important;
        font-size: 8px !important;
        font-weight: bold !important;
    }
    .attachments-title {
        font-size: 8px !important;
        font-weight: bold !important;
        margin-top: 10px !important;
    }
    .attachments-list {
        font-size: 7.5px !important;
        line-height: 1.3 !important;
        margin-left: 14px !important;
        margin-top: 4px !important;
    }
    .school-arabic-name {
        display: none !important;
    }
</style>
@endif

<!-- PAGE 1: STUDENT & PARENT REGISTRATION + MEDICAL INFO -->
<div class="paper-container paper-page-break">
    @if(isset($pageNumber))
        <div class="page-number-badge">
            PAGE {{ $pageNumber }}{{ isset($totalPages) && $totalPages > 1 ? ' OF ' . $totalPages : '' }}
        </div>
    @endif
    <div class="top-header-row">
        <div class="header-left-group">
            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                <tr>
                    <td style="width: 54px; vertical-align: middle;">
                        <img src="{{ $amisLogoSrc }}" alt="AMIS Logo" class="header-logo-amis" style="width: 50px; height: 50px; object-fit: contain;">
                    </td>
                    <td style="vertical-align: middle; text-align: center; padding: 0 4px;">
                        @if(!empty($arabicWordmarkSrc))
                            <div style="text-align: center; margin-bottom: 2px;">
                                <img src="{{ $arabicWordmarkSrc }}" alt="المدرسة المنورة الإسلامية" style="height: 17px; max-width: 210px; object-fit: contain;">
                            </div>
                        @else
                            <div class="school-arabic-name">المدرسة المنورة الإسلامية</div>
                        @endif
                        <h1 class="school-name" style="font-size: 0.95rem; margin: 0; line-height: 1.15;">AL MUNAWWARA ISLAMIC SCHOOL</h1>
                        <p class="school-address" style="font-size: 0.74rem; margin: 1px 0 0 0;">Bugac Ma-a Road, Davao City Philippines</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="header-right-group">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle; padding-right: 4px; text-align: right;">
                        <img src="{{ $depedLogoSrc }}" alt="DepEd Logo" class="header-logo-deped" style="width: 44px; height: 44px; object-fit: contain;">
                    </td>
                    <td style="vertical-align: middle; text-align: right;">
                        <div class="refund-notice-box">
                            NO REFUND OF<br>ENROLLMENT FEE
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="form-middle-grid">
        <div class="form-title-area">
            <h2 class="form-title" style="font-size: 1.10rem; margin: 0;">ENROLMENT APPLICATION FORM</h2>
            <p class="sy-title" style="font-size: 0.90rem; margin: 1px 0 4px 0;">SY {{ $app->school_year ?? '2026-2027' }}</p>
            
            <div class="student-info-bar">
                <span class="section-title">STUDENT INFORMATION</span>
                <div class="lrn-container">
                    <span>LRN:</span>
                    <input type="text" class="lrn-input" value="{{ mb_strtoupper($app->lrn ?? $student->student_number) }}" style="{{ $getDynamicStyle($app->lrn ?? $student->student_number, '0.88rem', '0.78rem', '0.70rem', '0.60rem', 12, 18, 24) }}">
                </div>
            </div>
        </div>

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

        <div class="photo-box" id="student-photo-container">
            <div style="display: inline-block; width: 75px; height: 75px; border: 1.2px solid #0f172a; position: relative; overflow: hidden; background: #fafafa; text-align: center; vertical-align: top;">
                @if(!empty($photoBase64))
                    <img src="{{ $photoBase64 }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <table style="width: 100%; height: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="vertical-align: middle; text-align: center; color: #64748b; font-size: 7.5px; font-weight: bold; font-family: sans-serif; line-height: 1.2;">
                                2x2<br>PHOTO
                            </td>
                        </tr>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <div class="field-container" style="margin-top: 2px;">
        <div class="grid-5-col">
            <div style="width: 25%;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->last_name ?? '') }}" style="{{ $getDynamicStyle($app->last_name ?? '', '0.96rem', '0.84rem', '0.70rem', '0.58rem', 14, 20, 26) }}">
                <span class="label-text">Last</span>
            </div>
            <div style="width: 25%;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->first_name ?? '') }}" style="{{ $getDynamicStyle($app->first_name ?? '', '0.96rem', '0.84rem', '0.70rem', '0.58rem', 14, 20, 26) }}">
                <span class="label-text">First</span>
            </div>
            <div style="width: 21%;">
                @php
                    $rawMiddle = trim($app->middle_name ?? '');
                    $mDisplay = '';
                    if ($rawMiddle !== '') {
                        $fChar = mb_substr($rawMiddle, 0, 1, 'UTF-8');
                        $mDisplay = ($fChar === '.') ? '.' : mb_strtoupper($fChar, 'UTF-8') . '.';
                    }
                @endphp
                <input type="text" class="input-line" value="{{ $mDisplay }}" style="{{ $getDynamicStyle($mDisplay, '0.96rem', '0.84rem', '0.72rem', '0.60rem', 22, 32, 42) }}">
                <span class="label-text">Middle</span>
            </div>
            <div style="width: 11%;">
                @php
                    $g = strtoupper(trim($app->gender ?? ''));
                    $sexChar = str_starts_with($g, 'F') ? 'F' : (str_starts_with($g, 'M') ? 'M' : $g);
                @endphp
                <input type="text" class="input-line" value="{{ $sexChar }}" style="text-align: center;">
                <span class="label-text" style="text-align: center;">Sex</span>
            </div>
            <div style="width: 18%;">
                @php
                    $rawGrade = $student->grade_level ?? $app->grade_level ?? '';
                    $shortGrade = $formatGradeLevelShort($rawGrade);
                @endphp
                <input type="text" class="input-line auto-fit-field" value="{{ $shortGrade }}" style="text-align: center;">
                <span class="label-text" style="text-align: center; line-height: 1.05;">GRADE<br>LEVEL</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <input type="text" class="input-line address-auto-fit" value="{{ $fullAddress }}" style="{{ $getAddressStyle($fullAddress) }}">
        <span class="label-text">Address</span>
    </div>

    <div class="field-container">
        <div class="grid-4-col-birth">
            <div>
                <input type="text" class="input-line" value="{{ $studentAge }}">
                <span class="label-text">Age</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ $app->date_of_birth ? mb_strtoupper(\Carbon\Carbon::parse($app->date_of_birth)->format('M d, Y')) : '' }}">
                <span class="label-text">Date of Birth</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->place_of_birth ?? '') }}" style="{{ $getDynamicStyle($app->place_of_birth ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 18, 25, 32) }}">
                <span class="label-text">Place of Birth</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->religion ?? 'ISLAM') }}" style="{{ $getDynamicStyle($app->religion ?? 'ISLAM', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 14, 20, 26) }}">
                <span class="label-text">Religion</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <input type="text" class="input-line" value="{{ mb_strtoupper($app->last_school_attended ?? '') }}" style="{{ $getDynamicStyle($app->last_school_attended ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 30, 42, 55) }}">
        <span class="label-text">Previous Attended School Name</span>
    </div>

    <div class="field-container">
        <div class="grid-2-col-school">
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->last_school_address ?? '') }}" style="{{ $getDynamicStyle($app->last_school_address ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 30, 42, 55) }}">
                <span class="label-text">Previous School Address</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ $app->last_school_contact ?? '' }}">
                <span class="label-text">Telephone No.</span>
            </div>
        </div>
    </div>

    <div class="section-header-row">
        PARENT INFORMATION
    </div>

    <div class="field-container">
        <div class="grid-parent-row">
            <div>
                <input type="text" class="input-line" value="{{ $fatherFull }}" style="{{ $getDynamicStyle($fatherFull, '0.96rem', '0.84rem', '0.70rem', '0.58rem', 18, 25, 32) }}">
                <span class="label-text">Father's Full Name</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->father_occupation ?? '') }}" style="{{ $getDynamicStyle($app->father_occupation ?? '', '0.92rem', '0.78rem', '0.64rem', '0.54rem', 11, 15, 20) }}">
                <span class="label-text">Occupation</span>
            </div>
            <div>
                @php
                    $fTel = trim(implode(' / ', array_filter([$app->father_phone ?? null, $app->father_email ?? null])));
                @endphp
                <input type="text" class="input-line" value="{{ $fTel }}" style="{{ $getDynamicStyle($fTel, '0.96rem', '0.84rem', '0.70rem', '0.58rem', 16, 22, 28) }}">
                <span class="label-text">Tel./Email Address</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <div class="grid-parent-row">
            <div>
                <input type="text" class="input-line" value="{{ $motherFull }}" style="{{ $getDynamicStyle($motherFull, '0.96rem', '0.84rem', '0.70rem', '0.58rem', 18, 25, 32) }}">
                <span class="label-text">Mother's Full Name</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->mother_occupation ?? '') }}" style="{{ $getDynamicStyle($app->mother_occupation ?? '', '0.92rem', '0.78rem', '0.64rem', '0.54rem', 11, 15, 20) }}">
                <span class="label-text">Occupation</span>
            </div>
            <div>
                @php
                    $mTel = trim(implode(' / ', array_filter([$app->mother_phone ?? null, $app->mother_email ?? null])));
                @endphp
                <input type="text" class="input-line" value="{{ $mTel }}" style="{{ $getDynamicStyle($mTel, '0.96rem', '0.84rem', '0.70rem', '0.58rem', 16, 22, 28) }}">
                <span class="label-text">Tel./Email Address</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <input type="text" class="input-line address-auto-fit" value="{{ $fullAddress }}" style="{{ $getAddressStyle($fullAddress) }}">
        <span class="label-text">Home Address</span>
    </div>

    <div class="section-header-row">
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
        <div class="field-container">
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

    <!-- MEDICAL INFORMATION (ON PAGE 1) -->
    <div class="section-header-row" style="margin-top: 6px; margin-bottom: 3px;">
        MEDICAL INFORMATION
    </div>

    @php
        $hasPsych = !empty($app->psych_testing) && $app->psych_testing !== 'no';
        $hasMed   = !empty($app->prescription_med) && $app->prescription_med !== 'no';
    @endphp

    <div class="p2-question-row">
        Has the student ever had psychological testing or been screened for academic difficulties or learning disabilities? 
        &nbsp; YES <span class="p2-inline-line">{{ $hasPsych ? '✓' : '' }}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasPsych ? '✓' : '' }}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->med_explanation ?? '') }}">
    </div>

    <div class="p2-question-row">
        Prescription Medication: 
        &nbsp; YES <span class="p2-inline-line">{{ $hasMed ? '✓' : '' }}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasMed ? '✓' : '' }}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->current_medications ?? '') }}">
    </div>

    <div class="field-container" style="margin-top: 3px;">
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
    </div>
</div>

<!-- PAGE 2: EMERGENCY CONTACTS, REFERRAL, POLICIES & SIGNATURES -->
<div class="paper-container">
    @if(isset($pageNumber))
        <div class="page-number-badge">
            PAGE 2{{ isset($totalPages) && $totalPages > 1 ? ' OF ' . $totalPages : '' }}
        </div>
    @endif

    <div class="section-header-row" style="margin-top: 0;">
        EMERGENCY CONTACTS <span style="font-size: 0.85em; font-weight: normal; text-transform: none; color: #475569;">(Other than above names)</span>
    </div>

    <div class="field-container" style="margin-top: 6px;">
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
    </div>

    <div class="section-header-row" style="margin-top: 18px;">
        REFERRAL
    </div>

    <div class="field-container" style="margin-top: 6px;">
        <input type="text" class="input-line" value="{{ mb_strtoupper($app->referral_source ?? '') }}">
        <span class="label-text">I heard about AMIS from</span>
    </div>

    <p class="p2-policy-text" style="margin-top: 18px;">
        I understand that if and when the applicant is enrolled, I agree to comply with the rules, regulations and policies of Al Munawwara Islamic School as outlined in the Parent Student Handbook and other official communications.
    </p>

    <p class="p2-policy-text" style="margin-top: 10px;">
        It is further understood that Al Munawwara Islamic School reserves the right to dismiss any student for any reason deemed to be in the best interest of the school. Dismissal of the student does not release the parent from the financial obligations related to the school fees and other fees thereat.
    </p>

    <div class="section-header-row" style="margin-top: 20px;">
        SIGNATURE
    </div>

    <div class="field-container" style="margin-top: 8px;">
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
    </div>

    <p class="signature-disclaimer" style="margin-top: 6px;">
        *Only completed application will be accepted. Submission of an application does not guarantee admission
    </p>

    <hr class="office-perforated-line" style="margin: 16px 0 12px 0;">

    <div style="border: 1px solid #94a3b8; padding: 6px 10px; margin-top: 4px; background: #ffffff;">
        <div style="font-size: 8px; font-weight: bold; color: #0f172a; margin-bottom: 4px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px;">
            FOR OFFICE USE ONLY
        </div>
        <div class="grid-office-row" style="margin-top: 4px;">
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
                <input type="text" class="input-line" style="width: 100px; display: inline-block;" value="{{ $app?->payment?->amount_paid ? '₱' . number_format($app->payment->amount_paid, 2) : '' }}">
            </div>
            <div>
                <span>OR No.:</span>
                <input type="text" class="input-line" style="width: 100px; display: inline-block;" value="{{ mb_strtoupper($app?->payment?->reference_number ?? '') }}">
            </div>
        </div>

        <div class="attachments-title" style="margin-top: 8px;">To be attached:</div>
        <ol class="attachments-list" style="margin-top: 3px;">
            <li>Photo copy of Birth Certificate</li>
            <li>Official Transcript from Previous School (Report Card)</li>
            <li>Medical Record (If any)</li>
            <li>Photo copy of Marriage Contract of Parents</li>
            <li>Picture 2 x 2</li>
        </ol>
    </div>
</div>
