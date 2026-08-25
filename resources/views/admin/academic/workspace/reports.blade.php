<x-academic.workspace-shell
    title="Schedules Reports & Export Portal"
    description="Generate printable weekly timetable reports and CSV exports by section, teacher, or room."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions><a href="{{ route('admin.academic.reports.export', request()->query()) }}" class="no-print inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><i data-lucide="download" class="h-4 w-4"></i>Export CSV</a><button type="button" onclick="window.print()" class="no-print inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white"><i data-lucide="printer" class="h-4 w-4"></i>Print Timetable</button></x-slot:actions>

    <section class="no-print rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                @foreach(['section'=>'Section View','teacher'=>'Teacher View','room'=>'Room View'] as $value=>$label)
                    <a href="{{ route('admin.academic.reports',['type'=>$value,'school_year'=>$schoolYear]) }}" class="rounded-lg px-3 py-2 text-xs font-black {{ $type===$value?'bg-white text-indigo-700 shadow-sm':'text-slate-500' }}">{{ $label }}</a>
                @endforeach
            </div>
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="school_year" value="{{ $schoolYear }}">
            <select name="entity" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-bold text-slate-700">
                @foreach($entities as $entity)
                    @php
                        $entityId=(string)data_get($entity,'id');
                        $entityName=$type==='teacher'?data_get($entity,'name'):($type==='room'?data_get($entity,'name'):data_get($entity,'section_title'));
                    @endphp
                    <option value="{{ $entityId }}" @selected($selected===$entityId)>{{ $entityName }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Generate report</button>
        </form>
    </section>

    @php
        $reportRows=$schedules->groupBy(fn($row)=>substr($row->start_time,0,5).'|'.substr($row->end_time,0,5))->sortKeys();
        $reportDays=['Sunday','Monday','Tuesday','Wednesday','Thursday'];
        $selectedEntity=$entities->first(fn($entity)=>(string)data_get($entity,'id')===$selected);
        $reportTitle=$type==='teacher'?data_get($selectedEntity,'name'):($type==='room'?data_get($selectedEntity,'name'):data_get($selectedEntity,'section_title'));
    @endphp
    <section class="overflow-hidden rounded-3xl border-2 border-slate-300 bg-white shadow-sm print:border-slate-900 print:shadow-none">
        <header class="border-b border-slate-200 px-6 py-6 text-center"><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-800">Al Munawwara Islamic School</p><h2 class="mt-2 text-xl font-black text-slate-900">{{ $reportTitle ?: 'No report target selected' }}</h2><p class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">Official Weekly Academic Schedule · SY {{ str_replace('-','–',$schoolYear) }}</p></header>
        @if($schedules->isEmpty())
            <div class="px-6 py-16 text-center"><i data-lucide="calendar-x" class="mx-auto h-10 w-10 text-slate-300"></i><h3 class="mt-4 text-lg font-black text-slate-800">No Timetable Blocks Scheduled</h3><p class="mt-1 text-sm text-slate-500">The selected {{ $type }} has no matching records.</p></div>
        @else
            <div class="overflow-x-auto"><table class="min-w-[920px] w-full table-fixed border-collapse text-xs"><colgroup><col class="w-36"><col class="w-16">@foreach($reportDays as $day)<col>@endforeach</colgroup><thead><tr class="bg-emerald-900 text-white"><th class="p-3 text-[10px] font-black uppercase">Time</th><th class="p-3 text-[10px] font-black uppercase">MIN</th>@foreach($reportDays as $day)<th class="border-l border-white/10 p-3 text-[10px] font-black uppercase">{{ $day }}</th>@endforeach</tr></thead><tbody>
                @foreach($reportRows as $timeKey=>$rows)
                    @php [$start,$end]=explode('|',$timeKey); $duration=$rows->first()->endMinutes()-$rows->first()->startMinutes(); @endphp
                    <tr class="border-b border-slate-200"><th class="bg-slate-50 px-3 py-4 text-center text-[10px] font-black">{{ date('g:i A',strtotime($start)) }}–{{ date('g:i A',strtotime($end)) }}</th><td class="border-l bg-slate-50 px-2 py-4 text-center text-[10px] font-black text-slate-500">{{ $duration }}</td>
                        @foreach($reportDays as $day)
                            @php $dayRows=$rows->filter(fn($row)=>$row->day===$day||$row->spans_all_days); @endphp
                            <td class="border-l border-slate-200 p-1.5 align-top">@foreach($dayRows as $row)<div class="mb-1 rounded-lg border-l-4 {{ $row->is_special?'border-amber-500 bg-amber-50':'border-indigo-500 bg-indigo-50' }} px-2 py-2 text-center"><p class="font-black text-slate-900">{{ $row->subject_name }}</p>@if($type!=='section')<p class="mt-0.5 text-[9px] font-bold text-slate-600">{{ $row->section?->section_title }}</p>@endif @if($type!=='teacher'&&$row->teacher_display)<p class="mt-0.5 text-[9px] font-semibold text-slate-500">{{ $row->teacher_display }}</p>@endif @if($type!=='room'&&$row->room)<p class="mt-0.5 text-[9px] font-semibold text-slate-500">{{ $row->room->name }}</p>@endif</div>@endforeach</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody></table></div>
        @endif
        <footer class="flex flex-wrap justify-between gap-3 border-t border-slate-200 px-6 py-4 text-[10px] font-semibold text-slate-400"><span>Generated {{ now()->format('M j, Y g:i A') }}</span><span>{{ $schedules->count() }} schedule blocks · centralized conflict validation</span></footer>
    </section>
</x-academic.workspace-shell>
