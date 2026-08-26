@extends('student.layout', ['heading' => 'Payment History'])

@section('content')
@php 
    $statusClasses = [ 
        'verified' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 
        'pending' => 'bg-amber-100 text-amber-800 border-amber-200', 
        'rejected' => 'bg-rose-100 text-rose-800 border-rose-200', 
    ];
@endphp

<section class="student-panel">
    <div class="student-panel-header">
        <div>
            <h2>Payment History</h2>
            <span>Track submitted receipts, finance review status, and official OR details.</span>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            <span class="student-status-pill">
                Verified: PHP {{ number_format((float) $verifiedTotal, 2) }}
            </span>
            <span class="student-status-pill bg-amber-50 text-amber-700 border-amber-100">
                Pending: PHP {{ number_format((float) $pendingTotal, 2) }}
            </span>
            <a href="{{ route('student.billing') }}" class="student-primary-btn">
                <i data-lucide="upload" class="w-4 h-4 mr-1"></i> Upload
            </a>
        </div>
    </div>

    <div class="pt-6">
        @if($payments->isNotEmpty())
            <div class="student-table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference / OR</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            @php 
                                $receiptUrl = $payment->receipt_url ? asset('storage/'.$payment->receipt_url) : null; 
                                $statusClass = $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap font-bold text-gray-600">
                                    {{ $payment->paid_at?->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}
                                </td>
                                <td class="font-extrabold text-gray-900">
                                    {{ $payment->method ? strtoupper($payment->method) : 'PAYMENT' }}
                                </td>
                                <td>
                                    <strong class="block text-gray-900">{{ $payment->reference_no ?: 'No reference' }}</strong>
                                    <span class="student-table-muted">OR: {{ $payment->or_number ?: 'Pending' }}</span>
                                </td>
                                <td class="font-black text-gray-900">
                                    PHP {{ number_format((float) $payment->amount, 2) }}
                                </td>
                                <td>
                                    <span class="student-status-pill {{ $payment->status === 'verified' ? '' : ($payment->status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-100') }}">
                                        {{ $payment->status ?: 'pending' }}
                                    </span>
                                </td>
                                <td class="max-w-xs text-xs font-semibold text-gray-500">
                                    {{ $payment->remarks ?: 'No remarks' }}
                                </td>
                                <td>
                                    @if($receiptUrl)
                                        <a href="{{ $receiptUrl }}" target="_blank" class="student-outline-btn" style="min-height:30px; padding:4px 12px; font-size:12px;">
                                            <i data-lucide="external-link" class="w-3.5 h-3.5 mr-1"></i> View
                                        </a>
                                    @else
                                        <span class="text-xs font-bold text-gray-300">No file</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dash-empty">
                <i data-lucide="receipt"></i>
                <p>No payment receipts recorded yet</p>
            </div>
        @endif
    </div>
</section>
@endsection