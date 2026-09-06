<x-admin-layout title="Finance Dashboard" :breadcrumbs="[['label' => 'Finance', 'href' => null], ['label' => 'Dashboard', 'href' => null]]">
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Finance Dashboard',
            'subtitle' => 'Review today’s collections, resolve payment issues, and open any student finance record from one place.',
        ])

        <section aria-labelledby="finance-summary-heading">
            <h2 id="finance-summary-heading" class="sr-only">Today’s Finance summary</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-finance.summary-card label="Collected Today" :value="'₱'.number_format($metrics['total_today'], 2)" hint="Approved online and onsite" icon="wallet" tone="emerald" :href="route('admin.finance.transactions.index', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'status' => 'APPROVED'])" />
                <x-finance.summary-card label="Approved Today" :value="$metrics['approved_today']" hint="Posted transactions" icon="badge-check" tone="emerald" :href="route('admin.finance.transactions.index', ['from' => now()->toDateString(), 'to' => now()->toDateString(), 'status' => 'APPROVED'])" />
                <x-finance.summary-card label="Pending Verification" :value="$metrics['pending']" hint="Waiting for Finance review" icon="clock-3" tone="amber" :href="route('admin.finance.verification.index', ['status' => 'PENDING'])" />
                <x-finance.summary-card label="Needs Attention" :value="$metrics['needs_attention']" hint="Review, duplicate, or reupload" icon="triangle-alert" tone="rose" :href="route('admin.finance.verification.index', ['status' => 'PENDING'])" />
                <x-finance.summary-card label="Historical Payments" :value="$metrics['historical_payments']" hint="All encoded legacy payments" icon="history" tone="violet" :href="route('admin.finance.transactions.index', ['source' => 'HISTORICAL'])" />
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Pending Payment Reviews</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Recent submissions that require a Finance decision.</p>
                </div>
                <a href="{{ route('admin.finance.verification.index', ['status' => 'PENDING']) }}" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">View all pending payments</a>
            </div>
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600">
                        <tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Grade / Section</th><th class="px-5 py-3 text-right">Amount</th><th class="px-5 py-3">Method</th><th class="px-5 py-3">Payment Date</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($reviewQueue as $receipt)
                            @php
                                $allocatedStudent = $receipt->paymentSubmission?->payments?->first()?->student;
                                $student = $allocatedStudent ?: $receipt->user?->students?->first();
                                $applicant = $student?->applicant ?: $receipt->user?->enrollmentApplicants?->first();
                                $studentName = $applicant?->full_name ?: $student?->full_name ?: $receipt->user?->name ?: 'Student account';
                                $grade = $student?->grade_level ?: $applicant?->grade_level ?: 'Not assigned';
                                $section = $student?->section;
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td data-label="Student" class="px-5 py-4"><p class="font-bold text-slate-950">{{ $studentName }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $student?->student_number ?: $applicant?->amis_student_id ?: $receipt->user?->name }}</p></td>
                                <td data-label="Grade / Section" class="px-5 py-4 text-slate-700">{{ $grade }}{{ $section ? ' / '.$section : '' }}</td>
                                <td data-label="Amount" class="px-5 py-4 text-right font-black text-slate-950">₱{{ number_format((float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount), 2) }}</td>
                                <td data-label="Method" class="px-5 py-4 text-slate-700">{{ $receipt->provider ?: 'Online payment' }}</td>
                                <td data-label="Payment date" class="px-5 py-4 text-slate-700">{{ $receipt->transaction_date?->format('M d, Y') ?: 'Not detected' }}</td>
                                <td data-label="Submitted" class="px-5 py-4 text-slate-700">{{ $receipt->paymentSubmission?->submitted_at?->format('M d, Y') ?: $receipt->created_at?->format('M d, Y') }}</td>
                                <td data-label="Status" class="px-5 py-4"><x-finance.status-badge :status="$receipt->status" /></td>
                                <td data-label="Action" class="px-5 py-4 text-right"><a href="{{ route('admin.finance.verification.show', $receipt) }}" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-emerald-700 px-3 text-xs font-bold text-white hover:bg-emerald-800">Review Payment</a></td>
                            </tr>
                        @empty
                            <tr><td data-label="" colspan="8" class="px-5 py-12 text-center"><i data-lucide="circle-check-big" class="mx-auto h-8 w-8 text-emerald-600"></i><p class="mt-2 font-bold text-slate-800">No payments are waiting for review.</p><p class="mt-1 text-sm text-slate-500">The verification queue is clear.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="quick-actions-heading">
            <div class="mb-3 flex items-center justify-between"><h2 id="quick-actions-heading" class="text-lg font-black text-slate-950">Quick Actions</h2><span class="text-sm text-slate-500">Common Finance tasks</span></div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Search Student', 'search', route('admin.finance.families.index')],
                    ['Record Payment', 'hand-coins', route('admin.finance.onsite.create')],
                    ['Student Accounts & SOA', 'users', route('admin.finance.families.index')],
                    ['Payment Records', 'arrow-left-right', route('admin.finance.transactions.index')],
                    ['Official Receipts', 'receipt-text', route('admin.finance.receipts.index')],
                    ['Monthly Reminders', 'bell-ring', route('admin.finance.monthly-reminders.index')],
                    ['Reports', 'chart-no-axes-combined', route('admin.finance.reports.index')],
                    ['Audit Log', 'history', route('admin.finance.audit.index')],
                ] as [$label, $icon, $href])
                    <a href="{{ $href }}" class="flex min-h-14 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-800 shadow-xs hover:border-emerald-300 hover:text-emerald-800"><i data-lucide="{{ $icon }}" class="h-4 w-4 text-emerald-700"></i><span>{{ $label }}</span></a>
                @endforeach
            </div>
        </section>
    </div>
</x-admin-layout>
