<x-admin-layout title="Official Receipt {{ $receipt->official_receipt_number }}">
    @php
        $studentRows = collect($receiptData['rows'] ?? []);
        $approvedBy = $receipt->transaction?->processor?->name ?: ($receiptData['cashier'] ?? 'AMIS Finance Cashier');
        $isPaid = ($receiptData['remaining_balance'] ?? 0) <= 0.01;
    @endphp

    <div class="finance-page mx-auto max-w-[1000px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Official Receipt', 'subtitle' => 'Permanent approved-payment receipt with per-billing-month snapshot.'])

        <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-10 shadow-sm">
            <!-- Header Block -->
            <div class="flex flex-col gap-5 border-b-2 border-emerald-700 pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <img src="{{ asset('images/AMIS_Logo_email.png') }}" alt="AMIS logo" class="h-14 w-14 shrink-0 object-contain sm:h-16 sm:w-16">
                    <div>
                        <p lang="ar" dir="rtl" class="w-fit font-serif text-lg font-bold leading-relaxed text-emerald-900 sm:text-xl">المدرسة المنورة الإسلامية</p>
                        <p class="text-xs font-black uppercase tracking-[.14em] text-emerald-700">Al Munawwara Islamic School</p>
                        <p class="text-[11px] text-slate-500">Don Julian Rodriguez Avenue, Ma-a, Davao City</p>
                        <h2 class="mt-2 text-xl font-black tracking-tight text-slate-900 sm:text-2xl">FAMILY PAYMENT RECEIPT</h2>
                    </div>
                </div>
                <div class="text-left sm:text-right space-y-1 text-xs">
                    <div><span class="text-slate-500">Receipt No.:</span> <strong class="text-slate-900 font-mono">{{ $receiptData['receipt_number'] }}</strong></div>
                    <div><span class="text-slate-500">Billing Period:</span> <strong class="text-slate-900">{{ $receiptData['billing_month'] ?? 'Current Billing' }}</strong></div>
                    <div><span class="text-slate-500">Status:</span> <strong class="{{ $isPaid ? 'text-emerald-700' : 'text-amber-700' }}">{{ $isPaid ? 'Fully Paid' : 'Partially Paid' }}</strong></div>
                </div>
            </div>

            <!-- Month Selection Tabs -->
            @if(!empty($monthlyReceipts) && count($monthlyReceipts) > 1)
                <div class="mt-5 flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 p-2.5 border border-slate-200">
                    <span class="mr-2 text-xs font-bold uppercase tracking-wider text-slate-500">Monthly Statements:</span>
                    @foreach($monthlyReceipts as $mMonth => $mReceipt)
                        @php $isSelected = ($receiptData['billing_month'] ?? '') === $mMonth; @endphp
                        <a href="{{ route('admin.finance.receipts.show', [$receipt, 'month' => $mMonth]) }}"
                           @class([
                               'rounded px-3 py-1.5 text-xs font-bold transition-all',
                               'bg-emerald-800 text-white shadow-sm' => $isSelected,
                               'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' => !$isSelected,
                           ])>
                            {{ $mMonth }} (₱{{ number_format($mReceipt['amount_applied'] ?? $mReceipt['payment_applied_this_transaction'] ?? 0, 2) }})
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Payer & Payment Details (Simple 2-Column Layout) -->
            <div class="mt-6 grid gap-6 border-b border-slate-200 pb-6 sm:grid-cols-2 text-xs">
                <div class="space-y-2">
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Parent / Guardian:</span>
                        <strong class="text-slate-900">{{ $receiptData['parent_name'] }}</strong>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Account / Email:</span>
                        <span class="font-medium text-slate-800">{{ $receipt->transaction?->family?->email ?? ($receiptData['family_email'] ?? 'Registered Family Account') }}</span>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Enrolled Students:</span>
                        <span class="font-bold text-slate-800">{{ count($studentRows) }} {{ count($studentRows) === 1 ? 'Student' : 'Students' }}</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Date:</span>
                        <strong class="text-slate-900">{{ $receiptData['date'] }}</strong>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Payment Method:</span>
                        <span class="font-bold text-slate-800">{{ $receiptData['payment_method'] }}</span>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Reference No.:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $receiptData['reference_number'] ?: 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between sm:justify-start gap-4">
                        <span class="w-32 text-slate-500">Issued By:</span>
                        <span class="font-bold text-slate-800">{{ $approvedBy }}</span>
                    </div>
                </div>
            </div>

            <!-- Student Payment Table (7 Columns Only) -->
            <div class="mt-6">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Student Payment Details ({{ $receiptData['billing_month'] ?? 'All' }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-y border-slate-300 bg-slate-50 text-[11px] font-bold uppercase text-slate-800">
                                <th class="py-2.5 px-3">Student</th>
                                <th class="py-2.5 px-2">Grade</th>
                                <th class="py-2.5 px-3 text-right">Amount Due</th>
                                <th class="py-2.5 px-3 text-right">Applied</th>
                                <th class="py-2.5 px-3 text-right">Total Paid</th>
                                <th class="py-2.5 px-3 text-right">Balance</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php
                                $totDue = 0; $totApplied = 0; $totPaid = 0; $totBal = 0;
                            @endphp
                            @forelse($studentRows as $row)
                                @php
                                    $due = (float)($row['amount_due'] ?? $row['monthly_due'] ?? 0);
                                    $applied = (float)($row['applied_this_transaction'] ?? $row['applied_amount'] ?? $row['amount_paid'] ?? 0);
                                    $cumPaid = (float)($row['total_paid_to_date'] ?? ($row['amount_paid'] ?? ($applied + ($row['previous_paid'] ?? 0))));
                                    $rem = max(0.0, round((float)($row['remaining'] ?? ($due - $cumPaid)), 2));

                                    $totDue += $due; $totApplied += $applied; $totPaid += $cumPaid; $totBal += $rem;

                                    $statusText = match(true) {
                                        $rem <= 0.01 => 'PAID',
                                        $applied > 0.01 || $cumPaid > 0.01 => 'PARTIAL',
                                        default => 'UNPAID',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-2.5 px-3">
                                        <strong class="text-slate-900">{{ $row['student_name'] }}</strong>
                                        @if(!empty($row['student_id']))
                                            <span class="block text-[10px] text-slate-500">{{ $row['student_id'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-2 text-slate-600">{{ $row['grade_level'] }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-700 whitespace-nowrap">₱{{ number_format($due, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-700 whitespace-nowrap">₱{{ number_format($applied, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right text-slate-800 whitespace-nowrap">₱{{ number_format($cumPaid, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-slate-900 whitespace-nowrap">₱{{ number_format($rem, 2) }}</td>
                                    <td class="py-2.5 px-3 text-center font-bold text-[11px] {{ $rem <= 0.01 ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $statusText }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="p-4 text-center text-slate-500">No active student assessments for this period.</td></tr>
                            @endforelse
                        </tbody>
                        @if($studentRows->isNotEmpty())
                            @php
                                $monthlyAppliedTotal = $totApplied > 0.001 ? $totApplied : (float)($receiptData['payment_applied_this_transaction'] ?? $receiptData['amount_applied'] ?? 0);
                            @endphp
                            <tfoot>
                                <tr class="border-y-2 border-emerald-700 bg-white font-bold text-slate-900 text-xs">
                                    <td colspan="2" class="py-2.5 px-3">TOTAL</td>
                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">₱{{ number_format((float)($receiptData['total_amount_due'] ?? $totDue), 2) }}</td>
                                    <td class="py-2.5 px-3 text-right text-emerald-700 whitespace-nowrap">₱{{ number_format($monthlyAppliedTotal, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">₱{{ number_format((float)($receiptData['total_paid_to_date'] ?? $totPaid), 2) }}</td>
                                    <td class="py-2.5 px-3 text-right whitespace-nowrap">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totBal), 2) }}</td>
                                    <td class="py-2.5 px-3 text-center"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Monthly Summary Section (Single Clean Section) -->
            <div class="mt-8 border-t border-slate-200 pt-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 mb-3">Monthly Summary — {{ $receiptData['billing_month'] ?? 'Billing Month' }}</h3>
                <div class="max-w-md space-y-2 text-xs">
                    @if(isset($receiptData['previous_balance']) && (float)$receiptData['previous_balance'] > 0.001)
                        <div class="flex justify-between">
                            <span class="text-slate-600">Previous Balance:</span>
                            <strong class="text-slate-900">₱{{ number_format((float)$receiptData['previous_balance'], 2) }}</strong>
                        </div>
                    @endif
                    @if((float)($receiptData['credit_applied'] ?? 0) > 0.001)
                        <div class="flex justify-between">
                            <span class="text-slate-600">Credit Applied:</span>
                            <strong class="text-amber-700">−₱{{ number_format((float)$receiptData['credit_applied'], 2) }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Balance After Credit:</span>
                            <strong class="text-slate-900">₱{{ number_format((float)($receiptData['previous_remaining_balance'] ?? $receiptData['previous_balance']), 2) }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Current Payment Received:</span>
                            <strong class="text-slate-900">₱{{ number_format((float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $monthlyAppliedTotal), 2) }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Current Payment Applied:</span>
                            <strong class="text-emerald-700">₱{{ number_format($monthlyAppliedTotal, 2) }}</strong>
                        </div>
                    @else
                        <div class="flex justify-between">
                            <span class="text-slate-600">Current Payment Received:</span>
                            <strong class="text-slate-900">₱{{ number_format((float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $monthlyAppliedTotal), 2) }}</strong>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Applied to {{ $receiptData['billing_month'] ?? 'Billing Month' }}:</span>
                            <strong class="text-emerald-700">₱{{ number_format($monthlyAppliedTotal, 2) }}</strong>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-slate-600">Total Paid to Date:</span>
                        <strong class="text-slate-900">₱{{ number_format((float)($receiptData['total_paid_to_date'] ?? $totPaid), 2) }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Remaining {{ $receiptData['billing_month'] ?? 'Month' }} Balance:</span>
                        <strong class="text-slate-900">₱{{ number_format((float)($receiptData['remaining_balance'] ?? $totBal), 2) }}</strong>
                    </div>
                    @php
                        $amountReceived = (float)($receiptData['amount_received'] ?? $receiptData['amount'] ?? $monthlyAppliedTotal);
                        $excessCarried = (float)($receiptData['credit_created'] ?? $receiptData['credit_balance'] ?? max(0, round($amountReceived - $monthlyAppliedTotal, 2)));
                    @endphp
                    @if($excessCarried > 0.001)
                        <div class="flex justify-between">
                            <span class="text-slate-600">Credit / Carried Forward:</span>
                            <strong class="text-emerald-700">₱{{ number_format($excessCarried, 2) }}</strong>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-bold">
                        <span class="text-slate-700">Payment Status:</span>
                        <span class="{{ $isPaid ? 'text-emerald-700' : 'text-amber-700' }}">{{ $isPaid ? 'FULLY PAID' : 'PARTIALLY PAID' }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer Notice -->
            <div class="mt-8 border-t border-slate-200 pt-4 flex flex-col sm:flex-row justify-between items-start sm:items-center text-[11px] text-slate-500 gap-2">
                <div>
                    <p>This is a system-generated Family Payment Receipt. No manual signature is required.</p>
                    <p class="text-[10px] text-slate-400">Date Generated: {{ $receiptData['generated_at'] ?? now()->format('F d, Y · h:i A') }} · Receipt ID: {{ $receiptData['receipt_number'] }}</p>
                </div>
                <div class="text-right sm:text-center min-w-[180px]">
                    <div class="border-b border-slate-900 h-6 mb-1"></div>
                    <p class="font-bold text-slate-900">AMIS FINANCE CASHIER</p>
                    <p class="text-[10px] text-slate-500">Authorized Representative</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                @if(!empty($monthlyReceipts) && count($monthlyReceipts) > 1)
                    @foreach($monthlyReceipts as $mMonth => $mReceipt)
                        <a href="{{ route('admin.finance.receipts.pdf', [$receipt, 'month' => $mMonth]) }}" target="_blank" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">
                            Download PDF — {{ $mMonth }}
                        </a>
                    @endforeach
                @else
                    <a href="{{ route('admin.finance.receipts.pdf', [$receipt, 'month' => $receiptData['billing_month'] ?? null]) }}" target="_blank" class="rounded-lg bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">
                        Download Printable PDF
                    </a>
                @endif
                <a href="{{ route('admin.finance.transactions.show', $receipt->transaction) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    View Transaction Details
                </a>
            </div>
        </div>
    </div>
</x-admin-layout>
