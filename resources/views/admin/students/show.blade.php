@php
    use Illuminate\Support\Str;

    // A. Visual Accent dynamic calculation
    $familyNo = $student->applicant->family_application_id ?? $student->id;
    $accentClasses = ['accent-green', 'accent-blue', 'accent-amber', 'accent-violet', 'accent-rose'];
    $accentClass = $accentClasses[$familyNo % 5];

    $name = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
    $displayName = $name ? Str::upper($name) : 'STUDENT PROFILE';
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
    
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
            ['LRN', $student->applicant->lrn],
        ]],
        ['title' => 'Personal Details', 'icon' => 'id-card', 'fields' => [
            ['First Name', $student->applicant->first_name],
            ['Middle Name', $student->applicant->middle_name],
            ['Last Name', $student->applicant->last_name],
            ['Suffix', $student->applicant->suffix],
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
        <div class="flex items-center gap-2">
            @unless ($isTeacherAdminViewer)
            <button @click="openEditModal = true"
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
            <a href="{{ route('admin.students.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2 text-sm font-bold text-slate-700 dark:text-slate-300 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98]">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
                <span>Back to directory</span>
            </a>
        </div>
    </div>

    <div class="applicant-page" x-data="{
         openEditModal: false,
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
            <!-- Dynamic Profile Header Card -->
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
                    <p class="mt-2 text-sm text-emerald-50/90 flex items-center flex-wrap gap-x-4 gap-y-1">
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                            {{ $student->school_email ?? '-' }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <i data-lucide="fingerprint" class="h-3.5 w-3.5 opacity-75"></i>
                            <span class="font-extrabold opacity-75">LRN:</span>
                            {{ $student->applicant->lrn ?? 'N/A' }}
                        </span>
                    </p>
                    <div class="applicant-pill-row">
                        <span class="applicant-pill applicant-pill-grade">{{ Str::upper($student->grade_level ?: 'Grade pending') }}</span>
                        <span class="applicant-pill applicant-pill-type">{{ Str::upper($student->applicant->student_type ?: 'Student') }}</span>
                        <span class="applicant-pill applicant-pill-mode">{{ Str::upper($student->applicant->learning_mode ?: 'Learning mode pending') }}</span>
                        <span class="applicant-pill applicant-pill-year">SY {{ $student->school_year ?? '-' }}</span>
                        @if (!$student->applicant || $student->applicant->completion_percentage < 100)
                            @php
                                $missingList = $student->applicant ? implode(', ', $student->applicant->incomplete_fields) : 'No profile';
                            @endphp
                            <span class="applicant-pill bg-amber-500/20 text-amber-200 border border-amber-500/30 font-extrabold cursor-help" title="Missing: {{ $missingList }}">INCOMPLETE</span>
                        @endif

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

        <!-- Preview Modal -->
        @include('admin.students.partials.show.modal')
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
                        <span>Edit Student Profile</span>
                    </h3>
                    <button @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form action="{{ route('admin.students.update-profile', $student) }}" method="POST" class="p-6 space-y-5 flex-1">
                    @csrf
                    
                    <!-- Academic Details -->
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Academic Info</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Student Type</label>
                                <input type="text" name="student_type" value="{{ $student->applicant->student_type ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Grade Level</label>
                                <select name="grade_level" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                    @foreach(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g)
                                        <option value="{{ $g }}" @if(($student->grade_level ?? '') === $g) selected @endif>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Learning Mode</label>
                                <input type="text" name="learning_mode" value="{{ $student->applicant->learning_mode ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">AMIS Student ID</label>
                                <input type="text" name="amis_student_id" value="{{ $student->applicant->amis_student_id ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">LRN</label>
                                <input type="text" name="lrn" value="{{ $student->applicant->lrn ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-800">

                    <!-- Personal Info -->
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Personal Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">First Name</label>
                                <input type="text" name="first_name" required value="{{ $student->applicant->first_name ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Middle Name</label>
                                <input type="text" name="middle_name" value="{{ $student->applicant->middle_name ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Last Name</label>
                                <input type="text" name="last_name" required value="{{ $student->applicant->last_name ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Suffix</label>
                                <input type="text" name="suffix" value="{{ $student->applicant->suffix ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Gender</label>
                                <input type="text" name="gender" value="{{ $student->applicant->gender ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Date of Birth</label>
                                <input type="date" name="date_of_birth" value="{{ optional($student->applicant->date_of_birth)->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Place of Birth</label>
                                <input type="text" name="place_of_birth" value="{{ $student->applicant->place_of_birth ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Religion</label>
                                <input type="text" name="religion" value="{{ $student->applicant->religion ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Ethnicity</label>
                                <input type="text" name="ethnicity" value="{{ $student->applicant->ethnicity ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-800">

                    <!-- Contact & Address -->
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Contact & Address</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Student Email</label>
                                <input type="email" name="email" value="{{ $student->applicant->email ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Student Mobile</label>
                                <input type="text" name="mobile" value="{{ $student->applicant->mobile_number ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Residence Address</label>
                            <textarea name="address" rows="2" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">{{ $student->applicant->address ?? $studentAddress }}</textarea>
                        </div>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-800">

                    <!-- Parent & Emergency -->
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Parent & Emergency Info</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Father's Full Name</label>
                                <input type="text" name="father_name" value="{{ trim(($student->applicant->father_first_name ?? '').' '.($student->applicant->father_last_name ?? '')) }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Mother's Full Name</label>
                                <input type="text" name="mother_name" value="{{ trim(($student->applicant->mother_first_name ?? '').' '.($student->applicant->mother_last_name ?? '')) }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Parent Email</label>
                                <input type="email" name="parent_email" value="{{ $student->applicant->parent_email ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Parent Mobile</label>
                                <input type="text" name="parent_mobile" value="{{ $student->applicant->parent_mobile ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Emergency Contact Person</label>
                                <input type="text" name="emergency_name" value="{{ $student->applicant->emergency_name ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Relationship</label>
                                <input type="text" name="emergency_relationship" value="{{ $student->applicant->emergency_relationship ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">Emergency Phone</label>
                                <input type="text" name="emergency_phone" value="{{ $student->applicant->emergency_phone ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100 dark:border-slate-800">

                    <!-- LRN -->
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 mb-3">Verification Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-350 mb-1">LRN (Learner Reference Number)</label>
                                <input type="text" name="lrn" value="{{ $student->applicant->lrn ?? '' }}" class="w-full rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-sm font-semibold text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-150 dark:border-slate-800">
                        <button type="button" @click="openEditModal = false" class="rounded-xl border border-slate-250 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 text-sm font-bold text-slate-750 dark:text-slate-300 transition hover:bg-slate-50 dark:hover:bg-slate-850 active:scale-[0.98] cursor-pointer">
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</x-admin-layout>
