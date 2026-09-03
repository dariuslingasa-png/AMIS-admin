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
        if (str_contains($subjectLower, 'science') || str_contains($subjectLower, 'sci') || str_contains($subjectLower, 'bio') || str_contains($subjectLower, 'phys')) { return 'flask-conical'; }
        if (str_contains($subjectLower, 'english') || str_contains($subjectLower, 'reading') || str_contains($subjectLower, 'lit')) { return 'book-open'; }
        if (str_contains($subjectLower, 'arabic') || str_contains($subjectLower, 'qur') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf') || str_contains($subjectLower, 'hadith')) { return 'book-text'; }
        if (str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict') || str_contains($subjectLower, 'tle')) { return 'monitor'; }
        if (str_contains($subjectLower, 'pe') || str_contains($subjectLower, 'physical') || str_contains($subjectLower, 'mapeh')) { return 'activity'; }
        if (str_contains($subjectLower, 'art') || str_contains($subjectLower, 'drawing')) { return 'palette'; }
        return 'file-text';
    };

    $getSubjectStyle = function ($subjectName) {
        $s = mb_strtolower(trim((string) $subjectName));
        if (str_contains($s, 'qur') || str_contains($s, 'arab') || str_contains($s, 'shaf') || str_contains($s, 'hadith') || str_contains($s, 'islam')) {
            return ['cat' => 'Islamic & Arabic', 'accent' => '#059669', 'bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#064e3b', 'badge_bg' => '#d1fae5', 'badge_text' => '#065f46'];
        }
        if (str_contains($s, 'math')) {
            return ['cat' => 'Mathematics', 'accent' => '#4f46e5', 'bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#1e1b4b', 'badge_bg' => '#e0e7ff', 'badge_text' => '#3730a3'];
        }
        if (str_contains($s, 'sci') || str_contains($s, 'bio') || str_contains($s, 'phys') || str_contains($s, 'res')) {
            return ['cat' => 'Science', 'accent' => '#9333ea', 'bg' => '#faf5ff', 'border' => '#e9d5ff', 'text' => '#3b0764', 'badge_bg' => '#f3e8ff', 'badge_text' => '#6b21a8'];
        }
        if (str_contains($s, 'eng') || str_contains($s, 'read') || str_contains($s, 'lit') || str_contains($s, 'lang') || $s === 'r & l' || $s === 'lcs' || $s === 'mil') {
            return ['cat' => 'English & Reading', 'accent' => '#0284c7', 'bg' => '#f0f9ff', 'border' => '#bae6fd', 'text' => '#0c4a6e', 'badge_bg' => '#e0f2fe', 'badge_text' => '#0369a1'];
        }
        if (str_contains($s, 'fili') || str_contains($s, 'makabansa') || str_contains($s, 'gmrc') || str_contains($s, 'esp')) {
            return ['cat' => 'Filipino & Values', 'accent' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#78350f', 'badge_bg' => '#fef3c7', 'badge_text' => '#92400e'];
        }
        if (str_contains($s, 'ap') || str_contains($s, 'araling') || str_contains($s, 'soc')) {
            return ['cat' => 'Araling Panlipunan', 'accent' => '#ea580c', 'bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12', 'badge_bg' => '#ffedd5', 'badge_text' => '#c2410c'];
        }
        if (str_contains($s, 'mapeh') || str_contains($s, 'music') || str_contains($s, 'art') || str_contains($s, 'pe') || str_contains($s, 'phys')) {
            return ['cat' => 'MAPEH & Arts', 'accent' => '#e11d48', 'bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#881337', 'badge_bg' => '#ffe4e6', 'badge_text' => '#be123c'];
        }
        if (str_contains($s, 'tle') || str_contains($s, 'comp') || str_contains($s, 'ict') || $s === 'ec') {
            return ['cat' => 'TLE & ICT', 'accent' => '#0d9488', 'bg' => '#f0fdfa', 'border' => '#99f6e4', 'text' => '#134e4a', 'badge_bg' => '#ccfbf1', 'badge_text' => '#0f766e'];
        }
        if (str_contains($s, 'circle') || str_starts_with($s, 'ct ') || $s === 'ct 1' || $s === 'ct 2') {
            return ['cat' => 'Circle Time', 'accent' => '#db2777', 'bg' => '#fdf2f8', 'border' => '#fbcfe8', 'text' => '#831843', 'badge_bg' => '#fce7f3', 'badge_text' => '#be185d'];
        }
        if (str_contains($s, 'meeting')) {
            return ['cat' => 'Meeting Time', 'accent' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a', 'badge_bg' => '#dbeafe', 'badge_text' => '#1d4ed8'];
        }
        if (str_contains($s, 'wrap-up')) {
            return ['cat' => 'Wrap-Up Time', 'accent' => '#ca8a04', 'bg' => '#fefce8', 'border' => '#fef08a', 'text' => '#713f12', 'badge_bg' => '#fef9c3', 'badge_text' => '#854d0e'];
        }
        if (str_contains($s, 'homeroom') || $s === 'hg') {
            return ['cat' => 'Homeroom Guidance', 'accent' => '#0891b2', 'bg' => '#ecfeff', 'border' => '#a5f3fc', 'text' => '#164e63', 'badge_bg' => '#cffafe', 'badge_text' => '#0e7490'];
        }
        return ['cat' => 'General', 'accent' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569'];
    };

    $teacherName = function ($subject): string {
        $raw = is_string($subject) ? $subject : (!empty($subject->teacher_display) ? $subject->teacher_display : ($subject->teacher_name ?? null));
        if (!$raw) return '—';

        $nameTrimmed = trim($raw);
        $nameLower = strtolower($nameTrimmed);

        $titleMap = [
            'teacher '  => 'Tchr.',
            'tchr. '    => 'Tchr.',
            'tchr '     => 'Tchr.',
            'ustadha '  => 'Ustadha',
            'ustadh '   => 'Ust.',
            'ustadz '   => 'Ust.',
            'ust. '     => 'Ust.',
            'ust '      => 'Ust.',
            'alimah '   => 'Alimah',
            'alima '    => 'Alima',
            'alim '     => 'Alim',
            'sir '      => 'Sir',
            'ma\'am '   => 'Ma\'am',
        ];

        foreach ($titleMap as $prefix => $shortTitle) {
            if (str_starts_with($nameLower, $prefix)) {
                $rest = trim(substr($nameTrimmed, strlen($prefix)));
                $firstName = ucfirst(strtolower(explode(' ', $rest)[0]));
                return $shortTitle . ' ' . $firstName;
            }
        }

        $parts = explode(' ', $nameTrimmed);
        return ucfirst(strtolower($parts[0]));
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
