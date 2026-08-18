<x-admin-layout title="Reminder Delivery Logs">
    <div class="space-y-6">

        <!-- ── HEADER ─────────────────────────────────────────────────────── -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">
                    <a href="{{ route('admin.finance.dashboard') }}" class="hover:underline">Finance</a>
                    <span>/</span>
                    <a href="{{ route('admin.finance.monthly-reminders.index', ['month' => $selectedMonth]) }}" class="hover:underline">Monthly Payment Reminder</a>
                    <span>/</span>
                    <span>Delivery Logs</span>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Reminder Delivery Logs
                </h1>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">
                    Full audit history of payment reminder dispatches, timestamps, attempts, and SMTP delivery states.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.finance.monthly-reminders.index', ['month' => $selectedMonth]) }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs transition flex items-center gap-1.5">
                    ← Back to Reminders
                </a>
            </div>
        </div>

        <!-- ── FILTER BAR ────────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.finance.monthly-reminders.history') }}"
                  class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Month Filter -->
                    <input type="month" name="month" value="{{ $selectedMonth }}"
                           class="text-xs font-bold bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-700 dark:text-slate-200">

                    <!-- Status Filter -->
                    <select name="status"
                            class="text-xs font-bold bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-700 dark:text-slate-200">
                        <option value="">All Statuses</option>
                        <option value="SENT" {{ $status === 'SENT' ? 'selected' : '' }}>SENT</option>
                        <option value="PENDING" {{ $status === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                        <option value="PROCESSING" {{ $status === 'PROCESSING' ? 'selected' : '' }}>PROCESSING</option>
                        <option value="RETRY" {{ $status === 'RETRY' ? 'selected' : '' }}>RETRY</option>
                        <option value="FAILED" {{ $status === 'FAILED' ? 'selected' : '' }}>FAILED</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="flex items-center gap-2 w-full md:w-72">
                    <input type="text" name="q" value="{{ $search }}"
                           placeholder="Search parent, student, email..."
                           class="w-full text-xs font-medium px-3.5 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500">
                    <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- ── LOGS TABLE ────────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/75 dark:bg-slate-900/50 text-slate-500 uppercase tracking-wider font-bold">
                            <th class="px-5 py-3.5">#</th>
                            <th class="px-4 py-3.5">Month</th>
                            <th class="px-4 py-3.5">Parent / Email</th>
                            <th class="px-4 py-3.5">Student(s)</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-center">Attempts</th>
                            <th class="px-4 py-3.5 text-right">Sent At</th>
                            <th class="px-4 py-3.5 text-left">Last Error / Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-medium">
                        @forelse($logs as $index => $log)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-xs">
                                    {{ $logs->firstItem() + $index }}
                                </td>

                                <td class="px-4 py-3.5 font-bold font-mono text-slate-700 dark:text-slate-300">
                                    {{ $log->billing_month }}
                                </td>

                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $log->parent_name ?: 'Parent' }}</p>
                                    <p class="font-mono text-xs text-slate-500">{{ $log->parent_email }}</p>
                                </td>

                                <td class="px-4 py-3.5 text-slate-700 dark:text-slate-300">
                                    {{ $log->student_names ?: '—' }}
                                </td>

                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $log->status_color }}">
                                        {{ $log->status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-600 dark:text-slate-400">
                                    {{ $log->attempts }}
                                </td>

                                <td class="px-4 py-3.5 text-right font-mono text-slate-500">
                                    {{ $log->sent_at ? $log->sent_at->format('M d, Y H:i') : '—' }}
                                </td>

                                <td class="px-4 py-3.5 text-xs text-rose-600 dark:text-rose-400 max-w-xs truncate" title="{{ $log->last_error }}">
                                    {{ $log->last_error ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    No reminder delivery audit logs found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
