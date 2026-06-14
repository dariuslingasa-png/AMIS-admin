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
    </div>
</x-admin-layout>
