@php
    $firstSectionId = (int) ($sections->first()?->id ?? 0);
    $activeWorkspace = 'schedule';
    $activeSectionId = (int) old('section_id', $firstSectionId);
    $failedForm = old('_schedule_form');
    $failedScheduleId = (int) old('schedule_id', 0);
    $activeSection = $sections->firstWhere('id', $activeSectionId) ?? $sections->first();
    $activeGradeLevel = $activeSection?->grade_level ?? '';
    $reopenAddModal  = session('reopen_add_modal');
    $clearDraftSection = session('clear_draft_section');
@endphp


<x-admin-layout title="Class Management Workspace">
    @if($clearDraftSection)
    <script>
        try { localStorage.removeItem('schedule_draft_{{ $clearDraftSection }}'); } catch(e) {}
    </script>
    @endif
    <div class="analytics-page flex flex-col gap-6" x-data="{
        activeWorkspace: 'schedule',
        activeSectionId: @js($activeSectionId),
        activeGradeLevel: @js($activeGradeLevel),
        gradeSections: @js($sections->groupBy('grade_level')->map(fn($group) => $group->map(fn($s) => ['id' => $s->id, 'learning_mode' => $s->learning_mode])->values())),
        schedulesBySection: @js($schedulesBySection),
        syncModal: false,
        addModal: @js((bool) $reopenAddModal),
        isSaving: false,
        isDeleting: false,
        isSyncing: false,
        editingCell: null,
        createModal: false,
        editModal: false,
        renameSectionModal: false,
        deleteModal: false,
        editAction: '',
        deleteAction: '',
        editForm: {
            id: 0,
            section_id: @js($reopenAddModal ?: $activeSectionId),
            subject_name: '',
            teacher_name: '',
            day: '',
            start_time: '',
            end_time: '',
            spans_all_days: false,
            selected_days: []
        },
        gradeHasMode(mode) {
            let list = this.gradeSections[this.activeGradeLevel] || [];
            if (mode === 'f2f') {
                return list.some(s => s.learning_mode.toLowerCase().includes('face') || s.learning_mode.toLowerCase().includes('f2f'));
            }
            return list.some(s => s.learning_mode.toLowerCase().includes('online') || s.learning_mode.toLowerCase().includes('flexible'));
        },
        openAddClass(sectionId) {
            let draftKey = 'schedule_draft_' + sectionId;
            let saved = null;
            try { saved = JSON.parse(localStorage.getItem(draftKey)); } catch(e) {}
            if (saved && saved.section_id == sectionId) {
                this.editForm = saved;
            } else {
                // Auto-fill start_time from the latest end_time of existing schedules
                let existing = this.schedulesBySection[String(sectionId)] || [];
                let latestEnd = '';
                if (existing.length > 0) {
                    let maxEnd = existing.reduce((max, s) => {
                        return s.end_time > max ? s.end_time : max;
                    }, '');
                    latestEnd = maxEnd;
                }
                this.editForm = {
                    id: 0,
                    section_id: sectionId,
                    subject_name: '',
                    teacher_name: '',
                    day: '',
                    start_time: latestEnd,
                    end_time: '',
                    spans_all_days: false,
                    selected_days: []
                };
            }
            this.addModal = true;
        },
        saveDraft() {
            if (!this.editForm.section_id) return;
            let draftKey = 'schedule_draft_' + this.editForm.section_id;
            // Only save if there's something meaningful to preserve
            if (this.editForm.subject_name || this.editForm.start_time) {
                try { localStorage.setItem(draftKey, JSON.stringify(this.editForm)); } catch(e) {}
            }
        },
        clearDraft(sectionId) {
            let id = sectionId || this.editForm.section_id;
            try { localStorage.removeItem('schedule_draft_' + id); } catch(e) {}
        },
        closeAddModal() {
            this.saveDraft();
            this.addModal = false;
        },
        toggleDaySelection(day) {
            if (this.editForm.selected_days.includes(day)) {
                this.editForm.selected_days = this.editForm.selected_days.filter(d => d !== day);
                if (this.editForm.selected_days.length === 0) {
                    this.editForm.start_time = '';
                    this.editForm.end_time = '';
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
        formatTimeIntervalLabel(start, end) {
            let parseTime = (t) => {
                let [h, m] = t.split(':').map(Number);
                let ampm = h >= 12 ? 'PM' : 'AM';
                let displayH = h % 12 || 12;
                return { displayH, mStr: String(m).padStart(2, '0'), ampm };
            };
            let s = parseTime(start);
            let e = parseTime(end);
            if (s.ampm === e.ampm) {
                return `${s.displayH}:${s.mStr}-${e.displayH}:${e.mStr} ${e.ampm}`;
            }
            return `${s.displayH}:${s.mStr} ${s.ampm} - ${e.displayH}:${e.mStr} ${e.ampm}`;
        },
        getPreviewIntervals() {
            let boundaries = [];
            let schedules = this.getSectionSchedules();
            
            schedules.forEach(s => {
                boundaries.push(s.start_minutes);
                boundaries.push(s.end_minutes);
            });

            if (this.editForm.start_time && this.editForm.end_time) {
                let [sh, sm] = this.editForm.start_time.split(':').map(Number);
                let [eh, em] = this.editForm.end_time.split(':').map(Number);
                if (!isNaN(sh) && !isNaN(eh)) {
                    let draftStart = sh * 60 + sm;
                    let draftEnd = eh * 60 + em;
                    if (draftStart < draftEnd) {
                        boundaries.push(draftStart);
                        boundaries.push(draftEnd);
                    }
                }
            }

            boundaries = [...new Set(boundaries)].sort((a, b) => a - b);
            let list = [];
            for (let i = 0; i < boundaries.length - 1; i++) {
                let startMin = boundaries[i];
                let endMin = boundaries[i+1];
                
                let sh = Math.floor(startMin / 60);
                let sm = startMin % 60;
                let eh = Math.floor(endMin / 60);
                let em = endMin % 60;

                let startTimeStr = `${String(sh).padStart(2, '0')}:${String(sm).padStart(2, '0')}`;
                let endTimeStr = `${String(eh).padStart(2, '0')}:${String(em).padStart(2, '0')}`;
                
                list.push({
                    start_minutes: startMin,
                    end_minutes: endMin,
                    start_time: startTimeStr,
                    end_time: endTimeStr,
                    label: this.formatTimeIntervalLabel(startTimeStr, endTimeStr),
                    minutes: endMin - startMin
                });
            }
            return list;
        },
        getSectionSchedules() {
            let id = String(this.editForm.section_id);
            return this.schedulesBySection[id] || [];
        },
        getClassCell(day, interval) {
            let schedules = this.getSectionSchedules();
            return schedules.find(s => {
                if (this.editForm && this.editForm.id && s.id == this.editForm.id) return false;
                let sDay = s.day || s.payload?.day;
                if (sDay !== day && !s.spans_all_days) return false;
                return s.start_minutes <= interval.start_minutes && s.end_minutes >= interval.end_minutes;
            });
        },
        hasClassCell(day, interval) {
            return !!this.getClassCell(day, interval);
        },
        isDraftCell(day, interval) {
            if (!this.isDaySelected(day)) return false;
            if (!this.editForm.start_time || !this.editForm.end_time) return false;
            let [sh, sm] = this.editForm.start_time.split(':').map(Number);
            let [eh, em] = this.editForm.end_time.split(':').map(Number);
            let draftStart = sh * 60 + sm;
            let draftEnd = eh * 60 + em;
            return draftStart <= interval.start_minutes && draftEnd >= interval.end_minutes;
        },
        getClassCellBg(day, interval) {
            if (this.isDraftCell(day, interval)) {
                return 'bg-indigo-600 text-white font-extrabold border border-indigo-700 shadow-xs';
            }
            let schedule = this.getClassCell(day, interval);
            if (!schedule) return 'bg-white hover:bg-slate-50 border border-slate-100';
            let color = schedule.color_class || 'academic';
            if (color === 'quran') return 'bg-emerald-50 text-emerald-800 border border-emerald-100 font-extrabold';
            if (color === 'hadith') return 'bg-amber-50 text-amber-800 border border-amber-100 font-extrabold';
            if (color === 'arabic') return 'bg-blue-50 text-blue-800 border border-blue-100 font-extrabold';
            if (color === 'recess') return 'bg-red-50 text-red-800 border border-red-100 font-extrabold';
            if (color === 'event') return 'bg-teal-50 text-teal-800 border border-teal-100 font-extrabold';
            return 'bg-purple-50 text-purple-800 border border-purple-100 font-extrabold';
        },
        getClassCellSubject(day, interval) {
            let schedule = this.getClassCell(day, interval);
            return schedule ? schedule.subject_name : '';
        },
        getClassCellTitle(day, interval) {
            let schedule = this.getClassCell(day, interval);
            return schedule ? `${schedule.subject_name} (${schedule.time_label})` : '';
        },
        clickPreviewCell(day, interval) {
            let schedule = this.getClassCell(day, interval);
            if (schedule) {
                this.startInlineEdit(schedule);
                return;
            }

            if (this.isDraftCell(day, interval)) {
                this.toggleDaySelection(day);
                return;
            }

            if (!this.editForm.start_time || !this.editForm.end_time) {
                this.editForm.day = day;
                this.editForm.selected_days = [day];
                this.editForm.start_time = interval.start_time;
                this.editForm.end_time = interval.end_time;
                return;
            }

            let [startH, startM] = this.editForm.start_time.split(':').map(Number);
            let startMin = startH * 60 + startM;
            if (interval.start_minutes === startMin) {
                this.toggleDaySelection(day);
            } else {
                this.editForm.day = day;
                this.editForm.selected_days = [day];
                this.editForm.start_time = interval.start_time;
                this.editForm.end_time = interval.end_time;
            }
        },
        getPreviewCellColspan(day, interval) {
            let daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

            // Helper: two cells are mergeable if same subject + same time slot
            let isSameClass = (a, b) => {
                if (!a || !b) return false;
                if (a.id && b.id && a.id === b.id) return true;
                return a.subject_name === b.subject_name &&
                       a.start_time === b.start_time &&
                       a.end_time === b.end_time;
            };

            if (this.isDraftCell(day, interval)) {
                let dayIndex = daysList.indexOf(day);
                if (dayIndex > 0) {
                    let prevDay = daysList[dayIndex - 1];
                    if (this.isDraftCell(prevDay, interval)) {
                        return 0;
                    }
                }
                let colspan = 1;
                for (let i = dayIndex + 1; i < 5; i++) {
                    let nextDay = daysList[i];
                    if (this.isDraftCell(nextDay, interval)) {
                        colspan++;
                    } else {
                        break;
                    }
                }
                return colspan;
            }

            let cell = this.getClassCell(day, interval);
            if (cell) {
                let dayIndex = daysList.indexOf(day);
                // If prev day has same class, this cell is part of a merge - hide it (return 0)
                if (dayIndex > 0) {
                    let prevDay = daysList[dayIndex - 1];
                    let prevCell = this.getClassCell(prevDay, interval);
                    if (isSameClass(prevCell, cell)) {
                        return 0;
                    }
                }
                // Count how many consecutive days share the same class - colspan
                let colspan = 1;
                for (let i = dayIndex + 1; i < 5; i++) {
                    let nextDay = daysList[i];
                    let nextCell = this.getClassCell(nextDay, interval);
                    if (isSameClass(cell, nextCell)) {
                        colspan++;
                    } else {
                        break;
                    }
                }
                return colspan;
            }

            return 1;
        },
        editId: null,
        editName: '',
        editError: '',
        editSaving: false,
        mode: 'Face-to-Face',
        grade: 'Kinder 1',
        schoolYear: @js(config('services.school.year')),
        openEdit(id, name) { this.editId = id; this.editName = name; this.editError = ''; this.editSaving = false; this.renameSectionModal = true; },
        async saveEdit() {
            this.editSaving = true; this.editError = '';
            try {
                const res = await fetch(`/ms-teams/${this.editId}/update`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ name: this.editName.trim() }),
                });
                const data = await res.json();
                if (data.success) { this.renameSectionModal = false; location.reload(); }
                else { this.editError = data.message || 'Failed to update'; this.editSaving = false; }
            } catch (e) { this.editError = 'Network error. Try again.'; this.editSaving = false; }
        },
        startInlineCreate(sectionId, day, startTime, endTime) {
            // Open the Add modal pre-filled with day, time, and section
            this.editForm = {
                id: 0,
                section_id: sectionId,
                subject_name: '',
                teacher_name: '',
                day: day,
                start_time: startTime,
                end_time: endTime,
                spans_all_days: false,
                selected_days: [day]
            };
            this.addModal = true;
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        startInlineEdit(entry) {
            this.editForm = {
                id: entry.id,
                section_id: entry.section_id,
                subject_name: entry.subject_name,
                teacher_name: entry.teacher_display || entry.teacher_name || '',
                day: entry.day,
                start_time: entry.start_time,
                end_time: entry.end_time,
                spans_all_days: entry.spans_all_days ? true : false,
                selected_days: entry.day ? entry.day.split(',') : []
            };
            if (this.editForm.teacher_name === 'Teacher pending') {
                this.editForm.teacher_name = '';
            }
            this.editAction = `/academic/schedules/${entry.id}`;
            this.editingCell = null;
            this.editModal = true;
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
    }"
    x-init="
        if (addModal && editForm.section_id && !editForm.start_time) {
            let existing = schedulesBySection[String(editForm.section_id)] || [];
            if (existing.length > 0) {
                let maxEnd = existing.reduce((max, s) => s.end_time > max ? s.end_time : max, '');
                editForm.start_time = maxEnd;
            }
        }
    ">
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
             x-show="renameSectionModal" x-cloak x-transition @click.self="if(!editSaving) renameSectionModal = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 border border-slate-100 overflow-hidden animate-scaleUp">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <span class="font-extrabold text-slate-900 text-base">Rename Section</span>
                    <button type="button" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition" x-show="!editSaving" @click="renameSectionModal = false">
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
                    <button type="button" @click="renameSectionModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 border border-slate-200 rounded-xl transition" :disabled="editSaving">Cancel</button>
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
