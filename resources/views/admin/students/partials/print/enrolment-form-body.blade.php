@php
    $app = $applicant ?? $student->applicant;
    $siblings = $siblings ?? [];
    
    $studentType = strtoupper($app->student_type ?? 'OLD');
    $isOld = str_contains($studentType, 'OLD');
    $isNew = str_contains($studentType, 'NEW') || !$isOld;

    if ($isPdf ?? false) {
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
        $amisLogoSrc = $GLOBALS['AMIS_LOGO_BASE64'];
        $depedLogoSrc = $GLOBALS['DEPED_LOGO_BASE64'];
        $arabicWordmarkSrc = $GLOBALS['ARABIC_WORDMARK_PNG_BASE64'] ?? null;
    } else {
        $amisLogoSrc = asset('images/AMIS_Logo.png');
        $depedLogoSrc = asset('images/logo/deped_logo.png');
        $arabicWordmarkSrc = asset('images/amis-arabic-wordmark.png');
    }

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
        if ($isPdf ?? false) {
            $candidate = storage_path('app/public/' . ltrim(str_replace('storage/', '', $app->photo_2x2_url), '/'));
            if (file_exists($candidate)) {
                $photoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($candidate));
            }
        } else {
            $photoBase64 = \App\Support\EnrollmentStorage::url($app->photo_2x2_url);
        }
    }
    if (!$photoBase64 && $student && !empty($student->photo_url)) {
        if ($isPdf ?? false) {
            $candidate = storage_path('app/public/' . ltrim(str_replace('storage/', '', $student->photo_url), '/'));
            if (file_exists($candidate)) {
                $photoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($candidate));
            }
        } else {
            $photoBase64 = \App\Support\EnrollmentStorage::url($student->photo_url);
        }
    }

    // Multi-tier Helper function for dynamic font-size calculation on long text (keeps normal text large)
    $getDynamicStyle = function($text, $baseSize = '0.96rem', $mediumSize = '0.86rem', $smallSize = '0.76rem', $xsmallSize = '0.68rem', $t1 = 28, $t2 = 38, $t3 = 50) {
        $len = mb_strlen(trim($text ?? ''));
        if ($len > $t3) {
            return "font-size: {$xsmallSize}; font-weight: 750;";
        }
        if ($len > $t2) {
            return "font-size: {$smallSize}; font-weight: 750;";
        }
        if ($len > $t1) {
            return "font-size: {$mediumSize}; font-weight: 750;";
        }
        return "";
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
        margin: 8mm 10mm 8mm 10mm !important;
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
        line-height: 1.25 !important;
        font-size: 10.5pt !important;
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
        border-bottom: 1.2px solid #0f172a !important;
        background: transparent !important;
        padding: 1px 2px 2px 2px !important;
        margin: 0 0 2px 0 !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-weight: bold !important;
        font-size: 10pt !important;
        color: #0f172a !important;
        line-height: 1.25 !important;
        min-height: 18px !important;
        height: auto !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
        box-sizing: border-box !important;
    }
    .label-text {
        display: block !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-size: 7.5pt !important;
        font-weight: bold !important;
        color: #475569 !important;
        text-transform: uppercase !important;
        margin-top: 2px !important;
        line-height: 1.15 !important;
        padding: 0 1px !important;
        letter-spacing: 0.2px !important;
    }
    .section-header-row {
        background-color: #f1f5f9 !important;
        border-left: 3.5px solid #059669 !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-size: 9.5pt !important;
        font-weight: bold !important;
        color: #0f172a !important;
        padding: 3px 6px !important;
        margin-top: 8px !important;
        margin-bottom: 4px !important;
    }
    .top-header-row {
        width: 100% !important;
        margin-bottom: 6px !important;
    }
    .header-main-table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    .doc-chk-box {
        display: inline-block !important;
        width: 11px !important;
        height: 11px !important;
        border: 1px solid #1e293b !important;
        background: #ffffff !important;
        text-align: center !important;
        line-height: 10px !important;
        font-size: 8.5pt !important;
        font-weight: bold !important;
        vertical-align: middle !important;
    }
    
    .form-middle-grid {
        display: table !important;
        width: 100% !important;
        margin-bottom: 6px !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }
    .form-title-area { display: table-cell !important; vertical-align: top !important; width: 56% !important; }
    .checkbox-stack { display: table-cell !important; vertical-align: top !important; width: 16% !important; text-align: left !important; padding-top: 4px !important; }
    .photo-box { display: table-cell !important; vertical-align: top !important; width: 28% !important; text-align: right !important; }

    .student-info-bar {
        background-color: #f1f5f9 !important;
        border-left: 3.5px solid #059669 !important;
        padding: 3px 6px !important;
        margin-top: 5px !important;
        margin-bottom: 3px !important;
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
    }
    .student-info-bar .section-title {
        display: table-cell !important;
        vertical-align: middle !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-size: 9.5pt !important;
        font-weight: bold !important;
        color: #0f172a !important;
        text-transform: uppercase !important;
    }
    .student-info-bar .lrn-container {
        display: table-cell !important;
        vertical-align: middle !important;
        text-align: right !important;
        font-size: 9pt !important;
        font-weight: bold !important;
        color: #0f172a !important;
    }
    .lrn-input {
        display: inline-block !important;
        width: 125px !important;
        border: none !important;
        border-bottom: 1.2px solid #0f172a !important;
        background: transparent !important;
        padding: 0 4px !important;
        font-family: 'DejaVu Sans', sans-serif !important;
        font-weight: bold !important;
        font-size: 9.5pt !important;
        text-align: center !important;
        min-height: 16px !important;
        height: auto !important;
        white-space: nowrap !important;
    }

    .grid-5-col, .grid-4-col-birth, .grid-2-col-school, .grid-parent-row, .grid-children-row, .grid-physician-row, .p2-emergency-grid, .signature-grid, .grid-office-row {
        display: table !important;
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        margin-bottom: 3px !important;
    }
    .grid-5-col > div, .grid-4-col-birth > div, .grid-2-col-school > div, .grid-parent-row > div, .grid-children-row > div, .grid-physician-row > div, .p2-emergency-grid > div, .signature-grid > div, .grid-office-row > div {
        display: table-cell !important;
        vertical-align: bottom !important;
        padding: 0 3px !important;
        box-sizing: border-box !important;
        white-space: normal !important;
        overflow-wrap: anywhere !important;
        word-wrap: break-word !important;
        word-break: break-word !important;
    }
    .field-container {
        margin-top: 4px !important;
    }
    .refund-notice-box {
        border: 1.5px solid #dc2626 !important;
        padding: 3px 6px !important;
        font-size: 7.5pt !important;
        font-weight: bold !important;
        color: #dc2626 !important;
        text-align: center !important;
        display: inline-block !important;
        line-height: 1.15 !important;
        box-sizing: border-box !important;
        border-radius: 3px !important;
    }
    .lives-with-row {
        margin-top: 8px !important;
        font-size: 9pt !important;
        font-weight: bold !important;
    }
    .radio-option {
        display: inline-block !important;
        margin-left: 12px !important;
    }
    .radio-line {
        display: inline-block !important;
        width: 16px !important;
        border-bottom: 1.2px solid #0f172a !important;
        text-align: center !important;
        font-weight: bold !important;
        margin-right: 3px !important;
    }
    .p2-question-row {
        font-size: 9pt !important;
        font-weight: bold !important;
        margin-top: 6px !important;
        margin-bottom: 3px !important;
        line-height: 1.3 !important;
    }
    .p2-inline-line {
        display: inline-block !important;
        width: 16px !important;
        border-bottom: 1.2px solid #0f172a !important;
        text-align: center !important;
        font-weight: bold !important;
    }
    .p2-explain-block {
        margin-top: 2px !important;
        margin-bottom: 4px !important;
    }
    .p2-explain-label {
        display: block !important;
        font-size: 7.5pt !important;
        color: #475569 !important;
        font-weight: bold !important;
    }
    .p2-policy-text {
        font-size: 8.8pt !important;
        line-height: 1.4 !important;
        margin-top: 10px !important;
        color: #334155 !important;
        text-align: justify !important;
    }
    .signature-disclaimer {
        font-size: 7.5pt !important;
        color: #64748b !important;
        margin-top: 8px !important;
        font-style: italic !important;
    }
    .office-perforated-line {
        border: none !important;
        border-top: 1.5px dashed #94a3b8 !important;
        margin: 14px 0 10px 0 !important;
    }
    .office-use-title {
        font-size: 10.5pt !important;
        font-weight: bold !important;
        color: #0f172a !important;
        margin-bottom: 5px !important;
        text-transform: uppercase !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding-bottom: 3px !important;
    }
    .office-label {
        font-size: 8.8pt !important;
        font-weight: bold !important;
        color: #1e293b !important;
    }
    .date-slash-inputs {
        display: inline-block !important;
    }
    .date-slash-input {
        width: 28px !important;
        display: inline-block !important;
        border: none !important;
        border-bottom: 1.2px solid #0f172a !important;
        text-align: center !important;
        font-size: 10.5pt !important;
        font-weight: bold !important;
        white-space: nowrap !important;
    }
    .date-slash-year {
        width: 52px !important;
    }
    .attachments-title {
        font-size: 10pt !important;
        font-weight: bold !important;
        font-style: italic !important;
        color: #0f172a !important;
        margin-top: 10px !important;
        margin-bottom: 4px !important;
    }
    .attachments-list {
        font-size: 8.8pt !important;
        line-height: 1.45 !important;
        margin-left: 18px !important;
        margin-top: 4px !important;
        color: #334155 !important;
    }
    .attachments-list li {
        margin-bottom: 2.5px !important;
    }
    .school-arabic-name {
        font-family: 'DejaVu Sans', serif !important;
        font-size: 12pt !important;
        font-weight: bold !important;
        color: #047857 !important;
        text-align: center !important;
        direction: rtl !important;
        line-height: 1.2 !important;
        margin-bottom: 2px !important;
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
        <table class="header-main-table" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
            <tr>
                <td style="width: @if($isPdf ?? false) 74px @else 80px @endif; vertical-align: middle; text-align: left; padding: 0;">
                    <img src="{{ $amisLogoSrc }}" alt="AMIS Logo" class="header-logo-amis" style="width: @if($isPdf ?? false) 70px @else 76px @endif; height: @if($isPdf ?? false) 70px @else 76px @endif; object-fit: contain; display: block;">
                </td>
                <td style="vertical-align: middle; text-align: center; padding: 0 6px;">
                    @if(!empty($arabicWordmarkSrc))
                        <div style="text-align: center; margin-bottom: 2px;">
                            <img src="{{ $arabicWordmarkSrc }}" alt="المدرسة المنورة الإسلامية" class="header-arabic-wordmark" style="height: @if($isPdf ?? false) 32px @else 36px @endif; max-width: @if($isPdf ?? false) 320px @else 360px @endif; object-fit: contain; display: inline-block;">
                        </div>
                    @else
                        <div class="school-arabic-name" style="font-family: 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; font-size: @if($isPdf ?? false) 14.5pt @else 1.65rem @endif; font-weight: bold; color: #047857; text-align: center; direction: rtl; line-height: 1.2; margin-bottom: 2px;">
                            المدرسة المنورة الإسلامية
                        </div>
                    @endif
                    <h1 class="school-name" style="font-family: 'Merriweather', Georgia, serif; font-size: @if($isPdf ?? false) 12.5pt @else 1.15rem @endif; font-weight: 900; letter-spacing: 0.3px; color: #0f172a; margin: 0; line-height: 1.15; text-transform: uppercase; white-space: nowrap;">
                        AL MUNAWWARA ISLAMIC SCHOOL
                    </h1>
                    <p class="school-address" style="font-family: 'Merriweather', Georgia, serif; font-size: @if($isPdf ?? false) 8pt @else 0.82rem @endif; font-weight: 600; color: #334155; margin: 2px 0 0 0; white-space: nowrap;">
                        Bugac Ma-a Road, Davao City Philippines
                    </p>
                </td>
                <td style="width: @if($isPdf ?? false) 74px @else 80px @endif; vertical-align: middle; text-align: center; padding: 0 4px;">
                    <img src="{{ $depedLogoSrc }}" alt="DepEd Logo" class="header-logo-deped" style="width: @if($isPdf ?? false) 70px @else 76px @endif; height: @if($isPdf ?? false) 70px @else 76px @endif; object-fit: contain; display: inline-block; vertical-align: middle;">
                </td>
                <td style="width: @if($isPdf ?? false) 104px @else 114px @endif; vertical-align: middle; text-align: right; padding: 0;">
                    <div class="refund-notice-box" style="border: @if($isPdf ?? false) 1.5px @else 2px @endif solid #dc2626; color: #dc2626; font-size: @if($isPdf ?? false) 7pt @else 0.78rem @endif; font-weight: 800; padding: @if($isPdf ?? false) 3px 5px @else 4px 6px @endif; border-radius: 4px; line-height: 1.15; text-align: center; text-transform: uppercase; display: inline-block;">
                        NO REFUND OF<br>ENROLLMENT FEE
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="form-middle-grid">
        <div class="form-title-area">
            <h2 class="form-title" style="font-size: @if($isPdf ?? false) 12pt @else 1.10rem @endif; margin: 0;">ENROLMENT APPLICATION FORM</h2>
            <p class="sy-title" style="font-size: @if($isPdf ?? false) 9.5pt @else 0.90rem @endif; margin: 1px 0 4px 0;">SY {{ $app->school_year ?? '2026-2027' }}</p>
            
            <div class="student-info-bar">
                <span class="section-title">STUDENT INFORMATION</span>
                <div class="lrn-container">
                    <span>LRN:</span>
                    <span class="lrn-input">{{ mb_strtoupper($app->lrn ?? $student->student_number ?? 'NA') }}</span>
                </div>
            </div>
        </div>

        <div class="checkbox-stack">
            <div class="checkbox-item">
                <span class="custom-checkbox">{!! $isOld ? '&#10003;' : '' !!}</span>
                <span>OLD</span>
            </div>
            <div class="checkbox-item">
                <span class="custom-checkbox">{!! $isNew ? '&#10003;' : '' !!}</span>
                <span>NEW</span>
            </div>
        </div>

        <div class="photo-box" id="student-photo-container">
            @if($isPdf ?? false)
                <div style="display: inline-block; width: 82px; height: 82px; border: 0.8px solid #94a3b8; overflow: hidden; background: #f8fafc; text-align: center; vertical-align: top;">
                    @if(!empty($photoBase64))
                        <img src="{{ $photoBase64 }}" alt="" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    @else
                        <table style="width: 100%; height: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: middle; text-align: center; color: #94a3b8; font-size: 8px; font-weight: bold; font-family: sans-serif; line-height: 1.2;">
                                    2x2<br>PHOTO
                                </td>
                            </tr>
                        </table>
                    @endif
                </div>
            @else
                @if(!empty($photoBase64))
                    <img src="{{ $photoBase64 }}" alt="2x2 Photo" style="width: 100%; height: 100%; object-fit: cover; object-position: center; display: block;">
                @else
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 0.80rem; font-weight: 700; font-family: 'Inter', sans-serif; line-height: 1.2; text-align: center;">
                        2x2<br>PHOTO
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="field-container" style="margin-top: 2px;">
        <div class="grid-5-col">
            <div @if($isPdf ?? false) style="width: 28%;" @endif>
                <div class="input-line">{!! !empty($app->last_name) ? e(mb_strtoupper($app->last_name)) : '&nbsp;' !!}</div>
                <span class="label-text">Last</span>
            </div>
            <div @if($isPdf ?? false) style="width: 28%;" @endif>
                <div class="input-line">{!! !empty($app->first_name) ? e(mb_strtoupper($app->first_name)) : '&nbsp;' !!}</div>
                <span class="label-text">First</span>
            </div>
            <div @if($isPdf ?? false) style="width: 24%;" @endif>
                @php
                    $rawMiddle = trim($app->middle_name ?? '');
                    $mDisplay = '';
                    if ($rawMiddle !== '') {
                        $fChar = mb_substr($rawMiddle, 0, 1, 'UTF-8');
                        $mDisplay = ($fChar === '.') ? '.' : mb_strtoupper($fChar, 'UTF-8') . '.';
                    }
                @endphp
                <div class="input-line">{!! !empty($mDisplay) ? e($mDisplay) : '&nbsp;' !!}</div>
                <span class="label-text">Middle</span>
            </div>
            <div @if($isPdf ?? false) style="width: 8%;" @endif>
                @php
                    $g = strtoupper(trim($app->gender ?? ''));
                    $sexChar = str_starts_with($g, 'F') ? 'F' : (str_starts_with($g, 'M') ? 'M' : $g);
                @endphp
                <div class="input-line" style="text-align: center;">{!! !empty($sexChar) ? e($sexChar) : '&nbsp;' !!}</div>
                <span class="label-text" style="text-align: center;">Sex</span>
            </div>
            <div @if($isPdf ?? false) style="width: 12%;" @endif>
                @php
                    $rawGrade = $student->grade_level ?? $app->grade_level ?? '';
                    $shortGrade = $formatGradeLevelShort($rawGrade);
                @endphp
                <div class="input-line auto-fit-field" style="text-align: center;">{!! !empty($shortGrade) ? e($shortGrade) : '&nbsp;' !!}</div>
                <span class="label-text" style="text-align: center; line-height: 1.05;">GRADE<br>LEVEL</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <div class="input-line address-auto-fit">{!! !empty($fullAddress) ? e($fullAddress) : '&nbsp;' !!}</div>
        <span class="label-text">Address</span>
    </div>

    <div class="field-container">
        <div class="grid-4-col-birth">
            <div>
                <div class="input-line">{!! $studentAge !== '' ? e($studentAge) : '&nbsp;' !!}</div>
                <span class="label-text">Age</span>
            </div>
            <div>
                <div class="input-line">{!! $app->date_of_birth ? e(mb_strtoupper(\Carbon\Carbon::parse($app->date_of_birth)->format('M d, Y'))) : '&nbsp;' !!}</div>
                <span class="label-text">Date of Birth</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->place_of_birth) ? e(mb_strtoupper($app->place_of_birth)) : '&nbsp;' !!}</div>
                <span class="label-text">Place of Birth</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->religion) ? e(mb_strtoupper($app->religion)) : 'ISLAM' !!}</div>
                <span class="label-text">Religion</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <div class="input-line">{!! !empty($app->last_school_attended) ? e(mb_strtoupper($app->last_school_attended)) : '&nbsp;' !!}</div>
        <span class="label-text">Previous Attended School Name</span>
    </div>

    <div class="field-container">
        <div class="grid-2-col-school">
            <div>
                <div class="input-line">{!! !empty($app->last_school_address) ? e(mb_strtoupper($app->last_school_address)) : '&nbsp;' !!}</div>
                <span class="label-text">Previous School Address</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->last_school_contact) ? e($app->last_school_contact) : '&nbsp;' !!}</div>
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
                <div class="input-line">{!! !empty($fatherFull) ? e($fatherFull) : '&nbsp;' !!}</div>
                <span class="label-text">Father's Full Name</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->father_occupation) ? e(mb_strtoupper($app->father_occupation)) : '&nbsp;' !!}</div>
                <span class="label-text">Occupation</span>
            </div>
            <div>
                @php
                    $fTel = trim(implode(' / ', array_filter([$app->father_phone ?? null, $app->father_email ?? null])));
                @endphp
                <div class="input-line">{!! !empty($fTel) ? e($fTel) : '&nbsp;' !!}</div>
                <span class="label-text">Tel./Email Address</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <div class="grid-parent-row">
            <div>
                <div class="input-line">{!! !empty($motherFull) ? e($motherFull) : '&nbsp;' !!}</div>
                <span class="label-text">Mother's Full Name</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->mother_occupation) ? e(mb_strtoupper($app->mother_occupation)) : '&nbsp;' !!}</div>
                <span class="label-text">Occupation</span>
            </div>
            <div>
                @php
                    $mTel = trim(implode(' / ', array_filter([$app->mother_phone ?? null, $app->mother_email ?? null])));
                @endphp
                <div class="input-line">{!! !empty($mTel) ? e($mTel) : '&nbsp;' !!}</div>
                <span class="label-text">Tel./Email Address</span>
            </div>
        </div>
    </div>

    <div class="field-container">
        <div class="input-line address-auto-fit">{!! !empty($fullAddress) ? e($fullAddress) : '&nbsp;' !!}</div>
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
                    <div class="input-line">{!! !empty($sibName) ? e($sibName) : '&nbsp;' !!}</div>
                    <span class="label-text">Name</span>
                </div>
                <div>
                    <div class="input-line">{!! $sibAge !== '' ? e($sibAge) : '&nbsp;' !!}</div>
                    <span class="label-text">Age</span>
                </div>
                <div>
                    <div class="input-line">{!! !empty($sibGrade) ? e($sibGrade) : '&nbsp;' !!}</div>
                    <span class="label-text">Grade Level</span>
                </div>
            </div>
        </div>
    @endfor

    <div class="lives-with-row">
        <span>Applicant lives with:</span>
        
        <div class="radio-option">
            <span class="radio-line">{!! $bothParents ? '&#10003;' : '&nbsp;' !!}</span>
            <span>Both Parents</span>
        </div>

        <div class="radio-option">
            <span class="radio-line">{!! $singleParent ? '&#10003;' : '&nbsp;' !!}</span>
            <span>Mother/Father</span>
        </div>

        <div class="radio-option">
            <span class="radio-line">{!! $guardianPresent ? '&#10003;' : '&nbsp;' !!}</span>
            <span>Guardian</span>
        </div>
    </div>
