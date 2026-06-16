<x-admin-layout title="Security Metrics">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-rose-700/30 bg-gradient-to-br from-rose-900 via-rose-700 to-pink-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Telemetry</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Security Metrics</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        System security monitoring, rate limit telemetry, and authentication analysis dashboard.
                    </p>
                </div>
            </div>
        </section>

        <!-- KPI Cards -->
        <div class="grid gap-6 sm:grid-cols-2">
            <x-card class="bg-white border-slate-200">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Rate Limit Blocks (429)</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-2">{{ number_format($total429) }}</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i data-lucide="shield-alert" class="h-6 w-6"></i>
                    </div>
                </div>
            </x-card>
            <x-card class="bg-white border-slate-200">
                <div class="p-6 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Failed Login Attempts</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-2">{{ number_format($totalFailedLogins) }}</h3>
                    </div>
                    <div class="h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i data-lucide="lock" class="h-6 w-6"></i>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Top Offending IPs -->
            <x-card title="Top Offending IP Addresses" subtitle="IPs with the highest rate limit blocks">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 font-extrabold uppercase tracking-wider text-slate-400">
                                    <th class="pb-3">IP Address</th>
                                    <th class="pb-3 text-right">Block Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                                @forelse ($topOffendingIps as $ip)
                                    <tr>
                                        <td class="py-3">{{ $ip->ip_address }}</td>
                                        <td class="py-3 text-right text-rose-600">{{ $ip->count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-6 text-center text-slate-400">No blocks logged.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>

            <!-- Most Targeted Endpoints -->
            <x-card title="Most Targeted Endpoints" subtitle="Routes triggering rate limiting the most">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-100 font-extrabold uppercase tracking-wider text-slate-400">
                                    <th class="pb-3">Endpoint</th>
                                    <th class="pb-3 text-right">Hit Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                                @forelse ($mostTargetedEndpoints as $ep)
                                    <tr>
                                        <td class="py-3"><code class="bg-slate-100 px-2 py-1 rounded text-slate-600">{{ $ep->endpoint_path }}</code></td>
                                        <td class="py-3 text-right text-rose-600">{{ $ep->count }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-6 text-center text-slate-400">No blocks logged.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>
        </div>

        <!-- Recent Security Events -->
        <x-card title="Recent Security Telemetry" subtitle="Real-time log of security events">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-100 font-extrabold uppercase tracking-wider text-slate-400">
                            <th class="px-6 py-3">Event</th>
                            <th class="px-6 py-3">Identifier</th>
                            <th class="px-6 py-3">IP Address</th>
                            <th class="px-6 py-3">Client / Browser</th>
                            <th class="px-6 py-3">Detail</th>
                            <th class="px-6 py-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse ($recentEvents as $event)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 ring-1 ring-rose-100">
                                        {{ str_replace('_', ' ', $event->event) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $event->email ?: 'N/A' }}</td>
                                <td class="px-6 py-4">{{ $event->ip_address }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <span>{{ $event->browser }}</span>
                                        <span class="text-[10px] text-slate-400">({{ $event->device }})</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500">{{ $event->message }}</td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center font-bold text-slate-400">No events logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-admin-layout>
