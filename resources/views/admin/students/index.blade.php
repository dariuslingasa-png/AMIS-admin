@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
    $msSyncColor = ['enrolled' => 'green', 'failed' => 'red', 'pending' => 'yellow'];
    $msSyncLabel = ['enrolled' => 'Synced', 'failed' => 'Sync Failed', 'pending' => 'Pending Teams'];
    $sort = request('sort', 'latest');
    $direction = request('direction', 'desc') === 'asc' ? 'asc' : 'desc';
    $sortUrl = fn ($key) => route('admin.students.index', array_merge(request()->except('page'), [
        'sort' => $key,
        'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc',
    ]));
    $sortIcon = fn ($key) => $sort !== $key ? 'arrow-up-down' : ($direction === 'asc' ? 'arrow-up' : 'arrow-down');
    $gradeTotals = collect($analytics['grades'] ?? [])->keyBy('grade_level');
    $genderAnalytics = $analytics['gender'] ?? ['male' => 0, 'female' => 0, 'not_set' => 0];
    $genderLabels = ['male' => 'Male', 'female' => 'Female', 'not_set' => 'Not Set'];
@endphp

<x-admin-layout
    title="Student Records"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Student Records', 'href' => null],
    ]"
>
    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Section Header -->
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Students Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Student Records</h1>
                <p class="mt-1 text-sm text-slate-500">View enrolled student accounts, credentials, and synchronized teams channels.</p>
            </div>
            <a href="{{ route('admin.students.dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                Dashboard
            </a>
        </div>

        <div class="px-6 py-5">
            <!-- Filter Bar Form -->
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <label class="relative col-span-12 lg:col-span-4">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search name, student number, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
                <select name="grade" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All grades</option>
                    @foreach($gradeOrder as $g)
                        <option value="{{ $g }}" @selected(request('grade') === $g)>{{ $g }}</option>
                    @endforeach
                </select>
                <select name="gender" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All genders</option>
                    <option value="male" @selected(request('gender') === 'male')>Male</option>
                    <option value="female" @selected(request('gender') === 'female')>Female</option>
                    <option value="not_set" @selected(request('gender') === 'not_set')>Not Set</option>
                </select>
                <select name="mode" class="{{ $inputClass }} col-span-8 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All learning modes</option>
                    <option value="Face-to-Face" @selected(request('mode') === 'Face-to-Face')>Face-to-Face</option>
                    <option value="Flexible" @selected(request('mode') === 'Flexible')>Flexible Online Learning</option>
                </select>
                <button class="col-span-4 lg:col-span-2 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>
            </form>

            <div class="mb-5 grid grid-cols-1 gap-3 xl:grid-cols-[220px_1fr_300px]">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Filtered Students</p>
                    <p class="mt-1 text-3xl font-black text-slate-950">{{ number_format($analytics['filtered_total'] ?? $students->total()) }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-400">of {{ number_format($stats['total_students'] ?? 0) }} total</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Grade Grid</p>
                        <a href="{{ $sortUrl('grade') }}" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1 text-xs font-black text-slate-600 transition hover:bg-slate-50">
                            Sort
                            <i data-lucide="{{ $sortIcon('grade') }}" class="h-3.5 w-3.5"></i>
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
                        @foreach ($gradeOrder as $grade)
                            @php $gradeCount = (int) optional($gradeTotals->get($grade))->total; @endphp
                            <a href="{{ route('admin.students.index', array_merge(request()->except('page'), ['grade' => $grade])) }}"
                               class="rounded-md border px-2.5 py-2 transition {{ request('grade') === $grade ? 'border-emerald-300 bg-emerald-50 text-emerald-800' : 'border-slate-100 bg-slate-50 text-slate-600 hover:border-slate-200 hover:bg-white' }}">
                                <div class="text-[11px] font-black">{{ $grade }}</div>
                                <div class="mt-1 text-lg font-black">{{ $gradeCount }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Gender Grid</p>
                        <a href="{{ $sortUrl('gender') }}" class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2.5 py-1 text-xs font-black text-slate-600 transition hover:bg-slate-50">
                            Sort
                            <i data-lucide="{{ $sortIcon('gender') }}" class="h-3.5 w-3.5"></i>
                        </a>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($genderLabels as $genderKey => $genderLabel)
                            <a href="{{ route('admin.students.index', array_merge(request()->except('page'), ['gender' => $genderKey])) }}"
                               class="rounded-md border px-3 py-2 text-center transition {{ request('gender') === $genderKey ? 'border-violet-300 bg-violet-50 text-violet-800' : 'border-slate-100 bg-slate-50 text-slate-600 hover:border-slate-200 hover:bg-white' }}">
                                <div class="text-[11px] font-black uppercase">{{ $genderLabel }}</div>
                                <div class="mt-1 text-2xl font-black">{{ number_format((int) ($genderAnalytics[$genderKey] ?? 0)) }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Table Wrapper -->
            <div class="overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-bold">Student</th>
                            <th class="w-36 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('student_id') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Student ID
                                    <i data-lucide="{{ $sortIcon('student_id') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-28 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('gender') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Gender
                                    <i data-lucide="{{ $sortIcon('gender') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-44 px-5 py-4 font-bold">
                                <a href="{{ $sortUrl('grade') }}" class="inline-flex items-center gap-1.5 hover:text-slate-800">
                                    Academic Profile
                                    <i data-lucide="{{ $sortIcon('grade') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="w-48 px-5 py-4 font-bold">School Email</th>
                            <th class="w-40 px-5 py-4 font-bold">MS Sync State</th>
                            <th class="w-36 px-5 py-4 text-right font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($students as $student)
                            @php
                                $fullName = trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? ''));
                                $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                                $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url ?? null);
                                $msStatus = $student->studentSection->ms_status ?? 'pending';
                                $gender = strtolower((string) ($student->applicant->gender ?? ''));
                                $genderLabel = $gender === 'male' ? 'Male' : ($gender === 'female' ? 'Female' : 'Not Set');
                                $genderClass = $gender === 'male' ? 'bg-blue-50 text-blue-700 ring-blue-100' : ($gender === 'female' ? 'bg-violet-50 text-violet-700 ring-violet-100' : 'bg-slate-50 text-slate-500 ring-slate-100');
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <!-- Student Photo & Name -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-smart-image
                                            :src="$photoUrl"
                                            :alt="$name"
                                            :fallback-initials="$initials ?: 'ST'"
                                            size="40"
                                            rounded="rounded-md"
                                            containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-extrabold"
                                            :eager="false"
                                        />
                                        <div>
                                            <div class="font-extrabold text-slate-950">{{ $name }}</div>
                                            <div class="mt-0.5 text-xs font-medium text-slate-500">SY {{ $student->school_year ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Student Number -->
                                <td class="px-5 py-4 font-extrabold text-slate-600">
                                    {{ $student->student_number ?? '-' }}
                                </td>

                                <!-- Gender -->
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-extrabold ring-1 {{ $genderClass }}">{{ $genderLabel }}</span>
                                </td>

                                <!-- Grade Level & Section -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-700">{{ $student->grade_level ?? '-' }}</div>
                                    <div class="mt-0.5 text-xxs font-semibold uppercase text-slate-400">
                                        {{ $student->studentSection->section->official_name ?? $student->studentSection->section->name ?? 'No Section' }}
                                    </div>
                                </td>

                                <!-- School Email -->
                                <td class="px-5 py-4 font-medium text-slate-600">
                                    {{ $student->school_email ?? '-' }}
                                </td>

                                <!-- MS Sync status -->
                                <td class="px-5 py-4">
                                    <x-badge :color="$msSyncColor[$msStatus] ?? 'gray'">
                                        {{ $msSyncLabel[$msStatus] ?? 'Pending' }}
                                    </x-badge>
                                </td>

                                <!-- Action -->
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center gap-2 rounded-md border border-emerald-100 bg-white px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                                            <i data-lucide="file-search" class="h-4 w-4"></i>
                                            Manage
                                        </a>
                                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                              onsubmit="return confirm('Delete {{ $student->student_number }} ({{ $student->school_email }})?\n\nThis will permanently delete the student from the portal and Microsoft 365. This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50" title="Delete Student">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No enrolled students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            <div class="mt-5">{{ $students->links() }}</div>
        </div>
    </section>
</x-admin-layout>
