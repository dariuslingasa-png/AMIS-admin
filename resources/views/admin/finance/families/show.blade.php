<x-admin-layout title="Family SOA — {{ $family->name }}">
    <div class="finance-page mx-auto max-w-[1440px] p-5 lg:p-8" x-data="{
        showUploadModal: false,
        showHistoryModal: false,
        showPreviewModal: false,
        selectedStudentId: '{{ $family->enrollmentApplicants->first()?->amis_student_id ?: ($family->enrollmentApplicants->first()?->student?->student_number ?: $family->enrollmentApplicants->first()?->id) }}',
        activeStudent: null,
        previewUrl: '',
        previewTitle: '',
        previewIsPdf: false,
        historyList: [],
        selectStudent(id) {
            this.selectedStudentId = this.selectedStudentId === id ? null : id;
        },
        openUpload(student) {
            this.activeStudent = student;
            this.showUploadModal = true;
        },
        openHistory(student, list) {
            this.activeStudent = student;
            this.historyList = list;
            this.showHistoryModal = true;
        },
        openPreview(url, title, isPdf) {
            this.previewUrl = url;
            this.previewTitle = title;
            this.previewIsPdf = isPdf;
            this.showPreviewModal = true;
        }
    }">
        {{-- TOP NAVIGATION BREADCRUMB / HEADER --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.students.families') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition mb-1">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                    Back to Family Accounts
                </a>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">
                        {{ $family->name }}
                    </h1>
                    @if ($family->is_demo ?? false)
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-amber-800 border border-amber-200">DEMO ACCOUNT</span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Consolidated Family Statement of Account and Student Document Management.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.finance.onsite.create', ['family' => $family->id]) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/></svg>
                    Record Onsite Payment
                </a>
            </div>
        </div>

        {{-- DEMO NOTICE (IF APPLICABLE) --}}
        @if ($family->is_demo ?? false)
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-950 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-200 text-amber-900 font-black text-xs">!</span>
                    <div>
                        <strong class="font-bold text-amber-900">Demo Testing Family:</strong>
                        All dues, payments, and document records shown below are isolated demo data for workflow verification.
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.finance.families.reset-demo', ['family' => $family->id]) }}" onsubmit="return confirm('Reset all demo payments for this family back to initial July 2026 state?');">
                    @csrf
                    <button type="submit" class="rounded-xl border border-amber-300 bg-white px-3.5 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-100 shadow-sm whitespace-nowrap transition">
                        Reset Demo Data
                    </button>
                </form>
            </div>
        @endif

        {{-- TOP STATS ROW --}}
        <div class="mb-8 grid gap-5 sm:grid-cols-3">
            <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-sm flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Outstanding Balance</span>
                <div class="mt-3 flex items-baseline justify-between">
                    <p class="text-3xl font-black tracking-tight">₱{{ number_format($outstanding->sum('remaining'), 2) }}</p>
                    <span class="text-xs font-semibold text-slate-400">Family Ledger</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Advance Credit Available</span>
                <div class="mt-3 flex items-baseline justify-between">
                    <p class="text-3xl font-black tracking-tight {{ $advanceCredit > 0 ? 'text-emerald-700' : 'text-slate-900' }}">
                        ₱{{ number_format($advanceCredit, 2) }}
                    </p>
                    <span class="text-xs font-semibold text-slate-400">Prepayment balance</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Family Information</span>
                <div class="mt-2">
                    <p class="font-extrabold text-slate-900 truncate text-sm">{{ $family->email }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                        <span>Family ID: <strong class="font-mono text-slate-700">{{ $family->id }}</strong></span>
                        <span>·</span>
                        <span>Students: <strong class="text-slate-700">{{ $family->enrollmentApplicants->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 1: STUDENT CARDS (CLEAN COMPACT 3-GRID) --}}
        <section class="mb-8">
            <div class="mb-4">
                <h2 class="text-lg font-black tracking-tight text-slate-900">Student Accounts &amp; Statements of Account</h2>
                <p class="text-xs text-slate-500">Select any student card to view detailed monthly fee schedule and manage official SOAs.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($family->enrollmentApplicants as $applicant)
                    @if ($applicant->student?->account)
                        @php
                            $sName = $applicant->full_name ?? ($applicant->student?->full_name ?? 'Student');
                            $sGrade = $applicant->grade_level ?? ($applicant->student?->grade_level ?? '');
                            $sId = $applicant->amis_student_id ?: ($applicant->student?->student_number ?: "STU-{$applicant->id}");
                            $sBalance = (float) ($applicant->student->account->remaining_balance ?? 0);
                            $sPaid = (float) ($applicant->student->account->paid_to_date ?? 0);
                            $initials = collect(explode(' ', $sName))->filter()->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('');
                        @endphp
                        <div
                            @click="selectStudent('{{ $sId }}')"
                            class="cursor-pointer rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between"
                            :class="selectedStudentId === '{{ $sId }}' ? 'border-emerald-600 ring-2 ring-emerald-500/20 bg-emerald-50/10' : 'border-slate-200 hover:border-slate-300'"
                        >
                            <div>
                                {{-- CARD TOP ROW --}}
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-slate-800 font-bold text-white text-xs shadow-2xs">
                                            {{ $initials ?: 'ST' }}
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="font-black text-slate-900 text-sm truncate tracking-tight">{{ mb_strtoupper($sName) }}</h3>
                                            <div class="mt-0.5 flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $sGrade }}</span>
                                                <span class="font-mono text-xs text-slate-400">{{ $sId }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-slate-400 pt-1">
                                        <svg class="h-5 w-5 transform transition-transform duration-200" :class="selectedStudentId === '{{ $sId }}' ? 'rotate-180 text-emerald-700' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- FINANCIAL STATS ROW --}}
                                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-100 p-3.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Paid to Date</span>
                                            <span class="text-xs font-bold text-slate-700">₱{{ number_format($sPaid, 2) }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Remaining Balance</span>
                                            <span class="text-xl font-black tracking-tight text-slate-900">₱{{ number_format($sBalance, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CARD ACTION FOOTER --}}
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold" :class="selectedStudentId === '{{ $sId }}' ? 'text-emerald-800' : 'text-slate-600'">
                                <span class="flex items-center gap-1.5">
                                    <span class="flex h-2 w-2 rounded-full" :class="selectedStudentId === '{{ $sId }}' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    <span x-text="selectedStudentId === '{{ $sId }}' ? 'Details Active' : 'View Details & SOA'"></span>
                                </span>
                                <span class="text-sm font-black" x-text="selectedStudentId === '{{ $sId }}' ? '⌄' : '›'"></span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- EXPANDED STUDENT DETAILS STUDIO (OPENS BELOW THE CARDS) --}}
            @foreach ($family->enrollmentApplicants as $applicant)
                @if ($applicant->student?->account)
                    @php
                        $sName = $applicant->full_name ?? ($applicant->student?->full_name ?? 'Student');
                        $sGrade = $applicant->grade_level ?? ($applicant->student?->grade_level ?? '');
                        $sId = $applicant->amis_student_id ?: ($applicant->student?->student_number ?: "STU-{$applicant->id}");
                        $sBalance = (float) ($applicant->student->account->remaining_balance ?? 0);
                        $sPaid = (float) ($applicant->student->account->paid_to_date ?? 0);
                        $studentSchedule = $applicant->student->account->monthly_schedule ?? collect();
                        $studentSoaList = $manualSoas->get($sId) ?? collect();
                        $latestManualSoa = $studentSoaList->firstWhere('is_current', true) ?? $studentSoaList->first();
                        $studentData = [
                            'id' => $sId,
                            'name' => $sName,
                            'grade' => $sGrade,
                            'email' => $family->email,
                            'school_year' => $applicant->student->account->school_year ?? '2026-2027',
                        ];
                    @endphp
                    <div
                        x-show="selectedStudentId === '{{ $sId }}'"
                        x-cloak
                        class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition"
                    >
                        {{-- STUDIO HEADER --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-slate-700">Student Account</span>
                                    <h3 class="text-xl font-black tracking-tight text-slate-900">{{ mb_strtoupper($sName) }}</h3>
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $sGrade }}</span>
                                    <span class="font-mono text-xs text-slate-400">{{ $sId }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    School Year: <strong>{{ $applicant->student->account->school_year ?? '2026-2027' }}</strong> · Status: <strong class="text-emerald-700">ACTIVE</strong>
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Remaining Balance</span>
                                    <span class="text-2xl font-black text-slate-900">₱{{ number_format($sBalance, 2) }}</span>
                                </div>
                                <button type="button" @click="selectedStudentId = null" class="rounded-xl border border-slate-200 bg-slate-50 p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition" title="Close Details">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- STUDIO 2-COLUMN GRID --}}
                        <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            {{-- LEFT COLUMN (7 COLS): MONTHLY PAYMENT SCHEDULE TABLE --}}
                            <div class="lg:col-span-7 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                        <svg class="h-4 w-4 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Monthly Payment Schedule (9 Months)
                                    </h4>
                                    <span class="text-xs font-bold text-slate-400">FIFO Month Order</span>
                                </div>

                                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-2xs">
                                    <table class="min-w-full text-left text-sm border-collapse">
                                        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-500 border-b border-slate-200">
                                            <tr>
                                                <th class="px-4 py-3">Billing Month</th>
                                                <th class="px-4 py-3 text-right">Monthly Fee</th>
                                                <th class="px-4 py-3 text-right">Paid</th>
                                                <th class="px-4 py-3 text-right">Balance</th>
                                                <th class="px-4 py-3 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($studentSchedule as $m)
                                                @php
                                                    $stBadge = match($m->status) {
                                                        'PAID' => 'bg-emerald-100 text-emerald-700',
                                                        'OVERDUE' => 'bg-rose-100 text-rose-700',
                                                        'CURRENT' => 'bg-amber-100 text-amber-800',
                                                        default => 'bg-slate-100 text-slate-600'
                                                    };
                                                @endphp
                                                <tr class="hover:bg-slate-50 transition">
                                                    <td class="px-4 py-3 font-bold text-slate-800">
                                                        {{ $m->month }}
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-medium text-slate-600">
                                                        ₱{{ number_format($m->fee ?? $m->original ?? 0, 2) }}
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-medium text-slate-600">
                                                        ₱{{ number_format($m->paid ?? $m->verified ?? 0, 2) }}
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-extrabold text-slate-900">
                                                        ₱{{ number_format($m->remaining, 2) }}
                                                    </td>
                                                    <td class="px-4 py-3 text-center">
                                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase {{ $stBadge }}">
                                                            {{ $m->status }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="p-8 text-center text-xs text-slate-400">No monthly schedule generated.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- RIGHT COLUMN (5 COLS): STATEMENT OF ACCOUNT MANAGEMENT --}}
                            <div class="lg:col-span-5 space-y-4">
                                {{-- OPTION A: FINANCE-UPLOADED MANUAL SOA --}}
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 shadow-2xs">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="inline-flex rounded-md bg-slate-200 text-slate-700 px-2 py-0.5 text-xs font-bold uppercase tracking-wider">Option 1</span>
                                            <h5 class="mt-1 text-sm font-black text-slate-900">Finance-Uploaded Manual SOA</h5>
                                        </div>
                                        <button type="button" @click="openUpload({{ Js::from($studentData) }})" class="inline-flex items-center gap-1 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 px-3 py-1.5 rounded-xl shadow-2xs transition">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                            {{ $latestManualSoa ? 'Upload Revision' : 'Upload SOA' }}
                                        </button>
                                    </div>

                                    @if ($latestManualSoa)
                                        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex rounded-md bg-emerald-100 text-emerald-800 px-2 py-0.5 text-xs font-bold uppercase">{{ $latestManualSoa->billing_month }}</span>
                                                    <span class="text-xs font-bold text-slate-700">v{{ $latestManualSoa->version }} (Current)</span>
                                                </div>
                                                <span class="text-xs text-slate-400">{{ $latestManualSoa->created_at->format('M d, Y') }}</span>
                                            </div>

                                            <div class="mt-3 flex items-center gap-2 text-xs text-slate-700">
                                                <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                <span class="truncate font-mono font-medium text-xs">{{ $latestManualSoa->original_filename }}</span>
                                                <span class="text-xs text-slate-400 whitespace-nowrap">({{ $latestManualSoa->formatted_file_size }})</span>
                                            </div>

                                            @if ($latestManualSoa->remarks)
                                                <p class="mt-2.5 text-xs text-slate-500 italic bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                                    "{{ $latestManualSoa->remarks }}"
                                                </p>
                                            @endif

                                            <div class="mt-3.5 flex items-center gap-2">
                                                <button type="button" @click="openPreview('{{ route('admin.finance.manual-soa.view', $latestManualSoa) }}', '{{ $latestManualSoa->student_name }} · {{ $latestManualSoa->billing_month }} SOA', {{ $latestManualSoa->is_pdf ? 'true' : 'false' }})" class="flex-1 rounded-xl bg-slate-900 px-3 py-2 text-center text-xs font-bold text-white hover:bg-slate-800 shadow-2xs transition">
                                                    View Document
                                                </button>
                                                <a href="{{ route('admin.finance.manual-soa.download', $latestManualSoa) }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-center text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs transition" title="Download Document">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                                </a>
                                                @if ($studentSoaList->count() > 1)
                                                    <button type="button" @click="openHistory({{ Js::from($studentData) }}, {{ Js::from($studentSoaList) }})" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs transition">
                                                        History ({{ $studentSoaList->count() }})
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-5 text-center">
                                            <p class="text-xs font-medium text-slate-500">No manual SOA uploaded yet for this student.</p>
                                            <button type="button" @click="openUpload({{ Js::from($studentData) }})" class="mt-3 inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 shadow-2xs transition">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                                Upload SOA (PDF/Image)
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                {{-- OPTION 2: OFFICIAL SCHOOL STATEMENT OF ACCOUNT (TEMPLATE & PRINT) --}}
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5 shadow-2xs">
                                    <div class="mb-2">
                                        <span class="inline-flex rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 text-xs font-bold uppercase tracking-wider">Option 2</span>
                                        <h5 class="mt-1 text-sm font-black text-slate-900">Official School Statement of Account</h5>
                                    </div>
                                    <p class="text-xs text-slate-500 mb-3.5">
                                        Official AL MUNAWWARA ISLAMIC SCHOOL format with Arabic branding, DepED recognition, Quranic ayah, and complete fee assessment ledger.
                                    </p>
                                    <a
                                        href="{{ route('admin.finance.students.official-soa', ['studentIdentifier' => $sId]) }}"
                                        target="_blank"
                                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-800 shadow-sm transition"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Open Official School SOA (Print / Save PDF)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </section>

        {{-- SECTION 2: LOWER SPLIT GRID (OUTSTANDING BILLING & PAYMENT HISTORY) --}}
        <div class="grid gap-6 lg:grid-cols-2 items-start">
            {{-- OUTSTANDING BILLING SCHEDULE --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="font-extrabold text-slate-900 text-sm tracking-tight">Outstanding Billing Schedule</h2>
                        <p class="text-[11px] text-slate-500">Allocated in FIFO sequence (oldest unpaid month first).</p>
                    </div>
                    <a href="{{ route('admin.finance.onsite.create', ['family' => $family->id]) }}" class="rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition">
                        + Pay Dues
                    </a>
                </div>

                <div class="mt-2 divide-y divide-slate-100 max-h-[280px] overflow-y-auto pr-1">
                    @forelse ($outstanding as $row)
                        @php
                            $studentName = $row['student']?->full_name
                                ?? (isset($row['student']->applicant) ? $row['student']->applicant?->full_name : null)
                                ?? 'Student';
                            $monthLabel = isset($row['billing']->month_name)
                                ? $row['billing']->month_name
                                : (isset($row['billing']->due_date) ? $row['billing']->due_date->format('F Y') : 'Current Dues');
                            $dueDateLabel = isset($row['billing']->due_date) ? $row['billing']->due_date->format('M d, Y') : 'N/A';
                            $isOverdue = isset($row['billing']->due_date) && $row['billing']->due_date->isPast();
                        @endphp
                        <div class="flex items-center justify-between gap-3 py-2 hover:bg-slate-50 px-2 rounded-xl transition text-xs">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <strong class="font-bold text-slate-900 text-xs">{{ $monthLabel }}</strong>
                                    <span class="text-slate-400">·</span>
                                    <span class="font-medium text-slate-700 truncate">{{ $studentName }}</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Orig: ₱{{ number_format($row['original'] ?? $row['original_amount'] ?? 0, 2) }} · Paid: ₱{{ number_format($row['verified'] ?? $row['verified_paid'] ?? 0, 2) }} · Due {{ $dueDateLabel }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-black text-slate-900 text-xs">₱{{ number_format($row['remaining'], 2) }}</p>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $isOverdue ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $isOverdue ? 'OVERDUE' : 'OUTSTANDING' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs font-semibold text-emerald-700">All current family billings are fully paid.</div>
                    @endforelse
                </div>
            </section>

            {{-- PAYMENT HISTORY & OFFICIAL RECEIPTS --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="font-extrabold text-slate-900 text-sm tracking-tight">Payment &amp; Official Receipts</h2>
                    <p class="text-[11px] text-slate-500">Verified transaction receipts and allocations.</p>
                </div>
                <div class="mt-2 divide-y divide-slate-100 max-h-[280px] overflow-y-auto pr-1">
                    @forelse ($transactions as $transaction)
                        <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="flex items-center justify-between gap-3 py-2 hover:bg-slate-50 px-2 rounded-xl transition text-xs">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-bold text-xs text-slate-900">
                                        OR# {{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number ?? "TX-{$transaction->id}" }}
                                    </span>
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 uppercase">{{ $transaction->payment_method ?? 'ONLINE' }}</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ isset($transaction->transaction_at) ? $transaction->transaction_at->format('M d, Y · h:i A') : $transaction->created_at->format('M d, Y · h:i A') }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-black text-slate-900 text-xs">₱{{ number_format((float)$transaction->amount, 2) }}</p>
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 uppercase">
                                    {{ $transaction->status ?? 'VERIFIED' }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-500">No posted Finance transactions yet.</div>
                    @endforelse
                </div>
                @if (method_exists($transactions, 'links'))
                    <div class="border-t border-slate-100 pt-2 mt-2">{{ $transactions->links() }}</div>
                @endif
            </section>
        </div>

        {{-- UPLOAD MANUAL SOA MODAL --}}
        <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="showUploadModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl transition" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-white">Manual Statement of Account</span>
                        <h3 class="mt-1 text-lg font-black text-slate-900">Upload Student SOA</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Student: <strong class="text-slate-800" x-text="activeStudent?.name"></strong> (<span x-text="activeStudent?.grade"></span>)</p>
                    </div>
                    <button type="button" @click="showUploadModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('/finance/students') }}/' + activeStudent?.id + '/manual-soa'" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="student_name" :value="activeStudent?.name">
                    <input type="hidden" name="family_email" :value="activeStudent?.email">
                    <input type="hidden" name="grade_level" :value="activeStudent?.grade">
                    <input type="hidden" name="school_year" :value="activeStudent?.school_year || '2026-2027'">

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Billing Month <span class="text-rose-600">*</span></label>
                        <select name="billing_month" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                            <option value="AUGUST 2026">AUGUST 2026</option>
                            <option value="JULY 2026">JULY 2026</option>
                            <option value="SEPTEMBER 2026">SEPTEMBER 2026</option>
                            <option value="OCTOBER 2026">OCTOBER 2026</option>
                            <option value="NOVEMBER 2026">NOVEMBER 2026</option>
                            <option value="DECEMBER 2026">DECEMBER 2026</option>
                            <option value="JANUARY 2027">JANUARY 2027</option>
                            <option value="FEBRUARY 2027">FEBRUARY 2027</option>
                            <option value="MARCH 2027">MARCH 2027</option>
                            <option value="APRIL 2027">APRIL 2027</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-400">If an SOA for this month already exists, the new upload will automatically become the latest version.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">SOA Document File <span class="text-rose-600">*</span></label>
                        <input type="file" name="soa_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-slate-800 hover:file:bg-slate-200">
                        <p class="mt-1 text-xs text-slate-400">Accepted formats: PDF, JPG, JPEG, PNG (Up to 15 MB).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Optional Remarks / Notes</label>
                        <textarea name="remarks" rows="2" placeholder="e.g. Official SOA issued after siblings discount adjustment." class="mt-1 block w-full rounded-xl border border-slate-300 p-2.5 text-xs text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                        <button type="button" @click="showUploadModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 shadow-sm transition">Upload Statement of Account</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SOA HISTORY MODAL --}}
        <div x-show="showHistoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="showHistoryModal = false">
            <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl transition" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold uppercase text-slate-800">SOA Audit Trail</span>
                        <h3 class="mt-1 text-lg font-black text-slate-900">SOA History &amp; Versions</h3>
                        <p class="text-xs text-slate-500">Student: <strong class="text-slate-800" x-text="activeStudent?.name"></strong></p>
                    </div>
                    <button type="button" @click="showHistoryModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4 divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                    <template x-for="soa in historyList" :key="soa.id">
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50 transition">
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-sm font-black text-slate-900" x-text="soa.billing_month"></strong>
                                    <span :class="soa.is_current ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'" class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold uppercase" x-text="soa.is_current ? 'Version ' + soa.version + ' (Current)' : 'Version ' + soa.version"></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500 font-mono">
                                    Uploaded <span x-text="new Date(soa.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})"></span> by <span x-text="soa.uploaded_by"></span> · <span x-text="soa.original_filename"></span>
                                </p>
                                <p x-show="soa.remarks" class="mt-1 text-xs text-slate-500 italic" x-text="'Remarks: ' + soa.remarks"></p>
                            </div>
                            <div class="flex items-center gap-1.5 sm:justify-end">
                                <button type="button" @click="openPreview('{{ url('/finance/manual-soa') }}/' + soa.id + '/view', soa.student_name + ' · ' + soa.billing_month + ' v' + soa.version, soa.mime_type?.includes('pdf') || soa.original_filename?.endsWith('.pdf'))" class="rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-800 shadow-sm transition">
                                    View
                                </button>
                                <a :href="'{{ url('/finance/manual-soa') }}/' + soa.id + '/download'" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                                    Download
                                </a>
                                <form :action="'{{ url('/finance/manual-soa') }}/' + soa.id" method="POST" onsubmit="return confirm('Delete this SOA document record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- DOCUMENT PREVIEW MODAL --}}
        <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/75 p-4" @click.self="showPreviewModal = false">
            <div class="w-full max-w-5xl h-[90vh] flex flex-col rounded-2xl bg-white shadow-2xl overflow-hidden transition" role="dialog" aria-modal="true">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3.5">
                    <h3 class="text-sm font-black text-slate-900 truncate" x-text="previewTitle"></h3>
                    <div class="flex items-center gap-2">
                        <a :href="previewUrl" target="_blank" class="rounded-xl bg-white border border-slate-300 px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                            Open in New Tab
                        </a>
                        <button type="button" @click="showPreviewModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 bg-slate-100 overflow-auto flex items-center justify-center p-3">
                    <template x-if="previewIsPdf">
                        <iframe :src="previewUrl" class="w-full h-full rounded-xl border border-slate-300 shadow-sm" frameborder="0"></iframe>
                    </template>
                    <template x-if="!previewIsPdf">
                        <img :src="previewUrl" alt="SOA Preview" class="max-w-full max-h-full object-contain rounded-xl shadow-md">
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
