@php
    $familyStudents = collect();
    foreach ($family->enrollmentApplicants ?? [] as $app) {
        $st = $app->student;
        $name = $app->full_name ?: ($st?->full_name ?: 'Student');
        $sId = $st?->id ?: $app->id;
        $sNumber = $st?->student_number ?: ($app->amis_student_id ?: "STU-{$app->id}");
        $grade = $app->grade_level ?: ($st?->grade_level ?: 'Grade 1');
        $billings = $st?->account?->monthlyBillings ?? collect();
        $familyStudents->push([
            'id' => $sId,
            'student_id' => $st?->id ?: $sId,
            'student_number' => $sNumber,
            'name' => $name,
            'grade' => $grade,
            'school_year' => $st?->account?->school_year ?: '2026-2027',
            'remaining_balance' => (float) ($st?->account?->remaining_balance ?? 0),
            'billings' => $billings->map(fn($b) => [
                'id' => $b->id,
                'month' => $b->month_name ?: ($b->due_date ? $b->due_date->format('F Y') : 'Month'),
                'due' => (float) $b->amount_due,
                'status' => $b->status,
            ])->values()->all(),
        ]);
    }
    if ($familyStudents->isEmpty() && isset($family->students)) {
        foreach ($family->students as $st) {
            $name = $st->full_name ?: 'Student';
            $billings = $st->account?->monthlyBillings ?? collect();
            $familyStudents->push([
                'id' => $st->id,
                'student_id' => $st->id,
                'student_number' => $st->student_number,
                'name' => $name,
                'grade' => $st->grade_level,
                'school_year' => $st->account?->school_year ?: '2026-2027',
                'remaining_balance' => (float) ($st->account?->remaining_balance ?? 0),
                'billings' => $billings->map(fn($b) => [
                    'id' => $b->id,
                    'month' => $b->month_name ?: ($b->due_date ? $b->due_date->format('F Y') : 'Month'),
                    'due' => (float) $b->amount_due,
                    'status' => $b->status,
                ])->values()->all(),
            ]);
        }
    }
    $firstStudent = $familyStudents->first();
@endphp

<x-admin-layout
    title="Family SOA — {{ $family->name }}"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => route('admin.finance.dashboard')],
        ['label' => 'Family Accounts', 'href' => route('admin.finance.families.index')],
        ['label' => $family->name, 'href' => null],
    ]"
