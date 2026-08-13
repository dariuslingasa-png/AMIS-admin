<x-admin-layout title="Official Receipt {{ $receipt->official_receipt_number }}">
    @php
        $studentRows = collect($receiptData['rows'] ?? []);
        $approvedBy = $receipt->transaction?->processor?->name ?: 'AMIS Support Staff';
    @endphp

    <div class="finance-page mx-auto max-w-[1050px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Official Receipt', 'subtitle' => 'Permanent approved-payment receipt with per-billing-month snapshot.'])

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-7 lg:p-10">
            <div class="flex flex-col gap-5 border-b-2 border-emerald-800 pb-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <img src="{{ asset('images/AMIS_Logo_email.png') }}" alt="AMIS logo" class="h-20 w-20 shrink-0 object-contain sm:h-24 sm:w-24">
                    <div>
                        <p lang="ar" dir="rtl" class="w-fit font-serif text-xl font-bold leading-relaxed text-emerald-900 sm:text-2xl">المدرسة المنورة الإسلامية</p>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Al Munawwara Islamic School</p>
                        <h2 class="mt-3 text-3xl font-black text-slate-900">FAMILY PAYMENT RECEIPT</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">AMIS Family Payment System</p>
                    </div>
                </div>
                <div class="sm:text-right">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Official Receipt No.</p>
                    <p class="mt-1 text-xl font-black text-emerald-900">{{ $receiptData['receipt_number'] }}</p>
                    @if(!empty($receiptData['billing_month']))
                        <p class="mt-1 text-xs font-bold uppercase text-emerald-800">Billing Month: {{ $receiptData['billing_month'] }}</p>
                    @endif
                    <span @class([
                        'mt-3 inline-flex rounded-full px-3 py-1 text-xs font-black tracking-wide',
                        'bg-emerald-100 text-emerald-700' => $receipt->status === 'ISSUED',
                        'bg-rose-100 text-rose-700' => $receipt->status === 'REVERSED',
                    ])>{{ $receipt->status === 'ISSUED' ? 'APPROVED' : $receipt->status }}</span>
                </div>
            </div>

            @if(!empty($monthlyReceipts) && count($monthlyReceipts) > 1)
                <div class="mt-6 flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 p-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 mr-2">Monthly Receipts:</span>
                    @foreach($monthlyReceipts as $mMonth => $mReceipt)
                        @php $isSelected = ($receiptData['billing_month'] ?? '') === $mMonth; @endphp
                        <a href="{{ route('admin.finance.receipts.show', [$receipt, 'month' => $mMonth]) }}"
                           @class([
                               'rounded-lg px-3 py-1.5 text-xs font-bold transition-all',
                               'bg-emerald-800 text-white shadow-sm' => $isSelected,
                               'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' => !$isSelected,
                           ])>
                            {{ $mMonth }} (₱{{ number_format($mReceipt['amount_applied'], 2) }})
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="grid gap-6 py-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase text-slate-500">Received from</p>
                    <p class="mt-1 text-lg font-extrabold text-slate-900">{{ $receipt->transaction?->family?->name }}</p>
                    <p class="text-sm text-slate-500">{{ $receipt->transaction?->family?->email }}</p>
                </div>
                <div class="sm:text-right">
                    <p class="text-xs font-bold uppercase text-slate-500">Total Payment Received</p>
                    <p class="mt-1 text-3xl font-black text-emerald-800">₱{{ number_format((float) $receipt->transaction?->amount, 2) }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-slate-50 p-4">
                <div class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><p class="text-xs text-slate-500">Transaction date</p><p class="font-bold text-slate-800">{{ $receipt->transaction?->transaction_at?->format('F d, Y') }}</p></div>
                    <div><p class="text-xs text-slate-500">Payment method</p><p class="font-bold text-slate-800">{{ $receipt->transaction?->payment_method_label }}</p></div>
                    <div><p class="text-xs text-slate-500">Transaction No.</p><p class="font-bold text-slate-800">{{ $receipt->transaction?->transaction_number }}</p></div>
                    <div><p class="text-xs text-slate-500">Payment reference</p><p class="font-bold text-slate-800">{{ $receipt->transaction?->reference_number ?: '—' }}</p></div>
                </div>
            </div>

            <div class="mt-7">
                <h3 class="font-extrabold text-slate-900">Student Payment Details ({{ $receiptData['billing_month'] ?? 'All' }})</h3>
                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="finance-mobile-table min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-bold">Student</th>
                                <th class="px-4 py-3 font-bold">Grade Level</th>
                                <th class="px-4 py-3 font-bold">Billing Month</th>
                                <th class="px-4 py-3 text-right font-bold">Amount Due</th>
                                <th class="px-4 py-3 text-right font-bold">Total Paid to Date</th>
                                <th class="px-4 py-3 text-right font-bold">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($studentRows as $row)
                                <tr>
                                    <td data-label="Student" class="px-4 py-3 font-bold text-slate-800">{{ $row['student_name'] }}</td>
                                    <td data-label="Grade Level" class="px-4 py-3 text-slate-600">{{ $row['grade_level'] }}</td>
                                    <td data-label="Billing Month" class="px-4 py-3 text-slate-600">{{ $row['billing_month'] }}</td>
                                    <td data-label="Amount Due" class="px-4 py-3 text-right font-bold text-slate-700">₱{{ number_format($row['amount_due'], 2) }}</td>
                                    <td data-label="Total Paid to Date" class="px-4 py-3 text-right font-bold text-emerald-800">₱{{ number_format($row['amount_paid'], 2) }}</td>
                                    <td data-label="Balance" class="px-4 py-3 text-right font-bold text-slate-800">₱{{ number_format($row['remaining'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td data-label="" colspan="6" class="p-4 text-sm text-slate-500">The payment was recorded as family advance credit.</td></tr>
                            @endforelse
                            @if($studentRows->isNotEmpty())
                                <tr class="border-t-2 border-emerald-700 bg-emerald-50 font-black">
                                    <td></td><td></td><td>TOTAL</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($receiptData['total_amount_due'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($receiptData['total_paid_to_date'], 2) }}</td>
                                    <td class="px-4 py-3 text-right">₱{{ number_format($receiptData['remaining_balance'], 2) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 rounded-xl bg-slate-50 p-5 text-sm">
                <h4 class="mb-3 font-black text-slate-900">Monthly Summary — {{ $receiptData['billing_month'] ?? 'Billing Month' }}</h4>
                @if(!empty($receiptData['previous_month_label']))
                    <div class="flex items-center justify-between gap-4 py-1.5"><span class="font-semibold text-slate-600">Previous Balance — {{ $receiptData['previous_month_label'] }}</span><strong class="text-slate-900">₱{{ number_format($receiptData['previous_balance'], 2) }}</strong></div>
                @else
                    <div class="flex items-center justify-between gap-4 py-1.5"><span class="font-semibold text-slate-600">Previous Balance</span><strong class="text-slate-900">₱{{ number_format($receiptData['previous_balance'] ?? 0, 2) }}</strong></div>
                @endif
                <div class="flex items-center justify-between gap-4 py-1.5"><span class="font-semibold text-slate-600">Monthly Charge</span><strong class="text-slate-900">₱{{ number_format($receiptData['total_amount_due'], 2) }}</strong></div>
                <div class="flex items-center justify-between gap-4 py-1.5"><span class="font-semibold text-slate-600">Payment Received (Transaction)</span><strong class="text-slate-900">₱{{ number_format($receiptData['amount_received'], 2) }}</strong></div>
                <div class="flex items-center justify-between gap-4 py-1.5"><span class="font-semibold text-slate-600">Payment Applied ({{ $receiptData['billing_month'] ?? 'Month' }})</span><strong class="text-emerald-700">₱{{ number_format($receiptData['amount_applied'], 2) }}</strong></div>
                <div class="mt-3 flex items-center justify-between gap-4 border-t border-slate-200 pt-4"><span class="font-black text-slate-900">Remaining {{ $receiptData['billing_month'] ?? 'Month' }} Balance</span><strong class="text-xl text-amber-700">₱{{ number_format($receiptData['remaining_balance'], 2) }}</strong></div>
                @if(($receiptData['credit_created'] ?? 0) > 0.01)<div class="flex items-center justify-between gap-4 py-1.5"><span class="font-black text-slate-900">Family Advance Credit Created</span><strong class="text-xl text-sky-700">₱{{ number_format($receiptData['credit_created'], 2) }}</strong></div>@endif
                <div class="mt-3 flex items-center justify-between gap-4 border-t border-slate-200 pt-4"><span class="font-black text-slate-900">Status</span><strong class="text-emerald-800">{{ $receiptData['payment_status'] }}</strong></div>
            </div>

            <div class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm">
                <span class="font-bold text-emerald-800">Approved by Finance:</span>
                <span class="ml-1 font-extrabold text-slate-800">{{ $approvedBy }}</span>
            </div>

            @if($receipt->status === 'REVERSED')
                <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><strong>REVERSED:</strong> {{ $receipt->reversal_reason }}<br><span class="text-xs">{{ $receipt->reversed_at?->format('M d, Y g:i A') }}</span></div>
            @endif

            <div class="mt-7 grid gap-3 sm:flex sm:flex-wrap">
                @if(!empty($monthlyReceipts) && count($monthlyReceipts) > 1)
                    @foreach($monthlyReceipts as $mMonth => $mReceipt)
                        <a href="{{ route('admin.finance.receipts.pdf', [$receipt, 'month' => $mMonth]) }}" target="_blank" class="rounded-xl bg-emerald-700 px-5 py-3 text-center text-sm font-bold text-white shadow-sm hover:bg-emerald-800">
                            View PDF — {{ $mMonth }}
                        </a>
                    @endforeach
                @else
                    <a href="{{ route('admin.finance.receipts.pdf', [$receipt, 'month' => $receiptData['billing_month'] ?? null]) }}" target="_blank" class="rounded-xl bg-emerald-700 px-5 py-3 text-center text-sm font-bold text-white shadow-sm hover:bg-emerald-800">Open printable PDF</a>
                @endif
                <a href="{{ route('admin.finance.transactions.show', $receipt->transaction) }}" class="rounded-xl border border-slate-300 px-5 py-3 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">View transaction audit</a>
            </div>
        </div>
    </div>
</x-admin-layout>
