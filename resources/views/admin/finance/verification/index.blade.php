<x-admin-layout title="Payment Verification">
    <div class="finance-page mx-auto max-w-[1500px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Payment Verification', 'subtitle' => 'Review original proof, OCR fields, duplicate risk, and the automatic allocation preview before approval.'])

        <div class="mb-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <nav class="grid grid-cols-2 border-b border-slate-200 sm:grid-cols-4" aria-label="Payment verification status">
                @foreach (['PENDING' => 'Waiting for Finance', 'APPROVED' => 'Payment posted', 'REJECTED' => 'Not accepted', 'ALL' => 'Every submission'] as $status => $description)
                    <a href="{{ route('admin.finance.verification.index', array_filter(['status' => $status, 'q' => request('q')])) }}"
                       @class([
                           'flex min-w-0 items-center justify-between gap-2 border-b-4 px-3 py-3 transition sm:px-5 sm:py-4',
                           'border-amber-500 bg-amber-50 text-amber-950' => $statusFilter === $status && $status === 'PENDING',
                           'border-emerald-600 bg-emerald-50 text-emerald-950' => $statusFilter === $status && $status === 'APPROVED',
                           'border-rose-600 bg-rose-50 text-rose-950' => $statusFilter === $status && $status === 'REJECTED',
                           'border-slate-700 bg-slate-50 text-slate-950' => $statusFilter === $status && $status === 'ALL',
                           'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-900' => $statusFilter !== $status,
                       ])>
                        <span>
                            <span class="block text-sm font-black">{{ $status }}</span>
                            <span class="mt-0.5 hidden text-xs font-medium opacity-75 sm:block">{{ $description }}</span>
                        </span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-sm font-black shadow-sm ring-1 ring-slate-200">{{ $statusCounts[$status] }}</span>
                    </a>
                @endforeach
            </nav>

            <form class="grid gap-3 p-4 md:grid-cols-[1fr_auto]">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <input name="q" value="{{ request('q') }}" placeholder="Search family, receipt, or reference" class="rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <button class="rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-bold text-white">Search</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Receipt / Family</th><th class="px-5 py-3">Payment status</th><th class="px-5 py-3">Extracted details</th><th class="px-5 py-3">Risk</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($receipts as $receipt)
                            @php
                                $verificationOr = $receipt->paymentSubmission?->financeTransaction?->officialReceipt?->official_receipt_number;
                                $isApproved = $receipt->status === 'APPROVED';
                                $isRejected = $receipt->status === 'REJECTED';
                                $paymentStatus = $isApproved ? 'APPROVED' : ($isRejected ? 'REJECTED' : 'PENDING');
                            @endphp
                            <tr @class([
                                'border-l-4 transition hover:bg-slate-50',
                                'border-amber-400 bg-amber-50/30' => $paymentStatus === 'PENDING',
                                'border-emerald-500' => $paymentStatus === 'APPROVED',
                                'border-rose-500' => $paymentStatus === 'REJECTED',
                            ])>
                                <td data-label="Receipt / Family" class="px-5 py-4"><p class="font-bold text-slate-900">{{ $receipt->user?->name ?? 'Family account' }}</p><p class="mt-1 text-xs text-slate-500">{{ $verificationOr ? 'Official Receipt No. '.$verificationOr : 'Submission No. '.$receipt->paymentSubmission?->submission_number }}<br>{{ $receipt->paymentSubmission?->submitted_at?->format('M d, Y g:i A') }}</p></td>
                                <td data-label="Payment status" class="px-5 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1.5 text-xs font-black tracking-wide',
                                        'bg-amber-100 text-amber-800 ring-1 ring-amber-200' => $paymentStatus === 'PENDING',
                                        'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' => $paymentStatus === 'APPROVED',
                                        'bg-rose-100 text-rose-800 ring-1 ring-rose-200' => $paymentStatus === 'REJECTED',
                                    ])>{{ $paymentStatus }}</span>
                                    <p class="mt-1.5 text-xs font-medium text-slate-500">{{ $paymentStatus === 'PENDING' ? 'Finance action needed' : ($paymentStatus === 'APPROVED' ? 'Payment posted' : 'Payment not accepted') }}</p>
                                </td>
                                <td data-label="Extracted details" class="px-5 py-4"><p class="font-extrabold text-slate-900">₱{{ number_format((float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount), 2) }}</p><div class="mt-1 flex flex-wrap gap-1"><span class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-bold text-sky-700">Online Payment</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $receipt->provider ?: 'Other' }}</span></div><p class="mt-1 text-xs text-slate-500">Ref {{ $receipt->reference_number ?: 'Not detected' }}</p></td>
                                <td data-label="Risk" class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-bold', 'bg-rose-100 text-rose-700' => !in_array($receipt->duplicate_status, ['UNIQUE','CLEAR']), 'bg-emerald-100 text-emerald-700' => in_array($receipt->duplicate_status, ['UNIQUE','CLEAR'])])>{{ str_replace('_', ' ', $receipt->duplicate_status) }}</span></td>
                                <td data-label="Action" class="px-5 py-4 text-right"><a href="{{ route('admin.finance.verification.show', $receipt) }}" class="font-bold text-emerald-700">{{ $paymentStatus === 'PENDING' ? 'Review' : 'View' }} →</a></td>
                            </tr>
                        @empty
                            <tr><td data-label="" colspan="5" class="px-5 py-12 text-center text-slate-500">No receipts match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $receipts->links() }}</div>
        </div>
    </div>
</x-admin-layout>
