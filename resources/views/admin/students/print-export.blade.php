<x-admin-layout
    title="Print & Export Student Records"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Print & Export', 'href' => null],
    ]"
>
    @php
        $currentGrade = $currentGrade ?? request('grade', '');
        $currentMode = $currentMode ?? request('mode', '');
        $currentGender = $currentGender ?? request('gender', '');
        $currentSearch = $currentSearch ?? request('search', '');
        
        $allGradeList = [
            'Kinder 1', 'Kinder 2',
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
        ];
    @endphp

    <div class="space-y-6">
        <!-- TOP EXECUTIVE HERO BANNER -->
        <section class="relative overflow-hidden rounded-3xl border border-emerald-800/40 bg-gradient-to-br from-emerald-900 via-teal-950 to-slate-950 p-6 md:p-8 text-white shadow-xl shadow-slate-950/20">
            <!-- Background Decorative Glow -->
            <div class="pointer-events-none absolute -right-16 -top-16 h-72 w-72 rounded-full bg-emerald-500/15 blur-3xl"></div>
            <div class="pointer-events-none absolute -left-16 -bottom-16 h-72 w-72 rounded-full bg-teal-500/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-[11px] font-black uppercase tracking-widest text-emerald-300 border border-emerald-400/30 backdrop-blur-sm">
                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            Official Documents Hub
                        </span>
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-bold text-slate-200">
                            SY 2026–2027
                        </span>
                        @if($currentGrade)
                            <span class="rounded-full bg-emerald-400 text-slate-950 px-2.5 py-0.5 text-[11px] font-black uppercase tracking-wider">
                                Filter: {{ $currentGrade }}
                            </span>
                        @endif
                    </div>
                    
                    <h1 class="mt-3 text-2xl md:text-3xl font-black tracking-tight text-white">
                        Student Records Print & Export Center
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-xs md:text-sm font-medium leading-relaxed text-emerald-100/80">
                        Generate official approved enrollment application forms, batch ID cards, student requirement archives, and DepEd registries with instant sub-second bundling.
                    </p>
                </div>

                <!-- Live Quick Stats Counter -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-md px-4 py-3 text-center min-w-[100px]">
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-emerald-300">Target Records</span>
                        <span class="text-2xl font-black text-white">{{ number_format($totalStudents) }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-md px-4 py-3 text-center min-w-[90px]">
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-emerald-200">F2F Mode</span>
                        <span class="text-xl font-black text-white">{{ number_format($f2fCount ?? 0) }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur-md px-4 py-3 text-center min-w-[90px]">
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-teal-200">ODL Mode</span>
                        <span class="text-xl font-black text-white">{{ number_format($odlCount ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- GRADE LEVEL QUICK SWITCHER RIBBON -->
        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between px-2 pb-2 mb-1 border-b border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <i data-lucide="graduation-cap" class="h-4 w-4 text-emerald-600"></i>
                    Select Grade Level Scope
                </span>
                <span class="text-[10px] font-bold text-slate-500">
                    Click to filter instantly
                </span>
            </div>

            <!-- Grade Pills List -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 pt-1 no-scrollbar">
                @php
                    $allGradesUrl = request()->fullUrlWithQuery(['grade' => null, 'page' => null]);
                @endphp
                <a href="{{ $allGradesUrl }}" 
                   class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-black transition whitespace-nowrap cursor-pointer {{ empty($currentGrade) ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    <span>All Grades</span>
                    <span class="rounded-md px-1.5 py-0.2 text-[10px] {{ empty($currentGrade) ? 'bg-emerald-800 text-emerald-100' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                        {{ array_sum($gradeCounts ?? []) }}
                    </span>
                </a>

                @foreach($allGradeList as $g)
                    @php
                        $gCount = $gradeCounts[$g] ?? 0;
                        $gShort = \App\Models\Student::abbreviateGrade($g);
                        $isActive = ($currentGrade === $g);
                        $gUrl = request()->fullUrlWithQuery(['grade' => $g, 'page' => null]);
                    @endphp
                    <a href="{{ $gUrl }}" 
                       class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-black transition whitespace-nowrap cursor-pointer {{ $isActive ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-500/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        <span>{{ $gShort }}</span>
                        <span class="text-[10px] font-bold opacity-80">({{ $g }})</span>
                        <span class="rounded-md px-1.5 py-0.2 text-[10px] {{ $isActive ? 'bg-emerald-800 text-emerald-100' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                            {{ $gCount }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- FILTER TOOLBAR -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900">
            <form method="GET" action="{{ route('admin.students.print-export') }}" id="filter-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Preserve Grade -->
                <input type="hidden" name="grade" id="p-filter-grade" value="{{ $currentGrade }}">

                <!-- Search Input -->
                <div class="relative">
                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1">Search Student</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                        <input type="text" 
                               name="search" 
                               id="p-filter-search"
                               value="{{ $currentSearch }}" 
                               placeholder="Name, Student ID, or LRN..." 
                               class="w-full h-10 pl-9 pr-3 rounded-xl border border-slate-200 bg-slate-50/50 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 transition dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                </div>

                <!-- Mode Filter -->
                <div>
                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1">Learning Mode</label>
                    <select name="mode" id="p-filter-mode" onchange="document.getElementById('filter-form').submit()" class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="" {{ $currentMode === '' ? 'selected' : '' }}>All Modes (F2F & ODL)</option>
                        <option value="F2F" {{ $currentMode === 'F2F' ? 'selected' : '' }}>Face to Face (F2F)</option>
                        <option value="ODL" {{ $currentMode === 'ODL' ? 'selected' : '' }}>Online Distance Learning (ODL)</option>
                    </select>
                </div>

                <!-- Gender Filter -->
                <div>
                    <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500 mb-1">Gender</label>
                    <select name="gender" id="p-filter-gender" onchange="document.getElementById('filter-form').submit()" class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="" {{ $currentGender === '' ? 'selected' : '' }}>All Genders</option>
                        <option value="male" {{ $currentGender === 'male' ? 'selected' : '' }}>Male Only</option>
                        <option value="female" {{ $currentGender === 'female' ? 'selected' : '' }}>Female Only</option>
                    </select>
                </div>

                <!-- Submit / Clear Actions -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 h-10 rounded-xl bg-emerald-600 hover:bg-emerald-700 font-black text-xs text-white transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                        <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                        <span>Apply</span>
                    </button>
                    @if($currentGrade || $currentMode || $currentGender || $currentSearch)
                        <a href="{{ route('admin.students.print-export') }}" class="h-10 px-3 rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 font-bold text-xs text-slate-600 transition flex items-center justify-center gap-1 cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                            <span class="hidden sm:inline">Reset</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- 4 ACTION CENTER CARDS (STRUCTURED & HIGH-VALUE) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- CARD 1: OFFICIAL ENROLLMENT FORMS -->
            <div class="rounded-3xl border-2 border-emerald-500/30 bg-gradient-to-b from-emerald-50/60 via-white to-white p-6 shadow-sm transition hover:shadow-md dark:border-emerald-500/20 dark:from-emerald-950/20 dark:via-slate-900 dark:to-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                                <i data-lucide="file-text" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">
                                    Enrollment Application Forms
                                </h3>
                                <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-emerald-700 dark:text-emerald-400">
                                    <i data-lucide="check-circle" class="h-3 w-3"></i>
                                    Official 2-Page Snapshot
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium leading-relaxed mb-5">
                        Compile approved official forms featuring genuine Islamic calligraphy, frozen student data, LRN, parent details, and signature declarations.
                    </p>
                </div>

                <div class="space-y-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <!-- Primary Batch Print Button -->
                    <button type="button" 
                            onclick="runPrintRecordAction('forms_batch')" 
                            class="w-full h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 font-black text-xs text-white transition flex items-center justify-center gap-2 shadow-sm shadow-emerald-600/10 cursor-pointer active:scale-[0.99]">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        <span>Print Batch ({{ number_format($totalStudents) }} Students)</span>
                    </button>

                    <!-- Instant ZIP Download Button -->
                    <button type="button" 
                            onclick="openBatchExportModal('enrollment_forms')" 
                            class="w-full h-10 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 font-extrabold text-xs text-emerald-900 transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99] dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <i data-lucide="folder-archive" class="h-4 w-4 text-emerald-600"></i>
                        <span>Download ZIP Package (PDF / DOCX)</span>
                    </button>
                </div>
            </div>

            <!-- CARD 2: STUDENT DOCUMENT DOSSIERS -->
            <div class="rounded-3xl border-2 border-sky-500/30 bg-gradient-to-b from-sky-50/60 via-white to-white p-6 shadow-sm transition hover:shadow-md dark:border-sky-500/20 dark:from-sky-950/20 dark:via-slate-900 dark:to-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-600 text-white shadow-md shadow-sky-600/20">
                                <i data-lucide="folder-archive" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">
                                    Student Document Dossiers
                                </h3>
                                <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-sky-700 dark:text-sky-400">
                                    <i data-lucide="layers" class="h-3 w-3"></i>
                                    Full File Archives
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium leading-relaxed mb-5">
                        Download complete student folders containing 2x2 photos, PSA birth certificates, Form 138 report cards, marriage contracts, and official payment receipts.
                    </p>
                </div>

                <div class="space-y-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <!-- Download Requirements ZIP -->
                    <button type="button" 
                            onclick="runPrintRecordAction('docs_zip')" 
                            class="w-full h-11 rounded-xl bg-sky-600 hover:bg-sky-700 font-black text-xs text-white transition flex items-center justify-center gap-2 shadow-sm shadow-sky-600/10 cursor-pointer active:scale-[0.99]">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        <span>Download Requirements ZIP</span>
                    </button>

                    <!-- Download Master Registry List -->
                    <button type="button" 
                            onclick="runPrintRecordAction('masters_list')" 
                            class="w-full h-10 rounded-xl border border-sky-200 bg-sky-50 hover:bg-sky-100 font-extrabold text-xs text-sky-900 transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99] dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-300">
                        <i data-lucide="list" class="h-4 w-4 text-sky-600"></i>
                        <span>Export Student Master Registry</span>
                    </button>
                </div>
            </div>

            <!-- CARD 3: IDENTIFICATION & CREDENTIALS -->
            <div class="rounded-3xl border-2 border-amber-500/30 bg-gradient-to-b from-amber-50/60 via-white to-white p-6 shadow-sm transition hover:shadow-md dark:border-amber-500/20 dark:from-amber-950/20 dark:via-slate-900 dark:to-slate-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-600 text-white shadow-md shadow-amber-600/20">
                                <i data-lucide="credit-card" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">
                                    ID Cards & Credentials
                                </h3>
                                <span class="inline-flex items-center gap-1 text-[11px] font-extrabold text-amber-700 dark:text-amber-400">
                                    <i data-lucide="key" class="h-3 w-3"></i>
                                    Printable Cards & Slips
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 font-medium leading-relaxed mb-5">
                        Print grid sheets of front & back official student ID cards, Microsoft 365 student account login credential slips, and Canva bulk design CSVs.
                    </p>
                </div>

                <div class="space-y-2.5 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <!-- Print ID Sheet -->
                    <button type="button" 
                            onclick="runPrintRecordAction('id_cards_grade')" 
                            class="w-full h-11 rounded-xl bg-amber-600 hover:bg-amber-700 font-black text-xs text-white transition flex items-center justify-center gap-2 shadow-sm shadow-amber-600/10 cursor-pointer active:scale-[0.99]">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        <span>Print ID Cards Sheet</span>
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        <!-- Microsoft Credentials -->
                        <button type="button" 
                                onclick="runPrintRecordAction('credentials')" 
                                class="h-10 rounded-xl border border-amber-200 bg-amber-50 hover:bg-amber-100 font-extrabold text-[11px] text-amber-900 transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-[0.99] dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                            <i data-lucide="key" class="h-3.5 w-3.5 text-amber-600"></i>
                            <span>MS Credentials</span>
                        </button>

                        <!-- Canva CSV Export -->
                        <button type="button" 
                                onclick="runPrintRecordAction('canva')" 
                                class="h-10 rounded-xl border border-amber-200 bg-amber-50 hover:bg-amber-100 font-extrabold text-[11px] text-amber-900 transition flex items-center justify-center gap-1.5 cursor-pointer active:scale-[0.99] dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                            <i data-lucide="sparkles" class="h-3.5 w-3.5 text-amber-600"></i>
                            <span>Canva CSV</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- TARGET STUDENTS PREVIEW REGISTRY TABLE -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-xs overflow-hidden dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/50 p-5 dark:border-slate-800 dark:bg-slate-800/40">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">
                            Target Student Registry Preview
                        </h3>
                        <p class="text-[11px] font-semibold text-slate-500">
                            Showing students matching active scope: <strong class="text-emerald-700 dark:text-emerald-400">{{ $currentGrade ?: 'All Grades' }}</strong>
                            @if($currentMode) • <strong>{{ $currentMode }}</strong> @endif
                            ({{ number_format($totalStudents) }} Total)
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            onclick="runPrintRecordAction('forms_batch')" 
                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3.5 py-2 text-xs font-black text-white transition shadow-xs cursor-pointer">
                        <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                        <span>Print All in Scope</span>
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-100/60 text-[10px] font-black uppercase tracking-wider text-slate-500 border-b border-slate-100 dark:bg-slate-800/60 dark:border-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3.5">Student Information</th>
                            <th class="px-4 py-3.5">Grade & Section</th>
                            <th class="px-4 py-3.5">Learning Mode</th>
                            <th class="px-4 py-3.5">Gender</th>
                            <th class="px-4 py-3.5 text-center">Official Form Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($previewStudents as $student)
                            @php
                                $appl = $student->applicant;
                                $lMode = strtolower($appl->learning_mode ?? '');
                                $isF2f = str_contains($lMode, 'face') || str_contains($lMode, 'f2f');
                                $doc = $student->officialEnrollmentForm;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition dark:hover:bg-slate-800/50">
                                <!-- Student Name & ID -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0 dark:bg-slate-800 dark:border-slate-700">
                                            @if($student->student_id_url || $appl?->photo_2x2_url)
                                                <img src="{{ \App\Support\EnrollmentStorage::url($student->student_id_url ?: $appl->photo_2x2_url) }}" alt="Student Photo" class="h-full w-full object-cover">
                                            @else
                                                <i data-lucide="user" class="h-4 w-4 text-slate-400"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.students.show', $student) }}" class="font-extrabold text-slate-900 hover:text-emerald-600 transition dark:text-white">
                                                {{ mb_strtoupper(($appl?->last_name ?? 'STUDENT') . ', ' . ($appl?->first_name ?? '') . (($m = \App\Models\EnrollmentApplicant::formatMiddleInitial($appl?->middle_name)) ? ' ' . $m : '') . ($appl?->suffix ? ' ' . $appl->suffix : '')) }}
                                            </a>
                                            <div class="flex items-center gap-2 text-[10px] text-slate-500 font-bold">
                                                <span class="text-emerald-700 dark:text-emerald-400">#{{ $student->student_number }}</span>
                                                @if($appl?->lrn)
                                                    <span>• LRN: {{ $appl->lrn }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Grade Level -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="font-extrabold text-slate-800 dark:text-slate-200">{{ $student->grade_level }}</span>
                                    <span class="block text-[10px] text-slate-500 font-semibold">{{ $student->studentSection?->section?->name ?: 'Unassigned Section' }}</span>
                                </td>

                                <!-- Learning Mode -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    @if($isF2f)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-[10px] font-black text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                                            <i data-lucide="school" class="h-3 w-3"></i>
                                            Face to Face
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-1 text-[10px] font-black text-sky-800 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800">
                                            <i data-lucide="laptop" class="h-3 w-3"></i>
                                            Online (ODL)
                                        </span>
                                    @endif
                                </td>

                                <!-- Gender -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="text-xs font-bold uppercase text-slate-700 dark:text-slate-300">
                                        {{ $appl?->gender ?: 'N/A' }}
                                    </span>
                                </td>

                                <!-- Official Form Status -->
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if($doc)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black text-emerald-800 border border-emerald-200 dark:bg-emerald-900/50 dark:text-emerald-300 dark:border-emerald-700">
                                            <i data-lucide="check" class="h-3 w-3"></i>
                                            Official PDF Ready (v{{ $doc->document_version }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-black text-amber-800 border border-amber-200 dark:bg-amber-900/50 dark:text-amber-300 dark:border-amber-700">
                                            <i data-lucide="clock" class="h-3 w-3"></i>
                                            Pending First Render
                                        </span>
                                    @endif
                                </td>

                                <!-- Row Actions -->
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- View / Print Form -->
                                        <a href="{{ route('admin.students.print-enrolment-form', $student) }}" 
                                           target="_blank" 
                                           class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 text-slate-600 transition cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" 
                                           title="Print Enrolment Form">
                                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                        </a>

                                        @if($doc)
                                            <!-- Download Official PDF -->
                                            <a href="{{ route('admin.students.official-enrollment-form.download', $student) }}" 
                                               class="p-1.5 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 transition cursor-pointer dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300" 
                                               title="Download Official PDF">
                                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                            </a>
                                        @endif

                                        <!-- Student Profile Link -->
                                        <a href="{{ route('admin.students.show', $student) }}" 
                                           class="p-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 transition cursor-pointer dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300" 
                                           title="View Student Profile">
                                            <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="p-3 rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">
                                            <i data-lucide="users-round" class="h-6 w-6"></i>
                                        </div>
                                        <p class="text-xs font-bold">No student records match the active filters.</p>
                                        <a href="{{ route('admin.students.print-export') }}" class="text-xs font-black text-emerald-600 hover:underline">Clear all filters</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if($previewStudents->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                    {{ $previewStudents->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- BACKGROUND BATCH EXPORT MODAL -->
    <div id="batch-export-modal" class="fixed inset-0 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300" style="z-index: 9999999 !important;">
        <div class="relative w-full max-w-lg scale-95 transform rounded-3xl border border-slate-200/80 bg-white p-6 md:p-8 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
            
            <!-- CLOSE BUTTON -->
            <button type="button" onclick="closeBatchExportModal()" class="absolute right-5 top-5 rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 transition cursor-pointer dark:bg-slate-800 dark:text-slate-400">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>

            <!-- STATE 1: CONFIGURATION -->
            <div id="export-state-config" class="space-y-5">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4 dark:border-slate-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <i data-lucide="folder-archive" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">Batch Export Enrollment Forms</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Fast server-side stream packaging</p>
                    </div>
                </div>

                <!-- Format Selection -->
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2.5">Select Export Format</label>
                    <div class="grid grid-cols-2 gap-3" id="export-format-selector">
                        <!-- PDF Card (Default) -->
                        <div onclick="selectExportFormat('pdf')" id="format-card-pdf" class="flex items-center justify-between p-3.5 rounded-2xl border-2 border-emerald-600 bg-emerald-50/30 text-slate-800 cursor-pointer transition hover:border-emerald-600 dark:bg-emerald-950/20 dark:text-white">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center dark:bg-rose-950/50 dark:text-rose-400">
                                    <i data-lucide="file-text" class="h-4 w-4"></i>
                                </span>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs font-black uppercase tracking-wide">PDF ZIP</span>
                                    <span class="text-[10px] text-slate-500 font-semibold">Official PDFs</span>
                                </div>
                            </div>
                            <div class="w-4 h-4 rounded-full border-4 border-emerald-600 flex items-center justify-center bg-white" id="format-radio-pdf">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div>
                            </div>
                        </div>

                        <!-- DOCX Card -->
                        <div onclick="selectExportFormat('docx')" id="format-card-docx" class="flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 bg-white text-slate-800 cursor-pointer transition hover:border-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <div class="flex items-center gap-2.5">
                                <span class="p-2 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center dark:bg-indigo-950/50 dark:text-indigo-400">
                                    <i data-lucide="file-edit" class="h-4 w-4"></i>
                                </span>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs font-black uppercase tracking-wide">DOCX ZIP</span>
                                    <span class="text-[10px] text-slate-500 font-semibold">Editable Word</span>
                                </div>
                            </div>
                            <div class="w-4 h-4 rounded-full border border-slate-300 flex items-center justify-center bg-white" id="format-radio-docx"></div>
                        </div>
                    </div>
                </div>

                <!-- Active Target Scope Indicator -->
                <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100 flex items-center justify-between dark:border-slate-800 dark:bg-slate-800/50">
                    <span class="text-xs font-extrabold text-slate-700 flex items-center gap-2 dark:text-slate-300">
                        <i data-lucide="filter" class="w-4 h-4 text-emerald-600"></i>
                        <span>Target Scope:</span>
                    </span>
                    <span id="modal-active-scope-badge" class="text-xs font-black text-emerald-800 bg-emerald-100 px-3 py-1 rounded-xl dark:bg-emerald-950 dark:text-emerald-300">
                        {{ $currentGrade ?: 'All Grade Levels' }} ({{ number_format($totalStudents) }} Students)
                    </span>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-2.5 pt-2">
                    <button type="button" onclick="startBackgroundExport()" class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-5 text-xs font-black text-white shadow-md shadow-emerald-600/20 transition active:scale-[0.99] cursor-pointer">
                        <i data-lucide="play" class="h-4 w-4"></i>
                        <span>Start Batch Export</span>
                    </button>
                    
                    <button type="button" onclick="runPrintRecordAction('forms_batch')" class="w-full h-10 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-4 text-xs font-bold text-slate-700 transition cursor-pointer dark:bg-slate-800 dark:text-slate-300">
                        <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                        <span>Open Batch Print View Instead</span>
                    </button>
                </div>
            </div>

            <!-- STATE 2: EXPORT IN PROGRESS -->
            <div id="export-state-progress" class="hidden space-y-5 text-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 animate-bounce dark:bg-emerald-950 dark:text-emerald-400">
                        <i data-lucide="rocket" class="h-7 w-7"></i>
                    </div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white">Compiling ZIP Archive</h3>
                    <p id="export-status-label" class="text-xs font-bold text-slate-600 dark:text-slate-400">Packaging official PDFs...</p>
                </div>

                <!-- Progress Bar -->
                <div class="h-3 w-full rounded-full bg-slate-100 overflow-hidden border border-slate-200/50 dark:bg-slate-800">
                    <div id="export-progress-bar" class="h-full rounded-full bg-emerald-600 transition-all duration-300" style="width: 0%"></div>
                </div>

                <div class="flex items-center justify-between text-xs font-bold text-slate-600 px-1 dark:text-slate-400">
                    <span id="export-percentage">0%</span>
                    <span id="export-processed-counter">0 / {{ $totalStudents }} Students</span>
                </div>

                <div class="rounded-2xl bg-slate-50 p-3 text-left space-y-1.5 text-xs text-slate-500 font-semibold border border-slate-100 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex justify-between">
                        <span>Estimated Remaining:</span>
                        <span id="export-remaining-time" class="text-slate-900 font-black dark:text-white">Calculating...</span>
                    </div>
                </div>

                <!-- Live Terminal Log Console -->
                <div class="rounded-2xl border border-slate-800 bg-slate-950 p-4 text-left shadow-inner">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2 mb-2">
                        <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Live Console Stream
                        </span>
                        <span class="text-[9px] font-mono text-slate-500">Real-time log</span>
                    </div>
                    <div id="export-log-lines" class="h-28 overflow-y-auto space-y-1 font-mono text-[11px] text-slate-300 pr-1">
                        <div class="text-slate-500">[System] Export task initiated...</div>
                    </div>
                </div>

                <button type="button" onclick="hideExportModal()" class="w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-4 text-xs font-bold text-slate-700 transition cursor-pointer dark:bg-slate-800 dark:text-slate-300">
                    <i data-lucide="eye-off" class="h-4 w-4"></i>
                    <span>Minimize (Continue Working in Background)</span>
                </button>
            </div>

            <!-- STATE 3: EXPORT COMPLETE -->
            <div id="export-state-complete" class="hidden space-y-5 text-center">
                <div class="flex flex-col items-center gap-2">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-100 text-emerald-700 shadow-md dark:bg-emerald-950 dark:text-emerald-400">
                        <i data-lucide="check-circle-2" class="h-9 w-9"></i>
                    </div>
                    <h3 class="text-base font-black text-emerald-800 uppercase tracking-wide dark:text-emerald-400">ZIP Package Ready!</h3>
                    <p class="text-xs text-slate-500 font-medium">Archive compiled successfully in record time.</p>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-left text-xs font-semibold text-slate-600 space-y-2 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
                    <div class="flex justify-between">
                        <span>Documents Packaged:</span>
                        <span class="text-slate-900 font-black dark:text-white">{{ number_format($totalStudents) }} Students</span>
                    </div>
                    <div class="flex justify-between">
                        <span id="export-complete-size-label">File Size:</span>
                        <span id="export-complete-size-val" class="text-slate-900 font-black dark:text-white">--</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Filename:</span>
                        <span id="export-complete-filename-val" class="text-slate-900 font-mono font-bold dark:text-white">export_archive.zip</span>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <button type="button" onclick="triggerActualZipDownload()" class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-5 text-xs font-black text-white shadow-md shadow-emerald-600/20 transition active:scale-[0.99] cursor-pointer">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        <span>Download Completed ZIP Archive</span>
                    </button>
                    <button type="button" onclick="closeBatchExportModal()" class="w-full h-10 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 hover:bg-slate-200 px-4 text-xs font-bold text-slate-700 transition cursor-pointer dark:bg-slate-800 dark:text-slate-300">
                        <span>Close</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- FLOATING BACKGROUND EXPORT INDICATOR -->
    <div id="export-floating-indicator" class="fixed bottom-6 right-6 hidden z-50 transform transition-all duration-300">
        <div class="flex items-center gap-3.5 rounded-2xl border border-slate-700/60 bg-slate-900/95 px-4 py-3 text-white shadow-2xl backdrop-blur-md cursor-pointer hover:border-emerald-500 transition" onclick="showExportModal()">
            <div class="relative flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-600 text-white">
                <i data-lucide="folder-archive" class="h-4 w-4 animate-spin"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Export in Progress</span>
                    <span id="export-floating-percentage" class="text-xs font-bold text-white">0%</span>
                </div>
                <div class="mt-1 h-1.5 w-36 rounded-full bg-slate-800 overflow-hidden">
                    <div id="export-floating-progress-bar" class="h-full rounded-full bg-emerald-500 transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC & HANDLERS -->
    <script>
        let selectedFormat = 'pdf';
        let currentExportId = null;
        let exportInterval = null;
        let isExportRunning = false;
        let exportPercent = 0;
        let currentDownloadUrl = null;
        let lastLogMessage = '';
        const totalStudents = {{ (int) $totalStudents }};

        function selectExportFormat(fmt) {
            selectedFormat = fmt;
            const pdfCard = document.getElementById('format-card-pdf');
            const docxCard = document.getElementById('format-card-docx');
            const pdfRadio = document.getElementById('format-radio-pdf');
            const docxRadio = document.getElementById('format-radio-docx');

            if (fmt === 'pdf') {
                if (pdfCard) pdfCard.className = 'flex items-center justify-between p-3.5 rounded-2xl border-2 border-emerald-600 bg-emerald-50/30 text-slate-800 cursor-pointer transition dark:bg-emerald-950/20 dark:text-white';
                if (docxCard) docxCard.className = 'flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 bg-white text-slate-800 cursor-pointer transition hover:border-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white';
                if (pdfRadio) pdfRadio.innerHTML = '<div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div>';
                if (docxRadio) docxRadio.innerHTML = '';
            } else {
                if (pdfCard) pdfCard.className = 'flex items-center justify-between p-3.5 rounded-2xl border border-slate-200 bg-white text-slate-800 cursor-pointer transition hover:border-emerald-600 dark:border-slate-700 dark:bg-slate-800 dark:text-white';
                if (docxCard) docxCard.className = 'flex items-center justify-between p-3.5 rounded-2xl border-2 border-emerald-600 bg-emerald-50/30 text-slate-800 cursor-pointer transition dark:bg-emerald-950/20 dark:text-white';
                if (pdfRadio) pdfRadio.innerHTML = '';
                if (docxRadio) docxRadio.innerHTML = '<div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div>';
            }
        }

        function openBatchExportModal(actionType) {
            const modal = document.getElementById('batch-export-modal');
            if (!modal) return;

            if (!isExportRunning) {
                document.getElementById('export-state-config').classList.remove('hidden');
                document.getElementById('export-state-progress').classList.add('hidden');
                document.getElementById('export-state-complete').classList.remove('hidden');
                document.getElementById('export-state-complete').classList.add('hidden');
            }

            modal.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeBatchExportModal() {
            const modal = document.getElementById('batch-export-modal');
            if (modal) modal.classList.add('hidden');
        }

        function showExportModal() {
            const modal = document.getElementById('batch-export-modal');
            if (modal) modal.classList.remove('hidden');
        }

        function hideExportModal() {
            closeBatchExportModal();
            if (isExportRunning) {
                const indicator = document.getElementById('export-floating-indicator');
                if (indicator) indicator.classList.remove('hidden');
            }
        }

        function appendConsoleLog(msg, isError = false) {
            if (!msg || msg === lastLogMessage) return;
            lastLogMessage = msg;

            const logBox = document.getElementById('export-log-lines');
            if (!logBox) return;

            const time = new Date().toLocaleTimeString();
            const logLine = document.createElement('div');
            logLine.className = isError ? 'text-rose-400 font-bold' : 'text-slate-300';
            logLine.innerHTML = `<span class="text-slate-500 font-mono">[${time}]</span> ${msg}`;
            logBox.appendChild(logLine);
            logBox.scrollTop = logBox.scrollHeight;
        }

        async function startBackgroundExport() {
            const mode = document.getElementById('p-filter-mode')?.value || '';
            const grade = document.getElementById('p-filter-grade')?.value || '';
            const gender = document.getElementById('p-filter-gender')?.value || '';
            const search = document.getElementById('p-filter-search')?.value || '';

            isExportRunning = true;
            exportPercent = 0;
            currentDownloadUrl = null;
            lastLogMessage = '';

            const logBox = document.getElementById('export-log-lines');
            if (logBox) {
                logBox.innerHTML = '<div class="text-slate-500">[System] Export task initiated...</div>';
            }

            document.getElementById('export-state-config').classList.add('hidden');
            document.getElementById('export-state-progress').classList.remove('hidden');

            const bar = document.getElementById('export-progress-bar');
            const percentageText = document.getElementById('export-percentage');
            const counterText = document.getElementById('export-processed-counter');
            const statusLabel = document.getElementById('export-status-label');
            const remainingTimeText = document.getElementById('export-remaining-time');
            const floatBar = document.getElementById('export-floating-progress-bar');
            const floatPercentage = document.getElementById('export-floating-percentage');

            if (statusLabel) statusLabel.innerText = 'Initiating server stream packaging...';
            appendConsoleLog(`Starting export for ${totalStudents} students (Format: ${selectedFormat.toUpperCase()})...`);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch('{{ route('admin.students.start-batch-export') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        format: selectedFormat,
                        grade: grade,
                        mode: mode,
                        gender: gender,
                        search: search,
                    }),
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    appendConsoleLog(result.message || 'Failed to start document export.', true);
                    alert(result.message || 'Failed to start document export.');
                    closeBatchExportModal();
                    return;
                }

                currentExportId = result.export_id;
                appendConsoleLog(`Background job #${currentExportId} started on server.`);

                if (exportInterval) clearInterval(exportInterval);

                // Poll progress every 1 second
                exportInterval = setInterval(async () => {
                    try {
                        const statusRes = await fetch("{{ url('/students/export-status') }}/" + currentExportId);
                        const data = await statusRes.json();

                        exportPercent = data.progress_percentage || 0;

                        if (bar) bar.style.width = exportPercent + '%';
                        if (floatBar) floatBar.style.width = exportPercent + '%';
                        if (percentageText) percentageText.innerText = exportPercent + '%';
                        if (floatPercentage) floatPercentage.innerText = exportPercent + '%';

                        if (counterText) {
                            counterText.innerText = `${(data.processed_count || 0).toLocaleString()} / ${(data.total_count || 0).toLocaleString()} Students`;
                        }

                        if (data.log_message) {
                            appendConsoleLog(data.log_message);
                        }

                        if (data.status === 'processing' || data.status === 'pending') {
                            if (statusLabel) statusLabel.innerText = data.status === 'pending' ? 'Preparing Queue...' : 'Packaging Documents...';
                            const processed = data.processed_count || 0;
                            const remainingSecs = processed > 0 ? Math.max(1, Math.round(((data.total_count - processed) / processed) * 2)) : Math.round(data.total_count * 1.5);
                            if (remainingTimeText) remainingTimeText.innerText = `~${remainingSecs} seconds`;
                        } else if (data.status === 'completed') {
                            clearInterval(exportInterval);
                            isExportRunning = false;
                            currentDownloadUrl = data.download_url;

                            appendConsoleLog(`Export job completed! Output ZIP ready for download.`);

                            const sizeVal = document.getElementById('export-complete-size-val');
                            const filenameVal = document.getElementById('export-complete-filename-val');

                            if (sizeVal) sizeVal.textContent = data.file_size_formatted || '0 B';
                            if (filenameVal) filenameVal.textContent = data.file_name || 'export_archive.zip';

                            document.getElementById('export-state-progress').classList.add('hidden');
                            document.getElementById('export-state-complete').classList.remove('hidden');

                            const indicator = document.getElementById('export-floating-indicator');
                            if (indicator) indicator.classList.add('hidden');
                        } else if (data.status === 'failed') {
                            clearInterval(exportInterval);
                            isExportRunning = false;
                            appendConsoleLog('Export failed: ' + (data.error_message || 'Unknown error occurred.'), true);
                            alert('Export failed: ' + (data.error_message || 'Unknown error occurred.'));
                            closeBatchExportModal();
                        }
                    } catch (err) {
                        console.error('Polling error:', err);
                    }
                }, 1000);

            } catch (err) {
                console.error('Failed to start export job:', err);
                alert('Failed to connect to export server.');
                closeBatchExportModal();
            }
        }

        function triggerActualZipDownload() {
            if (currentDownloadUrl) {
                window.location.href = currentDownloadUrl;
            } else {
                const mode = document.getElementById('p-filter-mode')?.value || '';
                const grade = document.getElementById('p-filter-grade')?.value || '';
                const gender = document.getElementById('p-filter-gender')?.value || '';
                const search = document.getElementById('p-filter-search')?.value || '';

                const params = new URLSearchParams();
                if (mode) params.append('mode', mode);
                if (grade) params.append('grade', grade);
                if (gender) params.append('gender', gender);
                if (search) params.append('search', search);
                if (selectedFormat) params.append('format', selectedFormat);

                const queryString = params.toString() ? '?' + params.toString() : '';
                window.location.href = '{{ route('admin.students.download-enrolment-forms-zip') }}' + queryString;
            }
            closeBatchExportModal();
        }

        function runPrintRecordAction(actionType) {
            const mode = document.getElementById('p-filter-mode')?.value || '';
            const grade = document.getElementById('p-filter-grade')?.value || '';
            const gender = document.getElementById('p-filter-gender')?.value || '';
            const search = document.getElementById('p-filter-search')?.value || '';

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
            } else if (actionType === 'id_cards' || actionType === 'id_cards_grade') {
                const gradeVal = grade || 'Kinder 1';
                window.open('/students/occupancy/grade/' + encodeURIComponent(gradeVal) + '/id-print', '_blank');
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
