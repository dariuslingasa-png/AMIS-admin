<x-admin-layout title="Family SOA — {{ $family->name }}">
    <div class="finance-page mx-auto max-w-[1400px] p-5 lg:p-8" x-data="{
        showUploadModal: false,
        showHistoryModal: false,
        showPreviewModal: false,
        activeStudent: null,
        previewUrl: '',
        previewTitle: '',
        previewIsPdf: false,
        historyList: [],
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
        @include('admin.finance._nav', [
            'title' => $family->name . (($family->is_demo ?? false) ? ' (DEMO DATA)' : ''),
            'subtitle' => 'Consolidated Family Statement of Account. Approved payments always settle the oldest balance first.'
        ])

        @if ($family->is_demo ?? false)
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-xs font-bold text-amber-900 shadow-sm">
                <div>
                    <span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-black uppercase text-amber-900 mr-2">DEMO DATA</span>
                    TEST / DEMO FAMILY ACCOUNT — All billing dues, balances, and allocations shown below are isolated demo data for workflow testing.
                </div>
                <form method="POST" action="{{ route('admin.finance.families.reset-demo', ['family' => $family->id]) }}" onsubmit="return confirm('Reset all demo payments for this family back to initial July 2026 state?');">
                    @csrf
                    <button type="submit" class="rounded-xl border border-amber-400 bg-amber-200 px-3 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-300 whitespace-nowrap transition">
                        Reset Demo Data
                    </button>
                </form>
            </div>
        @endif

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-300">Outstanding balance</p>
                <p class="mt-2 text-2xl font-black">₱{{ number_format($outstanding->sum('remaining'), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-violet-700">Advance credit</p>
                <p class="mt-2 text-2xl font-black text-violet-950">₱{{ number_format($advanceCredit, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Family contact</p>
                <p class="mt-2 font-extrabold text-slate-900">{{ $family->email }}</p>
                <p class="text-xs text-slate-500">Family ID: {{ $family->id }}</p>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-slate-900">Outstanding billing</h2>
                        <p class="text-xs text-slate-500">Displayed in allocation order (oldest first).</p>
                    </div>
                    <a href="{{ route('admin.finance.onsite.create', ['family' => $family->id]) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">
                        Record onsite payment
                    </a>
                </div>

                <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
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
                        <div class="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                            <div>
                                <p class="font-bold text-slate-800">{{ $monthLabel }} · {{ $studentName }}</p>
                                <p class="text-xs text-slate-500">Original ₱{{ number_format($row['original'] ?? $row['original_amount'] ?? 0, 2) }} · Verified ₱{{ number_format($row['verified'] ?? $row['verified_paid'] ?? 0, 2) }} · Due {{ $dueDateLabel }}</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="font-extrabold text-slate-900">₱{{ number_format($row['remaining'], 2) }}</p>
                                <p class="text-xs font-bold {{ $isOverdue ? 'text-rose-600' : 'text-amber-600' }}">
                                    {{ $isOverdue ? 'OVERDUE' : 'OUTSTANDING' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm font-semibold text-emerald-700">All current family billings are fully paid.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-extrabold text-slate-900">Student Accounts &amp; Statement of Account</h2>
                        <p class="text-xs text-slate-500">Manage Finance-Uploaded SOAs and view System-Computed SOAs per student.</p>
                    </div>
                </div>
                <div class="mt-4 space-y-4">
                    @foreach ($family->enrollmentApplicants as $applicant)
                        @if ($applicant->student?->account)
                            @php
                                $sName = $applicant->full_name ?? ($applicant->student?->full_name ?? 'Student');
                                $sGrade = $applicant->grade_level ?? ($applicant->student?->grade_level ?? '');
                                $sId = $applicant->amis_student_id ?: ($applicant->student?->student_number ?: "STU-{$applicant->id}");
                                $sBalance = (float) ($applicant->student->account->remaining_balance ?? 0);
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
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/50 shadow-sm transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 bg-white px-5 py-4">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 font-black text-emerald-800 text-xs">{{ $loop->iteration }}</span>
                                            <h3 class="font-black text-slate-900 text-base">{{ mb_strtoupper($sName) }}</h3>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ $sGrade }} · ID: <span class="font-mono font-bold text-slate-700">{{ $sId }}</span></p>
                                    </div>
                                    <div class="sm:text-right">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Ledger Balance</span>
                                        <span class="text-lg font-black text-slate-900">₱{{ number_format($sBalance, 2) }}</span>
                                    </div>
                                </div>

                                <div class="p-5 space-y-4">
                                    {{-- OPTION A: MANUAL STATEMENT OF ACCOUNT (FINANCE-UPLOADED) --}}
                                    <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-4">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-blue-100 pb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded-md bg-blue-600 px-2 py-0.5 text-[10px] font-black uppercase text-white tracking-wider">OPTION A</span>
                                                <h4 class="font-extrabold text-blue-950 text-xs">MANUAL STATEMENT OF ACCOUNT (FINANCE-UPLOADED)</h4>
                                            </div>
                                            <button type="button" @click="openUpload({{ Js::from($studentData) }})" class="inline-flex items-center gap-1 rounded-lg bg-blue-700 px-2.5 py-1.5 text-xs font-bold text-white hover:bg-blue-800 transition">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                                Upload New SOA
                                            </button>
                                        </div>

                                        @if ($latestManualSoa)
                                            <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-center bg-white rounded-lg p-3.5 border border-blue-100 shadow-2xs">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-black text-emerald-800">{{ $latestManualSoa->billing_month }}</span>
                                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">v{{ $latestManualSoa->version }} (Current)</span>
                                                        <span class="text-xs text-slate-500">· Uploaded {{ $latestManualSoa->created_at->format('M d, Y h:i A') }} by <b>{{ $latestManualSoa->uploaded_by }}</b></span>
                                                    </div>
                                                    <p class="mt-1.5 text-xs text-slate-600 truncate font-mono">
                                                        📄 {{ $latestManualSoa->original_filename }} ({{ $latestManualSoa->formatted_file_size }})
                                                    </p>
                                                    @if ($latestManualSoa->remarks)
                                                        <p class="mt-1 text-xs text-slate-500 italic">"{{ $latestManualSoa->remarks }}"</p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                                    <button type="button" @click="openPreview('{{ route('admin.finance.manual-soa.view', $latestManualSoa) }}', '{{ $latestManualSoa->student_name }} · {{ $latestManualSoa->billing_month }} SOA', {{ $latestManualSoa->is_pdf ? 'true' : 'false' }})" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                                        View SOA
                                                    </button>
                                                    <a href="{{ route('admin.finance.manual-soa.download', $latestManualSoa) }}" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs">
                                                        Download
                                                    </a>
                                                    @if ($studentSoaList->count() > 1)
                                                        <button type="button" @click="openHistory({{ Js::from($studentData) }}, {{ Js::from($studentSoaList) }})" class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-blue-800 hover:bg-blue-100">
                                                            History ({{ $studentSoaList->count() }})
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="mt-3 rounded-lg border border-dashed border-blue-200 bg-white p-4 text-center">
                                                <p class="text-xs text-slate-600">No manual Statement of Account has been uploaded yet for this student.</p>
                                                <p class="mt-1 text-[11px] text-slate-400">Finance staff can upload an externally prepared PDF or JPEG above.</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- OPTION B: SYSTEM-COMPUTED SOA — BETA --}}
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded-md bg-slate-700 px-2 py-0.5 text-[10px] font-black uppercase text-white tracking-wider">OPTION B</span>
                                                <h4 class="font-extrabold text-slate-800 text-xs">SYSTEM-COMPUTED SOA — BETA TEST</h4>
                                            </div>
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">AUTOMATIC ENGINE</span>
                                        </div>
                                        <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-600">
                                            <p class="max-w-md">
                                                Calculated automatically by the AMIS ledger engine (FIFO month settling &amp; child round-robin) for testing and verification.
                                            </p>
                                            <div class="font-bold text-slate-900">
                                                School Year: {{ $applicant->student->account->school_year ?? '2026-2027' }} · Status: <span class="text-emerald-700">{{ $applicant->student->account->status ?? 'ACTIVE' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        </div>

        <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-extrabold text-slate-900">Payment history</h2>
                <p class="text-xs text-slate-500">Online and onsite payments with official receipt numbers.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($transactions as $transaction)
                    <a href="{{ route('admin.finance.transactions.show', $transaction) }}" class="grid gap-2 px-5 py-4 hover:bg-slate-50 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Official Receipt No.</p>
                            <p class="font-bold text-slate-800">{{ $transaction->officialReceipt?->official_receipt_number ?? $transaction->official_receipt_number }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $transaction->payment_method ?? 'CASH' }}</span>
                                <span class="text-xs text-slate-500">{{ isset($transaction->transaction_at) ? $transaction->transaction_at->format('M d, Y g:i A') : '' }}</span>
                            </div>
                        </div>
                        <p class="font-extrabold text-slate-900">₱{{ number_format((float)$transaction->amount, 2) }}</p>
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-center text-xs font-bold text-emerald-700">{{ $transaction->status }}</span>
                    </a>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">No posted Finance transactions.</div>
                @endforelse
            </div>
            @if (method_exists($transactions, 'links'))
                <div class="border-t border-slate-100 px-5 py-4">{{ $transactions->links() }}</div>
            @endif
        </section>

        {{-- UPLOAD MANUAL SOA MODAL --}}
        <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" @click.self="showUploadModal = false">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-blue-800">Option A · Manual Upload</span>
                        <h3 class="mt-1 text-lg font-black text-slate-900">Upload Statement of Account</h3>
                        <p class="text-xs text-slate-500">Student: <strong class="text-slate-800" x-text="activeStudent?.name"></strong> (<span x-text="activeStudent?.grade"></span>)</p>
                    </div>
                    <button type="button" @click="showUploadModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="'{{ url('/finance/students') }}/' + activeStudent?.id + '/manual-soa'" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="student_name" :value="activeStudent?.name">
                    <input type="hidden" name="family_email" :value="activeStudent?.email">
                    <input type="hidden" name="grade_level" :value="activeStudent?.grade">
                    <input type="hidden" name="school_year" :value="activeStudent?.school_year || '2026-2027'">

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Billing Month <span class="text-rose-600">*</span></label>
                        <select name="billing_month" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 shadow-2xs focus:border-blue-600 focus:outline-hidden">
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
                        <p class="mt-1 text-[11px] text-slate-400">If an SOA for this month already exists, the new upload will automatically become the latest version.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">SOA Document File <span class="text-rose-600">*</span></label>
                        <input type="file" name="soa_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:rounded-xl file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-[11px] text-slate-400">Accepted formats: PDF, JPG, JPEG, PNG (Up to 15 MB).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700">Optional Remarks / Notes</label>
                        <textarea name="remarks" rows="2" placeholder="e.g. Official SOA issued after siblings discount adjustment." class="mt-1 block w-full rounded-xl border border-slate-300 p-2.5 text-xs text-slate-800 shadow-2xs focus:border-blue-600 focus:outline-hidden"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" @click="showUploadModal = false" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800 shadow-sm">Upload Statement of Account</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SOA HISTORY MODAL --}}
        <div x-show="showHistoryModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" @click.self="showHistoryModal = false">
            <div class="w-full max-w-2xl max-h-[85vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase text-slate-800">SOA Audit Trail</span>
                        <h3 class="mt-1 text-lg font-black text-slate-900">SOA History &amp; Versions</h3>
                        <p class="text-xs text-slate-500">Student: <strong class="text-slate-800" x-text="activeStudent?.name"></strong></p>
                    </div>
                    <button type="button" @click="showHistoryModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-4 divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                    <template x-for="soa in historyList" :key="soa.id">
                        <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50">
                            <div>
                                <div class="flex items-center gap-2">
                                    <strong class="text-sm text-slate-900" x-text="soa.billing_month"></strong>
                                    <span :class="soa.is_current ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'" class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase" x-text="soa.is_current ? 'Version ' + soa.version + ' (Current)' : 'Version ' + soa.version"></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Uploaded <span x-text="new Date(soa.created_at).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})"></span> by <span x-text="soa.uploaded_by"></span> · <span x-text="soa.original_filename"></span>
                                </p>
                                <p x-show="soa.remarks" class="mt-1 text-xs text-slate-500 italic" x-text="'Remarks: ' + soa.remarks"></p>
                            </div>
                            <div class="flex items-center gap-2 sm:justify-end">
                                <button type="button" @click="openPreview('{{ url('/finance/manual-soa') }}/' + soa.id + '/view', soa.student_name + ' · ' + soa.billing_month + ' v' + soa.version, soa.mime_type?.includes('pdf') || soa.original_filename?.endsWith('.pdf'))" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                    View
                                </button>
                                <a :href="'{{ url('/finance/manual-soa') }}/' + soa.id + '/download'" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                    Download
                                </a>
                                <form :action="'{{ url('/finance/manual-soa') }}/' + soa.id" method="POST" onsubmit="return confirm('Delete this SOA document record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">
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
        <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @click.self="showPreviewModal = false">
            <div class="w-full max-w-4xl h-[90vh] flex flex-col rounded-2xl bg-white shadow-2xl overflow-hidden" role="dialog" aria-modal="true">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3">
                    <h3 class="text-sm font-black text-slate-900 truncate" x-text="previewTitle"></h3>
                    <div class="flex items-center gap-2">
                        <a :href="previewUrl" target="_blank" class="rounded-lg bg-white border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                            Open in New Tab
                        </a>
                        <button type="button" @click="showPreviewModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 bg-slate-100 overflow-auto flex items-center justify-center p-2">
                    <template x-if="previewIsPdf">
                        <iframe :src="previewUrl" class="w-full h-full rounded-lg border border-slate-300 shadow-sm" frameborder="0"></iframe>
                    </template>
                    <template x-if="!previewIsPdf">
                        <img :src="previewUrl" alt="SOA Preview" class="max-w-full max-h-full object-contain rounded-lg shadow-md">
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
