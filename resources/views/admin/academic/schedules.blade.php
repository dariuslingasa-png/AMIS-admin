@php
    $firstSectionId = (int) ($sections->first()?->id ?? 0);
    $activeWorkspace = 'schedule';
    $activeSectionId = (int) old('section_id', $firstSectionId);
    $failedForm = old('_schedule_form');
    $failedScheduleId = (int) old('schedule_id', 0);
    $activeSection = $sections->firstWhere('id', $activeSectionId) ?? $sections->first();
    $activeGradeLevel = $activeSection?->grade_level ?? '';
@endphp

<x-admin-layout title="Class Management Workspace">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        activeWorkspace: 'schedule',
        activeSectionId: @js($activeSectionId),
        activeGradeLevel: @js($activeGradeLevel),
        gradeSections: @js($sections->groupBy('grade_level')->map(fn($group) => $group->map(fn($s) => ['id' => $s->id])->values())),
        schedulesBySection: @js($schedulesBySection),
        syncModal: false,
        isSaving: false,
        isDeleting: false,
        isSyncing: false,
        editingCell: null,
        createModal: false,
        editModal: false,
        addModal: false,
        deleteModal: false,
        editAction: '',
        deleteAction: '',
        editForm: {
            id: 0,
            section_id: 0,
            subject_name: '',
            teacher_name: '',
            day: 'Sunday',
            start_time: '08:00',
            end_time: '09:00',
            spans_all_days: false,
            selected_days: ['Sunday']
        },
        openAddClass(sectionId) {
            this.editForm = {
                id: 0,
                section_id: sectionId,
                subject_name: '',
                teacher_name: '',
                day: 'Sunday',
                start_time: '08:00',
                end_time: '09:00',
                spans_all_days: false,
                selected_days: ['Sunday']
            };
            this.addModal = true;
        },
        toggleDaySelection(day) {
            if (this.editForm.selected_days.includes(day)) {
                if (this.editForm.selected_days.length > 1) {
                    this.editForm.selected_days = this.editForm.selected_days.filter(d => d !== day);
                }
            } else {
                this.editForm.selected_days.push(day);
            }
        },
        isDaySelected(day) {
            return this.editForm.spans_all_days || this.editForm.selected_days.includes(day);
        },
        getSelectedDaysString() {
            return this.editForm.selected_days.join(',');
        },
        formatHourLabel(hour) {
            let h = hour > 12 ? hour - 12 : hour;
            let ampm = hour >= 12 ? 'PM' : 'AM';
            return `${h}:00 ${ampm}`;
        },
        getSectionSchedules() {
            return this.schedulesBySection[this.editForm.section_id] || [];
        },
        getClassCell(day, hour) {
            let schedules = this.getSectionSchedules();
            let startMin = hour * 60;
            let endMin = (hour + 1) * 60;
            return schedules.find(s => {
                let sDay = s.day || s.payload?.day;
                if (sDay !== day && !s.spans_all_days) return false;
                return s.start_minutes < endMin && s.end_minutes > startMin;
            });
        },
        hasClassCell(day, hour) {
            return !!this.getClassCell(day, hour);
        },
        getClassCellBg(day, hour) {
            let schedule = this.getClassCell(day, hour);
            if (!schedule) return 'bg-white hover:bg-slate-50 border border-slate-100';
            let color = schedule.color_class || 'academic';
            if (color === 'quran') return 'bg-emerald-50 text-emerald-800 border border-emerald-100 font-extrabold';
            if (color === 'hadith') return 'bg-amber-50 text-amber-800 border border-amber-100 font-extrabold';
            if (color === 'arabic') return 'bg-blue-50 text-blue-800 border border-blue-100 font-extrabold';
            if (color === 'recess') return 'bg-red-50 text-red-800 border border-red-100 font-extrabold';
            if (color === 'event') return 'bg-teal-50 text-teal-800 border border-teal-100 font-extrabold';
            return 'bg-purple-50 text-purple-800 border border-purple-100 font-extrabold';
        },
        getClassCellSubject(day, hour) {
            let schedule = this.getClassCell(day, hour);
            return schedule ? schedule.subject_name : '';
        },
        getClassCellTitle(day, hour) {
            let schedule = this.getClassCell(day, hour);
            return schedule ? `${schedule.subject_name} (${schedule.time_label})` : '';
        },
        clickPreviewCell(day, hour) {
            let schedule = this.getClassCell(day, hour);
            if (!schedule) {
                this.editForm.day = day;
                if (!this.editForm.selected_days.includes(day)) {
                    this.editForm.selected_days = [day];
                }
                let startH = String(hour).padStart(2, '0');
                let endH = String(hour + 1).padStart(2, '0');
                this.editForm.start_time = `${startH}:00`;
                this.editForm.end_time = `${endH}:00`;
            }
        },
        editId: null,
        editName: '',
        editError: '',
        editSaving: false,
        mode: 'Face-to-Face',
        grade: 'Kinder 1',
        schoolYear: @js(config('services.school.year')),
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
        },
        startInlineCreate(sectionId, day, startTime, endTime) {
            this.editingCell = {
                type: 'create',
                id: 0,
                section_id: sectionId,
                day: day,
                start_time: startTime,
                end_time: endTime,
                subject_name: '',
                teacher_name: ''
            };
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        startInlineEdit(entry) {
            this.editingCell = {
                type: 'edit',
                id: entry.id,
                section_id: entry.section_id,
                day: entry.day,
                start_time: entry.start_time,
                end_time: entry.end_time,
                subject_name: entry.subject_name,
                teacher_name: entry.teacher_display || entry.teacher_name || ''
            };
            if (this.editingCell.teacher_name === 'Teacher pending') {
                this.editingCell.teacher_name = '';
            }
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        cancelInline() {
            this.editingCell = null;
        },
        saveInline() {
            if (!this.editingCell.subject_name.trim()) {
                alert('Subject name is required.');
                return;
            }
            this.isSaving = true;
            let url = this.editingCell.type === 'create'
                ? '{{ route("admin.academic.schedules.store") }}'
                : `/academic/schedules/${this.editingCell.id}`;

            let form = document.createElement('form');
            form.method = 'POST';
            form.action = url;

            let fields = {
                _token: '{{ csrf_token() }}',
                section_id: this.editingCell.section_id,
                subject_name: this.editingCell.subject_name.trim(),
                teacher_display: this.editingCell.teacher_name.trim(),
                day: this.editingCell.day,
                start_time: this.editingCell.start_time,
                end_time: this.editingCell.end_time
            };

            if (this.editingCell.type === 'edit') {
                fields._method = 'PATCH';
            }

            for (let k in fields) {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = fields[k];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        },
        deleteInline() {
            if (!confirm('Are you sure you want to delete this class schedule?')) return;
            this.isDeleting = true;
            let form = document.createElement('form');
            form.method = 'POST';
            form.action = `/academic/schedules/${this.editingCell.id}`;

            let fields = {
                _token: '{{ csrf_token() }}',
                _method: 'DELETE'
            };

            for (let k in fields) {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = fields[k];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        },
        triggerSync() {
            this.isSyncing = true;
            setTimeout(() => { this.isSyncing = false; this.syncModal = false; }, 900);
        }
    }">
        <div class="academic-hero-banner">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Class Management</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Configure daily class sections, assign advisory roles, and schedule daily timetables.
                    </p>
                </div>
                <template x-if="activeWorkspace === 'sections'">
                    <button type="button" @click="syncModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-indigo-700"></i>
                        Sync MS Teams
                    </button>
                </template>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-extrabold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-extrabold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-extrabold text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @include('admin.academic.schedules._timetable')
        @include('admin.academic.schedules._modals')

        {{-- ═══ CREATE SECTION MODAL ═══ --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 backdrop-blur-sm"
             x-show="createModal" x-cloak x-transition @click.self="createModal = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 border border-slate-100 overflow-hidden animate-scaleUp">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <span class="font-extrabold text-slate-900 text-base">Create Grade Section</span>
                    <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" @click="createModal = false">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.ms-teams.store') }}" class="px-6 py-5 space-y-4">
                    @csrf
                    <input type="hidden" name="school_year" :value="schoolYear">

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Grade Level *</label>
                        <select name="grade_level" x-model="grade" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                            <option value="Kinder 1">Kinder 1</option><option value="Kinder 2">Kinder 2</option>
                            <option value="Grade 1">Grade 1</option><option value="Grade 2">Grade 2</option><option value="Grade 3">Grade 3</option><option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option><option value="Grade 6">Grade 6</option><option value="Grade 7">Grade 7</option><option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option><option value="Grade 10">Grade 10</option><option value="Grade 11">Grade 11</option><option value="Grade 12">Grade 12</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Learning Mode *</label>
                        <select name="learning_mode" x-model="mode" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                            <option value="Face-to-Face">Face-to-Face</option>
                            <option value="Flexible Online Learning">Flexible Online Learning</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Section Name (Optional)</label>
                        <input type="text" name="name" placeholder="e.g. ABU BAKR" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Gender *</label>
                        <select name="gender" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                            <option value="male">Boys Only</option>
                            <option value="female">Girls Only</option>
                            <option value="merge">Merge</option>
                        </select>
                    </div>

                    <div x-show="mode === 'Flexible Online Learning'" class="flex flex-col gap-1" x-transition>
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Shift *</label>
                        <select name="shift" class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-sm rounded-xl px-3 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 transition">
                            <option value="1st Shift">1st Shift</option>
                            <option value="2nd Shift">2nd Shift</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="createModal = false" class="px-4 py-2 text-xs font-bold text-slate-655 hover:bg-slate-50 border border-slate-200 rounded-xl transition">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-700 hover:bg-emerald-800 rounded-xl transition">Create Section</button>
                    </div>
                </form>
            </div>
        </div>
            </div>
        </div>

        {{-- ═══ RENAME SECTION MODAL ═══ --}}
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
