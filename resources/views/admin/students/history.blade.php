@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
    $msStatusBadge = [
        'enrolled' => 'green',
        'failed' => 'red',
        'pending' => 'yellow'
    ];
    $msStatusLabel = [
        'enrolled' => 'Microsoft Active',
        'failed' => 'Sync Error',
        'pending' => 'Sync Pending'
    ];
@endphp

<x-admin-layout
    title="Enrollment History"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Enrollment History', 'href' => null],
    ]"
>
    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 px-6 py-6 bg-gradient-to-r from-slate-50 to-slate-100/50">
            <div>
                <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Students Workspace</p>
                <h1 class="mt-1 text-xl font-extrabold text-slate-950">Enrollment & Onboarding History</h1>
                <p class="mt-1 text-xs md:text-sm text-slate-500 font-medium">A chronological audit log of student registrations, auto-generated emails, OR numbers, and portal credentials.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.students.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-95">
                    <i data-lucide="user-check" class="h-4 w-4"></i>
                    Student Records
                </a>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Filter Bar Form -->
            <form method="GET" class="mb-5 flex gap-3 max-w-lg">
                <label class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search name, student number, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98]">
                    <i data-lucide="search-code" class="h-4 w-4"></i>
                    Search Logs
                </button>
            </form>

            <!-- Audit Log Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-4 font-black">Timeline & Student</th>
                            <th class="w-36 px-5 py-4 font-black">Student ID</th>
                            <th class="w-48 px-5 py-4 font-black">Assigned Section</th>
                            <th class="w-56 px-5 py-4 font-black">Credentials Set</th>
                            <th class="w-48 px-5 py-4 font-black">Verified Payment</th>
                            <th class="w-40 px-5 py-4 font-black">MS Cloud State</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($logs as $log)
                            @php
                                $fullName = trim(($log->applicant->first_name ?? '').' '.($log->applicant->middle_name ?? '').' '.($log->applicant->last_name ?? ''));
                                $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT ACCOUNT';
                                $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                $photoUrl = \App\Support\EnrollmentStorage::url($log->applicant->photo_2x2_url ?? null);
                                $msStatus = $log->studentSection->ms_status ?? 'pending';
                                $paddedAppId = $log->applicant ? 'APPLICANT #' . str_pad($log->applicant->id, 4, '0', STR_PAD_LEFT) : 'APPLICANT';
                            @endphp
                            <tr class="transition hover:bg-slate-50/50">
                                <!-- Timestamp and Student Initials & Name -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Timeline point indicator -->
                                        <div class="relative flex flex-col items-center">
                                            <div class="h-2 w-2 rounded-full bg-emerald-600 ring-4 ring-emerald-50"></div>
                                        </div>
                                        <x-smart-image
                                            :src="$photoUrl"
                                            :alt="$name"
                                            :fallback-initials="$initials ?: 'ST'"
                                            size="40"
                                            rounded="rounded-xl"
                                            containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-extrabold"
                                            :eager="false"
                                        />
                                        <div>
                                            <div class="font-extrabold text-slate-900 leading-tight">{{ $name }}</div>
                                            <div class="mt-1 text-[10px] font-bold text-slate-400 flex items-center gap-1">
                                                <i data-lucide="clock" class="h-3 w-3"></i>
                                                {{ $log->created_at ? $log->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') : 'NA' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Student ID -->
                                <td class="px-5 py-4">
                                    <span class="font-black text-slate-800 bg-slate-100 px-2 py-0.5 rounded text-xs">
                                        {{ $log->student_number ?? '-' }}
                                    </span>
                                </td>

                                <!-- Grade Level & Section -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-700">{{ $log->grade_level ?? '-' }}</div>
                                    <div class="mt-0.5 text-[10px] font-bold uppercase text-slate-400">
                                        {{ $log->studentSection->section->official_name ?? $log->studentSection->section->name ?? 'No Section' }}
                                    </div>
                                </td>

                                <!-- School Email -->
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-700 text-xs flex items-center gap-1">
                                        <i data-lucide="mail" class="h-3 w-3 text-slate-400"></i>
                                        {{ $log->school_email ?? '-' }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] font-bold text-slate-400">
                                        {{ $paddedAppId }}
                                    </div>
                                </td>

                                <!-- Verified Payment & OR -->
                                <td class="px-5 py-4">
                                    @if($log->applicant && $log->applicant->payment)
                                        <div class="font-bold text-emerald-700 text-xs flex items-center gap-1">
                                            <i data-lucide="badge-check" class="h-3.5 w-3.5"></i>
                                            OR {{ $log->applicant->payment->or_number ?: 'VERIFIED' }}
                                        </div>
                                        <div class="mt-0.5 text-[10px] font-semibold text-slate-500">
                                            Amount: PHP {{ number_format($log->applicant->payment->amount_paid ?? 0, 2) }}
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 font-bold">Onboarded (Old/Scholar)</span>
                                    @endif
                                </td>

                                <!-- MS Sync status -->
                                <td class="px-5 py-4">
                                    <x-badge :color="$msStatusBadge[$msStatus] ?? 'gray'">
                                        {{ $msStatusLabel[$msStatus] ?? 'Unknown' }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">
                                    No enrollment onboarding events logged.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            <div class="mt-5">{{ $logs->links() }}</div>
        </div>
    </section>
</x-admin-layout>
