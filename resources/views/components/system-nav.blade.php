@props([
    'title' => 'System Management',
    'subtitle' => 'System Health, DevOps Operations, Database Backups, and Live Logs',
    'activeTab' => 'health'
])

<section class="overflow-hidden rounded-3xl border border-slate-700/30 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-xl shadow-slate-900/10">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">System Management Workspace</span>
            <h1 class="mt-3 text-3xl font-black tracking-tight flex items-center gap-3">
                @if($activeTab === 'health')
                    <i data-lucide="activity" class="w-8 h-8 text-emerald-400"></i>
                @elseif($activeTab === 'devops')
                    <i data-lucide="cpu" class="w-8 h-8 text-indigo-400"></i>
                @elseif($activeTab === 'backups')
                    <i data-lucide="database" class="w-8 h-8 text-violet-400"></i>
                @elseif($activeTab === 'logs')
                    <i data-lucide="terminal" class="w-8 h-8 text-sky-400"></i>
                @elseif($activeTab === 'email')
                    <i data-lucide="mail-plus" class="w-8 h-8 text-indigo-400"></i>
                @else
                    <i data-lucide="sliders" class="w-8 h-8 text-amber-400"></i>
                @endif
                <span>{{ $title }}</span>
            </h1>
            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                {{ $subtitle }}
            </p>
        </div>

        @if(isset($slot) && $slot->isNotEmpty())
            <div class="flex items-center gap-3 flex-wrap">
                {{ $slot }}
            </div>
        @endif
    </div>
</section>
