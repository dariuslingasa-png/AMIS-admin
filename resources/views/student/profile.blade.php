@extends('student.layout', ['heading' => 'My Profile'])

@section('content')
@php 
    $applicant = $student->applicant; 
    $photoUrl = \App\Support\EnrollmentStorage::url($applicant?->photo_2x2_url); 
    $fullName = $applicant?->full_name ?: Auth::user()->name; 
    $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? '')); 
    $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? '')); 
    $rows = [ 
        'Student Details' => [ 
            ['Student Number', $student->student_number], 
            ['Grade Level', $student->grade_level], 
            ['School Year', $student->school_year], 
            ['Section', $section?->official_name ?? $student->section], 
            ['Learning Mode', $section?->learning_mode ?? $applicant?->learning_mode], 
            ['School Email', $student->school_email ?? $student->ms_email], 
        ], 
        'Personal Information' => [ 
            ['Full Name', $fullName], 
            ['Gender', $applicant?->gender], 
            ['Date of Birth', $applicant?->date_of_birth?->format('M d, Y')], 
            ['Place of Birth', $applicant?->place_of_birth], 
            ['Religion', $applicant?->religion], 
            ['Address', $applicant?->street_address ?: $applicant?->address], 
        ], 
        'Guardian Contact' => [ 
            ['Father', $fatherName ?: null], 
            ['Mother', $motherName ?: null], 
            ['Parent Mobile', trim(($applicant->parent_country_code ?? '').' '.($applicant->parent_mobile ?? ''))], 
            ['Parent Email', $applicant?->parent_email], 
            ['Emergency Contact', $applicant?->emergency_name], 
            ['Emergency Phone', $applicant?->emergency_phone], 
        ], 
    ];
@endphp

<div class="space-y-8">
    <!-- Profile Header Card -->
    <div class="student-panel flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left relative z-10">
            <div class="w-24 h-24 rounded-full bg-emerald-50 border-2 border-emerald-100 overflow-hidden flex items-center justify-center shrink-0 shadow-sm">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $fullName }}" class="w-full h-full object-cover">
                @else
                    <span class="text-4xl font-black text-emerald-800">{{ mb_substr($fullName, 0, 1) }}</span>
                @endif
            </div>

            <div class="space-y-1">
                <span class="student-status-pill">Active Student</span>
                <h2 class="text-2xl font-black text-gray-900 leading-tight" style="margin: 6px 0 2px;">{{ $fullName }}</h2>
                <p class="text-gray-500 text-sm font-semibold">
                    Student ID: {{ $student->student_number }} • Grade: {{ $student->grade_level ?: 'Grade pending' }}
                </p>
            </div>
        </div>

        <div class="shrink-0 bg-emerald-50 border border-emerald-100 px-6 py-3.5 rounded-xl text-center sm:text-right relative z-10">
            <p class="text-[9px] text-emerald-800 font-bold uppercase tracking-wider" style="margin:0;">Class Section</p>
            <p class="text-xl font-black text-emerald-950 mt-0.5" style="margin:0;">{{ $section?->official_name ?? 'General' }}</p>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Side: Student Details & Personal info (2 cols) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Student Details Card -->
            <div class="student-panel">
                <div class="student-panel-header">
                    <h2>Academic Profile</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-4">
                    @php 
                        $detailIcons = [ 
                            'Student Number' => 'fingerprint', 
                            'Grade Level' => 'school', 
                            'School Year' => 'calendar', 
                            'Section' => 'layout', 
                            'Learning Mode' => 'monitor', 
                            'School Email' => 'mail', 
                        ];
                    @endphp
                    @foreach($rows['Student Details'] as [$label, $value])
                        <div class="p-4 rounded-xl border border-gray-150 bg-gray-50/20 flex items-center gap-4 hover:bg-gray-50/50 transition">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $detailIcons[$label] ?? 'info' }}" class="w-5 h-5"></i>
                            </div>
                            <div class="space-y-0.5 overflow-hidden">
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider" style="margin:0;">{{ $label }}</p>
                                <p class="font-extrabold text-sm text-gray-900 truncate" style="margin:0;" title="{{ $value }}">{{ $value ?: 'Not provided' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="student-panel">
                <div class="student-panel-header">
                    <h2>Personal Information</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-4">
                    @php 
                        $personalIcons = [ 
                            'Full Name' => 'smile', 
                            'Gender' => 'users-round', 
                            'Date of Birth' => 'calendar', 
                            'Place of Birth' => 'map-pin', 
                            'Religion' => 'bookmark', 
                            'Address' => 'home', 
                        ];
                    @endphp
                    @foreach($rows['Personal Information'] as [$label, $value])
                        <div class="p-4 rounded-xl border border-gray-150 bg-gray-50/20 flex items-center gap-4 hover:bg-gray-50/50 transition {{ $label === 'Address' ? 'sm:col-span-2' : '' }}">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $personalIcons[$label] ?? 'info' }}" class="w-5 h-5"></i>
                            </div>
                            <div class="space-y-0.5 overflow-hidden">
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider" style="margin:0;">{{ $label }}</p>
                                <p class="font-extrabold text-sm text-gray-950 truncate" style="margin:0;" title="{{ $value }}">{{ $value ?: 'Not provided' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side: Contact & Guardian Info (1 col) -->
        <div class="lg:col-span-1">
            <div class="student-panel sticky top-6">
                <div class="student-panel-header" style="padding:0; margin-bottom: 16px;">
                    <h2>Guardian Contacts</h2>
                </div>

                <div class="space-y-4 pt-2">
                    @php 
                        $contactIcons = [ 
                            'Father' => 'user', 
                            'Mother' => 'user', 
                            'Parent Mobile' => 'smartphone', 
                            'Parent Email' => 'mail', 
                            'Emergency Contact' => 'alert-triangle', 
                            'Emergency Phone' => 'phone-call', 
                        ];
                    @endphp
                    @foreach($rows['Guardian Contact'] as [$label, $value])
                        @php 
                            $isEmergency = str_contains($label, 'Emergency'); 
                            $cardBg = $isEmergency ? 'border-rose-100 bg-rose-50/30' : 'border-gray-150 bg-gray-50/15'; 
                            $iconBg = $isEmergency ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100';
                        @endphp
                        <div class="p-4 rounded-xl border {{ $cardBg }} flex items-center gap-4 hover:opacity-95 transition">
                            <div class="w-10 h-10 rounded-xl border {{ $iconBg }} flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $contactIcons[$label] ?? 'phone' }}" class="w-5 h-5"></i>
                            </div>
                            <div class="space-y-0.5 overflow-hidden">
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider" style="margin:0;">{{ $label }}</p>
                                <p class="font-extrabold text-sm text-gray-900 truncate" style="margin:0;" title="{{ $value }}">{{ $value ?: 'Not provided' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
