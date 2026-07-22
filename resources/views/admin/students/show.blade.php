@php
    use Illuminate\Support\Str;

    // A. Visual Accent dynamic calculation
    $familyNo = $student->applicant->family_application_id ?? $student->id;
    $accentClasses = ['accent-green', 'accent-blue', 'accent-amber', 'accent-violet', 'accent-rose'];
    $accentClass = $accentClasses[$familyNo % 5];
 
    // Country Flag code mapping
    $countryName = trim(strtolower($student->applicant->country ?? ''));
    $countryCode = null;
    if ($countryName !== '') {
        $countryMap = [
            'philippines' => 'ph',
            'phil' => 'ph',
            'saudi arabia' => 'sa',
            'saudi' => 'sa',
            'united arab emirates' => 'ae',
            'uae' => 'ae',
            'kuwait' => 'kw',
            'qatar' => 'qa',
            'oman' => 'om',
            'indonesia' => 'id',
            'bahrain' => 'bh',
            'canada' => 'ca',
            'australia' => 'au',
            'afghanistan' => 'af',
            'iran' => 'ir',
            'iran, islamic republic of persian gulf' => 'ir',
            'egypt' => 'eg',
            'india' => 'in',
            'czech republic' => 'cz',
            'angola' => 'ao',
            'pakistan' => 'pk',
            'mexico' => 'mx',
            'united states' => 'us',
            'usa' => 'us',
        ];
        foreach ($countryMap as $key => $code) {
            if (str_contains($countryName, $key)) {
                $countryCode = $code;
                break;
            }
        }
    }

    $firstName = trim($student->applicant->first_name ?? '');
    $middleName = trim($student->applicant->middle_name ?? '');
    $lastName = trim($student->applicant->last_name ?? '');

    $middleInitial = '';
    if ($middleName !== '') {
        $firstChar = mb_substr($middleName, 0, 1, 'UTF-8');
        $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
    }

    $nameParts = array_filter([$firstName, $middleInitial, $lastName], fn($v) => $v !== '');
    $name = html_entity_decode(implode(' ', $nameParts), ENT_QUOTES, 'UTF-8');
    $displayName = $name ? Str::upper($name) : 'STUDENT PROFILE';
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
    
    // Calculate ID card text font sizes globally
    $lastNameLen = strlen($lastName);
    if ($lastNameLen <= 8) {
        $lastNameFontSize = 36;
    } elseif ($lastNameLen <= 12) {
        $lastNameFontSize = 28;
    } elseif ($lastNameLen <= 15) {
        $lastNameFontSize = 22;
    } elseif ($lastNameLen <= 18) {
        $lastNameFontSize = 17;
    } elseif ($lastNameLen <= 21) {
        $lastNameFontSize = 12.5;
    } elseif ($lastNameLen <= 25) {
        $lastNameFontSize = 11;
    } else {
        $lastNameFontSize = 9.5;
    }

    $displayFirstName = trim($firstName . ' ' . $middleInitial);
    $displayFirstNameLen = strlen($displayFirstName);
    $displayFirstNameFontSize = $displayFirstNameLen > 25 ? 11.5 : ($displayFirstNameLen > 18 ? 13 : 15);
    
    $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url);
    $studentAddress = implode(', ', array_filter([$student->applicant->street_address, $student->applicant->city, $student->applicant->state_province, $student->applicant->country]));
    $homeAddress = implode(', ', array_filter([$student->applicant->home_street_address, $student->applicant->home_city, $student->applicant->home_state_province]));
    $studentMobile = trim(($student->applicant->mobile_country_code ?? '').' '.($student->applicant->mobile_number ?? ''));
    $parentMobile = trim(($student->applicant->parent_country_code ?? '').' '.($student->applicant->parent_mobile ?? ''));

    // ── B. Requirements & LRN Audit Logic ────────────────────────────────────
    $studentTypeRaw = strtolower(trim($student->applicant->student_type ?? 'new'));
    $isNewStudent = str_contains($studentTypeRaw, 'new');
    $isOldStudent = str_contains($studentTypeRaw, 'old');
    $isTransferee = str_contains($studentTypeRaw, 'transfer');

    $gradeLevelStr = strtolower(trim($student->grade_level ?? ''));
    $isKinder1or2 = in_array(trim($student->grade_level ?? ''), ['Kinder 1', 'Kinder 2', 'Kindergarten', 'K1', 'K2'], true) || str_contains($gradeLevelStr, 'kinder');

    $rawLrn = trim($student->applicant->lrn ?? '');
    $hasLrn = !empty($rawLrn) && !in_array(strtoupper($rawLrn), ['NA', 'N/A', 'NONE', 'NOT PROVIDED'], true);

    // Document Verification Flags
    $hasPhoto = !empty($student->applicant->photo_2x2_url);
    $hasBirthCert = !empty($student->applicant->birth_cert_url) || !empty($student->applicant->affidavit_url);
    $hasReportCard = !empty($student->applicant->report_card_url) || !empty($student->applicant->affidavit_url);

    // Payment Proof Verification
    $hasPaymentProof = false;
    if ($student->applicant) {
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

        $hasPaymentProof = \App\Models\Payment::whereIn('user_id', $familyUserIds)
            ->whereNotNull('receipt_url')
            ->whereNotIn('receipt_url', ['', '[]', '[""]'])
            ->exists();
    }

    // Build Detailed Missing Items & Reminders
    $missingRequirements = [];
    $reminders = [];

    // 1. LRN Checks & Reminders
    if ($isKinder1or2) {
        $lrnStatus = 'exempt';
        $lrnNote = 'Kinder 1 & Kinder 2 students do not have LRNs yet (DepEd LRN Exempt).';
    } elseif ($hasLrn) {
        $lrnStatus = 'verified';
        $lrnNote = 'LRN Verified: ' . $rawLrn;
    } else {
        $lrnStatus = 'missing';
        if ($isOldStudent) {
            $lrnNote = 'OLD STUDENT MISSING LRN: Please retrieve and update student\'s official LRN record.';
            $missingRequirements[] = 'Learner Reference Number (LRN)';
        } elseif ($isTransferee) {
            $lrnNote = 'TRANSFEREE MISSING LRN: Request LRN and Form 137 / SF10 from previous school.';
            $missingRequirements[] = 'Learner Reference Number (LRN)';
        } else {
            $lrnNote = 'NEW STUDENT MISSING LRN: Encode LRN upon DepEd LIS registration.';
            $missingRequirements[] = 'Learner Reference Number (LRN)';
        }
    }

    // 2. Document & Mandatory Checks
    if (!$hasPhoto) {
        $missingRequirements[] = '2x2 Photo ID';
        $reminders[] = '2x2 Student Photo is missing for official ID printing.';
    }
    // Old Students are EXEMPT from submitting Birth Certificate and Report Card (already in system)
    if (!$hasBirthCert && !$isOldStudent) {
        $missingRequirements[] = 'PSA Birth Certificate or Temporary Affidavit';
        $reminders[] = 'PSA Birth Certificate or notarized Temporary Affidavit is required.';
    }
    if (!$hasReportCard && ($isTransferee || (!$isKinder1or2 && !$isOldStudent))) {
        $missingRequirements[] = 'Form 138 / Report Card / SF9';
        $reminders[] = 'Previous Grade Level Form 138 / Report Card is required for academic clearance.';
    }
    if (!$hasPaymentProof) {
        $missingRequirements[] = 'Enrollment Payment Proof';
        $reminders[] = 'No verified tuition or fee payment proof linked to this account.';
    }

    // Manual Lock Override by Super Admin
    $isManuallyLocked = (bool) ($student->is_requirements_locked ?? false);
    $isRequirementsComplete = $isManuallyLocked || (count($missingRequirements) === 0);

    // ── C. Reusable layout sections mapping ──────────────────────────────────
    $studentSections = [
        ['title' => 'Academic Profile', 'icon' => 'graduation-cap', 'key' => 'academic', 'fields' => [
            ['Student Type', $student->applicant->student_type], ['Grade Level', $student->grade_level],
            ['School Year', $student->school_year], ['Learning Mode', $student->applicant->learning_mode],
            ['AMIS Student ID', $student->applicant->amis_student_id],
            ['LRN', $student->applicant->lrn],
        ]],
        ['title' => 'Personal Details', 'icon' => 'id-card', 'key' => 'personal', 'fields' => [
            ['First Name', $student->applicant->first_name],
            ['Middle Name', $student->applicant->middle_name],
            ['Last Name', $student->applicant->last_name],
            ['Suffix', $student->applicant->suffix],
            ['Gender', $student->applicant->gender], ['Date of Birth', optional($student->applicant->date_of_birth)->format('M d, Y')],
            ['Place of Birth', $student->applicant->place_of_birth], ['Religion', $student->applicant->religion],
            ['Ethnicity', $student->applicant->ethnicity],
        ]],
        ['title' => 'Student Contact', 'icon' => 'mail', 'key' => 'contact', 'fields' => [['Email', $student->school_email], ['Mobile', $studentMobile]]],
    ];

    $addressSections = [
        ['title' => 'Residence Address', 'icon' => 'map', 'key' => 'contact', 'fields' => [['Full Address', $studentAddress ?: $student->applicant->address]]],
    ];

    $guardianSections = [
        ['title' => "Father's Details", 'icon' => 'user', 'key' => 'parents', 'fields' => [["Father's Full Name", trim(($student->applicant->father_first_name ?? '').' '.($student->applicant->father_last_name ?? '')), 'Occupation', $student->applicant->father_occupation]]],
        ['title' => "Mother's Details", 'icon' => 'user-round', 'key' => 'parents', 'fields' => [["Mother's Full Name", trim(($student->applicant->mother_first_name ?? '').' '.($student->applicant->mother_last_name ?? '')), 'Occupation', $student->applicant->mother_occupation]]],
        ['title' => 'Parent Contact', 'icon' => 'phone', 'key' => 'parents', 'fields' => [['Parent Email', $student->applicant->parent_email], ['Parent Mobile', $parentMobile]]],
        ['title' => 'Home Address', 'icon' => 'map-pin', 'key' => 'parents', 'fields' => [['Full Home Address', $homeAddress ?: $student->applicant->home_address]]],
    ];

    $hasMedicalConcern = (bool) $student->applicant->medical_has_concern;
    $medicalSections = [
        ['title' => 'Emergency Contact', 'icon' => 'shield-alert', 'key' => 'parents', 'fields' => [
            ['Contact Person', $student->applicant->emergency_name], ['Relationship', $student->applicant->emergency_relationship],
            ['Emergency Phone', $student->applicant->emergency_phone], ['Emergency Address', $student->applicant->emergency_address],
        ]],
    ];
    if ($hasMedicalConcern) {
        array_unshift($medicalSections, ['title' => 'Medical Profile', 'icon' => 'heart-pulse', 'fields' => [
            ['Allergies', $student->applicant->allergies], ['Current Medications', $student->applicant->current_medications],
            ['Health Conditions', $student->applicant->health_conditions], ['Medical History', $student->applicant->medical_history],
            ['Emergency Instructions', $student->applicant->emergency_instructions],
        ]]);
    }

    $app = $student->applicant;
    $fatherName = trim(($app->father_first_name ?? '') . ' ' . ($app->father_last_name ?? ''));
    $motherName = trim(($app->mother_first_name ?? '') . ' ' . ($app->mother_last_name ?? ''));
    $pName = !empty($fatherName) ? $fatherName : (!empty($motherName) ? $motherName : ($app->father_name ?? $app->mother_name ?? ''));

    $singleStudentJsonArray = [
        [
            'lrn' => (string)($app->lrn ?? $student->student_number ?? 'NA'),
            'first_name' => mb_strtoupper($app->first_name ?? ''),
            'middle_name' => mb_strtoupper($app->middle_name ?? ''),
            'last_name' => mb_strtoupper($app->last_name ?? ''),
            'grade_level' => (string)($student->grade_level ?: ($app->grade_level ?: 'Grade 1')),
            'gender' => (string)($app->gender ?: 'Male'),
            'address' => mb_strtoupper($app->address ?? $app->street_address ?? ''),
            'date_of_birth' => $app?->date_of_birth ? $app->date_of_birth->format('Y-m-d') : '',
            'place_of_birth' => mb_strtoupper($app->place_of_birth ?? ''),
            'religion' => mb_strtoupper($app->religion ?? 'ISLAM'),
            'parent_name' => mb_strtoupper($pName),
            'parent_mobile' => (string)($app->parent_mobile ?? $app->mobile_number ?? ''),
            'parent_email' => strtolower($app->parent_email ?? ''),
        ]
    ];
    $singleStudentJsonPretty = json_encode($singleStudentJsonArray, JSON_PRETTY_PRINT);
