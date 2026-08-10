<x-admin-layout
    title="Applications Dashboard"
    :breadcrumbs="[
        ['label' => 'Applications', 'href' => route('admin.applications.dashboard')],
        ['label' => 'Dashboard', 'href' => null],
    ]"
>
    <script type="application/json" id="application-dashboard-chart-data">
        @json($applicationCharts ?? [])
    </script>

    <section class="overflow-hidden rounded-2xl border border-emerald-700/20 bg-gradient-to-r from-emerald-800 to-teal-950 p-6 text-white shadow-sm">
        <div class="flex items-start justify-between gap-6">
            <div>
                <span class="inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/15 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-emerald-100">
                    SY {{ $schoolYear }}
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight">Applications Dashboard</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50/90">
                    Monitor enrollment demand, review queue health, and grade-level capacity before opening the registry.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.applications.enrollment') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50">
                    <i data-lucide="table-2" class="h-4 w-4"></i>
                    Open Registry
                </a>
                <a href="{{ route('admin.applications.review') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 text-sm font-bold text-white transition hover:bg-white/15">
                    <i data-lucide="file-search" class="h-4 w-4"></i>
                    Review Queue
                </a>
            </div>
        </div>
    </section>

    <section class="mt-6 grid grid-cols-4 gap-4">
        @foreach ([
            ['label' => 'Applications', 'value' => $totalApplications, 'icon' => 'files', 'meta' => 'Submitted records', 'href' => route('admin.applications.enrollment')],
            ['label' => 'Families', 'value' => $familiesCount, 'icon' => 'users-round', 'meta' => 'Grouped applications', 'href' => route('admin.applications.enrollment')],
            ['label' => 'Review Queue', 'value' => $reviewQueue, 'icon' => 'search-check', 'meta' => 'Needs admin action', 'href' => route('admin.applications.review')],
            ['label' => 'Seats Available', 'value' => $capacityStats['available'], 'icon' => 'armchair', 'meta' => $capacityStats['utilization'].'% capacity used', 'href' => '#grade-capacity-watchlist'],
        ] as $stat)
            <a href="{{ $stat['href'] }}" class="block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:border-emerald-200 hover:bg-emerald-50/35 hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">Live</span>
                </div>
                <p class="mt-5 text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-3xl font-extrabold text-slate-950">{{ number_format($stat['value']) }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">{{ $stat['meta'] }}</p>
            </a>
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-12 gap-6">
        <x-dashboard.chart-card class="lg:col-span-4" title="Capacity Usage" subtitle="Seat utilization for current school year" chart="capacityRadialChart" />
        <x-dashboard.chart-card class="lg:col-span-8" title="Grade Capacity" subtitle="Enrolled seats against available capacity" chart="gradeCapacityChart" />
        <x-dashboard.chart-card class="lg:col-span-7" title="Application Flow" subtitle="Monthly application submissions" chart="applicationFlowChart" />
        <x-dashboard.chart-card class="lg:col-span-5" title="Student Type Mix" subtitle="New, old, returning, and transferee applicants" chart="applicationTypeChart" />
    </section>

    <section id="grade-capacity-watchlist" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-950">Grade Capacity Watchlist</h2>
                <p class="mt-1 text-sm text-slate-500">Slots by grade level, including applicant demand.</p>
            </div>
            <a href="{{ route('admin.enrollment.analytics') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
                Analytics
            </a>
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            @php
                $capacityRows = $slotRows->isNotEmpty() ? $slotRows : ($gradeSlots->isNotEmpty() ? $gradeSlots->map(fn ($slot) => [
                    'grade' => $slot['grade'],
                    'applicant_count' => $slot['applicant_count'] ?? 0,
                    'face_to_face' => $slot,
                    'first_shift' => [
                        'capacity' => 0,
                        'enrolled' => 0,
                        'available' => 0,
                        'used_percent' => 0,
                        'status' => 'No slot config',
                        'applicants' => 0,
                    ],
                    'second_shift' => [
                        'capacity' => 0,
                        'enrolled' => 0,
                        'available' => 0,
                        'used_percent' => 0,
                        'status' => 'No slot config',
                        'applicants' => 0,
                    ],
                ]) : collect());
            @endphp
            <table class="min-w-[1180px] w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-bold">Grade</th>
                        <th class="px-5 py-4 font-bold">Applicants</th>
                        <th class="px-5 py-4 font-bold">Face to Face</th>
                        <th class="px-5 py-4 font-bold">Flexible Learning - 1st Shift</th>
                        <th class="px-5 py-4 font-bold">Flexible Learning - 2nd Shift</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($capacityRows as $row)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-extrabold text-slate-950">{{ $row['grade'] }}</td>
                            <td class="px-5 py-4">
                                @if (($row['applicant_count'] ?? 0) > 0)
                                    <a href="{{ route('admin.applications.enrollment', ['grade' => $row['grade']]) }}" class="font-bold text-emerald-600 hover:text-emerald-800 hover:underline transition">
                                        {{ number_format($row['applicant_count']) }}
                                    </a>
                                @else
                                    <span class="font-semibold text-slate-400">0</span>
                                @endif
                            </td>
                            @foreach ([
                                'face_to_face' => 'Face to Face',
                                'first_shift' => 'Flexible Learning - 1st Shift',
                                'second_shift' => 'Flexible Learning - 2nd Shift',
                            ] as $slotKey => $slotLabel)
                                @php
                                    $slot = $row[$slotKey];
                                    $statusColor = $slot['status'] === 'Full' ? 'red' : ($slot['status'] === 'Limited' ? 'yellow' : ($slot['status'] === 'Open' ? 'green' : 'gray'));
                                    $meterColor = $slot['status'] === 'Full' ? 'bg-red-500' : ($slot['status'] === 'Limited' ? 'bg-yellow-500' : ($slot['status'] === 'Open' ? 'bg-emerald-500' : 'bg-slate-300'));
                                @endphp
                                <td class="px-5 py-4">
                                    <div class="min-w-[170px] space-y-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-xs font-extrabold uppercase tracking-wide text-slate-400">{{ $slotLabel }}</span>
                                            <x-badge :color="$statusColor">{{ strtoupper($slot['status']) }}</x-badge>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                @php
                                                    $modeParam = match ($slotKey) {
                                                        'face_to_face' => 'f2f',
                                                        'first_shift' => 'flexible_1st',
                                                        'second_shift' => 'flexible_2nd',
                                                        default => null,
                                                    };
                                                @endphp
                                                @if (($slot['applicants'] ?? 0) > 0)
                                                    <a href="{{ route('admin.applications.enrollment', ['grade' => $row['grade'], 'learning_mode' => $modeParam]) }}" class="block text-lg font-bold text-emerald-600 hover:text-emerald-800 hover:underline transition">
                                                        {{ number_format($slot['applicants']) }}
                                                    </a>
                                                @else
                                                    <span class="block text-lg font-extrabold text-slate-400">0</span>
                                                @endif
                                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Applicants</span>
                                            </div>
                                            <div class="text-right">
                                                <span class="block text-lg font-extrabold text-slate-950">{{ number_format($slot['available']) }}</span>
                                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Available</span>
                                            </div>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                            <div class="{{ $meterColor }} h-full rounded-full" style="width: {{ $slot['used_percent'] }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-[11px] font-semibold text-slate-500">
                                            <span>{{ number_format($slot['enrolled']) }} enrolled</span>
                                            <span>{{ number_format($slot['capacity']) }} capacity</span>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm font-medium text-slate-500">
                                No grade capacity configuration found yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
