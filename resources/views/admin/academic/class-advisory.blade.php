<x-admin-layout title="Adviser">
    @php
        if (!function_exists('getTeacherPhoto')) {
            function getTeacherPhoto($teacherKey) {
                $possiblePaths = [
                    "images/teachers/{$teacherKey}.jpg",
                    "images/teachers/teacher-{$teacherKey}.jpg",
                    "images/teachers/{$teacherKey}.png",
                    "images/teachers/teacher-{$teacherKey}.png",
                    "images/teachers/{$teacherKey}.jpeg",
                    "images/teachers/teacher-{$teacherKey}.jpeg",
                ];
                foreach ($possiblePaths as $path) {
                    if (file_exists(public_path($path))) {
                        return $path;
                    }
                }
                return null;
            }
        }
    @endphp
    <div class="analytics-page flex flex-col gap-6">
        <div class="academic-hero-banner">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Adviser</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Official advisory assignment list for Elementary and High School departments.
                    </p>
                </div>
            </div>
        </div>

        @foreach([
            'ELEMENTARY DEPARTMENT' => $elementaryAdvisories,
            'HIGH SCHOOL DEPARTMENT' => $highSchoolAdvisories,
        ] as $departmentTitle => $departmentRows)
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">{{ $departmentTitle }}</span>
                    @php
                        $isHighSchool = str_contains($departmentTitle, 'HIGH');
                        $isIsal = str_contains($departmentTitle, 'ISAL');
                        $isSubjectTeachers = str_contains($departmentTitle, 'SUBJECT');
                        
                        $badgeColor = $isIsal ? 'amber' : ($isSubjectTeachers ? 'violet' : ($isHighSchool ? 'indigo' : 'green'));
                        $roleCountText = ($isIsal || $isSubjectTeachers) ? 'Subject Teachers' : 'Class Advisors';
                    @endphp
                    <x-badge :color="$badgeColor">{{ $departmentRows->count() }} {{ $roleCountText }}</x-badge>
                </div>

                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($departmentRows as $row)
                        @php
                            $teacherPhoto = !empty($row['photo']) ? $row['photo'] : getTeacherPhoto(Str::slug(str_replace('TEACHER ', '', $row['teacher'])));
                            $initials = collect(explode(' ', str_replace('TEACHER ', '', $row['teacher'])))
                                ->map(fn($part) => Str::substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                        @endphp
                        <article @class([
                            'group relative flex flex-row overflow-hidden rounded-2xl border border-slate-150 bg-white shadow-3xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md min-h-[250px]',
                            'hover:border-indigo-300' => $isHighSchool,
                            'hover:border-amber-300' => $isIsal,
                            'hover:border-violet-300' => $isSubjectTeachers,
                            'hover:border-emerald-300' => ! $isHighSchool && ! $isIsal && ! $isSubjectTeachers,
                        ])>
                            <!-- Left Photo Container -->
                            @if($teacherPhoto)
                                <div class="relative w-48 shrink-0 overflow-hidden bg-slate-150 animate-pulse">
                                    <img
                                        src="{{ asset($teacherPhoto) . '?v=2' }}"
                                        alt="{{ $row['teacher'] }}"
                                        class="absolute inset-0 w-full h-full object-cover opacity-0 transition-all duration-300 group-hover:scale-105"
                                        onload="this.classList.remove('opacity-0'); this.parentElement.classList.remove('animate-pulse', 'bg-slate-150');"
                                    >
                                </div>
                            @else
                                <div @class([
                                    'relative w-48 shrink-0 overflow-hidden flex items-center justify-center bg-gradient-to-br transition-all duration-300',
                                    'from-indigo-50 to-indigo-100/40 text-indigo-700' => $isHighSchool,
                                    'from-amber-50 to-amber-100/40 text-amber-700' => $isIsal,
                                    'from-violet-50 to-violet-100/40 text-violet-700' => $isSubjectTeachers,
                                    'from-emerald-50 to-emerald-100/40 text-emerald-700' => ! $isHighSchool && ! $isIsal && ! $isSubjectTeachers,
                                ])>
                                    <span class="text-4xl font-black tracking-wider transition-transform duration-300 group-hover:scale-110">{{ $initials }}</span>
                                </div>
                            @endif

                            <!-- Right Details Area -->
                            <div class="flex-1 p-6 flex flex-col justify-between min-w-0">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span @class([
                                            'inline-flex rounded-lg border px-2.5 py-0.5 text-[10px] font-black shadow-3xs tracking-wider uppercase',
                                            'border-indigo-100 bg-indigo-50 text-indigo-700' => $isHighSchool,
                                            'border-amber-100 bg-amber-50 text-amber-700' => $isIsal,
                                            'border-violet-100 bg-violet-50 text-violet-700' => $isSubjectTeachers,
                                            'border-emerald-100 bg-emerald-50 text-emerald-700' => ! $isHighSchool && ! $isIsal && ! $isSubjectTeachers,
                                        ])>
                                            {{ $row['grade'] }}
                                        </span>
                                        <span @class([
                                            'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-extrabold border shadow-3xs',
                                            'bg-indigo-50 text-indigo-700 border-indigo-100' => $isHighSchool,
                                            'bg-amber-50 text-amber-700 border-amber-100' => $isIsal,
                                            'bg-violet-50 text-violet-700 border-violet-100' => $isSubjectTeachers,
                                            'bg-emerald-50 text-emerald-700 border-emerald-100' => ! $isHighSchool && ! $isIsal && ! $isSubjectTeachers,
                                        ])>
                                            <span @class([
                                                'w-1.5 h-1.5 rounded-full animate-pulse',
                                                'bg-indigo-500' => $isHighSchool,
                                                'bg-amber-500' => $isIsal,
                                                'bg-violet-500' => $isSubjectTeachers,
                                                'bg-emerald-500' => ! $isHighSchool && ! $isIsal && ! $isSubjectTeachers,
                                            ])></span>
                                            {{ ($isIsal || $isSubjectTeachers) ? 'ACTIVE' : 'ASSIGNED' }}
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-950 tracking-tight leading-tight line-clamp-2">
                                        {{ $row['grade_level'] }}
                                    </h3>
                                </div>

                                <div class="border-t border-slate-100 pt-3">
                                    <span class="block text-lg font-black text-slate-950 group-hover:text-indigo-950 transition-colors uppercase">
                                        {{ str_replace('TEACHER ', '', $row['teacher']) }}
                                    </span>
                                    <span class="mt-1 block text-xs font-bold uppercase tracking-wider text-slate-400">
                                        {{ ($isIsal || $isSubjectTeachers) ? 'Subject Teacher' : 'Class Advisor' }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
