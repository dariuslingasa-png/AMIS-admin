<x-admin-layout title="Class Advisory">
    <div class="analytics-page flex flex-col gap-6" x-data="{ createOpen: {{ $errors->any() ? 'true' : 'false' }} }">
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
                    <button type="button" @click="createOpen = true"
                        class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                        Add Advisory
                    </button>
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

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-extrabold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5">
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between gap-3">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Active Database Advisories</span>
                    <x-badge color="indigo">{{ $activeAdvisories->count() }} Active</x-badge>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse($activeAdvisories as $assignment)
                        <article class="rounded-2xl border border-slate-150 bg-slate-50/70 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex rounded-lg border border-indigo-100 bg-indigo-50 px-2.5 py-1 text-xs font-black text-indigo-700">
                                    {{ $assignment->section?->grade_level ?? 'Class' }}
                                </span>
                                <x-badge color="green">ASSIGNED</x-badge>
                            </div>
                            <h3 class="mt-3 text-base font-black text-slate-950">{{ $assignment->section?->section_title ?? 'Deleted section' }}</h3>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $assignment->teacher_name }}</p>
                            <p class="mt-3 text-[10px] font-black uppercase tracking-wider text-slate-400">SY {{ $assignment->school_year }} · Assigned {{ $assignment->assigned_at?->format('M d, Y') }}</p>
                        </article>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-8 text-center text-xs font-bold text-slate-400">
                            No database advisories assigned yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Create Advisory Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="createOpen" @click.self="createOpen = false" @keydown.escape.window="createOpen = false" x-cloak x-transition>
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-950 uppercase tracking-wider">Assign Advisory Class</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Advisory is separate from subject assignments.</p>
                    </div>
                    <button type="button" @click="createOpen = false" class="text-xl font-bold text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-bold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.academic.class-advisory.store') }}" class="space-y-4">
                    @csrf
                    <label class="grid gap-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Teacher *</span>
                        <select name="teacher_key" required class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">Select teacher</option>
                            @foreach($teacherOptions as $teacher)
                                <option value="{{ $teacher['id'] }}" @selected(old('teacher_key') === $teacher['id'])>{{ $teacher['name'] }} · {{ $teacher['email'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Advisory Section *</span>
                        <select name="section_id" required class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                            <option value="">Select class</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((int) old('section_id') === $section->id)>{{ $section->section_title }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="grid gap-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">School Year *</span>
                        <input name="school_year" value="{{ old('school_year', config('services.school.year')) }}" required class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                    </label>
                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="createOpen = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-50">Cancel</button>
                        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-xs font-black text-white hover:bg-indigo-600">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Save Advisory
                        </button>
                    </div>
                </form>
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
