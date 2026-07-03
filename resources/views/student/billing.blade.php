<x-student-layout title="Statement of Account">
<div class="space-y-8">
    <!-- Top Summary Banner -->
    <div class="student-panel flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2 relative z-10">
            <span class="student-status-pill">
                <i data-lucide="credit-card" class="w-3.5 h-3.5 mr-1 text-emerald-600"></i> Statement of Account
            </span>
            <h2 class="text-2xl font-black text-gray-900" style="margin: 8px 0 4px;">Tuition & School Fees Overview</h2>
            <p class="text-gray-500 text-sm font-semibold">Keep track of school dues, review monthly plans, and upload new payment screenshots.</p>
        </div>

        <div class="flex gap-4 relative z-10">
            <!-- Outstanding Box -->
            <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl text-center min-w-[150px]">
                <p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Remaining Balance</p>
                <p class="text-xl font-black text-gray-900 mt-1">
                    PHP {{ number_format((float) ($account->remaining_balance ?? 0), 2) }}
                </p>
            </div>

            <!-- Paid Box -->
            <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl text-center min-w-[150px]">
                <p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Total Amount Paid</p>
                <p class="text-xl font-black text-emerald-700 mt-1">
                    PHP {{ number_format((float) ($account->amount_paid ?? 0), 2) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Column 1 & 2: Billing details, Monthly schedule, History -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Fee Breakdown card -->
            @if($account)
                <div class="student-panel">
                    <div class="student-panel-header">
                        <h2>Detailed Fee Breakdown</h2>
                    </div>
                    
                    <div class="student-table-scroll mt-4">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fee Item</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Base Tuition Fee</td>
                                    <td class="text-right font-semibold text-gray-700">PHP {{ number_format((float) $account->tuition_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Books & Learning Materials</td>
                                    <td class="text-right font-semibold text-gray-700">PHP {{ number_format((float) $account->books_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Miscellaneous Fees</td>
                                    <td class="text-right font-semibold text-gray-700">PHP {{ number_format((float) $account->miscellaneous_fee, 2) }}</td>
                                </tr>
                                @if($account->discount_amount > 0)
                                    <tr class="text-emerald-700 bg-emerald-50 font-bold">
                                        <td>Sibling Discount ({{ $account->discount_type }})</td>
                                        <td class="text-right">- PHP {{ number_format((float) $account->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="bg-emerald-50/50 text-emerald-950 font-extrabold text-base border-t border-emerald-100/50">
                                    <td>Gross Total Balance</td>
                                    <td class="text-right text-emerald-700">PHP {{ number_format((float) $account->total_balance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Monthly Statement waterfall -->
            <div class="student-panel">
                <div class="student-panel-header">
                    <h2>Monthly Billing Schedule</h2>
                </div>

                @if($billings->isNotEmpty())
                    <div class="space-y-4 pt-4">
                        @foreach($billings as $billing)
                            @php 
                                $isOverdue = $billing->status === 'unpaid' && $billing->due_date->isPast();
                            @endphp
                            <div class="p-4 rounded-xl border border-gray-150 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/50 transition duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-sm shadow-sm shrink-0 {{ $billing->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($isOverdue ? 'bg-rose-100 text-rose-700 animate-pulse' : 'bg-emerald-50 text-emerald-700') }}">
                                        {{ mb_substr($billing->month_name, 0, 3) }}
                                    </div>
                                    <div>
                                        <h5 class="font-extrabold text-gray-900 text-sm sm:text-base" style="margin:0;">
                                            {{ $billing->month_name }} Dues
                                        </h5>
                                        <p class="text-xs font-semibold text-gray-500 mt-0.5">Due on {{ $billing->due_date->format('F d, Y') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-6">
                                    <div class="text-left sm:text-right">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase">Amount Due</p>
                                        <p class="font-extrabold text-sm sm:text-base text-gray-800 mt-0.5">PHP {{ number_format((float) $billing->amount_due, 2) }}</p>
                                    </div>

                                    <div>
                                        @if($billing->status === 'paid')
                                            <span class="student-status-pill">
                                                <i data-lucide="check" class="w-3 h-3 mr-1"></i> Paid
                                            </span>
                                        @elseif($isOverdue)
                                            <span class="student-status-pill bg-rose-105 text-rose-700 border-rose-200">
                                                <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i> Overdue
                                            </span>
                                        @else
                                            <span class="student-status-pill bg-sky-50 text-sky-700 border-sky-100">
                                                <i data-lucide="clock" class="w-3 h-3 mr-1"></i> Upcoming
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty">
                        <i data-lucide="calendar"></i>
                        <p>No monthly installments configured</p>
                    </div>
                @endif
            </div>

            <!-- Uploaded history list -->
            <div class="student-panel">
                <div class="student-panel-header">
                    <h2>Previous Payments & Proofs</h2>
                </div>

                @if($payments->isNotEmpty())
                    <div class="space-y-4 pt-4">
                        @foreach($payments as $pay)
                            <div class="p-4 rounded-xl border border-gray-150 bg-gray-50/20 hover:bg-gray-50/40 transition duration-300">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-start gap-3.5 min-w-0 flex-1">
                                        <div class="space-y-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h5 class="font-extrabold text-sm sm:text-base text-gray-900 capitalize" style="margin:0;">
                                                    {{ $pay->method }} Payment
                                                </h5>
                                                @if($pay->receipt_url)
                                                    <a href="{{ asset('storage/' . $pay->receipt_url) }}" target="_blank" class="text-[10px] bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded transition duration-300">
                                                        View Receipt
                                                    </a>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500 font-semibold truncate">
                                                Ref: <span class="font-bold text-gray-700">{{ $pay->reference_no }}</span>
                                                @if($pay->or_number) 
                                                    • OR: <span class="font-bold text-gray-700">{{ $pay->or_number }}</span>
                                                @endif 
                                                • Date: {{ $pay->paid_at ? $pay->paid_at->format('M d, Y') : $pay->created_at->format('M d, Y') }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-5">
                                        <div class="text-left sm:text-right">
                                            <p class="font-black text-sm text-gray-900">PHP {{ number_format((float) $pay->amount, 2) }}</p>
                                        </div>

                                        <div>
                                            @if($pay->status === 'verified')
                                                <span class="student-status-pill">
                                                    <i data-lucide="check" class="w-3 h-3 mr-1"></i> Verified
                                                </span>
                                            @elseif($pay->status === 'rejected')
                                                <span class="student-status-pill bg-rose-50 text-rose-700 border-rose-200" title="Remarks: {{ $pay->remarks ?? 'None' }}">
                                                    <i data-lucide="x" class="w-3 h-3 mr-1"></i> Rejected
                                                </span>
                                            @else
                                                <span class="student-status-pill bg-amber-50 text-amber-700 border-amber-100">
                                                    <i data-lucide="clock" class="w-3 h-3 mr-1"></i> Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($pay->status === 'rejected' && $pay->remarks)
                                    <div class="mt-2 text-xs font-bold text-rose-600 border-t border-rose-100/50 pt-2">
                                        Remarks: <span class="font-normal text-rose-500">{{ $pay->remarks }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty">
                        <i data-lucide="history"></i>
                        <p>No payment submissions recorded yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Column 3: Pay/Upload Proof -->
        <div class="lg:col-span-1">
            <div class="student-panel sticky top-6">
                <div class="border-b border-gray-150 pb-4">
                    <span class="student-status-pill">Parents Gateway</span>
                    <h3 class="font-black text-gray-900 text-lg mt-2" style="margin: 8px 0 4px;">Upload Proof</h3>
                    <p class="text-gray-400 text-xs font-semibold">Use GCash, Maya, or Bank Transfer, and upload a copy of the receipt.</p>
                </div>

                <!-- Form -->
                <form action="{{ route('student.billing.pay') }}" method="POST" enctype="multipart/form-data" class="student-form pt-4">
                    @csrf
                    
                    @if($billings->isNotEmpty())
                        <label>
                            <span>Pay for Specific Installment</span>
                            <select name="soa_monthly_billing_id" id="soa_monthly_billing_id">
                                <option value="">-- General / Multiple months --</option>
                                @foreach($billings->where('status', 'unpaid') as $bill)
                                    <option value="{{ $bill->id }}">{{ $bill->month_name }} (PHP {{ number_format((float) $bill->amount_due, 2) }})</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <label>
                        <span>Payment Method</span>
                        <select name="method" id="method" required>
                            <option value="gcash">GCash</option>
                            <option value="maya">Maya</option>
                            <option value="bdo">BDO Bank Transfer</option>
                            <option value="bpi">BPI Bank Transfer</option>
                            <option value="other">Other Channel / Bank</option>
                        </select>
                    </label>

                    <label>
                        <span>Amount Paid (PHP)</span>
                        <input type="number" step="0.01" name="amount" id="amount" placeholder="e.g. 4500.00" required>
                    </label>

                    <label>
                        <span>Transaction Reference Number</span>
                        <input type="text" name="reference_no" id="reference_no" placeholder="e.g. 5012 345 6789" required>
                    </label>

                    <label>
                        <span>Upload Receipt Screenshot</span>
                        <input type="file" name="receipt" id="receipt" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer" required>
                        <span class="text-[10px] text-gray-400 font-semibold block mt-1">Accepts JPG, PNG up to 5MB</span>
                    </label>

                    <button type="submit" class="student-primary-btn w-full mt-2">
                        Submit Payment Receipt
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-student-layout>
