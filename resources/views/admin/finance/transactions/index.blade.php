<x-admin-layout title="Finance Transactions">
    <div class="finance-page mx-auto max-w-[1500px] p-5 lg:p-8">
        @include('admin.finance._nav', [
            'title' => 'Transactions',
            'subtitle' => 'History of approved online and onsite family payments.',
        ])

        <form class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 xl:grid-cols-[minmax(280px,1fr)_160px_180px_170px_170px_auto]">
            <input name="q" value="{{ request('q') }}" placeholder="Search transaction, reference, or family"
                   class="h-12 rounded-xl border border-slate-300 px-4 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
            <select name="source" class="h-12 rounded-xl border border-slate-300 px-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                <option value="">All Payments</option>
                <option value="ONLINE" @selected(request('source') === 'ONLINE')>Online Payments</option>
                <option value="ONSITE" @selected(request('source') === 'ONSITE')>Onsite Payments</option>
            </select>
            <select name="method" class="h-12 rounded-xl border border-slate-300 px-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                <option value="">All payment methods</option>
                @foreach (['CASH' => 'Cash', 'GCASH' => 'GCash', 'MAYA' => 'Maya', 'BDO' => 'BDO', 'BANK_TRANSFER' => 'Bank Transfer', 'OTHER' => 'Other'] as $method => $methodLabel)
                    <option value="{{ $method }}" @selected(request('method') === $method)>{{ $methodLabel }}</option>
                @endforeach
            </select>
            <input name="from" type="date" value="{{ request('from') }}" aria-label="From date"
                   class="h-12 rounded-xl border border-slate-300 px-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
            <input name="to" type="date" value="{{ request('to') }}" aria-label="To date"
                   class="h-12 rounded-xl border border-slate-300 px-3 text-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
            <button class="h-12 rounded-xl bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Official Receipt</th>
                            <th class="px-5 py-3">Family</th>
                            <th class="px-5 py-3">Payment type</th>
                            <th class="px-5 py-3">Amount</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $transaction)
                            <tr class="hover:bg-slate-50">
                                <td data-label="Official receipt" class="px-5 py-4">
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Official Receipt No.</p>
                                    <p class="font-bold text-slate-900">{{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number }}</p>
                                    <p class="text-xs text-slate-500">{{ $transaction->transaction_at?->format('M d, Y g:i A') }}<br>Payment Reference: {{ $transaction->reference_number ?: '—' }}</p>
                                </td>
                                <td data-label="Family" class="px-5 py-4">
                                    <p class="font-bold text-slate-800">{{ $transaction->family?->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $transaction->family?->email }}</p>
                                </td>
                                <td data-label="Payment type" class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-sky-50 text-sky-700' => $transaction->source === 'ONLINE', 'bg-amber-50 text-amber-700' => $transaction->source === 'ONSITE'])>{{ $transaction->payment_source_label }}</span>
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $transaction->payment_method_label }}</span>
                                    </div>
                                </td>
                                <td data-label="Amount" class="px-5 py-4">
                                    <p class="font-extrabold text-slate-900">₱{{ number_format((float) $transaction->amount, 2) }}</p>
                                    @if ((float) $transaction->advance_credit > 0)
                                        <p class="text-xs font-bold text-violet-700">₱{{ number_format((float) $transaction->advance_credit, 2) }} advance credit</p>
                                    @endif
                                </td>
                                <td data-label="Action" class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="font-bold text-emerald-700 hover:text-emerald-900">View →</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td data-label="" colspan="5" class="px-5 py-12 text-center text-slate-500">No approved transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $transactions->links() }}</div>
        </div>
    </div>
</x-admin-layout>
