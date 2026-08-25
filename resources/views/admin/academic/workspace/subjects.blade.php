<x-academic.workspace-shell
    title="Subjects Course Directory"
    description="Manage academic subjects, weekly credit hours, grade levels, and semester coverage."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <x-slot:actions>
        <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-subject-modal'))" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">
            <i data-lucide="plus" class="h-4 w-4"></i>Add Subject Course
        </button>
    </x-slot:actions>

    <div x-data="subjectDirectory()" @open-subject-modal.window="openCreate()">
        <x-academic.workspace-filters placeholder="Search by subject name, code, or description..." :grades="true" :status="true" />

        @if ($subjects->isEmpty())
            <div class="mt-6 rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
                <i data-lucide="book-open" class="mx-auto h-9 w-9 text-slate-300"></i>
                <h2 class="mt-4 text-lg font-black text-slate-800">No Subjects Found</h2>
                <p class="mt-1 text-sm text-slate-500">Adjust the filters or create the first subject course.</p>
            </div>
        @else
            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($subjects as $subject)
                    <article class="flex min-h-56 flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">{{ $subject->code ?: 'No course code' }}</p>
                                    <h2 class="mt-1 text-lg font-black leading-snug text-slate-900">{{ $subject->name }}</h2>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase {{ $subject->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $subject->status }}</span>
                            </div>
                            <p class="mt-3 line-clamp-2 text-xs font-medium leading-5 text-slate-500">{{ $subject->description ?: 'No description supplied.' }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="rounded-lg border border-indigo-100 bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700">{{ $subject->grade_level }}</span>
                                <span class="rounded-lg border border-violet-100 bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700">{{ $subject->semester ?: 'Full Year' }}</span>
                                <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-600">{{ $subject->weekly_hours ? rtrim(rtrim(number_format((float) $subject->weekly_hours, 2), '0'), '.') : '—' }} hrs/week</span>
                            </div>
                        </div>
                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                            <span class="text-[10px] font-bold text-slate-400">{{ $subject->active_teacher_assignments_count }} active teacher assignment(s)</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="openEdit(@js($subject->only(['id', 'name', 'code', 'description', 'weekly_hours', 'semester', 'grade_level', 'school_year', 'status'])))" class="rounded-lg border border-slate-200 p-2 text-slate-500 hover:bg-indigo-50 hover:text-indigo-700" title="Edit subject"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                <form method="POST" action="{{ $subject->status === 'active' ? route('admin.academic.subjects.archive', $subject) : route('admin.academic.subjects.restore', $subject) }}">
                                    @csrf @method('PATCH')
                                    <button class="rounded-lg border border-slate-200 p-2 {{ $subject->status === 'active' ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}" title="{{ $subject->status === 'active' ? 'Archive' : 'Restore' }} subject"><i data-lucide="{{ $subject->status === 'active' ? 'archive' : 'archive-restore' }}" class="h-4 w-4"></i></button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="mt-6">{{ $subjects->links() }}</div>
        @endif

        <div x-cloak x-show="modal" class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/55 p-4" @keydown.escape.window="modal=false" @click.self="modal=false">
            <form method="POST" :action="editing ? `/academic/subjects/${form.id}` : '{{ route('admin.academic.subjects.store') }}'" class="w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl">
                @csrf
                <input type="hidden" name="_method" :value="editing ? 'PATCH' : 'POST'">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
                    <div><p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-600">Course catalog</p><h2 class="text-lg font-black text-slate-900" x-text="editing ? 'Edit Subject Course' : 'Create Subject Course'"></h2></div>
                    <button type="button" @click="modal=false" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100"><i data-lucide="x" class="h-5 w-5"></i></button>
                </div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Subject name</span><input name="name" x-model="form.name" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Course code</span><input name="code" x-model="form.code" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-indigo-400"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Weekly hours</span><input type="number" min="0.25" max="60" step="0.25" name="weekly_hours" x-model="form.weekly_hours" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-indigo-400"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Grade level</span><select name="grade_level" x-model="form.grade_level" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@foreach($gradeOptions as $grade)<option value="{{ $grade }}">{{ $grade }}</option>@endforeach</select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Semester</span><select name="semester" x-model="form.semester" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="Full Year">Full Year</option><option>1st Semester</option><option>2nd Semester</option></select></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">School year</span><input name="school_year" x-model="form.school_year" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></label>
                    <label><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Status</span><select name="status" x-model="form.status" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                    <label class="md:col-span-2"><span class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Description</span><textarea name="description" x-model="form.description" rows="3" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea></label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"><button type="button" @click="modal=false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600">Cancel</button><button class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700">Save Subject</button></div>
            </form>
        </div>
    </div>

    <script>
        function subjectDirectory() {
            const blank = () => ({ id: null, name: '', code: '', description: '', weekly_hours: 3, semester: 'Full Year', grade_level: 'Grade 1', school_year: @js($schoolYear), status: 'active' });
            return { modal: false, editing: false, form: blank(), openCreate() { this.form = blank(); this.editing = false; this.modal = true; }, openEdit(subject) { this.form = { ...blank(), ...subject, semester: subject.semester || 'Full Year' }; this.editing = true; this.modal = true; } };
        }
    </script>
</x-academic.workspace-shell>
