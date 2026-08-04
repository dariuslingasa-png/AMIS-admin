@php
    $pageTitle = 'Archived Applications';
@endphp

<x-admin-layout :title="$pageTitle" :breadcrumbs="[
    ['label' => 'Applications', 'href' => route('admin.applications.enrollment')],
    ['label' => 'Archive / Trash', 'href' => null]
]">
    <div class="space-y-6">
        <!-- Header Banner & Warning -->
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 text-white shadow-xl">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-2 rounded-full bg-rose-500/20 px-3 py-1 text-xs font-bold text-rose-300 border border-rose-500/30">
                    <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                    7-Day Auto Retention Policy
                </div>
                <h1 class="text-2xl font-black tracking-tight">Archived Enrollment Applications</h1>
                <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                    Deleted applications are stored here safely for 7 days. After 7 days, they are permanently purged from the system. Restoring an application makes it active again and restores parent access on the enrollment portal.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.applications.enrollment') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-white/20 hover:scale-105">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to Active Enrollment
                </a>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.applications.archive') }}" class="flex items-center gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by student name, ID, or parent email..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 py-2.5 text-xs font-medium text-slate-800 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-slate-800 cursor-pointer">
                    Filter
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.applications.archive') }}" class="inline-flex h-10 items-center gap-1 rounded-xl border border-slate-200 px-3.5 text-xs font-bold text-slate-600 hover:bg-slate-50">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Main Archive Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4">Applicant ID & Name</th>
                        <th class="px-5 py-4">Student Type</th>
                        <th class="px-5 py-4">Grade</th>
                        <th class="px-5 py-4">Archived Date</th>
                        <th class="px-5 py-4">Retention Status</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($archivedApplicants as $applicant)
                        @php
                            $daysLeft = 7 - (int) floor(now()->diffInDays($applicant->deleted_at));
                            if ($daysLeft < 0) $daysLeft = 0;
                            $applicantName = $applicant->full_name ?: 'N/A';
                            $formattedId = str_pad($applicant->id, 4, '0', STR_PAD_LEFT);
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            <!-- Name & ID -->
                            <td class="px-5 py-4 font-bold text-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 text-rose-700 font-black text-xs">
                                        {{ mb_substr($applicant->first_name ?: 'A', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-slate-900 text-sm">
                                            {{ $applicantName }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-mono">
                                            Applicant #{{ $formattedId }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Student Type -->
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-700">
                                    {{ $applicant->student_type ?? 'New' }}
                                </span>
                            </td>

                            <!-- Grade -->
                            <td class="px-5 py-4 font-bold text-slate-800">
                                {{ $applicant->grade_abbr ?? 'N/A' }}
                            </td>

                            <!-- Archived Date -->
                            <td class="px-5 py-4 text-slate-600 font-medium">
                                <div>{{ $applicant->deleted_at ? $applicant->deleted_at->format('M d, Y') : 'N/A' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $applicant->deleted_at ? $applicant->deleted_at->format('g:i A') : '' }}</div>
                            </td>

                            <!-- Retention Countdown -->
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-800">
                                    <i data-lucide="clock" class="h-3.5 w-3.5 text-amber-600"></i>
                                    Auto-purges in {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right align-middle whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2 whitespace-nowrap">
                                    <!-- Restore Form -->
                                    <form method="POST" action="{{ route('admin.applicants.restore', $applicant->id) }}" onsubmit="return confirm('Restore enrollment application for {{ e($applicantName) }}?')" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 text-xs font-bold text-emerald-700 shadow-3xs transition hover:bg-emerald-600 hover:text-white cursor-pointer" title="Restore Application">
                                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                            Restore
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                                    <i data-lucide="archive-x" class="h-6 w-6"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-600">No Archived Applications Found</p>
                                <p class="text-xs text-slate-400 mt-1">Deleted enrollment applications will appear here and be kept for 7 days before auto-purge.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($archivedApplicants->hasPages())
                <div class="border-t border-slate-100 p-4">
                    {{ $archivedApplicants->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
