@php
    use Illuminate\Support\Str;

    $familyNo = $applicant->family_application_id ?: min($applicant->id, isset($siblings) ? $siblings->min('id') ?? $applicant->id : $applicant->id);
    $accentClasses = ['accent-green', 'accent-blue', 'accent-amber', 'accent-violet', 'accent-rose'];
    $accentClass = $accentClasses[$familyNo % 5];

    $name = trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? ''));
    $displayName = $name ? Str::upper($name) : 'APPLICANT';
    $breadcrumbName = $displayName;
    $photoUrl = \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url);
    $paymentUrl = \App\Support\EnrollmentStorage::url($payment?->receipt_url);
    $paymentIsPdf = $payment?->receipt_url && strtolower(pathinfo($payment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
    $canReviewPayments = auth()->user()?->canReviewEnrollmentPayments() ?? false;
    $canReviewApplications = auth()->user()?->canReviewEnrollmentApplications() ?? false;
    $financeReviewer = (string) config('services.school.finance_reviewer_name', 'Finance Office');
    $paymentReadinessLabel = match (true) {
        !$payment?->receipt_url => "Waiting for Verification ({$financeReviewer})",
        $payment->status === 'verified' && $applicant->status === 'approved' => "Approved by {$financeReviewer}",
        $payment->status === 'verified' => "Verified by {$financeReviewer}",
        $payment->status === 'rejected' => "Rejected by {$financeReviewer}",
        default => "Waiting for Verification ({$financeReviewer})",
    };
    $approvalReadinessLabel = match (true) {
        $applicant->status === 'approved' => 'Enrollment Approved',
        $canApprove => 'Ready for Approval / Under Review',
        default => 'Ready for Approval / Follow-up',
    };
    $currentStatus = $applicant->status ?? 'under_review';
    $currentStatusLabel = $statusLabels[$currentStatus] ?? Str::headline($currentStatus);
    $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? ''));
    $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? ''));
    $emergencyContact = trim(($applicant->emergency_name ?? '').' / '.($applicant->emergency_phone ?? ''), ' /');
    $hasMedicalConcern = (bool) $applicant->medical_has_concern;
    $studentSections = [
        ['title' => 'Academic Details', 'icon' => 'graduation-cap', 'fields' => [
            ['Student Type', $applicant->student_type], ['Grade Level', $applicant->grade_level],
            ['School Year', $applicant->school_year], ['Learning Mode', $applicant->learning_mode],
            ['Timezone', $applicant->timezone], ['LRN', $applicant->lrn],
            ['AMIS Student ID', $applicant->amis_student_id],
        ]],
        ['title' => 'Personal Details', 'icon' => 'id-card', 'fields' => [
            ['Gender', $applicant->gender], ['Date of Birth', optional($applicant->date_of_birth)->format('M d, Y')],
            ['Place of Birth', $applicant->place_of_birth], ['Religion', $applicant->religion],
            ['Ethnicity', $applicant->ethnicity],
        ]],
        ['title' => 'Student Contact', 'icon' => 'mail', 'fields' => [['Email', $applicant->user->email ?? $applicant->email], ['Mobile', $studentMobile]]],
    ];
    $addressSections = [
        ['title' => 'Student Address', 'icon' => 'map', 'fields' => [['Street Address', $applicant->street_address], ['City', $applicant->city], ['State / Province', $applicant->state_province], ['Postal Code', $applicant->postal_code], ['Country', $applicant->country], ['Full Address', $studentAddress]]],
    ];
    $guardianSections = [
        ['title' => "Father's Details", 'icon' => 'user', 'fields' => [["Father's Full Name", $fatherName], ['Occupation', $applicant->father_occupation]]],
        ['title' => "Mother's Details", 'icon' => 'user-round', 'fields' => [["Mother's Full Name", $motherName], ['Occupation', $applicant->mother_occupation]]],
        ['title' => 'Parent Contact', 'icon' => 'phone', 'fields' => [['Parent Email', $applicant->parent_email], ['Parent Mobile', $parentMobile], ['Referral Source', $applicant->referral_source]]],
        ['title' => 'Home Address', 'icon' => 'map-pin', 'fields' => [['Full Home Address', $homeAddress], ['City', $applicant->home_city], ['State / Province', $applicant->home_state_province], ['Postal Code', $applicant->home_postal_code]]],
    ];
    $medicalSections = [
        ['title' => 'Emergency Contact', 'icon' => 'shield-alert', 'fields' => [
            ['Contact Person', $emergencyContact], ['Relationship', $applicant->emergency_relationship],
            ['Family Physician', $applicant->family_physician], ['Physician Phone', $applicant->physician_phone],
        ]],
    ];
    if ($hasMedicalConcern) {
        array_unshift($medicalSections, ['title' => 'Medical Background', 'icon' => 'heart-pulse', 'fields' => [
            ['Medical Concern', 'Yes'], ['Psych Testing', $applicant->psych_testing],
            ['Prescription Medicine', $applicant->prescription_med], ['Allergies', $applicant->allergies],
            ['Current Medications', $applicant->current_medications], ['Health Conditions', $applicant->health_conditions],
            ['Medical History', $applicant->medical_history], ['Emergency Instructions', $applicant->emergency_instructions],
            ['Medication Explanation', $applicant->med_explanation],
        ]]);
    }
    $discountInfo = [
        ['Sibling Order', $applicant->sibling_order], ['Discount Type', $applicant->discount_type],
        ['Discount Percentage', filled($applicant->discount_percentage) ? $applicant->discount_percentage.'%' : null],
        ['Discount Amount', filled($applicant->discount_amount) ? 'PHP '.number_format((float) $applicant->discount_amount, 2) : null],
        ['Last Completed Step', $applicant->last_step], ['Current Status', $statusLabels[$applicant->status] ?? $applicant->status],
    ];
