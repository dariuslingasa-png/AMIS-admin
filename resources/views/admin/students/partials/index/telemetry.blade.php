@php
    $total = $stats['total_students'] ?? 0;
    $f2f = $stats['f2f_students'] ?? 0;
    $odl = $stats['flexible_students'] ?? 0;
    $changed = $stats['passwords_changed'] ?? 0;
    $temp = $stats['passwords_temp'] ?? 0;
    $noAccount = $stats['no_ms_accounts'] ?? 0;

    $f2fPct = $total > 0 ? round(($f2f / $total) * 100) : 0;
    $odlPct = $total > 0 ? round(($odl / $total) * 100) : 0;
    $changedPct = $total > 0 ? round(($changed / $total) * 100) : 0;
    $tempPct = $total > 0 ? round(($temp / $total) * 100) : 0;
    $noAccountPct = $total > 0 ? round(($noAccount / $total) * 100) : 0;

    $elementaryGrades = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
    $highSchoolGrades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

    $passwordByGrade = collect($analytics['password_by_grade'] ?? []);
    $elementaryData = $passwordByGrade->filter(fn($gp) => in_array($gp->grade_level, $elementaryGrades));
    $highSchoolData = $passwordByGrade->filter(fn($gp) => in_array($gp->grade_level, $highSchoolGrades));

    // Totals for Elementary
    $eleTotal = $elementaryData->sum('total');
    $eleF2f = $elementaryData->sum('f2f');
    $eleOdl = $elementaryData->sum('odl');
    $eleChanged = $elementaryData->sum('changed');
    $eleTemp = $elementaryData->sum('temp');
    $eleNoAccount = $elementaryData->sum('no_account');
    $eleF2fPct = $eleTotal > 0 ? round(($eleF2f / $eleTotal) * 100) : 0;
    $eleOdlPct = $eleTotal > 0 ? round(($eleOdl / $eleTotal) * 100) : 0;
    $eleChangedPct = $eleTotal > 0 ? round(($eleChanged / $eleTotal) * 100) : 0;
    $eleTempPct = $eleTotal > 0 ? round(($eleTemp / $eleTotal) * 100) : 0;
    $eleNoAccountPct = $eleTotal > 0 ? round(($eleNoAccount / $eleTotal) * 100) : 0;

    // Totals for High School
    $hsTotal = $highSchoolData->sum('total');
    $hsF2f = $highSchoolData->sum('f2f');
    $hsOdl = $highSchoolData->sum('odl');
    $hsChanged = $highSchoolData->sum('changed');
    $hsTemp = $highSchoolData->sum('temp');
    $hsNoAccount = $highSchoolData->sum('no_account');
    $hsF2fPct = $hsTotal > 0 ? round(($hsF2f / $hsTotal) * 100) : 0;
    $hsOdlPct = $hsTotal > 0 ? round(($hsOdl / $hsTotal) * 100) : 0;
    $hsChangedPct = $hsTotal > 0 ? round(($hsChanged / $hsTotal) * 100) : 0;
    $hsTempPct = $hsTotal > 0 ? round(($hsTemp / $hsTotal) * 100) : 0;
    $hsNoAccountPct = $hsTotal > 0 ? round(($hsNoAccount / $hsTotal) * 100) : 0;
@endphp

