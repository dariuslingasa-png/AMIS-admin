<x-academic.workspace-shell
    title="Faculty Load & Grade Tracking"
    description="Monitor official teacher assignments, weekly teaching hours, student rosters, and real-time DepEd grade encoding completion rates."
    :school-year="$schoolYear"
>
    {{-- Top Metrics Bar --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Faculty</span>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalFaculty }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Approved / Published</span>
            <p class="text-2xl font-black text-emerald-800 mt-1">{{ $totalApprovedFaculty }}</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-700">Submitted for Review</span>
            <p class="text-2xl font-black text-amber-800 mt-1">{{ $totalSubmittedFaculty }}</p>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50/50 p-4 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-blue-700">Encoding in Progress</span>
            <p class="text-2xl font-black text-blue-800 mt-1">{{ $totalInProgressFaculty }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Not Started</span>
            <p class="text-2xl font-black text-slate-700 mt-1">{{ $totalNotStartedFaculty }}</p>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/50 p-4 shadow-xs">
            <span class="text-[10px] font-black uppercase tracking-wider text-indigo-700">Overall Completion</span>
            <p class="text-2xl font-black text-indigo-800 mt-1">{{ $overallCompletion }}%</p>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.academic.teacher-tracking') }}" class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <input type="hidden" name="school_year" value="{{ $schoolYear }}">

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Quarter selector --}}
                <div class="flex items-center rounded-xl bg-slate-100 p-1">
                    @foreach(['1st Quarter', '2nd Quarter', '3rd Quarter', '4th Quarter'] as $q)
                        <button type="submit" name="quarter" value="{{ $q }}" class="rounded-lg px-3 py-1.5 text-xs font-black transition {{ $selectedQuarter === $q ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-500 hover:text-slate-900' }}">
                            {{ $q }}
                        </button>
                    @endforeach
                </div>

                {{-- Department selector --}}
                <select name="department" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs focus:border-indigo-500 focus:outline-hidden">
                    <option value="All" {{ $selectedDept === 'All' ? 'selected' : '' }}>All Departments</option>
                    <option value="Kindergarten" {{ $selectedDept === 'Kindergarten' ? 'selected' : '' }}>Kindergarten</option>
                    <option value="Elementary" {{ $selectedDept === 'Elementary' ? 'selected' : '' }}>Elementary Department</option>
                    <option value="Junior High" {{ $selectedDept === 'Junior High' ? 'selected' : '' }}>Junior High School</option>
                    <option value="Senior High" {{ $selectedDept === 'Senior High' ? 'selected' : '' }}>Senior High School</option>
                </select>

                {{-- Status selector --}}
                <select name="status" onchange="this.form.submit()" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-xs focus:border-indigo-500 focus:outline-hidden">
                    <option value="All" {{ $selectedStatus === 'All' ? 'selected' : '' }}>All Progress Status</option>
                    <option value="Approved" {{ $selectedStatus === 'Approved' ? 'selected' : '' }}>Approved / Published</option>
                    <option value="Submitted" {{ $selectedStatus === 'Submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="In Progress" {{ $selectedStatus === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Not Started" {{ $selectedStatus === 'Not Started' ? 'selected' : '' }}>Not Started</option>
                </select>
            </div>

            {{-- Search bar --}}
            <div class="relative min-w-[240px]">
                <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search teacher name or email..." class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 py-2 text-xs font-bold text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-hidden">
            </div>
        </form>
    </div>

    {{-- Teacher Tracking Roster Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/75 text-[10px] font-black uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-4">Faculty Member</th>
                        <th class="py-3.5 px-3">Department</th>
                        <th class="py-3.5 px-3 text-center">Assigned Loads</th>
                        <th class="py-3.5 px-3 text-center">Weekly Hours</th>
                        <th class="py-3.5 px-3 text-center">Total Students</th>
                        <th class="py-3.5 px-4">{{ $selectedQuarter }} Progress</th>
                        <th class="py-3.5 px-3 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teachers as $t)
                        <tr class="hover:bg-slate-50/50 transition">
                            {{-- Teacher Info --}}
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    @if($t['photo'])
                                        <img src="{{ $t['photo'] }}" alt="{{ $t['name'] }}" class="h-9 w-9 rounded-full object-cover border border-slate-200 shrink-0">
                                    @else
                                        <div class="h-9 w-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-black text-xs shrink-0">
                                            {{ substr($t['name'], 0, 2) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-extrabold text-slate-900 text-sm leading-tight truncate">{{ $t['name'] }}</p>
                                        <p class="text-[11px] font-medium text-slate-500 truncate">{{ $t['email'] ?: 'No school email configured' }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Department --}}
                            <td class="py-3.5 px-3">
                                <span class="inline-block rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">
                                    {{ $t['dept'] }}
                                </span>
                            </td>

                            {{-- Assigned Loads --}}
                            <td class="py-3.5 px-3 text-center">
                                <span class="font-black text-slate-800">{{ $t['subjects_count'] }}</span>
                                <span class="text-[10px] text-slate-400 block font-semibold">{{ $t['sections_count'] }} section(s)</span>
                            </td>

                            {{-- Weekly Hours --}}
                            <td class="py-3.5 px-3 text-center">
                                <span class="font-black text-slate-800">{{ $t['weekly_hours'] }} hrs</span>
                            </td>

                            {{-- Total Students --}}
                            <td class="py-3.5 px-3 text-center">
                                <span class="font-black text-indigo-700">{{ $t['total_students'] }}</span>
                            </td>

                            {{-- Progress Bar --}}
                            <td class="py-3.5 px-4 min-w-[160px]">
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mb-1">
                                    <span>{{ $t['approved_count'] }} of {{ $t['total_expected'] }} graded</span>
                                    <span class="font-black text-slate-800">{{ $t['completion_rate'] }}%</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $t['completion_rate'] >= 100 ? 'bg-emerald-500' : ($t['completion_rate'] > 0 ? 'bg-amber-500' : 'bg-slate-300') }}" style="width: {{ min(100, $t['completion_rate']) }}%;"></div>
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-3.5 px-3 text-center">
                                @if($t['grading_status'] === 'Approved')
                                    <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[9px] font-black uppercase text-emerald-700">Approved</span>
                                @elseif($t['grading_status'] === 'Submitted')
                                    <span class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-[9px] font-black uppercase text-amber-700">Submitted</span>
                                @elseif($t['grading_status'] === 'In Progress')
                                    <span class="rounded-full bg-blue-50 border border-blue-200 px-2.5 py-1 text-[9px] font-black uppercase text-blue-700">In Progress</span>
                                @else
                                    <span class="rounded-full bg-slate-100 border border-slate-200 px-2.5 py-1 text-[9px] font-black uppercase text-slate-500">Not Started</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5" x-data="{ openModal: false }">
                                    <button type="button" @click="openModal = true" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-black text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Review Loads
                                    </button>

                                    {{-- Modal for reviewing individual sections & subjects --}}
                                    <div x-show="openModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 text-left" style="display: none;">
                                        <div @click.away="openModal = false" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                                <div>
                                                    <h3 class="text-base font-black text-slate-900">{{ $t['name'] }} — Subject Loads</h3>
                                                    <p class="text-xs font-semibold text-slate-500">{{ $selectedQuarter }} · SY {{ $schoolYear }}</p>
                                                </div>
                                                <button type="button" @click="openModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 text-base">✕</button>
                                            </div>

                                            <div class="space-y-3">
                                                @forelse($t['assigned_subjects'] as $sub)
                                                    @php
                                                        $subGrades = $sub->grades;
                                                        $isSubApproved = $subGrades->whereIn('status', ['approved', 'published'])->count() > 0;
                                                        $isSubSubmitted = $subGrades->where('status', 'submitted')->count() > 0;
                                                    @endphp
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-3.5">
                                                        <div>
                                                            <div class="flex items-center gap-2">
                                                                <h4 class="font-extrabold text-slate-900 text-sm">{{ $sub->subject_name }}</h4>
                                                                <span class="rounded bg-white border border-slate-200 px-1.5 py-0.5 text-[9px] font-black text-slate-600">{{ $sub->section?->name }}</span>
                                                            </div>
                                                            <p class="text-xs text-slate-500 font-medium mt-1">Schedule: <strong class="text-slate-700">{{ $sub->schedule ?: 'Pending' }}</strong></p>
                                                        </div>

                                                        <div class="flex items-center gap-2">
                                                            @if($isSubApproved)
                                                                <span class="rounded-full bg-emerald-100 text-emerald-800 px-2.5 py-1 text-[10px] font-black uppercase">Approved</span>
                                                            @else
                                                                <form method="POST" action="{{ route('admin.academic.teacher-tracking.approve', $sub->id) }}">
                                                                    @csrf
                                                                    <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
                                                                    <input type="hidden" name="school_year" value="{{ $schoolYear }}">
                                                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-700 shadow-xs cursor-pointer">
                                                                        Approve & Publish
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-xs text-slate-400 text-center py-4">No active section subjects found.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 font-semibold">
                                No faculty records match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-academic.workspace-shell>
