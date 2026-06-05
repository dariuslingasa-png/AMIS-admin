@extends('student.layout')
@section('content')
<div class="space-y-8">
<!-- Top Summary Banner -->
<div class="bg-white border border-gray-100 rounded-2xl p-8 text-gray-900 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
<div class="space-y-2">
<span class="bg-emerald-50 text-emerald-700 font-extrabold text-xs uppercase tracking-wider px-3 py-1 rounded-full border border-emerald-100/30 flex items-center gap-1.5 w-max">
<i data-lucide="credit-card" class="w-3.5 h-3.5 text-emerald-600"></i> Statement of Account
</span>

<h2 class="text-3xl font-black text-gray-950 kid-font"> Tuition & School Fees Overview
</h2>

<p class="text-gray-500 text-sm font-semibold"> Keep track of school dues, review monthly plans, and upload new payment screenshots.
</p>

</div>

<div class="flex gap-4">
<!-- Outstanding Box -->
<div class="bg-emerald-50/45 border border-emerald-100/40 p-4.5 rounded-3xl text-center min-w-[150px]">
<p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Remaining Balance</p>

<p class="text-2xl font-black text-gray-900 mt-1"> PHP {{ number_format((float) ($account->remaining_balance ?? 0), 2) }}
</p>

</div>

<!-- Paid Box -->
<div class="bg-emerald-50/45 border border-emerald-100/40 p-4.5 rounded-3xl text-center min-w-[150px]">
<p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Total Amount Paid</p>

<p class="text-2xl font-black text-emerald-700 mt-1"> PHP {{ number_format((float) ($account->amount_paid ?? 0), 2) }}
</p>

</div>

</div>

</div>

<!-- Main Grid layout -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
<!-- Column 1 & 2: Billing details, Monthly schedule, History -->
<div class="lg:col-span-2 space-y-8">
<!-- Fee Breakdown card -->
@if($account)
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
<h3 class="font-black text-gray-800 text-lg flex items-center gap-2 kid-font border-b border-gray-50 pb-4">
<i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-600"></i> Detailed Fee Breakdown
</h3>

<div class="overflow-hidden border border-emerald-100/30 rounded-2xl">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-emerald-50/20 text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-emerald-100/30">
<th class="p-4">Fee Item</th>

<th class="p-4 text-right">Amount</th>

</tr>

</thead>

<tbody class="text-sm font-semibold text-gray-700 divide-y divide-gray-50">
<tr>
<td class="p-4">Base Tuition Fee</td>

<td class="p-4 text-right">PHP {{ number_format((float) $account->tuition_fee, 2) }}</td>

</tr>

<tr>
<td class="p-4">Books & Learning Materials</td>

<td class="p-4 text-right">PHP {{ number_format((float) $account->books_fee, 2) }}</td>

</tr>

<tr>
<td class="p-4">Miscellaneous Fees</td>

<td class="p-4 text-right">PHP {{ number_format((float) $account->miscellaneous_fee, 2) }}</td>

</tr>

@if($account->discount_amount > 0)
<tr class="text-emerald-600 bg-emerald-50/20 font-bold">
<td class="p-4"> Sibling Discount ({{ $account->discount_type }})
</td>

<td class="p-4 text-right">- PHP {{ number_format((float) $account->discount_amount, 2) }}</td>

</tr>

@endif
<tr class="bg-emerald-50/30 text-emerald-950 font-extrabold text-base border-t border-emerald-100/50">
<td class="p-4">Gross Total Balance</td>

<td class="p-4 text-right text-emerald-700">PHP {{ number_format((float) $account->total_balance, 2) }}</td>

</tr>

</tbody>

</table>

</div>

</div>

@endif <!-- Monthly Statement waterfall -->
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
<h3 class="font-black text-gray-800 text-lg flex items-center gap-2 kid-font border-b border-gray-50 pb-4">
<i data-lucide="calendar" class="w-5 h-5 text-emerald-600"></i> Monthly Billing Schedule
</h3>

@if($billings->isNotEmpty())
<div class="space-y-4">
@foreach($billings as $billing)
@php $isOverdue = $billing->status === 'unpaid' && $billing->due_date->isPast();
@endphp
<div class="p-4.5 rounded-2xl border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/50 transition duration-300">
<div class="flex items-center gap-4.5">
<div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-lg shadow-sm shrink-0 {{ $billing->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($isOverdue ? 'bg-rose-100 text-rose-700 animate-pulse' : 'bg-emerald-50 text-emerald-700') }}"> {{ mb_substr($billing->month_name, 0, 3) }}
</div>

