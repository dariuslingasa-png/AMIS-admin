<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student ID Cards</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap');
        
        @page {
            size: 54mm 85.6mm;
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
        
        .card-page {
            width: 54mm;
            height: 85.6mm;
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
            background: #ffffff;
            overflow: hidden;
        }
        .card-page:last-of-type {
            page-break-after: avoid !important;
        }
        
        .id-card-scaler {
            width: 340px;
            height: 538px;
            position: absolute;
            top: 0;
            left: 0;
            transform: scale(0.6);
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
            top: 406px;
            width: 310px;
            height: 30px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            right: -15px;
            top: 405px;
            width: 22px;
            height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
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
        .parent-name {
            position: absolute;
            left: 15px;
            top: 85px;
            width: 310px;
            height: 28px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .parent-name h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
            line-height: 1.1;
        }
        
        .contact-number {
            position: absolute;
            left: 15px;
            top: 118px;
            width: 310px;
            height: 20px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 16px;
        }
        
        .contact-number h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1;
        }
        
        .address-box {
            position: absolute;
            left: 20px;
            top: 144px;
            width: 300px;
            height: 42px;
            z-index: 10;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 20px;
        }
        
        .address-box p {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin: 0;
            line-height: 1.25;
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

        <!-- Page 1: Front Side -->
        <div class="card-page">
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
                        $lastNameFontSize = $lastNameLen > 20 ? '16px' : ($lastNameLen > 15 ? '19px' : ($lastNameLen > 10 ? '23px' : '26px'));
                    @endphp
                    <div class="student-last-name">
                        <h3 style="font-size: {{ $lastNameFontSize }};">{{ $lastName }}</h3>
                    </div>

                    <!-- First Name -->
                    @php
                        $firstNameLen = strlen($firstName);
                        $firstNameFontSize = $firstNameLen > 25 ? '11px' : ($firstNameLen > 18 ? '13px' : '15px');
                    @endphp
                    <div class="student-first-name">
                        <h4 style="font-size: {{ $firstNameFontSize }};">{{ $firstName }}</h4>
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

        <!-- Page 2: Back Side -->
        <div class="card-page">
            <div class="id-card-scaler">
                <div class="id-card">
                    <!-- Background Template Image -->
                    <img src="{{ asset('assets/amis-id-template-back.png') }}?v=3" class="id-template" style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1;" alt="AMIS ID Template Back">

                    <!-- Parent Name -->
                    @php
                        $relationship = trim($applicant->emergency_relationship ?? '');
                        $displayNameText = $emergencyName;
                        if (!empty($relationship)) {
                            $displayNameText .= ' (' . $relationship . ')';
                        }
                        $parentNameLen = strlen($displayNameText);
                        $parentNameFontSize = $parentNameLen > 24 ? '15px' : ($parentNameLen > 18 ? '18px' : '22px');
                    @endphp
                    <div class="parent-name">
                        <h3 style="font-size: {{ $parentNameFontSize }};">{{ $displayNameText }}</h3>
                    </div>

                    <!-- Contact Number -->
                    <div class="contact-number">
                        <h4>{{ $emergencyPhone }}</h4>
                    </div>

                    <!-- Address -->
                    @php
                        $addressLen = strlen($homeAddress);
                        $addressFontSize = $addressLen > 60 ? '10.5px' : ($addressLen > 40 ? '12px' : '13.5px');
                    @endphp
                    <div class="address-box">
                        <p style="font-size: {{ $addressFontSize }};">{{ $homeAddress }}</p>
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
