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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body class="student-body">
@auth
@php 
    $layoutStudent = $student ?? null; 
    $layoutApplicant = $layoutStudent?->applicant; 
    $layoutPhotoUrl = \App\Support\EnrollmentStorage::url($layoutApplicant?->photo_2x2_url); 
    $layoutName = $layoutApplicant?->full_name ?: Auth::user()->name; 
    $layoutFirstName = $layoutApplicant?->first_name ?: Auth::user()->name; 
    $layoutInitial = mb_substr($layoutFirstName, 0, 1); 
    $layoutEmail = Auth::user()->email ?: ($layoutStudent?->school_email ?? ''); 
    $layoutStudentNo = $layoutStudent?->student_number ?: 'Student'; 
    $layoutNotifications = [ 
        [ 
            'title' => 'Announcements page is ready', 
            'body' => 'View the latest school reminders in the student announcement center.', 
            'icon' => 'megaphone', 
            'href' => route('student.announcements'), 
            'tone' => 'emerald', 
            'time' => 'New', 
        ], 
        [ 
            'title' => 'Check your weekly schedule', 
            'body' => 'Confirm class times and Teams links before attending your next class.', 
            'icon' => 'calendar-clock', 
            'href' => route('student.schedule'), 
            'tone' => 'sky', 
            'time' => 'Today', 
        ], 
        [ 
            'title' => 'Payment proof reminder', 
            'body' => 'Upload receipts with the correct reference number for finance review.', 
            'icon' => 'receipt-text', 
            'href' => route('student.payments.history'), 
            'tone' => 'amber', 
            'time' => 'Reminder', 
        ], 
    ]; 
    $menu = [
        ['route' => 'student.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard', 'tone' => 'emerald'],
        ['route' => 'student.schedule', 'icon' => 'calendar', 'label' => 'My Schedule', 'tone' => 'sky'],
        ['route' => 'student.subjects', 'icon' => 'book-open-check', 'label' => 'Subjects', 'tone' => 'emerald'],
        ['href' => config('services.ebook.url'), 'icon' => 'book-open', 'label' => 'eBook', 'tone' => 'indigo'],
        ['route' => 'student.grades', 'icon' => 'chart-no-axes-combined', 'label' => 'Grades', 'tone' => 'violet'],
        ['route' => 'student.announcements', 'icon' => 'megaphone', 'label' => 'Announcements', 'tone' => 'emerald'],
        ['route' => 'student.billing', 'icon' => 'credit-card', 'label' => 'My Billing (SOA)', 'tone' => 'amber'],
        ['route' => 'student.payments.history', 'icon' => 'receipt-text', 'label' => 'Payment History', 'tone' => 'amber'],
        ['route' => 'student.profile', 'icon' => 'user-round', 'label' => 'My Profile', 'tone' => 'violet'],
    ];