<div class="space-y-0.5">
<h5 class="font-black text-gray-900 text-sm sm:text-base kid-font"> {{ $billing->month_name }} Dues
</h5>

<p class="text-xs font-semibold text-gray-500"> Due on {{ $billing->due_date->format('F d, Y') }}
</p>

</div>

</div>

<div class="flex items-center justify-between sm:justify-end gap-6">
<div class="text-left sm:text-right">
<p class="text-xs font-bold text-gray-400">Amount Due</p>

<p class="font-extrabold text-sm sm:text-base text-gray-800 mt-0.5"> PHP {{ number_format((float) $billing->amount_due, 2) }}
</p>

</div>

<div>
@if($billing->status === 'paid')
<span class="bg-emerald-100 text-emerald-700 font-extrabold text-xs px-3.5 py-1.5 rounded-xl inline-flex items-center gap-1">
<i data-lucide="check" class="w-3.5 h-3.5"></i> Paid
</span>

@elseif($isOverdue)
<span class="bg-rose-100 text-rose-700 font-extrabold text-xs px-3.5 py-1.5 rounded-xl inline-flex items-center gap-1">
<i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> Overdue
</span>

@else
<span class="bg-emerald-50 text-emerald-700 font-extrabold text-xs px-3.5 py-1.5 rounded-xl inline-flex items-center gap-1">
<i data-lucide="clock" class="w-3.5 h-3.5"></i> Upcoming
</span>

@endif
</div>

</div>

</div>

@endforeach
</div>

@else
<div class="p-8 text-center bg-gray-50 border-2 border-dashed border-gray-100 rounded-2xl">
<i data-lucide="calendar" class="w-12 h-12 text-emerald-600 mx-auto mb-2"></i>
<h5 class="font-bold text-gray-800">No Monthly Installments Configured</h5>

<p class="text-gray-400 text-xs mt-1">This account might be on full payment schedule.</p>

</div>

@endif
</div>

<!-- Uploaded history list -->
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
<h3 class="font-black text-gray-800 text-lg flex items-center gap-2 kid-font border-b border-gray-50 pb-4">
<i data-lucide="history" class="w-5 h-5 text-emerald-600"></i> Previous Payments & Proofs
</h3>

@if($payments->isNotEmpty())
<div class="space-y-4">
@foreach($payments as $pay)
<div class="p-4 rounded-2xl border border-gray-50 bg-gray-50/20 hover:bg-gray-50/40 transition duration-300">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<div class="flex items-start gap-3.5">
<div class="space-y-0.5">
<div class="flex items-center gap-2">
<h5 class="font-extrabold text-sm sm:text-base text-gray-900 kid-font capitalize"> {{ $pay->method }} Payment
</h5>

@if($pay->receipt_url)
<a href="{{ asset('storage/' . $pay->receipt_url) }}" target="_blank" class="text-[10px] bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded-md transition duration-300"> View Receipt
</a>

@endif
</div>

<p class="text-xs text-gray-500 font-semibold"> Ref:
<span class="font-bold text-gray-700">{{ $pay->reference_no }}</span>

@if($pay->or_number) • OR:
<span class="font-bold text-gray-700">{{ $pay->or_number }}</span>

@endif • Date: {{ $pay->paid_at ? $pay->paid_at->format('M d, Y') : $pay->created_at->format('M d, Y') }}
</p>

</div>

</div>

<div class="flex items-center justify-between sm:justify-end gap-5">
<div class="text-left sm:text-right">
<p class="font-black text-sm text-gray-900"> PHP {{ number_format((float) $pay->amount, 2) }}
</p>

</div>

<div>
@if($pay->status === 'verified')
<span class="bg-emerald-100 text-emerald-800 font-bold text-xs px-3 py-1 rounded-full flex items-center gap-1">
<i data-lucide="check" class="w-3 h-3"></i> Verified
</span>

@elseif($pay->status === 'rejected')
<span class="bg-rose-100 text-rose-800 font-bold text-xs px-3 py-1 rounded-full flex items-center gap-1" title="Remarks: {{ $pay->remarks ?? 'None' }}">
<i data-lucide="x" class="w-3 h-3"></i> Rejected
</span>

