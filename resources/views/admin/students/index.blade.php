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
                <!-- Print Dropdown -->
                <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open; $nextTick(() => window.lucide && window.lucide.createIcons())" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50 cursor-pointer whitespace-nowrap shadow-sm">
                        <i data-lucide="printer" class="h-4 w-4 text-slate-500"></i>
                        <span>Print Records</span>
                        <i data-lucide="chevron-down" class="h-3 w-3 text-slate-400"></i>
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right.duration.150ms class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl z-50">
                        <a href="{{ route('admin.students.print-enrolment-forms-batch', request()->all()) }}" target="_blank" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50 transition">
                            <i data-lucide="file-signature" class="h-4 w-4 text-emerald-600"></i>
                            <span>Print Enrollment Application Forms ({{ request('grade') ?: 'All Grades' }})</span>
                        </a>
                        <button type="button" @click="open = false; downloadEnrolmentPngZip('{{ route('admin.students.print-enrolment-forms-batch', request()->all()) }}')" class="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition text-left cursor-pointer">
                            <i data-lucide="file-archive" class="h-4 w-4 text-emerald-600"></i>
                            <span>Zip Enrollment Forms JPG ({{ request('grade') ?: 'All Grades' }})</span>
                        </button>
                        <a href="{{ route('admin.students.index', array_merge(request()->all(), ['print_info' => 1])) }}" target="_blank" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <i data-lucide="file-text" class="h-4 w-4 text-slate-400"></i>
                            <span>Print Official Info Sheets</span>
                        </a>
                        <div class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold text-slate-400 bg-slate-50 cursor-not-allowed select-none">
                            <div class="flex items-center gap-2">
                                <i data-lucide="contact" class="h-4 w-4 text-slate-400"></i>
                                <span>Print ID Cards</span>
                            </div>
                            <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">Coming Soon</span>
                        </div>
                        <a href="{{ route('admin.students.index', array_merge(request()->all(), ['print_credentials' => 1])) }}" target="_blank" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <i data-lucide="key" class="h-4 w-4 text-slate-400"></i>
                            <span>Print Microsoft Credentials</span>
                        </a>
                        <a href="{{ route('admin.students.index', array_merge(request()->all(), ['print' => 1])) }}" target="_blank" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            <i data-lucide="list" class="h-4 w-4 text-slate-400"></i>
                            <span>Print Masters List</span>
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <button type="button" onclick="document.getElementById('bulk-print-modal').classList.remove('hidden')" class="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-50 transition">
                            <i data-lucide="list-checks" class="h-4 w-4 text-violet-500"></i>
                            <span>Bulk Print from Pasted List</span>
                        </button>
                        <div class="my-1 border-t border-slate-100"></div>
                        <a href="{{ route('admin.students.export-canva', request()->all()) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition">
                            <i data-lucide="download" class="h-4 w-4 text-emerald-600"></i>
                            <span>Export for Canva Bulk Create</span>
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <a href="{{ route('admin.students.export-verification-db') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 transition">
                            <i data-lucide="table" class="h-4 w-4 text-indigo-500"></i>
                            <span>Export Verification Database</span>
                        </a>
                        <div class="my-1 border-t border-slate-100"></div>
                        <a href="{{ route('admin.students.download-docs-zip', request()->all()) }}" download class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition">
                            <i data-lucide="file-archive" class="h-4 w-4 text-rose-500"></i>
                            <span>Download Student Documents (ZIP)</span>
                        </a>
                    </div>
                </div>
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
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 p-3 text-center transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-100 has-[:checked]:ring-2 has-[:checked]:ring-violet-300">
                            <input type="radio" name="print_type" value="print_id" class="sr-only" checked>
                            <i data-lucide="contact" class="h-5 w-5 text-violet-600"></i>
                            <span class="text-xs font-bold text-violet-700">ID Cards</span>
                        </label>
                        <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-center transition has-[:checked]:border-violet-500 has-[:checked]:bg-violet-100 has-[:checked]:ring-2 has-[:checked]:ring-violet-300">
                            <input type="radio" name="print_type" value="print_info" class="sr-only">
                            <i data-lucide="file-text" class="h-5 w-5 text-slate-500"></i>
                            <span class="text-xs font-bold text-slate-600">Info Sheets</span>
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
    <div id="zip-loading-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300">
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
</x-admin-layout>
@endif
