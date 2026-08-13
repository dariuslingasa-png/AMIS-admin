<x-admin-layout title="Family SOA — {{ $family->name }}">
    <div class="finance-page mx-auto max-w-[1400px] p-5 lg:p-8">
        @include('admin.finance._nav', [
            'title' => $family->name . (($family->is_demo ?? false) ? ' (DEMO DATA)' : ''),
            'subtitle' => 'Consolidated Family Statement of Account. Approved payments always settle the oldest balance first.'
        ])

        @if ($family->is_demo ?? false)
            <div class="mb-5 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-xs font-bold text-amber-900 shadow-sm">
                <span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-black uppercase text-amber-900 mr-2">DEMO DATA</span>
                TEST / DEMO FAMILY ACCOUNT — All billing dues, balances, and allocations shown below are isolated demo data for workflow testing.
            </div>
        @endif

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-300">Outstanding balance</p>
                <p class="mt-2 text-2xl font-black">₱{{ number_format($outstanding->sum('remaining'), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-violet-700">Advance credit</p>
                <p class="mt-2 text-2xl font-black text-violet-950">₱{{ number_format($advanceCredit, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Family contact</p>
                <p class="mt-2 font-extrabold text-slate-900">{{ $family->email }}</p>
                <p class="text-xs text-slate-500">Family ID: {{ $family->id }}</p>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-slate-900">Outstanding billing</h2>
                        <p class="text-xs text-slate-500">Displayed in allocation order (oldest first).</p>
                    </div>
                    <a href="{{ route('admin.finance.onsite.create', ['family' => $family->id]) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">
                        Record onsite payment
                    </a>
                </div>

                <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @forelse ($outstanding as $row)
                        @php
                            $studentName = $row['student']?->full_name
                                ?? (isset($row['student']->applicant) ? $row['student']->applicant?->full_name : null)
                                ?? 'Student';
                            $monthLabel = isset($row['billing']->month_name)
                                ? $row['billing']->month_name
                                : (isset($row['billing']->due_date) ? $row['billing']->due_date->format('F Y') : 'Current Dues');
                            $dueDateLabel = isset($row['billing']->due_date) ? $row['billing']->due_date->format('M d, Y') : 'N/A';
                            $isOverdue = isset($row['billing']->due_date) && $row['billing']->due_date->isPast();
                        @endphp
                        <div class="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-bold text-slate-800">{{ $monthLabel }} · {{ $studentName }}</p>
                                <p class="text-xs text-slate-500">Original ₱{{ number_format($row['original'] ?? $row['original_amount'] ?? 0, 2) }} · Verified ₱{{ number_format($row['verified'] ?? $row['verified_paid'] ?? 0, 2) }} · Due {{ $dueDateLabel }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="font-extrabold text-slate-900">₱{{ number_format($row['remaining'], 2) }}</p>
                                <p class="text-xs font-bold {{ $isOverdue ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ $isOverdue ? 'OVERDUE' : 'OUTSTANDING' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm font-semibold text-emerald-700">All current family billings are fully paid.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-extrabold text-slate-900">Family students</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($family->enrollmentApplicants as $applicant)
                        @if ($applicant->student?->account)
                            @php
                                $sName = $applicant->full_name ?? ($applicant->student?->full_name ?? 'Student');
                                $sGrade = $applicant->grade_level ?? ($applicant->student?->grade_level ?? '');
                                $sId = $applicant->amis_student_id ?? ($applicant->student?->amis_student_id ?? '');
                                $sBalance = (float) ($applicant->student->account->remaining_balance ?? 0);
                            @endphp
                            <details class="rounded-xl border border-slate-200 bg-slate-50/50">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3">
                                    <div>
                                        <p class="font-bold text-slate-800">{{ $sName }}</p>
                                        <p class="text-xs text-slate-500">{{ $sGrade }} · ID {{ $sId }}</p>
                                    </div>
                                    <p class="font-extrabold text-slate-900">₱{{ number_format($sBalance, 2) }}</p>
                                </summary>
                                <div class="border-t border-slate-100 bg-white px-4 py-3 text-xs text-slate-500">
                                    School year {{ $applicant->student->account->school_year ?? '2026-2027' }} · Account status <span class="font-bold uppercase text-emerald-700">{{ $applicant->student->account->status ?? 'ACTIVE' }}</span>
                                </div>
                            </details>
                        @endif
                    @endforeach
                </div>
            </section>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-extrabold text-slate-900">Payment history</h2>
                <p class="text-xs text-slate-500">Online and onsite payments with official receipt numbers.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($transactions as $transaction)
                    <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="grid gap-2 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Official Receipt No.</p>
                            <p class="font-bold text-slate-800">{{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $transaction->payment_method ?? 'CASH' }}</span>
                                <span class="text-xs text-slate-500">{{ isset($transaction->transaction_at) ? $transaction->transaction_at->format('M d, Y g:i A') : '' }}</span>
                            </div>
                        </div>
                        <p class="font-extrabold text-slate-900">₱{{ number_format((float)$transaction->amount, 2) }}</p>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-center text-xs font-bold text-emerald-700">{{ $transaction->status }}</span>
                    </a>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">No posted Finance transactions.</div>
                @endforelse
            </div>
            @if (method_exists($transactions, 'links'))
                <div class="border-t border-slate-100 px-5 py-4">{{ $transactions->links() }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
