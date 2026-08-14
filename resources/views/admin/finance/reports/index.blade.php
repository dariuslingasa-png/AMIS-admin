<x-admin-layout
    title="Finance Reports"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => route('admin.finance.dashboard')],
        ['label' => 'Reports', 'href' => null],
    ]"
>
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Finance Reports',
            'subtitle' => 'Approved collections, payment sources, methods, reversals, and total outstanding family balances.',
        ])

        <!-- Date Range Filter Form -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.finance.reports.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-3 w-full">
                <div class="flex-1">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1">From Date</label>
                    <input
                        name="from"
                        type="date"
                        value="{{ $from->format('Y-m-d') }}"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>
                <div class="flex-1">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-1">To Date</label>
                    <input
                        name="to"
                        type="date"
                        value="{{ $to->format('Y-m-d') }}"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3.5 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                    >
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="h-11 px-5 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:ring-4 focus:ring-slate-100">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
                    </button>
                    <a href="{{ route('admin.finance.reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="h-11 px-5 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-100">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Telemetry Summary Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Approved Collections</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i data-lucide="wallet" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">₱{{ number_format($summary['approved_amount'], 2) }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400">{{ $summary['approved_count'] }} transactions in date range</p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Online Payments</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700 border border-sky-200">
                        <i data-lucide="smartphone" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">₱{{ number_format($summary['online_amount'], 2) }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400">GCash, Maya, Bank transfers</p>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Onsite Collections</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700 border border-amber-200">
                        <i data-lucide="hand-coins" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">₱{{ number_format($summary['onsite_amount'], 2) }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-400">Counter Cash and POS payments</p>
                </div>
            </div>

            <div class="rounded-3xl border border-rose-200 bg-rose-50/50 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-rose-800">Reversed Amount</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700 border border-rose-200">
                        <i data-lucide="rotate-ccw" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-black text-rose-950 tracking-tight">₱{{ number_format($summary['reversed_amount'], 2) }}</p>
                    <p class="mt-1 text-xs font-medium text-rose-700">Reversals retain original OR</p>
                </div>
            </div>

            <div class="rounded-3xl border border-amber-200 bg-amber-50/50 p-5 shadow-xs flex flex-col justify-between sm:col-span-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-amber-800">Total Family Outstanding</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 border border-amber-200">
                        <i data-lucide="users" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl sm:text-3xl font-black text-amber-950 tracking-tight">₱{{ number_format($summary['outstanding'], 2) }}</p>
                    <p class="mt-1 text-xs font-medium text-amber-800">Aggregated across all current and overdue student accounts</p>
                </div>
            </div>
        </div>

        <!-- Collections by Method -->
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i data-lucide="pie-chart" class="h-4.5 w-4.5"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-900 text-base">Collections by Payment Method</h2>
                    <p class="text-xs text-slate-500">Breakdown of collections across all payment methods within selected date range.</p>
                </div>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($byMethod as $method)
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 transition hover:bg-slate-100/60">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">{{ $method->payment_method }}</p>
                        <p class="mt-2 text-xl font-black text-slate-900 tracking-tight">₱{{ number_format((float)$method->total, 2) }}</p>
                        <p class="mt-1 text-xs font-medium text-slate-400">{{ $method->count }} payment(s)</p>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-sm text-slate-400 italic">
                        No approved payments recorded in this date range.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-admin-layout>
