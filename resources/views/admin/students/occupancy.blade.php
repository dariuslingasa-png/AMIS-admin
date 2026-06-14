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

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Enrolled</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($totalOccupied) }}</span>
                    <span class="text-xs font-bold text-slate-500">/ {{ number_format($totalCapacity) }} Seats</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Overall Fill Rate: <span class="font-extrabold text-emerald-600">{{ $overallFillRate }}%</span>
                </div>
            </div>
            
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">F2F Occupancy</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($f2fOccupied) }}</span>
                    <span class="text-xs font-bold text-slate-500">/ {{ number_format($f2fCapacity) }} Seats</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    F2F Fill Rate: <span class="font-extrabold text-emerald-600">{{ $f2fFillRate }}%</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Flexible Occupancy</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($flexOccupied) }}</span>
                    <span class="text-xs font-bold text-slate-500">/ {{ number_format($flexCapacity) }} Seats</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Flexible Fill Rate: <span class="font-extrabold text-amber-600">{{ $flexFillRate }}%</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Sections</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ $sections->count() }}</span>
                    <span class="text-xs font-bold text-emerald-600">Active</span>
                </div>
                <div class="mt-2 text-[10px] font-semibold text-slate-500">
                    Average: <span class="font-extrabold text-slate-700">{{ $sections->count() > 0 ? round($totalOccupied / $sections->count(), 1) : 0 }} / section</span>
                </div>
            </div>
        </div>

        <!-- Sections Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($sections as $section)
                @php
                    $statusColor = $section->fill_rate >= 100 ? 'red' : ($section->fill_rate >= 85 ? 'amber' : 'emerald');
                    $statusColorsMap = [
                        'red' => [
                            'bg' => 'bg-rose-50 text-rose-700 border-rose-100',
                            'border' => 'border-t-rose-500',
                            'fill' => 'bg-rose-500',
                            'text' => 'text-rose-600'
                        ],
                        'amber' => [
                            'bg' => 'bg-amber-50 text-amber-700 border-amber-100',
                            'border' => 'border-t-amber-500',
                            'fill' => 'bg-amber-500',
                            'text' => 'text-amber-600'
                        ],
                        'emerald' => [
                            'bg' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                            'border' => 'border-t-emerald-600',
                            'fill' => 'bg-emerald-600',
                            'text' => 'text-emerald-600'
                        ],
                    ];
                    $theme = $statusColorsMap[$statusColor];
                    $statusText = $section->fill_rate >= 100 ? 'Full' : ($section->fill_rate >= 85 ? 'Near Capacity' : 'Available');
                @endphp
                <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm transition hover:shadow-md border-t-4 {{ $theme['border'] }} flex flex-col justify-between" x-data="{ showRoster: false }">
                    <div>
                        <!-- Header Title -->
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 tracking-tight uppercase leading-tight">{{ $section->section_title }}</h3>
                                <span class="inline-flex items-center gap-1 mt-1 rounded px-1.5 py-0.5 text-[9px] font-black uppercase bg-slate-100 text-slate-600">
                                    {{ $section->learning_mode ?: 'Flexible' }} &middot; {{ $section->shift ?: '1st Shift' }}
                                </span>
                            </div>
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $theme['bg'] }}">
                                {{ $statusText }}
                            </span>
                        </div>

                        <!-- Advisor details -->
                        <div class="mt-4 bg-slate-50/50 rounded-2xl p-3 border border-slate-100/60">
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">Class Advisor</span>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="rounded-full bg-slate-200 h-7 w-7 flex items-center justify-center text-[10px] font-black text-slate-600 uppercase">
                                    {{ collect(explode(' ', str_ireplace('TEACHER ', '', $section->advisor_name)))->filter()->take(2)->map(fn($p) => substr($p,0,1))->join('') ?: 'AD' }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-extrabold text-slate-800 truncate uppercase leading-tight">{{ $section->advisor_name }}</div>
                                    @if($section->advisor_email)
                                        <div class="text-[9px] text-slate-450 truncate font-semibold mt-0.5 flex items-center gap-1">
                                            <i data-lucide="mail" class="h-2.5 w-2.5"></i>
                                            {{ $section->advisor_email }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div class="mt-5 space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-semibold">
                                <span class="text-slate-500">Filled Seats</span>
                                <span class="font-extrabold text-slate-800">{{ $section->occupied }} / {{ $section->capacity_limit }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $theme['fill'] }} transition-all duration-500" style="width: {{ $section->fill_rate }}%;"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-bold">
                                <span class="{{ $theme['text'] }}">{{ $section->remaining }} seats left</span>
                                <span class="text-slate-450">{{ $section->fill_rate }}% Full</span>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Roster -->
                    <div x-show="showRoster" x-collapse x-cloak class="mt-4 pt-4 border-t border-slate-100/80 space-y-2.5">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Enrolled Students ({{ $section->occupied }})</h4>
                        @if($section->students->isEmpty())
                            <p class="text-xs font-semibold text-slate-400 italic py-2">No students enrolled yet.</p>
                        @else
                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-100 pr-1">
                                @foreach($section->students as $studentSec)
                                    @php
                                        $student = $studentSec->student;
                                        $applicant = $student->applicant;
                                        $fullName = $applicant ? html_entity_decode(implode(' ', array_filter([trim($applicant->first_name ?? ''), trim($applicant->middle_name ?? ''), trim($applicant->last_name ?? '')])), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                    @endphp
                                    <div class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.students.show', $student) }}" class="font-extrabold text-slate-800 hover:text-emerald-700 transition uppercase block truncate leading-tight">
                                                {{ $fullName }}
                                            </a>
                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5 block">{{ $student->student_number }}</span>
                                        </div>
                                        <span class="rounded bg-slate-50 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 uppercase shrink-0">
                                            {{ $applicant->learning_mode ?? 'F2F' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="mt-5 pt-4 border-t border-slate-150/40 flex items-center justify-between gap-2">
                        <button type="button" @click="showRoster = !showRoster" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-50 active:scale-[0.97] transition cursor-pointer">
                            <i data-lucide="users" class="h-3.5 w-3.5 text-slate-500"></i>
                            <span x-text="showRoster ? 'Hide Roster' : 'View Roster'">View Roster</span>
                        </button>
                        
                        <a href="{{ route('admin.students.roster-print', $section) }}" target="_blank" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 px-3.5 text-xs font-bold transition active:scale-[0.97]">
                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            Print
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                    <p class="text-sm font-bold text-slate-500">No school sections configured.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
