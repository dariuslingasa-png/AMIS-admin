@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
    $msSyncColor = ['enrolled' => 'green', 'failed' => 'red', 'pending' => 'yellow'];
    $msSyncLabel = ['enrolled' => 'Synced', 'failed' => 'Sync Failed', 'pending' => 'Pending Teams'];
    $sort = request('sort', 'latest');
    $direction = request('direction', 'desc') === 'asc' ? 'asc' : 'desc';
    $sortUrl = fn ($key) => route('admin.students.index', array_merge(request()->except('page'), [
        'sort' => $key,
        'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
    ]));
    $sortIcon = fn ($key) => $sort !== $key ? 'arrow-up-down' : ($direction === 'asc' ? 'arrow-up' : 'arrow-down');
    $gradeTotals = collect($analytics['grades'] ?? [])->keyBy('grade_level');
    $genderAnalytics = $analytics['gender'] ?? ['male' => 0, 'female' => 0, 'not_set' => 0];
    $genderLabels = ['male' => 'Male', 'female' => 'Female', 'not_set' => 'Not Set'];
@endphp

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
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 text-sm font-bold text-violet-700 transition hover:bg-violet-100 cursor-pointer">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        {{ request()->hasAny(['search', 'grade', 'type', 'gender', 'mode', 'ms_status']) ? 'Sync Filtered Licenses' : 'Sync Pending Licenses' }}
                    </button>
                </form>
                <a href="{{ route('admin.students.index', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print List
                </a>
                <a href="{{ route('admin.students.dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Dashboard
                </a>
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
                <span>Total Filtered: {{ $isPrint ? $students->count() : $students->total() }}</span>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Filter Bar Form -->
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3 print:hidden">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <label class="relative col-span-12 lg:col-span-3">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search name, ID, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
                <select name="grade" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All grades</option>
                    @foreach($gradeOrder as $g)
                        <option value="{{ $g }}" @selected(request('grade') === $g)>{{ $g }}</option>
                    @endforeach
                </select>
                <select name="type" class="{{ $inputClass }} col-span-6 lg:col-span-1 w-full" onchange="this.form.submit()">
                    <option value="">All types</option>
                    <option value="new" @selected(request('type') === 'new')>New Student</option>
                    <option value="old" @selected(request('type') === 'old')>Old Student</option>
                    <option value="transferee" @selected(request('type') === 'transferee')>Transferee</option>
                </select>
                <select name="gender" class="{{ $inputClass }} col-span-6 lg:col-span-1 w-full" onchange="this.form.submit()">
                    <option value="">All genders</option>
                    <option value="male" @selected(request('gender') === 'male')>Male</option>
                    <option value="female" @selected(request('gender') === 'female')>Female</option>
                    <option value="not_set" @selected(request('gender') === 'not_set')>Not Set</option>
                </select>
                <select name="mode" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All learning modes</option>
                    <option value="Face-to-Face" @selected(request('mode') === 'Face-to-Face')>Face-to-Face</option>
                    <option value="1st Shift" @selected(request('mode') === '1st Shift')>Flexible Online 1st Shift</option>
                    <option value="2nd Shift" @selected(request('mode') === '2nd Shift')>Flexible Online 2nd Shift</option>
                </select>
                <select name="ms_status" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All sync states</option>
                    <option value="no_license" @selected(request('ms_status') === 'no_license')>No License / Sync Failed</option>
                    <option value="enrolled" @selected(request('ms_status') === 'enrolled')>Synced (With License)</option>
                    <option value="failed" @selected(request('ms_status') === 'failed')>Sync Failed</option>
                    <option value="pending" @selected(request('ms_status') === 'pending')>Pending Teams</option>
                    <option value="no_account" @selected(request('ms_status') === 'no_account')>No Microsoft Account</option>
                </select>
                <button class="col-span-12 lg:col-span-1 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    <span class="lg:hidden">Filter</span>
                </button>
            </form>



            <!-- Table Wrapper -->
            <div class="overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-bold">Student</th>
                            <th class="w-36 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('student_id') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Student ID
                                    <i data-lucide="{{ $sortIcon('student_id') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-28 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('gender') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Gender
                                    <i data-lucide="{{ $sortIcon('gender') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-32 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('grade') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Grade
                                    <i data-lucide="{{ $sortIcon('grade') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-40 px-5 py-4 font-bold">Section</th>
                            <th class="w-48 px-5 py-4 font-bold">School Email</th>
                            <th class="w-40 px-5 py-4 font-bold">MS Sync State</th>
                            <th class="w-36 px-5 py-4 text-right font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($students as $student)
                            @php
                                $fullName = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                                $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                                $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url ?? null);
                                $msStatus = $student->studentSection->ms_status ?? 'pending';
                                $gender = strtolower((string) ($student->applicant->gender ?? ''));
                                $genderLabel = $gender === 'male' ? 'Male' : ($gender === 'female' ? 'Female' : 'Not Set');
                                $genderClass = $gender === 'male' ? 'bg-blue-50 text-blue-700 ring-blue-100' : ($gender === 'female' ? 'bg-violet-50 text-violet-700 ring-violet-100' : 'bg-slate-50 text-slate-500 ring-slate-100');
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <!-- Student Photo & Name -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-smart-image
                                            :src="$photoUrl"
                                            :alt="$name"
                                            :fallback-initials="$initials ?: 'ST'"
                                            size="40"
                                            rounded="rounded-md"
                                            containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-extrabold"
                                            :eager="false"
                                        />
                                        <div>
                                            <div class="font-extrabold text-slate-950">{{ $name }}</div>
                                            <div class="mt-0.5 text-xs font-medium text-slate-500">SY {{ $student->school_year ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Student Number -->
                                <td class="px-5 py-4 font-extrabold text-slate-600">
                                    {{ $student->student_number ?? '-' }}
                                </td>

                                <!-- Gender -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-extrabold ring-1 {{ $genderClass }}">{{ $genderLabel }}</span>
                                </td>

                                <!-- Grade -->
                                <td class="px-5 py-4 font-extrabold text-slate-700">
                                    {{ $student->grade_level ?? '-' }}
                                </td>

                                <!-- Section -->
                                <td class="px-5 py-4 font-medium text-slate-600">
                                    {{ $student->studentSection->section->official_name ?? $student->studentSection->section->name ?? 'No Section' }}
                                </td>

                                <!-- School Email -->
                                <td class="px-5 py-4 font-medium text-slate-600">
                                    {{ $student->school_email ?? '-' }}
                                </td>

                                <!-- MS Sync status -->
                                <td class="px-5 py-4">
                                    <x-badge :color="$msSyncColor[$msStatus] ?? 'gray'">
                                        {{ $msSyncLabel[$msStatus] ?? 'Pending' }}
                                    </x-badge>
                                </td>

                                <!-- Action -->
                                 <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if($student->ms_user_id && $msStatus !== 'enrolled')
                                            <form method="POST" action="{{ route('admin.ms-sync.student', $student) }}" class="inline-block">
                                                @csrf
                                                <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-violet-100 bg-violet-50 px-2.5 text-xs font-bold text-violet-700 transition hover:bg-violet-100 cursor-pointer" title="Sync Microsoft Account & License">
                                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                                    <span>Sync License</span>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center gap-2 rounded-md border border-emerald-100 bg-white px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                                            <i data-lucide="file-search" class="h-4 w-4"></i>
                                            Manage
                                        </a>
                                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                              onsubmit="return confirm('Delete {{ $student->student_number }} ({{ $student->school_email }})?\n\nThis will permanently delete the student from the portal and Microsoft 365. This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50" title="Delete Student">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No enrolled students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            @if(!$isPrint)
                <div class="mt-5">{{ $students->links() }}</div>
            @endif
        </div>
    </section>

    <!-- Print styling configuration -->
    <style>
        @media print {
            /* Hide all navigation, sidebars, dashboard links, buttons, and filters */
            #default-sidebar, 
            .admin-sidebar, 
            .admin-topbar, 
            topbar, 
            aside, 
            form, 
            nav, 
            .breadcrumbs, 
            .flash-messages, 
            footer, 
            .print\:hidden,
            .module-dashboard-link,
            .sidebar-section-container,
            .sidebar-profile-card,
            .admin-shell > a,
            .mb-5.grid,
            .mb-5,
            [data-lucide="arrow-left"] {
                display: none !important;
            }

            /* Reset container styling for standard page layout */
            .admin-content, 
            .admin-shell, 
            body, 
            main, 
            .mx-auto,
            section,
            .bg-white {
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                min-width: auto !important;
                width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                font-family: Arial, sans-serif !important;
            }

            .admin-content {
                margin-left: 0 !important;
            }

            /* Make the hidden print block visible */
            .print\:block {
                display: block !important;
            }

            /* Style the table to be simple plain raw text */
            table {
                border-collapse: collapse !important;
                width: 100% !important;
                font-family: Arial, sans-serif !important;
                border: 1px solid #000000 !important;
            }

            table th {
                position: static !important;
                background: #f1f5f9 !important;
                border: 1px solid #000000 !important;
                color: #000000 !important;
                font-family: Arial, sans-serif !important;
                font-weight: bold !important;
                font-size: 10px !important;
                padding: 6px 8px !important;
                text-transform: uppercase !important;
                text-align: left !important;
            }

            table td {
                border: 1px solid #000000 !important;
                padding: 6px 8px !important;
                font-family: Arial, sans-serif !important;
                font-size: 9px !important;
                color: #000000 !important;
                background: transparent !important;
            }

            /* Hide the actions column in the table during print */
            table th:last-child, 
            table td:last-child {
                display: none !important;
            }

            /* Remove icons and styling from inside table td */
            table td svg,
            table td i,
            table td [data-lucide],
            table td .inline-flex,
            table td span {
                display: inline !important;
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                color: #000000 !important;
                font-size: 9px !important;
                font-weight: normal !important;
            }

            /* Page break prevention rules for clean printing */
            tr {
                page-break-inside: avoid !important;
            }
        }
    </style>

    @if($isPrint)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        </script>
    @endif

    <!-- Sync Loading Modal -->
    <div id="sync-loading-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300">
        <div class="relative w-full max-w-md scale-95 transform rounded-2xl border border-slate-200/80 bg-white p-8 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 text-center">
            <!-- Spinner -->
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-violet-50 dark:bg-violet-950/30">
                <svg class="h-8 w-8 animate-spin text-violet-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <!-- Text -->
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Syncing Microsoft Licenses</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">We are updating status and assigning licenses to all student accounts. This may take 1-3 minutes. Please do not close or refresh this page.</p>
            
            <!-- Progress bar simulation (subtle animation) -->
            <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                <div class="h-full rounded-full bg-violet-600 animate-[loading-bar_2s_infinite_ease-in-out]" style="width: 30%"></div>
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
    document.querySelectorAll('form').forEach(form => {
        if (form.action.includes('ms-sync/sync-all-licenses') || form.action.includes('ms-sync/students')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent standard page navigation immediately
                
                const modal = document.getElementById('sync-loading-modal');
                const modalTitle = modal.querySelector('h3');
                const modalText = modal.querySelector('p');
                
                if (form.action.includes('ms-sync/students')) {
                    modalTitle.textContent = "Syncing Student Account";
                    modalText.textContent = "Updating status, teams enrollment, and Microsoft license for this student. Please wait...";
                } else {
                    modalTitle.textContent = "Syncing Microsoft Licenses";
                    modalText.textContent = "We are updating Microsoft status and licenses for the current student filter. Please do not close or refresh this page.";
                }
                
                modal.classList.remove('hidden');
                
                // Submit via fetch to keep the page alive and avoid aborting active images/logo
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    // Navigate to the final redirected URL once sync is done
                    window.location.href = response.url || window.location.href;
                })
                .catch(err => {
                    console.error('Sync error:', err);
                    window.location.reload();
                });
            });
        }
    });
    </script>
</x-admin-layout>
