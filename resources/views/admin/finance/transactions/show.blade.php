<x-admin-layout title="Transaction {{ $transaction->transaction_number }}">
    <div class="finance-page mx-auto max-w-[1240px] p-5 lg:p-8">
        @include('admin.finance._nav', [
            'title' => $transaction->transaction_number,
            'subtitle' => 'Payment record, automatic allocation, official receipt, and audit history.',
        ])

        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">Family</p>
                        <p class="mt-1 font-extrabold text-slate-900">{{ $transaction->family?->name }}</p>
                        <p class="text-xs text-slate-500">{{ $transaction->family?->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">Payment</p>
                        <p class="mt-1 text-2xl font-black text-slate-900">₱{{ number_format((float) $transaction->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">Status</p>
                        <span @class([
                            'mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold',
                            'bg-emerald-100 text-emerald-700' => $transaction->status === 'APPROVED',
                            'bg-rose-100 text-rose-700' => $transaction->status === 'REVERSED',
                        ])>{{ $transaction->status }}</span>
                        <p class="mt-2 text-xs text-slate-500">{{ $transaction->transaction_at?->format('M d, Y g:i A') }}</p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
                    <div><dt class="text-slate-500">Payment Source</dt><dd class="mt-1 font-bold text-slate-800">{{ $transaction->payment_source_label }}</dd></div>
                    <div><dt class="text-slate-500">Payment Method</dt><dd class="mt-1 font-bold text-slate-800">{{ $transaction->payment_method_label }}</dd></div>
                    <div><dt class="text-slate-500">Payment Reference</dt><dd class="mt-1 font-bold text-slate-800">{{ $transaction->reference_number ?: '—' }}</dd></div>
                    <div><dt class="text-slate-500">Processed by</dt><dd class="mt-1 font-bold text-slate-800">{{ $transaction->processor?->name }}</dd></div>
                    <div><dt class="text-slate-500">Family balance after</dt><dd class="mt-1 font-bold text-slate-800">₱{{ number_format((float) $transaction->family_balance_after, 2) }}</dd></div>
                </dl>

                @if ($transaction->officialReceipt)
                    <div class="mt-5 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Official Receipt No.</p>
                            <p class="mt-0.5 font-black text-emerald-950">{{ $transaction->officialReceipt->official_receipt_number }}</p>
                        </div>
                        <a href="{{ route('admin.finance.receipts.show', $transaction->officialReceipt) }}" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-center text-sm font-bold text-white hover:bg-emerald-800">View official receipt</a>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-extrabold text-slate-900">Automatic allocation</h2>
                        <p class="text-xs text-slate-500">Both online and onsite payments settle the oldest outstanding family balance first.</p>
                    </div>
                    @if ((float) $transaction->advance_credit > 0)
                        <span class="rounded-xl bg-violet-100 px-3 py-2 text-sm font-bold text-violet-800">Advance credit ₱{{ number_format((float) $transaction->advance_credit, 2) }}</span>
                    @endif
                </div>

                <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @forelse ($transaction->allocation_snapshot ?? [] as $index => $allocation)
                        @php
                            $alloc = is_array($allocation) ? $allocation : (array) $allocation;
                            $seq = $alloc['sequence'] ?? ($index + 1);
                            $month = $alloc['billing_month'] ?? ($alloc['month'] ?? 'Monthly Billing');
                            $studentName = $alloc['student_name'] ?? ($alloc['student'] ?? 'Demo Student');
                            $before = (float) ($alloc['balance_before'] ?? ($alloc['original_due'] ?? 0));
                            $applied = (float) ($alloc['applied_amount'] ?? ($alloc['allocated'] ?? ($alloc['amount_paid'] ?? 0)));
                            $remaining = (float) ($alloc['remaining_after'] ?? ($alloc['remaining_due'] ?? 0));
                        @endphp
                        <div class="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-bold text-slate-800">#{{ $seq }} · {{ $month }} · {{ $studentName }}</p>
                                <p class="text-xs text-slate-500">₱{{ number_format($before, 2) }} before · ₱{{ number_format($remaining, 2) }} remaining</p>
                            </div>
                            <p class="font-extrabold text-emerald-800">₱{{ number_format($applied, 2) }}</p>
                        </div>
                    @empty
                        <div class="p-5 text-center text-sm text-slate-500">No open billing; payment stored as advance credit.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                <h2 class="font-extrabold text-slate-900">Audit trail</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($audit as $entry)
                        <div class="border-l-2 border-slate-200 pl-4">
                            <p class="text-sm font-bold text-slate-800">{{ str_replace('_', ' ', $entry->event) }}</p>
                            <p class="text-xs text-slate-500">{{ $entry->created_at?->format('M d, Y g:i:s A') }}{{ $entry->reason ? ' · '.$entry->reason : '' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
