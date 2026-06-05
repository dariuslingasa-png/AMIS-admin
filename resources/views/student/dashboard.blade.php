@extends('student.layout')
@section('content')
@php $applicant = $student->applicant; $studentName = $applicant ? $applicant->full_name : $student->user->name; $firstName = $applicant?->first_name ?? $student->user->name; $fatherName = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? '')); $motherName = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? '')); $parentMobile = trim(($applicant->parent_country_code ?? '').' '.($applicant->parent_mobile ?? '')); $parentEmail = $applicant->parent_email ?? null; $emergencyName = $applicant->emergency_name ?? null; $emergencyPhone = trim((string) ($applicant->emergency_phone ?? '')); $photoUrl = \App\Support\EnrollmentStorage::url($applicant?->photo_2x2_url); $scheduledSubjects = $subjects->filter(fn ($subject) => trim((string) $subject->schedule) !== '')->count(); $teamsReady = filled($section?->ms_team_url);
@endphp
<div class="space-y-6" x-data="{ copied: false, idModalOpen: false }" @keydown.escape.window="idModalOpen = false">
<section class="dashboard-hero text-white">
<div class="max-w-3xl">
<span class="hero-eyebrow">
<i data-lucide="graduation-cap" class="mr-2 h-4 w-4"></i> Student Workspace
</span>

<h1 class="mt-4 text-3xl font-black leading-tight md:text-4xl"> Assalamu Alaikum, {{ $firstName }}.
</h1>

<p class="mt-3 max-w-2xl text-sm font-semibold leading-relaxed text-emerald-50/90"> Monitor your subjects, class schedule, Microsoft Teams access, billing status, and student profile from one focused AMIS workspace.
</p>

<div class="mt-5 flex flex-wrap gap-3">
<a href="{{ route('student.schedule') }}" class="hero-button hero-button-light">
<i data-lucide="calendar" class="h-4 w-4"></i> View Schedule
</a>

<a href="{{ route('student.billing') }}" class="hero-button hero-button-ghost">
<i data-lucide="wallet" class="h-4 w-4"></i> Open Billing
</a>

</div>

</div>

<div class="hidden min-w-[14rem] rounded-2xl border border-white/20 bg-white/10 p-5 lg:block">
<p class="text-xs font-bold uppercase tracking-wider text-emerald-100">School Year</p>

<p class="mt-1 text-2xl font-black">{{ $student->school_year }}</p>

<p class="mt-4 text-xs font-bold uppercase tracking-wider text-emerald-100">Section</p>

<p class="mt-1 truncate text-lg font-black">{{ $section?->official_name ?? 'General' }}</p>

</div>

</section>

<section id="modules" class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm">
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
<div>
<h2 class="text-xl font-bold text-slate-950">Student Modules</h2>

<p class="mt-1 text-sm text-slate-500">Open the main student workspaces from this launcher.</p>

</div>

<div class="flex flex-wrap gap-2">
<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $subjects->count() }} Subjects</span>

<span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700">{{ $scheduledSubjects }} Scheduled</span>

</div>

</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
<x-dashboard.module-card :href="route('student.schedule')" icon="calendar" name="Schedule" owner="Academic Office" summary="Weekly timetable, class times, and teachers" accent="sky" shape="soft" />
<x-dashboard.module-card :href="route('student.subjects')" icon="book-open-check" name="Subjects" owner="Registrar Office" summary="Registered subjects and class channel status" accent="emerald" shape="arch" />
<x-dashboard.module-card :href="route('student.billing')" icon="wallet" name="Billing" owner="Finance Office" summary="Statement of account and receipt upload" accent="amber" shape="soft" />
<x-dashboard.module-card :href="route('student.profile')" icon="user-round" name="Profile" owner="Student Records" summary="Student, guardian, and emergency details" accent="violet" shape="circle" />
</div>

</section>

<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
<article class="dashboard-card kpi-card">
<div class="flex items-start justify-between gap-4">
<span class="kpi-icon"><i data-lucide="book-open-check" class="h-5 w-5"></i></span>

<span class="trend-pill trend-pill-up"><i data-lucide="check" class="h-3.5 w-3.5"></i> Active</span>

