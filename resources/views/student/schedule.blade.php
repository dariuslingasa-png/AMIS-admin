<x-student-layout title="Class Schedule">
@php
    $scheduledCount = collect($weeklySchedule)->flatten()->where('is_break', false)->count();
    $todayCount = $todayClasses->where('is_break', false)->count();

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

    $getSubjectStyle = function ($subjectName) {
        $subjectLower = mb_strtolower((string) $subjectName);
        if (str_contains($subjectLower, 'math')) {
            return ['bg' => '#e0e7ff', 'border' => '#c7d2fe', 'text' => '#312e81', 'badge' => '#4338ca'];
        }
        if (str_contains($subjectLower, 'science')) {
            return ['bg' => '#f3e8ff', 'border' => '#e9d5ff', 'text' => '#581c87', 'badge' => '#7e22ce'];
        }
        if (str_contains($subjectLower, 'english') || str_contains($subjectLower, 'reading')) {
            return ['bg' => '#e0f2fe', 'border' => '#bae6fd', 'text' => '#0c4a6e', 'badge' => '#0284c7'];
        }
        if (str_contains($subjectLower, 'quran') || str_contains($subjectLower, 'qur')) {
            return ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#064e3b', 'badge' => '#059669'];
        }
        if (str_contains($subjectLower, 'arabic')) {
            return ['bg' => '#ecfdf5', 'border' => '#6ee7b7', 'text' => '#064e3b', 'badge' => '#047857'];
        }
        if (str_contains($subjectLower, 'hadith') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf')) {
            return ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#166534', 'badge' => '#15803d'];
        }
        if (str_contains($subjectLower, 'filipino')) {
            return ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#78350f', 'badge' => '#b45309'];
        }
        if (str_contains($subjectLower, 'mapeh')) {
            return ['bg' => '#ffe4e6', 'border' => '#fecdd3', 'text' => '#881337', 'badge' => '#be123c'];
        }
        if (str_contains($subjectLower, 'ap') || str_contains($subjectLower, 'araling')) {
            return ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12', 'badge' => '#c2410c'];
        }
        if (str_contains($subjectLower, 'tle') || str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict')) {
            return ['bg' => '#f0fdfa', 'border' => '#ccfbf1', 'text' => '#115e59', 'badge' => '#0f766e'];
        }
        if (str_contains($subjectLower, 'assembly') || str_contains($subjectLower, 'transition') || str_contains($subjectLower, 'break') || str_contains($subjectLower, 'recess')) {
            return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#475569', 'badge' => '#64748b'];
        }
        return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'badge' => '#475569'];
    };

    $teacherName = function ($s): string {
        if (is_string($s)) return $s;
        return !empty($s->teacher_display) ? $s->teacher_display : (!empty($s->teacher_name) ? $s->teacher_name : '—');
    };

    $getPhotoUrl = function ($teacherPhoto = null, $teacherKey = null, $teacherName = '') {
        if (empty($teacherKey) && !empty($teacherName)) {
            $cleanName = trim((string)$teacherName);
            while (preg_match('/^(TEACHER|TCHR\.?|UST\.?|USTADZ|USTADH|USTADHA|ALIM|SIR|MA\'AM|MAAM)\s+/i', $cleanName, $matches)) {
                $cleanName = trim(substr($cleanName, strlen($matches[0])));
            }
            $teacherKey = \Illuminate\Support\Str::slug($cleanName);
        }

        if ($teacherKey) {
            $adminPath = '/home2/amisdavc/admin.amis.edu.ph';
            if (!file_exists($adminPath)) {
                $adminPath = base_path('../amis_admin');
            }
            $possiblePaths = [
                "images/teachers/{$teacherKey}.jpg",
                "images/teachers/teacher-{$teacherKey}.jpg",
                "images/teachers/{$teacherKey}.png",
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($adminPath . '/public/' . $path)) {
                    return 'https://admin.amis.edu.ph/' . ltrim($path, '/');
                }
            }
        }
        return null;
    };

    $initialDay = in_array($todayName, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']) ? $todayName : 'Sunday';
@endphp

@include('student.schedule.partials._styles')

<div class="space-y-6" x-data="{ currentTab: 'grid', activeDay: '{{ $initialDay }}', previewPhoto: null, isFullscreen: false }" x-init="window.lucide && window.lucide.createIcons()">

    <!-- TOP SECTION: Student Enrollment & Section Details -->
    <div class="s-quick-actions-card" style="padding: 1.75rem; background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; flex-wrap: wrap;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 12px; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.25rem 0.65rem; border-radius: 999px;">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px; flex-shrink: 0; color: #059669;"></i>
                    <span>Official Class Schedule</span>
                </div>
                <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin: 0.5rem 0 0.25rem; letter-spacing: -0.02em;">Class Schedule</h1>
                <p style="font-size: 14px; font-weight: 500; color: #64748b; margin: 0;">
                    Official timetable synchronized in real-time with the AMIS Schedule Management System.
                </p>
            </div>

            @if(!empty($isTester))
                <div style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 14px; padding: 0.75rem 1rem; max-width: 320px;">
                    <form method="POST" action="{{ route('student.tester-override-section') }}">
                        @csrf
                        <label style="font-size: 11px; font-weight: 800; color: #92400e; text-transform: uppercase; display: block; margin-bottom: 0.35rem;">
                            🧪 Tester Section Switcher
                        </label>
                        <select name="section_id" onchange="this.form.submit()" style="width: 100%; font-size: 12px; font-weight: 700; padding: 0.4rem 0.6rem; border-radius: 8px; border: 1px solid #d97706; background: white; color: #78350f;">
                            <option value="">-- Real Enrolled Section --</option>
                            @foreach($allSections as $sOpt)
                                <option value="{{ $sOpt->id }}" {{ session('tester_override_section_id') == $sOpt->id ? 'selected' : '' }}>
                                    {{ $sOpt->grade_level }} - {{ $sOpt->name }} ({{ $sOpt->shift ?: 'F2F' }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif
        </div>

        <!-- Student Profile Metadata Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9;">
            <!-- Student Name -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 0.85rem 1rem;">
                <p style="font-size: 11px; font-weight: 750; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Student Name</p>
                <p style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0.25rem 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $studentInfo['name'] }}">
                    {{ $studentInfo['name'] }}
                </p>
                <p style="font-size: 11px; font-weight: 600; color: #94a3b8; margin: 0;">LRN: {{ $studentInfo['student_number'] ?? '—' }}</p>
            </div>

            <!-- Grade -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 0.85rem 1rem;">
                <p style="font-size: 11px; font-weight: 750; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Grade</p>
                <p style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0.25rem 0 0;">
                    {{ $studentInfo['grade_level'] }}
                </p>
            </div>

            <!-- Section -->
            <div style="background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 14px; padding: 0.85rem 1rem;">
                <p style="font-size: 11px; font-weight: 750; color: #0f766e; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Section</p>
                <p style="font-size: 16px; font-weight: 800; color: #0d9488; margin: 0.25rem 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $studentInfo['section'] }}">
                    {{ $studentInfo['section'] }}
                </p>
            </div>

            <!-- Modality / Shift -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 0.85rem 1rem;">
                <p style="font-size: 11px; font-weight: 750; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Modality / Shift</p>
                <div style="display: flex; align-items: center; gap: 0.35rem; margin-top: 0.25rem; flex-wrap: wrap;">
                    <span style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ $studentInfo['modality'] }}</span>
                    @if(!empty($studentInfo['shift']) && $studentInfo['shift'] !== 'Face-to-Face')
                        <span style="font-size: 11px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 0.15rem 0.45rem; border-radius: 6px;">{{ $studentInfo['shift'] }}</span>
                    @endif
                </div>
            </div>

            <!-- School Year -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 0.85rem 1rem;">
                <p style="font-size: 11px; font-weight: 750; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">School Year</p>
                <p style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0.25rem 0 0;">
                    {{ $studentInfo['school_year'] }}
                </p>
            </div>
        </div>
    </div>

    @if(!$hasSchedule)
        <!-- EMPTY STATE -->
        <div class="s-empty-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 4rem 2rem; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: #ecfdf5; border: 1.5px solid #a7f3d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
                <i data-lucide="calendar-x" style="width: 32px; height: 32px; color: #059669;"></i>
            </div>
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 0.5rem;">
                No Official Class Schedule Available
            </h3>
            <p style="font-size: 15px; font-weight: 500; color: #64748b; max-width: 520px; margin: 0 auto;">
                No official class schedule is currently available for your section. Please check again later.
            </p>
        </div>
    @else

        <!-- 1. TODAY'S CLASSES SECTION -->
        <div style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669;">
                        <i data-lucide="clock" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.02em;">
                            TODAY'S CLASSES
                        </h2>
                        <p style="font-size: 13px; font-weight: 600; color: #64748b; margin: 0;">
                            {{ \Carbon\Carbon::now('Asia/Manila')->format('l, F j, Y') }}
                        </p>
                    </div>
                </div>

                @if(!$isWeekend && $todayClasses->isNotEmpty())
                    <span style="font-size: 12px; font-weight: 750; color: #0f766e; background: #f0fdfa; border: 1px solid #ccfbf1; padding: 0.35rem 0.75rem; border-radius: 999px;">
                        {{ $todayClasses->where('is_break', false)->count() }} Subject Periods Today
                    </span>
                @endif
            </div>

            @if($isWeekend)
                <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 2rem 1.5rem; text-align: center;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #e0f2fe; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; color: #0284c7;">
                        <i data-lucide="sun" style="width: 24px; height: 24px;"></i>
                    </div>
                    <h4 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem;">Weekend Break</h4>
                    <p style="font-size: 14px; font-weight: 500; color: #64748b; margin: 0;">
                        No classes are scheduled today ({{ $todayName }}). Review your weekly schedule below to prepare for upcoming classes!
                    </p>
                </div>
            @elseif($todayClasses->isEmpty())
                <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 2rem 1.5rem; text-align: center;">
                    <p style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0;">
                        No official classes are scheduled for today ({{ $todayName }}).
                    </p>
                </div>
            @else
                <!-- Today's Classes List Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                    @foreach($todayClasses as $cls)
                        @php
                            $style = $getSubjectStyle($cls->subject_name);
                            $isBreak = $cls->is_break;
                        @endphp
                        <div style="background: {{ $style['bg'] }}; border: 1.5px solid {{ $style['border'] }}; border-radius: 16px; padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.85rem; position: relative; transition: all 0.2s ease;">
                            
                            <!-- Top Row: Day & Time & Status Badge -->
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 12px; font-weight: 800; color: {{ $style['text'] }}; text-transform: uppercase;">
                                    <i data-lucide="calendar" style="width: 13px; height: 13px;"></i>
                                    <span>{{ $cls->day }}</span>
                                </div>

                                <div>
                                    @if($cls->status === 'live')
                                        <span style="font-size: 11px; font-weight: 800; color: white; background: #059669; padding: 0.2rem 0.55rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 0.3rem;">
                                            <span style="width: 6px; height: 6px; border-radius: 50%; background: white; animation: pulse 1.5s infinite;"></span>
                                            IN SESSION
                                        </span>
                                    @elseif($cls->status === 'completed')
                                        <span style="font-size: 11px; font-weight: 750; color: #64748b; background: rgba(255,255,255,0.7); border: 1px solid #cbd5e1; padding: 0.15rem 0.5rem; border-radius: 999px;">
                                            COMPLETED
                                        </span>
                                    @elseif($isBreak)
                                        <span style="font-size: 11px; font-weight: 750; color: #b45309; background: #fef3c7; border: 1px solid #fde68a; padding: 0.15rem 0.5rem; border-radius: 999px;">
                                            BREAK / ASSEMBLY
                                        </span>
                                    @else
                                        <span style="font-size: 11px; font-weight: 750; color: #0284c7; background: rgba(255,255,255,0.7); border: 1px solid #bae6fd; padding: 0.15rem 0.5rem; border-radius: 999px;">
                                            UPCOMING
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Middle: Time & Subject (matching prompt format: Time, Subject, Assigned Teacher) -->
                            <div>
                                <div style="font-size: 15px; font-weight: 850; color: #0f172a; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <i data-lucide="clock-4" style="width: 14px; height: 14px; color: #64748b;"></i>
                                    <span>{{ $cls->time }}</span>
                                </div>

                                <h3 style="font-size: 17px; font-weight: 900; color: {{ $style['text'] }}; margin: 0; text-transform: uppercase; line-height: 1.3;">
                                    {{ $cls->subject_name }}
                                </h3>

                                <p style="font-size: 13px; font-weight: 750; color: {{ $style['text'] }}; opacity: 0.9; margin: 0.35rem 0 0; display: flex; align-items: center; gap: 0.35rem;">
                                    <i data-lucide="user" style="width: 13px; height: 13px;"></i>
                                    <span>{{ $cls->teacher_display ?: '—' }}</span>
                                </p>
                            </div>

                            <!-- Bottom Row: Room & Modality / Shift -->
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; color: {{ $style['text'] }}; opacity: 0.85; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06);">
                                <span>{{ $cls->room }}</span>
                                <span>{{ $cls->modality }} {{ $cls->shift ? '• ' . $cls->shift : '' }}</span>
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 2. WEEKLY CLASS SCHEDULE SECTION -->
        <div style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.02em;">
                        WEEKLY CLASS SCHEDULE
                    </h2>
                    <p style="font-size: 13px; font-weight: 600; color: #64748b; margin: 0;">
                        Complete 5-day academic timetable for {{ $studentInfo['section'] }} (Sunday to Thursday).
                    </p>
                </div>

                <!-- Tab switcher: Timetable Grid vs Day-by-Day List -->
                <div style="display: flex; background: #e2e8f0; padding: 0.25rem; border-radius: 12px; gap: 0.25rem;">
                    <button type="button" @click="currentTab = 'grid'; $nextTick(() => window.lucide && window.lucide.createIcons())" class="sched-tab-btn" :class="currentTab === 'grid' ? 'active' : ''">
                        <i data-lucide="calendar-range" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                        <span>Timetable Grid</span>
                    </button>

                    <button type="button" @click="currentTab = 'list'; $nextTick(() => window.lucide && window.lucide.createIcons())" class="sched-tab-btn" :class="currentTab === 'list' ? 'active' : ''">
                        <i data-lucide="list" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                        <span>Day-by-Day List</span>
                    </button>
                </div>
            </div>

            <!-- View 1: Timetable Grid (Desktop Matrix) -->
            <div x-show="currentTab === 'grid'" class="space-y-4">
                @include('student.schedule.partials._desktop-grid')
            </div>

            <!-- View 2: Day-by-Day List View -->
            <div x-show="currentTab === 'list'" class="space-y-4">
                <!-- Day selection tabs -->
                <div style="display: flex; overflow-x: auto; background: #f1f5f9; padding: 0.35rem; border-radius: 16px; gap: 0.35rem;">
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
                        <button type="button" 
                                @click="activeDay = '{{ $dayName }}'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                                :class="activeDay === '{{ $dayName }}' ? 'day-tab-btn active' : 'day-tab-btn'"
                                style="flex: 1; text-align: center; white-space: nowrap; padding: 0.65rem 1rem; border-radius: 12px; font-size: 14px; font-weight: 750; border: none; cursor: pointer; transition: all 0.15s ease;">
                            {{ $dayName }}
                            <span style="font-size: 11px; opacity: 0.75; font-weight: 600;">
                                ({{ count($weeklySchedule[$dayName] ?? []) }})
                            </span>
                        </button>
                    @endforeach
                </div>

                <!-- Daily list cards -->
                @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
                    <div x-show="activeDay === '{{ $dayName }}'" class="space-y-3">
                        @php
                            $classesForDay = $weeklySchedule[$dayName] ?? [];
                        @endphp

                        @if(empty($classesForDay))
                            <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 2rem; text-align: center;">
                                <p style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0;">
                                    No classes scheduled on {{ $dayName }}.
                                </p>
                            </div>
                        @else
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                                @foreach($classesForDay as $entry)
                                    @php
                                        $style = $getSubjectStyle($entry->subject_name);
                                    @endphp
                                    <div style="background: {{ $style['bg'] }}; border: 1.5px solid {{ $style['border'] }}; border-radius: 16px; padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.85rem;">
                                        <!-- Time & Day -->
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-size: 12px; font-weight: 850; color: {{ $style['text'] }}; text-transform: uppercase;">
                                                {{ $entry->day }}
                                            </span>
                                            <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.3rem;">
                                                <i data-lucide="clock" style="width: 13px; height: 13px; color: #64748b;"></i>
                                                {{ $entry->time }}
                                            </span>
                                        </div>

                                        <!-- Subject & Teacher -->
                                        <div>
                                            <h4 style="font-size: 16px; font-weight: 900; color: {{ $style['text'] }}; text-transform: uppercase; margin: 0; line-height: 1.3;">
                                                {{ $entry->subject_name }}
                                            </h4>
                                            <p style="font-size: 13px; font-weight: 750; color: {{ $style['text'] }}; opacity: 0.9; margin: 0.35rem 0 0; display: flex; align-items: center; gap: 0.35rem;">
                                                <i data-lucide="user" style="width: 13px; height: 13px;"></i>
                                                {{ $entry->teacher_display ?: '—' }}
                                            </p>
                                        </div>

                                        <!-- Room & Modality -->
                                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; color: {{ $style['text'] }}; opacity: 0.85; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06);">
                                            <span>{{ $entry->room }}</span>
                                            <span>{{ $entry->modality }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Always visible Mobile Timeline for smaller devices -->
            <div class="md:hidden mt-4">
                @include('student.schedule.partials._mobile-timeline')
            </div>
        </div>

    @endif

    @include('student.schedule.partials._preview-modal')
</div>
</x-student-layout>
