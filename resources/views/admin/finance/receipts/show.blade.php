<x-admin-layout title="Official Receipt {{ $receipt->official_receipt_number }}">
    @php
        $studentRows = collect($receiptData['rows'] ?? []);
        $approvedBy = $receipt->transaction?->processor?->name ?: ($receiptData['cashier'] ?? 'AMIS Finance Cashier');
    @endphp

    <div class="finance-page mx-auto max-w-[1100px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Official Receipt', 'subtitle' => 'Permanent approved-payment receipt with per-billing-month snapshot.'])

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8 lg:p-10">
            <!-- Header -->
            <div class="flex flex-col gap-5 border-b-2 border-emerald-700 pb-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <img src="{{ asset('images/AMIS_Logo_email.png') }}" alt="AMIS logo" class="h-16 w-16 shrink-0 object-contain sm:h-20 sm:w-20">
                    <div>
                        <p lang="ar" dir="rtl" class="w-fit font-serif text-xl font-bold leading-relaxed text-emerald-900 sm:text-2xl">المدرسة المنورة الإسلامية</p>
                        <p class="text-xs font-black uppercase tracking-[.16em] text-emerald-700">Al Munawwara Islamic School</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">OFFICIAL PAYMENT RECEIPT</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Finance Department · Student Accounts & Family Billing</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 sm:text-right">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Official Receipt No.</p>
                    <p class="mt-0.5 text-lg font-black text-emerald-950 sm:text-xl">{{ $receiptData['receipt_number'] }}</p>
                    @if(!empty($receiptData['billing_month']))
                        <p class="mt-1 text-xs font-bold uppercase text-slate-600">Billing Period: <span class="text-emerald-800">{{ $receiptData['billing_month'] }}</span></p>
                    @endif
                    <div class="mt-2">
                        @if(($receiptData['remaining_balance'] ?? 0) <= 0.01)
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-800">● FULLY PAID</span>
                        @else
                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800">● PARTIALLY PAID</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Month Selection Tabs -->
            @if(!empty($monthlyReceipts) && count($monthlyReceipts) > 1)
                <div class="mt-6 flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 p-3">
                    <span class="mr-2 text-xs font-bold uppercase tracking-wider text-slate-500">Monthly Statements:</span>
                    @foreach($monthlyReceipts as $mMonth => $mReceipt)
                        @php $isSelected = ($receiptData['billing_month'] ?? '') === $mMonth; @endphp
                        <a href="{{ route('admin.finance.receipts.show', [$receipt, 'month' => $mMonth]) }}"
                           @class([
                               'rounded-lg px-3.5 py-2 text-xs font-bold transition-all',
                               'bg-emerald-800 text-white shadow-sm' => $isSelected,
                               'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' => !$isSelected,
                           ])>
                            {{ $mMonth }} (₱{{ number_format($mReceipt['amount_applied'] ?? $mReceipt['payment_applied_this_transaction'] ?? 0, 2) }})
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Payer & Transaction Grid -->
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="border-b border-slate-100 pb-2 text-xs font-bold uppercase tracking-wide text-emerald-800">Payer / Account Information</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Parent / Guardian:</span><strong class="text-slate-900">{{ $receiptData['parent_name'] }}</strong></div>
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Account / Email:</span><span class="font-medium text-slate-700">{{ $receipt->transaction?->family?->email ?? ($receiptData['family_email'] ?? 'Family Account') }}</span></div>
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Enrolled Students:</span><span class="font-bold text-slate-800">{{ count($studentRows) }} Student(s)</span></div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="border-b border-slate-100 pb-2 text-xs font-bold uppercase tracking-wide text-emerald-800">Payment & Verification Details</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Transaction Date:</span><strong class="text-slate-900">{{ $receiptData['date'] }}</strong></div>
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Payment Method:</span><span class="font-bold text-slate-800">{{ $receiptData['payment_method'] }}</span></div>
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Reference No.:</span><span class="font-mono font-bold text-slate-700">{{ $receiptData['reference_number'] ?: '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-xs text-slate-500">Processed By:</span><span class="font-bold text-emerald-900">{{ $approvedBy }}</span></div>
                    </div>
                </div>
            </div>

            <!-- Student Payment Table -->
            <div class="mt-8">
                <h3 class="font-black text-slate-900">Student Payment Allocation — {{ $receiptData['billing_month'] ?? 'Billing Schedule' }}</h3>
                <div class="mt-3 overflow-x-auto rounded-xl border border-slate-200">
                    <table class="finance-mobile-table min-w-full text-left text-sm">
                        <thead class="bg-slate-900 text-xs uppercase text-white">
                            <tr>
                                <th class="px-4 py-3.5 font-bold">Student Name</th>
                                <th class="px-4 py-3.5 font-bold">Grade Level</th>
                                <th class="px-4 py-3.5 font-bold">Billing Month</th>
                                <th class="px-4 py-3.5 text-right font-bold">Monthly Assessment</th>
                                <th class="px-4 py-3.5 text-right font-bold">Prior Payments</th>
                                <th class="px-4 py-3.5 text-right font-bold">Applied This Tx</th>
                                <th class="px-4 py-3.5 text-right font-bold">Total Paid</th>
                                <th class="px-4 py-3.5 text-right font-bold">Month Balance</th>
                                <th class="px-4 py-3.5 text-center font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php
                                $totDue = 0; $totPrior = 0; $totApplied = 0; $totPaid = 0; $totBal = 0;
                            @endphp
                            @forelse($studentRows as $row)
                                @php
                                    $due = (float)($row['amount_due'] ?? 0);
                                    $applied = (float)($row['applied_this_transaction'] ?? $row['amount_paid'] ?? 0);
                                    $cumPaid = (float)($row['total_paid_to_date'] ?? ($applied + ($row['previous_paid'] ?? 0)));
                                    $prior = max(0.0, round($cumPaid - $applied, 2));
                                    $rem = max(0.0, round((float)($row['remaining'] ?? ($due - $cumPaid)), 2));

                                    $totDue += $due; $totPrior += $prior; $totApplied += $applied; $totPaid += $cumPaid; $totBal += $rem;
                                @endphp
                                <tr class="hover:bg-slate-50/80">
                                    <td data-label="Student" class="px-4 py-3.5 font-bold text-slate-900">{{ $row['student_name'] }}</td>
                                    <td data-label="Grade" class="px-4 py-3.5 text-slate-600">{{ $row['grade_level'] }}</td>
                                    <td data-label="Month" class="px-4 py-3.5 text-slate-600">{{ $row['billing_month'] ?? ($receiptData['billing_month'] ?? '') }}</td>
                                    <td data-label="Assessment" class="px-4 py-3.5 text-right font-medium text-slate-700">₱{{ number_format($due, 2) }}</td>
                                    <td data-label="Prior Paid" class="px-4 py-3.5 text-right text-slate-500">₱{{ number_format($prior, 2) }}</td>
                                    <td data-label="Applied This Tx" class="px-4 py-3.5 text-right font-black text-emerald-700">₱{{ number_format($applied, 2) }}</td>
                                    <td data-label="Total Paid" class="px-4 py-3.5 text-right font-bold text-slate-800">₱{{ number_format($cumPaid, 2) }}</td>
                                    <td data-label="Month Balance" class="px-4 py-3.5 text-right font-black {{ $rem <= 0.01 ? 'text-slate-400' : 'text-amber-700' }}">₱{{ number_format($rem, 2) }}</td>
                                    <td data-label="Status" class="px-4 py-3.5 text-center">
                                        @if($rem <= 0.01)
                                            <span class="inline-flex rounded bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">SETTLED</span>
                                        @elseif($applied > 0 || $prior > 0)
                                            <span class="inline-flex rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">PARTIAL</span>
                                        @else
                                            <span class="inline-flex rounded bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800">UNPAID</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="p-5 text-center text-sm text-slate-500">No active student assessments. The payment was recorded as family advance credit.</td></tr>
                            @endforelse
                        </tbody>
                        @if($studentRows->isNotEmpty())
                            <tfoot>
                                <tr class="border-t-2 border-emerald-700 bg-emerald-50/70 font-black text-slate-900">
                                    <td colspan="3" class="px-4 py-3.5">TOTAL ({{ $receiptData['billing_month'] ?? 'SCHEDULE' }})</td>
                                    <td class="px-4 py-3.5 text-right">₱{{ number_format($totDue, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right text-slate-600">₱{{ number_format($totPrior, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right text-emerald-800">₱{{ number_format($totApplied, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right">₱{{ number_format($totPaid, 2) }}</td>
                                    <td class="px-4 py-3.5 text-right {{ $totBal <= 0.01 ? 'text-emerald-800' : 'text-amber-800' }}">₱{{ number_format($totBal, 2) }}</td>
                                    <td class="px-4 py-3.5 text-center">
                                        <span class="inline-flex rounded px-2 py-0.5 text-xs font-bold {{ $totBal <= 0.01 ? 'bg-emerald-200 text-emerald-900' : 'bg-amber-200 text-amber-900' }}">
                                            {{ $totBal <= 0.01 ? 'SETTLED' : 'PARTIAL' }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Financial Summary & Reconciliation Grid -->
            <div class="mt-8 grid gap-5 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <h4 class="border-b border-slate-200 pb-2.5 font-black text-slate-900">Payment Distribution</h4>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-600">Payment Received (This Transaction):</span><strong class="text-slate-900">₱{{ number_format((float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $receipt->transaction?->amount ?? 0), 2) }}</strong></div>
                        <div class="flex justify-between"><span class="text-slate-600">Applied to {{ $receiptData['billing_month'] ?? 'Current Month' }}:</span><strong class="text-emerald-700">₱{{ number_format((float)($receiptData['payment_applied_this_transaction'] ?? $receiptData['amount_applied'] ?? $totApplied), 2) }}</strong></div>
                        @if((float)($receiptData['credit_created'] ?? 0) > 0.01)
                            <div class="flex justify-between"><span class="text-slate-600">Recorded as Advance Credit:</span><strong class="text-sky-700">+₱{{ number_format((float)$receiptData['credit_created'], 2) }}</strong></div>
                        @endif
                    </div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                    <h4 class="border-b border-emerald-200 pb-2.5 font-black text-emerald-950">Family Account Balance Status</h4>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Balance for {{ $receiptData['billing_month'] ?? 'Month' }}:</span>
                            <strong class="{{ $totBal <= 0.01 ? 'text-emerald-800' : 'text-amber-800' }}">
                                ₱{{ number_format($totBal, 2) }} {{ $totBal <= 0.01 ? '(SETTLED)' : '' }}
                            </strong>
                        </div>
                        <div class="flex justify-between border-t border-emerald-200 pt-2 font-black">
                            <span class="text-slate-800">Total Outstanding Balance:</span>
                            <strong class="text-lg text-slate-950">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totBal), 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                @if(!empty($monthlyReceipts) && count($monthlyReceipts) > 1)
                    @foreach($monthlyReceipts as $mMonth => $mReceipt)
                        <a href="{{ route('admin.finance.receipts.pdf', [$receipt, 'month' => $mMonth]) }}" target="_blank" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800">
                            Download PDF — {{ $mMonth }}
                        </a>
                    @endforeach
                @else
                    <a href="{{ route('admin.finance.receipts.pdf', [$receipt, 'month' => $receiptData['billing_month'] ?? null]) }}" target="_blank" class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800">
                        Download Printable PDF
                    </a>
                @endif
                <a href="{{ route('admin.finance.transactions.show', $receipt->transaction) }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    View Transaction Details
                </a>
            </div>
        </div>
    </div>
</x-admin-layout>
