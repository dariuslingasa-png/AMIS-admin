<x-admin-layout title="Finance Audit Log" :breadcrumbs="[['label' => 'Finance', 'href' => route('admin.finance.dashboard')], ['label' => 'Audit Log', 'href' => null]]">
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Audit Log',
            'subtitle' => 'A permanent, readable history of payment approvals, corrections, voids, SOA changes, and other Finance actions.',
        ])

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.finance.audit.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(260px,1fr)_220px_160px_160px_auto]">
                <div class="relative"><i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input name="q" value="{{ request('q') }}" placeholder="Search user, student, transaction, or reason..." class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-4 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"></div>
                <select name="event" aria-label="Action type" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"><option value="">All actions</option>@foreach($events as $event)<option value="{{ $event }}" @selected(request('event') === $event)>{{ str($event)->replace('_', ' ')->title() }}</option>@endforeach</select>
                <input name="from" type="date" value="{{ request('from') }}" aria-label="From date" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <input name="to" type="date" value="{{ request('to') }}" aria-label="To date" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                <div class="flex gap-2"><button class="h-11 rounded-xl bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-800">Apply Filters</button>@if(request()->hasAny(['q','event','from','to']))<a href="{{ route('admin.finance.audit.index') }}" class="inline-flex h-11 items-center px-2 text-sm font-bold text-slate-600 hover:text-slate-950">Clear</a>@endif</div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600"><tr><th class="px-5 py-3">Date &amp; Time</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Action</th><th class="px-5 py-3">Student / Account</th><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Change</th><th class="px-5 py-3">Reason</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($auditLogs as $entry)
                            @php
                                $action = match(strtoupper($entry->event)) {
                                    'ONLINE_PAYMENT_APPROVED' => 'Payment Approved',
                                    'ONSITE_PAYMENT_RECORDED' => 'Payment Created',
                                    'HISTORICAL_PAYMENT_ENCODED' => 'Historical Payment Created',
                                    'HISTORICAL_PAYMENT_UPDATED', 'PAYMENT_UPDATED', 'PAYMENT_RECORD_UPDATED' => 'Payment Edited',
                                    'HISTORICAL_PAYMENT_VOIDED', 'PAYMENT_VOIDED', 'PAYMENT_RECORD_VOIDED' => 'Payment Voided',
                                    'PAYMENT_REJECTED' => 'Payment Rejected',
                                    default => str($entry->event)->replace('_', ' ')->title(),
                                };
                                $changes = collect($entry->changes ?? []);
                                $previous = $changes->get('before') ?? $changes->get('old') ?? $changes->get('previous');
                                $new = $changes->get('after') ?? $changes->get('new') ?? $changes->get('current');
                                $oldValues = is_array($changes->get('old')) ? $changes->get('old') : [];
                                $newValues = is_array($changes->get('new')) ? $changes->get('new') : [];
                                $changedFields = collect(array_unique(array_merge(array_keys($oldValues), array_keys($newValues))))->filter(fn($key) => ($oldValues[$key] ?? null) != ($newValues[$key] ?? null));
                                $studentName = $entry->student?->applicant?->full_name ?: $entry->student?->full_name;
                                $transaction = $entry->transaction;
                            @endphp
                            <tr class="align-top hover:bg-slate-50/60">
                                <td data-label="Date & Time" class="whitespace-nowrap px-5 py-4"><p class="font-semibold text-slate-900">{{ $entry->created_at?->format('M d, Y') }}</p><p class="text-xs text-slate-500">{{ $entry->created_at?->format('g:i:s A') }}</p></td>
                                <td data-label="User" class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $entry->actor?->name ?: 'System' }}</p><p class="text-xs text-slate-500">{{ $entry->actor?->email }}</p></td>
                                <td data-label="Action" class="px-5 py-4"><span class="font-bold text-slate-900">{{ $action }}</span></td>
                                <td data-label="Student / Account" class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $studentName ?: $transaction?->family?->name ?: 'Not linked' }}</p>@if($entry->student?->student_number)<p class="text-xs text-slate-500">{{ $entry->student->student_number }}</p>@endif</td>
                                <td data-label="Transaction" class="px-5 py-4">@if($transaction)<a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="font-mono text-xs font-bold text-emerald-700 hover:underline">{{ $transaction->officialReceipt?->official_receipt_number ?: $transaction->transaction_number }}</a><p class="mt-1 font-bold text-slate-900">₱{{ number_format((float)($entry->amount ?? $transaction->amount), 2) }}</p>@else<span class="text-slate-400">—</span>@endif</td>
                                <td data-label="Change" class="max-w-xs px-5 py-4 text-xs text-slate-600">
                                    @if($changedFields->isNotEmpty())
                                        <div class="space-y-1.5">@foreach($changedFields as $field)<p><span class="font-semibold text-slate-800">{{ str($field)->replace('_', ' ')->title() }}:</span><br><span class="text-slate-500">{{ is_scalar($oldValues[$field] ?? null) ? ($oldValues[$field] ?? '—') : 'See details' }}</span> <span aria-hidden="true">→</span> <span class="font-semibold text-slate-800">{{ is_scalar($newValues[$field] ?? null) ? ($newValues[$field] ?? '—') : 'See details' }}</span></p>@endforeach</div>
                                    @elseif(is_array($changes->get('status')))
                                        <p><span class="font-semibold">Status:</span> {{ $changes->get('status')['old'] ?? '—' }} → {{ $changes->get('status')['new'] ?? '—' }}</p>
                                    @elseif($previous !== null || $new !== null)<p><span class="font-semibold">Previous:</span> {{ is_scalar($previous) ? $previous : 'See details' }}</p><p class="mt-1"><span class="font-semibold">New:</span> {{ is_scalar($new) ? $new : 'See details' }}</p>@elseif($changes->isNotEmpty())<span>{{ $changes->count() }} recorded change(s)</span>@else<span class="text-slate-400">No value change</span>@endif
                                </td>
                                <td data-label="Reason" class="max-w-sm px-5 py-4"><p class="text-sm text-slate-700">{{ $entry->reason ?: 'No reason required for this action.' }}</p>@if(!empty($entry->changes) || !empty($entry->allocation))<details class="mt-2"><summary class="cursor-pointer text-xs font-bold text-slate-600 hover:text-slate-950">View Technical Details</summary><pre class="mt-2 max-w-md overflow-x-auto rounded-lg bg-slate-950 p-3 text-[11px] leading-5 text-slate-100">{{ json_encode(['changes' => $entry->changes, 'allocation' => $entry->allocation], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>@endif</td>
                            </tr>
                        @empty
                            <tr><td data-label="" colspan="7" class="px-5 py-14 text-center"><i data-lucide="history" class="mx-auto h-8 w-8 text-slate-400"></i><p class="mt-2 font-bold text-slate-800">No audit records found.</p><p class="mt-1 text-sm text-slate-500">Try clearing the current filters.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($auditLogs->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $auditLogs->links() }}</div>@endif
        </section>
    </div>
</x-admin-layout>
