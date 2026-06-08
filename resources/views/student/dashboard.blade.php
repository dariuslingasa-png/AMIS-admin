@extends('student.layout', ['heading' => 'Dashboard'])

@section('content')
@php 
    $applicant = $student->applicant; 
    $studentName = $applicant ? $applicant->full_name : $student->user->name; 
    $firstName = $applicant?->first_name ?? $student->user->name; 
    $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? '')); 
    $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? '')); 
    $parentMobile = trim(($applicant->parent_country_code ?? '').' '.($applicant->parent_mobile ?? '')); 
    $parentEmail = $applicant->parent_email ?? null; 
    $emergencyName = $applicant->emergency_name ?? null; 
    $emergencyPhone = trim((string) ($applicant->emergency_phone ?? '')); 
    $photoUrl = \App\Support\EnrollmentStorage::url($applicant?->photo_2x2_url); 
    $scheduledSubjects = $subjects->filter(fn ($subject) => trim((string) $subject->schedule) !== '')->count(); 
    $teamsReady = filled($section?->ms_team_url);

    // Eager filter classes scheduled for today
    $todayName = now()->format('l'); // 'Monday', 'Tuesday', etc.
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
@endphp

<div class="space-y-6" x-data="{ copied: false, idModalOpen: false }" @keydown.escape.window="idModalOpen = false">
    {{-- Welcome Hero --}}
    <section class="dash-welcome">
        <div class="dash-welcome-body">
            <div class="flex items-center gap-2 flex-wrap mb-3">
                <p class="dash-welcome-greeting" style="margin: 0;">Assalamualaikum, {{ $firstName }}</p>
                <span class="inline-flex rounded-full bg-slate-900 text-white px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider">
                    {{ $applicant?->student_type ?: 'New' }}
                </span>
                <span class="inline-flex rounded-full bg-emerald-600 text-white px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider">
                    {{ $applicant?->learning_mode ?: 'Face-to-Face' }}
                </span>
            </div>
            <h2 class="dash-welcome-title">Your Student Dashboard</h2>
            <p class="dash-welcome-sub">Monitor your subjects, class schedule, billing status, and student profile — all in one place.</p>
            
            <div class="mt-4 flex flex-wrap items-center gap-3" x-data="{
                clientTime: '',
                clientTz: '',
                phTime: '',
                updateClocks() {
                    const now = new Date();
                    this.clientTime = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
                    
                    let tzName = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    const offsetMin = -now.getTimezoneOffset();
                    const offsetHrs = Math.floor(Math.abs(offsetMin) / 60);
                    const offsetRem = Math.abs(offsetMin) % 60;
                    const sign = offsetMin >= 0 ? '+' : '-';
                    const offsetStr = `UTC${sign}${offsetHrs}${offsetRem > 0 ? ':' + String(offsetRem).padStart(2, '0') : ''}`;
                    this.clientTz = `${tzName} (${offsetStr})`;
                    
                    this.phTime = now.toLocaleTimeString('en-US', { 
                        timeZone: 'Asia/Manila', 
                        hour: 'numeric', 
                        minute: '2-digit', 
                        second: '2-digit', 
                        hour12: true 
                    });
                }
            }" x-init="updateClocks(); setInterval(() => updateClocks(), 1000); $nextTick(() => window.lucide && window.lucide.createIcons())">
                {{-- Client Local Time Badge --}}
                <div class="inline-flex items-center gap-2 rounded-xl bg-slate-900/5 px-3 py-1.5 border border-slate-900/10 text-xs font-bold text-slate-700">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-500"></i>
                    <span>Local Time:</span>
                    <span class="font-extrabold text-slate-950" x-text="clientTime">--:--:--</span>
                    <span class="text-[10px] text-slate-400 font-semibold" x-text="clientTz">Detecting...</span>
                </div>

                {{-- Philippines School Time Badge --}}
                <div class="inline-flex items-center gap-2 rounded-xl bg-emerald-500/10 px-3 py-1.5 border border-emerald-500/20 text-xs font-bold text-emerald-800">
                    <span class="flex h-2 w-2 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>School Time (PH):</span>
                    <span class="font-extrabold text-emerald-950" x-text="phTime">--:--:--</span>
                    <span class="text-[10px] text-emerald-600 font-semibold">Manila (UTC+8)</span>
                </div>
            </div>
        </div>
        <img src="{{ asset('images/school_elements_bg.png') }}" class="dash-welcome-pattern" alt="School elements pattern">
    </section>

    {{-- Stats Grid --}}
    <section class="dash-stats">
        <article class="dash-stat dash-stat-green">
            <span class="dash-stat-icon"><i data-lucide="book-open-check"></i></span>
            <div>
                <p class="dash-stat-label">Enrolled Subjects</p>
                <strong class="dash-stat-value">{{ $subjects->count() }}</strong>
            </div>
        </article>
        <article class="dash-stat dash-stat-blue">
            <span class="dash-stat-icon"><i data-lucide="calendar-clock"></i></span>
            <div>
                <p class="dash-stat-label">Scheduled Classes</p>
                <strong class="dash-stat-value">{{ $scheduledSubjects }}</strong>
            </div>
        </article>
        <article class="dash-stat dash-stat-violet">
            <span class="dash-stat-icon"><i data-lucide="calendar-days"></i></span>
            <div>
                <p class="dash-stat-label">School Year</p>
                <strong class="dash-stat-value" style="font-size: 20px; line-height: 1.4;">{{ $student->school_year }}</strong>
            </div>
        </article>
        <article class="dash-stat dash-stat-amber">
            <span class="dash-stat-icon"><i data-lucide="wallet"></i></span>
            <div>
                <p class="dash-stat-label">Remaining Balance</p>
                <strong class="dash-stat-value">PHP {{ number_format((float) ($student->account->remaining_balance ?? 0), 2) }}</strong>
            </div>
        </article>
    </section>

    {{-- Main Content Grid --}}
    <div class="dash-grid">
        <div class="dash-main-col">
            {{-- Current Classes Panel --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2>Today's Schedule ({{ $todayName }})</h2>
                    <a href="{{ route('student.schedule') }}" class="dash-panel-link">Weekly timetable →</a>
                </div>
                
                @if($todaySubjects->isNotEmpty())
                    <div class="p-5 space-y-3">
                        @foreach($todaySubjects as $subject)
                            @php 
                                $subjLower = mb_strtolower($subject->subject_name); 
                                $iconName = 'file-text'; 
                                if (str_contains($subjLower, 'math')) { $iconName = 'binary'; } 
                                elseif (str_contains($subjLower, 'science')) { $iconName = 'beaker'; } 
                                elseif (str_contains($subjLower, 'english') || str_contains($subjLower, 'reading')) { $iconName = 'book-open'; } 
                                elseif (str_contains($subjLower, 'arabic') || str_contains($subjLower, 'qur') || str_contains($subjLower, 'islamic')) { $iconName = 'book'; } 
                                elseif (str_contains($subjLower, 'art') || str_contains($subjLower, 'drawing')) { $iconName = 'palette'; } 
                                elseif (str_contains($subjLower, 'pe') || str_contains($subjLower, 'physical')) { $iconName = 'activity'; } 
                                elseif (str_contains($subjLower, 'computer') || str_contains($subjLower, 'ict')) { $iconName = 'monitor'; }

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

                            <div class="relative overflow-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border transition {{ $classState === 'live' ? 'border-emerald-300 bg-emerald-50/30 hover:bg-emerald-50/40 shadow-xs' : ($classState === 'completed' ? 'border-gray-150 bg-gray-50/10' : 'border-gray-150 bg-gray-50/20 hover:bg-gray-50/50') }}">
                                @if($classState === 'completed')
                                    <div class="flex items-center justify-between w-full">
                                        <div class="text-left min-w-0">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Teacher: {{ $subject->teacher_name ?: 'To Be Assigned' }}</p>
                                            <h4 class="text-sm font-extrabold text-slate-900 mt-1 truncate">{{ $subject->subject_name }}</h4>
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
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border {{ $classState === 'live' ? 'bg-emerald-500 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-100' }}">
                                            <i data-lucide="{{ $iconName }}" class="h-5 w-5"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-extrabold text-gray-955 truncate" style="margin:0;">{{ $subject->subject_name }}</h4>
                                                @if($classState === 'live')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-500 text-white px-2 py-0.5 text-[8px] font-black uppercase tracking-wider animate-pulse">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-white"></span> Live
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-550 mt-0.5 truncate">Teacher: <span class="font-bold text-gray-700">{{ $subject->teacher_name ?: 'To Be Assigned' }}</span></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                        <div class="flex flex-col gap-1" x-data="{
                                            schoolTime: '{{ !empty($timePart) ? $timePart . ' PHT' : ($subject->schedule ?: 'To Be Announced') }}',
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
                                            <div class="flex items-center gap-1.5 text-xs font-bold px-2.5 py-1 rounded-lg border {{ $classState === 'live' ? 'text-emerald-900 bg-emerald-100 border-emerald-200' : 'text-emerald-800 bg-emerald-50 border-emerald-100/30' }}">
                                                <i data-lucide="clock" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span x-text="schoolTime"></span>
                                            </div>
                                            <template x-if="hasDifferentTz">
                                                <div class="flex items-center gap-1 text-[9px] font-bold px-1.5 py-0.5 rounded border border-sky-100 bg-sky-50 text-sky-850 justify-center">
                                                    <i data-lucide="globe" class="w-3.5 h-3.5 text-sky-500 mr-0.5 shrink-0"></i>
                                                    <span x-text="localTime"></span>
                                                </div>
                                            </template>
                                        </div>
                                        @if($subject->ms_channel_id && $section?->ms_team_url)
                                            <a href="{{ $section->ms_team_url }}" target="_blank" class="{{ $classState === 'live' ? 'student-primary-btn' : 'student-outline-btn' }}" style="min-height:32px; padding: 4px 12px; font-size:11.5px; border-radius: 8px;">
                                                <i data-lucide="video" class="w-3.5 h-3.5 mr-1"></i> Join
                                            </a>
                                        @else
                                            <span class="student-status-pill {{ $subject->schedule ? '' : 'bg-amber-50 text-amber-700 border-amber-100' }}">
                                                {{ $subject->schedule ? 'Scheduled' : 'Pending' }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty">
                        <i data-lucide="calendar"></i>
                        @if(in_array($todayName, ['Saturday', 'Sunday']))
                            <p>No classes scheduled for today. Happy Weekend! 🎉</p>
                        @else
                            <p>No classes scheduled for today ({{ $todayName }}).</p>
                        @endif
                        <a href="{{ route('student.schedule') }}" class="student-outline-btn mt-3" style="font-size: 12px; min-height: 32px; padding: 4px 12px; border-radius: 8px;">
                            <i data-lucide="calendar-range" class="w-3.5 h-3.5 mr-1"></i> View Weekly Schedule
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar Column --}}
        <div class="dash-sidebar-col">
            {{-- Latest Announcements Panel --}}
            <div class="dash-panel">
                <div class="dash-panel-header">
                    <h2>Latest Announcements</h2>
                    <a href="{{ route('student.announcements') }}" class="dash-panel-link">All →</a>
                </div>
                
                @if(count($announcements) > 0)
                    <div class="dash-feed">
                        @foreach(array_slice($announcements, 0, 3) as $announcement)
                            @php
                                $toneClasses = [ 
                                    'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100', 
                                    'sky' => 'bg-sky-50 text-sky-700 border-sky-100', 
                                    'amber' => 'bg-amber-50 text-amber-700 border-amber-100', 
                                ];
                                $tone = $toneClasses[$announcement['tone']] ?? $toneClasses['emerald'];
                            @endphp
                            <div class="dash-feed-item border-b border-gray-150 last:border-b-0" style="padding: 14px 20px;">
                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-black uppercase {{ $tone }}">
                                        {{ $announcement['type'] }}
                                    </span>
                                    <span class="text-[10px] font-semibold text-gray-400 flex items-center gap-1">
                                        <i data-lucide="calendar" class="w-3 h-3"></i>
                                        <span>{{ $announcement['date'] }}</span>
                                    </span>
                                </div>
                                <h4 class="text-xs font-extrabold text-gray-950 truncate" style="margin:0;">{{ $announcement['title'] }}</h4>
                                <p class="text-[11px] text-gray-550 mt-1 leading-relaxed">{{ $announcement['summary'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty">
                        <i data-lucide="megaphone"></i>
                        <p>No announcements yet.</p>
                    </div>
                @endif
            </div>

            {{-- Digital Student ID Widget --}}
            <div class="student-panel">
                <div class="student-panel-header" style="margin-bottom: 0;">
                    <h2>Digital Student ID</h2>
                    <button type="button" @click="idModalOpen = true; $nextTick(() => refreshStudentIcons())" class="student-light-btn">
                        <i data-lucide="maximize-2"></i> Open
                    </button>
                </div>
                <div class="pt-4">
                    <div class="mx-auto flex max-w-sm flex-col justify-between rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3 border-b border-emerald-50 pb-3">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" class="h-10 w-10 object-contain" alt="AMIS Logo">
                            <div>
                                <p class="text-xs font-black uppercase tracking-wider text-emerald-800">Al Munawwara Islamic School</p>
                                <p class="text-[10px] font-bold text-slate-400">Student Identification</p>
                            </div>
                        </div>
                        <div class="mt-5 flex items-center gap-4">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-emerald-100 bg-emerald-50">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="{{ $studentName }}" class="h-full w-full object-cover">
                                @else
                                    <span class="text-3xl font-black text-emerald-800">{{ mb_substr($firstName, 0, 1) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-black text-slate-950">{{ $studentName }}</p>
                                <p class="mt-1 text-xs font-extrabold text-emerald-700">ID: {{ $student->student_number }}</p>
                                <p class="mt-0.5 truncate text-xs font-semibold text-slate-500">{{ $student->grade_level }} / {{ $section?->official_name ?? 'General' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Student ID Modal --}}
    <div id="studentIdModal" class="student-modal-backdrop animate-fade-in" x-cloak x-show="idModalOpen" x-transition.opacity.duration.150ms>
        <div class="student-modal-card text-left" role="dialog" aria-modal="true" aria-labelledby="idModalTitle">
            <div class="student-panel-header" style="padding-top: 0; padding-left: 0; padding-right: 0;">
                <h2 id="idModalTitle">Digital Student ID</h2>
                <button type="button" @click="idModalOpen = false" class="student-icon-btn" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div class="bg-emerald-50/30 rounded-xl p-5 sm:p-6 mt-4">
                <div class="mx-auto grid gap-5 md:grid-cols-2">
                    <section class="rounded-2xl border border-emerald-100 bg-white p-6 text-gray-800 shadow-sm">
                        <div class="flex items-center gap-3 border-b border-emerald-50 pb-3">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" class="h-12 w-12 object-contain" alt="AMIS Logo">
                            <div>
                                <h4 class="font-black uppercase tracking-wider text-emerald-800 text-sm">Al Munawwara</h4>
                                <p class="text-xs font-bold text-gray-400">Islamic School</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center gap-5">
                            <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="{{ $studentName }}" class="h-full w-full object-cover">
                                @else
                                    <span class="text-4xl font-extrabold text-emerald-800">{{ mb_substr($firstName, 0, 1) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h5 class="truncate text-xl font-black text-gray-900">{{ $studentName }}</h5>
                                <p class="mt-1 text-sm font-extrabold text-emerald-600">ID: {{ $student->student_number }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-500">{{ $student->grade_level }} / {{ $section?->official_name ?? 'General' }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center justify-between border-t border-emerald-50 pt-3 text-[10px] font-black uppercase tracking-wider text-gray-400">
                            <span>SY {{ $student->school_year }}</span>
                            <span class="text-emerald-600">Official ID</span>
                        </div>
                    </section>
                    
                    <section class="rounded-2xl border border-emerald-100 bg-white p-6 text-gray-800 shadow-sm">
                        <div class="border-b border-emerald-50 pb-3">
                            <h4 class="font-black text-emerald-800 text-sm">Parent / Guardian Info</h4>
                            <p class="text-xs font-bold text-gray-400">For school verification and emergency use</p>
                        </div>
                        
                        <div class="mt-4 grid gap-3 text-xs">
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-2.5">
                                <p class="text-[9px] font-black uppercase text-gray-400">Father</p>
                                <p class="truncate font-extrabold text-gray-800 mt-0.5">{{ $fatherName ?: 'Not provided' }}</p>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-2.5">
                                <p class="text-[9px] font-black uppercase text-gray-400">Mother</p>
                                <p class="truncate font-extrabold text-gray-800 mt-0.5">{{ $motherName ?: 'Not provided' }}</p>
                            </div>
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-2.5">
                                <p class="text-[9px] font-black uppercase text-emerald-700">Parent Contact</p>
                                <p class="truncate font-extrabold text-gray-800 mt-0.5">{{ $parentMobile ?: 'Not provided' }}</p>
                                <p class="truncate font-semibold text-gray-500 mt-0.5">{{ $parentEmail ?: 'Email not provided' }}</p>
                            </div>
                            <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-2.5">
                                <p class="text-[9px] font-black uppercase text-rose-600">Emergency Contact</p>
                                <p class="truncate font-extrabold text-gray-800 mt-0.5">{{ $emergencyName ?: 'Not provided' }}</p>
                                <p class="truncate font-semibold text-gray-500 mt-0.5">{{ $emergencyPhone ?: 'Phone not provided' }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            
            <div class="student-modal-actions mt-5">
                <button type="button" class="student-outline-btn" @click="idModalOpen = false">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
