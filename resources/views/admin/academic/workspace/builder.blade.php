<x-academic.workspace-shell
    title="{{ $level === 'secondary' ? 'Senior High School Workspace' : 'Junior High School Workspace' }}"
    description="Build weekly timetables with centralized teacher, section, room, and locked-schedule conflict protection."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions>
        @if ($activeSection)
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-schedule-modal'))" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white"><i data-lucide="plus" class="h-4 w-4"></i>Add Schedule Block</button>
        @endif
    </x-slot:actions>

    <div x-data="scheduleBuilder()" @open-schedule-modal.window="openCreate()">
        <section class="no-print flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm xl:flex-row xl:items-center xl:justify-between">
            <div class="flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                <a href="{{ route('admin.academic.builder', ['level' => 'elementary', 'school_year' => $schoolYear]) }}" class="flex-1 rounded-lg px-4 py-2 text-center text-xs font-black {{ $level !== 'secondary' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500' }}">Kinder 1 – Grade 6</a>
                <a href="{{ route('admin.academic.builder', ['level' => 'secondary', 'school_year' => $schoolYear]) }}" class="flex-1 rounded-lg px-4 py-2 text-center text-xs font-black {{ $level === 'secondary' ? 'bg-white text-violet-700 shadow-sm' : 'text-slate-500' }}">Grade 7 – Grade 12</a>
            </div>
            <form method="GET" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row xl:max-w-2xl">
                <input type="hidden" name="level" value="{{ $level }}">
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">
                <select name="section" class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-bold text-slate-700">
                    @foreach ($sections as $section)
                        <option value="{{ $section->id }}" @selected($activeSection?->id === $section->id)>{{ $section->section_title }} · {{ $section->formatted_learning_mode }}{{ $section->shift ? ' · '.$section->shift : '' }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Load timetable</button>
            </form>
        </section>

        @if (! $activeSection)
            <div class="mt-6 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center"><i data-lucide="calendar-x" class="mx-auto h-9 w-9 text-slate-300"></i><h2 class="mt-4 text-lg font-black text-slate-800">No Active Sections</h2><p class="mt-1 text-sm text-slate-500">Create a section in this grade group before building its timetable.</p><a href="{{ route('admin.academic.sections') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Open Sections</a></div>
        @else
            <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <header class="flex flex-col gap-4 border-b border-slate-100 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div><p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">Active timetable</p><h2 class="mt-1 text-xl font-black text-slate-900">{{ $activeSection->section_title }}</h2><p class="mt-1 text-xs font-semibold text-slate-500">{{ $activeSection->formatted_learning_mode }}{{ $activeSection->shift ? ' · '.$activeSection->shift : '' }} · {{ $schedules->count() }} blocks · {{ $schedules->unique('subject_name')->count() }} subjects</p></div>
                    <div class="flex flex-wrap gap-2"><span class="rounded-full px-3 py-1.5 text-[10px] font-black {{ $conflicts->isEmpty() ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}"><i data-lucide="shield-check" class="mr-1 inline h-3.5 w-3.5"></i>{{ $conflicts->count() }} conflicts</span><span class="rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-black text-slate-600">{{ strtoupper($mode) }}</span></div>
                </header>

                @if ($conflicts->isNotEmpty())
                    <div class="border-b border-rose-100 bg-rose-50 p-4"><p class="text-xs font-black text-rose-800">Resolve these conflicts before publishing:</p><ul class="mt-2 list-disc space-y-1 pl-5 text-xs font-semibold text-rose-700">@foreach($conflicts as $conflict)<li>{{ $conflict['subject'] }} — {{ $conflict['message'] }}</li>@endforeach</ul></div>
                @endif

                @if ($timeRows->isEmpty())
                    <div class="px-6 py-16 text-center"><i data-lucide="calendar-plus" class="mx-auto h-10 w-10 text-slate-300"></i><h3 class="mt-4 text-lg font-black text-slate-800">No Timetable Blocks Scheduled</h3><p class="mt-1 text-sm text-slate-500">Add the first subject or fixed activity for this section.</p><button type="button" @click="openCreate()" class="mt-5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Add first block</button></div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-[960px] w-full table-fixed border-collapse text-xs">
                            <colgroup><col class="w-36"><col class="w-16">@foreach($days as $day)<col>@endforeach</colgroup>
                            <thead><tr class="bg-slate-900 text-white"><th class="p-3 text-center text-[10px] font-black uppercase tracking-wider">Time slot</th><th class="p-3 text-center text-[10px] font-black uppercase tracking-wider">MIN</th>@foreach($days as $day)<th class="border-l border-white/10 p-3 text-center text-[10px] font-black uppercase tracking-wider">{{ $day }}</th>@endforeach</tr></thead>
                            <tbody>
                                @foreach ($timeRows as $row)
                                    <tr class="border-b border-slate-200">
                                        <th class="bg-slate-50 px-3 py-4 text-center text-[11px] font-black text-slate-700">{{ $row['label'] }}</th>
                                        <td class="border-l border-slate-200 bg-slate-50 px-2 py-4 text-center text-[11px] font-black text-slate-500">{{ $row['duration'] }}</td>
                                        @if ($row['all_days'])
                                            @php $schedule = $row['all_days']; @endphp
                                            <td colspan="5" class="border-l border-slate-200 p-1.5">
                                                <button type="button" @click="openEdit(@js(['id'=>$schedule->id,'subject_id'=>$schedule->subject_id,'subject_name'=>$schedule->subject_name,'teacher_display'=>$schedule->teacher_display,'room_id'=>$schedule->room_id,'day'=>$schedule->day,'start_time'=>substr($schedule->start_time,0,5),'end_time'=>substr($schedule->end_time,0,5),'spans_all_days'=>$schedule->spans_all_days,'is_special'=>$schedule->is_special,'is_locked'=>$schedule->is_locked]))" class="min-h-16 w-full rounded-xl border-l-4 border-amber-500 bg-amber-50 px-3 py-2 text-center hover:bg-amber-100">
                                                    <span class="block text-sm font-black text-amber-900">{{ $schedule->subject_name }}</span><span class="mt-1 block text-[10px] font-bold text-amber-700">All school days{{ $schedule->is_locked ? ' · Locked' : '' }}</span>
                                                </button>
                                            </td>
                                        @else
                                            @foreach ($days as $day)
                                                @php $schedule = $row['days']->get($day); @endphp
                                                <td class="border-l border-slate-200 p-1.5 align-middle">
                                                    @if ($schedule)
                                                        @php
                                                            $tone = match($schedule->color_class) {
                                                                'quran', 'arabic', 'hadith' => 'border-violet-500 bg-violet-50 text-violet-950',
                                                                'event', 'recess' => 'border-amber-500 bg-amber-50 text-amber-950',
                                                                default => 'border-indigo-500 bg-indigo-50 text-indigo-950',
                                                            };
                                                        @endphp
                                                        <button type="button" @click="openEdit(@js(['id'=>$schedule->id,'subject_id'=>$schedule->subject_id,'subject_name'=>$schedule->subject_name,'teacher_display'=>$schedule->teacher_display,'room_id'=>$schedule->room_id,'day'=>$schedule->day,'start_time'=>substr($schedule->start_time,0,5),'end_time'=>substr($schedule->end_time,0,5),'spans_all_days'=>$schedule->spans_all_days,'is_special'=>$schedule->is_special,'is_locked'=>$schedule->is_locked]))" class="min-h-20 w-full rounded-xl border-l-4 px-3 py-2 text-left transition hover:brightness-95 {{ $tone }}">
                                                            <span class="block text-xs font-black">{{ $schedule->subject_name }}</span>
                                                            @if ($schedule->teacher_display)<span class="mt-1 block text-[10px] font-semibold opacity-80">{{ $schedule->teacher_display }}</span>@endif
                                                            @if ($schedule->room)<span class="mt-1 block text-[9px] font-bold opacity-70">{{ $schedule->room->name }}</span>@endif
                                                            @if ($schedule->is_locked)<span class="mt-1 inline-flex rounded bg-white/70 px-1.5 py-0.5 text-[8px] font-black uppercase">Locked</span>@endif
                                                        </button>
                                                    @else
                                                        <button type="button" @click="openCreate(@js($day), @js($row['start']), @js($row['end']))" class="min-h-20 w-full rounded-xl border border-dashed border-slate-200 text-slate-300 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600" title="Add block"><i data-lucide="plus" class="mx-auto h-4 w-4"></i></button>
                                                    @endif
                                                </td>
                                            @endforeach
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <footer class="flex flex-wrap gap-4 border-t border-slate-100 bg-slate-50 px-5 py-3 text-[10px] font-semibold text-slate-500"><span>Click an empty cell to add a block.</span><span>Multi-day saves are atomic.</span><span>Locked blocks cannot be edited or deleted.</span></footer>
            </section>
        @endif

        @if ($activeSection)
            <div x-cloak x-show="modal" class="fixed inset-0 z-[210] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="modal=false" @click.self="modal=false">
                <form method="POST" :action="editing ? `/academic/schedules/${form.id}` : '{{ route('admin.academic.schedules.store') }}'" class="max-h-[94vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                    @csrf <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'"><input type="hidden" name="section_id" value="{{ $activeSection->id }}"><input type="hidden" name="mode" value="{{ $mode }}"><input type="hidden" name="school_year" value="{{ $schoolYear }}"><input type="hidden" name="day" :value="form.days.join(',')"><input type="hidden" name="subject_name" :value="form.subject_name">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">{{ $activeSection->section_title }}</p><h2 class="text-lg font-black" x-text="editing ? 'Edit Schedule Block' : 'Add Schedule Block'"></h2></div><button type="button" @click="modal=false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Curriculum subject</span><select name="subject_id" x-model="form.subject_id" @change="chooseSubject($event)" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="">Custom / fixed activity</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" data-name="{{ $subject->name }}">{{ $subject->name }} · {{ $subject->grade_level }}</option>@endforeach</select></label>
                        <label class="md:col-span-2" x-show="!form.subject_id"><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Subject / activity label</span><input x-model="form.subject_name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm" placeholder="e.g. Mathematics or General Assembly"></label>
                        <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Teacher</span><select name="teacher_name" x-model="form.teacher_display" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="">Teacher pending</option>@foreach($teachers as $teacher)<option value="{{ $teacher['name'] }}">{{ $teacher['name'] }}</option>@endforeach</select></label>
                        <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Room / venue</span><select name="room_id" x-model="form.room_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="">No room / online</option>@foreach($rooms as $room)<option value="{{ $room->id }}">{{ $room->name }}</option>@endforeach</select></label>
                        <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Start time</span><input type="time" name="start_time" x-model="form.start_time" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                        <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">End time</span><input type="time" name="end_time" x-model="form.end_time" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                        <fieldset class="md:col-span-2" :disabled="editing"><legend class="mb-2 text-xs font-black uppercase text-slate-500">Schedule days</legend><div class="flex flex-wrap gap-2">@foreach($days as $day)<label class="cursor-pointer"><input type="checkbox" value="{{ $day }}" x-model="form.days" class="peer sr-only"><span class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-500 peer-checked:border-indigo-600 peer-checked:bg-indigo-600 peer-checked:text-white">{{ substr($day, 0, 3) }}</span></label>@endforeach</div></fieldset>
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"><input type="hidden" name="spans_all_days" value="0"><input type="checkbox" name="spans_all_days" value="1" x-model="form.spans_all_days" class="rounded text-indigo-600"><span class="text-xs font-bold text-slate-700">Span all school days</span></label>
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"><input type="hidden" name="is_special" value="0"><input type="checkbox" name="is_special" value="1" x-model="form.is_special" class="rounded text-indigo-600"><span class="text-xs font-bold text-slate-700">Fixed/non-subject activity</span></label>
                        <label class="md:col-span-2 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3"><input type="hidden" name="is_locked" value="0"><input type="checkbox" name="is_locked" value="1" x-model="form.is_locked" class="rounded text-amber-600"><span><span class="block text-xs font-black text-amber-900">Manually lock this schedule</span><span class="text-[10px] font-semibold text-amber-700">Locked blocks are protected from edits, deletion, and automatic replacement.</span></span></label>
                    </div>
                    <div class="flex items-center justify-between gap-2 border-t bg-slate-50 px-6 py-4"><div><button x-show="editing && !form.is_locked" type="button" @click="deleteBlock()" class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-bold text-rose-600">Delete</button></div><div class="flex gap-2"><button type="button" @click="modal=false" class="rounded-xl border bg-white px-4 py-2 text-sm font-bold text-slate-600">Cancel</button><button :disabled="form.is_locked && editing" class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-400">Save Block</button></div></div>
                </form>
                <form x-ref="deleteForm" method="POST" :action="`/academic/schedules/${form.id}`" class="hidden">@csrf @method('DELETE')</form>
            </div>
        @endif
    </div>
    <script>
        function scheduleBuilder(){ const blank=()=>({id:null,subject_id:'',subject_name:'',teacher_display:'',room_id:'',days:['Sunday'],start_time:'08:00',end_time:'09:00',spans_all_days:false,is_special:false,is_locked:false}); return {modal:false,editing:false,form:blank(),openCreate(day='Sunday',start='08:00',end='09:00'){this.form={...blank(),days:[day],start_time:start,end_time:end};this.editing=false;this.modal=true},openEdit(item){this.form={...blank(),...item,subject_id:item.subject_id||'',room_id:item.room_id||'',days:[item.day]};this.editing=true;this.modal=true},chooseSubject(event){const option=event.target.selectedOptions[0];if(option&&option.dataset.name)this.form.subject_name=option.dataset.name},deleteBlock(){if(confirm('Delete this schedule block and its matching weekly siblings?'))this.$refs.deleteForm.submit()}} }
    </script>
</x-academic.workspace-shell>
