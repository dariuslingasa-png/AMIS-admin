<x-student-layout title="Schedule">
@php
    $days = [
        'Sunday' => [],
        'Monday' => [],
        'Tuesday' => [],
        'Wednesday' => [],
        'Thursday' => [],
        'Friday' => [],
    ];

    // Group schedules by day
    foreach ($schedules as $s) {
        $day = $s->day;
        if (isset($days[$day])) {
            $days[$day][] = [
                'subject' => $s,
                'time' => date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time)),
            ];
        }
    }

    // Unscheduled are subjects that have NO class schedules
    $scheduledSubjectNames = $schedules->pluck('subject_name')->unique()->toArray();
    $unscheduled = [];
    foreach ($subjects as $subj) {
        if (!in_array($subj->subject_name, $scheduledSubjectNames)) {
            $unscheduled[] = [
                'subject' => $subj,
                'time' => 'To Be Announced',
            ];
        }
    }

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
        if (str_contains($subjectLower, 'arabic') || str_contains($subjectLower, 'qur') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf')) { return 'book'; }
        if (str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict')) { return 'monitor'; }
        if (str_contains($subjectLower, 'pe') || str_contains($subjectLower, 'physical')) { return 'activity'; }
        if (str_contains($subjectLower, 'art') || str_contains($subjectLower, 'drawing')) { return 'palette'; }
        return 'file-text';
    };

    $teacherName = function ($subject): string {
        $name = $subject->teacher_name ?: 'To Be Assigned';
        if ($name === 'To Be Assigned') {
            return $name;
        }
        $nameTrimmed = trim($name);
        $nameLower = strtolower($nameTrimmed);
        
        $titles = ['tchr', 'teacher', 'ust', 'ustadz', 'ustadh', 'ustadha', 'alim', 'alima', 'alimah'];
        
        $hasTitle = false;
        foreach ($titles as $title) {
            if (str_starts_with($nameLower, $title)) {
                $hasTitle = true;
                break;
            }
        }
        
        if ($hasTitle) {
            if (str_starts_with($nameLower, 'teacher ')) {
                return 'Tchr. ' . ucwords(strtolower(trim(substr($nameTrimmed, 8))));
            }
            return ucwords(strtolower($nameTrimmed));
        }
        
        return 'Tchr. ' . ucwords(strtolower($nameTrimmed));
    };

    foreach ($days as $day => $classes) {
        usort($classes, fn ($a, $b) => $timeSortValue($a['time']) <=> $timeSortValue($b['time']));
        $days[$day] = $classes;
    }

    $scheduledCount = $schedules->count();
    $todayName = now()->format('l');

    // Build the Grid Matrix for the Calendar Timetable UI
    $timeSlots = [];
    foreach ($schedules as $s) {
        $timeKey = substr($s->start_time, 0, 5) . '-' . substr($s->end_time, 0, 5);
        $timeSlots[$timeKey] = [
            'start' => $s->start_time,
            'end' => $s->end_time,
            'label' => date('g:i A', strtotime($s->start_time)) . ' - ' . date('g:i A', strtotime($s->end_time)),
        ];
    }
    // Sort time slots chronologically
    uasort($timeSlots, fn($a, $b) => strcmp($a['start'], $b['start']));

    $matrix = [];
    foreach ($timeSlots as $timeKey => $slot) {
        $matrix[$timeKey] = [
            'slot' => $slot,
            'days' => [
                'Sunday' => null,
                'Monday' => null,
                'Tuesday' => null,
                'Wednesday' => null,
                'Thursday' => null,
            ],
        ];
        foreach ($schedules as $s) {
            $sKey = substr($s->start_time, 0, 5) . '-' . substr($s->end_time, 0, 5);
            if ($sKey === $timeKey) {
                $matrix[$timeKey]['days'][$s->day] = $s;
            }
        }
    }

    // Default active day for mobile/tab selector
    $initialDay = $todayName;
    if (!in_array($initialDay, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'])) {
        $initialDay = 'Sunday';
    }

    // Color mapper based on subject categories for rich aesthetics
    $getSubjectStyle = function ($subjectName) {
        $subjectLower = mb_strtolower((string) $subjectName);
        if (str_contains($subjectLower, 'math')) {
            return ['bg' => '#e0e7ff', 'border' => '#c7d2fe', 'text' => '#312e81', 'icon_bg' => '#c7d2fe', 'icon_color' => '#312e81'];
        }
        if (str_contains($subjectLower, 'science')) {
            return ['bg' => '#f3e8ff', 'border' => '#e9d5ff', 'text' => '#581c87', 'icon_bg' => '#e9d5ff', 'icon_color' => '#581c87'];
        }
        if (str_contains($subjectLower, 'english') || str_contains($subjectLower, 'reading')) {
            return ['bg' => '#e0f2fe', 'border' => '#bae6fd', 'text' => '#0c4a6e', 'icon_bg' => '#bae6fd', 'icon_color' => '#0c4a6e'];
        }
        if (str_contains($subjectLower, 'arabic') || str_contains($subjectLower, 'qur') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf')) {
            return ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#14532d', 'icon_bg' => '#bbf7d0', 'icon_color' => '#14532d'];
        }
        if (str_contains($subjectLower, 'filipino')) {
            return ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#78350f', 'icon_bg' => '#fde68a', 'icon_color' => '#78350f'];
        }
        if (str_contains($subjectLower, 'mapeh')) {
            return ['bg' => '#ffe4e6', 'border' => '#fecdd3', 'text' => '#881337', 'icon_bg' => '#fecdd3', 'icon_color' => '#881337'];
        }
        if (str_contains($subjectLower, 'ap') || str_contains($subjectLower, 'araling')) {
            return ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12', 'icon_bg' => '#fed7aa', 'icon_color' => '#7c2d12'];
        }
        if (str_contains($subjectLower, 'tle') || str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict')) {
            return ['bg' => '#f0fdfa', 'border' => '#ccfbf1', 'text' => '#115e59', 'icon_bg' => '#ccfbf1', 'icon_color' => '#115e59'];
        }
        return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'icon_bg' => '#e2e8f0', 'icon_color' => '#475569'];
    };
@endphp

@once
<style>
    /* Main Timetable Switcher styling */
    .sched-tab-btn {
        border: none !important;
        border-radius: 10px !important;
        padding: 0.5rem 1.25rem !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        line-height: 22px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: transparent !important;
        color: #64748b !important;
    }
    .sched-tab-btn.active {
        background: white !important;
        color: #0d9488 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
    }

    /* Desktop Calendar Grid UI */
    .calendar-wrapper {
        width: 100%;
        overflow-x: auto;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 28px;
        padding: 1.5rem;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: 8rem repeat(5, minmax(180px, 1fr));
        gap: 0.65rem;
        min-width: 1000px;
    }
    .calendar-grid-header {
        font-size: 18px !important;
        font-weight: 700 !important;
        line-height: 24px !important;
        text-transform: uppercase;
        color: white;
        background: #0d9488;
        padding: 0.85rem 0.5rem;
        text-align: center;
        border-radius: 12px;
        letter-spacing: 0.05em;
    }
    .calendar-time-header {
        background: #1e293b;
    }
    .calendar-grid-row {
        display: contents;
    }
    .calendar-time-block {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 0.5rem;
        font-size: 13px !important;
        font-weight: 500 !important;
        line-height: 18px !important;
        color: #1e293b;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        min-height: 85px;
    }
    .calendar-cell {
        display: flex;
        flex-direction: column;
    }
    
    /* Calendar Class Card */
    .calendar-class-card {
        flex: 1;
        display: flex;
        flex-direction: row;
        gap: 0.65rem;
        align-items: center;
        background: white;
        border: 1.5px solid #e2e8f0;
        border-radius: 16px;
        padding: 0.65rem;
        min-height: 85px;
        position: relative;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .calendar-class-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.04);
    }
    
    /* Live Glow Alert */
    .class-live {
        animation: pulse-border 2s infinite alternate;
        position: relative;
    }
    .class-live::before {
        content: '';
        position: absolute;
        top: 6px;
        right: 6px;
        width: 8px;
        height: 8px;
        background-color: #ef4444;
        border-radius: 50%;
        animation: pulse-live 1.8s infinite;
        z-index: 10;
    }
    
    /* Completed State */
    .class-completed {
        opacity: 0.5;
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        color: #64748b !important;
        box-shadow: none !important;
    }
    .class-completed .icon-small {
        background: #cbd5e1 !important;
        color: #64748b !important;
        border-color: #94a3b8 !important;
    }
    
    /* Special/Break Slots (Assembly, Recess, Homeroom, Transition) */
    .class-special {
        background: #f1f5f9 !important;
        border: 1.5px dashed #cbd5e1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 16px !important;
        box-shadow: none !important;
        min-height: 85px !important;
    }
    .class-special:hover {
        transform: none !important;
        box-shadow: none !important;
    }
    .class-special-title {
        font-size: 0.85rem !important;
        font-weight: 800;
        color: #64748b;
        margin: 0;
    }

    /* Mobile Timeline View */
    .mobile-timeline {
        display: none;
    }
    .mobile-timeline-item {
        display: grid;
        grid-template-columns: 5.5rem minmax(0, 1fr);
        gap: 1rem;
        align-items: stretch;
    }
    .mobile-time {
        display: flex;
        flex-direction: column;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 850;
        color: #64748b;
        text-align: right;
        padding-right: 0.5rem;
        border-right: 2px solid #e2e8f0;
        position: relative;
    }
    .mobile-time::after {
        content: '';
        position: absolute;
        right: -5px;
        top: calc(50% - 4px);
        width: 8px;
        height: 8px;
        background: #cbd5e1;
        border-radius: 50%;
    }
    .mobile-time.live-time::after {
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    
    /* Responsive Switches */
    @media (max-width: 1023px) {
        .calendar-wrapper {
            display: none;
        }
        .mobile-timeline {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
    }
    
    /* Animations */
    @keyframes pulse-live {
        0% { transform: scale(0.9); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.4; }
        100% { transform: scale(0.9); opacity: 1; }
    }
    @keyframes pulse-border {
        from { box-shadow: 0 0 4px rgba(16,185,129,0.1); }
        to { box-shadow: 0 0 12px rgba(16,185,129,0.3); }
    }
    
    /* Style tab switcher buttons */
    .day-tab-btn {
        border: none !important;
        border-radius: 12px !important;
        padding: 0.65rem 1.15rem !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        line-height: 22px !important;
        cursor: pointer !important;
        transition: all 0.15s !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.375rem !important;
        background: transparent !important;
        color: #64748b !important;
    }
    .day-tab-btn.active {
        background: #0d9488 !important;
        color: white !important;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.15) !important;
    }
    
    /* Fullscreen Calendar styling */
    .calendar-wrapper.is-fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        max-width: none !important;
        max-height: none !important;
        z-index: 9999 !important;
        background: #ffffff !important;
        border-radius: 0 !important;
        padding: 2rem !important;
        overflow: auto !important;
        box-shadow: none !important;
    }
