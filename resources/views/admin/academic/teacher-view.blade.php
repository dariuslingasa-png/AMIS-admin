<x-admin-layout title="Teacher Profile Details">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        isResending: false,
        isToggling: false,
        isSavingSubjects: false,
        subjectIds: @js($teacher['subject_ids'] ?? []),
        subjectOptions: @js($teacher['subject_options'] ?? []),
        loadTarget: @js($teacher['load_target'] ?? 8),
        selectedSubjectName: '',
        selectedGrades: [],
        showExistModal: false,
        assignModalOpen: false,
        editModalOpen: false,
        editingSubjectId: null,
        editSubjectName: '',
        editGrade: '',
        globalAssignments: @js($globalAssignments ?? []),
        existModalTitle: 'Subject Already Assigned',
        existModalMessage: 'This subject is already in the teacher\'s handled subjects list.',
        existModalConfirm: false,
        existModalAction: 'add',
        grades: @js(str_contains($teacher['dept'], 'Elementary') ? ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'] : (str_contains($teacher['dept'], 'High') ? ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] : ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'])),
        getSubjectClass(name) {
            const lower = name.toLowerCase();
            if (lower.includes('qur')) return { bg: 'bg-sky-50 text-sky-700 border-sky-100/50', hoverBg: 'group-hover:bg-sky-600 group-hover:text-white group-hover:border-sky-605', hoverBorder: 'hover:border-sky-300' };
            if (lower.includes('hadith')) return { bg: 'bg-amber-50 text-amber-700 border-amber-100/50', hoverBg: 'group-hover:bg-amber-600 group-hover:text-white group-hover:border-amber-605', hoverBorder: 'hover:border-amber-300' };
            if (lower.includes('arabic')) return { bg: 'bg-pink-50 text-pink-700 border-pink-100/50', hoverBg: 'group-hover:bg-pink-600 group-hover:text-white group-hover:border-pink-605', hoverBorder: 'hover:border-pink-300' };
            if (lower.includes('recess')) return { bg: 'bg-rose-50 text-rose-700 border-rose-100/50', hoverBg: 'group-hover:bg-rose-600 group-hover:text-white group-hover:border-rose-605', hoverBorder: 'hover:border-rose-300' };
            if (lower.includes('meeting') || lower.includes('circle') || lower.includes('wrap')) return { bg: 'bg-violet-50 text-violet-700 border-violet-100/50', hoverBg: 'group-hover:bg-violet-600 group-hover:text-white group-hover:border-violet-605', hoverBorder: 'hover:border-violet-300' };
            return { bg: 'bg-emerald-50 text-emerald-700 border-emerald-100/50', hoverBg: 'group-hover:bg-emerald-600 group-hover:text-white group-hover:border-emerald-605', hoverBorder: 'hover:border-emerald-300' };
        },
        get subjectCount() {
            return this.subjectIds.length;
        },
        get subjects() {
            return this.subjectIds.map(val => {
                const id = Number(val);
                if (!isNaN(id)) {
                    const found = this.subjectOptions.find(opt => opt.id === id);
                    if (found) return found;
                }
                if (typeof val === 'string' && val.includes(' · ')) {
                    const parts = val.split(' · ');
                    return { id: val, name: parts[0], grade_level: parts[1] };
                }
                return { id: val, name: val, grade_level: 'New Subject' };
            });
        },
        get loadPercent() {
            return this.loadTarget > 0 ? Math.min(100, Math.round((this.subjectCount / this.loadTarget) * 100)) : 0;
        },
        get loadStatus() {
            if (this.subjectCount >= this.loadTarget) return 'Full Load';
            return this.subjectCount >= 6 ? 'Balanced Load' : 'Needs Load';
        },
        addSubject(confirmOverride = false) {
            const name = this.selectedSubjectName.trim();
            if (!name || this.selectedGrades.length === 0) return;

            let localDuplicates = [];
            let globalDuplicates = [];
            let validGrades = [];

            for (const grade of this.selectedGrades) {
                if (this.subjectCount + validGrades.length >= this.loadTarget) {
                    break;
                }

                const matched = this.subjectOptions.find(opt => 
                    opt.name.toLowerCase() === name.toLowerCase() && 
                    opt.grade_level.toLowerCase() === grade.toLowerCase()
                );
                const valueToAdd = matched ? matched.id : (name + ' · ' + grade);

                // 1. Local duplicate check
                const isLocalDuplicate = this.subjectIds.some(val => {
                    if (val === valueToAdd) return true;
                    const valObj = typeof val === 'number' || !isNaN(Number(val))
                        ? this.subjectOptions.find(opt => opt.id === Number(val))
                        : (typeof val === 'string' && val.includes(' · ') ? { name: val.split(' · ')[0], grade_level: val.split(' · ')[1] } : null);
                    
                    if (!valObj) return false;
                    
                    const addObj = matched ? matched : { name: name, grade_level: grade };
                    return valObj.name.toLowerCase() === addObj.name.toLowerCase() && 
                           valObj.grade_level.toLowerCase() === addObj.grade_level.toLowerCase();
                });

                if (isLocalDuplicate) {
                    localDuplicates.push(grade);
                    continue;
                }

                // 2. Global duplicate check (assigned to another teacher)
                const globalMatch = this.globalAssignments.find(ass => {
                    if (matched && ass.subject_id === matched.id) return true;
                    return ass.subject_name.toLowerCase() === name.toLowerCase() && 
                           ass.grade_level.toLowerCase() === grade.toLowerCase();
                });

                if (globalMatch) {
                    globalDuplicates.push({ grade: grade, teacherName: globalMatch.teacher_name, valueToAdd: valueToAdd });
                    continue;
                }

                validGrades.push(valueToAdd);
            }

            if (localDuplicates.length > 0) {
                this.existModalConfirm = false;
                this.existModalAction = 'add';
                this.existModalTitle = 'Subject Already Assigned';
                this.existModalMessage = 'This subject is already in the teacher\'s handled subjects list.';
                this.showExistModal = true;
                this.assignModalOpen = false;
                return;
            }

            if (globalDuplicates.length > 0 && !confirmOverride) {
                const teacherNames = [...new Set(globalDuplicates.map(d => d.teacherName))];
                this.existModalConfirm = true;
                this.existModalAction = 'add';
                this.existModalTitle = 'Subject Assigned to Another Teacher';
                this.existModalMessage = `This subject is already assigned to ${teacherNames.join(', ')}. Do you still want to assign it?`;
                this.showExistModal = true;
                this.assignModalOpen = false;
                return;
            }

            if (confirmOverride) {
                for (const dupe of globalDuplicates) {
                    validGrades.push(dupe.valueToAdd);
                }
            }

            if (validGrades.length > 0) {
                this.subjectIds = [...this.subjectIds, ...validGrades];
                this.selectedSubjectName = '';
                this.selectedGrades = [];
                this.showExistModal = false;
                this.assignModalOpen = false;
                this.isSavingSubjects = true;
                this.$nextTick(() => {
                    document.getElementById('subject-load').submit();
                });
            }
        },
        removeSubject(id) {
            this.subjectIds = this.subjectIds.filter(subjectId => subjectId !== id);
            this.isSavingSubjects = true;
            this.$nextTick(() => {
                document.getElementById('subject-load').submit();
            });
        },
        editSubject(subject) {
            this.editingSubjectId = subject.id;
            this.editSubjectName = subject.name;
            this.editGrade = subject.grade_level;
            this.editModalOpen = true;
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        saveSubjectEdit(confirmOverride = false) {
            const name = this.editSubjectName.trim();
            const grade = this.editGrade;
            if (!name || !grade) return;

            const matched = this.subjectOptions.find(opt => 
                opt.name.toLowerCase() === name.toLowerCase() && 
                opt.grade_level.toLowerCase() === grade.toLowerCase()
            );
            const newValue = matched ? matched.id : (name + ' · ' + grade);

            // 1. Local duplicate check
            const isLocalDuplicate = this.subjectIds.some(val => {
                if (val === this.editingSubjectId) return false;
                if (val === newValue) return true;

                const valObj = typeof val === 'number' || !isNaN(Number(val))
                    ? this.subjectOptions.find(opt => opt.id === Number(val))
                    : (typeof val === 'string' && val.includes(' · ') ? { name: val.split(' · ')[0], grade_level: val.split(' · ')[1] } : null);
                
                if (!valObj) return false;
                
                const addObj = matched ? matched : { name: name, grade_level: grade };
                return valObj.name.toLowerCase() === addObj.name.toLowerCase() && 
                       valObj.grade_level.toLowerCase() === addObj.grade_level.toLowerCase();
            });

            if (isLocalDuplicate) {
                this.existModalConfirm = false;
                this.existModalAction = 'edit';
                this.existModalTitle = 'Subject Already Assigned';
                this.existModalMessage = 'This subject is already in the teacher\'s handled subjects list.';
                this.showExistModal = true;
                this.editModalOpen = false;
                return;
            }

            // 2. Global duplicate check (assigned to another teacher)
            const globalMatch = this.globalAssignments.find(ass => {
                if (matched && ass.subject_id === matched.id) return true;
                return ass.subject_name.toLowerCase() === name.toLowerCase() && 
                       ass.grade_level.toLowerCase() === grade.toLowerCase();
            });

            if (globalMatch && !confirmOverride) {
                this.existModalConfirm = true;
                this.existModalAction = 'edit';
                this.existModalTitle = 'Subject Assigned to Another Teacher';
                this.existModalMessage = `This subject is already assigned to ${globalMatch.teacher_name}. Do you still want to assign it?`;
                this.showExistModal = true;
                this.editModalOpen = false;
                return;
            }

            // Replace the old value in subjectIds
            this.subjectIds = this.subjectIds.map(val => val === this.editingSubjectId ? newValue : val);
            this.showExistModal = false;
            this.editModalOpen = false;
            this.isSavingSubjects = true;
            this.$nextTick(() => {
                document.getElementById('subject-load').submit();
            });
        },
        confirmExistModal() {
            if (this.existModalAction === 'add') {
                this.addSubject(true);
            } else if (this.existModalAction === 'edit') {
                this.saveSubjectEdit(true);
            }
        },
        cancelExistModal() {
            this.showExistModal = false;
            if (this.existModalAction === 'add') {
                this.assignModalOpen = true;
            } else if (this.existModalAction === 'edit') {
                this.editModalOpen = true;
            }
        }
    }">
        <!-- Hero / Header Banner -->
        <div class="academic-hero-banner relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-900 to-indigo-950 p-6 md:p-8 text-white shadow-md">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                                      <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white uppercase">{{ $teacher['name'] }}</h1>
                    <p class="mt-1 text-sm text-indigo-150 font-light">
                        {{ $teacher['dept'] ?? 'Faculty Member' }} &bull; {{ $teacher['sections'] ?? 'No sections assigned' }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.academic.teachers') }}" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="arrow-left" class="w-4 h-4 text-indigo-700"></i>
                        Back to Directory
                    </a>
                </div>
            </div>
        </div>

        <!-- Notification Messages -->
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-extrabold flex items-center gap-2.5 shadow-3xs">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('temp_credentials'))
            <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-955 space-y-3 shadow-sm">
                <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wider text-amber-800">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Newly Generated Microsoft Credentials
                </div>
                <p class="text-xs font-semibold text-amber-700">Please copy and send these credentials to the teacher for their initial Microsoft login:</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                    <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-4">
                        <div class="min-w-0">
                            <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Microsoft Email</span>
                            <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ session('temp_credentials.email') }}</span>
                        </div>
                        <button type="button" id="copy-email-btn" onclick="copyToClipboard('{{ session('temp_credentials.email') }}', 'copy-email-btn')" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xxs font-black text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200 shrink-0 cursor-pointer">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            Copy
                        </button>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-4">
                        <div class="min-w-0">
                            <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Temporary Password</span>
                            <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ session('temp_credentials.password') }}</span>
                        </div>
                        <button type="button" id="copy-pass-btn" onclick="copyToClipboard('{{ session('temp_credentials.password') }}', 'copy-pass-btn')" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xxs font-black text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200 shrink-0 cursor-pointer">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Column: Profile Details -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-6 space-y-6 flex flex-col items-center text-center">
                    
                    <!-- Avatar Container -->
                    <div class="relative h-48 w-48 overflow-hidden rounded-full border-4 border-indigo-50 bg-slate-50 shadow-sm shrink-0">
                        @if ($teacher['photo'])
                            <img src="{{ asset(\App\Support\ImageHelper::thumb($teacher['photo'], 'large')) }}" alt="" class="h-full w-full object-cover object-center">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-indigo-50 text-4xl font-black text-indigo-700 select-none">
                                {{ $teacher['initials'] ?? 'TR' }}
                            </div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 bg-black/40 py-1.5">
                            <span class="text-[8px] font-black uppercase tracking-widest text-white block">Profile Image</span>
                        </div>
                    </div>

                    <div class="space-y-2 w-full">
                        <h2 class="text-lg font-black text-slate-900 leading-tight uppercase">{{ $teacher['name'] }}</h2>
                        <span class="text-xs text-slate-500 font-semibold block">{{ $teacher['email'] }}</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2 w-full pt-4 border-t border-slate-100">
                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $teacher['status'] === 'Active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            {{ $teacher['status'] }}
                        </span>
                        
                        @if ($teacher['microsoft_sync'] ?? true)
                            <x-badge color="green">MS365 Sync Active</x-badge>
                        @else
                            <x-badge color="gray">MS365 Sync Disabled</x-badge>
                        @endif
                    </div>

                    <div class="w-full text-left bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3.5">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Department</span>
                            <span class="text-xs font-bold text-slate-800">{{ $teacher['dept'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Current Assignment</span>
                            <span class="text-xs font-bold text-slate-800">{{ $teacher['sections'] ?? 'N/A' }}</span>
                        </div>
                    </div>

                </div>


            </div>

            <!-- Right Column: Subject & Account Details -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Subject Load Tracker -->
                <form id="subject-load" method="POST" action="{{ route('admin.academic.teachers.subjects.update', $teacher['id']) }}" class="scroll-mt-6 bg-white border border-indigo-100 rounded-2xl shadow-xs p-6 space-y-5" @submit="isSavingSubjects = true">
                    @csrf
                    @method('PATCH')

                    <template x-for="subjectId in subjectIds" :key="'subject-input-' + subjectId">
                        <input type="hidden" name="subjects[]" :value="subjectId">
                    </template>

                    <div class="border-b border-slate-100 pb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                                <i data-lucide="activity" class="w-5 h-5"></i>
                            </span>
                            <div>
                                <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Subject Load Tracker</span>
                            </div>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider ring-1 transition-all duration-150"
                              :class="loadStatus === 'Full Load' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : (loadStatus === 'Balanced Load' ? 'bg-sky-50 text-sky-700 ring-sky-100' : 'bg-amber-50 text-amber-700 ring-amber-100')"
                              x-text="loadStatus"></span>
                    </div>

                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-500">Current Load</span>
                                <div class="mt-1 text-3xl font-black text-indigo-950">
                                    <span x-text="subjectCount"></span>
                                    <span class="text-base text-indigo-500">/</span>
                                    <span x-text="loadTarget"></span>
                                    <span class="text-sm text-indigo-500">subjects</span>
                                </div>
                            </div>
                            <div class="hidden text-right sm:block">
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-indigo-400">Target Range</span>
                                <span class="mt-1 block text-xs font-black text-indigo-800">6 to 8 loads</span>
                            </div>
                        </div>
                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white ring-1 ring-indigo-100">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="loadPercent >= 100 ? 'bg-emerald-600' : (loadPercent >= 75 ? 'bg-sky-500' : 'bg-amber-500')"
                                 :style="'width: ' + loadPercent + '%'"></div>
                        </div>
                        <div class="mt-2 flex justify-between text-[10px] font-bold uppercase tracking-wider text-indigo-400">
                            <span>Minimum 6</span>
                            <span>Target 8</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="mb-3 flex items-center justify-between gap-3 pt-2">
                            <div>
                                <span class="inline-block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Handled Subjects</span>
                                <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold text-slate-500 ring-1 ring-slate-200" x-text="(loadTarget - subjectCount) + ' slots left'"></span>
                            </div>
                            <button type="button" @click="assignModalOpen = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xxs transition shadow-3xs cursor-pointer select-none" :disabled="subjectCount >= loadTarget">
                                <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                                Assign Subject
                            </button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" x-show="subjects.length > 0">
                            <template x-for="subject in subjects" :key="subject.id">
                                <div class="relative flex items-center justify-between p-3.5 rounded-xl border border-slate-200 bg-white hover:shadow-2xs transition-all duration-150 group"
                                     :class="getSubjectClass(subject.name).hoverBorder">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0 border transition-colors duration-150"
                                             :class="[getSubjectClass(subject.name).bg, getSubjectClass(subject.name).hoverBg]">
                                            <i data-lucide="book-open" class="h-4 w-4"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-xs font-black text-slate-900 truncate uppercase" x-text="subject.name"></span>
                                            <span class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wide mt-0.5" x-text="subject.grade_level"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" @click="editSubject(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-100 bg-slate-50/50 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition duration-150 shrink-0" title="Edit subject">
                                            <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <button type="button" @click="removeSubject(subject.id)" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-100 bg-slate-50/50 text-slate-400 hover:text-rose-600 hover:bg-rose-50 hover:border-rose-100 transition duration-150 shrink-0" title="Remove subject">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-center text-xs font-bold text-slate-400" x-show="subjects.length === 0">
                            No subjects added yet.
                        </div>
                    </div>
                </form>

                <!-- Weekly Schedule Timetable Card -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-6 space-y-6">
                    <div class="border-b border-slate-100 pb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                                <i data-lucide="calendar-days" class="w-5 h-5"></i>
                            </span>
                            <div>
                                <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase block">Weekly Schedule Timetable</span>
                                <span class="block text-[11px] text-slate-400 font-medium">Weekly timetable grid of scheduled classes for this teacher</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex w-fit rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-wider ring-1 bg-indigo-50 text-indigo-700 ring-indigo-100">
                                {{ count($schedules) }} Classes
                            </span>
                            <a href="{{ route('admin.academic.schedules', ['tab' => 'schedule']) }}" class="inline-flex items-center gap-1 px-3 py-1 text-[10px] font-bold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200 cursor-pointer">
                                Manage Schedules
                            </a>
                        </div>
                    </div>

                    @if(empty($schedules))
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-xs font-bold text-slate-400 flex flex-col items-center justify-center gap-2">
                            <i data-lucide="calendar" class="w-6 h-6 text-slate-350"></i>
                            <span>No scheduled classes for this teacher yet.</span>
                        </div>
                    @else
                        @php
                            $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

                            // Extract and sort unique time intervals
                            $intervals = [];
                            $timeBoundaries = [];
                            foreach ($schedules as $entry) {
                                $startMin = $entry['start_minutes'];
                                [$endH, $endM] = explode(':', $entry['end_time']);
                                $endMin = ($endH * 60) + $endM;
                                $timeBoundaries[] = $startMin;
                                $timeBoundaries[] = $endMin;
                            }
                            $timeBoundaries = array_unique($timeBoundaries);
                            sort($timeBoundaries);

                            for ($i = 0; $i < count($timeBoundaries) - 1; $i++) {
                                $intervals[] = [
                                    'start' => $timeBoundaries[$i],
                                    'end' => $timeBoundaries[$i+1],
                                    'start_time' => sprintf('%02d:%02d', intdiv($timeBoundaries[$i], 60), $timeBoundaries[$i] % 60),
                                    'end_time' => sprintf('%02d:%02d', intdiv($timeBoundaries[$i+1], 60), $timeBoundaries[$i+1] % 60),
                                    'minutes' => $timeBoundaries[$i+1] - $timeBoundaries[$i],
                                ];
                            }

                            // Helper to format time label (e.g. 7:30-7:40 a.m.)
                            if (!function_exists('formatTimetableTime')) {
                                function formatTimetableTime($start, $end) {
                                    $startAmPm = date('a', strtotime($start));
                                    $endAmPm = date('a', strtotime($end));
                                    
                                    $startAmPm = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $startAmPm);
                                    $endAmPm = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $endAmPm);
                                    
                                    if ($startAmPm === $endAmPm) {
                                        return date('g:i', strtotime($start)) . '-' . date('g:i', strtotime($end)) . ' ' . $endAmPm;
                                    }
                                    return date('g:i', strtotime($start)) . ' ' . $startAmPm . ' - ' . date('g:i', strtotime($end)) . ' ' . $endAmPm;
                                }
                            }

                            // Build 2D grid matrix
                            $grid = [];
                            foreach ($intervals as $iIdx => $interval) {
                                $grid[$iIdx] = [];
                                foreach ($daysList as $day) {
                                    $grid[$iIdx][$day] = null;
                                }
                            }

                            foreach ($daysList as $day) {
                                foreach ($intervals as $iIdx => $interval) {
                                    $matchingEntry = null;
                                    foreach ($schedules as $entry) {
                                        if ($entry['day'] !== $day) {
                                            continue;
                                        }
                                        $entryStart = $entry['start_minutes'];
                                        [$endH, $endM] = explode(':', $entry['end_time']);
                                        $entryEnd = ($endH * 60) + $endM;

                                        if ($entryStart <= $interval['start'] && $entryEnd >= $interval['end']) {
                                            $matchingEntry = $entry;
                                            break;
                                        }
                                    }

                                    if ($matchingEntry) {
                                        $isStart = ($matchingEntry['start_minutes'] === $interval['start']);
                                        $span = 0;
                                        if ($isStart) {
                                            foreach ($intervals as $subInterval) {
                                                if ($subInterval['start'] >= $matchingEntry['start_minutes']) {
                                                    [$entryEndH, $entryEndM] = explode(':', $matchingEntry['end_time']);
                                                    $entryEndMin = ($entryEndH * 60) + $entryEndM;
                                                    if ($subInterval['end'] <= $entryEndMin) {
                                                        $span++;
                                                    }
                                                }
                                            }
                                        }

                                        $grid[$iIdx][$day] = [
                                            'entry' => $matchingEntry,
                                            'is_start' => $isStart,
                                            'span' => $span,
                                        ];
                                    }
                                }
                            }

                            // Initialize horizontal merge tracking
                            foreach ($intervals as $iIdx => $interval) {
                                foreach ($daysList as $day) {
                                    if ($grid[$iIdx][$day]) {
                                        $grid[$iIdx][$day]['colspan'] = 1;
                                        $grid[$iIdx][$day]['skip_horizontal'] = false;
                                    }
                                }
                            }

                            // Compute horizontal colspans
                            foreach ($intervals as $iIdx => $interval) {
                                for ($d = 0; $d < count($daysList); $d++) {
                                    $day = $daysList[$d];
                                    $cell = $grid[$iIdx][$day];
                                    if (!$cell || !$cell['is_start'] || $cell['skip_horizontal']) {
                                        continue;
                                    }

                                    $colspan = 1;
                                    while ($d + $colspan < count($daysList)) {
                                        $nextDay = $daysList[$d + $colspan];
                                        $nextCell = $grid[$iIdx][$nextDay];
                                        
                                        if ($nextCell && $nextCell['is_start'] && !$nextCell['skip_horizontal'] && $nextCell['span'] === $cell['span'] && $nextCell['entry']['subject_name'] === $cell['entry']['subject_name'] && $nextCell['entry']['section_id'] === $cell['entry']['section_id']) {
                                            $colspan++;
                                        } else {
                                            break;
                                        }
                                    }

                                    if ($colspan > 1) {
                                        $grid[$iIdx][$day]['colspan'] = $colspan;
                                        for ($c = 1; $c < $colspan; $c++) {
                                            $targetDay = $daysList[$d + $c];
                                            for ($r = 0; $r < $cell['span']; $r++) {
                                                if ($grid[$iIdx + $r][$targetDay]) {
                                                    $grid[$iIdx + $r][$targetDay]['skip_horizontal'] = true;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        @endphp

                        <style>
                            /* Minimal overrides for the timetable grid using premium-table base */
                            .timetable-grid-wrap {
                                max-height: none !important;
                                overflow: visible !important;
                                border: 1px solid #edf2f7;
                                border-radius: .875rem;
                            }
                            .timetable-grid td {
                                border-top: 1px solid #f1f5f9;
                                border-right: 1px solid #f1f5f9;
                                vertical-align: middle;
                                text-align: center;
                                padding: 0 !important; /* Remove cell padding to allow full-height hover styling */
                                background-color: #ffffff;
                            }
                            .timetable-grid td:last-child {
                                border-right: none;
                            }
                            .timetable-grid .col-time {
                                width: 130px;
                                background-color: #f8fafc;
                                font-weight: 700;
                                color: #1e293b;
                                padding: 12px !important;
                            }
                            .timetable-grid .col-minutes {
                                width: 70px;
                                background-color: #f8fafc;
                                font-weight: 600;
                                color: #64748b;
                                padding: 12px !important;
                            }
                            .timetable-grid .cell-quran {
                                background-color: #f0f9ff !important; /* bg-sky-50 */
                                color: #0369a1 !important; /* text-sky-700 */
                                border-right: 1px solid #bae6fd !important;
                                border-top: 1px solid #bae6fd !important;
                            }
                            .timetable-grid .cell-quran:hover {
                                background-color: #e0f2fe !important;
                            }
                            .timetable-grid .cell-hadith {
                                background-color: #fffbeb !important; /* bg-amber-50 */
                                color: #b45309 !important; /* text-amber-700 */
                                border-right: 1px solid #fde68a !important;
                                border-top: 1px solid #fde68a !important;
                            }
                            .timetable-grid .cell-hadith:hover {
                                background-color: #fef3c7 !important;
                            }
                            .timetable-grid .cell-arabic {
                                background-color: #fdf2f8 !important; /* bg-pink-50 */
                                color: #be185d !important; /* text-pink-700 */
                                border-right: 1px solid #fce7f3 !important;
                                border-top: 1px solid #fce7f3 !important;
                            }
                            .timetable-grid .cell-arabic:hover {
                                background-color: #fce7f3 !important;
                            }
                            .timetable-grid .cell-recess {
                                background-color: #fff5f5 !important; /* bg-red-50 */
                                color: #c53030 !important; /* text-red-700 */
                                border-right: 1px solid #fed7d7 !important;
                                border-top: 1px solid #fed7d7 !important;
                            }
                            .timetable-grid .cell-recess:hover {
                                background-color: #fed7d7 !important;
                            }
                            .timetable-grid .cell-academic {
                                background-color: #f0fdf4 !important; /* bg-emerald-50 */
                                color: #15803d !important; /* text-emerald-700 */
                                border-right: 1px solid #dcfce7 !important;
                                border-top: 1px solid #dcfce7 !important;
                            }
                            .timetable-grid .cell-academic:hover {
                                background-color: #dcfce7 !important;
                            }
                            .timetable-grid .cell-event {
                                background-color: #f5f3ff !important; /* bg-violet-50 */
                                color: #6d28d9 !important; /* text-violet-700 */
                                border-right: 1px solid #ede9fe !important;
                                border-top: 1px solid #ede9fe !important;
                            }
                            .timetable-grid .cell-event:hover {
                                background-color: #ede9fe !important;
                            }
                            .timetable-grid .cell-empty {
                                background-color: #ffffff !important;
                                color: #1e293b !important;
                            }
                            .timetable-grid .cell-empty:hover {
                                background-color: #f8fafc !important;
                            }
                        </style>

                        <div class="premium-table-wrap timetable-grid-wrap">
                            <table class="premium-table timetable-grid">
                                <thead>
                                    <tr>
                                        <th class="col-time text-center uppercase tracking-wider font-extrabold text-[10px]" style="text-align: center;">Time</th>
                                        <th class="col-minutes text-center uppercase tracking-wider font-extrabold text-[10px]" style="text-align: center;">Minutes</th>
                                        @foreach($daysList as $day)
                                            <th class="text-center uppercase tracking-wider font-extrabold text-[10px]" style="text-align: center;">{{ $day }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($intervals as $iIdx => $interval)
                                        <tr>
                                            <td class="col-time font-bold text-slate-800">
                                                {{ formatTimetableTime($interval['start_time'], $interval['end_time']) }}
                                            </td>
                                            <td class="col-minutes font-semibold text-slate-500">
                                                {{ $interval['minutes'] }}
                                            </td>
                                            @foreach($daysList as $day)
                                                @php
                                                    $cell = $grid[$iIdx][$day];
                                                @endphp
                                                @if($cell)
                                                    @if($cell['is_start'] && !$cell['skip_horizontal'])
                                                        @php
                                                            $subjectLower = strtolower($cell['entry']['subject_name']);
                                                            $cellClass = 'cell-academic';
                                                            if (str_contains($subjectLower, 'qur')) {
                                                                $cellClass = 'cell-quran';
                                                            } elseif (str_contains($subjectLower, 'hadith')) {
                                                                $cellClass = 'cell-hadith';
                                                            } elseif (str_contains($subjectLower, 'arabic')) {
                                                                $cellClass = 'cell-arabic';
                                                            } elseif (str_contains($subjectLower, 'recess')) {
                                                                $cellClass = 'cell-recess';
                                                            } elseif (str_contains($subjectLower, 'meeting') || str_contains($subjectLower, 'circle') || str_contains($subjectLower, 'wrap')) {
                                                                $cellClass = 'cell-event';
                                                            } elseif (str_contains($subjectLower, 'assembly') || str_contains($subjectLower, 'departure')) {
                                                                $cellClass = 'cell-empty';
                                                            }
                                                        @endphp
                                                        <td rowspan="{{ $cell['span'] }}" colspan="{{ $cell['colspan'] }}" class="{{ $cellClass }}">
                                                            <div class="relative w-full h-full p-4 flex flex-col justify-center text-center group min-h-[55px]">
                                                                <span class="block font-extrabold text-[12px] leading-tight uppercase tracking-wide" style="color: inherit;">
                                                                    {{ $cell['entry']['subject_name'] }}
                                                                </span>
                                                                <span class="block text-[10px] font-bold uppercase mt-1.5 flex items-center justify-center gap-1.5" style="color: inherit; opacity: 0.8;">
                                                                    <i data-lucide="users" class="h-3.5 w-3.5 opacity-60"></i>
                                                                    {{ $cell['entry']['section_name'] }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    @endif
                                                @else
                                                    <td class="cell-empty"></td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Account Credentials & Actions Card -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-6 space-y-6">
                    <div class="border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 text-indigo-600"></i>
                        <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Microsoft Credentials Status</span>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl border {{ $teacher['password_changed'] === 'Yes' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $teacher['password_changed'] === 'Yes' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                <i data-lucide="{{ $teacher['password_changed'] === 'Yes' ? 'key-round' : 'alert-circle' }}" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-xs font-extrabold uppercase block tracking-wider">Has changed password?</span>
                                <span class="text-[11px] font-bold text-slate-500">
                                    Current Flag: <span class="font-extrabold {{ $teacher['password_changed'] === 'Yes' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $teacher['password_changed'] }}</span>
                                </span>
                            </div>
                        </div>

                    </div>

                    <!-- Conditional Credentials Section: Displayed if password_changed is No -->
                    @if ($teacher['password_changed'] === 'No')
                        <div class="bg-amber-50/40 border border-amber-200 rounded-xl p-4 md:p-5 space-y-4">
                            <div class="flex items-center gap-2 text-amber-800 font-extrabold text-xs uppercase tracking-wider">
                                <i data-lucide="key" class="w-4 h-4 text-amber-700"></i>
                                Microsoft Login Credentials (Temporary)
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-3">
                                    <div class="min-w-0">
                                        <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Microsoft Email</span>
                                        <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ $teacher['email'] }}</span>
                                    </div>
                                    <button type="button" id="copy-email-static" onclick="copyToClipboard('{{ $teacher['email'] }}', 'copy-email-static')" class="px-2 py-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200 cursor-pointer">
                                        Copy
                                    </button>
                                </div>
                                <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-3">
                                    <div class="min-w-0">
                                        <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Temporary Password</span>
                                        <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ $teacher['temporary_password'] ?: 'No password set' }}</span>
                                    </div>
                                    @if ($teacher['temporary_password'])
                                        <button type="button" id="copy-pass-static" onclick="copyToClipboard('{{ $teacher['temporary_password'] }}', 'copy-pass-static')" class="px-2 py-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200 cursor-pointer">
                                            Copy
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <p class="text-[10px] text-amber-700 font-semibold italic mt-1 leading-normal">
                                Note: These credentials will display here until the teacher logs in and successfully changes their password, or until the admin marks the status as "Yes".
                            </p>
                        </div>
                    @endif

                    <!-- Account Reset Action -->
                    <div class="border-t border-slate-100 pt-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="font-extrabold text-slate-900 text-xs block tracking-wide">Reset Teacher Account & Microsoft Credentials</span>
                            <span class="text-[11px] text-slate-400 font-medium block">
                                This will generate a new random temporary password, clear the changed status back to "No", and update Microsoft Graph.
                            </span>
                        </div>
                        <form method="POST" action="{{ route('admin.academic.teachers.resend') }}" @submit="isResending = true">
                            @csrf
                            <input type="hidden" name="id" value="{{ $teacher['id'] }}">
                            <button type="submit" :disabled="isResending" class="relative inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-black text-xs px-5 py-2.5 shadow-sm transition min-w-[200px] cursor-pointer">
                                <span class="btn-spinner" x-show="isResending"></span>
                                <i data-lucide="key" class="w-4 h-4" x-show="!isResending"></i>
                                Reset & Resend Credentials
                            </button>
                        </form>
                    </div>

                </div>

        </div>

        <!-- Subject Exist Warning Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="showExistModal" @click.self="showExistModal = false" @keydown.escape.window="showExistModal = false" x-cloak x-transition>
            <div class="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-xl text-center space-y-4">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 border border-amber-200">
                    <i data-lucide="alert-triangle" class="h-6 w-6"></i>
                </div>
                <div class="space-y-2">
                    <h3 class="text-base font-extrabold text-slate-950 uppercase tracking-wider" x-text="existModalTitle"></h3>
                    <p class="text-xs font-semibold text-slate-500 leading-normal" x-text="existModalMessage"></p>
                </div>
                <div class="pt-2">
                    <template x-if="!existModalConfirm">
                        <button type="button" @click="cancelExistModal()" class="w-full inline-flex justify-center rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-xs font-black text-white shadow-sm transition">
                            Understood
                        </button>
                    </template>
                    <template x-if="existModalConfirm">
                        <div class="flex gap-2">
                            <button type="button" @click="cancelExistModal()" class="flex-1 inline-flex justify-center rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2.5 text-xs font-bold text-slate-500 transition">
                                Cancel
                            </button>
                            <button type="button" @click="confirmExistModal()" class="flex-1 inline-flex justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-black text-white shadow-sm transition">
                                Assign Anyway
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Assign Subject Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="assignModalOpen" @click.self="assignModalOpen = false" @keydown.escape.window="assignModalOpen = false" x-cloak x-transition>
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-950 uppercase tracking-wider">Assign Catalog Subject</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Pick from the official subject catalog or type to register new ones.</p>
                    </div>
                    <button type="button" @click="assignModalOpen = false" class="text-xl font-bold text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">1. Subject Name *</span>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Type the subject name to assign or register.</p>
                    </div>
                    <input x-model="selectedSubjectName" placeholder="Type subject name (e.g. Science)..." class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" :disabled="subjectCount >= loadTarget" @keydown.enter.prevent="addSubject()">
                    
                    <div class="pt-2 flex items-center justify-between gap-4">
                        <div>
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">2. Grade Level *</span>
                            <p class="mt-0.5 text-xs font-semibold text-slate-500 font-medium">Select the target grade levels for this subject load.</p>
                        </div>
                        <button type="button" @click="selectedGrades = selectedGrades.length === grades.length ? [] : [...grades]"
                                class="inline-flex shrink-0 items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-black text-xxs transition shadow-3xs cursor-pointer select-none">
                            <span x-text="selectedGrades.length === grades.length ? 'Deselect All' : 'Select All'"></span>
                        </button>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="g in grades" :key="g">
                            <label class="relative flex items-center justify-center rounded-xl border-2 p-3 cursor-pointer select-none transition focus-within:ring-2 focus-within:ring-emerald-500/20"
                                   :class="selectedGrades.includes(g) ? 'border-emerald-500 bg-emerald-50/30 ring-1 ring-emerald-500/10' : 'border-slate-200 bg-white hover:border-emerald-300'">
                                <input type="checkbox" :value="g" x-model="selectedGrades" class="sr-only">
                                <span class="text-xs font-bold" :class="selectedGrades.includes(g) ? 'text-emerald-950 font-black' : 'text-slate-700'">
                                    <span x-text="g"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                    
                    <!-- Warning / Info Banner -->
                    <div class="mt-3 space-y-2" x-show="subjectCount >= loadTarget || (selectedGrades.length > 0 && selectedGrades.length > (loadTarget - subjectCount))" x-cloak>
                        <!-- Case 1: Limit Reached -->
                        <div x-show="subjectCount >= loadTarget" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-955 text-xs font-semibold flex items-start gap-2.5 shadow-3xs">
                            <i data-lucide="alert-circle" class="w-4.5 h-4.5 text-amber-600 shrink-0 mt-0.5"></i>
                            <div>
                                <span class="font-extrabold block uppercase tracking-wide text-amber-800 text-[10px] mb-0.5">Load Target Reached</span>
                                This teacher has reached their target load of <span x-text="loadTarget"></span> subjects. Remove an existing subject to assign a new one.
                            </div>
                        </div>
                        <!-- Case 2: Selected Exceeds Remaining Slots -->
                        <div x-show="subjectCount < loadTarget && selectedGrades.length > (loadTarget - subjectCount)" class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-955 text-xs font-semibold flex items-start gap-2.5 shadow-3xs">
                            <i data-lucide="alert-triangle" class="w-4.5 h-4.5 text-amber-600 shrink-0 mt-0.5"></i>
                            <div>
                                <span class="font-extrabold block uppercase tracking-wide text-amber-800 text-[10px] mb-0.5">Exceeds Remaining Slots</span>
                                Selecting <span x-text="selectedGrades.length"></span> grades will exceed the <span x-text="loadTarget - subjectCount"></span> remaining slot(s). Only the first <span x-text="loadTarget - subjectCount"></span> will be added.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="assignModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-50">Close</button>
                    <button type="button" @click="addSubject()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!selectedSubjectName || selectedGrades.length === 0 || subjectCount >= loadTarget">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Assign Subject
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Subject Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="editModalOpen" @click.self="editModalOpen = false" @keydown.escape.window="editModalOpen = false" x-cloak x-transition>
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-950 uppercase tracking-wider">Edit Assigned Subject</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Update subject name and grade level assignment.</p>
                    </div>
                    <button type="button" @click="editModalOpen = false" class="text-xl font-bold text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">1. Subject Name *</span>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Modify the subject name.</p>
                    </div>
                    <input x-model="editSubjectName" placeholder="Type subject name (e.g. Science)..." class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" @keydown.enter.prevent="saveSubjectEdit()">
                    
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">2. Grade Level *</span>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500 font-medium">Select the new grade level for this subject.</p>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="g in grades" :key="g">
                            <label class="relative flex items-center justify-center rounded-xl border-2 p-3 cursor-pointer select-none transition focus-within:ring-2 focus-within:ring-indigo-500/20"
                                   :class="editGrade === g ? 'border-indigo-500 bg-indigo-50/30 ring-1 ring-indigo-500/10' : 'border-slate-200 bg-white hover:border-indigo-300'">
                                <input type="radio" :value="g" x-model="editGrade" class="sr-only">
                                <span class="text-xs font-bold" :class="editGrade === g ? 'text-indigo-950 font-black' : 'text-slate-700'">
                                    <span x-text="g"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-50">Close</button>
                    <button type="button" @click="saveSubjectEdit();" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!editSubjectName || !editGrade">
                        <i data-lucide="check" class="h-4 w-4"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Inline script for clipboard copies and icons -->
    <script>
        function copyToClipboard(text, buttonId) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById(buttonId);
                const originalContent = btn.innerHTML;
                btn.innerHTML = `Copied!`;
                btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</x-admin-layout>
