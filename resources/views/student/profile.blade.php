<x-student-layout title="My Profile">

@php 
    $applicant = $student?->applicant; 
    $photoUrl = \App\Support\EnrollmentStorage::url($applicant?->photo_2x2_url); 
    $fullName = $applicant?->full_name ?: Auth::user()->name; 
    $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? '')); 
    $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? '')); 
    
    $rows = [ 
        'Student Details' => [ 
            ['Student Number', $student?->student_number ?? '260000', 'fingerprint'], 
            ['Grade Level', $student?->grade_level ?? 'Grade 1', 'graduation-cap'], 
            ['School Year', $student?->school_year ?? '2026-2027', 'calendar'], 
            ['Section', $section?->name ?? 'G1-AL-MUNAWWARA', 'layout-grid'], 
            ['Learning Mode', $applicant?->learning_mode ?? 'Online / Hybrid', 'laptop'], 
            ['School Email', $student?->school_email ?? Auth::user()->email, 'mail'], 
        ], 
        'Personal Information' => [ 
            ['Full Name', $fullName, 'user'], 
            ['Gender', $applicant?->gender ?? 'Male', 'user-check'], 
            ['Date of Birth', $applicant?->date_of_birth?->format('M d, Y') ?? '—', 'calendar-days'], 
            ['Place of Birth', $applicant?->place_of_birth ?? '—', 'map-pin'], 
            ['Religion', $applicant?->religion ?? 'Islam', 'shield-check'], 
            ['Address', $applicant?->street_address ?: ($applicant?->address ?? '—'), 'home'], 
        ], 
        'Guardian Contact' => [ 
            ['Father', $fatherName ?: '—', 'user'], 
            ['Mother', $motherName ?: '—', 'user'], 
            ['Parent Mobile', trim(($applicant->parent_country_code ?? '').' '.($applicant->parent_mobile ?? '—')), 'phone'], 
            ['Parent Email', $applicant?->parent_email ?? '—', 'mail'], 
            ['Emergency Contact', $applicant?->emergency_name ?? '—', 'alert-circle'], 
            ['Emergency Phone', $applicant?->emergency_phone ?? '—', 'phone-call'], 
        ], 
    ];
@endphp

<div class="space-y-6">
    
    <!-- 1. Profile Header Card -->
    <div class="portal-card p-6 flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left">
            <div class="w-20 h-20 rounded-2xl bg-emerald-50 border border-emerald-200 overflow-hidden flex items-center justify-center shrink-0">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $fullName }}" class="w-full h-full object-cover">
                @else
                    <span class="font-heading text-3xl font-black text-emerald-800">{{ mb_substr($fullName, 0, 1) }}</span>
                @endif
            </div>

            <div>
                <span class="portal-badge portal-badge-emerald">Active Student</span>
                <h2 class="font-heading text-2xl font-black text-slate-900 mt-1">{{ $fullName }}</h2>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">
                    Student ID: <span class="text-slate-800">{{ $student?->student_number ?? '260000' }}</span> • Grade: <span class="text-slate-800">{{ $student?->grade_level ?: 'Grade 1' }}</span>
                </p>
            </div>
        </div>

        <div class="shrink-0 bg-emerald-50 border border-emerald-200 px-5 py-3 rounded-2xl text-center sm:text-right">
            <p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Assigned Section</p>
            <p class="font-heading text-lg font-black text-emerald-950 mt-0.5">{{ $section?->name ?? 'G1-AL-MUNAWWARA' }}</p>
        </div>
    </div>

    <!-- 2. Profile Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Academic Profile & Personal Info -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Academic Profile -->
            <div class="portal-card p-6">
                <div class="flex items-center gap-2 pb-4 border-b border-slate-100">
                    <i data-lucide="graduation-cap" class="h-4.5 w-4.5 text-emerald-700"></i>
                    <h3 class="font-heading text-base font-extrabold text-slate-900">Academic Profile</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    @foreach($rows['Student Details'] as [$label, $value, $icon])
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center gap-3.5">
                            <div class="w-9 h-9 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $label }}</p>
                                <p class="font-bold text-xs text-slate-900 truncate mt-0.5" title="{{ $value }}">{{ $value }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Personal Information -->
            <div class="portal-card p-6">
                <div class="flex items-center gap-2 pb-4 border-b border-slate-100">
                    <i data-lucide="user" class="h-4.5 w-4.5 text-emerald-700"></i>
                    <h3 class="font-heading text-base font-extrabold text-slate-900">Personal Information</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                    @foreach($rows['Personal Information'] as [$label, $value, $icon])
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 flex items-center gap-3.5">
                            <div class="w-9 h-9 rounded-lg bg-sky-50 border border-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $icon }}" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $label }}</p>
                                <p class="font-bold text-xs text-slate-900 truncate mt-0.5" title="{{ $value }}">{{ $value }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Guardian & Emergency Contact -->
        <div class="space-y-6">
            <div class="portal-card p-6">
                <div class="flex items-center gap-2 pb-4 border-b border-slate-100">
                    <i data-lucide="shield-alert" class="h-4.5 w-4.5 text-emerald-700"></i>
                    <h3 class="font-heading text-base font-extrabold text-slate-900">Guardian & Contacts</h3>
                </div>

                <div class="space-y-3 mt-4">
                    @foreach($rows['Guardian Contact'] as [$label, $value, $icon])
                        <div class="p-3 rounded-xl border border-slate-200 bg-slate-50 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $label }}</p>
                                <p class="font-bold text-xs text-slate-900 truncate mt-0.5" title="{{ $value }}">{{ $value }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>

</x-student-layout>
