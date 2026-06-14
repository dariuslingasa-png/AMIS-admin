<x-admin-layout
    title="Students Dashboard"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Dashboard', 'href' => null],
    ]"
>
    <!-- ApexCharts Library directly -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script type="application/json" id="students-dashboard-chart-data">
        @json($studentsCharts ?? [])
    </script>

    <div class="space-y-6">
        <!-- Dashboard Header / Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-md">
            <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Students Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Students Dashboard</h1>
                    <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                        Monitor student admissions, active learning modes, classroom capacity allocations, and Microsoft 365 AD sync coverage.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                        Student Records
                    </a>
                    <a href="{{ route('admin.students.history') }}" class="inline-flex items-center gap-2 border border-white/20 bg-white/10 px-5 py-2.5 rounded-xl text-white hover:bg-white/15 active:bg-white/20 transition-all duration-150 text-sm font-black hover:scale-[1.02] cursor-pointer shadow-sm shadow-indigo-950/10">
                        <i data-lucide="history" class="w-4 h-4"></i>
                        Enrollment History
                    </a>
                </div>
            </div>
        </div>

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
            @php
                $syncPercentage = $stats['total_students'] > 0 ? round(($stats['ms_synced'] / $stats['total_students']) * 100) : 0;
                $syncProgressColor = $syncPercentage >= 90 ? 'bg-emerald-650' : ($syncPercentage >= 50 ? 'bg-amber-500' : 'bg-rose-500');
            @endphp
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
            @php
                $changePercentage = $stats['total_students'] > 0 ? round(($stats['passwords_changed'] / $stats['total_students']) * 100) : 0;
                $changeProgressColor = 'bg-emerald-600';
            @endphp
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

            <!-- 3. Face-to-Face Capacity -->
            @php
                $f2fPercent = $f2fStats['capacity'] > 0 ? min(100, round(($f2fStats['occupied'] / $f2fStats['capacity']) * 100)) : 0;
                $f2fColor = $f2fPercent >= 85 ? 'bg-rose-500' : ($f2fPercent >= 50 ? 'bg-amber-500' : 'bg-emerald-650');
            @endphp
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between h-36 transition hover:shadow-md border-t-4 border-t-emerald-650">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">F2F Capacity (30/sec)</span>
                        <h3 class="mt-2 text-3xl font-black text-slate-950 tracking-tight">{{ $f2fStats['occupied'] }}<span class="text-xs text-slate-400 font-bold"> / {{ $f2fStats['capacity'] }}</span></h3>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-655 ring-1 ring-emerald-100">
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

            <!-- 4. Flexible Capacity -->
            @php
                $flexPercent = $flexibleStats['capacity'] > 0 ? min(100, round(($flexibleStats['occupied'] / $flexibleStats['capacity']) * 100)) : 0;
                $flexColor = $flexPercent >= 85 ? 'bg-rose-500' : ($flexPercent >= 50 ? 'bg-amber-500' : 'bg-emerald-650');
            @endphp
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

    <style>
        .chart-grid-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }
        .chart-card-grade {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .chart-card-gender, .chart-card-mode {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        @media (min-width: 1024px) {
            .chart-grid-container {
                flex-direction: row;
            }
            .chart-card-grade {
                width: 50% !important;
            }
            .chart-card-gender, .chart-card-mode {
                width: 25% !important;
            }
        }
        /* Custom Modern Scrollbar style for pipeline columns */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
            transition: background 0.2s ease;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>

    <!-- ApexCharts Analytics Section -->
    <div class="chart-grid-container">
        <!-- Grade level distribution -->
        <div class="chart-card-grade rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Grade Level Enrollment Distribution</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Students enrolled per grade level (K1 to Grade 12)</p>
                </div>
                <i data-lucide="bar-chart-3" class="h-5 w-5 text-slate-400"></i>
            </div>
            <div id="studentGradeDistributionChart" class="w-full"></div>
        </div>

        <!-- Gender Distribution -->
        <div class="chart-card-gender rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Gender Division</h3>
                    <p class="text-[11px] text-slate-400 font-medium">Male vs Female students breakdown</p>
                </div>
                <i data-lucide="pie-chart" class="h-5 w-5 text-slate-400"></i>
            </div>
            <div id="studentGenderChart" class="w-full flex justify-center"></div>
        </div>

        <!-- Learning Mode distribution -->
        <div class="chart-card-mode rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Learning Mode Ratios</h3>
                    <p class="text-[11px] text-slate-400 font-medium">F2F vs Flexible Online Learning</p>
                </div>
                <i data-lucide="donut" class="h-5 w-5 text-slate-400"></i>
            </div>
            <div id="studentLearningModeChart" class="w-full flex justify-center"></div>
        </div>
    </div>

        <!-- Section Classroom Capacity list -->
        @php
            $order = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
            
            $f2fList = $sections->where('is_f2f', true)->sortBy(function($s) use ($order) {
                $idx = array_search($s->grade_level, $order);
                return $idx !== false ? $idx : 99;
            });

            $flex1List = $sections->where('is_f2f', false)->filter(function($s) {
                $shiftLower = strtolower((string) $s->shift);
                $modeLower = strtolower((string) $s->learning_mode);
                return str_contains($shiftLower, '1st') || str_contains($modeLower, '1st') || str_contains($shiftLower, '1') || str_contains($modeLower, '1');
            })->sortBy(function($s) use ($order) {
                $idx = array_search($s->grade_level, $order);
                return $idx !== false ? $idx : 99;
            });

            $flex2List = $sections->where('is_f2f', false)->filter(function($s) {
                $shiftLower = strtolower((string) $s->shift);
                $modeLower = strtolower((string) $s->learning_mode);
                return str_contains($shiftLower, '2nd') || str_contains($modeLower, '2nd') || str_contains($shiftLower, '2') || str_contains($modeLower, '2');
            })->sortBy(function($s) use ($order) {
                $idx = array_search($s->grade_level, $order);
                return $idx !== false ? $idx : 99;
            });
        @endphp

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- 1. FACE-TO-FACE -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-[640px]">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-xl bg-emerald-50 p-2 text-emerald-600">
                            <i data-lucide="door-open" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm">Face-to-Face (F2F)</h3>
                            <p class="text-[10px] text-slate-400 font-semibold">Seat Limit: 30 per section</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">
                        {{ $f2fList->count() }} Classes
                    </span>
                </div>

                <div class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                    @forelse($f2fList as $sec)
                        @php
                            $fillColor = $sec->fill_rate >= 90 ? 'bg-rose-500' : ($sec->fill_rate >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-white hover:shadow-xs flex flex-col justify-between gap-3 cursor-pointer select-none group" onclick="showSectionRoster('{{ $sec->id }}')" title="Click to view Advisory & student roster details for {{ $sec->grade_level }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                        {{ $sec->grade_level }}
                                    </span>
                                    
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <!-- Gender Tag -->
                                        @if($sec->gender === 'male')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-blue-50 text-blue-600">
                                                Boys
                                            </span>
                                        @elseif($sec->gender === 'female')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-pink-50 text-pink-600">
                                                Girls
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-violet-50 text-violet-600">
                                                Co-Ed
                                            </span>
                                        @endif

                                        <span class="text-slate-350 font-light">&middot;</span>

                                        <!-- Status indicator dot -->
                                        <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase {{ $sec->fill_rate >= 90 ? 'text-rose-500' : ($sec->fill_rate >= 60 ? 'text-amber-500' : 'text-emerald-600') }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $sec->fill_rate >= 90 ? 'bg-rose-500 animate-pulse' : ($sec->fill_rate >= 60 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                                            {{ $sec->fill_rate >= 90 ? 'Full' : ($sec->fill_rate >= 60 ? 'Fast' : 'Open') }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs font-black text-slate-900 block leading-none">
                                        {{ $sec->occupied }}<span class="text-[10px] text-slate-400 font-medium">/{{ $sec->capacity_limit }}</span>
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider mt-1">Seats</span>
                                </div>
                            </div>

                            <div class="mt-1 space-y-1.5">
                                <div class="flex items-center justify-between text-[9px] font-bold text-slate-400">
                                    <span>{{ $sec->remaining }} open seats</span>
                                    <span>{{ $sec->fill_rate }}% filled</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $fillColor }} transition-all duration-500" style="width: {{ $sec->fill_rate }}%;"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 font-medium">No F2F sections configured.</div>
                    @endforelse
                </div>
            </div>

            <!-- 2. FLEXIBLE ONLINE - 1ST SHIFT -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-[640px]">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-xl bg-amber-50 p-2 text-amber-600">
                            <i data-lucide="monitor" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm">Flexible - 1st Shift</h3>
                            <p class="text-[10px] text-slate-400 font-semibold">Seat Limit: 45 per section</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-100">
                        {{ $flex1List->count() }} Classes
                    </span>
                </div>

                <div class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                    @forelse($flex1List as $sec)
                        @php
                            $fillColor = $sec->fill_rate >= 90 ? 'bg-rose-500' : ($sec->fill_rate >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-white hover:shadow-xs flex flex-col justify-between gap-3 cursor-pointer select-none group" onclick="showSectionRoster('{{ $sec->id }}')" title="Click to view Advisory & student roster details for {{ $sec->grade_level }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                        {{ $sec->grade_level }}
                                    </span>
                                    <h4 class="font-bold text-slate-900 text-sm mt-0.5 tracking-tight leading-tight">
                                        {{ $sec->official_name ?: ($sec->name ?? 'Unnamed') }}
                                    </h4>
                                    
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <!-- Gender Tag -->
                                        @if($sec->gender === 'male')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-blue-50 text-blue-600">
                                                Boys
                                            </span>
                                        @elseif($sec->gender === 'female')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-pink-50 text-pink-600">
                                                Girls
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-violet-50 text-violet-600">
                                                Co-Ed
                                            </span>
                                        @endif

                                        <span class="text-slate-350 font-light">&middot;</span>

                                        <!-- Status indicator dot -->
                                        <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase {{ $sec->fill_rate >= 90 ? 'text-rose-500' : ($sec->fill_rate >= 60 ? 'text-amber-500' : 'text-emerald-600') }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $sec->fill_rate >= 90 ? 'bg-rose-500 animate-pulse' : ($sec->fill_rate >= 60 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                                            {{ $sec->fill_rate >= 90 ? 'Full' : ($sec->fill_rate >= 60 ? 'Fast' : 'Open') }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs font-black text-slate-900 block leading-none">
                                        {{ $sec->occupied }}<span class="text-[10px] text-slate-400 font-medium">/{{ $sec->capacity_limit }}</span>
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider mt-1">Seats</span>
                                </div>
                            </div>

                            <div class="mt-1 space-y-1.5">
                                <div class="flex items-center justify-between text-[9px] font-bold text-slate-400">
                                    <span>{{ $sec->remaining }} open seats</span>
                                    <span>{{ $sec->fill_rate }}% filled</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $fillColor }} transition-all duration-500" style="width: {{ $sec->fill_rate }}%;"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 font-medium">No 1st Shift sections configured.</div>
                    @endforelse
                </div>
            </div>

            <!-- 3. FLEXIBLE ONLINE - 2ND SHIFT -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-[640px]">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="rounded-xl bg-violet-50 p-2 text-violet-650">
                            <i data-lucide="moon" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm">Flexible - 2nd Shift</h3>
                            <p class="text-[10px] text-slate-400 font-semibold">Seat Limit: 45 per section</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-100">
                        {{ $flex2List->count() }} Classes
                    </span>
                </div>

                <div class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
                    @forelse($flex2List as $sec)
                        @php
                            $fillColor = $sec->fill_rate >= 90 ? 'bg-rose-500' : ($sec->fill_rate >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-white hover:shadow-xs flex flex-col justify-between gap-3 cursor-pointer select-none group" onclick="showSectionRoster('{{ $sec->id }}')" title="Click to view Advisory & student roster details for {{ $sec->grade_level }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                        {{ $sec->grade_level }}
                                    </span>
                                    <h4 class="font-bold text-slate-900 text-sm mt-0.5 tracking-tight leading-tight">
                                        {{ $sec->official_name ?: ($sec->name ?? 'Unnamed') }}
                                    </h4>
                                    
                                    <div class="flex items-center gap-1.5 mt-2">
                                        <!-- Gender Tag -->
                                        @if($sec->gender === 'male')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-blue-50 text-blue-600">
                                                Boys
                                            </span>
                                        @elseif($sec->gender === 'female')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-pink-50 text-pink-600">
                                                Girls
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-violet-50 text-violet-600">
                                                Co-Ed
                                            </span>
                                        @endif

                                        <span class="text-slate-350 font-light">&middot;</span>

                                        <!-- Status indicator dot -->
                                        <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase {{ $sec->fill_rate >= 90 ? 'text-rose-500' : ($sec->fill_rate >= 60 ? 'text-amber-500' : 'text-emerald-600') }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $sec->fill_rate >= 90 ? 'bg-rose-500 animate-pulse' : ($sec->fill_rate >= 60 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                                            {{ $sec->fill_rate >= 90 ? 'Full' : ($sec->fill_rate >= 60 ? 'Fast' : 'Open') }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-right flex-shrink-0">
                                    <span class="text-xs font-black text-slate-900 block leading-none">
                                        {{ $sec->occupied }}<span class="text-[10px] text-slate-400 font-medium">/{{ $sec->capacity_limit }}</span>
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider mt-1">Seats</span>
                                </div>
                            </div>

                            <div class="mt-1 space-y-1.5">
                                <div class="flex items-center justify-between text-[9px] font-bold text-slate-400">
                                    <span>{{ $sec->remaining }} open seats</span>
                                    <span>{{ $sec->fill_rate }}% filled</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $fillColor }} transition-all duration-500" style="width: {{ $sec->fill_rate }}%;"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400 font-medium">No 2nd Shift sections configured.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Beautiful Interactive Advisory & Roster Modal -->
    <div id="advisoryRosterModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200/80 transform scale-95 transition-all duration-300 flex flex-col max-h-[85vh]">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white relative flex-shrink-0">
                <div class="absolute right-0 top-0 -mr-6 -mt-6 h-32 w-32 rounded-full bg-emerald-500/15 blur-2xl"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-white/10 text-emerald-100 rounded-md border border-white/10 backdrop-blur-xs mb-2">
                            Class Details
                        </span>
                        <h2 id="modalGradeLevel" class="text-2xl font-black tracking-tight">Grade Level</h2>
                        <p id="modalAdvisoryName" class="text-sm text-emerald-100/90 font-bold mt-1 uppercase tracking-wider"></p>
                    </div>
                    <button onclick="closeAdvisoryModal()" class="rounded-xl bg-white/10 p-2 text-white/80 hover:bg-white/15 active:bg-white/20 transition-all cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Stats Roster Body -->
            <div class="p-6 overflow-y-auto flex-1 space-y-6 custom-scrollbar">
                <!-- Class telemetry grid -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                        <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Seats occupied</span>
                        <div id="modalOccupiedSeats" class="text-lg font-black text-slate-900 mt-1">0 / 0</div>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                        <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Remaining</span>
                        <div id="modalRemainingSeats" class="text-lg font-black text-slate-900 mt-1">0 open</div>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-center">
                        <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Fill Rate</span>
                        <div id="modalFillRate" class="text-lg font-black text-slate-900 mt-1">0%</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                        <div class="rounded-xl bg-violet-50 p-2 text-violet-650">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Gender allocation</span>
                            <div id="modalGender" class="text-xs font-bold text-slate-700 mt-0.5">Male</div>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-100 flex items-center gap-3">
                        <div class="rounded-xl bg-emerald-50 p-2 text-emerald-650">
                            <i data-lucide="monitor" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Learning Shift</span>
                            <div id="modalShift" class="text-xs font-bold text-slate-700 mt-0.5">1st Shift</div>
                        </div>
                    </div>
                </div>

                <!-- Student List -->
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm mb-3 flex items-center gap-1.5">
                        <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-500"></i>
                        Enrolled Class Roster
                        <span id="modalRosterCount" class="text-[10px] font-bold bg-slate-100 text-slate-650 px-2 py-0.5 rounded-full border border-slate-200/50">0 Students</span>
                    </h3>
                    
                    <div id="modalRosterList" class="space-y-2 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                        <!-- Student items inserted here -->
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-between items-center flex-shrink-0">
                <button id="exportPdfBtn" onclick="exportRosterToPdf()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black px-5 py-2.5 rounded-xl cursor-pointer transition shadow-sm flex items-center gap-1.5 transition-all duration-150 hover:scale-[1.02] active:scale-[0.98]">
                    <i data-lucide="file-down" class="w-4 h-4"></i>
                    Export Official PDF
                </button>
                <button onclick="closeAdvisoryModal()" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-black px-5 py-2.5 rounded-xl cursor-pointer transition shadow-sm">
                    Close Roster
                </button>
            </div>
        </div>
    </div>

    <!-- Serialized Roster & Sections Data -->
    <script type="application/json" id="sections-roster-data">
        @json($sections)
    </script>

    <!-- Independent ApexCharts & Interactive Modal Script -->
    <script>
        window.currentRosterSectionId = null;

        window.exportRosterToPdf = function() {
            if (!window.currentRosterSectionId) return;

            const exportBtn = document.getElementById('exportPdfBtn');
            if (exportBtn) {
                exportBtn.disabled = true;
                exportBtn.innerHTML = `<i data-lucide="file-down" class="w-4 h-4"></i> Opening PDF...`;
            }

            const printUrl = @json(route('admin.students.roster-print', ['section' => '__SECTION_ID__']));
            const targetUrl = printUrl.replace('__SECTION_ID__', encodeURIComponent(window.currentRosterSectionId)) + '?print=1';
            const win = window.open(targetUrl, '_blank');

            if (!win) {
                const link = document.createElement('a');
                link.href = targetUrl;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                document.body.appendChild(link);
                link.click();
                link.remove();
            }

            setTimeout(() => {
                if (exportBtn) {
                    exportBtn.disabled = false;
                    exportBtn.innerHTML = `<i data-lucide="file-down" class="w-4 h-4"></i> Export Official PDF`;
                    if (typeof lucide !== 'undefined' && lucide.createIcons) {
                        lucide.createIcons();
                    }
                }
            }, 500);
        };

        window.showSectionRoster = function(sectionId) {
            window.currentRosterSectionId = sectionId;

            const dataNode = document.getElementById('sections-roster-data');
            if (!dataNode) return;

            let sectionsList = [];
            try {
                sectionsList = JSON.parse(dataNode.textContent);
            } catch (e) {
                console.error("Failed to parse sections roster JSON", e);
                return;
            }

            const sec = sectionsList.find(s => s.id == sectionId);
            if (!sec) return;

            // Populate text elements
            document.getElementById('modalGradeLevel').textContent = sec.grade_level;
            document.getElementById('modalAdvisoryName').textContent = `Advisory: ${sec.official_name || sec.name || 'General'}`;
            document.getElementById('modalOccupiedSeats').textContent = `${sec.occupied} / ${sec.capacity_limit}`;
            document.getElementById('modalRemainingSeats').textContent = `${sec.remaining} open`;
            document.getElementById('modalFillRate').textContent = `${sec.fill_rate}%`;
            
            // Format gender
            let genderText = 'Co-Ed';
            if (sec.gender === 'male') genderText = 'Boys Only';
            else if (sec.gender === 'female') genderText = 'Girls Only';
            document.getElementById('modalGender').textContent = genderText;

            // Format shift
            document.getElementById('modalShift').textContent = sec.shift || sec.learning_mode || 'F2F Column';

            // Format roster count
            document.getElementById('modalRosterCount').textContent = `${sec.occupied} ${sec.occupied == 1 ? 'Student' : 'Students'}`;

            // Populate students list roster
            const listContainer = document.getElementById('modalRosterList');
            listContainer.innerHTML = '';

            if (sec.students && sec.students.length > 0) {
                sec.students.forEach((stuSec, idx) => {
                    const student = stuSec.student;
                    const applicant = student?.applicant;
                    if (!student || !applicant) return;

                    const fullName = `${applicant.first_name} ${applicant.last_name}`;
                    const studentNumber = student.student_number || 'N/A';
                    const email = student.school_email || 'N/A';

                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between p-3 rounded-2xl bg-white border border-slate-100 shadow-2xs hover:bg-slate-50/50 transition';
                    item.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-xs text-slate-650 border border-slate-200/50">
                                ${idx + 1}
                            </div>
                            <div>
                                <h5 class="text-xs font-extrabold text-slate-900">${fullName}</h5>
                                <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">${studentNumber}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-semibold text-slate-500">${email}</span>
                        </div>
                    `;
                    listContainer.appendChild(item);
                });
            } else {
                const empty = document.createElement('div');
                empty.className = 'py-8 text-center text-xs text-slate-400 font-medium';
                empty.textContent = 'No students currently enrolled in this section.';
                listContainer.appendChild(empty);
            }

            // Open Modal
            const modal = document.getElementById('advisoryRosterModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Trigger Lucide icons inside the modal
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
            }
        };

        window.closeAdvisoryModal = function() {
            const modal = document.getElementById('advisoryRosterModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        // Close on ESC or click outside
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAdvisoryModal();
        });

        document.addEventListener('DOMContentLoaded', () => {
            // Setup outside click listener
            const modalEl = document.getElementById('advisoryRosterModal');
            if (modalEl) {
                modalEl.addEventListener('click', (e) => {
                    if (e.target.id === 'advisoryRosterModal') closeAdvisoryModal();
                });
            }
            const chartsDataNode = document.getElementById('students-dashboard-chart-data');
            if (!chartsDataNode) return;

            let chartsData = null;
            try {
                chartsData = JSON.parse(chartsDataNode.textContent);
            } catch (e) {
                console.error("Failed to parse students dashboard chart JSON data", e);
                return;
            }

            const chartTheme = {
                blue: '#2563eb',
                green: '#059669',
                amber: '#d97706',
                emerald: '#10b981',
                slate: '#64748b',
                grid: '#eef2f7'
            };

            const baseChart = {
                chart: {
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    toolbar: { show: false },
                    animations: { enabled: true, speed: 600 },
                    foreColor: chartTheme.slate,
                },
                grid: {
                    borderColor: chartTheme.grid,
                    strokeDashArray: 4,
                },
                dataLabels: { enabled: false },
                legend: {
                    position: 'bottom',
                    fontWeight: 600,
                    labels: { colors: chartTheme.slate },
                },
                tooltip: {
                    theme: 'light',
                },
            };

            // Render Gender Chart
            const genderEl = document.querySelector('#studentGenderChart');
            if (genderEl && chartsData.gender?.data?.length) {
                new ApexCharts(genderEl, {
                    ...baseChart,
                    chart: { ...baseChart.chart, type: 'donut', height: 280 },
                    series: chartsData.gender.data,
                    labels: chartsData.gender.labels,
                    colors: [chartTheme.blue, chartTheme.green],
                    stroke: { width: 0 },
                    plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Genders' } } } } },
                }).render();
            }

            // Render Learning Mode Chart
            const modeEl = document.querySelector('#studentLearningModeChart');
            if (modeEl && chartsData.mode?.data?.length) {
                new ApexCharts(modeEl, {
                    ...baseChart,
                    chart: { ...baseChart.chart, type: 'donut', height: 280 },
                    series: chartsData.mode.data,
                    labels: chartsData.mode.labels,
                    colors: [chartTheme.amber, chartTheme.emerald],
                    stroke: { width: 0 },
                    plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Students' } } } } },
                }).render();
            }

            // Render Grade Level Distribution Chart
            const gradeEl = document.querySelector('#studentGradeDistributionChart');
            if (gradeEl && chartsData.gradeDistribution?.data?.length) {
                new ApexCharts(gradeEl, {
                    ...baseChart,
                    chart: { ...baseChart.chart, type: 'bar', height: 280 },
                    series: [{ name: 'Students', data: chartsData.gradeDistribution.data }],
                    xaxis: { categories: chartsData.gradeDistribution.labels },
                    colors: [chartTheme.emerald],
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
                }).render();
            }
        });
    </script>
</x-admin-layout>
