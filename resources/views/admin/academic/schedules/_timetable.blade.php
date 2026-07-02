<style>
    /* Timetable Grid Style */
    .timetable-grid-wrap {
        max-height: none !important;
        overflow: visible !important;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        background: #ffffff;
    }
    
    .timetable-grid {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100%;
    }
    
    .timetable-grid th {
        background-color: #f8fafc !important;
        color: #334155 !important;
        font-weight: 800 !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 11px !important;
        padding: 14px 10px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
    }
    .timetable-grid th:last-child {
        border-right: none !important;
    }
    
    .timetable-grid td {
        border-bottom: 1px solid #f1f5f9;
        border-right: 1px solid #f1f5f9;
        vertical-align: middle;
        text-align: center;
        padding: 6px !important; /* Premium inner padding */
        background-color: #ffffff;
        position: relative;
    }
    .timetable-grid td:last-child {
        border-right: none;
    }
    
    /* Time & Minutes Columns */
    .timetable-grid .col-time {
        width: 120px;
        background-color: #f8fafc;
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        color: #1e293b;
        font-size: 10px;
        padding: 12px 8px !important;
        border-right: 2px solid #e2e8f0 !important;
    }
    .timetable-grid .col-minutes {
        width: 65px;
        background-color: #f8fafc;
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        color: #64748b;
        font-size: 10px;
        padding: 12px 4px !important;
        border-right: 2px solid #e2e8f0 !important;
    }
    
    /* Premium Card Blocks inside cells */
    .timetable-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100%;
        min-height: 55px;
        padding: 10px 8px;
        border-radius: 0.75rem;
        transition: all 0.2s ease-in-out;
        text-align: center;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.03);
    }
    
    /* Hover effects */
    .timetable-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        filter: brightness(0.97);
    }
    
    /* Colors and Accents */
    .timetable-grid .cell-quran .timetable-card {
        background-color: #f0fdf4; /* soft green/emerald */
        color: #166534;
        border: 1px solid #bbf7d0;
        border-left: 4px solid #16a34a;
    }
    
    .timetable-grid .cell-hadith .timetable-card {
        background-color: #fffbeb; /* soft amber/yellow */
        color: #92400e;
        border: 1px solid #fef08a;
        border-left: 4px solid #ca8a04;
    }
    
    .timetable-grid .cell-arabic .timetable-card {
        background-color: #eff6ff; /* soft blue/sky */
        color: #1e40af;
        border: 1px solid #bfdbfe;
        border-left: 4px solid #2563eb;
    }
    
    .timetable-grid .cell-recess .timetable-card {
        background-color: #fff5f5; /* soft red */
        color: #9b2c2c;
        border: 1px solid #feb2b2;
        border-left: 4px solid #e53e3e;
    }
    
    .timetable-grid .cell-academic .timetable-card {
        background-color: #faf5ff; /* soft purple/violet */
        color: #6b21a8;
        border: 1px solid #e9d5ff;
        border-left: 4px solid #9333ea;
    }
    
    .timetable-grid .cell-event .timetable-card {
        background-color: #f0fdfa; /* soft teal */
        color: #115e59;
        border: 1px solid #99f6e4;
        border-left: 4px solid #0d9488;
    }
    
    .timetable-grid .cell-empty {
        background-color: #ffffff !important;
        border-bottom: 1px dashed #e2e8f0;
        border-right: 1px dashed #e2e8f0;
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
    {{-- Mode Selector Header --}}
    <div class="flex items-center justify-between bg-white border border-gray-150 px-5 py-4 rounded-2xl shadow-xs">
        <span class="text-slate-950 font-extrabold text-sm tracking-wide uppercase font-outfit">Weekly Timetables</span>
        <div class="flex items-center gap-4">
            <div class="flex gap-1 bg-slate-100 p-1 border border-slate-200/50 rounded-xl">
                <a href="{{ route('admin.academic.schedules', ['mode' => 'f2f', 'tab' => 'schedule']) }}" 
                   class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all {{ $mode === 'f2f' ? 'bg-white text-indigo-950 shadow-3xs font-extrabold' : 'text-slate-500 hover:text-slate-955' }}">
                    Face-to-Face
                </a>
                <a href="{{ route('admin.academic.schedules', ['mode' => 'online', 'tab' => 'schedule']) }}" 
                   class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition-all {{ $mode === 'online' ? 'bg-white text-indigo-950 shadow-3xs font-extrabold' : 'text-slate-500 hover:text-slate-955' }}">
                    Flexible Online
                </a>
            </div>
            
            <button type="button" @click="createModal = true"
                    class="inline-flex items-center gap-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs px-4 py-2 rounded-xl transition shadow-3xs cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                Add Section
            </button>
        </div>
    </div>

    @if($sections->isEmpty())
        <div class="bg-white border border-slate-150 rounded-2xl p-12 text-center shadow-xs">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mx-auto mb-4">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
            <div class="font-extrabold text-slate-900 text-sm mb-1">No Active Timetables</div>
            <p class="text-xs text-slate-500 mb-4">There are no {{ $mode === 'online' ? 'Flexible Online' : 'Face-to-Face' }} sections created for this school year yet.</p>
            <button type="button" @click="activeWorkspace = 'sections'; createModal = true"
                class="inline-flex items-center gap-2 bg-indigo-750 hover:bg-indigo-900 active:scale-95 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-3xs cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add First Section
            </button>
        </div>
    @else
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
                                {{ ucfirst($section->gender === 'male' ? 'Boys' : ($section->gender === 'female' ? 'Girls' : 'Merge')) }} · {{ $section->formatted_learning_mode }}
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
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge color="indigo">{{ $section->formatted_learning_mode }}</x-badge>
                        @if($section->ms_team_url)
                            <a href="{{ $section->ms_team_url }}" target="_blank" class="inline-flex items-center gap-1 bg-indigo-700 hover:bg-indigo-850 text-white font-extrabold text-[10px] px-3 py-1.5 rounded-lg transition ml-1">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i> Teams Class
                            </a>
                        @endif
                        
                        {{-- Add Class Button --}}
                        <button type="button" @click="openAddClass({{ $section->id }})"
                                class="inline-flex items-center gap-1.5 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-[10px] px-3 py-1.5 rounded-lg transition cursor-pointer">
                            <i data-lucide="plus" class="w-3 h-3"></i> Add Class
                        </button>
                        
                        {{-- Rename Action --}}
                        <button type="button" @click="openEdit({{ $section->id }}, '{{ $section->name ?: '' }}')"
                                class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-[10px] px-3 py-1.5 rounded-lg transition cursor-pointer">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i> Rename
                        </button>
                        
                        {{-- Delete Action --}}
                        <form method="POST" action="{{ route('admin.ms-teams.destroy', $section->id) }}" 
                              onsubmit="return confirm('Are you sure you want to delete this section? This will delete the MS Team and all associated student section records.')"
                              class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold text-[10px] px-3 py-1.5 rounded-lg transition cursor-pointer">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>

                @if($entries->isEmpty())
                    <div class="p-6">
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center flex flex-col items-center justify-center">
                            <i data-lucide="calendar-plus" class="mx-auto h-6 w-6 text-slate-400"></i>
                            <p class="mt-2 text-xs font-bold text-slate-500">No classes scheduled yet.</p>
                            <button type="button" 
                                    @click="openAddClass({{ $section->id }})"
                                    class="mt-4 inline-flex items-center gap-1.5 bg-indigo-700 hover:bg-indigo-850 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl transition shadow-3xs cursor-pointer">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i> Add First Class
                            </button>
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
                                                        <template x-if="editingCell && editingCell.type === 'edit' && editingCell.id === {{ $cell['entry']['id'] }}">
                                                            @include('admin.academic.schedules._inline-form')
                                                        </template>
                                                        <template x-if="!(editingCell && editingCell.type === 'edit' && editingCell.id === {{ $cell['entry']['id'] }})">
                                                            <div class="timetable-card cursor-pointer group"
                                                                 @click="startInlineEdit({{ json_encode($cell['entry']) }})">
                                                                <span class="block font-extrabold text-[11px] leading-tight uppercase tracking-wide font-outfit" style="color: inherit;">
                                                                    {{ $cell['entry']['subject_name'] }}
                                                                </span>
                                                                @if($cell['entry']['teacher_name'] && $cell['entry']['teacher_name'] !== 'Teacher pending')
                                                                    <span class="block text-[9px] font-bold uppercase mt-1 flex items-center justify-center gap-1" style="color: inherit; opacity: 0.85;">
                                                                        <i data-lucide="user" class="h-3 w-3 opacity-65" style="width: 12px; height: 12px;"></i>
                                                                        {{ $cell['entry']['teacher_name'] }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </template>
                                                    </td>
                                                @endif
                                            @else
                                                <td class="cell-empty">
                                                    <template x-if="editingCell && editingCell.type === 'create' && editingCell.section_id === {{ $section->id }} && editingCell.day === '{{ $day }}' && editingCell.start_time === '{{ $interval['start_time'] }}'">
                                                        @include('admin.academic.schedules._inline-form')
                                                    </template>
                                                    <template x-if="!(editingCell && editingCell.type === 'create' && editingCell.section_id === {{ $section->id }} && editingCell.day === '{{ $day }}' && editingCell.start_time === '{{ $interval['start_time'] }}')">
                                                        <button type="button" 
                                                            @click="startInlineCreate({{ $section->id }}, '{{ $day }}', '{{ $interval['start_time'] }}', '{{ $interval['end_time'] }}')"
                                                            class="w-full h-full min-h-[55px] cursor-pointer hover:bg-indigo-50/20 transition-colors flex items-center justify-center group rounded-lg">
                                                            <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                                        </button>
                                                    </template>
                                                </td>
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
    @endif
</div>