</div>

<div class="mt-5">
<p class="text-sm font-medium text-slate-500">Enrolled Subjects</p>

<p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $subjects->count() }}</p>

</div>

</article>

<article class="dashboard-card kpi-card">
<div class="flex items-start justify-between gap-4">
<span class="kpi-icon"><i data-lucide="calendar-clock" class="h-5 w-5"></i></span>

<span class="trend-pill trend-pill-up"><i data-lucide="clock" class="h-3.5 w-3.5"></i> Listed</span>

</div>

<div class="mt-5">
<p class="text-sm font-medium text-slate-500">Scheduled Classes</p>

<p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $scheduledSubjects }}</p>

</div>

</article>

<article class="dashboard-card kpi-card">
<div class="flex items-start justify-between gap-4">
<span class="kpi-icon"><i data-lucide="users" class="h-5 w-5"></i></span>

<span class="trend-pill {{ $teamsReady ? 'trend-pill-up' : 'trend-pill-down' }}">
<i data-lucide="{{ $teamsReady ? 'check' : 'clock' }}" class="h-3.5 w-3.5"></i> {{ $teamsReady ? 'Ready' : 'Pending' }}
</span>

</div>

<div class="mt-5">
<p class="text-sm font-medium text-slate-500">MS Teams</p>

<p class="mt-1 text-3xl font-bold tracking-tight text-slate-950">{{ $teamsReady ? 'Online' : 'Setup' }}</p>

</div>

</article>

<article class="dashboard-card kpi-card">
<div class="flex items-start justify-between gap-4">
<span class="kpi-icon"><i data-lucide="contact" class="h-5 w-5"></i></span>

<span class="trend-pill trend-pill-up"><i data-lucide="shield-check" class="h-3.5 w-3.5"></i> Official</span>

</div>

<div class="mt-5">
<p class="text-sm font-medium text-slate-500">Student ID</p>

<p class="mt-1 truncate text-3xl font-bold tracking-tight text-slate-950">{{ $student->student_number }}</p>

</div>

</article>

</section>

<div class="grid gap-6 lg:grid-cols-12">
<section class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm lg:col-span-8">
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
<div>
<h2 class="text-xl font-bold text-slate-950">Current Classes</h2>

<p class="mt-1 text-sm text-slate-500">{{ $section ? $section->section_title : 'Assigned Section' }} / {{ $student->school_year }}</p>

</div>

<a href="{{ route('student.subjects') }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-700 hover:bg-emerald-100"> View all
<i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
</a>

</div>

@if($subjects->isNotEmpty())
<div class="premium-table-wrap">
<table class="premium-table">
<thead>
<tr>
<th>Subject</th>

<th>Teacher</th>

<th>Schedule</th>

<th>Status</th>

</tr>

</thead>

<tbody>
@foreach($subjects as $subject)
<tr>
<td>
<span class="font-bold text-slate-900">{{ $subject->subject_name }}</span>

<span class="mt-0.5 block text-[10px] font-bold text-emerald-600">SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}</span>

</td>

<td>{{ $subject->teacher_name ?: 'To Be Assigned' }}</td>

<td>{{ $subject->schedule ?: 'To Be Announced' }}</td>

<td>
@if($subject->schedule)
<span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Scheduled</span>

@else
<span class="inline-flex items-center rounded-full border border-amber-100 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>

@endif
</td>

</tr>

@endforeach
</tbody>

</table>

</div>

@else
<div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center">
<i data-lucide="calendar" class="mx-auto h-10 w-10 text-emerald-600"></i>
<h3 class="mt-3 font-bold text-slate-900">No subjects yet</h3>

<p class="mt-1 text-xs font-semibold text-slate-500">Your subjects will appear here once section allocation is finalized.</p>

</div>

@endif
</section>

<aside id="quick-actions" class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm lg:col-span-4">
<div class="mb-5">
<h2 class="text-xl font-bold text-slate-950">Quick Actions</h2>

<p class="mt-1 text-sm text-slate-500">Common student portal tasks.</p>

</div>

