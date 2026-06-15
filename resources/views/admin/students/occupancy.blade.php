@php
    $totalCapacity = $sections->sum('capacity_limit');
    $totalOccupied = $sections->sum('occupied');
    $overallFillRate = $totalCapacity > 0 ? min(100, round(($totalOccupied / $totalCapacity) * 100)) : 0;
    
    $f2fCapacity = $sections->where('is_f2f', true)->sum('capacity_limit');
    $f2fOccupied = $sections->where('is_f2f', true)->sum('occupied');
    $f2fFillRate = $f2fCapacity > 0 ? min(100, round(($f2fOccupied / $f2fCapacity) * 100)) : 0;

    $flexCapacity = $sections->where('is_f2f', false)->sum('capacity_limit');
    $flexOccupied = $sections->where('is_f2f', false)->sum('occupied');
    $flexFillRate = $flexCapacity > 0 ? min(100, round(($flexOccupied / $flexCapacity) * 100)) : 0;
@endphp

<x-admin-layout
    title="Section Occupancy"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Section Occupancy', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Section Occupancy</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Monitor class sizes, advisor assignments, face-to-face vs flexible capacities, and section rosters.
                    </p>
                </div>
            </div>
        </section>


        <!-- Occupancy Container -->
        <div id="occupancyContainer" class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 lg:gap-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Official Students</p>
                        <div class="rounded-xl bg-emerald-50 p-1.5 text-emerald-600">
                            <i data-lucide="users" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($totalOfficial) }}</span>
                        <span class="text-xs font-bold text-emerald-600">Verified</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Registered Active Accounts
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Enrolled</p>
                        <div class="rounded-xl bg-blue-50 p-1.5 text-blue-600">
                            <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($totalOccupied) }}</span>
                        <span class="text-xs font-bold text-slate-500">/ {{ number_format($totalCapacity) }} Seats</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Overall Fill Rate: <span class="font-extrabold text-emerald-600">{{ $overallFillRate }}%</span>
                    </div>
                </div>
                
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">F2F Occupancy</p>
                        <div class="rounded-xl bg-sky-50 p-1.5 text-sky-600">
                            <i data-lucide="school" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($f2fOccupied) }}</span>
                        <span class="text-xs font-bold text-slate-500">/ {{ number_format($f2fCapacity) }} Seats</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        F2F Fill Rate: <span class="font-extrabold text-emerald-600">{{ $f2fFillRate }}%</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Flexible Occupancy</p>
                        <div class="rounded-xl bg-amber-50 p-1.5 text-amber-600">
                            <i data-lucide="laptop" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($flexOccupied) }}</span>
                        <span class="text-xs font-bold text-slate-500">/ {{ number_format($flexCapacity) }} Seats</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Flexible Fill Rate: <span class="font-extrabold text-amber-600">{{ $flexFillRate }}%</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Sections</p>
                        <div class="rounded-xl bg-indigo-50 p-1.5 text-indigo-600">
                            <i data-lucide="grid" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ $sections->count() }}</span>
                        <span class="text-xs font-bold text-emerald-600">Active</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Average: <span class="font-extrabold text-slate-700">{{ $sections->count() > 0 ? round($totalOccupied / $sections->count(), 1) : 0 }} / sec</span>
                    </div>
                </div>
            </div>

            <!-- Grade Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @forelse($sectionsGrouped as $gradeLevel => $gradeSections)
                    @include('admin.students.partials.occupancy.card', ['gradeLevel' => $gradeLevel, 'gradeSections' => $gradeSections])
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center bg-white">
                        <p class="text-sm font-bold text-slate-500">No school sections configured.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
