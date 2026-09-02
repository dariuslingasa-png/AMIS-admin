@php
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
        if (str_contains($subjectLower, 'circle time') || str_contains($subjectLower, 'circle')) {
            return ['bg' => '#f0fdfa', 'border' => '#99f6e4', 'text' => '#134e4a', 'badge' => '#0f766e'];
        }
        if (str_contains($subjectLower, 'meeting time') || str_contains($subjectLower, 'wrap-up')) {
            return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'badge' => '#475569'];
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
@endphp

@if($hasSchedule && !empty($matrix))
    <!-- DESKTOP TIMETABLE GRID -->
    @include('student.schedule.partials._desktop-grid')
    
    <!-- MOBILE TIMELINE VIEW -->
    <div class="md:hidden mt-4">
        @include('student.schedule.partials._mobile-timeline')
    </div>
@else
    <!-- EMPTY STATE -->
    <div class="s-empty-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 4rem 2rem; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #ecfdf5; border: 1.5px solid #a7f3d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
            <i data-lucide="calendar-x" style="width: 28px; height: 28px; color: #059669;"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem;">No Official Class Schedule Available</h3>
        <p style="font-size: 14px; font-weight: 500; color: #64748b; margin: 0 auto; max-width: 500px;">
            No official class schedule is currently available for your section. Please check again later.
        </p>
    </div>
@endif
