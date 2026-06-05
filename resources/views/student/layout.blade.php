<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'AMIS Student Portal' }}</title>
<link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="https://unpkg.com/lucide@latest"></script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style> :root { --brand-green: #005943; --brand-green-hover: #004232; --brand-green-dark: #003326; --brand-green-light: #f0faf7; --brand-green-border: #d0efe6; } body, .kid-font { font-family: 'Plus Jakarta Sans', sans-serif !important; } .bg-emerald-600 { background-color: var(--brand-green) !important; } .bg-emerald-700 { background-color: var(--brand-green-hover) !important; } .bg-emerald-800 { background-color: var(--brand-green-dark) !important; } .hover\:bg-emerald-700:hover { background-color: var(--brand-green-hover) !important; } .hover\:bg-emerald-800:hover { background-color: var(--brand-green-dark) !important; } .text-emerald-600 { color: var(--brand-green) !important; } .text-emerald-700 { color: var(--brand-green-hover) !important; } .text-emerald-800 { color: var(--brand-green-dark) !important; } .border-emerald-100, .border-emerald-200 { border-color: var(--brand-green-border) !important; } .bg-emerald-50 { background-color: var(--brand-green-light) !important; } .selection\:bg-emerald-200::selection { background-color: #a3e2cf !important; } .focus\:border-emerald-500:focus { border-color: var(--brand-green) !important; } .bg-emerald-50\/20 { background-color: rgba(0, 89, 67, 0.05) !important; } .bg-emerald-50\/10 { background-color: rgba(0, 89, 67, 0.03) !important; } .border-emerald-100\/30 { border-color: rgba(0, 89, 67, 0.12) !important; } .border-emerald-100\/50 { border-color: rgba(0, 89, 67, 0.22) !important; } .bg-emerald-100 { background-color: #d0efe6 !important; } .text-emerald-900 { color: #00221a !important; } .student-shell { min-height: 100vh; padding-top: 4rem; background: #f8fafc; } .student-sidebar { position: fixed !important; left: 0; top: 4rem; z-index: 40; width: 16rem; height: calc(100vh - 4rem); border-right: 1px solid #e5e7eb; background: #fff; transform: translateX(-100%); transition: transform .2s ease; } .student-sidebar.is-open { transform: translateX(0); } .student-content { min-height: calc(100vh - 4rem); padding: 1.5rem; } .student-content-inner { width: 100%; max-width: 80rem; margin: 0 auto; } .sidebar-overlay { position: fixed; left: 0; right: 0; bottom: 0; top: 4rem; z-index: 30; background: rgba(17, 24, 39, .42); } @media (min-width: 768px) { .student-sidebar { transform: translateX(0) !important; } .student-content { margin-left: 16rem; } .student-menu-button, .student-sidebar-close, .sidebar-overlay { display: none !important; } } @media (max-width: 767px) { .student-content { padding: 1rem; } }
</style>

</head>
<body class="min-h-screen text-gray-800 antialiased selection:bg-emerald-200 selection:text-emerald-900">
@auth
@php $layoutStudent = $student ?? null; $layoutApplicant = $layoutStudent?->applicant; $layoutPhotoUrl = \App\Support\EnrollmentStorage::url($layoutApplicant?->photo_2x2_url); $layoutName = $layoutApplicant?->full_name ?: Auth::user()->name; $layoutFirstName = $layoutApplicant?->first_name ?: Auth::user()->name; $layoutInitial = mb_substr($layoutFirstName, 0, 1); $layoutEmail = Auth::user()->email ?: ($layoutStudent?->school_email ?? ''); $layoutStudentNo = $layoutStudent?->student_number ?: 'Student'; $layoutNotifications = [ [ 'title' => 'Announcements page is ready', 'body' => 'View the latest school reminders in the student announcement center.', 'icon' => 'megaphone', 'href' => route('student.announcements'), 'tone' => 'emerald', 'time' => 'New', ], [ 'title' => 'Check your weekly schedule', 'body' => 'Confirm class times and Teams links before attending your next class.', 'icon' => 'calendar-clock', 'href' => route('student.schedule'), 'tone' => 'sky', 'time' => 'Today', ], [ 'title' => 'Payment proof reminder', 'body' => 'Upload receipts with the correct reference number for finance review.', 'icon' => 'receipt-text', 'href' => route('student.payments.history'), 'tone' => 'amber', 'time' => 'Reminder', ], ]; $workspaceSections = [ [ 'active' => request()->routeIs('student.dashboard') || request()->routeIs('student.announcements'), 'icon' => 'layout-dashboard', 'iconClass' => 'text-slate-600', 'headerClass' => 'text-slate-700', 'activeClass' => 'sidebar-link-active-slate', 'title' => 'Dashboard', 'links' => [ ['Overview', 'layout-dashboard', route('student.dashboard'), request()->routeIs('student.dashboard')], ['Announcements', 'megaphone', route('student.announcements'), request()->routeIs('student.announcements')], ['Student ID', 'contact', route('student.dashboard').'#student-id', false], ], ], [ 'active' => request()->routeIs('student.schedule') || request()->routeIs('student.subjects') || request()->routeIs('student.grades'), 'icon' => 'book-open-check', 'iconClass' => 'text-sky-600', 'headerClass' => 'text-sky-700', 'activeClass' => 'sidebar-link-active-sky', 'title' => 'Academic', 'links' => [ ['My Schedule', 'calendar', route('student.schedule'), request()->routeIs('student.schedule')], ['Subjects', 'book-open-check', route('student.subjects'), request()->routeIs('student.subjects')], ['Grades', 'chart-no-axes-combined', route('student.grades'), request()->routeIs('student.grades')], ], ], [ 'active' => request()->routeIs('student.billing') || request()->routeIs('student.payments.history'), 'icon' => 'wallet', 'iconClass' => 'text-amber-600', 'headerClass' => 'text-amber-700', 'activeClass' => 'sidebar-link-active-amber', 'title' => 'Finance', 'links' => [ ['My Billing (SOA)', 'credit-card', route('student.billing'), request()->routeIs('student.billing')], ['Payment History', 'receipt-text', route('student.payments.history'), request()->routeIs('student.payments.history')], ], ], [ 'active' => request()->routeIs('student.profile') || request()->routeIs('student.settings'), 'icon' => 'user-round', 'iconClass' => 'text-violet-600', 'headerClass' => 'text-violet-700', 'activeClass' => 'sidebar-link-active-violet', 'title' => 'Account', 'links' => [ ['My Profile', 'user-round', route('student.profile'), request()->routeIs('student.profile')], ['Settings', 'settings', route('student.settings'), request()->routeIs('student.settings')], ], ], ]; $activeSection = collect($workspaceSections)->firstWhere('active', true) ?? $workspaceSections[0];
@endphp
<div class="student-shell" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
<nav class="fixed top-0 z-50 w-full border-b border-gray-200 bg-white">
<div class="px-3 py-3 lg:px-5 lg:pl-3">
<div class="flex items-center justify-between">
<button type="button" @click="sidebarOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())" class="student-menu-button inline-flex items-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 md:hidden" aria-label="Open sidebar">
<i data-lucide="menu" class="h-6 w-6"></i>
</button>

<a href="{{ route('student.dashboard') }}" class="ms-2 flex items-center md:me-24">
<img src="{{ asset('images/AMIS_Logo.png') }}" class="me-3 h-8 w-8 object-contain" alt="AMIS Logo">
<span class="self-center whitespace-nowrap text-xl font-semibold">AMIS Student Portal</span>

</a>

<div class="flex items-center gap-3" x-data="{ notificationsOpen: false, profileOpen: false }" @keydown.escape.window="notificationsOpen = false; profileOpen = false">
<div class="relative">
<button type="button" @click="notificationsOpen = !notificationsOpen; profileOpen = false; $nextTick(() => window.lucide && window.lucide.createIcons())" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Notifications">
<i data-lucide="bell" class="h-5 w-5"></i>
</button>

<span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-amber-400 ring-2 ring-white"></span>

<div x-cloak x-show="notificationsOpen" x-transition.origin.top.right.duration.150ms @click.outside="notificationsOpen = false" class="absolute right-0 mt-3 w-96 max-w-[calc(100vw-2rem)] overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
<div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
<div>
<p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600">Notifications</p>

<h3 class="font-bold text-gray-900">Recent Updates</h3>

</div>

<span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold text-amber-700 ring-1 ring-amber-100">{{ count($layoutNotifications) }} new</span>

</div>

<div class="divide-y divide-gray-100">
@foreach($layoutNotifications as $notice)
@php $noticeTone = [ 'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100', 'sky' => 'bg-sky-50 text-sky-700 border-sky-100', 'amber' => 'bg-amber-50 text-amber-700 border-amber-100', ][$notice['tone']] ?? 'bg-emerald-50 text-emerald-700 border-emerald-100';
@endphp
<a href="{{ $notice['href'] }}" class="flex gap-3 px-4 py-3 hover:bg-gray-50">
<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border {{ $noticeTone }}">
<i data-lucide="{{ $notice['icon'] }}" class="h-5 w-5"></i>
</span>

<span class="min-w-0 flex-1">
<span class="flex items-center justify-between gap-3">
<span class="truncate text-sm font-bold text-gray-900">{{ $notice['title'] }}</span>

<span class="shrink-0 text-[10px] font-bold text-gray-400">{{ $notice['time'] }}</span>

</span>

<span class="mt-0.5 block text-xs font-semibold leading-relaxed text-gray-500">{{ $notice['body'] }}</span>

</span>

</a>

@endforeach
</div>

</div>

</div>

<div class="relative">
<button type="button" @click="profileOpen = !profileOpen; notificationsOpen = false; $nextTick(() => window.lucide && window.lucide.createIcons())" class="flex rounded-full bg-gray-800 text-sm focus:ring-4 focus:ring-gray-300" aria-label="Open user menu">
<span class="inline-flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-primary-100 text-primary-700">
@if($layoutPhotoUrl)
<img src="{{ $layoutPhotoUrl }}" alt="{{ $layoutFirstName }}" class="h-full w-full object-cover">
@else {{ $layoutInitial }}
@endif
</span>

</button>

<div x-cloak x-show="profileOpen" x-transition.origin.top.right.duration.150ms @click.outside="profileOpen = false" class="absolute right-0 mt-3 w-72 list-none divide-y divide-gray-100 rounded-lg bg-white text-base shadow">
<div class="px-4 py-3">
<p class="truncate text-sm text-gray-900">{{ $layoutName }}</p>

<p class="truncate text-sm font-medium text-gray-500">{{ $layoutEmail ?: $layoutStudentNo }}</p>

</div>

<ul class="py-1">
<li><a href="{{ route('student.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
</li>

<li><a href="{{ route('student.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
</li>

<li>
<form method="POST" action="{{ route('student.logout') }}">
@csrf
<button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">Sign out</button>

</form>

</li>

</ul>

</div>

</div>

</div>

</div>

</div>

</nav>

<div x-cloak x-show="sidebarOpen" x-transition.opacity.duration.150ms @click="sidebarOpen = false" class="sidebar-overlay md:hidden"></div>

<aside class="student-sidebar" :class="sidebarOpen ? 'is-open' : ''" aria-label="Sidebar">
<div class="flex h-full flex-col px-3 py-4">
<button type="button" @click="sidebarOpen = false" class="student-sidebar-close mb-3 inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-100 md:hidden" aria-label="Close sidebar">
<i data-lucide="x" class="h-5 w-5"></i>
</button>

<div class="sidebar-section-container">
<div class="sidebar-section-header">
<i data-lucide="layout-grid" class="sidebar-section-header-icon text-slate-600"></i>
<span class="font-extrabold text-xs tracking-wider uppercase text-slate-700">Main Menu</span>

</div>

<div class="space-y-1">
@php
$sidebarLinks = [
['student.dashboard', 'layout-dashboard', 'Dashboard', 'sidebar-link-active-slate'],
['student.schedule', 'calendar', 'My Schedule', 'sidebar-link-active-sky'],
['student.subjects', 'book-open-check', 'Subjects', 'sidebar-link-active-emerald'],
['student.grades', 'chart-no-axes-combined', 'Grades', 'sidebar-link-active-violet'],
['student.announcements', 'megaphone', 'Announcements', 'sidebar-link-active-emerald'],
['student.billing', 'credit-card', 'My Billing (SOA)', 'sidebar-link-active-amber'],
['student.payments.history', 'receipt-text', 'Payment History', 'sidebar-link-active-amber'],
['student.profile', 'user-round', 'My Profile', 'sidebar-link-active-violet'],
['student.settings', 'settings', 'Settings', 'sidebar-link-active-slate'],
];
@endphp
@foreach ($sidebarLinks as [$route, $icon, $label, $activeClass])
@php $isActive = request()->routeIs($route); @endphp
<a href="{{ route($route) }}" @click="sidebarOpen = false" class="sidebar-link{{ $isActive ? ' '.$activeClass : '' }}">
<i data-lucide="{{ $icon }}"></i>
<span>{{ $label }}</span>
</a>
@endforeach
</div>
</div>

<div class="sidebar-profile-card mt-auto">
<div class="sidebar-profile-info">
<span class="sidebar-profile-label">Signed in as</span>

<span class="sidebar-profile-name" title="{{ $layoutName }}">{{ $layoutName }}</span>

<span class="mt-0.5 truncate text-[11px] font-semibold text-slate-400">{{ $layoutStudentNo }}</span>

</div>

<form method="POST" action="{{ route('student.logout') }}">
@csrf
<button type="submit" class="sidebar-logout-btn">
<i data-lucide="log-out" class="h-4 w-4"></i>
<span>Sign Out</span>

</button>

</form>

</div>

</div>

</aside>

<main class="student-content">
@if (session('success'))
<div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>

@endif
@if (session('error'))
<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>

@endif
<div class="student-content-inner">
@yield('content')
</div>

<footer class="mt-8 border-t border-gray-200 pt-6 text-center text-xs font-semibold text-gray-400">
<p>&copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.</p>

</footer>

</main>

</div>

@else
@yield('content')
@endauth
<script> if (window.lucide) { window.lucide.createIcons(); } document.addEventListener('DOMContentLoaded', () => { if (window.lucide) { window.lucide.createIcons(); } }); setTimeout(() => window.lucide && window.lucide.createIcons(), 50); setTimeout(() => window.lucide && window.lucide.createIcons(), 300); setTimeout(() => window.lucide && window.lucide.createIcons(), 1000);
</script>

</body>
</html>
