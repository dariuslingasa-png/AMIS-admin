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
                        
                        $genderLabel = $section->gender === 'male' ? 'Boys' : 'Girls';
                        if ($section->is_f2f) {
                            $sectionDisplayName = "F2F - " . $genderLabel;
                        } else {
                            $sectionDisplayName = ($section->official_name ?: ($section->name ?: 'Flexible')) . " - " . $genderLabel;
                        }
                        
                        $secLearningModeLabel = $section->is_f2f ? 'F2F' : 'Flexible';
                    @endphp
                    <tbody x-data="{ showRoster: false }">
                        <tr class="hover:bg-slate-50/40 transition" style="border-bottom: {{ $loop->last ? 'none' : '1px solid #f1f5f9' }};">
                            <!-- Section Name & Mode -->
                            <td class="py-3 pr-2">
                                <div class="font-extrabold text-slate-800 uppercase leading-snug text-xs">{{ $sectionDisplayName }}</div>
                                <div class="text-[9px] font-black text-slate-400 uppercase mt-0.5">
                                    {{ $secLearningModeLabel }} &middot; {{ $section->shift ?: '1st Shift' }}
                                </div>
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
                                    <button type="button" @click="showRoster = !showRoster" class="h-7 w-7 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50 active:scale-[0.95] transition cursor-pointer" title="View Roster">
                                        <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <a href="{{ route('admin.students.roster-print', $section) }}" target="_blank" class="h-7 w-7 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 flex items-center justify-center transition active:scale-[0.95]" title="Print Student Roster">
                                        <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <a href="{{ route('admin.students.id-roster-print', $section) }}" target="_blank" class="h-7 w-7 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 hover:bg-purple-100 flex items-center justify-center transition active:scale-[0.95]" title="Export Section ID Cards Document (Google Docs / MS Word)">
                                        <i data-lucide="contact" class="h-3.5 w-3.5"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.students.occupancy.delete-section', $section) }}"
                                          onsubmit="return confirm('Delete section &quot;{{ $section->displayName }}&quot;?\n\nNote: This will only remove the section from the portal list. Microsoft Teams app and student records will NOT be deleted.')"
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
                        <!-- Collapsible Roster Row -->
                        <tr x-show="showRoster" x-cloak class="bg-slate-50/50">
                            <td colspan="3" class="p-3" style="border-bottom: 1px solid #f1f5f9;">
                                <div class="space-y-2">
                                    <h5 class="text-[9px] font-black uppercase tracking-wider text-slate-400">Class Roster ({{ $section->occupied }} Students)</h5>
                                    @if($section->students->isEmpty())
                                        <p class="text-[10px] font-semibold text-slate-400 italic">No students assigned to this section.</p>
                                    @else
                                        <div class="max-h-40 overflow-y-auto divide-y divide-slate-200/60 pr-1">
                                            @foreach($section->students as $studentSec)
                                                @php
                                                    $student = $studentSec->student;
                                                    $applicant = $student->applicant;
                                                    $fullName = $applicant ? html_entity_decode(implode(' ', array_filter([trim($applicant->first_name ?? ''), trim($applicant->middle_name ?? ''), trim($applicant->last_name ?? '')])), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                                @endphp
                                                <div class="py-1 flex items-center justify-between gap-2 text-[10px]">
                                                    <div class="min-w-0">
                                                        <a href="{{ route('admin.students.show', $student) }}" class="font-extrabold text-slate-700 hover:text-emerald-700 transition uppercase block truncate leading-tight">
                                                            {{ $fullName }}
                                                        </a>
                                                        <span class="text-[8px] font-bold text-slate-400 mt-0.5 block">{{ $student->student_number }}</span>
                                                    </div>
                                                    <span class="rounded bg-white border border-slate-200 px-1 py-0.5 text-[8px] font-bold text-slate-400 uppercase shrink-0">
                                                        {{ $applicant->learning_mode ?? 'F2F' }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforeach
            </table>
        </div>
    </div>
</div>
