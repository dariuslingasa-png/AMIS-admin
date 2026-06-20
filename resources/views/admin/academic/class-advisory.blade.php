<x-admin-layout title="Class Advisory">
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
                        @php
                            $teacherPhoto = getTeacherPhoto($assignment->teacher_key);
                            $assignmentInitials = collect(explode(' ', str_replace('TEACHER ', '', $assignment->teacher_name)))
                                ->map(fn($part) => Str::substr($part, 0, 1))
                                ->take(2)
                                ->implode('');
                            $isHighSchoolAssignment = str_contains(strtolower($assignment->section?->grade_level ?? ''), 'grade') 
                                && (int)filter_var($assignment->section?->grade_level, FILTER_SANITIZE_NUMBER_INT) >= 7;
                        @endphp
                        <article @class([
                            'group relative flex flex-row overflow-hidden rounded-2xl border border-slate-150 bg-white shadow-3xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md min-h-[250px]',
                            'hover:border-indigo-300' => $isHighSchoolAssignment,
                            'hover:border-emerald-300' => ! $isHighSchoolAssignment,
                        ])>
                            <!-- Left Photo Container -->
                            @if($teacherPhoto)
                                <div class="relative w-48 shrink-0 overflow-hidden bg-slate-50">
                                    <img src="{{ asset($teacherPhoto) }}" alt="{{ $assignment->teacher_name }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                </div>
                            @else
                                <div @class([
                                    'relative w-48 shrink-0 overflow-hidden flex items-center justify-center bg-gradient-to-br transition-all duration-300',
                                    'from-indigo-50 to-indigo-100/40 text-indigo-700' => $isHighSchoolAssignment,
                                    'from-emerald-50 to-emerald-100/40 text-emerald-700' => ! $isHighSchoolAssignment,
                                ])>
                                    <span class="text-4xl font-black tracking-wider transition-transform duration-300 group-hover:scale-110">{{ $assignmentInitials }}</span>
                                </div>
                            @endif

                            <!-- Right Details Area -->
                            <div class="flex-1 p-6 flex flex-col justify-between min-w-0">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span @class([
                                            'inline-flex rounded-lg border px-2.5 py-0.5 text-[10px] font-black shadow-3xs tracking-wider uppercase',
                                            'border-indigo-100 bg-indigo-50 text-indigo-700' => $isHighSchoolAssignment,
                                            'border-emerald-100 bg-emerald-50 text-emerald-700' => ! $isHighSchoolAssignment,
                                        ])>
                                            {{ $assignment->section?->grade_level ?? 'Class' }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 shadow-3xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            ACTIVE
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-black text-slate-950 tracking-tight leading-tight line-clamp-2">
                                        {{ $assignment->section?->section_title ?? 'Deleted section' }}
                                    </h3>
                                </div>

                                <div class="border-t border-slate-100 pt-3">
                                    <span class="block text-lg font-black text-slate-950 group-hover:text-indigo-950 transition-colors uppercase">
                                        {{ str_replace('TEACHER ', '', $assignment->teacher_name) }}
                                    </span>
                                    <span class="mt-1 block text-xs font-bold uppercase tracking-wider text-slate-400">
                                        SY {{ $assignment->school_year }}
                                    </span>
                                </div>
                            </div>
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
                                <div class="relative w-48 shrink-0 overflow-hidden bg-slate-50">
                                    <img src="{{ asset($teacherPhoto) }}" alt="{{ $row['teacher'] }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
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
