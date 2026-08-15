<div x-show="activeTab === 'documents'" class="space-y-6" x-cloak>
    <!-- Official Finalized Documents Section -->
    @php
        $officialForm = $student->officialEnrollmentForm;
        $isApproved = ($student->applicant?->status === 'approved');
    @endphp
    <x-card title="Official Documents" subtitle="Permanent immutable historical documents and Google Drive archive records">
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
                <div class="flex items-start gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 shrink-0 shadow-xs">
                        <i data-lucide="file-check-2" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h4 class="text-sm font-black text-slate-900">Enrollment Application Form</h4>
                            @if($officialForm)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100/80 px-2.5 py-0.5 text-[10px] font-black text-emerald-800 border border-emerald-200/60">
                                    ✓ Version {{ $officialForm->document_version }} (Finalized)
                                </span>
                            @elseif($isApproved)
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100/80 px-2.5 py-0.5 text-[10px] font-black text-sky-800 border border-sky-200/60">
                                    Ready for Finalization
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100/80 px-2.5 py-0.5 text-[10px] font-black text-amber-800 border border-amber-200/60">
                                    Draft Preview (Pending Approval)
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 font-medium mt-1">
                            @if($officialForm)
                                Generated: <strong class="text-slate-700">{{ $officialForm->generated_at?->format('M d, Y g:i A') ?? $officialForm->created_at->format('M d, Y g:i A') }}</strong>
                                • File size: {{ $officialForm->formatted_file_size }}
                            @else
                                The official permanent PDF is generated automatically upon application approval.
                            @endif
                        </p>

                        <!-- Google Drive Archive Status -->
                        <div class="mt-2.5 flex items-center gap-2 flex-wrap">
                            <span class="text-[11px] font-bold text-slate-400">Archive Status:</span>
                            @if($officialForm && $officialForm->archive_status === 'VERIFIED')
                                <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700 border border-emerald-200">
                                    <i data-lucide="cloud-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    Archived • Google Drive: Verified
                                </span>
                            @elseif($officialForm && in_array($officialForm->archive_status, ['QUEUED', 'PENDING', 'UPLOADING', 'RETRY_PENDING']))
                                <span class="inline-flex items-center gap-1 rounded-lg bg-sky-50 px-2.5 py-0.5 text-[10px] font-extrabold text-sky-700 border border-sky-200">
                                    <i data-lucide="cloud-upload" class="w-3.5 h-3.5 text-sky-600 animate-pulse"></i>
                                    Archive Status: Queued (Next Sync: 12:00 NN / 12:00 MN)
                                </span>
                            @elseif($isApproved)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-slate-50 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-600 border border-slate-200">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-500"></i>
                                    Pending Generation
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-lg bg-slate-50 px-2.5 py-0.5 text-[10px] font-extrabold text-slate-400 border border-slate-200">
                                    Not Queued (Draft Mode)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @if($isApproved)
                        <a href="{{ route('admin.students.official-enrollment-form.view', $student) }}" target="_blank" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3 text-xs font-extrabold text-slate-700 shadow-xs transition cursor-pointer">
                            <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-500"></i>
                            <span>View</span>
                        </a>
                        <a href="{{ route('admin.students.official-enrollment-form.download', $student) }}" class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 text-xs font-extrabold shadow-sm transition cursor-pointer">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                            <span>Download PDF</span>
                        </a>
                    @else
                        <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_blank" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 px-3.5 text-xs font-extrabold text-amber-800 shadow-xs transition cursor-pointer">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                            <span>Preview Draft</span>
                        </a>
                    @endif
                    <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_blank" class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 px-3 text-xs font-extrabold text-slate-700 shadow-xs transition cursor-pointer">
                        <i data-lucide="printer" class="w-3.5 h-3.5 text-slate-500"></i>
                        <span>Print</span>
                    </a>
                </div>
            </div>
        </div>
    </x-card>

    <!-- Mandatory Registration Requirements -->
    <x-card title="Registration Requirements" subtitle="Verify mandatory certificates and documents submitted during enrollment">
        <div class="mb-4 flex items-center justify-between gap-3">
            <span class="text-xs font-bold text-slate-500">Verified & Uploaded Documents:</span>
            <a href="{{ route('admin.students.download-docs-zip', ['search' => $student->student_number]) }}" download class="inline-flex h-9 items-center gap-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white px-3.5 text-xs font-extrabold shadow-sm transition">
                <i data-lucide="archive" class="h-4 w-4"></i>
                <span>Download Documents ZIP (2x2, Birth Cert, Form 138, etc.)</span>
            </a>
        </div>
        @if(isset($isRequirementsComplete) && $isRequirementsComplete)
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 p-3.5 flex items-center justify-between gap-3 text-emerald-900 dark:text-emerald-200">
                <div class="flex items-center gap-2 text-xs font-bold">
                    <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600 dark:text-emerald-400"></i>
                    <span>ALL REGISTRATION DOCUMENTS & LRN REQUIREMENTS VERIFIED AND CLEAR</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-black uppercase tracking-wider">LOCKED & COMPLETE</span>
            </div>
        @elseif(isset($missingRequirements) && !empty($missingRequirements))
            <div class="mb-4 rounded-xl border border-amber-200/80 bg-amber-50/90 p-3.5 space-y-2 text-amber-950">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-900">
                        <i data-lucide="alert-circle" class="h-4 w-4 text-amber-600 animate-pulse"></i>
                        <span>REQUIREMENTS REMINDER: {{ count($missingRequirements) }} Item(s) Pending Clearance</span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[9px] font-black uppercase tracking-wider">PENDING UPLOADS</span>
                </div>
                <div class="flex flex-wrap gap-1.5 pt-1.5 border-t border-amber-200/50">
                    @foreach($missingRequirements as $item)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200/70">
                            • {{ $item }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
        <div class="upload-grid">
            @php
                $docs = [
                    ['label' => '2x2 Photo ID', 'url' => $student->applicant->photo_2x2_url],
                    ['label' => 'Birth Certificate', 'url' => $student->applicant->birth_cert_url],
                    ['label' => 'Report Card / Form 138', 'url' => $student->applicant->report_card_url],
                    ['label' => 'Marriage Contract', 'url' => $student->applicant->marriage_contract_url],
                    ['label' => 'Medical History Records', 'url' => $student->applicant->medical_record_url],
                    ['label' => 'Temporary Proof (Affidavit)', 'url' => $student->applicant->affidavit_url]
                ];
            @endphp

            @foreach($docs as $doc)
                @php
                    $assetUrl = \App\Support\EnrollmentStorage::url($doc['url']);
                    $isPdf = $doc['url'] && strtolower(pathinfo($doc['url'], PATHINFO_EXTENSION)) === 'pdf';
                @endphp
                <article class="upload-card {{ $doc['url'] ? '' : 'upload-card-missing' }}">
                    <button type="button" class="upload-preview" @if ($assetUrl) @click="openPreview('{{ $assetUrl }}', '{{ $doc['label'] }}', {{ $isPdf ? 'true' : 'false' }})" @endif @disabled(!$assetUrl)>
                        @if ($assetUrl && !$isPdf)
                            <x-smart-preview-image :src="$assetUrl" :alt="$doc['label']" />
                        @elseif ($assetUrl && $isPdf)
                            <span class="upload-pdf"><i data-lucide="file-text" class="h-9 w-9"></i>PDF Receipt</span>
                        @else
                            <span class="upload-empty"><i data-lucide="upload-cloud" class="h-8 w-8"></i>No document</span>
                        @endif
                    </button>
                    <div class="upload-body">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-xs font-bold text-slate-950">{{ $doc['label'] }}</h3>
                            <x-badge color="{{ $doc['url'] ? 'green' : 'gray' }}">
                                {{ $doc['url'] ? 'Verified' : 'Missing' }}
                            </x-badge>
                        </div>
                        @if($doc['label'] === '2x2 Photo ID' && (!isset($isTeacherAdminViewer) || !$isTeacherAdminViewer))
                            <button type="button" onclick="openPhotoOptionsModal()" class="mt-2.5 w-full inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs cursor-pointer">
                                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                                <span>Upload / Edit Photo</span>
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </x-card>

    <!-- Divider -->
    <div class="border-t border-slate-200/60 my-6"></div>

    <!-- Enrollment Payment Proof -->
    @php
        $payment = $student->applicant?->payment;
        if (!$payment && $student->applicant) {
            $familyUserIds = \App\Models\EnrollmentApplicant::where('user_id', $student->applicant->user_id)
                ->orWhere(function($q) use ($student) {
                    if ($student->applicant->family_application_id) {
                        $q->where('family_application_id', $student->applicant->family_application_id);
                    } else {
                        $q->where('id', -1);
                    }
                })
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->toArray();

            $payment = \App\Models\Payment::whereIn('user_id', $familyUserIds)
                ->whereNotNull('receipt_url')
                ->whereNotIn('receipt_url', ['', '[]', '[""]'])
                ->first();
        }
    @endphp
    @if($payment)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left: Payment details card -->
            <x-card title="Payment Details" subtitle="Information submitted by the applicant">
                <dl class="space-y-4 text-sm mt-2">
                    <div class="flex justify-between py-3 border-b border-slate-100 dark:border-slate-800">
                        <dt class="font-semibold text-slate-500">Amount Paid</dt>
                        <dd class="font-extrabold text-slate-900 dark:text-white">₱{{ number_format($payment->amount, 2) }}</dd>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-100 dark:border-slate-800">
                        <dt class="font-semibold text-slate-500">Payment Method</dt>
                        <dd class="font-bold text-slate-800 dark:text-slate-200 uppercase">{{ $payment->method_label ?? $payment->method }}</dd>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-100 dark:border-slate-800">
                        <dt class="font-semibold text-slate-500">Reference Number</dt>
                        <dd class="font-mono text-slate-800 dark:text-slate-200 select-all">{{ $payment->reference_no ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-100 dark:border-slate-800">
                        <dt class="font-semibold text-slate-500">Payment Date</dt>
                        <dd class="font-semibold text-slate-700 dark:text-slate-300">
                            {{ $payment->paid_at?->format('M d, Y') ?? ($payment->created_at?->format('M d, Y') ?? '-') }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-3 border-b border-slate-100 dark:border-slate-800">
                        <dt class="font-semibold text-slate-500">Status</dt>
                        <dd>
                            @php
                                $statusColor = match(strtolower($payment->status)) {
                                    'verified', 'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $statusColor }}">
                                {{ ucfirst($payment->status ?: 'Pending') }}
                            </span>
                        </dd>
                    </div>
                    @if($payment->remarks)
                        <div class="py-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <dt class="font-semibold text-slate-500 mb-1">Remarks</dt>
                            <dd class="text-xs bg-slate-50 dark:bg-slate-800 p-3 rounded-lg text-slate-700 dark:text-slate-300 border border-slate-100 dark:border-slate-800 select-text leading-relaxed">
                                {{ $payment->remarks }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>
     
            <!-- Right: Receipts list -->
            <x-card title="Payment Receipt Proofs" subtitle="Click receipt image to zoom or view full size">
                <div class="space-y-4 mt-2">
                    @php
                        $receipts = $payment->receipt_urls ?? [];
                        $validReceipts = array_filter($receipts, fn($u) => filled($u) && $u !== '[]' && $u !== '[""]');
                    @endphp
                    @if(!empty($validReceipts))
                        <div class="grid grid-cols-1 gap-4">
                            @foreach($validReceipts as $index => $receiptPath)
                                @php
                                    $rUrl = \App\Support\EnrollmentStorage::url($receiptPath);
                                    $rIsPdf = $receiptPath && strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION)) === 'pdf';
                                    $cardLabel = count($validReceipts) > 1 ? 'Receipt Proof #' . ($index + 1) : 'Receipt Proof';
                                @endphp
                                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 flex flex-col gap-3 shadow-sm hover:border-emerald-250 transition-all duration-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold text-slate-700 uppercase tracking-wide">{{ $cardLabel }}</span>
                                        <button type="button" @click="openPreview('{{ $rUrl }}', '{{ $cardLabel }}', {{ $rIsPdf ? 'true' : 'false' }})" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer">
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            View Full Proof
                                        </button>
                                    </div>
                                    
                                    <button type="button" @click="openPreview('{{ $rUrl }}', '{{ $cardLabel }}', {{ $rIsPdf ? 'true' : 'false' }})" class="w-full overflow-hidden rounded-lg border border-slate-200 bg-white hover:opacity-95 transition-opacity cursor-zoom-in">
                                        @if($rIsPdf)
                                            <div class="flex flex-col items-center justify-center py-10 gap-2 text-slate-400">
                                                <i data-lucide="file-text" class="h-12 w-12 text-rose-500"></i>
                                                <span class="text-xs font-extrabold uppercase tracking-widest">PDF DOCUMENT</span>
                                            </div>
                                        @else
                                            <img src="{{ $rUrl }}" alt="{{ $cardLabel }}" class="w-full h-96 object-cover object-top block" loading="lazy">
                                        @endif
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                            <i data-lucide="image-off" class="h-10 w-10 mb-2"></i>
                            <p class="text-sm font-bold">No receipt uploads found.</p>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    @else
        <x-card title="Payment Receipt Proofs" subtitle="Verification status of tuition and registration fees">
            <div class="rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-500 shadow-sm flex flex-col items-center justify-center">
                <i data-lucide="receipt-text" class="h-12 w-12 text-slate-350 mb-3 animate-pulse"></i>
                <h3 class="text-lg font-bold text-slate-850">No Payment Record Found</h3>
                <p class="text-sm text-slate-455 mt-1 max-w-sm">This student record does not have any associated payment details linked to their application profile.</p>
            </div>
        </x-card>
    @endif
</div>
