<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS - Families with No Payment Proof</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
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
            width: 277mm; /* A4 landscape width */
            min-height: 190mm; /* A4 landscape height minus margins */
            margin: 0 auto;
            background: #fff;
            padding: 15mm 15mm;
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
            z-index: 100;
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
        h2 {
            margin: 0 0 3px;
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .muted { color: #64748b; }
        .roster {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            margin-top: 15px;
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
        .family-no { font-weight: 800; text-align: center; color: #475569; }
        .family-name { font-weight: 900; text-transform: uppercase; color: #0f172a; }
        .child-item { margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px dashed #e2e8f0; }
        .child-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: 0; }
        .child-name { font-weight: 700; text-transform: uppercase; }
        .badge {
            display: inline-block;
            font-size: 8px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            margin-left: 4px;
        }
        .badge-status {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .badge-type {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .badge-grade {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .family-status {
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9px;
        }
        .status-approved { color: #166534; }
        .status-rejected { color: #991b1b; }
        .status-review { color: #075985; }
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
            <h2>Families with No Payment Proof Registry</h2>
            <div class="muted">Academic Year {{ \App\Models\EnrollmentApplicant::whereNotNull('school_year')->latest()->value('school_year') ?? config('services.school.year', '2026-2027') }}</div>
        </section>

        <table class="roster">
            <thead>
                <tr>
                    <th class="family-no" style="width: 10%;">Family App #</th>
                    <th style="width: 25%;">Family Name / Parent Name</th>
                    <th style="width: 25%;">Contact Details</th>
                    <th style="width: 30%;">Children Details</th>
                    <th style="width: 10%; text-align: center;">Family Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $typeLabel = fn ($type) => match (\Illuminate\Support\Str::of((string) $type)->lower()->replace(['_', '-'], ' ')->squish()->toString()) {
                        'old', 'old student', 'returning', 'returnee', 'existing' => 'OLD STUDENT',
                        'transferee', 'transfer', 'transferee student' => 'TRANSFEREE STUDENT',
                        'new', 'new student' => 'NEW STUDENT',
                        default => 'NOT SET',
                    };
                    $statusLabels = [
                        'draft' => 'Draft',
                        'ready_for_submission' => 'Ready for Submission',
                        'pending' => 'Pending Approval',
                        'submitted' => 'Submitted',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ];
                @endphp
                @forelse ($families as $family)
                    @php
                        $representative = $family['representative'];
                        $appNo = str_pad($family['family_no'], 4, '0', STR_PAD_LEFT);
                        $statusClass = match ($family['overall_status']) {
                            'Approved' => 'status-approved',
                            'Rejected' => 'status-rejected',
                            default => 'status-review',
                        };
                    @endphp
                    <tr>
                        <td class="family-no">#{{ $appNo }}</td>
                        <td>
                            <div class="family-name">{{ $family['family_label'] }}</div>
                            <div style="font-size: 10px; color: #4b5563; margin-top: 2px;">Parent: {{ $family['parent_name'] }}</div>
                        </td>
                        <td>
                            <div><strong>Email:</strong> {{ $family['parent_email'] ?: 'N/A' }}</div>
                            <div style="margin-top: 2px;"><strong>Mobile:</strong> {{ $family['parent_mobile'] ?: 'N/A' }}</div>
                        </td>
                        <td>
                            @foreach ($family['children'] as $child)
                                @php
                                    $childName = \Illuminate\Support\Str::upper(html_entity_decode(implode(' ', array_filter([trim($child->first_name ?? ''), trim($child->middle_name ?? ''), trim($child->last_name ?? '')])), ENT_QUOTES, 'UTF-8') ?: 'STUDENT');
                                    $studentType = $typeLabel($child->student_type);
                                    $statusLabel = $statusLabels[$child->status] ?? \Illuminate\Support\Str::headline($child->status ?? 'under_review');
                                @endphp
                                <div class="child-item">
                                    <span class="child-name">{{ $childName }}</span>
                                    <span class="badge badge-type">{{ $studentType }}</span>
                                    <span class="badge badge-grade">{{ $child->grade_level }}</span>
                                    <span class="badge badge-status">{{ $statusLabel }}</span>
                                </div>
                            @endforeach
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="family-status {{ $statusClass }}">{{ $family['overall_status'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;">No families with no payment proof found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <footer class="footer">
            <div class="cert">
                This list is officially generated from the Al Munawwara School Administration database.<br>
                Date generated: {{ now()->format('F d, Y h:i A') }}
            </div>
            <div class="sign">
                <div class="line">AMIS Registrar Office</div>
                <div>School Year {{ \App\Models\EnrollmentApplicant::whereNotNull('school_year')->latest()->value('school_year') ?? config('services.school.year', '2026-2027') }}</div>
            </div>
        </footer>
    </main>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 250);
        });
    </script>
</body>
</html>
