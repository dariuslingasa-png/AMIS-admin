@php
    use Illuminate\Support\Str;

    // A. Visual Accent dynamic calculation
    $familyNo = $student->applicant->family_application_id ?? $student->id;
    $accentClasses = ['accent-green', 'accent-blue', 'accent-amber', 'accent-violet', 'accent-rose'];
    $accentClass = $accentClasses[$familyNo % 5];

    $name = trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? ''));
    $displayName = $name ? Str::upper($name) : 'STUDENT PROFILE';
    
    $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url);
    $studentAddress = implode(', ', array_filter([$student->applicant->street_address, $student->applicant->city, $student->applicant->state_province, $student->applicant->country]));
    $homeAddress = implode(', ', array_filter([$student->applicant->home_street_address, $student->applicant->home_city, $student->applicant->home_state_province]));
    $studentMobile = trim(($student->applicant->mobile_country_code ?? '').' '.($student->applicant->mobile_number ?? ''));
    $parentMobile = trim(($student->applicant->parent_country_code ?? '').' '.($student->applicant->parent_mobile ?? ''));

    // B. Reusable layout sections mapping (using same components for absolute consistency)
    $studentSections = [
        ['title' => 'Academic Profile', 'icon' => 'graduation-cap', 'fields' => [
            ['Student Type', $student->applicant->student_type], ['Grade Level', $student->grade_level],
            ['School Year', $student->school_year], ['Learning Mode', $student->applicant->learning_mode],
            ['AMIS Student ID', $student->applicant->amis_student_id],
        ]],
        ['title' => 'Personal Details', 'icon' => 'id-card', 'fields' => [
            ['Gender', $student->applicant->gender], ['Date of Birth', optional($student->applicant->date_of_birth)->format('M d, Y')],
            ['Place of Birth', $student->applicant->place_of_birth], ['Religion', $student->applicant->religion],
            ['Ethnicity', $student->applicant->ethnicity],
        ]],
        ['title' => 'Student Contact', 'icon' => 'mail', 'fields' => [['Email', $student->school_email], ['Mobile', $studentMobile]]],
    ];

    $addressSections = [
        ['title' => 'Residence Address', 'icon' => 'map', 'fields' => [['Full Address', $studentAddress ?: $student->applicant->address]]],
    ];

    $guardianSections = [
        ['title' => "Father's Details", 'icon' => 'user', 'fields' => [["Father's Full Name", trim(($student->applicant->father_first_name ?? '').' '.($student->applicant->father_last_name ?? '')), 'Occupation', $student->applicant->father_occupation]]],
        ['title' => "Mother's Details", 'icon' => 'user-round', 'fields' => [["Mother's Full Name", trim(($student->applicant->mother_first_name ?? '').' '.($student->applicant->mother_last_name ?? '')), 'Occupation', $student->applicant->mother_occupation]]],
        ['title' => 'Parent Contact', 'icon' => 'phone', 'fields' => [['Parent Email', $student->applicant->parent_email], ['Parent Mobile', $parentMobile]]],
        ['title' => 'Home Address', 'icon' => 'map-pin', 'fields' => [['Full Home Address', $homeAddress ?: $student->applicant->home_address]]],
    ];

    $hasMedicalConcern = (bool) $student->applicant->medical_has_concern;
    $medicalSections = [
        ['title' => 'Emergency Contact', 'icon' => 'shield-alert', 'fields' => [
            ['Contact Person', $student->applicant->emergency_name], ['Relationship', $student->applicant->emergency_relationship],
            ['Emergency Phone', $student->applicant->emergency_phone],
        ]],
    ];
    if ($hasMedicalConcern) {
        array_unshift($medicalSections, ['title' => 'Medical Profile', 'icon' => 'heart-pulse', 'fields' => [
            ['Allergies', $student->applicant->allergies], ['Current Medications', $student->applicant->current_medications],
            ['Health Conditions', $student->applicant->health_conditions], ['Medical History', $student->applicant->medical_history],
            ['Emergency Instructions', $student->applicant->emergency_instructions],
        ]]);
    }
