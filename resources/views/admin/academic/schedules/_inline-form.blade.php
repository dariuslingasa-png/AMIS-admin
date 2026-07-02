<div class="absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 z-30 bg-white border border-slate-200 rounded-2xl shadow-xl p-4 text-slate-800 space-y-3 w-[260px] cursor-default" @click.stop>
    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
        <span class="text-[10px] font-black uppercase text-indigo-700 tracking-wider" x-text="editingCell.type === 'create' ? 'Schedule Class' : 'Edit Schedule'"></span>
        <button type="button" @click="cancelInline()" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
    </div>
    <div class="flex flex-col gap-1">
        <label class="text-[9px] font-black uppercase text-slate-400 tracking-wide text-left">Subject *</label>
        <input type="text" x-model="editingCell.subject_name" placeholder="Subject Name" 
            class="w-full bg-slate-50 border border-slate-200 text-slate-850 text-xs rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition text-left">
    </div>
    <div class="flex flex-col gap-1">
        <label class="text-[9px] font-black uppercase text-slate-400 tracking-wide text-left">Teacher</label>
        <input type="text" x-model="editingCell.teacher_name" list="schedule-teachers" placeholder="Teacher Name" 
            class="w-full bg-slate-50 border border-slate-200 text-slate-850 text-xs rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition text-left">
    </div>
    <div class="flex flex-col gap-1">
        <label class="text-[9px] font-black uppercase text-slate-400 tracking-wide text-left">End Time *</label>
        <select x-model="editingCell.end_time" class="w-full bg-slate-50 border border-slate-200 text-slate-850 text-xs rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 transition text-left">
            @foreach($timeOptions as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center justify-between gap-1.5 pt-2 border-t border-slate-100">
        <button type="button" @click="cancelInline()" class="px-3.5 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer">Cancel</button>
        <div class="flex items-center gap-1.5">
            <template x-if="editingCell.type === 'edit'">
                <button type="button" @click="deleteInline()" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition cursor-pointer" title="Delete">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </template>
            <button type="button" @click="saveInline()" class="px-4 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-850 rounded-xl transition cursor-pointer shadow-sm shadow-indigo-950/20" :disabled="isSaving" :class="isSaving ? 'opacity-50' : ''">Save</button>
        </div>
    </div>
</div>
