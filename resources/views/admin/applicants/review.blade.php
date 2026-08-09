@php
    use Illuminate\Support\Str;

    $familyNo = $applicant->family_application_id ?: min($applicant->id, isset($siblings) ? $siblings->min('id') ?? $applicant->id : $applicant->id);
    $accentClasses = ['accent-green', 'accent-blue', 'accent-amber', 'accent-violet', 'accent-rose'];
    $accentClass = $accentClasses[$familyNo % 5];
    $canReviewApplications = auth()->user()?->canReviewEnrollmentApplications() ?? false;
    $canReviewPayments = auth()->user()?->canReviewEnrollmentPayments() ?? false;
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
    $currentStatus = $applicant->status ?? 'under_review';
    $docStatuses = $docStatuses ?? [];
    $inboxNeedsResend = fn ($child) => $child->status === 'approved' && data_get($child, 'onboarding_email_status') !== 'sent';

    $allFamily = $familyChildren ?? collect([$applicant]);
    $childrenCount = $allFamily->count();
    $approvedCount = $allFamily->where('status', 'approved')->count();
    $familyApprovalCount = $allFamily->filter(fn ($child) => !in_array($child->status, ['approved', 'draft'], true))->count();
    $familyLastName = trim($applicant->mother_last_name ?: ($applicant->father_last_name ?: $applicant->last_name ?: 'Family'));

    $paymentUrl = \App\Support\EnrollmentStorage::url($payment?->receipt_url);
    $paymentIsPdf = $payment?->receipt_url && strtolower(pathinfo($payment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';

    $badgeColor = fn ($s) => Str::after($statusBadges[$s] ?? 'badge-gray', 'badge-');
    $typeLabel = fn ($type) => match (Str::of((string) $type)->lower()->replace(['_', '-'], ' ')->squish()->toString()) {
        'old', 'old student', 'returning', 'returnee', 'existing' => 'OLD STUDENT',
        'transferee', 'transfer', 'transferee student' => 'TRANSFEREE STUDENT',
        'new', 'new student' => 'NEW STUDENT',
        default => 'NOT SET',
    };
    $typeClass = fn ($label) => match ($label) {
        'OLD STUDENT' => 'bg-green-100 text-green-800',
        'TRANSFEREE STUDENT' => 'bg-amber-100 text-amber-800',
        'NEW STUDENT' => 'bg-blue-100 text-blue-800',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

<x-admin-layout title="Family Enrollment Review" :breadcrumbs="[['label' => 'Applications', 'href' => route('admin.applications.enrollment')], ['label' => 'Family Review', 'href' => null]]">
    <div x-data="{
             approving: false,
             preview: false, src: '', label: '', pdf: false, zoom: 1,
             panning: false, panEl: null, panX: 0, panY: 0, panLeft: 0, panTop: 0,
             statusOpen: false,
             statusValue: @js(in_array($currentStatus, \App\Services\Admin\Enrollment\EnrollmentReviewService::MANUAL_REVIEW_STATUSES) ? $currentStatus : ''),
             statusLabel: @js(in_array($currentStatus, \App\Services\Admin\Enrollment\EnrollmentReviewService::MANUAL_REVIEW_STATUSES) ? ($statusLabels[$currentStatus] ?? $currentStatus) : 'Select status...'),
             openPreview(url, title, isPdf) { this.preview = true; this.src = url; this.label = title; this.pdf = isPdf; this.zoom = 1; },
             closePreview() { this.preview = false; this.zoom = 1; this.stopPan(); },
             zoomIn() { this.zoom = Math.min(3, Number((this.zoom + 0.1).toFixed(2))); },
             zoomOut() { this.zoom = Math.max(0.1, Number((this.zoom - 0.1).toFixed(2))); },
             resetZoom() { this.zoom = 1; },
             startPan(event) {
                 if (this.pdf) return;
                 const point = event.touches ? event.touches[0] : event;
                 this.panning = true; this.panEl = event.currentTarget;
                 this.panX = point.pageX; this.panY = point.pageY;
                 this.panLeft = this.panEl.scrollLeft; this.panTop = this.panEl.scrollTop;
                 this.panEl.classList.add('cursor-grabbing');
             },
             movePan(event) {
                 if (!this.panning || !this.panEl) return;
                 event.preventDefault();
                 const point = event.touches ? event.touches[0] : event;
                 this.panEl.scrollLeft = this.panLeft - (point.pageX - this.panX);
                 this.panEl.scrollTop = this.panTop - (point.pageY - this.panY);
             },
             stopPan() { if (this.panEl) this.panEl.classList.remove('cursor-grabbing'); this.panning = false; this.panEl = null; },
             chooseStatus(value, label) { this.statusValue = value; this.statusLabel = label; this.statusOpen = false; },
             showPaymentReject: false,
         }"
         x-effect="document.body.classList.toggle('overflow-hidden', preview)"
         @keydown.escape.window="closePreview(); statusOpen = false"
         @mouseup.window="stopPan()" @touchend.window="stopPan()">

        <div class="mb-5 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-950">Family Enrollment Review</h1>
                <p class="mt-1 text-sm text-slate-500">Family #{{ str_pad($familyNo, 4, '0', STR_PAD_LEFT) }} &mdash; {{ $childrenCount }} {{ Str::plural('child', $childrenCount) }} &middot; {{ $approvedCount }} approved</p>
            </div>
            <a href="{{ route('admin.applications.enrollment') }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-bold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to Registry
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6">

            {{-- FAMILY HEADER --}}
            <section class="applicant-profile-card {{ $accentClass }} rounded-xl p-6">
                    <div class="flex items-center gap-5">
                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-white/20 text-2xl font-black text-white">
                            {{ strtoupper(substr($familyLastName, 0, 2)) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">{{ Str::upper($familyLastName) }} Family</h2>
                            <p class="text-sm text-emerald-50/80">
                                {{ $childrenCount }} {{ Str::plural('child', $childrenCount) }} enrolled
                                &middot; SY {{ $applicant->school_year ?? '-' }}
                            </p>
                        </div>
                    </div>
                </section>

                {{-- CHILDREN LIST --}}
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-black uppercase tracking-wider text-emerald-950 flex items-center gap-2">
                                <i data-lucide="users" class="h-4 w-4 text-emerald-600"></i>
                                # FAMILY OF {{ Str::upper($applicant->last_name) }}
                            </h2>
                            <p class="text-xs font-bold text-slate-500 mt-0.5">List of children enrolled under this family account</p>
                        </div>
                        <span class="text-xs font-bold text-slate-500">{{ $childrenCount }} {{ Str::plural('child', $childrenCount) }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-bold">Name</th>
                                    <th class="px-5 py-3 font-bold">Grade</th>
                                    <th class="px-5 py-3 font-bold">Student Type</th>
                                    <th class="px-5 py-3 font-bold">Status</th>
                                    <th class="px-5 py-3 font-bold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($allFamily as $child)
                                @php
                                    $photoUrl = \App\Support\EnrollmentStorage::url($child->photo_2x2_url);
                                    $initials = collect(explode(' ', trim($child->full_name)))->filter()->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->join('');
                                    $studentType = $typeLabel($child->student_type);
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-[10px] font-extrabold text-emerald-700 ring-1 ring-emerald-100">
                                                {{ $initials ?: 'ST' }}
                                            </span>
                                            <div>
                                                <div class="font-extrabold text-slate-950">{{ Str::upper($child->full_name) }}</div>
                                                <div class="text-[10px] text-slate-400">{{ $child->learning_mode ?: '-' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-slate-700">{{ $child->grade_level ?: '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-md px-2.5 py-1 text-xs font-extrabold {{ $typeClass($studentType) }}">{{ $studentType }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <x-badge :color="$badgeColor($child->status)">{{ $statusLabels[$child->status] ?? Str::headline($child->status) }}</x-badge>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if ($canReviewApplications)
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($inboxNeedsResend($child))
                                                    <form method="POST" action="{{ route('admin.applicants.send-welcome', $child) }}" onsubmit="return confirm('Resend inbox email and reset temporary password for this student?')">
                                                        @csrf
                                                        <button class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-amber-100 bg-amber-50 px-3 text-xs font-bold text-amber-700 transition hover:bg-amber-100" title="Resend inbox email">
                                                            <i data-lucide="send" class="h-3.5 w-3.5"></i> Resend Inbox
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('admin.applicants.show', $child) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-emerald-100 bg-white px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50" title="View details">
                                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i> View
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                @unless ($isTeacherAdminViewer)
                    {{-- PAYMENT --}}
                    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">Payment Proof</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Amount</p>
                                    <p class="text-2xl font-black text-slate-900 mt-1">
                                        {{ $payment?->amount ? '₱'.number_format((float) $payment->amount, 2) : '₱0.00' }}
                                    </p>
                                    @if ($payment?->reference_no)
                                        <p class="text-xs text-slate-500 mt-1">Ref: {{ $payment->reference_no }}</p>
                                    @endif
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</p>
                                    <p class="text-lg font-black mt-1 {{ ($payment?->status ?? '') === 'verified' ? 'text-emerald-600' : (($payment?->status ?? '') === 'rejected' ? 'text-rose-600' : 'text-amber-600') }}">
                                        {{ $pmLabels[$payment?->status] ?? ($payment ? 'Pending' : 'No Payment') }}
                                    </p>
                                </div>
                            </div>

                            @if ($payment && $payment->status === 'pending' && $canReviewPayments)
                                <div class="mt-4 border-t border-slate-100 pt-4 space-y-3">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Review Payment Proof</p>
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="inline-block" onsubmit="return confirm('Verify this payment proof of ₱{{ number_format($payment->amount, 2) }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-xs font-extrabold text-white transition hover:bg-emerald-700 shadow-sm cursor-pointer">
                                                <i data-lucide="check" class="h-3.5 w-3.5"></i> Verify Payment
                                            </button>
                                        </form>
                                        <button type="button" @click="showPaymentReject = !showPaymentReject" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-4 text-xs font-extrabold text-rose-700 transition hover:bg-rose-50 shadow-sm cursor-pointer">
                                            <i data-lucide="x" class="h-3.5 w-3.5"></i> Reject Payment
                                        </button>
                                    </div>
                                    <div x-show="showPaymentReject" x-cloak class="mt-3 p-3 bg-rose-50/50 rounded-xl border border-rose-100">
                                        <form method="POST" action="{{ route('admin.payments.reject', $payment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="block text-xs font-bold text-rose-800 mb-1">Reason for Rejection</label>
                                            <textarea name="remarks" required rows="2" class="w-full rounded-lg border border-rose-200 bg-white p-2 text-xs font-medium text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Please specify why the payment is rejected..."></textarea>
                                            <div class="mt-2 flex justify-end">
                                                <button type="submit" class="inline-flex h-8 items-center justify-center rounded-lg bg-rose-600 px-3 text-xs font-bold text-white transition hover:bg-rose-700 cursor-pointer">
                                                    Submit Rejection
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            @php
                                $receipts = [];
                                if ($payment && filled($payment->receipt_url)) {
                                    $receipts = $payment->receipt_urls;
                                } elseif (filled($applicant->proof_of_payment)) {
                                    $receipts = [$applicant->proof_of_payment];
                                }
                            @endphp
                            @if (count($receipts) > 0)
                                <div class="space-y-4">
                                    @foreach ($receipts as $index => $receiptPath)
                                        @php
                                            $url = \App\Support\EnrollmentStorage::url($receiptPath);
                                            $isPdf = $receiptPath && strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION)) === 'pdf';
                                        @endphp
                                        <div class="max-w-sm mx-auto rounded-xl border border-slate-100 overflow-hidden shadow-sm">
                                            <div class="bg-slate-50 px-4 py-1.5 text-[11px] font-extrabold text-slate-500 border-b border-slate-100 flex items-center justify-between">
                                                <span>RECEIPT #{{ $index + 1 }}</span>
                                                <a href="{{ $url }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 hover:underline">Open in new tab</a>
                                            </div>
                                            <a href="#" @click.prevent="openPreview('{{ $url }}', 'Payment Proof #{{ $index + 1 }}', {{ $isPdf ? 'true' : 'false' }})" class="block h-96 overflow-hidden bg-slate-50 relative rounded-b-xl">
                                                @if ($isPdf)
                                                    <span class="upload-pdf h-full flex flex-col items-center justify-center gap-2 text-slate-400 bg-slate-50">
                                                        <i data-lucide="file-text" class="h-9 w-9 text-rose-500"></i>
                                                        PDF Receipt
                                                    </span>
                                                @else
                                                    <x-smart-preview-image :src="$url" alt="Payment Proof #{{ $index + 1 }}" class="object-contain" />
                                                @endif
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center">
                                    <i data-lucide="receipt-text" class="mx-auto h-8 w-8 text-slate-300"></i>
                                    <p class="mt-2 text-sm text-slate-500">No payment proof uploaded yet.</p>
                                </div>
                            @endif

                            @if ($totalFamilyChildren > 1)
                                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                                    <p class="text-xs font-extrabold uppercase tracking-wider text-amber-800 mb-2">
                                        Family Payment Summary
                                    </p>
                                    <p class="text-sm text-amber-700">
                                        <strong>{{ $totalFamilyChildren }}</strong> children in this family.
                                        Expected: <strong>₱{{ number_format($expectedPayment, 2) }}</strong>
                                        (₱{{ number_format($enrollmentFee, 2) }} × {{ $totalFamilyChildren }})
                                    </p>
                                    @if ($paymentInsufficient)
                                        <div class="mt-3 rounded-lg border border-amber-200 bg-white p-3 text-xs font-bold text-amber-800">
                                            Payment proof amount is ₱{{ number_format((float) $payment->amount, 2) }} only.
                                            Please verify if this payment is intended for:
                                            {{ $allFamily->pluck('full_name')->implode(', ') }}.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </section>
                @endunless

                {{-- REVIEW DECISION --}}
                <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                        <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-700">Review Decision</h2>
                        @php
                            $isPaymentVerified = $payment && $payment->status === 'verified';
                        @endphp
                        @if ($isPaymentVerified)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase rounded-md tracking-wider">
                                <i data-lucide="check-circle-2" class="w-3 h-3 text-emerald-600"></i> Payment Verified
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-800 text-[10px] font-black uppercase rounded-md tracking-wider">
                                <i data-lucide="alert-circle" class="w-3 h-3 text-amber-600"></i> Payment Verification Required
                            </span>
                        @endif
                    </div>
                    
                    @if (!($payment && $payment->status === 'verified'))
                        <div class="mx-6 mt-5 p-4 rounded-xl border-2 border-amber-300 bg-amber-50 shadow-sm flex items-start gap-3">
                            <div class="rounded-lg bg-amber-100 p-2 text-amber-700 shrink-0">
                                <i data-lucide="shield-alert" class="h-6 w-6"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xs font-black uppercase tracking-wider text-amber-900">⚠️ PAYMENT VERIFICATION REQUIRED</h4>
                                <p class="text-xs font-bold text-amber-800 mt-1">
                                    PLS APPROVE PAYMENT VERIFICATION BEFORE YOU APPROVE & GENERATE STUDENT ACCOUNT.
                                </p>
                                @if ($payment && $payment->status === 'pending' && $canReviewPayments)
                                    <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="mt-3">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase rounded-lg shadow-sm transition-colors cursor-pointer" onclick="return confirm('Approve and verify payment proof of ₱{{ number_format((float) $payment->amount, 2) }}?')">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i> APPROVE PAYMENT VERIFICATION NOW
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($canReviewApplications)
                    <div class="border-b border-slate-100 bg-emerald-50/40 px-6 py-5">
                        <form method="POST" action="{{ route('admin.applicants.approve-family', $applicant) }}" @submit="approving = true">
                            @csrf
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-black text-slate-900">Approve & Generate Family</p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">
                                        {{ $approvedCount }}/{{ $childrenCount }} approved
                                        @if ($familyApprovalCount > 0)
                                            &middot; {{ $familyApprovalCount }} {{ Str::plural('child', $familyApprovalCount) }} ready to process
                                        @else
                                            &middot; all children already approved, photo sync available
                                        @endif
                                    </p>
                                </div>
                                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-700">
                                    <i data-lucide="users-round" class="h-4 w-4"></i>
                                    {{ $familyApprovalCount > 0 ? 'Approve & Generate Family' : 'Sync Family Photos' }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('admin.applicants.status', $applicant) }}" class="p-6 space-y-4" @submit="if (statusValue === 'approved') approving = true">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="approval_scope" value="family">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-900">Status</label>
                            <input type="hidden" name="status" :value="statusValue">
                            <div class="review-select-wrap" @click.outside="statusOpen = false">
                                <button type="button" class="review-select-button" :class="{ 'review-select-button-open': statusOpen }" @click="statusOpen = !statusOpen">
                                    <span x-text="statusLabel"></span>
                                    <i data-lucide="chevron-down" class="h-4 w-4"></i>
                                </button>
                                <div x-show="statusOpen" x-transition class="review-select-menu" x-cloak>
                                    @foreach ($statusLabels ?? [] as $value => $label)
                                        @if (!in_array($value, \App\Services\Admin\Enrollment\EnrollmentReviewService::MANUAL_REVIEW_STATUSES))
                                            @continue
                                        @endif
                                        <button type="button" class="review-select-option" :class="{ 'review-select-option-active': statusValue === @js($value) }" @click="chooseStatus(@js($value), @js($label))">
                                            <div class="text-left pr-4 font-bold">{{ $label }}</div>
                                            <i data-lucide="check" class="h-4 w-4 shrink-0 text-emerald-600" x-show="statusValue === @js($value)"></i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-900">Remarks</label>
                            <textarea name="remarks" rows="3" class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm">{{ old('remarks', $applicant->review_remarks) }}</textarea>
                        </div>
                        <button class="review-save-button">
                            <i data-lucide="save" class="h-4 w-4" x-show="statusValue !== 'approved'"></i>
                            <i data-lucide="users-round" class="h-4 w-4" x-show="statusValue === 'approved'"></i>
                            <span x-text="statusValue === 'approved' ? 'Approve & Generate Family' : 'Save Review'">Save Review</span>
                        </button>
                    </form>
                    @endif
                </section>
            </div>

            {{-- APPROVAL LOADING --}}
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm" x-show="approving" x-cloak>
            <div class="flex flex-col items-center gap-4 rounded-2xl bg-white px-10 py-8 shadow-2xl">
                <svg class="h-10 w-10 animate-spin text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-sm font-bold text-slate-700">Processing approval...</p>
                <p class="text-xs text-slate-400">Generating AMIS ID, Microsoft account & SOA</p>
            </div>
        </div>

        {{-- PREVIEW MODAL --}}
        <template x-teleport="body">
            <div x-show="preview" class="preview-modal" x-cloak>
                <button type="button" class="preview-backdrop" @click="closePreview()"></button>
                <div class="preview-panel">
                    <div class="preview-head gap-3">
                        <strong x-text="label"></strong>
                        <div class="ml-auto flex items-center gap-2">
                            <div class="flex items-center gap-2" x-show="!pdf">
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomOut()">-</button>
                                <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(zoom * 100) + '%'"></span>
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomIn()">+</button>
                                <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100" @click="resetZoom()">Reset</button>
                            </div>
                            <button type="button" class="text-2xl leading-none text-slate-500" @click="closePreview()">&times;</button>
                        </div>
                    </div>
                    <div class="preview-body cursor-grab select-none overflow-auto"
                         @mousedown="startPan($event)" @mousemove="movePan($event)" @mouseleave="stopPan()"
                         @touchstart.passive="startPan($event)" @touchmove="movePan($event)">
                        <template x-if="!pdf">
                            <img :src="src" :alt="label" class="transition-all duration-150" :style="'max-width: none; width: ' + (zoom * 100) + '%; height: auto;'">
                        </template>
                        <template x-if="pdf"><iframe :src="src"></iframe></template>
                    </div>
                </div>
            </div>
        </template>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</x-admin-layout>
