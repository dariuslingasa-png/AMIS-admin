<div class="flex gap-1.5 p-1 bg-slate-100 border border-slate-200/50 rounded-2xl max-w-xl shadow-3xs">
    <button type="button" @click="activeWorkspace = 'sections'"
        :class="activeWorkspace === 'sections' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
        class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider">
        <i data-lucide="users-round" class="w-3.5 h-3.5"></i>
        Active Sections
    </button>
    <button type="button" @click="activeWorkspace = 'advisory'"
        :class="activeWorkspace === 'advisory' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
        class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider">
        <i data-lucide="contact-2" class="w-3.5 h-3.5"></i>
        Advisory Faculty
    </button>
    <button type="button" @click="activeWorkspace = 'schedule'"
        :class="activeWorkspace === 'schedule' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
        class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider">
        <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i>
        Class Schedules
    </button>
</div>
