<x-admin-layout title="Email Composer & Bulk Messaging">
    <div class="space-y-6">
        <!-- Header Banner -->
        <section class="overflow-hidden rounded-3xl border border-indigo-800/40 bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 p-6 text-white shadow-xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-black uppercase tracking-[0.2em] text-indigo-300 border border-indigo-400/30">
                        Institutional Email System
                    </span>
                    <h1 class="mt-3 text-3xl font-black tracking-tight flex items-center gap-3">
                        <i data-lucide="mail-plus" class="w-8 h-8 text-indigo-400"></i>
                        <span>Email Composer & Bulk Dispatch</span>
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-300">
                        Compose rich text HTML emails, select recipients from Student & Faculty records, attach documents, and dispatch bulk email queues with automatic Multi-SMTP failover.
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="{{ route('admin.email-composer.create') }}"
                       class="inline-flex h-12 items-center gap-2.5 rounded-2xl bg-gradient-to-r from-indigo-500 to-emerald-500 px-6 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-950/40 transition hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        <i data-lucide="pen-tool" class="w-4 h-4"></i>
                        <span>Compose New Email</span>
                    </a>
                    <a href="{{ route('admin.email-composer.templates') }}"
                       class="inline-flex h-12 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 text-xs font-black uppercase tracking-wider text-white backdrop-blur-xs transition hover:bg-white/20 cursor-pointer">
                        <i data-lucide="layout-template" class="w-4 h-4 text-indigo-300"></i>
                        <span>Templates Directory</span>
                    </a>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Metric KPI Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Sent -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-black uppercase tracking-wider">Total Emails Sent</span>
                    <div class="rounded-2xl bg-emerald-50 p-2 text-emerald-600">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900">{{ number_format($totalSent) }}</div>
                    <p class="mt-1 text-xs font-semibold text-emerald-600">Verified System Deliveries</p>
                </div>
            </div>

            <!-- Failed Delivery -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-black uppercase tracking-wider">Failed / Bounced</span>
                    <div class="rounded-2xl bg-rose-50 p-2 text-rose-600">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900">{{ number_format($totalFailed) }}</div>
                    <p class="mt-1 text-xs font-semibold text-rose-500">Bounced or Rejected Addresses</p>
                </div>
            </div>

            <!-- SMTP Failover State -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-black uppercase tracking-wider">Active SMTP Mailer</span>
                    <div class="rounded-2xl bg-indigo-50 p-2 text-indigo-600">
                        <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg font-black text-slate-900 uppercase truncate">{{ $smtpMetrics['active_mailer'] }}</div>
                    <p class="mt-1 text-xs font-semibold text-indigo-600">Multi-SMTP Pool Protection</p>
                </div>
            </div>

            <!-- Email Presets -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-black uppercase tracking-wider">Reusable Templates</span>
                    <div class="rounded-2xl bg-violet-50 p-2 text-violet-600">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900">{{ $presetTemplatesCount }}</div>
                    <p class="mt-1 text-xs font-semibold text-violet-600">Available Presets & Layouts</p>
                </div>
            </div>
        </div>

        <!-- Bulk Campaigns Monitor Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-800">Recent Bulk Email Campaigns</h3>
                    <p class="text-xs font-medium text-slate-500">Queue processing status and delivery completion counts</p>
                </div>
                <a href="{{ route('admin.email-composer.logs') }}" class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                    View Full Logs <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            @if(count($campaigns) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3">Campaign Title</th>
                                <th class="px-6 py-3">Target Group</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Progress / Delivery</th>
                                <th class="px-6 py-3">Date Dispatched</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                            @foreach($campaigns as $c)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-extrabold text-slate-900 block text-sm">{{ $c->title }}</span>
                                        <span class="text-slate-400 text-[11px] font-normal truncate block max-w-xs">{{ $c->subject }}</span>
                                    </td>
                                    <td class="px-6 py-4 uppercase font-bold text-slate-600">
                                        {{ str_replace('_', ' ', $c->recipient_type) }}
                                        @if($c->recipient_filter)
                                            <span class="text-indigo-600 block text-[11px] font-medium lowercase">({{ $c->recipient_filter }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($c->status === 'completed')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Completed
                                            </span>
                                        @elseif($c->status === 'sending')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-ping"></span> Sending Queue
                                            </span>
                                        @elseif($c->status === 'queued')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-50 text-amber-700 border border-amber-200">
                                                Queued
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-50 text-rose-700 border border-rose-200">
                                                Failed
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-between text-[11px] font-bold mb-1">
                                            <span>{{ $c->sent_count }} / {{ $c->recipient_count }} sent</span>
                                            @if($c->failed_count > 0)
                                                <span class="text-rose-500 font-extrabold">{{ $c->failed_count }} failed</span>
                                            @endif
                                        </div>
                                        @php
                                            $total = max(1, $c->recipient_count);
                                            $pct = min(100, round(($c->sent_count / $total) * 100));
                                        @endphp
                                        <div class="h-1.5 w-36 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-indigo-600 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 font-normal">
                                        {{ $c->created_at ? $c->created_at->format('M d, Y h:i A') : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center text-slate-400">
                    <i data-lucide="inbox" class="w-10 h-10 mx-auto text-slate-300 mb-2"></i>
                    <p class="font-bold text-sm">No Bulk Email Campaigns Created Yet</p>
                    <p class="text-xs text-slate-400 mt-1">Click "Compose New Email" to send official announcements or targeted emails.</p>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
