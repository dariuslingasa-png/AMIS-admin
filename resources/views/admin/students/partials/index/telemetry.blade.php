@php
    $total = $stats['total_students'] ?? 0;
    $changed = $stats['passwords_changed'] ?? 0;
    $temp = $stats['passwords_temp'] ?? 0;
    $noAccount = $stats['no_ms_accounts'] ?? 0;

    $changedPct = $total > 0 ? round(($changed / $total) * 100) : 0;
    $tempPct = $total > 0 ? round(($temp / $total) * 100) : 0;
    $noAccountPct = $total > 0 ? round(($noAccount / $total) * 100) : 0;
@endphp

<div x-data="{ open: false }" class="mb-6 rounded-xl border border-slate-200 bg-slate-50/50 p-4 print:hidden">
    <!-- Summary Header Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Changed Card -->
        <div class="relative overflow-hidden rounded-lg border border-emerald-100 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Changed Password</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($changed) }}</h3>
                </div>
                <div class="rounded-full bg-emerald-50 p-2.5 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check"><path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $changedPct }}% of student list</span>
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ $changedPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- Temporary Card -->
        <div class="relative overflow-hidden rounded-lg border border-amber-100 bg-white p-4 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Temporary Password</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ number_format($temp) }}</h3>
                </div>
                <div class="rounded-full bg-amber-50 p-2.5 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $tempPct }}% of student list</span>
                <div class="h-1.5 w-24 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full bg-amber-500" style="width: {{ $tempPct }}%"></div>
                </div>
            </div>
        </div>

        <!-- No Account Card -->
        <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md">
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
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-250 bg-white px-3 py-1.5 text-xs font-extrabold text-slate-700 transition hover:bg-slate-50 cursor-pointer shadow-sm"
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
        class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-inner"
        style="display: none;"
    >
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-55 bg-slate-50 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">Grade Level</th>
                    <th class="px-4 py-3 text-center">Total Students</th>
                    <th class="px-4 py-3 text-center text-emerald-700">Changed Password</th>
                    <th class="px-4 py-3 text-center text-amber-700">Temporary Password</th>
                    <th class="px-4 py-3 text-center text-slate-600">No Microsoft Account</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                @forelse ($analytics['password_by_grade'] ?? [] as $gp)
                    @php
                        $gTotal = $gp->total ?: 0;
                        $gChanged = $gp->changed ?: 0;
                        $gTemp = $gp->temp ?: 0;
                        $gNoAccount = $gp->no_account ?: 0;

                        $gChangedPct = $gTotal > 0 ? round(($gChanged / $gTotal) * 100) : 0;
                        $gTempPct = $gTotal > 0 ? round(($gTemp / $gTotal) * 100) : 0;
                        $gNoAccountPct = $gTotal > 0 ? round(($gNoAccount / $gTotal) * 100) : 0;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-4 py-2.5 font-bold text-slate-900">{{ $gp->grade_level }}</td>
                        <td class="px-4 py-2.5 text-center font-extrabold text-slate-950">{{ number_format($gTotal) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] text-emerald-700 ring-1 ring-emerald-100">
                                {{ number_format($gChanged) }} ({{ $gChangedPct }}%)
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-[10px] text-amber-700 ring-1 ring-amber-100">
                                {{ number_format($gTemp) }} ({{ $gTempPct }}%)
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[10px] text-slate-600 ring-1 ring-slate-200">
                                {{ number_format($gNoAccount) }} ({{ $gNoAccountPct }}%)
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400 font-medium">No grade level telemetry available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
