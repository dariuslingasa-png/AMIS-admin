@php
    $app = $applicant ?? $student->applicant;
    $siblings = $siblings ?? [];
    
    $studentType = strtoupper($app->student_type ?? 'OLD');
    $isOld = str_contains($studentType, 'OLD');
    $isNew = str_contains($studentType, 'NEW') || !$isOld;

    static $cachedAmisLogo = null;
    static $cachedDepedLogo = null;

    if ($cachedAmisLogo === null) {
        $path = public_path('images/AMIS_Logo.png');
        $cachedAmisLogo = file_exists($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : asset('images/AMIS_Logo.png');
    }

    if ($cachedDepedLogo === null) {
        $path = public_path('images/logo/deped_logo.png');
        $cachedDepedLogo = file_exists($path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($path)) : asset('images/logo/deped_logo.png');
    }

    $amisLogoSrc = $cachedAmisLogo;
    $depedLogoSrc = $cachedDepedLogo;

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

    // Robust Student 2x2 Photo URL resolver with Base64 embedding for Dompdf & Word compatibility
    $photoSrc = null;
    $photoRelative = $app->photo_2x2_url ?? $student->photo_url ?? null;
    if ($photoRelative) {
        $absPath = \App\Support\EnrollmentStorage::getAbsolutePath($photoRelative);
        if ($absPath && file_exists($absPath)) {
            $ext = pathinfo($absPath, PATHINFO_EXTENSION);
            $imgData = @file_get_contents($absPath);
            if ($imgData) {
                $photoSrc = 'data:image/' . $ext . ';base64,' . base64_encode($imgData);
            }
        }
    }
    if (!$photoSrc && $app && !empty($app->photo_2x2_url)) {
        $photoSrc = \App\Support\EnrollmentStorage::url($app->photo_2x2_url);
    }

    // Multi-tier Helper function for dynamic font-size calculation on long text
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
@endphp

<!-- PAGE 1: ENROLMENT APPLICATION FORM -->
<div class="paper-container paper-page-break">
    @if(isset($pageNumber))
        <div class="page-number-badge">
            PAGE {{ $pageNumber }}{{ isset($totalPages) && $totalPages > 1 ? ' OF ' . $totalPages : '' }}
        </div>
    @endif

    <!-- Table-based Header Row for Dompdf/Word/Browser Compatibility -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="width: 75px; vertical-align: middle;">
                <img src="{{ $amisLogoSrc }}" alt="AMIS Logo" style="width: 70px; height: 70px; object-fit: contain;">
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <h1 class="school-name" style="margin: 0; font-size: 1.15rem; font-weight: 900; letter-spacing: 0.3px; color: #0f172a; text-transform: uppercase;">AL MUNAWWARA ISLAMIC SCHOOL</h1>
                <p class="school-address" style="margin: 2px 0 0 0; font-size: 0.85rem; color: #334155;">Bugac Ma-a Road, Davao City Philippines</p>
            </td>
            <td style="width: 70px; text-align: right; vertical-align: middle;">
                <img src="{{ $depedLogoSrc }}" alt="DepEd Logo" style="width: 65px; height: 65px; object-fit: contain;">
            </td>
            <td style="width: 140px; text-align: right; vertical-align: middle; padding-left: 8px;">
                <div class="refund-notice-box" style="border: 2px solid #dc2626; padding: 4px 8px; text-align: center; font-family: 'Inter', sans-serif; font-weight: 800; font-size: 0.80rem; color: #dc2626; text-transform: uppercase; border-radius: 4px;">
                    NO REFUND OF<br>ENROLLMENT FEE
                </div>
            </td>
        </tr>
    </table>

    <!-- Table-based Middle Grid (Title, Checkboxes, 2x2 Photo) -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="vertical-align: top;">
                <h2 class="form-title" style="font-size: 1.3rem; font-weight: 900; text-transform: uppercase; margin: 0; color: #0f172a;">ENROLMENT APPLICATION FORM</h2>
                <p class="sy-title" style="font-size: 1.1rem; font-weight: 700; margin: 3px 0 10px 0; color: #1e293b;">SY {{ $app->school_year ?? '2026-2027' }}</p>
                
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 800; text-transform: uppercase; padding-right: 10px; white-space: nowrap; color: #0f172a;">STUDENT INFORMATION</td>
                        <td style="font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 700; white-space: nowrap; color: #0f172a;">LRN:</td>
                        <td style="padding-left: 5px;">
                            <input type="text" class="lrn-input" value="{{ mb_strtoupper($app->lrn ?? $student->student_number) }}" style="border: none; border-bottom: 1.5px solid #0f172a; font-family: 'Inter', sans-serif; font-size: 1rem; font-weight: 700; color: #0f172a; width: 180px; outline: none; text-transform: uppercase; {{ $getDynamicStyle($app->lrn ?? $student->student_number, '1rem', '0.88rem', '0.78rem', '0.68rem', 12, 18, 24) }}">
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 90px; vertical-align: top; padding-top: 15px;">
                <div style="font-family: 'Inter', sans-serif; font-weight: 800; font-size: 0.88rem; color: #0f172a; margin-bottom: 8px;">
                    <span style="display: inline-block; width: 16px; height: 16px; border: 1.5px solid #0f172a; text-align: center; line-height: 14px; font-size: 12px; font-weight: 900; margin-right: 4px; border-radius: 2px;">{{ $isOld ? 'X' : '' }}</span> OLD
                </div>
                <div style="font-family: 'Inter', sans-serif; font-weight: 800; font-size: 0.88rem; color: #0f172a;">
                    <span style="display: inline-block; width: 16px; height: 16px; border: 1.5px solid #0f172a; text-align: center; line-height: 14px; font-size: 12px; font-weight: 900; margin-right: 4px; border-radius: 2px;">{{ $isNew ? 'X' : '' }}</span> NEW
                </div>
            </td>
            <td style="width: 120px; text-align: right; vertical-align: top;">
                <div style="width: 115px; height: 115px; border: 1.5px solid #0f172a; display: inline-block; overflow: hidden; background: #fafafa; text-align: center; border-radius: 3px;">
                    @if($photoSrc)
                        <img src="{{ $photoSrc }}" alt="Student Photo" style="width: 115px; height: 115px; object-fit: cover;">
                    @else
                        <div style="padding-top: 45px; font-family: 'Inter', sans-serif; font-size: 8px; font-weight: 800; text-transform: uppercase; color: #64748b;">2x2 PHOTO</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- 5-Column Student Name & Grade Row -->
    <div class="field-container">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 28%; padding-right: 8px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->last_name ?? '') }}" style="{{ $getDynamicStyle($app->last_name ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 22, 32, 42) }}">
                    <span class="label-text">Last</span>
                </td>
                <td style="width: 28%; padding-right: 8px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->first_name ?? '') }}" style="{{ $getDynamicStyle($app->first_name ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 22, 32, 42) }}">
                    <span class="label-text">First</span>
                </td>
                <td style="width: 24%; padding-right: 8px; vertical-align: top;">
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
                </td>
                <td style="width: 8%; padding-right: 8px; text-align: center; vertical-align: top;">
                    @php
                        $g = strtoupper(trim($app->gender ?? ''));
                        $sexChar = str_starts_with($g, 'F') ? 'F' : (str_starts_with($g, 'M') ? 'M' : $g);
                    @endphp
                    <input type="text" class="input-line" value="{{ $sexChar }}" style="text-align: center;">
                    <span class="label-text" style="text-align: center;">Sex</span>
                </td>
                <td style="width: 12%; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($student->grade_level ?? $app->grade_level ?? '') }}" style="{{ $getDynamicStyle($student->grade_level ?? $app->grade_level ?? '', '0.82rem', '0.72rem', '0.62rem', '0.52rem', 8, 14, 20) }}">
                    <span class="label-text">Grade Level</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Student Address -->
    <div class="field-container" style="margin-top: 14px;">
        <input type="text" class="input-line" value="{{ $fullAddress }}" style="{{ $getDynamicStyle($fullAddress, '0.92rem', '0.78rem', '0.66rem', '0.54rem', 35, 55, 75) }}">
        <span class="label-text">Address</span>
    </div>

    <!-- 4-Column Birth Info -->
    <div class="field-container" style="margin-top: 14px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 12%; padding-right: 10px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ $studentAge }}">
                    <span class="label-text">Age</span>
                </td>
                <td style="width: 25%; padding-right: 10px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ $app?->date_of_birth ? mb_strtoupper($app->date_of_birth->format('M d, Y')) : '' }}">
                    <span class="label-text">Date of Birth</span>
                </td>
                <td style="width: 43%; padding-right: 10px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->place_of_birth ?? '') }}" style="{{ $getDynamicStyle($app->place_of_birth ?? '', '0.92rem', '0.78rem', '0.66rem', '0.54rem', 18, 30, 42) }}">
                    <span class="label-text">Place of Birth</span>
                </td>
                <td style="width: 20%; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->religion ?? 'Islam') }}">
                    <span class="label-text">Religion</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Previous School Name -->
    <div class="field-container" style="margin-top: 14px;">
        <input type="text" class="input-line" value="{{ mb_strtoupper($app->previous_school_name ?? '') }}" style="{{ $getDynamicStyle($app->previous_school_name ?? '', '0.92rem', '0.78rem', '0.66rem', '0.54rem', 30, 50, 70) }}">
        <span class="label-text">Previous Attended School Name</span>
    </div>

    <!-- Previous School Address & Phone -->
    <div class="field-container" style="margin-top: 14px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 70%; padding-right: 15px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->previous_school_address ?? '') }}" style="{{ $getDynamicStyle($app->previous_school_address ?? '', '0.92rem', '0.78rem', '0.66rem', '0.54rem', 30, 50, 70) }}">
                    <span class="label-text">Previous School Address</span>
                </td>
                <td style="width: 30%; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->mobile_number ?? $app->parent_mobile ?? '') }}">
                    <span class="label-text">Telephone No.</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-header-row" style="margin-top: 22px;">
        PARENT INFORMATION
    </div>

    <!-- Father's Info -->
    <div class="field-container" style="margin-top: 10px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 42%; padding-right: 12px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ $fatherFull }}" style="{{ $getDynamicStyle($fatherFull, '0.96rem', '0.84rem', '0.72rem', '0.60rem', 25, 35, 45) }}">
                    <span class="label-text">Father's Full Name</span>
                </td>
                <td style="width: 26%; padding-right: 12px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->father_occupation ?? '') }}" style="{{ $getDynamicStyle($app->father_occupation ?? '', '0.94rem', '0.82rem', '0.70rem', '0.60rem', 22, 32, 42) }}">
                    <span class="label-text">Occupation</span>
                </td>
                <td style="width: 32%; vertical-align: top;">
                    <div style="border-bottom: 1.5px solid #0f172a; padding: 1px 3px; min-height: 24px;">
                        <div style="font-family: 'Inter', sans-serif; font-size: 0.88rem; font-weight: 750; color: #0f172a; line-height: 1.2;">
                            {{ $parentPhone }}
                        </div>
                    </div>
                    <span class="label-text">Tel./Email address</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Mother's Info -->
    <div class="field-container" style="margin-top: 14px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 42%; padding-right: 12px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ $motherFull }}" style="{{ $getDynamicStyle($motherFull, '0.96rem', '0.84rem', '0.72rem', '0.60rem', 25, 35, 45) }}">
                    <span class="label-text">Mother's Full Name</span>
                </td>
                <td style="width: 26%; padding-right: 12px; vertical-align: top;">
                    <input type="text" class="input-line" value="{{ mb_strtoupper($app->mother_occupation ?? '') }}" style="{{ $getDynamicStyle($app->mother_occupation ?? '', '0.94rem', '0.82rem', '0.70rem', '0.60rem', 22, 32, 42) }}">
                    <span class="label-text">Occupation</span>
                </td>
                <td style="width: 32%; vertical-align: top;">
                    <div style="border-bottom: 1.5px solid #0f172a; padding: 1px 3px; min-height: 24px;">
                        <div style="font-family: 'Inter', sans-serif; font-size: 0.88rem; font-weight: 750; color: #0f172a; line-height: 1.2;">
                            {{ $parentPhone }}
                        </div>
                    </div>
                    <span class="label-text">Tel./Email address</span>
                </td>
            </tr>
        </table>
    </div>

    @php
        $formattedHomeAddress = mb_strtoupper(preg_replace('/(\d+)([A-Za-z])/', '$1 $2', $app->home_address ?? $fullAddress));
    @endphp

    <div class="field-container" style="margin-top: 14px;">
        <input type="text" class="input-line" value="{{ $formattedHomeAddress }}" style="{{ $getDynamicStyle($formattedHomeAddress, '0.96rem', '0.82rem', '0.70rem', '0.60rem', 45, 65, 85) }}">
        <span class="label-text">Home Address</span>
    </div>

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
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 55%; padding-right: 10px; vertical-align: top;">
                        <input type="text" class="input-line" value="{{ $sibName }}" style="{{ $getDynamicStyle($sibName, '0.98rem', '0.82rem', '0.68rem', '0.58rem', 22, 32, 40) }}">
                        <span class="label-text">Name</span>
                    </td>
                    <td style="width: 15%; padding-right: 10px; vertical-align: top;">
                        <input type="text" class="input-line" value="{{ $sibAge }}">
                        <span class="label-text">Age</span>
                    </td>
                    <td style="width: 30%; vertical-align: top;">
                        <input type="text" class="input-line" value="{{ $sibGrade }}">
                        <span class="label-text">Grade Level</span>
                    </td>
                </tr>
            </table>
        </div>
    @endfor

    <!-- Lives With Row -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 600;">
        <tr>
            <td style="width: 160px;">Applicant lives with:</td>
            <td style="width: 130px;">
                <span style="display: inline-block; width: 30px; border-bottom: 1.5px solid #000; text-align: center; font-weight: 800; margin-right: 4px;">{{ $bothParents ? 'X' : '' }}</span> Both Parents
            </td>
            <td style="width: 140px;">
                <span style="display: inline-block; width: 30px; border-bottom: 1.5px solid #000; text-align: center; font-weight: 800; margin-right: 4px;">{{ $singleParent ? 'X' : '' }}</span> Mother/Father
            </td>
            <td>
                <span style="display: inline-block; width: 30px; border-bottom: 1.5px solid #000; text-align: center; font-weight: 800; margin-right: 4px;">{{ $guardianPresent ? 'X' : '' }}</span> Guardian
            </td>
        </tr>
    </table>
