@php
    $sectionDisplayName = $section->official_name ?: ($section->name ?: 'General Section');
    $enrolledCount = $section->students->count();
    $fillRate = $capacity > 0 ? min(100, round(($enrolledCount / $capacity) * 100)) : 0;
@endphp

<x-admin-layout
    title="Manage Section - {{ $sectionDisplayName }}"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Class Occupancy', 'href' => route('admin.students.occupancy')],
        ['label' => $sectionDisplayName, 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Top Header Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-widest text-slate-200">
                            {{ $section->grade_level }}
                        </span>
                        @if($section->learning_mode)
                            <span class="inline-flex rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-200">
                                {{ $section->learning_mode }}
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-3 text-3xl font-black tracking-tight">{{ $sectionDisplayName }}</h1>
                    <p class="mt-1 text-sm font-medium text-emerald-100 flex items-center gap-3">
                        <span>Enrolled: <strong class="text-white font-extrabold">{{ $enrolledCount }} / {{ $capacity }}</strong> Seats ({{ $fillRate }}%)</span>
                        @if($section->grade_advisor)
                            <span>&bull; Advisor: <strong class="text-white font-extrabold">{{ str_ireplace('TEACHER ', '', $section->grade_advisor->teacher_name ?? '') }}</strong></span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.students.occupancy') }}" class="inline-flex items-center gap-1.5 rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-bold text-white hover:bg-white/20 transition active:scale-95">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Back to Occupancy</span>
                    </a>
                    <a href="{{ route('admin.students.id-roster-print', $section) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-2xl bg-purple-600 px-4 py-2.5 text-xs font-black text-white shadow-md hover:bg-purple-700 transition active:scale-95">
                        <i data-lucide="contact" class="w-4 h-4"></i>
                        <span>Export ID Cards Sheet</span>
                    </a>
                    <a href="{{ route('admin.students.roster-print', $section) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-2xl bg-white px-4 py-2.5 text-xs font-black text-emerald-900 shadow-md hover:bg-emerald-50 transition active:scale-95">
                        <i data-lucide="printer" class="w-4 h-4 text-emerald-600"></i>
                        <span>Print Roster PDF</span>
                    </a>
                </div>
            </div>
        </section>

        <!-- Main Workspace: 2 Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- LEFT COLUMN: Current Enrolled Roster (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                                <span>Current Roster</span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium">{{ $enrolledCount }} student(s) in section</p>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                            {{ $fillRate }}% Filled
                        </span>
                    </div>

                    @if($section->students->isEmpty())
                        <div class="py-12 text-center rounded-2xl border border-dashed border-slate-200 bg-slate-50/50">
                            <i data-lucide="user-x" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-xs font-bold text-slate-500">No students assigned to this section yet.</p>
                            <p class="text-[11px] text-slate-400 mt-1">Use the panel on the right to add students.</p>
                        </div>
                    @else
                        <div class="space-y-2 max-h-[600px] overflow-y-auto pr-1 divide-y divide-slate-100">
                            @foreach($section->students as $secStudent)
                                @php
                                    $st = $secStudent->student;
                                    $app = $st?->applicant;
                                    $stName = $app ? html_entity_decode(implode(' ', array_filter([trim($app->first_name ?? ''), trim($app->middle_name ?? ''), trim($app->last_name ?? '')])), ENT_QUOTES, 'UTF-8') : 'Student #' . $st->student_number;
                                @endphp
                                <div class="pt-2.5 pb-2 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.students.show', $st) }}" target="_blank" class="font-black text-xs text-slate-900 hover:text-emerald-700 transition uppercase block truncate">
                                            {{ $stName }}
                                        </a>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold mt-0.5">
                                            <span>ID: {{ $st->student_number }}</span>
                                            @if($app?->lrn)
                                                <span>&bull; LRN: {{ $app->lrn }}</span>
                                            @endif
                                            <span>&bull; {{ $st->grade_level }}</span>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('admin.students.occupancy.remove-student', $secStudent) }}" onsubmit="return confirm('Remove {{ addslashes($stName) }} from {{ addslashes($sectionDisplayName) }}?')" class="shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 px-2.5 py-1 rounded-xl transition cursor-pointer" title="Remove from section">
                                            <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                            <span>Remove</span>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- RIGHT COLUMN: Add Students Workspace (7 Cols) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i data-lucide="user-plus" class="w-5 h-5 text-emerald-600"></i>
                                <span>Add Students to {{ $sectionDisplayName }}</span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium">Search and select official student records in the school database</p>
                        </div>
                    </div>

                    <!-- Filter & Search Form -->
                    <form method="GET" action="{{ route('admin.students.occupancy.manage-section', $section) }}" class="space-y-3 mb-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Student Name, ID Number, or LRN..."
                                       class="w-full h-11 pl-10 pr-4 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500 focus:bg-white transition">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            </div>
                            <button type="submit" class="h-11 px-5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold transition shadow-sm cursor-pointer shrink-0 flex items-center justify-center gap-1.5">
                                <span>Search Records</span>
                            </button>
                        </div>

                        <!-- Grade Filter Tabs -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="text-xs font-bold text-slate-500">Filter:</span>
                            <a href="{{ route('admin.students.occupancy.manage-section', [$section, 'grade_filter' => 'matching', 'search' => request('search')]) }}"
                               class="px-3 py-1 rounded-xl text-xs font-bold transition {{ $gradeFilter === 'matching' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                {{ $section->grade_level ?: 'Matching Grade' }} Students Only
                            </a>
                            <a href="{{ route('admin.students.occupancy.manage-section', [$section, 'grade_filter' => 'all', 'search' => request('search')]) }}"
                               class="px-3 py-1 rounded-xl text-xs font-bold transition {{ $gradeFilter === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                All School Records (Search Entire Database)
                            </a>
                        </div>
                    </form>

                    <!-- Student Selection Form -->
                    <form method="POST" action="{{ route('admin.students.occupancy.assign-students', $section) }}" x-data="{ selectAll: false }">
                        @csrf
                        
                        <div class="flex items-center justify-between px-3 py-2 bg-slate-50 rounded-2xl border border-slate-200/80 mb-3 text-xs font-bold text-slate-600">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" @click="selectAll = !selectAll; $el.closest('form').querySelectorAll('input[type=checkbox]').forEach(c => c.checked = selectAll)"
                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span>Select All Visible Students</span>
                            </label>
                            <span class="text-slate-400 font-semibold">{{ $availableStudents->count() }} Student Records Found</span>
                        </div>

                        <div class="space-y-1.5 max-h-[420px] overflow-y-auto pr-1 border border-slate-200 rounded-2xl p-2 bg-slate-50/30">
                            @forelse($availableStudents as $st)
                                @php
                                    $stApp = $st->applicant;
                                    $stName = $stApp ? html_entity_decode(implode(' ', array_filter([trim($stApp->first_name ?? ''), trim($stApp->middle_name ?? ''), trim($stApp->last_name ?? '')])), ENT_QUOTES, 'UTF-8') : 'Student #' . $st->student_number;
                                    $currentSec = $st->studentSection?->section;
                                    $isEnrolledInThis = $currentSec && $currentSec->id === $section->id;
                                @endphp
                                <label class="flex items-center justify-between p-3 rounded-xl bg-white border border-slate-200/80 hover:border-emerald-300 hover:shadow-xs transition cursor-pointer">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <input type="checkbox" name="student_ids[]" value="{{ $st->id }}" @checked($isEnrolledInThis)
                                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        <div class="min-w-0">
                                            <span class="block text-xs font-black text-slate-900 uppercase truncate leading-tight">{{ $stName }}</span>
                                            <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 mt-0.5">
                                                <span>ID: {{ $st->student_number }}</span>
                                                @if($stApp?->lrn)
                                                    <span>&bull; LRN: {{ $stApp->lrn }}</span>
                                                @endif
                                                <span>&bull; Grade: <strong>{{ $st->grade_level }}</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        @if($isEnrolledInThis)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                                                <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                                                <span>Assigned Here</span>
                                            </span>
                                        @elseif($currentSec)
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                                                In: {{ $currentSec->name }}
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">
                                                Unassigned
                                            </span>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="py-12 text-center text-slate-400 italic">
                                    <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                    <p class="text-xs font-bold text-slate-500">No student records found matching your search.</p>
                                    @if($gradeFilter === 'matching')
                                        <p class="text-[11px] text-slate-400 mt-1">Try switching to "All School Records" tab above to search across all grade levels.</p>
                                    @endif
                                </div>
                            @endforelse
                        </div>

                        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">Check the boxes and click assign to update section roster.</span>
                            <button type="submit" class="h-11 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-md active:scale-95 cursor-pointer flex items-center gap-2">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Assign Selected Students to {{ $sectionDisplayName }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
