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
        // Prefer teacher_display from class_schedules (already matched), fall back to teacher_name from section_subjects
        $raw = !empty($subject->teacher_display) ? $subject->teacher_display : ($subject->teacher_name ?? null);
        if (!$raw) return '—';

        $nameTrimmed = trim($raw);
        $nameLower   = strtolower($nameTrimmed);

        // Title → Short prefix + first name only
        $titleMap = [
            'teacher '  => 'Tchr.',
            'tchr. '    => 'Tchr.',
            'tchr '     => 'Tchr.',
            'ustadha '  => 'Ustadha',
            'ustadh '   => 'Ustadh',
            'ustadz '   => 'Ustadz',
            'ust. '     => 'Ust.',
            'ust '      => 'Ust.',
            'alimah '   => 'Alimah',
            'alima '    => 'Alima',
            'alim '     => 'Alim',
        ];

        foreach ($titleMap as $prefix => $shortTitle) {
            if (str_starts_with($nameLower, $prefix)) {
                $rest      = trim(substr($nameTrimmed, strlen($prefix)));
                $firstName = ucfirst(strtolower(explode(' ', $rest)[0]));
                return $shortTitle . ' ' . $firstName;
            }
        }

        // No title — just first name with Tchr. prefix
        $firstName = ucfirst(strtolower(explode(' ', $nameTrimmed)[0]));
        return 'Tchr. ' . $firstName;
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
        if (str_contains($subjectLower, 'quran') || str_contains($subjectLower, 'qur')) {
            return ['bg' => '#f0fdf4', 'border' => '#86efac', 'text' => '#14532d', 'icon_bg' => '#86efac', 'icon_color' => '#14532d'];
        }
        if (str_contains($subjectLower, 'arabic')) {
            return ['bg' => '#ecfdf5', 'border' => '#6ee7b7', 'text' => '#064e3b', 'icon_bg' => '#6ee7b7', 'icon_color' => '#064e3b'];
        }
        if (str_contains($subjectLower, 'hadith') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf')) {
            return ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#166534', 'icon_bg' => '#bbf7d0', 'icon_color' => '#166534'];
        }
        if (str_contains($subjectLower, 'circle time') || str_contains($subjectLower, 'circle')) {
            return ['bg' => '#f0fdfa', 'border' => '#99f6e4', 'text' => '#134e4a', 'icon_bg' => '#99f6e4', 'icon_color' => '#0f766e'];
        }
        if (str_contains($subjectLower, 'ct ') || $subjectLower === 'ct 2' || $subjectLower === 'ct 1' || str_starts_with($subjectLower, 'ct ')) {
            return ['bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#3730a3', 'icon_bg' => '#c7d2fe', 'icon_color' => '#4338ca'];
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

    $getPhotoUrl = function ($teacherPhoto, $teacherKey = null, $teacherName = '') {
        if (empty($teacherKey)) {
            if (!empty($teacherPhoto)) {
                $teacherKey = pathinfo($teacherPhoto, PATHINFO_FILENAME);
                $teacherKey = str_replace('teacher-', '', $teacherKey);
            } elseif (!empty($teacherName)) {
                $cleanName = trim((string)$teacherName);
                while (preg_match('/^(TEACHER|TCHR\.?|UST\.?|USTADZ|USTADH|USTADHA|ALIM|SIR|MA\'AM|MAAM)\s+/i', $cleanName, $matches)) {
                    $cleanName = trim(substr($cleanName, strlen($matches[0])));
                }
                $teacherKey = \Illuminate\Support\Str::slug($cleanName);
            }
        }

        if (empty($teacherKey)) {
            if (empty($teacherPhoto)) return null;
            if (str_starts_with($teacherPhoto, 'http://') || str_starts_with($teacherPhoto, 'https://')) {
                return $teacherPhoto;
            }
            return 'https://admin.amis.edu.ph/' . ltrim($teacherPhoto, '/');
        }

        $adminPath = '/home2/amisdavc/admin.amis.edu.ph';
        if (!file_exists($adminPath)) {
            $adminPath = base_path('../amis_admin');
        }

        $overrides = [];
        $overridesJsonPath = $adminPath . '/storage/app/academic_teacher_overrides.json';
        if (file_exists($overridesJsonPath)) {
            $overrides = json_decode(file_get_contents($overridesJsonPath), true) ?? [];
        }

        $photoPath = $overrides[$teacherKey]['photo'] ?? null;

        if (empty($photoPath)) {
            $possiblePaths = [
                "images/teachers/{$teacherKey}.jpg",
                "images/teachers/teacher-{$teacherKey}.jpg",
                "images/teachers/{$teacherKey}.png",
                "images/teachers/teacher-{$teacherKey}.png",
                "images/teachers/{$teacherKey}.jpeg",
                "images/teachers/teacher-{$teacherKey}.jpeg",
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($adminPath . '/public/' . $path)) {
                    $photoPath = $path;
                    break;
                }
            }
        }

        if ($photoPath) {
            return 'https://admin.amis.edu.ph/' . ltrim($photoPath, '/');
        }

        if (empty($teacherPhoto)) return null;
        if (str_starts_with($teacherPhoto, 'http://') || str_starts_with($teacherPhoto, 'https://')) {
            return $teacherPhoto;
        }
        return 'https://admin.amis.edu.ph/' . ltrim($teacherPhoto, '/');
    };
@endphp

@include('student.schedule.partials._styles')

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
                @include('student.schedule.partials._desktop-grid')
                @include('student.schedule.partials._mobile-timeline')
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

        @include('student.schedule.partials._subject-list')
    </div>

    @include('student.schedule.partials._preview-modal')
</div>
</x-student-layout>
