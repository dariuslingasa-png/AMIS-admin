@extends('student.layout', ['heading' => 'My Subjects'])

@section('content')
@php 
    $todayName = now()->format('l'); // 'Monday', 'Tuesday', etc.
    
    // Filter classes scheduled for today
    $todaySubjects = $subjects->filter(function ($subj) use ($todayName) {
        $sched = trim((string) $subj->schedule);
        if (empty($sched)) {
            return false;
        }
        
        $parts = explode(' ', $sched);
        $dayPart = $parts[0] ?? '';
        
        $isM = str_contains($dayPart, 'M');
        $isW = str_contains($dayPart, 'W');
        $isF = str_contains($dayPart, 'F');
        $isTh = str_contains($dayPart, 'Th');
        $isT = str_contains(str_replace('Th', '', $dayPart), 'T');
        
        return match ($todayName) {
            'Monday' => $isM,
            'Tuesday' => $isT,
            'Wednesday' => $isW,
            'Thursday' => $isTh,
            'Friday' => $isF,
            default => false,
        };
    })->sortBy(function ($subj) {
        $sched = trim((string) $subj->schedule);
        if (empty($sched)) {
            return PHP_INT_MAX;
        }
        $parts = explode(' ', $sched);
        $timePart = trim(substr($sched, strlen($parts[0] ?? '')));
        $timeRange = explode('-', $timePart);
        $startTimeStr = trim($timeRange[0] ?? '');
        if (!empty($startTimeStr)) {
            $time = strtotime(date('Y-m-d') . ' ' . $startTimeStr);
            if ($time !== false) {
                return $time;
            }
        }
        return PHP_INT_MAX;
    });

    // Group subjects by day for Weekly Calendar
    $days = [
        'Monday' => [],
        'Tuesday' => [],
        'Wednesday' => [],
        'Thursday' => [],
        'Friday' => [],
    ];
    $unscheduled = [];

    foreach ($subjects as $subj) {
        $sched = trim((string) $subj->schedule);
        if (empty($sched)) {
            $unscheduled[] = $subj;
            continue;
        }

        $parts = explode(' ', $sched);
        $dayPart = $parts[0] ?? '';

        $isM = str_contains($dayPart, 'M');
        $isW = str_contains($dayPart, 'W');
        $isF = str_contains($dayPart, 'F');
        $isTh = str_contains($dayPart, 'Th');
        $isT = str_contains(str_replace('Th', '', $dayPart), 'T');

        $matched = false;
        foreach ([
            'Monday' => $isM,
            'Tuesday' => $isT,
            'Wednesday' => $isW,
            'Thursday' => $isTh,
            'Friday' => $isF,
        ] as $day => $isScheduled) {
            if ($isScheduled) {
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
        if (str_contains($subjectLower, 'english') || str_contains($subjectLower, 'reading')) { return 'book-open'; }
        if (str_contains($subjectLower, 'arabic') || str_contains($subjectLower, 'qur') || str_contains($subjectLower, 'islamic')) { return 'book'; }
        if (str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict')) { return 'monitor'; }
        if (str_contains($subjectLower, 'pe') || str_contains($subjectLower, 'physical')) { return 'activity'; }
        if (str_contains($subjectLower, 'art') || str_contains($subjectLower, 'drawing')) { return 'palette'; }
        return 'file-text';
    };
@endphp

<div class="space-y-6" x-data="{ currentTab: '{{ $todaySubjects->isNotEmpty() ? 'today' : 'weekly' }}' }">
    {{-- Header Panel --}}
    <div class="student-panel">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="student-status-pill">
                    <i data-lucide="book-open" class="h-3.5 w-3.5 mr-1"></i>
                    Registered Subjects
                </span>
                <h2 class="text-2xl font-black text-gray-900 mt-2.5" style="margin: 10px 0 4px;">My Registered Subjects</h2>
                <p class="text-sm font-semibold text-gray-500">
                    {{ $section?->official_name ?? 'General Section' }} · School Year {{ $student->school_year }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-150 bg-gray-50 px-4 py-2.5 text-center shrink-0">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Total Subjects</p>
                <p class="mt-0.5 text-xl font-black text-gray-950">{{ $subjects->count() }}</p>
            </div>
        </div>
    </div>
    <div class="student-panel">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="student-status-pill">
                    <i data-lucide="video" class="h-3.5 w-3.5 mr-1"></i>
                    Subject Portal
                </span>
                <h2 class="text-xl font-black text-gray-900 mt-2" style="margin: 8px 0 4px;">Meetings & Learning Materials</h2>
                <p class="text-sm font-semibold text-gray-500">Teacher updates appear here automatically per enrolled subject.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 mt-4">
            @forelse($subjects as $subject)
                @php
                    $meetings = $subject->meetings ?? collect();
                    $materials = $subject->materials ?? collect();
                @endphp
                <article class="student-subject-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-extrabold text-gray-900 text-base" style="margin:0;">{{ $subject->subject_name }}</h3>
                            <p class="text-xs font-semibold text-gray-500 mt-1">Teacher: {{ $subject->teacher_name ?: 'To Be Assigned' }}</p>
                        </div>
                        <span class="student-subject-code-pill">SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-2">Meetings</p>
                            @forelse($meetings->take(3) as $meeting)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-150 bg-gray-50 p-3 mb-2">
                                    <div class="min-w-0">
                                        <strong class="block truncate text-sm text-gray-900">{{ $meeting->title }}</strong>
                                        <span class="text-xs font-semibold text-gray-500">{{ $meeting->meeting_date?->format('M d, Y') }} · {{ substr((string) $meeting->meeting_time, 0, 5) }}</span>
                                    </div>
                                    @if($meeting->meeting_url)
                                        <a href="{{ $meeting->meeting_url }}" target="_blank" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white">Join Meeting</a>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs font-semibold text-gray-400">No meetings posted.</p>
                            @endforelse
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-2">Materials</p>
                            @forelse($materials->take(3) as $material)
                                @php $url = $material->external_url ?: \Illuminate\Support\Facades\Storage::disk($material->disk ?: 'public')->url($material->path); @endphp
                                <a href="{{ $url }}" target="_blank" class="flex items-center justify-between gap-3 rounded-xl border border-gray-150 bg-gray-50 p-3 mb-2 text-sm font-bold text-emerald-700">
                                    <span class="truncate">{{ $material->title }}</span>
                                    <i data-lucide="external-link" class="h-4 w-4"></i>
                                </a>
                            @empty
                                <p class="text-xs font-semibold text-gray-400">No materials posted.</p>
                            @endforelse
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center text-sm font-semibold text-gray-400">No subjects found.</div>
            @endforelse
        </div>
    </div>

    {{-- Tabs Switcher --}}
    <div class="flex flex-col gap-3 border-b border-gray-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex w-max max-w-full gap-1 rounded-xl bg-gray-150 p-1">
            <button type="button" @click="currentTab = 'today'; $nextTick(() => refreshStudentIcons())" :class="currentTab === 'today' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-500 hover:text-gray-855'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-extrabold transition cursor-pointer">
                <i data-lucide="clock" class="h-4 w-4"></i>
                <span>Today's Classes ({{ $todayName }})</span>
            </button>
            <button type="button" @click="currentTab = 'weekly'; $nextTick(() => refreshStudentIcons())" :class="currentTab === 'weekly' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-500 hover:text-gray-855'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-extrabold transition cursor-pointer">
                <i data-lucide="calendar-range" class="h-4 w-4"></i>
                <span>Weekly Calendar</span>
            </button>
        </div>
    </div>>

    {{-- Tab Contents --}}
    <div>
        {{-- Today's Classes Tab --}}
        <div x-show="currentTab === 'today'" class="space-y-4">
            @if($todaySubjects->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($todaySubjects as $subject)
                        @php 
                            $icon = $subjectIcon($subject->subject_name);
                            
                            // Class State Tracking
                            $classState = 'upcoming'; 
                            $sched = trim((string) $subject->schedule);
                            $timePart = '';
                            $isScheduledTomorrow = false;
                            if (!empty($sched)) {
                                $parts = explode(' ', $sched);
                                $timePart = trim(substr($sched, strlen($parts[0] ?? '')));
                                $timeRange = explode('-', $timePart);
                                $startTimeStr = trim($timeRange[0] ?? '');
                                $endTimeStr = trim($timeRange[1] ?? '');
                                
                                if (!empty($startTimeStr) && !empty($endTimeStr)) {
                                    $startTime = strtotime(date('Y-m-d') . ' ' . $startTimeStr);
                                    $endTime = strtotime(date('Y-m-d') . ' ' . $endTimeStr);
                                    if ($startTime !== false && $endTime !== false) {
                                        $now = time();
                                        if ($now > $endTime) {
                                            $classState = 'completed';
                                        } elseif ($now >= $startTime && $now <= $endTime) {
                                            $classState = 'live';
                                        }
                                    }
                                }
                                
                                // Check if scheduled tomorrow
                                $tomorrowName = now()->addDay()->format('l');
                                $dayPart = $parts[0] ?? '';
                                $isM = str_contains($dayPart, 'M');
                                $isW = str_contains($dayPart, 'W');
                                $isF = str_contains($dayPart, 'F');
                                $isTh = str_contains($dayPart, 'Th');
                                $isT = str_contains(str_replace('Th', '', $dayPart), 'T');
                                
                                $isScheduledTomorrow = match ($tomorrowName) {
                                    'Monday' => $isM,
                                    'Tuesday' => $isT,
                                    'Wednesday' => $isW,
                                    'Thursday' => $isTh,
                                    'Friday' => $isF,
                                    default => false,
                                };
                            }
                        @endphp
                        <div class="relative overflow-hidden student-subject-card flex flex-col justify-between gap-6 group transition {{ $classState === 'live' ? 'border-emerald-300 bg-emerald-50/30 shadow-xs' : '' }}">
                            @if($classState === 'completed')
                                <div class="flex items-center justify-between w-full">
                                    <div class="text-left min-w-0">
                                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Teacher: {{ $subject->teacher_name ?: 'To Be Assigned' }}</p>
                                        <h3 class="text-sm font-extrabold text-slate-900 mt-1 truncate">{{ $subject->subject_name }}</h3>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                                        <span class="text-[10px] font-black text-white bg-rose-600 px-3 py-1 rounded-full uppercase tracking-widest shadow-xs">
                                            END OF CLASS
                                        </span>
                                        @if($isScheduledTomorrow)
                                            <span class="inline-flex items-center gap-1 text-[9px] font-black text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full border border-gray-200 uppercase tracking-widest">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i> Tomorrow
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start gap-4">
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border {{ $classState === 'live' ? 'bg-emerald-500 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-100' }} shadow-xs">
                                        <i data-lucide="{{ $icon }}" class="h-6 w-6"></i>
                                    </span>
                                    <div class="space-y-1.5 overflow-hidden min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-extrabold text-gray-900 text-lg truncate group-hover:text-emerald-700 transition" style="margin:0;">
                                                {{ $subject->subject_name }}
                                            </h3>
                                            @if($classState === 'live')
                                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-500 text-white px-2 py-0.5 text-[8px] font-black uppercase tracking-wider animate-pulse">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-white"></span> Live
                                                </span>
                                            @endif
                                        </div>
                                        <span class="student-subject-code-pill mt-1">
                                            SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <div class="flex flex-col gap-1.5 mt-2">
                                            <p class="text-xs font-semibold text-gray-500 flex items-center gap-1.5">
                                                <i data-lucide="user" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                                <span>Teacher: <strong class="text-gray-700 font-bold">{{ $subject->teacher_name ?: 'To Be Assigned' }}</strong></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-4 border-t border-gray-150 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div x-data="{
                                        schoolTime: '{{ !empty($timePart) ? $timePart . \' PHT\' : ($subject->schedule ?: \'To Be Announced\') }}',
                                        localTime: '',
                                        hasDifferentTz: false,
                                        init() {
                                            const startStr = '{{ $startTimeStr }}';
                                            const endStr = '{{ $endTimeStr }}';
                                            if (!startStr || !endStr) return;
                                            
                                            const todayStr = new Date().toISOString().split('T')[0];
                                            const parseTime = (tStr) => {
                                                const parts = tStr.match(/(\d+):(\d+)\s*([AP]M)/i);
                                                if (!parts) return null;
                                                let hrs = parseInt(parts[1], 10);
                                                const mins = parseInt(parts[2], 10);
                                                const ampm = parts[3].toUpperCase();
                                                if (ampm === 'PM' && hrs < 12) hrs += 12;
                                                if (ampm === 'AM' && hrs === 12) hrs = 0;
                                                return new Date(`${todayStr}T${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00+08:00`);
                                            };
                                            
                                            const start = parseTime(startStr);
                                            const end = parseTime(endStr);
                                            if (start && end) {
                                                const manilaOffset = 480;
                                                const clientOffset = -new Date().getTimezoneOffset();
                                                if (manilaOffset !== clientOffset) {
                                                    this.hasDifferentTz = true;
                                                    const opts = { hour: 'numeric', minute: '2-digit', hour12: true };
                                                    const lStart = start.toLocaleTimeString([], opts);
                                                    const lEnd = end.toLocaleTimeString([], opts);
                                                    
                                                    const offsetHrs = Math.floor(Math.abs(clientOffset) / 60);
                                                    const sign = clientOffset >= 0 ? '+' : '-';
                                                    const offsetStr = `UTC${sign}${offsetHrs}`;
                                                    this.localTime = `${lStart} - ${lEnd} (${offsetStr})`;
                                                }
                                            }
                                        }
                                    }">
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Today's Time</p>
                                        <div class="flex items-center gap-1.5 text-xs font-extrabold mt-1 px-3 py-1 rounded-xl border {{ $classState === 'live' ? 'text-emerald-900 bg-emerald-100 border-emerald-200' : 'text-emerald-800 bg-emerald-50 border-emerald-100/30' }}">
                                            <i data-lucide="clock" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                            <span x-text="schoolTime"></span>
                                        </div>
                                        <template x-if="hasDifferentTz">
                                            <div class="flex items-center gap-1 text-[9px] font-bold mt-1 px-2.5 py-0.5 rounded border border-sky-100 bg-sky-50 text-sky-850 justify-center">
                                                <i data-lucide="globe" class="w-3 h-3 text-sky-500 mr-0.5 shrink-0"></i>
                                                <span x-text="localTime"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <div>
                                        @if($subject->ms_channel_id && $section?->ms_team_url)
                                            <a href="{{ $section->ms_team_url }}" target="_blank" class="{{ $classState === 'live' ? 'student-primary-btn' : 'student-outline-btn' }}">
                                                <i data-lucide="video" class="w-3.5 h-3.5"></i>
                                                <span>Join Class Channel</span>
                                            </a>
                                        @else
                                            <span class="student-status-pill bg-gray-50 text-gray-400 border border-gray-100">
                                                <i data-lucide="clock" class="w-3.5 h-3.5 mr-1"></i> Pending Room
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="dash-empty border border-gray-150 rounded-xl bg-white p-8">
                    <i data-lucide="calendar"></i>
                    @if(in_array($todayName, ['Saturday', 'Sunday']))
                        <p class="text-gray-500 font-semibold mt-2">No subjects scheduled for today. Happy Weekend! 🎉</p>
                    @else
                        <p class="text-gray-500 font-semibold mt-2">No subjects scheduled for today ({{ $todayName }}).</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Weekly Calendar Tab --}}
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
                                        $classState = 'upcoming';
                                        $startTimeStr = '';
                                        $endTimeStr = '';
                                        
                                        $sched = trim((string) $subject->schedule);
                                        if (!empty($sched)) {
                                            $parts = explode(' ', $sched);
                                            $timePart = trim(substr($sched, strlen($parts[0] ?? '')));
                                            $timeRange = explode('-', $timePart);
                                            $startTimeStr = trim($timeRange[0] ?? '');
                                            $endTimeStr = trim($timeRange[1] ?? '');
                                            
                                            if ($dayName === $todayName) {
                                                if (!empty($startTimeStr) && !empty($endTimeStr)) {
                                                    $startTime = strtotime(date('Y-m-d') . ' ' . $startTimeStr);
                                                    $endTime = strtotime(date('Y-m-d') . ' ' . $endTimeStr);
                                                    if ($startTime !== false && $endTime !== false) {
                                                        $now = time();
                                                        if ($now > $endTime) {
                                                            $classState = 'completed';
                                                        } elseif ($now >= $startTime && $now <= $endTime) {
                                                            $classState = 'live';
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="p-4 rounded-xl border transition flex flex-col justify-between gap-3 {{ $classState === 'completed' ? 'opacity-50 grayscale-20 border-gray-150 bg-gray-50/10' : ($classState === 'live' ? 'border-emerald-300 bg-emerald-50/30' : 'border-gray-100 bg-gray-50/20 hover:bg-gray-50/50') }}">
                                        <div class="flex items-start gap-3 min-w-0">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $classState === 'completed' ? 'bg-gray-100 text-gray-550 border-gray-200' : ($classState === 'live' ? 'bg-emerald-500 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-100') }}">
                                                <i data-lucide="{{ $icon }}" class="h-4.5 w-4.5"></i>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <h4 class="text-xs font-extrabold text-gray-955 truncate" style="margin:0;">{{ $subject->subject_name }}</h4>
                                                    @if($classState === 'live')
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-500 text-white px-1.5 py-0.5 text-[7px] font-black uppercase tracking-wider animate-pulse">
                                                            Live
                                                        </span>
                                                    @endif
                                                </div>
                                                <p class="text-[10px] text-gray-550 mt-1 truncate">Teacher: {{ $subject->teacher_name ?: 'To Be Assigned' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-1.5 pt-2 border-t border-gray-100/50 mt-1" x-data="{
                                            schoolTime: '{{ $subject->schedule }} PHT',
                                            localTime: '',
                                            hasDifferentTz: false,
                                            init() {
                                                const startStr = '{{ $startTimeStr }}';
                                                const endStr = '{{ $endTimeStr }}';
                                                if (!startStr || !endStr) return;
                                                
                                                const todayStr = new Date().toISOString().split('T')[0];
                                                const parseTime = (tStr) => {
                                                    const parts = tStr.match(/(\d+):(\d+)\s*([AP]M)/i);
                                                    if (!parts) return null;
                                                    let hrs = parseInt(parts[1], 10);
                                                    const mins = parseInt(parts[2], 10);
                                                    const ampm = parts[3].toUpperCase();
                                                    if (ampm === 'PM' && hrs < 12) hrs += 12;
                                                    if (ampm === 'AM' && hrs === 12) hrs = 0;
                                                    return new Date(`${todayStr}T${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00+08:00`);
                                                };
                                                
                                                const start = parseTime(startStr);
                                                const end = parseTime(endStr);
                                                if (start && end) {
                                                    const manilaOffset = 480;
                                                    const clientOffset = -new Date().getTimezoneOffset();
                                                    if (manilaOffset !== clientOffset) {
                                                        this.hasDifferentTz = true;
                                                        const opts = { hour: 'numeric', minute: '2-digit', hour12: true };
                                                        const lStart = start.toLocaleTimeString([], opts);
                                                        const lEnd = end.toLocaleTimeString([], opts);
                                                        
                                                        const offsetHrs = Math.floor(Math.abs(clientOffset) / 60);
                                                        const sign = clientOffset >= 0 ? '+' : '-';
                                                        const offsetStr = `UTC${sign}${offsetHrs}`;
                                                        
                                                        // Extract schedule day part (e.g. M/W/F or T/Th)
                                                        const schedStr = '{{ $subject->schedule }}';
                                                        const dayPart = schedStr.split(' ')[0] || '';
                                                        
                                                        this.localTime = `${dayPart} ${lStart} - ${lEnd} (${offsetStr})`;
                                                    }
                                                }
                                            }
                                        }">
                                            <div class="flex items-center justify-between gap-2 w-full">
                                                <span class="inline-flex items-center gap-1 text-[10px] font-black {{ $classState === 'completed' ? 'text-gray-400 bg-gray-50 border-gray-200/50' : ($classState === 'live' ? 'text-emerald-900 bg-emerald-100 border-emerald-200' : 'text-emerald-800 bg-emerald-50 border-emerald-100/20') }} px-2 py-0.5 rounded border">
                                                    <i data-lucide="clock" class="w-3.5 h-3.5 {{ $classState === 'completed' ? 'text-gray-405' : 'text-emerald-600' }}"></i>
                                                    <span x-text="schoolTime"></span>
                                                </span>
                                                @if($classState === 'completed')
                                                    <span class="text-[10px] font-extrabold text-gray-400 flex items-center gap-0.5">
                                                        <i data-lucide="check-circle-2" class="w-3 h-3 text-gray-400"></i> Completed
                                                    </span>
                                                @elseif($subject->ms_channel_id && $section?->ms_team_url)
                                                    <a href="{{ $section->ms_team_url }}" target="_blank" class="text-[10px] font-extrabold text-emerald-600 hover:text-emerald-700 flex items-center gap-0.5">
                                                        Join <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            <template x-if="hasDifferentTz">
                                                <div class="flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded border border-sky-100 bg-sky-50 text-sky-850 justify-center w-max max-w-full">
                                                    <i data-lucide="globe" class="w-3 h-3 text-sky-500 mr-0.5 shrink-0"></i>
                                                    <span x-text="localTime"></span>
                                                </div>
                                            </template>
                                        </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs font-semibold text-gray-400">No classes scheduled</p>
                        @endif
                    </div>
                @endforeach

                @if(count($unscheduled) > 0)
                    <div class="border border-gray-150 rounded-xl bg-gray-50/50 p-5 shadow-sm space-y-4">
                        <div class="border-b border-gray-200 pb-2.5">
                            <h3 class="text-sm font-extrabold text-gray-500 uppercase tracking-wider">Unscheduled / Pending Subjects</h3>
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
                                        <p class="text-[10px] text-gray-400 mt-0.5">Time to be announced</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>    </div>
</div>
@endsection
