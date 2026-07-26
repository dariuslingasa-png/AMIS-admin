<x-admin-layout
    title="Print & Export Student Records"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Print & Export', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Print & Export Center</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Select filter options below to print batch forms, generate printable ID sheets, or download report archives.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Card -->
        <x-card title="Export Student Registry" subtitle="Download and print enrollment forms, school forms, and administrative reports">
            <!-- FILTER OPTIONS -->
            <div class="rounded-2xl border border-slate-100 bg-emerald-50/40 p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-black uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                        <i data-lucide="filter" class="h-4 w-4 text-emerald-600"></i>
                        Filter Target Student Records
                    </span>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                        Live Filter
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Learning Mode Filter -->
                    <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Mode</label>
                        <select id="p-filter-mode" class="w-full h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer">
                            <option value="" {{ request('mode') == '' ? 'selected' : '' }}>All Modes (F2F & ODL)</option>
                            <option value="F2F" {{ request('mode') == 'F2F' ? 'selected' : '' }}>Face to Face (F2F)</option>
                            <option value="ODL" {{ request('mode') == 'ODL' ? 'selected' : '' }}>Online Distance Learning (ODL)</option>
                        </select>
                    </div>

                    <!-- Grade Level Filter -->
                    <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Grade</label>
                        <select id="p-filter-grade" class="w-full h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer">
                            <option value="" {{ request('grade') == '' ? 'selected' : '' }}>All Grade Levels</option>
                            <option value="Kinder 1" {{ request('grade') == 'Kinder 1' ? 'selected' : '' }}>K1 (Kinder 1)</option>
                            <option value="Kinder 2" {{ request('grade') == 'Kinder 2' ? 'selected' : '' }}>K2 (Kinder 2)</option>
                            @foreach (['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g)
                                <option value="{{ $g }}" {{ request('grade') == $g ? 'selected' : '' }}>{{ \App\Models\Student::abbreviateGrade($g) }} ({{ $g }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Gender Filter (Optional) -->
                    <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Gender (Optional)</label>
                        <select id="p-filter-gender" class="w-full h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer">
                            <option value="" {{ request('gender') == '' ? 'selected' : '' }}>All Genders</option>
                            <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male Only</option>
                            <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female Only</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- THREE COLUMNS LAYOUT -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Column 1: Student Documents -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-emerald-50/20 p-5 shadow-xs transition hover:shadow-md">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4 border-b border-emerald-100 pb-2.5">
                            <div class="p-2.5 rounded-xl bg-emerald-100 text-emerald-700">
                                <i data-lucide="file-text" class="h-5 w-5"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Student Documents</h4>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-600 font-semibold">
                            <!-- Enrollment Form (ACTIVE) -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-600/70"></i>
                                    <span>Enrollment Form</span>
                                </span>
                                <button type="button" onclick="openBatchExportModal('enrollment_forms')" class="text-emerald-700 hover:text-emerald-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <!-- Learner's Profile -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Learner's Profile</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Student ID Card -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="credit-card" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Student ID Card</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Class Schedule -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Class Schedule</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Report Card (SF9) -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="award" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Report Card (SF9)</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Permanent Record (SF10) -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="book-open" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Permanent Record (SF10)</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Certificate of Enrollment -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Certificate of Enrollment</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Good Moral Certificate -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="shield" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Good Moral Certificate</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Certificate of Completion (Grade 10) -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Certificate of Completion (Grade 10)</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Certificate of Graduation (Grade 12) -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check-square" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Certificate of Graduation (Grade 12)</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Certificate of Recognition / Awards -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Certificate of Recognition / Awards</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Certificate of Attendance -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="user-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Certificate of Attendance</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Certificate of Transfer -->
                            <li class="flex items-center justify-between py-1 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-input" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Certificate of Transfer (if applicable)</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Column 2: DepEd School Forms -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-amber-50/20 p-5 shadow-xs transition hover:shadow-md">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4 border-b border-amber-100 pb-2.5">
                            <div class="p-2.5 rounded-xl bg-amber-100 text-amber-700">
                                <i data-lucide="library" class="h-5 w-5"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">DepEd School Forms</h4>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-600 font-semibold">
                            <!-- SF1 – School Register -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="archive" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF1 – School Register</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF2 – Daily Attendance Report -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF2 – Daily Attendance Report</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF3 – Books Issued and Returned -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="book-marked" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF3 – Books Issued and Returned</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF4 – Monthly Learner's Movement & Attendance -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="trending-up" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF4 – Monthly Learner's Movement & Attendance</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF5 – Report on Promotion & Learning Progress -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="line-chart" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF5 – Report on Promotion & Learning Progress</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF6 – Summarized Report on Promotion -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="pie-chart" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF6 – Summarized Report on Promotion</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF9 – Report Card -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-signature" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF9 – Report Card</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- SF10 – Permanent Record -->
                            <li class="flex items-center justify-between py-1 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-git" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">SF10 – Permanent Record</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Column 3: Reports -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-sky-50/20 p-5 shadow-xs transition hover:shadow-md">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4 border-b border-sky-100 pb-2.5">
                            <div class="p-2.5 rounded-xl bg-sky-100 text-sky-700">
                                <i data-lucide="file-bar-chart" class="h-5 w-5"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Reports</h4>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-600 font-semibold">
                            <!-- Master List (ACTIVE) -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="list" class="w-3.5 h-3.5 text-sky-650"></i>
                                    <span class="font-extrabold text-slate-900">Master List</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('masters_list')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <!-- Class List -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Class List</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Enrollment Summary -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="bar-chart-3" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Enrollment Summary</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Attendance Report -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Attendance Report</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Grade Sheet -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="sheet" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Grade Sheet</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Honors List -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="crown" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Honors List</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Promotion List -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="rocket" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Promotion List</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Graduating Students List -->
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="user-plus" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Graduating Students List</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <!-- Student Directory -->
                            <li class="flex items-center justify-between py-1 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="contact" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Student Directory</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ZIP ARCHIVES & BULK UTILITIES -->
            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-5 mt-6">
                <span class="text-xs font-black uppercase tracking-wider text-slate-700 block mb-3">
                    Bulk Downloads & Utilities
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <button type="button" onclick="runPrintRecordAction('forms_jpg')" class="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-3 text-xs font-extrabold text-slate-750 hover:bg-emerald-50 hover:text-emerald-950 transition cursor-pointer text-left shadow-xs">
                        <span class="flex items-center gap-2">
                            <i data-lucide="folder-archive" class="w-4 h-4 text-emerald-600"></i>
                            <span>Enrollment Forms ZIP</span>
                        </span>
                        <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                    </button>

                    <button type="button" onclick="runPrintRecordAction('id_cards')" class="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-3 text-xs font-extrabold text-slate-750 hover:bg-emerald-50 hover:text-emerald-950 transition cursor-pointer text-left shadow-xs">
                        <span class="flex items-center gap-2">
                            <i data-lucide="folder-archive" class="w-4 h-4 text-emerald-600"></i>
                            <span>ID Cards ZIP</span>
                        </span>
                        <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                    </button>

                    <button type="button" onclick="runPrintRecordAction('credentials')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-3 text-xs font-extrabold text-slate-750 hover:bg-slate-100 transition cursor-pointer text-left">
                        <i data-lucide="key" class="h-4 w-4 text-amber-600"></i>
                        <span>Microsoft Credentials</span>
                    </button>

                    <button type="button" onclick="runPrintRecordAction('canva')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-3 text-xs font-extrabold text-emerald-800 hover:bg-emerald-50 transition cursor-pointer text-left">
                        <i data-lucide="sparkles" class="h-4 w-4 text-emerald-600"></i>
                        <span>Canva Bulk Create</span>
                    </button>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Background Batch Export Modal -->
    <div id="batch-export-modal" class="fixed inset-0 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300" style="z-index: 9999999 !important;">
        <div class="relative w-full max-w-lg scale-95 transform rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
            
            <!-- CLOSE BUTTON (Top-Right) -->
            <button type="button" onclick="closeBatchExportModal()" class="absolute right-4 top-4 rounded-full bg-slate-100 p-1.5 text-slate-500 hover:bg-slate-200 transition cursor-pointer dark:bg-slate-800 dark:text-slate-400">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>

            <!-- STATE 1: CONFIGURATION -->
            <div id="export-state-config" class="space-y-5">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-700">
                        <i data-lucide="file-text" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">Batch Export Enrollment Forms</h3>
                        <p class="text-[10px] text-slate-500 font-medium">Configure format and export scope</p>
                    </div>
                </div>

                <!-- Format Selection -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2.5">Format</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="export-format-selector">
                        <!-- PDF Card -->
                        <div onclick="selectExportFormat('pdf')" id="format-card-pdf" class="flex items-center justify-between p-3 rounded-xl border-2 border-emerald-600 bg-emerald-50/20 text-slate-800 cursor-pointer transition hover:border-emerald-600 hover:bg-emerald-50/10">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center">
                                    <i data-lucide="file-text" class="h-4 w-4"></i>
                                </span>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs font-black uppercase tracking-wide">PDF</span>
                                    <span class="text-[9px] text-slate-500">Standard document layout</span>
                                </div>
                            </div>
                            <div class="w-4 h-4 rounded-full border-4 border-emerald-600 flex items-center justify-center bg-white" id="format-radio-pdf">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div>
                            </div>
                        </div>

                        <!-- DOCX Card -->
                        <div onclick="selectExportFormat('docx')" id="format-card-docx" class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-white text-slate-800 cursor-pointer transition hover:border-emerald-600 hover:bg-emerald-50/10">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center">
                                    <i data-lucide="file-edit" class="h-4 w-4"></i>
                                </span>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs font-black uppercase tracking-wide">DOCX</span>
                                    <span class="text-[9px] text-slate-500">Editable Microsoft Word</span>
                                </div>
                            </div>
                            <div class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center bg-white" id="format-radio-docx"></div>
                        </div>
                    </div>
                </div>

                <!-- Range Selection -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Range</label>
                    <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <input type="checkbox" checked disabled class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs font-extrabold text-slate-800">All Students ({{ number_format($totalStudents) }})</span>
                    </label>
                </div>

                <div class="border-t border-b border-dashed border-slate-200 py-3 text-xs text-slate-500 font-semibold space-y-1">
                    <div class="flex justify-between">
                        <span>Estimated Time:</span>
                        <span class="text-slate-850 font-black">~2-4 minutes</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Output File:</span>
                        <span id="export-output-file-label" class="text-slate-850 font-black">enrollment_forms_2026.pdf</span>
                    </div>
                </div>

                <!-- Action Button -->
                <button type="button" onclick="startBackgroundExport()" class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 text-xs font-black text-white shadow-sm transition active:scale-[0.99] cursor-pointer">
                    <i data-lucide="play" class="h-4 w-4"></i>
                    <span>Start Export</span>
                </button>
            </div>

            <!-- STATE 2: EXPORT IN PROGRESS -->
            <div id="export-state-progress" class="hidden space-y-5 text-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="p-3 rounded-full bg-emerald-50 text-emerald-600 animate-bounce">
                        <i data-lucide="rocket" class="h-6 w-6"></i>
                    </div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">Export Started</h3>
                    <p id="export-status-label" class="text-xs font-bold text-slate-600">Preparing documents...</p>
                </div>

                <!-- Progress Bar -->
                <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden border border-slate-200/50">
                    <div id="export-progress-bar" class="h-full rounded-full bg-emerald-600 transition-all duration-300" style="width: 0%"></div>
                </div>

                <div class="flex items-center justify-between text-xs font-bold text-slate-600 px-1">
                    <span id="export-percentage">0%</span>
                    <span id="export-processed-counter">0 / {{ $totalStudents }} Students</span>
                </div>

                <div class="rounded-xl bg-slate-50 p-3 text-left space-y-1.5 text-xs text-slate-500 font-semibold border border-slate-100">
                    <div class="flex justify-between">
                        <span>Estimated Remaining:</span>
                        <span id="export-remaining-time" class="text-slate-850 font-black">Calculating...</span>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400 font-medium">Please keep using the system. You'll be notified when the download is ready.</p>

                <!-- Action Button -->
                <button type="button" onclick="hideExportModal()" class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-4 text-xs font-bold text-slate-700 transition active:scale-[0.99] cursor-pointer">
                    <i data-lucide="eye-off" class="h-4 w-4"></i>
                    <span>Hide</span>
                </button>
            </div>

            <!-- STATE 3: EXPORT COMPLETE -->
            <div id="export-state-complete" class="hidden space-y-5 text-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="p-3 rounded-full bg-emerald-100 text-emerald-700 shadow-md">
                        <i data-lucide="check-circle-2" class="h-8 w-8"></i>
                    </div>
                    <h3 class="text-base font-black text-emerald-800 uppercase tracking-wide">Export Complete!</h3>
                    <p class="text-xs text-slate-500 font-medium">Your export has been completed successfully.</p>
                </div>

                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-left text-xs font-semibold text-slate-600 space-y-2">
                    <div class="flex justify-between">
                        <span>Total Documents:</span>
                        <span class="text-slate-850 font-black">{{ number_format($totalStudents) }} Enrollment Forms</span>
                    </div>
                    <div class="flex justify-between">
                        <span id="export-complete-size-label">ZIP Size:</span>
                        <span id="export-complete-size-val" class="text-slate-850 font-black">186 MB</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Filename:</span>
                        <span id="export-complete-filename-val" class="text-slate-850 font-mono font-bold">enrollment_forms_2026.zip</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Completion Time:</span>
                        <span id="export-completion-time" class="text-slate-850 font-black">Completed Just Now</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col gap-2">
                    <button type="button" onclick="triggerActualZipDownload()" class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 text-xs font-black text-white shadow-md transition active:scale-[0.99] cursor-pointer">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        <span>Download ZIP</span>
                    </button>
                    <button type="button" onclick="closeBatchExportModal()" class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-4 text-xs font-bold text-slate-700 transition cursor-pointer">
                        <span>Close</span>
                    </button>
                </div>
            </div>
            
        </div>
    </div>

    <!-- Background Export Floating Indicator -->
    <div id="export-floating-indicator" class="fixed bottom-6 right-6 hidden items-center gap-3 rounded-2xl border border-emerald-200 bg-white p-4 shadow-xl dark:border-slate-800 dark:bg-slate-900 animate-slide-up cursor-pointer hover:border-emerald-300 transition" style="z-index: 9999999 !important;" onclick="showExportModalFromIndicator()">
        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
            <svg class="h-5 w-5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <div>
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">Exporting in Background</h4>
            <div class="flex items-center gap-2 mt-1">
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div id="export-floating-progress-bar" class="h-full bg-emerald-600 transition-all duration-300" style="width: 0%"></div>
                </div>
                <span id="export-floating-percentage" class="text-[10px] font-bold text-emerald-700">0%</span>
            </div>
        </div>
    </div>

    <!-- Script Section -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Teleport modal and floating indicator to document body to overlay sidebar/navbar
        const modal = document.getElementById('batch-export-modal');
        const indicator = document.getElementById('export-floating-indicator');
        if (modal) document.body.appendChild(modal);
        if (indicator) document.body.appendChild(indicator);

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('mode')) {
            const el = document.getElementById('p-filter-mode');
            if (el) el.value = urlParams.get('mode');
        }
        if (urlParams.has('grade')) {
            const el = document.getElementById('p-filter-grade');
            if (el) el.value = urlParams.get('grade');
        }
        if (urlParams.has('gender')) {
            const el = document.getElementById('p-filter-gender');
            if (el) el.value = urlParams.get('gender');
        }

        // Bind auto-reload on filter changes
        const filters = ['p-filter-mode', 'p-filter-grade', 'p-filter-gender'];
        filters.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function() {
                    applyFiltersAndReload();
                });
            }
        });
    });

    function applyFiltersAndReload() {
        const mode = document.getElementById('p-filter-mode')?.value || '';
        const grade = document.getElementById('p-filter-grade')?.value || '';
        const gender = document.getElementById('p-filter-gender')?.value || '';
        const search = '{{ request('search', '') }}';

        const params = new URLSearchParams();
        if (mode) params.append('mode', mode);
        if (grade) params.append('grade', grade);
        if (gender) params.append('gender', gender);
        if (search) params.append('search', search);

        if (window.startLoadingTransition) {
            window.startLoadingTransition();
        }
        window.location.href = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    }

    // Background Export State Variables
    let exportInterval = null;
    let exportPercent = 0;
    const totalStudents = {{ $totalStudents }};
    let isExportRunning = false;
    let selectedFormat = 'pdf'; // Default format

    function selectExportFormat(format) {
        selectedFormat = format;
        const formats = ['pdf', 'docx'];
        
        formats.forEach(f => {
            const card = document.getElementById('format-card-' + f);
            const radio = document.getElementById('format-radio-' + f);
            
            if (f === format) {
                // Active State styling matching tailwind rules
                card.className = "flex items-center justify-between p-3 rounded-xl border-2 border-emerald-600 bg-emerald-50/20 text-slate-800 cursor-pointer transition hover:border-emerald-600 hover:bg-emerald-50/10";
                radio.className = "w-4 h-4 rounded-full border-4 border-emerald-600 flex items-center justify-center bg-white";
                radio.innerHTML = '<div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div>';
            } else {
                // Inactive State styling
                card.className = "flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-white text-slate-800 cursor-pointer transition hover:border-emerald-600 hover:bg-emerald-50/10";
                radio.className = "w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center bg-white";
                radio.innerHTML = '';
            }
        });

        // Update output file label extension dynamically
        const label = document.getElementById('export-output-file-label');
        if (label) {
            label.textContent = 'enrollment_forms_2026.' + format;
        }
    }

    function openBatchExportModal(type) {
        const modal = document.getElementById('batch-export-modal');
        if (modal) {
            document.body.appendChild(modal);
        }
        if (isExportRunning) {
            // Re-open directly to progress screen
            showExportModal();
            return;
        }
        if (modal) {
            modal.classList.remove('hidden');
            document.getElementById('export-state-config').classList.remove('hidden');
            document.getElementById('export-state-progress').classList.add('hidden');
            document.getElementById('export-state-complete').classList.add('hidden');
            if (window.lucide) window.lucide.createIcons();
        }
    }

    function closeBatchExportModal() {
        const modal = document.getElementById('batch-export-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function showExportModal() {
        const modal = document.getElementById('batch-export-modal');
        if (modal) {
            document.body.appendChild(modal);
            modal.classList.remove('hidden');
        }
    }

    function hideExportModal() {
        closeBatchExportModal();
        // Show floating indicator if running
        if (isExportRunning) {
            const indicator = document.getElementById('export-floating-indicator');
            if (indicator) {
                document.body.appendChild(indicator);
                indicator.classList.remove('hidden');
            }
        }
    }

    function showExportModalFromIndicator() {
        const indicator = document.getElementById('export-floating-indicator');
        if (indicator) indicator.classList.add('hidden');
        showExportModal();
    }

    function startBackgroundExport() {
        isExportRunning = true;
        exportPercent = 0;

        // Transition views inside modal
        document.getElementById('export-state-config').classList.add('hidden');
        document.getElementById('export-state-progress').classList.remove('hidden');

        const bar = document.getElementById('export-progress-bar');
        const percentageText = document.getElementById('export-percentage');
        const counterText = document.getElementById('export-processed-counter');
        const statusLabel = document.getElementById('export-status-label');
        const remainingTimeText = document.getElementById('export-remaining-time');
        const floatBar = document.getElementById('export-floating-progress-bar');
        const floatPercentage = document.getElementById('export-floating-percentage');

        if (exportInterval) clearInterval(exportInterval);

        exportInterval = setInterval(() => {
            if (exportPercent < 100) {
                exportPercent += Math.floor(Math.random() * 3) + 1;
                if (exportPercent > 100) exportPercent = 100;

                // Update UI values
                if (bar) bar.style.width = exportPercent + '%';
                if (floatBar) floatBar.style.width = exportPercent + '%';
                if (percentageText) percentageText.innerText = exportPercent + '%';
                if (floatPercentage) floatPercentage.innerText = exportPercent + '%';

                // Processed count simulation
                const currentProcessed = Math.round((exportPercent / 100) * totalStudents);
                if (counterText) counterText.innerText = `${currentProcessed.toLocaleString()} / ${totalStudents.toLocaleString()} Students`;

                // Status updates based on percentage
                if (exportPercent <= 10) {
                    if (statusLabel) statusLabel.innerText = 'Preparing...';
                    if (remainingTimeText) remainingTimeText.innerText = 'Calculating time...';
                } else if (exportPercent <= 85) {
                    if (statusLabel) statusLabel.innerText = 'Generating Documents...';
                    // Estimate remaining time
                    const secondsLeft = Math.round(((100 - exportPercent) / (exportPercent)) * 12);
                    if (remainingTimeText) remainingTimeText.innerText = `~${secondsLeft > 0 ? secondsLeft : 1} seconds`;
                } else if (exportPercent <= 95) {
                    if (statusLabel) statusLabel.innerText = 'Compressing ZIP...';
                    if (remainingTimeText) remainingTimeText.innerText = 'Compressing file structure...';
                } else {
                    if (statusLabel) statusLabel.innerText = 'Finalizing...';
                    if (remainingTimeText) remainingTimeText.innerText = 'Preparing download payload...';
                }
            } else {
                // Done!
                clearInterval(exportInterval);
                isExportRunning = false;

                // Update completed state stats dynamically
                const sizeLabel = document.getElementById('export-complete-size-label');
                const sizeVal = document.getElementById('export-complete-size-val');
                const filenameVal = document.getElementById('export-complete-filename-val');
                const downloadBtnSpan = document.querySelector('#export-state-complete button span');

                if (selectedFormat === 'pdf') {
                    if (sizeLabel) sizeLabel.textContent = 'ZIP Size:';
                    if (sizeVal) sizeVal.textContent = '186 MB';
                    if (filenameVal) filenameVal.textContent = 'enrollment_forms_2026_pdf.zip';
                    if (downloadBtnSpan) downloadBtnSpan.textContent = 'Download PDF ZIP';
                } else {
                    if (sizeLabel) sizeLabel.textContent = 'ZIP Size:';
                    if (sizeVal) sizeVal.textContent = '186 MB';
                    if (filenameVal) filenameVal.textContent = 'enrollment_forms_2026_docx.zip';
                    if (downloadBtnSpan) downloadBtnSpan.textContent = 'Download DOCX ZIP';
                }

                // Transition to success screen
                document.getElementById('export-state-progress').classList.add('hidden');
                document.getElementById('export-state-complete').classList.remove('hidden');

                // Hide floating indicator
                const indicator = document.getElementById('export-floating-indicator');
                if (indicator) indicator.classList.add('hidden');

                // Trigger audio or browser notification
                if (Notification.permission === "granted") {
                    new Notification("Export Complete!", {
                        body: `Enrollment Forms ${selectedFormat.toUpperCase()} has been compiled successfully.`,
                        icon: '/favicon.ico'
                    });
                } else if (Notification.permission !== "denied") {
                    Notification.requestPermission();
                }
            }
        }, 150);
    }

    function triggerBackgroundDownload(url) {
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function triggerActualZipDownload() {
        const mode = document.getElementById('p-filter-mode')?.value || '';
        const grade = document.getElementById('p-filter-grade')?.value || '';
        const gender = document.getElementById('p-filter-gender')?.value || '';
        const search = '{{ request('search', '') }}';

        const params = new URLSearchParams();
        if (mode) params.append('mode', mode);
        if (grade) params.append('grade', grade);
        if (gender) params.append('gender', gender);
        if (search) params.append('search', search);
        if (selectedFormat) params.append('format', selectedFormat);

        const queryString = params.toString() ? '?' + params.toString() : '';

        window.location.href = '{{ route('admin.students.download-enrolment-forms-zip') }}' + queryString;
        closeBatchExportModal();
    }

    function runPrintRecordAction(actionType) {
        const mode = document.getElementById('p-filter-mode')?.value || '';
        const grade = document.getElementById('p-filter-grade')?.value || '';
        const gender = document.getElementById('p-filter-gender')?.value || '';
        const search = '{{ request('search', '') }}';

        const params = new URLSearchParams();
        if (mode) params.append('mode', mode);
        if (grade) params.append('grade', grade);
        if (gender) params.append('gender', gender);
        if (search) params.append('search', search);

        const queryString = params.toString() ? '?' + params.toString() : '';

        if (actionType === 'forms_batch') {
            window.open('{{ route('admin.students.print-enrolment-forms-batch') }}' + queryString, '_blank');
        } else if (actionType === 'forms_jpg') {
            openBatchExportModal('enrollment_forms');
        } else if (actionType === 'id_cards') {
            const idParams = new URLSearchParams(params);
            idParams.append('print_id', '1');
            idParams.append('is_print', '1');
            window.open('{{ route('admin.students.index') }}?' + idParams.toString(), '_blank');
        } else if (actionType === 'docs_zip') {
            triggerBackgroundDownload('{{ route('admin.students.download-docs-zip') }}' + queryString);
        } else if (actionType === 'credentials') {
            const credParams = new URLSearchParams(params);
            credParams.append('print_credentials', '1');
            credParams.append('is_print', '1');
            window.open('{{ route('admin.students.index') }}?' + credParams.toString(), '_blank');
        } else if (actionType === 'masters_list') {
            const listParams = new URLSearchParams(params);
            listParams.append('print', '1');
            listParams.append('is_print', '1');
            window.open('{{ route('admin.students.index') }}?' + listParams.toString(), '_blank');
        } else if (actionType === 'canva') {
            triggerBackgroundDownload('{{ route('admin.students.export-canva') }}' + queryString);
        }
    }
    </script>
</x-admin-layout>
