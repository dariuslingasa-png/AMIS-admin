@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
    $sort = request('sort', 'latest');
    $direction = request('direction', $sort === 'name' ? 'asc' : 'desc') === 'asc' ? 'asc' : 'desc';
    $sortUrl = fn ($key) => route('admin.students.index', array_merge(request()->except('page'), [
        'sort' => $key,
        'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
    ]));
    $sortIcon = fn ($key) => $sort !== $key ? 'arrow-up-down' : ($direction === 'asc' ? 'arrow-up' : 'arrow-down');
    $gradeOrder = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
@endphp

@if ($isPrint)
    @include('admin.students.partials.index.print')
@else
<x-admin-layout
    title="Student Records"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Student Records', 'href' => null],
    ]"
>
    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Section Header -->
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Students Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Student Records</h1>
                <p class="mt-1 text-sm text-slate-500">View enrolled student accounts, credentials, and synchronized teams channels. <span class="font-semibold text-slate-700">({{ number_format($analytics['filtered_total'] ?? $students->total()) }} of {{ number_format($stats['total_students'] ?? 0) }} total)</span></p>
            </div>
            <div class="flex items-center gap-2 print:hidden">
                <form method="POST" action="{{ route('admin.ms-sync.sync-all-licenses') }}" onsubmit="return confirm('Sync and assign licenses for the currently filtered student list? This may take a few minutes.')" class="inline-block">
                    @csrf
                    @foreach (request()->only(['search', 'grade', 'type', 'gender', 'mode', 'ms_status']) as $key => $value)
                        @if (filled($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 text-xs font-bold text-emerald-800 transition hover:bg-emerald-100 cursor-pointer whitespace-nowrap shadow-sm">
                        <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
                        <span>{{ request()->hasAny(['search', 'grade', 'type', 'gender', 'mode', 'ms_status']) ? 'Sync Filtered Licenses' : 'Sync Pending Licenses' }}</span>
                    </button>
                </form>
                <!-- Print Records Modal Trigger Button -->
                <button type="button" onclick="openPrintRecordsModal()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 px-4 text-xs font-black text-white shadow-sm transition cursor-pointer whitespace-nowrap">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span>Print Records</span>
                </button>
            </div>
        </div>

        <!-- Hidden Print Header -->
        <div class="hidden print:block mb-6 text-center border-b-2 border-slate-350 pb-4">
            <h1 class="uppercase tracking-tight text-slate-900 font-bold" style="font-family: Arial, sans-serif; font-size: 14px;">STUDENT RECORDS MASTERS LIST</h1>
            <h2 class="uppercase tracking-wide text-slate-700 font-bold mt-1" style="font-family: Arial, sans-serif; font-size: 11px;">
                @if(request('grade'))
                    Grade Level: {{ request('grade') }}
                @else
                    All Grades
                @endif
                @if(request('type'))
                    | Type: {{ strtoupper(request('type')) }}
                @endif
                @if(request('mode'))
                    | Mode: {{ request('mode') }}
                @endif
            </h2>
            <div class="mt-3 flex justify-center gap-6 text-slate-500 font-normal" style="font-family: Arial, sans-serif; font-size: 9px;">
                <span>Total Filtered: {{ $students->total() }}</span>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Telemetry Summary -->
            @include('admin.students.partials.index.telemetry')

            <!-- Filter Bar Form -->
            @include('admin.students.partials.index.filters')

            <!-- Table of Enrollees -->
            @include('admin.students.partials.index.table')
        </div>
    </section>

    <!-- Print Records Master Modal -->
    <div id="print-records-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-md" onclick="closePrintRecordsModal()">
        <div onclick="event.stopPropagation()" class="relative w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl border border-slate-100">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 shadow-sm">
                        <i data-lucide="printer" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-950 uppercase tracking-wide">Print & Export Student Records</h3>
                        <p class="text-xs text-slate-500 font-medium">Select target filters below to print forms, ID cards, or download ZIP archives.</p>
                    </div>
                </div>
                <button type="button" onclick="closePrintRecordsModal()" class="rounded-full bg-slate-200/60 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition cursor-pointer">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                <!-- FILTER SECTION -->
                <div class="rounded-2xl border border-slate-100 bg-emerald-50/40 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                            <i data-lucide="filter" class="h-3.5 w-3.5 text-emerald-600"></i>
                            Filter Options
                        </span>
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                            Live Selection
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Learning Mode Filter -->
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1">Mode</label>
                            <select id="p-filter-mode" class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer">
                                <option value="" {{ request('mode') == '' ? 'selected' : '' }}>All Modes (F2F & ODL)</option>
                                <option value="F2F" {{ request('mode') == 'F2F' ? 'selected' : '' }}>Face to Face (F2F)</option>
                                <option value="ODL" {{ request('mode') == 'ODL' ? 'selected' : '' }}>Online Distance Learning (ODL)</option>
                            </select>
                        </div>

                        <!-- Grade Level Filter -->
                        <div>
                            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1">Grade</label>
                            <select id="p-filter-grade" class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer">
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
                            <label class="block text-[11px] font-extrabold uppercase tracking-wider text-slate-600 mb-1">Gender (Optional)</label>
                            <select id="p-filter-gender" class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition cursor-pointer">
                                <option value="" {{ request('gender') == '' ? 'selected' : '' }}>All Genders</option>
                                <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male Only</option>
                                <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female Only</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3 MAIN ACTION CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Action Card 1: Print Enrollment Forms JPG -->
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-emerald-50/20 p-4 transition hover:border-emerald-400 hover:shadow-md">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 rounded-xl bg-emerald-100 text-emerald-700">
                                    <i data-lucide="file-signature" class="h-5 w-5"></i>
                                </div>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Print Enrollment Forms JPG</h4>
                            </div>
                            <p class="text-[11.5px] text-slate-500 font-semibold leading-relaxed mb-4">
                                Print official enrollment forms in batch or export high-resolution JPG images.
                            </p>
                        </div>
                        <div class="space-y-2 pt-2 border-t border-emerald-100">
                            <button type="button" onclick="runPrintRecordAction('forms_batch')" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-700 px-3 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-800 cursor-pointer">
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                <span>Print Forms Batch</span>
                            </button>
                            <button type="button" onclick="runPrintRecordAction('forms_jpg')" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-300 bg-white px-3 py-2.5 text-xs font-extrabold text-emerald-800 shadow-sm transition hover:bg-emerald-50 cursor-pointer">
                                <i data-lucide="file-archive" class="h-3.5 w-3.5 text-emerald-600"></i>
                                <span>Zip Forms JPG</span>
                            </button>
                        </div>
                    </div>

                    <!-- Action Card 2: Print ID Cards -->
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-sky-50/20 p-4 transition hover:border-sky-400 hover:shadow-md">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 rounded-xl bg-sky-100 text-sky-700">
                                    <i data-lucide="contact" class="h-5 w-5"></i>
                                </div>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Print ID Cards</h4>
                            </div>
                            <p class="text-[11.5px] text-slate-500 font-semibold leading-relaxed mb-4">
                                Generate printable student ID cards sheet formatted front & back for PVC card printing.
                            </p>
                        </div>
                        <div class="pt-2 border-t border-sky-100">
                            <button type="button" onclick="runPrintRecordAction('id_cards')" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-sky-600 px-3 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-sky-700 cursor-pointer">
                                <i data-lucide="credit-card" class="h-3.5 w-3.5"></i>
                                <span>Print ID Cards Sheet</span>
                            </button>
                        </div>
                    </div>

                    <!-- Action Card 3: Download ZIP (Student Documents) -->
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-rose-50/20 p-4 transition hover:border-rose-400 hover:shadow-md">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 rounded-xl bg-rose-100 text-rose-700">
                                    <i data-lucide="folder-archive" class="h-5 w-5"></i>
                                </div>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Download Documents ZIP</h4>
                            </div>
                            <p class="text-[11.5px] text-slate-500 font-semibold leading-relaxed mb-4">
                                Download all requirement files (PSA Birth Cert, Report Cards, Photos) with clean student filenames.
                            </p>
                        </div>
                        <div class="pt-2 border-t border-rose-100">
                            <button type="button" onclick="runPrintRecordAction('docs_zip')" class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-700 px-3 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-rose-800 cursor-pointer">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                <span>Download ZIP Archive</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ADDITIONAL REPORTS & UTILITIES -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-700 block mb-3">
                        Additional Reports & Utilities
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                        <button type="button" onclick="runPrintRecordAction('credentials')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition cursor-pointer text-left">
                            <i data-lucide="key" class="h-4 w-4 text-amber-600"></i>
                            <span>Microsoft Credentials</span>
                        </button>
                        <button type="button" onclick="runPrintRecordAction('masters_list')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition cursor-pointer text-left">
                            <i data-lucide="list" class="h-4 w-4 text-blue-600"></i>
                            <span>Masters List PDF</span>
                        </button>
                        <button type="button" onclick="closePrintRecordsModal(); document.getElementById('bulk-print-modal').classList.remove('hidden')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-violet-800 hover:bg-violet-50 transition cursor-pointer text-left">
                            <i data-lucide="list-checks" class="h-4 w-4 text-violet-600"></i>
                            <span>Bulk Print List</span>
                        </button>
                        <button type="button" onclick="runPrintRecordAction('canva')" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-white p-2.5 text-xs font-extrabold text-emerald-800 hover:bg-emerald-50 transition cursor-pointer text-left">
                            <i data-lucide="sparkles" class="h-4 w-4 text-emerald-600"></i>
                            <span>Canva Bulk Create</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end border-t border-slate-100 bg-slate-50/80 px-6 py-3">
                <button type="button" onclick="closePrintRecordsModal()" class="rounded-xl border border-slate-100 bg-white px-5 py-2 text-xs font-extrabold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Print from List Modal -->
    <div id="bulk-print-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-md">
        <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-2xl">
            <button type="button" onclick="document.getElementById('bulk-print-modal').classList.add('hidden')" class="absolute right-4 top-4 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
            <div class="mb-6">
                <div class="mb-1 text-xs font-extrabold uppercase tracking-wider text-violet-600">Bulk Print</div>
                <h2 class="text-xl font-bold text-slate-900">Bulk Print from List</h2>
                <p class="mt-1 text-sm text-slate-500">Paste student numbers from Excel — one per line, or separated by commas. The system will auto-generate all their records.</p>
            </div>

            <form method="POST" action="{{ route('admin.students.bulk-print-list') }}" target="_blank" id="bulk-print-form">
                @csrf
                <div class="mb-4">
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Student Numbers</label>
                    <textarea
                        name="student_numbers"
                        id="bulk-student-numbers"
                        rows="8"
                        placeholder="260429&#10;260430&#10;260431&#10;...paste from Excel here"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-violet-400 focus:ring-4 focus:ring-violet-100 resize-none"
                        required
                    ></textarea>
                    <p id="bulk-count-label" class="mt-1.5 text-xs text-slate-400">0 student numbers detected</p>
                </div>
                <div class="mb-6">
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Print Type</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 p-3 text-center transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-100 has-[:checked]:ring-2 has-[:checked]:ring-violet-300">
                            <input type="radio" name="print_type" value="print_id" class="sr-only" checked>
                            <i data-lucide="contact" class="h-5 w-5 text-violet-600"></i>
                            <span class="text-xs font-bold text-violet-700">ID Cards</span>
                        </label>
                        <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-center transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-100 has-[:checked]:ring-2 has-[:checked]:ring-violet-300">
                            <input type="radio" name="print_type" value="print_credentials" class="sr-only">
                            <i data-lucide="key" class="h-5 w-5 text-slate-500"></i>
                            <span class="text-xs font-bold text-slate-600">Credentials</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="flex-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-6 text-sm font-bold text-white transition hover:bg-violet-700 active:scale-[0.98]">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Generate &amp; Print
                    </button>
                    <button type="button" onclick="document.getElementById('bulk-print-modal').classList.add('hidden')" class="h-11 rounded-xl border border-slate-200 px-5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sync Loading Modal -->
    <div id="sync-loading-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300">
        <div class="relative w-full max-w-md scale-95 transform rounded-2xl border border-slate-200/80 bg-white p-8 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 text-center">
            <!-- Spinner -->
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950/30">
                <svg class="h-8 w-8 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <!-- Text -->
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Syncing Microsoft Licenses</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">We are updating status and assigning licenses to all student accounts. This may take 1-3 minutes. Please do not close or refresh this page.</p>
            
            <!-- Progress bar simulation (subtle animation) -->
            <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-emerald-600 animate-[loading-bar_2s_infinite_ease-in-out]" style="width: 30%"></div>
            </div>
        </div>
    </div>

    <style>
    @keyframes loading-bar {
        0% { transform: translateX(-100%); width: 30%; }
        50% { width: 60%; }
        100% { transform: translateX(350%); width: 30%; }
    }
    </style>

    <script>
    function copyToClipboard(text, button) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check text-emerald-600"><path d="M20 6 9 17l-5-5"/></svg>';
            button.disabled = true;
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.disabled = false;
            }, 1500);
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    }

    // Bulk print list — live count
    const bulkTextarea = document.getElementById('bulk-student-numbers');
    const bulkCountLabel = document.getElementById('bulk-count-label');
    if (bulkTextarea) {
        bulkTextarea.addEventListener('input', function() {
            const nums = this.value.split(/[\r\n,;\t]+/).map(s => s.trim()).filter(Boolean);
            const unique = [...new Set(nums)];
            bulkCountLabel.textContent = unique.length + ' student number' + (unique.length !== 1 ? 's' : '') + ' detected';
        });
    }

    // Reinitialize lucide icons whenever the modal is opened
    document.getElementById('bulk-print-modal')?.addEventListener('transitionend', () => {
        if (window.lucide) window.lucide.createIcons();
    });

    document.querySelectorAll('form').forEach(form => {
        if (form.action.includes('ms-sync/sync-all-licenses') || form.action.includes('ms-sync/students')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const modal = document.getElementById('sync-loading-modal');
                const modalTitle = modal.querySelector('h3');
                const modalText = modal.querySelector('p');
                
                if (form.action.includes('ms-sync/students')) {
                    modalTitle.textContent = "Syncing Student Account";
                    modalText.textContent = "Updating Microsoft account status and license for this student. Please wait...";
                } else {
                    modalTitle.textContent = "Syncing Microsoft Licenses";
                    modalText.textContent = "We are updating Microsoft status and licenses for the current student filter. Please do not close or refresh this page.";
                }
                
                modal.classList.remove('hidden');
                
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    window.location.href = response.url || window.location.href;
                })
                .catch(err => {
                    console.error('Sync error:', err);
                    window.location.reload();
                });
            });
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
        closePrintRecordsModal();
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
    </script>

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
                <div id="zip-logs-console" class="h-40 overflow-y-auto rounded-xl bg-slate-950 p-3 font-mono text-[11px] leading-relaxed text-emerald-400 border border-slate-800 shadow-inner">
                    <div class="text-slate-500 italic">Initializing generator log...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Microsoft Credentials & Password Quick Modal Pop-up -->
    <div id="credentials-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/40 backdrop-blur-sm transition-opacity duration-200" onclick="if(event.target === this) closeStudentCredentialsModal()">
        <div class="relative w-full max-w-md scale-95 transform rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all duration-200 dark:border-slate-800 dark:bg-slate-900">
            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-4">
                <div class="flex items-center gap-3">
                    <div id="cred-modal-avatar" class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 font-extrabold text-sm ring-1 ring-emerald-200 overflow-hidden shrink-0">
                        ST
                    </div>
                    <div>
                        <h3 id="cred-modal-name" class="text-base font-black text-slate-900 dark:text-white leading-tight">STUDENT NAME</h3>
                        <p class="text-xs font-bold text-slate-500 mt-0.5">
                            <span id="cred-modal-id" class="text-emerald-700 font-extrabold">#261125</span> • 
                            <span id="cred-modal-grade">Grade 6</span> (<span id="cred-modal-section">Section</span>)
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeStudentCredentialsModal()" class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition cursor-pointer">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Microsoft Account Credentials Card -->
            <div class="space-y-3.5">
                <!-- Email Field -->
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex items-center justify-between text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="mail" class="h-3.5 w-3.5 text-emerald-600"></i>
                            Microsoft School Email
                        </span>
                        <span id="cred-modal-status-badge" class="rounded px-1.5 py-0.5 text-[9px] font-extrabold uppercase bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                            Active Account
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-2xs focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 dark:border-slate-700 dark:bg-slate-900 transition-all">
                        <input type="text" id="cred-modal-email" readonly class="w-full bg-transparent border-0 outline-none font-mono text-xs font-bold text-slate-900 dark:text-white p-0 m-0 focus:outline-none focus:ring-0 focus:border-0 selection:bg-emerald-100 selection:text-emerald-900" value="s.261125@amis.edu.ph" style="border: none !important; outline: none !important; box-shadow: none !important;">
                        <button type="button" onclick="copyCredText('cred-modal-email', 'Email')" class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-[11px] font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-300 transition cursor-pointer shrink-0">
                            <i data-lucide="copy" class="h-3 w-3"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                </div>

                <!-- Password Field -->
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex items-center justify-between text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="key-round" class="h-3.5 w-3.5 text-amber-600"></i>
                            Account Password
                        </span>
                        <span id="cred-modal-pass-state" class="rounded px-1.5 py-0.5 text-[9px] font-extrabold uppercase bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                            Temporary Active
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-2xs focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/20 dark:border-slate-700 dark:bg-slate-900 transition-all">
                        <div class="relative w-full">
                            <input type="password" id="cred-modal-password" readonly class="w-full bg-transparent border-0 outline-none font-mono text-xs font-bold text-slate-900 dark:text-white p-0 m-0 focus:outline-none focus:ring-0 focus:border-0 selection:bg-amber-100 selection:text-amber-900" value="Amis@98213" style="border: none !important; outline: none !important; box-shadow: none !important;">
                            <input type="text" id="cred-modal-password-text" readonly class="hidden w-full bg-transparent border-0 outline-none font-mono text-xs font-bold text-slate-900 dark:text-white p-0 m-0 focus:outline-none focus:ring-0 focus:border-0 selection:bg-amber-100 selection:text-amber-900" value="Amis@98213" style="border: none !important; outline: none !important; box-shadow: none !important;">
                        </div>
                        <button type="button" onclick="togglePasswordVisibility()" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition shrink-0" title="Toggle Show/Hide Password">
                            <i id="cred-modal-eye-icon" data-lucide="eye" class="h-4 w-4"></i>
                        </button>
                        <button type="button" onclick="copyCredText('cred-modal-password-text', 'Password')" class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-[11px] font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-700 hover:border-amber-300 transition cursor-pointer shrink-0">
                            <i data-lucide="copy" class="h-3 w-3"></i>
                            <span>Copy</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Action Buttons: Reset Password, Resend Email & Sync License -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <form id="cred-modal-reset-form" method="POST" action="" onsubmit="return confirm('Reset Microsoft Office 365 password to default temporary password (Amis@12345)?')">
                        @csrf
                        <input type="hidden" name="reset_format" value="default">
                        <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50/80 px-2 text-xs font-bold text-amber-800 hover:bg-amber-100 transition cursor-pointer shadow-sm" title="Reset Password">
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5 text-amber-600"></i>
                            <span>Reset Pass</span>
                        </button>
                    </form>

                    <form id="cred-modal-resend-form" method="POST" action="">
                        @csrf
                        <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-indigo-300 bg-indigo-50/80 px-2 text-xs font-bold text-indigo-800 hover:bg-indigo-100 transition cursor-pointer shadow-sm" title="Resend Email">
                            <i data-lucide="send" class="h-3.5 w-3.5 text-indigo-600"></i>
                            <span>Resend Email</span>
                        </button>
                    </form>

                    <form id="cred-modal-sync-form" method="POST" action="">
                        @csrf
                        <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50/80 px-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition cursor-pointer shadow-sm" title="Sync Microsoft account status and license">
                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5 text-emerald-600"></i>
                            <span>Sync License</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Toast Feedback Notification -->
            <div id="cred-copy-toast" class="hidden mt-3 rounded-lg bg-emerald-800 px-3 py-2 text-center text-xs font-bold text-emerald-50 transition shadow-sm">
                <span id="cred-copy-toast-msg">Copied to clipboard!</span>
            </div>

            <!-- Modal Footer Actions (Clean soft colors, NO black blocks) -->
            <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 dark:border-slate-800 pt-4">
                <button type="button" onclick="copyCombinedCredentials()" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition cursor-pointer shadow-sm">
                    <i data-lucide="copy-check" class="h-4 w-4 text-emerald-600"></i>
                    <span>Copy Both</span>
                </button>
                <div class="flex items-center gap-2">
                    <a id="cred-modal-print-btn" href="#" target="_blank" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-sm" title="Print Credential Slip">
                        <i data-lucide="printer" class="h-4 w-4 text-slate-500"></i>
                        <span>Print Slip</span>
                    </a>
                    <button type="button" onclick="closeStudentCredentialsModal()" class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 text-xs font-bold text-slate-700 hover:bg-slate-200 transition cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentModalStudent = null;

        function openStudentCredentialsModal(student) {
            currentModalStudent = student;
            document.getElementById('cred-modal-name').innerText = student.name || 'STUDENT PROFILE';
            document.getElementById('cred-modal-id').innerText = '#' + (student.student_number || '-');
            document.getElementById('cred-modal-grade').innerText = student.grade || '-';
            document.getElementById('cred-modal-section').innerText = student.section || 'Unassigned';
            
            document.getElementById('cred-modal-email').value = student.email || 'No Email Set';
            
            const passVal = student.temp_password || 'N/A';
            document.getElementById('cred-modal-password').value = passVal;
            document.getElementById('cred-modal-password-text').value = passVal;
            
            const passState = document.getElementById('cred-modal-pass-state');
            if (student.password_changed) {
                passState.innerText = 'User Changed Password';
                passState.className = 'rounded px-1.5 py-0.5 text-[9px] font-extrabold uppercase bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            } else {
                passState.innerText = 'Temporary Initial Active';
                passState.className = 'rounded px-1.5 py-0.5 text-[9px] font-extrabold uppercase bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            }

            const statusBadge = document.getElementById('cred-modal-status-badge');
            if (student.ms_user_id) {
                statusBadge.innerText = 'Active Account';
                statusBadge.className = 'rounded px-1.5 py-0.5 text-[9px] font-extrabold uppercase bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            } else {
                statusBadge.innerText = 'Pending Setup';
                statusBadge.className = 'rounded px-1.5 py-0.5 text-[9px] font-extrabold uppercase bg-slate-100 text-slate-600 ring-1 ring-slate-200';
            }

            const avatarEl = document.getElementById('cred-modal-avatar');
            if (student.photo_url) {
                avatarEl.innerHTML = `<img src="${student.photo_url}" class="h-full w-full rounded-xl object-cover">`;
            } else {
                const initials = (student.name || 'ST').split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
                avatarEl.innerText = initials;
            }

            const printBtn = document.getElementById('cred-modal-print-btn');
            if (printBtn && student.print_url) {
                printBtn.href = student.print_url;
            }

            const resendForm = document.getElementById('cred-modal-resend-form');
            if (resendForm && student.resend_url) {
                resendForm.action = student.resend_url;
            }

            const resetForm = document.getElementById('cred-modal-reset-form');
            if (resetForm && student.resend_url) {
                resetForm.action = student.resend_url;
            }

            const syncForm = document.getElementById('cred-modal-sync-form');
            if (syncForm && student.sync_url) {
                syncForm.action = student.sync_url;
            }

            const modal = document.getElementById('credentials-modal');
            modal.classList.remove('hidden');
            if (window.lucide) window.lucide.createIcons();
        }

        function closeStudentCredentialsModal() {
            document.getElementById('credentials-modal').classList.add('hidden');
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('cred-modal-password');
            const textInput = document.getElementById('cred-modal-password-text');
            const eyeIcon = document.getElementById('cred-modal-eye-icon');
            
            if (passInput.classList.contains('hidden')) {
                passInput.classList.remove('hidden');
                textInput.classList.add('hidden');
                if (eyeIcon) eyeIcon.setAttribute('data-lucide', 'eye');
            } else {
                passInput.classList.add('hidden');
                textInput.classList.remove('hidden');
                if (eyeIcon) eyeIcon.setAttribute('data-lucide', 'eye-off');
            }
            if (window.lucide) window.lucide.createIcons();
        }

        function copyCredText(elementId, label) {
            const el = document.getElementById(elementId);
            if (!el) return;
            navigator.clipboard.writeText(el.value).then(() => {
                showCredToast(`${label} copied to clipboard!`);
            });
        }

        function copyCombinedCredentials() {
            if (!currentModalStudent) return;
            const email = document.getElementById('cred-modal-email').value;
            const pass = document.getElementById('cred-modal-password-text').value;
            const text = `Student: ${currentModalStudent.name}\nAMIS ID: #${currentModalStudent.student_number}\nEmail: ${email}\nPassword: ${pass}`;
            navigator.clipboard.writeText(text).then(() => {
                showCredToast('Student Email & Password copied to clipboard!');
            });
        }

        function showCredToast(msg) {
            const toast = document.getElementById('cred-copy-toast');
            const msgEl = document.getElementById('cred-copy-toast-msg');
            if (!toast || !msgEl) return;
            msgEl.innerText = msg;
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 2500);
        }

        function openPrintRecordsModal() {
            const modal = document.getElementById('print-records-modal');
            if (modal) {
                modal.classList.remove('hidden');
                if (window.lucide) window.lucide.createIcons();
            }
        }

        function closePrintRecordsModal() {
            const modal = document.getElementById('print-records-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeStudentCredentialsModal();
                closePrintRecordsModal();
            }
        });
    </script>
</x-admin-layout>
@endif
