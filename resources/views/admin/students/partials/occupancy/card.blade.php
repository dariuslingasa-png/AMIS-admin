@php
    $gradeOccupied = $gradeSections->sum('occupied');
    $gradeCapacity = $gradeSections->sum('capacity_limit');
    $gradeFillRate = $gradeCapacity > 0 ? min(100, round(($gradeOccupied / $gradeCapacity) * 100)) : 0;
    
    $gradeStatusColor = $gradeFillRate >= 100 ? 'red' : ($gradeFillRate >= 85 ? 'amber' : 'emerald');
    $gradeThemeMap = [
        'red' => [
            'bg' => 'bg-rose-50 text-rose-700 border-rose-100',
            'fill' => 'bg-rose-500',
            'border' => 'border-t-rose-500'
        ],
        'amber' => [
            'bg' => 'bg-amber-50 text-amber-700 border-amber-100',
            'fill' => 'bg-amber-500',
            'border' => 'border-t-amber-500'
        ],
        'emerald' => [
            'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'fill' => 'bg-emerald-600',
            'border' => 'border-t-emerald-600'
        ],
    ];
    $gTheme = $gradeThemeMap[$gradeStatusColor];
    
    // Retrieve grade level advisor once for the whole card
    $firstSection = $gradeSections->first();
    $advisor = $firstSection ? $firstSection->grade_advisor : null;
    $advisorName = $advisor ? ($advisor->teacher_name ?? $advisor->teacher?->name ?? 'No Advisor') : 'No Advisor';
    $advisorEmail = $advisor ? ($advisor->teacher_email ?? $advisor->teacher?->email ?? null) : null;
@endphp

