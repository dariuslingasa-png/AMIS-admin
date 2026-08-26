<x-student-layout title="My Subjects">

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

<div class="space-y-6" x-data="{ currentTab: '{{ $todaySubjects->isNotEmpty() ? 'today' : 'all' }}' }">
    
    <!-- 1. Header Card -->
    <div class="portal-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                <i data-lucide="book-open" class="h-4 w-4"></i>
                <span>Enrolled Curriculum</span>
            </div>
            <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">My Registered Subjects</h2>
            <p class="text-xs font-medium text-slate-500">
                {{ $section?->name ?? 'G1-AL-MUNAWWARA' }} · School Year {{ $student?->school_year ?? '2026-2027' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-center">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Subjects</p>
                <p class="font-heading text-xl font-black text-slate-900">{{ $subjects->count() }}</p>
            </div>
        </div>
    </div>

    <!-- 2. Tabs Selector -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
        <button type="button" @click="currentTab = 'today'" 
                :class="currentTab === 'today' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'" 
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition cursor-pointer">
            <i data-lucide="clock" class="h-3.5 w-3.5"></i>
            <span>Today's Classes ({{ $todayName }})</span>
        </button>
        <button type="button" @click="currentTab = 'all'" 
                :class="currentTab === 'all' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'" 
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition cursor-pointer">
            <i data-lucide="layout-grid" class="h-3.5 w-3.5"></i>
            <span>All Subjects ({{ $subjects->count() }})</span>
        </button>
    </div>

    <!-- 3. Tab Contents -->
    <div>
        <!-- Today's Classes Tab -->
        <div x-show="currentTab === 'today'" class="space-y-4">
            @if($todaySubjects->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($todaySubjects as $subject)
                        @php 
                            $icon = $subjectIcon($subject->subject_name);
                        @endphp
                        <div class="portal-card p-5 flex flex-col justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
                                    <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="portal-badge portal-badge-emerald">Enrolled</span>
                                        <span class="text-[11px] font-bold text-slate-500">{{ $subject->schedule ?: 'Schedule TBA' }}</span>
                                    </div>
                                    <h3 class="font-heading text-base font-extrabold text-slate-900 mt-1">
                                        {{ $subject->subject_name }}
                                    </h3>
                                    <p class="text-xs font-semibold text-slate-500 mt-0.5">
                                        Teacher: <span class="text-slate-800">{{ $subject->teacher_name ?: 'Assigned Faculty' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[11px] font-bold text-slate-400">SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}</span>
                                <a href="https://teams.microsoft.com/" target="_blank" 
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-indigo-700">
                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                    <span>Microsoft Teams</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="portal-empty-state">
                    <div class="portal-empty-icon">
                        <i data-lucide="calendar" class="h-6 w-6"></i>
                    </div>
                    <h3 class="font-heading text-base font-bold text-slate-800">No classes scheduled for today</h3>
                    <p class="text-xs text-slate-500 mt-1">Check "All Subjects" to see your full curriculum.</p>
                </div>
            @endif
        </div>

        <!-- All Subjects Grid Tab -->
        <div x-show="currentTab === 'all'" class="space-y-4" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($subjects as $subject)
                    @php 
                        $icon = $subjectIcon($subject->subject_name);
                    @endphp
                    <div class="portal-card p-5 flex flex-col justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="h-11 w-11 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center shrink-0 border border-teal-100">
                                <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}
                                </span>
                                <h3 class="font-heading text-base font-extrabold text-slate-900 mt-0.5">
                                    {{ $subject->subject_name }}
                                </h3>
                                <p class="text-xs font-semibold text-slate-500 mt-1">
                                    Teacher: <span class="text-slate-800">{{ $subject->teacher_name ?: 'Assigned Faculty' }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-600">{{ $subject->schedule ?: 'Schedule TBA' }}</span>
                            <span class="portal-badge portal-badge-emerald">Active</span>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full portal-empty-state">
                        <div class="portal-empty-icon">
                            <i data-lucide="book-open" class="h-6 w-6"></i>
                        </div>
                        <h3 class="font-heading text-base font-bold text-slate-800">No subjects registered</h3>
                        <p class="text-xs text-slate-500 mt-1">Please contact administration for subject assignments.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>

</x-student-layout>
