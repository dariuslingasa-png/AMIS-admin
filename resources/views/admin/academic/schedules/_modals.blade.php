<datalist id="schedule-teachers">
    @foreach($teachers as $teacher)
        <option value="{{ $teacher['name'] }}"></option>
    @endforeach
</datalist>

<div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs p-4"
     x-show="syncModal" x-cloak x-transition>
    <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-8 flex flex-col gap-4 border border-slate-200">
        <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
            <span class="admin-modal-title text-base font-extrabold text-slate-950">Sync Microsoft Teams</span>
            <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="syncModal = false">&times;</button>
        </div>
        <p class="text-xs font-semibold text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-150">
            This action will sync all active class sections with your institutional Microsoft 365 tenant.
        </p>
        <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
            <button type="button" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="syncModal = false">Cancel</button>
            <button type="button" class="relative inline-flex items-center justify-center px-5 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer min-w-[125px] shadow-sm shadow-indigo-950/20"
                    :class="isSyncing ? 'btn-loading' : ''" @click="triggerSync()">
                <span class="btn-spinner" x-show="isSyncing"></span>
                <span class="btn-text-content">Sync Workspace</span>
            </button>
        </div>
    </div>
</div>

<div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs p-4"
     x-show="addModal" x-cloak x-transition>
    <form method="POST" action="{{ route('admin.academic.schedules.store') }}" class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-4xl p-8 flex flex-col gap-4 border border-slate-200" @submit="isSaving = true">
        @csrf
        <input type="hidden" name="_schedule_form" value="create">
        <input type="hidden" name="_add_another" id="add_another_flag" value="0">
        <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
                <span class="admin-modal-title text-base font-extrabold text-slate-950">Schedule Class</span>
                <div class="text-[11px] text-slate-400 font-light mt-0.5">Map a subject to a day and time slot.</div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="closeAddModal()">&times;</button>
        </div>
        @include('admin.academic.schedules._form-fields', ['prefix' => 'create'])
        <div class="admin-modal-footer flex justify-between gap-2 pt-3 border-t border-slate-50 mt-2">
            <button type="button" class="px-4 py-2 text-xs font-bold text-slate-655 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="closeAddModal()">Cancel</button>
            <div class="flex gap-2">
                <button type="submit"
                    @click="clearDraft(); document.getElementById('add_another_flag').value = '1'"
                    class="relative inline-flex min-w-[160px] items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold text-indigo-700 bg-white border-2 border-indigo-200 hover:bg-indigo-50 hover:border-indigo-400 rounded-xl transition cursor-pointer"
                    :class="isSaving ? 'btn-loading opacity-60' : ''" :disabled="isSaving">
                    <span class="btn-spinner" x-show="isSaving"></span>
                    <span class="btn-text-content">+ Add Another Slot</span>
                </button>
                <button type="submit"
                    @click="clearDraft(); document.getElementById('add_another_flag').value = '0'"
                    class="relative inline-flex min-w-[130px] items-center justify-center px-4 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer"
                    :class="isSaving ? 'btn-loading' : ''" :disabled="isSaving">
                    <span class="btn-spinner" x-show="isSaving"></span>
                    <span class="btn-text-content">Save Schedule</span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs p-4"
     x-show="editModal" x-cloak x-transition>
    <form method="POST" :action="editAction" class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-4xl p-8 flex flex-col gap-4 border border-slate-200" @submit="isSaving = true">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_schedule_form" value="edit">
        <input type="hidden" name="schedule_id" x-model="editForm.id">
        <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
                <span class="admin-modal-title text-base font-extrabold text-slate-950">Edit Schedule</span>
                <div class="text-[11px] text-slate-400 font-light mt-0.5" x-text="editForm.subject_name"></div>
            </div>
            <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="editModal = false">&times;</button>
        </div>
        @include('admin.academic.schedules._form-fields', ['prefix' => 'edit'])
        <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
            <button type="button" class="px-4 py-2 text-xs font-bold text-slate-655 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="editModal = false">Cancel</button>
            <button type="submit" class="relative inline-flex min-w-[130px] items-center justify-center px-4 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer" :class="isSaving ? 'btn-loading' : ''" :disabled="isSaving">
                <span class="btn-spinner" x-show="isSaving"></span>
                <span class="btn-text-content">Update Schedule</span>
            </button>
        </div>
    </form>
</div>

<div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs p-4"
     x-show="deleteModal" x-cloak x-transition>
    <form method="POST" :action="deleteAction" class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-8 flex flex-col gap-4 border border-slate-200" @submit="isDeleting = true">
        @csrf
        @method('DELETE')
        <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
            <span class="admin-modal-title text-base font-extrabold text-slate-950">Delete Schedule</span>
            <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="deleteModal = false">&times;</button>
        </div>
        <p class="text-xs font-semibold text-slate-600">Remove <strong x-text="editForm.subject_name"></strong> from this timetable?</p>
        <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
            <button type="button" class="px-4 py-2 text-xs font-bold text-slate-655 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="deleteModal = false">Cancel</button>
            <button type="submit" class="relative inline-flex min-w-[120px] items-center justify-center px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition cursor-pointer" :class="isDeleting ? 'btn-loading' : ''" :disabled="isDeleting">
                <span class="btn-spinner" x-show="isDeleting"></span>
                <span class="btn-text-content">Delete</span>
            </button>
        </div>
    </form>
</div>
