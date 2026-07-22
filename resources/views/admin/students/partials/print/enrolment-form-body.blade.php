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

    // Robust Student 2x2 Photo URL resolver (optimised to use direct storage URLs instead of heavy server-side base64 encoding)
    $resolvePhotoUrl = function($relativePath) {
        if (empty($relativePath)) return null;
        $lpath = ltrim($relativePath, '/');
        $candidates = [
            public_path('storage/' . $lpath),
            storage_path('app/public/' . $lpath),
            storage_path('app/' . $lpath),
            '/home2/amisdavc/enrollment.amis.edu.ph/storage/app/public/' . $lpath,
            '/home2/amisdavc/enrollment.amis.edu.ph/public/storage/' . $lpath,
            public_path($lpath)
        ];
        foreach ($candidates as $c) {
            if (file_exists($c) && is_file($c)) {
                return asset('storage/' . $lpath);
            }
        }
        return null;
    };

    $photoSrc = null;
    if ($app && !empty($app->photo_2x2_url)) {
        $photoSrc = $resolvePhotoUrl($app->photo_2x2_url);
    }
    if (!$photoSrc && $student && !empty($student->photo_url)) {
        $photoSrc = $resolvePhotoUrl($student->photo_url);
    }
    if (!$photoSrc && $student && !empty($student->obfuscated_id)) {
        $photoSrc = 'https://amis.edu.ph/student-photo/' . $student->obfuscated_id . '.jpg';
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
@endphp

<!-- PAGE 1: ENROLMENT APPLICATION FORM -->
<div class="paper-container paper-page-break">
    @if(isset($pageNumber))
        <div class="page-number-badge">
            PAGE {{ $pageNumber }}{{ isset($totalPages) && $totalPages > 1 ? ' OF ' . $totalPages : '' }}
        </div>
    @endif
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

        <div class="photo-box">
            @if($photoSrc)
                <img src="{{ $photoSrc }}" alt="Student 2x2 Photo">
            @endif
        </div>
    </div>

    <div class="field-container">
        <div class="grid-5-col">
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->last_name ?? '') }}" style="{{ $getDynamicStyle($app->last_name ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 22, 32, 42) }}">
                <span class="label-text">Last</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->first_name ?? '') }}" style="{{ $getDynamicStyle($app->first_name ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 22, 32, 42) }}">
                <span class="label-text">First</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->middle_name ?? '') }}" style="{{ $getDynamicStyle($app->middle_name ?? '', '0.96rem', '0.84rem', '0.72rem', '0.60rem', 22, 32, 42) }}">
                <span class="label-text">Middle</span>
            </div>
            <div>
                @php
                    $g = strtoupper(trim($app->gender ?? ''));
                    $sexChar = str_starts_with($g, 'F') ? 'F' : (str_starts_with($g, 'M') ? 'M' : $g);
                @endphp
                <input type="text" class="input-line" value="{{ $sexChar }}" style="text-align: center;">
                <span class="label-text" style="text-align: center;">Sex</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($student->grade_level ?? $app->grade_level ?? '') }}" style="{{ $getDynamicStyle($student->grade_level ?? $app->grade_level ?? '', '0.82rem', '0.72rem', '0.62rem', '0.52rem', 8, 14, 20) }}">
                <span class="label-text">Grade Level</span>
            </div>
        </div>
    </div>

    <div class="field-container" style="margin-top: 14px;">
        <input type="text" class="input-line" value="{{ $fullAddress }}" style="{{ $getDynamicStyle($fullAddress, '0.92rem', '0.78rem', '0.66rem', '0.54rem', 35, 55, 75) }}">
        <span class="label-text">Address</span>
    </div>

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
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->place_of_birth ?? '') }}" style="{{ $getDynamicStyle($app->place_of_birth ?? '', '0.92rem', '0.78rem', '0.66rem', '0.54rem', 18, 30, 42) }}">
                <span class="label-text">Place of Birth</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->religion ?? 'Islam') }}">
                <span class="label-text">Religion</span>
            </div>
        </div>
    </div>

    <div class="field-container" style="margin-top: 14px;">
        <input type="text" class="input-line" value="{{ mb_strtoupper($app->previous_school_name ?? '') }}" style="{{ $getDynamicStyle($app->previous_school_name ?? '', '0.92rem', '0.78rem', '0.66rem', '0.54rem', 30, 50, 70) }}">
        <span class="label-text">Previous Attended School Name</span>
    </div>

    <div class="field-container" style="margin-top: 14px;">
        <div class="grid-2-col-school">
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->previous_school_address ?? '') }}" style="{{ $getDynamicStyle($app->previous_school_address ?? '', '0.92rem', '0.78rem', '0.66rem', '0.54rem', 30, 50, 70) }}">
                <span class="label-text">Previous School Address</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->mobile_number ?? $app->parent_mobile ?? '') }}">
                <span class="label-text">Telephone No.</span>
            </div>
        </div>
    </div>

    <div class="section-header-row" style="margin-top: 22px;">
        PARENT INFORMATION
    </div>

    <div class="field-container" style="margin-top: 10px;">
        <div class="grid-parent-row">
            <div>
                <input type="text" class="input-line" value="{{ $fatherFull }}" style="{{ $getDynamicStyle($fatherFull, '0.96rem', '0.84rem', '0.72rem', '0.60rem', 25, 35, 45) }}">
                <span class="label-text">Father's Full Name</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->father_occupation ?? '') }}" style="{{ $getDynamicStyle($app->father_occupation ?? '', '0.94rem', '0.82rem', '0.70rem', '0.60rem', 22, 32, 42) }}">
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

    <div class="field-container" style="margin-top: 14px;">
        <div class="grid-parent-row">
            <div>
                <input type="text" class="input-line" value="{{ $motherFull }}" style="{{ $getDynamicStyle($motherFull, '0.96rem', '0.84rem', '0.72rem', '0.60rem', 25, 35, 45) }}">
                <span class="label-text">Mother's Full Name</span>
            </div>
            <div>
                <input type="text" class="input-line" value="{{ mb_strtoupper($app->mother_occupation ?? '') }}" style="{{ $getDynamicStyle($app->mother_occupation ?? '', '0.94rem', '0.82rem', '0.70rem', '0.60rem', 22, 32, 42) }}">
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
        &nbsp; YES <span class="p2-inline-line">{{ $hasPsych ? '✓' : '' }}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasPsych ? '✓' : '' }}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->med_explanation ?? '') }}">
    </div>

    <div class="p2-question-row" style="margin-top: 20px;">
        Prescription Medication: 
        &nbsp; YES <span class="p2-inline-line">{{ $hasMed ? '✓' : '' }}</span> 
        &nbsp;&nbsp; NO <span class="p2-inline-line">{{ !$hasMed ? '✓' : '' }}</span>
    </div>

    <div class="p2-explain-block">
        <span class="p2-explain-label">If yes, please explain:</span>
        <input type="text" class="p2-full-line" value="{{ mb_strtoupper($app->current_medications ?? '') }}">
    </div>

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

    <hr class="office-perforated-line">

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

    <div class="attachments-title">To be attached:</div>
    <ol class="attachments-list">
        <li>Photo copy of Birth Certificate</li>
        <li>Official Transcript from Previous School (Report Card)</li>
        <li>Medical Record (If any)</li>
        <li>Photo copy of Marriage Contract of Parents</li>
        <li>Picture 2 x 2</li>
    </ol>
</div>