<p class="text-[10px] text-rose-600 font-semibold text-right mt-1 max-w-[150px] truncate"> {{ $pay->remarks }}
</p>

@else
<span class="bg-amber-100 text-amber-800 font-bold text-xs px-3 py-1 rounded-full flex items-center gap-1">
<i data-lucide="clock" class="w-3 h-3"></i> Pending Review
</span>

@endif
</div>

</div>

</div>

</div>

@endforeach
</div>

@else
<div class="p-8 text-center bg-gray-50 border-2 border-dashed border-gray-100 rounded-2xl">
<i data-lucide="history" class="w-12 h-12 text-emerald-600 mx-auto mb-2"></i>
<h5 class="font-bold text-gray-800">No Payment Submissions Recorded</h5>

<p class="text-gray-400 text-xs mt-1">Upload a receipt on the right side to file a new payment!</p>

</div>

@endif
</div>

</div>

<!-- Column 3: Pay/Upload Proof -->
<div class="lg:col-span-1">
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6 sticky top-6">
<div class="border-b border-gray-50 pb-4">
<span class="bg-emerald-100 text-emerald-700 font-bold text-xs px-3 py-1 rounded-full"> Parents Gateway
</span>

<h3 class="font-black text-gray-800 text-lg kid-font mt-2"> Upload Proof of Payment
</h3>

<p class="text-gray-400 text-xs font-semibold mt-1"> Use GCash, Maya, or Bank Transfer, and upload a copy of the receipt.
</p>

</div>

<!-- Form -->
<form action="{{ route('student.billing.pay') }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-sm font-semibold">
@csrf <!-- Select Month/Billing (If available) -->
@if($billings->isNotEmpty())
<div class="space-y-1.5">
<label for="soa_monthly_billing_id" class="text-xs font-bold text-gray-600 block pl-1"> Pay for Specific Installment
</label>
<select name="soa_monthly_billing_id" id="soa_monthly_billing_id" class="w-full px-4 py-3 bg-emerald-50/20 border border-emerald-100 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-gray-700 transition duration-300">
<option value="">-- General / Multiple months --</option>
@foreach($billings->where('status', 'unpaid') as $bill)
<option value="{{ $bill->id }}"> {{ $bill->month_name }} (PHP {{ number_format((float) $bill->amount_due, 2) }})
</option>
@endforeach
</select>
</div>

@endif <!-- Method choice -->
<div class="space-y-1.5">
<label for="method" class="text-xs font-bold text-gray-600 block pl-1"> Payment Method
</label>
<select name="method" id="method" class="w-full px-4 py-3 bg-emerald-50/20 border border-emerald-100 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-gray-700 transition duration-300" required>
<option value="gcash">GCash</option>
<option value="maya">Maya</option>
<option value="bdo">BDO Bank Transfer</option>
<option value="bpi">BPI Bank Transfer</option>
<option value="other">Other Channel / Bank</option>
</select>
</div>

<!-- Amount -->
<div class="space-y-1.5">
<label for="amount" class="text-xs font-bold text-gray-600 block pl-1"> Amount Paid (PHP)
</label>
<input type="number" step="0.01" name="amount" id="amount" placeholder="e.g. 4500.00" class="w-full px-4 py-3 bg-emerald-50/20 border border-emerald-100 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-gray-700 placeholder-gray-300 transition duration-300" required>
</div>

<!-- Reference number -->
<div class="space-y-1.5">
<label for="reference_no" class="text-xs font-bold text-gray-600 block pl-1"> Transaction Reference Number
</label>
<input type="text" name="reference_no" id="reference_no" placeholder="e.g. 5012 345 6789" class="w-full px-4 py-3 bg-emerald-50/20 border border-emerald-100 rounded-xl focus:outline-none focus:border-emerald-500 focus:bg-white text-gray-700 placeholder-gray-300 transition duration-300" required>
</div>

<!-- File input -->
<div class="space-y-1.5">
<label for="receipt" class="text-xs font-bold text-gray-600 block pl-1"> Upload Receipt Screenshot
</label>
<input type="file" name="receipt" id="receipt" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer" required>
<span class="text-[10px] text-gray-400 font-semibold block pl-1 mt-1">Accepts JPG, PNG up to 5MB</span>

</div>

<!-- Submit -->
<button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-2xl transition duration-300 flex items-center justify-center gap-1.5 mt-2"> Submit Payment Receipt
</button>

</form>

</div>

</div>

</div>

</div>

@endsection
