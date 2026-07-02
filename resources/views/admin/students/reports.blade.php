@inject('gdriveService', 'App\Services\GoogleDriveService')

<x-admin-layout
    title="Microsoft 365 & Teams Analytics Dashboard"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'M365 & Teams Analytics', 'href' => null],
    ]"
>
    <!-- Include Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS for Printable Reports & Clean Layout -->
    <style>
        @media print {
            body aside#default-sidebar,
            body aside.admin-sidebar,
            body nav,
            body footer,
            body .no-print,
            body #topLoadingBar,
            body #toastContainer,
            body .breadcrumbs {
                display: none !important;
                visibility: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
                pointer-events: none !important;
            }
            body .admin-shell, 
            body .admin-content, 
            body main, 
            body .mx-auto, 
            body #adminMainContent {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                background: white !important;
                margin-left: 0 !important;
                padding-left: 0 !important;
            }
            body, html {
                background: white !important;
                color: black !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            table {
                font-size: 10px !important;
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                padding: 4px 6px !important;
                border: 1px solid #cbd5e1 !important;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            tr:nth-child(even) {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            img {
                max-height: 80px !important;
                object-contain: contain !important;
            }
        }
            .print-full-width {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .chart-container-print {
                page-break-inside: avoid;
                break-inside: avoid;
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
        .border-rose-200 { border-color: #fecdd3 !important; }
        .border-emerald-200 { border-color: #a7f3d0 !important; }
        .border-amber-100 { border-color: #fef3c7 !important; }
        .border-rose-100 { border-color: #ffe4e6 !important; }
        .border-pink-100 { border-color: #fce7f3 !important; }
        .bg-pink-50 { background-color: #fdf2f8 !important; }
        .bg-pink-50\/20 { background-color: rgba(253, 242, 248, 0.2) !important; }
        .text-pink-700 { color: #be185d !important; }
    </style>

    <div x-data="reportsDashboard" class="space-y-6 print-full-width">

        <!-- Top Status Banner -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 no-print">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i data-lucide="line-chart" class="h-6 w-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">M365 & Teams Analytics</h1>
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

        <!-- Dashboard Filter Panel -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4 no-print">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i>
                    Dashboard Filters
                </h2>
                <button 
                    type="button"
                    @click="
                        selectedSchoolYear = '';
                        selectedGrade = '';
                        selectedLearningMode = '';
                        selectedAdviser = '';
                        selectedStatus = '';
                        selectedGender = '';
                        searchQuery = '';
                        fetchData(1);
                    "
                    class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 transition"
                >
                    Reset Filters
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
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

                <!-- Adviser -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Advisor</label>
                    <select x-model="selectedAdviser" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Advisors</option>
                        @foreach($advisors as $adv)
                            <option value="{{ $adv['email'] }}">{{ $adv['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">M365 / Sync Status</label>
                    <select x-model="selectedStatus" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Accounts</option>
                        <option value="has_account">Has Microsoft Account</option>
                        <option value="no_account">No Microsoft Account</option>
                        <option value="active_account">Account Enabled (Azure)</option>
                        <option value="inactive_account">Account Disabled (Azure)</option>
                        <option value="logged_in">Logged In</option>
                        <option value="never_signed_in">Never Signed In</option>
                        <option value="joined_teams">Joined Teams</option>
                        <option value="not_joined_teams">Not Yet Joined Teams</option>
                        <option value="licensed">Microsoft License Assigned</option>
                        <option value="unlicensed">No Microsoft License</option>
                        <option value="assigned_class">Assigned Class</option>
                        <option value="no_class">Without Assigned Class</option>
                        <option value="joined_class">Joined Class Section</option>
                        <option value="not_joined_class">Not Yet Joined Class</option>
                        <option value="temp_password">Initial Temporary Password</option>
                        <option value="password_changed">Password Changed</option>
                    </select>
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Gender</label>
                    <select x-model="selectedGender" @change="fetchData(1)" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition">
                        <option value="">All Genders</option>
                        <option value="male">Boys</option>
                        <option value="female">Girls</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Tabs Navigation -->
        <div class="border-b border-slate-200 no-print">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button 
                    @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="w-full sm:w-auto text-center border-b-2 py-4 px-1 text-sm font-extrabold transition cursor-pointer"
                >
                    Overview Dashboard
                </button>
                <button 
                    @click="activeTab = 'm365'"
                    :class="activeTab === 'm365' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="w-full sm:w-auto text-center border-b-2 py-4 px-1 text-sm font-extrabold transition cursor-pointer"
                >
                    Microsoft 365 Analytics
                </button>
                <button 
                    @click="activeTab = 'teams'"
                    :class="activeTab === 'teams' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="w-full sm:w-auto text-center border-b-2 py-4 px-1 text-sm font-extrabold transition cursor-pointer"
                >
                    Teams & Engagement
                </button>
                <button 
                    @click="activeTab = 'roster'; if (!rosterLoaded) loadRosterData()"
                    :class="activeTab === 'roster' ? 'border-teal-600 text-teal-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="w-full sm:w-auto text-center border-b-2 py-4 px-1 text-sm font-extrabold transition cursor-pointer flex items-center gap-1.5"
                >
                    <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
                    Class Roster Report
                </button>
                <button 
                    @click="activeTab = 'payments'; if (!paymentsLoaded) loadPaymentsData()"
                    :class="activeTab === 'payments' ? 'border-amber-600 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    class="w-full sm:w-auto text-center border-b-2 py-4 px-1 text-sm font-extrabold transition cursor-pointer flex items-center gap-1.5"
                >
                    <i data-lucide="banknote" class="h-3.5 w-3.5"></i>
                    Enrollment Payments
                </button>
            </nav>
        </div>

        <!-- Tab Panel: Overview Dashboard -->
        <div x-show="activeTab === 'overview'" x-transition class="space-y-6">            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-11 gap-4">
                <!-- Card 1 -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = ''; selectedGender = ''; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-slate-400 hover:text-slate-600 hover:bg-slate-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total Enrolled</p>
                    <div class="text-2xl font-black text-slate-900 mt-2" x-text="summary.total_registered">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Students in records</p>
                </div>
                <!-- Card 2: ODL 1st Shift -->
                <div class="rounded-2xl border border-amber-100 bg-amber-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = 'odl_1st'; selectedGender = ''; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-amber-500 hover:text-amber-700 hover:bg-amber-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-amber-600">ODL 1st Shift</p>
                    <div class="text-2xl font-black text-amber-700 mt-2" x-text="summary.odl_1st_count">0</div>
                    <p class="text-[9px] font-semibold text-amber-600 mt-1">1st shift online</p>
                </div>
                <!-- Card 3: ODL 2nd Shift -->
                <div class="rounded-2xl border border-rose-100 bg-rose-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = 'odl_2nd'; selectedGender = ''; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-rose-500 hover:text-rose-700 hover:bg-rose-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-rose-600">ODL 2nd Shift</p>
                    <div class="text-2xl font-black text-rose-700 mt-2" x-text="summary.odl_2nd_count">0</div>
                    <p class="text-[9px] font-semibold text-rose-600 mt-1">2nd shift online</p>
                </div>
                <!-- Card 4: F2F Mode -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = 'f2f'; selectedGender = ''; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-slate-500 hover:text-slate-700 hover:bg-slate-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-550">Face-to-Face</p>
                    <div class="text-2xl font-black text-slate-700 mt-2" x-text="summary.f2f_count">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">In-person mode</p>
                </div>
                <!-- Card 4.1: Boys -->
                <div class="rounded-2xl border border-blue-100 bg-blue-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedGender = 'male'; selectedLearningMode = ''; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-blue-500 hover:text-blue-700 hover:bg-blue-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Boys List"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-blue-600">Boys Count</p>
                    <div class="text-2xl font-black text-blue-700 mt-2" x-text="summary.boy_count">0</div>
                    <p class="text-[9px] font-semibold text-blue-600 mt-1">Male students</p>
                </div>
                <!-- Card 4.2: Girls -->
                <div class="rounded-2xl border border-pink-100 bg-pink-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedGender = 'female'; selectedLearningMode = ''; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-pink-500 hover:text-pink-700 hover:bg-pink-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Girls List"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-pink-700">Girls Count</p>
                    <div class="text-2xl font-black text-pink-700 mt-2" x-text="summary.girl_count">0</div>
                    <p class="text-[9px] font-semibold text-pink-600 mt-1">Female students</p>
                </div>
                <!-- Card 5 -->
                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = ''; selectedStatus = 'has_account'; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-indigo-600">MS Accounts</p>
                    <div class="text-2xl font-black text-indigo-700 mt-2" x-text="summary.accounts_generated">0</div>
                    <p class="text-[9px] font-semibold text-indigo-600 mt-1">Accounts provisioned</p>
                </div>
                <!-- Card 6 -->
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = ''; selectedStatus = 'active_account'; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-emerald-500 hover:text-emerald-700 hover:bg-emerald-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-600">Active Accounts</p>
                    <div class="text-2xl font-black text-emerald-700 mt-2" x-text="summary.active_accounts">0</div>
                    <p class="text-[9px] font-semibold text-emerald-600 mt-1">Enabled in Azure AD</p>
                </div>
                <!-- Card 7 -->
                <div class="rounded-2xl border border-blue-100 bg-blue-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = ''; selectedStatus = 'logged_in'; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-blue-500 hover:text-blue-700 hover:bg-blue-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-blue-600">Portal Logins</p>
                    <div class="text-2xl font-black text-blue-700 mt-2" x-text="summary.logged_in">0</div>
                    <p class="text-[9px] font-semibold text-blue-600 mt-1">Students logged in</p>
                </div>
                <!-- Card 8 -->
                <div class="rounded-2xl border border-purple-100 bg-purple-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = ''; selectedStatus = 'joined_teams'; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-purple-500 hover:text-purple-700 hover:bg-purple-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-purple-600">Joined Teams</p>
                    <div class="text-2xl font-black text-purple-700 mt-2" x-text="summary.joined_teams">0</div>
                    <p class="text-[9px] font-semibold text-purple-600 mt-1">Enrolled in Teams</p>
                </div>
                <!-- Card 9 -->
                <div class="rounded-2xl border border-teal-100 bg-teal-50/20 p-5 shadow-xs hover:shadow-md transition duration-300 relative group">
                    <button 
                        @click="selectedLearningMode = ''; selectedStatus = 'joined_class'; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                        class="absolute top-3 right-3 text-teal-500 hover:text-teal-700 hover:bg-teal-100/50 p-1.5 rounded-lg transition cursor-pointer"
                        title="View Details in Directory"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </button>
                    <p class="text-[9px] font-black uppercase tracking-wider text-teal-600">Joined Class</p>
                    <div class="text-2xl font-black text-teal-700 mt-2" x-text="summary.joined_class">0</div>
                    <p class="text-[9px] font-semibold text-teal-700 mt-1">Confirmed class rosters</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Bar Chart: Grade Distribution -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[320px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Students per Grade Level</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartGradeDistribution"></canvas>
                    </div>
                </div>

                <!-- Pie Chart: Account Provisioning Status -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[320px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Microsoft Account Status</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartAccountStatus"></canvas>
                    </div>
                </div>

                <!-- Doughnut Chart: Class Join Status -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[320px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Class Join Status</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartClassJoinStatus"></canvas>
                    </div>
                </div>
            </div>

            <!-- Grade Level Breakdown Table -->
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden chart-container-print">
                <div class="p-5 border-b border-slate-100">
                    <h3 class="text-sm font-extrabold text-slate-900">Analytics Breakdown per Grade Level</h3>
                    <p class="text-xs text-slate-500 mt-1">Overview of student accounts, logins, and Teams activation by grade.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs align-middle">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th class="px-5 py-3 font-black">Grade Level</th>
                                <th class="px-5 py-3 text-center font-black">Total Students</th>
                                <th class="px-5 py-3 text-center font-black">ODL 1st Shift</th>
                                <th class="px-5 py-3 text-center font-black">ODL 2nd Shift</th>
                                <th class="px-5 py-3 text-center font-black">F2F Mode</th>
                                <th class="px-5 py-3 text-center font-black">MS Accounts</th>
                                <th class="px-5 py-3 text-center font-black">Portal Logins</th>
                                <th class="px-5 py-3 text-center font-black">Joined Teams</th>
                                <th class="px-5 py-3 text-center font-black">Joined Class</th>
                                <th class="px-5 py-3 text-center font-black">Password Changed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="g in gradeBreakdown" :key="g.grade_level">
                                <tr class="hover:bg-slate-50/30 transition">
                                    <td class="px-5 py-3">
                                        <span class="font-extrabold text-slate-800 uppercase" x-text="g.grade_level"></span>
                                    </td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.total"></td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700">
                                        <span x-text="g.odl_1st"></span>
                                        <button 
                                            @click="selectedGrade = g.grade_level; selectedLearningMode = 'odl_1st'; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                                            class="ml-1 text-amber-500 hover:text-amber-700 cursor-pointer inline-flex align-middle opacity-60 hover:opacity-100 transition"
                                            title="View Details"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700">
                                        <span x-text="g.odl_2nd"></span>
                                        <button 
                                            @click="selectedGrade = g.grade_level; selectedLearningMode = 'odl_2nd'; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                                            class="ml-1 text-rose-500 hover:text-rose-700 cursor-pointer inline-flex align-middle opacity-60 hover:opacity-100 transition"
                                            title="View Details"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700">
                                        <span x-text="g.f2f"></span>
                                        <button 
                                            @click="selectedGrade = g.grade_level; selectedLearningMode = 'f2f'; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                                            class="ml-1 text-slate-500 hover:text-slate-700 cursor-pointer inline-flex align-middle opacity-60 hover:opacity-100 transition"
                                            title="View Details"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.accounts"></td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.logged_in"></td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.joined_teams"></td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.joined_class"></td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.password_changed"></td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 font-black text-slate-900 border-t-2 border-slate-200">
                                <td class="px-5 py-3.5 uppercase text-slate-800">Total</td>
                                <td class="px-5 py-3.5 text-center text-slate-800" x-text="sumTotal()"></td>
                                <td class="px-5 py-3.5 text-center text-amber-700">
                                    <span x-text="sumOdl1()"></span>
                                    <button 
                                        @click="selectedGrade = ''; selectedLearningMode = 'odl_1st'; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                                        class="ml-1.5 text-amber-600 hover:text-amber-800 cursor-pointer inline-flex align-middle opacity-80 hover:opacity-100 transition"
                                        title="View ODL 1st Shift List"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-5 py-3.5 text-center text-rose-700">
                                    <span x-text="sumOdl2()"></span>
                                    <button 
                                        @click="selectedGrade = ''; selectedLearningMode = 'odl_2nd'; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                                        class="ml-1.5 text-rose-600 hover:text-rose-800 cursor-pointer inline-flex align-middle opacity-80 hover:opacity-100 transition"
                                        title="View ODL 2nd Shift List"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-5 py-3.5 text-center text-slate-700">
                                    <span x-text="sumF2f()"></span>
                                    <button 
                                        @click="selectedGrade = ''; selectedLearningMode = 'f2f'; selectedStatus = ''; fetchData(1); document.getElementById('student-directory').scrollIntoView({ behavior: 'smooth' })"
                                        class="ml-1.5 text-slate-600 hover:text-slate-800 cursor-pointer inline-flex align-middle opacity-80 hover:opacity-100 transition"
                                        title="View Face-to-Face List"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </button>
                                </td>
                                <td class="px-5 py-3.5 text-center text-indigo-700" x-text="sumAccounts()"></td>
                                <td class="px-5 py-3.5 text-center text-blue-700" x-text="sumLogins()"></td>
                                <td class="px-5 py-3.5 text-center text-purple-700" x-text="sumTeams()"></td>
                                <td class="px-5 py-3.5 text-center text-teal-700" x-text="sumClass()"></td>
                                <td class="px-5 py-3.5 text-center text-emerald-700" x-text="sumPassword()"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Panel: Microsoft 365 Analytics -->
        <div x-show="activeTab === 'm365'" x-transition class="space-y-6">
            <!-- M365 Cards Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- MS License Assigned -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">License Assigned</p>
                    <div class="text-2xl font-black text-slate-900 mt-2" x-text="summary.licensed_count">0</div>
                    <p class="text-[9px] font-semibold text-blue-600 mt-1">M365 Student SKU</p>
                </div>
                <!-- No Microsoft License -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">No License</p>
                    <div class="text-2xl font-black text-rose-600 mt-2" x-text="summary.unlicensed_count">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Needs license assignment</p>
                </div>
                <!-- Inactive Microsoft Accounts -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Disabled Accounts</p>
                    <div class="text-2xl font-black text-rose-600 mt-2" x-text="summary.inactive_accounts">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Disabled in Azure AD</p>
                </div>
                <!-- Never Signed In -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Never Signed In</p>
                    <div class="text-2xl font-black text-amber-600 mt-2" x-text="summary.never_signed_in">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Inactive credentials</p>
                </div>
                <!-- Temp Password Not Changed -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Temp Passwords</p>
                    <div class="text-2xl font-black text-amber-600 mt-2" x-text="summary.temp_password">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Initial password active</p>
                </div>
                <!-- Password Changed -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Passwords Changed</p>
                    <div class="text-2xl font-black text-emerald-600 mt-2" x-text="summary.password_changed">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Credentials updated</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Pie Chart: M365 Login Status -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[280px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">M365 Login Status</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartLoginStatus"></canvas>
                    </div>
                </div>

                <!-- Pie Chart: Active vs Inactive Accounts -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[280px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Active vs Inactive (Azure)</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartActiveInactive"></canvas>
                    </div>
                </div>

                <!-- Pie Chart: License Status -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[280px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">License Assignment</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartLicenseStatus"></canvas>
                    </div>
                </div>

                <!-- Pie Chart: Password Change Status -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[280px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Password Status</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartPasswordChangeStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Panel: Teams & Engagement -->
        <div x-show="activeTab === 'teams'" x-transition class="space-y-6">
            <!-- Teams Cards Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Students Joined Microsoft Teams -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Joined Teams</p>
                    <div class="text-2xl font-black text-slate-900 mt-2" x-text="summary.joined_teams">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Sync confirmed</p>
                </div>
                <!-- Students Not Yet Joined Microsoft Teams -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Not Joined Teams</p>
                    <div class="text-2xl font-black text-rose-600 mt-2" x-text="summary.not_joined_teams">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Needs Teams sync</p>
                </div>
                <!-- Students with Assigned Class -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Assigned Class</p>
                    <div class="text-2xl font-black text-slate-900 mt-2" x-text="summary.assigned_class">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Local section assigned</p>
                </div>
                <!-- Students Without Assigned Class -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Without Class</p>
                    <div class="text-2xl font-black text-rose-600 mt-2" x-text="summary.without_class">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Unallocated roster slots</p>
                </div>
                <!-- Students with Joined Class -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Joined Class</p>
                    <div class="text-2xl font-black text-emerald-600 mt-2" x-text="summary.joined_class">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Active class rosters</p>
                </div>
                <!-- Students Not Yet Joined Class -->
                <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Not Joined Class</p>
                    <div class="text-2xl font-black text-rose-600 mt-2" x-text="summary.not_joined_class">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">Pending channel invitations</p>
                </div>
                <!-- Teams App Used -->
                <div class="rounded-2xl border border-teal-100 bg-teal-50/30 p-5 shadow-xs col-span-2 lg:col-span-1">
                    <p class="text-[9px] font-black uppercase tracking-wider text-teal-600">Used Teams App</p>
                    <div class="text-2xl font-black text-teal-700 mt-2" x-text="summary.teams_app_used ?? '—'">0</div>
                    <p class="text-[9px] font-semibold text-teal-500 mt-1">Active in last 30 days</p>
                </div>
                <!-- Teams App Never Used -->
                <div class="rounded-2xl border border-rose-100 bg-rose-50/20 p-5 shadow-xs col-span-2 lg:col-span-1">
                    <p class="text-[9px] font-black uppercase tracking-wider text-rose-500">Never Used App</p>
                    <div class="text-2xl font-black text-rose-600 mt-2" x-text="summary.teams_app_never_used ?? '—'">0</div>
                    <p class="text-[9px] font-semibold text-slate-500 mt-1">No Teams activity recorded</p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Donut Chart: Microsoft Teams Adoption -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[280px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Microsoft Teams Adoption</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartTeamsAdoption"></canvas>
                    </div>
                </div>

                <!-- Donut Chart: Class Join Status -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col h-[280px] chart-container-print">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-4">Class Join Status</h3>
                    <div class="flex-1 relative">
                        <canvas id="chartClassJoinStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ Tab Panel: Class Roster Report ═══ -->
        <div x-show="activeTab === 'roster'" x-transition class="space-y-6">

            <!-- Overall KPIs -->
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-2xl border border-teal-100 bg-teal-50/30 p-5 shadow-xs text-center">
                    <p class="text-[9px] font-black uppercase tracking-wider text-teal-600">Total Assigned</p>
                    <div class="text-3xl font-black text-teal-700 mt-2" x-text="rosterOverall.total ?? '—'"></div>
                    <p class="text-[9px] font-semibold text-teal-500 mt-1">Students with section</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/30 p-5 shadow-xs text-center">
                    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-600">Joined Class</p>
                    <div class="text-3xl font-black text-emerald-700 mt-2" x-text="rosterOverall.joined ?? '—'"></div>
                    <div class="text-[9px] font-bold text-emerald-600 mt-1" x-text="(rosterOverall.pct ?? 0) + '% joined'"></div>
                </div>
                <div class="rounded-2xl border border-rose-100 bg-rose-50/30 p-5 shadow-xs text-center">
                    <p class="text-[9px] font-black uppercase tracking-wider text-rose-600">Not Joined</p>
                    <div class="text-3xl font-black text-rose-700 mt-2" x-text="rosterOverall.not_joined ?? '—'"></div>
                    <div class="text-[9px] font-bold text-rose-500 mt-1" x-text="(100 - (rosterOverall.pct ?? 0)) + '% pending'"></div>
                </div>
            </div>

            <!-- Loading spinner -->
            <div x-show="rosterLoading" class="flex items-center justify-center py-16 gap-3">
                <svg class="animate-spin h-6 w-6 text-teal-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-sm font-bold text-slate-500">Loading class roster data…</span>
            </div>

            <!-- Grade Summary Table -->
            <div x-show="!rosterLoading && rosterGradeSummary.length > 0"
                 class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Grade-Level Roster Summary</h3>
                        <p class="text-xs text-slate-500 mt-0.5">How many students per grade have joined their class section in MS Teams.</p>
                    </div>
                    <button type="button" @click="exportRosterCsv()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        <i data-lucide="download" class="h-3.5 w-3.5 text-slate-500"></i> Export CSV
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th class="px-5 py-3">Grade Level</th>
                                <th class="px-5 py-3 text-center">Total</th>
                                <th class="px-5 py-3 text-center">Joined</th>
                                <th class="px-5 py-3 text-center">Not Joined</th>
                                <th class="px-5 py-3">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="g in rosterGradeSummary" :key="g.grade_level">
                                <tr class="hover:bg-slate-50/40 transition">
                                    <td class="px-5 py-3 font-extrabold text-slate-800 uppercase" x-text="g.grade_level"></td>
                                    <td class="px-5 py-3 text-center font-bold text-slate-700" x-text="g.total"></td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100" x-text="g.joined"></span>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold"
                                              :class="g.not_joined > 0 ? 'bg-rose-50 text-rose-700 border border-rose-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100'"
                                              x-text="g.not_joined"></span>
                                    </td>
                                    <td class="px-5 py-3 w-48">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-100 rounded-full h-2">
                                                <div class="h-2 rounded-full transition-all duration-500"
                                                     :class="g.pct === 100 ? 'bg-emerald-500' : g.pct >= 70 ? 'bg-teal-500' : g.pct >= 40 ? 'bg-amber-500' : 'bg-rose-500'"
                                                     :style="'width: ' + g.pct + '%'"></div>
                                            </div>
                                            <span class="text-[9px] font-black text-slate-500 w-8 text-right" x-text="g.pct + '%'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Per-Section Accordion -->
            <div x-show="!rosterLoading" class="space-y-3">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-500 flex items-center gap-2">
                    <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                    Per-Section Breakdown
                    <input type="text" x-model="rosterSearch" placeholder="Search section…"
                        class="ml-auto border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-medium outline-none focus:ring-2 focus:ring-teal-400/20 focus:border-teal-400 transition bg-white w-48">
                </h3>

                <template x-for="sec in filteredRosterSections" :key="sec.id">
                    <div class="rounded-2xl border bg-white shadow-xs overflow-hidden"
                         :class="sec.pct === 100 ? 'border-emerald-100' : sec.pct >= 70 ? 'border-teal-100' : sec.not_joined > 0 ? 'border-rose-100' : 'border-slate-100'">

                        <!-- Section Header (clickable) -->
                        <button type="button" @click="sec._open = !sec._open"
                            class="w-full flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-slate-50/60 transition text-left">

                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Avatar badge -->
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-white text-[10px] font-black"
                                     :class="sec.gender === 'Girls'
                                        ? 'bg-gradient-to-br from-rose-400 to-pink-600'
                                        : 'bg-gradient-to-br from-indigo-500 to-blue-700'"
                                     x-text="sec.gender === 'Girls' ? '♀' : '♂'"></div>

                                <div class="min-w-0">
                                    <div class="font-extrabold text-slate-900 text-sm uppercase truncate">
                                        <span x-text="sec.grade_level"></span>
                                        <template x-if="sec.name"><span x-text="' — ' + sec.name"></span></template>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-md"
                                              :class="sec.mode === 'ODL' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700'"
                                              x-text="sec.mode + (sec.shift ? ' · ' + sec.shift : '')"></span>
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-md"
                                              :class="sec.gender === 'Girls' ? 'bg-rose-50 text-rose-700' : 'bg-indigo-50 text-indigo-700'"
                                              x-text="sec.gender"></span>
                                        <template x-if="!sec.has_team">
                                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700">No MS Team</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: stats + progress -->
                            <div class="flex items-center gap-4 shrink-0">
                                <div class="text-right hidden sm:block">
                                    <div class="text-[10px] font-black text-slate-500"><span x-text="sec.joined"></span>/<span x-text="sec.total"></span> joined</div>
                                    <div class="text-[9px] text-slate-400" x-text="sec.not_joined + ' not joined'"></div>
                                </div>
                                <!-- Mini progress ring -->
                                <div class="relative w-10 h-10 shrink-0">
                                    <svg class="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"></circle>
                                        <circle cx="18" cy="18" r="15.9" fill="none" stroke-width="3"
                                                :stroke="sec.pct === 100 ? '#10b981' : sec.pct >= 70 ? '#14b8a6' : sec.pct >= 40 ? '#f59e0b' : '#f43f5e'"
                                                :stroke-dasharray="sec.pct + ' 100'"
                                                stroke-linecap="round"></circle>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center text-[8px] font-black text-slate-700"
                                         x-text="sec.pct + '%'"></div>
                                </div>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                   :class="sec._open ? 'rotate-180' : ''"></i>
                            </div>
                        </button>

                        <!-- Expanded: Two-Column Student Lists -->
                        <div x-show="sec._open" x-transition class="border-t border-slate-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">

                                <!-- Joined Students -->
                                <div class="p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Joined Class</span>
                                        <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full" x-text="sec.joined_students.length"></span>
                                    </div>
                                    <div class="space-y-1 max-h-52 overflow-y-auto">
                                        <template x-if="sec.joined_students.length === 0">
                                            <p class="text-[10px] text-slate-400 font-bold text-center py-4">No joined students yet.</p>
                                        </template>
                                        <template x-for="(st, i) in sec.joined_students" :key="st.student_number">
                                            <div class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-emerald-50/40 transition">
                                                <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-[8px] font-black flex items-center justify-center shrink-0" x-text="i + 1"></div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-800 text-[10px] truncate uppercase" x-text="st.name"></div>
                                                    <div class="text-[9px] font-mono text-slate-400 truncate" x-text="st.student_number"></div>
                                                </div>
                                                <span class="shrink-0 text-[8px] font-black px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">✓ Enrolled</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Not Joined Students -->
                                <div class="p-4">
                                    <div class="flex items-center gap-2 mb-3">
                                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-700">Not Joined</span>
                                        <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded-full" x-text="sec.not_joined_students.length"></span>
                                    </div>
                                    <div class="space-y-1 max-h-52 overflow-y-auto">
                                        <template x-if="sec.not_joined_students.length === 0">
                                            <p class="text-[10px] text-emerald-600 font-bold text-center py-4">🎉 All students have joined!</p>
                                        </template>
                                        <template x-for="(st, i) in sec.not_joined_students" :key="st.student_number">
                                            <div class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-rose-50/40 transition">
                                                <div class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 text-[8px] font-black flex items-center justify-center shrink-0" x-text="i + 1"></div>
                                                <div class="min-w-0">
                                                    <div class="font-bold text-slate-800 text-[10px] truncate uppercase" x-text="st.name"></div>
                                                    <div class="text-[9px] font-mono text-slate-400 truncate" x-text="st.student_number"></div>
                                                </div>
                                                <span class="shrink-0 text-[8px] font-black px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500 capitalize" x-text="st.ms_status || 'pending'"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </template>

                <template x-if="filteredRosterSections.length === 0 && !rosterLoading">
                    <div class="text-center py-10 text-slate-400 font-bold text-xs">No sections match your search.</div>
                </template>
            </div>

        </div><!-- /roster tab -->

        <!-- ═══ Tab Panel: Enrollment Payments Report ═══ -->
        <div x-show="activeTab === 'payments'" x-transition class="space-y-6">

            <!-- Overall KPIs -->
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-2xl border border-amber-100 bg-amber-50/30 p-5 shadow-xs text-center">
                    <p class="text-[9px] font-black uppercase tracking-wider text-amber-600">With Payment Proof</p>
                    <div class="text-3xl font-black text-amber-700 mt-2" x-text="paymentsData.with_payment ? paymentsData.with_payment.length : '0'"></div>
                    <p class="text-[9px] font-semibold text-amber-500 mt-1">Applicants who uploaded receipt</p>
                </div>
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/30 p-5 shadow-xs text-center">
                    <p class="text-[9px] font-black uppercase tracking-wider text-emerald-600">Approved (No Payment)</p>
                    <div class="text-3xl font-black text-emerald-700 mt-2" x-text="paymentsData.approved_no_payment ? paymentsData.approved_no_payment.length : '0'"></div>
                    <p class="text-[9px] font-semibold text-emerald-500 mt-1">Approved but no receipt yet</p>
                </div>
            </div>

            <!-- Print & Sync Actions -->
            <div class="flex flex-wrap justify-end items-center gap-2 no-print">
                @if ($gdriveService->isConfigured())
                    <button 
                        type="button" 
                        @click="syncGoogleDrive()"
                        :disabled="gdriveSyncing"
                        class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 px-3.5 text-xs font-bold text-white shadow-sm transition cursor-pointer"
                    >
                        <template x-if="!gdriveSyncing">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="cloud-lightning" class="h-3.5 w-3.5"></i>
                                Sync to Google Drive
                            </span>
                        </template>
                        <template x-if="gdriveSyncing">
                            <span class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Syncing enrollees...
                            </span>
                        </template>
                    </button>
                @endif
                <a 
                    href="{{ route('admin.google-drive.auth') }}" 
                    class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                    title="Reconnect Google Drive OAuth Refresh Token if uploads fail"
                >
                    <i data-lucide="key-round" class="h-3.5 w-3.5 text-slate-500"></i>
                    <span>Connect Google Drive</span>
                </a>
                <button type="button" @click="printReport()" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                    <i data-lucide="printer" class="h-3.5 w-3.5 text-slate-500"></i>
                    <span>Print / PDF Report</span>
                </button>
            </div>

            <!-- Loading spinner -->
            <div x-show="paymentsLoading" class="flex items-center justify-center py-16 gap-3">
                <svg class="animate-spin h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-bold text-slate-500">Loading            <!-- Payments List Tables -->
            <div x-show="!paymentsLoading" class="space-y-6">
                <!-- Table 1: With Payment Proof -->
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Applicants with Uploaded Payment Proof</h3>
                            <p class="text-xs text-slate-500 mt-1">List of all students/families that have uploaded enrollment payment proof.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs align-middle">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th class="px-5 py-3 font-black">Enrolled Student(s) / Children</th>
                                    <th class="px-5 py-3 font-black">Parent/Contact</th>
                                    <th class="px-5 py-3 font-black">Payment Proof</th>
                                    <th class="px-5 py-3 font-black">Receipt Image</th>
                                    <th class="px-5 py-3 font-black no-print">Receipt File</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="item in paymentsData.with_payment" :key="item.family_id ? 'fam_' + item.family_id : 'solo_' + item.students[0].id">
                                    <tr class="hover:bg-slate-50/30 transition">
                                        <td class="px-5 py-3">
                                            <div class="space-y-1">
                                                <template x-for="std in item.students">
                                                    <div class="flex flex-wrap items-center gap-1.5 py-1 border-b border-slate-100/50 last:border-b-0">
                                                        <span class="inline-flex items-center justify-center rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-[9px] font-black text-slate-650 dark:text-slate-350" x-text="'ID: ' + std.id"></span>
                                                        <span class="font-extrabold text-slate-850 dark:text-white" x-text="std.name"></span>
                                                        <span class="text-[10px] font-bold text-slate-500" x-text="'(' + std.grade + ')'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-slate-600 space-y-0.5">
                                            <div class="font-bold text-slate-700" x-text="item.parent"></div>
                                            <div class="text-xxs text-slate-400" x-text="item.email"></div>
                                            <div class="text-xxs text-slate-400" x-text="item.mobile"></div>
                                        </td>
                                        <td class="px-5 py-3 text-slate-700 space-y-0.5">
                                            <div class="font-bold text-amber-600" x-text="'PHP ' + item.payment.amount"></div>
                                            <div class="text-xxs font-mono text-slate-500"><span x-text="item.payment.method"></span>: <span x-text="item.payment.ref_no"></span></div>
                                            <div class="mt-0.5"><span class="inline-flex items-center px-2 py-0.2 rounded-full text-[9px] font-extrabold uppercase tracking-wide bg-indigo-50 text-indigo-700" x-text="item.payment.status"></span></div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <template x-if="item.payment.receipt">
                                                <div>
                                                    <template x-if="item.payment.receipt.toLowerCase().endsWith('.pdf')">
                                                        <span class="text-xxs text-indigo-600 font-bold">PDF Document</span>
                                                    </template>
                                                    <template x-if="!item.payment.receipt.toLowerCase().endsWith('.pdf')">
                                                        <img :src="'/payments/receipt-file?path=' + encodeURIComponent(item.payment.receipt)" class="h-24 w-auto rounded border border-slate-200 object-contain shadow-xs bg-slate-50" style="max-width: 130px;" />
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!item.payment.receipt">
                                                <span class="text-slate-400 font-bold">N/A</span>
                                            </template>
                                        </td>
                                        <td class="px-5 py-3 no-print">
                                            <template x-if="item.payment.receipt">
                                                <a :href="'/payments/receipt-file?path=' + encodeURIComponent(item.payment.receipt)" target="_blank" class="inline-flex h-7 items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xxs font-bold text-indigo-600 hover:bg-slate-50 transition cursor-pointer">
                                                    <i data-lucide="eye" class="h-3 w-3"></i>
                                                    <span>View</span>
                                                </a>
                                            </template>
                                            <template x-if="!item.payment.receipt">
                                                <span class="text-slate-400 font-bold">N/A</span>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="paymentsData.with_payment && paymentsData.with_payment.length === 0">
                                    <td colspan="5" class="p-8 text-center text-slate-400 font-medium">
                                        No applicants found with uploaded payment proof.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Table 2: Approved (No Payment Proof) -->
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100">
                        <h3 class="text-sm font-extrabold text-slate-900">Approved Applicants (No Payment Proof)</h3>
                        <p class="text-xs text-slate-500 mt-1">List of all students that have been approved for enrollment but have not uploaded payment proof.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs align-middle">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th class="px-5 py-3 font-black">Enrolled Student(s) / Children</th>
                                    <th class="px-5 py-3 font-black">Parent/Contact</th>
                                    <th class="px-5 py-3 font-black">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="item in paymentsData.approved_no_payment" :key="item.family_id ? 'fam_' + item.family_id : 'solo_' + item.students[0].id">
                                    <tr class="hover:bg-slate-50/30 transition">
                                        <td class="px-5 py-3">
                                            <div class="space-y-1">
                                                <template x-for="std in item.students">
                                                    <div class="flex flex-wrap items-center gap-1.5 py-1 border-b border-slate-100/50 last:border-b-0">
                                                        <span class="inline-flex items-center justify-center rounded bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 text-[9px] font-black text-slate-650 dark:text-slate-350" x-text="'ID: ' + std.id"></span>
                                                        <span class="font-extrabold text-slate-850 dark:text-white" x-text="std.name"></span>
                                                        <span class="text-[10px] font-bold text-slate-500" x-text="'(' + std.grade + ')'"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-slate-600 space-y-0.5">
                                            <div class="font-bold text-slate-700" x-text="item.parent"></div>
                                            <div class="text-xxs text-slate-400" x-text="item.email"></div>
                                            <div class="text-xxs text-slate-400" x-text="item.mobile"></div>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xxs font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700" x-text="item.status_label"></span>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="paymentsData.approved_no_payment && paymentsData.approved_no_payment.length === 0">
                                    <td colspan="3" class="p-8 text-center text-slate-400 font-medium">
                                        No approved applicants found without payment proof.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /payments tab -->

        <!-- Student Details Paginated Directory (Always Visible below stats) -->
        <section id="student-directory" x-show="activeTab !== 'payments' && activeTab !== 'roster'" class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden chart-container-print">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Student Telemetry Directory</h2>
                    <p class="text-xs text-slate-500 mt-1">Detailed directory of student credentials, licensing, activity logs, and group states.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 no-print">
                    <!-- Export Utilities -->
                    <button type="button" @click="exportCsv()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        <i data-lucide="file-spreadsheet" class="h-3.5 w-3.5 text-slate-500"></i> Export CSV
                    </button>
                    <button type="button" @click="exportExcel()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        <i data-lucide="download" class="h-3.5 w-3.5 text-slate-500"></i> Export Excel
                    </button>
                    <button type="button" @click="printReport()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        <i data-lucide="printer" class="h-3.5 w-3.5 text-slate-500"></i> Print / PDF
                    </button>
                </div>
            </div>

            <!-- Directory Search Input -->
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 no-print flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative w-full sm:max-w-xs">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="search" class="h-3.5 w-3.5"></i>
                        </div>
                        <input 
                            type="search" 
                            x-model="searchQuery" 
                            @input.debounce.300ms="fetchData(1)"
                            placeholder="Search by name, ID, or email..."
                            class="w-full bg-white border border-slate-200 text-slate-800 text-xs rounded-xl pl-9 pr-4 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 outline-none font-bold transition"
                        >
                    </div>

                    <!-- Quick Gender Filters -->
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="selectedGender = 'male'; selectedLearningMode = ''; selectedStatus = ''; fetchData(1)"
                            :class="selectedGender === 'male' ? 'bg-blue-50 text-blue-700 border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-black transition cursor-pointer shadow-xs active:scale-95"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            BOY LIST
                        </button>
                        <button 
                            type="button" 
                            @click="selectedGender = 'female'; selectedLearningMode = ''; selectedStatus = ''; fetchData(1)"
                            :class="selectedGender === 'female' ? 'bg-pink-50 text-pink-700 border-pink-600' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-black transition cursor-pointer shadow-xs active:scale-95"
                            style="color: #be185d !important;"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            GIRL LIST
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 shrink-0">
                    <span x-text="students.total + ' matching records'"></span>
                </div>
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
                            <th class="px-4 py-3 font-black">License Status</th>
                            <th class="px-4 py-3 font-black">Account Enabled</th>
                            <th class="px-4 py-3 font-black">Last Activity</th>
                            <th class="px-4 py-3 font-black">Teams Joined</th>
                            <th class="px-4 py-3 font-black">Teams App</th>
                            <th class="px-4 py-3 font-black">Class Joined</th>
                            <th class="px-4 py-3 font-black">Password Status</th>
                            <th class="px-4 py-3 text-right font-black no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Loading State -->
                        <tr x-show="loading">
                            <td colspan="11" class="p-8 text-center text-slate-400 font-bold no-print">
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Retrieving telemetry records from database...</span>
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
                                <td class="px-4 py-3 font-mono text-[10px] text-slate-600">
                                    <span x-text="s.email"></span>
                                    <div class="text-slate-400 mt-0.5" x-show="s.password_changed !== 'Changed'">Temp PW: <span class="font-extrabold" x-text="s.temp_password"></span></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="s.license === 'Licensed' ? 'bg-blue-50 text-blue-700' : 'bg-rose-50 text-rose-700'" class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" x-text="s.license"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="s.account_enabled === 'Enabled' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" x-text="s.account_enabled"></span>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-500" x-text="s.last_sign_in"></td>
                                <td class="px-4 py-3">
                                    <span :class="s.teams_joined === 'Joined' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-500'" class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" x-text="s.teams_joined"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="s.teams_app_used">
                                        <div>
                                            <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold bg-teal-50 text-teal-700">✓ Active</span>
                                            <div class="text-[9px] text-slate-400 mt-0.5" x-text="s.teams_last_activity"></div>
                                        </div>
                                    </template>
                                    <template x-if="!s.teams_app_used">
                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 text-slate-500">No Activity</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="s.class_joined === 'Joined' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-bold uppercase" x-text="s.class_joined"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="s.password_changed === 'Changed' ? 'bg-emerald-50 text-emerald-700 font-extrabold' : 'bg-amber-50 text-amber-700 font-bold'" class="inline-flex rounded px-1.5 py-0.5 text-[9px] uppercase" x-text="s.password_changed"></span>
                                </td>
                                <td class="px-4 py-3 text-right no-print">
                                    <a :href="`/students/${s.id}`" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                                        Inspect
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <!-- Empty State -->
                        <tr x-show="!loading && students.data.length === 0">
                            <td colspan="11" class="p-8 text-center text-slate-400 font-medium">
                                No student telemetry records match the selected parameters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print" x-show="students.last_page > 1">
                <span class="text-xs font-bold text-slate-400" x-text="'Page ' + students.current_page + ' of ' + students.last_page"></span>
                <div class="flex items-center gap-1">
                    <button 
                        type="button" 
                        @click="fetchData(students.current_page - 1)"
                        :disabled="students.current_page === 1"
                        class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer disabled:opacity-50"
                    >
                        Previous
                    </button>
                    <button 
                        type="button" 
                        @click="fetchData(students.current_page + 1)"
                        :disabled="students.current_page === students.last_page"
                        class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer disabled:opacity-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reportsDashboard', () => ({
                // Navigation Tab
                activeTab: 'overview',

                // Filters State
                selectedSchoolYear: '',
                selectedGrade: '',
                selectedLearningMode: '',
                selectedAdviser: '',
                selectedStatus: '',
                selectedGender: '',
                searchQuery: '',

                // Data Storage
                summary: {
                    total_registered: 0,
                    accounts_generated: 0,
                    licensed_count: 0,
                    unlicensed_count: 0,
                    active_accounts: 0,
                    inactive_accounts: 0,
                    logged_in: 0,
                    never_signed_in: 0,
                    joined_teams: 0,
                    not_joined_teams: 0,
                    assigned_class: 0,
                    without_class: 0,
                    joined_class: 0,
                    not_joined_class: 0,
                    temp_password: 0,
                    password_changed: 0,
                    last_sign_in_activity: 'Loading...',
                    odl_1st_count: 0,
                    odl_2nd_count: 0,
                    f2f_count: 0,
                    boy_count: 0,
                    girl_count: 0,
                    teams_app_used: 0,
                    teams_app_never_used: 0,
                },
                gradeBreakdown: [],
                students: { data: [], current_page: 1, last_page: 1, total: 0 },
                lastSync: 'Never',
                generatedAt: '',

                // Loading states
                loading: false,
                syncing: false,
                errorMessage: '',
                successMessage: '',
                perPage: 10,
                currentPage: 1,
                charts: {},

                // Class Roster Report tab state
                rosterLoading: false,
                rosterLoaded: false,
                rosterSections: [],
                rosterGradeSummary: [],
                rosterOverall: { total: 0, joined: 0, not_joined: 0, pct: 0 },
                rosterSearch: '',

                // Enrollment Payments tab state
                paymentsLoading: false,
                gdriveSyncing: false,
                paymentsLoaded: false,
                paymentsData: { with_payment: [], approved_no_payment: [] },

                get filteredRosterSections() {
                    if (!this.rosterSearch.trim()) return this.rosterSections;
                    const q = this.rosterSearch.toLowerCase();
                    return this.rosterSections.filter(s =>
                        (s.grade_level || '').toLowerCase().includes(q) ||
                        (s.name || '').toLowerCase().includes(q) ||
                        (s.gender || '').toLowerCase().includes(q) ||
                        (s.mode || '').toLowerCase().includes(q)
                    );
                },

                sumTotal() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.total || 0), 0);
                },
                sumOdl1() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.odl_1st || 0), 0);
                },
                sumOdl2() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.odl_2nd || 0), 0);
                },
                sumF2f() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.f2f || 0), 0);
                },
                sumAccounts() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.accounts || 0), 0);
                },
                sumLogins() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.logged_in || 0), 0);
                },
                sumTeams() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.joined_teams || 0), 0);
                },
                sumClass() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.joined_class || 0), 0);
                },
                sumPassword() {
                    return this.gradeBreakdown.reduce((sum, g) => sum + (g.password_changed || 0), 0);
                },

                init() {
                    this.fetchData();
                    // Auto refresh every 5 minutes
                    setInterval(() => this.fetchData(this.currentPage), 300000);
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
                        status: this.selectedStatus,
                        gender: this.selectedGender
                    });

                    try {
                        const res = await fetch(`/students/reports/data?${params.toString()}`);
                        const data = await res.json();

                        if (data.success) {
                            this.summary = data.summary;
                            this.gradeBreakdown = data.gradeBreakdown;
                            this.students = data.students;
                            this.lastSync = data.last_sync;
                            this.generatedAt = data.generated_at;

                            this.$nextTick(() => {
                                try {
                                    this.renderCharts(data.charts);
                                } catch (err) {
                                    console.error('Error rendering charts:', err);
                                }
                                try {
                                    if (typeof lucide !== 'undefined') {
                                        lucide.createIcons();
                                    }
                                } catch (err) {
                                    console.error('Error creating lucide icons:', err);
                                }
                            });
                            if (this.activeTab === 'payments' || this.paymentsLoaded) {
                                this.loadPaymentsData();
                            } else {
                                this.paymentsLoaded = false;
                            }
                        } else {
                            this.errorMessage = 'Failed to load report analytics data.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Network error loading analytics dashboard data.';
                    } finally {
                        this.loading = false;
                    }
                },

                async forceSync() {
                    this.syncing = true;
                    this.errorMessage = '';
                    this.successMessage = '';

                    try {
                        const res = await fetch(`/students/reports/sync`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();

                        if (data.success) {
                            this.successMessage = data.message;
                            this.lastSync = data.last_sync;
                            this.fetchData(this.currentPage);
                        } else {
                            this.errorMessage = data.message || 'Synchronization failed.';
                        }
                    } catch (e) {
                        this.errorMessage = 'Network error during Microsoft Graph sync.';
                    } finally {
                        this.syncing = false;
                    }
                },

                renderCharts(chartData) {
                    if (typeof Chart === 'undefined') {
                        console.warn('Chart.js is not loaded yet. Retrying chart rendering in 500ms...');
                        setTimeout(() => this.renderCharts(chartData), 500);
                        return;
                    }
                    if (!chartData) {
                        console.warn('No chart data available.');
                        return;
                    }

                    // Destroy existing chart instances to prevent rendering issues on resize/update
                    Object.keys(this.charts).forEach(key => {
                        if (this.charts[key]) this.charts[key].destroy();
                    });

                    const createChart = (id, type, labels, data, colors, isDonut = false) => {
                        const ctx = document.getElementById(id);
                        if (!ctx) return null;

                        return new Chart(ctx, {
                            type: type,
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: data,
                                    backgroundColor: colors,
                                    borderWidth: 0,
                                    hoverOffset: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: { size: 9, weight: 'bold' },
                                            boxWidth: 10,
                                            padding: 8
                                        }
                                    }
                                },
                                cutout: isDonut ? '65%' : undefined
                            }
                        });
                    };

                    const colors = {
                        emerald: ['#10b981', '#a7f3d0'],
                        indigo: ['#6366f1', '#c7d2fe'],
                        rose: ['#f43f5e', '#fecdd3'],
                        amber: ['#f59e0b', '#fde68a'],
                        blue: ['#3b82f6', '#bfdbfe'],
                        purple: ['#8b5cf6', '#ddd6fe'],
                        slate: ['#475569', '#cbd5e1']
                    };

                    // Provisioned vs Unprovisioned
                    if (chartData.accountStatus && chartData.accountStatus.labels) {
                        this.charts.accountStatus = createChart('chartAccountStatus', 'pie', chartData.accountStatus.labels, chartData.accountStatus.data, [colors.indigo[0], colors.slate[1]]);
                    }
                    
                    // Active vs Inactive Logins
                    if (chartData.loginStatus && chartData.loginStatus.labels) {
                        this.charts.loginStatus = createChart('chartLoginStatus', 'pie', chartData.loginStatus.labels, chartData.loginStatus.data, [colors.blue[0], colors.amber[0]]);
                    }
                    
                    // Teams Adoption
                    if (chartData.teamsAdoption && chartData.teamsAdoption.labels) {
                        this.charts.teamsAdoption = createChart('chartTeamsAdoption', 'doughnut', chartData.teamsAdoption.labels, chartData.teamsAdoption.data, [colors.purple[0], colors.slate[1]], true);
                    }
                    
                    // Class Join Status
                    if (chartData.classJoinStatus && chartData.classJoinStatus.labels) {
                        this.charts.classJoinStatus = createChart('chartClassJoinStatus', 'doughnut', chartData.classJoinStatus.labels, chartData.classJoinStatus.data, [colors.emerald[0], colors.rose[0]], true);
                    }
                    
                    // Password Change Status
                    if (chartData.passwordChangeStatus && chartData.passwordChangeStatus.labels) {
                        this.charts.passwordChangeStatus = createChart('chartPasswordChangeStatus', 'pie', chartData.passwordChangeStatus.labels, chartData.passwordChangeStatus.data, [colors.emerald[0], colors.amber[0]]);
                    }
                    
                    // Active vs Inactive Accounts
                    if (chartData.activeInactive && chartData.activeInactive.labels) {
                        this.charts.activeInactive = createChart('chartActiveInactive', 'pie', chartData.activeInactive.labels, chartData.activeInactive.data, [colors.emerald[0], colors.rose[0]]);
                    }
                    
                    // License Assignment Status
                    if (chartData.licenseStatus && chartData.licenseStatus.labels) {
                        this.charts.licenseStatus = createChart('chartLicenseStatus', 'pie', chartData.licenseStatus.labels, chartData.licenseStatus.data, [colors.blue[0], colors.rose[0]]);
                    }

                    // Bar chart for grade level student counts
                    const barCtx = document.getElementById('chartGradeDistribution');
                    if (barCtx && chartData.gradeDistribution && chartData.gradeDistribution.labels) {
                        this.charts.gradeDistribution = new Chart(barCtx, {
                            type: 'bar',
                            data: {
                                labels: chartData.gradeDistribution.labels,
                                datasets: [{
                                    data: chartData.gradeDistribution.data,
                                    backgroundColor: colors.indigo[0],
                                    borderRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 } } },
                                    x: { grid: { display: false }, ticks: { font: { size: 9 } } }
                                }
                            }
                        });
                    }
                },

                exportCsv() {
                    if (!this.students.data.length) return;
                    let headers = ['Student Number', 'Full Name', 'Grade Level', 'Email', 'License Status', 'Account Status', 'Last Sign-in', 'Teams Joined', 'Class Joined', 'Password Changed', 'Teams App Used', 'Teams Last Activity', 'Meetings Attended'];
                    let rows = this.students.data.map(s => [
                        s.student_number,
                        s.name,
                        s.grade,
                        s.email,
                        s.license,
                        s.account_enabled,
                        s.last_sign_in,
                        s.teams_joined,
                        s.class_joined,
                        s.password_changed,
                        s.teams_app_used ? 'Yes' : 'No',
                        s.teams_last_activity,
                        s.teams_meetings_attended
                    ]);
                    let csvContent = '\uFEFF' + [headers.join(','), ...rows.map(e => e.map(val => `"${val}"`).join(','))].join('\n');
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement("a");
                    link.href = URL.createObjectURL(blob);
                    link.setAttribute("download", `AMIS_M365_Analytics_${new Date().toISOString().slice(0,10)}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },

                async loadRosterData() {
                    this.rosterLoading = true;
                    try {
                        const res = await fetch('/students/reports/class-roster');
                        const data = await res.json();
                        if (data.success) {
                            // Add _open toggle to each section
                            this.rosterSections = (data.sections || []).map(s => ({ ...s, _open: false }));
                            this.rosterGradeSummary = data.grade_summary || [];
                            this.rosterOverall = data.overall || { total: 0, joined: 0, not_joined: 0, pct: 0 };
                            this.rosterLoaded = true;
                            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                        }
                    } catch (e) {
                        console.error('Failed to load class roster data:', e);
                    } finally {
                        this.rosterLoading = false;
                    }
                },

                async loadPaymentsData() {
                    this.paymentsLoading = true;
                    try {
                        const params = new URLSearchParams({
                            search: this.searchQuery,
                            school_year: this.selectedSchoolYear,
                            grade_level: this.selectedGrade,
                            learning_mode: this.selectedLearningMode,
                            status: this.selectedStatus,
                            gender: this.selectedGender
                        });
                        const res = await fetch(`{{ route('admin.students.reports.enrollment-payments') }}?${params.toString()}`);
                        const data = await res.json();
                        if (data.success) {
                            this.paymentsData = data;
                            this.paymentsLoaded = true;
                            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
                        }
                    } catch (e) {
                        console.error('Failed to load enrollment payments data:', e);
                    } finally {
                        this.paymentsLoading = false;
                    }
                },

                exportRosterCsv() {
                    if (!this.rosterSections.length) return;
                    let rows = [['Section', 'Grade Level', 'Gender', 'Mode', 'Shift', 'Total', 'Joined', 'Not Joined', '% Joined']];
                    this.rosterSections.forEach(sec => {
                        rows.push([
                            sec.name || '',
                            sec.grade_level,
                            sec.gender,
                            sec.mode,
                            sec.shift || '',
                            sec.total,
                            sec.joined,
                            sec.not_joined,
                            sec.pct + '%'
                        ]);
                    });
                    let csvContent = '\uFEFF' + rows.map(r => r.map(v => `"${v}"`).join(',')).join('\n');
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.setAttribute('download', `AMIS_ClassRoster_${new Date().toISOString().slice(0,10)}.csv`);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },

                exportExcel() {
                    this.exportCsv();
                },

                printReport() {
                    window.print();
                },

                async syncGoogleDrive() {
                    if (this.gdriveSyncing) return;
                    if (!confirm('Are you sure you want to sync enrollees matching your current filters to Google Drive? This will create folders per grade and student name, and upload their files.')) {
                        return;
                    }
                    this.gdriveSyncing = true;
                    try {
                        const params = new URLSearchParams({
                            search: this.searchQuery,
                            school_year: this.selectedSchoolYear,
                            grade_level: this.selectedGrade,
                            learning_mode: this.selectedLearningMode,
                            status: this.selectedStatus,
                            gender: this.selectedGender
                        });
                        const res = await fetch(`{{ route('admin.students.reports.sync-google-drive') }}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: params.toString()
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert(data.message);
                        } else {
                            alert('Failed: ' + (data.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error('Failed to sync to Google Drive:', e);
                        alert('Failed: Network error occurred.');
                    } finally {
                        this.gdriveSyncing = false;
                    }
                }
            }));
        });
    </script>
</x-admin-layout>
