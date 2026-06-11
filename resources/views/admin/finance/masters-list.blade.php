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
                th:last-child, /* Actions column header */
                td:last-child { /* Actions column cells */
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

                /* Table grid styling for print */
                table {
                    width: 100% !important;
                    border-collapse: collapse !important;
                    margin-top: 15px !important;
                    font-size: 11px !important;
                }

                th, td {
                    border: 1px solid #cbd5e1 !important;
                    padding: 8px 10px !important;
                    color: #000000 !important;
                    background: transparent !important;
                }

                th {
                    background-color: #f1f5f9 !important;
                    font-weight: bold !important;
                    text-transform: uppercase !important;
                }

                /* Keep badges simple/flat on print */
                .rounded-full, .rounded {
                    border: 1px solid #cbd5e1 !important;
                    background: transparent !important;
                    color: #000000 !important;
                    box-shadow: none !important;
                    padding: 2px 6px !important;
                }

                /* Force background colors to show on screen/PDF printers */
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
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
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
            <form method="GET" class="grid gap-3 xl:grid-cols-[minmax(280px,1fr)_180px_120px_auto]">
                <label class="relative block">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search family, student, ref, source, OR..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                </label>

                <select name="method" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All Methods</option>
                    @foreach (['remittance' => 'Remittance', 'gcash' => 'GCash', 'bdo' => 'BDO Bank Transfer', 'maya' => 'Maya', 'cash' => 'Cash', 'other' => 'Other'] as $methodValue => $methodLabel)
                        <option value="{{ $methodValue }}" @selected(request('method') === $methodValue)>{{ $methodLabel }}</option>
                    @endforeach
                </select>

                <select name="per_page" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    @foreach ([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} rows</option>
                    @endforeach
                </select>

                <div class="flex gap-2 flex-wrap">
                    <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800 cursor-pointer">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
                    </button>
                    <button type="button" onclick="window.print()" class="inline-flex h-11 items-center gap-2 rounded-xl bg-slate-800 px-4 text-sm font-black text-white shadow-sm transition hover:bg-slate-900 cursor-pointer">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print PDF
                    </button>
                    @if (request()->hasAny(['search', 'method', 'per_page']))
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
        </div>

        <!-- Master List Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead class="bg-white text-[11px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-4">Family Name</th>
                        <th class="px-5 py-4">Children Details</th>
                        <th class="px-5 py-4">Reference No</th>
                        <th class="px-5 py-4">MOP & Source</th>
                        <th class="px-5 py-4">Payment Date</th>
                        <th class="px-5 py-4 text-right">Amount</th>
                        <th class="px-5 py-4">OR Number</th>
                        <th class="px-5 py-4">Verified By</th>
                        <th class="px-5 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white font-semibold text-slate-700">
                    @forelse ($entries as $entry)
                        <tr class="transition hover:bg-slate-50/80">
                            <!-- Family Name -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-black text-slate-950 text-base">{{ $entry->family_name }}</div>
                                <div class="mt-1 text-[10px] text-slate-400 uppercase tracking-wider">
                                    Entry ID: #{{ str_pad((string) $entry->id, 5, '0', STR_PAD_LEFT) }}
                                </div>
                            </td>

                            <!-- Children Details -->
                            <td class="px-5 py-4 align-top">
                                <div class="space-y-2.5">
                                    @forelse ($entry->students as $student)
                                        <div class="flex items-center gap-2 flex-wrap @if(!$loop->last) pb-2.5 border-b border-slate-100/70 @endif">
                                            <span class="font-bold text-slate-900 text-[13.5px]">{{ $student->student_name }}</span>
                                            
                                            <span class="rounded-full bg-slate-50 px-2 py-0.5 text-[9.5px] font-bold uppercase tracking-wide text-slate-500 border border-slate-200/60">
                                                {{ $student->grade_level }}
                                            </span>

                                            @php
                                                $type = strtoupper($student->student_type ?? '');
                                                $mode = strtoupper($student->learning_mode ?? '');
                                                
                                                // Normalize mode: if it contains FOL, ODL, SHIFT, or FLEXIBLE, it's ODL. Otherwise, F2F.
                                                $cleanMode = (str_contains($mode, 'FOL') || str_contains($mode, 'ODL') || str_contains($mode, 'SHIFT') || str_contains($mode, 'FLEXIBLE')) ? 'ODL' : 'F2F';
                                                
                                                // Normalize type
                                                if (str_contains($type, 'NEW')) {
                                                    $cleanType = 'NEW';
                                                } elseif (str_contains($type, 'OLD')) {
                                                    $cleanType = 'OLD';
                                                } elseif (str_contains($type, 'TRANSFER')) {
                                                    $cleanType = 'TRANSFEREE';
                                                } elseif (str_contains($type, 'RETURN')) {
                                                    $cleanType = 'RETURNING';
                                                } else {
                                                    $cleanType = $type ?: 'NEW';
                                                }

                                                $badgeText = "{$cleanType} {$cleanMode}";

                                                if ($cleanType === 'NEW') {
                                                    $badgeClass = $cleanMode === 'ODL' 
                                                        ? 'bg-purple-50 text-purple-700 border border-purple-100' 
                                                        : 'bg-sky-50 text-sky-700 border border-sky-100';
                                                } elseif ($cleanType === 'OLD') {
                                                    $badgeClass = $cleanMode === 'ODL' 
                                                        ? 'bg-amber-50 text-amber-700 border border-amber-100' 
                                                        : 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                                                } else {
                                                    $badgeClass = $cleanMode === 'ODL'
                                                        ? 'bg-indigo-50 text-indigo-700 border border-indigo-100'
                                                        : 'bg-slate-50 text-slate-600 border border-slate-200';
                                                }
                                            @endphp
                                            <span class="rounded-full px-2 py-0.5 text-[9.5px] font-black uppercase tracking-wider {{ $badgeClass }}">
                                                {{ $badgeText }}
                                            </span>
                                        </div>
                                    @empty
                                        <span class="text-xs font-semibold text-slate-400">No student records</span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Reference No -->
                            <td class="px-5 py-4 align-top font-mono text-slate-800 text-sm tracking-tight">
                                {{ $entry->reference_no ?: '-' }}
                            </td>

                            <!-- MOP & Source -->
                            <td class="px-5 py-4 align-top">
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
                                @if ($entry->remittance_source)
                                    <div class="mt-1 text-[11px] font-black text-amber-600 uppercase tracking-wide">
                                        {{ $entry->remittance_source }}
                                    </div>
                                @endif
                            </td>

                            <!-- Payment Date -->
                            <td class="px-5 py-4 align-top">
                                <div class="text-slate-900 text-sm font-semibold">{{ $entry->payment_date?->format('M d, Y') }}</div>
                                <div class="mt-0.5 text-[11px] text-slate-400 font-medium">{{ $entry->payment_date?->format('l') }}</div>
                            </td>

                            <!-- Amount -->
                            <td class="px-5 py-4 align-top text-right font-black text-slate-900 text-[15px] tabular-nums">
                                {{ number_format($entry->amount, 2) }}
                            </td>

                            <!-- OR Number -->
                            <td class="px-5 py-4 align-top font-bold text-slate-900">
                                @if ($entry->or_number)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-800 font-bold border border-emerald-100">
                                        <i data-lucide="receipt" class="h-3.5 w-3.5"></i>
                                        {{ $entry->or_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-normal italic">No OR</span>
                                @endif
                            </td>

                            <!-- Verified By -->
                            <td class="px-5 py-4 align-top">
                                <div class="text-slate-900 text-xs font-bold">{{ $entry->verifier?->name ?? 'System' }}</div>
                                <div class="mt-0.5 text-[10px] text-slate-400 font-semibold uppercase tracking-wider">
                                    {{ $entry->created_at?->format('M d, Y h:i A') }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 align-top text-right">
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center">
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
        <div class="border-t border-slate-100 px-5 py-4">
            {{ $entries->links() }}
        </div>
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
</x-admin-layout>
