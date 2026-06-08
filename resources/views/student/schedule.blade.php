@extends('student.layout', ['heading' => 'My Schedule'])

@section('content')
@php
    $days = [
        'Monday' => [],
        'Tuesday' => [],
        'Wednesday' => [],
        'Thursday' => [],
        'Friday' => [],
    ];

    $unscheduled = [];

    $timeSortValue = function (string $time): int {
        if (preg_match('/(\d{1,2}:\d{2}\s*[AP]M)/i', $time, $matches)) {
            return strtotime($matches[1]) ?: PHP_INT_MAX;
        }
        return PHP_INT_MAX;
    };

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

    $teacherName = fn ($subject): string => $subject->teacher_name ?: 'To Be Assigned';
    $sampleTeacherPhotos = [
        'https://randomuser.me/api/portraits/men/32.jpg',
        'https://randomuser.me/api/portraits/women/44.jpg',
        'https://randomuser.me/api/portraits/men/46.jpg',
        'https://randomuser.me/api/portraits/women/68.jpg',
        'https://randomuser.me/api/portraits/men/75.jpg',
        'https://randomuser.me/api/portraits/women/65.jpg',
        'https://randomuser.me/api/portraits/men/22.jpg',
        'https://randomuser.me/api/portraits/women/28.jpg',
    ];
    $teacherAvatar = fn (string $name): string => $sampleTeacherPhotos[abs(crc32($name)) % count($sampleTeacherPhotos)];

    foreach ($subjects as $subj) {
        $sched = trim((string) $subj->schedule);
        if (empty($sched)) {
            $unscheduled[] = [
                'subject' => $subj,
                'time' => 'To Be Announced',
            ];
            continue;
        }

        $parts = explode(' ', $sched);
        $dayPart = $parts[0] ?? '';

        $isM = str_contains($dayPart, 'M');
        $isW = str_contains($dayPart, 'W');
        $isF = str_contains($dayPart, 'F');
        $isTh = str_contains($dayPart, 'Th');
        $isT = str_contains(str_replace('Th', '', $dayPart), 'T');

        $timePart = trim(substr($sched, strlen($dayPart)));
        if (empty($timePart)) {
            $timePart = $sched;
        }

        $subjData = [
            'subject' => $subj,
            'time' => $timePart,
        ];
        $matched = false;

        foreach ([
            'Monday' => $isM,
            'Tuesday' => $isT,
            'Wednesday' => $isW,
            'Thursday' => $isTh,
            'Friday' => $isF,
        ] as $day => $isScheduled) {
            if ($isScheduled) {
                $days[$day][] = $subjData;
                $matched = true;
            }
        }

        if (!$matched) {
            $unscheduled[] = $subjData;
        }
    }

    foreach ($days as $day => $classes) {
        usort($classes, fn ($a, $b) => $timeSortValue($a['time']) <=> $timeSortValue($b['time']));
        $days[$day] = $classes;
    }

    $scheduledCount = collect($days)->flatten(1)->count();
    $todayName = now()->format('l');
@endphp

