<x-admin-layout title="Aging Report">
    <div class="space-y-6">
        <!-- Hero Banner -->
        <section class="overflow-hidden rounded-3xl border border-amber-100 bg-gradient-to-br from-slate-950 via-amber-800 to-orange-700 p-6 text-white shadow-xl shadow-amber-900/10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-amber-50">Finance Management</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Aging Report</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-amber-50/90">Track overdue tuition balances by 30/60/90+ day buckets. Send payment reminders directly.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.finance.export-soa') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white/10 border border-white/20 px-5 py-3 text-sm font-black text-white transition hover:bg-white/20">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        Export All SOA
                    </a>
                    <a href="{{ route('admin.finance.dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-amber-700 shadow-lg shadow-amber-900/20 transition hover:bg-amber-50">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        Finance Dashboard
                    </a>
                </div>
            </div>
        </section>

        <!-- Summary Stats -->
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Current (Not Due)</div>
                <div class="mt-2 text-2xl font-black text-slate-950">{{ $stats['current_count'] }}</div>
                <div class="mt-1 text-xs font-semibold text-slate-500">PHP {{ number_format($stats['current_amount'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-amber-600">1–30 Days Overdue</div>
                <div class="mt-2 text-2xl font-black text-amber-700">{{ $stats['days_30_count'] }}</div>
                <div class="mt-1 text-xs font-semibold text-amber-600">PHP {{ number_format($stats['days_30_amount'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-orange-100 bg-orange-50 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-orange-600">31–60 Days Overdue</div>
                <div class="mt-2 text-2xl font-black text-orange-700">{{ $stats['days_60_count'] }}</div>
                <div class="mt-1 text-xs font-semibold text-orange-600">PHP {{ number_format($stats['days_60_amount'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-rose-100 bg-rose-50 p-5 shadow-xs">
                <div class="text-[10px] font-black uppercase tracking-wider text-rose-600">60+ Days Overdue</div>
                <div class="mt-2 text-2xl font-black text-rose-700">{{ $stats['days_90_count'] }}</div>
                <div class="mt-1 text-xs font-semibold text-rose-600">PHP {{ number_format($stats['days_90_amount'], 2) }}</div>
            </div>
        </div>

        <!-- Overdue Accounts Table -->
        @foreach (['days_30' => ['1–30 Days', 'amber'], 'days_60' => ['31–60 Days', 'orange'], 'days_90' => ['60+ Days', 'rose']] as $bucket => [$label, $color])
            @if ($aging[$bucket]->isNotEmpty())
                <x-card title="{{ $label }} Overdue" subtitle="{{ $aging[$bucket]->count() }} accounts — PHP {{ number_format($aging[$bucket]->sum('remaining_balance'), 2) }} total">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white text-[11px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Grade</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                    <th class="px-4 py-3">Oldest Unpaid</th>
                                    <th class="px-4 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($aging[$bucket] as $account)
                                    @php
                                        $oldestUnpaid = $account->monthlyBillings->where('status', 'unpaid')->sortBy('due_date')->first();
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.soa.show', $account) }}" class="font-black text-slate-950 hover:text-amber-700">
                                                {{ $account->student?->applicant?->full_name ?? 'Student' }}
                                            </a>
                                            <div class="text-[10px] font-semibold text-slate-400">{{ $account->student?->student_number ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-slate-700">{{ $account->grade_abbr }}</td>
                                        <td class="px-4 py-3 text-right font-black text-{{ $color }}-700 tabular-nums">PHP {{ number_format((float) $account->remaining_balance, 2) }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-600">{{ $oldestUnpaid?->month_name ?? '-' }} ({{ $oldestUnpaid?->due_date ?? '-' }})</td>
                                        <td class="px-4 py-3 text-center">
                                            <form method="POST" action="{{ route('admin.finance.send-reminder', $account) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-100 px-3 py-1.5 text-xs font-black text-amber-800 transition hover:bg-amber-200" onclick="return confirm('Send payment reminder email?')">
                                                    <i data-lucide="mail" class="h-3 w-3"></i> Remind
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        @endforeach

        @if ($stats['total_overdue'] === 0)
            <x-card>
                <div class="py-10 text-center">
                    <i data-lucide="check-circle" class="mx-auto h-10 w-10 text-emerald-400"></i>
                    <p class="mt-3 text-sm font-black text-slate-700">No overdue accounts. All payments are current.</p>
                </div>
            </x-card>
        @endif
    </div>
</x-admin-layout>
