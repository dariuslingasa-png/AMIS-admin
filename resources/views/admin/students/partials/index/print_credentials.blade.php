<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $credTitle = 'AMIS-Student-Credentials-Print';
        if (isset($students) && count($students) === 1) {
            $st = $students->first();
            $app = $st?->applicant;
            $ln = strtoupper(trim($app?->last_name ?? ''));
            $fn = strtoupper(trim($app?->first_name ?? ''));
            $gr = strtoupper(trim(str_replace(' ', '', $st?->grade_level ?? '')));
            if ($ln || $fn) {
                $credTitle = implode('-', array_filter([$ln, $fn, $gr ?: 'GRADE', 'CREDENTIALS']));
            }
        }
    @endphp
    <title>{{ $credTitle }}</title>
    <style>
        @page { size: A4; margin: 10mm 8mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #fff; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 10px; padding: 10px 15px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; z-index: 1000; }
        .toolbar button { border: 0; border-radius: 8px; color: #fff; cursor: pointer; font-weight: 800; padding: 9px 14px; font-size: 11px; display: inline-flex; align-items: center; gap: 6px; }
        .btn-green { background: #059669; }
        .btn-blue { background: #2563eb; }
        .btn-slate { background: #475569; }
        .page { width: 210mm; margin: 0 auto; background: #fff; padding: 5mm; }
        .slips-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; width: 100%; }
        .slip-card { border: 2px dashed #94a3b8; border-radius: 10px; padding: 18px; background: #ffffff; display: flex; flex-direction: column; justify-content: space-between; page-break-inside: avoid; break-inside: avoid; position: relative; min-height: 62mm; }
        .slip-header { display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 10px; }
        .slip-logo { width: 32px; height: 32px; object-fit: contain; }
        .slip-school-name { font-size: 10px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.02em; }
        .slip-school-sub { font-size: 7px; color: #059669; font-weight: 700; text-transform: uppercase; margin-top: 1px; }
        .slip-title { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; font-size: 8px; font-weight: 900; text-align: center; padding: 3px 0; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; }
        .slip-row { display: flex; margin-bottom: 6px; font-size: 10px; }
        .slip-label { width: 90px; color: #64748b; font-weight: 700; font-size: 9px; text-transform: uppercase; }
        .slip-value { color: #0f172a; font-weight: 700; }
        .slip-value.name { font-size: 11px; color: #0f172a; }
        .slip-password-box { background: #fffbeb; border: 1px solid #fef3c7; padding: 8px 10px; border-radius: 6px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; }
        .slip-password-label { font-size: 8px; font-weight: 800; color: #b45309; text-transform: uppercase; letter-spacing: 0.03em; }
        .slip-password-val { font-family: monospace; font-size: 13px; font-weight: bold; color: #1e293b; letter-spacing: 0.02em; }
        .slip-footer { margin-top: 12px; border-top: 1px solid #f1f5f9; padding-top: 8px; font-size: 8px; color: #64748b; text-align: center; line-height: 1.3; }
        .table-container { display: none; width: 100%; }
        .cred-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .cred-table th { background: #f1f5f9; color: #334155; font-weight: bold; text-transform: uppercase; font-size: 9px; padding: 8px 10px; border: 1px solid #cbd5e1; text-align: left; }
        .cred-table td { padding: 8px 10px; font-size: 10px; border: 1px solid #cbd5e1; color: #334155; }
        .cred-table tr:nth-child(even) { background: #f8fafc; }
        .font-mono { font-family: monospace; font-size: 11px; font-weight: bold; }
        @media print {
            .toolbar, .action-bar-container, .page-number-badge { display: none !important; }
            body { background: #fff; }
            .page { width: auto; margin: 0; padding: 0; }
        }
    </style>
</head>
<body class="layout-slips">
    <div class="toolbar" style="display: flex; flex-direction: column; align-items: stretch; gap: 10px; padding: 14px 20px; background: #ffffff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 1000; font-family: 'Inter', system-ui, -apple-system, sans-serif; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            @if(isset($students) && count($students) === 1)
                @php $singleStudent = $students->first(); @endphp
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #eff6ff; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: center; color: #2563eb; shrink-0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; line-height: 1.2;">
                            Microsoft Account Credentials Slip
                        </h3>
                        <p style="font-size: 0.75rem; font-weight: 600; color: #64748b; margin: 2px 0 0 0;">
                            Student: <strong style="color: #0f172a;">{{ $singleStudent->full_name }}</strong> • AMIS ID: <strong style="color: #059669;">#{{ $singleStudent->student_number }}</strong>
                        </p>
                    </div>
                </div>
            @else
                <h3 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a;">Student Credentials Printer</h3>
            @endif
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" class="btn-green" onclick="window.print()" style="font-family: inherit; border-radius: 10px;">Print / Save PDF</button>
                <button type="button" class="btn-blue" id="btn-show-slips" onclick="switchLayout('slips')" style="font-family: inherit; border-radius: 10px;">Credential Slips</button>
                <button type="button" class="btn-slate" id="btn-show-table" onclick="switchLayout('table')" style="font-family: inherit; border-radius: 10px;">Credential Table</button>
            </div>
        </div>

        @if(isset($students) && count($students) === 1)
            <!-- Below Navbar Switcher Buttons (SAME TAB NAVIGATION - target="_self") -->
            <div style="display: flex; align-items: center; gap: 6px; overflow-x: auto; padding-top: 8px; border-top: 1px solid #f1f5f9;">
                <!-- ID FORM -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_id' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-family: inherit; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h3v3H7z"/><path d="M14 7h3"/><path d="M14 11h3"/><path d="M7 14h10"/><path d="M7 17h10"/></svg>
                    <span>ID Form</span>
                </a>

                <!-- MICROSOFT ACCOUNT FORM (ACTIVE) -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_credentials' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; text-decoration: none; border: 1px solid #2563eb; background: #eff6ff; color: #1d4ed8; font-family: inherit; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                    <span>Microsoft Account Form</span>
                </a>

                <!-- ENROLLMENT FORM -->
                <a href="{{ route('admin.students.print-enrolment-form', $singleStudent) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-family: inherit; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    <span>Enrollment Form</span>
                </a>

                <!-- INFO FORM -->
                <a href="{{ route('admin.students.index', ['search' => $singleStudent->student_number, 'print_info' => 1]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-family: inherit; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Info Form</span>
                </a>

                <!-- GRADE FORM -->
                <a href="{{ route('admin.students.show', $singleStudent) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-family: inherit; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <span>Grade Form</span>
                </a>

                <!-- DOCUMENTS FORM -->
                <a href="{{ route('admin.students.download-docs-zip', ['student_id' => $singleStudent->id]) }}" target="_self" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; text-decoration: none; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-family: inherit; white-space: nowrap;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L8.6 3.3A2 2 0 0 0 6.9 2.5H4a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2z"/></svg>
                    <span>Documents Form</span>
                </a>
            </div>
        @endif
    </div>

    <main class="page">
        <!-- Slips Container -->
        <div id="slips-container" class="slips-grid">
            @foreach ($students as $student)
                @php
                    $fullName = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                    $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                @endphp
                <div class="slip-card">
                    <div>
                        <div class="slip-header">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="Logo" class="slip-logo">
                            <div>
                                <div class="slip-school-name">Al Munawwara Islamic School</div>
                                <div class="slip-school-sub">Official Student Portal</div>
                            </div>
                        </div>
                        <div class="slip-title">Student Login Credentials</div>
                        
                        <div class="slip-row">
                            <div class="slip-label">Student Name:</div>
                            <div class="slip-value name">{{ $name }}</div>
                        </div>
                        <div class="slip-row">
                            <div class="slip-label">Grade Level:</div>
                            <div class="slip-value">{{ $student->grade_level }}</div>
                        </div>
                        <div class="slip-row">
                            <div class="slip-label">AMIS ID:</div>
                            <div class="slip-value font-mono">{{ $student->student_number }}</div>
                        </div>
                        <div class="slip-row">
                            <div class="slip-label">Microsoft Email:</div>
                            <div class="slip-value font-mono">{{ $student->school_email ?? '-' }}</div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="slip-password-box">
                            @if ($student->password_changed_at)
                                <span class="slip-password-label" style="color: #059669;">Password Status:</span>
                                <span class="slip-password-val" style="color: #059669; font-size: 11px; text-transform: uppercase;">Changed by Student</span>
                            @else
                                <span class="slip-password-label">Temporary Password:</span>
                                <span class="slip-password-val">{{ $student->temp_password ?? 'N/A' }}</span>
                            @endif
                        </div>
                        <div class="slip-footer">
                            Go to <strong>Microsoft Login</strong> to sign in.<br>
                            Please change your temporary password immediately upon first login.
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Table Container -->
        <div id="table-container" class="table-container">
            <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #059669; padding-bottom: 10px;">
                <h2 style="margin: 0; text-transform: uppercase; font-size: 14px; letter-spacing: 0.05em;">AL MUNAWWARA ISLAMIC SCHOOL</h2>
                <div style="font-size: 9px; color: #059669; font-weight: bold; text-transform: uppercase; margin-top: 2px;">Student Portal Credentials Master List</div>
                <div style="font-size: 8px; color: #64748b; margin-top: 4px;">School Year 2026-2027 | Total: {{ $students->count() }} Students</div>
            </div>
            
            <table class="cred-table">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">#</th>
                        <th style="width: 35%;">Student Name</th>
                        <th style="width: 15%;">AMIS ID</th>
                        <th style="width: 25%;">Microsoft Email</th>
                        <th style="width: 20%;">Temporary Password</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $index => $student)
                        @php
                            $fullName = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                            $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                        @endphp
                        <tr>
                            <td style="text-align: center; font-weight: bold; color: #64748b;">{{ $index + 1 }}</td>
                            <td style="font-weight: bold; color: #0f172a;">{{ $name }}</td>
                            <td class="font-mono">{{ $student->student_number }}</td>
                            <td class="font-mono">{{ $student->school_email ?? '-' }}</td>
                            <td class="font-mono">
                                @if ($student->password_changed_at)
                                    <span style="color: #059669; font-weight: bold; text-transform: uppercase;">Changed</span>
                                @else
                                    <span style="color: #b45309;">{{ $student->temp_password ?? 'N/A' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function switchLayout(layout) {
            const body = document.body;
            const btnSlips = document.getElementById('btn-show-slips');
            const btnTable = document.getElementById('btn-show-table');
            
            if (layout === 'slips') {
                body.className = 'layout-slips';
                btnSlips.className = 'btn-blue';
                btnTable.className = 'btn-slate';
                document.getElementById('slips-container').style.display = 'grid';
                document.getElementById('table-container').style.display = 'none';
            } else {
                body.className = 'layout-table';
                btnSlips.className = 'btn-slate';
                btnTable.className = 'btn-blue';
                document.getElementById('slips-container').style.display = 'none';
                document.getElementById('table-container').style.display = 'block';
            }
        }
        
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
