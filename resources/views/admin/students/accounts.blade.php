@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
@endphp

<x-admin-layout
    title="Account Onboarding Checklist"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Account Onboarding', 'href' => null],
    ]"
>
    <!-- Stats Cards Section -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="users" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Accounts</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i data-lucide="alert-circle" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Pending Onboarding</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['pending']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i data-lucide="check-circle" class="h-6 w-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Fully Onboarded</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['completed']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Section Header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Account Management</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Student Onboarding Tracker</h1>
                <p class="mt-1 text-sm text-slate-500">Track and manage student record status, payment verification, and Microsoft synchronization.</p>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Filter & Search Bar Form -->
            <form method="GET" class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Three Radio Buttons Filter styled as Segmented Tabs -->
                <div class="inline-flex rounded-lg bg-slate-100 p-1">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="status_filter" value="all" @checked($statusFilter === 'all') class="sr-only" onchange="this.form.submit()">
                        <span class="inline-flex h-9 items-center justify-center rounded-md px-4 text-xs font-bold transition-all {{ $statusFilter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-950' }}">
                            All Accounts ({{ $stats['total'] }})
                        </span>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="status_filter" value="pending" @checked($statusFilter === 'pending') class="sr-only" onchange="this.form.submit()">
                        <span class="inline-flex h-9 items-center justify-center rounded-md px-4 text-xs font-bold transition-all {{ $statusFilter === 'pending' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-950' }}">
                            Pending ({{ $stats['pending'] }})
                        </span>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="status_filter" value="completed" @checked($statusFilter === 'completed') class="sr-only" onchange="this.form.submit()">
                        <span class="inline-flex h-9 items-center justify-center rounded-md px-4 text-xs font-bold transition-all {{ $statusFilter === 'completed' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-950' }}">
                            Completed ({{ $stats['completed'] }})
                        </span>
                    </label>
                </div>

                <!-- Search Input -->
                <label class="relative w-full md:w-80">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search ID, name, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
            </form>

            <!-- Grid Checklist Table -->
            <div class="overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="w-32 px-5 py-4 font-bold">Student ID</th>
                            <th class="px-5 py-4 font-bold">Student Info</th>
                            <th class="w-32 px-5 py-4 text-center font-bold">1. Fill Up?</th>
                            <th class="w-36 px-5 py-4 text-center font-bold">2. Payment?</th>
                            <th class="w-32 px-5 py-4 text-center font-bold">3. 2x2 Pic?</th>
                            <th class="w-36 px-5 py-4 text-center font-bold">4. MS Account?</th>
                            <th class="w-36 px-5 py-4 text-center font-bold">5. Teams?</th>
                            <th class="w-28 px-5 py-4 text-right font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($students as $student)
                            @php
                                $applicant = $student->applicant;
                                $fullName = $applicant ? html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                                $upperName = \Illuminate\Support\Str::upper($fullName);
                                
                                // Step 1: Profile Filled Up (100% completion)
                                $isFilled = $applicant && $applicant->completion_percentage === 100;
                                $fillPercent = $applicant ? $applicant->completion_percentage : 0;
                                
                                // Step 2: Payment Proof (receipt url uploaded)
                                $hasPayment = false;
                                if ($applicant && $applicant->payment) {
                                    $urls = $applicant->payment->receipt_urls;
                                    $validUrls = array_filter($urls, fn($u) => filled($u) && $u !== '[]' && $u !== '[""]');
                                    if (!empty($validUrls)) {
                                        $hasPayment = true;
                                    }
                                }
                                
                                // Step 3: 2x2 Pic (photo url uploaded)
                                $hasPic = $applicant && filled($applicant->photo_2x2_url);
                                
                                // Step 4: Microsoft Exist in Azure AD (ms user id generated)
                                $hasMsAccount = filled($student->ms_user_id);
                                
                                // Step 5: Teams Enrolled (enrolled at is filled)
                                $isTeamsEnrolled = filled($student->ms_teams_enrolled_at);
                                
                                $studentType = $applicant ? $applicant->student_type : 'New';
                                $learningMode = $applicant ? $applicant->learning_mode : 'F2F';
                                
                                // Parse Shift / learning mode type abbreviation
                                $modeAbbr = 'F2F';
                                if (str_contains(strtolower($learningMode), 'online') || str_contains(strtolower($learningMode), 'flexible') || str_contains(strtolower($learningMode), 'odl')) {
                                    $modeAbbr = 'ODL';
                                }
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <!-- Student Number -->
                                <td class="px-5 py-4 font-extrabold text-slate-800">
                                    {{ $student->student_number ?? '-' }}
                                </td>

                                <!-- Student details -->
                                <td class="px-5 py-4">
                                    <div class="font-extrabold text-slate-950">{{ $upperName }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500">
                                        <span>{{ $student->grade_level }}</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-violet-700 bg-violet-50 px-1.5 py-0.5 rounded">{{ strtoupper($studentType) }}</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-sky-700 bg-sky-50 px-1.5 py-0.5 rounded">{{ $modeAbbr }}</span>
                                    </div>
                                </td>

                                <!-- Grid Check 1: Fill Up -->
                                <td class="px-5 py-4 text-center">
                                    @if ($isFilled)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" title="Profile 100% Completed">
                                            <i data-lucide="check" class="h-4 w-4 stroke-[3]"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 px-2 items-center justify-center rounded bg-slate-50 text-xs font-bold text-slate-500 ring-1 ring-slate-100" title="Profile is {{ $fillPercent }}% complete">
                                            {{ $fillPercent }}%
                                        </span>
                                    @endif
                                </td>

                                <!-- Grid Check 2: Payment Proof -->
                                <td class="px-5 py-4 text-center">
                                    @if ($hasPayment)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" title="Payment Proof Uploaded">
                                            <i data-lucide="check" class="h-4 w-4 stroke-[3]"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-rose-500 ring-1 ring-rose-100" title="Missing Payment Proof">
                                            <i data-lucide="minus" class="h-4 w-4"></i>
                                        </span>
                                    @endif
                                </td>

                                <!-- Grid Check 3: 2x2 Pic -->
                                <td class="px-5 py-4 text-center">
                                    @if ($hasPic)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" title="2x2 Photo Uploaded">
                                            <i data-lucide="check" class="h-4 w-4 stroke-[3]"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-rose-500 ring-1 ring-rose-100" title="Missing 2x2 Photo">
                                            <i data-lucide="minus" class="h-4 w-4"></i>
                                        </span>
                                    @endif
                                </td>

                                <!-- Grid Check 4: Microsoft AD Account -->
                                <td class="px-5 py-4 text-center">
                                    @if ($hasMsAccount)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" title="Microsoft Account Provisioned">
                                            <i data-lucide="check" class="h-4 w-4 stroke-[3]"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-rose-500 ring-1 ring-rose-100" title="Missing Microsoft Account">
                                            <i data-lucide="minus" class="h-4 w-4"></i>
                                        </span>
                                    @endif
                                </td>

                                <!-- Grid Check 5: Teams Enrolled -->
                                <td class="px-5 py-4 text-center">
                                    @if ($isTeamsEnrolled)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100" title="Enrolled in Teams Classrooms">
                                            <i data-lucide="check" class="h-4 w-4 stroke-[3]"></i>
                                        </span>
                                    @else
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-50 text-rose-500 ring-1 ring-rose-100" title="Pending Teams Enrollment">
                                            <i data-lucide="minus" class="h-4 w-4"></i>
                                        </span>
                                    @endif
                                </td>

                                <!-- Action button -->
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        <i data-lucide="file-search" class="h-4 w-4"></i>
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                    No students match the current checklist filter.
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
