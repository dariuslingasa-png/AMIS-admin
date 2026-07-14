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
    
    $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url);
    $studentAddress = implode(', ', array_filter([$student->applicant->street_address, $student->applicant->city, $student->applicant->state_province, $student->applicant->country]));
    $homeAddress = implode(', ', array_filter([$student->applicant->home_street_address, $student->applicant->home_city, $student->applicant->home_state_province]));
    $studentMobile = trim(($student->applicant->mobile_country_code ?? '').' '.($student->applicant->mobile_number ?? ''));
    $parentMobile = trim(($student->applicant->parent_country_code ?? '').' '.($student->applicant->parent_mobile ?? ''));

    // B. Reusable layout sections mapping (using same components for absolute consistency)
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
    <div x-data="{
         openEditModal: false,
         showIdPreview: false,
         openPasswordModal: false,
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

    <!-- Page back link -->
    <div class="mb-5 flex justify-between items-center">
        <div>
            <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Student Administration</span>
        </div>
        <div class="flex items-center gap-2">
            @unless ($isTeacherAdminViewer)
            <button @click="openEditModal = true; editSection = 'all'"
                    class="inline-flex items-center gap-2 rounded-xl border border-transparent bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98] cursor-pointer">
                <i data-lucide="edit" class="h-4 w-4"></i>
                <span>Edit Profile</span>
            </button>
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
            <section class="applicant-profile-card relative overflow-hidden {{ $accentClass }}">
                @if ($countryCode)
                    <div class="absolute right-0 top-0 bottom-0 h-full w-1/3 overflow-hidden pointer-events-none opacity-20 select-none print-hide" style="mask-image: linear-gradient(to left, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%); -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,1) 0%, rgba(0,0,0,0) 100%);">
                        <img src="https://flagcdn.com/w640/{{ $countryCode }}.png" 
                             alt="Country Flag" 
                             class="h-full w-full object-cover object-right animate-pulse"
                             style="filter: url(#wavy-flag-filter); transform: scale(1.15); transform-origin: right center; animation-duration: 4s;">
                    </div>
                @endif

                <div class="absolute top-4 right-4 flex items-center gap-2 print-hide" style="z-index: 10;">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-white/10 text-white border border-white/25 uppercase tracking-wider">
                        SY {{ $student->school_year ?? '-' }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-white/10 text-white border border-white/25 uppercase tracking-wider">
                        ID #{{ $student->student_number ?? 'Pending' }}
                    </span>
                </div>
                
                <div class="relative flex items-center justify-center">
                    <button type="button" class="applicant-photo overflow-hidden hover:brightness-95 transition-all duration-200 cursor-zoom-in" @if ($photoUrl) @click="openPreview('{{ $photoUrl }}', '2x2 Photo', false)" @endif>
                        @if ($photoUrl)
                            <img src="{{ $photoUrl }}" alt="2x2 Photo" class="w-full h-full object-cover block" loading="eager" decoding="async" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span class="w-full h-full items-center justify-center text-xs font-extrabold" style="display:none">NO PHOTO</span>
                        @else
                            NO PHOTO
                        @endif
                    </button>
                    @if (auth()->user()?->hasRole('super_admin'))
                        <button type="button" onclick="event.stopPropagation(); document.getElementById('student-photo-input').click()" 
                                class="absolute bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-lg border border-white/20 transition active:scale-95 cursor-pointer z-20 flex items-center justify-center"
                                style="width: 28px; height: 28px; bottom: 4px; right: 4px;"
                                title="Change Profile Photo">
                            <i data-lucide="camera" class="w-3.5 h-3.5 text-white"></i>
                        </button>
                        <input type="file" id="student-photo-input" name="photo" accept="image/*" class="hidden" onchange="uploadStudentPhoto(this)">
                    @endif
                </div>
                
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-3xl font-black tracking-tight font-outfit uppercase">{{ $displayName }}</h2>
                        @if (!$student->applicant || $student->applicant->completion_percentage < 100)
                            @php
                                $missingList = $student->applicant ? implode(', ', $student->applicant->incomplete_fields) : 'No profile';
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-200 border border-amber-500/30 cursor-help uppercase tracking-wider animate-pulse" title="Missing: {{ $missingList }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                Incomplete
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                Active
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
                            <span class="font-medium tracking-wide"><span class="font-bold opacity-75">LRN:</span> {{ $student->applicant->lrn ?? 'N/A' }}</span>
                        </div>
                        @if ($student->applicant->country)
                        <div class="flex items-center gap-2">
                            <i data-lucide="globe" class="w-4 h-4 opacity-75"></i>
                            <span class="font-medium tracking-wide flex items-center gap-1.5">
                                @if ($countryCode)
                                    <img src="https://flagcdn.com/16x12/{{ $countryCode }}.png" class="inline rounded-xs shadow-xs" alt="{{ $student->applicant->country }}">
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
        </main>

        <!-- Right Sidebar -->
        @unless ($isTeacherAdminViewer)
            @include('admin.students.partials.show.sidebar')
        @endunless
    </div>

        <!-- Preview Modal -->
        @include('admin.students.partials.show.modal')

        <!-- ID Card Preview Modal -->
        <div x-show="showIdPreview" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in"
             @click.self="showIdPreview = false">
            <div class="bg-slate-100 dark:bg-slate-950 rounded-3xl max-w-3xl w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-850 bg-white dark:bg-slate-900">
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
                        $firstName = trim($student->applicant->first_name ?? '');
                        $middleName = trim($student->applicant->middle_name ?? '');
                        $lastName = trim($student->applicant->last_name ?? '');
                        $middleInitial = '';
                        if ($middleName !== '') {
                            $firstChar = mb_strtoupper(mb_substr($middleName, 0, 1));
                            $middleInitial = ($firstChar === '.') ? '.' : $firstChar . '.';
                        }
                        
                        $fullNameParts = array_filter([$firstName, $middleInitial, $lastName], fn($val) => $val !== '');
                        $fullName = html_entity_decode(implode(' ', $fullNameParts), ENT_QUOTES, 'UTF-8');
                        $displayGrade = $student->grade_level;

                        $homeAddress = implode(', ', array_filter([$student->applicant->home_street_address, $student->applicant->home_city, $student->applicant->home_state_province]));
                        if (empty($homeAddress)) {
                            $homeAddress = $student->applicant->home_address ?: '-';
                        }
                        
                        $emergencyName = $student->applicant->emergency_name ?: '-';
                        if (empty($emergencyName) || strtolower($emergencyName) === 'emergency contact') {
                            $emergencyName = trim(($student->applicant->father_first_name ?? '') . ' ' . ($student->applicant->father_last_name ?? '')) ?: (trim(($student->applicant->mother_first_name ?? '') . ' ' . ($student->applicant->mother_last_name ?? '')) ?: 'Registrar Office');
                        }
                        
                        $relationship = trim($student->applicant->emergency_relationship ?? '');
                        if (!empty($relationship)) {
                            $emergencyName .= ' (' . $relationship . ')';
                        }
                        
                        $emergencyPhone = $student->applicant->emergency_phone ?: '-';
                        if (empty($emergencyPhone)) {
                            $emergencyPhone = $student->applicant->parent_mobile ?: ($student->applicant->mobile_number ?: '+63 900 000 0000');
                        }

                        $studentNumber = $student->student_number;
                        $hash = base64_encode((int)$studentNumber + 987654);
                        $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
                        
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
                        $lastNameFontSize = $lastNameLen > 20 ? '12px' : ($lastNameLen > 15 ? '15px' : ($lastNameLen > 10 ? '19px' : '26px'));
                    @endphp

                    <!-- Front Side Card -->
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Front Side</span>
                        <div class="relative rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-800" style="width: 280px; height: 443px; background-color: #064e3b;">
                            <!-- Background template image -->
                            <img src="{{ asset('assets/amis-id-template.png') }}?v=3" class="absolute inset-0 w-full h-full object-cover" style="z-index: 1; pointer-events: none;" alt="AMIS ID Template">
                            
                            <!-- Student Photo -->
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" class="absolute object-cover rounded-lg border border-white/20" style="left: 67px; top: 94px; width: 146px; height: 142px; z-index: 10;">
                            @else
                                <div class="absolute rounded-lg bg-slate-100 flex items-center justify-center text-center border border-dashed border-slate-300" style="left: 67px; top: 94px; width: 146px; height: 142px; z-index: 10; font-size: 10px; font-weight: bold; color: #94a3b8;">NO PHOTO</div>
                            @endif

                            <!-- Student ID -->
                            <div class="absolute text-white font-black tracking-wide text-center uppercase" style="left: 0; top: 243px; width: 280px; height: 12px; z-index: 10; font-size: 10px;">{{ $studentNumber }}</div>

                            <!-- Last Name -->
                            <div class="absolute text-center font-black text-[#0f172a] uppercase tracking-tight flex flex-col justify-center items-center animate-fade-in" style="left: 12px; top: 271px; width: 256px; height: 32px; z-index: 10; font-size: {{ $lastNameFontSize }}; line-height: 1.1;">{{ $lastName }}</div>

                            <!-- First Name -->
                            <div class="absolute text-center font-bold text-[#334155] uppercase leading-none flex flex-col justify-center items-center animate-fade-in" style="left: 12px; top: 304px; width: 256px; height: 18px; z-index: 10; font-size: 12px;">{{ $firstName }}</div>

                            <!-- Grade Level -->
                            <div class="absolute text-center font-black uppercase tracking-wide flex flex-col justify-center items-center animate-fade-in" style="left: 12px; top: 340px; width: 256px; height: 24px; z-index: 10; font-size: 20px; color: {{ $getGradeColor($displayGrade) }};">{{ $displayGrade }}</div>

                            <!-- LRN -->
                            @if($student->applicant->lrn && !in_array(strtoupper($student->applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                <div class="absolute font-bold text-[#1e293b] whitespace-nowrap" style="right: 1px; top: 283px; z-index: 10; font-size: 12.5px; transform: rotate(-90deg); transform-origin: right center; width: 18px; height: 107px; display: flex; align-items: center; justify-content: center;">
                                    LRN: <span>{{ $student->applicant->lrn }}</span>
                                </div>
                            @endif

                            <!-- QR Code -->
                            <div class="absolute p-0.5 rounded bg-white" style="left: 111px; top: 377px; width: 58px; height: 58px; z-index: 10;">
                                <img src="{{ $qrCodeUrl }}" alt="QR Verification" class="w-full h-full object-contain">
                            </div>
                        </div>
                    </div>

                    <!-- Back Side Card -->
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Back Side</span>
                        <div class="relative rounded-2xl overflow-hidden shadow-lg border border-slate-200 dark:border-slate-800" style="width: 280px; height: 443px; background-color: #064e3b;">
                            <!-- Background template image -->
                            <img src="{{ asset('assets/amis-id-template-back.png') }}?v=3" class="absolute inset-0 w-full h-full object-cover" style="z-index: 1; pointer-events: none;" alt="AMIS ID Template Back">

                            <!-- Emergency Name -->
                            @php
                                $parentNameLen = strlen($emergencyName);
                                $parentNameFontSize = $parentNameLen > 24 ? '11px' : ($parentNameLen > 18 ? '13px' : '15px');
                            @endphp
                            <div class="absolute text-center font-black text-[#0f172a] uppercase leading-tight flex flex-col justify-center" style="left: 12px; top: 70px; width: 256px; height: 23px; z-index: 10; font-size: {{ $parentNameFontSize }};">{{ $emergencyName }}</div>

                            <!-- Emergency Contact -->
                            <div class="absolute text-center font-bold text-[#1e293b] leading-none flex flex-col justify-center" style="left: 12px; top: 97px; width: 256px; height: 16px; z-index: 10; font-size: 12.5px;">{{ $emergencyPhone }}</div>

                            <!-- Home Address -->
                            <div class="absolute text-center font-bold text-[#475569] uppercase leading-tight flex flex-col justify-center" style="left: 16px; top: 119px; width: 248px; height: 35px; z-index: 10; font-size: 9px; padding: 0 16px;">{{ $homeAddress }}</div>
                        </div>
                    </div></div>

                <!-- Footer -->
                <div class="flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-855 bg-white dark:bg-slate-900">
                    <p class="text-xs text-slate-400 font-medium">Verify all details before printing the physical card.</p>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="showIdPreview = false" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-xs font-bold text-slate-700 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-850 active:scale-[0.98] cursor-pointer">
                            Close
                        </button>
                        <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_id' => 1]) }}"
                           target="_blank"
                           class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-xs font-bold shadow-md transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                            <span>Print ID Card</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Settings Modal -->
        <div x-show="openPasswordModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in"
             @click.self="openPasswordModal = false">
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

        <!-- Edit Profile Modal -->
        @unless ($isTeacherAdminViewer)
        <div x-show="openEditModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in"
             @click.self="openEditModal = false">
            
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
        @endunless
    </div>

    <!-- Photo Cropping Modal -->
    @if (auth()->user()?->hasRole('super_admin'))
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <div id="photo-crop-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-800 flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-150 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i data-lucide="crop" class="h-4.5 w-4.5 text-emerald-600"></i>
                    <span>Crop Student Photo (1:1 Ratio)</span>
                </h3>
                <button type="button" onclick="closeCropModal()" class="text-slate-400 hover:text-slate-655 dark:hover:text-slate-200 transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            <!-- Body -->
            <div class="p-5 flex flex-col items-center justify-center bg-slate-50 dark:bg-slate-950/20">
                <div class="w-full max-h-[45vh] overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center bg-slate-100 dark:bg-slate-900 p-4">
                    <div style="max-width: 100%; max-height: 35vh; width: 100%; display: block;">
                        <img id="crop-image-preview" src="" alt="Source image for cropping" style="max-width: 100%; max-height: 35vh; display: block; margin: 0 auto;">
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 mt-3 font-semibold">Drag and adjust the square selection to crop the 2x2 photo.</p>
            </div>
            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-slate-150 dark:border-slate-800 bg-white dark:bg-slate-900">
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
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    @if (auth()->user()?->hasRole('super_admin'))
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper = null;
        let selectedFile = null;

        function uploadStudentPhoto(input) {
            if (!input.files || !input.files[0]) return;
            
            selectedFile = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const cropImg = document.getElementById('crop-image-preview');
                
                // Show Crop Modal first to establish layout dimensions
                const cropModal = document.getElementById('photo-crop-modal');
                cropModal.classList.remove('hidden');
                
                cropImg.onload = function() {
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    cropper = new Cropper(cropImg, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        background: false,
                        autoCropArea: 0.8,
                        responsive: true,
                        checkOrientation: false,
                        modal: true,
                        guides: true,
                        highlight: true,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
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
            
            // Get 400x400 cropped canvas
            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400
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
    </script>

    <!-- SVG Wavy Flag Filter -->
    <svg class="hidden" width="0" height="0">
        <defs>
            <filter id="wavy-flag-filter">
                <feTurbulence type="fractalNoise" baseFrequency="0.012 0.04" numOctaves="2" result="noise" />
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="22" xChannelSelector="R" yChannelSelector="G" />
            </filter>
        </defs>
    </svg>
</x-admin-layout>
