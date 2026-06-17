<x-admin-layout title="System Health">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-700/30 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">System Management</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">System Health</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Monitor live database latency, disk consumption, external API connections, and core service uptime thresholds.
                    </p>
                </div>
            </div>
        </section>

        <!-- Status Grid -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($healthStatus as $key => $status)
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Core Service Status</span>
                        @if ($status['connected'])
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-100">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Healthy
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-700 ring-1 ring-rose-100">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                Offline
                            </span>
                        @endif
                    </div>

                    <div class="mt-4">
                        <h3 class="text-base font-black text-slate-950">{{ $status['name'] }}</h3>
                        <p class="text-xs font-semibold text-slate-400 mt-1">{{ $status['version'] }}</p>
                    </div>

                    <div class="mt-6 border-t border-slate-100 pt-4 flex items-center justify-between text-xs font-bold text-slate-500">
                        <span>Metrics / Latency</span>
                        <span class="{{ $status['connected'] ? 'text-slate-800' : 'text-rose-600' }}">{{ $status['metrics'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Email Tracking Section -->
        <section class="space-y-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 text-white shadow-lg shadow-violet-500/25">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-950 tracking-tight">SMTP Email Tracking</h2>
                    <p class="text-xs font-semibold text-slate-400">Outgoing email volume, mailer usage, and recent activity</p>
                </div>
            </div>

            <!-- Volume Stats Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Today --}}
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-violet-50"></div>
                    <span class="relative text-[10px] font-black uppercase tracking-widest text-violet-500">Today</span>
                    <p class="relative mt-2 text-3xl font-black text-slate-950">{{ number_format($emailStats['today']) }}</p>
                    <p class="relative mt-1 text-xs font-semibold text-slate-400">emails sent</p>
                    @if ($emailStats['failed_today'] > 0)
                        <p class="relative mt-1.5 text-[10px] font-black text-rose-600">
                            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-rose-500 mr-1"></span>
                            {{ $emailStats['failed_today'] }} failed
                        </p>
                    @endif
                </div>

                {{-- This Week --}}
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-blue-50"></div>
                    <span class="relative text-[10px] font-black uppercase tracking-widest text-blue-500">This Week</span>
                    <p class="relative mt-2 text-3xl font-black text-slate-950">{{ number_format($emailStats['this_week']) }}</p>
                    <p class="relative mt-1 text-xs font-semibold text-slate-400">emails sent</p>
                </div>

                {{-- This Month --}}
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-emerald-50"></div>
                    <span class="relative text-[10px] font-black uppercase tracking-widest text-emerald-500">This Month</span>
                    <p class="relative mt-2 text-3xl font-black text-slate-950">{{ number_format($emailStats['this_month']) }}</p>
                    <p class="relative mt-1 text-xs font-semibold text-slate-400">emails sent</p>
                </div>

                {{-- Active Mailer --}}
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="absolute -right-3 -top-3 h-16 w-16 rounded-full bg-amber-50"></div>
                    <span class="relative text-[10px] font-black uppercase tracking-widest text-amber-500">Active Mailer</span>
                    <p class="relative mt-2 text-lg font-black text-slate-950 uppercase">{{ $emailStats['smtp_config']['default_mailer'] }}</p>
                    <p class="relative mt-1 text-xs font-semibold text-slate-400">{{ $emailStats['smtp_config']['from_address'] }}</p>
                </div>
            </div>

            {{-- 7-Day Send Volume Chart --}}
            @if (!empty($emailStats['daily_chart']))
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-4">7-Day Send Volume</h3>
                    @php
                        $maxCount = max(1, max(array_column($emailStats['daily_chart'], 'count')));
                    @endphp
                    <div class="flex items-end gap-2 h-32">
                        @foreach ($emailStats['daily_chart'] as $day)
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[10px] font-black text-slate-600">{{ $day['count'] }}</span>
                                <div class="w-full rounded-t-lg transition-all duration-500 {{ $day['count'] > 0 ? 'bg-gradient-to-t from-violet-600 to-violet-400' : 'bg-slate-100' }}"
                                     style="height: {{ $day['count'] > 0 ? max(8, ($day['count'] / $maxCount) * 100) : 4 }}%"></div>
                                <span class="text-[10px] font-bold text-slate-400 mt-1">{{ $day['day'] }}</span>
                                <span class="text-[9px] font-semibold text-slate-300">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                {{-- SMTP Configuration --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">SMTP Configuration</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        @foreach ($emailStats['smtp_config']['mailers'] as $name => $mailer)
                            <div class="px-6 py-3.5 flex items-center justify-between {{ $mailer['is_default'] ? 'bg-violet-50/50' : '' }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-black text-slate-800">{{ $name }}</span>
                                        @if ($mailer['is_default'])
                                            <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-violet-700">Default</span>
                                        @endif
                                    </div>
                                    <p class="text-xs font-semibold text-slate-400 mt-0.5 truncate">
                                        @if ($mailer['host'] !== '—')
                                            {{ $mailer['host'] }}:{{ $mailer['port'] }}
                                            <span class="text-slate-300 mx-1">·</span>
                                            {{ strtoupper($mailer['encryption']) }}
                                        @else
                                            {{ $mailer['transport'] }}
                                        @endif
                                    </p>
                                </div>
                                <span class="flex-shrink-0 inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-500">{{ $mailer['transport'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="border-t border-slate-100 px-6 py-3 bg-slate-50/50">
                        <p class="text-[10px] font-bold text-slate-400">
                            From: <span class="text-slate-600">{{ $emailStats['smtp_config']['from_name'] }} &lt;{{ $emailStats['smtp_config']['from_address'] }}&gt;</span>
                        </p>
                    </div>
                </div>

                {{-- Mailer Usage Breakdown --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Mailer Usage — This Month</h3>
                    </div>
                    @if (count($emailStats['mailer_breakdown']) > 0)
                        <div class="divide-y divide-slate-50">
                            @foreach ($emailStats['mailer_breakdown'] as $row)
                                @php
                                    $totalMonth = max(1, $emailStats['this_month']);
                                    $pct = round(($row->total / $totalMonth) * 100);
                                @endphp
                                <div class="px-6 py-3.5">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-black text-slate-800">{{ $row->mailer }}</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs font-bold text-emerald-600">{{ number_format($row->sent_count) }} sent</span>
                                            @if ($row->failed_count > 0)
                                                <span class="text-xs font-bold text-rose-500">{{ number_format($row->failed_count) }} failed</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-indigo-500 transition-all duration-700" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 mt-1">{{ number_format($row->total) }} total · {{ $pct }}% of monthly volume</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-6 py-10 text-center">
                            <svg class="mx-auto h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51" />
                            </svg>
                            <p class="mt-2 text-xs font-bold text-slate-400">No emails sent this month yet</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Email Activity --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Recent Email Activity</h3>
                    <span class="text-[10px] font-bold text-slate-300">Last 10 messages</span>
                </div>
                @if ($emailStats['recent']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/50">
                                    <th class="px-6 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Time</th>
                                    <th class="px-6 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Recipient</th>
                                    <th class="px-6 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Subject</th>
                                    <th class="px-6 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Mailer</th>
                                    <th class="px-6 py-2.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach ($emailStats['recent'] as $log)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <span class="text-xs font-semibold text-slate-600">{{ $log->sent_at?->format('M d, g:i A') ?? '—' }}</span>
                                        </td>
                                        <td class="px-6 py-3 max-w-[200px]">
                                            <span class="text-xs font-semibold text-slate-700 truncate block">{{ \Illuminate\Support\Str::limit($log->to_addresses, 40) }}</span>
                                        </td>
                                        <td class="px-6 py-3 max-w-[250px]">
                                            <span class="text-xs font-medium text-slate-500 truncate block">{{ \Illuminate\Support\Str::limit($log->subject, 50) }}</span>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">{{ $log->mailer }}</span>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            @if ($log->status === 'sent')
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-100">
                                                    <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                                    Sent
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-rose-700 ring-1 ring-rose-100">
                                                    <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                                                    Failed
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        <p class="mt-3 text-sm font-bold text-slate-400">No email activity recorded yet</p>
                        <p class="mt-1 text-xs text-slate-300">Emails will appear here automatically once the system sends them</p>
                    </div>
                @endif
            </div>

            @if (!$emailStats['available'])
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-amber-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div>
                            <p class="text-sm font-bold text-amber-800">Email tracking table not found</p>
                            <p class="mt-1 text-xs text-amber-600">Run <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-[11px]">php artisan migrate</code> to create the <code class="rounded bg-amber-100 px-1.5 py-0.5 font-mono text-[11px]">email_logs</code> table and start tracking.</p>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>

</x-admin-layout>
