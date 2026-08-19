@php
    use Illuminate\Support\Str;

    $familyNo = $applicant->family_application_id ?: min($applicant->id, isset($siblings) ? $siblings->min('id') ?? $applicant->id : $applicant->id);
    $accentClasses = ['accent-green', 'accent-blue', 'accent-amber', 'accent-violet', 'accent-rose'];
    $accentClass = $accentClasses[$familyNo % 5];

    $name = html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
    $displayName = $name ? Str::upper($name) : 'APPLICANT';
    $breadcrumbName = $displayName;
    $photoUrl = \App\Support\EnrollmentStorage::url($applicant->photo_2x2_url);
    $paymentUrl = \App\Support\EnrollmentStorage::url($payment?->receipt_url);
    $paymentIsPdf = $payment?->receipt_url && strtolower(pathinfo($payment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
    $canReviewPayments = auth()->user()?->canReviewEnrollmentPayments() ?? false;
    $canReviewApplications = auth()->user()?->canReviewEnrollmentApplications() ?? false;
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
    $inboxNeedsResend = $applicant->status === 'approved' && data_get($applicant, 'onboarding_email_status') !== 'sent';
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
    $rawConcern = strtolower(trim((string) ($applicant->medical_has_concern ?? '')));
    $hasMedicalConcern = !empty($rawConcern) && !in_array($rawConcern, ['no', 'none', 'false', '0', 'n/a', 'na'], true);

    if (!$hasMedicalConcern) {
        $hasMedicalConcern = !empty($applicant->allergies)
            || !empty($applicant->current_medications)
            || !empty($applicant->health_conditions)
            || !empty($applicant->medical_history)
            || !empty($applicant->emergency_instructions);
    }
    $studentSections = [
        ['title' => 'Academic Details', 'icon' => 'graduation-cap', 'fields' => [
            ['Student Type', $applicant->student_type], ['Grade Level', $applicant->grade_abbr],
            ['School Year', $applicant->school_year], ['Learning Mode', $applicant->learning_mode],
            ['Timezone', $applicant->timezone], ['LRN', $applicant->lrn],
            ['AMIS Student ID', $applicant->amis_student_id],
        ]],
        ['title' => 'Personal Details', 'icon' => 'id-card', 'fields' => [
            ['First Name', $applicant->first_name],
            ['Middle Name', $applicant->middle_name],
            ['Last Name', $applicant->last_name],
            ['Suffix', $applicant->suffix],
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
        ['title' => 'Parent Contact', 'icon' => 'phone', 'fields' => [['Parent Email', $applicant->parent_email], ['Parent Mobile', $parentMobile], ['Facebook Name / Link', $applicant->facebook], ['WhatsApp Number', $applicant->whatsapp], ['Referral Source', $applicant->referral_source]]],
        ['title' => 'Home Address', 'icon' => 'map-pin', 'fields' => [['Full Home Address', $homeAddress], ['City', $applicant->home_city], ['State / Province', $applicant->home_state_province], ['Postal Code', $applicant->home_postal_code]]],
    ];
    $emergencyAddr = $applicant->emergency_address ?: ($homeAddress ?: $studentAddress);
    $medicalSections = [
        ['title' => 'Emergency Contact', 'icon' => 'shield-alert', 'fields' => [
            ['Contact Person', $emergencyContact], ['Relationship', $applicant->emergency_relationship],
            ['Emergency Address', $emergencyAddr], ['Family Physician', $applicant->family_physician], ['Physician Phone', $applicant->physician_phone],
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
        <div class="mb-5 flex justify-end gap-2">
            @if ($canReviewApplications && $inboxNeedsResend)
                <form method="POST" action="{{ route('admin.applicants.send-welcome', $applicant) }}" onsubmit="return confirm('Resend inbox email and reset temporary password for this student?')">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-100">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        Resend Inbox
                    </button>
                </form>
            @endif
            @if ($canReviewApplications)
                <form method="POST" action="{{ route('admin.applicants.approve-family', $applicant) }}" class="inline-block" @submit="approving = true" onsubmit="return confirm('Approve all enrollees in this family and auto-generate Microsoft 365 accounts, student numbers & SOA?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 cursor-pointer shadow-sm">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                        Approve & Generate Family (Microsoft M365 + SOA)
                    </button>
                </form>
                <a href="{{ route('admin.applicants.review', $applicant) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    Family Review / Full Details
                </a>
            @endif
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
                            <span class="applicant-pill applicant-pill-grade">{{ Str::upper($applicant->grade_abbr ?: 'Grade pending') }}</span>
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
                            Next Sibling: {{ Str::upper(html_entity_decode($nextSibling->full_name ?: 'Applicant', ENT_QUOTES, 'UTF-8')) }}
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

                @unless ($isTeacherAdminViewer)
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
                @endunless

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
                                    <td class="py-3 pr-4 font-bold text-slate-900">{{ Str::upper(html_entity_decode($sibling->full_name, ENT_QUOTES, 'UTF-8')) }}</td>
                                    <td class="py-3 pr-4">{{ $sibling->grade_abbr ?: '-' }}</td>
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


                @unless ($isTeacherAdminViewer)
                    <x-card title="Medical & Emergency" subtitle="Health details submitted by parent">
                        <div class="detail-section-stack">
                            @foreach ($medicalSections as $section)
                                <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                            @endforeach
                        </div>
                    </x-card>
                @endunless

                @unless ($isTeacherAdminViewer)
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
                @endunless

                @unless ($isTeacherAdminViewer)


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
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach ($docMap as $docKey => $doc)
                            <x-applicant.document-card :applicant="$applicant" :doc-key="$docKey" :doc="$doc" :status="$docStatuses[$docKey] ?? 'pending'" />
                        @endforeach
                    </div>
                </x-card>
                @endunless

                @if ($canReviewApplications)
                <x-card title="System Verification" subtitle="Approve or reject per section. Rejections auto-generate remarks.">
                    <div class="space-y-0 divide-y divide-slate-100">
                        @foreach ([
                            ['key' => 'student_info', 'label' => 'Student Information'],
                            ['key' => 'documents', 'label' => 'Document Verification'],
                            ['key' => 'photo_2x2', 'label' => '2x2 Picture'],
                            ['key' => 'report_card_affidavit', 'label' => 'Report Card / Affidavit'],
                        ] as $section)
                        @php
                            $sKey = $section['key'];
                            if ($sKey === 'student_info') {
                                $verified = ($docStatuses['_student_info'] ?? '') === 'approved';
                                $rejected = ($docStatuses['_student_info'] ?? '') === 'rejected';
                            } elseif ($sKey === 'documents') {
                                $verified = $allDocsOk && !$anyDocRejected;
                                $rejected = $anyDocRejected;
                            } elseif ($sKey === 'photo_2x2') {
                                $verified = ($docStatuses['photo_2x2'] ?? '') === 'approved';
                                $rejected = ($docStatuses['photo_2x2'] ?? '') === 'rejected';
                            } elseif ($sKey === 'report_card_affidavit') {
                                $verified = ($docStatuses['report_card'] ?? '') === 'approved' || ($docStatuses['affidavit'] ?? '') === 'approved';
                                $rejected = (($docStatuses['report_card'] ?? '') === 'rejected' || ($docStatuses['affidavit'] ?? '') === 'rejected') && !$verified;
                            }
                        @endphp
                        <div class="flex items-center justify-between px-1 py-3 first:pt-0 last:pb-0">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $section['label'] }}</p>
                                <p class="text-xs mt-0.5">
                                    @if ($verified)<span class="text-emerald-600 font-bold">Verified</span>
                                    @elseif ($rejected)<span class="text-rose-600 font-bold">Rejected</span>
                                    @else<span class="text-amber-600">Pending Verification</span>
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.applicants.verify-section', $applicant) }}">
                                    @csrf
                                    <input type="hidden" name="section" value="{{ $sKey }}">
                                    <input type="hidden" name="action" value="approve">
                                    <button class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold transition {{ $verified ? 'bg-emerald-100 text-emerald-500 cursor-default' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} {{ $verified ? 'pointer-events-none' : '' }}">
                                        <i data-lucide="{{ $verified ? 'check' : 'check' }}" class="h-3.5 w-3.5"></i>
                                        {{ $verified ? 'Approved' : 'Approve' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.applicants.verify-section', $applicant) }}">
                                    @csrf
                                    <input type="hidden" name="section" value="{{ $sKey }}">
                                    <input type="hidden" name="action" value="reject">
                                    <button class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold transition {{ $rejected ? 'bg-rose-100 text-rose-500 cursor-default' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }} {{ $rejected ? 'pointer-events-none' : '' }}">
                                        <i data-lucide="{{ $rejected ? 'x' : 'x' }}" class="h-3.5 w-3.5"></i>
                                        {{ $rejected ? 'Rejected' : 'Reject' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </x-card>
                @endif

                {{-- FAMILY & SIBLINGS TABLE CARD --}}
                @if ((isset($allFamily) && count($allFamily) > 0) || (isset($familyChildren) && count($familyChildren) > 0))
                @php
                    $siblingsList = $allFamily ?? $familyChildren;
                @endphp
                <x-card title="Family & Siblings" subtitle="Other children enrolled under the same parent account">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase text-slate-400 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3 font-extrabold">Name</th>
                                    <th class="px-5 py-3 font-extrabold">Grade</th>
                                    <th class="px-5 py-3 font-extrabold">Status / Completion</th>
                                    <th class="px-5 py-3 font-extrabold text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($siblingsList as $child)
                                @php
                                    $isCurrent = $child->id === $applicant->id;
                                @endphp
                                <tr class="transition hover:bg-slate-50/80 {{ $isCurrent ? 'bg-emerald-50/40' : '' }}">
                                    <td class="px-5 py-3.5 font-black text-slate-950">
                                        {{ Str::upper($child->full_name) }}
                                        @if ($isCurrent)
                                            <span class="ml-2 px-2 py-0.5 text-[9px] font-black uppercase bg-emerald-100 text-emerald-800 rounded-md">Current</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 font-extrabold text-slate-700">{{ $child->grade_level ?: '-' }}</td>
                                    <td class="px-5 py-3.5">
                                        <span class="px-2.5 py-1 text-xs font-extrabold rounded-md border {{ $child->status === 'approved' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-emerald-50/60 border-emerald-100 text-emerald-700' }}">
                                            {{ $statusLabels[$child->status] ?? Str::headline($child->status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        @if (!$isCurrent)
                                            <a href="{{ route('admin.applicants.show', $child) }}" class="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-600 hover:text-emerald-700 hover:underline">
                                                View Sibling &rarr;
                                            </a>
                                        @else
                                            <span class="text-xs font-bold text-slate-400">Viewing</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
                @endif
            </main>

                                    <aside class="review-panel space-y-4">
                {{-- 1. APPLICANT CARD --}}
                <x-card title="Applicant">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Application ID #</span>
                            <span class="text-base font-black text-slate-950">{{ str_pad($applicant->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        @unless ($isTeacherAdminViewer)
                        <div>
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Family</span>
                            <span class="text-sm font-bold text-slate-700">FAMILY #{{ str_pad($familyNo, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        @endunless
                        <div class="col-span-2">
                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Full Name</span>
                            <span class="text-sm font-black text-slate-950">{{ $displayName }}</span>
                        </div>
                    </div>
                </x-card>

                {{-- 2. PAYMENT VERIFICATION GUARD BANNER --}}
                @if (isset($payment) && $payment && $payment->status === 'pending' && ($canReviewPayments ?? true))
                    <div class="p-3.5 rounded-xl border-2 border-amber-300 bg-amber-50 shadow-sm space-y-2.5">
                        <div class="flex items-start gap-2.5">
                            <div class="rounded-lg bg-amber-100 p-1.5 text-amber-700 shrink-0 mt-0.5">
                                <i data-lucide="shield-alert" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-black uppercase tracking-wider text-amber-900">⚠️ PAYMENT VERIFICATION REQUIRED</h4>
                                <p class="text-[10px] font-bold text-amber-800 mt-0.5 leading-snug">
                                    PLS APPROVE PAYMENT VERIFICATION BEFORE YOU APPROVE & GENERATE STUDENT ACCOUNT.
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.payments.verify', $payment) }}" class="w-full">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase rounded-lg shadow-sm transition-colors cursor-pointer" onclick="return confirm('Approve and verify payment proof of ₱{{ number_format((float) $payment->amount, 2) }}?')">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> APPROVE PAYMENT
                            </button>
                        </form>
                    </div>
                @endif

                {{-- 3. PAYMENT DETAILS & PICTURE CARD --}}
                @unless ($isTeacherAdminViewer)
                @php
                    $receiptPath = $applicant->enrollment_fee_receipt_url ?: ($payment?->receipt_url ?: $applicant->proof_of_payment);
                    $receiptAssetUrl = \App\Support\EnrollmentStorage::url($receiptPath);
                    $isReceiptPdf = $receiptPath && strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION)) === 'pdf';
                @endphp
                <x-card title="PAYMENT DETAILS" subtitle="Proof of payment & summary">
                    <div class="space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Amount</span>
                                <span class="text-sm font-black text-slate-900">{{ $payment?->amount ? '₱'.number_format((float) $payment->amount, 2) : '₱4,000.00' }}</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Method</span>
                                <span class="text-xs font-black text-slate-900 uppercase">{{ $payment?->method ?: $payment?->payment_provider ?: 'GCash' }}</span>
                            </div>
                        </div>

                        @if ($payment?->reference_no)
                        <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Ref No:</span>
                            <span class="text-xs font-black text-emerald-700 font-mono">{{ $payment->reference_no }}</span>
                        </div>
                        @endif

                        {{-- Picture Card --}}
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            <span class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Payment Receipt Picture</span>
                            @if ($receiptAssetUrl)
                                <div class="relative w-full h-36 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden group shadow-sm">
                                    @if ($isReceiptPdf)
                                        <div class="w-full h-full flex flex-col items-center justify-center gap-1 cursor-pointer text-rose-500" @click="openPreview('{{ $receiptAssetUrl }}', 'Payment Proof', true)">
                                            <i data-lucide="file-text" class="h-8 w-8"></i>
                                            <span class="text-[10px] font-black uppercase text-slate-600">PDF Receipt</span>
                                        </div>
                                    @else
                                        <img src="{{ $receiptAssetUrl }}" alt="Payment Proof" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer" @click="openPreview('{{ $receiptAssetUrl }}', 'Payment Proof', false)">
                                        <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                            <span class="px-2.5 py-1 bg-white/90 text-slate-900 text-[10px] font-black rounded-md shadow">Click to Expand ↗</span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="w-full h-24 rounded-xl border border-dashed border-slate-200 bg-slate-50 flex flex-col items-center justify-center text-slate-400">
                                    <i data-lucide="image-off" class="h-6 w-6"></i>
                                    <span class="text-[10px] font-bold mt-1">No Receipt Uploaded</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-card>
                @endunless

                {{-- 4. FAMILY & SIBLINGS CARD (AT THE BOTTOM OF SIDEBAR) --}}
                @php
                    $siblingsList = $allFamily ?? $familyChildren ?? collect([]);
                @endphp
                @if (count($siblingsList) > 0)
                <x-card title="FAMILY & SIBLINGS" subtitle="List of children in family">
                    <div class="space-y-2">
                        @foreach ($siblingsList as $child)
                            @php
                                $childInitials = collect(explode(' ', trim($child->full_name)))->filter()->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->join('');
                                $isCurrent = $child->id === $applicant->id;
                            @endphp
                            <a href="{{ route('admin.applicants.show', $child) }}" class="flex items-center justify-between p-2.5 rounded-xl border transition-all shadow-sm {{ $isCurrent ? 'border-emerald-300 bg-emerald-50/60 font-bold' : 'border-slate-200 bg-white hover:bg-emerald-50/40 hover:border-emerald-300' }}">
                                <div class="flex items-center gap-2.5 truncate">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-[10px] font-black text-emerald-800 shrink-0">
                                        {{ $childInitials ?: 'ST' }}
                                    </span>
                                    <div class="truncate">
                                        <p class="text-xs font-black text-slate-900 truncate">{{ Str::upper($child->full_name) }}</p>
                                        <p class="text-[10px] font-bold text-slate-500">{{ $child->grade_level ?: 'Grade Pending' }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md shrink-0 {{ $child->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $child->status }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </x-card>
                @endif
                @if ($canReviewApplications)
                <a href="{{ route('admin.applicants.review', $applicant) }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black uppercase tracking-wider text-emerald-700 transition hover:bg-emerald-100 shadow-3xs">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    Family Review / Final Action
                </a>
                @endif
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
                    <div class="preview-body cursor-grab select-none overflow-auto flex items-center justify-center min-h-[60vh] max-h-[80vh] p-4 bg-slate-900/5 rounded-b-2xl"
                         @mousedown="startPan($event)"
                         @mousemove="movePan($event)"
                         @mouseleave="stopPan()"
                         @touchstart.passive="startPan($event)"
                         @touchmove="movePan($event)">
                        <template x-if="!pdf && photo">
                            <img :src="src" :alt="label" class="preview-photo transition-all duration-150 rounded-lg shadow-md max-h-[75vh] object-contain mx-auto" :style="'transform: scale(' + zoom + ');'">
                        </template>
                        <template x-if="!pdf && !photo">
                            <img :src="src" :alt="label" class="transition-all duration-150 rounded-lg shadow-md max-h-[75vh] max-w-full object-contain mx-auto" :style="'transform: scale(' + zoom + '); transform-origin: center center;'">
                        </template>
                        <template x-if="pdf"><iframe :src="src" class="w-full h-[75vh] rounded-lg border-0"></iframe></template>
                    </div>
                </div>
            </div>
        </template>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</x-admin-layout>
