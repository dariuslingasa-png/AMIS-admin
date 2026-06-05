@extends('student.layout')
@section('content')
@php $applicant = $student->applicant; $photoUrl = \App\Support\EnrollmentStorage::url($applicant?->photo_2x2_url); $fullName = $applicant?->full_name ?: $student->user->name; $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? '')); $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? '')); $rows = [ 'Student Details' => [ ['Student Number', $student->student_number], ['Grade Level', $student->grade_level], ['School Year', $student->school_year], ['Section', $section?->official_name ?? $student->section], ['Learning Mode', $section?->learning_mode ?? $applicant?->learning_mode], ['School Email', $student->school_email ?? $student->ms_email], ], 'Personal Information' => [ ['Full Name', $fullName], ['Gender', $applicant?->gender], ['Date of Birth', $applicant?->date_of_birth?->format('M d, Y')], ['Place of Birth', $applicant?->place_of_birth], ['Religion', $applicant?->religion], ['Address', $applicant?->street_address ?: $applicant?->address], ], 'Guardian Contact' => [ ['Father', $fatherName ?: null], ['Mother', $motherName ?: null], ['Parent Mobile', trim(($applicant->parent_country_code ?? '').' '.($applicant->parent_mobile ?? ''))], ['Parent Email', $applicant?->parent_email], ['Emergency Contact', $applicant?->emergency_name], ['Emergency Phone', $applicant?->emergency_phone], ], ];
@endphp
<div class="space-y-8">
<!-- Profile Header Card -->
<div class="bg-white border border-gray-100 rounded-2xl p-8 text-gray-900 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden">
<div class="flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left">
<div class="w-24 h-24 rounded-full bg-emerald-50 border-2 border-emerald-100/50 overflow-hidden flex items-center justify-center shrink-0 shadow-sm relative">
@if($photoUrl)
<img src="{{ $photoUrl }}" alt="{{ $fullName }}" class="w-full h-full object-cover">
@else
<span class="text-4xl font-black text-emerald-800 kid-font">{{ mb_substr($fullName, 0, 1) }}</span>

@endif
</div>

<div class="space-y-1">
<span class="bg-emerald-50 text-emerald-700 font-extrabold text-[10px] uppercase tracking-wider px-2.5 py-1 rounded-full inline-block border border-emerald-100/30"> Active Student
</span>

<h2 class="text-3xl font-black text-gray-950 kid-font leading-tight"> {{ $fullName }}
</h2>

<p class="text-gray-500 text-sm font-semibold"> Student ID: {{ $student->student_number }} • Grade: {{ $student->grade_level ?: 'Grade pending' }}
</p>

</div>

</div>

<div class="shrink-0 bg-emerald-50/40 border border-emerald-100/40 px-6 py-3.5 rounded-3xl text-center sm:text-right">
<p class="text-[9px] text-emerald-800 font-bold uppercase tracking-wider">Class Section</p>

<p class="text-xl font-black text-emerald-950 mt-0.5">{{ $section?->official_name ?? 'General' }}</p>

</div>

</div>

<!-- Details Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
<!-- Left Side: Student Details & Personal info (2 cols) -->
<div class="lg:col-span-2 space-y-8">
<!-- Student Details Card -->
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
<h3 class="font-black text-gray-800 text-lg flex items-center gap-2.5 kid-font border-b border-gray-50 pb-4">
<i data-lucide="graduation-cap" class="w-5 h-5 text-emerald-600"></i> Academic Profile
</h3>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
@php $detailIcons = [ 'Student Number' => 'fingerprint', 'Grade Level' => 'school', 'School Year' => 'calendar', 'Section' => 'layout', 'Learning Mode' => 'monitor', 'School Email' => 'mail', ];
@endphp
@foreach($rows['Student Details'] as [$label, $value])
<div class="p-4.5 rounded-2xl border border-gray-50 bg-gray-50/15 flex items-center gap-4">
<div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100/50 text-emerald-700 flex items-center justify-center shrink-0">
<i data-lucide="{{ $detailIcons[$label] ?? 'info' }}" class="w-5 h-5"></i>
</div>

<div class="space-y-0.5 overflow-hidden">
<p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">{{ $label }}</p>

<p class="font-extrabold text-sm text-gray-900 truncate" title="{{ $value }}">{{ $value ?: 'Not provided' }}</p>

</div>

</div>

@endforeach
</div>

</div>

<!-- Personal Information Card -->
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
<h3 class="font-black text-gray-800 text-lg flex items-center gap-2.5 kid-font border-b border-gray-50 pb-4">
<i data-lucide="user" class="w-5 h-5 text-emerald-600"></i> Personal Information
</h3>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
@php $personalIcons = [ 'Full Name' => 'smile', 'Gender' => 'users-round', 'Date of Birth' => 'cake', 'Place of Birth' => 'map-pin', 'Religion' => 'bookmark', 'Address' => 'home', ];
@endphp
@foreach($rows['Personal Information'] as [$label, $value])
<div class="p-4.5 rounded-2xl border border-gray-50 bg-gray-50/15 flex items-center gap-4 {{ $label === 'Address' ? 'sm:col-span-2' : '' }}">
<div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100/50 text-emerald-700 flex items-center justify-center shrink-0">
<i data-lucide="{{ $personalIcons[$label] ?? 'info' }}" class="w-5 h-5"></i>
</div>

<div class="space-y-0.5 overflow-hidden">
<p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">{{ $label }}</p>

<p class="font-extrabold text-sm text-gray-900 truncate" title="{{ $value }}">{{ $value ?: 'Not provided' }}</p>

</div>

</div>

@endforeach
</div>

</div>

</div>

<!-- Right Side: Contact & Guardian Info (1 col) -->
<div class="lg:col-span-1">
<div class="bg-white border border-gray-100 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6 sticky top-6">
<h3 class="font-black text-gray-800 text-lg flex items-center gap-2.5 kid-font border-b border-gray-50 pb-4">
<i data-lucide="shield-alert" class="w-5 h-5 text-emerald-600"></i> Guardian Contacts
</h3>

<div class="space-y-4 text-sm font-semibold">
@php $contactIcons = [ 'Father' => 'user', 'Mother' => 'user', 'Parent Mobile' => 'smartphone', 'Parent Email' => 'mail', 'Emergency Contact' => 'alert-triangle', 'Emergency Phone' => 'phone-call', ];
@endphp
@foreach($rows['Guardian Contact'] as [$label, $value])
@php $isEmergency = str_contains($label, 'Emergency'); $cardBg = $isEmergency ? 'border-rose-100 bg-rose-50/30' : 'border-gray-50 bg-gray-50/15'; $iconBg = $isEmergency ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100/50';
@endphp
<div class="p-4.5 rounded-2xl border {{ $cardBg }} flex items-center gap-4">
<div class="w-10 h-10 rounded-xl border {{ $iconBg }} flex items-center justify-center shrink-0">
<i data-lucide="{{ $contactIcons[$label] ?? 'phone' }}" class="w-5 h-5"></i>
</div>

<div class="space-y-0.5 overflow-hidden">
<p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">{{ $label }}</p>

<p class="font-extrabold text-sm text-gray-900 truncate" title="{{ $value }}">{{ $value ?: 'Not provided' }}</p>

</div>

</div>

@endforeach
</div>

</div>

</div>

</div>

</div>

@endsection
