<style>
    /* Minimal overrides for the timetable grid using premium-table base */
    .timetable-grid-wrap {
        max-height: none !important;
        overflow: visible !important;
        border: 1px solid #edf2f7;
        border-radius: .875rem;
    }
    .timetable-grid td {
        border-top: 1px solid #f1f5f9;
        border-right: 1px solid #f1f5f9;
        vertical-align: middle;
        text-align: center;
        padding: 0 !important; /* Remove cell padding to allow full-height hover styling */
        background-color: #ffffff;
    }
    .timetable-grid td:last-child {
        border-right: none;
    }
    .timetable-grid .col-time {
        width: 130px;
        background-color: #f8fafc;
        font-weight: 700;
        color: #1e293b;
        padding: 12px !important;
    }
    .timetable-grid .col-minutes {
        width: 70px;
        background-color: #f8fafc;
        font-weight: 600;
        color: #64748b;
        padding: 12px !important;
    }
    .timetable-grid .cell-quran {
        background-color: #f0f9ff !important; /* bg-sky-50 */
        color: #0369a1 !important; /* text-sky-700 */
        border-right: 1px solid #bae6fd !important;
        border-top: 1px solid #bae6fd !important;
    }
    .timetable-grid .cell-quran:hover {
        background-color: #e0f2fe !important;
    }
    .timetable-grid .cell-hadith {
        background-color: #fffbeb !important; /* bg-amber-50 */
        color: #b45309 !important; /* text-amber-700 */
        border-right: 1px solid #fde68a !important;
        border-top: 1px solid #fde68a !important;
    }
    .timetable-grid .cell-hadith:hover {
        background-color: #fef3c7 !important;
    }
    .timetable-grid .cell-arabic {
        background-color: #fdf2f8 !important; /* bg-pink-50 */
        color: #be185d !important; /* text-pink-700 */
        border-right: 1px solid #fce7f3 !important;
        border-top: 1px solid #fce7f3 !important;
    }
    .timetable-grid .cell-arabic:hover {
        background-color: #fce7f3 !important;
    }
    .timetable-grid .cell-recess {
        background-color: #fff5f5 !important; /* bg-red-50 */
        color: #c53030 !important; /* text-red-700 */
        border-right: 1px solid #fed7d7 !important;
        border-top: 1px solid #fed7d7 !important;
    }
    .timetable-grid .cell-recess:hover {
        background-color: #fed7d7 !important;
    }
    .timetable-grid .cell-academic {
        background-color: #f0fdf4 !important; /* bg-emerald-50 */
        color: #15803d !important; /* text-emerald-700 */
        border-right: 1px solid #dcfce7 !important;
        border-top: 1px solid #dcfce7 !important;
    }
    .timetable-grid .cell-academic:hover {
        background-color: #dcfce7 !important;
    }
    .timetable-grid .cell-event {
        background-color: #f5f3ff !important; /* bg-violet-50 */
        color: #6d28d9 !important; /* text-violet-700 */
        border-right: 1px solid #ede9fe !important;
        border-top: 1px solid #ede9fe !important;
    }
    .timetable-grid .cell-event:hover {
        background-color: #ede9fe !important;
    }
    .timetable-grid .cell-empty {
        background-color: #ffffff !important;
        color: #1e293b !important;
    }
    .timetable-grid .cell-empty:hover {
        background-color: #f8fafc !important;
    }
</style>

@php
    $sectionsByGrade = $sections->groupBy('grade_level');
    $gradeOrder = [
        'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 
        'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 
        'Grade 11', 'Grade 12'
    ];
    $sortedGradeLevels = $sectionsByGrade->keys()->sortBy(function ($grade) use ($gradeOrder) {
        $idx = array_search($grade, $gradeOrder);
        return $idx !== false ? $idx : 999;
    });
@endphp

