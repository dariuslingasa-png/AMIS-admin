<x-admin-layout title="Security Alerts">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-rose-700/30 bg-gradient-to-br from-rose-900 via-rose-700 to-pink-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Security Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Security Alerts</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Real-time threat detection telemetry, suspicious IP probes, and user lockout monitoring alerts.
                    </p>
                </div>
            </div>
        </section>

        <!-- Alerts list -->
        <x-card title="System Security Alerts Feed" subtitle="Current alert logs and locked account listings">
            <div class="p-6 space-y-4">
                @forelse ($alerts as $alert)
                    @if ($alert->type === 'critical')
                        <div class="rounded-2xl border border-rose-200 bg-rose-50/50 p-4 text-xs font-bold text-rose-800 leading-6 flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                <i data-lucide="shield-alert" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-extrabold uppercase tracking-wide text-rose-950">{{ $alert->title }}</span>
                                <p class="mt-1 font-semibold text-rose-700 leading-normal">{{ $alert->message }}</p>
                                <span class="mt-2 block text-[10px] font-black uppercase tracking-wider text-rose-400">
                                    Triggered: {{ \Carbon\Carbon::parse($alert->timestamp)->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 text-xs font-bold text-amber-800 leading-6 flex items-start gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                <i data-lucide="lock" class="h-4 w-4"></i>
                            </div>
                            <div>
                                <span class="block text-sm font-extrabold uppercase tracking-wide text-amber-950">{{ $alert->title }}</span>
                                <p class="mt-1 font-semibold text-amber-700 leading-normal">{{ $alert->message }}</p>
                                <span class="mt-2 block text-[10px] font-black uppercase tracking-wider text-amber-400">
                                    Triggered: {{ \Carbon\Carbon::parse($alert->timestamp)->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="py-12 text-center">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3">
                            <i data-lucide="shield-check" class="h-6 w-6"></i>
                        </div>
                        <p class="font-bold text-sm text-slate-800">Your system is secure</p>
                        <p class="text-xs font-semibold text-slate-400 mt-1">No active lockouts or suspicious login threats detected.</p>
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>
</x-admin-layout>
