@extends('student.layout')
@section('content')
@php $statusClasses = [ 'verified' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'pending' => 'bg-amber-100 text-amber-800 border-amber-200', 'rejected' => 'bg-rose-100 text-rose-800 border-rose-200', ];
@endphp
<div>
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
<div class="px-6 py-5 sm:px-8 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
<div>
<p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 flex items-center gap-2">
<i data-lucide="receipt-text" class="w-4 h-4"></i> Receipt Tracking
</p>

<h2 class="mt-1 text-2xl font-black text-gray-900 kid-font">Payment History</h2>

<p class="mt-1 text-sm font-semibold text-gray-500">Track submitted receipts, finance review status, and official OR details.</p>

</div>

<div class="flex flex-wrap items-center gap-2">
<span class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700"> Verified: PHP {{ number_format((float) $verifiedTotal, 2) }}
</span>

<span class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-2 text-sm font-extrabold text-amber-700"> Pending: PHP {{ number_format((float) $pendingTotal, 2) }}
</span>

<a href="{{ route('student.billing') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-extrabold transition">
<i data-lucide="upload" class="w-4 h-4"></i> Upload
</a>

</div>

</div>

@if($payments->isNotEmpty())
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead class="bg-gray-50 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider">
<tr>
<th class="px-6 py-4">Date</th>

<th class="px-6 py-4">Method</th>

<th class="px-6 py-4">Reference / OR</th>

<th class="px-6 py-4">Amount</th>

<th class="px-6 py-4">Status</th>

<th class="px-6 py-4">Remarks</th>

<th class="px-6 py-4">Receipt</th>

</tr>

</thead>

<tbody class="divide-y divide-gray-100 text-sm">
@foreach($payments as $payment)
@php $receiptUrl = $payment->receipt_url ? asset('storage/'.$payment->receipt_url) : null; $statusClass = $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-700 border-gray-200';
@endphp
<tr class="hover:bg-emerald-50/20 transition">
<td class="px-6 py-5 whitespace-nowrap font-bold text-gray-600">{{ $payment->paid_at?->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}</td>

<td class="px-6 py-5 font-extrabold text-gray-900">{{ $payment->method ? strtoupper($payment->method) : 'PAYMENT' }}</td>

<td class="px-6 py-5">
<span class="block font-extrabold text-gray-900">{{ $payment->reference_no ?: 'No reference' }}</span>

<span class="block text-[10px] font-semibold text-gray-400">OR: {{ $payment->or_number ?: 'Pending' }}</span>

</td>

<td class="px-6 py-5 font-black text-gray-900">PHP {{ number_format((float) $payment->amount, 2) }}</td>

<td class="px-6 py-5">
<span class="inline-flex rounded-full border px-3 py-1 text-[10px] font-extrabold uppercase {{ $statusClass }}"> {{ $payment->status ?: 'pending' }}
</span>

</td>

<td class="px-6 py-5 max-w-xs text-xs font-semibold text-gray-500">{{ $payment->remarks ?: 'No remarks' }}</td>

<td class="px-6 py-5">
@if($receiptUrl)
<a href="{{ $receiptUrl }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-100 px-3 py-2 text-xs font-extrabold transition">
<i data-lucide="external-link" class="w-3.5 h-3.5"></i> View
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
<div class="p-10 text-center">
<i data-lucide="receipt" class="w-10 h-10 text-emerald-600 mx-auto"></i>
<h3 class="mt-3 font-black text-gray-900 kid-font">No payment receipts yet</h3>

<p class="mt-1 text-xs font-semibold text-gray-400">Uploaded receipts will appear here after submission.</p>

</div>

@endif
</section>

</div>

@endsection 