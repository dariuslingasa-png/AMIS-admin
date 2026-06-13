@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
    $msSyncColor = ['enrolled' => 'green', 'failed' => 'red', 'pending' => 'yellow'];
    $msSyncLabel = ['enrolled' => 'Synced', 'failed' => 'Sync Failed', 'pending' => 'Pending Teams'];
    $sort = request('sort', 'name');
    $direction = request('direction', $sort === 'name' ? 'asc' : 'desc') === 'asc' ? 'asc' : 'desc';
    $sortUrl = fn ($key) => route('admin.students.index', array_merge(request()->except('page'), [
        'sort' => $key,
        'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
    ]));
    $sortIcon = fn ($key) => $sort !== $key ? 'arrow-up-down' : ($direction === 'asc' ? 'arrow-up' : 'arrow-down');
    $gradeTotals = collect($analytics['grades'] ?? [])->keyBy('grade_level');
    $genderAnalytics = $analytics['gender'] ?? ['male' => 0, 'female' => 0, 'not_set' => 0];
    $genderLabels = ['male' => 'Male', 'female' => 'Female', 'not_set' => 'Not Set'];
@endphp

@if ($isPrint)
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student Records List</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #fff;
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
            padding: 10mm 5mm;
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
            font-size: 15px;
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
        table {
            border-collapse: collapse !important;
            width: 100% !important;
            font-family: Arial, sans-serif !important;
            border: none !important;
            margin-bottom: 2rem !important;
        }
        table th {
            background: #f8fafc !important;
            color: #1e293b !important;
            font-family: Arial, sans-serif !important;
            font-weight: bold !important;
            font-size: 10px !important;
            padding: 8px 10px !important;
            text-transform: uppercase !important;
            text-align: left !important;
        }
        table td {
            border: none !important;
            padding: 8px 10px !important;
            font-family: Arial, sans-serif !important;
            font-size: 9px !important;
            color: #334155 !important;
            background: transparent !important;
        }
        tr {
            page-break-inside: avoid !important;
        }
        .page-break-after {
            page-break-after: always !important;
            break-after: page !important;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-10 { margin-bottom: 2.5rem; }
        .text-slate-500 { color: #64748b; }
        .text-slate-800 { color: #1e293b; }
        .text-slate-900 { color: #0f172a; }
        .text-xs { font-size: 0.75rem; }
        .text-sm { font-size: 0.875rem; }
        .pb-1\.5 { padding-bottom: 0.375rem; }
        .border-b { border-bottom: 1px solid #cbd5e1; }
        .border-slate-300 { border-color: #cbd5e1; }
        .uppercase { text-transform: uppercase; }
        .tracking-wider { letter-spacing: 0.05em; }
        .tracking-tight { letter-spacing: -0.025em; }
        .font-semibold { font-weight: 600; }
        .font-normal { font-weight: 400; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-3 { margin-top: 0.75rem; }
        .flex { display: flex; }
        .justify-center { justify-content: center; }
        .gap-6 { gap: 1.5rem; }
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
        @media print {
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
                Total Filtered: {{ $students->count() }} Students
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
            @endphp
            <div class="grade-print-section mb-10 {{ !$loop->last ? 'page-break-after' : '' }}">
                <h2 class="text-sm font-bold text-slate-800 mb-3 pb-1.5 uppercase tracking-wider" style="font-family: Arial, sans-serif;">
                    {{ $gradeName }} <span class="text-slate-500 font-normal">({{ $gradeStudents->count() }} Students)</span>
                </h2>
                <table class="w-full text-left text-sm print-table mb-6">
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">#</th>
                            <th style="width: 25%">Student</th>
                            <th style="width: 12%">AMIS ID</th>
                            <th style="width: 10%">Gender</th>
                            <th style="width: 10%">Type</th>
                            <th style="width: 20%">Mode</th>
                            <th style="width: 18%">AMIS School Email</th>
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
                            @endphp
                            <tr>
                                <td style="text-align: center; font-weight: bold; color: #64748b;">{{ $loop->iteration }}</td>
                                <td class="font-bold text-slate-900">{{ $name }}</td>
                                <td class="font-semibold">{{ $student->student_number ?? '-' }}</td>
                                <td>{{ $genderLabel }}</td>
                                <td class="font-semibold text-slate-700">{{ $sType }}</td>
                                <td class="text-slate-600">{{ $lModeLabel }}</td>
                                <td>{{ $student->school_email ?? '-' }}</td>
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
@else
<x-admin-layout
    title="Student Records"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Student Records', 'href' => null],
    ]"
>
    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Section Header -->
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Students Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Student Records</h1>
                <p class="mt-1 text-sm text-slate-500">View enrolled student accounts, credentials, and synchronized teams channels. <span class="font-semibold text-slate-700">({{ number_format($analytics['filtered_total'] ?? $students->total()) }} of {{ number_format($stats['total_students'] ?? 0) }} total)</span></p>
            </div>
            <div class="flex items-center gap-2 print:hidden">
                <form method="POST" action="{{ route('admin.ms-sync.sync-all-licenses') }}" onsubmit="return confirm('Sync and assign licenses for the currently filtered student list? This may take a few minutes.')" class="inline-block">
                    @csrf
                    @foreach (request()->only(['search', 'grade', 'type', 'gender', 'mode', 'ms_status']) as $key => $value)
                        @if (filled($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 text-sm font-bold text-violet-700 transition hover:bg-violet-100 cursor-pointer">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        {{ request()->hasAny(['search', 'grade', 'type', 'gender', 'mode', 'ms_status']) ? 'Sync Filtered Licenses' : 'Sync Pending Licenses' }}
                    </button>
                </form>
                <a href="{{ route('admin.students.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print List
                </a>
                <a href="{{ route('admin.students.dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Dashboard
                </a>
            </div>
        </div>

        <!-- Hidden Print Header -->
        <div class="hidden print:block mb-6 text-center border-b-2 border-slate-350 pb-4">
            <h1 class="uppercase tracking-tight text-slate-900 font-bold" style="font-family: Arial, sans-serif; font-size: 14px;">STUDENT RECORDS MASTERS LIST</h1>
            <h2 class="uppercase tracking-wide text-slate-700 font-bold mt-1" style="font-family: Arial, sans-serif; font-size: 11px;">
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
            <div class="mt-3 flex justify-center gap-6 text-slate-500 font-normal" style="font-family: Arial, sans-serif; font-size: 9px;">
                <span>Total Filtered: {{ $isPrint ? $students->count() : $students->total() }}</span>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Filter Bar Form -->
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3 print:hidden">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <label class="relative col-span-12 lg:col-span-3">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search name, ID, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
                <select name="grade" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All grades</option>
                    @foreach($gradeOrder as $g)
                        <option value="{{ $g }}" @selected(request('grade') === $g)>{{ $g }}</option>
                    @endforeach
                </select>
                <select name="type" class="{{ $inputClass }} col-span-6 lg:col-span-1 w-full" onchange="this.form.submit()">
                    <option value="">All types</option>
                    <option value="new" @selected(request('type') === 'new')>New Student</option>
                    <option value="old" @selected(request('type') === 'old')>Old Student</option>
                    <option value="transferee" @selected(request('type') === 'transferee')>Transferee</option>
                </select>
                <select name="gender" class="{{ $inputClass }} col-span-6 lg:col-span-1 w-full" onchange="this.form.submit()">
                    <option value="">All genders</option>
                    <option value="male" @selected(request('gender') === 'male')>Male</option>
                    <option value="female" @selected(request('gender') === 'female')>Female</option>
                    <option value="not_set" @selected(request('gender') === 'not_set')>Not Set</option>
                </select>
                <select name="mode" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All learning modes</option>
                    <option value="Face-to-Face" @selected(request('mode') === 'Face-to-Face')>Face-to-Face</option>
                    <option value="1st Shift" @selected(request('mode') === '1st Shift')>Flexible Online 1st Shift</option>
                    <option value="2nd Shift" @selected(request('mode') === '2nd Shift')>Flexible Online 2nd Shift</option>
                </select>
                <select name="ms_status" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All sync states</option>
                    <option value="no_license" @selected(request('ms_status') === 'no_license')>No License / Sync Failed</option>
                    <option value="enrolled" @selected(request('ms_status') === 'enrolled')>Synced (With License)</option>
                    <option value="failed" @selected(request('ms_status') === 'failed')>Sync Failed</option>
                    <option value="pending" @selected(request('ms_status') === 'pending')>Pending Teams</option>
                    <option value="no_account" @selected(request('ms_status') === 'no_account')>No Microsoft Account</option>
                </select>
                <button class="col-span-12 lg:col-span-1 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    <span class="lg:hidden">Filter</span>
                </button>
            </form>



            <!-- Table Wrapper -->
            <div class="overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('name') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Student
                                    <i data-lucide="{{ $sortIcon('name') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-36 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('student_id') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Student ID
                                    <i data-lucide="{{ $sortIcon('student_id') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-28 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('gender') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Gender
                                    <i data-lucide="{{ $sortIcon('gender') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-32 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('grade') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Grade
                                    <i data-lucide="{{ $sortIcon('grade') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-40 px-5 py-4 font-bold print:hidden">Section</th>
                            <th class="w-48 px-5 py-4 font-bold">School Email / Temp Pass</th>
                            <th class="w-40 px-5 py-4 font-bold print:hidden">MS Sync State</th>
                            <th class="w-36 px-5 py-4 text-right font-bold print:hidden">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($students as $student)
                             @php
                                $fullName = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                                $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                                $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url ?? null);
                                $msStatus = $student->studentSection->ms_status ?? 'pending';
                                $gender = strtolower((string) ($student->applicant->gender ?? ''));
                                $genderLabel = $gender === 'male' ? 'Male' : ($gender === 'female' ? 'Female' : 'Not Set');
                                $genderClass = $gender === 'male' ? 'bg-blue-50 text-blue-700 ring-blue-100' : ($gender === 'female' ? 'bg-violet-50 text-violet-700 ring-violet-100' : 'bg-slate-50 text-slate-500 ring-slate-100');
                                
                                $studentType = $student->applicant ? $student->applicant->student_type : 'New';
                                $learningMode = $student->applicant ? $student->applicant->learning_mode : 'F2F';
                                $modeAbbr = 'F2F';
                                $lmLower = strtolower($learningMode);
                                if (str_contains($lmLower, 'online') || str_contains($lmLower, 'flexible') || str_contains($lmLower, 'odl') || str_contains($lmLower, 'shift')) {
                                    $modeAbbr = 'ODL';
                                }
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <!-- Student Photo & Name -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-smart-image
                                            :src="$photoUrl"
                                            :alt="$name"
                                            :fallback-initials="$initials ?: 'ST'"
                                            size="40"
                                            rounded="rounded-md"
                                            containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-extrabold print:hidden"
                                            :eager="false"
                                        />
                                        <div>
                                            <div class="font-extrabold text-slate-950">{{ $name }}</div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500">
                                                <span>SY {{ $student->school_year ?? '-' }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-violet-700 bg-violet-50 px-1.5 py-0.5 rounded">{{ strtoupper($studentType) }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded">{{ $modeAbbr }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Student Number -->
                                <td class="px-5 py-4 font-extrabold text-slate-600">
                                    {{ $student->student_number ?? '-' }}
                                </td>

                                <!-- Gender -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-extrabold ring-1 {{ $genderClass }}">{{ $genderLabel }}</span>
                                </td>

                                <!-- Grade -->
                                <td class="px-5 py-4 font-extrabold text-slate-700">
                                    {{ $student->grade_level ?? '-' }}
                                </td>

                                <!-- Section -->
                                <td class="px-5 py-4 font-medium text-slate-600 print:hidden">
                                    {{ $student->studentSection->section->official_name ?? $student->studentSection->section->name ?? 'No Section' }}
                                </td>

                                <!-- School Email / Temp Pass -->
                                <td class="px-5 py-4 text-xs">
                                    <div class="font-semibold text-slate-800 break-all select-all">{{ $student->school_email ?? '-' }}</div>
                                    <div class="mt-1 flex items-center gap-1 print:hidden">
                                        <span class="text-slate-400 font-extrabold uppercase tracking-wider text-[10px]">Pass:</span>
                                        @php
                                            $isHashed = str_starts_with($student->temp_password ?? '', '$');
                                        @endphp
                                        @if ($isHashed || blank($student->temp_password))
                                            <span class="text-slate-500 font-semibold text-[10px]">-</span>
                                        @else
                                            <span class="font-mono bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-[11px] text-slate-800 dark:text-slate-200 select-all font-semibold">{{ $student->temp_password }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- MS Sync status -->
                                <td class="px-5 py-4 print:hidden">
                                    <x-badge :color="$msSyncColor[$msStatus] ?? 'gray'">
                                        {{ $msSyncLabel[$msStatus] ?? 'Pending' }}
                                    </x-badge>
                                </td>

                                <!-- Action -->
                                 <td class="px-5 py-4 text-right print:hidden">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($student->ms_user_id && $msStatus !== 'enrolled')
                                            <form method="POST" action="{{ route('admin.ms-sync.student', $student) }}" class="inline-block">
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-violet-100 bg-violet-50 px-2.5 text-xs font-bold text-violet-700 transition hover:bg-violet-100 cursor-pointer" title="Sync Microsoft Account & License">
                                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                                    <span>Sync License</span>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center gap-2 rounded-md border border-emerald-100 bg-white px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                                            <i data-lucide="file-search" class="h-4 w-4"></i>
                                            Manage
                                        </a>
                                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                              onsubmit="return confirm('Delete {{ $student->student_number }} ({{ $student->school_email }})?\n\nThis will permanently delete the student from the portal and Microsoft 365. This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50" title="Delete Student">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No enrolled students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination links -->
            <div class="mt-5">{{ $students->links() }}</div>
        </div>
    </section>

    <!-- Print styling configuration -->
    <style>
        @media print {
            /* Hide all navigation, sidebars, dashboard links, buttons, and filters */
            #default-sidebar, 
            .admin-sidebar, 
            .admin-topbar, 
            topbar, 
            aside, 
            form, 
            nav, 
            .breadcrumbs, 
            .flash-messages, 
            footer, 
            .print\:hidden,
            .module-dashboard-link,
            .sidebar-section-container,
            .sidebar-profile-card,
            .admin-shell > a,
            .mb-5.grid,
            .mb-5,
            [data-lucide="arrow-left"] {
                display: none !important;
            }

            /* Reset container styling for standard page layout */
            .admin-content, 
            .admin-shell, 
            body, 
            main, 
            .mx-auto,
            section,
            .bg-white {
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                min-width: auto !important;
                width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                font-family: Arial, sans-serif !important;
            }

            .admin-content {
                margin-left: 0 !important;
            }

            /* Make the hidden print block visible */
            .print\:block {
                display: block !important;
            }

            /* Style the table to be simple plain raw text */
            table {
                border-collapse: collapse !important;
                width: 100% !important;
                font-family: Arial, sans-serif !important;
                border: none !important;
                margin-bottom: 2rem !important;
            }

            table th {
                position: static !important;
                background: #f8fafc !important;
                border-bottom: 1.5px solid #cbd5e1 !important;
                color: #1e293b !important;
                font-family: Arial, sans-serif !important;
                font-weight: bold !important;
                font-size: 10px !important;
                padding: 8px 10px !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            table td {
                border: none !important;
                border-bottom: 1px solid #e2e8f0 !important;
                padding: 8px 10px !important;
                font-family: Arial, sans-serif !important;
                font-size: 9px !important;
                color: #334155 !important;
                background: transparent !important;
            }

            /* Remove icons and styling from inside table td */
            table td svg,
            table td i,
            table td [data-lucide],
            table td .inline-flex,
            table td span {
                display: inline !important;
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                color: #334155 !important;
                font-size: 9px !important;
                font-weight: normal !important;
            }

            /* Hide sort icons in table headers during print */
            table th svg,
            table th i,
            table th [data-lucide] {
                display: none !important;
            }

            /* Ensure print:hidden takes precedence even inside table cells */
            .print\:hidden,
            table td .print\:hidden,
            table td span.print\:hidden,
            table td div.print\:hidden,
            table td x-smart-image.print\:hidden {
                display: none !important;
            }

            /* Hide student photos during print */
            table td img,
            table td .flex-shrink-0,
            table td x-smart-image {
                display: none !important;
            }

            /* Page break prevention rules for clean printing */
            tr {
                page-break-inside: avoid !important;
            }

            .page-break-after {
                page-break-after: always !important;
                break-after: page !important;
            }
        }
    </style>

    @if($isPrint)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        </script>
    @endif

    <!-- Sync Loading Modal -->
    <div id="sync-loading-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300">
        <div class="relative w-full max-w-md scale-95 transform rounded-2xl border border-slate-200/80 bg-white p-8 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 text-center">
            <!-- Spinner -->
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-violet-50 dark:bg-violet-950/30">
                <svg class="h-8 w-8 animate-spin text-violet-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <!-- Text -->
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Syncing Microsoft Licenses</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">We are updating status and assigning licenses to all student accounts. This may take 1-3 minutes. Please do not close or refresh this page.</p>
            
            <!-- Progress bar simulation (subtle animation) -->
            <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-violet-600 animate-[loading-bar_2s_infinite_ease-in-out]" style="width: 30%"></div>
            </div>
        </div>
    </div>

    <style>
    @keyframes loading-bar {
        0% { transform: translateX(-100%); width: 30%; }
        50% { width: 60%; }
        100% { transform: translateX(350%); width: 30%; }
    }
    </style>

    <script>
    document.querySelectorAll('form').forEach(form => {
        if (form.action.includes('ms-sync/sync-all-licenses') || form.action.includes('ms-sync/students')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent standard page navigation immediately
                
                const modal = document.getElementById('sync-loading-modal');
                const modalTitle = modal.querySelector('h3');
                const modalText = modal.querySelector('p');
                
                if (form.action.includes('ms-sync/students')) {
                    modalTitle.textContent = "Syncing Student Account";
                    modalText.textContent = "Updating status, teams enrollment, and Microsoft license for this student. Please wait...";
                } else {
                    modalTitle.textContent = "Syncing Microsoft Licenses";
                    modalText.textContent = "We are updating Microsoft status and licenses for the current student filter. Please do not close or refresh this page.";
                }
                
                modal.classList.remove('hidden');
                
                // Submit via fetch to keep the page alive and avoid aborting active images/logo
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    // Navigate to the final redirected URL once sync is done
                    window.location.href = response.url || window.location.href;
                })
                .catch(err => {
                    console.error('Sync error:', err);
                    window.location.reload();
                });
            });
        }
    });
    </script>
</x-admin-layout>
@endif
