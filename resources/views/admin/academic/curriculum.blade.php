<x-admin-layout title="Academic Setup Workspace">
    <div class="analytics-page flex flex-col gap-6" x-data="{ 
        activeWorkspace: 'framework',
        activeTab: 'matatag', 
        addModal: false,
        configModal: false,
        addTermModal: false,
        addEventModal: false,
        createSubjectModal: false,
        searchGrades: '',
        
        // Subjects tab state
        searchSubjects: '',
        activeDivision: 'elementary',
        activeMode: 'f2f',
        activeGrade: 'All',
        showSubjectsSkeleton: false,
        isSavingSubject: false,
        triggerSubjectsSearch(val) {
            this.showSubjectsSkeleton = true;
            setTimeout(() => { this.showSubjectsSkeleton = false; }, 300);
        }
    }">
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
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Academic Setup</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Configure learning frameworks, catalog offered subjects, manage grade levels, and track school years.
                    </p>
                </div>
                <div>
                    <template x-if="activeWorkspace === 'framework'">
                        <button type="button" @click="addModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                            Add Framework
                        </button>
                    </template>
                    <template x-if="activeWorkspace === 'subjects'">
                        <button type="button" @click="createSubjectModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                            Create Subject
                        </button>
                    </template>
                    <template x-if="activeWorkspace === 'grades'">
                        <button type="button" @click="configModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                            <i data-lucide="settings" class="w-4 h-4 text-indigo-700"></i>
                            Configure Grades
                        </button>
                    </template>
                    <template x-if="activeWorkspace === 'years'">
                        <button type="button" @click="addTermModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                            Add Term
                        </button>
                    </template>
                    <template x-if="activeWorkspace === 'calendar'">
                        <button type="button" @click="addEventModal = true" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                            Add Event
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Dynamic Segmented Workspace Tab Bar -->
        <div class="flex flex-wrap gap-1.5 p-1 bg-slate-100 border border-slate-200/50 rounded-2xl max-w-2xl shadow-3xs">
            <button type="button" @click="activeWorkspace = 'framework'" 
                :class="activeWorkspace === 'framework' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider min-w-[120px]">
                <i data-lucide="map" class="w-3.5 h-3.5"></i>
                Framework
            </button>
            <button type="button" @click="activeWorkspace = 'grades'" 
                :class="activeWorkspace === 'grades' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider min-w-[120px]">
                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                Grade Levels
            </button>
            <button type="button" @click="activeWorkspace = 'years'" 
                :class="activeWorkspace === 'years' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider min-w-[120px]">
                <i data-lucide="calendar-range" class="w-3.5 h-3.5"></i>
                School Years
            </button>
            <button type="button" @click="activeWorkspace = 'calendar'" 
                :class="activeWorkspace === 'calendar' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'" 
                class="flex-1 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider min-w-[100px]">
                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                Calendar
            </button>
        </div>

        <!-- ==================== WORKSPACE: FRAMEWORK ==================== -->
        <div x-show="activeWorkspace === 'framework'" x-transition class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-8 bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-slate-900 font-extrabold text-base block">Active Curriculum Programs</span>
                    <span class="text-[11px] text-gray-400 font-medium mt-0.5">Toggle and review active guidelines from DepEd</span>
                </div>
                <div class="flex gap-1 p-1 bg-slate-100 rounded-xl border border-slate-150 max-w-md">
                    <button type="button" @click="activeTab = 'matatag'" 
                        :class="activeTab === 'matatag' ? 'bg-white text-sky-700 shadow-xs font-black' : 'text-slate-500 hover:text-slate-950 hover:bg-white/40 font-semibold'" 
                        class="flex-1 py-2 text-xs rounded-lg transition duration-200 cursor-pointer flex items-center justify-center gap-1.5">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        MATATAG Curriculum
                    </button>
                    <button type="button" @click="activeTab = 'k12'" 
                        :class="activeTab === 'k12' ? 'bg-white text-sky-700 shadow-xs font-black' : 'text-slate-500 hover:text-slate-950 hover:bg-white/40 font-semibold'" 
                        class="flex-1 py-2 text-xs rounded-lg transition duration-200 cursor-pointer flex items-center justify-center gap-1.5">
                        <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                        K-12 Basic Education
                    </button>
                </div>
                <div x-show="activeTab === 'matatag'" class="space-y-6 pt-2" x-transition>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-800 gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-xs font-extrabold uppercase tracking-wider block text-emerald-950">Active Implementation</span>
                                <span class="text-[11px] text-emerald-600 font-medium">Applied for Kindergarten, Grade 1, 4, and 7</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold bg-emerald-100 text-emerald-800 px-3.5 py-1.5 rounded-xl border border-emerald-200/40 w-max shrink-0">SY {{ config('services.school.previous_year', '2025-2026') }} onwards</span>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Description</label>
                        <p class="text-slate-600 text-xs font-semibold leading-relaxed bg-slate-50/50 p-4 rounded-xl border border-slate-150/50">
                            The MATATAG Curriculum focuses on foundational literacy, numeracy, values integration (GMRC), Makabansa integration, and deep Islamic values integration across grade levels to provide a simplified, high-impact basic education.
                        </p>
                    </div>
                </div>
                <div x-show="activeTab === 'k12'" class="space-y-6 pt-2" x-transition x-cloak>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-2xl bg-amber-50 border border-amber-100 text-amber-800 gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center shrink-0">
                                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-xs font-extrabold uppercase tracking-wider block text-amber-950">Transition / Legacy</span>
                                <span class="text-[11px] text-amber-600 font-medium">Phasing out as MATATAG rolls out gradually</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold bg-amber-100 text-amber-800 px-3.5 py-1.5 rounded-xl border border-amber-200/40 w-max shrink-0">Legacy Support</span>
                    </div>
                </div>
            </div>
            <!-- Right Sidebar: Subject Categories Overview -->
            <div class="lg:col-span-4 bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-5">
                <div class="border-b border-slate-100 pb-4">
                    <span class="text-slate-900 font-extrabold text-base block">Core Divisions</span>
                    <span class="text-[11px] text-gray-400 font-medium mt-0.5">Overview of category groupings</span>
                </div>
                <div class="space-y-4">
                    <div class="group flex gap-4 items-start p-4 bg-purple-50/20 border border-purple-100/55 rounded-2xl hover:bg-purple-50/40 hover:-translate-y-0.5 transition-all duration-200 shadow-3xs">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-purple-50 text-purple-600 shrink-0 shadow-3xs"><i data-lucide="book-marked" class="w-5 h-5"></i></div>
                        <div class="flex-1 space-y-1">
                            <span class="font-extrabold text-slate-900 text-sm block group-hover:text-purple-800 transition-colors">Islamic & Arabic Studies</span>
                            <span class="text-xs text-slate-500 font-medium block">Qur'an, Arabic Language, SHAF values</span>
                        </div>
                    </div>
                    <div class="group flex gap-4 items-start p-4 bg-emerald-50/20 border border-emerald-100/55 rounded-2xl hover:bg-emerald-50/40 hover:-translate-y-0.5 transition-all duration-200 shadow-3xs">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-50 text-emerald-655 shrink-0 shadow-3xs"><i data-lucide="award" class="w-5 h-5"></i></div>
                        <div class="flex-1 space-y-1">
                            <span class="font-extrabold text-slate-900 text-sm block group-hover:text-emerald-800 transition-colors">General Academics</span>
                            <span class="text-xs text-slate-500 font-medium block">Mathematics, Science, English, Filipino</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== WORKSPACE: GRADE LEVELS ==================== -->
        <div x-show="activeWorkspace === 'grades'" x-transition class="space-y-6">
            @php
                $gradesList = ['Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'];
            @endphp
            <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-xs">
                <div class="relative w-full sm:max-w-xs">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="search" x-model="searchGrades" placeholder="Search grade levels..." class="w-full bg-gray-50 border border-gray-200 text-slate-900 text-sm rounded-xl pl-10 pr-4 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all duration-150">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($gradesList as $g)
                    @php
                        $gradeSections = $sections->where('grade_level', $g);
                        $secCount = $gradeSections->count();
                        $studCount = $gradeSections->sum('students_count');
                        $maxCapacity = $secCount * 40; 
                        $usedPct = $maxCapacity > 0 ? min(100, round(($studCount / $maxCapacity) * 100)) : 0;
                        $statusLabel = $usedPct >= 90 ? 'Full' : ($usedPct >= 70 ? 'Limited' : 'Available');
                        $barColor = $usedPct >= 90 ? 'bg-rose-500' : ($usedPct >= 70 ? 'bg-amber-500' : 'bg-emerald-500');
                    @endphp
                    <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-5 hover:shadow-md hover:border-violet-300 hover:-translate-y-1 transition duration-200 flex flex-col justify-between"
                         x-show="searchGrades === '' || '{{ strtolower($g) }}'.includes(searchGrades.toLowerCase())">
                        <div>
                            <div class="flex justify-between items-start">
                                <span class="font-extrabold text-slate-900 text-sm block tracking-wide">{{ $g }}</span>
                                <x-badge color="purple">LEVEL</x-badge>
                            </div>
                            <div class="mt-4 pt-3.5 border-t border-slate-100/70">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-700">
                                    <span>Occupancy</span>
                                    <span class="text-slate-900 font-extrabold">{{ $studCount }} / {{ $maxCapacity > 0 ? $maxCapacity : '—' }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $barColor }}" style="width: {{ $usedPct }}%"></div>
                                </div>
                                <div class="flex items-center justify-between mt-2.5 text-[9px] uppercase tracking-wider text-slate-400 font-bold">
                                    <span>{{ $secCount }} active class(es)</span>
                                    <span class="{{ $usedPct >= 90 ? 'text-rose-600' : ($usedPct >= 70 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $statusLabel }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- ==================== WORKSPACE: SCHOOL YEARS ==================== -->
        <div x-show="activeWorkspace === 'years'" x-transition class="space-y-6">
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Historical Terms</span>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>School Year</th>
                                <th>Active Semester</th>
                                <th>Total Enrollment</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schoolYears as $sy)
                                @php
                                    $statusColor = $sy['status'] === 'Active' ? 'green' : ($sy['status'] === 'Upcoming' ? 'blue' : 'gray');
                                @endphp
                                <tr>
                                    <td class="font-bold text-slate-900 text-sm">SY {{ $sy['year'] }}</td>
                                    <td class="font-semibold text-slate-655 text-xs">{{ $sy['semester'] }}</td>
                                    <td class="font-extrabold text-emerald-600 text-xs">{{ number_format($sy['enrolled']) }} enrolled</td>
                                    <td>
                                        <x-badge color="{{ $statusColor }}">{{ Str::upper($sy['status']) }}</x-badge>
                                    </td>
                                    <td style="text-align: right;">
                                        <button class="px-3.5 py-1.5 text-xxs font-bold text-slate-700 hover:bg-slate-100 rounded-lg border border-slate-200 transition cursor-pointer shadow-3xs">Configure</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== WORKSPACE: CALENDAR ==================== -->
        <div x-show="activeWorkspace === 'calendar'" x-transition class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2 bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Upcoming Academic Events</span>
                </div>
                <div class="p-5 space-y-6">
                    @php
                        $groupedEvents = collect($events)->groupBy(function($event) {
                            return \Carbon\Carbon::parse($event['date'])->format('F Y');
                        });
                    @endphp
                    @forelse($groupedEvents as $month => $monthEvents)
                        <div class="space-y-3">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-wider block border-b border-slate-100 pb-1.5">{{ $month }}</span>
                            <div class="space-y-3">
                                @foreach($monthEvents as $ev)
                                    @php
                                        $type = $ev['type'] ?? 'Academic';
                                        if ($type === 'Enrollment') {
                                            $borderLColor = 'border-l-blue-500'; $bgIconColor = 'bg-blue-50'; $textIconColor = 'text-blue-600';
                                            $badgeClasses = 'bg-blue-50 text-blue-700 border-blue-100/50';
                                        } elseif ($type === 'Holiday') {
                                            $borderLColor = 'border-l-rose-500'; $bgIconColor = 'bg-rose-50'; $textIconColor = 'text-rose-600';
                                            $badgeClasses = 'bg-rose-50 text-rose-700 border-rose-100/50';
                                        } elseif ($type === 'Exam') {
                                            $borderLColor = 'border-l-amber-500'; $bgIconColor = 'bg-amber-50'; $textIconColor = 'text-amber-600';
                                            $badgeClasses = 'bg-amber-50 text-amber-700 border-amber-100/50';
                                        } else {
                                            $borderLColor = 'border-l-emerald-500'; $bgIconColor = 'bg-emerald-50'; $textIconColor = 'text-emerald-600';
                                            $badgeClasses = 'bg-emerald-50 text-emerald-700 border-emerald-100/50';
                                        }
                                        $day = \Carbon\Carbon::parse($ev['date'])->format('d');
                                        $dayName = \Carbon\Carbon::parse($ev['date'])->format('D');
                                    @endphp
                                    <div class="group flex items-center justify-between p-4 bg-white border border-gray-150 rounded-2xl hover:bg-slate-50/55 transition shadow-3xs border-l-4 {{ $borderLColor }}">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-xl {{ $bgIconColor }} flex flex-col items-center justify-center shrink-0 shadow-3xs">
                                                <span class="text-[10px] font-black {{ $textIconColor }} leading-none uppercase">{{ $dayName }}</span>
                                                <span class="text-base font-extrabold text-slate-800 mt-1 leading-none">{{ $day }}</span>
                                            </div>
                                            <div>
                                                <span class="font-extrabold text-slate-900 text-sm block tracking-wide group-hover:text-indigo-850 transition-colors">{{ $ev['title'] }}</span>
                                                <span class="text-[10px] text-gray-400 font-semibold mt-1 block">
                                                    {{ \Carbon\Carbon::parse($ev['date'])->format('F d, Y') }}
                                                </span>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-extrabold border {{ $badgeClasses }} tracking-wider">
                                            {{ Str::upper($ev['type']) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 flex flex-col items-center justify-center gap-2">
                            <i data-lucide="info" class="w-8 h-8 text-slate-350"></i>
                            <p class="font-semibold text-sm">No scheduled events found.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            <!-- Right Legend Box -->
            <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Category Legend</span>
                </div>
                <div class="space-y-3">
                    <div class="group flex items-center justify-between p-3.5 bg-blue-50/20 border border-blue-100/50 rounded-2xl hover:bg-blue-50/40 hover:-translate-y-0.5 transition duration-200 shadow-3xs">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"><i data-lucide="user-plus" class="w-4 h-4"></i></div>
                            <span class="text-xs font-bold text-slate-800">Enrollment Phase</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100/50">ENROLLMENT</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODALS CONFIG REGISTRY ==================== -->
        <!-- 1. Add Framework Modal -->
        <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs" x-show="addModal" x-cloak x-transition>
            <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-6 flex flex-col gap-4 border border-slate-200" @click.away="addModal = false">
                <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <span class="admin-modal-title text-base font-extrabold text-slate-950">Add Framework</span>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="addModal = false">&times;</button>
                </div>
                <div class="space-y-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Framework Title *</label>
                        <input type="text" placeholder="e.g. MATATAG Phase 2" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                </div>
                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="addModal = false">Cancel</button>
                    <button class="px-4 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer" @click="addModal = false">Save Framework</button>
                </div>
            </div>
        </div>

        <!-- 2. Create Subject Modal -->
        <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs" x-show="createSubjectModal" x-cloak x-transition>
            <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-6 flex flex-col gap-4 border border-slate-200" @click.away="createSubjectModal = false">
                <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <span class="admin-modal-title text-base font-extrabold text-slate-950">Add New Subject</span>
                        <div class="text-[11px] text-slate-400 font-light mt-0.5">Register a course in the school database</div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="createSubjectModal = false">&times;</button>
                </div>
                <div class="space-y-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Name *</label>
                        <input type="text" placeholder="e.g. Mathematics" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Code *</label>
                        <input type="text" placeholder="e.g. MATH1" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Grade Level *</label>
                        <select class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                            <option>Grade 1</option><option>Grade 2</option><option>Grade 3</option>
                        </select>
                    </div>
                </div>
                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button type="button" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="createSubjectModal = false">Cancel</button>
                    <button type="button" class="relative inline-flex items-center justify-center px-5 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer min-w-[125px] shadow-sm shadow-indigo-950/20" 
                            :class="isSavingSubject ? 'btn-loading' : ''" 
                            @click="isSavingSubject = true; setTimeout(() => { isSavingSubject = false; createSubjectModal = false; }, 850)">
                        <span class="btn-spinner" x-show="isSavingSubject"></span>
                        <span class="btn-text-content">Save Subject</span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