<div class="space-y-3">
<x-dashboard.quick-action :href="route('student.schedule')" icon="calendar" label="Open Schedule" meta="Review class days and times" />
<x-dashboard.quick-action :href="route('student.billing')" icon="upload" label="Upload Receipt" meta="Submit proof of payment" />
<x-dashboard.quick-action :href="route('student.announcements')" icon="megaphone" label="School Updates" meta="Read latest announcements" />
<x-dashboard.quick-action :href="route('student.profile')" icon="user-round" label="Student Profile" meta="Check personal and guardian records" />
</div>

</aside>

</div>

<div class="grid gap-6 lg:grid-cols-12">
<section id="student-id" class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm lg:col-span-5">
<div class="mb-5 flex items-center justify-between gap-4">
<div>
<h2 class="text-xl font-bold text-slate-950">Digital Student ID</h2>

<p class="mt-1 text-sm text-slate-500">Preview and print your portal ID.</p>

</div>

<button type="button" @click="idModalOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())" class="inline-flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-700 hover:bg-emerald-100">
<i data-lucide="maximize-2" class="h-3.5 w-3.5"></i> Open
</button>

</div>

<div class="mx-auto flex max-w-sm flex-col justify-between rounded-2xl border border-emerald-100 bg-white p-5">
<div class="flex items-center gap-3 border-b border-emerald-50 pb-3">
<img src="{{ asset('images/AMIS_Logo.png') }}" class="h-10 w-10 object-contain" alt="AMIS Logo">
<div>
<p class="text-xs font-black uppercase tracking-wider text-emerald-800">Al Munawwara Islamic School</p>

<p class="text-[10px] font-bold text-slate-400">Student Identification</p>

</div>

</div>

<div class="mt-5 flex items-center gap-4">
<div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-emerald-100 bg-emerald-50">
@if($photoUrl)
<img src="{{ $photoUrl }}" alt="{{ $studentName }}" class="h-full w-full object-cover">
@else
<span class="text-3xl font-black text-emerald-800">{{ mb_substr($firstName, 0, 1) }}</span>

@endif
</div>

<div class="min-w-0">
<p class="truncate text-base font-black text-slate-950">{{ $studentName }}</p>

<p class="mt-1 text-xs font-extrabold text-emerald-700">ID: {{ $student->student_number }}</p>

<p class="mt-0.5 truncate text-xs font-semibold text-slate-500">{{ $student->grade_level }} / {{ $section?->official_name ?? 'General' }}</p>

</div>

</div>

</div>

</section>

<section class="rounded-2xl border border-slate-200/70 bg-white p-6 shadow-sm lg:col-span-7">
<div class="mb-5 flex items-center justify-between gap-4">
<div>
<h2 class="text-xl font-bold text-slate-950">Microsoft Teams Access</h2>

<p class="mt-1 text-sm text-slate-500">Use these details for online class access.</p>

</div>

<span class="rounded-full border {{ $teamsReady ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-amber-100 bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-bold"> {{ $teamsReady ? 'Room ready' : 'Room pending' }}
</span>

</div>

<div class="grid gap-4 sm:grid-cols-2">
<div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
<p class="text-[10px] font-black uppercase tracking-wider text-slate-400">School Username</p>

<p class="mt-1 truncate text-sm font-bold text-slate-900">{{ $student->school_email ?? 'Not assigned yet' }}</p>

@if($student->school_email)
<button type="button" @click="navigator.clipboard.writeText('{{ $student->school_email }}'); copied = true; setTimeout(() => copied = false, 2000)" class="mt-3 rounded-lg border border-emerald-100 bg-white px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-50">
<span x-show="!copied">Copy</span>

<span x-show="copied">Copied</span>

</button>

@endif
</div>

<div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
<p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Initial Password</p>

<p class="mt-1 text-sm font-bold text-slate-900">
@if($student->temp_password) Amis@*****
@else Same as portal password
@endif
</p>

<p class="mt-3 text-xs font-semibold text-slate-500">Check parent SMS/Email for credential details.</p>

</div>

</div>

@if($teamsReady)
<a href="{{ $section->ms_team_url }}" target="_blank" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
<i data-lucide="external-link" class="h-4 w-4"></i> Open MS Teams Portal
</a>

