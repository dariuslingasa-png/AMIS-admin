<x-academic.workspace-shell
    title="SY {{ str_replace('-', '–', $schoolYear) }} Existing Schedule Copy"
    description="Audit encoded subjects, scheduled days, weekly minutes, teacher ownership, and room allocation from the official database copy."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions>@if($activeSection)<a href="{{ route('admin.academic.builder', ['level'=>$level,'section'=>$activeSection->id,'school_year'=>$schoolYear]) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white"><i data-lucide="square-pen" class="h-4 w-4"></i>Encode Schedule Block</a>@endif</x-slot:actions>

    <section class="no-print rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1"><a href="{{ route('admin.academic.schedule-copy', ['level'=>'elementary','school_year'=>$schoolYear]) }}" class="rounded-lg px-3 py-2 text-xs font-black {{ $level !== 'secondary' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500' }}">Kinder–Grade 6</a><a href="{{ route('admin.academic.schedule-copy', ['level'=>'secondary','school_year'=>$schoolYear]) }}" class="rounded-lg px-3 py-2 text-xs font-black {{ $level === 'secondary' ? 'bg-white text-violet-700 shadow-sm' : 'text-slate-500' }}">Grade 7–12</a></div>
            <input type="hidden" name="level" value="{{ $level }}">
            <input type="hidden" name="school_year" value="{{ $schoolYear }}">
            <select name="section" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-bold text-slate-700">@foreach($sections as $section)<option value="{{ $section->id }}" @selected($activeSection?->id === $section->id)>{{ $section->section_title }} · {{ $section->formatted_learning_mode }}{{ $section->shift ? ' · '.$section->shift : '' }}</option>@endforeach</select>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Audit section</button>
        </form>
    </section>

    @if (!$activeSection)
        <div class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center"><h2 class="text-lg font-black text-slate-800">No section schedules available</h2></div>
    @else
        @php
            $uniqueSubjects = $schedules->where('is_special', false)->pluck('subject_name')->unique();
            $weeklyMinutes = $schedules->sum(fn($row) => $row->endMinutes() - $row->startMinutes());
            $fiveDaySubjects = $schedules->where('is_special', false)->groupBy('subject_name')->filter(fn($rows) => $rows->pluck('day')->unique()->count() === 5)->count();
        @endphp
        <div class="grid gap-4 md:grid-cols-3">
            @foreach([['Total Subjects',$uniqueSubjects->count(),'book-open','indigo'],['Weekly Hours',round($weeklyMinutes/60,1),'clock-3','emerald'],['Five-Day Subjects',$fiveDaySubjects,'calendar-check','violet']] as [$label,$value,$icon,$tone])
                <article class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div><p class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900">{{ $value }}</p></div><span class="rounded-xl bg-slate-100 p-3 text-slate-600"><i data-lucide="{{ $icon }}" class="h-6 w-6"></i></span></article>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-black text-slate-900">{{ $activeSection->section_title }}</h2><p class="mt-1 text-xs font-semibold text-slate-500">Official database schedule audit · {{ $schedules->count() }} encoded blocks</p></header>
            <div class="overflow-x-auto"><table class="w-full min-w-[780px] text-left text-xs"><thead class="bg-slate-900 text-white"><tr><th class="px-4 py-3 text-[10px] font-black uppercase">Subject / Activity</th><th class="px-4 py-3 text-[10px] font-black uppercase">Teacher</th><th class="px-4 py-3 text-[10px] font-black uppercase">Days</th><th class="px-4 py-3 text-[10px] font-black uppercase">Time</th><th class="px-4 py-3 text-[10px] font-black uppercase">Weekly MIN</th><th class="px-4 py-3 text-[10px] font-black uppercase">Room</th><th class="px-4 py-3 text-[10px] font-black uppercase">State</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($schedules->groupBy(fn($row) => implode('|',[$row->subject_name,$row->teacher_key,$row->start_time,$row->end_time,$row->spans_all_days])) as $rows)
                    @php $first=$rows->first(); $minutes=($first->endMinutes()-$first->startMinutes())*($first->spans_all_days?5:$rows->count()); @endphp
                    <tr class="hover:bg-slate-50"><td class="px-4 py-4 font-black text-slate-900">{{ $first->subject_name }}@if($first->is_special)<span class="ml-2 rounded bg-amber-50 px-1.5 py-0.5 text-[8px] font-black uppercase text-amber-700">Fixed</span>@endif</td><td class="px-4 py-4 font-semibold text-slate-600">{{ $first->teacher_display ?: 'Teacher pending' }}</td><td class="px-4 py-4 font-semibold text-slate-600">{{ $first->spans_all_days ? 'All school days' : $rows->pluck('day')->join(', ') }}</td><td class="px-4 py-4 font-semibold text-slate-600">{{ date('g:i A',strtotime($first->start_time)) }}–{{ date('g:i A',strtotime($first->end_time)) }}</td><td class="px-4 py-4 font-black text-slate-800">{{ $minutes }}</td><td class="px-4 py-4 font-semibold text-slate-600">{{ $first->room?->name ?: '—' }}</td><td class="px-4 py-4"><span class="rounded-full px-2 py-1 text-[9px] font-black uppercase {{ $first->is_locked ? 'bg-amber-50 text-amber-700' : ($first->teacher_status==='unmatched'?'bg-rose-50 text-rose-700':'bg-emerald-50 text-emerald-700') }}">{{ $first->is_locked ? 'Locked' : $first->teacher_status }}</span></td></tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-sm font-semibold text-slate-400">No encoded schedule blocks for this section.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    @endif
</x-academic.workspace-shell>
