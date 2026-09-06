<!-- Quick Print Records Hub Modal -->
<div id="print-records-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-md transition-all duration-200" onclick="if(event.target === this) closePrintRecordsModal()">
    <div class="relative w-full max-w-2xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900 animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 px-6 py-5 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3.5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                    <i data-lucide="printer" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-950 dark:text-white">Print & Export Hub</h3>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">Quickly generate batch print sheets, ID cards, credentials, or forms for filtered students.</p>
                </div>
            </div>
            <button type="button" onclick="closePrintRecordsModal()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 transition cursor-pointer">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- Body: 1-Click Action Grid -->
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                
                <!-- 1. ID Cards Batch -->
                <button type="button" onclick="closePrintRecordsModal(); runPrintRecordAction('id_cards')" class="flex items-start gap-3.5 p-4 rounded-2xl border border-emerald-200 dark:border-emerald-900/60 bg-emerald-50/50 dark:bg-emerald-950/20 hover:bg-emerald-100/60 transition text-left cursor-pointer group">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm group-hover:scale-105 transition">
                        <i data-lucide="contact-round" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-extrabold text-emerald-900 dark:text-emerald-300">ID Cards (Dual-Sided Sheet)</h4>
                        <p class="text-[11px] font-medium text-emerald-700/80 dark:text-emerald-400 mt-0.5">Generate all Front & Back ID cards ready for card printer.</p>
                    </div>
                </button>

                <!-- 2. Application Forms Batch -->
                <button type="button" onclick="closePrintRecordsModal(); runPrintRecordAction('forms_batch')" class="flex items-start gap-3.5 p-4 rounded-2xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/20 hover:bg-blue-100/60 transition text-left cursor-pointer group">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-sm group-hover:scale-105 transition">
                        <i data-lucide="file-signature" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-extrabold text-blue-900 dark:text-blue-300">Official Enrollment Forms</h4>
                        <p class="text-[11px] font-medium text-blue-700/80 dark:text-blue-400 mt-0.5">Print complete student enrollment application sheets.</p>
                    </div>
                </button>

                <!-- 3. Credentials & Password Slips -->
                <button type="button" onclick="closePrintRecordsModal(); runPrintRecordAction('credentials')" class="flex items-start gap-3.5 p-4 rounded-2xl border border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/20 hover:bg-amber-100/60 transition text-left cursor-pointer group">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white shadow-sm group-hover:scale-105 transition">
                        <i data-lucide="key-round" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-extrabold text-amber-900 dark:text-amber-300">Microsoft Credentials Slips</h4>
                        <p class="text-[11px] font-medium text-amber-700/80 dark:text-amber-400 mt-0.5">Print login usernames and passwords for student release.</p>
                    </div>
                </button>

                <!-- 4. Masters Enrollees List Table -->
                <button type="button" onclick="closePrintRecordsModal(); runPrintRecordAction('masters_list')" class="flex items-start gap-3.5 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 hover:bg-slate-100 transition text-left cursor-pointer group">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-700 text-white shadow-sm group-hover:scale-105 transition">
                        <i data-lucide="table" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-slate-200">Masters Student List</h4>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">Print clean tabular roster of filtered students.</p>
                    </div>
                </button>
            </div>

            <!-- Bottom: Bulk List Paste shortcut -->
            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <button type="button" onclick="closePrintRecordsModal(); document.getElementById('bulk-print-modal').classList.remove('hidden');" class="inline-flex items-center gap-1.5 text-xs font-bold text-violet-700 dark:text-violet-400 hover:underline cursor-pointer">
                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5"></i>
                    <span>Paste custom list of Student Numbers from Excel...</span>
                </button>

                <button type="button" onclick="closePrintRecordsModal()" class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
