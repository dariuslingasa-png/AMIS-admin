<x-admin-layout title="Academic Operations Workspace">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        activeWorkspace: 'attendance',
        exportModal: false,
        isExporting: false,
        isSyncing: false,
        activeSyncId: null,
        triggerSync(id) {
            this.activeSyncId = id;
            setTimeout(() => { this.activeSyncId = null; }, 950);
        },
        triggerGlobalExport() {
            this.isExporting = true;
            setTimeout(() => { this.isExporting = false; this.exportModal = false; }, 900);
        }
    }">
        <!-- Hero / Header Banner -->
        <div class="academic-hero-banner">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Academic Operations</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Monitor daily student attendance, track quarterly grade submissions, and compile registrar report datasets.
                    </p>
                </div>
                <div>
                    <template x-if="activeWorkspace === 'reports'">
                        <button type="button" @click="exportModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                            <i data-lucide="download-cloud" class="w-4 h-4 text-indigo-700"></i>
                            Export Datasets
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Telemetry Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Attendance Rate -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-emerald-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Attendance Rate</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-655 group-hover:scale-110 transition-transform">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-emerald-700 group-hover:text-emerald-650 transition-colors">{{ $attendance['rate'] }}%</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Daily average this week</p>
                </div>
            </div>

            <!-- Grades Submitted -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-purple-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Grades Submitted</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-50 text-purple-650 group-hover:scale-110 transition-transform">
                        <i data-lucide="award" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-900 group-hover:text-purple-650 transition-colors">{{ $grades['submitted'] }} / {{ $grades['total'] }}</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Sections finalized</p>
                </div>
            </div>

            <!-- Pending Audits -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-amber-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Pending Grades</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-655 group-hover:scale-110 transition-transform">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-amber-700 group-hover:text-amber-650 transition-colors">{{ $grades['pending'] }} Groups</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Awaiting instructor upload</p>
                </div>
            </div>

            <!-- Export logs -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-blue-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Archived Exports</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-655 group-hover:scale-110 transition-transform">
                        <i data-lucide="archive" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-blue-700 group-hover:text-blue-650 transition-colors">{{ count($reports) }} Sheets</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Ready for sync</p>
                </div>
            </div>
        </div>

        <!-- Segmented Tab Switcher Bar -->
        <div class="flex gap-1.5 p-1 bg-slate-100 border border-slate-200/50 rounded-2xl max-w-xl shadow-3xs">
            <button type="button" @click="activeWorkspace = 'attendance'" 
                :class="activeWorkspace === 'attendance' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                Attendance Logs
            </button>
            <button type="button" @click="activeWorkspace = 'grades'" 
                :class="activeWorkspace === 'grades' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider">
                <i data-lucide="award" class="w-3.5 h-3.5"></i>
                Grading & Marks
            </button>
            <button type="button" @click="activeWorkspace = 'reports'" 
                :class="activeWorkspace === 'reports' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider">
                <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                Exports & Reports
            </button>
        </div>

        <!-- ==================== WORKSPACE: ATTENDANCE ==================== -->
        <div x-show="activeWorkspace === 'attendance'" x-transition class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Attendance Stats card -->
                <div class="bg-white border border-gray-150 rounded-2xl p-5 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-slate-900 font-extrabold text-sm tracking-wide block uppercase mb-4">Daily Ratio Snapshot</span>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-xs font-semibold">
                                <span class="text-emerald-600 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Present
                                </span>
                                <span class="text-slate-800 font-extrabold">{{ $attendance['present'] }} students</span>
                            </div>
                            <div class="flex justify-between items-center text-xs font-semibold">
                                <span class="text-rose-600 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Absent
                                </span>
                                <span class="text-slate-800 font-extrabold">{{ $attendance['absent'] }} students</span>
                            </div>
                            <div class="flex justify-between items-center text-xs font-semibold">
                                <span class="text-amber-600 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Excused
                                </span>
                                <span class="text-slate-800 font-extrabold">{{ $attendance['excused'] }} students</span>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-100 mt-5">
                        <button type="button" @click="triggerSync('attendance')" class="w-full relative inline-flex items-center justify-center gap-2 bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200/80 font-black text-xs px-4 py-2.5 rounded-xl transition cursor-pointer shadow-3xs" :class="activeSyncId === 'attendance' ? 'btn-loading' : ''">
                            <span class="btn-spinner" x-show="activeSyncId === 'attendance'"></span>
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-slate-500" x-show="activeSyncId !== 'attendance'"></i>
                            <span class="btn-text-content">Sync MS Teams Attendance</span>
                        </button>
                    </div>
                </div>

                <!-- By Grade Level Table -->
                <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden md:col-span-2">
                    <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-3.5 flex items-center justify-between">
                        <span class="text-slate-900 font-extrabold text-xs tracking-wide uppercase">Attendance by Grade Level</span>
                    </div>
                    <div class="premium-table-wrap">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Grade Level Group</th>
                                    <th>Attendance Target</th>
                                    <th style="text-align: right;">Weekly Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendance['by_grade'] as $grade => $rate)
                                    <tr>
                                        <td class="font-extrabold text-slate-900 text-xs">{{ $grade }}</td>
                                        <td>
                                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden border border-slate-200/50 max-w-[120px]">
                                                <div class="bg-emerald-500 h-full rounded-full" style="width: {{ $rate }}%"></div>
                                            </div>
                                        </td>
                                        <td class="font-extrabold text-emerald-600 text-xs text-right">{{ $rate }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== WORKSPACE: GRADING & MARKS ==================== -->
        <div x-show="activeWorkspace === 'grades'" x-transition class="space-y-6">
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Quarterly Grade Sheet Logs</span>
                    <x-badge color="indigo">1st Quarter</x-badge>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Section / Class Group</th>
                                <th>Grade Upload Status</th>
                                <th>Upload Date</th>
                                <th style="text-align: right;">Operations</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grades['sections'] as $index => $g)
                                <tr>
                                    <td class="font-extrabold text-slate-900 text-sm">{{ $g['name'] }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $g['status'] === 'Submitted' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100/50' : 'bg-amber-50 text-amber-700 border border-amber-100/50' }} uppercase">
                                            {{ $g['status'] }}
                                        </span>
                                    </td>
                                    <td class="text-xs font-semibold text-slate-500">{{ $g['date'] }}</td>
                                    <td style="text-align: right;">
                                        <div class="flex justify-end gap-2">
                                            <template x-if="'{{ $g['status'] }}' === 'Submitted'">
                                                <button type="button" @click="triggerSync({{ $index }})" class="relative px-3 py-1.5 text-xxs font-bold text-slate-700 hover:bg-slate-50 rounded-lg border border-slate-200 transition cursor-pointer shadow-3xs" :class="activeSyncId === {{ $index }} ? 'btn-loading' : ''">
                                                    <span class="btn-spinner" x-show="activeSyncId === {{ $index }}"></span>
                                                    <span class="btn-text-content">Audit Sheets</span>
                                                </button>
                                            </template>
                                            <template x-if="'{{ $g['status'] }}' === 'Pending'">
                                                <button type="button" @click="triggerSync({{ $index }})" class="relative px-3 py-1.5 text-xxs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-lg transition cursor-pointer shadow-3xs" :class="activeSyncId === {{ $index }} ? 'btn-loading' : ''">
                                                    <span class="btn-spinner" x-show="activeSyncId === {{ $index }}"></span>
                                                    <span class="btn-text-content">Request Marks</span>
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== WORKSPACE: EXPORTS & REPORTS ==================== -->
        <div x-show="activeWorkspace === 'reports'" x-transition class="space-y-6">
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Available Registrar Datasets</span>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Report Name</th>
                                <th>File Format</th>
                                <th>File Size</th>
                                <th>Compiled Date</th>
                                <th style="text-align: right;">Download Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $r)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="file-text" class="w-4 h-4 text-indigo-600"></i>
                                            <span class="font-extrabold text-slate-900 text-sm tracking-wide">{{ $r['name'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $r['format'] === 'PDF' ? 'bg-rose-50 text-rose-700 border border-rose-100/50' : 'bg-emerald-50 text-emerald-700 border border-emerald-100/50' }} uppercase">
                                            {{ $r['format'] }}
                                        </span>
                                    </td>
                                    <td class="text-xs font-semibold text-slate-500">{{ $r['size'] }}</td>
                                    <td class="text-xs font-semibold text-slate-500">{{ $r['date'] }}</td>
                                    <td style="text-align: right;">
                                        <button class="px-3.5 py-1.5 text-xxs font-bold text-slate-700 hover:bg-slate-100 rounded-lg border border-slate-200 transition cursor-pointer shadow-3xs">
                                            Download
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== MODALS CONFIG REGISTRY ==================== -->
        <!-- Global Dataset Export Modal -->
        <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs" 
             x-show="exportModal" x-cloak x-transition>
            <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-6 flex flex-col gap-4 border border-slate-200" @click.away="exportModal = false">
                <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <span class="admin-modal-title text-base font-extrabold text-slate-950">Compile Master Datasets</span>
                        <div class="text-[11px] text-slate-400 font-light mt-0.5">Run background compilation of system spreadsheets</div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="exportModal = false">&times;</button>
                </div>
                <div class="space-y-4">
                    <p class="text-xs font-semibold text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-150">
                        This operation compiles student data, advisor directories, grading distribution curves, and syncs Microsoft Teams audit performance.
                    </p>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Select Export Format *</label>
                        <select class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                            <option>PDF Registrar Format (Standard)</option>
                            <option>Excel Sync Layout (Raw Dataset)</option>
                        </select>
                    </div>
                </div>
                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button type="button" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="exportModal = false">Cancel</button>
                    <button type="button" class="relative inline-flex items-center justify-center px-5 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer min-w-[125px] shadow-sm shadow-indigo-950/20" 
                            :class="isExporting ? 'btn-loading' : ''" 
                            @click="triggerGlobalExport()">
                        <span class="btn-spinner" x-show="isExporting"></span>
                        <span class="btn-text-content">Begin Export</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
