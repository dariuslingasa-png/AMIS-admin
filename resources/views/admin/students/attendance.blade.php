<x-admin-layout
    title="M365 Class Call Attendance Dashboard"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Call Attendance', 'href' => null],
    ]"
>
    <!-- Custom CSS for Printable Reports & Clean Layout -->
    <style>
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .no-print, .no-print * {
                display: none !important;
            }
            .print-full-width {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }
        [x-cloak] { display: none !important; }

        /* Custom border overrides to resolve Tailwind CSS compilation/caching issues on production */
        .border-slate-100 { border-color: #f1f5f9 !important; }
        .border-slate-200 { border-color: #e2e8f0 !important; }
        .border-indigo-100 { border-color: #e0e7ff !important; }
        .border-emerald-100 { border-color: #d1fae5 !important; }
        .border-blue-100 { border-color: #dbeafe !important; }
        .border-purple-100 { border-color: #f3e8ff !important; }
        .border-teal-100 { border-color: #ccfbf1 !important; }
        .border-rose-100 { border-color: #ffe4e6 !important; }
        .border-pink-100 { border-color: #fce7f3 !important; }
        .bg-teal-50 { background-color: #f0fdf4 !important; }
        .text-teal-700 { color: #0f766e !important; }
    </style>

    <div x-data="attendanceDashboard" class="space-y-6 print-full-width">

        <!-- Top Status Banner -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i data-lucide="video" class="h-6 w-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Class Call Attendance</h1>
                    <p class="text-xs font-semibold text-slate-500 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span>Report Generated: <strong class="text-slate-700" x-text="generatedAt || 'Loading...'"></strong></span>
                        <span>·</span>
                        <span>M365 Sync: <strong class="text-slate-700" x-text="lastSync"></strong></span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 self-start md:self-center">
                <!-- Sync Button -->
                <button 
                    type="button" 
                    @click="forceSync()"
                    :disabled="syncing"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 active:scale-95 transition shadow-xs cursor-pointer disabled:opacity-50"
                >
                    <svg x-show="syncing" class="animate-spin h-3.5 w-3.5 text-slate-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <i x-show="!syncing" data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                    <span x-text="syncing ? 'Synchronizing...' : 'Sync Microsoft Graph'"></span>
                </button>
            </div>
        </section>

        <!-- Feedback Messages -->
        <div class="no-print space-y-3" x-cloak>
            <div x-show="errorMessage" class="flex items-center gap-3 rounded-2xl bg-rose-50 border border-rose-200 px-4 py-3 text-xs font-bold text-rose-800">
                <i data-lucide="alert-circle" class="h-4.5 w-4.5 text-rose-600 shrink-0"></i>
                <span x-text="errorMessage"></span>
            </div>
            <div x-show="successMessage" class="flex items-center gap-3 rounded-2xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-xs font-bold text-emerald-800">
                <i data-lucide="check-circle-2" class="h-4.5 w-4.5 text-emerald-600 shrink-0"></i>
                <span x-text="successMessage"></span>
            </div>
        </div>

        <!-- Filters Panel -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4 no-print">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i>
                    Attendance Filters
                </h2>
                <button 
                    type="button"
                    @click="
                        selectedSchoolYear = '';
                        selectedGrade = '';
                        selectedLearningMode = '';
                        selectedAdviser = '';
                        selectedStatus = '';
                        searchQuery = '';
                        fetchData(1);
                    "
                    class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 transition"
                >
                    Reset Filters
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- School Year -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">School Year</label>
                    <select x-model="selectedSchoolYear" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All School Years</option>
                        @foreach($schoolYears as $sy)
                            <option value="{{ $sy }}">{{ $sy }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Grade Level -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Grade Level</label>
                    <select x-model="selectedGrade" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Grade Levels</option>
                        @foreach($gradeLevels as $gl)
                            <option value="{{ $gl }}">{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Learning Mode -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Learning Mode</label>
                    <select x-model="selectedLearningMode" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Modes</option>
                        <option value="f2f">Face-to-Face</option>
                        <option value="odl_1st">ODL - 1st Shift</option>
                        <option value="odl_2nd">ODL - 2nd Shift</option>
                    </select>
                </div>

                <!-- Advisor -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Advisor</label>
                    <select x-model="selectedAdviser" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Advisors</option>
                        @foreach($advisors as $adv)
                            <option value="{{ $adv['email'] }}">{{ $adv['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Call Status -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Class Call Status</label>
                    <select x-model="selectedStatus" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Attendance Statuses</option>
                        <option value="joined_call">Joined Class Call (Meetings > 0)</option>
                        <option value="no_call">Never Joined Call (Meetings = 0)</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Enrolled -->
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total Enrolled</p>
                <div class="text-2xl font-black text-slate-900 mt-2" x-text="summary.total_registered">0</div>
                <p class="text-[9px] font-semibold text-slate-500 mt-1">Students in records</p>
            </div>
            <!-- Active Call Participants -->
            <div class="rounded-2xl border border-teal-100 bg-teal-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                <button 
                    @click="selectedStatus = 'joined_call'; fetchData(1);"
                    class="absolute top-3 right-3 text-teal-600 hover:text-teal-800 hover:bg-teal-100/50 p-1.5 rounded-lg transition cursor-pointer"
                    title="Filter Joined Call"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </button>
                <p class="text-[9px] font-black uppercase tracking-wider text-teal-700">Joined Class Calls</p>
                <div class="text-2xl font-black text-teal-750 mt-2" x-text="summary.teams_app_used">0</div>
                <p class="text-[9px] font-semibold text-teal-700 mt-1">Students with meeting activity</p>
            </div>
            <!-- Never Joined Call -->
            <div class="rounded-2xl border border-rose-100 bg-rose-50/10 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                <button 
                    @click="selectedStatus = 'no_call'; fetchData(1);"
                    class="absolute top-3 right-3 text-rose-500 hover:text-rose-700 hover:bg-rose-100/50 p-1.5 rounded-lg transition cursor-pointer"
                    title="Filter Never Joined"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </button>
                <p class="text-[9px] font-black uppercase tracking-wider text-rose-600">Never Joined Call</p>
                <div class="text-2xl font-black text-rose-700 mt-2" x-text="summary.teams_app_never_used">0</div>
                <p class="text-[9px] font-semibold text-rose-600 mt-1">Zero meetings attended</p>
            </div>
            <!-- Average Meetings -->
            <div class="rounded-2xl border border-blue-100 bg-blue-50/20 p-5 shadow-xs hover:shadow-md transition duration-300">
                <p class="text-[9px] font-black uppercase tracking-wider text-blue-600">Average Calls</p>
                <div class="text-2xl font-black text-blue-700 mt-2" x-text="avgMeetings()">0</div>
                <p class="text-[9px] font-semibold text-blue-600 mt-1">Meetings per active student</p>
            </div>
        </div>

        <!-- Student Roster Section -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Student Call Attendance Directory</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Roster of synced enrollees with Microsoft Teams call telemetry data.</p>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-center no-print">
                    <button @click="exportCSV()" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xxs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        <i data-lucide="download" class="h-3 w-3"></i> Export CSV
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="no-print flex items-center justify-between gap-4">
                <div class="relative w-full max-w-sm">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </span>
                    <input 
                        type="text" 
                        x-model="searchQuery" 
                        @input.debounce.300ms="fetchData(1)"
                        placeholder="Search by name, ID, or email..." 
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-4 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                </div>
                <div class="text-xxs font-semibold text-slate-400" x-text="`${students.total} matching records`"></div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs align-middle">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th class="px-4 py-3 font-black">Student ID</th>
                            <th class="px-4 py-3 font-black">Student Name</th>
                            <th class="px-4 py-3 font-black">Grade</th>
                            <th class="px-4 py-3 font-black">Gender</th>
                            <th class="px-4 py-3 font-black">Microsoft Email</th>
                            <th class="px-4 py-3 text-center font-black">Meetings Attended</th>
                            <th class="px-4 py-3 font-black">Last Call/Activity</th>
                            <th class="px-4 py-3 font-black">Teams Roster</th>
                            <th class="px-4 py-3 text-right font-black no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Loading State -->
                        <tr x-show="loading">
                            <td colspan="9" class="p-8 text-center text-slate-400 font-bold no-print">
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Retrieving attendance records...</span>
                                </div>
                            </td>
                        </tr>
                        <!-- Roster Items -->
                        <template x-for="s in students.data" :key="s.id">
                            <tr class="hover:bg-slate-50/50 transition" x-show="!loading">
                                <td class="px-4 py-3 font-extrabold text-slate-700" x-text="s.student_number"></td>
                                <td class="px-4 py-3 font-extrabold text-slate-900 uppercase" x-text="s.name"></td>
                                <td class="px-4 py-3 uppercase">
                                    <span class="inline-flex rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-black text-slate-600" x-text="s.grade"></span>
                                </td>
                                <td class="px-4 py-3 uppercase">
                                    <span :class="s.gender === 'Boy' ? 'bg-blue-50 text-blue-700 border border-blue-100' : (s.gender === 'Girl' ? 'bg-pink-50 text-pink-700 border border-pink-100' : 'bg-slate-50 text-slate-500')" class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-extrabold" x-text="s.gender"></span>
                                </td>
                                <td class="px-4 py-3 font-mono text-[10px] text-slate-600" x-text="s.email"></td>
                                <td class="px-4 py-3 text-center">
                                    <span 
                                        :class="s.teams_meetings_attended > 0 ? 'bg-teal-50 text-teal-700 border border-teal-100' : 'bg-slate-50 text-slate-500 border border-slate-100'"
                                        class="inline-flex rounded px-2.5 py-1 text-xs font-black" 
                                        x-text="`${s.teams_meetings_attended} Call${s.teams_meetings_attended !== 1 ? 's' : ''}`"
                                    ></span>
                                </td>
                                <td class="px-4 py-3 font-semibold">
                                    <template x-if="s.teams_app_used">
                                        <span class="text-slate-800" x-text="s.teams_last_activity"></span>
                                    </template>
                                    <template x-if="!s.teams_app_used">
                                        <span class="text-slate-400 font-normal">Never Joined</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="s.class_joined === 'Joined' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" x-text="s.class_joined"></span>
                                </td>
                                <td class="px-4 py-3 text-right no-print">
                                    <a :href="`/students/${s.id}`" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                                        Inspect Profile
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <!-- Empty State -->
                        <tr x-show="!loading && students.data.length === 0">
                            <td colspan="9" class="p-8 text-center text-slate-400 font-bold">
                                No student call attendance records match the selected filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Panel -->
            <div x-show="students.last_page > 1" class="no-print flex items-center justify-between border-t border-slate-100 pt-4">
                <button 
                    type="button" 
                    @click="fetchData(students.current_page - 1)" 
                    :disabled="students.current_page === 1"
                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 active:scale-98 transition disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
                >
                    Previous
                </button>
                <span class="text-xs font-bold text-slate-500" x-text="`Page ${students.current_page} of ${students.last_page}`"></span>
                <button 
                    type="button" 
                    @click="fetchData(students.current_page + 1)" 
                    :disabled="students.current_page === students.last_page"
                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 active:scale-98 transition disabled:opacity-40 disabled:pointer-events-none cursor-pointer"
                >
                    Next
                </button>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('attendanceDashboard', () => ({
                // Filters State
                selectedSchoolYear: '',
                selectedGrade: '',
                selectedLearningMode: '',
                selectedAdviser: '',
                selectedStatus: '',
                searchQuery: '',

                // Data Storage
                summary: {
                    total_registered: 0,
                    teams_app_used: 0,
                    teams_app_never_used: 0,
                },
                students: { data: [], current_page: 1, last_page: 1, total: 0 },
                lastSync: 'Never',
                generatedAt: '',

                // Loading states
                loading: false,
                syncing: false,
                errorMessage: '',
                successMessage: '',
                perPage: 15,
                currentPage: 1,

                avgMeetings() {
                    if (!this.students.data || this.students.data.length === 0) return 0;
                    let totalMeetings = 0;
                    let activeStudents = 0;
                    
                    // Sum up all meetings from current dataset
                    this.students.data.forEach(s => {
                        let meetings = parseInt(s.teams_meetings_attended) || 0;
                        if (meetings > 0) {
                            totalMeetings += meetings;
                            activeStudents++;
                        }
                    });
                    
                    if (activeStudents === 0) return 0;
                    return (totalMeetings / activeStudents).toFixed(1);
                },

                init() {
                    this.fetchData();
                },

                async fetchData(page = 1) {
                    this.loading = true;
                    this.errorMessage = '';
                    this.currentPage = page;

                    let params = new URLSearchParams({
                        page: this.currentPage,
                        per_page: this.perPage,
                        search: this.searchQuery,
                        school_year: this.selectedSchoolYear,
                        grade_level: this.selectedGrade,
                        learning_mode: this.selectedLearningMode,
                        adviser: this.selectedAdviser,
                        status: this.selectedStatus
                    });

                    try {
                        const res = await fetch(`/students/reports/data?${params.toString()}`);
                        const data = await res.json();

                        if (data.success) {
                            this.summary = data.summary;
                            this.students = data.students;
                            this.lastSync = data.last_sync;
                            this.generatedAt = data.generated_at;
                        } else {
                            this.errorMessage = data.message || 'Failed to retrieve attendance data.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Connection error: ' + e.message;
                    } finally {
                        this.loading = false;
                        if (window.lucide) window.lucide.createIcons();
                    }
                },

                async forceSync() {
                    this.syncing = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const res = await fetch('/students/reports/sync', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await res.json();

                        if (data.success) {
                            this.successMessage = 'Microsoft Graph sync complete! Telemetry updated.';
                            this.fetchData(1);
                        } else {
                            this.errorMessage = data.message || 'Sync failed.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Sync error: ' + e.message;
                    } finally {
                        this.syncing = false;
                    }
                },

                exportCSV() {
                    let csv = [];
                    let headers = ['Student ID', 'Full Name', 'Grade Level', 'Email', 'Gender', 'Meetings Attended', 'Last Call Activity', 'Class Joined Status'];
                    csv.push(headers.join(','));

                    this.students.data.forEach(s => {
                        let row = [
                            s.student_number,
                            `"${s.name}"`,
                            s.grade,
                            s.gender,
                            s.email,
                            s.teams_meetings_attended,
                            s.teams_app_used ? s.teams_last_activity : 'Never Joined',
                            s.class_joined
                        ];
                        csv.push(row.join(','));
                    });

                    let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
                    let encodedUri = encodeURI(csvContent);
                    let link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", `m365_class_call_attendance_${new Date().toISOString().slice(0,10)}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }));
        });
    </script>
</x-admin-layout>
