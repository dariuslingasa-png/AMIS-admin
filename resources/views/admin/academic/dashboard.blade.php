<x-admin-layout title="Academic Dashboard">
    <script type="application/json" id="academic-dashboard-chart-data">
        @json($academicCharts ?? [])
    </script>

    <div class="space-y-6">
        <!-- Hero / Header Banner -->
        <div class="academic-hero-banner">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Academic Dashboard</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Monitor subjects, sections, learning modes, grade coverage, and school-year setup from one workspace.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.academic.schedules') }}" class="inline-flex items-center gap-2 border border-white/20 bg-white/10 px-5 py-2.5 rounded-xl text-white hover:bg-white/15 active:bg-white/20 transition-all duration-150 text-sm font-black hover:scale-[1.02] cursor-pointer shadow-sm shadow-indigo-950/10">
                        <i data-lucide="grid" class="w-4 h-4 text-white"></i>
                        Class Management
                    </a>
                    <a href="{{ route('admin.academic.subjects') }}" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="book-open" class="w-4 h-4 text-indigo-700"></i>
                        Open Subjects
                    </a>
                </div>
            </div>
        </div>

        <!-- Telemetry Metrics Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <!-- 1. Total Subjects -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-sky-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Total Subjects</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-sky-50 text-sky-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-900 group-hover:text-sky-600 transition-colors">
                        {{ $academicStats['subjects'] }}
                    </span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Offered courses</p>
                </div>
            </div>

            <!-- 2. Sections -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-blue-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Sections</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="users-round" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-blue-700 group-hover:text-blue-600 transition-colors">
                        {{ $academicStats['sections'] }}
                    </span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Active group classes</p>
                </div>
            </div>

            <!-- 3. Enrolled Learners -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-emerald-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Enrolled Learners</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-650 group-hover:scale-110 transition-transform">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-emerald-700 group-hover:text-emerald-600 transition-colors">
                        {{ number_format($academicStats['students']) }}
                    </span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Active target learners</p>
                </div>
            </div>

            <!-- 4. School Year -->
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-amber-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">School Year</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-600 group-hover:scale-110 transition-transform">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-900 group-hover:text-amber-600 transition-colors">
                        {{ $academicStats['school_year'] }}
                    </span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Active calendar period</p>
                </div>
            </div>
        </div>

        <!-- Charts Layout -->
        <div class="grid gap-6 xl:grid-cols-12">
            <x-dashboard.chart-card class="xl:col-span-4" title="Subject Division" subtitle="Elementary against high school subjects" chart="academicSubjectDivisionChart" />
            <x-dashboard.chart-card class="xl:col-span-4" title="Learning Mode Sections" subtitle="Face to face and flexible learning shifts" chart="academicSectionModeChart" />
            <x-dashboard.chart-card class="xl:col-span-4" title="Grade Subject Load" subtitle="Subjects configured per grade level" chart="academicGradeSubjectsChart" />
            <x-dashboard.chart-card class="xl:col-span-12" title="Grade Section Coverage" subtitle="Active sections from Kinder to Grade 12" chart="academicGradeSectionsChart" />
        </div>

        <!-- Category Grid -->
        <div class="grid gap-6 xl:grid-cols-3">
            <x-card title="Elementary" subtitle="Kinder 1 to Grade 6">
                <div class="flex flex-wrap gap-2 pt-2">
                    @foreach (['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'] as $grade)
                        <span class="rounded-full border border-sky-100 bg-sky-50 px-3.5 py-1 text-xs font-black uppercase tracking-wide text-sky-700 shadow-2xs hover:scale-105 transition-transform">{{ Str::upper($grade) }}</span>
                    @endforeach
                </div>
            </x-card>
            <x-card title="High School" subtitle="Grade 7 to Grade 12">
                <div class="flex flex-wrap gap-2 pt-2">
                    @foreach (['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $grade)
                        <span class="rounded-full border border-emerald-100 bg-emerald-50 px-3.5 py-1 text-xs font-black uppercase tracking-wide text-emerald-700 shadow-2xs hover:scale-105 transition-transform">{{ Str::upper($grade) }}</span>
                    @endforeach
                </div>
            </x-card>
            <x-card title="Learning Modes" subtitle="Academic class delivery setup">
                <div class="space-y-2.5 pt-2">
                    @foreach (['Face to Face', 'Flexible Online Learning - 1st Shift', 'Flexible Online Learning - 2nd Shift'] as $mode)
                        <div class="rounded-2xl border border-slate-150 bg-slate-50/50 px-4.5 py-3.5 text-xs font-black uppercase tracking-wide text-slate-700 hover:border-sky-100 hover:bg-sky-50/30 transition-colors shadow-3xs">{{ $mode }}</div>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>
</x-admin-layout>
