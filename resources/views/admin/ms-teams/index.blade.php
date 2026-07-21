<x-admin-layout title="Class Sections Directory">
    @php
        $breadcrumbs = [['label' => 'Class Sections', 'href' => null]];
    @endphp

    <div x-data="{
        createModal: false, editModal: false, editId: null, editName: '', editError: '', editSaving: false,
        mode: 'Flexible Online Learning', grade: 'Kinder 2', shifts: ['1st Shift'], genders: ['male', 'female'],
        genderSingle: 'male', schoolYear: @js(config('services.school.year')), previewList: [], progressMode: false,
        progressPercent: 0, progressLabel: '', progressRows: [], search: '',
        init() {
            this.$watch('mode', () => this.updatePreview()); this.$watch('grade', () => this.updatePreview());
            this.$watch('shifts', () => this.updatePreview()); this.$watch('genders', () => this.updatePreview());
            this.updatePreview();
        },
        updatePreview() {
            if (this.mode !== 'Flexible Online Learning') { this.previewList = []; return; }
            this.previewList = getFlexibleSections(this.grade, this.shifts, this.genders)
                .map(item => item.name ? `${item.prefix} - ${item.name} [${item.genderLabel} & ${item.shift}]` : `${item.prefix} [${item.genderLabel} & ${item.shift}]`);
        },
        async startCreating() {
            let combos = [];
            if (this.mode === 'Flexible Online Learning') {
                getFlexibleSections(this.grade, this.shifts, this.genders).forEach(item => {
                    combos.push({ grade_level: item.grade, learning_mode: this.mode, shift: item.shift, gender: item.gender, name: item.name, school_year: this.schoolYear });
                });
            } else { combos.push({ grade_level: this.grade, learning_mode: this.mode, shift: null, gender: this.genderSingle, name: null, school_year: this.schoolYear }); }
            if (!combos.length) return;
            this.progressMode = true;
            this.progressRows = combos.map(c => ({ title: c.learning_mode === 'Flexible Online Learning' ? `${c.grade_level} ${c.shift} ${c.gender === 'male' ? 'Boys' : 'Girls'}` : `${c.grade_level} F2F ${c.gender === 'male' ? 'Boys' : 'Girls'}`, status: 'pending', error: '' }));
            for (let i = 0; i < combos.length; i++) {
                this.progressLabel = `Creating ${i + 1} of ${combos.length}…`; this.progressRows[i].status = 'spinning';
                try {
                    const res = await fetch('{{ route("admin.ms-teams.store-single") }}', {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify(combos[i]),
                    });
                    const data = await res.json();
                    this.progressRows[i].status = data.success ? 'done' : 'error';
                    if (!data.success) this.progressRows[i].error = data.message || 'Failed';
                } catch (e) { this.progressRows[i].status = 'error'; }
                this.progressPercent = Math.round(((i + 1) / combos.length) * 100);
            }
            this.progressLabel = 'Done!';
        },
        openEdit(id, name) { this.editId = id; this.editName = name; this.editError = ''; this.editSaving = false; this.editModal = true; },
        async saveEdit() {
            this.editSaving = true; this.editError = '';
            try {
                const res = await fetch(`/ms-teams/${this.editId}/update`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.editName.trim() }),
                });
                const data = await res.json();
                if (data.success) { this.editModal = false; location.reload(); }
                else { this.editError = data.message || 'Failed to update'; this.editSaving = false; }
            } catch (e) { this.editError = 'Network error. Try again.'; this.editSaving = false; }
        }
    }">

    @php
        $f2fCount      = $sections->where('learning_mode', 'Face-to-Face')->count();
        $flexCount     = $sections->filter(fn($s) => str_contains($s->learning_mode ?? '', 'Flexible'))->count();
        $totalSubjects = $sections->sum('subjects_count');

        $gradeOrder = [
            'Kinder 1' => 1, 'Kinder 2' => 2,
            'Grade 1' => 3, 'Grade 2' => 4, 'Grade 3' => 5, 'Grade 4' => 6,
            'Grade 5' => 7, 'Grade 6' => 8, 'Grade 7' => 9, 'Grade 8' => 10,
            'Grade 9' => 11, 'Grade 10' => 12, 'Grade 11' => 13, 'Grade 12' => 14
        ];
        $groupedSections = $sections->groupBy('grade_level')->sortBy(fn($v, $k) => $gradeOrder[$k] ?? 99);
    @endphp

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-700 mb-1">Academic Workspace</p>
            <h1 class="text-2xl font-extrabold text-slate-950 tracking-tight">Class Sections</h1>
            <p class="mt-1 text-sm text-slate-500">Manage grade sections, assign subjects, and enroll students.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.ms-teams.roster') }}"
               class="inline-flex items-center gap-2 border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-sm px-4 py-2.5 rounded-xl transition-all shadow-xs">
                <i data-lucide="users-round" class="w-4 h-4"></i>
                Student Roster
            </a>
            <a href="{{ route('admin.ms-teams.structure') }}"
               class="inline-flex items-center gap-2 border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-800 font-bold text-sm px-4 py-2.5 rounded-xl transition-all shadow-xs">
                <i data-lucide="network" class="w-4 h-4"></i>
                Teams Structure
            </a>
            <button type="button" @click="createModal = true"
                class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-all shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Add Section
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-bold text-emerald-800">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-5 flex items-center gap-3 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm font-bold text-rose-800">
            <i data-lucide="alert-circle" class="w-4 h-4 text-rose-500 shrink-0"></i>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Sections</div>
            <div class="text-3xl font-black text-slate-900">{{ $stats['total_sections'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Face-to-Face</div>
            <div class="text-3xl font-black text-slate-900">{{ $f2fCount }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Flexible Online</div>
            <div class="text-3xl font-black text-slate-900">{{ $flexCount }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Enrolled (MS)</div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($stats['total_enrolled']) }}</div>
        </div>
    </div>

    {{-- Search + count bar --}}
    <div class="flex items-center justify-between gap-4 mb-5">
        <div class="relative w-full sm:max-w-xs">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"></i>
            <input type="search" x-model="search" placeholder="Search sections…"
                class="w-full bg-white border border-slate-200 text-slate-800 text-sm rounded-xl pl-9 pr-4 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none font-medium transition">
        </div>
        <span class="text-xs font-bold text-slate-400 shrink-0">{{ $sections->count() }} sections</span>
    </div>

    {{-- Grade cards grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($groupedSections as $grade => $gradeSections)
            @php
                $firstSection = $gradeSections->first();
                $gradeAdvisor = $firstSection?->grade_advisor;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden"
                 x-show="search === '' || '{{ strtolower($grade . ' ' . $gradeSections->pluck('name')->implode(' ')) }}'.includes(search.toLowerCase())">

                {{-- Grade card header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-extrabold text-slate-900 text-sm tracking-tight uppercase">{{ $grade }}</div>
                            @if($gradeAdvisor)
                                <div class="text-[10px] font-bold text-teal-700 mt-0.5">{{ $gradeAdvisor->teacher_name }}</div>
                            @endif
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                        {{ $gradeSections->count() }} {{ Str::plural('section', $gradeSections->count()) }}
                    </span>
                </div>

                {{-- Sections list --}}
                <div class="divide-y divide-slate-50 p-3 space-y-1">
                    @foreach($gradeSections as $section)
                        @php
                            $isFlex    = str_contains($section->learning_mode ?? '', 'Flexible');
                            $modeBg    = $isFlex ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700';
                            $genderBg  = $section->gender === 'male' ? 'bg-indigo-50 text-indigo-700' : ($section->gender === 'female' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700');
                            $genderLbl = $section->gender === 'male' ? 'Boys' : ($section->gender === 'female' ? 'Girls' : 'Merge');
                            $sectionName = $section->name ?? null;
                        @endphp
                        <div class="flex items-center justify-between gap-3 rounded-xl px-3 py-3 hover:bg-slate-50 transition-colors"
                             x-show="search === '' || '{{ strtolower($section->grade_level . ' ' . $section->name) }}'.includes(search.toLowerCase())">

                            {{-- Left: name + badges --}}
                            <div class="min-w-0 flex-1">
                                <div class="font-extrabold text-slate-900 text-sm uppercase tracking-tight truncate">
                                    {{ $sectionName ?? '—' }}
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[9px] font-bold {{ $modeBg }}">
                                        {{ $isFlex ? 'FOL' : 'F2F' }}
                                        @if($section->shift) · {{ Str::before($section->shift, ' Shift') }}S @endif
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[9px] font-bold {{ $genderBg }}">{{ $genderLbl }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                        <i data-lucide="users" class="w-2.5 h-2.5"></i>{{ $section->enrolled_count }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                        <i data-lucide="book-open" class="w-2.5 h-2.5"></i>{{ $section->subjects_count }}
                                    </span>
                                </div>
                            </div>

                            {{-- Right: actions --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button"
                                    @click.stop="openEdit({{ $section->id }}, '{{ addslashes($section->name ?? '') }}')"
                                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-400 hover:text-slate-700 hover:border-slate-300 flex items-center justify-center transition">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="{{ route('admin.ms-teams.show', $section) }}"
                                   class="inline-flex items-center gap-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-[10px] px-3 py-1.5 rounded-lg transition">
                                    Manage <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-100 rounded-2xl p-12 text-center shadow-xs">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mx-auto mb-4">
                    <i data-lucide="school" class="w-6 h-6"></i>
                </div>
                <div class="font-extrabold text-slate-900 text-sm mb-1">No Active Sections</div>
                <p class="text-xs text-slate-500 mb-4">Start by adding grade sections for the current school year.</p>
                <button type="button" @click="createModal = true"
                    class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add First Section
                </button>
            </div>
        @endforelse
    </div>

    {{-- ═══ CREATE MODAL ═══ --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm"
         x-show="createModal" x-cloak x-transition @click.self="if(!progressMode) createModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 overflow-hidden animate-scaleUp">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <span class="font-extrabold text-slate-900 text-base">Create Grade Section</span>
                <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" x-show="!progressMode" @click="createModal = false">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="px-6 py-5 space-y-4" x-show="!progressMode">
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Grade Level *</label>
                    <select x-model="grade" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                        <option value="Kinder 1">Kinder 1</option><option value="Kinder 2">Kinder 2</option>
                        <option value="Grade 1">Grade 1</option><option value="Grade 2">Grade 2</option><option value="Grade 3">Grade 3</option><option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option><option value="Grade 6">Grade 6</option><option value="Grade 7">Grade 7</option><option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option><option value="Grade 10">Grade 10</option><option value="Grade 11">Grade 11</option><option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Learning Mode *</label>
                    <select x-model="mode" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                        <option value="Face-to-Face">Face-to-Face</option>
                        <option value="Flexible Online Learning">Flexible Online Learning</option>
                    </select>
                </div>
                <div x-show="mode !== 'Flexible Online Learning'" class="flex flex-col gap-1" x-transition>
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Gender *</label>
                    <select x-model="genderSingle" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                        <option value="male">Boys Only</option><option value="female">Girls Only</option><option value="merge">Merge</option>
                    </select>
                </div>
                <div x-show="mode === 'Flexible Online Learning'" class="grid grid-cols-2 gap-4" x-transition>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Shift *</label>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer"><input type="checkbox" value="1st Shift" x-model="shifts" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> 1st Shift</label>
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer"><input type="checkbox" value="2nd Shift" x-model="shifts" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> 2nd Shift</label>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Gender *</label>
                        <div class="flex flex-col gap-1.5">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer"><input type="checkbox" value="male" x-model="genders" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Boys</label>
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer"><input type="checkbox" value="female" x-model="genders" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Girls</label>
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer"><input type="checkbox" value="merge" x-model="genders" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Merge</label>
                        </div>
                    </div>
                </div>
                <div x-show="mode === 'Flexible Online Learning' && previewList.length > 0" class="rounded-xl bg-emerald-50 border border-emerald-100 p-3 text-xs text-emerald-800 space-y-1" x-transition>
                    <div class="font-bold">Will create <strong x-text="previewList.length"></strong> section(s):</div>
                    <template x-for="p in previewList"><div x-text="'• ' + p" class="font-extrabold"></div></template>
                </div>
                <div x-show="mode === 'Flexible Online Learning' && previewList.length === 0" class="rounded-xl bg-amber-50 border border-amber-100 p-3 text-xs font-bold text-amber-800" x-transition>
                    No flexible section preset found for this combination.
                </div>
            </div>

            <div class="px-6 py-5 space-y-4" x-show="progressMode" x-transition>
                <div class="space-y-1.5 max-h-48 overflow-y-auto p-2 bg-slate-50 border border-slate-100 rounded-xl">
                    <template x-for="row in progressRows">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 py-1.5 px-2 rounded-lg">
                            <span class="shrink-0">
                                <template x-if="row.status === 'pending'"><span class="inline-block h-4 w-4 rounded-full border-2 border-slate-200"></span></template>
                                <template x-if="row.status === 'spinning'"><svg class="animate-spin h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></template>
                                <template x-if="row.status === 'done'"><svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></template>
                                <template x-if="row.status === 'error'"><svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></template>
                            </span>
                            <span x-text="row.title + (row.error ? ' — ' + row.error : '')"></span>
                        </div>
                    </template>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5"><div class="h-full bg-emerald-600 rounded-full transition-all duration-300" :style="'width: ' + progressPercent + '%'"></div></div>
                <div x-text="progressLabel" class="text-[10px] font-bold text-center text-slate-500 uppercase tracking-wider"></div>
                <div x-show="progressLabel === 'Done!'" x-transition><button type="button" @click="location.reload()" class="w-full px-4 py-2.5 text-sm font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl transition">Done — Reload</button></div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2" x-show="!progressMode">
                <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 border border-slate-200 rounded-xl transition">Cancel</button>
                <button type="button" @click="startCreating()" class="px-5 py-2 text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl transition disabled:opacity-50" :disabled="mode === 'Flexible Online Learning' && !previewList.length">Create Section</button>
            </div>
        </div>
    </div>

    {{-- ═══ RENAME MODAL ═══ --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm"
         x-show="editModal" x-cloak x-transition @click.self="if(!editSaving) editModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 border border-slate-100 overflow-hidden animate-scaleUp">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <span class="font-extrabold text-slate-900 text-base">Rename Section</span>
                <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" x-show="!editSaving" @click="editModal = false">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div x-show="editError" class="rounded-xl bg-rose-50 border border-rose-100 px-4 py-3 text-xs font-bold text-rose-800" x-text="editError"></div>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Section Name</label>
                    <input type="text" x-model="editName" placeholder="e.g. UTHMAN IBN AFFAN"
                        class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition"
                        :disabled="editSaving">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 border border-slate-200 rounded-xl transition" :disabled="editSaving">Cancel</button>
                <button type="button" @click="saveEdit()" class="px-5 py-2 text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl transition disabled:opacity-50" :disabled="editSaving" x-text="editSaving ? 'Saving…' : 'Save Changes'"></button>
            </div>
        </div>
    </div>

    <script>
    function getFlexibleSections(grade, shifts, genders) {
        let list = [];
        shifts.forEach(shift => { genders.forEach(gender => { list.push({ grade, shift, gender, name: null, prefix: getGradePrefix(grade), genderLabel: gender === 'male' ? 'Boys' : (gender === 'female' ? 'Girls' : 'Merge') }); }); });
        return list;
    }
    function getGradePrefix(grade) {
        if (grade === 'Kinder 1') return 'K1';
        if (grade === 'Kinder 2') return 'K2';
        return 'G' + grade.replace('Grade ', '');
    }
    </script>

    </div>
</x-admin-layout>