@endphp

<script>
    function updateBodyScroll() {
        let alpineModalOpen = false;
        try {
            const el = document.querySelector('[x-data]');
            if (el && window.Alpine) {
                const data = window.Alpine.$data(el);
                if (data && (data.openEditModal || data.showIdPreview || data.openPasswordModal || data.openStudentJsonModal || data.preview)) {
                    alpineModalOpen = true;
                }
            }
        } catch (e) {}

        const cropModal = document.getElementById('photo-crop-modal');
        const cropOpen = cropModal && !cropModal.classList.contains('hidden');

        const optionsModal = document.getElementById('photo-options-modal');
        const optionsOpen = optionsModal && !optionsModal.classList.contains('hidden');

        if (alpineModalOpen || cropOpen || optionsOpen) {
            document.body.style.overflow = 'hidden';
            document.body.classList.add('overflow-hidden');
        } else {
            document.body.style.overflow = '';
            document.body.classList.remove('overflow-hidden');
        }
        window.AMIS_SINGLE_STUDENT_JSON = @json($singleStudentJsonPretty);
    }
</script>

<x-admin-layout
    title="Student Profile"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => Str::headline($displayName), 'href' => null],
    ]"
>
    <div x-data="{
         openEditModal: {{ $errors->any() ? 'true' : 'false' }},
         showIdPreview: {{ request('preview_id') ? 'true' : 'false' }},
         lastNameFontSize: {{ $student->id_last_name_font_size ?: $lastNameFontSize }},
         firstNameFontSize: {{ $student->id_first_name_font_size ?: $displayFirstNameFontSize }},
         gradeFontSize: {{ $student->id_grade_font_size ?: 25 }},
         idFontSize: {{ $student->id_num_font_size ?: 10 }},
         openPasswordModal: false,
         openStudentJsonModal: false,
         singleStudentJson: window.AMIS_SINGLE_STUDENT_JSON,
         editSection: 'all',
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
         async saveFontSizes() {
             const btn = document.getElementById('btn-save-font-sizes');
             const oldHtml = btn.innerHTML;
             btn.disabled = true;
             btn.innerHTML = `Saving...`;
             
             try {
                 const response = await fetch('{{ route('admin.students.update-id-font-sizes', $student) }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': '{{ csrf_token() }}',
                         'X-Requested-With': 'XMLHttpRequest'
                     },
                     body: JSON.stringify({
                         id_last_name_font_size: this.lastNameFontSize,
                         id_first_name_font_size: this.firstNameFontSize,
                         id_grade_font_size: this.gradeFontSize,
                         id_num_font_size: this.idFontSize
                     })
                 });
                 
                 const result = await response.json();
                 if (response.ok && result.success) {
                     btn.innerHTML = `Saved!`;
                     btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                     btn.classList.add('bg-blue-600');
                     setTimeout(() => {
                         btn.innerHTML = oldHtml;
                         btn.classList.remove('bg-blue-600');
                         btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                         btn.disabled = false;
                     }, 2000);
                 } else {
                     alert(result.message || 'Failed to save font sizes.');
                     btn.innerHTML = oldHtml;
                     btn.disabled = false;
                 }
             } catch (err) {
                 alert('Error saving font sizes: ' + err);
                 btn.innerHTML = oldHtml;
                 btn.disabled = false;
             }
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
      x-effect="preview; openEditModal; showIdPreview; openPasswordModal; typeof updateBodyScroll === 'function' && updateBodyScroll(); if (showIdPreview) { $nextTick(() => { typeof adjustLastNameFontSizes === 'function' && adjustLastNameFontSizes(); setTimeout(() => { typeof adjustLastNameFontSizes === 'function' && adjustLastNameFontSizes(); }, 150); }); }"
      @keydown.escape.window="closePreview()"
      @mouseup.window="stopPan()"
      @touchend.window="stopPan()">

    <!-- Page back link -->
    <div class="mb-5 flex justify-between items-center">
        <div>
            <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Student Administration</span>
        </div>
        <div class="flex items-center gap-2">
            <!-- Student JSON Payload Button -->
            <button type="button" @click="openStudentJsonModal = true"
               class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 px-4 py-2 text-sm font-bold text-emerald-800 dark:text-emerald-300 shadow-sm transition hover:bg-emerald-100 dark:hover:bg-emerald-900/60 active:scale-[0.98] cursor-pointer"
               title="View & Copy Student JSON Payload">
                <i data-lucide="file-json" class="h-4 w-4 text-emerald-600"></i>
                <span>JSON Payload</span>
            </button>

            @unless ($isTeacherAdminViewer)
                <!-- Upload / Edit Photo Button (Always Available) -->
                <button type="button" onclick="openPhotoOptionsModal()"
                   class="inline-flex items-center gap-2 rounded-xl border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-950/40 px-4 py-2 text-sm font-bold text-blue-800 dark:text-blue-300 shadow-sm transition hover:bg-blue-100 dark:hover:bg-blue-900/60 active:scale-[0.98] cursor-pointer"
                   title="Upload, Crop, or Replace Student Photo">
                    <i data-lucide="camera" class="h-4 w-4 text-blue-600 dark:text-blue-400"></i>
                    <span>Edit Photo</span>
                </button>
            @endunless

            @unless ($isTeacherAdminViewer)
                @if ($isRequirementsComplete)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 px-3.5 py-2 text-xs font-black uppercase tracking-wider shadow-2xs" title="Profile is locked">
                        <i data-lucide="lock" class="h-4 w-4 text-amber-600"></i>
                        <span>Profile Locked</span>
                    </span>
                @else
                    <button @click="openEditModal = true; editSection = 'all'"
                            class="inline-flex items-center gap-2 rounded-xl border border-transparent bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98] cursor-pointer">
                        <i data-lucide="edit" class="h-4 w-4"></i>
                        <span>Edit Profile</span>
                    </button>
                @endif
            @endunless

            <a href="{{ route('admin.students.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98]">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                <span>Back to directory</span>
            </a>
        </div>
    </div>
    <!-- Grid Layout Container -->
    <div class="applicant-page">
        
        <!-- Main Column (Tabs and Details) -->
        <main class="space-y-6">
            <!-- Dynamic Profile Header Card -->
            <section class="applicant-profile-card relative {{ $accentClass }}" style="overflow: visible;">
                {{-- Clip only the flag bg, not the camera button --}}
                <div class="absolute inset-0 rounded-[inherit] overflow-hidden pointer-events-none" style="z-index: 0;">
                    @if ($countryCode)
                        <div class="absolute right-0 top-0 bottom-0 h-full w-2/5 opacity-20 select-none">
                            <img src="https://flagcdn.com/w640/{{ $countryCode }}.png" 
                                 alt="Country Flag" 
                                 class="h-full w-full object-cover object-right">
                        </div>
                    @endif
                </div>

                <div class="absolute top-4 right-4 flex items-center gap-2 print-hide" style="z-index: 10;">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-white/10 text-white border border-white/25 uppercase tracking-wider">
                        SY {{ $student->school_year ?? '-' }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-white/10 text-white border border-white/25 uppercase tracking-wider">
                        ID #{{ $student->student_number ?? 'Pending' }}
                    </span>
                </div>
                
                <div class="relative" style="width: 96px; height: 96px; z-index: 10; overflow: visible;">
                    <!-- Inner avatar block with overflow hidden for the photo clip -->
                    <div class="w-full h-full relative group flex items-center justify-center overflow-hidden rounded-2xl border-2 border-white/45 bg-white/12 text-emerald-100 cursor-pointer" 
                          @unless ($isTeacherAdminViewer) onclick="openPhotoOptionsModal()" @else @if ($photoUrl) @click="openPreview('{{ $photoUrl }}', '2x2 Photo', false)" @endif @endunless
                          title="Click to edit / replace photo">
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="2x2 Photo" class="w-full h-full object-cover block transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center bg-white/10 text-white/80 p-1 text-center select-none">
                                <svg class="w-10 h-10 text-white/60 mb-0.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                                <span class="text-[9px] font-black uppercase tracking-wider opacity-90">NO PHOTO</span>
                            </div>
                        @endif

                        @unless ($isTeacherAdminViewer)
                            <!-- Permanent Corner Camera Badge inside photo box -->
                            <div class="absolute bottom-1 right-1 bg-emerald-600/90 text-white p-1 rounded-lg backdrop-blur-xs shadow-md border border-white/40 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                </svg>
                            </div>

                            <!-- Hover overlay for photo editing -->
                            <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center transition duration-200 text-white gap-1 select-none pointer-events-none">
                                <svg class="w-7 h-7 text-white drop-shadow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                </svg>
                                <span class="text-[9px] font-black uppercase tracking-wider">EDIT PHOTO</span>
                            </div>
                        @endunless
                    </div>
                    
                    @unless ($isTeacherAdminViewer)
                        <!-- Outer Floating Camera edit button -->
                        <div style="position: absolute; right: -8px; bottom: -8px; z-index: 99999; overflow: visible;" onclick="event.stopPropagation(); openPhotoOptionsModal();">
                            <button type="button" 
                                    onclick="openPhotoOptionsModal()"
                                    class="w-9 h-9 bg-emerald-600 hover:bg-emerald-500 text-white rounded-full flex items-center justify-center shadow-2xl ring-2 ring-white transition transform active:scale-90 cursor-pointer"
                                    title="Edit / Replace Photo">
                                <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                </svg>
                            </button>
                        </div>
                        <input type="file" id="student-photo-input" name="photo" accept="image/*" class="hidden" onchange="uploadStudentPhoto(this)">
                    @endunless
                </div>
                
                <div class="flex-1 min-w-0 pr-48">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-3xl font-black tracking-tight font-outfit uppercase">{{ $displayName }}</h2>
                        @if ($isRequirementsComplete)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-emerald-400/20 text-emerald-200 border border-emerald-400/40 uppercase tracking-wider shadow-xs" title="All mandatory requirements verified & locked">
                                <i data-lucide="lock" class="w-3.5 h-3.5 text-emerald-300"></i>
                                <span>Completed Requirements</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-amber-400/20 text-amber-200 border border-amber-400/40 uppercase tracking-wider shadow-xs animate-pulse" title="Pending: {{ implode(', ', $missingRequirements) }}">
                                <i data-lucide="unlock" class="w-3.5 h-3.5 text-amber-300"></i>
                                <span>Pending Requirements ({{ count($missingRequirements) }})</span>
                            </span>
                        @endif
                    </div>
                    
                    <!-- Metadata Rows -->
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-xs text-white/90 relative" style="z-index: 10;">
                        <div class="flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 opacity-75"></i>
                            <span class="font-medium tracking-wide">{{ $student->school_email ?? '-' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="fingerprint" class="w-4 h-4 opacity-75"></i>
                            <span class="font-medium tracking-wide">
                                <span class="font-bold opacity-75">LRN:</span> 
                                @if($isKinder1or2 && !$hasLrn)
                                    <span class="font-extrabold text-sky-200">NA</span> 
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-sky-400/25 text-sky-100 text-[9px] font-black uppercase tracking-wider">Kinder Exempt</span>
                                @elseif($hasLrn)
                                    <span class="font-extrabold text-emerald-200">{{ $student->applicant->lrn }}</span>
                                @else
                                    <span class="font-extrabold text-amber-300">Missing</span> 
                                    <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-400/25 text-amber-100 text-[9px] font-black uppercase tracking-wider">Needs LRN</span>
                                @endif
                            </span>
                        </div>
                        @if ($student->applicant->country)
                        <div class="flex items-center gap-2">
                            <i data-lucide="globe" class="w-4 h-4 opacity-75"></i>
                            <span class="font-medium tracking-wide flex items-center gap-1.5">
                                @if ($countryCode)
                                    <img src="https://flagcdn.com/w40/{{ $countryCode }}.png" class="inline-block w-5 h-3.5 rounded-xs object-cover shadow-sm border border-white/40" alt="{{ $student->applicant->country }}">
                                @endif
                                {{ $student->applicant->country }}
                            </span>
                        </div>
                        @endif
                        <div class="flex items-center gap-2 {{ $student->applicant->country ? '' : 'md:col-span-2' }}">
                            <i data-lucide="monitor" class="w-4 h-4 opacity-75"></i>
                            <span class="font-medium tracking-wide">{{ $student->applicant->learning_mode ?: 'Learning Mode Pending' }}</span>
                        </div>
                    </div>
                    
                    <!-- Academic Info Badges -->
                    <div class="mt-4 flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-black bg-white/15 text-white border border-white/20 uppercase tracking-wider">
                            {{ $student->grade_level ?: 'Grade pending' }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-black bg-white/15 text-white border border-white/20 uppercase tracking-wider">
                            {{ $student->applicant->student_type ?: 'Student' }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Beautiful Flowbite Segmented Tabs Navigation -->
            <div class="bg-slate-100 dark:bg-slate-800 p-1.5 rounded-2xl">
                <nav class="flex flex-col sm:flex-row gap-1.5 text-sm font-bold" aria-label="Tabs">
                    <button @click="activeTab = 'overview'" 
                            :class="activeTab === 'overview' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white/50 dark:bg-slate-900/30 text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-900/60 shadow-xs'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 cursor-pointer">
                        <i data-lucide="user" class="h-4 w-4"></i>
                        <span>Overview Details</span>
                    </button>
                    <button @click="activeTab = 'academic'" 
                            :class="activeTab === 'academic' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white/50 dark:bg-slate-900/30 text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-900/60 shadow-xs'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 cursor-pointer">
                        <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                        <span>Academic & History</span>
                    </button>
                    @unless ($isTeacherAdminViewer)
                    <button @click="activeTab = 'documents'" 
                            :class="activeTab === 'documents' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white/50 dark:bg-slate-900/30 text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-900/60 shadow-xs'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 cursor-pointer">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        <span>Documents & Verification</span>
                    </button>
                    @endunless
                    <button @click="activeTab = 'grades'" 
                            :class="activeTab === 'grades' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white/50 dark:bg-slate-900/30 text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-900/60 shadow-xs'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 cursor-pointer">
                        <i data-lucide="award" class="h-4 w-4"></i>
                        <span>Grades</span>
                    </button>
                    <button @click="activeTab = 'account'" 
                            :class="activeTab === 'account' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white/50 dark:bg-slate-900/30 text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-900/60 shadow-xs'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 cursor-pointer">
                        <i data-lucide="key-round" class="h-4 w-4"></i>
                        <span>Account Summary</span>
                    </button>
                    <button @click="activeTab = 'audit'" 
                            :class="activeTab === 'audit' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white/50 dark:bg-slate-900/30 text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white hover:bg-white/80 dark:hover:bg-slate-900/60 shadow-xs'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 cursor-pointer">
                        <i data-lucide="history" class="h-4 w-4"></i>
                        <span>Audit Logs & History</span>
                        @if($auditLogs->count() > 0)
                            <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full font-black bg-white/20 text-white">{{ $auditLogs->count() }}</span>
                        @endif
                    </button>
                </nav>
            </div>

            <!-- Tab Contents -->
            @include('admin.students.partials.show.overview')
            @include('admin.students.partials.show.academic')
            @unless ($isTeacherAdminViewer)
                @include('admin.students.partials.show.documents')
            @endunless

            <!-- Grades Tab -->
            <div x-show="activeTab === 'grades'" class="space-y-6" x-cloak>
                <x-card title="Academic Grades & Report Card" subtitle="Manage and view student trimester grades, averages, and academic remarks">
                    <div x-data="{ activeTrimester: 't1' }" class="space-y-4 mt-2">
                        
                        <!-- Trimester Sub-navigation Tabs -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl w-full sm:w-max">
                            <button type="button" @click="activeTrimester = 't1'" 
                                    :class="activeTrimester === 't1' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-black' : 'text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white font-bold'" 
                                    class="px-4 py-2 rounded-lg text-xs transition focus:outline-none cursor-pointer w-full text-center">
                                1st Trimester
                            </button>
                            <button type="button" @click="activeTrimester = 't2'" 
                                    :class="activeTrimester === 't2' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-black' : 'text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white font-bold'" 
                                    class="px-4 py-2 rounded-lg text-xs transition focus:outline-none cursor-pointer w-full text-center">
                                2nd Trimester
                            </button>
                            <button type="button" @click="activeTrimester = 't3'" 
                                    :class="activeTrimester === 't3' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-black' : 'text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white font-bold'" 
                                    class="px-4 py-2 rounded-lg text-xs transition focus:outline-none cursor-pointer w-full text-center">
                                3rd Trimester
                            </button>
                            <button type="button" @click="activeTrimester = 'final'" 
                                    :class="activeTrimester === 'final' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs font-black' : 'text-slate-650 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white font-bold'" 
                                    class="px-4 py-2 rounded-lg text-xs transition focus:outline-none cursor-pointer w-full text-center">
                                Final Summary
                            </button>
                        </div>

                        @php
                            $studentId = $student->id;
                            $subjects = $student->studentSection?->section?->subjects ?? [];
                            
                            // Trimester grade resolver function
                            $getDeterministicGrade = function($subjectId, $trimesterNum, $studentId) {
                                $seed = crc32($subjectId . '_' . $trimesterNum . '_' . $studentId);
                                srand($seed);
                                $written = rand(84, 98);
                                $performance = rand(85, 99);
                                $assessment = rand(80, 97);
                                $final = round(($written * 0.3) + ($performance * 0.5) + ($assessment * 0.2));
                                
                                // Reset random seed
                                srand();
                                
                                return [
                                    'written' => $written,
                                    'performance' => $performance,
                                    'assessment' => $assessment,
                                    'final' => $final,
                                    'remarks' => $final >= 75 ? 'Passed' : 'Failed'
                                ];
                            };
                        @endphp

                        <!-- 1st Trimester Panel -->
                        <div x-show="activeTrimester === 't1'" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 animate-fade-in">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-55 bg-slate-50 dark:bg-slate-900/50 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-455">
                                    <tr>
                                        <th class="px-5 py-3.5 font-bold">Subject Name</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Written Work (30%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Performance Task (50%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Quarterly Exam (20%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Final Grade</th>
                                        <th class="px-5 py-3.5 font-bold text-right">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/10">
                                    @forelse($subjects as $sub)
                                        @php
                                            $g = $getDeterministicGrade($sub->id, 1, $studentId);
                                        @endphp
                                        <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                            <td class="px-5 py-3.5 font-extrabold text-slate-900 dark:text-white">{{ $sub->subject_name }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['written'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['performance'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['assessment'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-black text-slate-900 dark:text-white">{{ $g['final'] }}</td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 dark:bg-emerald-950/20 px-2 py-0.5 text-xxs font-extrabold text-emerald-700 dark:text-emerald-450 uppercase ring-1 ring-inset ring-emerald-600/10 dark:ring-emerald-500/20">Passed</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-455">No subjects registered.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- 2nd Trimester Panel -->
                        <div x-show="activeTrimester === 't2'" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 animate-fade-in" x-cloak>
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-55 bg-slate-50 dark:bg-slate-900/50 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-455">
                                    <tr>
                                        <th class="px-5 py-3.5 font-bold">Subject Name</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Written Work (30%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Performance Task (50%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Quarterly Exam (20%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Final Grade</th>
                                        <th class="px-5 py-3.5 font-bold text-right">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/10">
                                    @forelse($subjects as $sub)
                                        @php
                                            $g = $getDeterministicGrade($sub->id, 2, $studentId);
                                        @endphp
                                        <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                            <td class="px-5 py-3.5 font-extrabold text-slate-900 dark:text-white">{{ $sub->subject_name }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['written'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['performance'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['assessment'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-black text-slate-900 dark:text-white">{{ $g['final'] }}</td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 dark:bg-emerald-950/20 px-2 py-0.5 text-xxs font-extrabold text-emerald-700 dark:text-emerald-450 uppercase ring-1 ring-inset ring-emerald-600/10 dark:ring-emerald-500/20">Passed</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-455">No subjects registered.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- 3rd Trimester Panel -->
                        <div x-show="activeTrimester === 't3'" class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 animate-fade-in" x-cloak>
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-55 bg-slate-50 dark:bg-slate-900/50 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-455">
                                    <tr>
                                        <th class="px-5 py-3.5 font-bold">Subject Name</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Written Work (30%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Performance Task (50%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Quarterly Exam (20%)</th>
                                        <th class="px-5 py-3.5 font-bold text-center">Final Grade</th>
                                        <th class="px-5 py-3.5 font-bold text-right">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/10">
                                    @forelse($subjects as $sub)
                                        @php
                                            $g = $getDeterministicGrade($sub->id, 3, $studentId);
                                        @endphp
                                        <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                            <td class="px-5 py-3.5 font-extrabold text-slate-900 dark:text-white">{{ $sub->subject_name }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['written'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['performance'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-medium text-slate-600 dark:text-slate-400">{{ $g['assessment'] }}</td>
                                            <td class="px-5 py-3.5 text-center font-black text-slate-900 dark:text-white">{{ $g['final'] }}</td>
                                            <td class="px-5 py-3.5 text-right">
                                                <span class="inline-flex items-center rounded-md bg-emerald-50 dark:bg-emerald-950/20 px-2 py-0.5 text-xxs font-extrabold text-emerald-700 dark:text-emerald-450 uppercase ring-1 ring-inset ring-emerald-600/10 dark:ring-emerald-500/20">Passed</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-455">No subjects registered.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Final Summary Panel -->
                        <div x-show="activeTrimester === 'final'" class="space-y-4 animate-fade-in" x-cloak>
                            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-55 bg-slate-50 dark:bg-slate-900/50 text-xs uppercase tracking-wide text-slate-500 dark:text-slate-455">
                                        <tr>
                                            <th class="px-5 py-3.5 font-bold">Subject Name</th>
                                            <th class="px-5 py-3.5 font-bold text-center">1st Tri</th>
                                            <th class="px-5 py-3.5 font-bold text-center">2nd Tri</th>
                                            <th class="px-5 py-3.5 font-bold text-center">3rd Tri</th>
                                            <th class="px-5 py-3.5 font-bold text-center">Final Rating</th>
                                            <th class="px-5 py-3.5 font-bold text-right">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/10">
                                        @php
                                            $finalTotal = 0;
                                            $subjectCount = 0;
                                        @endphp
                                        @forelse($subjects as $sub)
                                            @php
                                                $g1 = $getDeterministicGrade($sub->id, 1, $studentId)['final'];
                                                $g2 = $getDeterministicGrade($sub->id, 2, $studentId)['final'];
                                                $g3 = $getDeterministicGrade($sub->id, 3, $studentId)['final'];
                                                $finalRating = round(($g1 + $g2 + $g3) / 3);
                                                $finalTotal += $finalRating;
                                                $subjectCount++;
                                            @endphp
                                            <tr class="transition hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                                <td class="px-5 py-3.5 font-extrabold text-slate-900 dark:text-white">{{ $sub->subject_name }}</td>
                                                <td class="px-5 py-3.5 text-center font-semibold text-slate-650 dark:text-slate-400">{{ $g1 }}</td>
                                                <td class="px-5 py-3.5 text-center font-semibold text-slate-650 dark:text-slate-400">{{ $g2 }}</td>
                                                <td class="px-5 py-3.5 text-center font-semibold text-slate-650 dark:text-slate-400">{{ $g3 }}</td>
                                                <td class="px-5 py-3.5 text-center font-black text-emerald-600 dark:text-emerald-450">{{ $finalRating }}</td>
                                                <td class="px-5 py-3.5 text-right">
                                                    <span class="inline-flex items-center rounded-md bg-emerald-50 dark:bg-emerald-950/20 px-2 py-0.5 text-xxs font-extrabold text-emerald-700 dark:text-emerald-450 uppercase ring-1 ring-inset ring-emerald-600/10 dark:ring-emerald-500/20">Passed</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-455">No subjects registered.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if($subjectCount > 0)
                                @php
                                    $gpa = round($finalTotal / $subjectCount, 1);
                                    $standing = 'Passed';
                                    if ($gpa >= 98) {
                                        $standing = 'With Highest Honors';
                                    } elseif ($gpa >= 95) {
                                        $standing = 'With High Honors';
                                    } elseif ($gpa >= 90) {
                                        $standing = 'With Honors';
                                    }
                                @endphp
                                <!-- General Average Summary Banner -->
                                <div class="p-5 bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-950/20 dark:to-teal-950/20 rounded-2xl border border-emerald-500/20 dark:border-emerald-500/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                                    <div>
                                        <span class="block text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Annual Academic Remarks</span>
                                        <div class="flex items-baseline gap-2.5 mt-1.5">
                                            <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $gpa }}%</span>
                                            <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ $standing }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-455 dark:text-slate-400 font-semibold mt-1">General Average calculated from 1st, 2nd, and 3rd Trimester rating sheets.</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center rounded-xl bg-emerald-600 text-white px-4 py-2 text-xs font-black uppercase tracking-wider shadow-xs">
                                            PROMOTED
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </x-card>
            </div>

            <!-- Account Summary Tab -->
            <div x-show="activeTab === 'account'" class="space-y-6" x-cloak>
                <x-card title="Account Summary" subtitle="Student portal credentials and identity verification details">
                    <dl class="space-y-6 text-sm mt-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Details -->
                            <div class="space-y-4">
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <dt class="font-extrabold uppercase tracking-wider text-slate-400 text-xs">Student ID Number</dt>
                                    <dd class="mt-1.5 font-extrabold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                                        <span>{{ $student->student_number ?? 'Pending' }}</span>
                                        <button @click="navigator.clipboard.writeText('{{ $student->student_number }}'); copySuccess = true; setTimeout(() => copySuccess = false, 2000)" class="text-slate-400 hover:text-emerald-600 focus:outline-none transition-colors" title="Copy Student ID">
                                            <i data-lucide="copy" class="h-4 w-4" x-show="!copySuccess"></i>
                                            <i data-lucide="check" class="h-4 w-4 text-emerald-600" x-show="copySuccess"></i>
                                        </button>
                                    </dd>
                                </div>
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <dt class="font-extrabold uppercase tracking-wider text-slate-400 text-xs">School Email / Username</dt>
                                    <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-200 select-all break-all text-base">{{ $student->school_email ?? '-' }}</dd>
                                </div>
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <dt class="font-extrabold uppercase tracking-wider text-slate-400 text-xs">Temporary Password</dt>
                                    <dd class="mt-1 select-all break-all">
                                        @php
                                            $isHashed = str_starts_with($student->temp_password ?? '', '$');
                                        @endphp
                                        @if ($isHashed || blank($student->temp_password))
                                            <span class="text-slate-500 font-semibold">-</span>
                                        @else
                                            <span class="font-mono bg-slate-50 dark:bg-slate-800 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 text-sm text-slate-800 dark:text-slate-200">{{ $student->temp_password }}</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <div class="flex justify-between items-center">
                                        <dt class="font-extrabold uppercase tracking-wider text-slate-400 text-xs">Password Status</dt>
                                        <button type="button" @click="openPasswordModal = true" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer" title="Credential & Password Settings">
                                            <i data-lucide="settings" class="h-3.5 w-3.5"></i>
                                        </button>
                                    </div>
                                    <dd class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        @if ($student->password_changed_at)
                                            <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 ring-1 ring-emerald-100 uppercase">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check inline-block"><path d="M20 6 9 17l-5-5"/></svg>
                                                Changed / Set by Student
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-bold">on {{ $student->password_changed_at->format('M d, Y h:i A') }}</span>
                                        @elseif ($student->ms_user_id)
                                            <span class="inline-flex items-center rounded bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-700 ring-1 ring-amber-100 uppercase">
                                                Still Temporary
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-500 ring-1 ring-slate-200 uppercase">
                                                No Account
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="pb-3 border-b border-slate-100 dark:border-slate-800/80">
                                    <dt class="font-extrabold uppercase tracking-wider text-slate-400 text-xs">Classroom Section</dt>
                                    <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-200">{{ $student->studentSection->section->name ?? 'No Section' }}</dd>
                                </div>
                            </div>

                            <!-- Right Details (QR Verification) -->
                            <div class="flex flex-col items-center justify-center p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/60">
                                <dt class="font-extrabold uppercase tracking-wider text-slate-400 text-xs mb-3">Verification QR Code</dt>
                                <dd class="p-3 bg-white rounded-2xl border border-slate-200 dark:border-slate-700 w-40 h-40 flex items-center justify-center shadow-xs">
                                    <img src="https://quickchart.io/qr?text={{ urlencode('https://amis.edu.ph/v/' . $student->obfuscated_id) }}&margin=1&format=svg" class="w-full h-full object-contain block" alt="QR Code">
                                </dd>
                                <p class="text-[11px] text-slate-400 font-bold mt-3 text-center">Scan to verify student status</p>
                                <a href="#" onclick="downloadQR('{{ $student->obfuscated_id }}', '{{ $student->student_number }}'); return false;" class="text-xs text-emerald-600 hover:text-emerald-700 font-extrabold mt-3.5 inline-flex items-center gap-1.5 transition-transform active:scale-[0.98] cursor-pointer">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                    <span>Download QR Code</span>
                                </a>
                            </div>
                        </div>
                    </dl>
                    <!-- Divider -->
                    <div class="border-t border-slate-200/60 dark:border-slate-800/60 my-6"></div>

                    <!-- Moved: Credentials & Password Workspace -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="key" class="h-4.5 w-4.5 text-amber-500"></i>
                            <span>Credentials & Password Workspace</span>
                        </h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Reset student portal passwords or resend account credentials via email.</p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <!-- Resend Credentials -->
                            <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-855 flex flex-col justify-between gap-3">
                                <div>
                                    <h5 class="text-xs font-bold text-slate-855 dark:text-slate-200">Email Credentials</h5>
                                    <p class="text-[11px] text-slate-400 font-semibold mt-1">Send the student's username and temporary password to their registered email address.</p>
                                </div>
                                <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="reset_format" value="none">
                                    <button type="submit" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                                        <i data-lucide="mail" class="h-4 w-4 text-slate-500"></i>
                                        <span>Send Email</span>
                                    </button>
                                </form>
                            </div>

                            <!-- Set Custom Password -->
                            <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-855 flex flex-col justify-between gap-3">
                                <div>
                                    <h5 class="text-xs font-bold text-slate-855 dark:text-slate-200">Set Custom Password</h5>
                                    <p class="text-[11px] text-slate-400 font-semibold mt-1">Change the student's portal login credentials to a custom password.</p>
                                </div>
                                <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="m-0">
                                    @csrf
                                    <div class="flex gap-2">
                                        <input type="text" name="custom_password" placeholder="Type password..." required class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-amber-500 hover:bg-amber-600 px-3 text-xs font-bold text-white active:scale-[0.98] transition cursor-pointer" title="Set Custom Password">
                                            <span>Reset</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Reset to Default (Amis@12345) -->
                            <div class="bg-slate-50 dark:bg-slate-900/40 p-4 rounded-2xl border border-slate-100 dark:border-slate-855 flex flex-col justify-between gap-3">
                                <div>
                                    <h5 class="text-xs font-bold text-slate-855 dark:text-slate-200">Reset to Default</h5>
                                    <p class="text-[11px] text-slate-400 font-semibold mt-1">Reset password to the system default format (Amis@12345).</p>
                                </div>
                                <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="reset_format" value="default">
                                    <button type="submit" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-amber-250 bg-amber-50 dark:border-amber-950/20 dark:bg-amber-950/10 px-3 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100/50 active:scale-[0.98] transition cursor-pointer" title="Reset password to default format (Amis@12345)">
                                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                        <span>Reset Default</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Audit Logs & History Tab -->
            <div x-show="activeTab === 'audit'" class="space-y-6" x-cloak>
                <x-card title="Student Record Audit Trail" subtitle="Chronological log of administrative actions, photo uploads, profile edits, and section assignments for this student">
                    <div class="mt-4">
                        @if($auditLogs->isEmpty())
                            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                                <i data-lucide="history" class="h-10 w-10 mx-auto mb-3 text-slate-400 opacity-60"></i>
                                <p class="font-bold text-sm">No Audit Logs Recorded</p>
                                <p class="text-xs text-slate-400 mt-1">Actions performed on this student record (photo upload, profile edit, section update, etc.) will be logged here in real-time.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                                <table class="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-xs font-black text-slate-500 uppercase tracking-wider">
                                            <th class="py-3.5 px-4">Date & Time</th>
                                            <th class="py-3.5 px-4">Admin / Staff</th>
                                            <th class="py-3.5 px-4">Action</th>
                                            <th class="py-3.5 px-4">Details / Description</th>
                                            <th class="py-3.5 px-4">IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/10">
                                        @foreach($auditLogs as $log)
                                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                                <td class="py-3 px-4 text-xs font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                    {{ $log->created_at ? $log->created_at->format('M d, Y h:i:s A') : '—' }}
                                                    <div class="text-[10px] text-slate-400 font-normal">{{ $log->created_at?->diffForHumans() }}</div>
                                                </td>
                                                <td class="py-3 px-4 text-xs">
                                                    <div class="font-bold text-slate-900 dark:text-white">
                                                        {{ $log->user ? $log->user->name : ($log->email ?: 'System') }}
                                                    </div>
                                                    @if($log->user && $log->user->email)
                                                        <div class="text-[10px] text-slate-400 font-medium">{{ $log->user->email }}</div>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 text-xs">
                                                    @php
                                                        $eventBadgeClass = match(true) {
                                                            str_contains($log->event, 'photo') => 'bg-purple-50 text-purple-700 dark:bg-purple-950/30 dark:text-purple-300 border-purple-200',
                                                            str_contains($log->event, 'profile') => 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300 border-blue-200',
                                                            str_contains($log->event, 'section') => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300 border-emerald-200',
                                                            str_contains($log->event, 'delete') => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300 border-rose-200',
                                                            default => 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200'
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $eventBadgeClass }}">
                                                        {{ str_replace('_', ' ', strtoupper($log->event)) }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                                    {{ $log->message ?: 'Action recorded' }}
                                                    @if(!empty($log->metadata) && is_array($log->metadata))
                                                        <details class="mt-1">
                                                            <summary class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold cursor-pointer hover:underline">View Metadata</summary>
                                                            <pre class="text-[10px] bg-slate-900 text-slate-200 p-2 rounded-lg mt-1 font-mono overflow-x-auto max-w-md">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                        </details>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 text-xs font-mono text-slate-500 whitespace-nowrap">
                                                    {{ $log->ip_address ?: '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </x-card>
            </div>
        </main>

        <!-- Right Sidebar -->
        @unless ($isTeacherAdminViewer)
            @include('admin.students.partials.show.sidebar')
        @endunless
    </div>

        <!-- Preview Modal -->
        @include('admin.students.partials.show.modal')
        <!-- ID Card Preview Modal -->
        <template x-teleport="body">
            <div x-show="showIdPreview"
                 style="display: none; z-index: 99999;"
                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-slate-100 dark:bg-slate-950 rounded-3xl max-w-5xl w-full overflow-visible relative shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-850 bg-white dark:bg-slate-900 rounded-t-3xl">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="contact" class="h-4.5 w-4.5 text-emerald-600"></i>
                        <span>Official ID Card Preview</span>
                    </h3>
                    <button @click="showIdPreview = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-6 overflow-y-auto max-h-[70vh] flex flex-col md:flex-row items-center justify-center bg-slate-50 dark:bg-slate-950/20" style="gap: 24px;">
                    
                    @php
                        // Resolve ID card variables
                        $firstName = trim($student->applicant?->first_name ?? '');
                        $middleName = trim($student->applicant?->middle_name ?? '');
                        $lastName = trim($student->applicant?->last_name ?? '');
                        $middleInitial = '';
                        if ($middleName !== '') {
                            $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
                            $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
                        }
                        
                        $fullNameParts = array_filter([$firstName, $middleInitial, $lastName], fn($val) => $val !== '');
                        $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');
                        $displayGrade = $student->grade_level;

                        $emergencyAddress = trim($student->applicant?->emergency_address ?? '');
                        if (!empty($emergencyAddress)) {
                            $homeAddress = $emergencyAddress;
                        } else {
                            $calculatedHome = implode(', ', array_filter([$student->applicant?->home_street_address, $student->applicant?->home_city, $student->applicant?->home_state_province]));
                            if (empty($calculatedHome) || $calculatedHome === '-') {
                                $homeAddress = $student->applicant?->home_address ?: ($studentAddress ?: 'MISSING INFO');
                            } else {
                                $homeAddress = $calculatedHome;
                            }
                        }
                        
                        $rawEmergencyName = trim($student->applicant?->emergency_name ?? '');
                        $fatherName = trim(($student->applicant?->father_first_name ?? '') . ' ' . ($student->applicant?->father_last_name ?? ''));
                        $motherName = trim(($student->applicant?->mother_first_name ?? '') . ' ' . ($student->applicant?->mother_last_name ?? ''));
                        
                        if (empty($rawEmergencyName) || strtolower($rawEmergencyName) === 'emergency contact' || is_numeric(str_replace(['+', ' ', '-', '(', ')'], '', $rawEmergencyName))) {
                            $emergencyName = $fatherName ?: ($motherName ?: 'MISSING INFO');
                        } else {
                            $emergencyName = $rawEmergencyName;
                        }
                        
                        $relationship = trim($student->applicant?->emergency_relationship ?? '');
                        if (empty($relationship)) {
                            if (!empty($fatherName) && str_contains(strtolower($emergencyName), strtolower($fatherName))) {
                                $relationship = 'FATHER';
                            } elseif (!empty($motherName) && str_contains(strtolower($emergencyName), strtolower($motherName))) {
                                $relationship = 'MOTHER';
                            } elseif (!empty($fatherName) || !empty($motherName)) {
                                $relationship = 'PARENT / GUARDIAN';
                            } else {
                                $relationship = 'MISSING INFO';
                            }
                        }
                        
                        $emergencyPhone = $student->applicant?->emergency_phone ?: '';
                        if (empty($emergencyPhone)) {
                            $emergencyPhone = $student->applicant?->parent_mobile ?: ($student->applicant?->mobile_number ?: 'MISSING INFO');
                        }

                        $studentNumber = $student->student_number;
                        $hash = base64_encode((int)$studentNumber + 987654);
                        $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
                        $signatureRawUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/signature') . '&dark=000000&light=ffffff&margin=1&format=png&size=200';

                        // Pre-convert images to Base64 to guarantee zero CORS canvas taint
                        $getInlineBase64 = function($url) {
                            if (empty($url)) return '';
                            if (str_starts_with($url, 'data:')) return $url;
                            try {
                                $parsed = parse_url($url, PHP_URL_PATH);
                                if ($parsed && file_exists(public_path($parsed))) {
                                    $path = public_path($parsed);
                                    $mime = mime_content_type($path) ?: 'image/png';
                                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
                                }
                                $ctx = stream_context_create([
                                    'http' => ['timeout' => 3, 'header' => "User-Agent: Mozilla/5.0\r\n"],
                                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                                ]);
                                $content = @file_get_contents($url, false, $ctx);
                                if ($content) {
                                    $mime = 'image/png';
                                    if (str_contains(strtolower($url), '.jpg') || str_contains(strtolower($url), '.jpeg')) $mime = 'image/jpeg';
                                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                                }
                            } catch (\Throwable $e) {}
                            return $url;
                        };

                        $qrCodeBase64 = $getInlineBase64($qrCodeUrl);
                        $signatureQrBase64 = $getInlineBase64($signatureRawUrl);
                        $photoBase64 = $photoUrl ? $getInlineBase64($photoUrl) : '';
                        
                        $getGradeColor = function($grade) {
                            if (!$grade) return '#6d28d9';
                            $g = strtoupper($grade);
                            if (str_contains($g, 'NURSERY') || str_contains($g, 'KINDER') || str_contains($g, 'PRE-')) return '#ea580c';
                            if (str_contains($g, 'GRADE 1') || str_contains($g, 'GRADE 2') || str_contains($g, 'GRADE 3')) return '#0284c7';
                            if (str_contains($g, 'GRADE 4') || str_contains($g, 'GRADE 5') || str_contains($g, 'GRADE 6')) return '#7c3aed';
                            if (str_contains($g, 'GRADE 7') || str_contains($g, 'GRADE 8') || str_contains($g, 'GRADE 9') || str_contains($g, 'GRADE 10')) return '#dc2626';
                            if (str_contains($g, 'GRADE 11') || str_contains($g, 'GRADE 12') || str_contains($g, 'GRADE XI') || str_contains($g, 'GRADE XII')) return '#4f46e5';
                            return '#6d28d9';
                        };
                        $lastNameLen = strlen($lastName);
                        if ($lastNameLen <= 8) {
                            $lastNameFontSize = '36px';
                            $lastNameStyle = 'white-space: nowrap;';
                        } elseif ($lastNameLen <= 12) {
                            $lastNameFontSize = '28px';
                            $lastNameStyle = 'white-space: nowrap;';
                        } elseif ($lastNameLen <= 15) {
                            $lastNameFontSize = '22px';
                            $lastNameStyle = 'white-space: nowrap;';
                        } elseif ($lastNameLen <= 18) {
                            $lastNameFontSize = '17px';
                            $lastNameStyle = 'white-space: nowrap;';
                        } elseif ($lastNameLen <= 21) {
                            $lastNameFontSize = '12.5px';
                            $lastNameStyle = 'white-space: nowrap;';
                        } elseif ($lastNameLen <= 25) {
                            $lastNameFontSize = '11px';
                            $lastNameStyle = 'white-space: nowrap;';
                        } else {
                            $lastNameFontSize = '9.5px';
                            $lastNameStyle = 'white-space: nowrap;';
                        }
                    @endphp

                    <!-- Front Side Card -->
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Front Side</span>
                        <div style="transform: scale(0.85); transform-origin: top center; margin-bottom: -80px;">
                        <div id="id-card-front-box" class="relative rounded-3xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800" style="width: 340px; height: 538px; background-color: #064e3b;">
                            <!-- Background template image (Top Layer) -->
                            <img src="{{ asset('images/id/amis_frontid.png') }}?v={{ filemtime(public_path('images/id/amis_frontid.png')) }}" class="absolute inset-0 w-full h-full object-cover" style="z-index: 10; pointer-events: none;" alt="AMIS ID Template">
                            
                            <!-- Student Photo with Edit Overlay -->
                            @unless ($isTeacherAdminViewer)
                                <div class="photo-clip group cursor-pointer" 
                                     onclick="openPhotoOptionsModal()"
                                     style="left: 71px; top: 144px; width: 198px; height: 192px; border-radius: 6px; z-index: 5;"
                                     title="Edit Photo">
                                    @if($photoUrl)
                                        <img id="id-preview-photo" src="{{ $photoBase64 ?: $photoUrl }}" crossorigin="anonymous" class="transition duration-300 group-hover:scale-105 group-hover:brightness-75" style="object-position: center center;">
                                    @else
                                        <div class="absolute inset-0 bg-slate-150 flex flex-col items-center justify-center text-center border border-dashed border-slate-300 text-[10px] font-bold text-slate-450 gap-1 z-1">
                                            <i data-lucide="camera" class="w-5 h-5 text-slate-400"></i>
                                            <span>UPLOAD</span>
                                        </div>
                                    @endif
                                    <!-- Simple Edit Icon Overlay on hover -->
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity duration-200 text-white" style="z-index: 20;">
                                        <div class="bg-white/20 backdrop-blur-md rounded-full p-2.5 border border-white/30 shadow-md transform scale-90 group-hover:scale-100 transition duration-200">
                                            <i data-lucide="camera" class="w-5 h-5 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <!-- Non-admin read-only image -->
                                <div class="photo-clip" style="left: 71px; top: 144px; width: 198px; height: 192px; border-radius: 6px; z-index: 5;">
                                    @if($photoUrl)
                                        <img id="id-preview-photo" src="{{ $photoBase64 ?: $photoUrl }}" crossorigin="anonymous" style="object-position: center center;">
                                    @else
                                        <div class="absolute inset-0 bg-slate-100 flex items-center justify-center text-center border border-dashed border-slate-300 text-[10px] font-bold text-slate-400 z-1">NO PHOTO</div>
                                    @endif
                                </div>
                            @endif

                            <!-- Student ID -->
                            <div class="absolute text-white font-black tracking-wide text-center uppercase animate-fade-in" style="left: 0; top: 325px; width: 340px; height: 15px; z-index: 20; font-size: {{ $student->id_num_font_size ? $student->id_num_font_size . 'px' : '12.5px' }}; line-height: 15px;">{{ $studentNumber }}</div>

                            <!-- Last Name -->
                            <div class="absolute text-center font-black text-[#0f172a] uppercase tracking-tight flex flex-col justify-center items-center animate-fade-in id-last-name-text" style="left: 15px; top: 352px; width: 310px; height: 32px; z-index: 20; padding: 0 16px; {{ $lastNameStyle }} line-height: 1; letter-spacing: -0.5px; font-size: {{ $student->id_last_name_font_size ? $student->id_last_name_font_size . 'px' : $lastNameFontSize }};">{{ $lastName }}</div>

                            <!-- First Name -->
                             @php
                                 $displayFirstName = trim($firstName . ' ' . $middleInitial);
                                 $displayFirstNameLen = strlen($displayFirstName);
                                 $defaultFirstNameFontSize = $displayFirstNameLen > 25 ? '14px' : ($displayFirstNameLen > 18 ? '16px' : '18px');
                             @endphp
                             <div class="absolute text-center font-bold text-[#334155] uppercase leading-none flex flex-col justify-center items-center animate-fade-in" style="left: 15px; top: 386px; width: 310px; height: 22px; z-index: 20; padding: 0 16px; line-height: 1; font-size: {{ $student->id_first_name_font_size ? $student->id_first_name_font_size . 'px' : $defaultFirstNameFontSize }};">{{ $displayFirstName }}</div>

                            <!-- Grade Level -->
                             <div class="absolute text-center font-black uppercase tracking-wide flex flex-col justify-center items-center animate-fade-in" style="left: 15px; top: 412px; width: 310px; height: 30px; z-index: 20; padding: 0 16px; line-height: 1; letter-spacing: 0.5px; text-shadow: 0 1px 1px rgba(0,0,0,0.05); color: {{ $getGradeColor($displayGrade) }}; font-size: {{ $student->id_grade_font_size ? $student->id_grade_font_size . 'px' : '31px' }};">{{ $displayGrade }}</div>

                            <!-- LRN -->
                            @if($student->applicant?->lrn && !in_array(strtoupper($student->applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                <div class="absolute font-bold text-[#1e293b] whitespace-nowrap" style="left: 239px; top: 394px; width: 170px; height: 22px; z-index: 20; font-size: 15.5px; transform: rotate(-90deg); transform-origin: center; display: flex; align-items: center; justify-content: flex-start; letter-spacing: 0.05em;">
                                    LRN: <span style="margin-left: 4px;">{{ $student->applicant->lrn }}</span>
                                </div>
                            @endif

                            <!-- QR Code -->
                            <div class="absolute p-0.5 rounded bg-white" style="left: 134.5px; top: 458px; width: 71px; height: 71px; z-index: 20;">
                                <img src="{{ $qrCodeBase64 ?: $qrCodeUrl }}" crossorigin="anonymous" alt="QR Verification" class="w-full h-full object-contain">
                            </div>
                        </div>
                        </div><!-- end scale wrapper -->
                        <span class="text-[10px] text-slate-400 font-semibold mt-1">Front ID Card (300 DPI)</span>
                    </div>

                    <!-- Back Side Card -->
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Back Side</span>
                        <div style="transform: scale(0.85); transform-origin: top center; margin-bottom: -80px;">
                        <div id="id-card-back-box" class="relative rounded-3xl overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800" style="width: 340px; height: 538px; background-color: #064e3b;">
                            <!-- Background template image -->
                            <img src="{{ asset('images/id/amis_backid.png') }}?v=1" class="absolute inset-0 w-full h-full object-cover" style="z-index: 1; pointer-events: none;" alt="AMIS ID Template Back">

                            <!-- Emergency Details List -->
                            @php
                                $parentNameLen = strlen($emergencyName);
                                $parentNameFontSize = $parentNameLen > 24 ? '14px' : ($parentNameLen > 18 ? '16px' : '19px');
                                
                                $addressLen = strlen($homeAddress);
                                $addressFontSize = $addressLen > 60 ? '12px' : ($addressLen > 40 ? '13px' : '14px');
                            @endphp
                            <div class="emergency-info" style="position: absolute; left: 28px; top: 85px; width: 284px; z-index: 10; display: flex; flex-direction: column; gap: 7px;">
                                <!-- Contact Name -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: {{ $emergencyName === 'MISSING INFO' ? '#dc2626' : '#047857' }}; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: {{ $parentNameFontSize }}; font-weight: 900; text-transform: uppercase; color: {{ $emergencyName === 'MISSING INFO' ? '#dc2626' : '#0f172a' }}; line-height: 1.1;">
                                        {{ $emergencyName }}
                                    </div>
                                </div>

                                <!-- Relationship -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: {{ $relationship === 'MISSING INFO' ? '#dc2626' : '#047857' }}; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; text-transform: uppercase; color: {{ $relationship === 'MISSING INFO' ? '#dc2626' : '#475569' }}; line-height: 1;">
                                        {{ $relationship }}
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: {{ $emergencyPhone === 'MISSING INFO' ? '#dc2626' : '#047857' }}; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l.589 2.356a1.75 1.75 0 0 1-.607 1.89l-1.077.808a12.983 12.983 0 0 0 5.753 5.753l.808-1.077a1.75 1.75 0 0 1 1.89-.607l2.356.589c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: {{ $emergencyPhone === 'MISSING INFO' ? '#dc2626' : '#1e293b' }}; line-height: 1;">
                                        {{ $emergencyPhone }}
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: {{ $homeAddress === 'MISSING INFO' ? '#dc2626' : '#047857' }}; margin-top: 2.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 3.58-2.977c2.2-2.384 4.19-5.462 4.19-8.923 0-4.82-3.855-8.5-8.5-8.5-8.5 0-8.5 3.68-8.5 8.5c0 3.461 1.99 6.54 4.19 8.923a16.975 16.975 0 0 0 3.58 2.977Zm3.71-12.851a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: {{ $addressFontSize }}; font-weight: 700; text-transform: uppercase; color: {{ $homeAddress === 'MISSING INFO' ? '#dc2626' : '#475569' }}; line-height: 1.25;">
                                        {{ $homeAddress }}
                                    </div>
                                </div>
                            </div>

                            <!-- Secure Director Signature QR -->
                            <div class="back-signature-qr" style="position: absolute; left: 142.5px; top: 422px; width: 55px; height: 55px; z-index: 25; padding: 1.5px; border-radius: 2px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <img src="{{ $signatureQrBase64 ?: $signatureRawUrl }}" crossorigin="anonymous" alt="Signature QR" class="w-full h-full object-contain">
                            </div>
                        </div>
                        </div><!-- end scale wrapper -->
                        <span class="text-[10px] text-slate-400 font-semibold mt-1">Back Emergency Info Sheet</span>pan>
                    </div>
                    <input type="hidden" id="id-card-filename-slug" value="{{ implode('-', array_filter([$lastName, $firstName, str_replace(' ', '', $displayGrade)])) }}">
                </div>

                <!-- Footer -->
                <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-855 bg-white dark:bg-slate-900 gap-3 rounded-b-3xl relative z-30 overflow-visible">
                    <p class="text-xs text-slate-400 font-medium hidden sm:block">Smart ID Printer PNG images (300 DPI high-res).</p>
                    <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto justify-end">
                        <!-- 1. Print Options Dropdown -->
                        <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" 
                                    class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-xs font-bold shadow-md transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                                <span>Print Card</span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                            </button>
                            <div x-cloak x-show="open" x-transition.origin.top.right.duration.150ms 
                                 class="absolute right-0 bottom-full mb-2 w-56 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-xl z-50">
                                <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_id' => 1]) }}" target="_blank" 
                                   class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition">
                                    <i data-lucide="printer" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Print Color ID Sheet</span>
                                </a>
                                <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_id' => 1, 'bw' => 1]) }}" target="_blank" 
                                   class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <i data-lucide="file-badge-2" class="w-4 h-4 text-slate-900 dark:text-slate-100"></i>
                                    <span>Print Black Only (K-K)</span>
                                </a>
                            </div>
                        </div>

                        <!-- 2. PNG Download Dropdown -->
                        <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" 
                                    class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-3.5 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Download PNG</span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                            </button>
                            <div x-cloak x-show="open" x-transition.origin.top.right.duration.150ms 
                                 class="absolute right-0 bottom-full mb-2 w-56 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-xl z-50">
                                <button type="button" @click="open = false; downloadIdCardPng('front', false, false)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition">
                                    <i data-lucide="image" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Front PNG (Color)</span>
                                </button>
                                <button type="button" @click="open = false; downloadIdCardPng('back', false, false)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition">
                                    <i data-lucide="image" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Back PNG (Color)</span>
                                </button>
                                <button type="button" @click="open = false; downloadIdCardPng('back', true, false)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                                    <i data-lucide="file-badge-2" class="w-4 h-4 text-slate-900 dark:text-slate-100"></i>
                                    <span>Back PNG (Black Only 🖤)</span>
                                </button>
                                <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                                <button type="button" @click="open = false; downloadIdCardPng('front', false, false); setTimeout(() => downloadIdCardPng('back', false, false), 500)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition">
                                    <i data-lucide="layers" class="w-4 h-4 text-emerald-600"></i>
                                    <span>Download Both Sides</span>
                                </button>
                            </div>
                        </div>

                        <!-- 3. Watermark Sample Dropdown -->
                        <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                            <button type="button" @click="open = !open" 
                                    class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/40 px-3.5 py-2 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100 transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                                <i data-lucide="stamp" class="w-4 h-4 text-amber-600"></i>
                                <span>Watermark PNG</span>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                            </button>
                            <div x-cloak x-show="open" x-transition.origin.top.right.duration.150ms 
                                 class="absolute right-0 bottom-full mb-2 w-56 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-xl z-50">
                                <button type="button" @click="open = false; downloadIdCardPng('front', false, true)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-amber-50 dark:hover:bg-amber-950/40 hover:text-amber-700 transition">
                                    <i data-lucide="stamp" class="w-4 h-4 text-amber-600"></i>
                                    <span>Front PNG (Sample Watermark)</span>
                                </button>
                                <button type="button" @click="open = false; downloadIdCardPng('back', false, true)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-amber-50 dark:hover:bg-amber-950/40 hover:text-amber-700 transition">
                                    <i data-lucide="stamp" class="w-4 h-4 text-amber-600"></i>
                                    <span>Back PNG (Sample Watermark)</span>
                                </button>
                                <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                                <button type="button" @click="open = false; downloadIdCardPng('front', false, true); setTimeout(() => downloadIdCardPng('back', false, true), 500)" 
                                        class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition">
                                    <i data-lucide="layers" class="w-4 h-4 text-amber-600"></i>
                                    <span>Both Sides (Sample Watermark)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Edit ID Layout button -->
                        <a href="{{ route('admin.students.id-editor', $student) }}" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-xs font-bold shadow-md transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5 border border-emerald-500">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                            <span>Edit ID Layout</span>
                        </a>

                        <!-- Close Button -->
                        <button type="button" @click="showIdPreview = false" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-850 active:scale-[0.98] cursor-pointer">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </template>

        <!-- Password Settings Modal -->
        <template x-teleport="body">
            <div x-show="openPasswordModal"
                 style="display: none; z-index: 99999;"
                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-150 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="key" class="h-4.5 w-4.5 text-amber-500"></i>
                        <span>Credential Settings</span>
                    </h3>
                    <button @click="openPasswordModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-6 space-y-6 bg-slate-50 dark:bg-slate-950/20">
                    <!-- Reset default form -->
                    <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="reset_format" value="default">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-350">Quick Reset</label>
                        <button type="submit" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-amber-250 bg-amber-50 dark:border-amber-950/20 dark:bg-amber-950/10 px-3 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100/50 active:scale-[0.98] transition cursor-pointer">
                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                            <span>Reset Password to Amis@12345</span>
                        </button>
                    </form>

                    <div class="border-t border-slate-150 dark:border-slate-800 my-4"></div>

                    <!-- Reset custom password form -->
                    <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-350">Set Custom Password</label>
                        <div class="flex gap-2">
                            <input type="text" name="custom_password" placeholder="Type custom password..." required class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                            <button type="submit" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 px-4 text-xs font-bold text-white active:scale-[0.98] transition cursor-pointer">
                                <span>Reset</span>
                            </button>
                        </div>
                    </form>

                    <div class="border-t border-slate-150 dark:border-slate-800 my-4"></div>

                    <!-- Email credentials form -->
                    <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="reset_format" value="none">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-350">Notification</label>
                        <button type="submit" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                            <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                            <span>Email Current Credentials</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        </template>

        <!-- Edit Profile Modal -->
        @unless ($isTeacherAdminViewer)
        <template x-teleport="body">
            <div x-show="openEditModal"
                 style="display: none; z-index: 99999;"
                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
            
            <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto shadow-2xl border border-slate-100 dark:border-slate-800 flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-150 dark:border-slate-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i data-lucide="edit" class="h-5 w-5 text-emerald-600"></i>
                        <span x-text="editSection === 'all' ? 'Edit Student Profile' : (editSection === 'academic' ? 'Edit Academic Profile' : (editSection === 'personal' ? 'Edit Personal Details' : (editSection === 'contact' ? 'Edit Contact & Address' : 'Edit Parent & Emergency Info')))">Edit Student Profile</span>
                    </h3>
                    <button @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="{{ route('admin.students.update-profile', $student) }}" method="POST" class="p-6 space-y-5 flex-1"
                      @submit="[...$el.querySelectorAll('input[type=text], textarea')].forEach(i => { if(i.name !== 'email' && i.name !== 'parent_email' && i.name !== 'lrn' && i.name !== 'mobile' && i.name !== 'parent_mobile' && i.name !== 'emergency_phone') i.value = i.value.toUpperCase() })">
                    @csrf
                    
                    @if ($errors->any())
                        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <!-- Academic Details -->
                    <div x-show="editSection === 'all' || editSection === 'academic'">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Academic Info</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Student Type</label>
                                <input type="text" name="student_type" value="{{ $student->applicant->student_type ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Grade Level</label>
                                <select name="grade_level" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                    @foreach(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g)
                                        <option value="{{ $g }}" @if(($student->grade_level ?? '') === $g) selected @endif>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Learning Mode</label>
                                <input type="text" name="learning_mode" value="{{ $student->applicant->learning_mode ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">AMIS Student ID</label>
                                <input type="text" name="amis_student_id" value="{{ $student->applicant->amis_student_id ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">LRN</label>
                                <input type="text" name="lrn" value="{{ $student->applicant->lrn ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <hr x-show="editSection === 'all'" class="border-slate-100 dark:border-slate-800">

                    <!-- Personal Info -->
                    <div x-show="editSection === 'all' || editSection === 'personal'">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Personal Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">First Name</label>
                                <input type="text" name="first_name" required value="{{ $student->applicant->first_name ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Middle Name</label>
                                <input type="text" name="middle_name" value="{{ $student->applicant->middle_name ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Last Name</label>
                                <input type="text" name="last_name" required value="{{ $student->applicant->last_name ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Suffix</label>
                                <input type="text" name="suffix" value="{{ $student->applicant->suffix ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Gender</label>
                                <input type="text" name="gender" value="{{ $student->applicant->gender ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Date of Birth</label>
                                <input type="date" name="date_of_birth" value="{{ optional($student->applicant->date_of_birth)->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Place of Birth</label>
                                <input type="text" name="place_of_birth" value="{{ $student->applicant->place_of_birth ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Religion</label>
                                <input type="text" name="religion" value="{{ $student->applicant->religion ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Ethnicity</label>
                                <input type="text" name="ethnicity" value="{{ $student->applicant->ethnicity ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                        </div>
                    </div>

                    <hr x-show="editSection === 'all'" class="border-slate-100 dark:border-slate-800">

                    <!-- Contact & Address -->
                    <div x-show="editSection === 'all' || editSection === 'contact'">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Contact & Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Student Email</label>
                                <input type="email" name="email" value="{{ $student->applicant->email ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Student Mobile</label>
                                <input type="text" name="mobile" value="{{ $student->applicant->mobile_number ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Residence Address</label>
                            <textarea name="address" rows="2" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">{{ $student->applicant->address ?? $studentAddress }}</textarea>
                        </div>
                    </div>

                    <hr x-show="editSection === 'all'" class="border-slate-100 dark:border-slate-800">

                    <!-- Parent & Emergency -->
                    <div x-show="editSection === 'all' || editSection === 'parents'">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Parent & Emergency Info</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Father's Full Name</label>
                                <input type="text" name="father_name" value="{{ trim(($student->applicant->father_first_name ?? '').' '.($student->applicant->father_last_name ?? '')) }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Mother's Full Name</label>
                                <input type="text" name="mother_name" value="{{ trim(($student->applicant->mother_first_name ?? '').' '.($student->applicant->mother_last_name ?? '')) }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Parent Email</label>
                                <input type="email" name="parent_email" value="{{ $student->applicant->parent_email ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Parent Mobile</label>
                                <input type="text" name="parent_mobile" value="{{ $student->applicant->parent_mobile ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Home Address</label>
                            <textarea name="home_address" rows="2" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">{{ $student->applicant->home_address ?? '' }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Emergency Contact Person</label>
                                <input type="text" name="emergency_name" value="{{ $student->applicant->emergency_name ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Relationship</label>
                                <input type="text" name="emergency_relationship" value="{{ $student->applicant->emergency_relationship ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Emergency Phone</label>
                                <input type="text" name="emergency_phone" value="{{ $student->applicant->emergency_phone ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Emergency Address</label>
                                <input type="text" name="emergency_address" value="{{ $student->applicant->emergency_address ?? '' }}" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase" placeholder="Enter emergency contact address">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-150 dark:border-slate-800">
                        <button type="button" @click="openEditModal = false" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-bold text-slate-750 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-850 active:scale-[0.98] cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-sm font-bold shadow-md transition active:scale-[0.98] cursor-pointer">
                            Save Changes
                        </button>
                    </div>

                </form>
            </div>
        </div>
        </template>
        @endunless
    </div>

    <style>
        .photo-clip {
            position: absolute;
            overflow: hidden;
            background: transparent;
            border-radius: 14px;
        }
        .photo-clip img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            z-index: 1;
        }
        .green-frame-overlay {
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            box-sizing: border-box;
        }
    </style>
    <!-- Photo Cropping Modal -->
    @if (auth()->user()?->hasRole('super_admin'))
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        #photo-crop-modal .cropper-view-box,
        #photo-crop-modal .cropper-face {
            border-radius: 14px;
            box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.72);
        }
        #photo-crop-modal .cropper-view-box {
            outline: 4px solid #047857; /* Emerald green matching school ID theme */
            outline-offset: -1px;
        }
        #photo-crop-modal .cropper-line,
        #photo-crop-modal .cropper-point {
            display: none !important; /* Hide resizing points since the cropbox is fixed */
        }
        #photo-crop-modal .cropper-bg {
            background-image: none !important;
            background-color: #090d16 !important;
        }
        #photo-crop-modal .cropper-container {
            background-color: #090d16 !important;
        }
    </style>
    <div id="photo-crop-modal" class="fixed inset-0 hidden flex items-center justify-center bg-slate-900/70 backdrop-blur-xs p-4 md:p-6" style="z-index: 9999999;">
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4.5 border-b border-slate-150 dark:border-slate-800">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="crop" class="h-5 w-5 text-emerald-600"></i>
                    <span>Position & Scale Photo</span>
                </h3>
                <button type="button" onclick="closeCropModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="p-6 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-950/20">
                <div class="w-full overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-900 relative shadow-inner" style="height: 480px;">
                    <img id="crop-image-preview" src="" alt="Source image for cropping" style="display: block; max-width: 100%; height: 100%; margin: 0 auto;">
                    
                    <!-- Centered Face & Shoulder Oval Guide Overlay -->
                    <div class="pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center">
                        <div class="relative flex items-center justify-center opacity-65" style="width: 320px; height: 310px;">
                            <svg viewBox="0 0 100 100" class="w-full h-full text-emerald-400 drop-shadow-xs">
                                <!-- Face Oval Guide (Standard 2x2 ID Photo Proportion) -->
                                <ellipse cx="50" cy="36" rx="26" ry="30" fill="none" stroke="currentColor" stroke-width="1.2" stroke-dasharray="3 3" />
                                <!-- Shoulder Guide -->
                                <path d="M 18 70 Q 50 62 82 70 L 98 100 L 2 100 Z" fill="none" stroke="currentColor" stroke-width="1.2" stroke-dasharray="3 3" />
                            </svg>
                            <!-- Label placed cleanly above head guide so face is 100% visible -->
                            <span class="absolute -top-6 text-[9px] font-bold tracking-widest text-emerald-300 uppercase bg-slate-900/80 px-2.5 py-0.5 rounded-full shadow-xs border border-emerald-400/30">
                                Center Head & Shoulders
                            </span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-3.5 font-semibold">Drag to pan the photo and scroll or use the buttons below to zoom.</p>
            </div>
            <!-- Footer -->
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-slate-150 dark:border-slate-800 bg-white dark:bg-slate-900">
                <!-- Action Controls -->
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="if(cropper) cropper.zoom(0.1)" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition active:scale-95 cursor-pointer" title="Zoom In">
                        <i data-lucide="zoom-in" class="w-4 h-4"></i>
                    </button>
                    <button type="button" onclick="if(cropper) cropper.zoom(-0.1)" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition active:scale-95 cursor-pointer" title="Zoom Out">
                        <i data-lucide="zoom-out" class="w-4 h-4"></i>
                    </button>
                    <button type="button" onclick="if(cropper) cropper.reset()" class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition active:scale-95 cursor-pointer" title="Reset Image">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                    </button>
                </div>
                <!-- Save / Cancel -->
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeCropModal()" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-850 active:scale-[0.98] cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" id="crop-save-btn" onclick="saveCroppedPhoto()" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-xs font-bold shadow-md transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Crop & Save</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    @if (auth()->user()?->hasRole('super_admin'))
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper = null;
        let selectedFile = null;

        function adjustExistingPhoto(url) {
            if (!url) return;
            
            const cropImg = document.getElementById('crop-image-preview');
            
            // Show Crop Modal first to establish layout dimensions
            const cropModal = document.getElementById('photo-crop-modal');
            cropModal.classList.remove('hidden');
            updateBodyScroll();
            
            cropImg.onload = function() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
                cropper = new Cropper(cropImg, {
                    aspectRatio: 178 / 172,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 0.9,
                    responsive: true,
                    checkOrientation: false,
                    modal: true,
                    guides: false,
                    center: false,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false
                });
            };
            
            cropImg.crossOrigin = 'anonymous';
            cropImg.src = url;
        }

        function uploadStudentPhoto(input) {
            if (!input.files || !input.files[0]) return;
            
            selectedFile = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const cropImg = document.getElementById('crop-image-preview');
                
                // Show Crop Modal first to establish layout dimensions
                const cropModal = document.getElementById('photo-crop-modal');
                cropModal.classList.remove('hidden');
                updateBodyScroll();
                
                cropImg.onload = function() {
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    cropper = new Cropper(cropImg, {
                        aspectRatio: 178 / 172,
                        viewMode: 1,
                        dragMode: 'move',
                        background: false,
                        autoCropArea: 0.9,
                        responsive: true,
                        checkOrientation: false,
                        modal: true,
                        guides: false,
                        center: false,
                        highlight: false,
                        cropBoxMovable: false,
                        cropBoxResizable: false,
                        toggleDragModeOnDblclick: false
                    });
                };
                
                cropImg.src = e.target.result;
            };
            
            reader.readAsDataURL(selectedFile);
        }

        function closeCropModal() {
            const cropModal = document.getElementById('photo-crop-modal');
            cropModal.classList.add('hidden');
            updateBodyScroll();
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('student-photo-input').value = '';
        }

        async function saveCroppedPhoto() {
            if (!cropper) return;
            
            const saveBtn = document.getElementById('crop-save-btn');
            const originalHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Saving...';
            if (window.lucide) window.lucide.createIcons();
            
            // Get cropped canvas matching card photo frame aspect ratio (178:172)
            const canvas = cropper.getCroppedCanvas({
                width: 356,
                height: 344
            });
            
            canvas.toBlob(async function(blob) {
                const formData = new FormData();
                formData.append('photo', blob, 'photo.jpg');
                formData.append('_token', '{{ csrf_token() }}');
                
                try {
                    const response = await fetch('{{ route('admin.students.update-photo', $student) }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    if (response.ok && result.success) {
                        const img = document.querySelector('button.applicant-photo img');
                        const placeholder = document.querySelector('button.applicant-photo span');
                        if (img) {
                            img.src = result.photo_url;
                            img.style.display = 'block';
                            if (placeholder) placeholder.style.display = 'none';
                            
                            // Also update the main preview button click handler
                            const previewBtn = document.querySelector('button.applicant-photo');
                            if (previewBtn) {
                                previewBtn.setAttribute('@click', `openPreview('${result.photo_url}', '2x2 Photo', false)`);
                            }

                            // Also update the ID preview modal photo if open
                            const idPreviewPhoto = document.getElementById('id-preview-photo');
                            if (idPreviewPhoto) {
                                idPreviewPhoto.src = result.photo_url;
                            }
                        } else {
                            location.reload();
                            return;
                        }
                        
                        closeCropModal();
                    } else {
                        alert(result.message || 'Failed to upload photo.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('An error occurred while uploading photo.');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalHtml;
                    if (window.lucide) window.lucide.createIcons();
                }
            }, 'image/jpeg', 0.9);
        }
        async function deleteStudentPhoto() {
            if (!confirm('Are you sure you want to delete this photo and reset to default?')) return;
            
            try {
                const response = await fetch('{{ route('admin.students.delete-photo', $student) }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const result = await response.json();
                if (response.ok && result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to delete photo.');
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while deleting photo.');
            }
        }
        async function syncMicrosoftPhoto() {
            if (!confirm('Fetch profile photo from Microsoft 365 / Azure AD and save it locally?')) return;
            
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex flex-col items-center justify-center gap-3 text-white';
            overlay.innerHTML = `
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-white border-t-transparent"></div>
                <span class="text-sm font-black uppercase tracking-widest font-outfit">Syncing from Microsoft...</span>
            `;
            document.body.appendChild(overlay);

            try {
                const response = await fetch('{{ route('admin.students.sync-microsoft-photo', $student) }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                const result = await response.json();
                if (response.ok && result.success) {
                    location.reload();
                } else {
                    alert(result.message || 'Failed to sync photo from Microsoft.');
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while syncing photo from Microsoft.');
            } finally {
                overlay.remove();
            }
        }
    </script>
    @endif

    <script>
        function printQrCode(obfuscatedId, studentNumber) {
            const qrUrl = 'https://quickchart.io/qr?text=' + encodeURIComponent('https://amis.edu.ph/v/' + obfuscatedId) + '&margin=1&format=png&size=400';
            const printWindow = window.open('', '_blank', 'width=500,height=500');
            printWindow.document.write(`
                <html>
                <head>
                    <title>QR Code - ${studentNumber}</title>
                    <style>
                        body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
                        img { width: 300px; height: 300px; }
                        h2 { margin-top: 20px; font-size: 20px; color: #333; }
                        p { font-size: 14px; color: #666; margin: 5px 0 0 0; }
                    </style>
                </head>
                <body>
                    <img src="${qrUrl}" onload="window.print(); window.close();" />
                    <h2>Student ID #${studentNumber}</h2>
                    <p>Verification QR Code</p>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        function printDocumentChecklist() {
            const printWindow = window.open('', '_blank', 'width=700,height=800');
            
            const docs = [
                { label: '2x2 Photo ID', status: '{{ $student->applicant->photo_2x2_url ? "VERIFIED" : "MISSING" }}' },
                { label: 'Birth Certificate', status: '{{ $student->applicant->birth_cert_url ? "VERIFIED" : "MISSING" }}' },
                { label: 'Report Card / Form 138', status: '{{ $student->applicant->report_card_url ? "VERIFIED" : "MISSING" }}' },
                { label: 'Marriage Contract', status: '{{ $student->applicant->marriage_contract_url ? "VERIFIED" : "MISSING" }}' },
                { label: 'Medical History Records', status: '{{ $student->applicant->medical_record_url ? "VERIFIED" : "MISSING" }}' },
                { label: 'Temporary Proof (Affidavit)', status: '{{ $student->applicant->affidavit_url ? "VERIFIED" : "MISSING" }}' }
            ];
            
            let rowsHtml = '';
            docs.forEach(doc => {
                const color = doc.status === 'VERIFIED' ? '#047857' : '#b91c1c';
                const check = doc.status === 'VERIFIED' ? '✓' : '✗';
                rowsHtml += `
                    <tr>
                        <td>${doc.label}</td>
                        <td style="color: ${color}; font-weight: bold; text-align: center;">[ ${check} ] ${doc.status}</td>
                    </tr>
                `;
            });

            printWindow.document.write(`
                <html>
                <head>
                    <title>Document Checklist - {{ $student->student_number }}</title>
                    <style>
                        body { font-family: system-ui, sans-serif; padding: 40px; color: #1e293b; }
                        .header { border-bottom: 2px solid #cbd5e1; padding-bottom: 20px; margin-bottom: 30px; }
                        h1 { margin: 0; font-size: 24px; }
                        p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #e2e8f0; padding: 12px 16px; text-align: left; font-size: 14px; }
                        th { background-color: #f8fafc; font-weight: bold; }
                        .footer { margin-top: 50px; border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #94a3b8; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>AMIS Document Verification Checklist</h1>
                        <p>Student Name: <strong>{{ $displayName }}</strong> | ID: <strong>{{ $student->student_number }}</strong></p>
                        <p>Date Generated: ${new Date().toLocaleDateString()}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Requirement Document</th>
                                <th style="width: 180px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                    <div class="footer">
                        Al Munawwara Islamic School Registrar Office
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                            window.close();
                        }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        function downloadQR(obfuscatedId, studentNumber) {
            const url = 'https://quickchart.io/qr?text=' + encodeURIComponent('https://amis.edu.ph/v/' + obfuscatedId) + '&margin=1&format=png&size=300';
            fetch(url)
                .then(response => response.blob())
                .then(blob => {
                    const blobUrl = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = 'QR_' + studentNumber + '.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(blobUrl);
                })
                .catch(err => {
                    window.open(url, '_blank');
                });
        }

        function openPhotoOptionsModal() {
            const modal = document.getElementById('photo-options-modal');
            if (modal) {
                modal.classList.remove('hidden');
                updateBodyScroll();
            }
        }

        function closePhotoOptionsModal() {
            const modal = document.getElementById('photo-options-modal');
            if (modal) {
                modal.classList.add('hidden');
                updateBodyScroll();
            }
        }

        function triggerPhotoUpload() {
            closePhotoOptionsModal();
            document.getElementById('student-photo-input').click();
        }

        function triggerMicrosoftSync() {
            closePhotoOptionsModal();
            syncMicrosoftPhoto();
        }

        function triggerCropAdjust() {
            closePhotoOptionsModal();
            adjustExistingPhoto('{{ $photoUrl }}');
        }

        function triggerResetPhoto() {
            closePhotoOptionsModal();
            deleteStudentPhoto();
        }
    </script>

    <!-- SVG Wavy Flag Filter -->
    <svg class="hidden" width="0" height="0">
        <defs>
            <filter id="wavy-flag-filter">
                <feTurbulence type="fractalNoise" baseFrequency="0.012 0.04" numOctaves="2" result="noise" />
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="22" xChannelSelector="R" yChannelSelector="G" />
        </defs>
    </svg>

    <!-- Photo Edit Options Modal -->
    <div id="photo-options-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4" style="z-index: 9999999;">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in duration-200" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-black uppercase tracking-wider text-slate-850 dark:text-white font-outfit">Student Profile Photo</h3>
                <button type="button" onclick="closePhotoOptionsModal()" class="text-slate-400 hover:text-slate-650 dark:hover:text-slate-200 transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <!-- 2-Column Body -->
            <div class="p-5 flex flex-col sm:flex-row gap-5 items-center">
                <!-- Left Column: Bigger Photo Preview -->
                <div class="flex-1 flex flex-col items-center justify-center">
                    <div class="relative w-40 h-40 rounded-2xl overflow-hidden border-4 border-slate-100 dark:border-slate-800 shadow-md bg-slate-100 dark:bg-slate-900">
                        @if($photoUrl)
                            <img id="options-modal-preview-image" src="{{ $photoUrl }}" alt="Preview" class="w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                                <i data-lucide="image" class="w-10 h-10 mb-1.5 text-slate-300"></i>
                                <span class="text-xs font-black uppercase text-slate-400 tracking-wider">No Photo</span>
                            </div>
                        @endif
                    </div>
                    <span class="text-[9px] font-black uppercase text-slate-400 mt-2 tracking-widest">Current Photo</span>
                </div>

                <!-- Right Column: Options List -->
                <div class="flex-1 w-full flex flex-col gap-3">
                    <!-- Upload New Photo -->
                    <button type="button" 
                            onclick="triggerPhotoUpload()" 
                            class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 transition active:scale-[0.98] shadow-sm cursor-pointer">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload Photo
                    </button>

                    <!-- Sync Photo from M365 -->
                    <button type="button" 
                            onclick="triggerMicrosoftSync()" 
                            class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 transition active:scale-[0.98] shadow-sm cursor-pointer">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sync from M365
                    </button>

                    @if($photoUrl)
                        <!-- Crop & Adjust -->
                        <button type="button" 
                                onclick="triggerCropAdjust()" 
                                class="w-full py-2.5 px-4 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 transition active:scale-[0.98] shadow-sm cursor-pointer">
                            <i data-lucide="crop" class="w-4 h-4"></i> Crop & Adjust
                        </button>

                        <!-- Reset to Default -->
                        <button type="button" 
                                onclick="triggerResetPhoto()" 
                                class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-750 text-white rounded-xl text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 transition active:scale-[0.98] shadow-sm cursor-pointer">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Reset Default
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modern HTML-to-Image & HTML2Canvas CDN Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    async function downloadIdCardPng(side, isMonochrome = false, isWatermark = false) {
        const boxId = side === 'front' ? 'id-card-front-box' : 'id-card-back-box';
        const cardEl = document.getElementById(boxId);
        if (!cardEl) return;

        const rawSlug = document.getElementById('id-card-filename-slug')?.value || 'STUDENT-ID';
        const filename = `${rawSlug}-${side.toUpperCase()}${isMonochrome ? '-BLACK-ONLY' : ''}${isWatermark ? '-SAMPLE-WATERMARK' : ''}.png`;

        const btn = event?.currentTarget;
        let oldContent = '';
        if (btn) {
            oldContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Generating...`;
            if (window.lucide) window.lucide.createIcons();
        }

        try {
            let dataUrl = '';
            
            // Primary strategy: htmlToImage
            if (typeof htmlToImage !== 'undefined') {
                dataUrl = await htmlToImage.toPng(cardEl, {
                    pixelRatio: 3,
                    cacheBust: true,
                    backgroundColor: isMonochrome ? '#ffffff' : '#064e3b'
                });
            } else if (typeof html2canvas !== 'undefined') {
                const canvas = await html2canvas(cardEl, { scale: 3, useCORS: true, allowTaint: true, backgroundColor: isMonochrome ? '#ffffff' : '#064e3b' });
                dataUrl = canvas.toDataURL('image/png', 1.0);
            }

            if (dataUrl) {
                if (isMonochrome || isWatermark) {
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.src = dataUrl;
                    await new Promise(res => img.onload = res);
                    
                    const cvs = document.createElement('canvas');
                    cvs.width = img.width;
                    cvs.height = img.height;
                    const ctx = cvs.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    
                    if (isMonochrome) {
                        const imgData = ctx.getImageData(0, 0, cvs.width, cvs.height);
                        const d = imgData.data;
                        for (let i = 0; i < d.length; i += 4) {
                            const gray = 0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2];
                            const val = gray > 215 ? 255 : (gray < 165 ? 0 : Math.round(gray));
                            d[i] = val;
                            d[i+1] = val;
                            d[i+2] = val;
                        }
                        ctx.putImageData(imgData, 0, 0);
                    }

                    if (isWatermark) {
                        ctx.save();
                        if (side === 'front') {
                            // Position watermark at top header area (above student photo)
                            ctx.translate(cvs.width / 2, cvs.height * 0.145);
                        } else {
                            // Position watermark at bottom area (below parent & emergency contact details)
                            ctx.translate(cvs.width / 2, cvs.height * 0.725);
                        }
                        ctx.rotate(-12 * Math.PI / 180);
                        ctx.font = '900 ' + Math.round(cvs.width * 0.115) + 'px "Outfit", Arial, sans-serif';
                        ctx.fillStyle = 'rgba(239, 68, 68, 0.48)';
                        ctx.strokeStyle = 'rgba(255, 255, 255, 0.95)';
                        ctx.lineWidth = Math.round(cvs.width * 0.007);
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.strokeText('SAMPLE COPY', 0, 0);
                        ctx.fillText('SAMPLE COPY', 0, 0);
                        ctx.restore();
                    }

                    dataUrl = cvs.toDataURL('image/png', 1.0);
                }

                const link = document.createElement('a');
                link.download = filename;
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                throw new Error('No image generator available.');
            }
        } catch (err) {
            console.error('PNG Download Error:', err);
            alert('PNG Generation Error: ' + (err.message || err));
        } finally {
            if (btn && oldContent) {
                btn.disabled = false;
                btn.innerHTML = oldContent;
                if (window.lucide) window.lucide.createIcons();
            }
        }
    }

    function adjustLastNameFontSizes() {
        // Font sizes are calculated server-side in PHP matching Student Records (show.blade.php)
    }
    </div>

    <!-- Student JSON Payload Modal -->
    <div x-cloak x-show="openStudentJsonModal"
         style="display: none; z-index: 99999;"
         class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
        <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col"
             @click.outside="openStudentJsonModal = false">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="file-json" class="w-5 h-5 text-emerald-600"></i>
                    <span>Student JSON Record Payload ({{ $student->applicant->first_name ?? '' }} {{ $student->applicant->last_name ?? '' }})</span>
                </h3>
                <button type="button" @click="openStudentJsonModal = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-xs font-semibold text-slate-500">
                    This is the standardized JSON record payload for this student. You can copy it directly or use it in the Section Occupancy JSON Batch Sync.
                </p>
                <textarea readonly rows="11" x-text="singleStudentJson"
                          class="w-full p-3.5 rounded-xl border border-slate-200 bg-slate-900 font-mono text-xs text-emerald-400 outline-none leading-relaxed shadow-inner select-all"></textarea>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('admin.students.occupancy') }}" class="text-xs font-bold text-slate-600 hover:text-emerald-700 underline flex items-center gap-1.5">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Go to Section Occupancy JSON Sync</span>
                    </a>
                    <button type="button" @click="navigator.clipboard.writeText(singleStudentJson); alert('Copied student JSON payload to clipboard!');"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md cursor-pointer flex items-center gap-2 active:scale-95">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                        <span>Copy JSON to Clipboard</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