<div x-data="{ open: false }" class="mb-6 rounded-xl border border-slate-200 bg-slate-50/50 p-4 print:hidden">
    <!-- Summary Header Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4">
        <!-- Total Enrolled Card -->
        <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition-shadow duration-150 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Enrolled</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($total) }}</h3>
                </div>
                <div class="rounded-full bg-slate-50 p-2.5 text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>100% of student list</span>
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-slate-500" style="width: 100%"></div>
                </div>
            </div>
        </div>

        <!-- F2F Card -->
        <div class="relative overflow-hidden rounded-lg border border-indigo-100 bg-white p-4 shadow-sm transition-shadow duration-150 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Face-to-Face (F2F)</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($f2f) }}</h3>
                </div>
                <div class="rounded-full bg-indigo-50 p-2.5 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-check"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $f2fPct }}% of student list</span>
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-indigo-500" style="width: {{ $f2fPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- ODL Card -->
        <div class="relative overflow-hidden rounded-lg border border-rose-100 bg-white p-4 shadow-sm transition-shadow duration-150 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Online / ODL</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($odl) }}</h3>
                </div>
                <div class="rounded-full bg-rose-50 p-2.5 text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-laptop"><path d="M20 16V8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8"/><rect width="20" height="4" x="2" y="16" rx="1"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $odlPct }}% of student list</span>
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-rose-500" style="width: {{ $odlPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Changed Card -->
        <a href="{{ route('admin.students.index', array_merge(request()->except(['page', 'password_status']), ['password_status' => 'changed'])) }}" class="group relative overflow-hidden rounded-lg border border-emerald-100 bg-white p-4 shadow-sm transition-all duration-150 hover:shadow-md hover:border-emerald-300 hover:ring-2 hover:ring-emerald-100 cursor-pointer block" title="Click to filter students who changed their password">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600 group-hover:text-emerald-700">Changed Password</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($changed) }}</h3>
                </div>
                <div class="rounded-full bg-emerald-50 p-2.5 text-emerald-600 group-hover:bg-emerald-100 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check"><path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span class="font-bold text-emerald-700 group-hover:underline">Filter {{ $changedPct }}% changed</span>
                <div class="h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ $changedPct }}%"></div>
                </div>
            </div>
        </a>

        <!-- Temporary Card -->
        <a href="{{ route('admin.students.index', array_merge(request()->except(['page', 'password_status']), ['password_status' => 'temp'])) }}" class="group relative overflow-hidden rounded-lg border border-amber-100 bg-white p-4 shadow-sm transition-all duration-150 hover:shadow-md hover:border-amber-300 hover:ring-2 hover:ring-amber-100 cursor-pointer block" title="Click to filter students with active temporary passwords">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-600 group-hover:text-amber-700">Temporary Password</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($temp) }}</h3>
                </div>
                <div class="rounded-full bg-amber-50 p-2.5 text-amber-600 group-hover:bg-amber-100 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span class="font-bold text-amber-700 group-hover:underline">Filter {{ $tempPct }}% temp</span>
                <div class="h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-amber-500" style="width: {{ $tempPct }}%"></div>
                </div>
            </div>
        </a>

        <!-- No Account Card -->
        <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition-shadow duration-150 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">No Microsoft Account</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($noAccount) }}</h3>
                </div>
                <div class="rounded-full bg-slate-100 p-2.5 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-x"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" x2="22" y1="8" y2="13"/><line x1="22" x2="17" y1="8" y2="13"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $noAccountPct }}% of student list</span>
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-slate-400" style="width: {{ $noAccountPct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle Action -->
    <div class="mt-4 flex justify-between items-center border-t border-slate-200/60 pt-3">
        <span class="text-xs text-slate-500 font-semibold">Tracked per Grade Level</span>
        <button 
            type="button" 
            @click="open = !open" 
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-250 bg-white px-3 py-1.5 text-xs font-extrabold text-slate-700 transition-colors duration-100 hover:bg-slate-50 cursor-pointer shadow-sm"
        >
            <span x-text="open ? 'Hide Grade Breakdown' : 'Show Grade Breakdown'">Show Grade Breakdown</span>
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down"><path d="m6 9 6 6 6-6"/></svg>
            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-up" style="display: none;"><path d="m18 15-6-6-6 6"/></svg>
        </button>
    </div>

    <!-- Collapsible Grade Table -->
    <div 
        x-show="open" 
        x-collapse 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-inner p-5 space-y-6"
        style="display: none;"
    >
        <!-- Elementary Section -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Elementary Department</span>
                <span class="text-[10px] font-black text-slate-400 uppercase">Kinder 1 to Grade 6</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-55 bg-slate-50 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5">Grade Level</th>
                            <th class="px-4 py-2.5 text-center">Total Students</th>
                            <th class="px-4 py-2.5 text-center text-indigo-700">F2F</th>
                            <th class="px-4 py-2.5 text-center text-rose-700">ODL</th>
                            <th class="px-4 py-2.5 text-center text-emerald-700">Changed Password</th>
                            <th class="px-4 py-2.5 text-center text-amber-700">Temporary Password</th>
                            <th class="px-4 py-2.5 text-center text-slate-600">No Microsoft Account</th>
                            <th class="px-4 py-2.5 text-center">Print List</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @foreach ($elementaryData as $gp)
                            @php
                                $gTotal = $gp->total ?: 0;
                                $gF2f = $gp->f2f ?: 0;
                                $gOdl = $gp->odl ?: 0;
                                $gChanged = $gp->changed ?: 0;
                                $gTemp = $gp->temp ?: 0;
                                $gNoAccount = $gp->no_account ?: 0;

                                $gF2fPct = $gTotal > 0 ? round(($gF2f / $gTotal) * 100) : 0;
                                $gOdlPct = $gTotal > 0 ? round(($gOdl / $gTotal) * 100) : 0;
                                $gChangedPct = $gTotal > 0 ? round(($gChanged / $gTotal) * 100) : 0;
                                $gTempPct = $gTotal > 0 ? round(($gTemp / $gTotal) * 100) : 0;
                                $gNoAccountPct = $gTotal > 0 ? round(($gNoAccount / $gTotal) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2 font-bold text-slate-900">{{ $gp->grade_level }}</td>
                                <td class="px-4 py-2 text-center font-extrabold text-slate-950">{{ number_format($gTotal) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 text-[10px] text-indigo-700 ring-1 ring-indigo-100">
                                        {{ number_format($gF2f) }} ({{ $gF2fPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-0.5 text-[10px] text-rose-700 ring-1 ring-rose-100">
                                        {{ number_format($gOdl) }} ({{ $gOdlPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 ring-1 ring-emerald-100">
                                        {{ number_format($gChanged) }} ({{ $gChangedPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[10px] text-amber-700 ring-1 ring-amber-100">
                                        {{ number_format($gTemp) }} ({{ $gTempPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600 ring-1 ring-slate-200">
                                        {{ number_format($gNoAccount) }} ({{ $gNoAccountPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('admin.students.index', ['print' => 1, 'grade' => $gp->grade_level, 'show_passwords' => 1]) }}" target="_blank" class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-700 transition-colors duration-100 hover:bg-slate-50 shadow-sm" title="Print Master List for {{ $gp->grade_level }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-printer text-slate-500"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect width="12" height="8" x="6" y="14" rx="1"/></svg>
                                        <span>PDF</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 font-black text-slate-900 border-t-2 border-slate-200">
                        <tr>
                            <td class="px-4 py-2.5 font-black text-xs uppercase tracking-wider text-emerald-800">Total Elementary</td>
                            <td class="px-4 py-2.5 text-center text-xs font-black text-slate-950">{{ number_format($eleTotal) }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-indigo-100 px-2.5 py-0.5 text-[10px] font-black text-indigo-800 ring-1 ring-indigo-200">
                                    {{ number_format($eleF2f) }} ({{ $eleF2fPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-rose-100 px-2.5 py-0.5 text-[10px] font-black text-rose-800 ring-1 ring-rose-200">
                                    {{ number_format($eleOdl) }} ({{ $eleOdlPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black text-emerald-800 ring-1 ring-emerald-200">
                                    {{ number_format($eleChanged) }} ({{ $eleChangedPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2.5 py-0.5 text-[10px] font-black text-amber-800 ring-1 ring-amber-200">
                                    {{ number_format($eleTemp) }} ({{ $eleTempPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-slate-200 px-2.5 py-0.5 text-[10px] font-black text-slate-800 ring-1 ring-slate-300">
                                    {{ number_format($eleNoAccount) }} ({{ $eleNoAccountPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-400 font-bold">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- High School Section -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-800">High School Department</span>
                <span class="text-[10px] font-black text-slate-400 uppercase">Grade 7 to Grade 12</span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-slate-100">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5">Grade Level</th>
                            <th class="px-4 py-2.5 text-center">Total Students</th>
                            <th class="px-4 py-2.5 text-center text-indigo-700">F2F</th>
                            <th class="px-4 py-2.5 text-center text-rose-700">ODL</th>
                            <th class="px-4 py-2.5 text-center text-emerald-700">Changed Password</th>
                            <th class="px-4 py-2.5 text-center text-amber-700">Temporary Password</th>
                            <th class="px-4 py-2.5 text-center text-slate-600">No Microsoft Account</th>
                            <th class="px-4 py-2.5 text-center">Print List</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @foreach ($highSchoolData as $gp)
                            @php
                                $gTotal = $gp->total ?: 0;
                                $gF2f = $gp->f2f ?: 0;
                                $gOdl = $gp->odl ?: 0;
                                $gChanged = $gp->changed ?: 0;
                                $gTemp = $gp->temp ?: 0;
                                $gNoAccount = $gp->no_account ?: 0;

                                $gF2fPct = $gTotal > 0 ? round(($gF2f / $gTotal) * 100) : 0;
                                $gOdlPct = $gTotal > 0 ? round(($gOdl / $gTotal) * 100) : 0;
                                $gChangedPct = $gTotal > 0 ? round(($gChanged / $gTotal) * 100) : 0;
                                $gTempPct = $gTotal > 0 ? round(($gTemp / $gTotal) * 100) : 0;
                                $gNoAccountPct = $gTotal > 0 ? round(($gNoAccount / $gTotal) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-2 font-bold text-slate-900">{{ $gp->grade_level }}</td>
                                <td class="px-4 py-2 text-center font-extrabold text-slate-950">{{ number_format($gTotal) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-0.5 text-[10px] text-indigo-700 ring-1 ring-indigo-100">
                                        {{ number_format($gF2f) }} ({{ $gF2fPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-0.5 text-[10px] text-rose-700 ring-1 ring-rose-100">
                                        {{ number_format($gOdl) }} ({{ $gOdlPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 ring-1 ring-emerald-100">
                                        {{ number_format($gChanged) }} ({{ $gChangedPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[10px] text-amber-700 ring-1 ring-amber-100">
                                        {{ number_format($gTemp) }} ({{ $gTempPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600 ring-1 ring-slate-200">
                                        {{ number_format($gNoAccount) }} ({{ $gNoAccountPct }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <a href="{{ route('admin.students.index', ['print' => 1, 'grade' => $gp->grade_level, 'show_passwords' => 1]) }}" target="_blank" class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-700 transition-colors duration-100 hover:bg-slate-50 shadow-sm" title="Print Master List for {{ $gp->grade_level }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-printer text-slate-500"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect width="12" height="8" x="6" y="14" rx="1"/></svg>
                                        <span>PDF</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 font-black text-slate-900 border-t-2 border-slate-200">
                        <tr>
                            <td class="px-4 py-2.5 font-black text-xs uppercase tracking-wider text-indigo-800">Total High School</td>
                            <td class="px-4 py-2.5 text-center text-xs font-black text-slate-950">{{ number_format($hsTotal) }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-indigo-100 px-2.5 py-0.5 text-[10px] font-black text-indigo-800 ring-1 ring-indigo-200">
                                    {{ number_format($hsF2f) }} ({{ $hsF2fPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-rose-100 px-2.5 py-0.5 text-[10px] font-black text-rose-800 ring-1 ring-rose-200">
                                    {{ number_format($hsOdl) }} ({{ $hsOdlPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black text-emerald-800 ring-1 ring-emerald-200">
                                    {{ number_format($hsChanged) }} ({{ $hsChangedPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-amber-100 px-2.5 py-0.5 text-[10px] font-black text-amber-800 ring-1 ring-amber-200">
                                    {{ number_format($hsTemp) }} ({{ $hsTempPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center gap-1 rounded bg-slate-200 px-2.5 py-0.5 text-[10px] font-black text-slate-800 ring-1 ring-slate-300">
                                    {{ number_format($hsNoAccount) }} ({{ $hsNoAccountPct }}%)
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-400 font-bold">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