</div>

<!-- PAGE 2: MEDICAL INFORMATION, EMERGENCY CONTACTS, REFERRAL & POLICIES -->
<div class="paper-container paper-page-break">
    <div class="section-header-row" style="margin-top: 5px; margin-bottom: 15px;">
        MEDICAL INFORMATION
    </div>

    @php
        $hasPsych = !empty($app->psych_testing) && $app->psych_testing !== 'no';
        $hasMed   = !empty($app->prescription_med) && $app->prescription_med !== 'no';
    @endphp

    <div class="p2-question-row">
        Has the student ever had psychological testing or been screened for academic difficulties or learning disabilities? 
        &nbsp; YES <span class="p2-inline-line">{{ $hasPsych ? 'X' : '' }}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasPsych ? 'X' : '' }}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->med_explanation ?? '') }}">
    </div>

    <div class="p2-question-row" style="margin-top: 20px;">
        Prescription Medication: 
        &nbsp; YES <span class="p2-inline-line">{{ $hasMed ? 'X' : '' }}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasMed ? 'X' : '' }}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->current_medications ?? '') }}">
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 12px;">
        <tr>
            <td style="width: 60%; padding-right: 15px; vertical-align: top;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->family_physician ?? '') }}">
                <span class="label-text">Family Physician:</span>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->physician_phone ?? '') }}">
                <span class="label-text">Phone:</span>
            </td>
        </tr>
    </table>

    <div class="section-header-row" style="margin-top: 25px;">
        EMERGENCY CONTACTS <span style="font-size: 0.9rem; font-weight: normal; text-transform: none; color: #475569;">(Other than above names)</span>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <td style="width: 45%; padding-right: 12px; vertical-align: top;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->emergency_name ?? '') }}" style="{{ $getDynamicStyle($app->emergency_name ?? '', '0.98rem', '0.80rem', '0.68rem', '0.58rem', 20, 28, 35) }}">
                <span class="label-text">Name</span>
            </td>
            <td style="width: 30%; padding-right: 12px; vertical-align: top;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->emergency_relationship ?? '') }}">
                <span class="label-text">Relationship</span>
            </td>
            <td style="width: 25%; vertical-align: top;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->emergency_phone ?? '') }}">
                <span class="label-text">Phone</span>
            </td>
        </tr>
    </table>

    <div class="section-header-row" style="margin-top: 25px;">
        REFERRAL
    </div>

    <div class="field-container" style="margin-top: 10px;">
        <input type="text" class="input-line" value="{{ mb_strtoupper($app->referral_source ?? '') }}">
        <span class="label-text">I heard about AMIS from</span>
    </div>

    <p class="p2-policy-text">
        I understand that if and when the applicant is enrolled, I agree to comply with the rules, regulations and policies of Al Munawwara Islamic School as outlined in the Parent Student Handbook and other official communications.
    </p>

    <p class="p2-policy-text">
        It is further understood that Al Munawwara Islamic School reserves the right to dismiss any student for any reason deemed to be in the best interest of the school. Dismissal of the student does not release the parent from the financial obligations related to the school fees and other fees thereat.
    </p>

    <div class="section-header-row" style="margin-top: 25px;">
        SIGNATURE
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-top: 25px;">
        <tr>
            <td style="width: 70%; padding-right: 25px; vertical-align: top;">
                <input type="text" class="input-line" value="{{ $fatherFull ?: $motherFull }}" style="{{ $getDynamicStyle($fatherFull ?: $motherFull, '0.98rem', '0.80rem', '0.68rem', '0.58rem', 22, 32, 40) }}">
                <span class="label-text">Parent/Guardian</span>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <input type="text" class="input-line" value="{{ mb_strtoupper($student->created_at->format('M d, Y')) }}">
                <span class="label-text">Date</span>
            </td>
        </tr>
    </table>

    <p class="signature-disclaimer">
        *Only completed application will be accepted. Submission of an application does not guarantee admission
    </p>

    <hr class="office-perforated-line">

    <table style="width: 100%; border-collapse: collapse; margin-top: 12px; font-family: 'Inter', sans-serif; font-size: 0.92rem; font-weight: 600;">
        <tr>
            <td style="width: 45%; vertical-align: top;">
                <span>Application submitted on:</span>
                <span style="font-weight: 800; border-bottom: 1.5px solid #0f172a; padding: 0 4px;">{{ $student->created_at->format('m / d / Y') }}</span>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <span>Paid:</span>
                <input type="text" class="input-line" style="width: 100px; display: inline-block;" value="{{ $app?->payment?->amount_paid ? '₱' . number_format($app->payment->amount_paid, 2) : '' }}">
            </td>
            <td style="width: 25%; vertical-align: top;">
                <span>OR No.:</span>
                <input type="text" class="input-line" style="width: 100px; display: inline-block;" value="{{ mb_strtoupper($app?->payment?->reference_number ?? '') }}">
            </td>
        </tr>
    </table>

    <div class="attachments-title">To be attached:</div>
    <ol class="attachments-list">
        <li>Photo copy of Birth Certificate</li>
        <li>Official Transcript from Previous School (Report Card)</li>
        <li>Medical Record (If any)</li>
        <li>Photo copy of Marriage Contract of Parents</li>
        <li>Picture 2 x 2</li>
    </ol>
</div>