@endphp

<x-admin-layout
    title="Student Profile"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => Str::headline($displayName), 'href' => null],
    ]"
>
    <!-- Page back link -->
    <div class="mb-5 flex justify-between items-center">
        <div>
            <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Student Administration</span>
        </div>
        <a href="{{ route('admin.students.index') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98]">
            <i data-lucide="chevron-left" class="h-4 w-4"></i>
            Back to directory
        </a>
    </div>

    <div class="applicant-page" x-data="{
         copySuccess: false,
         activeTab: 'overview',
         preview: false,
         src: '',
         label: '',
         pdf: false,
         zoom: 1,
         panning: false,
         panEl: null,
         panX: 0,
         panY: 0,
         panLeft: 0,
         panTop: 0,
         openPreview(url, title, isPdf) {
             this.preview = true;
             this.src = url;
             this.label = title;
             this.pdf = isPdf;
             this.zoom = 1;
         },
         closePreview() {
             this.preview = false;
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
         }
     }"
     x-effect="document.body.classList.toggle('overflow-hidden', preview)"
     @keydown.escape.window="closePreview()"
     @mouseup.window="stopPan()"
     @touchend.window="stopPan()">
        
        <!-- Main Column (Tabs and Details) -->
        <main class="space-y-6">
            <!-- Dynamic Profile Header Card (Conforms perfectly to the original applicant card design) -->
            <section class="applicant-profile-card relative {{ $accentClass }}">
                <span class="application-number-pill">Student ID #{{ $student->student_number ?? 'Pending' }}</span>
                <button type="button" class="applicant-photo" @if ($photoUrl) @click="openPreview('{{ $photoUrl }}', '2x2 Photo', false)" @endif>
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="2x2 Photo" class="w-full h-full object-cover block" loading="eager" decoding="async" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <span class="w-full h-full items-center justify-center text-xs font-extrabold" style="display:none">NO PHOTO</span>
                    @else
                        NO PHOTO
                    @endif
                </button>
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">{{ $displayName }}</h2>
                    <p class="mt-2 text-sm text-emerald-50/90 flex items-center gap-1.5">
                        <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                        {{ $student->school_email ?? '-' }}
                    </p>
                    <div class="applicant-pill-row">
                        <span class="applicant-pill applicant-pill-grade">{{ Str::upper($student->grade_level ?: 'Grade pending') }}</span>
                        <span class="applicant-pill applicant-pill-type">{{ Str::upper($student->applicant->student_type ?: 'Student') }}</span>
                        <span class="applicant-pill applicant-pill-mode">{{ Str::upper($student->applicant->learning_mode ?: 'Learning mode pending') }}</span>
                        <span class="applicant-pill applicant-pill-year">SY {{ $student->school_year ?? '-' }}</span>
                    </div>
                </div>
            </section>

            <!-- Beautiful Flowbite Segmented Tabs Navigation -->
            <div class="bg-slate-100 dark:bg-slate-800 p-1.5 rounded-2xl">
                <nav class="flex flex-col sm:flex-row gap-1 text-sm font-bold" aria-label="Tabs">
                    <button @click="activeTab = 'overview'" 
                            :class="activeTab === 'overview' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-900/50'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 sm:flex-initial cursor-pointer">
                        <i data-lucide="user" class="h-4 w-4"></i>
                        <span>Overview Details</span>
                    </button>
                    <button @click="activeTab = 'academic'" 
                            :class="activeTab === 'academic' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-900/50'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 sm:flex-initial cursor-pointer">
                        <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                        <span>Classroom & MS Teams</span>
                    </button>
                    <button @click="activeTab = 'soa'" 
                            :class="activeTab === 'soa' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-900/50'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 sm:flex-initial cursor-pointer">
                        <i data-lucide="credit-card" class="h-4 w-4"></i>
                        <span>Tuition Ledger (SOA)</span>
                    </button>
                    <button @click="activeTab = 'documents'" 
                            :class="activeTab === 'documents' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-900/50'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 sm:flex-initial cursor-pointer">
                        <i data-lucide="folder-open" class="h-4 w-4"></i>
                        <span>Requirement Files</span>
                    </button>
                </nav>
            </div>

            <!-- Tab Content 1: Overview Details -->
            <div x-show="activeTab === 'overview'" class="space-y-6" x-cloak>
                <x-card title="Student Profile" subtitle="Core demographics and contact info">
                    <div class="detail-section-stack">
                        @foreach ($studentSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Residential Info" subtitle="Residence details from enrollment form">
                    <div class="detail-section-stack">
                        @foreach ($addressSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Parent / Guardian Details" subtitle="Grouped parent contacts and home addresses">
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
                                        @if($sibling->status === 'approved' && $sibling->student)
                                            <a href="{{ route('admin.students.show', $sibling->student) }}" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                                View Student Profile <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('admin.applicants.show', $sibling) }}" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                                View Applicant File <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
                @endif

                <x-card title="Medical Background" subtitle="Health info and emergency response contacts">
                    <div class="detail-section-stack">
                        @foreach ($medicalSections as $section)
                            <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
                        @endforeach
                    </div>
                </x-card>
            </div>

            <!-- Tab Content 2: Classroom & MS Teams -->
            <div x-show="activeTab === 'academic'" class="space-y-6" x-cloak>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h4 class="text-xxs font-extrabold uppercase tracking-wider text-slate-400">Section Classroom</h4>
                        <p class="mt-1 text-base font-extrabold text-slate-900">{{ $student->studentSection->section->official_name ?? $student->studentSection->section->name ?? 'Unnamed Section' }}</p>
                        <div class="mt-2.5 flex items-center gap-1.5">
                            <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xxs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">{{ $student->studentSection->section->learning_mode ?? '-' }}</span>
                            @if($student->studentSection->section->shift)
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xxs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $student->studentSection->section->shift }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h4 class="text-xxs font-extrabold uppercase tracking-wider text-slate-400">Microsoft AD Identity</h4>
                        <p class="mt-1 text-xxs font-mono text-slate-600 overflow-x-auto select-all">{{ $student->ms_user_id ?? 'No AD object mapped' }}</p>
                        <div class="mt-2">
                            @php
                                $msStatus = $student->studentSection->ms_status ?? 'pending';
                                $badgeColor = match($msStatus) { 'enrolled' => 'green', 'failed' => 'red', default => 'yellow' };
                                $badgeLabel = match($msStatus) { 'enrolled' => 'Synced', 'failed' => 'Failed', default => 'Pending' };
                            @endphp
                            <x-badge :color="$badgeColor">MS Sync: {{ $badgeLabel }}</x-badge>
                        </div>
                    </div>
                </div>

                <x-card title="Registered Subjects & Channels" subtitle="Academic subjects linked in Teams">
                    <div class="overflow-hidden rounded-md border border-slate-200 mt-2">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-4 font-bold">Subject Name</th>
                                    <th class="px-5 py-4 font-bold">Assigned Teacher</th>
                                    <th class="px-5 py-4 font-bold">Schedule</th>
                                    <th class="px-5 py-4 font-bold text-right">Teams Channel</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($student->studentSection->section->subjects ?? [] as $sub)
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="px-5 py-4 font-extrabold text-slate-950">{{ $sub->subject_name }}</td>
                                        <td class="px-5 py-4 font-semibold text-slate-700">{{ $sub->teacher_name ?? 'TBA' }}</td>
                                        <td class="px-5 py-4 font-medium text-slate-500">{{ $sub->schedule ?? '-' }}</td>
                                        <td class="px-5 py-4 text-right font-mono text-xxs text-slate-400 select-all">{{ $sub->ms_channel_id ?? 'No channel' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-10 text-center text-sm font-medium text-slate-500">No subjects registered yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <!-- Tab Content 3: Tuition Ledger (SOA) -->
            <div x-show="activeTab === 'soa'" class="space-y-6" x-cloak>
                @if($student->account)
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                        @foreach ([
                            ['Gross', '₱'.number_format($student->account->gross_total, 2)],
                            ['Discount', '₱'.number_format($student->account->discount_amount, 2) . ' ('.$student->account->discount_percentage.'%)'],
                            ['Downpayment', '₱'.number_format($student->account->enrollment_fee_paid, 2)],
                            ['Paid Total', '₱'.number_format($student->account->amount_paid, 2)],
                            ['Remaining', '₱'.number_format($student->account->remaining_balance, 2)]
                        ] as [$label, $value])
                            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                                <span class="text-xxs font-extrabold uppercase tracking-wider text-slate-400 block">{{ $label }}</span>
                                <span class="text-sm font-extrabold text-slate-900 block mt-1">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                    <x-card title="Tuition Billing Schedule" subtitle="Chronological Statement of Account ledgers">
                        <div class="overflow-hidden rounded-md border border-slate-200 mt-2">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-5 py-4 font-bold">Month</th>
                                        <th class="px-5 py-4 font-bold">Due Date</th>
                                        <th class="px-5 py-4 font-bold">Amount Due</th>
                                        <th class="px-5 py-4 font-bold">Description</th>
                                        <th class="px-5 py-4 text-right font-bold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach($student->account->monthlyBillings as $billing)
                                        <tr class="transition hover:bg-slate-50">
                                            <td class="px-5 py-4 font-extrabold text-slate-950">{{ $billing->month_name }}</td>
                                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $billing->due_date?->format('M d, Y') ?? '-' }}</td>
                                            <td class="px-5 py-4 font-extrabold text-slate-950">₱{{ number_format($billing->amount_due, 2) }}</td>
                                            <td class="px-5 py-4 font-medium text-slate-500 text-xs">{{ $billing->description }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <x-badge color="{{ $billing->status === 'paid' ? 'green' : 'red' }}">
                                                    {{ ucfirst($billing->status) }}
                                                </x-badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                @else
                    <div class="empty-state">
                        <i data-lucide="receipt-text" class="h-8 w-8"></i>
                        <p>No billing ledger initialized yet.</p>
                    </div>
                @endif
            </div>

            <!-- Tab Content 4: Requirement Files -->
            <div x-show="activeTab === 'documents'" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-cloak>
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
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </main>

        <!-- Right Sidebar (Review Panel style) -->
        <aside class="review-panel space-y-6">
            <!-- Account Information Card -->
            <x-card title="Account Summary">
                <dl class="space-y-4 text-xs">
                    <div>
                        <dt class="font-extrabold uppercase tracking-wider text-slate-400">Student ID Number</dt>
                        <dd class="mt-1.5 font-extrabold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                            <span>{{ $student->student_number ?? 'Pending' }}</span>
                            <button @click="navigator.clipboard.writeText('{{ $student->student_number }}'); copySuccess = true; setTimeout(() => copySuccess = false, 2000)" class="text-slate-400 hover:text-emerald-600 focus:outline-none transition-colors" title="Copy Student ID">
                                <i data-lucide="copy" class="h-4 w-4" x-show="!copySuccess"></i>
                                <i data-lucide="check" class="h-4 w-4 text-emerald-600" x-show="copySuccess"></i>
                            </button>
                        </dd>
                    </div>
                    <div class="border-t border-slate-100 pt-3.5 dark:border-slate-800">
                        <dt class="font-extrabold uppercase tracking-wider text-slate-400">School Email</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-200 select-all break-all">{{ $student->school_email ?? '-' }}</dd>
                    </div>
                    <div class="border-t border-slate-100 pt-3.5 dark:border-slate-800">
                        <dt class="font-extrabold uppercase tracking-wider text-slate-400">Classroom Section</dt>
                        <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-200">{{ $student->studentSection->section->name ?? 'No Section' }}</dd>
                    </div>
                </dl>
            </x-card>

            <!-- Actions Panel -->
            <x-card title="Actions Workspace">
                <div class="space-y-3.5">
                    <!-- Update Status Form -->
                    <form method="POST" action="{{ route('admin.students.update-status', $student) }}" class="border-b border-slate-100 pb-4 mb-4 dark:border-slate-800">
                        @csrf
                        <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Administrative Status</label>
                        <div class="flex gap-2">
                            <select name="status" class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                <option value="verified" @selected(($student->user->account_status ?? 'verified') === 'verified')>Active / Verified</option>
                                <option value="suspended" @selected(($student->user->account_status ?? 'verified') === 'suspended')>Suspended / Deactivated</option>
                                <option value="graduated" @selected(($student->user->account_status ?? 'verified') === 'graduated')>Graduated</option>
                                <option value="transferred" @selected(($student->user->account_status ?? 'verified') === 'transferred')>Transferred</option>
                                <option value="withdrawn" @selected(($student->user->account_status ?? 'verified') === 'withdrawn')>Withdrawn</option>
                            </select>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Save Status">
                                Save
                            </button>
                        </div>
                    </form>

                    <!-- Update Microsoft Email Form -->
                    <form method="POST" action="{{ route('admin.students.update-email', $student) }}" class="border-b border-slate-100 pb-4 mb-4 dark:border-slate-800">
                        @csrf
                        <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Microsoft / School Email</label>
                        <div class="flex gap-2">
                            <input type="email" name="email" value="{{ $student->school_email }}" required class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Save Email">
                                Rename
                            </button>
                        </div>
                    </form>

                    <!-- Resend credentials form -->
                    <form method="POST" action="{{ route('admin.students.resend', $student) }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2.5 rounded-xl bg-amber-500 px-4 text-sm font-bold text-white hover:bg-amber-600 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                            <i data-lucide="key" class="h-4 w-4"></i>
                            <span>Resend Credentials</span>
                        </button>
                    </form>

                    <!-- Force Teams Sync -->
                    @if($student->ms_user_id)
                        <form method="POST" action="{{ route('admin.ms-sync.student', $student) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2.5 rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                                <span>Sync Teams Account</span>
                            </button>
                        </form>
                    @endif

                    <!-- Delete Student -->
                    <div class="border-t border-rose-100 pt-4 mt-4 dark:border-rose-900/30">
                        <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                              onsubmit="return confirm('Delete {{ $student->student_number }} ({{ $student->school_email }})?\n\nThis will permanently delete the student from the portal and Microsoft 365. This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2.5 rounded-xl border border-rose-200 bg-white px-4 text-sm font-bold text-rose-600 hover:bg-rose-50 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                <span>Delete Student</span>
                            </button>
                        </form>
                    </div>
                </div>
            </x-card>

            <!-- Onboarding Checklist -->
            <x-card title="Onboarding Checklist">
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-1">
                        <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">User Created</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $student->ms_user_id ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-400 dark:ring-rose-500/20' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $student->ms_user_id ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                            {{ $student->ms_user_id ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800 pt-2">
                        <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">Teams Enrolled</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $student->ms_teams_enrolled_at ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/20 dark:text-amber-400 dark:ring-amber-500/20' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $student->ms_teams_enrolled_at ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                            {{ $student->ms_teams_enrolled_at ? 'Enrolled' : 'Pending' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800 pt-2">
                        <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">SOA Initialized</span>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $student->account ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-400 dark:ring-rose-500/20' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $student->account ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                            {{ $student->account ? 'Initialized' : 'Missing' }}
                        </span>
                    </div>
                </div>
            </x-card>
        </aside>

        <!-- Preview Modal Portal (Identical to original modal previews for consistency) -->
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
                            <button id="download-pdf-btn" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadPdf()">
                                <i data-lucide="download" class="h-3.5 w-3.5"></i> Download PDF
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