<div class="rounded-3xl border border-slate-200/80 bg-white shadow-sm hover:shadow-md transition duration-300 border-t-4 {{ $gTheme['border'] }} p-5 flex flex-col justify-between">
    <div>
        <!-- Grade Card Header -->
        <div class="flex items-center justify-between pb-3" style="border-bottom: 1px solid #f1f5f9;">
            <div class="flex items-center gap-2">
                <div class="rounded-xl bg-emerald-50 p-1.5 text-emerald-600">
                    <i data-lucide="graduation-cap" class="h-4.5 w-4.5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 tracking-tight uppercase">{{ $gradeLevel }}</h3>
                    @if($advisor)
                        <div class="text-[9px] text-slate-500 font-bold mt-0.5 flex items-center gap-1">
                            <i data-lucide="user" class="h-2.5 w-2.5 text-emerald-600"></i>
                            Advisor: <span class="font-extrabold text-slate-700 uppercase" title="{{ $advisorEmail }}">{{ str_ireplace('TEACHER ', '', $advisorName) }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="text-right flex items-center gap-2">
                <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $gTheme['bg'] }}">
                    {{ $gradeOccupied }} / {{ $gradeCapacity }} Seats Enrolled
                </span>
                <a href="{{ route('admin.students.grade-roster-print', $gradeLevel) }}" target="_blank" class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center transition active:scale-[0.95]" title="Print Grade PDF (All Sections)">
                    <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                </a>
            </div>
        </div>

        <!-- Grade Card Overall Progress Bar -->
        <div class="mt-3 space-y-1">
            <div class="flex justify-between text-[9px] font-extrabold text-slate-500 uppercase tracking-wider">
                <span>Grade Load</span>
                <span>{{ $gradeFillRate }}% Capacity</span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $gTheme['fill'] }} transition-all duration-500" style="width: {{ $gradeFillRate }}%;"></div>
            </div>
        </div>

        <!-- Nested Compact Sections Table -->
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-xs align-middle">
                <thead>
                    <tr class="text-[9px] font-black uppercase tracking-wider text-slate-400" style="border-bottom: 1px solid #e2e8f0;">
                        <th class="pb-2 font-black">Section</th>
                        <th class="pb-2 font-black">Occupancy</th>
                        <th class="pb-2 text-right font-black">Actions</th>
                    </tr>
                </thead>
                @foreach($gradeSections as $section)
                    @php
                        $secStatusColor = $section->fill_rate >= 100 ? 'red' : ($section->fill_rate >= 85 ? 'amber' : 'emerald');
                        $secThemeMap = [
                            'red' => ['bg' => 'bg-rose-50 text-rose-700', 'fill' => 'bg-rose-500', 'text' => 'text-rose-600'],
                            'amber' => ['bg' => 'bg-amber-50 text-amber-700', 'fill' => 'bg-amber-500', 'text' => 'text-amber-600'],
                            'emerald' => ['bg' => 'bg-emerald-50 text-emerald-700', 'fill' => $section->gender === 'male' ? 'bg-blue-600' : 'bg-pink-500', 'text' => 'text-emerald-600'],
                        ];
                        $sTheme = $secThemeMap[$secStatusColor];
                        
                        $sectionDisplayName = $section->official_name ?: ($section->name ?: 'General Section');
                        if ($section->gender && in_array($section->gender, ['male', 'female'])) {
                            $genderSuffix = $section->gender === 'male' ? 'Boys' : 'Girls';
                            if (!str_contains(strtolower($sectionDisplayName), strtolower($genderSuffix))) {
                                $sectionDisplayName .= " - " . $genderSuffix;
                            }
                        }
                        
                        $modeParts = array_filter([
                            $section->learning_mode ?: null,
                            $section->shift ?: null
                        ]);
                        $secSubtext = !empty($modeParts) ? implode(' · ', $modeParts) : '';
                    @endphp
                    <tbody x-data="{ showRoster: false, openAssignModal: false, searchStudent: '' }">
                        <tr class="hover:bg-slate-50/40 transition" style="border-bottom: {{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                            <!-- Section Name & Mode -->
                            <td class="py-3 pr-2">
                                <div class="font-extrabold text-slate-800 uppercase leading-snug text-xs">{{ $sectionDisplayName }}</div>
                                @if($secSubtext)
                                    <div class="text-[9px] font-black text-slate-400 uppercase mt-0.5">
                                        {{ $secSubtext }}
                                    </div>
                                @endif
                            </td>
                            <!-- Occupancy Bar -->
                            <td class="py-3 pr-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-extrabold text-slate-800 text-[11px] min-w-[32px]">{{ $section->occupied }}/{{ $section->capacity_limit }}</span>
                                    <div class="h-1.5 w-12 rounded-full bg-slate-100 overflow-hidden shrink-0 hidden sm:block">
                                        <div class="h-full rounded-full {{ $sTheme['fill'] }} transition-all duration-300" style="width: {{ $section->fill_rate }}%;"></div>
                                    </div>
                                </div>
                            </td>
                            <!-- Actions -->
                            <td class="py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($section->ms_team_id)
                                        <a href="{{ $section->ms_team_url }}" target="_blank" class="h-7 w-7 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 hover:bg-purple-100/80 flex items-center justify-center transition active:scale-[0.95]" title="Open Microsoft Teams Workspace">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12.5 12a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2zM9.5 2A2.5 2.5 0 0 0 7 4.5v15A2.5 2.5 0 0 0 9.5 22h5a2.5 2.5 0 0 0 2.5-2.5v-15A2.5 2.5 0 0 0 14.5 2h-5zM9.5 3.5h5a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-15a1 1 0 0 1 1-1z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    <button type="button" @click="openQuickEdit = true" class="h-7 w-7 rounded-lg bg-amber-50 text-amber-700 border border-amber-100 hover:bg-amber-100 flex items-center justify-center transition active:scale-[0.95]" title="Rename / Edit Section">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <a href="{{ route('admin.students.occupancy.manage-section', $section) }}" class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 hover:bg-emerald-100 flex items-center justify-center transition active:scale-[0.95]" title="Manage Section & Add Students">
                                        <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <button type="button" @click="showRoster = !showRoster" class="h-7 w-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50 active:scale-[0.95] transition cursor-pointer" title="View Roster">
                                        <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <!-- Print Dropdown -->
                                    <div class="relative inline-block text-left" x-data="{ openPrintDropdown: false }" @click.outside="openPrintDropdown = false">
                                        <button type="button" @click="openPrintDropdown = !openPrintDropdown" class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center transition active:scale-[0.95]" title="Print Student Roster">
                                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <div x-cloak x-show="openPrintDropdown" x-transition.origin.top.right 
                                             class="absolute right-0 mt-1 w-36 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl z-50 text-left">
                                            <a href="{{ route('admin.students.roster-print', $section) }}" target="_blank" 
                                               class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-[11px] font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span>Official Roster</span>
                                            </a>
                                            <a href="{{ route('admin.students.roster-print', $section) }}?watermark=1" target="_blank" 
                                               class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-[11px] font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-600"></i>
                                                <span>Watermark Copy</span>
                                            </a>
                                        </div>
                                    </div>
                                    <!-- ID Cards Dropdown -->
                                    <div class="relative inline-block text-left" x-data="{ openIdDropdown: false }" @click.outside="openIdDropdown = false">
                                        <button type="button" @click="openIdDropdown = !openIdDropdown" class="h-7 w-7 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 hover:bg-purple-100 flex items-center justify-center transition active:scale-[0.95]" title="Export Section ID Cards Document">
                                            <i data-lucide="contact" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <div x-cloak x-show="openIdDropdown" x-transition.origin.top.right 
                                             class="absolute right-0 mt-1 w-36 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl z-50 text-left">
                                            <a href="{{ route('admin.students.id-roster-print', $section) }}" target="_blank" 
                                               class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-[11px] font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700 transition">
                                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-purple-600"></i>
                                                <span>Official ID Cards</span>
                                            </a>
                                            <a href="{{ route('admin.students.id-roster-print', $section) }}?watermark=1" target="_blank" 
                                               class="flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-[11px] font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-amber-600"></i>
                                                <span>Watermark Copy</span>
                                            </a>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.students.occupancy.delete-section', $section) }}"
                                          onsubmit="return confirm('Delete section &quot;{{ $sectionDisplayName }}&quot;?\n\nNote: This will only remove the section from the portal list. Microsoft Teams app and student records will NOT be deleted.')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="h-7 w-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 flex items-center justify-center transition active:scale-[0.95] cursor-pointer" title="Delete Section (Portal List Only)">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Quick Edit Modal -->
                        <template x-teleport="body">
                            <div x-show="openQuickEdit"
                                 style="display: none; z-index: 99999;"
                                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
                                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col"
                                     @click.outside="openQuickEdit = false">
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <i data-lucide="pencil" class="w-5 h-5 text-amber-500"></i>
                                            <span>Rename & Edit Section</span>
                                        </h3>
                                        <button @click="openQuickEdit = false" class="text-slate-400 hover:text-slate-600 transition">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                    <form method="POST" action="{{ route('admin.students.occupancy.update-section', $section) }}" class="p-6 space-y-4 text-left">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Section Name</label>
                                            <input type="text" name="name" value="{{ $section->name }}" required placeholder="e.g. ALI IBN ABI TALIB"
                                                   class="w-full h-11 px-3.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Grade Level</label>
                                                <select name="grade_level" required class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                                    @foreach(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g)
                                                        <option value="{{ $g }}" @selected($section->grade_level === $g)>{{ $g }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Learning Mode <span class="text-slate-400 font-normal">(optional)</span></label>
                                                <select name="learning_mode" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                                    <option value="">None / Default</option>
                                                    <option value="Flexible Online Learning" @selected($section->learning_mode === 'Flexible Online Learning')>Flexible Online Learning</option>
                                                    <option value="Face-to-Face" @selected($section->learning_mode === 'Face-to-Face')>Face-to-Face</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Shift <span class="text-slate-400 font-normal">(optional)</span></label>
                                                <select name="shift" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                                    <option value="">None / No Shift</option>
                                                    <option value="1st Shift" @selected($section->shift === '1st Shift')>1st Shift</option>
                                                    <option value="2nd Shift" @selected($section->shift === '2nd Shift')>2nd Shift</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Gender Allocation <span class="text-slate-400 font-normal">(optional)</span></label>
                                                <select name="gender" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                                    <option value="merge" @selected(($section->gender ?? 'merge') === 'merge')>Co-Ed (Merge)</option>
                                                    <option value="female" @selected($section->gender === 'female')>Girls Only</option>
                                                    <option value="male" @selected($section->gender === 'male')>Boys Only</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                                            <button type="button" @click="openQuickEdit = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md cursor-pointer">
                                                Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>

                        <!-- Collapsible Roster Row -->
                        <tr x-show="showRoster" x-cloak class="bg-slate-50/50">
                            <td colspan="3" class="p-3" style="border-bottom: 1px solid #f1f5f9;">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-[9px] font-black uppercase tracking-wider text-slate-400">Class Roster ({{ $section->occupied }} Students)</h5>
                                        <a href="{{ route('admin.students.occupancy.manage-section', $section) }}" class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-2 py-1 text-[9px] font-extrabold text-white hover:bg-emerald-700 transition active:scale-95 cursor-pointer">
                                            <i data-lucide="user-plus" class="w-3 h-3"></i>
                                            <span>Manage / Add Students</span>
                                        </a>
                                    </div>
                                    @if($section->students->isEmpty())
                                        <div class="py-3 text-center">
                                            <p class="text-[10px] font-semibold text-slate-400 italic mb-2">No students assigned to this section.</p>
                                            <a href="{{ route('admin.students.occupancy.manage-section', $section) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-1.5 text-xs font-black text-white shadow-xs hover:bg-emerald-700 transition active:scale-95 cursor-pointer">
                                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                                <span>Manage & Add Students Page</span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="max-h-48 overflow-y-auto divide-y divide-slate-200/60 pr-1">
                                            @foreach($section->students as $studentSec)
                                                @php
                                                    $student = $studentSec->student;
                                                    $applicant = $student?->applicant;
                                                    $fullName = $applicant ? html_entity_decode(implode(' ', array_filter([trim($applicant->first_name ?? ''), trim($applicant->middle_name ?? ''), trim($applicant->last_name ?? '')])), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                                @endphp
                                                <div class="py-1.5 flex items-center justify-between gap-2 text-[10px]">
                                                    <div class="min-w-0">
                                                        <a href="{{ route('admin.students.show', $student) }}" class="font-extrabold text-slate-700 hover:text-emerald-700 transition uppercase block truncate leading-tight">
                                                            {{ $fullName }}
                                                        </a>
                                                        <span class="text-[8px] font-bold text-slate-400 mt-0.5 block">{{ $student->student_number }}</span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="rounded bg-white border border-slate-200 px-1 py-0.5 text-[8px] font-bold text-slate-400 uppercase shrink-0">
                                                            {{ $applicant->learning_mode ?? 'F2F' }}
                                                        </span>
                                                        <form method="POST" action="{{ route('admin.students.occupancy.remove-student', $studentSec) }}" onsubmit="return confirm('Remove student from section?')" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-500 hover:text-rose-700 text-[10px] font-bold px-1.5 py-0.5 rounded hover:bg-rose-50 transition cursor-pointer" title="Remove student from section">
                                                                &times;
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Assign Students Modal for this Section -->
                        <template x-teleport="body">
                            <div x-show="openAssignModal"
                                 style="display: none; z-index: 99999;"
                                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
                                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh]"
                                     @click.outside="openAssignModal = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100">
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                                        <div>
                                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                                <i data-lucide="user-plus" class="w-5 h-5 text-emerald-600"></i>
                                                <span>Add Students to Section</span>
                                            </h3>
                                            <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $sectionDisplayName }} ({{ $gradeLevel }})</p>
                                        </div>
                                        <button @click="openAssignModal = false" class="text-slate-400 hover:text-slate-600 transition">
                                            <i data-lucide="x" class="w-5 h-5"></i>
                                        </button>
                                    </div>

                                    @php
                                        $gradeStudents = isset($studentsByGrade[$gradeLevel]) ? $studentsByGrade[$gradeLevel] : collect();
                                    @endphp

                                    <form method="POST" action="{{ route('admin.students.occupancy.assign-students', $section) }}" class="flex flex-col flex-1 overflow-hidden p-6">
                                        @csrf
                                        <div class="mb-3">
                                            <input type="text" x-model="searchStudent" placeholder="Search student by name or student number..."
                                                   class="w-full h-10 px-3.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                        </div>

                                        <div class="flex-1 overflow-y-auto border border-slate-200 rounded-xl p-2 divide-y divide-slate-100 max-h-72">
                                            @forelse($gradeStudents as $st)
                                                @php
                                                    $stApplicant = $st->applicant;
                                                    $stName = $stApplicant ? html_entity_decode(implode(' ', array_filter([trim($stApplicant->first_name ?? ''), trim($stApplicant->middle_name ?? ''), trim($stApplicant->last_name ?? '')])), ENT_QUOTES, 'UTF-8') : 'Student #' . $st->student_number;
                                                    $currentSec = $st->studentSection?->section;
                                                    $isAlreadyInThisSec = $currentSec && $currentSec->id === $section->id;
                                                @endphp
                                                <label x-show="!searchStudent || '{{ strtolower(addslashes($stName)) }} {{ $st->student_number }}'.includes(searchStudent.toLowerCase())"
                                                       class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition">
                                                    <div class="flex items-center gap-3 min-w-0">
                                                        <input type="checkbox" name="student_ids[]" value="{{ $st->id }}" @checked($isAlreadyInThisSec)
                                                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                        <div class="min-w-0">
                                                            <span class="block text-xs font-extrabold text-slate-800 uppercase truncate">{{ $stName }}</span>
                                                            <span class="block text-[9px] font-bold text-slate-400">ID: {{ $st->student_number }} @if($currentSec)&bull; Current: {{ $currentSec->name }}@else &bull; Unassigned @endif</span>
                                                        </div>
                                                    </div>
                                                    @if($isAlreadyInThisSec)
                                                        <span class="text-[9px] font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">Assigned</span>
                                                    @endif
                                                </label>
                                            @empty
                                                <div class="p-6 text-center text-xs text-slate-400 italic">No official student records found for {{ $gradeLevel }}.</div>
                                            @endforelse
                                        </div>

                                        <div class="flex items-center justify-end gap-2 pt-4 mt-3 border-t border-slate-100">
                                            <button type="button" @click="openAssignModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold transition shadow-md cursor-pointer">
                                                Add Selected Students
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                    </tbody>
                @endforeach
            </table>
        </div>
    </div>
</div>
