<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Official Roster - {{ $section->grade_level }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 18mm 16mm;
        }
        .toolbar {
            position: sticky;
            top: 0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 10px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .toolbar button {
            border: 0;
            border-radius: 8px;
            background: #059669;
            color: #fff;
            cursor: pointer;
            font-weight: 800;
            padding: 9px 14px;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #059669;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand-mark,
        .brand-text,
        .status {
            display: table-cell;
            vertical-align: middle;
        }
        .brand-mark { width: 54px; }
        .brand-logo {
            display: block;
            width: 48px;
            height: 48px;
            object-fit: contain;
        }
        h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: .02em;
        }
        .subtitle {
            margin-top: 2px;
            color: #059669;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .status {
            width: 150px;
            text-align: right;
        }
        .badge {
            display: inline-block;
            border: 1px solid #a7f3d0;
            border-radius: 999px;
            background: #ecfdf5;
            color: #065f46;
            font-size: 8px;
            font-weight: 900;
            padding: 5px 9px;
        }
        h2 {
            margin: 0 0 3px;
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .muted { color: #64748b; }
        .meta {
            width: 100%;
            margin: 16px 0 22px;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .meta td {
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
        }
        .label {
            width: 20%;
            color: #64748b;
            font-size: 9px;
            font-weight: 800;
        }
        .value {
            width: 30%;
            color: #0f172a;
            font-weight: 900;
        }
        .roster {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
        }
        .roster th {
            background: #0f172a;
            color: #fff;
            padding: 9px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
        }
        .roster td {
            padding: 8px 9px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .roster tbody tr:nth-child(even) td { background: #f8fafc; }
        .number { width: 34px; text-align: center; color: #64748b; }
        .student-no { width: 110px; text-align: center; font-weight: 800; }
        .student-name { font-weight: 900; text-transform: uppercase; }
        .footer {
            display: table;
            width: 100%;
            margin-top: 38px;
            color: #64748b;
            font-size: 9px;
        }
        .cert,
        .sign {
            display: table-cell;
            vertical-align: bottom;
        }
        .sign {
            width: 240px;
            text-align: center;
        }
        .line {
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            color: #0f172a;
            font-weight: 900;
            text-transform: uppercase;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

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
            <div class="muted">Academic Year {{ $section->school_year ?? $section->students->first()?->student?->school_year ?? config('services.school.previous_year', '2025-2026') }}</div>
        </section>

        <table class="meta">
            <tr>
                <td class="label">Grade Level</td>
                <td class="value">{{ $section->grade_level }}</td>
                <td class="label">Section Name</td>
                <td class="value">{{ $section->official_name ?: ($section->name ?: 'To Be Assigned (TBA)') }}</td>
            </tr>
            <tr>
                <td class="label">Shift / Mode</td>
                <td class="value">{{ $section->shift ?: $section->learning_mode ?: 'F2F Column' }}</td>
                <td class="label">Total Enrolled</td>
                <td class="value">{{ $occupied }} / {{ $capacity }} ({{ $fillRate }}%)</td>
            </tr>
            <tr>
                <td class="label">Gender Allocation</td>
                <td class="value">
                    @if ($section->gender === 'male')
                        Boys Only
                    @elseif ($section->gender === 'female')
                        Girls Only
                    @else
                        Co-Ed
                    @endif
                </td>
                <td class="label">Remaining Seats</td>
                <td class="value">{{ $remaining }} open</td>
            </tr>
        </table>

        <table class="roster">
            <thead>
                <tr>
                    <th class="number">#</th>
                    <th class="student-no">Student No.</th>
                    <th>Student Name</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($section->students as $studentSection)
                    @php
                        $student = $studentSection->student;
                        $applicant = $student?->applicant;
                        $nameParts = array_filter([
                            $applicant?->first_name,
                            $applicant?->middle_name,
                        ], fn ($part) => filled($part));
                        $name = trim(($applicant?->last_name ?? '') . ', ' . implode(' ', $nameParts), ' ,') ?: 'N/A';
                    @endphp
                    <tr>
                        <td class="number">{{ $loop->iteration }}</td>
                        <td class="student-no">{{ $student?->student_number ?: 'N/A' }}</td>
                        <td class="student-name">{{ $name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center; padding:20px; color:#94a3b8;">No students officially enrolled.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="footer">
            <div class="cert">
                This roster is officially generated from the Al Munawwara School Administration database.<br>
                Date generated: {{ now()->format('F d, Y h:i A') }}
            </div>
            <div class="sign">
                <div class="line">AMIS Registrar Office</div>
                <div>School Year {{ $section->school_year ?? $section->students->first()?->student?->school_year ?? config('services.school.previous_year', '2025-2026') }}</div>
            </div>
        </footer>
    </main>

    @if (request()->boolean('print'))
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>
</html>
