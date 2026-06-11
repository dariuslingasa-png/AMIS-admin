        <div class="print:hidden bg-white p-8 border border-slate-200 rounded-3xl shadow-sm space-y-6 mt-6">
            
            <!-- Ledger Title -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-black uppercase tracking-wider text-slate-800 flex items-center gap-2" style="font-size: 19.5px !important;">
                    <i data-lucide="history" class="h-5.5 w-5.5 text-slate-500"></i>
                    Payment Transaction
                </h3>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-[13.5px] font-bold text-slate-600">
                    {{ $allPayments->count() }} {{ Str::plural('record', $allPayments->count()) }}
                </span>
            </div>

            <!-- Ledger Table Container (No outer box borders!) -->
            <div class="overflow-x-auto">
                <table class="ledger-table w-full text-left">
                    <thead>
                        <tr class="bg-sage-medium text-black font-bold uppercase" style="font-size: 19.5px !important; background-color: #b8cece !important;">
                            <th class="px-4 py-3" style="background-color: #b8cece !important;">INVOICE / OR</th>
                            <th class="px-4 py-3" style="background-color: #b8cece !important;">DATE</th>
                            <th class="px-4 py-3" style="background-color: #b8cece !important;">METHOD & REF</th>
                            <th class="px-4 py-3 text-right" style="background-color: #b8cece !important;">AMOUNT</th>
                            <th class="px-4 py-3 text-center" style="background-color: #b8cece !important;">STATUS</th>
                            <th class="px-4 py-3 text-center" style="background-color: #b8cece !important;">PROOF</th>
                            @if ($canReviewPayments)
                                <th class="px-4 py-3 text-right" style="background-color: #b8cece !important;">ACTIONS</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="font-semibold text-black">
                        @forelse ($allPayments as $p)
                            @php
                                $pIsPdf = $p->receipt_url && strtolower(pathinfo($p->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
                                $statusColor = match(strtolower($p->status)) {
                                    'verified', 'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-amber-50 text-amber-700 border-amber-200',
                                };
                                $pInvoiceNo = isset($invoice) ? $invoice->invoice_no : 'INV-ENR-'.str_pad((string) $p->id, 5, '0', STR_PAD_LEFT);
                                
                                // Calculate predicted OR number
                                $baseOr = str_replace('INV-', config('services.school.or_prefix', 'OR-'), $invoice ? $invoice->invoice_no : 'INV-ENR-00000');
                                $verifiedCount = $approvedPayments->count();
                                if ($verifiedCount === 0) {
                                    $isFull = ((float)$p->amount >= (float)($invoice ? $invoice->total_amount : 0));
                                    $predictedOr = $isFull ? $baseOr : $baseOr . '-1';
                                } else {
                                    $predictedOr = $baseOr . '-' . ($verifiedCount + 1);
                                }
                            @endphp
                            <tr class="hover-row transition-colors">
                                <td class="px-4 py-4">
                                    @if (strtolower($p->status) === 'verified')
                                        <div class="font-bold text-black" style="font-size: 19.5px !important;">{{ $p->or_number }}</div>
                                        <div class="text-[13.5px] text-emerald-600 font-bold mt-1">Verified</div>
                                    @elseif (strtolower($p->status) === 'rejected')
                                        <div class="font-bold text-slate-700" style="font-size: 19.5px !important;">{{ $pInvoiceNo }}</div>
                                        <div class="text-[13.5px] text-rose-600 font-bold mt-1">Rejected</div>
                                    @else
                                        <div class="font-bold text-slate-800" style="font-size: 19.5px !important;">{{ $pInvoiceNo }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-black font-semibold" style="font-size: 19.5px !important;">{{ $p->created_at?->format('M d, Y') }}</div>
                                    <div class="text-[13.5px] text-slate-400 font-semibold mt-1">{{ $p->created_at?->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-bold text-slate-700 uppercase" style="font-size: 19.5px !important;">{{ $p->method_label }}</div>
                                    <div class="text-[13.5px] text-slate-500 font-semibold mt-1">Ref: {{ $p->reference_no ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-4 text-right font-black text-black" style="font-size: 19.5px !important;">
                                    {{ number_format($p->amount, 2) }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-[13.5px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                        {{ $p->status ?: 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if ($p->receipt_url)
                                        <button type="button" 
                                                @click="openPreview('{{ \App\Support\EnrollmentStorage::url($p->receipt_url) }}', 'Payment Proof: {{ $familyLabel }}', {{ $pIsPdf ? 'true' : 'false' }}); if ('{{ strtolower($p->status) }}' === 'pending') { currentPayment = {{ $p->id }}; currentInvoice = '{{ $pInvoiceNo }}'; predictedOr = '{{ $predictedOr }}'; financeMethod = '{{ in_array(strtolower($p->method), ['remittance', 'gcash', 'bdo', 'maya', 'cash']) ? strtolower($p->method) : 'other' }}'; remittanceSource = ''; financePaymentDate = '{{ $p->paid_at?->format('Y-m-d') ?: ($p->created_at?->format('Y-m-d') ?: now()->format('Y-m-d')) }}'; financeReferenceNo = '{{ $p->reference_no }}'; document.getElementById('modal-approve-form-finance').action = '{{ route('admin.payments.verify', $p) }}'; document.getElementById('modal-reject-form-finance').action = '{{ route('admin.payments.reject', $p) }}'; remarks = ''; }"
                                                class="btn-premium btn-view">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                            View
                                        </button>
                                    @else
                                        <span class="text-[13.5px] text-slate-400 font-semibold">No Proof</span>
                                    @endif
                                </td>
                                @if ($canReviewPayments)
                                    <td class="px-4 py-4 text-right font-bold">
                                        @if (strtolower($p->status) === 'pending')
                                            <div class="flex items-center justify-end gap-2">
                                                <button type="button" @click="approveModal = true; currentPayment = {{ $p->id }}; currentInvoice = '{{ $pInvoiceNo }}'; predictedOr = '{{ $predictedOr }}'; isSubmitting = false; financeMethod = '{{ in_array(strtolower($p->method), ['remittance', 'gcash', 'bdo', 'maya', 'cash']) ? strtolower($p->method) : 'other' }}'; remittanceSource = ''; financePaymentDate = '{{ $p->paid_at?->format('Y-m-d') ?: ($p->created_at?->format('Y-m-d') ?: now()->format('Y-m-d')) }}'; financeReferenceNo = '{{ $p->reference_no }}'; document.getElementById('approve-form').action = '{{ route('admin.payments.verify', $p) }}';" class="btn-premium btn-approve">
                                                    Verify
                                                </button>
                                                <button type="button" @click="rejectModal = true; currentPayment = {{ $p->id }}; currentInvoice = '{{ $pInvoiceNo }}'; remarks = ''; isSubmitting = false; document.getElementById('reject-form').action = '{{ route('admin.payments.reject', $p) }}';" class="btn-premium btn-reject">
                                                    Reject
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[13.5px] text-slate-400 font-bold italic">Reviewed</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canReviewPayments ? 7 : 6 }}" class="px-4 py-8 text-center text-slate-400 font-bold" style="font-size: 19.5px !important;">
                                    No payment history found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($advancePayments->isNotEmpty())
                <!-- Advance Payments Ledger Divider -->
                <div class="mt-12 mb-8 border-t-2 border-dashed border-slate-200"></div>

                <!-- Advance Payments Ledger Title -->
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-black uppercase tracking-wider text-slate-800 flex items-center gap-2" style="font-size: 19.5px !important;">
                        <i data-lucide="piggy-bank" class="h-5.5 w-5.5 text-emerald-600"></i>
                        Advance Payments & Excess Credits
                    </h3>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-[13.5px] font-bold text-emerald-800">
                        {{ $advancePayments->count() }} {{ Str::plural('record', $advancePayments->count()) }}
                    </span>
                </div>

                <!-- Advance Payments Table -->
                <div class="overflow-x-auto">
                    <table class="ledger-table w-full text-left">
                        <thead>
                            <tr class="bg-emerald-50 text-emerald-850 font-bold uppercase" style="font-size: 17.5px !important;">
                                <th class="px-4 py-3">Source OR</th>
                                <th class="px-4 py-3">Date Generated</th>
                                <th class="px-4 py-3 text-right">Initial Credit</th>
                                <th class="px-4 py-3 text-right">Remaining Balance</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="font-semibold text-black">
                            @foreach ($advancePayments as $ap)
                                @php
                                    $apStatusColor = match(strtolower($ap->status)) {
                                        'available' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'partially_applied' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        default => 'bg-slate-50 text-slate-500 border-slate-200',
                                    };
                                @endphp
                                <tr class="hover-row transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-black" style="font-size: 19.5px !important;">{{ $ap->or_number }}</div>
                                        <div class="text-[13.5px] text-slate-400 font-semibold mt-1">Source Invoice: {{ $invoiceNo }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-black font-semibold" style="font-size: 19.5px !important;">{{ $ap->created_at?->format('M d, Y') }}</div>
                                        <div class="text-[13.5px] text-slate-400 font-semibold mt-1">{{ $ap->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-right font-black text-slate-700" style="font-size: 19.5px !important;">
                                        {{ number_format($ap->initial_amount, 2) }}
                                    </td>
                                    <td class="px-4 py-4 text-right font-black text-emerald-800" style="font-size: 19.5px !important;">
                                        {{ number_format($ap->remaining_balance, 2) }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-[13.5px] font-bold uppercase tracking-wider {{ $apStatusColor }}">
                                            {{ $ap->status ?: 'Available' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