@endif
</section>

</div>

<div x-cloak x-show="idModalOpen" x-transition.opacity.duration.150ms class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/65 px-4 py-6 print:hidden" role="dialog" aria-modal="true">
<div class="absolute inset-0" @click="idModalOpen = false"></div>

<div class="relative w-full max-w-5xl overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-2xl">
<div class="h-2 bg-emerald-700"></div>

<div class="flex items-center justify-between gap-4 border-b border-emerald-100/50 px-5 py-4">
<div>
<p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600">Digital Student ID</p>

<h3 class="text-base font-black text-gray-900">AMIS Identification Card</h3>

</div>

<button type="button" @click="idModalOpen = false" class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-gray-500 hover:bg-gray-100">
<i data-lucide="x" class="h-5 w-5"></i>
</button>

</div>

<div class="bg-emerald-50/30 p-5 sm:p-8">
<div class="mx-auto grid max-w-4xl gap-5 md:grid-cols-2">
<section class="rounded-2xl border border-emerald-100 bg-white p-7 text-gray-800">
<div class="flex items-center gap-3 border-b border-emerald-50 pb-3">
<img src="{{ asset('images/AMIS_Logo.png') }}" class="h-14 w-14 object-contain" alt="AMIS Logo">
<div>
<h4 class="font-black uppercase tracking-wider text-emerald-800">Al Munawwara</h4>

<p class="text-xs font-bold text-gray-400">Islamic School</p>

</div>

</div>

<div class="mt-6 flex items-center gap-5">
<div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50">
@if($photoUrl)
<img src="{{ $photoUrl }}" alt="{{ $studentName }}" class="h-full w-full object-cover">
@else
<span class="text-5xl font-extrabold text-emerald-800">{{ mb_substr($firstName, 0, 1) }}</span>

@endif
</div>

<div class="min-w-0">
<h5 class="truncate text-2xl font-black text-gray-900">{{ $studentName }}</h5>

<p class="mt-1 text-sm font-extrabold text-emerald-600">ID: {{ $student->student_number }}</p>

<p class="mt-1 text-sm font-semibold text-gray-500">{{ $student->grade_level }} / {{ $section?->official_name ?? 'General' }}</p>

</div>

</div>

<div class="mt-6 flex items-center justify-between border-t border-emerald-50 pt-3 text-xs font-black uppercase tracking-wider text-gray-400">
<span>SY {{ $student->school_year }}</span>

<span class="text-emerald-600">Official ID</span>

</div>

</section>

<section class="rounded-2xl border border-emerald-100 bg-white p-7 text-gray-800">
<div class="border-b border-emerald-50 pb-3">
<h4 class="font-black text-emerald-800">Parent / Guardian Info</h4>

<p class="text-xs font-bold text-gray-400">For school verification and emergency use</p>

</div>

<div class="mt-5 grid gap-3 text-sm">
<div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
<p class="text-[10px] font-black uppercase text-gray-400">Father</p>

<p class="truncate font-extrabold text-gray-800">{{ $fatherName ?: 'Not provided' }}</p>

</div>

<div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
<p class="text-[10px] font-black uppercase text-gray-400">Mother</p>

<p class="truncate font-extrabold text-gray-800">{{ $motherName ?: 'Not provided' }}</p>

</div>

<div class="rounded-xl border border-emerald-100 bg-emerald-50/60 px-4 py-3">
<p class="text-[10px] font-black uppercase text-emerald-700">Parent Contact</p>

<p class="truncate font-extrabold text-gray-800">{{ $parentMobile ?: 'Not provided' }}</p>

<p class="truncate font-semibold text-gray-500">{{ $parentEmail ?: 'Email not provided' }}</p>

</div>

<div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3">
<p class="text-[10px] font-black uppercase text-rose-600">Emergency Contact</p>

<p class="truncate font-extrabold text-gray-800">{{ $emergencyName ?: 'Not provided' }}</p>

<p class="truncate font-semibold text-gray-500">{{ $emergencyPhone ?: 'Phone not provided' }}</p>

</div>

</div>

</section>

</div>

</div>

</div>

</div>

</div>

@endsection