</div>

<!-- PAGE 2: MEDICAL INFORMATION, EMERGENCY CONTACTS, REFERRAL, POLICIES & SIGNATURES -->
<div class="paper-container">
    @if(isset($pageNumber))
        <div class="page-number-badge">
            PAGE 2{{ isset($totalPages) && $totalPages > 1 ? ' OF ' . $totalPages : '' }}
        </div>
    @endif

    <!-- MEDICAL INFORMATION -->
    <div class="section-header-row" style="margin-top: 0;">
        MEDICAL INFORMATION
    </div>

    @php
        $hasPsych = !empty($app->psych_testing) && $app->psych_testing !== 'no';
        $hasMed   = !empty($app->prescription_med) && $app->prescription_med !== 'no';
    @endphp

    <div class="p2-question-row">
        Has the student ever had psychological testing or been screened for academic difficulties or learning disabilities? 
        &nbsp; YES <span class="p2-inline-line">{!! $hasPsych ? '&#10003;' : '&nbsp;' !!}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{!! !$hasPsych ? '&#10003;' : '&nbsp;' !!}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <div class="p2-full-line">{!! !empty($app->med_explanation) ? e(mb_strtoupper($app->med_explanation)) : '&nbsp;' !!}</div>
    </div>

    <div class="p2-question-row">
        Prescription Medication: 
        &nbsp; YES <span class="p2-inline-line">{!! $hasMed ? '&#10003;' : '&nbsp;' !!}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{!! !$hasMed ? '&#10003;' : '&nbsp;' !!}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <div class="p2-full-line">{!! !empty($app->current_medications) ? e(mb_strtoupper($app->current_medications)) : '&nbsp;' !!}</div>
    </div>

    <div class="field-container" style="margin-top: 3px;">
        <div class="grid-physician-row">
            <div>
                <div class="input-line">{!! !empty($app->family_physician) ? e(mb_strtoupper($app->family_physician)) : '&nbsp;' !!}</div>
                <span class="label-text">Family Physician:</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->physician_phone) ? e(mb_strtoupper($app->physician_phone)) : '&nbsp;' !!}</div>
                <span class="label-text">Phone:</span>
            </div>
        </div>
    </div>

    <div class="section-header-row" style="margin-top: 14px;">
        EMERGENCY CONTACTS <span style="font-size: 0.85em; font-weight: normal; text-transform: none; color: #475569;">(Other than above names)</span>
    </div>

    <div class="field-container" style="margin-top: 6px;">
        <div class="p2-emergency-grid">
            <div>
                <div class="input-line">{!! !empty($app->emergency_name) ? e(mb_strtoupper($app->emergency_name)) : '&nbsp;' !!}</div>
                <span class="label-text">Name</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->emergency_relationship) ? e(mb_strtoupper($app->emergency_relationship)) : '&nbsp;' !!}</div>
                <span class="label-text">Relationship</span>
            </div>
            <div>
                <div class="input-line">{!! !empty($app->emergency_phone) ? e(mb_strtoupper($app->emergency_phone)) : '&nbsp;' !!}</div>
                <span class="label-text">Phone</span>
            </div>
        </div>
    </div>

    <div class="section-header-row" style="margin-top: 18px;">
        REFERRAL
    </div>

    <div class="field-container" style="margin-top: 6px;">
        <div class="input-line">{!! !empty($app->referral_source) ? e(mb_strtoupper($app->referral_source)) : '&nbsp;' !!}</div>
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
                <div class="input-line">{!! !empty($fatherFull ?: $motherFull) ? e($fatherFull ?: $motherFull) : '&nbsp;' !!}</div>
                <span class="label-text">Parent/Guardian</span>
            </div>
            <div>
                @php
                    $submittedDate = $student->created_at ?? $app->created_at ?? now();
                @endphp
                <div class="input-line">{!! mb_strtoupper($submittedDate->format('M d, Y')) !!}</div>
                <span class="label-text">Date</span>
            </div>
        </div>
    </div>

    <p class="signature-disclaimer" style="margin-top: 6px;">
        *Only completed application will be accepted. Submission of an application does not guarantee admission
    </p>

    <hr class="office-perforated-line" style="margin: 16px 0 12px 0;">

    <div class="office-use-box" style="border: 1px solid #94a3b8; padding: @if($isPdf ?? false) 6px 8px @else 10px 14px @endif; margin-top: 4px; background: #ffffff; border-radius: 4px;">
        <div class="office-use-title" style="font-size: @if($isPdf ?? false) 10.5pt @else 0.95rem @endif; font-weight: 800; color: #0f172a; margin-bottom: 6px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
            FOR OFFICE USE ONLY
        </div>
        <div class="grid-office-row" style="margin-top: 6px;">
            <div style="width: 44%;">
                <span class="office-label" style="font-size: @if($isPdf ?? false) 9pt @else 0.90rem @endif; font-weight: 700; color: #1e293b;">Application submitted on:</span>
                <div class="date-slash-inputs" style="margin-left: 4px;">
                    <span class="date-slash-input" style="font-size: @if($isPdf ?? false) 10pt @else 0.95rem @endif; font-weight: bold; border-bottom: 1.5px solid #0f172a; padding: 0 2px; width: @if($isPdf ?? false) 28px @else 32px @endif; display: inline-block; text-align: center;">{{ $submittedDate->format('m') }}</span> /
                    <span class="date-slash-input" style="font-size: @if($isPdf ?? false) 10pt @else 0.95rem @endif; font-weight: bold; border-bottom: 1.5px solid #0f172a; padding: 0 2px; width: @if($isPdf ?? false) 28px @else 32px @endif; display: inline-block; text-align: center;">{{ $submittedDate->format('d') }}</span> /
                    <span class="date-slash-input date-slash-year" style="font-size: @if($isPdf ?? false) 10pt @else 0.95rem @endif; font-weight: bold; border-bottom: 1.5px solid #0f172a; padding: 0 2px; width: @if($isPdf ?? false) 52px @else 44px @endif; display: inline-block; text-align: center;">{{ $submittedDate->format('Y') }}</span>
                </div>
            </div>
            <div style="width: 28%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 38px; vertical-align: bottom; font-size: @if($isPdf ?? false) 9pt @else 0.90rem @endif; font-weight: 700; color: #1e293b;">Paid:</td>
                        <td style="vertical-align: bottom;">
                            <div class="input-line" style="font-size: @if($isPdf ?? false) 9.5pt @else 0.92rem @endif;">{!! $app?->payment?->amount_paid ? '₱' . number_format($app->payment->amount_paid, 2) : '&nbsp;' !!}</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div style="width: 28%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50px; vertical-align: bottom; font-size: @if($isPdf ?? false) 9pt @else 0.90rem @endif; font-weight: 700; color: #1e293b;">OR No.:</td>
                        <td style="vertical-align: bottom;">
                            <div class="input-line" style="font-size: @if($isPdf ?? false) 9.5pt @else 0.92rem @endif;">{!! !empty($app?->payment?->reference_number) ? e(mb_strtoupper($app->payment->reference_number)) : '&nbsp;' !!}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @php
            $hasBirthCert = (!empty($app?->birth_cert_url) && $app->birth_cert_url !== '[]' && $app->birth_cert_url !== '[""]') || (($app?->document_statuses['birth_cert'] ?? '') === 'approved');
            $hasReportCard = (!empty($app?->report_card_url) && $app->report_card_url !== '[]' && $app->report_card_url !== '[""]') || (($app?->document_statuses['report_card'] ?? '') === 'approved');
            $hasMedicalRecord = (!empty($app?->medical_record_url) && $app->medical_record_url !== '[]' && $app->medical_record_url !== '[""]') || (($app?->document_statuses['medical_record'] ?? '') === 'approved');
            $hasMarriageContract = (!empty($app?->marriage_contract_url) && $app->marriage_contract_url !== '[]' && $app->marriage_contract_url !== '[""]') || (($app?->document_statuses['marriage_contract'] ?? '') === 'approved');
            $hasPhoto2x2 = (!empty($app?->photo_2x2_url) && $app->photo_2x2_url !== '[]' && $app->photo_2x2_url !== '[""]') || !empty($photoBase64) || (($app?->document_statuses['photo_2x2'] ?? '') === 'approved');

            $checkedByName = null;
            $checkedDate = null;

            if (!empty($officialDoc?->creator?->name) && !in_array(strtolower(trim($officialDoc->creator->name)), ['admin', 'amis admin', 'system', 'amis generated', 'test user'])) {
                $checkedByName = $officialDoc->creator->name;
                $checkedDate = $officialDoc->created_at?->format('M d, Y');
            } elseif (!empty($app?->reviewer_name)) {
                $checkedByName = $app->reviewer_name;
                $checkedDate = $app->updated_at ? $app->updated_at->format('M d, Y') : $submittedDate->format('M d, Y');
            }
        @endphp

        <!-- Document Checklist Table -->
        <table class="doc-checklist-table" style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: @if($isPdf ?? false) 8.5pt @else 0.86rem @endif;">
            <thead>
                <tr>
                    <th style="text-align: left; font-size: @if($isPdf ?? false) 9pt @else 0.90rem @endif; font-weight: 800; font-style: italic; color: #0f172a; padding: 2px 0 4px 0; text-transform: uppercase;">
                        To be attached:
                    </th>
                    <th style="text-align: right; width: 90px; font-size: @if($isPdf ?? false) 8pt @else 0.80rem @endif; font-weight: 800; color: #475569; padding: 2px 10px 4px 0; text-transform: uppercase; letter-spacing: 0.5px;">
                        Received
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1.5px 0; color: #1e293b; line-height: 1.25;">
                        1. Photo copy of Birth Certificate
                    </td>
                    <td style="text-align: right; padding: 1.5px 16px 1.5px 0; vertical-align: middle;">
                        <span class="doc-chk-box">{!! $hasBirthCert ? '&#10003;' : '&nbsp;' !!}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1.5px 0; color: #1e293b; line-height: 1.25;">
                        2. Official Transcript from Previous School (Report Card)
                    </td>
                    <td style="text-align: right; padding: 1.5px 16px 1.5px 0; vertical-align: middle;">
                        <span class="doc-chk-box">{!! $hasReportCard ? '&#10003;' : '&nbsp;' !!}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1.5px 0; color: #1e293b; line-height: 1.25;">
                        3. Medical Record (If any)
                    </td>
                    <td style="text-align: right; padding: 1.5px 16px 1.5px 0; vertical-align: middle;">
                        <span class="doc-chk-box">{!! $hasMedicalRecord ? '&#10003;' : '&nbsp;' !!}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1.5px 0; color: #1e293b; line-height: 1.25;">
                        4. Photo copy of Marriage Contract of Parents
                    </td>
                    <td style="text-align: right; padding: 1.5px 16px 1.5px 0; vertical-align: middle;">
                        <span class="doc-chk-box">{!! $hasMarriageContract ? '&#10003;' : '&nbsp;' !!}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1.5px 0; color: #1e293b; line-height: 1.25;">
                        5. Picture 2 x 2
                    </td>
                    <td style="text-align: right; padding: 1.5px 16px 1.5px 0; vertical-align: middle;">
                        <span class="doc-chk-box">{!! $hasPhoto2x2 ? '&#10003;' : '&nbsp;' !!}</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Verification Information Row -->
        <div class="doc-verification-row" style="margin-top: 8px; border-top: 1px dashed #cbd5e1; padding-top: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 65%; vertical-align: bottom; padding-right: 14px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 145px; vertical-align: bottom; font-size: @if($isPdf ?? false) 8pt @else 0.82rem @endif; font-weight: 700; color: #1e293b; white-space: nowrap;">
                                    Documents Checked By:
                                </td>
                                <td style="vertical-align: bottom;">
                                    <div class="input-line" style="font-size: @if($isPdf ?? false) 8.5pt @else 0.86rem @endif; min-height: 14px;">
                                        &nbsp;
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 35%; vertical-align: bottom;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td style="width: 85px; vertical-align: bottom; font-size: @if($isPdf ?? false) 8pt @else 0.82rem @endif; font-weight: 700; color: #1e293b; white-space: nowrap;">
                                    Date Checked:
                                </td>
                                <td style="vertical-align: bottom;">
                                    <div class="input-line" style="font-size: @if($isPdf ?? false) 8.5pt @else 0.86rem @endif; min-height: 14px;">
                                        &nbsp;
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

@if(!empty($includeAttachments) && !empty($imageAttachments))
    @foreach($imageAttachments as $att)
        <div class="paper-container attachment-print-page" style="page-break-before: always; width: 100%; text-align: center; padding-top: 12px;">
            <div style="text-align: left; border-bottom: 2px solid #059669; padding-bottom: 4px; margin-bottom: 12px;">
                <div style="font-family: 'DejaVu Sans', sans-serif; font-size: 10.5pt; font-weight: bold; color: #0f172a; text-transform: uppercase;">
                    ATTACHMENT: {{ $att['label'] }}
                </div>
                <div style="font-family: 'DejaVu Sans', sans-serif; font-size: 7.5pt; color: #475569; margin-top: 2px;">
                    Student: {{ $student->full_name }} &bull; AMIS ID: #{{ $student->student_number }} &bull; Grade: {{ $student->grade_level }}
                </div>
            </div>
            <div style="display: block; text-align: center; width: 100%; margin-top: 8px;">
                <img src="{{ $att['data_uri'] }}" style="max-width: 180mm; max-height: 235mm; object-fit: contain; margin: 0 auto; display: inline-block;">
            </div>
        </div>
    @endforeach
@endif
