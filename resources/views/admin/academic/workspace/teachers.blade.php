<x-academic.workspace-shell
    title="Teachers Registry"
    description="Register academic faculty, set department and maximum weekly load, and monitor active teaching assignments."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions><button type="button" onclick="window.dispatchEvent(new CustomEvent('open-teacher-modal'))" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white"><i data-lucide="user-plus" class="h-4 w-4"></i>Register Teacher</button></x-slot:actions>

    <div x-data="teacherDirectory()" @open-teacher-modal.window="openCreate()">
        <x-academic.workspace-filters placeholder="Search teacher, email, or department..." :status="true" :school-year="$schoolYear" />

        @if ($teachers->isEmpty())
            <div class="mt-6 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center"><i data-lucide="users" class="mx-auto h-9 w-9 text-slate-300"></i><h2 class="mt-4 text-lg font-black text-slate-800">No Teachers Found</h2><p class="mt-1 text-sm text-slate-500">Adjust the filters or register a faculty member.</p></div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($teachers as $teacher)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 text-sm font-black text-indigo-700">
                                @if ($teacher['photo'] ?? null)<img src="{{ asset($teacher['photo']) }}" alt="" class="h-full w-full object-cover">@else{{ collect(explode(' ', preg_replace('/^(TEACHER|USTADH|USTADHA|USTADZ|ALIM|SIR)\s+/i', '', $teacher['name'])))->filter()->map(fn($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}@endif
                            </div>
                            <div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-2"><h2 class="truncate text-base font-black text-slate-900">{{ $teacher['name'] }}</h2><span class="rounded-full px-2 py-1 text-[9px] font-black uppercase {{ strtolower($teacher['status']) === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $teacher['status'] }}</span></div><p class="mt-1 truncate text-xs font-semibold text-slate-500">{{ $teacher['email'] }}</p><p class="mt-2 text-[10px] font-bold uppercase tracking-wide text-indigo-600">{{ $teacher['dept'] ?: 'Unassigned Department' }}</p></div>
                        </div>
                        <div class="mt-5 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center">
                            <div><p class="text-[9px] font-black uppercase text-slate-400">Subjects</p><p class="mt-1 text-sm font-black text-slate-800">{{ $teacher['subject_count'] ?? 0 }}</p></div>
                            <div><p class="text-[9px] font-black uppercase text-slate-400">Max Load</p><p class="mt-1 text-sm font-black text-slate-800">{{ $teacher['max_load'] ?? 40 }}h</p></div>
                            <div><p class="text-[9px] font-black uppercase text-slate-400">Load</p><p class="mt-1 text-sm font-black text-slate-800">{{ $teacher['load_status'] ?? '—' }}</p></div>
                        </div>
                        <div class="mt-4 flex items-center justify-between"><span class="text-[10px] font-bold text-slate-400">Microsoft sync: {{ ($teacher['microsoft_sync'] ?? true) ? 'Enabled' : 'Disabled' }}</span><button type="button" @click="openEdit(@js(collect($teacher)->only(['id','name','first_name','middle_name','last_name','email','dept','status','max_load','microsoft_sync'])->all()))" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-indigo-50 hover:text-indigo-700"><i data-lucide="pencil" class="h-3.5 w-3.5"></i>Edit</button></div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $teachers->links() }}</div>
        @endif

        <div x-cloak x-show="modal" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="modal=false" @click.self="modal=false">
            <form method="POST" action="{{ route('admin.academic.teachers') }}" enctype="multipart/form-data" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl">
                @csrf <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'"><input type="hidden" name="id" x-model="form.id">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-6 py-5"><div><p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">Faculty registry</p><h2 class="text-lg font-black" x-text="editing ? 'Edit Teacher Information' : 'Register New Faculty'"></h2></div><button type="button" @click="modal=false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Prefix</span><select name="prefix" x-model="form.prefix" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option>TEACHER</option><option>USTADZ</option><option>USTADHA</option><option>ALIM</option><option>ALIMA</option><option>SIR</option><option>MS</option><option>MRS</option><option>MR</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">First name</span><input name="first_name" x-model="form.first_name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Middle name</span><input name="middle_name" x-model="form.middle_name" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Last name</span><input name="last_name" x-model="form.last_name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">School email</span><input type="email" name="email" x-model="form.email" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Department</span><select name="dept" x-model="form.dept" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="">Unassigned</option><option>Elementary Department</option><option>High School Department</option><option>Islamic School and Arabic Language Department</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Maximum weekly load</span><input type="number" min="1" max="80" step="0.5" name="max_load" x-model="form.max_load" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Status</span><select name="status" x-model="form.status" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option>Active</option><option>Inactive</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase text-slate-500">Profile photo</span><input type="file" name="photo" accept="image/*" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs"></label>
                    <label class="md:col-span-2 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"><input type="hidden" name="microsoft_sync" value="0"><input type="checkbox" name="microsoft_sync" value="1" x-model="form.microsoft_sync" class="rounded border-slate-300 text-indigo-600"><span class="text-sm font-bold text-slate-700">Synchronize this faculty account with Microsoft 365</span></label>
                </div>
                <div class="sticky bottom-0 flex justify-end gap-2 border-t bg-slate-50 px-6 py-4"><button type="button" @click="modal=false" class="rounded-xl border bg-white px-4 py-2 text-sm font-bold text-slate-600">Cancel</button><button class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white">Save Teacher</button></div>
            </form>
        </div>
    </div>
    <script>
        function teacherDirectory(){
            const blank=()=>({id:'',prefix:'TEACHER',first_name:'',middle_name:'',last_name:'',email:'',dept:'',status:'Active',max_load:40,microsoft_sync:true});
            const splitName=(teacher)=>{ const parts=String(teacher.name||'').trim().split(/\s+/); const allowed=['TEACHER','USTADZ','USTADHA','ALIM','ALIMA','SIR','MS','MRS','MR']; let prefix=allowed.includes(String(parts[0]||'').replace('.','').toUpperCase())?String(parts.shift()).replace('.','').toUpperCase():'TEACHER'; return {prefix,first_name:teacher.first_name||parts.shift()||'',last_name:teacher.last_name||parts.pop()||'',middle_name:teacher.middle_name||parts.join(' ')} };
            return {modal:false,editing:false,form:blank(),openCreate(){this.form=blank();this.editing=false;this.modal=true},openEdit(teacher){this.form={...blank(),...teacher,...splitName(teacher),microsoft_sync:teacher.microsoft_sync!==false};this.editing=true;this.modal=true}};
        }
    </script>
</x-academic.workspace-shell>
