@php
    $totalActive = \App\Models\Student::whereHas('user', fn($q) => $q->where('account_status', 'verified'))->count();
    $totalGraduated = \App\Models\Student::whereHas('user', fn($q) => $q->where('account_status', 'graduated'))->count();
    $totalTransferred = \App\Models\Student::whereHas('user', fn($q) => $q->where('account_status', 'transferred'))->count();
    $totalWithdrawn = \App\Models\Student::whereHas('user', fn($q) => $q->where('account_status', 'withdrawn'))->count();

    $statusColors = [
        'verified' => 'emerald',
        'suspended' => 'red',
        'graduated' => 'blue',
        'transferred' => 'amber',
        'withdrawn' => 'gray'
    ];

    $statusLabels = [
        'verified' => 'Active / Verified',
        'suspended' => 'Suspended',
        'graduated' => 'Graduated',
        'transferred' => 'Transferred',
        'withdrawn' => 'Withdrawn'
    ];
@endphp

<x-admin-layout
    title="Promotions & Transfers"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Promotions & Transfers', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Promotions & Transfers</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Manage academic year promotions, graduations, school transfers, and enrollment withdrawals.
                    </p>
                </div>
            </div>
        </section>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Active Students</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($totalActive) }}</span>
                    <span class="text-xs font-bold text-emerald-600">Verified</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Graduated</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($totalGraduated) }}</span>
                    <span class="text-xs font-bold text-blue-600">Alumni</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Transferred Out</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($totalTransferred) }}</span>
                    <span class="text-xs font-bold text-amber-600">Other Schools</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Withdrawn</p>
                <div class="flex items-baseline gap-2 mt-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ number_format($totalWithdrawn) }}</span>
                    <span class="text-xs font-bold text-slate-500">Dropped</span>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <x-card title="Promotions & Transitions Registry" subtitle="Manage grade level advancement and status updates">
            <!-- Search Filter -->
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.students.promotions') }}" class="flex items-center gap-2">
                            <div class="relative w-full sm:w-[320px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student or ID..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-450 focus:ring-4 focus:ring-emerald-100">
                                @if(request()->filled('search'))
                                    <a href="{{ route('admin.students.promotions') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 cursor-pointer">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Table Container -->
            <div id="tableContainer">
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[850px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">Student</th>
                                <th class="px-5 py-3">Current Grade</th>
                                <th class="px-5 py-3">Academic Year</th>
                                <th class="px-5 py-3">Administrative Status</th>
                                <th class="w-44 px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($students as $student)
                                @php
                                    $applicant = $student->applicant;
                                    $fullName = $applicant ? html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? '').($applicant->suffix ? ' '.$applicant->suffix : '')), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                    $status = $student->user->account_status ?? 'verified';
                                    $color = $statusColors[$status] ?? 'gray';
                                    $label = $statusLabels[$status] ?? ucfirst($status);
                                @endphp
                                <tr class="align-middle transition hover:bg-slate-50/30">
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold text-slate-900 block uppercase">{{ $fullName }}</span>
                                        <div class="mt-1 flex items-center gap-1.5 text-xs font-bold text-slate-400">
                                            <span>{{ $student->student_number }}</span>
                                            @if ($student->applicant && $student->applicant->user)
                                                <span class="text-slate-300">•</span>
                                                <a href="{{ route('admin.students.families', ['search' => $student->applicant->user->email]) }}" class="inline-flex items-center gap-0.5 text-emerald-600 hover:text-emerald-700 font-extrabold transition" title="View Family Account">
                                                    <i data-lucide="home" class="h-3 w-3"></i>
                                                    Family
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-xs font-bold text-slate-655">
                                        {{ $student->grade_level }}
                                    </td>
                                    <td class="px-5 py-4 text-xs font-semibold text-slate-500">
                                        SY {{ $student->school_year }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <x-badge :color="$color">
                                            {{ $label }}
                                        </x-badge>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-slate-50 border border-slate-200 px-3.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                                            <i data-lucide="arrow-left-right" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            Promote / Transfer
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No student promotion records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                    <p class="text-xs font-bold text-slate-500">
                        Showing {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} of {{ $students->total() }} students
                    </p>
                    <div class="w-full sm:w-auto">{{ $students->links() }}</div>
                </div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
