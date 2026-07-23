<x-admin-layout title="DevOps Control Center">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-700/30 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">System Management</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight flex items-center gap-3">
                        <i data-lucide="cpu" class="w-8 h-8 text-indigo-400"></i>
                        <span>DevOps Operations & Control Center</span>
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Environment inspection, database table defragmentation, maintenance mode switches, queue workers, and server state management.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('admin.system-management.health.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white/10 border border-white/20 px-4 py-2.5 text-xs font-black text-white hover:bg-white/20 transition cursor-pointer backdrop-blur-xs">
                        <i data-lucide="activity" class="w-4 h-4 text-emerald-300"></i>
                        <span>System Health</span>
                    </a>
                    <a href="{{ route('admin.system-management.logs.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-sky-500/20 border border-sky-400/40 px-4 py-2.5 text-xs font-black text-sky-200 hover:bg-sky-500/30 transition cursor-pointer backdrop-blur-xs">
                        <i data-lucide="terminal" class="w-4 h-4 text-sky-300"></i>
                        <span>Live Logs</span>
                    </a>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Grid Layout: Maintenance Switch & Environment Audit -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- 1. System Maintenance Mode Control -->
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Public Access Status</span>
                        @if($isMaintenanceMode)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-700 ring-1 ring-amber-200">
                                <span class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
                                Maintenance Mode ACTIVE
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Portal LIVE to Public
                            </span>
                        @endif
                    </div>

                    <div class="mt-4">
                        <h3 class="text-lg font-black text-slate-900">System Maintenance Mode Switch</h3>
                        <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">
                            Lock public user access during major database updates or system upgrades. Generates a secret admin bypass key for logged-in administrators.
                        </p>
                    </div>

                    @if($maintenanceSecret)
                        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-2xl text-xs font-medium text-amber-900 space-y-1">
                            <span class="font-extrabold uppercase text-[10px] text-amber-700 tracking-wider">Secret Admin Bypass Link:</span>
                            <div class="font-mono text-[11px] break-all select-all font-bold text-amber-950 bg-amber-100/70 p-2 rounded-xl border border-amber-300/50">
                                {{ $maintenanceSecret }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">Toggle Maintenance State</span>
                    <form method="POST" action="{{ route('admin.system-management.devops.maintenance') }}" onsubmit="return confirm('Are you sure you want to toggle System Maintenance Mode?')">
                        @csrf
                        @if($isMaintenanceMode)
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-md cursor-pointer flex items-center gap-2">
                                <i data-lucide="play" class="w-4 h-4"></i>
                                <span>Turn OFF Maintenance Mode (Go LIVE)</span>
                            </button>
                        @else
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black transition shadow-md cursor-pointer flex items-center gap-2">
                                <i data-lucide="pause-circle" class="w-4 h-4"></i>
                                <span>Enable Maintenance Mode</span>
                            </button>
                        @endif
                    </form>
                </div>
            </div>

            <!-- 2. Environment Configuration Inspector -->
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-4 h-4 text-indigo-600"></i>
                        <span>Environment Config Inspector (`.env`)</span>
                    </h3>
                    <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-slate-100 text-slate-600">{{ $envAudit['app_env'] }}</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">APP_DEBUG</span>
                        <span class="text-xs font-extrabold {{ $envAudit['app_debug'] ? 'text-rose-600 font-black' : 'text-emerald-600' }}">
                            {{ $envAudit['app_debug'] ? 'TRUE (DEBUG ACTIVE)' : 'FALSE (SECURE)' }}
                        </span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">MAIL_MAILER</span>
                        <span class="text-xs font-extrabold text-slate-800 uppercase">{{ $envAudit['mail_driver'] }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">QUEUE_CONNECTION</span>
                        <span class="text-xs font-extrabold text-slate-800 uppercase">{{ $envAudit['queue_driver'] }}</span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="text-[10px] font-black uppercase text-slate-400 block">CACHE_STORE</span>
                        <span class="text-xs font-extrabold text-slate-800 uppercase">{{ $envAudit['cache_driver'] }}</span>
                    </div>
                </div>

                @if($envAudit['app_debug'])
                    <div class="mt-4 p-3 bg-rose-50 border border-rose-200 rounded-2xl text-xs font-bold text-rose-700 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                        <span>Security Alert: APP_DEBUG is set to TRUE. Set APP_DEBUG=false in production .env to prevent sensitive stack trace leaks.</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Grid Layout: Database Table Optimizer & Queue Worker Monitor -->
        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <!-- 3. Database Table Metrics & Optimizer -->
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Database Schema & Table Optimizer</h3>
                        <p class="text-xs font-semibold text-slate-400">Inspect table row counts, index sizes, and run defragmentation</p>
                    </div>
                    <form method="POST" action="{{ route('admin.system-management.devops.db-optimize') }}" onsubmit="return confirm('Defragment and optimize core database tables?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black transition shadow-md cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="database" class="w-3.5 h-3.5"></i>
                            <span>Optimize Tables</span>
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 font-black uppercase text-slate-400 text-[10px] border-b border-slate-100">
                            <tr>
                                <th class="p-3">Table Name</th>
                                <th class="p-3">Est. Rows</th>
                                <th class="p-3">Data Size</th>
                                <th class="p-3">Index Size</th>
                                <th class="p-3">Total Size</th>
                                <th class="p-3">Engine</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                            @forelse($dbTables as $t)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-3 font-bold font-mono text-slate-900">{{ $t['name'] }}</td>
                                    <td class="p-3 font-bold text-emerald-700">{{ number_format($t['rows']) }}</td>
                                    <td class="p-3 text-slate-600">{{ $t['data_size'] }}</td>
                                    <td class="p-3 text-slate-600">{{ $t['index_size'] }}</td>
                                    <td class="p-3 font-extrabold text-indigo-600">{{ $t['total_size'] }}</td>
                                    <td class="p-3 text-[10px] font-bold text-slate-400 uppercase">{{ $t['engine'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400 italic">No database table metrics retrieved.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Queue Jobs & Active Session Stats -->
            <div class="space-y-6">
                <!-- Queue Worker Card -->
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Queue Worker Status</h3>
                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-extrabold text-[10px] uppercase">{{ config('queue.default') }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-[10px] font-black uppercase text-slate-400 block">Pending Jobs</span>
                            <span class="text-xl font-black text-slate-900">{{ number_format($pendingJobs) }}</span>
                        </div>
                        <div class="p-3 bg-rose-50 rounded-2xl border border-rose-100">
                            <span class="text-[10px] font-black uppercase text-rose-500 block">Failed Jobs</span>
                            <span class="text-xl font-black text-rose-700">{{ number_format($failedJobs) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100">
                        <form method="POST" action="{{ route('admin.system-management.devops.queue.retry') }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-black transition cursor-pointer">
                                Retry Failed
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.system-management.devops.queue.flush') }}" onsubmit="return confirm('Clear all failed jobs from queue?')" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 text-[11px] font-black transition cursor-pointer">
                                Flush Queue
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Active Session Stats -->
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400">Active User Sessions</span>
                        <div class="rounded-xl bg-emerald-50 p-1.5 text-emerald-600">
                            <i data-lucide="users" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="text-3xl font-black text-slate-900">{{ number_format($activeSessionsCount) }}</span>
                        <span class="text-xs font-bold text-emerald-600">Active (30-min window)</span>
                    </div>
                    <p class="mt-2 text-[11px] font-semibold text-slate-400">
                        Logged-in user sessions currently connected to AMIS.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
