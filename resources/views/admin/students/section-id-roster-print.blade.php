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
                $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . base64_encode((int)$studentNumber + 987654)) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
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
                                            <img src="{{ $photoUrl }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div style="width: 100%; height: 100%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; color: #94a3b8;">NO PHOTO</div>
                                        @endif
                                    </div>
 
                                    <!-- Student ID -->
                                    <div class="absolute text-white font-black text-center uppercase" style="position: absolute; left: 0; top: 267px; width: 280px; z-index: 20; font-size: 10px;">{{ $studentNumber }}</div>
 
                                    <!-- Last Name -->
                                    <div class="absolute text-center font-black text-[#0f172a] uppercase flex items-center justify-center" style="position: absolute; left: 12px; top: 291px; width: 256px; height: 32px; z-index: 20; font-size: 22px;">{{ $lastName }}</div>
 
                                    <!-- First Name -->
                                    <div class="absolute text-center font-bold text-[#334155] uppercase flex items-center justify-center" style="position: absolute; left: 12px; top: 318px; width: 256px; height: 18px; z-index: 20; font-size: 13px;">{{ trim($firstName . ' ' . $middleInitial) }}</div>
 
                                    <!-- Grade Level -->
                                    <div class="absolute text-center font-black uppercase flex items-center justify-center" style="position: absolute; left: 12px; top: 341px; width: 256px; height: 24px; z-index: 20; font-size: 24px; color: #0284c7;">{{ $section->grade_level }}</div>
 
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
                                    <div style="position: absolute; left: 23px; top: 70px; width: 233px; z-index: 10; display: flex; flex-direction: column; gap: 5.5px;">
                                        <div style="font-size: 13px; font-weight: 900; text-transform: uppercase; color: #0f172a;">
                                            👤 {{ $applicant?->emergency_name ?: ($applicant?->father_first_name ? $applicant->father_first_name . ' ' . $applicant->father_last_name : 'PARENT / GUARDIAN') }}
                                        </div>
                                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569;">
                                            ❤️ {{ $applicant?->emergency_relationship ?: 'PARENT / GUARDIAN' }}
                                        </div>
                                        <div style="font-size: 12px; font-weight: 800; color: #1e293b;">
                                            📞 {{ $applicant?->emergency_phone ?: ($applicant?->parent_mobile ?: '+63 900 000 0000') }}
                                        </div>
                                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #475569;">
                                            📍 {{ $applicant?->home_address ?: 'DAVAO CITY, PHILIPPINES' }}
                                        </div>
                                    </div>
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
