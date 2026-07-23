<x-admin-layout title="DevOps Control Center">
    <div class="space-y-6">
        <!-- Reusable Workspace Header Component -->
        <x-system-nav title="DevOps Operations & Control Center" subtitle="Environment inspection, database table defragmentation, maintenance mode switches, queue workers, and server state management." activeTab="devops" />

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
        <!-- 1. System Multi-Portal Maintenance & Access Control -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <i data-lucide="shield-alert" class="w-5 h-5 text-indigo-600"></i>
                        <span>AMIS Portals Maintenance & Shutdown Control</span>
                    </h3>
                    <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">
                        Individually or globally lock public user access during major database updates, grading periods, or scheduled maintenance.
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form method="POST" action="{{ route('admin.system-management.devops.maintenance') }}" onsubmit="return confirm('Lock ALL public portals (Enrollment, Teacher, Student) in Maintenance Mode?')">
                        @csrf
                        <input type="hidden" name="portal" value="all_on">
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black transition shadow-sm cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                            <span>Lock All Public</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.system-management.devops.maintenance') }}" onsubmit="return confirm('Bring ALL AMIS portals LIVE to the public?')">
                        @csrf
                        <input type="hidden" name="portal" value="all_off">
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-sm cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                            <span>Bring All LIVE</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Portal Grid (4 Portals) -->
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($portalsMaintenance as $key => $p)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 flex flex-col justify-between space-y-4 hover:border-slate-300 transition">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">{{ $p['badge'] }}</span>
                                @if($p['is_down'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-black uppercase text-amber-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600 animate-ping"></span>
                                        MAINTENANCE
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-black uppercase text-emerald-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        LIVE
                                    </span>
                                @endif
                            </div>

                            <h4 class="text-sm font-black text-slate-900 mt-2">{{ $p['name'] }}</h4>
                            <a href="https://{{ $p['domain'] }}" target="_blank" class="text-[11px] font-bold text-indigo-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                <span>{{ $p['domain'] }}</span>
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                            </a>

                            @if($p['secret'])
                                <div class="mt-3 p-2 bg-amber-50 border border-amber-200 rounded-xl text-[10px] space-y-1">
                                    <span class="font-extrabold uppercase text-[9px] text-amber-700 tracking-wider">Secret Bypass Link:</span>
                                    <div class="font-mono text-[10px] break-all select-all font-bold text-amber-950 bg-amber-100/70 p-1.5 rounded-lg border border-amber-300/50">
                                        {{ $p['secret'] }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-slate-200/80">
                            <form method="POST" action="{{ route('admin.system-management.devops.maintenance') }}" onsubmit="return confirm('Toggle Maintenance Mode for {{ $p['name'] }}?')">
                                @csrf
                                <input type="hidden" name="portal" value="{{ $key }}">
                                @if($p['is_down'])
                                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                                        <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                        <span>Turn LIVE</span>
                                    </button>
                                @else
                                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black transition shadow-xs cursor-pointer flex items-center justify-center gap-1.5">
                                        <i data-lucide="pause-circle" class="w-3.5 h-3.5"></i>
                                        <span>Maintenance Mode</span>
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                @endforeach
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
