<x-academic.workspace-shell
    title="Section Tracking & Subject Breakdown"
    description="Review official sections, enrolled student rosters, assigned subject teachers, Sunday–Thursday schedules, and grade encoding progress."
    :school-year="$schoolYear"
>
    {{-- Top Metrics Bar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Sections</span>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ $totalSectionsCount }}</p>
            <p class="text-xs font-semibold text-slate-500 mt-1">Official sections across all grades & modalities</p>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/50 p-5 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700">Enrolled Students</span>
            <p class="text-3xl font-black text-indigo-800 mt-1">{{ $totalStudentsEnrolled }}</p>
            <p class="text-xs font-semibold text-indigo-600 mt-1">Assigned to active sections</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700">{{ $selectedQuarter }} Completion</span>
            <p class="text-3xl font-black text-emerald-800 mt-1">{{ $averageCompletion }}%</p>
            <p class="text-xs font-semibold text-emerald-600 mt-1">Average grades approved per section</p>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.academic.section-tracking') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <input type="hidden" name="school_year" value="{{ $schoolYear }}">

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Quarter Selector --}}
                <div class="flex items-center rounded-xl bg-slate-100 p-1">
                    @foreach(['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'] as $q)
                        <button type="submit" name="quarter" value="{{ $q }}" class="rounded-lg px-3 py-1.5 text-xs font-black transition {{ $selectedQuarter === $q ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
                            {{ $q }}
                        </button>
                    @endforeach
                </div>

                {{-- Grade Level Selector --}}
                <select name="grade_level" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs focus:border-indigo-500 focus:outline-hidden">
                    <option value="All" {{ $selectedGrade === 'All' ? 'selected' : '' }}>All Grade Levels</option>
                    @foreach($gradeLevels as $gl)
                        <option value="{{ $gl }}" {{ $selectedGrade === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                    @endforeach
                </select>

                {{-- Modality Selector --}}
                <select name="modality" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs focus:border-indigo-500 focus:outline-hidden">
                    <option value="All" {{ $selectedMode === 'All' ? 'selected' : '' }}>All Modalities</option>
                    <option value="F2F" {{ $selectedMode === 'F2F' ? 'selected' : '' }}>Face-to-Face (F2F)</option>
                    <option value="ODL" {{ $selectedMode === 'ODL' ? 'selected' : '' }}>Flexible Online (ODL)</option>
                </select>

                {{-- Shift Selector --}}
                <select name="shift" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs focus:border-indigo-500 focus:outline-hidden">
                    <option value="All" {{ $selectedShift === 'All' ? 'selected' : '' }}>All Shifts</option>
                    <option value="1st Shift" {{ $selectedShift === '1st Shift' ? 'selected' : '' }}>1st Shift</option>
                    <option value="2nd Shift" {{ $selectedShift === '2nd Shift' ? 'selected' : '' }}>2nd Shift</option>
                </select>
            </div>

            {{-- Search Bar --}}
            <div class="relative min-w-[240px]">
                <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search section name..." class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 py-2 text-xs font-bold text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden">
            </div>
        </form>
    </div>

    {{-- Sections Grid --}}
    <div class="grid grid-cols-1 gap-6">
        @forelse($sections as $sec)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs" x-data="{ expanded: false }">
                {{-- Section Header Bar --}}
                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-sm shrink-0">
                            {{ substr($sec['grade_level'], 0, 2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-black text-slate-900 text-base leading-tight">{{ $sec['name'] }}</h3>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 border border-slate-200">{{ $sec['grade_level'] }}</span>
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase text-emerald-700 border border-emerald-200">{{ $sec['learning_mode'] }}</span>
                                @if($sec['shift'])
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase text-amber-700 border border-amber-200">{{ $sec['shift'] }}</span>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-slate-500 mt-1">
                                <strong>{{ $sec['student_count'] }}</strong> enrolled learners · <strong>{{ $sec['total_subjects'] }}</strong> academic subjects
                            </p>
                        </div>
                    </div>

                    {{-- Completion Progress & Toggle Button --}}
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Quarter Progress</span>
                            <span class="text-sm font-black text-slate-900">{{ $sec['approved_subjects'] }} / {{ $sec['total_subjects'] }} Approved</span>
                        </div>
                        <button type="button" @click="expanded = !expanded" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50 cursor-pointer flex items-center gap-1.5 shadow-xs">
                            <span x-text="expanded ? 'Hide Subjects' : 'View Subjects'">View Subjects</span>
                            <span x-text="expanded ? '▲' : '▼'">▼</span>
                        </button>
                    </div>
                </div>

                {{-- Expandable Subject Roster --}}
                <div x-show="expanded" class="p-5 border-t border-slate-100 space-y-3" style="display: none;">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Official Subject Loads & Faculty Timetables</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @forelse($sec['subjects'] as $s)
                            <div class="rounded-xl border border-slate-200 bg-white p-3.5 flex flex-col justify-between gap-3 shadow-xs">
                                <div>
                                    <div class="flex items-center justify-between gap-2">
                                        <h5 class="font-extrabold text-slate-900 text-sm">{{ $s['name'] }}</h5>
                                        @if($s['status'] === 'Approved')
                                            <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-700">Approved</span>
                                        @elseif($s['status'] === 'Submitted')
                                            <span class="rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-[9px] font-black uppercase text-amber-700">Submitted</span>
                                        @elseif($s['status'] === 'Draft')
                                            <span class="rounded-full bg-blue-50 border border-blue-200 px-2 py-0.5 text-[9px] font-black uppercase text-blue-700">Draft</span>
                                        @else
                                            <span class="rounded-full bg-slate-100 border border-slate-200 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500">Not Encoded</span>
                                        @endif
                                    </div>
                                    <p class="text-xs font-semibold text-slate-700 mt-1">Faculty: {{ $s['teacher_name'] }}</p>
                                    <p class="text-[11px] font-medium text-slate-500">{{ $s['teacher_email'] ?: 'No email' }}</p>
                                </div>

                                <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-[10px] font-black text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                                        {{ $s['schedule'] }}
                                    </span>
                                    @if($s['status'] !== 'Approved')
                                        <form method="POST" action="{{ route('admin.academic.teacher-tracking.approve', $s['id']) }}">
                                            @csrf
                                            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                                            <input type="hidden" name="school_year" value="{{ $schoolYear }}">
                                            <button type="submit" class="text-[11px] font-black text-emerald-700 hover:text-emerald-800 cursor-pointer">
                                                Approve →
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 col-span-2 text-center py-4">No academic subjects assigned to this section.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center text-slate-400 font-semibold shadow-xs">
                No sections found matching the selected filters.
            </div>
        @endforelse
    </div>
</x-academic.workspace-shell>
