<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student ID Cards</title>
    <style>
        @page {
            size: 85.6mm 54mm;
            margin: 0;
        }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
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
            width: 85.6mm;
            height: 54mm;
            page-break-after: always;
            position: relative;
            box-sizing: border-box;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-page:last-of-type {
            page-break-after: avoid !important;
        }
        
        /* ID-1 Standard Plastic Card Size in CSS */
        .id-card {
            width: 85.6mm;
            height: 54mm;
            position: relative;
            overflow: hidden;
            background: #fff;
            text-align: left;
            font-family: Arial, sans-serif;
            box-shadow: none;
            border: none;
        }
        
        /* Front Card Graphic Design */
        .id-card-front {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 45%, #059669 75%, #022c22 100%);
            color: #fff;
        }
        .id-card-front::after {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 60%;
            height: 100%;
            background: radial-gradient(circle at bottom right, rgba(234, 179, 8, 0.22) 0%, transparent 70%);
            pointer-events: none;
            z-index: 1;
        }
        .id-card-header {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.06);
            border-bottom: 0.5px solid rgba(255, 255, 255, 0.1);
            height: 32px;
            position: relative;
            z-index: 2;
        }
        .id-card-logo {
            width: 22px;
            height: 22px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .id-card-school-name {
            font-size: 6.5px;
            font-weight: 900;
            letter-spacing: 0.2px;
            text-transform: uppercase;
            line-height: 1.1;
            color: #ffffff;
        }
        .id-card-school-sub {
            font-size: 4.5px;
            font-weight: 700;
            color: #eab308;
            text-transform: uppercase;
            line-height: 1;
            margin-top: 0.5px;
        }
        .id-card-body {
            display: flex;
            padding: 6px 8px;
            gap: 6px;
            height: calc(54mm - 44px);
            align-items: center;
            position: relative;
            z-index: 2;
        }
        .id-card-photo-box {
            width: 18mm;
            height: 23.4mm;
            border-radius: 3px;
            border: 1.2px solid #eab308;
            background: #022c22;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .id-card-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .id-card-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            gap: 2px;
        }
        .id-card-name {
            font-size: 11px;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-bottom: 0.5px solid rgba(234, 179, 8, 0.4);
            padding-bottom: 1.5px;
            margin-bottom: 1px;
        }
        .id-card-field {
            display: flex;
            font-size: 8.5px;
            line-height: 1.1;
        }
        .id-card-label {
            width: 46px;
            color: #a7f3d0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .id-card-value {
            flex: 1;
            font-weight: bold;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .id-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 8px;
            background: #ffffff;
            color: #064e3b;
            font-size: 7px;
            font-weight: 950;
            height: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 2;
            border-top: 0.5px solid rgba(6, 78, 59, 0.15);
        }
        
        /* Back Card Rules & Contacts */
        .id-card-back {
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .id-card-back-header {
            background: #064e3b;
            color: #fff;
            font-size: 6px;
            font-weight: bold;
            text-align: center;
            padding: 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            height: 12px;
        }
        .id-card-back-body {
            padding: 6px 8px;
            font-size: 5px;
            line-height: 1.3;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .id-card-back-title {
            font-weight: bold;
            color: #064e3b;
            margin-bottom: 2px;
            text-transform: uppercase;
            font-size: 5px;
        }
        .id-card-back-terms {
            color: #64748b;
            margin-bottom: 4px;
        }
        .id-card-back-contact-box {
            background: #f1f5f9;
            border: 0.5px solid #cbd5e1;
            border-radius: 4px;
            padding: 3px 5px;
        }
        .id-card-back-row {
            display: flex;
            margin-bottom: 1px;
        }
        .id-card-back-row:last-child {
            margin-bottom: 0;
        }
        .id-card-back-label {
            width: 78px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .id-card-back-value {
            flex: 1;
            font-weight: bold;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .id-card-back-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 4px 8px;
            border-top: 0.5px solid #e2e8f0;
            height: 18px;
            background: #ffffff;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print ID Cards</button>
    </div>

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
            
            $nameLength = mb_strlen($displayName);
            $nameFontSize = '11px';
            if ($nameLength > 25) {
                $nameFontSize = '8px';
            } elseif ($nameLength > 20) {
                $nameFontSize = '9.5px';
            } elseif ($nameLength > 16) {
                $nameFontSize = '10.5px';
            }
            
            $photoUrl = \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url);
            
            $homeAddress = implode(', ', array_filter([$applicant->home_street_address, $applicant->home_city, $applicant->home_state_province]));
            if (empty($homeAddress)) {
                $homeAddress = $applicant->home_address ?: '-';
            }
            
            $emergencyName = $applicant->emergency_name ?: '-';
            $emergencyPhone = $applicant->emergency_phone ?: '-';
            
            $sectionName = $student->studentSection->section->name ?? ($student->section ?: 'No Section');
            $advisorObj = $student->studentSection->section?->grade_advisor ?? null;
            $advisorName = $advisorObj ? html_entity_decode(trim($advisorObj->teacher_name), ENT_QUOTES, 'UTF-8') : 'N/A';
            if (empty($advisorName) || $advisorName === 'N/A') {
                $advisories = config('class_advisories') ?? [];
                $allAdvisories = array_merge($advisories['elementary'] ?? [], $advisories['high_school'] ?? []);
                $targetGrade = strtolower(trim($student->grade_level ?? ''));
                foreach ($allAdvisories as $adv) {
                    $advGradeLower = strtolower($adv['grade_level'] ?? '');
                    $advKeyLower = strtolower($adv['grade'] ?? '');
                    if ($targetGrade !== '' && (
                        str_contains($targetGrade, $advGradeLower) || 
                        str_contains($advGradeLower, $targetGrade) || 
                        $targetGrade === $advKeyLower
                    )) {
                        $advisorName = $adv['teacher'];
                        break;
                    }
                }
            }
            if (empty($advisorName)) {
                $advisorName = 'N/A';
            }

            $isOdl = false;
            $learningMode = strtolower($applicant->learning_mode ?? '');
            if (str_contains($learningMode, 'online') || str_contains($learningMode, 'odl') || str_contains($learningMode, 'distance')) {
                $isOdl = true;
            }
            $cardNotice = $isOdl 
                ? 'This card is non-transferable and remains property of Al Munawwara Islamic School. Must be presented for official transactions.'
                : 'This card is non-transferable and remains property of Al Munawwara Islamic School. Must be worn inside campus at all times.';
        @endphp
        
        <!-- Page 1: Front Side -->
        <div class="card-page">
            <div class="id-card id-card-front">
                <!-- Watermark Logo -->
                <img src="{{ asset('images/AMIS_Logo.png') }}" style="position: absolute; right: -15mm; top: 3mm; width: 48mm; height: 48mm; object-fit: contain; opacity: 0.08; pointer-events: none; z-index: 1;" alt="AMIS Watermark Logo">
                
                <!-- Gold Glowing Orbs -->
                <div style="position: absolute; top: -5mm; right: -5mm; width: 35mm; height: 35mm; border-radius: 50%; background: radial-gradient(circle, rgba(234, 179, 8, 0.12) 0%, transparent 70%); pointer-events: none; z-index: 1;"></div>
                <div style="position: absolute; bottom: -10mm; left: -10mm; width: 45mm; height: 45mm; border-radius: 50%; background: radial-gradient(circle, rgba(234, 179, 8, 0.1) 0%, transparent 70%); pointer-events: none; z-index: 1;"></div>
                
                <!-- Elegant Curved Waves -->
                <svg style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; opacity: 0.15;" viewBox="0 0 85.6 54">
                    <path d="M -10 54 L -10 40 C 15 35, 30 50, 50 42 C 68 34, 75 46, 95 36 L 95 54 Z" fill="url(#frontGoldGradient)" />
                    <path d="M -10 54 L -10 43 C 18 38, 32 46, 52 38 C 70 31, 78 41, 95 32 L 95 54 Z" fill="#022c22" opacity="0.6" />
                    <defs>
                        <linearGradient id="frontGoldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#eab308" />
                            <stop offset="50%" stop-color="#ca8a04" />
                            <stop offset="100%" stop-color="#fef08a" />
                        </linearGradient>
                    </defs>
                </svg>
                
                <!-- Subtle geometric pattern lines overlay -->
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: repeating-linear-gradient(30deg, transparent, transparent 4px, rgba(255, 255, 255, 0.015) 4px, rgba(255, 255, 255, 0.015) 8px); pointer-events: none; z-index: 1;"></div>

                <div class="id-card-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <img class="id-card-logo" src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                        <div>
                            <div class="id-card-school-name">Al Munawwara Islamic School</div>
                            <div class="id-card-school-sub">OFFICIAL AMIS STUDENT ID</div>
                        </div>
                    </div>
                    <div style="font-family: 'Times New Roman', serif; font-size: 7px; font-weight: bold; color: #ffffff; direction: rtl; text-align: right; margin-right: 4px; line-height: 1;">
                        المدرسة المنورة الإسلامية
                    </div>
                </div>
                
                <div class="id-card-body" style="display: flex; padding: 6px 8px; gap: 6px; height: calc(54mm - 44px); align-items: center; justify-content: space-between;">
                    <div class="id-card-photo-box" style="width: 18mm; height: 23.4mm; border-radius: 3px; border: 1.2px solid #eab308; background: #022c22; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Photo" class="id-card-photo">
                        @else
                            <span style="font-size: 8px; font-weight: bold; color: #ef4444; text-align: center; text-transform: uppercase; line-height: 1.2; padding: 2px;">MISSING PHOTO</span>
                        @endif
                    </div>
                    
                    <div class="id-card-details" style="flex: 1; display: flex; flex-direction: column; justify-content: center; min-width: 0; gap: 3px;">
                        <div class="id-card-name" title="{{ $fullName }}" style="font-size: {{ $nameFontSize }}; font-weight: 900; color: #ffffff; text-transform: uppercase; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-bottom: 0.5px solid rgba(234, 179, 8, 0.4); padding-bottom: 1.5px; margin-bottom: 1px;">{{ $displayName }}</div>
                        <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                            <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">ID No:</div>
                            <div class="id-card-value font-mono" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $student->student_number }}</div>
                        </div>
                        <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                            <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">LRN:</div>
                            <div class="id-card-value font-mono" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $applicant->lrn ?: 'N/A' }}</div>
                        </div>
                        <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                            <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">Grade:</div>
                            <div class="id-card-value" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $student->grade_level }}</div>
                        </div>
                        <div class="id-card-field" style="display: flex; font-size: 8.5px; line-height: 1.1;">
                            <div class="id-card-label" style="width: 46px; color: #a7f3d0; font-weight: bold; text-transform: uppercase; flex-shrink: 0;">Adviser:</div>
                            <div class="id-card-value" title="{{ $advisorName }}" style="flex: 1; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $advisorName }}</div>
                        </div>
                    </div>
                    
                    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; position: relative; padding: 2.5px; width: 13.5mm; height: 13.5mm; background: transparent;">
                        <!-- Corner brackets -->
                        <div style="position: absolute; top: 0; left: 0; width: 3.5mm; height: 3.5mm; border-top: 1.2px solid #ffffff; border-left: 1.2px solid #ffffff; border-top-left-radius: 2px;"></div>
                        <div style="position: absolute; top: 0; right: 0; width: 3.5mm; height: 3.5mm; border-top: 1.2px solid #ffffff; border-right: 1.2px solid #ffffff; border-top-right-radius: 2px;"></div>
                        <div style="position: absolute; bottom: 0; left: 0; width: 3.5mm; height: 3.5mm; border-bottom: 1.2px solid #ffffff; border-left: 1.2px solid #ffffff; border-bottom-left-radius: 2px;"></div>
                        <div style="position: absolute; bottom: 0; right: 0; width: 3.5mm; height: 3.5mm; border-bottom: 1.2px solid #ffffff; border-right: 1.2px solid #ffffff; border-bottom-right-radius: 2px;"></div>
                        
                        <!-- QR Code Image -->
                        <img src="https://quickchart.io/qr?text={{ urlencode('https://amis.edu.ph/v/' . $student->obfuscated_id) }}&dark=ffffff&light=0000&margin=1&format=svg" style="width: 100%; height: 100%; object-fit: contain; display: block;" alt="QR Code">
                    </div>
                </div>
                
                <div class="id-card-footer" style="background: #ffffff; color: #064e3b;">
                    <span>Student</span>
                    <span>S.Y. {{ $student->school_year }}</span>
                </div>
            </div>
        </div>
        
        <!-- Page 2: Back Side -->
        <div class="card-page">
            <div class="id-card id-card-back">
                <div class="id-card-back-header">
                    Important School Notice
                </div>
                
                <div class="id-card-back-body">
                    <div class="id-card-back-title">Rules & Regulations</div>
                    <div class="id-card-back-terms" style="margin-bottom: 2px;">
                        {{ $cardNotice }}
                    </div>
                    
                    <div class="id-card-back-contact-box" style="margin-bottom: 3px; padding: 3px 5px;">
                        <table style="width: 100%; border-collapse: collapse; border: none; table-layout: auto;">
                            <tr style="line-height: 1.25;">
                                <td style="width: 1%; white-space: nowrap; font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 6px 0.5px 0; vertical-align: top;">IN CASE OF EMERGENCY:</td>
                                <td style="font-weight: 900; color: #064e3b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 0; vertical-align: top;">{{ $emergencyName }}</td>
                            </tr>
                            <tr style="line-height: 1.25;">
                                <td style="width: 1%; white-space: nowrap; font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 6px 0.5px 0; vertical-align: top;">CONTACT NO:</td>
                                <td class="font-mono" style="font-weight: 900; color: #064e3b; font-size: 7.5px; padding: 0.5px 0; vertical-align: top; white-space: nowrap;">{{ $emergencyPhone }}</td>
                            </tr>
                            <tr style="line-height: 1.25;">
                                <td style="width: 1%; white-space: nowrap; font-weight: bold; color: #64748b; text-transform: uppercase; font-size: 7.5px; padding: 0.5px 6px 0.5px 0; vertical-align: top;">ADDRESS:</td>
                                <td style="white-space: normal; line-height: 1.15; font-size: 6.5px; text-transform: uppercase; padding: 0.5px 0; vertical-align: top; color: #1e293b; font-weight: bold;" title="{{ $homeAddress }}">{{ \Illuminate\Support\Str::upper($homeAddress) }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- School Details & Quote -->
                    <div style="border-top: 0.5px solid #cbd5e1; padding-top: 2.5px; text-align: center; line-height: 1.2;">
                        <div style="font-size: 4.5px; color: #475569; font-weight: bold;">
                            Don Julian Rodriguez Avenue, Ma-a, Davao City, Philippines, 8000
                        </div>
                        <div style="font-size: 4.5px; font-style: italic; font-weight: 800; color: #059669; margin-top: 1px;">
                            "Enabling Our Students To Learn in Fid Dunya Wal Akhira"
                        </div>
                        <div style="font-size: 5px; font-weight: bold; color: #064e3b; text-transform: uppercase; margin-top: 2px; letter-spacing: 0.3px;">
                            Valid until March 31, 2027
                        </div>
                        <div style="margin-top: 2.5px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5px;">
                            <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text={{ urlencode($student->student_number) }}&scale=2&height=6" style="height: 5.5mm; max-width: 45mm; object-fit: contain;" alt="Barcode">
                            <span style="font-size: 4px; font-family: monospace; font-weight: bold; color: #1e293b; letter-spacing: 0.5px;">{{ $student->student_number }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="id-card-back-footer" style="padding: 2px 8px; height: 20px; display: flex; justify-content: center; align-items: center; background: #ffffff;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 15px; width: auto; object-fit: contain;">
                        <div style="display: flex; flex-direction: column; line-height: 1.1; text-align: left;">
                            <span style="font-size: 6px; font-weight: 900; color: #064e3b; font-family: 'Times New Roman', serif; direction: rtl;">المدرسة المنورة الإسلامية</span>
                            <span style="font-size: 5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Al Munawwara Islamic School</span>
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
