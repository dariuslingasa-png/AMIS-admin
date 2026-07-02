<x-admin-layout title="Class Workspace — {{ $section->section_title }}">
    @php
        $breadcrumbs = [
            ['label' => 'Class Workspace', 'href' => route('admin.ms-teams.index')],
            ['label' => $section->section_title, 'href' => null]
        ];

        $dbSubjects = $section->subjects->keyBy('subject_name');
        
        // Only show subjects that exist in the database for this section
        $allSubjectNames = $dbSubjects->keys()->toArray();

        if (!function_exists('getSubjectStyle')) {
            function getSubjectStyle($name) {
                $lower = strtolower($name);
                if (str_contains($lower, 'qur')) {
                    return ['bg-sky-50 text-sky-700 border-sky-100', 'bg-sky-500'];
                }
                if (str_contains($lower, 'hadith')) {
                    return ['bg-amber-50 text-amber-700 border-amber-100', 'bg-amber-500'];
                }
                if (str_contains($lower, 'arabic')) {
                    return ['bg-pink-50 text-pink-700 border-pink-100', 'bg-pink-500'];
                }
                if (str_contains($lower, 'recess')) {
                    return ['bg-rose-50 text-rose-700 border-rose-100', 'bg-rose-500'];
                }
                if (str_contains($lower, 'meeting') || str_contains($lower, 'circle') || str_contains($lower, 'wrap')) {
                    return ['bg-violet-50 text-violet-700 border-violet-100', 'bg-violet-500'];
                }
                return ['bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-emerald-500'];
            }
        }

        if (!function_exists('getSubjectInitials')) {
            function getSubjectInitials($name) {
                $words = explode(' ', str_replace(['&', 'and', '(', ')', ',', '’'], '', $name));
                $initials = '';
                foreach ($words as $w) {
                    if (!empty($w)) {
                        $initials .= strtoupper($w[0]);
                    }
                }
                return substr($initials, 0, 2);
            }
        }
    @endphp

    <div x-data="workspaceData(@js($allSubjectNames), @js($dbSubjects))">
        <!-- Back navigation -->
        <div class="mb-4">
            <a href="{{ route('admin.ms-teams.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-900 transition">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Class Workspace
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-100 p-4 text-xs font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start animate-fade-in">
            <!-- Left Column: Section Details & Subjects -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Glassmorphic Section Hero Banner -->
                <div class="relative overflow-hidden p-6 md:p-8 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-2xl border border-emerald-700/30 shadow-sm text-white">
                    <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-emerald-500/10 blur-3xl"></div>
                    <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-teal-500/10 blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-emerald-500/20 text-emerald-300 rounded-full border border-emerald-500/30 backdrop-blur-xs mb-3">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Active Class Workspace
                        </span>
                        <h1 class="text-xl md:text-2xl font-extrabold tracking-tight text-white uppercase">
                            {{ $section->grade_level }} - {{ $section->name ?? 'UNNAMED SECTION' }}
                        </h1>
                        
                        @php
                            $learningLabel = $section->formatted_learning_mode;
                            $genderLabel = $section->gender === 'male' ? 'Boys Room' : ($section->gender === 'female' ? 'Girls Room' : 'Mixed Room');
                            
                            $chipClass = 'inline-flex items-center px-3.5 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs';
                        @endphp

                        <div class="flex flex-wrap gap-2 mt-4 items-center">
                            <span class="{{ $chipClass }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                                {{ $learningLabel }}
                            </span>
                            <span class="{{ $chipClass }}">
                                {{ $genderLabel }}
                            </span>
                            @if($section->grade_advisor)
                                <span class="{{ $chipClass }} bg-teal-500/20 text-teal-100 border-teal-500/30 flex items-center gap-1.5">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-teal-300"></i>
                                    Advisor: <span class="uppercase">{{ $section->grade_advisor->teacher_name }}</span>
                                    @if($section->ms_team_id)
                                        <button 
                                            @click="syncAdvisor()" 
                                            :disabled="loadingMap['advisor-sync']"
                                            class="ml-2 px-2 py-0.5 text-[9px] font-bold bg-white/20 hover:bg-white/30 text-white rounded transition disabled:opacity-50"
                                            title="Sync Advisor as Team Owner"
                                        >
                                            <span x-show="!loadingMap['advisor-sync']">Sync</span>
                                            <span x-show="loadingMap['advisor-sync']">...</span>
                                        </button>
                                    @endif
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Assigned Subjects Card -->
                <div class="admin-card bg-white border border-slate-150 rounded-2xl shadow-xs overflow-hidden">
                    <div class="admin-card-header bg-slate-50/50 border-b border-slate-200/50 px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="admin-card-title text-slate-900 font-extrabold text-sm tracking-wide">Assigned Subjects</span>
                            <div class="text-[10px] text-slate-400 font-light mt-0.5">Assigned subjects and MS Teams private channels for this section</div>
                        </div>
                        @if($section->ms_team_id)
                            <button 
                                @click="autoCreateAll()" 
                                :disabled="isAutoCreating"
                                class="px-4 py-2 text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-700 rounded-xl transition-all shadow-xs flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <template x-if="isAutoCreating">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Creating Channels...
                                    </span>
                                </template>
                                <template x-if="!isAutoCreating">
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="zap" class="w-3.5 h-3.5"></i> Auto Create Channels
                                    </span>
                                </template>
                            </button>
                        @else
                            <span class="px-3 py-1.5 text-xxs font-bold text-amber-800 bg-amber-50 border border-amber-100 rounded-lg">Setup MS Team first</span>
                        @endif
                    </div>

                    <div x-show="autoCreateError" class="m-4 rounded-xl bg-rose-50 border border-rose-100 p-4 text-xs font-bold text-rose-800" x-text="autoCreateError"></div>

                    <div class="divide-y divide-slate-100 max-h-[600px] overflow-y-auto p-1">
                        @forelse($allSubjectNames as $name)
                            @php
                                $subjectRecord = $dbSubjects[$name] ?? null;
                                $hasChannel = $subjectRecord && $subjectRecord->ms_channel_id;
                                $style = getSubjectStyle($name);
                                $initials = getSubjectInitials($name);
                            @endphp
                            <div class="flex flex-col md:flex-row md:items-center justify-between py-4 hover:bg-slate-50/40 rounded-xl px-4 transition-colors gap-4">
                                <!-- Left: initials & subject info -->
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="w-10 h-10 rounded-full border {{ $style[0] }} font-black text-xs flex items-center justify-center shrink-0 shadow-xs uppercase">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-slate-900 text-sm uppercase tracking-wide flex items-center gap-2">
                                            {{ $name }}
                                            @if($subjectRecord)
                                                <form method="POST" action="{{ route('admin.ms-teams.subjects.destroy', $subjectRecord) }}" x-on:submit.prevent="if(confirm('Remove subject {{ addslashes($name) }}?')) $el.submit()" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-slate-350 hover:text-rose-600 transition-colors">
                                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1">
                                            @if($hasChannel)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-full">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Teams Connected
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold bg-slate-50 text-slate-400 border border-slate-100 rounded-full">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span> No Teams Channel
                                                </span>
                                            @endif
                                            
                                            <!-- Inline Editable Schedule -->
                                            <div class="flex items-center gap-1">
                                                <span 
                                                    id="sched-text-{{ Str::slug($name) }}"
                                                    @click="editingSchedule['{{ $name }}'] = true" 
                                                    class="cursor-pointer text-[10px] font-bold font-mono text-slate-500 bg-slate-50 hover:bg-slate-100 px-2 py-0.5 rounded border border-slate-200/50 transition"
                                                >
                                                    {{ $subjectRecord->schedule ?? 'Set Schedule' }}
                                                </span>
                                                <input 
                                                    id="sched-input-{{ Str::slug($name) }}"
                                                    x-show="editingSchedule['{{ $name }}']"
                                                    type="text" 
                                                    value="{{ $subjectRecord->schedule ?? '' }}" 
                                                    @blur="editingSchedule['{{ $name }}'] = false; saveSchedule('{{ addslashes($name) }}', $event.target.value)"
                                                    @keyup.enter="$event.target.blur()"
                                                    class="text-[10px] font-mono text-slate-700 bg-white border border-slate-300 rounded px-1.5 py-0.5 outline-none focus:ring-1 focus:ring-emerald-500 w-28"
                                                    x-init="$watch('editingSchedule.{{ $name }}', value => { if(value) { $nextTick(() => $el.focus()); } })"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Middle: Teacher Selector -->
                                <div class="flex-1 max-w-xs md:mx-4">
                                    <label class="text-[9px] font-extrabold uppercase tracking-wider text-slate-400 block mb-1">Assigned Teacher</label>
                                    <select 
                                        id="select-{{ Str::slug($name) }}"
                                        @change="saveTeacher('{{ addslashes($name) }}', $event.target.value, $event.target.options[$event.target.selectedIndex].getAttribute('data-name'))"
                                        class="w-full bg-slate-50 border border-slate-250 text-slate-700 text-xs rounded-xl px-2.5 py-1.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-semibold"
                                    >
                                        <option value="" data-name="">Select Teacher...</option>
                                        @foreach($teachers as $teacher)
                                            <option 
                                                value="{{ $teacher->email }}" 
                                                data-name="{{ $teacher->name }}"
                                                {{ ($subjectRecord && $subjectRecord->teacher_name === $teacher->name) ? 'selected' : '' }}
                                            >
                                                {{ strtoupper($teacher->name) }} ({{ $teacher->load_count }}/8 loads)
                                            </option>
                                        @endforeach
                                    </select>
                                    <span id="indicator-{{ Str::slug($name) }}" class="hidden text-emerald-600 text-[9px] font-bold mt-1 flex items-center gap-0.5 animate-pulse">
                                        <i data-lucide="check" class="w-3 h-3"></i> Assignment Saved
                                    </span>
                                </div>

                                <!-- Right: Channel Action -->
                                <div class="flex items-center gap-2 shrink-0 md:justify-end">
                                    @if(!$hasChannel)
                                        <button 
                                            @click="createSingleChannel('{{ addslashes($name) }}')"
                                            :disabled="loadingMap['{{ $name }}']"
                                            class="px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-800 hover:bg-emerald-700 rounded-xl transition-all shadow-xs flex items-center gap-1.5 disabled:opacity-50"
                                        >
                                            <template x-if="loadingMap['{{ $name }}']">
                                                <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </template>
                                            <span>Create Channel</span>
                                        </button>
                                    @else
                                        @if($section->ms_team_url)
                                            <a href="{{ $section->ms_team_url }}" target="_blank" class="px-3.5 py-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 rounded-xl hover:bg-emerald-600 hover:text-white border border-emerald-100 transition-all">Join Class ↗</a>
                                        @else
                                            <span class="text-xs font-medium text-slate-400">Connected</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-slate-400">
                                <i data-lucide="info" class="w-8 h-8 mx-auto text-slate-300 mb-2"></i>
                                <p class="font-semibold text-sm">No subjects assigned yet. Add a subject below to get started.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Custom Subject Adding inline at bottom -->
                    <div class="px-5 py-3.5 bg-slate-50/50 border-t border-slate-150/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-xs font-bold text-slate-500">Add Subject Name:</span>
                        <div class="flex items-center gap-2 max-w-sm w-full">
                            <input 
                                type="text" 
                                x-model="subjectName" 
                                placeholder="e.g. Science, Mathematics" 
                                class="w-full bg-white border border-slate-200 text-xs rounded-xl px-3 py-1.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-semibold"
                            >
                            <button 
                                type="button" 
                                @click="if (subjectName.trim()) { addSubjectOnly(subjectName.trim()); subjectName = ''; }" 
                                class="px-3.5 py-1.5 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-600 hover:text-white rounded-xl transition-all border border-emerald-100 flex items-center shrink-0"
                            >
                                + Add Subject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Enrolled Students Roster -->
            <div class="admin-card bg-white border border-slate-150 rounded-2xl shadow-xs p-5 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <span class="admin-card-title text-slate-900 font-extrabold text-sm tracking-wide">Enrolled Students</span>
                    <span class="badge badge-green font-bold text-xxs bg-emerald-50 text-emerald-805 border border-emerald-100 mt-1 block w-max">{{ $enrollments->count() }} active</span>
                </div>
                <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto space-y-1 p-1">
                    @forelse($enrollments as $e)
                        @php
                            $first = $e->student->applicant?->first_name ?? 'S';
                            $last = $e->student->applicant?->last_name ?? '';
                            $initials = strtoupper(substr($first, 0, 1) . ( $last ? substr($last, 0, 1) : '' ));
                        @endphp
                        <div class="flex items-center justify-between py-2.5 hover:bg-slate-50/50 rounded-lg px-2 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200/50 text-slate-655 font-black text-xxs flex items-center justify-center shrink-0">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <div class="font-extrabold text-slate-900 text-xs uppercase">{{ $last }}, {{ $first }}</div>
                                    <div class="text-[9px] text-slate-400 font-mono tracking-wide mt-0.5">{{ $e->student->student_number }}</div>
                                </div>
                            </div>
                            <span class="badge badge-green text-[9px] font-bold">Active</span>
                        </div>
                    @empty
                        <div class="admin-empty py-6 text-center text-slate-400">
                            <p class="font-semibold text-xs">No students enrolled yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- AlpineJS Data Component -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('workspaceData', (allSubjectNames, dbSubjects) => ({
                loadingMap: {},
                savingMap: {},
                editingSchedule: {},
                subjectName: '',
                isAutoCreating: false,
                autoCreateError: '',
                subjects: allSubjectNames,
                dbSubjects: dbSubjects,
                
                async autoCreateAll() {
                    if (this.isAutoCreating) return;
                    this.isAutoCreating = true;
                    this.autoCreateError = '';
                    
                    const pending = this.subjects.filter(name => !this.dbSubjects[name] || !this.dbSubjects[name].ms_channel_id);
                    
                    for (let name of pending) {
                        this.loadingMap[name] = true;
                        
                        const selectEl = document.getElementById('select-' + this.slugify(name));
                        const teacherUpn = selectEl ? selectEl.value : '';
                        const teacherName = selectEl ? selectEl.options[selectEl.selectedIndex].getAttribute('data-name') || '' : '';
                        const schedVal = document.getElementById('sched-input-' + this.slugify(name))?.value || '';
                        
                        try {
                            const res = await fetch('{{ route("admin.ms-teams.subjects.store", $section) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    subject_name: name,
                                    teacher_name: teacherName,
                                    teacher_upn: teacherUpn,
                                    schedule: schedVal,
                                    create_channel: true
                                })
                            });
                            const data = await res.json();
                            if (!data.success) {
                                this.autoCreateError = data.message || `Failed for ${name}`;
                            }
                        } catch (e) {
                            this.autoCreateError = `Network error for ${name}`;
                        }
                        this.loadingMap[name] = false;
                    }
                    
                    this.isAutoCreating = false;
                    location.reload();
                },
                
                async createSingleChannel(name) {
                    if (this.loadingMap[name]) return;
                    this.loadingMap[name] = true;
                    
                    const selectEl = document.getElementById('select-' + this.slugify(name));
                    const teacherUpn = selectEl ? selectEl.value : '';
                    const teacherName = selectEl ? selectEl.options[selectEl.selectedIndex].getAttribute('data-name') || '' : '';
                    const schedVal = document.getElementById('sched-input-' + this.slugify(name))?.value || '';
                    
                    try {
                        const res = await fetch('{{ route("admin.ms-teams.subjects.store", $section) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                subject_name: name,
                                teacher_name: teacherName,
                                teacher_upn: teacherUpn,
                                schedule: schedVal,
                                create_channel: true
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to create channel.');
                        }
                    } catch (e) {
                        alert('Network error. Please try again.');
                    }
                    this.loadingMap[name] = false;
                },

                async addSubjectOnly(name) {
                    if (this.loadingMap[name]) return;
                    this.loadingMap[name] = true;
                    try {
                        const res = await fetch('{{ route("admin.ms-teams.subjects.store", $section) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                subject_name: name,
                                create_channel: false
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.message || 'Failed to add subject.');
                        }
                    } catch (e) {
                        alert('Network error. Please try again.');
                    }
                    this.loadingMap[name] = false;
                },
                
                async saveTeacher(name, teacherUpn, teacherName) {
                    if (this.savingMap[name]) return;
                    this.savingMap[name] = true;
                    
                    const schedVal = document.getElementById('sched-input-' + this.slugify(name))?.value || '';
                    
                    try {
                        const res = await fetch('{{ route("admin.ms-teams.subjects.store", $section) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                subject_name: name,
                                teacher_name: teacherName,
                                teacher_upn: teacherUpn,
                                schedule: schedVal,
                                create_channel: false
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            const indicator = document.getElementById('indicator-' + this.slugify(name));
                            if (indicator) {
                                indicator.classList.remove('hidden');
                                setTimeout(() => indicator.classList.add('hidden'), 2000);
                            }
                        } else {
                            alert(data.message || 'Failed to save teacher assignment.');
                        }
                    } catch (e) {
                        alert('Network error saving teacher.');
                    }
                    this.savingMap[name] = false;
                },
                
                async saveSchedule(name, schedule) {
                    const selectEl = document.getElementById('select-' + this.slugify(name));
                    const teacherUpn = selectEl ? selectEl.value : '';
                    const teacherName = selectEl ? selectEl.options[selectEl.selectedIndex].getAttribute('data-name') || '' : '';
                    
                    try {
                        const res = await fetch('{{ route("admin.ms-teams.subjects.store", $section) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                subject_name: name,
                                teacher_name: teacherName,
                                teacher_upn: teacherUpn,
                                schedule: schedule,
                                create_channel: false
                            })
                        });
                        const data = await res.json();
                        if (data.success) {
                            const schedSpan = document.getElementById('sched-text-' + this.slugify(name));
                            if (schedSpan) {
                                schedSpan.innerText = schedule || 'Set Schedule';
                            }
                        } else {
                            alert(data.message || 'Failed to save schedule.');
                        }
                    } catch (e) {
                        alert('Network error saving schedule.');
                    }
                },
                
                async syncAdvisor() {
                    if (this.loadingMap['advisor-sync']) return;
                    this.loadingMap['advisor-sync'] = true;
                    try {
                        const res = await fetch('{{ route("admin.ms-teams.sync-advisor", $section) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        alert(data.message || 'Sync operation complete.');
                    } catch (e) {
                        alert('Network error syncing advisor.');
                    }
                    this.loadingMap['advisor-sync'] = false;
                },
                
                slugify(str) {
                    return str.toLowerCase().replace(/[^a-z0-9]/g, '-');
                }
            }));
        });
    </script>
</x-admin-layout>
