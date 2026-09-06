@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
@endphp

<x-admin-layout
    title="Unassigned Students"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Unassigned Students', 'href' => null],
    ]"
>
    <div x-data="{
        selectedStudents: [],
        selectAll: false,
        bulkSectionId: '',
        gcModalOpen: false,
        copiedToast: false,
        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedStudents = Array.from(document.querySelectorAll('.student-checkbox')).map(cb => cb.value);
            } else {
                this.selectedStudents = [];
            }
        },
        copyGcText() {
            const text = document.getElementById('gc-text-area').value;
            navigator.clipboard.writeText(text).then(() => {
                this.copiedToast = true;
                setTimeout(() => this.copiedToast = false, 3000);
            });
        }
    }" class="space-y-6">

        <!-- Top Header & Actions -->
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 border border-amber-200">
                            <i data-lucide="alert-circle" class="h-3.5 w-3.5 text-amber-600"></i>
                            Needs Section Assignment
                        </span>
                        <span class="text-xs font-semibold text-slate-400">•</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">S.Y. 2026–2027</span>
                    </div>
                    <h1 class="mt-2 text-2xl font-black text-slate-950 flex items-center gap-3">
                        <span>Unassigned Students Masterlist</span>
                        <span class="inline-flex items-center justify-center rounded-full bg-rose-100 px-3 py-0.5 text-sm font-black text-rose-700">
                            {{ number_format($students->total()) }}
                        </span>
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Review and assign sections to enrolled students who currently have no section in the AMIS database.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Copy for GC Button -->
                    <button type="button" @click="gcModalOpen = true" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800 active:scale-[0.98] cursor-pointer">
                        <i data-lucide="message-square" class="h-4 w-4"></i>
                        <span>Copy for GC Announcement</span>
                    </button>

                    <!-- Export CSV Button -->
                    <a href="{{ route('admin.students.unassigned.export-csv', request()->query()) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                        <i data-lucide="download" class="h-4 w-4 text-emerald-600"></i>
                        <span>Export CSV</span>
                    </a>

                    <!-- Back to Main Student Records -->
                    <a href="{{ route('admin.students.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i data-lucide="arrow-left" class="h-4 w-4 text-slate-400"></i>
                        <span>All Student Records</span>
                    </a>
                </div>
            </div>

            <!-- Telemetry Stat Cards -->
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- ODL Unassigned Card -->
                <div class="rounded-xl border border-rose-100 bg-rose-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700">ODL / Online (Unassigned)</span>
                        <div class="rounded-full bg-rose-100 p-2 text-rose-700">
                            <i data-lucide="laptop" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-rose-900">{{ number_format($totalUnassignedOdl) }}</div>
                    <p class="mt-1 text-xs text-rose-600 font-medium">Requires section for subjects/grades</p>
                </div>

                <!-- F2F Unassigned Card -->
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Face-to-Face (F2F)</span>
                        <div class="rounded-full bg-indigo-100 p-2 text-indigo-700">
                            <i data-lucide="users" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-indigo-900">{{ number_format($totalUnassignedF2f) }}</div>
                    <p class="mt-1 text-xs text-indigo-600 font-medium">Section deliberately left blank</p>
                </div>

                <!-- Total Unassigned Card -->
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Unassigned</span>
                        <div class="rounded-full bg-white p-2 text-slate-700 border border-slate-200">
                            <i data-lucide="user-x" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-slate-900">{{ number_format($totalUnassigned) }}</div>
                    <p class="mt-1 text-xs text-slate-500 font-medium">All modalities combined</p>
                </div>

                <!-- Filtered Result Card -->
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Current View</span>
                        <div class="rounded-full bg-emerald-100 p-2 text-emerald-700">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-black text-emerald-900">{{ number_format($students->total()) }}</div>
                    <p class="mt-1 text-xs text-emerald-700 font-medium">Page {{ $students->currentPage() }} of {{ $students->lastPage() }}</p>
                </div>
            </div>

            <!-- Grade Quick Filter Pills -->
            <div class="mt-6 border-t border-slate-100 pt-5">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">Filter by Grade Level (ODL Unassigned count):</div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.students.unassigned', array_merge(request()->except('grade', 'page'), ['grade' => ''])) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ empty($gradeFilter) ? 'bg-slate-900 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        <span>All Grades</span>
                        <span class="rounded-full {{ empty($gradeFilter) ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-600' }} px-1.5 py-0.2 text-[10px]">{{ $totalUnassignedOdl }}</span>
                    </a>
                    @foreach($gradeOrder as $g)
                        @php $gCount = $odlByGrade[$g] ?? 0; @endphp
                        <a href="{{ route('admin.students.unassigned', array_merge(request()->except('grade', 'page'), ['grade' => $g])) }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $gradeFilter === $g ? 'bg-emerald-700 text-white shadow-sm' : ($gCount > 0 ? 'border border-rose-200 bg-rose-50/70 text-rose-800 hover:bg-rose-100' : 'border border-slate-200 bg-white text-slate-400 hover:bg-slate-50') }}">
                            <span>{{ $g }}</span>
                            @if($gCount > 0)
                                <span class="rounded-full {{ $gradeFilter === $g ? 'bg-emerald-800 text-white' : 'bg-rose-200 text-rose-900 font-extrabold' }} px-1.5 py-0.2 text-[10px]">{{ $gCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Filter Bar & Form -->
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.students.unassigned') }}" class="grid grid-cols-12 gap-3" id="filterForm">
                <!-- Search Box -->
                <label class="relative col-span-12 sm:col-span-6 lg:col-span-3">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search name, ID, or LRN..." class="{{ $inputClass }} w-full pl-9">
                </label>

                <!-- Grade Filter -->
                <select name="grade" class="{{ $inputClass }} col-span-6 sm:col-span-3 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All Grade Levels</option>
                    @foreach($gradeOrder as $g)
                        <option value="{{ $g }}" @selected($gradeFilter === $g)>{{ $g }}</option>
                    @endforeach
                </select>

                <!-- Mode Filter -->
                <select name="mode" class="{{ $inputClass }} col-span-6 sm:col-span-3 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="odl" @selected($modeFilter === 'odl')>Online / ODL Only</option>
                    <option value="f2f" @selected($modeFilter === 'f2f')>Face-to-Face Only</option>
                    <option value="all" @selected($modeFilter === 'all')>All Learning Modes</option>
                </select>

                <!-- Shift Filter -->
                <select name="shift" class="{{ $inputClass }} col-span-6 sm:col-span-3 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All Shifts</option>
                    <option value="1st Shift" @selected($shiftFilter === '1st Shift')>1st Shift</option>
                    <option value="2nd Shift" @selected($shiftFilter === '2nd Shift')>2nd Shift</option>
                </select>

                <!-- Gender Filter -->
                <select name="gender" class="{{ $inputClass }} col-span-6 sm:col-span-3 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All Genders</option>
                    <option value="male" @selected($genderFilter === 'male')>Male</option>
                    <option value="female" @selected($genderFilter === 'female')>Female</option>
                </select>

                <!-- Submit Button -->
                <div class="col-span-12 lg:col-span-1 flex items-center gap-2">
                    <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        <span>Filter</span>
                    </button>
                    @if(!empty($search) || !empty($gradeFilter) || $modeFilter !== 'odl' || !empty($shiftFilter) || !empty($genderFilter))
                        <a href="{{ route('admin.students.unassigned') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-slate-500 hover:bg-slate-50 hover:text-slate-800" title="Reset Filters">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <!-- Bulk Action Bar (Shows when checkboxes are selected) -->
        <div x-show="selectedStudents.length > 0" x-cloak class="rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 shadow-md backdrop-blur-sm transition-all flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-xs font-black text-white" x-text="selectedStudents.length"></span>
                <span class="text-sm font-bold text-emerald-950">Student(s) selected for bulk assignment</span>
            </div>

            <form method="POST" action="{{ route('admin.students.unassigned.bulk-assign') }}" class="flex flex-wrap items-center gap-2.5">
                @csrf
                <template x-for="id in selectedStudents" :key="id">
                    <input type="hidden" name="student_ids[]" :value="id">
                </template>

                <select name="section_id" x-model="bulkSectionId" class="h-10 rounded-lg border border-emerald-300 bg-white px-3 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-400" required>
                    <option value="">-- Choose Target Section --</option>
                    @foreach($sections as $g => $gradeSections)
                        <optgroup label="=== {{ $g }} ===">
                            @foreach($gradeSections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }} ({{ $sec->shift ?: 'Shift N/A' }})</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <button type="submit" :disabled="!bulkSectionId" class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                    <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                    <span>Assign Selected</span>
                </button>

                <button type="button" @click="selectedStudents = []; selectAll = false" class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
            </form>
        </div>

        <!-- Student List Table -->
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            @if($students->isEmpty())
                <div class="py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <i data-lucide="check-circle-2" class="h-8 w-8"></i>
                    </div>
                    <h3 class="mt-4 text-base font-bold text-slate-900">No Unassigned Students Found</h3>
                    <p class="mt-1 text-sm text-slate-500">All student records for the selected filter have already been assigned to sections.</p>
                    <div class="mt-6">
                        <a href="{{ route('admin.students.unassigned') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5 text-slate-400"></i>
                            Reset Filters
                        </a>
                    </div>
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
                                <th class="w-28 px-5 py-4 font-extrabold">Grade Level</th>
                                <th class="w-44 px-5 py-4 font-extrabold">Learning Mode</th>
                                <th class="w-24 px-5 py-4 font-extrabold">Gender</th>
                                <th class="w-40 px-5 py-4 font-extrabold">Parent Contact</th>
                                <th class="w-64 px-5 py-4 pr-6 text-right font-extrabold">Assign Section</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($students as $st)
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
                                    $gradeSections = $sections[$st->grade_level] ?? collect();
                                @endphp
                                <tr class="transition-colors duration-100 hover:bg-slate-50/90">
                                    <!-- Checkbox -->
                                    <td class="px-5 py-4">
                                        <input type="checkbox" value="{{ $st->id }}" x-model="selectedStudents" class="student-checkbox rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                    </td>

                                    <!-- Student Number -->
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center font-mono font-black text-xs text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-lg tracking-tight">
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
                                                containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-black"
                                                :eager="false"
                                            />
                                            <div>
                                                <a href="{{ route('admin.students.show', $st->id) }}" class="font-extrabold text-slate-950 hover:text-emerald-700 hover:underline leading-tight">
                                                    {{ $name }}
                                                </a>
                                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500">
                                                    @if($st->applicant?->lrn)
                                                        <span class="font-mono text-[11px] text-slate-500">LRN: {{ $st->applicant->lrn }}</span>
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

                                    <!-- Learning Mode & Shift -->
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

                                    <!-- Parent Contact -->
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                                        <div class="text-xs font-medium text-slate-800">{{ $st->applicant?->parent_mobile ?? $st->applicant?->mobile_number ?? 'No Mobile' }}</div>
                                        <div class="text-[11px] text-slate-400 truncate max-w-[140px]">{{ $st->applicant?->parent_email ?? '' }}</div>
                                    </td>

                                    <!-- Section Assign Quick Dropdown -->
                                    <td class="whitespace-nowrap px-5 py-4 pr-6 text-right">
                                        <form method="POST" action="{{ route('admin.students.unassigned.assign') }}" class="inline-flex items-center gap-1.5 justify-end">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $st->id }}">
                                            <select name="section_id" class="h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-700 outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100 max-w-[170px]" required>
                                                <option value="">Select Section...</option>
                                                @if($gradeSections->isNotEmpty())
                                                    @foreach($gradeSections as $sec)
                                                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                                    @endforeach
                                                @else
                                                    @foreach($sections as $otherG => $otherSecs)
                                                        <optgroup label="{{ $otherG }}">
                                                            @foreach($otherSecs as $sec)
                                                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-emerald-700 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800 active:scale-[0.98] cursor-pointer" title="Assign Section">
                                                Assign
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($students->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $students->links() }}
                    </div>
                @endif
            @endif
        </section>

        <!-- Group Chat Text Modal -->
        <div x-show="gcModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div @click.away="gcModalOpen = false" class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
                <button type="button" @click="gcModalOpen = false" class="absolute right-4 top-4 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <i data-lucide="message-square" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Copy Group Chat Announcement</h2>
                        <p class="text-xs text-slate-500">Formatted text with all unassigned ODL students per grade level ready to post.</p>
                    </div>
                </div>

                <div class="mb-4">
                    <textarea id="gc-text-area" rows="12" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 font-mono text-xs leading-relaxed text-slate-800 outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 resize-none" readonly>{{ $gcTextContent }}</textarea>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-xs text-emerald-700 font-bold" x-show="copiedToast" x-transition>
                        ✓ Copied to clipboard successfully!
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" @click="copyGcText()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-700 px-5 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 active:scale-[0.98] cursor-pointer">
                            <i data-lucide="copy" class="h-4 w-4"></i>
                            <span>Copy to Clipboard</span>
                        </button>
                        <button type="button" @click="gcModalOpen = false" class="h-10 rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-600 hover:bg-slate-100">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
