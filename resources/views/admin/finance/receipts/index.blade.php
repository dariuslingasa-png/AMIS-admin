<x-admin-layout
    title="Official Receipts"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => route('admin.finance.dashboard')],
        ['label' => 'Official Receipts', 'href' => null],
    ]"
>
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Official Receipts',
            'subtitle' => 'Permanent record of issued online and onsite official receipts with line-item breakdowns.',
        ])

        <!-- Filters Form -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.finance.receipts.index') }}" class="flex flex-col md:flex-row items-center gap-3 w-full">
                <div class="relative w-full md:flex-1">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search by OR number, transaction ID, or family..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                    >
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </div>
                </div>

                <select name="status" class="h-11 w-full md:w-48 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All Statuses</option>
                    <option value="ISSUED" @selected(request('status') === 'ISSUED')>Issued</option>
                    <option value="REVERSED" @selected(request('status') === 'REVERSED')>Reversed</option>
                </select>

                <button type="submit" class="h-11 w-full md:w-28 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-100">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>

                @if(request()->filled('q') || request()->filled('status'))
                    <a href="{{ route('admin.finance.receipts.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700 px-2 py-2">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Receipts Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm text-slate-500 border-collapse">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Official Receipt</th>
                            <th class="px-6 py-4">Family Account</th>
                            <th class="px-6 py-4">Payment Channel</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($receipts as $receipt)
                            @php
                                $isReversed = $receipt->status === 'REVERSED';
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition group">
                                <td data-label="Official receipt" class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/80 shrink-0">
                                            <i data-lucide="receipt-text" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 tracking-tight">{{ $receipt->official_receipt_number }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $receipt->issued_at?->format('M d, Y · g:i A') }}</p>
                                            @if($receipt->transaction?->transaction_number)
                                                <p class="text-[11px] font-mono text-slate-400">Tx: {{ $receipt->transaction?->transaction_number }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Family" class="px-6 py-4">
                                    <p class="font-bold text-slate-900 group-hover:text-emerald-700 transition uppercase tracking-tight">{{ $receipt->transaction?->family?->name }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $receipt->transaction?->family?->email }}</p>
                                </td>
                                <td data-label="Payment" class="px-6 py-4">
                                    <p class="font-black text-slate-900 text-sm tracking-tight">₱{{ number_format((float)$receipt->transaction?->amount, 2) }}</p>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold',
                                            'bg-sky-50 text-sky-800 border border-sky-200' => $receipt->transaction?->source === 'ONLINE',
                                            'bg-amber-50 text-amber-800 border border-amber-200' => $receipt->transaction?->source === 'ONSITE'
                                        ])>{{ $receipt->transaction?->payment_source_label }}</span>
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $receipt->transaction?->payment_method_label }}</span>
                                    </div>
                                </td>
                                <td data-label="Status" class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-emerald-50 text-emerald-800 border border-emerald-200' => !$isReversed,
                                        'bg-rose-50 text-rose-800 border border-rose-200' => $isReversed
                                    ])>
                                        <span class="h-1.5 w-1.5 rounded-full {{ $isReversed ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                        {{ $receipt->status }}
                                    </span>
                                </td>
                                <td data-label="Action" class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.finance.receipts.show', $receipt) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        <span>View OR</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                                            <i data-lucide="receipt" class="h-10 w-10"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-base font-bold text-slate-800">No official receipts found</h3>
                                            <p class="text-sm text-slate-500 max-w-sm mx-auto">No issued or reversed official receipts matched your search filters.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($receipts->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $receipts->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
