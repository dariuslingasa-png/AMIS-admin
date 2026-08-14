<x-admin-layout
    title="Finance Transactions"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => route('admin.finance.dashboard')],
        ['label' => 'Transactions', 'href' => null],
    ]"
>
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Finance Transactions',
            'subtitle' => 'History of approved online and onsite family payments.',
        ])

        <!-- Filters Form -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.finance.transactions.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(280px,1fr)_160px_180px_170px_170px_auto]">
                <div class="relative">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search transaction, reference, or family..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                    >
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </div>
                </div>

                <select name="source" class="h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All Payments</option>
                    <option value="ONLINE" @selected(request('source') === 'ONLINE')>Online Payments</option>
                    <option value="ONSITE" @selected(request('source') === 'ONSITE')>Onsite Payments</option>
                </select>

                <select name="method" class="h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All Payment Methods</option>
                    @foreach (['CASH' => 'Cash', 'GCASH' => 'GCash', 'MAYA' => 'Maya', 'BDO' => 'BDO', 'BANK_TRANSFER' => 'Bank Transfer', 'OTHER' => 'Other'] as $method => $methodLabel)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ $methodLabel }}</option>
                    @endforeach
                </select>

                <input name="from" type="date" value="{{ request('from') }}" aria-label="From date"
                       class="h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">

                <input name="to" type="date" value="{{ request('to') }}" aria-label="To date"
                       class="h-11 rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">

                <div class="flex items-center gap-2">
                    <button type="submit" class="h-11 w-full xl:w-28 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-100">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
                    </button>
                    @if(request()->hasAny(['q', 'source', 'method', 'from', 'to']))
                        <a href="{{ route('admin.finance.transactions.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700 px-2 py-2">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm text-slate-500 border-collapse">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Official Receipt</th>
                            <th class="px-6 py-4">Family Account</th>
                            <th class="px-6 py-4">Payment Channel</th>
                            <th class="px-6 py-4 text-right">Amount</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $transaction)
                            <tr class="hover:bg-slate-50/60 transition group">
                                <td data-label="Official receipt" class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/80 shrink-0">
                                            <i data-lucide="receipt" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 tracking-tight">{{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $transaction->transaction_at?->format('M d, Y · g:i A') }}</p>
                                            @if($transaction->reference_number)
                                                <p class="text-[11px] font-mono text-slate-400">Ref: {{ $transaction->reference_number }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Family" class="px-6 py-4">
                                    <p class="font-bold text-slate-900 group-hover:text-emerald-700 transition uppercase tracking-tight">{{ $transaction->family?->name }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $transaction->family?->email }}</p>
                                </td>
                                <td data-label="Payment type" class="px-6 py-4">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold',
                                            'bg-sky-50 text-sky-800 border border-sky-200' => $transaction->source === 'ONLINE',
                                            'bg-amber-50 text-amber-800 border border-amber-200' => $transaction->source === 'ONSITE'
                                        ])>{{ $transaction->payment_source_label }}</span>
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $transaction->payment_method_label }}</span>
                                    </div>
                                </td>
                                <td data-label="Amount" class="px-6 py-4 text-right">
                                    <p class="text-base font-black text-slate-900 tracking-tight">₱{{ number_format((float) $transaction->amount, 2) }}</p>
                                    @if ((float) $transaction->advance_credit > 0)
                                        <p class="text-xs font-bold text-emerald-700 mt-0.5">₱{{ number_format((float) $transaction->advance_credit, 2) }} advance credit</p>
                                    @endif
                                </td>
                                <td data-label="Action" class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                                            <i data-lucide="inbox" class="h-10 w-10"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-base font-bold text-slate-800">No approved transactions found</h3>
                                            <p class="text-sm text-slate-500 max-w-sm mx-auto">No transaction records matched your search criteria or date filters.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
