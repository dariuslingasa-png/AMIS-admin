<x-student-layout title="Payment History">

@php 
    $statusClasses = [ 
        'verified' => 'portal-badge-emerald', 
        'pending'  => 'portal-badge-amber', 
        'rejected' => 'portal-badge-rose', 
    ];
@endphp

<div class="space-y-6">
    <div class="portal-card p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                    <i data-lucide="receipt" class="h-4 w-4"></i>
                    <span>Transactions</span>
                </div>
                <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">Payment Records</h2>
                <p class="text-xs font-medium text-slate-500">Official receipt history and payment submissions.</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2">
                <span class="portal-badge portal-badge-emerald">
                    Verified: PHP {{ number_format((float) ($verifiedTotal ?? 0), 2) }}
                </span>
                <span class="portal-badge portal-badge-amber">
                    Pending: PHP {{ number_format((float) ($pendingTotal ?? 0), 2) }}
                </span>
            </div>
        </div>

        <div class="mt-4">
            @if(isset($payments) && $payments->isNotEmpty())
                <div class="portal-table-container">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th class="portal-th">Date</th>
                                <th class="portal-th">Method</th>
                                <th class="portal-th">Reference / OR</th>
                                <th class="portal-th text-right">Amount</th>
                                <th class="portal-th">Status</th>
                                <th class="portal-th">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                @php 
                                    $badge = $statusClasses[$payment->status] ?? 'portal-badge-slate';
                                @endphp
                                <tr class="portal-tr">
                                    <td class="portal-td font-semibold text-slate-600">
                                        {{ $payment->paid_at?->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="portal-td font-bold text-slate-900">
                                        {{ $payment->method ? strtoupper($payment->method) : 'PAYMENT' }}
                                    </td>
                                    <td class="portal-td font-mono text-xs font-bold text-slate-700">
                                        {{ $payment->reference_no ?? '—' }}
                                    </td>
                                    <td class="portal-td text-right font-black text-slate-900">
                                        PHP {{ number_format((float) $payment->amount, 2) }}
                                    </td>
                                    <td class="portal-td">
                                        <span class="portal-badge {{ $badge }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                    <td class="portal-td text-xs text-slate-500">
                                        {{ $payment->remarks ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="portal-empty-state">
                    <div class="portal-empty-icon">
                        <i data-lucide="receipt" class="h-6 w-6"></i>
                    </div>
                    <h3 class="font-heading text-base font-bold text-slate-800">No payment records found</h3>
                    <p class="text-xs text-slate-500 mt-1">Uploaded proofs and official receipts will appear here.</p>
                </div>
            @endif
        </div>
    </div>
</div>

</x-student-layout>