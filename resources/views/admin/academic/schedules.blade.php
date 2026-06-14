@php
    $firstSectionId = (int) ($sections->first()?->id ?? 0);
    $activeWorkspace = request('tab', session('schedule_workspace', $errors->any() ? 'schedule' : 'sections'));
    $activeSectionId = (int) old('section_id', $firstSectionId);
    $failedForm = old('_schedule_form');
    $failedScheduleId = (int) old('schedule_id', 0);
@endphp

<x-admin-layout title="Class Management Workspace">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        activeWorkspace: @js($activeWorkspace),
        activeSectionId: @js($activeSectionId),
        addModal: @js($errors->any() && $failedForm !== 'edit'),
        editModal: @js($errors->any() && $failedForm === 'edit'),
        deleteModal: false,
        syncModal: false,
        isSaving: false,
        isDeleting: false,
        isSyncing: false,
        editAction: @js($failedScheduleId ? route('admin.academic.schedules.update', $failedScheduleId) : ''),
        deleteAction: '',
        editForm: @js([
            'id' => $failedScheduleId,
            'section_id' => $activeSectionId,
            'subject_name' => old('subject_name', ''),
            'teacher_name' => old('teacher_name', ''),
            'day' => old('day', 'Monday'),
            'start_time' => old('start_time', '08:00'),
            'end_time' => old('end_time', '09:00'),
        ]),
        openEdit(payload) {
            this.editForm = payload;
            this.editAction = payload.update_url;
            this.editModal = true;
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        openDelete(payload) {
            this.deleteAction = payload.destroy_url;
            this.editForm = payload;
            this.deleteModal = true;
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
                <template x-if="activeWorkspace === 'schedule'">
                    <button type="button" @click="addModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                        Schedule Class
                    </button>
                </template>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-extrabold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-extrabold text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        @include('admin.academic.schedules._tabs')
        @include('admin.academic.schedules._sections')
        @include('admin.academic.schedules._advisory')
        @include('admin.academic.schedules._timetable')
        @include('admin.academic.schedules._modals')
    </div>
</x-admin-layout>
