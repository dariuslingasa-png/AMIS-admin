@extends('student.layout', ['heading' => 'My Subjects'])

@section('content')
@php 
    $nowManila = \Carbon\Carbon::now('Asia/Manila');
    $todayName = $nowManila->format('l'); // 'Sunday', 'Monday', 'Tuesday', etc.
    
    // AMIS School Days: Sunday through Thursday
    $schoolDays = [
        'Sunday' => 'Sun',
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
    ];
    $isSchoolDay = in_array($todayName, array_keys($schoolDays));

    // Helper to check if a schedule string matches a day
    $matchesDay = function (?string $sched, string $dayName) use ($schoolDays): bool {
        if (empty($sched)) return false;
        $dayAbbrev = $schoolDays[$dayName] ?? substr($dayName, 0, 3);
        
        // If Sun-Thu or Sun–Thu, it matches any of the 5 school days
        if (str_contains($sched, 'Sun-Thu') || str_contains($sched, 'Sun–Thu')) {
            return in_array($dayName, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);
        }
        
        return str_contains($sched, $dayAbbrev);
    };

    // Filter classes scheduled for today
    $todaySubjects = $subjects->filter(function ($subj) use ($todayName, $matchesDay) {
        return $matchesDay($subj->schedule, $todayName);
    })->sortBy(function ($subj) {
        $sched = trim((string) $subj->schedule);
        if (empty($sched)) return PHP_INT_MAX;
        preg_match('/(\d{1,2}:\d{2}\s*(?:AM|PM))/i', $sched, $matches);
        if (!empty($matches[1])) {
            $time = strtotime(date('Y-m-d') . ' ' . $matches[1]);
            if ($time !== false) return $time;
        }
        return PHP_INT_MAX;
    });

    // Group subjects by day for Weekly Calendar
    $days = [
        'Sunday' => [],
        'Monday' => [],
        'Tuesday' => [],
        'Wednesday' => [],
        'Thursday' => [],
    ];
    $unscheduled = [];

    foreach ($subjects as $subj) {
        $matched = false;
        foreach (array_keys($days) as $day) {
            if ($matchesDay($subj->schedule, $day)) {
                $days[$day][] = $subj;
                $matched = true;
            }
        }
        if (!$matched) {
            $unscheduled[] = $subj;
        }
    }

    $subjectIcon = function (?string $subjectName): string {
        $subjectLower = mb_strtolower((string) $subjectName);
        if (str_contains($subjectLower, 'math')) { return 'binary'; }
        if (str_contains($subjectLower, 'science')) { return 'beaker'; }
        if (str_contains($subjectLower, 'english') || str_contains($subjectLower, 'reading') || str_contains($subjectLower, 'language')) { return 'book-open'; }
        if (str_contains($subjectLower, 'arabic') || str_contains($subjectLower, 'qur') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf')) { return 'book'; }
        if (str_contains($subjectLower, 'gmrc') || str_contains($subjectLower, 'values') || str_contains($subjectLower, 'esp')) { return 'heart-handshake'; }
        if (str_contains($subjectLower, 'makabansa') || str_contains($subjectLower, 'araling') || str_contains($subjectLower, 'ap')) { return 'globe'; }
        if (str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict') || str_contains($subjectLower, 'tle')) { return 'monitor'; }
        if (str_contains($subjectLower, 'pe') || str_contains($subjectLower, 'physical') || str_contains($subjectLower, 'mapeh')) { return 'activity'; }
        if (str_contains($subjectLower, 'art') || str_contains($subjectLower, 'music')) { return 'palette'; }
        return 'file-text';
    };

    $formatTeacherName = function ($name) {
        $name = trim((string) $name) ?: 'To Be Assigned';
        if ($name === 'To Be Assigned' || $name === '—') return $name;
        $lower = strtolower($name);
        if (str_starts_with($lower, 'teacher ') || str_starts_with($lower, 'alim ') || str_starts_with($lower, 'ustadz ')) {
            return ucwords($lower);
        }
        return 'Teacher ' . ucwords($lower);
    };
@endphp

<div class="space-y-6" x-data="{ currentTab: '{{ $todaySubjects->isNotEmpty() ? 'today' : 'weekly' }}' }">
    {{-- Header Panel --}}
    <div class="student-panel">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="student-status-pill">
                    <i data-lucide="book-open" class="h-3.5 w-3.5 mr-1"></i>
                    Official Academic Curriculum
                </span>
                <h2 class="text-2xl font-black text-gray-900 mt-2.5" style="margin: 10px 0 4px;">My Registered Subjects</h2>
                <p class="text-sm font-semibold text-gray-500">
                    {{ $section?->name ?? 'Section' }} ({{ $section?->grade_level ?? $student->grade_level }}) · 
                    {{ $section?->learning_mode ?? ($student?->applicant?->learning_mode ?? 'Flexible Online Learning') }}
                    @if($section?->shift) · {{ $section->shift }} @endif
                    · School Year {{ $student->school_year ?? '2026-2027' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="rounded-xl border border-gray-150 bg-gray-50 px-4 py-2.5 text-center shrink-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Total Subjects</p>
                    <p class="mt-0.5 text-xl font-black text-gray-950">{{ $subjects->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Official Subject Cards Grid --}}
    <div class="student-panel">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="student-status-pill">
                    <i data-lucide="layers" class="h-3.5 w-3.5 mr-1"></i>
                    Subject Directory & Assigned Faculty
                </span>
                <h2 class="text-xl font-black text-gray-900 mt-2" style="margin: 8px 0 4px;">Official Subject Loads</h2>
                <p class="text-sm font-semibold text-gray-500">Official teacher assignments and Sunday–Thursday timetables.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 mt-5">
            @forelse($subjects as $subject)
                @php
                    $icon = $subjectIcon($subject->subject_name);
                    $meetings = $subject->meetings ?? collect();
                    $materials = $subject->materials ?? collect();
                    $tName = $formatTeacherName($subject->teacher_name);
                    $tEmail = $subject->teacher_email;
                    $tPhoto = $subject->teacher_photo ? asset($subject->teacher_photo) : null;
                    $tInitials = collect(explode(' ', $subject->teacher_name))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');

                    // Grade Status for this subject
                    $subjectGrades = isset($grades) && isset($grades[$subject->id]) ? $grades[$subject->id] : collect();
                    $latestQuarterGrade = $subjectGrades->whereNotNull('quarter_grade')->last();
                @endphp
                <article class="student-subject-card" style="border: 1.5px solid #e2e8f0; border-radius: 18px; padding: 1.5rem; background: #ffffff; display: flex; flex-direction: column; justify-content: space-between; gap: 1.25rem; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#10b981'; this.style.boxShadow='0 8px 20px rgba(16,185,129,0.06)'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    <div>
                        {{-- Top Bar: Icon, Subject Name, ID Pill --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div style="width: 44px; height: 44px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669; flex-shrink: 0;">
                                    <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-black text-gray-900 text-lg" style="margin:0; line-height: 1.2;">{{ $subject->subject_name }}</h3>
                                    <span class="inline-block text-[11px] font-bold text-gray-500 mt-1">
                                        {{ $section?->grade_level ?? 'Academic Subject' }}
                                    </span>
                                </div>
                            </div>
                            <span class="student-subject-code-pill">SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>

                        {{-- Teacher Info Card --}}
                        <div style="margin-top: 1rem; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 14px; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem;">
                            @if($tPhoto)
                                <img src="{{ $tPhoto }}" alt="{{ $tName }}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid #10b981; flex-shrink: 0;">
                            @else
                                <div style="width: 38px; height: 38px; border-radius: 50%; background: #047857; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800; flex-shrink: 0;">
                                    {{ $tInitials ?: 'TR' }}
                                </div>
                            @endif
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 0.85rem; font-weight: 800; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $tName }}
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $tEmail ?: 'Assigned AMIS Faculty' }}
                                </div>
                            </div>
                        </div>

                        {{-- Metadata Pills: Timetable & Standing --}}
                        <div style="margin-top: 0.85rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            {{-- Schedule Badge --}}
                            <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 750; color: #0d9488; background: #f0fdfa; border: 1px solid #ccfbf1; padding: 0.3rem 0.65rem; border-radius: 8px;">
                                <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                                <span>{{ $subject->schedule ?: 'Schedule to be announced' }}</span>
                            </div>

                            {{-- Academic Standing / Approved Grade --}}
                            @if($latestQuarterGrade)
                                <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 800; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.3rem 0.65rem; border-radius: 8px;">
                                    <i data-lucide="award" class="h-3.5 w-3.5"></i>
                                    <span>Grade: {{ $latestQuarterGrade->quarter_grade }} ({{ $latestQuarterGrade->remarks }})</span>
                                </div>
                            @else
                                <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.3rem 0.65rem; border-radius: 8px;">
                                    <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                    <span>Quarter 1 Ongoing</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions: Teams Channel, Meetings & Materials Preview --}}
                    <div style="border-top: 1px solid #f1f5f9; padding-top: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                        <div class="flex items-center gap-2 text-xs font-semibold text-gray-500">
                            <span>{{ $meetings->count() }} meeting(s)</span>
                            <span>·</span>
                            <span>{{ $materials->count() }} material(s)</span>
                        </div>

                        <div>
                            @if($subject->ms_channel_id && $section?->ms_team_url)
                                <a href="{{ $section->ms_team_url }}" target="_blank" class="student-primary-btn" style="padding: 0.45rem 0.9rem; font-size: 0.78rem;">
                                    <i data-lucide="video" class="w-3.5 h-3.5 mr-1"></i>
                                    <span>Join MS Teams</span>
                                </a>
                            @else
                                <span class="student-status-pill bg-gray-50 text-gray-400 border border-gray-150" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;">
                                    <i data-lucide="clock" class="w-3 h-3 mr-1"></i> Class Active
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm font-semibold text-gray-400 col-span-2">
                    No registered subjects found for this section.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Tabs Switcher: Today's Classes & Weekly Timetable --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex w-max max-w-full gap-1 rounded-xl bg-gray-150 p-1">
            <button type="button" @click="currentTab = 'today'; $nextTick(() => refreshStudentIcons())" :class="currentTab === 'today' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-500 hover:text-gray-855'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-extrabold transition cursor-pointer">
                <i data-lucide="clock" class="h-4 w-4"></i>
                <span>Today's Classes ({{ $todayName }})</span>
            </button>
            <button type="button" @click="currentTab = 'weekly'; $nextTick(() => refreshStudentIcons())" :class="currentTab === 'weekly' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-500 hover:text-gray-855'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-extrabold transition cursor-pointer">
                <i data-lucide="calendar-range" class="h-4 w-4"></i>
                <span>Weekly Timetable (Sun–Thu)</span>
            </button>
        </div>
    </div>

    {{-- Tab Contents --}}
    <div>
        {{-- Today's Classes Tab --}}
        <div x-show="currentTab === 'today'" class="space-y-4">
            @if($todaySubjects->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($todaySubjects as $subject)
                        @php 
                            $icon = $subjectIcon($subject->subject_name);
                            $tName = $formatTeacherName($subject->teacher_name);
                        @endphp
                        <div class="relative overflow-hidden student-subject-card flex flex-col justify-between gap-4 p-5 rounded-2xl border border-gray-200 bg-white shadow-xs">
                            <div class="flex items-start gap-4">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border bg-emerald-50 text-emerald-700 border-emerald-100 shadow-xs">
                                    <i data-lucide="{{ $icon }}" class="h-6 w-6"></i>
                                </span>
                                <div class="space-y-1 overflow-hidden min-w-0 flex-1">
                                    <h3 class="font-extrabold text-gray-900 text-base truncate" style="margin:0;">
                                        {{ $subject->subject_name }}
                                    </h3>
                                    <p class="text-xs font-semibold text-gray-500">
                                        Faculty: <strong class="text-gray-700 font-bold">{{ $tName }}</strong>
                                    </p>
                                    <div class="inline-flex items-center gap-1.5 text-xs font-extrabold mt-1 text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-100">
                                        <i data-lucide="clock" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        <span>{{ $subject->schedule }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-[11px] font-bold text-gray-400">Class In Session Today</span>
                                @if($subject->ms_channel_id && $section?->ms_team_url)
                                    <a href="{{ $section->ms_team_url }}" target="_blank" class="student-primary-btn" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;">
                                        Join Channel
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="dash-empty border border-gray-150 rounded-xl bg-white p-8 text-center">
                    <i data-lucide="calendar" class="mx-auto mb-2 text-gray-400 h-8 w-8"></i>
                    @if(in_array($todayName, ['Friday', 'Saturday']))
                        <p class="text-gray-600 font-bold text-base mt-2">Happy Weekend! 🎉</p>
                        <p class="text-gray-400 font-medium text-xs mt-1">Official classes resume on Sunday.</p>
                    @else
                        <p class="text-gray-500 font-semibold mt-2">No classes scheduled for today ({{ $todayName }}).</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Weekly Calendar Tab (Sunday - Thursday) --}}
        <div x-show="currentTab === 'weekly'" class="space-y-6">
            <div class="grid grid-cols-1 gap-4">
                @foreach($days as $dayName => $dayClasses)
                    <div class="border border-gray-150 rounded-xl bg-white p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2.5">
                            <h3 class="text-sm font-extrabold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full {{ $dayName === $todayName ? 'bg-emerald-600 animate-pulse' : 'bg-gray-300' }}"></span>
                                {{ $dayName }}
                                @if($dayName === $todayName)
                                    <span class="ml-2 rounded-full bg-emerald-600 px-2 py-0.5 text-[9px] font-black uppercase text-white">Today</span>
                                @endif
                            </h3>
                            <span class="text-xs font-bold text-gray-400">{{ count($dayClasses) }} Subject(s)</span>
                        </div>
                        @if(count($dayClasses) > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach($dayClasses as $subject)
                                    @php 
                                        $icon = $subjectIcon($subject->subject_name); 
                                        $tName = $formatTeacherName($subject->teacher_name);
                                    @endphp
                                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/30 hover:bg-gray-50/60 transition flex flex-col justify-between gap-3">
                                        <div class="flex items-start gap-3 min-w-0">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                <i data-lucide="{{ $icon }}" class="h-4.5 w-4.5"></i>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <h4 class="text-xs font-extrabold text-gray-900 truncate" style="margin:0;">{{ $subject->subject_name }}</h4>
                                                <p class="text-[10px] text-gray-500 mt-1 truncate">Teacher: {{ $tName }}</p>
                                            </div>
                                        </div>
                                        <div class="pt-2 border-t border-gray-100 mt-1 flex items-center justify-between">
                                            <span class="text-[10px] font-black text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                                                {{ $subject->schedule }}
                                            </span>
                                            @if($subject->ms_channel_id && $section?->ms_team_url)
                                                <a href="{{ $section->ms_team_url }}" target="_blank" class="text-[10px] font-extrabold text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5">
                                                    Teams <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs font-semibold text-gray-400">No classes scheduled on {{ $dayName }}.</p>
                        @endif
                    </div>
                @endforeach

                @if(count($unscheduled) > 0)
                    <div class="border border-gray-150 rounded-xl bg-gray-50/50 p-5 shadow-sm space-y-4">
                        <div class="border-b border-gray-200 pb-2.5">
                            <h3 class="text-sm font-extrabold text-gray-500 uppercase tracking-wider">Unscheduled / Asynchronous Subjects</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($unscheduled as $subject)
                                @php $icon = $subjectIcon($subject->subject_name); @endphp
                                <div class="p-4 rounded-xl border border-gray-150 bg-white shadow-xs flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-500 border border-gray-150">
                                        <i data-lucide="{{ $icon }}" class="h-4.5 w-4.5"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-extrabold text-gray-950 truncate" style="margin:0;">{{ $subject->subject_name }}</h4>
                                        <p class="text-[10px] text-gray-400 mt-0.5">Teacher: {{ $formatTeacherName($subject->teacher_name) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