@endphp
<div class="student-shell" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <!-- Sidebar Backdrop for mobile -->
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" 
         class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-xs transition-opacity duration-300 lg:hidden"
         style="display: none;"></div>

    <aside class="student-sidebar" :class="{ 'open': sidebarOpen }">
        <div class="student-sidebar-top">
            <!-- Mobile close button -->
            <button type="button" @click="sidebarOpen = false" class="student-mobile-close-btn" aria-label="Close Sidebar" style="display: none; position: absolute; top: 18px; right: 18px; width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--s-border); background: var(--s-surface); align-items: center; justify-content: center; color: var(--t-secondary); cursor: pointer;">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>

            <a href="{{ route('student.dashboard') }}" class="student-brand">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS">
                <div class="student-brand-text">
                    <strong>AMIS</strong>
                    <small>Student Portal</small>
                </div>
            </a>

            <div class="student-nav-section">
                <span class="student-nav-label">Navigation</span>
                <nav class="student-nav">
                    @foreach($menu as $item)
                        @php
                            $itemRoute = $item['route'] ?? null;
                            $itemHref = $itemRoute ? route($itemRoute) : $item['href'];
                            $isActive = $itemRoute ? request()->routeIs($itemRoute.'*') : false;
                        @endphp
                        <a href="{{ $itemHref }}" class="student-nav-{{ $item['tone'] }} {{ $isActive ? 'active' : '' }}">
                            <span class="student-nav-icon-box">
                                <i data-lucide="{{ $item['icon'] }}" class="student-nav-icon"></i>
                            </span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </div>

        <div class="student-sidebar-bottom">
            <div class="student-profile-card" onclick="window.location.href='{{ route('student.profile') }}'">
                <div class="student-avatar overflow-hidden">
                    @if($layoutPhotoUrl)
                        <img src="{{ $layoutPhotoUrl }}" alt="{{ $layoutFirstName }}" class="h-full w-full object-cover">
                    @else
                        {{ $layoutInitial }}
                    @endif
                </div>
                <div class="student-profile-info">
                    <strong>{{ $layoutName }}</strong>
                    <small>{{ $layoutStudentNo }}</small>
                </div>
                <form method="POST" action="{{ route('student.logout') }}" style="margin:0;" onclick="event.stopPropagation();">
                    @csrf
                    <button type="submit" class="student-profile-chevron" title="Sign Out">
                        <i data-lucide="log-out" style="width:14px;height:14px;"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="student-main">
        <div class="student-container">
            <header class="student-topbar" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px;">
            <div class="student-topbar-start" style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                <button type="button" @click="sidebarOpen = true" class="student-mobile-menu-btn" aria-label="Toggle Sidebar" style="display: none; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--s-border); background: var(--s-surface); color: var(--t-secondary); cursor: pointer; flex-shrink: 0;">
                    <i data-lucide="menu" style="width: 20px; height: 20px;"></i>
                </button>
                <div style="min-width: 0;">
                    <div class="student-topbar-eyebrow">Al Munawwara Islamic School</div>
                    <h1 style="margin: 0; font-size: clamp(20px, 4.5vw, 28px); font-weight: 800; color: var(--t-primary); letter-spacing: -0.4px; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $heading ?? 'Student Portal' }}</h1>
                </div>
            </div>
            <div class="student-topbar-end" x-data="{ notificationsOpen: false, profileOpen: false }" @keydown.escape.window="notificationsOpen = false; profileOpen = false">
                <!-- Notifications Dropdown -->
                <div class="relative">
                    <button type="button" @click="notificationsOpen = !notificationsOpen; profileOpen = false; $nextTick(() => window.lucide && window.lucide.createIcons())" class="student-icon-btn" aria-label="Notifications">
                        <i data-lucide="bell"></i>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-amber-400 ring-2 ring-white"></span>
                    </button>
                    <div x-cloak x-show="notificationsOpen" x-transition.origin.top.right.duration.150ms @click.outside="notificationsOpen = false" class="absolute right-0 mt-3 w-96 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-gray-150 bg-white shadow-xl z-50">
                        <div class="flex items-center justify-between border-b border-gray-150 px-4 py-3">
                            <div class="text-left">
                                <p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600">Notifications</p>
                                <h3 class="text-sm font-bold text-gray-900">Recent Updates</h3>
                            </div>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold text-amber-700 ring-1 ring-amber-100">{{ count($layoutNotifications) }} new</span>
                        </div>
                        <div class="divide-y divide-gray-150">
                            @foreach($layoutNotifications as $notice)
                                @php 
                                    $noticeTone = [
                                        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'sky' => 'bg-sky-50 text-sky-700 border-sky-100',
                                        'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    ][$notice['tone']] ?? 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                @endphp
                                <a href="{{ $notice['href'] }}" class="flex gap-3 px-4 py-3 hover:bg-gray-50 text-left">
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

                <a href="{{ route('student.settings') }}" class="student-icon-btn" aria-label="Settings">
                    <i data-lucide="settings"></i>
                </a>
                <div class="student-topbar-divider"></div>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <button type="button" @click="profileOpen = !profileOpen; notificationsOpen = false; $nextTick(() => window.lucide && window.lucide.createIcons())" class="flex rounded-full focus:ring-4 focus:ring-gray-300" aria-label="Open user menu">
                        <span class="inline-flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-emerald-100 text-emerald-700 font-bold text-sm">
                            @if($layoutPhotoUrl)
                                <img src="{{ $layoutPhotoUrl }}" alt="{{ $layoutFirstName }}" class="h-full w-full object-cover">
                            @else 
                                {{ $layoutInitial }}
                            @endif
                        </span>
                    </button>
                    <div x-cloak x-show="profileOpen" x-transition.origin.top.right.duration.150ms @click.outside="profileOpen = false" class="absolute right-0 mt-3 w-72 list-none divide-y divide-gray-150 rounded-xl bg-white text-base shadow-xl border border-gray-150 z-50">
                        <div class="px-4 py-3 text-left">
                            <p class="truncate text-sm font-bold text-gray-900">{{ $layoutName }}</p>
                            <p class="truncate text-xs font-semibold text-gray-500 mt-0.5">{{ $layoutEmail ?: $layoutStudentNo }}</p>
                        </div>
                        <ul class="py-1">
                            <li><a href="{{ route('student.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-semibold text-left">Dashboard</a></li>
                            <li><a href="{{ route('student.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-semibold text-left">My Profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('student.logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 font-semibold">Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>

        @if(session('success'))
            <div class="student-alert">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="student-error">
                <i data-lucide="alert-circle"></i>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
        </div>
    </main>
</div>
@else
    @yield('content')
@endauth
<script>
    const refreshStudentIcons = () => window.lucide?.createIcons();
    refreshStudentIcons();
    document.addEventListener('DOMContentLoaded', refreshStudentIcons);
    window.addEventListener('load', refreshStudentIcons);
    setTimeout(refreshStudentIcons, 100);
    setTimeout(refreshStudentIcons, 500);
</script>
</body>
</html>