@once
<style>
    .schedule-board {
        display: grid;
        gap: 1rem;
    }
    .schedule-day {
        display: grid;
        grid-template-columns: 10.5rem minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
        border: 1px solid var(--s-border);
        border-radius: var(--r-lg);
        background: var(--s-surface);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }
    .schedule-day-meta {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 1.25rem;
        padding: 1.25rem;
        background: linear-gradient(180deg, #f8fafc 0%, #ecfdf5 100%);
        border-right: 1px solid var(--s-border);
    }
    .schedule-classes {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
        gap: .75rem;
        padding: 1.25rem;
    }
    .schedule-session {
        display: grid;
        grid-template-columns: 5.75rem minmax(0, 1fr);
        min-height: 9.75rem;
        border: 1px solid var(--s-border);
        border-radius: var(--r);
        background: #fbfdff;
        overflow: hidden;
        transition: border-color var(--duration) var(--ease), box-shadow var(--duration) var(--ease), transform var(--duration) var(--ease);
    }
    .schedule-session:hover {
        border-color: #a7f3d0;
        box-shadow: var(--shadow-md);
        transform: translateY(-1px);
    }
    .teacher-photo-panel {
        position: relative;
        min-height: 100%;
        background: #d1fae5;
        overflow: hidden;
    }
    .teacher-photo-panel img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .teacher-photo-panel::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(251, 253, 255, 0) 42%, #fbfdff 100%);
        pointer-events: none;
    }
    .schedule-session-body {
        display: flex;
        min-width: 0;
        flex-direction: column;
        justify-content: space-between;
        gap: .85rem;
        padding: .95rem 1rem .95rem .5rem;
    }
    .teacher-strip {
        display: grid;
        grid-template-columns: 5rem minmax(0, 1fr);
        overflow: hidden;
    }
    .teacher-strip .teacher-photo-panel::after {
        background: linear-gradient(90deg, rgba(248, 250, 252, 0) 42%, #f8fafc 100%);
    }
    .subject-icon-box {
        height: 2.35rem;
        width: 2.35rem;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .85rem;
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    @media (max-width: 900px) {
        .schedule-day {
            grid-template-columns: 1fr;
        }
        .schedule-day-meta {
            flex-direction: row;
            align-items: center;
            border-right: 0;
            border-bottom: 1px solid var(--s-border);
        }
    }
    @media (max-width: 520px) {
        .schedule-session {
            grid-template-columns: 5rem minmax(0, 1fr);
        }
    }
</style>
@endonce

<div class="space-y-6" x-data="{ currentTab: 'grid' }">
    <div class="student-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <span class="student-status-pill">
                    <i data-lucide="calendar-days" class="h-3.5 w-3.5 mr-1"></i>
                    Student Timetable
                </span>
                <h2 class="text-2xl font-black text-gray-900 mt-3" style="margin: 12px 0 4px;">My Schedule & Subjects</h2>
                <p class="text-sm font-semibold text-gray-500">
                    Weekly class times, teacher details, and live classroom access in one view.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:min-w-[25rem]">
                <div class="rounded-xl border border-gray-150 bg-gray-50 px-4 py-3 text-center">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Enrolled Subjects</p>
                    <p class="mt-1 text-2xl font-black text-gray-950">{{ $subjects->count() }}</p>
                </div>

                <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800">Class Section</p>
                    <p class="mt-1 truncate text-sm font-black text-emerald-900" title="{{ $section ? $section->section_title : 'General' }}">
                        {{ $section ? $section->official_name : 'General' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-3 border-b border-gray-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="inline-flex w-max max-w-full gap-1 rounded-xl bg-gray-150 p-1">
            <button type="button" @click="currentTab = 'grid'; $nextTick(() => refreshStudentIcons())" :class="currentTab === 'grid' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-500 hover:text-gray-850'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-extrabold transition cursor-pointer">
                <i data-lucide="calendar-range" class="h-4 w-4"></i>
                <span>Weekly Timetable</span>
            </button>

            <button type="button" @click="currentTab = 'list'; $nextTick(() => refreshStudentIcons())" :class="currentTab === 'list' ? 'bg-white text-emerald-900 shadow-sm' : 'text-gray-500 hover:text-gray-855'" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-extrabold transition cursor-pointer">
                <i data-lucide="list" class="h-4 w-4"></i>
                <span>Subject List</span>
            </button>
        </div>

        <div class="text-xs font-extrabold text-gray-400">
            School Year {{ $student->school_year }}
        </div>
    </div>

    <div>
        <div x-show="currentTab === 'grid'" class="space-y-4">
            @if($subjects->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-150 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Scheduled Blocks</p>
                        <p class="mt-1 text-xl font-black text-gray-950">{{ $scheduledCount }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-150 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Class Days</p>
                        <p class="mt-1 text-xl font-black text-gray-950">{{ collect($days)->filter(fn ($classes) => count($classes) > 0)->count() }} / 5</p>
                    </div>

                    <div class="rounded-xl border border-gray-150 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Unscheduled</p>
                        <p class="mt-1 text-xl font-black text-gray-950">{{ count($unscheduled) }}</p>
                    </div>
                </div>

                <div class="schedule-board">
                    @foreach($days as $dayName => $dayClasses)
                        <section class="schedule-day">
                            <div class="schedule-day-meta">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $dayName === $todayName ? 'bg-emerald-600' : 'bg-gray-300' }}"></span>
                                        <h4 class="text-sm font-black uppercase tracking-wider text-gray-950" style="margin:0;">{{ $dayName }}</h4>
                                    </div>
                                    <p class="mt-1 text-xs font-bold text-gray-500">
                                        {{ count($dayClasses) }} {{ \Illuminate\Support\Str::plural('class', count($dayClasses)) }}
                                    </p>
                                </div>

                                @if($dayName === $todayName)
                                    <span class="w-max rounded-full bg-emerald-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white">Today</span>
                                @else
                                    <span class="w-max rounded-full border border-gray-150 bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-gray-400">Weekday</span>
                                @endif
                            </div>

                            <div class="schedule-classes">
                                @forelse($dayClasses as $cls)
                                    @php
                                        $subj = $cls['subject'];
                                        $currentTeacherName = $teacherName($subj);
                                        $classState = 'upcoming';
                                        if ($dayName === $todayName) {
                                            $sched = trim((string) $subj->schedule);
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
                                            }
                                        }
                                    @endphp

                                    <article class="schedule-session {{ $classState === 'completed' ? 'opacity-50 grayscale-20' : ($classState === 'live' ? 'border-emerald-300 ring-1 ring-emerald-300' : '') }}">
                                        <div class="teacher-photo-panel">
                                            <img src="{{ $teacherAvatar($currentTeacherName) }}" alt="{{ $currentTeacherName }}">
                                        </div>

                                        <div class="schedule-session-body">
                                            <div class="flex items-start gap-3">
                                                <span class="subject-icon-box {{ $classState === 'completed' ? 'bg-gray-100 text-gray-550 border-gray-200' : ($classState === 'live' ? 'bg-emerald-500 text-white border-emerald-600' : '') }}">
                                                    <i data-lucide="{{ $subjectIcon($subj->subject_name) }}" class="h-5 w-5"></i>
                                                </span>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <h5 class="truncate text-sm font-black text-gray-950" title="{{ $subj->subject_name }}" style="margin:0;">
                                                            {{ $subj->subject_name }}
                                                        </h5>
                                                        @if($classState === 'live')
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-500 text-white px-1.5 py-0.5 text-[7px] font-black uppercase tracking-wider animate-pulse">
                                                                Live
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="mt-1 truncate text-xs font-bold text-gray-600" title="{{ $currentTeacherName }}">
                                                        {{ $currentTeacherName }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-end justify-between gap-3 border-t border-gray-150 pt-3">
                                                <div class="min-w-0">
                                                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Time</p>
                                                    <div class="mt-1 inline-flex max-w-full items-center gap-1.5 rounded-full border {{ $classState === 'completed' ? 'border-gray-200 bg-gray-50 text-gray-400' : 'border-emerald-100 bg-emerald-50 text-emerald-800' }} px-2.5 py-1 text-[11px] font-black">
                                                        <i data-lucide="clock" class="h-3.5 w-3.5 shrink-0 {{ $classState === 'completed' ? 'text-gray-400' : 'text-emerald-600' }}"></i>
                                                        <span class="truncate">{{ $cls['time'] }}</span>
                                                    </div>
                                                </div>

                                                @if($classState === 'completed')
                                                    <span class="text-[10px] font-extrabold text-gray-400 flex items-center gap-0.5 mb-1">
                                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5 text-gray-400"></i> Completed
                                                    </span>
                                                @elseif($subj->ms_channel_id)
                                                    <a href="{{ $section->ms_team_url ?? 'https://teams.microsoft.com/' }}" target="_blank" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white transition hover:bg-emerald-700" aria-label="Join class">
                                                        <i data-lucide="video" class="h-4 w-4"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @empty
                                    <div class="flex min-h-[8rem] items-center justify-center rounded-2xl border border-dashed border-gray-150 bg-gray-50 text-xs font-bold text-gray-400">
                                        No classes scheduled
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>

                @if(count($unscheduled) > 0)
                    <section class="student-panel mt-6">
                        <div class="student-panel-header" style="margin-bottom:0; padding:0;">
                            <h2>Special / Unscheduled Subjects</h2>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 pt-4">
                            @foreach($unscheduled as $u)
                                @php
                                    $currentTeacherName = $teacherName($u['subject']);
                                @endphp
                                <div class="teacher-strip rounded-xl border border-gray-150 bg-gray-50 overflow-hidden shadow-sm">
                                    <div class="teacher-photo-panel">
                                        <img src="{{ $teacherAvatar($currentTeacherName) }}" alt="{{ $currentTeacherName }}">
                                    </div>

                                    <div class="min-w-0 p-4 pl-2 flex flex-col justify-between">
                                        <div class="flex items-start gap-3">
                                            <span class="subject-icon-box">
                                                <i data-lucide="{{ $subjectIcon($u['subject']->subject_name) }}" class="h-5 w-5"></i>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <h5 class="truncate text-sm font-black text-gray-950" style="margin:0;">{{ $u['subject']->subject_name }}</h5>
                                                <p class="mt-1 truncate text-xs font-bold text-gray-600">{{ $currentTeacherName }}</p>
                                                <span class="mt-3 inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-emerald-800 ring-1 ring-emerald-100">{{ $u['time'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @else
                <div class="dash-empty">
                    <i data-lucide="calendar"></i>
                    <p>Your schedule is empty</p>
                </div>
            @endif
        </div>

        <div x-show="currentTab === 'list'" class="space-y-6">
            @if($subjects->isNotEmpty())
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach($subjects as $subj)
                        @php
                            $currentTeacherName = $teacherName($subj);
                        @endphp
                        <article class="overflow-hidden rounded-xl border border-gray-150 bg-white shadow-sm flex flex-col justify-between">
                            <div class="teacher-strip bg-gray-50 border-b border-gray-150">
                                <div class="teacher-photo-panel">
                                    <img src="{{ $teacherAvatar($currentTeacherName) }}" alt="{{ $currentTeacherName }}">
                                </div>

                                <div class="flex min-w-0 items-start gap-4 p-5 pl-2">
                                    <span class="subject-icon-box h-12 w-12">
                                        <i data-lucide="{{ $subjectIcon($subj->subject_name) }}" class="h-6 w-6"></i>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <h4 class="truncate text-lg font-black text-gray-950" title="{{ $subj->subject_name }}" style="margin:0;">{{ $subj->subject_name }}</h4>
                                        <p class="mt-1 text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Primary Teacher</p>
                                        <p class="truncate text-sm font-bold text-gray-700" title="{{ $currentTeacherName }}">{{ $currentTeacherName }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Weekly Schedule</p>
                                    <div class="mt-1 inline-flex max-w-full items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-800">
                                        <i data-lucide="clock" class="h-4 w-4 shrink-0"></i>
                                        <span class="truncate">{{ $subj->schedule ?: 'To Be Announced' }}</span>
                                    </div>
                                </div>

                                @if($subj->ms_channel_id)
                                    <a href="{{ $section->ms_team_url ?? 'https://teams.microsoft.com/' }}" target="_blank" class="student-primary-btn">
                                        <i data-lucide="video" class="h-4 w-4"></i>
                                        <span>Join Class</span>
                                    </a>
                                @else
                                    <span class="inline-flex items-center rounded-xl bg-gray-50 px-3 py-2 text-[11px] font-bold text-gray-400">
                                        Live room unavailable
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="dash-empty">
                    <i data-lucide="book-open"></i>
                    <p>No subjects enrolled yet</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
