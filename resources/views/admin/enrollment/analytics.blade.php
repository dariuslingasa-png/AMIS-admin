<x-admin-layout title="Enrollment Analytics">
@php
    $maxCountry = max((int) ($countryCounts->max() ?? 0), 1);
    $maxProvince = max((int) ($provinceCounts->max() ?? 0), 1);
    $maxCity = max((int) ($cityCounts->max() ?? 0), 1);
    $newStudentPercent = $summary['total'] > 0 ? round(($summary['new_students'] / $summary['total']) * 100, 1) : 0;
    $oldStudentPercent = $summary['total'] > 0 ? round(($summary['old_students'] / $summary['total']) * 100, 1) : 0;
@endphp

<div class="space-y-6">
    <!-- Hero / Header Banner -->
    <div class="relative overflow-hidden p-6 md:p-8 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-2xl border border-emerald-700/30 shadow-sm text-white">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-teal-500/10 blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-emerald-500/20 text-emerald-300 rounded-full border border-emerald-500/30 backdrop-blur-xs mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Enrollment Insights
                </span>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white">Country Records & Grade Slots</h1>
                <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                    See where applicants are coming from and which grade levels or shifts still have available capacity for SY <span class="font-bold text-white bg-emerald-500/30 px-2.5 py-0.5 rounded-md">{{ $schoolYear }}</span>.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.enrollment.reports') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-sm shadow-emerald-900/30 hover:scale-[1.02] focus:ring-4 focus:ring-emerald-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    Open Reports
                </a>
            </div>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <!-- 1. Total Applications -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-indigo-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Total Applications</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A12.018 12.018 0 0 1 12 21.25c-1.11 0-2.176-.15-3.18-.435v-.109m3.18.435c1.11 0 2.176-.15 3.18-.435M12 21.25a12.018 12.018 0 0 1-3.18-.435v-.109m0 0a9.38 9.38 0 0 1-2.625.372 9.337 9.337 0 0 1-4.121-.952 4.125 4.125 0 0 1 7.533-2.493M5.25 19.128v-.003c0-1.113.285-2.16.786-3.07m0 0a12.018 12.018 0 0 1 6.002-3.07M6.036 15.27c0-1.07.27-2.106.776-3.004m0 0a12.02 12.02 0 0 1 10.428 0M12 12.25c-1.94 0-3.69-.85-4.898-2.21A5.002 5.002 0 0 1 12 7.25c2.91 0 5.298 2.228 5.298 5a4.978 4.978 0 0 1-5.298.25Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                    {{ number_format($summary['total']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Excluding drafts</p>
            </div>
        </div>

        <!-- 2. Countries Recorded -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-teal-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Countries Recorded</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                    {{ number_format($summary['countries']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Applicant home countries</p>
            </div>
        </div>

        <!-- 3. Cities Recorded -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-purple-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Cities Recorded</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                    {{ number_format($summary['cities']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Applicant city coverage</p>
            </div>
        </div>

        <!-- 4. Total Slot Capacity -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-emerald-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Slot Capacity</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125M3.75 10.125v3.75m16.5-3.75v3.75m-16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125M3.75 13.875v3.75m16.5-3.75v3.75" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                    {{ number_format($summary['slot_capacity']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Configured grade seats</p>
            </div>
        </div>

        <!-- 5. Slots Available -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-sky-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Slots Available</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-sky-50 dark:bg-sky-900/30 text-sky-600 dark:text-sky-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.75 3.75 0 0 1 21 12Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                    {{ number_format($summary['slot_available']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Unenrolled capacity left</p>
            </div>
        </div>

        <!-- 6. Limited / Full -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-amber-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Limited / Full</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                    {{ number_format($summary['limited_slots']) }} <span class="text-gray-300 dark:text-gray-600 font-normal">/</span> {{ number_format($summary['full_slots']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">Critical grade levels</p>
            </div>
        </div>
    </div>

    <!-- Student Type Analytics -->
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-blue-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">New Students</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.375 21 12.318 12.318 0 0 1 3 19.235Z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                    {{ number_format($summary['new_students']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">{{ $newStudentPercent }}% of active applications</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-green-500">
            <div class="flex items-center justify-between">
                <span class="font-bold text-gray-400 dark:text-gray-500 text-xs tracking-wider uppercase">Old Students</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 0-.982-3.456m-4.025 3.456a7.454 7.454 0 0 1 .982-3.456m3.043 0a3.375 3.375 0 1 0-6.044 0m6.044 0H7.478" />
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                    {{ number_format($summary['old_students']) }}
                </span>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 font-medium">{{ $oldStudentPercent }}% of active applications</p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-5 hover:shadow-sm transition-all duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Student Type Breakdown</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-light">NEW STUDENT vs OLD STUDENT applications</p>
                </div>
            </div>
            <div class="min-h-[190px] flex items-center justify-center">
                <div id="studentTypeBarChart" class="w-full"></div>
            </div>
        </div>
    </div>

    <!-- Charts Section 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Country Records Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-6 hover:shadow-sm transition-all duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Country Records</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-light">Applicant distribution by nationality address</p>
                </div>
                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                    </svg>
                </div>
            </div>
            <div class="min-h-[280px] flex items-center justify-center">
                <div id="countryDonutChart" class="w-full"></div>
            </div>
        </div>

        <!-- City Records Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-6 hover:shadow-sm transition-all duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">City Records</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-light">Applicant count across active cities</p>
                </div>
                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12a.75.75 0 0 1 .75.75v16.5a.75.75 0 0 1-.75.75H3a.75.75 0 0 1-.75-.75V3.75A.75.75 0 0 1 3 3Z" />
                    </svg>
                </div>
            </div>
            <div class="min-h-[280px] flex items-center justify-center">
                <div id="cityBarChart" class="w-full"></div>
            </div>
        </div>
    </div>

    <!-- Grade Slot Matrix Table Wrapper -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-6 hover:shadow-sm transition-all duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-4 gap-2">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Grade Slot Availability Matrix</h3>
                <p class="text-xs text-gray-400 mt-0.5 font-light">Real-time status indicators and utilization meters per grade schedule</p>
            </div>
            <div class="flex gap-2">
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400 px-2 py-0.5 rounded-md border border-emerald-100 dark:border-emerald-900/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Open
                </span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-400 px-2 py-0.5 rounded-md border border-amber-100 dark:border-amber-900/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Limited
                </span>
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 bg-rose-50 dark:bg-rose-950/40 dark:text-rose-400 px-2 py-0.5 rounded-md border border-rose-100 dark:border-rose-900/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Full
                </span>
            </div>
        </div>
        <div class="relative overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-[11px] text-gray-400 uppercase bg-gray-50/80 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700 font-bold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4">Grade Level</th>
                        <th scope="col" class="px-6 py-4">Face-to-Face Slot</th>
                        <th scope="col" class="px-6 py-4">1st Shift Slot</th>
                        <th scope="col" class="px-6 py-4">2nd Shift Slot</th>
                        <th scope="col" class="px-6 py-4 text-center">Applicants</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    @forelse ($slotRows as $row)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors">
                            <td class="px-6 py-5 font-bold text-gray-800 dark:text-white text-sm tracking-wide">
                                {{ $row['grade'] }}
                            </td>
                            @foreach (['face_to_face', 'first_shift', 'second_shift'] as $slotKey)
                                @php $slot = $row[$slotKey]; @endphp
                                <td class="px-6 py-5">
                                    <div class="flex flex-col min-w-[150px]">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-extrabold text-gray-900 dark:text-white">
                                                {{ number_format($slot['available']) }}
                                            </span>
                                            
                                            @if($slot['status'] === 'Full')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
                                                    FULL
                                                </span>
                                            @elseif($slot['status'] === 'Limited')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30">
                                                    LIMITED
                                                </span>
                                            @elseif($slot['status'] === 'Open' || $slot['status'] === 'Available')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                    AVAILABLE
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400 border border-gray-150 dark:border-gray-700/50">
                                                    UNCONFIGURED
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">
                                            of {{ number_format($slot['capacity']) }} slots available
                                        </div>
                                        
                                        <!-- Flowbite Animated Progress Bar -->
                                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                            @if($slot['status'] === 'Full')
                                                <div class="bg-rose-500 h-1.5 rounded-full" style="width: {{ $slot['used_percent'] }}%"></div>
                                            @elseif($slot['status'] === 'Limited')
                                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ $slot['used_percent'] }}%"></div>
                                            @else
                                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $slot['used_percent'] }}%"></div>
                                            @endif
                                        </div>
                                        
                                        <div class="text-[10px] font-medium text-gray-400 dark:text-gray-500 mt-1 flex items-center justify-between">
                                            <span>{{ number_format($slot['enrolled']) }} enrolled</span>
                                            <span>{{ round($slot['used_percent']) }}% used</span>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                            <td class="px-6 py-5 text-center font-extrabold text-gray-800 dark:text-white">
                                {{ number_format($row['applicant_count']) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                    <span class="text-sm font-semibold">No grade slot configuration found.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Charts Section 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Province Column Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-6 hover:shadow-sm transition-all duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Province / State Records</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-light">Applicant distribution across regions</p>
                </div>
                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
                    </svg>
                </div>
            </div>
            <div class="min-h-[280px] flex items-center justify-center">
                <div id="provinceColumnChart" class="w-full"></div>
            </div>
        </div>

        <!-- Slot Summary radial Gauge -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-6 hover:shadow-sm transition-all duration-200">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Slot Utilization Summary</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-light">Total space occupancy across all active grade levels</p>
                </div>
                <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                    </svg>
                </div>
            </div>
            <div class="min-h-[280px] flex flex-col justify-center items-center py-4">
                <div id="slotRadialChart" class="w-full flex justify-center items-center"></div>
                
                <div class="text-center mt-3 max-w-sm">
                    <div class="font-extrabold text-gray-800 dark:text-white text-base tracking-tight">
                        {{ number_format($slotTotals['available']) }} slots available
                    </div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 font-light">
                        {{ number_format($slotTotals['enrolled']) }} enrolled of {{ number_format($slotTotals['capacity']) }} total configured seats
                    </div>
                    <div class="mt-4 flex flex-wrap justify-center gap-3 font-semibold text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        <span class="px-2 py-0.5 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-150 dark:border-gray-700/50">Limited: {{ number_format($slotTotals['limited']) }}</span>
                        <span class="px-2 py-0.5 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-150 dark:border-gray-700/50">Full: {{ number_format($slotTotals['full']) }}</span>
                        <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded-md border border-emerald-100 dark:border-emerald-900/30">SY {{ $schoolYear }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Enrollment Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-150 dark:border-gray-700/50 shadow-xs p-6 hover:shadow-sm transition-all duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-700 pb-4 mb-4 gap-2">
            <div>
                <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider">Recent Enrollment Activity</h3>
                <p class="text-xs text-gray-400 mt-0.5 font-light">Latest submitted applications waiting for verification check</p>
            </div>
            <a href="{{ route('admin.applications.enrollment') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:text-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 rounded-xl border border-emerald-100 dark:border-emerald-900/30 transition-all shadow-2xs hover:scale-[1.01]">
                View Applications
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.25" stroke="currentColor" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        </div>
        <div class="relative overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-[11px] text-gray-400 uppercase bg-gray-50/80 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700 font-bold tracking-wider">
                    <tr>
                        <th scope="col" class="px-6 py-4">Applicant</th>
                        <th scope="col" class="px-6 py-4">Country</th>
                        <th scope="col" class="px-6 py-4">City</th>
                        <th scope="col" class="px-6 py-4">Grade</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    @forelse ($recent as $applicant)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-extrabold text-gray-900 dark:text-white tracking-wide uppercase text-sm">
                                    {{ Str::upper($applicant->full_name) }}
                                </div>
                                <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 font-light">
                                    {{ $applicant->user->email ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ strtoupper($applicant->country ?? '-') }}
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                {{ strtoupper($applicant->city ?? '-') }}
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-gray-800 dark:text-white">
                                {{ $applicant->grade_abbr ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $status = $applicant->status;
                                    $label = $statusLabels[$status] ?? $status;
                                @endphp
                                @if($status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                        {{ $label }}
                                    </span>
                                @elseif($status === 'rejected' || $status === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
                                        {{ $label }}
                                    </span>
                                @elseif($status === 'under_review')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30">
                                        {{ $label }}
                                    </span>
                                @elseif($status === 'submitted' || $status === 'ready_for_submission')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/30">
                                        {{ $label }}
                                    </span>
                                @elseif($status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30">
                                        {{ $label }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-600 dark:bg-gray-700/50 dark:text-gray-400 border border-gray-150 dark:border-gray-700/50">
                                        {{ $label }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-400 dark:text-gray-500 font-medium">
                                {{ $applicant->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                    <span class="text-sm font-semibold">No enrollment activity yet.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Country Records (Donut Chart)
    const studentTypeCounts = @json($typeCounts);
    const studentTypeLabels = Object.keys(studentTypeCounts);
    const studentTypeData = Object.values(studentTypeCounts);

    if (studentTypeData.length > 0) {
        const studentTypeOptions = {
            series: [{
                name: 'Applications',
                data: studentTypeData
            }],
            chart: {
                type: 'bar',
                height: 190,
                toolbar: { show: false },
                fontFamily: 'Inter, system-ui, -apple-system, sans-serif'
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: true,
                    barHeight: '58%',
                    distributed: true
                }
            },
            colors: ['#2563eb', '#16a34a', '#d97706', '#64748b'],
            dataLabels: {
                enabled: true,
                style: { fontSize: '11px', fontWeight: 800, colors: ['#ffffff'] }
            },
            legend: { show: false },
            xaxis: {
                categories: studentTypeLabels,
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 550 }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#475569', fontSize: '11px', fontWeight: 800 }
                }
            },
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.08)',
                strokeDashArray: 4,
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '12px' }
            }
        };
        new ApexCharts(document.querySelector("#studentTypeBarChart"), studentTypeOptions).render();
    } else {
        document.querySelector("#studentTypeBarChart").innerHTML = '<div class="py-12 text-center text-xs text-gray-400">No student type records yet.</div>';
    }

    const countryCounts = @json($countryCounts);
    const countryLabels = Object.keys(countryCounts).map(c => c.toUpperCase());
    const countryData = Object.values(countryCounts);

    if (countryData.length > 0) {
        const countryOptions = {
            series: countryData,
            labels: countryLabels,
            chart: {
                type: 'donut',
                height: 280,
                fontFamily: 'Inter, system-ui, -apple-system, sans-serif'
            },
            colors: ['#059669', '#3b82f6', '#8b5cf6', '#f59e0b', '#0f766e', '#06b6d4', '#10b981', '#6366f1', '#a855f7', '#ec4899'],
            stroke: {
                show: true,
                width: 2,
                colors: ['#ffffff']
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                fontSize: '11px',
                fontWeight: 600,
                labels: { colors: '#64748b' },
                markers: { width: 8, height: 8, radius: 6 }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '12px',
                                fontWeight: 600,
                                color: '#64748b',
                                offsetY: -8
                            },
                            value: {
                                show: true,
                                fontSize: '20px',
                                fontWeight: 800,
                                color: '#1e293b',
                                offsetY: 8,
                                formatter: function(val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'TOTAL',
                                color: '#64748b',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '12px' }
            }
        };
        new ApexCharts(document.querySelector("#countryDonutChart"), countryOptions).render();
    } else {
        document.querySelector("#countryDonutChart").innerHTML = '<div class="py-12 text-center text-xs text-gray-400">No country records yet.</div>';
    }

    // 2. City Records (Rounded Horizontal Bar Chart)
    const cityCounts = @json($cityCounts);
    const cityLabels = Object.keys(cityCounts).map(c => c.toUpperCase());
    const cityData = Object.values(cityCounts);

    if (cityData.length > 0) {
        const cityOptions = {
            series: [{
                name: 'Applicants',
                data: cityData
            }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Inter, system-ui, -apple-system, sans-serif'
            },
            plotOptions: {
                bar: {
                    borderRadius: 5,
                    horizontal: true,
                    barHeight: '55%',
                    distributed: true
                }
            },
            colors: ['#059669', '#3b82f6', '#8b5cf6', '#f59e0b', '#0f766e', '#06b6d4', '#10b981', '#6366f1', '#a855f7', '#ec4899'],
            dataLabels: { enabled: false },
            legend: { show: false },
            xaxis: {
                categories: cityLabels,
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 550 }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#475569', fontSize: '11px', fontWeight: 700 }
                }
            },
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.08)',
                strokeDashArray: 4,
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '12px' }
            }
        };
        new ApexCharts(document.querySelector("#cityBarChart"), cityOptions).render();
    } else {
        document.querySelector("#cityBarChart").innerHTML = '<div class="py-12 text-center text-xs text-gray-400">No city records yet.</div>';
    }

    // 3. Province / State Records (Rounded Vertical Column Chart)
    const provinceCounts = @json($provinceCounts);
    const provinceLabels = Object.keys(provinceCounts).map(p => p.toUpperCase());
    const provinceData = Object.values(provinceCounts);

    if (provinceData.length > 0) {
        const provinceOptions = {
            series: [{
                name: 'Applicants',
                data: provinceData
            }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Inter, system-ui, -apple-system, sans-serif'
            },
            plotOptions: {
                bar: {
                    borderRadius: 5,
                    columnWidth: '45%',
                    distributed: true
                }
            },
            colors: ['#3b82f6', '#8b5cf6', '#059669', '#f59e0b', '#0f766e', '#06b6d4', '#10b981', '#6366f1', '#a855f7', '#ec4899'],
            dataLabels: { enabled: false },
            legend: { show: false },
            xaxis: {
                categories: provinceLabels,
                labels: {
                    style: { colors: '#475569', fontSize: '11px', fontWeight: 600 }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#94a3b8', fontSize: '11px', fontWeight: 550 }
                }
            },
            grid: {
                borderColor: 'rgba(148, 163, 184, 0.08)',
                strokeDashArray: 4,
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            tooltip: {
                theme: 'light',
                style: { fontSize: '12px' }
            }
        };
        new ApexCharts(document.querySelector("#provinceColumnChart"), provinceOptions).render();
    } else {
        document.querySelector("#provinceColumnChart").innerHTML = '<div class="py-12 text-center text-xs text-gray-400">No province records yet.</div>';
    }

    // 4. Slot Summary (Overall Slot Utilization Radial Gauge)
    const slotTotals = @json($slotTotals);
    const percentUsed = slotTotals.capacity > 0 ? Math.min(100, Math.round((slotTotals.enrolled / slotTotals.capacity) * 100)) : 0;

    const radialOptions = {
        series: [percentUsed],
        chart: {
            type: 'radialBar',
            height: 220,
            sparkline: { enabled: true },
            fontFamily: 'Inter, system-ui, -apple-system, sans-serif'
        },
        colors: ['#059669'], // Emerald color
        plotOptions: {
            radialBar: {
                startAngle: -95,
                endAngle: 95,
                track: {
                    background: 'rgba(148, 163, 184, 0.1)',
                    strokeWidth: '97%',
                    margin: 5, // margin is in pixels
                },
                dataLabels: {
                    name: { show: false },
                    value: {
                        offsetY: -2,
                        fontSize: '32px',
                        fontWeight: 800,
                        color: '#0f172a',
                        formatter: function(val) {
                            return val + "%";
                        }
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'horizontal',
                shadeIntensity: 0.4,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 50, 100]
            },
        },
        labels: ['Utilization'],
    };

    new ApexCharts(document.querySelector("#slotRadialChart"), radialOptions).render();
});
</script>
</x-admin-layout>