>
    <div class="finance-page mx-auto max-w-[1440px] space-y-6" x-data="{
        showUploadModal: false,
        showHistoryModal: false,
        showPreviewModal: false,
        showHistoricalModal: false,
        showEditModal: false,
        showVoidModal: false,
        showAuditModal: false,
        sourceFilter: 'ALL',
        studentsList: {{ Js::from($familyStudents) }},
        selectedStudentId: '{{ $firstStudent['id'] ?? '' }}',
        activeStudent: {{ Js::from($firstStudent) }},
        previewUrl: '',
        previewTitle: '',
        previewIsPdf: false,
        historyList: [],
        auditList: [],
        historicalForm: {
            student_id: '{{ $firstStudent['student_id'] ?? '' }}',
            academic_year: '2026-2027',
            payment_date: '{{ now()->format('Y-m-d') }}',
            amount: '',
            fee_category: 'TUITION',
            target_billing_id: '',
            or_number: '',
            payment_method: 'CASH',
            reference_number: '',
            remarks: ''
        },
        editData: {
            id: null,
            transaction_number: '',
            official_receipt_number: '',
            amount: 0,
            payment_date: '',
            fee_category: 'TUITION',
            payment_method: 'CASH',
            reference_number: '',
            academic_year: '2026-2027',
            remarks: '',
            reason: '',
            created_by_name: '',
            created_at_fmt: '',
            updated_at_fmt: ''
        },
        voidData: {
            id: null,
            transaction_number: '',
            official_receipt_number: '',
            amount: 0,
            reason: ''
        },
        selectStudent(id) {
            this.selectedStudentId = this.selectedStudentId === id ? null : id;
            this.activeStudent = this.studentsList.find(s => s.id == id) || null;
            if (this.activeStudent) {
                this.historicalForm.student_id = this.activeStudent.student_id;
            }
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
        },
        openHistoricalModal(studentId = null) {
            if (studentId) {
                this.historicalForm.student_id = studentId;
            } else if (!this.historicalForm.student_id && this.studentsList.length > 0) {
                this.historicalForm.student_id = this.studentsList[0].student_id;
            }
            this.showHistoricalModal = true;
        },
        openEdit(tx) {
            this.editData = {
                id: tx.id,
                transaction_number: tx.transaction_number || ('TX-' + tx.id),
                official_receipt_number: tx.official_receipt?.official_receipt_number || tx.official_receipt_number || '',
                amount: parseFloat(tx.amount || 0).toFixed(2),
                payment_date: tx.transaction_at ? tx.transaction_at.substring(0, 10) : '{{ now()->format('Y-m-d') }}',
                fee_category: tx.fee_category || 'TUITION',
                payment_method: (tx.payment_method || 'CASH').toUpperCase(),
                reference_number: tx.reference_number || '',
                academic_year: tx.academic_year || '2026-2027',
                remarks: tx.remarks || '',
                reason: '',
                created_by_name: tx.processor?.name || 'Finance Staff',
                created_at_fmt: tx.created_at ? new Date(tx.created_at).toLocaleString() : 'N/A',
                updated_at_fmt: tx.updated_at ? new Date(tx.updated_at).toLocaleString() : 'N/A'
            };
            this.showEditModal = true;
        },
        openVoid(tx) {
            this.voidData = {
                id: tx.id,
                transaction_number: tx.transaction_number || ('TX-' + tx.id),
                official_receipt_number: tx.official_receipt?.official_receipt_number || tx.official_receipt_number || '',
                amount: parseFloat(tx.amount || 0).toFixed(2),
                reason: ''
            };
            this.showVoidModal = true;
        },
        openAuditTrail(tx) {
            fetch('{{ url('/finance/transactions') }}/' + tx.id + '/details')
                .then(r => r.json())
                .then(data => {
                    this.auditList = data.audit_logs || [];
                    this.showAuditModal = true;
                })
                .catch(() => {
                    alert('Could not load audit logs for this transaction.');
                });
        }
    }">
        {{-- TOP BANNER HEADER --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 sm:p-8 text-white shadow-md">
            <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <a href="{{ route('admin.finance.families.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-200 hover:text-white transition mb-2">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        Back to Family Accounts
                    </a>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                            {{ $family->name }}
                        </h1>
                        @if ($family->is_demo ?? false)
                            <span class="inline-flex items-center rounded-full bg-amber-400/20 border border-amber-300/40 px-2.5 py-0.5 text-xs font-black uppercase tracking-wider text-amber-200">DEMO ACCOUNT</span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-emerald-100/90 mt-1 font-light">
                        Official Statement of Account, Historical Payment Encoding &amp; Student Ledger Studio.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                    <button type="button" @click="openHistoricalModal()" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 hover:bg-purple-800 text-white px-4 py-2.5 text-xs font-bold shadow-sm transition border border-purple-500/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        + Add Historical Payment
                    </button>
                    <a href="{{ route('admin.finance.onsite.create', ['family' => $family->id]) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white text-emerald-950 px-4 py-2.5 text-xs font-black shadow-sm hover:bg-emerald-50 transition">
                        <svg class="h-4 w-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v12m6-6H6"/></svg>
                        Record Onsite Payment
                    </a>
                </div>
            </div>
        </div>

        {{-- FLASH ALERTS --}}
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 shadow-sm flex items-start gap-3">
                <svg class="h-5 w-5 shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-xs font-semibold">{{ session('success') }}</div>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-sm flex items-start gap-3">
                <svg class="h-5 w-5 shrink-0 text-rose-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <h4 class="font-bold text-xs">Action Failed:</h4>
                    <ul class="mt-1 list-disc pl-4 text-xs space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- DEMO NOTICE (IF APPLICABLE) --}}
        @if ($family->is_demo ?? false)
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-950 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-200 text-amber-900 font-black text-xs">!</span>
                    <div>
                        <strong class="font-bold text-amber-900">Demo Testing Account:</strong>
                        All dues, payments, and documents shown are isolated demo data for workflow verification.
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.finance.families.reset-demo', ['family' => $family->id]) }}" onsubmit="return confirm('Reset all demo payments for this family?');">
                    @csrf
                    <button type="submit" class="rounded-xl border border-amber-300 bg-white px-3.5 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-100 shadow-sm transition">
                        Reset Demo Data
                    </button>
                </form>
            </div>
        @endif

        {{-- TOP STATS ROW --}}
        <div class="grid gap-5 sm:grid-cols-3">
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
                        <span>Linked Children: <strong class="text-slate-700">{{ $familyStudents->count() }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 1: STUDENT CARDS (CLEAN 3-GRID) --}}
        <section>
            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-lg font-black tracking-tight text-slate-900">Student Accounts &amp; SOA Breakdown</h2>
                    <p class="text-xs text-slate-500">Select any student card to view detailed monthly fee schedule and official statements.</p>
                </div>
                <button type="button" @click="openHistoricalModal()" class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-800 bg-purple-50 hover:bg-purple-100 border border-purple-200 px-3 py-1.5 rounded-xl transition">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Historical Payment for a Child
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                @foreach ($familyStudents as $stData)
                    @php
                        $initials = collect(explode(' ', $stData['name']))->filter()->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('');
                    @endphp
                    <div
                        @click="selectStudent('{{ $stData['id'] }}')"
                        class="cursor-pointer rounded-2xl border bg-white p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between"
                        :class="selectedStudentId === '{{ $stData['id'] }}' ? 'border-emerald-600 ring-2 ring-emerald-500/20 bg-emerald-50/10' : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div>
                            {{-- CARD TOP ROW --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-slate-800 font-bold text-white text-xs shadow-2xs">
                                        {{ $initials ?: 'ST' }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-black text-slate-900 text-sm truncate tracking-tight">{{ mb_strtoupper($stData['name']) }}</h3>
                                        <div class="mt-0.5 flex items-center gap-1.5 flex-wrap">
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $stData['grade'] }}</span>
                                            <span class="font-mono text-xs text-slate-400">{{ $stData['student_number'] }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-slate-400 pt-1">
                                    <svg class="h-5 w-5 transform transition-transform duration-200" :class="selectedStudentId === '{{ $stData['id'] }}' ? 'rotate-180 text-emerald-700' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- FINANCIAL STATS ROW --}}
                            <div class="mt-4 rounded-xl bg-slate-50 border border-slate-100 p-3.5">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">SY {{ $stData['school_year'] }}</span>
                                        <span class="text-xs font-bold text-emerald-700">Account Active</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Remaining Balance</span>
                                        <span class="text-xl font-black tracking-tight text-slate-900">₱{{ number_format($stData['remaining_balance'], 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD ACTION FOOTER --}}
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold" :class="selectedStudentId === '{{ $stData['id'] }}' ? 'text-emerald-800' : 'text-slate-600'">
                            <span class="flex items-center gap-1.5">
                                <span class="flex h-2 w-2 rounded-full" :class="selectedStudentId === '{{ $stData['id'] }}' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                <span x-text="selectedStudentId === '{{ $stData['id'] }}' ? 'Schedule Studio Open' : 'View Schedule &amp; SOA'"></span>
                            </span>
                            <span class="text-sm font-black" x-text="selectedStudentId === '{{ $stData['id'] }}' ? '⌄' : '›'"></span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- EXPANDED STUDENT DETAILS STUDIO --}}
            @foreach ($familyStudents as $stData)
                @php
                    $studentSoaList = $manualSoas->get($stData['student_number']) ?? ($manualSoas->get($stData['id']) ?? collect());
                    $latestManualSoa = $studentSoaList->firstWhere('is_current', true) ?? $studentSoaList->first();
                @endphp
                <div
                    x-show="selectedStudentId === '{{ $stData['id'] }}'"
                    x-cloak
                    class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition"
                >
                    {{-- STUDIO HEADER --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center rounded-md bg-slate-100 border border-slate-200 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-slate-700">Student Account</span>
                                <h3 class="text-xl font-black tracking-tight text-slate-900">{{ mb_strtoupper($stData['name']) }}</h3>
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $stData['grade'] }}</span>
                                <span class="font-mono text-xs text-slate-400">{{ $stData['student_number'] }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                School Year: <strong>{{ $stData['school_year'] }}</strong> · Outstanding Dues: <strong class="text-rose-700">₱{{ number_format($stData['remaining_balance'], 2) }}</strong>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openHistoricalModal('{{ $stData['student_id'] }}')" class="rounded-xl bg-purple-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-purple-800 shadow-sm transition inline-flex items-center gap-1.5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Encode Payment
                            </button>
                        </div>
                    </div>

                    {{-- TWO-COLUMN STUDIO --}}
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
                        {{-- LEFT COLUMN: MONTHLY BILLING SCHEDULE --}}
                        <div class="lg:col-span-7">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Monthly Installment Breakdown</h4>
                            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                        <tr>
                                            <th class="p-3">Month / Due</th>
                                            <th class="p-3 text-right">Fee Due</th>
                                            <th class="p-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($stData['billings'] as $bRow)
                                            <tr class="hover:bg-slate-50/70 transition">
                                                <td class="p-3 font-semibold text-slate-800">{{ $bRow['month'] }}</td>
                                                <td class="p-3 text-right font-black text-slate-900">₱{{ number_format($bRow['due'], 2) }}</td>
                                                <td class="p-3 text-center">
                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $bRow['status'] === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                                        {{ strtoupper($bRow['status']) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="p-4 text-center text-slate-400">No monthly billing records found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN: SOA ACTIONS & OFFICIAL FORM --}}
                        <div class="lg:col-span-5 space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <span class="inline-flex rounded-md bg-emerald-100 text-emerald-800 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">Official Document</span>
                                <h5 class="mt-1 text-sm font-black text-slate-900">Official Generated Statement of Account</h5>
                                <p class="text-xs text-slate-500 mt-1 mb-3">Computed assessment with official school branding, Arabic letterhead, DepED permit, and full payment breakdown.</p>
                                <a
                                    href="{{ route('admin.finance.students.official-soa', ['studentIdentifier' => $stData['student_number']]) }}"
                                    target="_blank"
                                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 shadow-sm transition"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Open Official School SOA (Print / PDF)
                                </a>
                            </div>

                            {{-- MANUAL UPLOAD SOA --}}
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h5 class="text-xs font-black text-slate-900 uppercase tracking-wider">Finance-Uploaded Manual SOA</h5>
                                    <button type="button" @click="openUpload({{ Js::from($stData) }})" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-700 hover:text-slate-900 underline">
                                        Upload File
                                    </button>
                                </div>
                                @if ($latestManualSoa)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs flex items-center justify-between">
                                        <div class="truncate">
                                            <strong class="font-bold text-slate-900">{{ $latestManualSoa->billing_month }}</strong>
                                            <span class="text-slate-400 block truncate">{{ $latestManualSoa->original_filename }}</span>
                                        </div>
                                        <button type="button" @click="openPreview('{{ route('admin.finance.manual-soa.view', $latestManualSoa) }}', '{{ $stData['name'] }} SOA', {{ $latestManualSoa->is_pdf ? 'true' : 'false' }})" class="rounded-lg bg-slate-900 text-white px-2.5 py-1 text-xs font-bold shrink-0">
                                            View
                                        </button>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 italic">No manual SOA uploaded yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        {{-- SECTION 2: UNIFIED SOA & PAYMENT TRANSACTION LEDGER --}}
        <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-black text-emerald-800 uppercase">Unified Ledger</span>
                        <h2 class="text-lg font-black tracking-tight text-slate-900">SOA &amp; Payment History</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Complete record of Online, Onsite, and Historical / Manual payments with real-time recalculation.</p>
                </div>

                {{-- FILTER PILLS & ACTION BUTTON --}}
                <div class="flex items-center gap-2.5 flex-wrap">
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-bold text-slate-600">
                        <button type="button" @click="sourceFilter = 'ALL'" :class="sourceFilter === 'ALL' ? 'bg-white text-slate-900 shadow-xs' : 'hover:text-slate-900'" class="rounded-lg px-3 py-1.5 transition">
                            All ({{ count($transactions) }})
                        </button>
                        <button type="button" @click="sourceFilter = 'ONLINE'" :class="sourceFilter === 'ONLINE' ? 'bg-white text-emerald-700 shadow-xs' : 'hover:text-slate-900'" class="rounded-lg px-3 py-1.5 transition">
                            Online ({{ collect($transactions->items())->where('source', 'ONLINE')->count() }})
                        </button>
                        <button type="button" @click="sourceFilter = 'ONSITE'" :class="sourceFilter === 'ONSITE' ? 'bg-white text-teal-700 shadow-xs' : 'hover:text-slate-900'" class="rounded-lg px-3 py-1.5 transition">
                            Onsite ({{ collect($transactions->items())->where('source', 'ONSITE')->count() }})
                        </button>
                        <button type="button" @click="sourceFilter = 'HISTORICAL'" :class="sourceFilter === 'HISTORICAL' ? 'bg-white text-purple-700 shadow-xs' : 'hover:text-slate-900'" class="rounded-lg px-3 py-1.5 transition">
                            Historical ({{ collect($transactions->items())->whereIn('source', ['HISTORICAL', 'MANUAL'])->count() }})
                        </button>
                    </div>

                    <button type="button" @click="openHistoricalModal()" class="inline-flex items-center gap-1.5 rounded-xl bg-purple-700 px-3.5 py-2 text-xs font-bold text-white hover:bg-purple-800 shadow-sm transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        + Add Historical Payment
                    </button>
                </div>
            </div>

            {{-- TRANSACTIONS TABLE --}}
            <div class="mt-4 overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-[11px] font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3.5">Payment Date</th>
                            <th class="px-4 py-3.5">OR / Transaction #</th>
                            <th class="px-4 py-3.5">Category &amp; Remarks</th>
                            <th class="px-4 py-3.5">Academic Year</th>
                            <th class="px-4 py-3.5">Method</th>
                            <th class="px-4 py-3.5 text-right">Amount Paid</th>
                            <th class="px-4 py-3.5 text-center">Source</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $tx)
                            @php
                                $isHistorical = in_array(strtoupper((string)$tx->source), ['HISTORICAL', 'MANUAL'], true);
                                $isVoided = strtoupper((string)$tx->status) === 'VOIDED';
                                $orNo = $tx->officialReceipt?->official_receipt_number ?: ($tx->official_receipt_number ?: "TX-{$tx->id}");
                                $txDate = $tx->transaction_at ? $tx->transaction_at->format('M d, Y') : $tx->created_at->format('M d, Y');
                            @endphp
                            <tr
                                x-show="sourceFilter === 'ALL' || (sourceFilter === 'ONLINE' && '{{ strtoupper($tx->source) }}' === 'ONLINE') || (sourceFilter === 'ONSITE' && '{{ strtoupper($tx->source) }}' === 'ONSITE') || (sourceFilter === 'HISTORICAL' && {{ $isHistorical ? 'true' : 'false' }})"
                                class="hover:bg-slate-50/70 transition {{ $isVoided ? 'opacity-60 bg-rose-50/20' : '' }}"
                            >
                                {{-- 1. Date --}}
                                <td class="px-4 py-3.5 font-bold text-slate-900 whitespace-nowrap">
                                    {{ $txDate }}
                                    <span class="block text-[10px] font-normal text-slate-400">{{ $tx->created_at->format('h:i A') }}</span>
                                </td>

                                {{-- 2. OR # & TX # --}}
                                <td class="px-4 py-3.5">
                                    <div class="font-mono font-black text-slate-900 text-xs">{{ $orNo }}</div>
                                    <span class="font-mono text-[10px] text-slate-400">{{ $tx->transaction_number ?: "TX-{$tx->id}" }}</span>
                                </td>

                                {{-- 3. Category & Remarks --}}
                                <td class="px-4 py-3.5 max-w-xs">
                                    <span class="inline-flex rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-700">
                                        {{ $tx->fee_category ?: 'TUITION' }}
                                    </span>
                                    <div class="text-[11px] text-slate-500 mt-0.5 truncate" title="{{ $tx->remarks ?: 'No remarks' }}">
                                        {{ $tx->remarks ?: 'Regular Payment' }}
                                    </div>
                                    @if ($tx->correction_reason)
                                        <div class="text-[10px] text-amber-700 italic mt-0.5">Corr: {{ $tx->correction_reason }}</div>
                                    @endif
                                    @if ($tx->reversal_reason)
                                        <div class="text-[10px] text-rose-700 italic mt-0.5">Void: {{ $tx->reversal_reason }}</div>
                                    @endif
                                </td>

                                {{-- 4. Academic Year --}}
                                <td class="px-4 py-3.5 font-semibold text-slate-700 whitespace-nowrap">
                                    {{ $tx->academic_year ?: '2026-2027' }}
                                </td>

                                {{-- 5. Method --}}
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-700">
                                        {{ $tx->payment_method ?: 'CASH' }}
                                    </span>
                                    @if ($tx->reference_number)
                                        <span class="block font-mono text-[10px] text-slate-400 mt-0.5">Ref: {{ $tx->reference_number }}</span>
                                    @endif
                                </td>

                                {{-- 6. Amount --}}
                                <td class="px-4 py-3.5 text-right font-black text-sm {{ $isVoided ? 'line-through text-slate-400' : 'text-slate-900' }} whitespace-nowrap">
                                    ₱{{ number_format((float) $tx->amount, 2) }}
                                </td>

                                {{-- 7. Source --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if ($isHistorical)
                                        <span class="inline-flex rounded-full bg-purple-100 border border-purple-200 px-2.5 py-0.5 text-[10px] font-black uppercase text-purple-800">
                                            Historical
                                        </span>
                                    @elseif (strtoupper((string)$tx->source) === 'ONLINE')
                                        <span class="inline-flex rounded-full bg-emerald-100 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-black uppercase text-emerald-800">
                                            Online
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-teal-100 border border-teal-200 px-2.5 py-0.5 text-[10px] font-black uppercase text-teal-800">
                                            Onsite
                                        </span>
                                    @endif
                                </td>

                                {{-- 8. Status --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    @if ($isVoided)
                                        <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-rose-700">
                                            VOIDED
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-emerald-700">
                                            APPROVED
                                        </span>
                                    @endif
                                </td>

                                {{-- 9. Actions --}}
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.finance.transactions.show', $tx) }}" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition" title="View Transaction / OR">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        @if (! $isVoided)
                                            <button type="button" @click="openEdit({{ Js::from($tx) }})" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-600 hover:bg-slate-100 hover:text-emerald-700 transition" title="Edit SOA Record">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button type="button" @click="openVoid({{ Js::from($tx) }})" class="rounded-lg border border-rose-200 bg-white p-1.5 text-rose-600 hover:bg-rose-50 transition" title="Void Record">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        @endif
                                        <button type="button" @click="openAuditTrail({{ Js::from($tx) }})" class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition" title="Audit Trail">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-slate-400">
                                    No payment transactions recorded for this family yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="mt-4 border-t border-slate-100 pt-3">
                    {{ $transactions->links() }}
                </div>
            @endif
        </section>

        {{-- MODAL 1: ADD HISTORICAL PAYMENT --}}
        <div x-show="showHistoricalModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="showHistoricalModal = false">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex rounded-full bg-purple-100 text-purple-800 px-3 py-0.5 text-xs font-bold uppercase tracking-wider">Historical Encoding</span>
                        <h3 class="mt-1 text-xl font-black text-slate-900">Add Historical / Previous Payment</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Encode legacy receipt for previous payments made prior to the current system.</p>
                    </div>
                    <button type="button" @click="showHistoricalModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- NOTICE BANNER --}}
                <div class="mt-4 rounded-2xl border border-purple-200 bg-purple-50/60 p-4 text-xs text-purple-900 flex items-start gap-3">
                    <svg class="h-5 w-5 text-purple-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <strong class="font-bold">Historical Ledger Notice:</strong>
                        This transaction will be recorded with source <code class="font-bold text-purple-900 bg-purple-200/60 px-1 py-0.5 rounded">HISTORICAL</code> and will <strong>NOT</strong> inflate today's daily collection metrics. Outstanding balances will automatically be updated according to: <em>Charges - Valid Payments - Adjustments = Outstanding Balance</em>.
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.finance.families.historical-payment', $family->id) }}" class="mt-5 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- STUDENT SELECTION --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Target Student <span class="text-rose-600">*</span></label>
                            <select name="student_id" x-model="historicalForm.student_id" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                                <template x-for="st in studentsList" :key="st.id">
                                    <option :value="st.student_id" x-text="st.name + ' (' + st.grade + ' · ' + st.student_number + ')'"></option>
                                </template>
                            </select>
                        </div>

                        {{-- ACADEMIC YEAR --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Academic Year <span class="text-rose-600">*</span></label>
                            <select name="academic_year" x-model="historicalForm.academic_year" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                                <option value="2026-2027">2026-2027 (Current)</option>
                                <option value="2025-2026">2025-2026</option>
                                <option value="2024-2025">2024-2025</option>
                                <option value="2023-2024">2023-2024</option>
                                <option value="2022-2023">2022-2023</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- PAYMENT DATE --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Payment Date (Date on Receipt) <span class="text-rose-600">*</span></label>
                            <input type="date" name="payment_date" x-model="historicalForm.payment_date" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                        </div>

                        {{-- AMOUNT PAID --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Amount Paid (₱) <span class="text-rose-600">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" x-model="historicalForm.amount" required placeholder="0.00" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-black text-slate-900 shadow-sm focus:border-purple-600 focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- FEE CATEGORY --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Payment Type / Fee Category <span class="text-rose-600">*</span></label>
                            <select name="fee_category" x-model="historicalForm.fee_category" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                                <option value="TUITION">Tuition / Monthly Installment</option>
                                <option value="ENROLLMENT_FEE">Enrollment Downpayment</option>
                                <option value="MISCELLANEOUS">Miscellaneous Fee</option>
                                <option value="BOOKS">Books &amp; Materials</option>
                                <option value="PREVIOUS_BALANCE">Previous Balance Settlement</option>
                                <option value="OTHER">Other School Fee</option>
                            </select>
                        </div>

                        {{-- PAYMENT METHOD --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Payment Method <span class="text-rose-600">*</span></label>
                            <select name="payment_method" x-model="historicalForm.payment_method" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                                <option value="CASH">Cash at Counter</option>
                                <option value="GCASH">GCash</option>
                                <option value="BANK_TRANSFER">Bank Deposit / Transfer (BDO / Other)</option>
                                <option value="MAYA">Maya</option>
                                <option value="REMITTANCE">Remittance</option>
                                <option value="OTHER">Other Channel</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- OFFICIAL RECEIPT NUMBER --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Official Receipt (OR) Number</label>
                            <input type="text" name="or_number" x-model="historicalForm.or_number" placeholder="e.g. OR-2025-0812 or 89231" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                        </div>

                        {{-- REFERENCE NUMBER --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Reference / Bank Transaction # (Optional)</label>
                            <input type="text" name="reference_number" x-model="historicalForm.reference_number" placeholder="e.g. 100982390123" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden">
                        </div>
                    </div>

                    {{-- REMARKS --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700">Remarks / Description</label>
                        <textarea name="remarks" x-model="historicalForm.remarks" rows="2" placeholder="e.g. Encoded from old manual receipt book #14 for SY 2025-2026 tuition." class="mt-1 block w-full rounded-xl border border-slate-300 p-2.5 text-xs text-slate-800 shadow-sm focus:border-purple-600 focus:outline-hidden"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                        <button type="button" @click="showHistoricalModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-purple-700 px-5 py-2.5 text-xs font-bold text-white hover:bg-purple-800 shadow-sm transition">
                            Save Historical Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 2: EDIT SOA RECORD --}}
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="showEditModal = false">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex rounded-full bg-slate-900 text-white px-3 py-0.5 text-xs font-bold uppercase tracking-wider">Record Correction</span>
                        <h3 class="mt-1 text-xl font-black text-slate-900">Edit SOA Payment Record</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Transaction: <strong class="font-mono text-slate-800" x-text="editData.transaction_number"></strong></p>
                    </div>
                    <button type="button" @click="showEditModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- AUDIT NOTICE --}}
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-600 space-y-1">
                    <div class="flex justify-between">
                        <span>Originally Encoded by: <strong class="text-slate-800" x-text="editData.created_by_name"></strong></span>
                        <span>Added on: <strong class="text-slate-800" x-text="editData.created_at_fmt"></strong></span>
                    </div>
                    <p class="text-[11px] text-slate-500">Every change creates a permanent audit log entry and triggers automatic ledger balance recalculation.</p>
                </div>

                <form :action="'{{ url('/finance/transactions') }}/' + editData.id + '/update-record'" method="POST" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- PAYMENT DATE --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Payment Date <span class="text-rose-600">*</span></label>
                            <input type="date" name="payment_date" x-model="editData.payment_date" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                        </div>

                        {{-- AMOUNT PAID --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Amount Paid (₱) <span class="text-rose-600">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" x-model="editData.amount" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-black text-slate-900 shadow-sm focus:border-slate-900 focus:outline-hidden">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- OR NUMBER --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Official Receipt Number</label>
                            <input type="text" name="or_number" x-model="editData.official_receipt_number" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                        </div>

                        {{-- PAYMENT METHOD --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Payment Method <span class="text-rose-600">*</span></label>
                            <select name="payment_method" x-model="editData.payment_method" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                                <option value="CASH">Cash</option>
                                <option value="GCASH">GCash</option>
                                <option value="BANK_TRANSFER">Bank Transfer / Deposit</option>
                                <option value="MAYA">Maya</option>
                                <option value="REMITTANCE">Remittance</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- FEE CATEGORY --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Fee Category</label>
                            <select name="fee_category" x-model="editData.fee_category" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                                <option value="TUITION">Tuition / Monthly Dues</option>
                                <option value="ENROLLMENT_FEE">Enrollment Downpayment</option>
                                <option value="MISCELLANEOUS">Miscellaneous Fee</option>
                                <option value="BOOKS">Books Fee</option>
                                <option value="PREVIOUS_BALANCE">Previous Balance</option>
                                <option value="OTHER">Other Fee</option>
                            </select>
                        </div>

                        {{-- ACADEMIC YEAR --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700">Academic Year</label>
                            <select name="academic_year" x-model="editData.academic_year" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-bold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                                <option value="2026-2027">2026-2027</option>
                                <option value="2025-2026">2025-2026</option>
                                <option value="2024-2025">2024-2025</option>
                                <option value="2023-2024">2023-2024</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Remarks / Description</label>
                        <input type="text" name="remarks" x-model="editData.remarks" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-semibold text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden">
                    </div>

                    {{-- MANDATORY REASON FOR CORRECTION --}}
                    <div class="rounded-2xl border border-amber-300 bg-amber-50/50 p-4">
                        <label class="block text-xs font-black text-amber-900 uppercase tracking-wider">
                            Reason for Correction <span class="text-rose-600">* (Mandatory for Audit Trail)</span>
                        </label>
                        <textarea name="reason" x-model="editData.reason" required rows="2" placeholder="e.g. Corrected encoding typo on receipt amount from ₱500 to ₱5000." class="mt-1.5 block w-full rounded-xl border border-amber-300 p-2.5 text-xs text-slate-900 shadow-sm focus:border-slate-900 focus:outline-hidden bg-white"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                        <button type="button" @click="showEditModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-800 shadow-sm transition">
                            Save Corrections &amp; Recalculate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 3: VOID TRANSACTION RECORD --}}
        <div x-show="showVoidModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="showVoidModal = false">
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex rounded-full bg-rose-100 text-rose-800 px-3 py-0.5 text-xs font-bold uppercase tracking-wider">Danger Zone</span>
                        <h3 class="mt-1 text-xl font-black text-slate-900">Void Payment Transaction</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Transaction: <strong class="font-mono text-slate-800" x-text="voidData.transaction_number"></strong> (₱<span x-text="voidData.amount"></span>)</p>
                    </div>
                    <button type="button" @click="showVoidModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900 flex items-start gap-3">
                    <svg class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <strong class="font-bold">Permanent Audit Trail:</strong>
                        Voiding will mark this transaction as <strong>VOIDED</strong>, reverse its payment credit in the SOA ledger, and automatically recalculate outstanding dues. The transaction is retained in the financial audit log for compliance.
                    </div>
                </div>

                <form :action="'{{ url('/finance/transactions') }}/' + voidData.id + '/void-record'" method="POST" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-black text-rose-950 uppercase tracking-wider">
                            Reason for Voiding <span class="text-rose-600">* (Mandatory)</span>
                        </label>
                        <textarea name="reason" x-model="voidData.reason" required rows="3" placeholder="e.g. Duplicate receipt encoded by mistake / Payment bounced / Customer cancelled." class="mt-1.5 block w-full rounded-xl border border-rose-300 p-2.5 text-xs text-slate-900 shadow-sm focus:border-rose-600 focus:outline-hidden"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                        <button type="button" @click="showVoidModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-rose-700 px-5 py-2.5 text-xs font-bold text-white hover:bg-rose-800 shadow-sm transition">
                            Confirm &amp; Void Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- MODAL 4: AUDIT TRAIL MODAL --}}
        <div x-show="showAuditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" @click.self="showAuditModal = false">
            <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-3xl bg-white p-6 sm:p-8 shadow-2xl transition" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-flex rounded-full bg-slate-100 text-slate-800 px-3 py-0.5 text-xs font-bold uppercase">Audit Trail</span>
                        <h3 class="mt-1 text-xl font-black text-slate-900">Transaction History &amp; Logs</h3>
                    </div>
                    <button type="button" @click="showAuditModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="log in auditList" :key="log.id">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-black text-slate-900 uppercase" x-text="log.event"></span>
                                <span class="text-slate-400 text-[11px]" x-text="new Date(log.created_at).toLocaleString()"></span>
                            </div>
                            <p class="mt-1.5 text-slate-700" x-text="'Reason / Notes: ' + (log.reason || 'N/A')"></p>
                            <div x-show="log.changes" class="mt-2 rounded-xl bg-white border border-slate-200 p-2 font-mono text-[10px] text-slate-600 overflow-x-auto">
                                <pre x-text="JSON.stringify(log.changes, null, 2)"></pre>
                            </div>
                        </div>
                    </template>
                    <div x-show="auditList.length === 0" class="p-6 text-center text-slate-400 text-xs">
                        No audit log entries recorded yet for this transaction.
                    </div>
                </div>
            </div>
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

                <form :action="'{{ url('/finance/students') }}/' + (activeStudent?.student_number || activeStudent?.id) + '/manual-soa'" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="student_name" :value="activeStudent?.name">
                    <input type="hidden" name="family_email" value="{{ $family->email }}">
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
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">SOA Document File <span class="text-rose-600">*</span></label>
                        <input type="file" name="soa_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-slate-800 hover:file:bg-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Optional Remarks</label>
                        <textarea name="remarks" rows="2" placeholder="e.g. Official SOA issued after siblings discount adjustment." class="mt-1 block w-full rounded-xl border border-slate-300 p-2.5 text-xs text-slate-800 shadow-sm focus:border-slate-900 focus:outline-hidden"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 border-t border-slate-100 pt-4">
                        <button type="button" @click="showUploadModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800 shadow-sm transition">Upload Statement of Account</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- PREVIEW MODAL --}}
        <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4" @click.self="showPreviewModal = false">
            <div class="w-full max-w-4xl h-[85vh] rounded-3xl bg-white p-6 shadow-2xl flex flex-col transition" role="dialog" aria-modal="true">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-black text-slate-900 text-sm truncate" x-text="previewTitle"></h3>
                    <button type="button" @click="showPreviewModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="mt-3 flex-1 overflow-hidden rounded-2xl bg-slate-100">
                    <template x-if="previewIsPdf">
                        <iframe :src="previewUrl" class="w-full h-full border-0"></iframe>
                    </template>
                    <template x-if="!previewIsPdf">
                        <div class="w-full h-full flex items-center justify-center p-4">
                            <img :src="previewUrl" class="max-h-full max-w-full object-contain rounded-xl shadow-md" alt="Document Preview">
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
