<style>
    .timetable-spreadsheet {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
    }
    .timetable-spreadsheet th {
        position: sticky;
        top: 0;
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 2px solid #e2e8f0;
        border-right: 1px solid #e5e7eb;
        z-index: 10;
        text-align: center;
    }
    .timetable-spreadsheet th:last-child {
        border-right: none;
    }
    .timetable-spreadsheet td {
        padding: 10px;
        border-bottom: 1px solid #e5e7eb;
        border-right: 1px solid #e5e7eb;
        vertical-align: middle;
        background-color: #ffffff;
    }
    .timetable-spreadsheet td:last-child {
        border-right: none;
    }
    .timetable-spreadsheet .header-title {
        background-color: #ffffff !important;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #0f172a;
        padding: 16px;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
        border-right: none;
    }
    .timetable-spreadsheet .col-time {
        width: 140px;
        font-weight: 700;
        color: #1e293b;
        text-align: center;
        background-color: #f8fafc;
    }
    .timetable-spreadsheet .col-minutes {
        width: 80px;
        font-weight: 600;
        color: #64748b;
        text-align: center;
        background-color: #f8fafc;
    }
    .timetable-card {
        background-color: #f8fafc;
        border: 1px solid #f1f5f9;
        border-left: 4px solid #2563eb; /* Accent blue */
        border-radius: 8px;
        padding: 10px 14px;
        text-align: left;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        min-height: 55px;
    }
    .timetable-card:hover {
        background-color: #f1f5f9;
        border-left-color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    }
    .timetable-card-event {
        background-color: #f8fafc;
        border: 1px solid #f1f5f9;
        border-left: 4px solid #94a3b8; /* Slate neutral accent for events */
        border-radius: 8px;
        padding: 10px 14px;
        text-align: left;
        transition: all 0.2s ease;
        position: relative;
        min-height: 55px;
    }
    .timetable-card-event:hover {
        background-color: #f1f5f9;
        border-left-color: #64748b;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    }
    .timetable-spreadsheet .cell-empty {
        background-color: #ffffff !important;
        border: 1px solid #f1f5f9;
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
                            {{ $section->shift ?? 'F2F' }} · {{ ucfirst($section->gender === 'male' ? 'Boys' : 'Girls') }} · {{ $section->learning_mode }}
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
        <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-5" x-show="activeSectionId === {{ $section->id }}" x-transition>
            <div class="border-b border-slate-100 pb-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <span class="text-slate-900 font-extrabold text-base block">
                        {{ $section->grade_level }} - {{ $section->official_name ?: $section->name ?: 'General' }} Timetable
                    </span>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <span>{{ $entries->count() }} scheduled classes</span>
                        @if($section->grade_advisor)
                            <span>•</span>
                            <span class="text-indigo-600">Advisor: {{ $section->grade_advisor->teacher_name }}</span>
                        @endif
                    </div>
                </div>
                <x-badge color="indigo">{{ $section->learning_mode }}</x-badge>
            </div>

            @if($entries->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                    <i data-lucide="calendar-plus" class="mx-auto h-6 w-6 text-slate-400"></i>
                    <p class="mt-2 text-xs font-bold text-slate-500">No classes scheduled yet.</p>
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

                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-xs">
                    <table class="timetable-spreadsheet">
                        <thead>
                            <tr>
                                <th colspan="7" class="header-title">
                                    {{ strtoupper($section->grade_level) }} - {{ strtoupper($section->official_name ?: $section->name ?: 'General') }} CLASS SCHEDULE ({{ $section->learning_mode === 'Face-to-Face' ? 'F2F' : 'ODL' }})
                                </th>
                            </tr>
                            <tr>
                                <th class="col-time">Time</th>
                                <th class="col-minutes">Minutes</th>
                                @foreach($daysList as $day)
                                    <th>{{ $day }}</th>
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
                                                    // Classify events vs classes
                                                    $isEvent = str_contains($subjectLower, 'assembly') || str_contains($subjectLower, 'recess') || str_contains($subjectLower, 'departure');
                                                    $cardClass = $isEvent ? 'timetable-card-event' : 'timetable-card';
                                                @endphp
                                                <td rowspan="{{ $cell['span'] }}" colspan="{{ $cell['colspan'] }}">
                                                    <div class="{{ $cardClass }} group">
                                                        <div class="flex flex-col justify-center">
                                                            <span class="block font-bold text-[12px] text-slate-800 leading-tight">{{ $cell['entry']['subject_name'] }}</span>
                                                            @if($cell['entry']['teacher_name'] && $cell['entry']['teacher_name'] !== 'Teacher pending')
                                                                <span class="block text-[10px] font-medium text-slate-500 mt-1.5 flex items-center gap-1.5">
                                                                    <i data-lucide="user" class="h-3 w-3 opacity-60"></i>
                                                                    {{ $cell['entry']['teacher_name'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="absolute top-1.5 right-1.5 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-150">
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
