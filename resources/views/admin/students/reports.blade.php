<x-admin-layout
    title="Student Reports"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Reports', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner -->
        <section class="relative overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <!-- Decorative blur shapes -->
            <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute -left-16 -bottom-16 h-40 w-40 rounded-full bg-teal-500/10 blur-3xl"></div>

            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">
                        Reports Workspace
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Student Records & Roster Reports</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Access, analyze, and print grade-level rosters, track LRN compliance, and export official reports for registration and audit purposes.
                    </p>
                </div>
            </div>
        </section>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
            <!-- Stat 1 -->
            <div class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition duration-300 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Enrolled Students</p>
                    <div class="rounded-xl bg-emerald-50 p-2 text-emerald-600 group-hover:scale-110 transition duration-300">
                        <i data-lucide="graduation-cap" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($stats['total_enrolled']) }}</span>
                    <span class="text-xs font-bold text-slate-500">Students</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Assigned to class sections
                </div>
            </div>

            <!-- Stat 2 -->
            <div class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition duration-300 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">With LRN</p>
                    <div class="rounded-xl bg-blue-50 p-2 text-blue-600 group-hover:scale-110 transition duration-300">
                        <i data-lucide="shield-check" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($stats['total_with_lrn']) }}</span>
                    <span class="text-xs font-bold text-blue-600">
                        @if($stats['total_enrolled'] > 0)
                            {{ round(($stats['total_with_lrn'] / $stats['total_enrolled']) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Learner Reference Number coverage
                </div>
            </div>

            <!-- Stat 3 -->
            <div class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition duration-300 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Missing LRN</p>
                    <div class="rounded-xl bg-rose-50 p-2 text-rose-600 group-hover:scale-110 transition duration-300">
                        <i data-lucide="shield-alert" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-2xl font-black text-rose-600">{{ number_format($stats['total_without_lrn']) }}</span>
                    <span class="text-xs font-bold text-rose-500">Needs Attention</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Students missing LRN in records
                </div>
            </div>

            <!-- Stat 4 -->
            <div class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition duration-300 relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Verified Users</p>
                    <div class="rounded-xl bg-indigo-50 p-2 text-indigo-600 group-hover:scale-110 transition duration-300">
                        <i data-lucide="user-check" class="h-4.5 w-4.5"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($stats['total_official']) }}</span>
                    <span class="text-xs font-bold text-indigo-600">Active Accounts</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Total portal users verified
                </div>
            </div>
        </div>

        <!-- Grade Levels Report Table Section -->
        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Official Roster Documents</h2>
                    <p class="text-xs font-medium text-slate-500">Print or save alphabetical student rosters sorted per grade level.</p>
                </div>
                @if($stats['total_enrolled'] > 0)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.students.print-all-rosters') }}" 
                           target="_blank" 
                           class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-extrabold text-white hover:bg-indigo-700 active:scale-[0.97] transition shadow-md cursor-pointer"
                        >
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            Print All Grades
                        </a>
                        <a href="{{ route('admin.students.print-all-rosters', ['print' => 1]) }}" 
                           target="_blank" 
                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 active:scale-[0.97] transition cursor-pointer"
                           title="Direct Print All"
                        >
                            <i data-lucide="external-link" class="h-4 w-4"></i>
                        </a>
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm align-middle">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th class="px-6 py-4 font-black">Grade Level</th>
                            <th class="px-6 py-4 font-black">Total Enrolled</th>
                            <th class="px-6 py-4 font-black">LRN Coverage</th>
                            <th class="px-6 py-4 font-black">LRN Status</th>
                            <th class="px-6 py-4 text-right font-black">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($grades as $g)
                            @php
                                $total = $g['enrolled_count'];
                                $withLrn = $g['with_lrn'];
                                $withoutLrn = $g['without_lrn'];
                                $coveragePercent = $total > 0 ? round(($withLrn / $total) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-extrabold text-xs">
                                            {{ substr($g['grade_level'], 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-800 uppercase tracking-tight text-xs">{{ $g['grade_level'] }}</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase mt-0.5">Al Munawwara School</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-lg border border-slate-200/80 bg-white px-2 py-0.5 text-[10px] font-black text-slate-700 uppercase">
                                        {{ $total }} {{ Str::plural('Student', $total) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 max-w-[200px]">
                                        <span class="font-black text-slate-800 text-xs min-w-[32px]">{{ $coveragePercent }}%</span>
                                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $coveragePercent === 100 ? 'bg-emerald-500' : ($coveragePercent >= 75 ? 'bg-blue-500' : ($coveragePercent > 0 ? 'bg-amber-500' : 'bg-slate-300')) }}" style="width: {{ $coveragePercent }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($total === 0)
                                        <span class="inline-flex items-center gap-1 rounded bg-slate-50 px-1.5 py-0.5 text-[9px] font-bold text-slate-400 uppercase">
                                            Empty Grade
                                        </span>
                                    @elseif ($withoutLrn === 0)
                                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 uppercase">
                                            <i data-lucide="check-circle-2" class="h-3 w-3"></i> Complete
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-1.5 py-0.5 text-[9px] font-bold text-rose-700 uppercase" title="{{ $withoutLrn }} students lack LRN">
                                            <i data-lucide="alert-triangle" class="h-3 w-3"></i> {{ $withoutLrn }} Missing LRN
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($total > 0)
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.students.grade-roster-print', $g['grade_level']) }}" 
                                               target="_blank" 
                                               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-extrabold text-white hover:bg-emerald-700 active:scale-[0.97] transition shadow-sm cursor-pointer"
                                            >
                                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                                Print PDF
                                            </a>
                                            <a href="{{ route('admin.students.grade-roster-print', ['grade' => $g['grade_level'], 'print' => 1]) }}" 
                                               target="_blank" 
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 active:scale-[0.97] transition cursor-pointer"
                                               title="Direct Print"
                                            >
                                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                            </a>
                                        </div>
                                    @else
                                        <button disabled class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-extrabold text-slate-400 cursor-not-allowed">
                                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                            No Students
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
