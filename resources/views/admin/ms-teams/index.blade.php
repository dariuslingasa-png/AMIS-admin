<x-admin-layout title="Academic Workspace — Class Workspace">
    @php
        $breadcrumbs = [
            ['label' => 'Class Workspace', 'href' => null]
        ];
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

    <div class="analytics-page flex flex-col gap-6">
        <!-- Glassmorphic Command Hero Banner -->
        <div class="relative overflow-hidden p-6 md:p-8 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-2xl border border-emerald-700/30 shadow-sm text-white">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-emerald-500/10 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-teal-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-emerald-500/20 text-emerald-300 rounded-full border border-emerald-500/30 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Academic sections
                    </span>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Class Sections Directory</h1>
                    <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                        Configure sections, set up schedules, assign academic courses, and enroll students into active classes.
                    </p>
                </div>
                <button type="button" @click="createModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-emerald-50 active:bg-emerald-100 text-emerald-800 font-bold text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-sm hover:scale-[1.02]">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    Add Section
                </button>
            </div>
        </div>

        @php
            $f2fCount = $sections->where('learning_mode', 'Face-to-Face')->count();
            $flexCount = $sections->filter(fn($s) => str_contains($s->learning_mode ?? '', 'Flexible'))->count();
            $totalSubjects = $sections->sum('subjects_count');
        @endphp

        <!-- Telemetry metric cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-emerald-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Total Sections</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-650">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-955">{{ $stats['total_sections'] }}</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-blue-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Face-to-Face</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-655">
                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-955">{{ $f2fCount }}</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-purple-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Flexible Online</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-50 text-purple-650">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-955">{{ $flexCount }}</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-amber-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Subjects Set Up</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-650">
                        <i data-lucide="book-marked" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-955">{{ $totalSubjects }}</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-indigo-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Enrolled Students</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-50 text-indigo-650">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-955">{{ number_format($stats['total_enrolled']) }}</span>
                </div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-xs">
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="search" x-model="search" placeholder="Search sections..." class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl pl-10 pr-4 py-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            </div>
        </div>

        <!-- Section Header -->
        <div class="flex items-center justify-between mt-2">
            <div>
                <h2 class="text-base font-bold text-slate-800 tracking-wide">Active Class Workspace Sections</h2>
                <p class="text-xs text-slate-400 font-light mt-0.5">Manage and organize class rosters and academic course workspaces</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-100/80">{{ $sections->count() }} Sections</span>
        </div>

        @php
            $gradeOrder = [
                'Kinder 1' => 1, 'Kinder 2' => 2,
                'Grade 1' => 3, 'Grade 2' => 4, 'Grade 3' => 5, 'Grade 4' => 6,
                'Grade 5' => 7, 'Grade 6' => 8, 'Grade 7' => 9, 'Grade 8' => 10,
                'Grade 9' => 11, 'Grade 10' => 12, 'Grade 11' => 13, 'Grade 12' => 14
            ];

            $groupedSections = $sections->groupBy('grade_level')->sortBy(function ($items, $key) use ($gradeOrder) {
                return $gradeOrder[$key] ?? 99;
            });
        @endphp

        <!-- Sections Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @forelse ($groupedSections as $grade => $gradeSections)
                <div class="bg-white rounded-2xl border border-slate-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between"
                     x-show="search === '' || @js($gradeSections->map(fn($s) => strtolower($s->grade_level . ' ' . $s->section_title . ' ' . $s->name))->toArray()).some(str => str.includes(search.toLowerCase()))">
                    <div>
                        @php
                            $firstSection = $gradeSections->first();
                            $gradeAdvisor = $firstSection ? $firstSection->grade_advisor : null;
                        @endphp
                        <!-- Grade Card Header -->
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-800 flex items-center justify-center">
                                    <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    @if(isset($gradeTeams[$grade]))
                                        <a href="{{ $gradeTeams[$grade]->team_url }}" target="_blank" class="hover:underline flex items-center gap-1.5 group/grade" title="Open Grade-Level Teams Workspace">
                                            <h2 class="text-sm font-bold text-slate-800 tracking-tight uppercase group-hover/grade:text-emerald-700">{{ $grade }}</h2>
                                            <svg class="w-3.5 h-3.5 text-purple-600 shrink-0 opacity-85 hover:opacity-100 transition-opacity" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12.5 12a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2zM9.5 2A2.5 2.5 0 0 0 7 4.5v15A2.5 2.5 0 0 0 9.5 22h5a2.5 2.5 0 0 0 2.5-2.5v-15A2.5 2.5 0 0 0 14.5 2h-5zM9.5 3.5h5a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-15a1 1 0 0 1 1-1z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <h2 class="text-sm font-bold text-slate-800 tracking-tight uppercase">{{ $grade }}</h2>
                                    @endif
                                    @if($gradeAdvisor)
                                        <span class="text-[10px] font-bold text-teal-700 block mt-0.5 uppercase tracking-wide">
                                            Advisor: {{ $gradeAdvisor->teacher_name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                {{ $gradeSections->count() }} {{ Str::plural('Section', $gradeSections->count()) }}
                            </span>
                        </div>

                        <!-- Sections List within this Grade Card -->
                        <div class="space-y-3">
                            @foreach($gradeSections as $section)
                                @php
                                    $isFlex = str_contains($section->formatted_learning_mode, 'Flexible');
                                    $modeBadgeColor = $isFlex ? 'bg-purple-100/60 text-purple-700' : 'bg-blue-100/60 text-blue-700';
                                    $modeLabel = $section->formatted_learning_mode;
                                    $genderBadgeColor = $section->gender === 'male' ? 'bg-indigo-100/60 text-indigo-700' : 'bg-rose-100/60 text-rose-700';
                                    $genderLabel = $section->gender === 'male' ? 'Boys' : 'Girls';
                                @endphp
                                <div class="p-4 bg-slate-50/70 rounded-xl border border-slate-100 hover:border-slate-200/80 transition-colors flex flex-col gap-3.5"
                                     x-show="search === '' || '{{ strtolower($section->grade_level) }} {{ strtolower($section->section_title) }} {{ strtolower($section->name) }}'.includes(search.toLowerCase())">
                                    
                                    <!-- Top Row: Name and Manage Button -->
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-black text-slate-800 uppercase tracking-tight truncate flex-1">{{ $section->name ?? 'UNNAMED' }}</span>
                                        <a href="{{ route('admin.ms-teams.show', $section) }}" 
                                           class="inline-flex items-center gap-1 bg-emerald-800 hover:bg-emerald-700 text-white font-bold text-xxs px-3 py-2 rounded-xl transition shadow-3xs shrink-0">
                                            <span>Manage</span>
                                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Badges & Stats Row -->
                                    <div class="flex flex-wrap items-center gap-1.5 justify-between">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold {{ $modeBadgeColor }}">
                                                {{ $modeLabel }}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold {{ $genderBadgeColor }}">
                                                {{ $genderLabel }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2.5 text-[10px] font-bold text-slate-400">
                                            <span class="flex items-center gap-0.5">
                                                <i data-lucide="user-check" class="w-3 h-3 text-slate-400"></i>
                                                {{ $section->enrolled_count }}
                                            </span>
                                            <span>&middot;</span>
                                            <span class="flex items-center gap-0.5">
                                                <i data-lucide="book-open" class="w-3 h-3 text-slate-400"></i>
                                                {{ $section->subjects_count }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Bottom Row: Teams Sync Status -->
                                    <div class="pt-2.5 border-t border-slate-100 flex items-center justify-between gap-3">
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">Teams Status</span>
                                        @if($section->ms_team_id)
                                            <a href="{{ $section->ms_team_url }}" target="_blank" 
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl border border-purple-100 bg-purple-50 text-purple-700 hover:bg-purple-100 hover:border-purple-200 transition text-[9px] font-extrabold"
                                               title="Open Microsoft Teams Workspace">
                                                <div class="w-4.5 h-4.5 rounded bg-purple-600 text-white flex items-center justify-center shrink-0">
                                                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12.5 12a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2zM9.5 2A2.5 2.5 0 0 0 7 4.5v15A2.5 2.5 0 0 0 9.5 22h5a2.5 2.5 0 0 0 2.5-2.5v-15A2.5 2.5 0 0 0 14.5 2h-5zM9.5 3.5h5a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-15a1 1 0 0 1 1-1z"/>
                                                    </svg>
                                                </div>
                                                <span>Connected</span>
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl border border-slate-100 bg-slate-50 text-slate-400 text-[9px] font-extrabold">
                                                <i data-lucide="help-circle" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                                Not Linked
                                            </span>
                                        @endif
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white border border-gray-200 rounded-2xl p-10 text-center shadow-xs">
                    <div class="max-w-md mx-auto flex flex-col items-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 mb-4">
                            <i data-lucide="school" class="w-6 h-6"></i>
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900 mb-1">No Active Sections</h3>
                        <p class="text-xs text-slate-500 font-light mb-5">Start by creating class sections for your school year.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Create Section Modal -->
    <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs" 
         x-show="createModal" x-cloak x-transition @click.self="if(!progressMode) createModal = false">
        <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-6 flex flex-col gap-4 border border-slate-150 animate-scaleUp">
            <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <span class="admin-modal-title text-base font-extrabold text-slate-950">Create Grade Section</span>
                    <div class="text-[11px] text-slate-400 font-light mt-0.5">Initializes a new grade-level class group</div>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" x-show="!progressMode" @click="createModal = false">&times;</button>
            </div>
            
            <div class="space-y-4" x-show="!progressMode">
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Grade Level *</label>
                    <select x-model="grade" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                        <option value="Kinder 1">Kinder 1</option><option value="Kinder 2">Kinder 2</option><option value="Grade 1">Grade 1</option><option value="Grade 2">Grade 2</option><option value="Grade 3">Grade 3</option><option value="Grade 4">Grade 4</option><option value="Grade 5">Grade 5</option><option value="Grade 6">Grade 6</option><option value="Grade 7">Grade 7</option><option value="Grade 8">Grade 8</option><option value="Grade 9">Grade 9</option><option value="Grade 10">Grade 10</option><option value="Grade 11">Grade 11</option><option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Learning Mode *</label>
                    <select x-model="mode" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                        <option value="Face-to-Face">Face-to-Face</option>
                        <option value="Flexible Online Learning">Flexible Online Learning</option>
                    </select>
                </div>
                <div x-show="mode !== 'Flexible Online Learning'" class="flex flex-col gap-1" x-transition>
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Gender *</label>
                    <select x-model="genderSingle" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none"><option value="male">Boys Only</option><option value="female">Girls Only</option></select>
                </div>
                <div x-show="mode === 'Flexible Online Learning'" class="grid grid-cols-2 gap-4" x-transition>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Shift *</label>
                        <div class="space-y-2 text-xs font-bold text-slate-700 mt-1">
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" value="1st Shift" x-model="shifts" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> 1st Shift</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" value="2nd Shift" x-model="shifts" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> 2nd Shift</label>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Gender *</label>
                        <div class="space-y-2 text-xs font-bold text-slate-700 mt-1">
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" value="male" x-model="genders" class="rounded border-slate-300 text-emerald-650 focus:ring-emerald-500"> Boys</label>
                            <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" value="female" x-model="genders" class="rounded border-slate-300 text-emerald-650 focus:ring-emerald-500"> Girls</label>
                        </div>
                    </div>
                </div>
                <div x-show="mode === 'Flexible Online Learning' && previewList.length > 0" class="rounded-xl bg-emerald-50 border border-emerald-100 p-3.5 text-xs text-emerald-800 space-y-1" x-transition>
                    <div>Will create <strong x-text="previewList.length"></strong> section(s):</div>
                    <template x-for="p in previewList"><div x-text="'• ' + p" class="font-extrabold"></div></template>
                </div>
                <div x-show="mode === 'Flexible Online Learning' && previewList.length === 0" class="rounded-xl bg-amber-50 border border-amber-100 p-3.5 text-xs font-bold text-amber-800" x-transition>
                    No official flexible section preset found for this grade, shift, and gender combination.
                </div>
                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-bold text-slate-655 hover:bg-slate-50 border border-slate-200 rounded-xl transition">Cancel</button>
                    <button type="button" @click="startCreating()" class="px-4 py-2 text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-700 rounded-xl transition" :disabled="mode === 'Flexible Online Learning' && !previewList.length">Create Section</button>
                </div>
            </div>

            <!-- Progress Loader -->
            <div class="space-y-4 pt-2" x-show="progressMode" x-transition>
                <div class="space-y-2 max-h-48 overflow-y-auto p-1 bg-slate-50 border border-slate-150 rounded-xl">
                    <template x-for="(row, idx) in progressRows">
                        <div class="flex items-center gap-2.5 text-xs font-bold text-slate-700 py-1 px-2 hover:bg-slate-100/50 rounded-lg">
                            <span class="shrink-0 flex items-center">
                                <template x-if="row.status === 'pending'"><span class="h-4 w-4 rounded-full border-2 border-slate-200"></span></template>
                                <template x-if="row.status === 'spinning'"><svg class="animate-spin h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></template>
                                <template x-if="row.status === 'done'"><svg class="h-4 w-4 text-emerald-655" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></template>
                                <template x-if="row.status === 'error'"><svg class="h-4 w-4 text-rose-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></template>
                            </span>
                            <span x-text="row.title + (row.error ? ' — ' + row.error : '')"></span>
                        </div>
                    </template>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full bg-emerald-655 rounded-full transition-all duration-300" :style="'width: ' + progressPercent + '%'"></div>
                </div>
                <div x-text="progressLabel" class="text-xxs font-bold text-center text-slate-500 uppercase tracking-wider"></div>
                <div x-show="progressLabel === 'Done!'" class="text-center pt-2" x-transition><button type="button" @click="location.reload()" class="w-full px-4 py-2 text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-700 rounded-xl transition">Close & Reload</button></div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs" 
         x-show="editModal" x-cloak x-transition @click.self="if(!editSaving) editModal = false">
        <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-6 flex flex-col gap-4 border border-slate-150 animate-scaleUp">
            <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                <div>
                    <span class="admin-modal-title text-base font-extrabold text-slate-950">Rename Section</span>
                    <div class="text-[11px] text-slate-400 font-light mt-0.5">Renames the class group</div>
                </div>
                <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" x-show="!editSaving" @click="editModal = false">&times;</button>
            </div>
            <div class="space-y-4">
                <div x-show="editError" class="rounded-xl bg-rose-50 border border-rose-100 p-3 text-xs font-bold text-rose-800" x-text="editError"></div>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Section Name (optional)</label>
                    <input type="text" x-model="editName" placeholder="e.g. UTHMAN IBN AFFAN" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" :disabled="editSaving">
                </div>
                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button type="button" @click="editModal = false" class="px-4 py-2 text-xs font-bold text-slate-655 hover:bg-slate-50 border border-slate-200 rounded-xl transition" :disabled="editSaving">Cancel</button>
                    <button type="button" @click="saveEdit()" class="px-4 py-2 text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-700 rounded-xl transition" :disabled="editSaving" x-text="editSaving ? 'Saving...' : 'Save Changes'"></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function getFlexibleSections(grade, shifts, genders) {
        let list = [];
        shifts.forEach(shift => {
            genders.forEach(gender => {
                list.push({
                    grade: grade,
                    shift: shift,
                    gender: gender,
                    name: null,
                    prefix: getGradePrefix(grade),
                    genderLabel: gender === 'male' ? 'Boys' : 'Girls'
                });
            });
        });
        return list;
    }

    function getGradePrefix(grade) {
        if (grade === 'Kinder 1') return 'K1'; if (grade === 'Kinder 2') return 'K2';
        return 'G' + grade.replace('Grade ', '');
    }
    </script>
</x-admin-layout>
