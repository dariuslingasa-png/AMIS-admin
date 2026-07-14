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
            <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_info' => 1]) }}"
               target="_blank"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98]">
                <i data-lucide="printer" class="h-4 w-4 text-slate-500"></i>
                <span>Print Official Sheet</span>
            </a>
            <button type="button" @click="showIdPreview = true"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] cursor-pointer">
                <i data-lucide="contact" class="h-4 w-4 text-slate-500"></i>
                <span>Print ID Card</span>
            </button>
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
                                class="absolute bottom-1 right-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full p-1 shadow-lg border border-white/20 transition active:scale-95 cursor-pointer z-20 flex items-center justify-center"
                                style="width: 24px; height: 24px;"
                                title="Change Profile Photo">
                            <i data-lucide="camera" class="w-3 h-3"></i>
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
                        <span>Academic & History</span>
                    </button>
                    @unless ($isTeacherAdminViewer)
                    <button @click="activeTab = 'documents'" 
                            :class="activeTab === 'documents' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-white/50 dark:hover:bg-slate-900/50'" 
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl transition-all duration-200 focus:outline-none flex-1 sm:flex-initial cursor-pointer">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        <span>Documents & Verification</span>
                    </button>
                    @endunless
                </nav>
            </div>

            <!-- Tab Contents -->
            @include('admin.students.partials.show.overview')
            @include('admin.students.partials.show.academic')
            @unless ($isTeacherAdminViewer)
                @include('admin.students.partials.show.documents')
            @endunless
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
                <div class="p-6 overflow-y-auto max-h-[70vh] flex flex-col md:flex-row items-center justify-center gap-8 bg-slate-50 dark:bg-slate-950/20">
                    
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
                            <div class="absolute text-white font-black tracking-wide text-center uppercase" style="left: 100px; top: 243px; width: 80px; height: 12px; z-index: 10; font-size: 10px;">{{ $studentNumber }}</div>

                            <!-- Last Name -->
                            <div class="absolute text-center font-black text-[#0f172a] uppercase tracking-tight leading-none flex flex-col justify-center animate-fade-in" style="left: 12px; top: 275px; width: 256px; height: 26px; z-index: 10; font-size: 18px;">{{ $lastName }}</div>

                            <!-- First Name -->
                            <div class="absolute text-center font-bold text-[#334155] uppercase leading-none flex flex-col justify-center animate-fade-in" style="left: 12px; top: 301px; width: 256px; height: 18px; z-index: 10; font-size: 12px;">{{ $firstName }}</div>

                            <!-- Grade Level -->
                            <div class="absolute text-center font-black uppercase tracking-wide flex flex-col justify-center animate-fade-in" style="left: 12px; top: 334px; width: 256px; height: 24px; z-index: 10; font-size: 20px; color: {{ $getGradeColor($displayGrade) }};">{{ $displayGrade }}</div>

                            <!-- LRN -->
                            @if($student->applicant->lrn && !in_array(strtoupper($student->applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                                <div class="absolute font-bold text-[#1e293b] whitespace-nowrap" style="right: 6px; top: 333px; z-index: 10; font-size: 12px; transform: rotate(-90deg); transform-origin: center; width: 18px; height: 107px; display: flex; align-items: center; justify-content: center; translate: 40px 25px;">
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
                            <div class="absolute text-center font-black text-[#0f172a] uppercase leading-tight flex flex-col justify-center" style="left: 12px; top: 70px; width: 256px; height: 23px; z-index: 10; font-size: 16px;">{{ $emergencyName }}</div>

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
