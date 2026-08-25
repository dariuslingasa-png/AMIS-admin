<x-academic.workspace-shell
    title="Faculty Workload Tracker"
    description="Monitor unique section-subject assignments against each teacher's maximum weekly teaching load."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    @if ($selectedTeacher)
        <section x-data="{ tab: 'overview' }" class="space-y-5">
            <div class="no-print flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <a href="{{ route('admin.academic.workload', ['school_year' => $schoolYear]) }}" class="inline-flex items-center gap-1.5 text-xs font-black text-indigo-700 hover:underline"><i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>Back to faculty workloads</a>
                    <h2 class="mt-3 text-2xl font-black text-slate-900">{{ $selectedTeacher['name'] }}</h2>
                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $selectedTeacher['dept'] ?: 'Unassigned Department' }} · {{ $selectedTeacher['status'] }}</p>
                </div>
                <span class="rounded-full px-3 py-1.5 text-xs font-black {{ $selectedTeacher['assigned_hours'] > $selectedTeacher['max_load'] ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $selectedTeacher['assigned_hours'] > $selectedTeacher['max_load'] ? 'Overloaded' : 'Healthy Load' }}</span>
            </div>

            <div class="no-print flex gap-1 overflow-x-auto border-b border-slate-200">
                @foreach (['overview' => 'Overview', 'schedule' => 'Weekly Schedule', 'subjects' => 'Subjects', 'analytics' => 'Load Analytics'] as $value => $label)
                    <button type="button" @click="tab='{{ $value }}'" :class="tab==='{{ $value }}' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500'" class="shrink-0 border-b-2 px-4 py-3 text-xs font-black">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="tab==='overview'" class="grid gap-4 md:grid-cols-4">
                @foreach ([['Assigned Hours', $selectedTeacher['assigned_hours']], ['Maximum Load', $selectedTeacher['max_load']], ['Remaining', $selectedTeacher['remaining_hours']], ['Assignments', $selectedTeacher['assignments_count']]] as [$label, $value])
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900">{{ $value }}</p></article>
                @endforeach
            </div>

            <div x-cloak x-show="tab==='schedule'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[720px] text-left text-xs"><thead class="bg-slate-900 text-white"><tr><th class="px-4 py-3">Day</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Section</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Room</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($selectedSchedules as $row)<tr><td class="px-4 py-3 font-bold">{{ $row->spans_all_days ? 'All school days' : $row->day }}</td><td class="px-4 py-3">{{ date('g:i A',strtotime($row->start_time)) }}–{{ date('g:i A',strtotime($row->end_time)) }}</td><td class="px-4 py-3">{{ $row->section?->section_title }}</td><td class="px-4 py-3 font-black">{{ $row->subject_name }}</td><td class="px-4 py-3">{{ $row->room?->name ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="px-6 py-12 text-center text-slate-400">No scheduled blocks for this academic year.</td></tr>@endforelse</tbody></table>
            </div>

            <div x-cloak x-show="tab==='subjects'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse($selectedSchedules->where('is_special', false)->groupBy(fn($row) => $row->subject_id ?: mb_strtolower($row->subject_name)) as $rows)
                    @php $subjectRow=$rows->first(); @endphp
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h3 class="font-black text-slate-900">{{ $subjectRow->subject_name }}</h3><p class="mt-2 text-xs font-semibold text-slate-500">{{ $rows->pluck('section.section_title')->filter()->unique()->join(', ') }}</p><p class="mt-3 text-[10px] font-black uppercase tracking-wide text-indigo-700">{{ $rows->count() }} weekly block(s)</p></article>
                @empty
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center text-sm font-semibold text-slate-400">No subject assignments found.</div>
                @endforelse
            </div>

            <div x-cloak x-show="tab==='analytics'" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-end justify-between gap-4"><div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Weekly utilization</p><p class="mt-2 text-4xl font-black text-slate-900">{{ $selectedTeacher['utilization'] }}%</p></div><p class="text-right text-xs font-semibold text-slate-500">{{ $selectedTeacher['assigned_hours'] }} of {{ $selectedTeacher['max_load'] }} hours assigned</p></div>
                <div class="mt-5 h-4 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $selectedTeacher['assigned_hours'] > $selectedTeacher['max_load'] ? 'bg-rose-500' : ($selectedTeacher['utilization'] >= 80 ? 'bg-amber-500' : 'bg-indigo-600') }}" style="width: {{ min(100,$selectedTeacher['utilization']) }}%"></div></div>
            </div>
        </section>
    @else
        <x-academic.workspace-filters placeholder="Search faculty workload..." />

    @php
        $totalAssigned = $workloads->sum('assigned_hours');
        $overloaded = $workloads->filter(fn($row) => $row['assigned_hours'] > $row['max_load'])->count();
        $available = $workloads->where('remaining_hours', '>', 0)->count();
    @endphp
    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Assigned Hours</p><p class="mt-2 text-3xl font-black text-slate-900">{{ round($totalAssigned,1) }}</p><p class="mt-1 text-xs font-semibold text-slate-500">Across {{ $workloads->count() }} faculty members</p></article>
        <article class="rounded-2xl border {{ $overloaded ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} p-5 shadow-sm"><p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Overloaded Faculty</p><p class="mt-2 text-3xl font-black {{ $overloaded ? 'text-rose-700' : 'text-emerald-700' }}">{{ $overloaded }}</p><p class="mt-1 text-xs font-semibold text-slate-500">Requires schedule review</p></article>
        <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm"><p class="text-[10px] font-black uppercase tracking-wider text-slate-500">Available Faculty</p><p class="mt-2 text-3xl font-black text-indigo-700">{{ $available }}</p><p class="mt-1 text-xs font-semibold text-slate-500">Has remaining weekly capacity</p></article>
    </div>

    @if($workloads->isEmpty())
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center"><h2 class="text-lg font-black text-slate-800">No workload records found</h2></div>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($workloads as $teacher)
                @php $over = $teacher['assigned_hours'] > $teacher['max_load']; @endphp
                <a href="{{ route('admin.academic.workload', ['teacher' => $teacher['id'], 'school_year' => $schoolYear]) }}" class="block rounded-2xl border {{ $over ? 'border-rose-200' : 'border-slate-200' }} bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3"><div><h2 class="text-base font-black text-slate-900">{{ $teacher['name'] }}</h2><p class="mt-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ $teacher['dept'] ?: 'Unassigned Department' }}</p></div><span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase {{ strtolower($teacher['status'])==='active'?'bg-emerald-50 text-emerald-700':'bg-slate-100 text-slate-500' }}">{{ $teacher['status'] }}</span></div>
                    <div class="mt-5 flex items-end justify-between"><div><p class="text-3xl font-black {{ $over ? 'text-rose-700' : 'text-slate-900' }}">{{ $teacher['assigned_hours'] }}<span class="ml-1 text-sm text-slate-400">/ {{ $teacher['max_load'] }} hrs</span></p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $teacher['assignments_count'] }} unique section-subject assignments</p></div><span class="text-sm font-black {{ $over ? 'text-rose-700' : 'text-indigo-700' }}">{{ $teacher['utilization'] }}%</span></div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $over ? 'bg-rose-500' : ($teacher['utilization'] >= 80 ? 'bg-amber-500' : 'bg-indigo-500') }}" style="width: {{ min(100,$teacher['utilization']) }}%"></div></div>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-[10px] font-bold"><span class="text-slate-400">Remaining capacity</span><span class="{{ $over ? 'text-rose-700' : 'text-emerald-700' }}">{{ $over ? round($teacher['assigned_hours']-$teacher['max_load'],1).' hrs over' : $teacher['remaining_hours'].' hrs' }}</span></div>
                    <p class="mt-4 border-t border-slate-100 pt-4 text-[10px] font-black text-indigo-700">View full workload profile →</p>
                </a>
            @endforeach
        </div>
    @endif
    @endif
</x-academic.workspace-shell>
