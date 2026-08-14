<x-admin-layout
    title="Payment Verification"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => route('admin.finance.dashboard')],
        ['label' => 'Payment Verification', 'href' => null],
    ]"
>
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Payment Verification',
            'subtitle' => 'Review original payment proof, OCR extracted fields, duplicate risk, and automatic allocation preview before approval.',
        ])

        <!-- Status Filter Tabs & Search Bar -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <nav class="grid grid-cols-2 border-b border-slate-200/80 sm:grid-cols-4" aria-label="Payment verification status">
                @foreach (['PENDING' => 'Waiting for Finance', 'APPROVED' => 'Payment posted', 'REJECTED' => 'Not accepted', 'ALL' => 'Every submission'] as $status => $description)
                    <a href="{{ route('admin.finance.verification.index', array_filter(['status' => $status, 'q' => request('q')])) }}"
                       @class([
                           'flex min-w-0 items-center justify-between gap-2 border-b-2 px-4 py-3.5 transition sm:px-6 sm:py-4',
                           'border-amber-500 bg-amber-50/60 text-amber-950 font-bold' => $statusFilter === $status && $status === 'PENDING',
                           'border-emerald-600 bg-emerald-50/60 text-emerald-950 font-bold' => $statusFilter === $status && $status === 'APPROVED',
                           'border-rose-600 bg-rose-50/60 text-rose-950 font-bold' => $statusFilter === $status && $status === 'REJECTED',
                           'border-slate-700 bg-slate-50 text-slate-950 font-bold' => $statusFilter === $status && $status === 'ALL',
                           'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-900 font-medium' => $statusFilter !== $status,
                       ])>
                        <span>
                            <span class="block text-xs font-black uppercase tracking-wider">{{ $status }}</span>
                            <span class="mt-0.5 hidden text-[11px] font-normal opacity-75 sm:block">{{ $description }}</span>
                        </span>
                        <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-black shadow-2xs ring-1 ring-slate-200">{{ $statusCounts[$status] }}</span>
                    </a>
                @endforeach
            </nav>

            <form class="grid gap-3 p-4 md:grid-cols-[1fr_auto]" method="GET" action="{{ route('admin.finance.verification.index') }}">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative">
                    <input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search by family name, submission number, or reference number..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                    >
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </div>
                </div>
                <button type="submit" class="h-11 px-6 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-100">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>
            </form>
        </div>

        <!-- Verification Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm text-slate-500 border-collapse">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Receipt / Family</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Extracted Details</th>
                            <th class="px-6 py-4">Fraud Risk</th>
                            <th class="px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($receipts as $receipt)
                            @php
                                $verificationOr = $receipt->paymentSubmission?->financeTransaction?->officialReceipt?->official_receipt_number;
                                $isApproved = $receipt->status === 'APPROVED';
                                $isRejected = $receipt->status === 'REJECTED';
                                $paymentStatus = $isApproved ? 'APPROVED' : ($isRejected ? 'REJECTED' : 'PENDING');
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition group">
                                <td data-label="Receipt / Family" class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/80 shrink-0">
                                            <i data-lucide="badge-check" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 group-hover:text-emerald-700 transition uppercase tracking-tight">{{ $receipt->user?->name ?? 'Family account' }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ $verificationOr ? 'OR No. '.$verificationOr : 'Sub No. '.$receipt->paymentSubmission?->submission_number }} · {{ $receipt->paymentSubmission?->submitted_at?->format('M d, Y · g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Payment status" class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-amber-50 text-amber-800 border border-amber-200' => $paymentStatus === 'PENDING',
                                        'bg-emerald-50 text-emerald-800 border border-emerald-200' => $paymentStatus === 'APPROVED',
                                        'bg-rose-50 text-rose-800 border border-rose-200' => $paymentStatus === 'REJECTED',
                                    ])>
                                        <span class="h-1.5 w-1.5 rounded-full {{ $paymentStatus === 'PENDING' ? 'bg-amber-500' : ($paymentStatus === 'APPROVED' ? 'bg-emerald-500' : 'bg-rose-500') }}"></span>
                                        {{ $paymentStatus }}
                                    </span>
                                </td>
                                <td data-label="Extracted details" class="px-6 py-4">
                                    <p class="text-base font-black text-slate-900 tracking-tight">₱{{ number_format((float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount), 2) }}</p>
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-flex items-center rounded-md bg-sky-50 border border-sky-200 px-2 py-0.5 text-xs font-bold text-sky-800">Online</span>
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $receipt->provider ?: 'Other' }}</span>
                                    </div>
                                    <p class="mt-1 text-[11px] font-mono text-slate-400">Ref: {{ $receipt->reference_number ?: 'Not detected' }}</p>
                                </td>
                                <td data-label="Risk" class="px-6 py-4">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-rose-50 text-rose-800 border border-rose-200' => !in_array($receipt->duplicate_status, ['UNIQUE','CLEAR']),
                                        'bg-emerald-50 text-emerald-800 border border-emerald-200' => in_array($receipt->duplicate_status, ['UNIQUE','CLEAR'])
                                    ])>
                                        <span class="h-1.5 w-1.5 rounded-full {{ in_array($receipt->duplicate_status, ['UNIQUE','CLEAR']) ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ str_replace('_', ' ', $receipt->duplicate_status) }}
                                    </span>
                                </td>
                                <td data-label="Action" class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.finance.verification.show', $receipt) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        <span>{{ $paymentStatus === 'PENDING' ? 'Review' : 'View' }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                                            <i data-lucide="inbox" class="h-10 w-10"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-base font-bold text-slate-800">No payment receipts found</h3>
                                            <p class="text-sm text-slate-500 max-w-sm mx-auto">No verification queue items matched these filters.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($receipts->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">{{ $receipts->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
