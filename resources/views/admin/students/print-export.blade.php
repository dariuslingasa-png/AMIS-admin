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
                        Select filter options below to print batch forms, generate printable ID sheets, or download requirements ZIP archives.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Card -->
        <x-card title="Export Student Registry" subtitle="Download and print enrollment forms, ID cards sheet, and student documents">
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
                <!-- Column 1: Download Individual Documents -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-emerald-50/20 p-5 shadow-xs transition hover:shadow-md">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4 border-b border-emerald-100 pb-2.5">
                            <div class="p-2.5 rounded-xl bg-emerald-100 text-emerald-700">
                                <i data-lucide="file-text" class="h-5 w-5"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Individual Documents</h4>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-600 font-semibold">
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-600/70"></i>
                                    <span>Enrollment Form (PDF)</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('forms_batch')" class="text-emerald-700 hover:text-emerald-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="credit-card" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Student ID Card</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="user" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Student Profile</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Registration Form</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-check" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Assessment Form</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Class Schedule</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="award" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Report Card</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="book-open" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Permanent Record</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="shield" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Good Moral Cert.</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Cert. of Enrollment</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-signature" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Cert. of Reg. (COR)</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Cert. of Completion</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                            <li class="flex items-center justify-between py-1 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="list-todo" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-slate-500">Requirements Checklist</span>
                                </span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/50">Coming Soon</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Column 2: Download Student Requirements -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-sky-50/20 p-5 shadow-xs transition hover:shadow-md">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4 border-b border-sky-100 pb-2.5">
                            <div class="p-2.5 rounded-xl bg-sky-100 text-sky-700">
                                <i data-lucide="check-square" class="h-5 w-5"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Student Requirements</h4>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-600 font-semibold">
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="image" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>2x2 / ID Photo</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>PSA Birth Certificate</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="activity" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Vaccination Record</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="heart-pulse" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Medical Certificate</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-digit" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Form 137 / SF10</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Form 138 / Report Card</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Good Moral</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="home" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Proof of Address</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 border-b border-slate-100/50 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="users" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Parent/Guardian ID</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                            <li class="flex items-center justify-between py-1 hover:bg-slate-50/50 rounded px-1.5 transition">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="pencil-line" class="w-3.5 h-3.5 text-sky-600/70"></i>
                                    <span>Signed Consent Forms</span>
                                </span>
                                <button type="button" onclick="runPrintRecordAction('docs_zip')" class="text-sky-700 hover:text-sky-900 font-extrabold hover:underline cursor-pointer">Download</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Column 3: Bulk Download -->
                <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-rose-50/20 p-5 shadow-xs transition hover:shadow-md">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4 border-b border-rose-100 pb-2.5">
                            <div class="p-2.5 rounded-xl bg-rose-100 text-rose-700">
                                <i data-lucide="archive" class="h-5 w-5"></i>
                            </div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Bulk Download</h4>
                        </div>
                        
                        <!-- Master Action Button -->
                        <button type="button" onclick="runPrintRecordAction('docs_zip')" class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-rose-600 hover:bg-rose-700 py-4.5 px-4 text-xs font-black text-white shadow-md transition active:scale-[0.98] cursor-pointer mb-6">
                            <i data-lucide="download-cloud" class="h-5 w-5"></i>
                            <span class="uppercase tracking-wider">Download All Documents ZIP</span>
                        </button>

                        <div class="space-y-2.5">
                            <span class="text-[10px] font-black uppercase tracking-widest text-rose-700/60 block mb-1">Additional ZIP Archives:</span>
                            
                            <button type="button" onclick="runPrintRecordAction('docs_zip')" class="w-full flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-rose-50/50 hover:text-rose-900 hover:border-rose-200 transition cursor-pointer text-left shadow-xs">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-rose-600/70"></i>
                                    <span>Requirements ZIP</span>
                                </span>
                                <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                            </button>

                            <button type="button" onclick="runPrintRecordAction('forms_jpg')" class="w-full flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-rose-50/50 hover:text-rose-900 hover:border-rose-200 transition cursor-pointer text-left shadow-xs">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-rose-600/70"></i>
                                    <span>Enrollment Forms ZIP</span>
                                </span>
                                <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                            </button>

                            <button type="button" onclick="runPrintRecordAction('id_cards')" class="w-full flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-rose-50/50 hover:text-rose-900 hover:border-rose-200 transition cursor-pointer text-left shadow-xs">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-rose-600/70"></i>
                                    <span>ID Cards ZIP</span>
                                </span>
                                <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                            </button>

                            <button type="button" onclick="runPrintRecordAction('docs_zip')" class="w-full flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-rose-50/50 hover:text-rose-900 hover:border-rose-200 transition cursor-pointer text-left shadow-xs">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-rose-600/70"></i>
                                    <span>Student Photos ZIP</span>
                                </span>
                                <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                            </button>

                            <button type="button" onclick="runPrintRecordAction('docs_zip')" class="w-full flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-rose-50/50 hover:text-rose-900 hover:border-rose-200 transition cursor-pointer text-left shadow-xs">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-rose-600/70"></i>
                                    <span>Report Cards ZIP</span>
                                </span>
                                <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                            </button>

                            <button type="button" onclick="runPrintRecordAction('docs_zip')" class="w-full flex items-center justify-between rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-rose-50/50 hover:text-rose-900 hover:border-rose-200 transition cursor-pointer text-left shadow-xs">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-rose-600/70"></i>
                                    <span>Certificates ZIP</span>
                                </span>
                                <i data-lucide="download" class="h-3.5 w-3.5 text-slate-400"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADDITIONAL REPORTS & UTILITIES -->
            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 mt-6">
                <span class="text-xs font-black uppercase tracking-wider text-slate-700 block mb-3">
                    Additional Reports & Utilities
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    <button type="button" onclick="runPrintRecordAction('credentials')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition cursor-pointer text-left">
                        <i data-lucide="key" class="h-4 w-4 text-amber-600"></i>
                        <span>Microsoft Credentials</span>
                    </button>
                    <button type="button" onclick="runPrintRecordAction('masters_list')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition cursor-pointer text-left">
                        <i data-lucide="list" class="h-4 w-4 text-blue-600"></i>
                        <span>Masters List PDF</span>
                    </button>
                    <button type="button" onclick="runPrintRecordAction('canva')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-emerald-800 hover:bg-emerald-50 transition cursor-pointer text-left">
                        <i data-lucide="sparkles" class="h-4 w-4 text-emerald-600"></i>
                        <span>Canva Bulk Create</span>
                    </button>
                </div>
            </div>
        </x-card>
    </div>

    <!-- ZIP PNG Loading Modal -->
    <div id="zip-loading-modal" class="fixed inset-0 z-[100000] hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300">
        <div class="relative w-full max-w-lg scale-95 transform rounded-2xl border border-slate-200/80 bg-white p-6 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 text-center">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 animate-spin text-violet-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">Generating Instant JPG ZIP Archive</h3>
                </div>
                <span id="zip-counter-badge" class="px-3 py-1 rounded-full text-xs font-black bg-violet-100 text-violet-800 dark:bg-violet-950/80 dark:text-violet-300">
                    0 / 0 Students
                </span>
            </div>

            <p id="zip-progress-text" class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-3 text-left">Loading student application forms... Please wait.</p>
            
            <div class="h-2 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden mb-4 border border-slate-200/50">
                <div id="zip-progress-bar" class="h-full rounded-full bg-violet-600 transition-all duration-200" style="width: 0%"></div>
            </div>

            <!-- Terminal Console Logs Box -->
            <div class="text-left">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-1">Live Processing Logs:</span>
                <div id="zip-logs-console" class="h-32 w-full rounded-xl bg-slate-950 p-3 font-mono text-[10px] text-emerald-400 overflow-y-auto leading-relaxed shadow-inner">
                    <!-- Logs injected here -->
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });

    function addZipLog(msg) {
        const consoleEl = document.getElementById('zip-logs-console');
        if (!consoleEl) return;
        const time = new Date().toLocaleTimeString('en-US', { hour12: false });
        const line = document.createElement('div');
        line.innerHTML = `<span class="text-slate-500">[${time}]</span> ${msg}`;
        consoleEl.appendChild(line);
        consoleEl.scrollTop = consoleEl.scrollHeight;
    }

    function downloadEnrolmentPngZip(url) {
        const modal = document.getElementById('zip-loading-modal');
        const bar = document.getElementById('zip-progress-bar');
        const text = document.getElementById('zip-progress-text');
        const badge = document.getElementById('zip-counter-badge');
        const consoleEl = document.getElementById('zip-logs-console');
        
        if (modal) modal.classList.remove('hidden');
        if (bar) bar.style.width = '3%';
        if (text) text.innerHTML = '<span class="font-bold text-violet-600">Step 1 of 3:</span> Connecting to server & fetching student database...';
        if (badge) badge.innerText = 'Initializing...';
        if (consoleEl) consoleEl.innerHTML = '';
        
        addZipLog('📡 Connecting to server and fetching student records...');

        const oldIframe = document.getElementById('zip-iframe');
        if (oldIframe) oldIframe.remove();
        
        const iframe = document.createElement('iframe');
        iframe.id = 'zip-iframe';
        iframe.style.position = 'absolute';
        iframe.style.left = '-9999px';
        iframe.style.top = '-9999px';
        iframe.style.width = '1200px';
        iframe.style.height = '1600px';
        iframe.style.border = 'none';
        iframe.style.pointerEvents = 'none';
        
        iframe.src = url + (url.includes('?') ? '&' : '?') + 'auto_zip_png=1';
        document.body.appendChild(iframe);
    }
    
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'zip_started') {
            const bar = document.getElementById('zip-progress-bar');
            const text = document.getElementById('zip-progress-text');
            if (bar) bar.style.width = '10%';
            if (text) text.innerHTML = '<span class="font-bold text-violet-600">Step 2 of 3:</span> Compiling templates & loading student photos...';
            addZipLog('⚙️ HTML2Canvas image engine initialized.');
        }
        if (event.data && (event.data.type === 'zip_progress' || event.data.type === 'zip_log')) {
            const bar = document.getElementById('zip-progress-bar');
            const text = document.getElementById('zip-progress-text');
            const badge = document.getElementById('zip-counter-badge');
            const scaledPercent = Math.round(10 + ((event.data.percent || 0) * 0.85));
            
            if (bar) bar.style.width = scaledPercent + '%';
            if (event.data.current && event.data.total) {
                if (badge) badge.innerText = `${event.data.current} / ${event.data.total} Students (${scaledPercent}%)`;
                if (text) text.innerHTML = `<span class="font-bold text-violet-600">Step 3 of 3:</span> Processing Student ${event.data.current} of ${event.data.total}...`;
            }
            if (event.data.message) {
                addZipLog(event.data.message);
            }
        }
        if (event.data && event.data.type === 'zip_done') {
            const bar = document.getElementById('zip-progress-bar');
            const text = document.getElementById('zip-progress-text');
            const badge = document.getElementById('zip-counter-badge');
            
            if (bar) bar.style.width = '100%';
            if (badge) badge.innerText = 'Completed 100%';
            if (text) text.innerHTML = '<span class="font-bold text-emerald-600">✔ ZIP Generated Successfully!</span> Download starting...';
            addZipLog(event.data.message || '⚡ ZIP Archive generated & download started!');
            
            setTimeout(() => {
                const modal = document.getElementById('zip-loading-modal');
                if (modal) modal.classList.add('hidden');
                const iframe = document.getElementById('zip-iframe');
                if (iframe) iframe.remove();
            }, 3500);
        }
    });

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
            downloadEnrolmentPngZip('{{ route('admin.students.print-enrolment-forms-batch') }}' + queryString);
        } else if (actionType === 'id_cards') {
            const idParams = new URLSearchParams(params);
            idParams.append('print_id', '1');
            idParams.append('is_print', '1');
            window.open('{{ route('admin.students.index') }}?' + idParams.toString(), '_blank');
        } else if (actionType === 'docs_zip') {
            window.location.href = '{{ route('admin.students.download-docs-zip') }}' + queryString;
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
            window.location.href = '{{ route('admin.students.export-canva') }}' + queryString;
        }
    }
    </script>
</x-admin-layout>
