<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Section ID Cards Roster - {{ $section->grade_level }} {{ $section->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f1f5f9;
            color: #0f172a;
            font-family: 'Outfit', Arial, sans-serif;
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
        .id-card-wrap {
            display: inline-block;
            transform-origin: top center;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
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
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div class="toolbar-title">
            📋 Section ID Cards Roster: {{ $section->grade_level }} - {{ $section->official_name ?: $section->name }}
        </div>
        <div class="toolbar-actions">
            <button type="button" class="btn-action btn-secondary" onclick="copyDocumentHtml()">
                📋 Copy for Google Docs / MS Word
            </button>
            <button type="button" class="btn-action" onclick="window.print()">
                🖨️ Print / Save as PDF
            </button>
        </div>
    </div>

    <div class="page-container" id="roster-document-area">
        <div class="section-header">
            <h1 class="section-title">{{ $section->grade_level }} - {{ $section->official_name ?: $section->name }}</h1>
            <div class="section-subtitle">Official Student ID Cards Roster Document • S.Y. {{ $section->school_year ?? config('services.school.previous_year', '2025-2026') }}</div>
        </div>

        @forelse($section->students as $index => $studentSection)
            @php
                $student = $studentSection->student;
                $applicant = $student?->applicant;
                if (!$student) continue;

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
                $photoUrl = $student->photo_url ?: ($applicant?->photo_url ?: '');
                $hash = base64_encode((int)$studentNumber + 987654);
                $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
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

                $lastNameLen = strlen($lastName);
                if ($lastNameLen <= 8) {
                    $lastNameFontSize = '30px';
                    $lastNameStyle = 'white-space: nowrap;';
                } elseif ($lastNameLen <= 12) {
                    $lastNameFontSize = '23px';
                    $lastNameStyle = 'white-space: nowrap;';
                } elseif ($lastNameLen <= 16) {
                    $lastNameFontSize = '19px';
                    $lastNameStyle = 'white-space: nowrap;';
                } elseif ($lastNameLen <= 20) {
                    $lastNameFontSize = '15px';
                    $lastNameStyle = 'white-space: nowrap;';
                } else {
                    $lastNameFontSize = '12.5px';
                    $lastNameStyle = 'word-break: break-word;';
                }
            @endphp

            <div class="student-card-item">
                <div class="student-item-header">
                    <span>{{ $index + 1 }}. {{ strtoupper($fullName) }}</span>
                    <span style="font-size: 11px; font-weight: 700; color: #475569;">ID: {{ $studentNumber }} | LRN: {{ $lrn }}</span>
                </div>

                <table class="cards-table">
                    <tr>
                        <!-- Front Card -->
                        <td>
                            <div class="id-card-wrap">
                                <div class="rounded-2xl overflow-hidden text-left" style="position: relative; width: 280px; height: 443px; background-color: #064e3b; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin: 0 auto;">
                                    <img src="{{ asset('images/id/amis_frontid.png') }}" class="absolute inset-0 w-full h-full object-cover" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0; width: 100%; height: 100%; z-index: 10; pointer-events: none;">
                                    
                                    <!-- Photo -->
                                    <div class="photo-clip absolute overflow-hidden" style="position: absolute; left: 57px; top: 124px; width: 166px; height: 162px; border-radius: 12px; z-index: 5; overflow: hidden;">
                                        @if($photoUrl)
                                            <img src="{{ $photoUrl }}" style="width: 100%; height: 100%; object-fit: cover; object-position: center center;">
                                        @else
                                            <div style="width: 100%; height: 100%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; color: #94a3b8; border: 1px dashed #cbd5e1;">NO PHOTO</div>
                                        @endif
                                    </div>
 
                                    <!-- Student ID -->
                                    <div class="absolute text-white font-black tracking-wide text-center uppercase" style="position: absolute; left: 0; top: 267px; width: 280px; height: 12px; z-index: 20; font-size: 10px;">{{ $studentNumber }}</div>
 
                                    <!-- Last Name -->
                                    <div class="absolute text-center font-black text-[#0f172a] uppercase tracking-tight flex flex-col justify-center items-center" style="position: absolute; left: 12px; top: 291px; width: 256px; height: 32px; z-index: 20; font-size: {{ $lastNameFontSize }}; {{ $lastNameStyle }} line-height: 1.1;">{{ $lastName }}</div>
 
                                    <!-- First Name -->
                                    @php
                                        $displayFirstName = trim($firstName . ' ' . $middleInitial);
                                        $displayFirstNameLen = strlen($displayFirstName);
                                        $displayFirstNameFontSize = $displayFirstNameLen > 25 ? '11.5px' : ($displayFirstNameLen > 18 ? '13px' : '15px');
                                    @endphp
                                    <div class="absolute text-center font-bold text-[#334155] uppercase leading-none flex flex-col justify-center items-center" style="position: absolute; left: 12px; top: 318px; width: 256px; height: 18px; z-index: 20; font-size: {{ $displayFirstNameFontSize }};">{{ $displayFirstName }}</div>
 
                                    <!-- Grade Level -->
                                    <div class="absolute text-center font-black uppercase tracking-wide flex flex-col justify-center items-center" style="position: absolute; left: 12px; top: 341px; width: 256px; height: 24px; z-index: 20; font-size: 25px; color: {{ $getGradeColor($displayGrade) }};">{{ $displayGrade }}</div>
 
                                    <!-- LRN (Vertical) -->
                                    @if($applicant?->lrn && !in_array(strtoupper($applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                        <div class="absolute font-bold text-[#1e293b] whitespace-nowrap" style="position: absolute; left: 197px; top: 323px; width: 140px; height: 18px; z-index: 20; font-size: 12.5px; transform: rotate(-90deg); transform-origin: center; display: flex; align-items: center; justify-content: flex-start; letter-spacing: 0.05em;">
                                            LRN: <span>{{ $applicant->lrn }}</span>
                                        </div>
                                    @endif

                                    <!-- QR Code -->
                                    <div class="absolute p-0.5 rounded bg-white" style="position: absolute; left: 111px; top: 377px; width: 58px; height: 58px; z-index: 20; overflow: hidden;">
                                        <img src="{{ $qrCodeUrl }}" style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>
                                </div>
                            </div>
                        </td>
 
                        <!-- Back Card -->
                        <td>
                            <div class="id-card-wrap">
                                <div class="rounded-2xl overflow-hidden text-left" style="position: relative; width: 280px; height: 443px; background-color: #064e3b; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin: 0 auto;">
                                    <img src="{{ asset('images/id/amis_backid.png') }}" class="absolute inset-0 w-full h-full object-cover" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none;">
 
                                    <!-- Details -->
                                    @php
                                        $parentNameLen = strlen($emergencyName);
                                        $parentNameFontSize = $parentNameLen > 24 ? '11.5px' : ($parentNameLen > 18 ? '13px' : '15.5px');
                                        
                                        $addressLen = strlen($homeAddress);
                                        $addressFontSize = $addressLen > 60 ? '10px' : ($addressLen > 40 ? '11px' : '12px');
                                    @endphp
                                    <div style="position: absolute; left: 23px; top: 70px; width: 233px; z-index: 10; display: flex; flex-direction: column; gap: 5.5px;">
                                        <!-- Contact Name -->
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <span style="flex-shrink: 0; width: 11.5px; height: 11.5px; color: #047857; margin-top: 1.5px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg>
                                            </span>
                                            <div style="text-align: left; font-family: 'Outfit', sans-serif; font-size: {{ $parentNameFontSize }}; font-weight: 900; text-transform: uppercase; color: #0f172a; line-height: 1.1;">
                                                {{ $emergencyName }}
                                            </div>
                                        </div>

                                        <!-- Relationship -->
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <span style="flex-shrink: 0; width: 11.5px; height: 11.5px; color: #047857; margin-top: 1.5px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                                            </span>
                                            <div style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 12.5px; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1;">
                                                {{ $relationship }}
                                            </div>
                                        </div>

                                        <!-- Phone -->
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <span style="flex-shrink: 0; width: 11.5px; height: 11.5px; color: #047857; margin-top: 1.5px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l.589 2.356a1.75 1.75 0 0 1-.607 1.89l-1.077.808a12.983 12.983 0 0 0 5.753 5.753l.808-1.077a1.75 1.75 0 0 1 1.89-.607l2.356.589c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" /></svg>
                                            </span>
                                            <div style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 12.5px; font-weight: 800; color: #1e293b; line-height: 1;">
                                                {{ $emergencyPhone }}
                                            </div>
                                        </div>

                                        <!-- Address -->
                                        <div style="display: flex; align-items: flex-start; gap: 8px;">
                                            <span style="flex-shrink: 0; width: 11.5px; height: 11.5px; color: #047857; margin-top: 2px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 3.58-2.977c2.2-2.384 4.19-5.462 4.19-8.923 0-4.82-3.855-8.5-8.5-8.5-8.5 0-8.5 3.68-8.5 8.5c0 3.461 1.99 6.54 4.19 8.923a16.975 16.975 0 0 0 3.58 2.977Zm3.71-12.851a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" clip-rule="evenodd" /></svg>
                                            </span>
                                            <div style="text-align: left; font-family: 'Outfit', sans-serif; font-size: {{ $addressFontSize }}; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1.25;">
                                                {{ $homeAddress }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Secure Director Signature QR -->
                                    @if(in_array((string)$student->student_number, ['260253', '260254', '260158', '260895', '260894', '260893']))
                                        <div style="position: absolute; left: 117.5px; top: 348px; width: 45px; height: 45px; z-index: 25; padding: 1.5px; border-radius: 2px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                            <img src="{{ $signatureRawUrl }}" alt="Signature QR" style="width: 100%; height: 100%; object-fit: contain;">
                                        </div>
                                    @else
                                        <div style="position: absolute; left: 70px; top: 342px; width: 140px; text-align: center; z-index: 25; pointer-events: none;">
                                            <span style="font-family: 'Outfit', sans-serif; font-size: 6.5px; font-weight: 900; text-transform: uppercase; color: #64748b; letter-spacing: 0.08em; border: 1px dashed #cbd5e1; padding: 2px 4px; border-radius: 4px; background: rgba(255, 255, 255, 0.9); display: inline-block; box-shadow: 0 1px 1px rgba(0,0,0,0.05);">Secure Signature Coming Soon</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @empty
            <div style="text-align: center; padding: 40px; color: #64748b; font-weight: bold;">
                No students enrolled in this section.
            </div>
        @endforelse
    </div>

    <script>
        function copyDocumentHtml() {
            const area = document.getElementById('roster-document-area');
            if (!area) return;

            const range = document.createRange();
            range.selectNode(area);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);

            try {
                document.execCommand('copy');
                alert('✅ Section ID Roster copied! You can now paste (Ctrl+V) directly into Google Docs or Microsoft Word!');
            } catch (err) {
                alert('Copy failed: ' + err);
            }
            window.getSelection().removeAllRanges();
        }
    </script>
</body>
</html>
