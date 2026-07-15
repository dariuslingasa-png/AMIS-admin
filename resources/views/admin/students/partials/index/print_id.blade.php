<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student ID Cards</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap');
        
        @page {
            size: portrait;
            margin: 0;
        }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: 'Inter', sans-serif;
        }
        .toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        .toolbar button {
            border: 0;
            border-radius: 8px;
            background: #059669;
            color: #fff;
            cursor: pointer;
            font-weight: 800;
            padding: 9px 14px;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; }
        }
        
        .id-print-page {
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-after: always;
            page-break-inside: avoid;
            box-sizing: border-box;
            background: #ffffff;
        }
        .id-print-page:last-of-type {
            page-break-after: avoid !important;
        }
        
        .id-pair-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 10mm;
            page-break-inside: avoid;
        }
        
        .id-card-wrapper {
            width: 70.2mm;
            height: 111.28mm;
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
            background: transparent;
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
        }
        
        .student-photo {
            position: absolute;
            left: 81px;
            top: 114px;
            width: 178px;
            height: 172px;
            overflow: hidden;
            border-radius: 14px;
            z-index: 10;
            object-fit: cover;
        }
        
        .photo-placeholder {
            position: absolute;
            left: 81px;
            top: 114px;
            width: 178px;
            height: 172px;
            z-index: 10;
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
            z-index: 10;
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
            z-index: 10;
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
            z-index: 10;
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
            top: 414px;
            width: 310px;
            height: 30px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
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
            z-index: 10;
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
            z-index: 10;
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
            color: #047857; /* Emerald green to match theme */
            margin-top: 1.5px;
        }
        .emerg-text {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print ID Cards</button>
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
            
            $photoUrl = \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url);
            
            $homeAddress = implode(', ', array_filter([$applicant->home_street_address, $applicant->home_city, $applicant->home_state_province]));
            if (empty($homeAddress)) {
                $homeAddress = $applicant->home_address ?: '-';
            }
            
            $emergencyName = $applicant->emergency_name ?: '-';
            if (empty($emergencyName) || strtolower($emergencyName) === 'emergency contact') {
                $emergencyName = trim(($applicant->father_first_name ?? '') . ' ' . ($applicant->father_last_name ?? '')) ?: (trim(($applicant->mother_first_name ?? '') . ' ' . ($applicant->mother_last_name ?? '')) ?: 'Registrar Office');
            }
            
            $emergencyPhone = $applicant->emergency_phone ?: '-';
            if (empty($emergencyPhone)) {
                $emergencyPhone = $applicant->parent_mobile ?: ($applicant->mobile_number ?: '+63 900 000 0000');
            }

            // QR Code Verification URL
            $studentNumber = $student->student_number;
            $hash = base64_encode((int)$studentNumber + 987654);
            $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
            
            // Format ID for badge (e.g. 260123)
            $badgeStudentId = $studentNumber;

            $displayGrade = $student->grade_level;
        @endphp

        <!-- Print Page Container -->
        <div class="id-print-page">
            <div class="id-pair-container">
                <!-- Front Side -->
                <div class="id-card-wrapper">
                    <div class="id-card-scaler">
                        <div class="id-card">
                            <!-- Background Template Image -->
                            <img src="{{ asset('assets/amis-id-template.png') }}?v=3" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;" alt="AMIS ID Template">
                            
                            <!-- Student Photo -->
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" class="student-photo" style="z-index: 10;">
                            @else
                                <div class="photo-placeholder">Photo Missing</div>
                            @endif

                            <!-- Student ID Badge text -->
                            <div class="student-id">{{ $badgeStudentId }}</div>

                            <!-- Last Name -->
                            @php
                                $lastNameLen = strlen($lastName);
                                $lastNameFontSize = $lastNameLen > 20 ? '16px' : ($lastNameLen > 15 ? '19px' : ($lastNameLen > 10 ? '24px' : '32px'));
                            @endphp
                            <div class="student-last-name">
                                <h3 style="font-size: {{ $lastNameFontSize }};">{{ $lastName }}</h3>
                            </div>

                            <!-- First Name -->
                            @php
                                $displayFirstName = trim($firstName . ' ' . $middleInitial);
                                $firstNameLen = strlen($displayFirstName);
                                $firstNameFontSize = $firstNameLen > 25 ? '14px' : ($firstNameLen > 18 ? '16px' : '18px');
                            @endphp
                            <div class="student-first-name">
                                <h4 style="font-size: {{ $firstNameFontSize }};">{{ $displayFirstName }}</h4>
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

                <!-- Back Side -->
                <div class="id-card-wrapper">
                    <div class="id-card-scaler">
                        <div class="id-card">
                            <!-- Background Template Image -->
                            <img src="{{ asset('assets/amis-id-template-back.png') }}?v=3" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;" alt="AMIS ID Template Back">

                            <!-- Emergency Details List -->
                            @php
                                $relationship = trim($applicant->emergency_relationship ?? '');
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
                                        {{ $relationship ?: 'Emergency Contact' }}
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
