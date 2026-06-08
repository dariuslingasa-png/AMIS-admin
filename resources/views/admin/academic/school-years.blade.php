<x-admin-layout title="School Years">
    <div class="analytics-page flex flex-col gap-6">
        <div class="academic-hero-banner">
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 mb-3">
                    Academic Workspace
                </span>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">School Years</h1>
                <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                    Review active academic year enrollment coverage.
                </p>
            </div>
        </div>

        <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
            <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4">
                <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Academic Year Registry</span>
            </div>
            <div class="premium-table-wrap">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Semester</th>
                            <th>Enrolled Students</th>
                            <th style="text-align:right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schoolYears as $schoolYear)
                            <tr>
                                <td class="font-bold text-slate-900 text-sm">{{ $schoolYear['year'] }}</td>
                                <td class="font-semibold text-slate-500 text-xs">{{ $schoolYear['semester'] }}</td>
                                <td class="font-extrabold text-slate-800 text-xs">{{ $schoolYear['enrolled'] }} students</td>
                                <td style="text-align:right;">
                                    <x-badge color="emerald">{{ $schoolYear['status'] }}</x-badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
