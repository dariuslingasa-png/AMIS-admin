<x-admin-layout title="Class Advisory">
    <div class="analytics-page flex flex-col gap-6">
        <div class="academic-hero-banner">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Class Advisory</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Official advisory assignment list for Elementary and High School departments.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-right backdrop-blur-xs">
                        <span class="block text-xs font-bold uppercase tracking-wider text-indigo-100">Total Advisories</span>
                        <span class="text-2xl font-black text-white">{{ $advisories->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs border-t-4 border-t-emerald-500">
                <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Elementary Department</span>
                <div class="mt-3 text-2xl md:text-3xl font-extrabold text-emerald-700">{{ $elementaryAdvisories->count() }}</div>
                <p class="text-[11px] text-gray-400 mt-1 font-medium">K1 to Grade 6</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs border-t-4 border-t-indigo-500">
                <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">High School Department</span>
                <div class="mt-3 text-2xl md:text-3xl font-extrabold text-indigo-700">{{ $highSchoolAdvisories->count() }}</div>
                <p class="text-[11px] text-gray-400 mt-1 font-medium">Grade 7 to Grade 12</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs border-t-4 border-t-amber-500">
                <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Status</span>
                <div class="mt-3 text-2xl md:text-3xl font-extrabold text-slate-900">Active</div>
                <p class="text-[11px] text-gray-400 mt-1 font-medium">Official advisory mapping</p>
            </div>
        </div>

        @foreach([
            'CLASS ADVISORY - ELEMENTARY DEPARTMENT' => $elementaryAdvisories,
            'HIGH SCHOOL DEPARTMENT' => $highSchoolAdvisories,
        ] as $departmentTitle => $departmentRows)
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">{{ $departmentTitle }}</span>
                    <x-badge color="{{ str_contains($departmentTitle, 'HIGH') ? 'indigo' : 'green' }}">{{ $departmentRows->count() }} Advisors</x-badge>
                </div>

                <div class="p-5 max-h-[560px] overflow-y-auto overscroll-contain grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($departmentRows as $row)
                        @php
                            $initials = collect(explode(' ', str_replace('TEACHER ', '', $row['teacher'])))
                                ->map(fn($part) => Str::substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                            $isHighSchool = str_contains($departmentTitle, 'HIGH');
                        @endphp
                        <article @class([
                            'rounded-2xl border border-slate-150 bg-white p-4 shadow-3xs transition-colors',
                            'hover:border-indigo-200' => $isHighSchool,
                            'hover:border-emerald-200' => ! $isHighSchool,
                        ])>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span @class([
                                        'inline-flex rounded-lg border px-2.5 py-1 text-xs font-black shadow-3xs',
                                        'border-indigo-100 bg-indigo-50 text-indigo-700' => $isHighSchool,
                                        'border-emerald-100 bg-emerald-50 text-emerald-700' => ! $isHighSchool,
                                    ])>
                                        {{ $row['grade'] }}
                                    </span>
                                    <h3 class="mt-3 text-base font-black text-slate-950">{{ $row['grade_level'] }}</h3>
                                    <p class="mt-0.5 text-xs font-bold uppercase tracking-wider text-slate-400">{{ $row['department'] }}</p>
                                </div>
                                <x-badge color="green">ASSIGNED</x-badge>
                            </div>

                            <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                                <div @class([
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-black shadow-3xs',
                                    'text-indigo-700' => $isHighSchool,
                                    'text-emerald-700' => ! $isHighSchool,
                                ])>
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0">
                                    <span class="block truncate text-sm font-extrabold text-slate-900">{{ $row['teacher'] }}</span>
                                    <span class="mt-0.5 block text-xs font-semibold text-slate-500">Class advisor</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
