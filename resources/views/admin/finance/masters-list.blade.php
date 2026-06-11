<x-admin-layout title="Finance Masters List">
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

                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800 cursor-pointer">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
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
                                <div class="space-y-3">
                                    @forelse ($entry->students as $student)
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="font-black text-slate-900 text-[13.5px]">{{ $student->student_name }}</span>
                                            
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                                {{ $student->grade_level }}
                                            </span>

                                            @php
                                                $mode = strtoupper($student->learning_mode);
                                                $modeClass = str_contains($mode, 'FOL') || str_contains($mode, 'ODL') || str_contains($mode, 'SHIFT') 
                                                    ? 'bg-amber-50 text-amber-700 ring-amber-100' 
                                                    : 'bg-blue-50 text-blue-700 ring-blue-100';
                                            @endphp
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide ring-1 {{ $modeClass }}">
                                                {{ $student->learning_mode }}
                                            </span>

                                            @php
                                                $type = strtoupper($student->student_type);
                                                $typeClass = $type === 'NEW' 
                                                    ? 'bg-purple-50 text-purple-700 ring-purple-100' 
                                                    : 'bg-emerald-50 text-emerald-700 ring-emerald-100';
                                            @endphp
                                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wide ring-1 {{ $typeClass }}">
                                                {{ $student->student_type }}
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-14 text-center">
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
</x-admin-layout>
