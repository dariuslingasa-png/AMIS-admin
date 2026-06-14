<!-- Beautiful Interactive Advisory & Roster Modal -->
<div id="advisoryRosterModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200/80 transform scale-95 transition-all duration-300 flex flex-col max-h-[85vh]">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white relative flex-shrink-0">
            <div class="absolute right-0 top-0 -mr-6 -mt-6 h-32 w-32 rounded-full bg-emerald-500/15 blur-2xl"></div>
            <div class="flex items-start justify-between">
                <div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-white/10 text-emerald-100 rounded-md border border-white/10 backdrop-blur-xs mb-2">
                        Class Details
                    </span>
                    <h2 id="modalGradeLevel" class="text-2xl font-black tracking-tight">Grade Level</h2>
                    <p id="modalAdvisoryName" class="text-sm text-emerald-100/90 font-bold mt-1 uppercase tracking-wider"></p>
                </div>
                <button onclick="closeAdvisoryModal()" class="rounded-xl bg-white/10 p-2 text-white/80 hover:bg-white/15 active:bg-white/20 transition-all cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>

        <!-- Stats Roster Body -->
        <div class="p-6 overflow-y-auto flex-1 space-y-6 custom-scrollbar">
            <!-- Class telemetry grid -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Seats occupied</span>
                    <div id="modalOccupiedSeats" class="text-lg font-black text-slate-900 mt-1">0 / 0</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Remaining</span>
                    <div id="modalRemainingSeats" class="text-lg font-black text-slate-900 mt-1">0 open</div>
                </div>
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Fill Rate</span>
                    <div id="modalFillRate" class="text-lg font-black text-slate-900 mt-1">0%</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                    <div class="rounded-xl bg-violet-50 p-2 text-violet-650">
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Gender allocation</span>
                        <div id="modalGender" class="text-xs font-bold text-slate-700 mt-0.5">Male</div>
                    </div>
                </div>
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                    <div class="rounded-xl bg-emerald-50 p-2 text-emerald-600">
                        <i data-lucide="monitor" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Learning Shift</span>
                        <div id="modalShift" class="text-xs font-bold text-slate-700 mt-0.5">1st Shift</div>
                    </div>
                </div>
            </div>

            <!-- Student List -->
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm mb-3 flex items-center gap-1.5">
                    <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-500"></i>
                    Enrolled Class Roster
                    <span id="modalRosterCount" class="text-[10px] font-bold bg-slate-100 text-slate-650 px-2 py-0.5 rounded-full border border-slate-200/50">0 Students</span>
                </h3>
                
                <div id="modalRosterList" class="space-y-2 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                    <!-- Student items inserted here -->
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-between items-center flex-shrink-0">
            <button id="exportPdfBtn" onclick="exportRosterToPdf()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-5 py-2.5 rounded-xl cursor-pointer transition shadow-sm flex items-center gap-1.5 transition-all duration-150 hover:scale-[1.02] active:scale-[0.98]">
                <i data-lucide="file-down" class="w-4 h-4"></i>
                Export Official PDF
            </button>
            <button onclick="closeAdvisoryModal()" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-black px-5 py-2.5 rounded-xl cursor-pointer transition shadow-sm">
                Close Roster
            </button>
        </div>
    </div>
</div>
