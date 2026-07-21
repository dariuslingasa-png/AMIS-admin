<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grade ID Cards Roster - {{ $grade }}</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap');
        
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }
        .toolbar {
            position: sticky;
            top: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 24px;
            background: #0f172a;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
        }
        .toolbar-title {
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .toolbar-actions {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            border: 0;
            border-radius: 8px;
            background: #059669;
            color: #fff;
            cursor: pointer;
            font-weight: 800;
            font-size: 12px;
            padding: 8px 16px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-action:hover { background: #047857; }
        .btn-secondary {
            background: #334155;
        }
        .btn-secondary:hover { background: #475569; }

        .page-container {
            max-width: 210mm;
            margin: 20px auto;
            background: #fff;
            padding: 15mm;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            text-align: center;
            border-bottom: 2px solid #059669;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
        }
        .section-subtitle {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }

        .student-card-item {
            margin-bottom: 32px;
            page-break-inside: avoid;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            background: #fafafa;
        }
        .student-item-header {
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cards-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cards-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 8px;
        }

        /* Front & Back Card Scaling */
        .id-card-wrapper {
            width: 70.2mm;
            height: 111.28mm;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            background: transparent;
            margin: 0 auto;
        }
        
        .id-card-scaler {
            width: 340px;
            height: 538px;
            position: absolute;
            top: 0;
            left: 0;
            transform: scale(0.78);
            transform-origin: top left;
        }
        
        .id-card {
            width: 340px;
            height: 538px;
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            background: #064e3b;
            text-align: left;
        }
        
        .photo-clip {
            position: absolute;
            left: 71px;
            top: 144px;
            width: 198px;
            height: 192px;
            overflow: hidden;
            background: transparent;
            border-radius: 6px;
        }
        
        .photo-clip img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center center;
            display: block;
            z-index: 1;
        }
        
        .photo-placeholder {
            position: absolute;
            left: 71px;
            top: 144px;
            width: 198px;
            height: 192px;
            z-index: 5;
            border-radius: 6px;
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
            top: 325px;
            width: 340px;
            height: 15px;
            z-index: 20;
            background: transparent;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            letter-spacing: 0.05em;
            font-size: var(--id-font-size, 12.5px);
            color: white;
            text-align: center;
            line-height: 15px;
        }
        
        .student-last-name {
            position: absolute;
            left: 15px;
            top: 352px;
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
            top: 386px;
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
            top: 412px;
            width: 310px;
            height: 30px;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 16px;
        }
        
        .student-grade span {
            font-family: 'Outfit', sans-serif;
            font-size: 31px;
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
            left: 239px;
            top: 394px;
            width: 170px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
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
        .emergency-info {
            position: absolute;
            left: 28px;
            top: 85px;
            width: 284px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }
        .emerg-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .emerg-icon {
            flex-shrink: 0;
            width: 14px;
            height: 14px;
            color: #047857;
            margin-top: 1.5px;
        }
        .emerg-text {
            text-align: left;
        }
        .back-signature-qr {
            position: absolute;
            left: 142.5px;
            top: 422px;
            width: 55px;
            height: 55px;
            z-index: 25;
            padding: 1.5px;
            border-radius: 2px;
            background: white;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .back-signature-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Watermark stamp style */
        .id-watermark {
            position: absolute;
            z-index: 99;
            pointer-events: none;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(239, 68, 68, 0.48);
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.95);
            paint-order: stroke fill;
            border: 4px double rgba(239, 68, 68, 0.48);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 24px;
            transform: rotate(-12deg);
        }
        .front-watermark {
            left: 55px;
            top: 72px;
        }
        .back-watermark {
            left: 55px;
            top: 388px;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .no-print { display: none !important; }
            .page-container {
                max-width: none;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .student-card-item {
                border: 1px solid #cbd5e1;
                page-break-inside: avoid;
            }
            /* Prevent browser from upscaling images during print = reduces memory */
            img {
                image-rendering: auto;
                max-width: 100% !important;
            }
            /* Hide toolbar in print */
            .toolbar, .no-print { display: none !important; }
        }
    </style>
    <style id="dynamic-font-sizes">
        :root {
            --id-font-size: 12.5px;
            --last-name-font-size: 30px;
            --first-name-font-size: 16px;
            --grade-font-size: 31px;
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div class="toolbar-title">
            📋 Grade ID Cards Roster: {{ strtoupper($grade) }}
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-action btn-secondary" onclick="toggleEditor()" id="btn-toggle-editor">
                ✏️ Edit Font Sizes
            </button>
            <button type="button" class="btn-action btn-secondary" onclick="copyDocumentHtml()">
                📋 Copy for Google Docs / MS Word
            </button>
            <button type="button" class="btn-action btn-secondary" onclick="printBySection(this)" id="btn-print-section" title="Print one section at a time — safer for large grades">
                📄 Print by Section
            </button>
            <button type="button" class="btn-action btn-secondary" onclick="smartPrint(this)" id="btn-print-pdf" title="Raw print without rendering to flat images first">
                🖨️ Raw Print All
            </button>
            <button type="button" class="btn-action" onclick="optimizeAndPrint(this)" style="background: #0284c7;" id="btn-optimize-print" title="Highly Recommended: Converts ID cards to flat images to prevent browser crashes during large prints">
                🚀 Optimize & Print (Prevents Crash)
            </button>
        </div>
    </div>

    <div class="page-container" id="roster-document-area">
        <div class="section-header">
            <h1 class="section-title">GRADE LEVEL: {{ strtoupper($grade) }}</h1>
            <div class="section-subtitle">Official Student ID Cards Roster Document • S.Y. {{ config('services.school.previous_year', '2025-2026') }}</div>
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

        @php
            $formatSectionName = function($section) {
                if (!$section) return 'UNASSIGNED';
                
                $isF2f = str_contains(strtolower((string) $section->learning_mode), 'face') ||
                         str_contains(strtolower((string) $section->learning_mode), 'f2f') ||
                         strtoupper((string) $section->shift) === 'F2F';
                         
                if ($isF2f) {
                    return strtoupper($section->name);
                }
                
                $shift = $section->shift ? strtoupper(trim($section->shift)) : '';
                $gender = '';
                if ($section->gender && in_array(strtolower($section->gender), ['male', 'female', 'boy', 'girl', 'boys', 'girls'])) {
                    $g = strtolower($section->gender);
                    if ($g === 'male' || $g === 'boy' || $g === 'boys') {
                        $gender = 'BOYS';
                    } else {
                        $gender = 'GIRLS';
                    }
                }
                
                $rawName = strtoupper(trim($section->name));
                $rawName = preg_replace('/\s*-\s*(GIRLS|BOYS)\s*$/i', '', $rawName);
                $rawName = preg_replace('/\s*(GIRLS|BOYS)\s*$/i', '', $rawName);
                
                $parts = array_filter([$shift, $gender]);
                $prefix = implode(' ', $parts);
                
                if ($prefix) {
                    return $prefix . ' – ' . $rawName;
                }
                
                return $rawName;
            };

            $groupedStudents = $students->groupBy(function($s) use ($formatSectionName) {
                return $formatSectionName($s->studentSection?->section);
            })->sortBy(function($students, $sectionName) {
                $sec = $students->first()?->studentSection?->section;
                if (!$sec) return 999;
                
                $isF2f = str_contains(strtolower((string) $sec->learning_mode), 'face') ||
                         str_contains(strtolower((string) $sec->learning_mode), 'f2f') ||
                         strtoupper((string) $sec->shift) === 'F2F';
                         
                if ($isF2f) {
                    return 10;
                }
                
                $shiftWeight = 0;
                $shift = strtolower((string)$sec->shift);
                if (str_contains($shift, '1st') || str_contains($shift, 'first')) {
                    $shiftWeight = 100;
                } elseif (str_contains($shift, '2nd') || str_contains($shift, 'second')) {
                    $shiftWeight = 200;
                } else {
                    $shiftWeight = 300;
                }
                
                $genderWeight = 0;
                $gender = strtolower((string)$sec->gender);
                if ($gender === 'female' || $gender === 'girl' || $gender === 'girls') {
                    $genderWeight = 1;
                } elseif ($gender === 'male' || $gender === 'boy' || $gender === 'boys') {
                    $genderWeight = 2;
                } else {
                    $genderWeight = 3;
                }
                
                return $shiftWeight + $genderWeight;
            });
        @endphp

        @forelse($groupedStudents as $sectionName => $sectionStudents)
            <div class="section-group-header" style="margin-top: 36px; margin-bottom: 20px; border-bottom: 2px solid #0f172a; padding-bottom: 8px; page-break-before: {{ $loop->first ? 'avoid' : 'always' }}; text-align: left;">
                <h2 style="font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 900; text-transform: uppercase; color: #0f172a; margin: 0; display: flex; align-items: center; justify-content: space-between;">
                    <span>{{ $sectionName }}</span>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: none; margin-left: auto;">({{ $sectionStudents->count() }} Students)</span>
                </h2>
            </div>
            
            @foreach($sectionStudents as $index => $student)
                @php
                    $applicant = $student->applicant;
                    
                    $firstName = trim($applicant?->first_name ?? '');
                    $middleName = trim($applicant?->middle_name ?? '');
                    $lastName = trim($applicant?->last_name ?? '');
                    
                    $middleInitial = '';
                    if ($middleName !== '') {
                        $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
                        $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
                    }
                    
                    $fullNameParts = array_filter([$lastName . ',', $firstName, $middleInitial], fn($v) => $v !== '');
                    $fullName = implode(' ', $fullNameParts);

                    $studentNumber = $student->student_number ?? 'N/A';
                    $lrn = $applicant?->lrn && !in_array(strtoupper($applicant->lrn), ['N/A', 'NA', 'EMPTY', '']) ? $applicant->lrn : 'N/A';

                    // Resolve Photos & QR Codes
                    $photoUrl = '';
                    if ($student->photo_url) {
                        $photoUrl = $student->photo_url;
                    } elseif ($applicant?->photo_url) {
                        $photoUrl = $applicant->photo_url;
                    } elseif ($applicant?->photo_2x2_url) {
                        $photoUrl = \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url);
                    }
                    $hash = base64_encode((int)$studentNumber + 987654);
                    $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=100';
                    $signatureRawUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/signature') . '&dark=000000&light=ffffff&margin=1&format=png&size=200';

                    $displayGrade = $student->grade_level;

                    $homeAddress = implode(', ', array_filter([$applicant?->home_street_address, $applicant?->home_city, $applicant?->home_state_province]));
                    if (empty($homeAddress)) {
                        $homeAddress = $applicant?->home_address ?: 'DAVAO CITY, PHILIPPINES';
                    }
                    
                    $rawEmergencyName = trim($applicant?->emergency_name ?? '');
                    $fatherName = trim(($applicant?->father_first_name ?? '') . ' ' . ($applicant?->father_last_name ?? ''));
                    $motherName = trim(($applicant?->mother_first_name ?? '') . ' ' . ($applicant?->mother_last_name ?? ''));
                    
                    if (empty($rawEmergencyName) || strtolower($rawEmergencyName) === 'emergency contact' || is_numeric(str_replace(['+', ' ', '-', '(', ')'], '', $rawEmergencyName))) {
                        $emergencyName = $fatherName ?: ($motherName ?: 'REGISTRAR OFFICE');
                    } else {
                        $emergencyName = $rawEmergencyName;
                    }
                    
                    $relationship = trim($applicant?->emergency_relationship ?? '');
                    if (empty($relationship)) {
                        if (!empty($fatherName) && str_contains(strtolower($emergencyName), strtolower($fatherName))) {
                            $relationship = 'FATHER';
                        } elseif (!empty($motherName) && str_contains(strtolower($emergencyName), strtolower($motherName))) {
                            $relationship = 'MOTHER';
                        } else {
                            $relationship = 'PARENT / GUARDIAN';
                        }
                    }
                    
                    $emergencyPhone = $applicant?->emergency_phone ?: '';
                    if (empty($emergencyPhone)) {
                        $emergencyPhone = $applicant?->parent_mobile ?: ($applicant?->mobile_number ?: '+63 900 000 0000');
                    }

                    // Dynamic font sizes matching print_id.blade.php
                    $lastNameStyle = 'white-space: nowrap;';
                    $lastNameLen = strlen($lastName);
                    if ($student->id_last_name_font_size) {
                        $lastNameFontSize = $student->id_last_name_font_size . 'px';
                        if ($lastNameLen > 20) {
                            $lastNameStyle = 'word-break: break-word;';
                        }
                    } else {
                        if ($lastNameLen <= 8) {
                            $lastNameFontSize = '36px';
                        } elseif ($lastNameLen <= 12) {
                            $lastNameFontSize = '28px';
                        } elseif ($lastNameLen <= 16) {
                            $lastNameFontSize = '23px';
                        } elseif ($lastNameLen <= 20) {
                            $lastNameFontSize = '18px';
                        } elseif ($lastNameLen <= 24) {
                            $lastNameFontSize = '14px';
                        } elseif ($lastNameLen <= 28) {
                            $lastNameFontSize = '12px';
                        } else {
                            $lastNameFontSize = '10.5px';
                        }
                    }

                    $displayFirstName = trim($firstName . ' ' . $middleInitial);
                    $firstNameLen = strlen($displayFirstName);
                    if ($student->id_first_name_font_size) {
                        $firstNameFontSize = $student->id_first_name_font_size . 'px';
                    } else {
                        $firstNameFontSize = $firstNameLen > 25 ? '14px' : ($firstNameLen > 18 ? '16px' : '18px');
                    }

                    $gradeFontSize = $student->id_grade_font_size ? ($student->id_grade_font_size . 'px') : '31px';
                    $idFontSize = $student->id_num_font_size ? ($student->id_num_font_size . 'px') : '12.5px';
                @endphp

                <div class="student-card-item">
                    <div class="student-item-header">
                        <span>{{ $index + 1 }}. {{ strtoupper($fullName) }}</span>
                        <span style="font-size: 11px; font-weight: 700; color: #475569;">{{ strtoupper($sectionName) }} | {{ strtoupper($applicant?->student_type ?: 'NEW') }} | ID: {{ $studentNumber }} | LRN: {{ $lrn }}</span>
                    </div>

                    <table class="cards-table">
                        <tr>
                            <!-- Front Card -->
                            <td>
                                <div class="id-card-wrapper">
                                    <div class="id-card-scaler">
                                        <div class="id-card">
                                             <!-- Background Template Image (Top Layer) -->
                                             <img src="{{ asset('images/id/amis_frontid.png') }}?v={{ filemtime(public_path('images/id/amis_frontid.png')) }}" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 10; pointer-events: none;" alt="AMIS ID Template">
                                             
                                             @if(request('watermark') == 1)
                                                 <div class="id-watermark front-watermark">SAMPLE COPY</div>
                                             @endif
                                             
                                             <!-- Student Photo Container (Middle Layer) -->
                                             @if($photoUrl)
                                                 <div class="photo-clip">
                                                     <img src="{{ $photoUrl }}" alt="Student Photo">
                                                 </div>
                                             @else
                                                 <div class="photo-placeholder">Photo Missing</div>
                                             @endif

                                            <!-- Student ID Badge text -->
                                            <div class="student-id" style="font-size: var(--id-font-size, {{ $idFontSize }});">{{ $studentNumber }}</div>

                                            <!-- Last Name -->
                                            <div class="student-last-name">
                                                <h3 style="font-size: var(--last-name-font-size, {{ $lastNameFontSize }}); {{ $lastNameStyle }}">{{ $lastName }}</h3>
                                            </div>

                                            <!-- First Name -->
                                            <div class="student-first-name">
                                                <h4 style="font-size: var(--first-name-font-size, {{ $firstNameFontSize }});">{{ $displayFirstName }}</h4>
                                            </div>

                                            <!-- Grade Level -->
                                            <div class="student-grade">
                                                <span style="color: {{ $getGradeColor($displayGrade) }}; font-size: var(--grade-font-size, {{ $gradeFontSize }});">{{ strtoupper($displayGrade) }}</span>
                                            </div>

                                            <!-- LRN (Vertical) -->
                                            @if($applicant?->lrn && !in_array(strtoupper($applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
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
                            </td>

                            <!-- Back Card -->
                            <td>
                                <div class="id-card-wrapper">
                                    <div class="id-card-scaler">
                                        <div class="id-card">
                                            <!-- Background Template Image -->
                                            <img src="{{ asset('images/id/amis_backid.png') }}?v=1" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; pointer-events: none;" alt="AMIS ID Template Back">

                                            @if(request('watermark') == 1)
                                                <div class="id-watermark back-watermark">SAMPLE COPY</div>
                                            @endif

                                            <!-- Emergency Details List -->
                                            @php
                                                $parentNameLen = strlen($emergencyName);
                                                $parentNameFontSize = $parentNameLen > 24 ? '14px' : ($parentNameLen > 18 ? '16px' : '19px');
                                                
                                                $addressLen = strlen($homeAddress);
                                                $addressFontSize = $addressLen > 60 ? '12px' : ($addressLen > 40 ? '13px' : '14px');
                                            @endphp
                                            <div class="emergency-info">
                                                <!-- Contact Name -->
                                                <div class="emerg-row">
                                                    <span class="emerg-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg>
                                                    </span>
                                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: {{ $parentNameFontSize }}; font-weight: 900; text-transform: uppercase; color: #0f172a; line-height: 1.1;">
                                                        {{ $emergencyName }}
                                                    </div>
                                                </div>

                                                <!-- Relationship -->
                                                <div class="emerg-row">
                                                    <span class="emerg-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                                                    </span>
                                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1;">
                                                        {{ $relationship }}
                                                    </div>
                                                </div>

                                                <!-- Phone -->
                                                <div class="emerg-row">
                                                    <span class="emerg-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l.589 2.356a1.75 1.75 0 0 1-.607 1.89l-1.077.808a12.983 12.983 0 0 0 5.753 5.753l.808-1.077a1.75 1.75 0 0 1 1.89-.607l2.356.589c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" /></svg>
                                                    </span>
                                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: #1e293b; line-height: 1;">
                                                        {{ $emergencyPhone }}
                                                    </div>
                                                </div>

                                                <!-- Address -->
                                                <div class="emerg-row">
                                                    <span class="emerg-icon" style="margin-top: 2.5px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 3.58-2.977c2.2-2.384 4.19-5.462 4.19-8.923 0-4.82-3.855-8.5-8.5-8.5-8.5 0-8.5 3.68-8.5 8.5c0 3.461 1.99 6.54 4.19 8.923a16.975 16.975 0 0 0 3.58 2.977Zm3.71-12.851a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" clip-rule="evenodd" /></svg>
                                                    </span>
                                                    <div class="emerg-text" style="font-family: 'Outfit', sans-serif; font-size: {{ $addressFontSize }}; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1.25;">
                                                        {{ $homeAddress }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Secure Director Signature QR -->
                                            @if(in_array((string)$student->student_number, ['260253', '260254', '260158', '260895', '260894', '260893']))
                                                @php
                                                    $signatureQrUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/signature') . '&dark=000000&light=ffffff&margin=1&format=png&size=200';
                                                @endphp
                                                <div class="back-signature-qr">
                                                    <img src="{{ $signatureQrUrl }}" alt="Signature QR">
                                                </div>
                                            @else
                                                <div class="secure-signature-placeholder" style="position: absolute; left: 85px; top: 432px; width: 170px; text-align: center; z-index: 25; pointer-events: none;">
                                                    <span style="font-family: 'Outfit', sans-serif; font-size: 8px; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: 0.1em; border: 1.5px dashed #cbd5e1; padding: 3px 6px; border-radius: 6px; background: rgba(255, 255, 255, 0.85); display: inline-block; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Secure Signature Coming Soon</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        @empty
            <div style="text-align: center; padding: 40px; color: #64748b; font-weight: bold;">
                No students enrolled in this grade level.
            </div>
        @endforelse
    </div>

    <!-- Floating Editor Panel (hidden during print) -->
    <div id="print-editor-panel" style="display: none; position: fixed; right: 24px; top: 70px; width: 260px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 16px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); z-index: 99999; font-family: 'Outfit', sans-serif;" class="no-print">
        <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
            <h4 style="margin: 0; font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; display: flex; align-items: center; gap: 6px;">
                ✏️ ID Font Editor
            </h4>
            <button onclick="toggleEditor()" style="border: 0; background: transparent; cursor: pointer; color: #94a3b8; font-weight: bold; font-size: 14px;">✕</button>
        </div>

        <!-- Last Name slider -->
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                <span>Last Name</span>
                <span id="lbl-last-name" style="font-weight: 800; color: #0f172a;">30px</span>
            </div>
            <input type="range" min="10" max="45" step="0.5" value="30" oninput="updateFontSize('--last-name-font-size', this.value, 'lbl-last-name')" style="width: 100%; cursor: pointer;">
        </div>

        <!-- First Name slider -->
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                <span>First Name</span>
                <span id="lbl-first-name" style="font-weight: 800; color: #0f172a;">15px</span>
            </div>
            <input type="range" min="8" max="25" step="0.5" value="15" oninput="updateFontSize('--first-name-font-size', this.value, 'lbl-first-name')" style="width: 100%; cursor: pointer;">
        </div>

        <!-- Grade Level slider -->
        <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                <span>Grade Level</span>
                <span id="lbl-grade" style="font-weight: 800; color: #0f172a;">31px</span>
            </div>
            <input type="range" min="12" max="35" step="0.5" value="31" oninput="updateFontSize('--grade-font-size', this.value, 'lbl-grade')" style="width: 100%; cursor: pointer;">
        </div>

        <!-- Student ID slider -->
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 4px;">
                <span>Student ID</span>
                <span id="lbl-id" style="font-weight: 800; color: #0f172a;">12.5px</span>
            </div>
            <input type="range" min="8" max="18" step="0.5" value="12.5" oninput="updateFontSize('--id-font-size', this.value, 'lbl-id')" style="width: 100%; cursor: pointer;">
        </div>

        <!-- Reset Button -->
        <button onclick="resetFontSizes()" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fafafa; font-size: 10px; font-weight: 800; color: #475569; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 4px;">
            <span>Reset Default</span>
        </button>
    </div>

    <script>
        function loadScript(src) {
            return new Promise((resolve, reject) => {
                if (window.html2canvas || document.querySelector(`script[src="${src}"]`)) {
                    resolve();
                    return;
                }
                const s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        async function optimizeAndPrint(btn) {
            // First adjust all name sizes to fit perfectly!
            adjustLastNameFontSizes();
            
            const originalHtml = btn.innerHTML;
            btn.disabled = true;

            const cards = Array.from(document.querySelectorAll('.id-card'));
            const total = cards.length;
            if (total === 0) {
                alert('No cards found to optimize.');
                btn.disabled = false;
                return;
            }

            btn.innerHTML = '⏳ Loading optimizer engine...';
            try {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js');
            } catch (err) {
                alert('Failed to load optimizer library: ' + err);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                return;
            }

            // Loop through each card sequentially to avoid massive memory usage spikes
            for (let i = 0; i < total; i++) {
                const card = cards[i];
                const wrapper = card.closest('.id-card-wrapper');
                if (!wrapper) continue;

                btn.innerHTML = `⏳ Rendering cards: ${i + 1}/${total} (${Math.round((i / total) * 100)}%)...`;

                try {
                    const canvas = await html2canvas(card, {
                        scale: 2.2, // 220 DPI (looks very crisp but keeps image size and memory low)
                        useCORS: true,
                        allowTaint: true,
                        backgroundColor: null,
                        logging: false
                    });

                    const dataUrl = canvas.toDataURL('image/png', 0.95);
                    
                    // Replace complex HTML layout with simple flat high-res image
                    wrapper.innerHTML = `<img src="${dataUrl}" class="optimized-print-img" style="width: 100%; height: 100%; object-fit: contain; display: block; border-radius: 6.5mm;">`;
                } catch (err) {
                    console.error('Error optimizing card ' + i + ':', err);
                }
            }

            btn.innerHTML = '🖨️ Opening print dialog...';
            // Wait for DOM to adjust
            await new Promise(resolve => setTimeout(resolve, 500));
            window.print();
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }

        async function smartPrint(btn) {
            const originalHtml = btn.innerHTML;
            btn.disabled = true;

            const allImgs = Array.from(document.querySelectorAll('img'));
            const notLoaded = allImgs.filter(img => !img.complete || img.naturalWidth === 0);

            if (notLoaded.length === 0) {
                window.print();
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                return;
            }

            btn.innerHTML = '⏳ Loading images (0/' + notLoaded.length + ')...';
            let loaded = 0;

            const timeout = ms => new Promise(resolve => setTimeout(resolve, ms));

            const imagePromises = notLoaded.map(img => {
                return new Promise(resolve => {
                    const done = () => {
                        loaded++;
                        btn.innerHTML = '⏳ Loading images (' + loaded + '/' + notLoaded.length + ')...';
                        resolve();
                    };
                    img.onload = done;
                    img.onerror = done;
                    if (img.complete) done();
                });
            });

            await Promise.race([
                Promise.all(imagePromises),
                timeout(15000)
            ]);

            btn.innerHTML = '🖨️ Opening print dialog...';
            await timeout(300);
            window.print();
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }

        async function printBySection(btn) {
            const sections = document.querySelectorAll('.section-group-header + div, .section-print-group');
            // Get all section groups by finding section-group-header elements
            const sectionHeaders = Array.from(document.querySelectorAll('.section-group-header'));
            
            if (sectionHeaders.length === 0) {
                // Fallback: just print all
                await smartPrint(document.getElementById('btn-print-pdf'));
                return;
            }

            const originalHtml = btn.innerHTML;
            btn.disabled = true;

            // Wrap each section group in a printable div if not already done
            // Find all student-card-item groups per section
            const allItems = document.querySelectorAll('.student-card-item');
            const totalSections = sectionHeaders.length;

            for (let i = 0; i < sectionHeaders.length; i++) {
                btn.innerHTML = `📄 Printing section ${i + 1}/${totalSections}...`;

                // Find items belonging to this section (between this header and the next)
                const currentHeader = sectionHeaders[i];
                const nextHeader = sectionHeaders[i + 1] || null;
                
                // Hide all section groups except current
                sectionHeaders.forEach((h, idx) => {
                    h.style.display = (idx === i) ? '' : 'none';
                });

                // Hide all student cards not in current section
                let inCurrentSection = false;
                allItems.forEach(item => {
                    const prevHeader = getPreviousHeader(item, sectionHeaders);
                    item.style.display = (prevHeader === currentHeader) ? '' : 'none';
                });

                // Wait a moment for DOM to update
                await new Promise(resolve => setTimeout(resolve, 200));
                window.print();
                // Wait for print dialog to close
                await new Promise(resolve => setTimeout(resolve, 800));
            }

            // Restore all
            sectionHeaders.forEach(h => h.style.display = '');
            allItems.forEach(item => item.style.display = '');

            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }

        function getPreviousHeader(element, headers) {
            let best = null;
            for (const h of headers) {
                if (h.compareDocumentPosition(element) & Node.DOCUMENT_POSITION_FOLLOWING) {
                    best = h;
                } else {
                    break;
                }
            }
            return best;
        }

        function toggleEditor() {
            const panel = document.getElementById('print-editor-panel');
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        }

        function adjustLastNameFontSizes() {
            // Target print list templates (which use .student-last-name h3 or container divs)
            const elements = document.querySelectorAll('.student-last-name h3, .student-last-name, .id-last-name-text, [style*="top: 352px"][style*="width: 310px"]');
            elements.forEach(el => {
                const textEl = el.querySelector('h3') || el;
                // If this is the container div, clientWidth is defined. Otherwise fallback to 310px.
                const container = el.classList.contains('student-last-name') ? el : el.closest('.student-last-name') || el;
                const maxW = 278; // 310px card width - 32px horizontal padding
                
                // Get current font size
                let fontSize = parseFloat(window.getComputedStyle(textEl).fontSize);
                if (isNaN(fontSize) || fontSize <= 0) return;

                // Reset inline font size style before shrinking to get clean scrollWidth
                textEl.style.fontSize = '';
                fontSize = parseFloat(window.getComputedStyle(textEl).fontSize);
                
                let limit = 100;
                // Shrink sequentially until scrollWidth fits within the maximum bounds
                while (textEl.scrollWidth > maxW && fontSize > 8 && limit > 0) {
                    fontSize -= 0.5;
                    textEl.style.setProperty('font-size', fontSize + 'px', 'important');
                    limit--;
                }
            });
        }

        // Run auto-shrink on window load
        window.addEventListener('load', () => {
            adjustLastNameFontSizes();
        });

        // Also hook into resize/orientation updates just in case
        window.addEventListener('resize', adjustLastNameFontSizes);

        function updateFontSize(cssVar, val, lblId) {
            document.documentElement.style.setProperty(cssVar, val + 'px');
            document.getElementById(lblId).innerText = val + 'px';
            // Re-run auto-shrink after slider adjustments
            setTimeout(adjustLastNameFontSizes, 50);
        }

        function resetFontSizes() {
            updateFontSize('--last-name-font-size', 30, 'lbl-last-name');
            updateFontSize('--first-name-font-size', 15, 'lbl-first-name');
            updateFontSize('--grade-font-size', 31, 'lbl-grade');
            updateFontSize('--id-font-size', 12.5, 'lbl-id');
            
            // Reset input ranges value
            const ranges = document.querySelectorAll('#print-editor-panel input[type="range"]');
            ranges[0].value = 30;
            ranges[1].value = 15;
            ranges[2].value = 31;
            ranges[3].value = 12.5;
            setTimeout(adjustLastNameFontSizes, 100);
        }

        function copyDocumentHtml() {
            const area = document.getElementById('roster-document-area');
            if (!area) return;

            const range = document.createRange();
            range.selectNode(area);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);

            try {
                document.execCommand('copy');
                alert('✅ Grade ID Roster copied! You can now paste (Ctrl+V) directly into Google Docs or Microsoft Word!');
            } catch (err) {
                alert('Copy failed: ' + err);
            }
            window.getSelection().removeAllRanges();
        }
    </script>
</body>
</html>