</style>
@once
<script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
@endonce
@endonce

<div class="space-y-6" x-data="{ currentTab: 'grid', activeDay: '{{ $initialDay }}', previewPhoto: null, isFullscreen: false }" x-init="window.lucide && window.lucide.createIcons()">
    <!-- Header card -->
    <div class="s-quick-actions-card" style="padding: 1.75rem; background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 12px; font-weight: 600; line-height: 16px; color: #0d9488; background: #f0fdfa; border: 1px solid #ccfbf1; padding: 0.25rem 0.65rem; border-radius: 999px;">
                <i data-lucide="calendar-days" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                <span>Weekly Timetable</span>
            </div>
            <h2 style="font-size: 30px; font-weight: 700; line-height: 38px; color: #0f172a; margin: 0.5rem 0 0.25rem;">Student Weekly Calendar</h2>
            <p style="font-size: 15px; font-weight: 400; line-height: 24px; color: #475569; margin: 0;">A clean weekly grid calendar of your classes, advisory logs, and live sessions.</p>
        </div>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap; flex-shrink: 0;">
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0.75rem 1.25rem; text-align: center; min-width: 130px;">
                <p style="font-size: 12px; font-weight: 600; line-height: 16px; color: #64748b; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Enrolled Subjects</p>
                <p style="font-size: 30px; font-weight: 700; line-height: 38px; color: #0f172a; margin: 0; margin-top: 0.25rem; line-height: 1;">{{ $subjects->count() }}</p>
            </div>

            <div style="background: #f0fdfa; border: 1.5px solid #ccfbf1; border-radius: 14px; padding: 0.75rem 1.25rem; text-align: center; min-width: 130px;">
                <p style="font-size: 12px; font-weight: 600; line-height: 16px; color: #0f766e; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Class Section</p>
                <p style="font-size: 18px; font-weight: 700; line-height: 24px; color: #0d9488; margin: 0; margin-top: 0.25rem; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $section ? $section->section_title : 'General' }}">
                    {{ $section ? $section->official_name : 'General' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Tab switcher bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 0.75rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; background: #e2e8f0; padding: 0.25rem; border-radius: 12px; gap: 0.25rem;">
            <button type="button" @click="currentTab = 'grid'; $nextTick(() => window.lucide && window.lucide.createIcons())" class="sched-tab-btn" :class="currentTab === 'grid' ? 'active' : ''">
                <i data-lucide="calendar-range" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                <span>Timetable Calendar</span>
            </button>

            <button type="button" @click="currentTab = 'list'; $nextTick(() => window.lucide && window.lucide.createIcons())" class="sched-tab-btn" :class="currentTab === 'list' ? 'active' : ''">
                <i data-lucide="list" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                <span>Subject List</span>
            </button>
        </div>

        <div style="font-size: 12px; font-weight: 600; line-height: 16px; color: #64748b;">
            School Year {{ $student->school_year }}
        </div>
    </div>

    <div>
        <!-- Calendar Grid/Timetable Tab -->
        <div x-show="currentTab === 'grid'" class="space-y-4">
            @if($schedules->isNotEmpty())
                <!-- DESKTOP TIMETABLE VIEW -->
                <div :class="isFullscreen ? 'calendar-wrapper is-fullscreen' : 'calendar-wrapper'">
                     <!-- Fullscreen Toggle Button -->
                     <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                         <button type="button" @click="isFullscreen = !isFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                                 class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 px-3.5 py-2 rounded-xl transition cursor-pointer shadow-3xs"
                                 style="font-size: 15px; font-weight: 600; line-height: 22px;">
                             <template x-if="!isFullscreen">
                                 <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                     <i data-lucide="maximize-2" style="width: 14px; height: 14px;"></i> Full Screen
                                 </span>
                             </template>
                             <template x-if="isFullscreen">
                                 <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                                     <i data-lucide="minimize-2" style="width: 14px; height: 14px;"></i> Exit Full Screen
                                 </span>
                             </template>
                         </button>
                     </div>

                    <div class="calendar-grid">
                        <!-- Headers -->
                        <div class="calendar-grid-header calendar-time-header">Time Block</div>
                        <div class="calendar-grid-header">Sunday</div>
                        <div class="calendar-grid-header">Monday</div>
                        <div class="calendar-grid-header">Tuesday</div>
                        <div class="calendar-grid-header">Wednesday</div>
                        <div class="calendar-grid-header">Thursday</div>

                        <!-- Matrix Rows -->
                        @foreach($matrix as $timeKey => $row)
                            @php
                                $startMin = $row['slot']['start'];
                                $endMin = $row['slot']['end'];
                                $duration = (strtotime($endMin) - strtotime($startMin)) / 60;

                                // Check if all 5 days have the exact same subject
                                $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                                $firstDayClass = $row['days'][$daysList[0]];
                                $allSame = true;
                                if (!$firstDayClass) {
                                    $allSame = false;
                                } else {
                                    foreach ($daysList as $d) {
                                        $curr = $row['days'][$d];
                                        if (!$curr || $curr->subject_name !== $firstDayClass->subject_name) {
                                            $allSame = false;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <div class="calendar-grid-row">
                                <!-- Time column cell -->
                                <div class="calendar-time-block">
                                    <span style="font-size: 13px; font-weight: 500; line-height: 18px;">{{ date('g:i A', strtotime($row['slot']['start'])) }}</span>
                                    <span style="font-size: 12px; font-weight: 600; line-height: 16px; color: #64748b; margin: 0.1rem 0;">to</span>
                                    <span style="font-size: 13px; font-weight: 500; line-height: 18px;">{{ date('g:i A', strtotime($row['slot']['end'])) }}</span>
                                </div>

                                @if($allSame)
                                    <!-- MERGED CELL SPANNING 5 COLUMNS -->
                                    @php
                                        $s = $firstDayClass;
                                        
                                        // Calculate live/completed status
                                        $classState = 'upcoming';
                                        $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                                        $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                                        if ($startTime !== false && $endTime !== false) {
                                            $now = time();
                                            // Check if today matches any school day since it's on all days
                                            if (in_array($todayName, $daysList)) {
                                                if ($now > $endTime) {
                                                    $classState = 'completed';
                                                } elseif ($now >= $startTime && $now <= $endTime) {
                                                    $classState = 'live';
                                                }
                                            }
                                        }

                                        $isSpecialWord = str_contains(strtolower($s->subject_name), 'transition') || 
                                                         str_contains(strtolower($s->subject_name), 'recess') || 
                                                         str_contains(strtolower($s->subject_name), 'break') ||
                                                         str_contains(strtolower($s->subject_name), 'general assembly');
                                    @endphp
                                    <div class="calendar-cell" style="grid-column: span 5;">
                                        @if($isSpecialWord)
                                            <div class="calendar-class-card class-special" style="width: 100%;" title="{{ $s->subject_name }}">
                                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                                    <i data-lucide="coffee" style="width: 16px; height: 16px; color: #64748b; flex-shrink: 0;"></i>
                                                    <p class="class-special-title" style="font-size: 18px !important; font-weight: 700 !important; line-height: 24px !important;">{{ $s->subject_name }}</p>
                                                </div>
                                            </div>
                                        @else
                                            @php
                                                $currentTeacherName = $teacherName($s);
                                                $photoUrl = !empty($s->teacher_photo) ? asset($s->teacher_photo) : null;
                                                $style = $getSubjectStyle($s->subject_name);
                                            @endphp
                                            <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}"
                                                 style="background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.75rem; align-items: center; padding: 0.65rem 1rem;">
                                                
                                                <!-- Left: Teacher photo in squircle (border-radius: 12px) -->
                                                <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher' }" @endif
                                                     style="width: 44px; height: 44px; border-radius: 10px; background: white; border: 1px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;"
                                                     title="Click to view teacher photo">
                                                    @if($photoUrl)
                                                        <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                                    @else
                                                        @php
                                                            $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                                 ->take(2)
                                                                ->implode('');
                                                        @endphp
                                                        <span style="font-size: 0.75rem; font-weight: 850; color: {{ $style['text'] }} !important;">{{ $initials ?: '?' }}</span>
                                                    @endif
                                                </div>

                                                <!-- Right: Details -->
                                                <div style="flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between;">
                                                    <div>
                                                        <h4 style="font-size: 18px; font-weight: 700; line-height: 24px; color: {{ $style['text'] }} !important; margin: 0;">
                                                            {{ $s->subject_name }}
                                                        </h4>
                                                        <p style="font-size: 14px; font-weight: 500; line-height: 20px; color: {{ $style['text'] }} !important; opacity: 0.85; margin: 0.05rem 0 0;">
                                                            {{ $currentTeacherName }}
                                                        </p>
                                                    </div>

                                                    @if($s->ms_channel_id)
                                                         @if($s->is_joinable)
                                                             <a href="{{ $s->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $s->team_url ?? 'https://teams.microsoft.com/' }}');" style="width: 24px; height: 24px; border-radius: 50%; background: {{ $style['icon_color'] }}; display: inline-flex; align-items: center; justify-content: center; color: white; text-decoration: none; cursor: pointer;" title="Join Class">
                                                                 <i data-lucide="video" style="width: 11px; height: 11px;"></i>
                                                             </a>
                                                         @else
                                                             <button type="button" disabled style="width: 24px; height: 24px; border-radius: 50%; background: #cbd5e1; border: none; display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: not-allowed;" title="{{ $s->membership_status_label }}">
                                                                 <i data-lucide="lock" style="width: 11px; height: 11px;"></i>
                                                             </button>
                                                         @endif
                                                     @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- INDIVIDUAL COLUMN CELLS -->
                                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $day)
                                        @php
                                            $s = $row['days'][$day];
                                            
                                            // Calculate live/completed status
                                            $classState = 'upcoming';
                                            if ($s) {
                                                $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                                                $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                                                if ($startTime !== false && $endTime !== false) {
                                                    $now = time();
                                                    if ($day === $todayName) {
                                                        if ($now > $endTime) {
                                                            $classState = 'completed';
                                                        } elseif ($now >= $startTime && $now <= $endTime) {
                                                            $classState = 'live';
                                                        }
                                                    }
                                                }
                                            }

                                            $isSpecialWord = $s && (
                                                str_contains(strtolower($s->subject_name), 'transition') || 
                                                str_contains(strtolower($s->subject_name), 'recess') || 
                                                str_contains(strtolower($s->subject_name), 'break') ||
                                                str_contains(strtolower($s->subject_name), 'general assembly')
                                            );
                                        @endphp

                                        <div class="calendar-cell">
                                            @if($s)
                                                @if($isSpecialWord)
                                                    <div class="calendar-class-card class-special" title="{{ $s->subject_name }}">
                                                        <div style="display: flex; align-items: center; gap: 0.35rem; justify-content: center; text-align: center;">
                                                            <i data-lucide="coffee" style="width: 14px; height: 14px; color: #94a3b8; flex-shrink: 0;"></i>
                                                            <p class="class-special-title" style="font-size: 18px !important; font-weight: 700 !important; line-height: 24px !important;">{{ $s->subject_name }}</p>
                                                        </div>
                                                    </div>
                                                @else
                                                    @php
                                                        $currentTeacherName = $teacherName($s);
                                                        $photoUrl = !empty($s->teacher_photo) ? asset($s->teacher_photo) : null;
                                                        $style = $getSubjectStyle($s->subject_name);
                                                    @endphp
                                                    <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}"
                                                         style="background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.5rem; align-items: center;">
                                                        
                                                        <!-- Left: Teacher photo in circle -->
                                                        <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher' }" @endif
                                                             style="width: 44px; height: 44px; border-radius: 50%; background: white; border: 1.5px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;"
                                                             title="Click to view teacher photo">
                                                            @if($photoUrl)
                                                                <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                            @else
                                                                @php
                                                                    $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                                        ->take(2)
                                                                        ->implode('');
                                                                @endphp
                                                                <span style="font-size: 0.75rem; font-weight: 850; color: {{ $style['text'] }} !important; display: flex; align-items: center; justify-content: center; text-align: center; width: 100%; height: 100%;">{{ $initials ?: '?' }}</span>
                                                            @endif
                                                        </div>

                                                        <!-- Right: Subject details -->
                                                        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
                                                            <div>
                                                                <h4 style="font-size: 18px; font-weight: 700; line-height: 24px; color: {{ $style['text'] }} !important; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $s->subject_name }}">
                                                                    {{ $s->subject_name }}
                                                                </h4>
                                                                <p style="font-size: 14px; font-weight: 500; line-height: 20px; color: {{ $style['text'] }} !important; opacity: 0.85; margin: 0.05rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $currentTeacherName }}">
                                                                    {{ $currentTeacherName }}
                                                                </p>
                                                            </div>
                                                            <div style="display: flex; align-items: center; justify-content: flex-end; margin-top: 0.15rem;">
                                                                 @if($s->ms_channel_id)
                                                                    @if($s->is_joinable)
                                                                        <a href="{{ $s->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $s->team_url ?? 'https://teams.microsoft.com/' }}');" style="width: 18px; height: 18px; border-radius: 50%; background: {{ $style['icon_color'] }}; display: inline-flex; align-items: center; justify-content: center; color: white; transition: background 0.15s; text-decoration: none; cursor: pointer;" title="Join Class">
                                                                            <i data-lucide="video" style="width: 8px; height: 8px;"></i>
                                                                        </a>
                                                                    @else
                                                                        <button type="button" disabled style="width: 18px; height: 18px; border-radius: 50%; background: #cbd5e1; border: none; display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: not-allowed;" title="{{ $s->membership_status_label }}">
                                                                            <i data-lucide="lock" style="width: 8px; height: 8px;"></i>
                                                                        </button>
                                                                    @endif
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div style="flex: 1; border: 1px dashed #cbd5e1; border-radius: 16px; background: #fafbfc; min-height: 85px;"></div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- MOBILE/TABLET TIMETABLE VIEW (Tab Switching + Timeline list) -->
                <div class="mobile-timeline space-y-4">
                    <!-- Day selection tabs -->
                    <div style="display: flex; overflow-x: auto; background: #f1f5f9; padding: 0.35rem; border-radius: 16px; gap: 0.25rem;">
                        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
                            <button type="button" 
                                    @click="activeDay = '{{ $dayName }}'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                                    :class="activeDay === '{{ $dayName }}' ? 'day-tab-btn active' : 'day-tab-btn'"
                                    style="flex: 1; text-align: center; white-space: nowrap;">
                                {{ substr($dayName, 0, 3) }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Daily Classes Timeline -->
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
                        <div x-show="activeDay === '{{ $dayName }}'" class="space-y-4 fade-up">
                            @php
                                $dayClasses = $days[$dayName] ?? [];
                            @endphp

                            @forelse($dayClasses as $cls)
                                @php
                                    $s = $cls['subject'];
                                    
                                    // Calculate live/completed status
                                    $classState = 'upcoming';
                                    $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                                    $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                                    if ($startTime !== false && $endTime !== false) {
                                        $now = time();
                                        if ($dayName === $todayName) {
                                            if ($now > $endTime) {
                                                $classState = 'completed';
                                            } elseif ($now >= $startTime && $now <= $endTime) {
                                                $classState = 'live';
                                            }
                                        }
                                    }

                                    $isSpecialWord = (
                                        str_contains(strtolower($s->subject_name), 'transition') || 
                                        str_contains(strtolower($s->subject_name), 'recess') || 
                                        str_contains(strtolower($s->subject_name), 'break') ||
                                        str_contains(strtolower($s->subject_name), 'general assembly')
                                    );
                                @endphp

                                <div class="mobile-timeline-item">
                                    <!-- Left Time slot -->
                                    <div class="mobile-time {{ $classState === 'live' ? 'live-time' : '' }}">
                                        <span style="color: #0f172a; font-weight: 900;">{{ date('g:i A', strtotime($s->start_time)) }}</span>
                                        <span style="font-size: 0.6rem; color: #94a3b8; font-weight: 700; margin: 0.05rem 0;">to</span>
                                        <span style="color: #64748b; font-weight: 750;">{{ date('g:i A', strtotime($s->end_time)) }}</span>
                                    </div>

                                    <!-- Right Content card -->
                                    <div style="flex: 1; min-width: 0;">
                                        @if($isSpecialWord)
                                            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 0.85rem; display: flex; align-items: center; justify-content: center; height: 100%;">
                                                <p style="font-size: 0.8rem; font-weight: 850; color: #64748b; margin: 0;">{{ $s->subject_name }}</p>
                                            </div>
                                        @else
                                            @php
                                                $currentTeacherName = $teacherName($s);
                                                $photoUrl = !empty($s->teacher_photo) ? asset($s->teacher_photo) : null;
                                                $style = $getSubjectStyle($s->subject_name);
                                            @endphp
                                            <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}" 
                                                 style="min-height: 85px; background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.5rem; align-items: center;">
                                                
                                                <!-- Left: Teacher photo in circle -->
                                                <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher' }" @endif
                                                     style="width: 38px; height: 38px; border-radius: 50%; background: white; border: 1.5px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;">
                                                    @if($photoUrl)
                                                        <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                                    @else
                                                        @php
                                                            $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                                ->take(2)
                                                                ->implode('');
                                                        @endphp
                                                        <span style="font-size: 0.7rem; font-weight: 850; color: {{ $style['text'] }} !important; display: flex; align-items: center; justify-content: center; text-align: center; width: 100%; height: 100%;">{{ $initials ?: '?' }}</span>
                                                    @endif
                                                </div>

                                                <!-- Right: Subject details -->
                                                <div style="flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between;">
                                                    <div>
                                                        <h4 style="font-size: 0.9rem; font-weight: 850; color: {{ $style['text'] }} !important; margin: 0; line-height: 1.3;">
                                                            {{ $s->subject_name }}
                                                        </h4>
                                                        <p style="font-size: 0.75rem; font-weight: 750; color: {{ $style['text'] }} !important; opacity: 0.85; margin: 0.2rem 0 0;">
                                                            {{ $currentTeacherName }}
                                                        </p>
                                                    </div>

                                                    @if($s->ms_channel_id)
                                                         @if($s->is_joinable)
                                                             <a href="{{ $s->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $s->team_url ?? 'https://teams.microsoft.com/' }}');" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 900; color: white; background: {{ $style['icon_color'] }}; padding: 0.35rem 0.75rem; border-radius: 10px; text-decoration: none; cursor: pointer;"
                                                                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                                                 <i data-lucide="video" style="width: 12px; height: 12px;"></i>
                                                                 <span>Join Room</span>
                                                             </a>
                                                         @else
                                                             <button type="button" disabled style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 900; color: #94a3b8; background: #cbd5e1; border: none; padding: 0.35rem 0.75rem; border-radius: 10px; cursor: not-allowed; opacity: 0.85;"
                                                                title="{{ $s->membership_status_label }}">
                                                                 <i data-lucide="lock" style="width: 12px; height: 12px;"></i>
                                                                 <span>Join Room</span>
                                                             </button>
                                                         @endif
                                                     @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div style="display: flex; min-height: 8rem; align-items: center; justify-content: center; border-radius: 16px; border: 1.5px dashed #e2e8f0; background: #f8fafc; font-size: 0.8rem; font-weight: 750; color: #94a3b8; text-align: center;">
                                    No classes scheduled for {{ $dayName }}
                                </div>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @else
                <div class="s-empty-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 4rem 2rem;">
                    <div class="s-empty-icon-wrapper">
                        <i data-lucide="calendar" style="width: 32px; height: 32px; color: #059669; flex-shrink: 0;"></i>
                    </div>
                    <h3 class="s-empty-title">Your Schedule is Empty</h3>
                    <p class="s-empty-text">No registered subjects or sections were found for your student profile.</p>
                </div>
            @endif
        </div>

        <!-- Subject List Tab -->
        <div x-show="currentTab === 'list'" class="space-y-6">
            @if($subjects->isNotEmpty())
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1.5rem;">
                    @foreach($subjects as $subj)
                        @php
                            $currentTeacherName = $teacherName($subj);
                            $listPhotoUrl = !empty($subj->teacher_photo) ? asset($subj->teacher_photo) : null;
                        @endphp
                        <article class="s-quick-actions-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; min-height: 230px; padding: 0;">
                            <div class="teacher-strip" style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; height: 110px; display: grid; grid-template-columns: 90px 1fr;">
                                <div class="teacher-photo-panel"
                                     @if($listPhotoUrl) @click="previewPhoto = { url: '{{ $listPhotoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher' }" @endif
                                     style="cursor: pointer; display: flex; align-items: center; justify-content: center; background: #ecfdf5; overflow: hidden;"
                                     title="Click to preview profile picture">
                                    @if($listPhotoUrl)
                                        <img src="{{ $listPhotoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        @php
                                            $listInitials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                ->take(2)
                                                ->implode('');
                                        @endphp
                                        <span style="font-size: 1.25rem; font-weight: 850; color: #047857;">{{ $listInitials ?: '?' }}</span>
                                    @endif
                                </div>

                                <div style="display: flex; align-items: start; gap: 0.75rem; padding: 1rem; min-width: 0;">
                                    <span class="subject-icon-box" style="height: 2.25rem; width: 2.25rem; border-radius: 0.75rem;">
                                        <i data-lucide="{{ $subjectIcon($subj->subject_name) }}" style="width: 18px; height: 18px;"></i>
                                    </span>

                                    <div style="min-width: 0; flex: 1;">
                                        <h4 style="font-size: 0.95rem; font-weight: 850; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $subj->subject_name }}">{{ $subj->subject_name }}</h4>
                                         <p style="font-size: 0.65rem; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin: 0.5rem 0 0; letter-spacing: 0.05em;">MS Team & Status</p>
                                         <p style="font-size: 0.8rem; font-weight: 750; color: #475569; margin: 0.15rem 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $subj->ms_team_name }}">{{ $subj->ms_team_name }}</p>
                                         <div style="margin-top: 0.25rem;">
                                             @if($subj->membership_status === 'enrolled')
                                                 <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;">
                                                     <span style="width:4px;height:4px;background:#16a34a;border-radius:50%;"></span>Enrolled
                                                 </span>
                                             @elseif($subj->membership_status === 'not_enrolled')
                                                 <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Not yet enrolled in Microsoft Teams. Click 'Sync MS Teams' on dashboard to retry.">
                                                     <span style="width:4px;height:4px;background:#ea580c;border-radius:50%;"></span>Not Enrolled
                                                 </span>
                                             @else
                                                 <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fca5a5;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Section has no Microsoft Team ID.">
                                                     <span style="width:4px;height:4px;background:#dc2626;border-radius:50%;"></span>No Team ID
                                                 </span>
                                             @endif
                                         </div>
                                     </div>
                                 </div>
                             </div>

                             <div style="padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; flex: 1;">
                                 <div>
                                     <p style="font-size: 0.65rem; font-weight: 850; color: #94a3b8; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Weekly Schedule</p>
                                     <div style="margin-top: 0.25rem; display: inline-flex; max-width: 100%; align-items: center; gap: 0.25rem; border-radius: 999px; border: 1px solid #ccfbf1; background: #f0fdfa; font-size: 0.75rem; font-weight: 850; color: #0f766e; padding: 0.25rem 0.65rem;">
                                         <i data-lucide="clock" style="width: 12px; height: 12px; flex-shrink: 0; color: #0d9488;"></i>
                                         <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $subj->schedule ?: 'To Be Announced' }}</span>
                                     </div>
                                 </div>

                                 <div>
                                     @if($subj->ms_channel_id)
                                         @if($subj->is_joinable)
                                             <a href="{{ $subj->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $subj->team_url ?? 'https://teams.microsoft.com/' }}');" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 900; color: white; background: #5865f2; padding: 0.6rem; border-radius: 12px; text-decoration: none; transition: background 0.15s; cursor: pointer;"
                                                onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#5865f2'">
                                                 <i data-lucide="video" class="w-4 h-4"></i>
                                                 <span>Join Class</span>
                                             </a>
                                         @else
                                             <button type="button" disabled style="display: flex; width: 100%; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 900; color: #94a3b8; background: #cbd5e1; border: none; padding: 0.6rem; border-radius: 12px; cursor: not-allowed; opacity: 0.85;"
                                                title="{{ $subj->membership_status_label }}">
                                                 <i data-lucide="lock" class="w-4 h-4"></i>
                                                 <span>Join Class</span>
                                             </button>
                                         @endif
                                    @else
                                        <div style="text-align: center; padding: 0.5rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 0.75rem; font-weight: 750; color: #94a3b8;">
                                            Live room unavailable
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="s-empty-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 4rem 2rem;">
                    <div class="s-empty-icon-wrapper">
                        <i data-lucide="book-open" style="width: 32px; height: 32px; color: #059669; flex-shrink: 0;"></i>
                    </div>
                    <h3 class="s-empty-title">No Subjects Enrolled</h3>
                    <p class="s-empty-text">No subjects have been registered for your section yet.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Image Preview Modal Overlay -->
    <div x-show="previewPhoto" 
         x-cloak 
         @keydown.escape.window="previewPhoto = null"
         style="position: fixed !important; inset: 0 !important; z-index: 9999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 1.5rem !important; background: rgba(15, 23, 42, 0.75) !important; backdrop-filter: blur(4px) !important;"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
         
         <div @click.outside="previewPhoto = null"
              style="position: relative !important; background: white !important; border-radius: 24px !important; padding: 1.25rem !important; max-width: 420px !important; width: 100% !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; display: flex !important; flex-direction: column !important; gap: 1rem !important; margin: auto !important;"
              x-transition:enter="transition ease-out duration-300 transform"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-200 transform"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-95">
              
              <!-- Modal Header: Title + Close Button -->
              <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 0.25rem;">
                  <h4 style="font-size: 1.05rem; font-weight: 900; color: #0f172a; margin: 0;">Teacher Profile</h4>
                  <button @click="previewPhoto = null" 
                          style="border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.15s;"
                          onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a'"
                          onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                  </button>
              </div>

              <!-- Image -->
              <div style="width: 100%; border-radius: 18px; overflow: hidden; background: #ecfdf5; border: 2px solid #a7f3d0; display: flex; align-items: center; justify-content: center;">
                  <img :src="previewPhoto?.url" :alt="previewPhoto?.name" style="width: 100%; height: auto; max-height: 60vh; object-fit: contain; display: block;">
              </div>

              <!-- Title / Name -->
              <div style="text-align: center; margin-bottom: 0.25rem;">
                  <h3 style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0;" x-text="previewPhoto?.name"></h3>
                  <p style="font-size: 0.8rem; font-weight: 850; color: #059669; margin: 0.25rem 0 0; text-transform: uppercase; letter-spacing: 0.05em;" x-text="previewPhoto?.role"></p>
              </div>
         </div>
    </div>
</div>
</x-student-layout>
