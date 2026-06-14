@php
    $syncPercentage = $stats['total_students'] > 0 ? round(($stats['ms_synced'] / $stats['total_students']) * 100) : 0;
    $syncProgressColor = $syncPercentage >= 90 ? 'bg-emerald-600' : ($syncPercentage >= 50 ? 'bg-amber-500' : 'bg-rose-500');
    
    $changePercentage = $stats['total_students'] > 0 ? round(($stats['passwords_changed'] / $stats['total_students']) * 100) : 0;
    $changeProgressColor = 'bg-emerald-600';

    $f2fPercent = $f2fStats['capacity'] > 0 ? min(100, round(($f2fStats['occupied'] / $f2fStats['capacity']) * 100)) : 0;
    $f2fColor = $f2fPercent >= 85 ? 'bg-rose-500' : ($f2fPercent >= 50 ? 'bg-amber-500' : 'bg-emerald-600');

    $flexPercent = $flexibleStats['capacity'] > 0 ? min(100, round(($flexibleStats['occupied'] / $flexibleStats['capacity']) * 100)) : 0;
    $flexColor = $flexPercent >= 85 ? 'bg-rose-500' : ($flexPercent >= 50 ? 'bg-amber-500' : 'bg-emerald-600');
@endphp

<!-- Telemetry Statistics Grid -->
<div class="grid gap-5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
    <!-- 1. Enrolled Students -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between h-36 transition hover:shadow-md border-t-4 border-t-emerald-600">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Enrolled</span>
                <h3 class="mt-2 text-3xl font-black text-slate-950 tracking-tight">{{ number_format($stats['total_students']) }}</h3>
            </div>
            <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600 ring-1 ring-emerald-100">
                <i data-lucide="users" class="h-6 w-6"></i>
            </div>
        </div>
        <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-500 mt-2">
            <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">{{ $stats['f2f_students'] }} Face-to-Face</span>
            &middot;
            <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">{{ $stats['flexible_students'] }} Flexible</span>
        </div>
    </div>

    <!-- 2. Microsoft AD Sync -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between h-36 transition hover:shadow-md border-t-4 border-t-blue-500">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">M365 Cloud Sync</span>
                <h3 class="mt-2 text-3xl font-black text-slate-950 tracking-tight">{{ number_format($stats['ms_synced']) }}</h3>
            </div>
            <div class="rounded-2xl bg-blue-50 p-3 text-blue-600 ring-1 ring-blue-100">
                <i data-lucide="cloud-lightning" class="h-6 w-6"></i>
            </div>
        </div>
        <div class="mt-2 space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                <span>Sync Ratio</span>
                <span class="font-bold text-slate-700">{{ $syncPercentage }}%</span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $syncProgressColor }} transition-all duration-500" style="width: {{ $syncPercentage }}%;"></div>
            </div>
        </div>
    </div>

    <!-- 3. Password Changes -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between h-36 transition hover:shadow-md border-t-4 border-t-emerald-600">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Passwords Changed</span>
                <h3 class="mt-2 text-3xl font-black text-slate-950 tracking-tight">{{ number_format($stats['passwords_changed']) }}</h3>
            </div>
            <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600 ring-1 ring-emerald-100">
                <i data-lucide="key-round" class="h-6 w-6"></i>
            </div>
        </div>
        <div class="mt-2 space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                <span>Change Ratio</span>
                <span class="font-bold text-slate-700">{{ $changePercentage }}%</span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $changeProgressColor }} transition-all duration-500" style="width: {{ $changePercentage }}%;"></div>
            </div>
        </div>
    </div>

    <!-- 4. Face-to-Face Capacity -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between h-36 transition hover:shadow-md border-t-4 border-t-emerald-600">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">F2F Capacity (30/sec)</span>
                <h3 class="mt-2 text-3xl font-black text-slate-950 tracking-tight">{{ $f2fStats['occupied'] }}<span class="text-xs text-slate-400 font-bold"> / {{ $f2fStats['capacity'] }}</span></h3>
            </div>
            <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600 ring-1 ring-emerald-100">
                <i data-lucide="door-open" class="h-6 w-6"></i>
            </div>
        </div>
        <div class="mt-2 space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1 rounded">{{ $f2fStats['remaining'] }} seats left</span>
                <span class="font-bold text-slate-700">{{ $f2fPercent }}% Full</span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $f2fColor }} transition-all duration-500" style="width: {{ $f2fPercent }}%;"></div>
            </div>
        </div>
    </div>

    <!-- 5. Flexible Capacity -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between h-36 transition hover:shadow-md border-t-4 border-t-amber-500">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Flexible Capacity (45/sec)</span>
                <h3 class="mt-2 text-3xl font-black text-slate-950 tracking-tight">{{ $flexibleStats['occupied'] }}<span class="text-xs text-slate-400 font-bold"> / {{ $flexibleStats['capacity'] }}</span></h3>
            </div>
            <div class="rounded-2xl bg-amber-50 p-3 text-amber-600 ring-1 ring-amber-100">
                <i data-lucide="monitor" class="h-6 w-6"></i>
            </div>
        </div>
        <div class="mt-2 space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1 rounded">{{ $flexibleStats['remaining'] }} seats left</span>
                <span class="font-bold text-slate-700">{{ $flexPercent }}% Full</span>
            </div>
            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $flexColor }} transition-all duration-500" style="width: {{ $flexPercent }}%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Segmented Learning Mode Capacity Gauges -->
