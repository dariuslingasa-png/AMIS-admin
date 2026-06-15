@inject('reviewService', 'App\Services\Admin\Enrollment\EnrollmentReviewService')

@php
    $docColor = ['approved' => 'green', 'rejected' => 'red', 'pending' => 'yellow'];
@endphp

<x-admin-layout
    title="Document Verification"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Document Verification', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Document Verification</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Audit and verify uploaded registration files, birth certificates, and academic credentials for active students.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Card -->
        <x-card title="Verification Registry" subtitle="Review verification statuses for mandatory enrollment requirements">
            <!-- Search Filter -->
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.students.verification') }}" class="flex items-center gap-2">
                            <div class="relative w-full sm:w-[320px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student or ID..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-450 focus:ring-4 focus:ring-emerald-100">
                                @if(request()->filled('search'))
                                    <a href="{{ route('admin.students.verification') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
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
                    <table class="w-full text-left text-sm min-w-[950px]">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-5 py-3">Student Info</th>
                                <th class="px-5 py-3">Grade Level</th>
                                <th class="px-5 py-3">Required Documents & Status</th>
                                <th class="w-44 px-5 py-3 text-center">Verification Readiness</th>
                                <th class="w-36 px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($students as $student)
                                @php
                                    $applicant = $student->applicant;
                                    $fullName = $applicant ? html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                    
                                    $requirements = $applicant ? $reviewService->getRequiredDocuments($applicant) : [];
                                    $statuses = $applicant->document_statuses ?? [];
                                    $approved = collect(array_keys($requirements))->filter(fn ($key) => ($statuses[$key] ?? 'pending') === 'approved')->count();
                                    $totalReq = count($requirements);
                                    $ready = $totalReq > 0 && $approved === $totalReq;
                                @endphp
                                <tr class="align-middle transition hover:bg-slate-50/30">
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold text-slate-900 block uppercase">{{ $fullName }}</span>
                                        <span class="text-xs font-bold text-slate-450 mt-1 block">{{ $student->student_number }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                        {{ $student->grade_level }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($applicant)
                                            <div class="flex flex-wrap gap-2 max-w-xl">
                                                @foreach ($requirements as $key => $label)
                                                    @php $state = $statuses[$key] ?? 'pending'; @endphp
                                                    <x-badge :color="$docColor[$state] ?? 'gray'">
                                                        {{ $label }}: {{ \Illuminate\Support\Str::headline($state) }}
                                                    </x-badge>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs font-bold text-slate-400 italic">No applicant record linked</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        @if($totalReq > 0)
                                            <x-badge :color="$ready ? 'green' : 'yellow'">
                                                {{ $approved }}/{{ $totalReq }} Approved
                                            </x-badge>
                                        @else
                                            <span class="text-xs font-bold text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.students.show', $student) }}#documents" class="inline-flex h-9 items-center justify-center gap-1 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                                            <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                            Verify Files
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No student document verification records found.</td>
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
