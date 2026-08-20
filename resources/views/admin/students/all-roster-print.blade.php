<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Official Grade Roster - All Grades</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { width: 210mm; min-height: 297mm; margin: 20px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border-radius: 8px; page-break-after: always; }
        .page:last-of-type { page-break-after: avoid; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; z-index: 50; }
        .toolbar button { border: 0; border-radius: 8px; background: #059669; color: #fff; cursor: pointer; font-weight: 800; padding: 9px 14px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #059669; padding-bottom: 14px; margin-bottom: 18px; }
        .brand-mark, .brand-text, .status { display: table-cell; vertical-align: middle; }
        .brand-mark { width: 54px; }
        .brand-logo { display: block; width: 48px; height: 48px; object-fit: contain; }
        h1 { margin: 0; font-size: 16px; font-weight: 900; letter-spacing: .02em; }
        .subtitle { margin-top: 2px; color: #059669; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .status { width: 150px; text-align: right; }
        .badge { display: inline-block; border: 1px solid #a7f3d0; border-radius: 999px; background: #ecfdf5; color: #065f46; font-size: 8px; font-weight: 900; padding: 5px 9px; }
        h2 { margin: 0 0 3px; font-size: 14px; font-weight: 900; text-transform: uppercase; }
        .muted { color: #64748b; }
        .meta { width: 100%; margin: 16px 0 22px; border-collapse: collapse; border: 1px solid #e2e8f0; background: #f8fafc; }
        .meta td { padding: 9px 10px; border: 1px solid #e2e8f0; }
        .label { width: 20%; color: #64748b; font-size: 9px; font-weight: 800; }
        .value { width: 30%; color: #0f172a; font-weight: 900; }
        .roster { width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1; }
        .roster th { background: #0f172a; color: #fff; padding: 9px; border: 1px solid #cbd5e1; font-size: 9px; text-align: left; text-transform: uppercase; }
        .roster td { padding: 8px 9px; border: 1px solid #e2e8f0; vertical-align: top; }
        .roster tbody tr:nth-child(even) td { background: #f8fafc; }
        .number { width: 34px; text-align: center; color: #64748b; }
        .student-no { width: 110px; text-align: center; font-weight: 800; }
        .student-name { font-weight: 900; text-transform: uppercase; }
        .footer { display: table; width: 100%; margin-top: 38px; color: #64748b; font-size: 9px; }
        .cert, .sign { display: table-cell; vertical-align: bottom; }
        .sign { width: 240px; text-align: center; }
        .line { border-top: 1px solid #94a3b8; padding-top: 6px; color: #0f172a; font-weight: 900; text-transform: uppercase; }
        @media print {
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .toolbar { display: none; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; border-radius: 0; }
            .roster, .roster th, .roster td { border: 1px solid #cbd5e1 !important; }
            .meta, .meta td { border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    @foreach ($gradesData as $grade => $students)
        <main class="page">
            <header class="header">
                <div class="brand-mark">
                    <img class="brand-logo" src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                </div>
                <div class="brand-text">
                    <div style="font-size: 15px; font-weight: bold; color: #059669; font-family: 'Times New Roman', Times, serif; margin-bottom: 2px;">مدرسة المنورة الإسلامية</div>
                    <h1 style="font-size: 16px; font-weight: 900; letter-spacing: .02em; margin: 0; color: #0f172a;">AL MUNAWWARA ISLAMIC SCHOOL</h1>
                    <div class="subtitle" style="margin-top: 4px; font-size: 9px; font-weight: 800; color: #059669; text-transform: uppercase;">ENABLING OUR STUDENTS TO LEARN FID DUNYA WAL AKHIRA</div>
                </div>
            </header>

            <section>
                <h2>Officially Enrolled Student List</h2>
                <div class="muted">Academic Year {{ $students->first()?->school_year ?? config('services.school.previous_year', '2025-2026') }}</div>
            </section>

            <table class="meta">
                <tr>
                    <td class="label">Grade Level</td>
                    <td class="value">{{ $grade }}</td>
                    <td class="label">Total Students</td>
                    <td class="value">{{ $students->count() }}</td>
                </tr>
                <tr>
                    <td class="label">With LRN</td>
                    <td class="value" style="color: #059669; font-weight: 900;">
                        {{ $students->filter(function($s) {
                            $lrn = strtoupper(trim($s->applicant?->lrn ?? ''));
                            return !empty($lrn) && $lrn !== 'NA' && $lrn !== 'N/A' && $lrn !== 'MISSING LRN' && $lrn !== 'NA - MISSING LRN';
                        })->count() }}
                    </td>
                    <td class="label" style="color: #ef4444; font-weight: 900;">Missing LRN</td>
                    <td class="value" style="color: #ef4444; font-weight: 900;">
                        {{ $students->filter(function($s) {
                            $lrn = strtoupper(trim($s->applicant?->lrn ?? ''));
                            return empty($lrn) || $lrn === 'NA' || $lrn === 'N/A' || $lrn === 'MISSING LRN' || $lrn === 'NA - MISSING LRN';
                        })->count() }}
                    </td>
                </tr>
            </table>

            <table class="roster">
                <thead>
                    <tr>
                        <th class="number">#</th>
                        <th class="student-no">LRN</th>
                        <th>Student Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        @php
                            $applicant = $student->applicant;
                            $lastName = html_entity_decode(strtoupper(trim($applicant?->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                            $firstName = html_entity_decode(strtoupper(trim($applicant?->first_name ?? '')), ENT_QUOTES, 'UTF-8');
                            $middleName = html_entity_decode(strtoupper(trim($applicant?->middle_name ?? '')), ENT_QUOTES, 'UTF-8');
                            $suffix = html_entity_decode(strtoupper(trim($applicant?->suffix ?? '')), ENT_QUOTES, 'UTF-8');
                            
                            $nameParts = array_filter([$firstName, $middleName, $suffix], fn ($part) => filled($part));
                            $name = trim($lastName . ', ' . implode(' ', $nameParts), ' ,') ?: 'N/A';
                            
                            $lrn = $applicant?->lrn;
                        @endphp
                        <tr>
                            <td class="number">{{ $loop->iteration }}</td>
                            <td class="student-no" style="font-weight: 800;">
                                @php
                                    $lrnVal = strtoupper(trim($lrn ?? ''));
                                    $isLrnMissing = empty($lrnVal) || $lrnVal === 'NA' || $lrnVal === 'N/A' || $lrnVal === 'MISSING LRN';
                                @endphp
                                @if (!$isLrnMissing)
                                    {{ $lrn }}
                                @else
                                    <span style="color: #ef4444; font-weight: 900;">MISSING LRN</span>
                                @endif
                            </td>
                            <td class="student-name">{{ $name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <footer class="footer">
                <div class="cert">
                    This roster is officially generated from the Al Munawwara School Administration database.<br>
                    Date generated: {{ now()->format('F d, Y h:i A') }}
                </div>
                <div class="sign">
                    <div class="line">AMIS Registrar Office</div>
                    <div>School Year {{ $students->first()?->school_year ?? config('services.school.previous_year', '2025-2026') }}</div>
                </div>
            </footer>
        </main>
    @endforeach

    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>
</html>