<div class="grid gap-6 md:grid-cols-2">
    <!-- F2F Capacity Card -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="rounded-2xl bg-emerald-50 p-2.5 text-emerald-600">
                    <i data-lucide="door-open" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Face-to-Face Classroom Status</h3>
                    <p class="text-xs text-slate-400 font-medium">Classroom sessions physical seat allocations</p>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">
                {{ $f2fStats['sections_count'] }} Sections
            </span>
        </div>
        
        <div class="mt-6 flex items-center justify-between gap-6">
            <div class="space-y-4 flex-1">
                <div>
                    <span class="text-xs font-bold text-slate-400">Total Enrolled Slots</span>
                    <div class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">
                        {{ $f2fStats['occupied'] }} Enrolled
                    </div>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400">Section Seat Limit</span>
                    <div class="text-base font-extrabold text-slate-700 mt-0.5">
                        30 students per section
                    </div>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400">Physical Rooms Status</span>
                    <div class="text-xs font-semibold text-slate-500 mt-1 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                        {{ $f2fStats['remaining'] }} physical seats currently open
                    </div>
                </div>
            </div>

            <!-- Progress Gauge Wheel -->
            <div class="relative flex h-32 w-32 items-center justify-center rounded-full bg-slate-50 border border-slate-100">
                <svg class="absolute transform -rotate-90" width="112" height="112">
                    <circle cx="56" cy="56" r="48" stroke="#f1f5f9" stroke-width="8" fill="transparent" />
                    <circle cx="56" cy="56" r="48" stroke="#059669" stroke-width="8" fill="transparent" 
                        stroke-dasharray="301.6"
                        stroke-dashoffset="{{ 301.6 - (301.6 * $f2fPercent) / 100 }}"
                        stroke-linecap="round"
                    />
                </svg>
                <div class="text-center z-10">
                    <span class="text-2xl font-black text-slate-900">{{ $f2fPercent }}%</span>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400 mt-0.5">Occupied</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Flexible Online Learning Capacity Card -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="rounded-2xl bg-amber-50 p-2.5 text-amber-600">
                    <i data-lucide="monitor" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Flexible Online Status</h3>
                    <p class="text-xs text-slate-400 font-medium">Online group sessions virtual seat capacities</p>
                </div>
            </div>
            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-100">
                {{ $flexibleStats['sections_count'] }} Sections
            </span>
        </div>

        <div class="mt-6 flex items-center justify-between gap-6">
            <div class="space-y-4 flex-1">
                <div>
                    <span class="text-xs font-bold text-slate-400">Total Enrolled Slots</span>
                    <div class="text-2xl font-black text-slate-900 tracking-tight mt-0.5">
                        {{ $flexibleStats['occupied'] }} Enrolled
                    </div>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400">Section Seat Limit</span>
                    <div class="text-base font-extrabold text-slate-700 mt-0.5">
                        45 students per section
                    </div>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-400">Virtual Rooms Status</span>
                    <div class="text-xs font-semibold text-slate-500 mt-1 flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></span>
                        {{ $flexibleStats['remaining'] }} virtual slots currently open
                    </div>
                </div>
            </div>

            <!-- Progress Gauge Wheel -->
            <div class="relative flex h-32 w-32 items-center justify-center rounded-full bg-slate-50 border border-slate-100">
                <svg class="absolute transform -rotate-90" width="112" height="112">
                    <circle cx="56" cy="56" r="48" stroke="#f1f5f9" stroke-width="8" fill="transparent" />
                    <circle cx="56" cy="56" r="48" stroke="#d97706" stroke-width="8" fill="transparent" 
                        stroke-dasharray="301.6"
                        stroke-dashoffset="{{ 301.6 - (301.6 * $flexPercent) / 100 }}"
                        stroke-linecap="round"
                    />
                </svg>
                <div class="text-center z-10">
                    <span class="text-2xl font-black text-slate-900">{{ $flexPercent }}%</span>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400 mt-0.5">Occupied</p>
                </div>
            </div>
        </div>
    </div>
</div>
