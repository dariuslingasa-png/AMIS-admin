@props([
    'title' => 'System Management',
    'subtitle' => 'System Health, DevOps Operations, Database Backups, and Live Logs',
    'activeTab' => 'health'
])

<section class="overflow-hidden rounded-3xl border border-slate-700/30 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-xl shadow-slate-900/10">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">System Management Workspace</span>
            <h1 class="mt-4 text-3xl font-black tracking-tight flex items-center gap-3">
                @if($activeTab === 'health')
                    <i data-lucide="activity" class="w-8 h-8 text-emerald-400"></i>
                @elseif($activeTab === 'devops')
                    <i data-lucide="cpu" class="w-8 h-8 text-indigo-400"></i>
                @elseif($activeTab === 'backups')
                    <i data-lucide="database" class="w-8 h-8 text-violet-400"></i>
                @elseif($activeTab === 'logs')
                    <i data-lucide="terminal" class="w-8 h-8 text-sky-400"></i>
                @else
                    <i data-lucide="sliders" class="w-8 h-8 text-amber-400"></i>
                @endif
                <span>{{ $title }}</span>
            </h1>
            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                {{ $subtitle }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.system-management.health.index') }}" 
               class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-xs font-black transition cursor-pointer backdrop-blur-xs {{ $activeTab === 'health' ? 'bg-emerald-500/30 border border-emerald-400/60 text-emerald-200' : 'bg-white/10 border border-white/20 text-white hover:bg-white/20' }}">
                <i data-lucide="activity" class="w-4 h-4 text-emerald-300"></i>
                <span>System Health</span>
            </a>

            <a href="{{ route('admin.system-management.devops.index') }}" 
               class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-xs font-black transition cursor-pointer backdrop-blur-xs {{ $activeTab === 'devops' ? 'bg-indigo-500/30 border border-indigo-400/60 text-indigo-200' : 'bg-white/10 border border-white/20 text-white hover:bg-white/20' }}">
                <i data-lucide="cpu" class="w-4 h-4 text-indigo-300"></i>
                <span>DevOps Control</span>
            </a>

            <a href="{{ route('admin.system-management.backups.index') }}" 
               class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-xs font-black transition cursor-pointer backdrop-blur-xs {{ $activeTab === 'backups' ? 'bg-violet-500/30 border border-violet-400/60 text-violet-200' : 'bg-white/10 border border-white/20 text-white hover:bg-white/20' }}">
                <i data-lucide="database" class="w-4 h-4 text-violet-300"></i>
                <span>Backup Center</span>
            </a>

            <a href="{{ route('admin.system-management.logs.index') }}" 
               class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-xs font-black transition cursor-pointer backdrop-blur-xs {{ $activeTab === 'logs' ? 'bg-sky-500/30 border border-sky-400/60 text-sky-200' : 'bg-white/10 border border-white/20 text-white hover:bg-white/20' }}">
                <i data-lucide="terminal" class="w-4 h-4 text-sky-300"></i>
                <span>Live Logs</span>
            </a>
        </div>
    </div>
</section>
