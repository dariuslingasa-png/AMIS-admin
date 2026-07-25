<x-admin-layout title="Finance Masters List">
    <div x-data="{
        editModal: false,
        isSubmitting: false,
        actionUrl: '',
        entry: {
            payment_date: '',
            method: '',
            reference_no: '',
            remittance_source: '',
            amount: '',
            or_number: ''
        },
        openEditModal(item) {
            this.entry.payment_date = item.payment_date || '';
            this.entry.method = item.method || 'remittance';
            this.entry.reference_no = item.reference_no || '';
            this.entry.remittance_source = item.remittance_source || '';
            this.entry.amount = item.amount || '';
            this.entry.or_number = item.or_number || '';
            this.actionUrl = `{{ route('admin.finance.masters-list.update', '__ID__') }}`.replace('__ID__', item.id);
            this.isSubmitting = false;
            this.editModal = true;
        }
    }">
        <!-- Print CSS Style Overrides -->
        <style>
            @media print {
                /* Hide non-printable elements */
                #default-sidebar,
                .admin-sidebar,
                .admin-topbar,
                .sidebar,
                aside,
                header,
                nav,
                footer,
                .breadcrumbs,
                .breadcrumb,
                .print\:hidden,
                form,
                .border-b.border-slate-100, /* Search/filters container */
                .grid.gap-3.border-b, /* Stats cards */
                .border-t.border-slate-100.px-5.py-4, /* Pagination container */
                .amis-card > div:first-child, /* Card header */
                .actions-col {
                    display: none !important;
                }

                .print-hidden-col {
                    display: none !important;
                }

                /* Hide icons in print to keep it clean */
                td svg,
                td i,
                td [data-lucide] {
                    display: none !important;
                }

                /* Reset background colors and layout margins for print */
                body, html {
                    background: #ffffff !important;
                    color: #000000 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    font-family: sans-serif !important;
                }

                .admin-shell {
                    display: block !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .admin-content {
                    margin-left: 0 !important;
                    padding: 0 !important;
                    background: #ffffff !important;
                }

                main {
                    padding: 0 !important;
                }

                /* Ensure card container has no border/shadow on print */
                .amis-card,
                .bg-white,
                .rounded-3xl,
                .shadow-sm,
                .border {
                    border: none !important;
                    box-shadow: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }

                /* Expand scrolled tables to print full rows without clipping */
                .overflow-x-auto {
                    overflow: visible !important;
                    max-height: none !important;
                }

                /* Table grid styling for print */
                table {
                    width: 100% !important;
                    min-width: 0 !important;
                    border-collapse: collapse !important;
                    margin-top: 12px !important;
                    font-size: 9.5px !important;
                    table-layout: fixed !important;
                }

                thead {
                    display: table-header-group !important;
                }

                tbody,
                tr {
                    break-inside: avoid !important;
                    page-break-inside: avoid !important;
                }

                th, td {
                    border: 1px solid #cbd5e1 !important;
                    padding: 5px 7px !important;
                    color: #000000 !important;
                    background: transparent !important;
                }

                th {
                    background-color: #f1f5f9 !important;
                    font-weight: bold !important;
                    text-transform: uppercase !important;
                }

                /* Keep badges simple/flat plain text on print */
                td .rounded-full, 
                td .rounded,
                td [class*="bg-"],
                td [class*="text-"],
                td .border,
                td .ring-1 {
                    border: none !important;
                    background: transparent !important;
                    color: #000000 !important;
                    box-shadow: none !important;
                    padding: 0 !important;
                    margin: 0 !important;
                    border-radius: 0 !important;
                    font-size: 11px !important;
                    font-weight: inherit !important;
                }

                /* Force background colors to show on screen/PDF printers */
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                @page {
                    size: A4 portrait;
                    margin: 10mm;
                }

                .max-w-screen-2xl,
                .finance-master-table-wrap,
                .finance-master-table {
                    max-width: none !important;
                    width: 100% !important;
                }
            }
        </style>

        <!-- Print Header (Only visible on print) -->
        <div class="hidden print:block text-center mb-6 pt-4">
            <h2 class="text-2xl font-black uppercase tracking-wider text-black">Al Munawwara Islamic School</h2>
            <h3 class="text-lg font-bold uppercase tracking-widest text-slate-700 mt-1">Finance Masters List</h3>
            <p class="text-xs text-slate-500 mt-1">Generated on {{ now()->format('F d, Y h:i A') }}</p>
        </div>

        <x-card title="Finance Masters List" subtitle="Master Ledger of Auto-Populated verified payments & remittance logs">
        
        <!-- Search and Filters -->
        @php
            $sort = request('sort');
            $dir  = request('dir', 'asc');
            $sortLink = fn($col) => route('admin.finance.masters-list', array_merge(request()->except(['page', 'sort', 'dir']), ['sort' => $col, 'dir' => $sort === $col && $dir === 'asc' ? 'desc' : 'asc']));
            $sortIcon = fn($col) => $sort === $col ? ($dir === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevrons-up-down';
        @endphp
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
            <form method="GET" class="grid gap-3 xl:grid-cols-[minmax(260px,1fr)_140px_130px_120px_auto]">
                <label class="relative block">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search student name..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                </label>

                <select name="grade" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" onchange="this.form.submit()">
                    <option value="">All Grades</option>
                    @foreach ($gradeLevels as $g)
                        <option value="{{ $g }}" @selected(request('grade') === $g)>{{ $g }}</option>
                    @endforeach
                </select>

                <select name="method" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All MOP</option>
                    @foreach (['remittance' => 'Remittance', 'gcash' => 'GCash', 'bdo' => 'BDO', 'maya' => 'Maya', 'cash' => 'Cash', 'other' => 'Other'] as $methodValue => $methodLabel)
                        <option value="{{ $methodValue }}" @selected(request('method') === $methodValue)>{{ $methodLabel }}</option>
                    @endforeach
                </select>

                <select name="per_page" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    @foreach ([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} rows</option>
                    @endforeach
                </select>

                <div class="flex gap-2 flex-wrap">
                    @if ($sort)
                        <a href="{{ route('admin.finance.masters-list', request()->except(['sort', 'dir'])) }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-purple-200 bg-purple-50 px-4 text-sm font-black text-purple-700 transition hover:bg-purple-100">
                            <i data-lucide="arrow-up-down" class="h-4 w-4"></i>
                            Reset Sort
                        </a>
                    @endif
                    <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800 cursor-pointer">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
                    </button>
                    <button type="button" onclick="window.location.href='{{ route('admin.finance.masters-list', array_merge(request()->except('page'), ['print' => 1])) }}'" class="inline-flex h-11 items-center gap-2 rounded-xl bg-slate-800 px-4 text-sm font-black text-white shadow-sm transition hover:bg-slate-900 cursor-pointer">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print PDF
                    </button>
                    @if (request()->hasAny(['search', 'method', 'grade', 'sort']))
                        <a href="{{ route('admin.finance.masters-list') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-600 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Summary Statistics Cards -->
        <div class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-4">
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <i data-lucide="list-checks" class="h-6 w-6"></i>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Ledgers</div>
                    <div class="mt-0.5 text-2xl font-black text-slate-950">{{ number_format($totalEntries) }}</div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-4">
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <i data-lucide="circle-dollar-sign" class="h-6 w-6"></i>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Collected</div>
                    <div class="mt-0.5 text-2xl font-black text-slate-950">₱{{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-4">
                <div class="p-3 bg-purple-50 rounded-xl text-purple-600">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Students</div>
                    <div class="mt-0.5 text-2xl font-black text-slate-950">{{ number_format($totalStudents) }}</div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 flex items-center gap-4">
                <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                    <i data-lucide="home" class="h-6 w-6"></i>
                </div>
                <div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Families</div>
                    <div class="mt-0.5 text-2xl font-black text-slate-950">{{ number_format($totalFamilies) }}</div>
                </div>
            </div>
        </div>

        <!-- Master List Table -->
        <div class="finance-master-table-wrap overflow-x-auto">
            <table class="finance-master-table w-full min-w-[1200px] text-left text-sm">
                <thead class="bg-white text-[11px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-4 w-16">#</th>
                        <th class="px-5 py-4">
                            <a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-1 hover:text-slate-700 no-underline">
                                Student Name <i data-lucide="{{ $sortIcon('name') }}" class="h-3.5 w-3.5"></i>
                            </a>
                        </th>
                        <th class="px-5 py-4">Gender</th>
                        <th class="px-5 py-4">
                            <a href="{{ $sortLink('grade') }}" class="inline-flex items-center gap-1 hover:text-slate-700 no-underline">
                                Grade <i data-lucide="{{ $sortIcon('grade') }}" class="h-3.5 w-3.5"></i>
                            </a>
                        </th>
                        <th class="px-5 py-4">Learning Mode</th>
                        <th class="px-5 py-4 print-hidden-col">MOP</th>
                        <th class="px-5 py-4 text-right actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white font-semibold text-slate-700">
                    @forelse ($entries as $entry)
                        @php
                            $students = $entry->students;
                            $studentCount = max(1, $students->count());
                        @endphp
                        @if ($students->isEmpty())
                            <tr class="transition hover:bg-slate-50/80 border-b border-slate-100">
                                <td class="px-5 py-4 align-top">-</td>
                                <td class="px-5 py-4 align-top text-slate-400 italic">No student records</td>
                                <td class="px-5 py-4 align-top">-</td>
                                <td class="px-5 py-4 align-top">-</td>
                                <td class="px-5 py-4 align-top">-</td>
                                <td class="px-5 py-4 align-top print-hidden-col">-</td>
                                <td class="px-5 py-4 align-top text-right actions-col">-</td>
                            </tr>
                        @else
                            @foreach ($students as $index => $student)
                                @php
                                    $enrollmentStatusLabel = $student->enrollment_status_label ?? null;
                                    $enrollmentStatusClass = match ($student->enrollment_status_color ?? 'slate') {
                                        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
                                        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                        default => 'bg-slate-50 text-slate-600 ring-slate-100',
                                    };
                                @endphp
                                <tr class="transition hover:bg-slate-50/80 @if($loop->last) border-b border-slate-250 @else border-b border-slate-100/40 @endif">
                                    <td class="px-5 py-4 align-top font-black tabular-nums text-slate-500">
                                        {{ $student->ledger_row_number ?? '-' }}
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        @if ($student->enrollment_url ?? false)
                                            <a href="{{ $student->enrollment_url }}" class="group inline-flex max-w-[280px] flex-col gap-1 no-underline" title="Open enrollment application">
                                                <span class="font-bold uppercase text-slate-900 text-[13.5px] break-words whitespace-normal leading-tight group-hover:text-emerald-700">
                                                    {{ Str::upper($student->student_name) }}
                                                </span>
                                                <span class="flex flex-wrap items-center gap-1 print:hidden">
                                                    <span class="inline-flex w-fit items-center gap-1 rounded-lg bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-100">
                                                        <i data-lucide="external-link" class="h-3 w-3"></i>
                                                        Enrollment
                                                    </span>
                                                    @if ($enrollmentStatusLabel)
                                                        <span class="inline-flex w-fit items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wider ring-1 {{ $enrollmentStatusClass }}" title="{{ $student->enrollment_status_title ?? $enrollmentStatusLabel }}">
                                                            <i data-lucide="{{ $enrollmentStatusLabel === 'Enrolled' ? 'check-circle-2' : 'clock-3' }}" class="h-3 w-3"></i>
                                                            {{ $enrollmentStatusLabel }}
                                                        </span>
                                                    @endif
                                                </span>
                                            </a>
                                        @else
                                            <span class="inline-flex max-w-[280px] flex-col gap-1">
                                                <span class="font-bold uppercase text-slate-900 text-[13.5px] max-w-[260px] break-words whitespace-normal inline-block leading-tight">
                                                    {{ Str::upper($student->student_name) }}
                                                </span>
                                                @if ($enrollmentStatusLabel)
                                                    <span class="inline-flex w-fit items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-black uppercase tracking-wider ring-1 {{ $enrollmentStatusClass }} print:hidden" title="{{ $student->enrollment_status_title ?? $enrollmentStatusLabel }}">
                                                        <i data-lucide="{{ $enrollmentStatusLabel === 'Enrolled' ? 'check-circle-2' : 'clock-3' }}" class="h-3 w-3"></i>
                                                        {{ $enrollmentStatusLabel }}
                                                    </span>
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        @php
                                            $gender = strtolower((string) ($student->gender ?? ''));
                                            $genderLabel = match (true) {
                                                str_starts_with($gender, 'f') => 'F',
                                                str_starts_with($gender, 'm') => 'M',
                                                default => '-',
                                            };
                                        @endphp
                                        <span class="text-xs font-bold uppercase {{ strtolower($student->gender ?? '') === 'female' ? 'text-pink-600' : 'text-sky-600' }}">
                                            {{ $genderLabel }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 border border-slate-200/60 inline-block">
                                            {{ $student->grade_abbr }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        @php
                                            $type = strtoupper($student->student_type ?? '');
                                            $mode = strtoupper($student->learning_mode ?? '');
                                            $cleanMode = (str_contains($mode, 'FOL') || str_contains($mode, 'ODL') || str_contains($mode, 'SHIFT') || str_contains($mode, 'FLEXIBLE')) ? 'ODL' : 'F2F';
                                            if (str_contains($type, 'NEW')) $cleanType = 'NEW';
                                            elseif (str_contains($type, 'OLD')) $cleanType = 'OLD';
                                            elseif (str_contains($type, 'TRANSFER')) $cleanType = 'TRANSFEREE';
                                            elseif (str_contains($type, 'RETURN')) $cleanType = 'RETURNING';
                                            else $cleanType = $type ?: 'NEW';
                                            $badgeText = "{$cleanType} {$cleanMode}";
                                            $badgeClass = match (true) {
                                                $cleanType === 'NEW' && $cleanMode === 'ODL' => 'bg-purple-50 text-purple-700 border border-purple-100',
                                                $cleanType === 'NEW' => 'bg-sky-50 text-sky-700 border border-sky-100',
                                                $cleanType === 'OLD' && $cleanMode === 'ODL' => 'bg-amber-50 text-amber-700 border border-amber-100',
                                                $cleanType === 'OLD' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                                                default => $cleanMode === 'ODL' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-slate-50 text-slate-600 border border-slate-200',
                                            };
                                        @endphp
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $badgeClass }} inline-block">
                                            {{ $badgeText }}
                                        </span>
                                    </td>
                                    @if ($index === 0)
                                    <td class="px-5 py-4 align-top print-hidden-col" rowspan="{{ $studentCount }}">
                                        @php
                                            $method = strtolower($entry->method);
                                            $badgeClass = match($method) {
                                                'remittance' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'gcash' => 'bg-sky-50 text-sky-700 border-sky-200',
                                                'bdo' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                'maya' => 'bg-violet-50 text-violet-700 border-violet-200',
                                                'cash' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                default => 'bg-slate-50 text-slate-700 border-slate-200'
                                            };
                                        @endphp
                                        <span class="inline-flex rounded border px-2.5 py-1 text-[11px] font-black uppercase tracking-wider {{ $badgeClass }}">
                                            {{ $entry->method_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 align-top text-right actions-col" rowspan="{{ $studentCount }}">
                                        <div class="flex justify-end gap-2">
                                            @if ($entry->enrollment_url ?? false)
                                                <a href="{{ $entry->enrollment_url }}"
                                                   class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3.5 py-2 text-xs font-black uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-100 hover:text-emerald-800">
                                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                                    Enrollment
                                                </a>
                                            @endif
                                            <button type="button"
                                                    @click="openEditModal(@js([
                                                        'id' => $entry->id,
                                                        'payment_date' => $entry->payment_date?->format('Y-m-d'),
                                                        'method' => $entry->method,
                                                        'reference_no' => $entry->reference_no,
                                                        'remittance_source' => $entry->remittance_source,
                                                        'amount' => $entry->amount,
                                                        'or_number' => $entry->or_number,
                                                    ]))"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 px-3.5 py-2 text-xs font-black uppercase tracking-wider text-amber-700 ring-1 ring-amber-200 transition hover:bg-amber-100 hover:text-amber-800 cursor-pointer">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i data-lucide="search-x" class="h-6 w-6"></i>
                                    </span>
                                    <div class="mt-3 text-sm font-black text-slate-700">No finance master logs found</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">Records are automatically created when payment proofs are verified.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if (request('print') !== '1' && method_exists($entries, 'links'))
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $entries->links() }}
            </div>
        @endif
        </x-card>

        <!-- Edit Modal -->
        <div x-show="editModal" x-cloak x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl p-6 space-y-4" @click.away="!isSubmitting && (editModal = false)">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-black text-slate-950 uppercase tracking-wider flex items-center gap-2" style="font-size: 19.5px !important;">
                        <i data-lucide="pencil" class="h-6 w-6 text-amber-600"></i>
                        Edit Ledger Entry
                    </h3>
                    <button type="button" @click="editModal = false" :disabled="isSubmitting" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 transition disabled:opacity-50">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                
                <!-- Body Form -->
                <form :action="actionUrl" method="POST" @submit="isSubmitting = true" class="space-y-4 text-left">
                    @csrf
                    @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Method of Payment</label>
                            <select name="method" x-model="entry.method" required class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                                <option value="remittance">Remittance</option>
                                <option value="gcash">GCash</option>
                                <option value="bdo">BDO Bank Transfer</option>
                                <option value="maya">Maya</option>
                                <option value="cash">Cash</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Payment Date</label>
                            <input type="date" name="payment_date" x-model="entry.payment_date" required class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Amount</label>
                            <input type="number" step="0.01" name="amount" x-model="entry.amount" required class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>
                        <div>
                            <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Reference No</label>
                            <input type="text" name="reference_no" x-model="entry.reference_no" placeholder="Reference No" class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>
                    </div>

                    <div x-show="entry.method === 'remittance'" x-transition>
                        <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Remittance Source</label>
                        <input type="text" name="remittance_source" x-model="entry.remittance_source" placeholder="e.g. AL GHURAIR EXCHANGE" :required="entry.method === 'remittance'" class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>

                    <div>
                        <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">OR Number</label>
                        <input type="text" name="or_number" x-model="entry.or_number" placeholder="OR Number" class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" @click="editModal = false" :disabled="isSubmitting" class="px-5 py-2.5 rounded-2xl border border-slate-200 bg-white text-sm font-black text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-2xl bg-amber-600 hover:bg-amber-700 text-sm font-black text-white transition shadow-sm disabled:opacity-50 cursor-pointer">
                            <span x-show="!isSubmitting">Save Changes</span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5 justify-center">
                                <svg class="animate-spin h-4.5 w-4.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (request('print') == '1')
    <script>window.onload = function() { window.print(); }</script>
    @endif
</x-admin-layout>
