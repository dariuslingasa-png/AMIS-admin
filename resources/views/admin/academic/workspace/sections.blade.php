<x-academic.workspace-shell
    title="Academic Class Sections"
    description="Configure grade levels, modality, shift, gender grouping, tracks, and schedule rosters."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions>
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-section-modal'))" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700"><i data-lucide="plus" class="h-4 w-4"></i>Create Section</button>
    </x-slot:actions>

    <div x-data="sectionDirectory()" @open-section-modal.window="openCreate()">
        <x-academic.workspace-filters placeholder="Search section name, grade, modality, or strand..." :grades="true" :status="true" :school-year="$schoolYear" :grade-options="$gradeOptions">
            <select name="mode" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700">
                <option value="">All modalities</option>
                <option value="f2f" @selected(request('mode') === 'f2f')>Face-to-Face</option>
                <option value="online" @selected(request('mode') === 'online')>Flexible Online</option>
            </select>
        </x-academic.workspace-filters>

        @if ($sections->isEmpty())
            <div class="mt-6 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center"><i data-lucide="layers" class="mx-auto h-9 w-9 text-slate-300"></i><h2 class="mt-4 text-lg font-black text-slate-800">No Sections Found</h2><p class="mt-1 text-sm text-slate-500">Adjust the filters or create a class section.</p></div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($sections as $section)
                    <article class="flex min-h-64 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <span class="rounded-xl bg-indigo-50 p-2.5 text-indigo-600"><i data-lucide="layers" class="h-5 w-5"></i></span>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $section->academic_status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $section->academic_status }}</span>
                            </div>
                            <h2 class="mt-4 text-base font-black leading-snug text-slate-900">{{ $section->section_title }}</h2>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $section->formatted_learning_mode }}{{ $section->shift && $section->shift !== 'F2F' ? ' · '.$section->shift : '' }}</p>
                            <div class="mt-4 flex flex-wrap gap-1.5">
                                <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-600">{{ ucfirst($section->gender === 'merge' ? 'mixed' : ($section->gender === 'na' ? 'not specified' : $section->gender)) }}</span>
                                @if ($section->track_strand)<span class="rounded-lg border border-violet-100 bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700">{{ $section->track_strand }}</span>@endif
                            </div>
                            <dl class="mt-5 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center">
                                <div><dt class="text-[9px] font-black uppercase text-slate-400">Students</dt><dd class="mt-1 text-sm font-black text-slate-800">{{ $section->students_count }}</dd></div>
                                <div><dt class="text-[9px] font-black uppercase text-slate-400">Subjects</dt><dd class="mt-1 text-sm font-black text-slate-800">{{ $section->subjects_count }}</dd></div>
                                <div><dt class="text-[9px] font-black uppercase text-slate-400">Blocks</dt><dd class="mt-1 text-sm font-black text-slate-800">{{ $section->schedules_count }}</dd></div>
                            </dl>
                        </div>
                        <div class="mt-5 flex items-center justify-between">
                            <a href="{{ route('admin.academic.builder', ['level' => in_array($section->grade_level, array_slice($gradeOptions, 8)) ? 'secondary' : 'elementary', 'section' => $section->id, 'school_year' => $schoolYear]) }}" class="text-xs font-black text-indigo-700 hover:underline">Open timetable</a>
                            <div class="flex gap-2">
                                <button type="button" @click="openEdit(@js($section->only(['id','name','grade_level','learning_mode','shift','gender','track_strand','academic_status'])))" class="rounded-lg border border-slate-200 p-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-700"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                @if ($section->academic_status === 'active')
                                    <form method="POST" action="{{ route('admin.academic.sections.destroy', $section) }}" onsubmit="return confirm('Archive this section? Students and schedules will be preserved.')">@csrf @method('DELETE')<button class="rounded-lg border border-slate-200 p-2 text-amber-600 hover:bg-amber-50"><i data-lucide="archive" class="h-4 w-4"></i></button></form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $sections->links() }}</div>
        @endif

        <div x-cloak x-show="modal" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="modal=false" @click.self="modal=false">
            <form method="POST" :action="editing ? `/academic/sections/${form.id}` : '{{ route('admin.academic.sections.store') }}'" class="w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                @csrf <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">Section registry</p><h2 class="text-lg font-black text-slate-900" x-text="editing ? 'Edit Class Section' : 'Register Class Section'"></h2></div><button type="button" @click="modal=false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Section name</span><input name="name" x-model="form.name" placeholder="e.g. Abu Sufyan Ibn Al-Harith" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Grade level</span><select name="grade_level" x-model="form.grade_level" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@foreach($gradeOptions as $grade)<option value="{{ $grade }}">{{ $grade }}</option>@endforeach</select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Learning modality</span><select name="learning_mode" x-model="form.learning_mode" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option>Face-to-Face</option><option>Flexible Online Learning</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Shift</span><select name="shift" x-model="form.shift" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="F2F">F2F</option><option>1st Shift</option><option>2nd Shift</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Gender group</span><select name="gender" x-model="form.gender" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="male">Male</option><option value="female">Female</option><option value="merge">Mixed</option><option value="na">Not specified</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Track / strand</span><input name="track_strand" x-model="form.track_strand" placeholder="Optional" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Status</span><select name="academic_status" x-model="form.academic_status" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"><button type="button" @click="modal=false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600">Cancel</button><button class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white">Save Section</button></div>
            </form>
        </div>
    </div>
    <script>
        function sectionDirectory() {
            const blank = () => ({ id: null, name: '', grade_level: 'Grade 1', learning_mode: 'Face-to-Face', shift: 'F2F', gender: 'merge', track_strand: '', academic_status: 'active' });
            return { modal: false, editing: false, form: blank(), openCreate() { this.form=blank(); this.editing=false; this.modal=true; }, openEdit(section) { this.form={...blank(),...section}; this.editing=true; this.modal=true; } };
        }
    </script>
</x-academic.workspace-shell>
