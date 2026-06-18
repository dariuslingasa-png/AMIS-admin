<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student Records List</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { margin: 0; background: #fff; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.35; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; padding: 10mm 5mm; }
        .header { display: table; width: 100%; border-bottom: 2px solid #059669; padding-bottom: 14px; margin-bottom: 18px; }
        .brand-mark, .brand-text, .status { display: table-cell; vertical-align: middle; }
        .brand-mark { width: 54px; }
        .brand-logo { display: block; width: 48px; height: 48px; object-fit: contain; }
        h1 { margin: 0; font-size: 15px; font-weight: 900; letter-spacing: .02em; }
        .subtitle { margin-top: 2px; color: #059669; font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .status { width: 150px; text-align: right; }
        .badge { display: inline-block; border: 1px solid #a7f3d0; border-radius: 999px; background: #ecfdf5; color: #065f46; font-size: 8px; font-weight: 900; padding: 5px 9px; }
        table { border-collapse: collapse !important; width: 100% !important; font-family: Arial, sans-serif !important; border: none !important; margin-bottom: 2rem !important; }
        table th { background: #f8fafc !important; color: #1e293b !important; font-family: Arial, sans-serif !important; font-weight: bold !important; font-size: 10px !important; padding: 8px 10px !important; text-transform: uppercase !important; text-align: left; }
        table td { border: none !important; padding: 8px 10px !important; font-family: Arial, sans-serif !important; font-size: 9px !important; color: #334155 !important; background: transparent !important; }
        tr { page-break-inside: avoid !important; }
        .page-break-after { page-break-after: always !important; break-after: page !important; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-10 { margin-bottom: 2.5rem; }
        .text-slate-500 { color: #64748b; }
        .text-slate-800 { color: #1e293b; }
        .text-slate-900 { color: #0f172a; }
        .pb-1\.5 { padding-bottom: 0.375rem; }
        .uppercase { text-transform: uppercase; }
        .tracking-wider { letter-spacing: 0.05em; }
        .tracking-tight { letter-spacing: -0.025em; }
        .font-semibold { font-weight: 600; }
        .font-normal { font-weight: 400; }
        .mt-1 { margin-top: 0.25rem; }
        .toolbar { position: sticky; top: 0; display: flex; justify-content: flex-end; gap: 8px; padding: 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .toolbar button { border: 0; border-radius: 8px; background: #059669; color: #fff; cursor: pointer; font-weight: 800; padding: 9px 14px; }
        @media print {
            .toolbar { display: none; }
            .page { width: auto; min-height: auto; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="page">
        <!-- Print Header -->
        <header class="header" style="border-bottom: 2px solid #059669; padding-bottom: 10px; margin-bottom: 20px;">
            <div style="display: table; width: 100%; border-collapse: collapse;">
                <div style="display: table-row;">
                    <!-- Left: English Name -->
                    <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: left;">
                        <h1 style="font-family: Arial, sans-serif; font-weight: 900; font-size: 14px; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">
                            AL MUNAWWARA ISLAMIC SCHOOL
                        </h1>
                        <div style="margin-top: 2px; color: #64748b; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                            Official School Portal
                        </div>
                    </div>
                    <!-- Center: Logo -->
                    <div style="display: table-cell; vertical-align: middle; width: 20%; text-align: center;">
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="height: 54px; width: auto; display: inline-block;">
                    </div>
                    <!-- Right: Arabic Name -->
                    <div style="display: table-cell; vertical-align: middle; width: 40%; text-align: right; direction: rtl;">
                        <h1 style="font-family: 'Times New Roman', serif; font-weight: 900; font-size: 18px; margin: 0; color: #059669; letter-spacing: 0.03em;">
                            المدرسة المنورة الإسلامية
                        </h1>
                    </div>
                </div>
            </div>
            <!-- Address Centered Below Logo -->
            <div style="text-align: center; font-size: 9px; color: #475569; font-weight: 700; margin-top: 8px; font-family: Arial, sans-serif; text-transform: uppercase; letter-spacing: 0.03em;">
                Don Julian Rodriguez Avenue, Ma-a, Davao City, Philippines, 8000
            </div>
        </header>

        <div class="mb-6 text-center">
            <h1 class="uppercase tracking-tight text-slate-900 font-bold" style="font-family: Arial, sans-serif; font-size: 14px; margin: 0; letter-spacing: 0.05em;">
                OFFICIAL STUDENT LIST – SCHOOL YEAR 2026–2027
            </h1>
            <h2 class="uppercase tracking-wide text-slate-700 font-bold mt-1" style="font-family: Arial, sans-serif; font-size: 10px; margin: 4px 0 0 0; letter-spacing: 0.02em;">
                @if(request('grade'))
                    Grade Level: {{ request('grade') }}
                @else
                    All Grades
                @endif
                @if(request('type'))
                    | Type: {{ strtoupper(request('type')) }}
                @endif
                @if(request('mode'))
                    | Mode: {{ request('mode') }}
                @endif
            </h2>
            <div class="text-slate-500 font-normal" style="font-family: Arial, sans-serif; font-size: 9px; margin-top: 4px;">
                Total Filtered: {{ $students->count() }} Students | 
                Changed Password: {{ $students->whereNotNull('password_changed_at')->count() }} | 
                Temporary Password: {{ $students->whereNull('password_changed_at')->whereNotNull('ms_user_id')->count() }} | 
                No Microsoft Account: {{ $students->whereNull('ms_user_id')->count() }}
            </div>
        </div>

        <!-- Grouped by Grade Level -->
        @php
            $groupedStudents = $students->groupBy('grade_level')->sortBy(function ($group, $gradeName) use ($gradeOrder) {
                $pos = array_search($gradeName, $gradeOrder);
                return $pos === false ? 999 : $pos;
            });
        @endphp

        @foreach ($groupedStudents as $gradeName => $gradeStudents)
            @php
                $sortedGradeStudents = $gradeStudents->sort(function ($a, $b) {
                    $lmA = strtolower($a->applicant->learning_mode ?? 'face-to-face');
                    $lmB = strtolower($b->applicant->learning_mode ?? 'face-to-face');
                    
                    $weightA = 9;
                    if (str_contains($lmA, 'face') || str_contains($lmA, 'f2f')) {
                        $weightA = 1;
                    } elseif (str_contains($lmA, '1st')) {
                        $weightA = 2;
                    } elseif (str_contains($lmA, '2nd')) {
                        $weightA = 3;
                    }
                    
                    $weightB = 9;
                    if (str_contains($lmB, 'face') || str_contains($lmB, 'f2f')) {
                        $weightB = 1;
                    } elseif (str_contains($lmB, '1st')) {
                        $weightB = 2;
                    } elseif (str_contains($lmB, '2nd')) {
                        $weightB = 3;
                    }
                    
                    if ($weightA !== $weightB) {
                        return $weightA <=> $weightB;
                    }
                    
                    $nameA = html_entity_decode(trim(($a->applicant->last_name ?? '').', '.($a->applicant->first_name ?? '')), ENT_QUOTES, 'UTF-8');
                    $nameB = html_entity_decode(trim(($b->applicant->last_name ?? '').', '.($b->applicant->first_name ?? '')), ENT_QUOTES, 'UTF-8');
                    
                    return strcasecmp($nameA, $nameB);
                });

                $gradeChanged = $gradeStudents->whereNotNull('password_changed_at')->count();
                $gradeTemp = $gradeStudents->whereNull('password_changed_at')->whereNotNull('ms_user_id')->count();
                $gradeNoAcc = $gradeStudents->whereNull('ms_user_id')->count();
            @endphp
            <div class="grade-print-section mb-10 {{ !$loop->last ? 'page-break-after' : '' }}">
                <h2 class="text-sm font-bold text-slate-800 mb-3 pb-1.5 uppercase tracking-wider" style="font-family: Arial, sans-serif;">
                    {{ $gradeName }} 
                    <span class="text-slate-500 font-normal" style="font-size: 10px; text-transform: none;">
                        ({{ $gradeStudents->count() }} Students | Password Status: {{ $gradeChanged }} Changed, {{ $gradeTemp }} Temporary, {{ $gradeNoAcc }} No Account)
                    </span>
                </h2>
                <table class="w-full text-left text-sm print-table mb-6">
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">#</th>
                            <th style="width: 22%">Student</th>
                            <th style="width: 10%">AMIS ID</th>
                            <th style="width: 8%">Gender</th>
                            <th style="width: 8%">Type</th>
                            <th style="width: 15%">Mode</th>
                            <th style="width: 20%">AMIS School Email</th>
                            <th style="width: 12%">Password Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sortedGradeStudents as $student)
                            @php
                                $fullName = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                                $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                                $gender = strtolower((string) ($student->applicant->gender ?? ''));
                                $genderLabel = $gender === 'male' ? 'MALE' : ($gender === 'female' ? 'FEMALE' : 'NOT SET');
                                
                                $sType = strtoupper((string) ($student->applicant->student_type ?? 'NEW'));
                                $lMode = $student->applicant->learning_mode ?? 'Face-to-Face';
                                if (str_contains(strtolower($lMode), '1st')) {
                                    $lModeLabel = 'Flexible Online 1st Shift';
                                } elseif (str_contains(strtolower($lMode), '2nd')) {
                                    $lModeLabel = 'Flexible Online 2nd Shift';
                                } elseif (str_contains(strtolower($lMode), 'face') || str_contains(strtolower($lMode), 'f2f')) {
                                    $lModeLabel = 'Face-to-Face';
                                } else {
                                    $lModeLabel = $lMode;
                                }

                                if ($student->password_changed_at) {
                                    $pStatusLabel = 'CHANGED';
                                    $pStatusStyle = 'color: #059669; font-weight: bold;';
                                } elseif ($student->ms_user_id) {
                                    $pStatusLabel = 'TEMPORARY';
                                    $pStatusStyle = 'color: #b45309; font-weight: bold;';
                                } else {
                                    $pStatusLabel = 'NO ACCOUNT';
                                    $pStatusStyle = 'color: #64748b; font-weight: bold;';
                                }
                            @endphp
                            <tr>
                                <td style="text-align: center; font-weight: bold; color: #64748b;">{{ $loop->iteration }}</td>
                                <td class="font-bold text-slate-900">{{ $name }}</td>
                                <td class="font-semibold">{{ $student->student_number ?? '-' }}</td>
                                <td>{{ $genderLabel }}</td>
                                <td class="font-semibold text-slate-700">{{ $sType }}</td>
                                <td class="text-slate-600">{{ $lModeLabel }}</td>
                                <td>{{ $student->school_email ?? '-' }}</td>
                                <td style="{{ $pStatusStyle }}">{{ $pStatusLabel }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </main>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