@endphp

<x-admin-layout title="Applicant Detail" :breadcrumbs="[['label' => 'Applications', 'href' => route('admin.applications.enrollment')], ['label' => 'Enrollment', 'href' => route('admin.applications.enrollment')], ['label' => $breadcrumbName, 'href' => null]]">
    <div x-data="{
             preview: false,
             src: '',
             label: '',
             pdf: false,
             photo: false,
             zoom: 1,
             panning: false,
             panEl: null,
             panX: 0,
             panY: 0,
             panLeft: 0,
             panTop: 0,
             statusOpen: false,
             statusValue: @js($currentStatus),
             statusLabel: @js($currentStatusLabel),
             statusDescriptions: {
                 draft: 'Application is still being drafted.',
                 ready_for_submission: 'Application is complete and ready for submission.',
                 submitted: 'Student successfully submitted application.',
                 under_review: 'Admin is already reviewing the enrollment application.',
                 pending: 'Waiting for additional requirements, payment, or clarification.',
                 approved: 'Enrollment application approved.',
                 rejected: 'Enrollment application declined.'
             },
             openPreview(url, title, isPdf, kind = 'document') {
                 this.preview = true;
                 this.src = url;
                 this.label = title;
                 this.pdf = isPdf;
                 this.photo = kind === 'photo';
                 this.zoom = 1;
             },
             closePreview() {
                 this.preview = false;
                 this.photo = false;
                 this.zoom = 1;
                 this.stopPan();
             },
             zoomIn() {
                 this.zoom = Math.min(3, Number((this.zoom + 0.1).toFixed(2)));
             },
             zoomOut() {
                 this.zoom = Math.max(0.1, Number((this.zoom - 0.1).toFixed(2)));
             },
             resetZoom() {
                 this.zoom = 1;
             },
             startPan(event) {
                 if (this.pdf) return;
                 const point = event.touches ? event.touches[0] : event;
                 this.panning = true;
                 this.panEl = event.currentTarget;
                 this.panX = point.pageX;
                 this.panY = point.pageY;
                 this.panLeft = this.panEl.scrollLeft;
                 this.panTop = this.panEl.scrollTop;
                 this.panEl.classList.add('cursor-grabbing');
             },
             movePan(event) {
                 if (!this.panning || !this.panEl) return;
                 event.preventDefault();
                 const point = event.touches ? event.touches[0] : event;
                 this.panEl.scrollLeft = this.panLeft - (point.pageX - this.panX);
                 this.panEl.scrollTop = this.panTop - (point.pageY - this.panY);
             },
             stopPan() {
                 if (this.panEl) this.panEl.classList.remove('cursor-grabbing');
                 this.panning = false;
                 this.panEl = null;
             },
             chooseStatus(value, label) {
                 this.statusValue = value;
                 this.statusLabel = label;
                 this.statusOpen = false;
             },
             async downloadPdf() {
                 if (!this.src) return;
                 const url = this.src;
                 const filename = (this.label || 'document').replace(/[^a-zA-Z0-9]/g, '_') + '.pdf';
                 if (this.pdf) {
                     const link = document.createElement('a');
                     link.href = url;
                     link.download = filename;
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     return;
                 }
                 try {
                     const btn = document.getElementById('download-pdf-btn');
                     const originalText = btn.innerHTML;
                     btn.innerHTML = '<i data-lucide=\'loader-2\' class=\'h-3.5 w-3.5 animate-spin\'></i> Converting...';
                     if (window.lucide) window.lucide.createIcons();
                     const { jsPDF } = window.jspdf;
                     const img = new Image();
                     img.crossOrigin = 'Anonymous';
                     img.src = url;
                     img.onload = () => {
                         const pdf = new jsPDF({
                             orientation: img.width > img.height ? 'landscape' : 'portrait',
                             unit: 'px',
                             format: [img.width, img.height]
                         });
                         pdf.addImage(img, 'JPEG', 0, 0, img.width, img.height);
                         pdf.save(filename);
                         btn.innerHTML = originalText;
                         if (window.lucide) window.lucide.createIcons();
                     };
                     img.onerror = () => {
                         const link = document.createElement('a');
                         link.href = url;
                         link.download = this.label || 'image';
                         document.body.appendChild(link);
                         link.click();
                         document.body.removeChild(link);
                         btn.innerHTML = originalText;
                         if (window.lucide) window.lucide.createIcons();
                     };
                 } catch (e) {
                     console.error(e);
                     window.open(url, '_blank');
                 }
             },
             downloadImage() {
                 if (!this.src) return;
                 const filename = (this.label || 'photo').replace(/[^a-zA-Z0-9]/g, '_');
                 const link = document.createElement('a');
                 link.href = this.src;
                 link.download = filename;
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
             }
         }"
         x-effect="document.body.classList.toggle('overflow-hidden', preview)"
         @keydown.escape.window="closePreview(); statusOpen = false"
         @mouseup.window="stopPan()"
         @touchend.window="stopPan()">
        <div class="mb-5 flex justify-end">
            <a href="{{ route('admin.applications.enrollment') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-4 py-2 text-sm font-bold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to applications
            </a>
        </div>

        <div class="applicant-page">
            <main class="space-y-6">
                <section class="applicant-profile-card relative {{ $accentClass }}">
                    <span class="application-number-pill">Application #{{ str_pad($applicant->id, 4, '0', STR_PAD_LEFT) }}</span>
                    <button type="button" class="applicant-photo" @if ($photoUrl) @click="openPreview('{{ $photoUrl }}', 'Applicant Photo', false, 'photo')" @endif>
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="Applicant Photo" class="w-full h-full object-cover block" loading="eager" decoding="async" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span class="w-full h-full items-center justify-center text-xs font-extrabold" style="display:none">NO PHOTO</span>
                        @else
                            NO PHOTO
                        @endif
                    </button>
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">{{ $displayName }}</h2>
                        <p class="mt-2 text-sm text-emerald-50/90">{{ filled($studentAddress ?? null) ? Str::upper($studentAddress) : 'STUDENT ADDRESS NOT PROVIDED' }}</p>
                        <div class="applicant-pill-row">
                            <span class="applicant-pill applicant-pill-grade">{{ Str::upper($applicant->grade_level ?: 'Grade pending') }}</span>
                            <span class="applicant-pill applicant-pill-type">{{ Str::upper($applicant->student_type ?: 'Student') }}</span>
                            <span class="applicant-pill applicant-pill-mode">{{ Str::upper($applicant->learning_mode ?: 'Learning mode pending') }}</span>
                            <span class="applicant-pill applicant-pill-year">SY {{ $applicant->school_year ?? '-' }}</span>
                        </div>
                    </div>

                    @if(isset($siblings) && $siblings->isNotEmpty())
                        @php
                            $nextSibling = $siblings->where('id', '>', $applicant->id)->first() ?? $siblings->first();
                        @endphp
                        <a href="{{ route('admin.applicants.show', $nextSibling) }}" class="applicant-next-sibling">
                            Next Sibling: {{ Str::upper($nextSibling->full_name ?: 'Applicant') }}
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </a>
                    @endif
                </section>

                <x-card title="Student Information" subtitle="Core enrollment details">
                    <div class="detail-section-stack">
                        @foreach ($studentSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Address & Contact" subtitle="Student residence from enrollment form">
                    <div class="detail-section-stack">
                        @foreach ($addressSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Parent / Guardian" subtitle="Organized parent and home details">
                    <div class="detail-section-stack">
                        @foreach ($guardianSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>

                @if(isset($siblings) && $siblings->isNotEmpty())
                <x-card title="Family & Siblings" subtitle="Other children enrolled under the same parent account">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-slate-200 text-slate-500">
                                <tr>
                                    <th class="py-2 pr-4 font-medium">Name</th>
                                    <th class="py-2 pr-4 font-medium">Grade</th>
                                    <th class="py-2 pr-4 font-medium">Status / Completion</th>
                                    <th class="py-2 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($siblings as $sibling)
                                <tr>
                                    <td class="py-3 pr-4 font-bold text-slate-900">{{ Str::upper($sibling->full_name) }}</td>
                                    <td class="py-3 pr-4">{{ $sibling->grade_level ?: '-' }}</td>
                                    <td class="py-3 pr-4">
                                        @if(in_array($sibling->status, ['draft', 'pending', 'ready_for_submission']))
                                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Missing Details / Incomplete</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">{{ $statusLabels[$sibling->status] ?? ucfirst($sibling->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <a href="{{ route('admin.applicants.show', $sibling) }}" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                            View Sibling <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
                @endif


                <x-card title="Medical & Emergency" subtitle="Health details submitted by parent">
                    <div class="detail-section-stack">
                        @foreach ($medicalSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Enrollment Metadata" subtitle="Discount, progress, and review state">
                    <dl class="detail-grid">
                        @foreach ($discountInfo as [$label, $value])
                            <x-applicant.field :label="$label" :value="$value" />
                        @endforeach
                    </dl>
                </x-card>

                @if (filled($applicant->affidavit_data))
                    <x-card title="Affidavit Details" subtitle="Temporary proof information">
                        <dl class="detail-grid">
                            @foreach ($applicant->affidavit_data as $label => $value)
                                <x-applicant.field :label="Str::headline($label)" :value="is_array($value) ? implode(', ', $value) : $value" />
                            @endforeach
                        </dl>
                    </x-card>
                @endif

                <x-card title="Uploaded Documents" subtitle="Review submitted files and mark each document status">
                    @if ($canReviewApplications)
                        <x-slot:actions>
                            <form method="POST" action="{{ route('admin.applicants.document', $applicant) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="doc_key" value="uploaded_documents">
                                <input type="hidden" name="status" value="approved">
                                <button class="doc-action doc-action-approve">APPROVE</button>
                            </form>
                            <form method="POST" action="{{ route('admin.applicants.document', $applicant) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="doc_key" value="uploaded_documents">
                                <input type="hidden" name="status" value="rejected">
                                <button class="doc-action doc-action-reject">REJECT</button>
                            </form>
                        </x-slot:actions>
                    @endif
                    <div class="upload-grid">
                        @foreach ($docMap as $docKey => $doc)
                            <x-applicant.document-card :applicant="$applicant" :doc-key="$docKey" :doc="$doc" :status="$docStatuses[$docKey] ?? 'pending'" />
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Payment Proof" subtitle="Enrollment fee verification">
                    @if ($paymentUrl)
                        @if ($payment->status === 'pending' && $canReviewPayments)
                            <!-- Actionable Verification Banner directly in Applicant Review -->
                            <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl border border-amber-100 bg-amber-50/50 p-4 text-amber-950 dark:border-amber-900/30 dark:bg-amber-950/20 transition hover:bg-amber-100/50 duration-300">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-xl bg-amber-500 p-2.5 text-white shadow-md shadow-amber-500/20 animate-pulse">
                                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                                    </div>
                                    <div class="text-xs">
                                        <h4 class="font-extrabold uppercase tracking-wider text-amber-800">Verify Payment Proof</h4>
                                        <p class="mt-0.5 font-medium text-amber-700">Review the attached proof. You can verify the payment directly from this page.</p>
                                    </div>
                                </div>
                                <div class="sm:ml-auto flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('admin.payments.verify', $payment) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-600/20 transition-all hover:bg-emerald-700 active:scale-[0.98] cursor-pointer">
                                            <i data-lucide="check" class="h-4 w-4"></i> Verify Payment
                                        </button>
                                    </form>
                                    <button type="button" onclick="document.getElementById('payment-reject-form').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-rose-600/20 transition-all hover:bg-rose-700 active:scale-[0.98] cursor-pointer">
                                        <i data-lucide="x" class="h-4 w-4"></i> Reject
                                    </button>
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-white border border-slate-200 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-slate-50">
                                        Open in Finance
                                    </a>
                                </div>
                            </div>

                            <!-- Reject Form (hidden by default) -->
                            <div id="payment-reject-form" class="mb-6 hidden rounded-2xl border border-rose-100 bg-rose-50/50 p-4">
                                <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-rose-800 mb-1">Rejection Remarks</label>
                                        <textarea name="remarks" rows="2" class="w-full rounded-xl border border-rose-200 bg-white p-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500" placeholder="Provide a reason for rejection (e.g. Incorrect amount, blurry receipt, invalid reference number)" required></textarea>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="document.getElementById('payment-reject-form').classList.add('hidden')" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
                                        <button type="submit" class="rounded-xl bg-rose-600 px-4 py-1.5 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700">Submit Rejection</button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <!-- Show read-only status banner or standard Open in Finance -->
                            <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 text-emerald-950 dark:border-emerald-900/30 dark:bg-emerald-950/20 transition hover:bg-emerald-100/50 duration-300">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-xl bg-emerald-600 p-2.5 text-white shadow-md shadow-emerald-600/20">
                                        <i data-lucide="landmark" class="h-5 w-5"></i>
                                    </div>
                                    <div class="text-xs">
                                        <h4 class="font-extrabold uppercase tracking-wider text-emerald-800">
                                            @if ($payment->status === 'verified')
                                                Payment Verified ✅
                                            @elseif ($payment->status === 'rejected')
                                                Payment Rejected ❌
                                            @else
                                                Finance Payment Proof
                                            @endif
                                        </h4>
                                        <p class="mt-0.5 font-medium text-emerald-700">
                                            @if ($payment->status === 'verified')
                                                This family payment proof has been successfully verified (OR #{{ $payment->or_number ?? '-' }}).
                                            @elseif ($payment->status === 'rejected')
                                                This payment proof was rejected: {{ $payment->remarks }}
                                            @else
                                                Enrollment fee payment proof is submitted and under review.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="sm:ml-auto">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-600/20 transition-all hover:bg-emerald-750 active:scale-[0.98]">
                                        Open in Finance <i data-lucide="arrow-right" class="h-4 w-4"></i>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-3 gap-4">
                            <a href="#" @click.prevent="openPreview('{{ $paymentUrl }}', 'Payment Proof', {{ $paymentIsPdf ? 'true' : 'false' }})" class="upload-preview rounded-xl border border-slate-200 block overflow-hidden transition-all hover:ring-4 hover:ring-emerald-100" title="Click to view payment proof">
                                @if ($paymentIsPdf)
                                    <span class="upload-pdf"><i data-lucide="file-text" class="h-9 w-9"></i>PDF Receipt</span>
                                @else
                                    <x-smart-preview-image :src="$paymentUrl" alt="Payment Proof" />
                                @endif
                            </a>
                            <dl class="detail-grid col-span-2">
                                <x-applicant.field label="Status" :value="$pmLabels[$payment->status] ?? $payment->status" />
                                <x-applicant.field label="Method" :value="$payment->method_label ?? $payment->method" />
                                <x-applicant.field label="Reference No." :value="$payment->reference_no" />
                                <x-applicant.field label="Amount" :value="$payment->amount ? 'PHP '.number_format((float) $payment->amount, 2) : null" />
                                <x-applicant.field label="Submitted At" :value="$payment->paid_at?->format('M d, Y h:i A')" />
                                <x-applicant.field label="Verified At" :value="$payment->verified_at?->format('M d, Y h:i A')" />
                            </dl>
                        </div>
                    @else
                        <div class="empty-state">
                            <i data-lucide="receipt-text" class="h-8 w-8"></i>
                            <p>No payment proof uploaded.</p>
                        </div>
                    @endif
                </x-card>
            </main>

            <aside class="review-panel space-y-4">
                <x-card title="Applicant">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Application ID #</span>
                            <span class="text-base font-black text-slate-950">{{ str_pad($applicant->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Family</span>
                            <span class="text-sm font-bold text-slate-700">FAMILY #{{ str_pad($familyNo, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Full Name</span>
                            <span class="text-sm font-black text-slate-950">{{ $displayName }}</span>
                        </div>
                    </div>
                </x-card>

                @if ($canReviewApplications)
                <x-card title="Review Actions">
                    <form method="POST" action="{{ route('admin.applicants.status', $applicant) }}" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-900">Application Status</label>
                            <input type="hidden" name="status" :value="statusValue">
                            <div class="review-select-wrap" @click.outside="statusOpen = false">
                                <button type="button" class="review-select-button" :class="{ 'review-select-button-open': statusOpen }" @click="statusOpen = !statusOpen">
                                    <span x-text="statusLabel"></span>
                                    <i data-lucide="chevron-down" class="h-4 w-4"></i>
                                </button>
                                <div x-show="statusOpen" x-transition class="review-select-menu" x-cloak>
                                    @foreach ($statusLabels ?? [] as $value => $label)
                                        <button type="button" class="review-select-option" :class="{ 'review-select-option-active': statusValue === @js($value) }" @click="chooseStatus(@js($value), @js($label))">
                                            <div class="text-left pr-4">
                                                <div class="font-bold">{{ $label }}</div>
                                                <div class="text-[10px] text-slate-400 mt-0.5" :class="{ 'text-emerald-700/80': statusValue === @js($value) }" x-text="statusDescriptions[@js($value)]"></div>
                                            </div>
                                            <i data-lucide="check" class="h-4 w-4 shrink-0 text-emerald-600" x-show="statusValue === @js($value)"></i>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-900">Remarks</label>
                            <textarea name="remarks" rows="2" class="w-full rounded-lg border border-slate-300 bg-white p-2.5 text-sm">{{ old('remarks', $applicant->review_remarks) }}</textarea>
                        </div>
                        <button class="review-save-button">
                            <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                            Save Review
                        </button>
                    </form>
                </x-card>
                @endif

                <x-card title="Readiness">
                    <div class="space-y-3 text-sm">
                        <div class="review-readiness-row"><span>Documents</span><span class="readiness-pill {{ $allDocsOk ? 'readiness-emerald' : 'readiness-amber' }}">{{ $allDocsOk ? 'Approved' : 'Pending / Not Prior' }}</span></div>
                        <div class="review-readiness-row"><span>Payment</span><span class="readiness-pill {{ $paymentOk ? 'readiness-emerald' : ($payment?->status === 'rejected' ? 'readiness-rose' : 'readiness-orange') }}">{{ $paymentReadinessLabel }}</span></div>
                        <div class="review-readiness-row"><span>Approval</span><span class="readiness-pill {{ $applicant->status === 'approved' || $canApprove ? 'readiness-emerald' : 'readiness-amber' }}">{{ $approvalReadinessLabel }}</span></div>
                        @if (!$paymentOk)
                            <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-bold leading-5 text-amber-700">
                                @if ($payment?->status === 'rejected')
                                    Payment rejected by {{ $financeReviewer }}. You may still approve; finance follow-up remains separate.
                                @else
                                    Payment is not verified yet. You may still approve; finance can verify or follow up later.
                                @endif
                            </div>
                        @elseif (!$allDocsOk)
                            <div class="rounded-xl border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-bold leading-5 text-amber-700">
                                Documents are not prior. You may approve; missing document remarks will be kept for follow-up while AMIS ID, SOA, and Microsoft account generation continue.
                            </div>
                        @endif
                        @if ($canApprove && $applicant->status !== 'approved')
                            <div class="pt-3.5 border-t border-slate-100 mt-2">
                                <form method="POST" action="{{ route('admin.applicants.status', $applicant) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-700/10 hover:bg-emerald-750 transition active:scale-[0.98] cursor-pointer">
                                        <i data-lucide="check-circle-2" class="h-4.5 w-4.5 text-emerald-100"></i>
                                        Approve & Generate All Three
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </x-card>
            </aside>
        </div>

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
                            <button x-show="!photo" id="download-pdf-btn" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadPdf()">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download PDF
                            </button>
                            <button x-show="photo" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadImage()">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download Image
                            </button>
                            <button type="button" class="text-2xl leading-none text-slate-500" @click="closePreview()">&times;</button>
                        </div>
                    </div>
                    <div class="preview-body cursor-grab select-none overflow-auto"
                         @mousedown="startPan($event)"
                         @mousemove="movePan($event)"
                         @mouseleave="stopPan()"
                         @touchstart.passive="startPan($event)"
                         @touchmove="movePan($event)">
                        <template x-if="!pdf && photo">
                            <img :src="src" :alt="label" class="preview-photo transition-all duration-150" :style="'transform: scale(' + zoom + ');'">
                        </template>
                        <template x-if="!pdf && !photo">
                            <img :src="src" :alt="label" class="transition-all duration-150" :style="'max-width: none; width: ' + (zoom * 100) + '%; height: auto;'">
                        </template>
                        <template x-if="pdf"><iframe :src="src"></iframe></template>
                    </div>

                    @if ($payment && $payment->status === 'pending' && $canReviewPayments)
                        <div x-show="label === 'Payment Proof'" class="bg-amber-50 border-t border-amber-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-text">
                            <div class="text-xs text-amber-900 font-bold uppercase tracking-wider text-left">
                                Verify Payment Proof
                                <p class="text-[11px] text-amber-700 font-semibold normal-case mt-0.5">Please check the receipt amount and reference number before verifying.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.payments.verify', $payment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black uppercase tracking-wider text-white hover:bg-emerald-700 shadow-md cursor-pointer">
                                        <i data-lucide="check" class="h-4 w-4"></i> Verify Payment
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('modal-reject-form-applicant').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700 shadow-md cursor-pointer">
                                    <i data-lucide="x" class="h-4 w-4"></i> Reject
                                </button>
                            </div>
                        </div>
                        
                        <!-- Modal Reject Form -->
                        <div id="modal-reject-form-applicant" x-show="label === 'Payment Proof'" class="hidden bg-rose-50 border-t border-rose-250 p-4 text-left select-text">
                            <form method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-rose-800 mb-1">Rejection Remarks</label>
                                    <textarea name="remarks" rows="2" class="w-full rounded-xl border border-rose-200 bg-white p-2.5 text-sm text-slate-900 focus:outline-none" placeholder="Provide a reason for rejection..." required></textarea>
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('modal-reject-form-applicant').classList.add('hidden')" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">Cancel</button>
                                    <button type="submit" class="rounded-xl bg-rose-600 px-4 py-1.5 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700">Submit Rejection</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </template>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</x-admin-layout>