<div x-show="activeWorkspace === 'schedule'" x-transition class="space-y-6">
    <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs space-y-4">
        <div>
            <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">Select Grade Level</label>
            <div class="flex flex-wrap gap-1.5">
                @foreach($sortedGradeLevels as $grade)
                    <button type="button" 
                        @click="activeGradeLevel = '{{ $grade }}'; activeSectionId = (gradeSections['{{ $grade }}'] && gradeSections['{{ $grade }}'][0]) ? gradeSections['{{ $grade }}'][0].id : 0"
                        :class="activeGradeLevel === '{{ $grade }}' ? 'bg-indigo-700 text-white shadow-xs font-bold' : 'bg-gray-50 text-slate-655 hover:bg-gray-100 hover:text-slate-900'"
                        class="px-3.5 py-2 text-xs rounded-xl transition cursor-pointer shadow-3xs font-extrabold">
                        {{ $grade }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="border-t border-slate-100 pt-3">
            <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">Select Section / Mode</label>
            <div class="flex flex-wrap gap-2">
                @foreach($sections as $section)
                    <button type="button" 
                        x-show="activeGradeLevel === '{{ $section->grade_level }}'"
                        @click="activeSectionId = {{ $section->id }}"
                        :class="activeSectionId === {{ $section->id }} ? 'bg-indigo-100 text-indigo-850 shadow-3xs font-extrabold' : 'bg-slate-50 text-slate-600 hover:bg-slate-100/80 shadow-3xs'"
                        class="px-4 py-2.5 text-xs rounded-xl transition cursor-pointer text-left">
                        <span class="block font-black text-slate-950">{{ $section->official_name ?: $section->name ?: 'General' }}</span>
                        <span class="block text-[9px] opacity-80 font-semibold mt-0.5" :class="activeSectionId === {{ $section->id }} ? 'text-indigo-700' : 'text-slate-400'">
                            {{ ucfirst($section->gender === 'male' ? 'Boys' : 'Girls') }} · {{ $section->formatted_learning_mode }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @foreach($sections as $section)
        @php
            $entries = $schedulesBySection->get($section->id, collect());
            $sectionLabel = trim($section->grade_level . ($section->name ? ' - ' . $section->name : ''));
        @endphp
        <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden" x-show="activeSectionId === {{ $section->id }}" x-transition>
            <!-- Title Bar matching Active Sections Catalog design -->
            <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase block">
                        {{ $section->grade_level }} - {{ $section->official_name ?: $section->name ?: 'General' }} Timetable
                    </span>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">
                        <span>{{ $entries->count() }} scheduled classes</span>
                        @if($section->grade_advisor)
                            <span>•</span>
                            <span class="text-indigo-650 uppercase">Advisor: {{ $section->grade_advisor->teacher_name }}</span>
                        @endif
                    </div>
                </div>
                <x-badge color="indigo">{{ $section->formatted_learning_mode }}</x-badge>
            </div>

            @if($entries->isEmpty())
                <div class="p-6">
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                        <i data-lucide="calendar-plus" class="mx-auto h-6 w-6 text-slate-400"></i>
                        <p class="mt-2 text-xs font-bold text-slate-500">No classes scheduled yet.</p>
                    </div>
                </div>
            @else
                @php
                    $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

                    // Extract and sort unique time intervals
                    $intervals = [];
                    $timeBoundaries = [];
                    foreach ($entries as $entry) {
                        $startMin = $entry['start_minutes'];
                        [$endH, $endM] = explode(':', $entry['end_time']);
                        $endMin = ($endH * 60) + $endM;
                        $timeBoundaries[] = $startMin;
                        $timeBoundaries[] = $endMin;
                    }
                    $timeBoundaries = array_unique($timeBoundaries);
                    sort($timeBoundaries);

                    for ($i = 0; $i < count($timeBoundaries) - 1; $i++) {
                        $intervals[] = [
                            'start' => $timeBoundaries[$i],
                            'end' => $timeBoundaries[$i+1],
                            'start_time' => sprintf('%02d:%02d', intdiv($timeBoundaries[$i], 60), $timeBoundaries[$i] % 60),
                            'end_time' => sprintf('%02d:%02d', intdiv($timeBoundaries[$i+1], 60), $timeBoundaries[$i+1] % 60),
                            'minutes' => $timeBoundaries[$i+1] - $timeBoundaries[$i],
                        ];
                    }

                    // Helper to format time label (e.g. 7:30-7:40 a.m.)
                    if (!function_exists('formatTimetableTime')) {
                        function formatTimetableTime($start, $end) {
                            $startAmPm = date('a', strtotime($start));
                            $endAmPm = date('a', strtotime($end));
                            
                            $startAmPm = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $startAmPm);
                            $endAmPm = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $endAmPm);
                            
                            if ($startAmPm === $endAmPm) {
                                return date('g:i', strtotime($start)) . '-' . date('g:i', strtotime($end)) . ' ' . $endAmPm;
                            }
                            return date('g:i', strtotime($start)) . ' ' . $startAmPm . ' - ' . date('g:i', strtotime($end)) . ' ' . $endAmPm;
                        }
                    }

                    // Build 2D grid matrix
                    $grid = [];
                    foreach ($intervals as $iIdx => $interval) {
                        $grid[$iIdx] = [];
                        foreach ($daysList as $day) {
                            $grid[$iIdx][$day] = null;
                        }
                    }

                    foreach ($daysList as $day) {
                        foreach ($intervals as $iIdx => $interval) {
                            $matchingEntry = null;
                            foreach ($entries as $entry) {
                                if ($entry['day'] !== $day) {
                                    continue;
                                }
                                $entryStart = $entry['start_minutes'];
                                [$endH, $endM] = explode(':', $entry['end_time']);
                                $entryEnd = ($endH * 60) + $endM;

                                if ($entryStart <= $interval['start'] && $entryEnd >= $interval['end']) {
                                    $matchingEntry = $entry;
                                    break;
                                }
                            }

                            if ($matchingEntry) {
                                $isStart = ($matchingEntry['start_minutes'] === $interval['start']);
                                $span = 0;
                                if ($isStart) {
                                    foreach ($intervals as $subInterval) {
                                        if ($subInterval['start'] >= $matchingEntry['start_minutes']) {
                                            [$entryEndH, $entryEndM] = explode(':', $matchingEntry['end_time']);
                                            $entryEndMin = ($entryEndH * 60) + $entryEndM;
                                            if ($subInterval['end'] <= $entryEndMin) {
                                                $span++;
                                            }
                                        }
                                    }
                                }

                                $grid[$iIdx][$day] = [
                                    'entry' => $matchingEntry,
                                    'is_start' => $isStart,
                                    'span' => $span,
                                ];
                            }
                        }
                    }

                    // Initialize horizontal merge tracking
                    foreach ($intervals as $iIdx => $interval) {
                        foreach ($daysList as $day) {
                            if ($grid[$iIdx][$day]) {
                                $grid[$iIdx][$day]['colspan'] = 1;
                                $grid[$iIdx][$day]['skip_horizontal'] = false;
                            }
                        }
                    }

                    // Compute horizontal colspans
                    foreach ($intervals as $iIdx => $interval) {
                        for ($d = 0; $d < count($daysList); $d++) {
                            $day = $daysList[$d];
                            $cell = $grid[$iIdx][$day];
                            if (!$cell || !$cell['is_start'] || $cell['skip_horizontal']) {
                                continue;
                            }

                            $colspan = 1;
                            while ($d + $colspan < count($daysList)) {
                                $nextDay = $daysList[$d + $colspan];
                                $nextCell = $grid[$iIdx][$nextDay];
                                
                                if ($nextCell && $nextCell['is_start'] && !$nextCell['skip_horizontal'] && $nextCell['span'] === $cell['span'] && $nextCell['entry']['subject_name'] === $cell['entry']['subject_name'] && $nextCell['entry']['teacher_name'] === $cell['entry']['teacher_name']) {
                                    $colspan++;
                                } else {
                                    break;
                                }
                            }

                            if ($colspan > 1) {
                                $grid[$iIdx][$day]['colspan'] = $colspan;
                                for ($c = 1; $c < $colspan; $c++) {
                                    $targetDay = $daysList[$d + $c];
                                    for ($r = 0; $r < $cell['span']; $r++) {
                                        if ($grid[$iIdx + $r][$targetDay]) {
                                            $grid[$iIdx + $r][$targetDay]['skip_horizontal'] = true;
                                        }
                                    }
                                }
                            }
                        }
                    }
                @endphp

                <div class="premium-table-wrap timetable-grid-wrap">
                    <table class="premium-table timetable-grid">
                        <thead>
                            <tr>
                                <th class="col-time text-center uppercase tracking-wider font-extrabold text-[10px]" style="text-align: center;">Time</th>
                                <th class="col-minutes text-center uppercase tracking-wider font-extrabold text-[10px]" style="text-align: center;">Minutes</th>
                                @foreach($daysList as $day)
                                    <th class="text-center uppercase tracking-wider font-extrabold text-[10px]" style="text-align: center;">{{ $day }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($intervals as $iIdx => $interval)
                                <tr>
                                    <td class="col-time font-bold text-slate-800">
                                        {{ formatTimetableTime($interval['start_time'], $interval['end_time']) }}
                                    </td>
                                    <td class="col-minutes font-semibold text-slate-500">
                                        {{ $interval['minutes'] }}
                                    </td>
                                    @foreach($daysList as $day)
                                        @php
                                            $cell = $grid[$iIdx][$day];
                                        @endphp
                                        @if($cell)
                                            @if($cell['is_start'] && !$cell['skip_horizontal'])
                                                @php
                                                    $payload = base64_encode(json_encode($cell['entry']['payload'] + [
                                                        'update_url' => route('admin.academic.schedules.update', $cell['entry']['id']),
                                                        'destroy_url' => route('admin.academic.schedules.destroy', $cell['entry']['id']),
                                                    ]));
                                                    $subjectLower = strtolower($cell['entry']['subject_name']);
                                                    $cellClass = 'cell-academic';
                                                    if (str_contains($subjectLower, 'qur')) {
                                                        $cellClass = 'cell-quran';
                                                    } elseif (str_contains($subjectLower, 'hadith')) {
                                                        $cellClass = 'cell-hadith';
                                                    } elseif (str_contains($subjectLower, 'arabic')) {
                                                        $cellClass = 'cell-arabic';
                                                    } elseif (str_contains($subjectLower, 'recess')) {
                                                        $cellClass = 'cell-recess';
                                                    } elseif (str_contains($subjectLower, 'meeting') || str_contains($subjectLower, 'circle') || str_contains($subjectLower, 'wrap')) {
                                                        $cellClass = 'cell-event';
                                                    } elseif (str_contains($subjectLower, 'assembly') || str_contains($subjectLower, 'departure')) {
                                                        $cellClass = 'cell-empty';
                                                    }
                                                @endphp
                                                <td rowspan="{{ $cell['span'] }}" colspan="{{ $cell['colspan'] }}" class="{{ $cellClass }}">
                                                    <div class="relative w-full h-full p-4 flex flex-col justify-center text-center group min-h-[55px]">
                                                        <span class="block font-extrabold text-[12px] leading-tight uppercase tracking-wide" style="color: inherit;">
                                                            {{ $cell['entry']['subject_name'] }}
                                                        </span>
                                                        @if($cell['entry']['teacher_name'] && $cell['entry']['teacher_name'] !== 'Teacher pending')
                                                            <span class="block text-[10px] font-bold uppercase mt-1.5 flex items-center justify-center gap-1.5" style="color: inherit; opacity: 0.8;">
                                                                <i data-lucide="user" class="h-3 w-3 opacity-60"></i>
                                                                {{ $cell['entry']['teacher_name'] }}
                                                            </span>
                                                        @endif
                                                        <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                                            <button type="button" data-entry="{{ $payload }}" @click="openEdit(JSON.parse(atob($el.dataset.entry)))" class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white text-slate-700 border border-slate-200 hover:text-blue-600 hover:bg-blue-50 shadow-3xs cursor-pointer transition" title="Edit">
                                                                <i data-lucide="pencil" class="h-2.5 w-2.5"></i>
                                                            </button>
                                                            <button type="button" data-entry="{{ $payload }}" @click="openDelete(JSON.parse(atob($el.dataset.entry)))" class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-white text-rose-600 border border-slate-200 hover:bg-rose-50 shadow-3xs cursor-pointer transition" title="Delete">
                                                                <i data-lucide="trash-2" class="h-2.5 w-2.5"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endif
                                        @else
                                            <td class="cell-empty"></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
</div>
