@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
@endphp

<x-admin-layout
    title="Archived Students"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Archived Students', 'href' => null],
    ]"
>
    <div x-data="{
        selectedStudents: [],
        selectAll: false,
        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedStudents = Array.from(document.querySelectorAll('.student-checkbox')).map(cb => cb.value);
            } else {
                this.selectedStudents = [];
            }
        }
    }" class="space-y-6">

        <!-- Top Header & Stats -->
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 border border-slate-200">
                            <i data-lucide="archive" class="h-3.5 w-3.5 text-slate-600"></i>
                            Student Archive Workspace
                        </span>
                        <span class="text-xs font-semibold text-slate-400">•</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">S.Y. 2026–2027</span>
                    </div>
                    <h1 class="mt-2 text-2xl font-black text-slate-950 flex items-center gap-3">
                        <span>Archived Students Masterlist</span>
                        <span class="inline-flex items-center justify-center rounded-full bg-slate-200 px-3 py-0.5 text-sm font-black text-slate-800">
                            {{ number_format($totalArchived) }}
                        </span>
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Students removed from active class lists. Photos, credentials, and records are safely preserved and can be restored anytime.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('admin.students.unassigned') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 text-xs font-bold text-amber-800 shadow-sm transition hover:border-amber-300 hover:bg-amber-100">
                        <i data-lucide="user-x" class="h-4 w-4 text-amber-600"></i>
                        <span>Unassigned Students</span>
                    </a>
                    <a href="{{ route('admin.students.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i data-lucide="arrow-left" class="h-4 w-4 text-slate-400"></i>
                        <span>All Active Students</span>
                    </a>
                </div>
            </div>

            <!-- Grade Quick Filter Pills -->
            <div class="mt-6 border-t border-slate-100 pt-5">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Filter by Grade Level:</div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.students.archive', array_merge(request()->except('grade', 'page'), ['grade' => ''])) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ empty($gradeFilter) ? 'bg-slate-900 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        <span>All Grades</span>
                        <span class="rounded-full {{ empty($gradeFilter) ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600' }} px-1.5 py-0.2 text-[10px]">{{ $totalArchived }}</span>
                    </a>
                    @foreach($gradeOrder as $g)
                        @php $gCount = $archivedByGrade[$g] ?? 0; @endphp
                        <a href="{{ route('admin.students.archive', array_merge(request()->except('grade', 'page'), ['grade' => $g])) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $gradeFilter === $g ? 'bg-emerald-700 text-white shadow-sm' : ($gCount > 0 ? 'border border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100 font-extrabold' : 'border border-slate-200 bg-white text-slate-400 hover:bg-slate-50') }}">
                            <span>{{ $g }}</span>
                            @if($gCount > 0)
                                <span class="rounded-full {{ $gradeFilter === $g ? 'bg-emerald-800 text-white' : 'bg-amber-200 text-amber-900 font-black' }} px-1.5 py-0.2 text-[10px]">{{ $gCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Filter Bar -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.students.archive') }}" class="grid grid-cols-12 gap-3" id="filterForm">
                <label class="relative col-span-12 sm:col-span-6 lg:col-span-4">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search name, student ID, or LRN..." class="{{ $inputClass }} w-full pl-9">
                </label>

                <select name="grade" class="{{ $inputClass }} col-span-6 sm:col-span-3 lg:col-span-3 w-full" onchange="this.form.submit()">
                    <option value="">All Grade Levels</option>
                    @foreach($gradeOrder as $g)
                        <option value="{{ $g }}" @selected($gradeFilter === $g)>{{ $g }}</option>
                    @endforeach
                </select>

                <select name="mode" class="{{ $inputClass }} col-span-6 sm:col-span-3 lg:col-span-3 w-full" onchange="this.form.submit()">
                    <option value="">All Learning Modes</option>
                    <option value="odl" @selected($modeFilter === 'odl')>Online / ODL</option>
                    <option value="f2f" @selected($modeFilter === 'f2f')>Face-to-Face</option>
                </select>

                <div class="col-span-12 lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800 cursor-pointer">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        <span>Filter</span>
                    </button>
                    @if(!empty($search) || !empty($gradeFilter) || !empty($modeFilter))
                        <a href="{{ route('admin.students.archive') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-slate-500 hover:bg-slate-50" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Bulk Action Toolbar -->
        <div x-show="selectedStudents.length > 0" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-md flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-xs font-black text-white" x-text="selectedStudents.length"></span>
                <span class="text-sm font-bold text-emerald-950">Archived student(s) selected</span>
            </div>

            <form method="POST" action="{{ route('admin.students.archive.bulk-restore') }}" onsubmit="return confirm('Restore all selected students back to active list?')" class="inline-flex items-center gap-2">
                @csrf
                <template x-for="id in selectedStudents" :key="id">
                    <input type="hidden" name="student_ids[]" :value="id">
                </template>

                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 cursor-pointer">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    <span>Restore Selected to Active</span>
                </button>

                <button type="button" @click="selectedStudents = []; selectAll = false" class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
            </form>
        </div>

        <!-- Table of Archived Students -->
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            @if($archivedStudents->isEmpty())
                <div class="py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i data-lucide="archive" class="h-8 w-8"></i>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">No Archived Students Found</h3>
                    <p class="mt-1 text-sm text-slate-500">There are no archived student records matching your current filter.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-500">
                                <th class="w-12 px-5 py-4">
                                    <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                </th>
                                <th class="w-32 px-5 py-4 font-extrabold">Student Number</th>
                                <th class="px-5 py-4 font-extrabold">Student Info</th>
                                <th class="w-32 px-5 py-4 font-extrabold">Grade Level</th>
                                <th class="w-48 px-5 py-4 font-extrabold">Learning Mode</th>
                                <th class="w-28 px-5 py-4 font-extrabold">Gender</th>
                                <th class="w-36 px-5 py-4 font-extrabold">Date Archived</th>
                                <th class="w-40 px-5 py-4 text-right font-extrabold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($archivedStudents as $st)
                                @php
                                    $firstName = trim($st->applicant->first_name ?? '');
                                    $middleName = trim($st->applicant->middle_name ?? '');
                                    $lastName = trim($st->applicant->last_name ?? '');
                                    $suffix = trim($st->applicant->suffix ?? '');
                                    $middleInitial = \App\Models\EnrollmentApplicant::formatMiddleInitial($middleName) ?? '';
                                    $fullName = html_entity_decode(implode(' ', array_filter([$lastName . ',', $firstName, $middleName ?: $middleInitial, $suffix])), ENT_QUOTES, 'UTF-8');
                                    $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                                    $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                    $photoUrl = \App\Support\EnrollmentStorage::url($st->applicant->photo_2x2_url ?? null);
                                    
                                    $gender = strtolower((string) ($st->applicant->gender ?? ''));
                                    $genderLabel = $gender === 'male' ? 'Male' : ($gender === 'female' ? 'Female' : 'Not Set');
                                    
                                    $mode = $st->applicant->learning_mode ?? 'Flexible Online Learning';
                                    $isF2f = str_contains(strtolower($mode), 'face') || str_contains(strtolower($mode), 'f2f');
                                @endphp
                                <tr class="transition-colors duration-100 hover:bg-slate-50/90">
                                    <!-- Checkbox -->
                                    <td class="px-5 py-4">
                                        <input type="checkbox" value="{{ $st->id }}" x-model="selectedStudents" class="student-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                    </td>

                                    <!-- Student Number -->
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center font-mono font-black text-xs text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg tracking-tight">
                                            {{ $st->student_number ?? '-' }}
                                        </span>
                                    </td>

                                    <!-- Student Photo & Info -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-smart-image
                                                :src="$photoUrl"
                                                :alt="$name"
                                                :fallback-initials="$initials ?: 'ST'"
                                                size="42"
                                                rounded="rounded-xl"
                                                containerClass="bg-slate-100 text-slate-700 ring-1 ring-slate-200 font-black"
                                                :eager="false"
                                            />
                                            <div>
                                                <div class="font-extrabold text-slate-950 leading-tight">
                                                    {{ $name }}
                                                </div>
                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500">
                                                    @if($st->applicant?->lrn)
                                                        <span class="font-mono text-[11px] text-slate-500">LRN: {{ $st->applicant->lrn }}</span>
                                                    @endif
                                                    @if($st->applicant?->parent_mobile || $st->applicant?->mobile_number)
                                                        <span>•</span>
                                                        <span>{{ $st->applicant->parent_mobile ?? $st->applicant->mobile_number }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Grade Level -->
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-extrabold text-slate-800 border border-slate-200">
                                            {{ $st->grade_level }}
                                        </span>
                                    </td>

                                    <!-- Learning Mode -->
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if($isF2f)
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700 border border-teal-200">
                                                <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                                Face-to-Face
                                            </span>
                                        @elseif(str_contains(strtolower($mode), '1st'))
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-200">
                                                <i data-lucide="sun" class="h-3.5 w-3.5 text-amber-600"></i>
                                                Online - 1st Shift
                                            </span>
                                        @elseif(str_contains(strtolower($mode), '2nd'))
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-800 border border-purple-200">
                                                <i data-lucide="moon" class="h-3.5 w-3.5 text-purple-600"></i>
                                                Online - 2nd Shift
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 border border-rose-200">
                                                <i data-lucide="laptop" class="h-3.5 w-3.5"></i>
                                                {{ $mode }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Gender -->
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if($gender === 'male')
                                            <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 text-xs font-bold text-sky-700 border border-sky-200">
                                                Male
                                            </span>
                                        @elseif($gender === 'female')
                                            <span class="inline-flex items-center rounded-md bg-pink-50 px-2 py-0.5 text-xs font-bold text-pink-700 border border-pink-200">
                                                Female
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <!-- Date Archived -->
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                        <div class="font-bold text-xs text-slate-700">{{ $st->deleted_at?->format('M d, Y') }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $st->deleted_at?->format('h:i A') }}</div>
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <!-- Restore Button -->
                                            <form method="POST" action="{{ route('admin.students.archive.restore', $st->id) }}" onsubmit="return confirm('Restore this student back to active list?')">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8.5 items-center gap-1.5 rounded-lg bg-emerald-700 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800 active:scale-[0.98] cursor-pointer" title="Restore to Active">
                                                    <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                                    <span>Restore</span>
                                                </button>
                                            </form>

                                            <!-- Force Delete Button -->
                                            <form method="POST" action="{{ route('admin.students.archive.force-delete', $st->id) }}" onsubmit="return confirm('Permanently delete this student record? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8.5 w-8.5 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600 cursor-pointer" title="Permanently Delete">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($archivedStudents->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $archivedStudents->links() }}
                    </div>
                @endif
            @endif
        </section>

    </div>
</x-admin-layout>
