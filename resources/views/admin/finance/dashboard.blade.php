<x-admin-layout title="Finance Dashboard">
    <div class="finance-page mx-auto max-w-[1500px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Finance Dashboard', 'subtitle' => 'One workspace for online verification, onsite collections, family balances, receipts, and reporting.'])

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Pending verification', $metrics['pending'], 'Needs Finance action', 'amber'],
                ['Needs review', $metrics['needs_review'], 'Low confidence or unclear', 'orange'],
                ['Possible duplicates', $metrics['duplicates'], 'Review before approval', 'rose'],
                ['Reupload required', $metrics['reupload'], 'Parent action needed', 'slate'],
                ['Approved today', $metrics['approved_today'], 'Online payments', 'emerald'],
                ['Onsite today', $metrics['onsite_today'], 'Counter payments', 'teal'],
                ['Total collected today', '₱'.number_format($metrics['total_today'], 2), 'Approved only', 'blue'],
                ['Family outstanding', '₱'.number_format($metrics['outstanding'], 2), 'Current and overdue', 'violet'],
            ] as [$label, $value, $hint, $tone])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-slate-900">{{ $value }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div><h2 class="font-extrabold text-slate-900">Verification queue</h2><p class="text-xs text-slate-500">Newest receipts that need a Finance decision.</p></div>
                    <a href="{{ route('admin.finance.verification.index') }}" class="text-sm font-bold text-emerald-700">View all →</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($reviewQueue as $receipt)
                        <a href="{{ route('admin.finance.verification.show', $receipt) }}" class="grid gap-2 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div><p class="font-bold text-slate-800">{{ $receipt->user?->name ?? 'Family account' }}</p><p class="text-xs text-slate-500">{{ $receipt->submission_id }} · {{ $receipt->provider ?: 'Payment proof' }}</p></div>
                            <div class="sm:text-right"><p class="font-extrabold text-slate-900">₱{{ number_format((float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount), 2) }}</p><span class="text-xs font-bold text-amber-700">{{ str_replace('_', ' ', $receipt->status) }}</span></div>
                        </a>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500">The verification queue is clear.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-extrabold text-slate-900">Recent transactions</h2><p class="text-xs text-slate-500">Latest approved online and onsite payments.</p></div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recent as $transaction)
                        <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 hover:bg-slate-50">
                            <div class="min-w-0"><p class="truncate text-sm font-bold text-slate-800">{{ $transaction->family?->name }}</p><p class="text-xs text-slate-500">OR No. {{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number }} · {{ $transaction->payment_source_label }} · {{ $transaction->payment_method_label }}</p></div>
                            <p class="whitespace-nowrap font-extrabold text-slate-900">₱{{ number_format((float) $transaction->amount, 2) }}</p>
                        </a>
                    @empty
                        <div class="px-5 py-10 text-center text-sm text-slate-500">No Finance transactions yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
