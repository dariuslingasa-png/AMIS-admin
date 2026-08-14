<x-admin-layout
    title="Finance Dashboard"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => null],
        ['label' => 'Dashboard', 'href' => null],
    ]"
>
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Finance Dashboard',
            'subtitle' => 'One workspace for online verification, onsite collections, family balances, receipts, and reporting.'
        ])

        <!-- Telemetry KPI Metrics Grid -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @php
                $metricConfigs = [
                    ['Pending verification', $metrics['pending'], 'Needs Finance action', 'clock', 'amber'],
                    ['Needs review', $metrics['needs_review'], 'Low confidence or unclear', 'alert-circle', 'orange'],
                    ['Possible duplicates', $metrics['duplicates'], 'Review before approval', 'copy', 'rose'],
                    ['Reupload required', $metrics['reupload'], 'Parent action needed', 'refresh-cw', 'slate'],
                    ['Approved today', $metrics['approved_today'], 'Online payments', 'check-circle-2', 'emerald'],
                    ['Onsite today', $metrics['onsite_today'], 'Counter payments', 'hand-coins', 'teal'],
                    ['Total collected today', '₱'.number_format($metrics['total_today'], 2), 'Approved only', 'wallet', 'emerald'],
                    ['Family outstanding', '₱'.number_format($metrics['outstanding'], 2), 'Current and overdue', 'credit-card', 'slate'],
                ];
            @endphp

            @foreach ($metricConfigs as [$label, $value, $hint, $icon, $tone])
                @php
                    $iconBg = match($tone) {
                        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'orange' => 'bg-orange-50 text-orange-700 border-orange-200',
                        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
                        'teal' => 'bg-teal-50 text-teal-700 border-teal-200',
                        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };
                @endphp
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs transition hover:shadow-md hover:border-slate-300 flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">{{ $label }}</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl border {{ $iconBg }} shrink-0">
                            <i data-lucide="{{ $icon }}" class="h-4.5 w-4.5"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $value }}</p>
                        <p class="mt-1 text-xs font-medium text-slate-400">{{ $hint }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Verification Queue and Recent Transactions -->
        <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <!-- Left: Verification Queue -->
            <section class="rounded-3xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                            <i data-lucide="badge-check" class="h-4.5 w-4.5"></i>
                        </div>
                        <div>
                            <h2 class="font-extrabold text-slate-900 text-base">Verification Queue</h2>
                            <p class="text-xs text-slate-500">Newest payment receipts that need Finance verification.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.finance.verification.index') }}" class="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-700 hover:text-emerald-800 hover:underline">
                        <span>View all</span>
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($reviewQueue as $receipt)
                        <a href="{{ route('admin.finance.verification.show', $receipt) }}" class="grid gap-2 px-6 py-4 hover:bg-slate-50/70 sm:grid-cols-[1fr_auto] sm:items-center transition group">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 group-hover:text-emerald-700 transition">{{ $receipt->user?->name ?? 'Family Account' }}</p>
                                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $receipt->submission_id }} · {{ $receipt->provider ?: 'Payment Proof' }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="font-black text-slate-900 text-base tracking-tight">₱{{ number_format((float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount), 2) }}</p>
                                <span class="inline-flex items-center rounded-md bg-amber-50 border border-amber-200 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-800 mt-0.5">
                                    {{ str_replace('_', ' ', $receipt->status) }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 mb-2">
                                <i data-lucide="check-check" class="h-5 w-5"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-800">The verification queue is clear.</p>
                            <p class="text-xs text-slate-400 mt-0.5">No pending payment receipts at this moment.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- Right: Recent Transactions -->
            <section class="rounded-3xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4.5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                            <i data-lucide="arrow-left-right" class="h-4.5 w-4.5"></i>
                        </div>
                        <div>
                            <h2 class="font-extrabold text-slate-900 text-base">Recent Transactions</h2>
                            <p class="text-xs text-slate-500">Latest approved online and onsite payments.</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.finance.transactions.index') }}" class="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-700 hover:text-emerald-800 hover:underline">
                        <span>View all</span>
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse ($recent as $transaction)
                        <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="flex items-center justify-between gap-4 px-6 py-3.5 hover:bg-slate-50/70 transition group">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 group-hover:text-emerald-700 transition">{{ $transaction->family?->name }}</p>
                                <p class="text-xs text-slate-400 truncate mt-0.5">OR No. {{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number }} · {{ $transaction->payment_source_label }} · {{ $transaction->payment_method_label }}</p>
                            </div>
                            <p class="whitespace-nowrap font-black text-slate-900 text-sm tracking-tight shrink-0">₱{{ number_format((float) $transaction->amount, 2) }}</p>
                        </a>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500 mb-2">
                                <i data-lucide="inbox" class="h-5 w-5"></i>
                            </div>
                            <p class="text-sm font-bold text-slate-800">No Finance transactions yet.</p>
                            <p class="text-xs text-slate-400 mt-0.5">New payments will appear here automatically.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
