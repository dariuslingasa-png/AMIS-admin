<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AMIS Student Portal' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/AMIS_Logo.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">
    <style>[x-cloak]{display:none!important}</style>
    <!-- Inter and Plus Jakarta Sans — clean, modern, highly readable for parents & students -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* ═══════════════════════════════════════════════
           TYPOGRAPHY SYSTEM — Inter / Plus Jakarta Sans
           Clean, modern, highly legible for all ages
        ═══════════════════════════════════════════════ */
        :root {
            --font-main: 'Inter', 'Plus Jakarta Sans', system-ui, sans-serif;
            /* High contrast palette */
            --bg: #f1f5f9 !important;
            --text-main: #0f172a !important;
            --text-muted: #334155 !important;
            --text-light: #64748b !important;
            --border: #cbd5e1 !important;
            --border-light: #e2e8f0 !important;
        }

        /* Apply font globally — overrides Vite-compiled CSS */
        *, *::before, *::after {
            font-family: var(--font-main) !important;
        }

        body {
            font-size: 15.5px !important;
            font-weight: 500 !important;
            line-height: 1.7 !important;
            color: var(--text-main) !important;
            background: var(--bg) !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
            letter-spacing: -0.005em !important;
        }

        /* Skeleton shimmer animation */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .skeleton-shimmer {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%) !important;
            background-size: 200% 100% !important;
            animation: shimmer 1.5s infinite linear !important;
        }
        .skeleton-layout {
            position: fixed !important;
            inset: 0 !important;
            z-index: 99999 !important;
            background: #fafbfc !important;
            overflow-y: auto !important;
        }
        @media (max-width: 900px) {
            .skeleton-sidebar-only { display: none !important; }
        }

        @media (min-width: 901px) {
            .student-sidebar {
                width: 280px !important; /* Slightly wider sidebar */
                border-right: 2px solid #e2e8f0 !important;
                background: #ffffff !important;
            }
        }
        .student-sidebar-brand {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            padding: 1.5rem 1.25rem 1.25rem !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        .student-sidebar-brand img {
            width: 44px !important;
            height: 44px !important;
            object-fit: contain !important;
            border-radius: 10px !important;
            flex-shrink: 0 !important;
        }
        .student-sidebar-brand-name {
            font-size: 1.25rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            letter-spacing: -0.025em !important;
            line-height: 1.2 !important;
        }
        .student-sidebar-brand-sub {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            color: #059669 !important;
            letter-spacing: 0.01em !important;
        }
        .student-sidebar-nav {
            flex: 1 !important;
            padding: 1.25rem 1rem !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.25rem !important;
            overflow-y: auto !important;
        }
        .student-sidebar-section {
            font-size: 0.85rem !important;
            font-weight: 800 !important;
            color: #059669 !important;
            letter-spacing: 0.1em !important;
            margin-top: 1.25rem !important;
            margin-bottom: 0.35rem !important;
            padding-left: 1rem !important;
            text-transform: uppercase !important;
        }
        .student-nav-item {
            display: flex !important;
            align-items: center !important;
            font-size: 1.125rem !important;
            font-weight: 600 !important;
            color: #334155 !important;
            padding: 0.75rem 1.15rem !important;
            gap: 0.85rem !important;
            border-radius: 12px !important;
            line-height: 1.4 !important;
        }
        .student-nav-item svg {
            width: 20px !important; /* Larger icons */
            height: 20px !important;
            stroke-width: 2.25px !important;
        }
        .student-nav-item:hover {
            color: #0f172a !important;
            background: #f1f5f9 !important;
        }
        .student-nav-item.active {
            background: #ecfdf5 !important;
            color: #047857 !important;
            font-weight: 850 !important;
        }
        .student-nav-item.active:before {
            width: 5px !important;
            border-radius: 0 4px 4px 0 !important;
            background: #047857 !important;
        }

        /* Color themes for active sidebar nav items */
        .student-nav-item.s-nav-blue.active {
            background: #eff6ff !important;
            color: #1d4ed8 !important;
        }
        .student-nav-item.s-nav-blue.active:before {
            background: #1d4ed8 !important;
        }

        .student-nav-item.s-nav-orange.active {
            background: #fff7ed !important;
            color: #c2410c !important;
        }
        .student-nav-item.s-nav-orange.active:before {
            background: #c2410c !important;
        }

        .student-nav-item.s-nav-purple.active {
            background: #f5f3ff !important;
            color: #6d28d9 !important;
        }
        .student-nav-item.s-nav-purple.active:before {
            background: #6d28d9 !important;
        }

        .student-nav-item.s-nav-teal.active {
            background: #f0fdfa !important;
            color: #0f766e !important;
        }
        .student-nav-item.s-nav-teal.active:before {
            background: #0f766e !important;
        }

        .student-nav-item.s-nav-amber.active {
            background: #fffbeb !important;
            color: #b45309 !important;
        }
        .student-nav-item.s-nav-amber.active:before {
            background: #b45309 !important;
        }

        .student-nav-item.s-nav-emerald.active {
            background: #ecfdf5 !important;
            color: #047857 !important;
        }
        .student-nav-item.s-nav-emerald.active:before {
            background: #047857 !important;
        }

        .student-nav-item.s-nav-rose.active {
            background: #fdf2f8 !important;
            color: #be185d !important;
        }
        .student-nav-item.s-nav-rose.active:before {
            background: #be185d !important;
        }

        .student-nav-item.s-nav-slate.active {
            background: #f1f5f9 !important;
            color: #334155 !important;
        }
        .student-nav-item.s-nav-slate.active:before {
            background: #334155 !important;
        }
        .student-nav-soon {
            font-size: 0.75rem !important;
            font-weight: 800 !important;
            color: #475569 !important;
            background: #e2e8f0 !important;
            border: 1px solid #cbd5e1 !important;
            padding: 0.2rem 0.5rem !important;
        }
        .student-nav-item.disabled {
            color: #94a3b8 !important;
            opacity: 0.8 !important;
        }

        @media (min-width: 901px) {
            /* Adjust main layout margin to match new sidebar width */
            .student-main {
                margin-left: 280px !important;
            }
        }
        
        /* Mobile header row overrides */
        @media (max-width: 640px) {
            .student-topbar-row {
                padding: 0.65rem 0.85rem !important;
                gap: 8px !important;
            }
            .hide-on-mobile {
                display: none !important;
            }
            .date-hide-mobile {
                display: none !important;
            }
            
            /* Responsive table-to-cards fallback for mobile screens */
            .s-table-header {
                display: none !important;
            }
            .s-table-row {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.75rem !important;
                padding: 1.15rem 1rem !important;
                border-bottom: 1.5px solid #f1f5f9 !important;
            }
            .s-table-row > div {
                min-width: 0 !important;
            }
            .s-table-row a {
                width: 100% !important;
                justify-content: center !important;
                margin-top: 0.25rem !important;
            }
            .s-table-row .no-setup-span {
                width: 100% !important;
                padding-left: 1.5rem !important;
                display: block !important;
            }
            .s-table-cell-schedule {
                padding-left: 1.5rem !important;
            }
        }

        /* Topbar Header */
        .student-topbar {
            height: 66px !important;
            border-bottom: 1.5px solid #e2e8f0 !important;
            padding: 0 2rem !important;
        }
        .student-topbar-title {
            font-size: 1.2rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            letter-spacing: -0.02em !important;
            line-height: 1.3 !important;
        }
        .student-topbar-date {
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            letter-spacing: 0.01em !important;
        }

        /* ─── Premium High-Legibility Design System Overrides ─── */

        /* Grid Layouts */
        .s-two-col-grid {
            display: grid !important;
            grid-template-columns: 1fr 300px !important;
            gap: 1.5rem !important;
            align-items: start !important;
        }
        @media (max-width: 1024px) {
            .s-two-col-grid {
                grid-template-columns: 1fr !important;
            }
        }

        .s-equal-two-col-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 1.5rem !important;
            align-items: start !important;
        }
        @media (max-width: 1024px) {
            .s-equal-two-col-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Hero Banner */
        .s-dash-hero {
            background: linear-gradient(120deg, #064e3b 0%, #065f46 45%, #059669 100%) !important;
            border-radius: 24px !important;
            padding: 2.25rem !important;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 1.75rem;
            border: 2px solid #047857 !important;
            box-shadow: 0 8px 30px rgba(5, 150, 105, 0.18) !important;
        }
         @media (max-width: 640px) {
             .s-dash-hero {
                 flex-direction: row !important;
                 align-items: center !important;
                 padding: 1.25rem 1.5rem !important;
                 gap: 1.25rem !important;
             }
         }
        .s-dash-hero-meta {
            font-size: 0.78rem !important;
            font-weight: 700 !important;
            color: #a7f3d0 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            margin-bottom: 0.4rem !important;
        }
        .s-dash-hero-title {
            font-size: 2rem !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            letter-spacing: -0.03em !important;
            line-height: 1.15 !important;
            margin-bottom: 0.5rem !important;
        }
        .s-dash-hero-sub {
            font-size: 0.95rem !important;
            color: rgba(255,255,255,0.88) !important;
            font-weight: 600 !important;
            letter-spacing: 0.005em !important;
        }

        /* Stat Cards */
        .s-stats-grid-3 {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 1.25rem !important;
        }
        .s-stats-grid-4 {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1.25rem !important;
        }
        @media (max-width: 768px) {
            .s-stats-grid-3, .s-stats-grid-4 {
                grid-template-columns: 1fr !important;
            }
        }
        .s-stat-card {
            background: #ffffff !important;
            border-radius: 20px !important;
            border: none !important;
            padding: 1.5rem !important;
            box-shadow: none !important;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease-in-out !important;
        }
        .s-stat-card:hover {
            transform: translateY(-3px) !important;
            box-shadow: none !important;
        }
        .s-stat-card-stripe {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            border-radius: 20px 20px 0 0;
        }
        .s-stat-card-icon {
            width: 46px !important;
            height: 46px !important;
            border-radius: 12px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-bottom: 1rem !important;
        }
        .s-stat-card-label {
            font-size: 0.75rem !important;
            font-weight: 700 !important;
            color: #64748b !important;
            text-transform: uppercase !important;
            letter-spacing: 0.09em !important;
            margin-bottom: 0.4rem !important;
        }
        .s-stat-card-value {
            font-size: 1.75rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            letter-spacing: -0.035em !important;
            line-height: 1.1 !important;
        }

        /* Tables & Timetables */
        .s-table-card {
            background: #ffffff !important;
            border-radius: 20px !important;
            border: none !important;
            box-shadow: none !important;
            overflow: hidden;
        }
        .s-table-header {
            display: grid !important;
            padding: 1.15rem 1.5rem !important;
            background: #f1f5f9 !important;
            border-bottom: 1.5px solid #e2e8f0 !important;
            align-items: center !important;
        }
        .s-table-header-label {
            font-size: 0.85rem !important;
            font-weight: 850 !important;
            color: #1e293b !important;
            text-transform: uppercase;
            letter-spacing: 0.08em !important;
        }
        .s-table-row {
            display: grid !important;
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid #f1f5f9 !important;
            align-items: center !important;
            transition: background 0.15s ease-in-out !important;
        }
        .s-table-row:last-child {
            border-bottom: none !important;
        }
        .s-table-row:hover {
            background: #f8fafc !important;
        }
        .s-table-cell-subject {
            font-size: 1.05rem !important;
            font-weight: 850 !important;
            color: #0f172a !important;
        }
        .s-table-cell-teacher {
            font-size: 0.95rem !important;
            font-weight: 750 !important;
            color: #334155 !important;
        }
        .s-table-cell-schedule {
            font-size: 0.95rem !important;
            font-weight: 750 !important;
            color: #334155 !important;
        }

        /* Empty states */
        .s-empty-card {
            background: #ffffff !important;
            border-radius: 20px !important;
            border: none !important;
            padding: 4rem 2rem !important;
            text-align: center !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 1.25rem !important;
        }
        .s-empty-icon-wrapper {
            width: 80px !important;
            height: 80px !important;
            border-radius: 50% !important;
            background: #f0fdf4 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-bottom: 0.5rem !important;
            box-shadow: none !important;
            border: 2px solid #d1fae5 !important;
        }
        .s-empty-title {
            font-size: 1.35rem !important;
            font-weight: 900 !important;
            color: #0f172a !important;
            margin: 0 !important;
        }
        .s-empty-text {
            font-size: 1rem !important;
            color: #475569 !important;
            margin: 0 !important;
            max-width: 400px !important;
            line-height: 1.6 !important;
            font-weight: 700 !important;
        }

        /* Quick Actions Card */
        .s-quick-actions-card {
            background: #ffffff !important;
            border-radius: 20px !important;
            border: none !important;
            padding: 1.5rem !important;
            box-shadow: none !important;
        }
        .s-quick-actions-title {
            font-size: 1.15rem !important;
            font-weight: 850 !important;
            color: #0f172a !important;
            margin-bottom: 1rem !important;
        }
        .s-quick-action-btn {
            display: flex !important;
            align-items: center !important;
            gap: 0.875rem !important;
            padding: 0.85rem 1rem !important;
            border-radius: 12px !important;
            background: #f8fafc !important;
            border: none !important;
            text-decoration: none !important;
            transition: all 0.15s ease-in-out !important;
        }
        .s-quick-action-btn:hover {
            background: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            transform: translateY(-1.5px) !important;
        }
        .s-quick-action-label {
            font-size: 0.95rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
        }

        /* Dropdown Menu */
        .s-dropdown {
            position: absolute !important;
            top: calc(100% + 8px) !important;
            right: 0 !important;
            min-width: 220px !important;
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            z-index: 100 !important;
            padding: 0.375rem !important;
        }
        .s-dropdown-item {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            padding: 0.65rem 0.85rem !important;
            font-size: 0.875rem !important;
            font-weight: 700 !important;
            color: #475569 !important;
            text-decoration: none !important;
            border-radius: 10px !important;
            transition: all 0.15s ease-in-out !important;
            cursor: pointer !important;
            width: 100% !important;
            box-sizing: border-box !important;
            background: transparent !important;
            border: none !important;
            text-align: left !important;
        }
        .s-dropdown-item:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }
        .s-dropdown-item.danger {
            color: #ef4444 !important;
        }
        .s-dropdown-item.danger:hover {
            background: #fef2f2 !important;
            color: #dc2626 !important;
        }
        .s-dropdown-item svg {
            color: #94a3b8 !important;
            transition: color 0.15s ease-in-out !important;
        }
        .s-dropdown-item:hover svg {
            color: #475569 !important;
        }
        .s-dropdown-item.danger svg {
            color: #fca5a5 !important;
        }
        .s-dropdown-item.danger:hover svg {
            color: #ef4444 !important;
        }
    </style>
</head>
<body>

{{-- Page transition --}}
<div id="page-transition">
    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS">
    <div class="dots"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>
</div>
<script>
    document.addEventListener('click', function(e) {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const h = a.getAttribute('href');
        if (!h || h.startsWith('#') || h.startsWith('javascript') || h.startsWith('http') || a.target === '_blank') return;
        document.getElementById('page-transition').classList.add('active');
    });
    window.addEventListener('pageshow', function() {
        document.getElementById('page-transition').classList.remove('active');
    });
</script>

@php
    $student  = Auth::user()->student?->load('applicant');
    $fullName = $student?->applicant
        ? $student->applicant->first_name . ' ' . $student->applicant->last_name
        : Auth::user()->name;
    $photo    = $student?->applicant?->photo_2x2_url;
    $studentNo= $student?->student_number ?? '';
    
    $grade    = $student?->grade_level ?? '';
    $testerSections = [];
    if (auth()->check() && (auth()->user()->email === 'mon.lingasa@amis.edu.ph' || auth()->user()->username === '260000')) {
        $testerSections = \Illuminate\Support\Facades\DB::table('sections')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level', 'learning_mode'])
            ->groupBy('grade_level');

        if (session()->has('tester_override_section_id')) {
            $overriddenSec = \Illuminate\Support\Facades\DB::table('sections')->where('id', session('tester_override_section_id'))->first();
            if ($overriddenSec) {
                $grade = $overriddenSec->grade_level . ' (' . $overriddenSec->name . ')';
            }
        }
    }
    
    $initials = collect(explode(' ', $fullName))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
@endphp

<div x-data="{ loaded: false, userMenu: false, sidebarOpen: false }" x-init="setTimeout(() => loaded = true, 400)">

    {{-- Initial loading skeleton --}}
    <div x-show="!loaded" x-cloak
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="skeleton-layout">
        @include('layouts.skeleton')
    </div>

    <div x-show="loaded" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="student-layout">

        <!-- Sidebar Backdrop for mobile -->
        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" 
             class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-xs transition-opacity duration-300 lg:hidden"
             style="display: none;"></div>

        {{-- ══ SIDEBAR ══ --}}
        <aside class="student-sidebar" :class="{ 'open': sidebarOpen }">
            <!-- Mobile close button -->
            <button type="button" @click="sidebarOpen = false" class="student-mobile-close-btn" aria-label="Close Sidebar" style="display: none; position: absolute; top: 18px; right: 18px; width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--s-border); background: var(--s-surface); align-items: center; justify-content: center; color: var(--t-secondary); cursor: pointer; z-index: 10;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            {{-- Brand --}}
            <div class="student-sidebar-brand">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS">
                <div>
                    <div class="student-sidebar-brand-name">AMIS</div>
                    <div class="student-sidebar-brand-sub">Student Portal</div>
                    @if($student && $student->school_year)
                        <div style="font-size: 0.65rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 1px;">
                            SY {{ $student->school_year }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Nav --}}
            <nav class="student-sidebar-nav">
                <div class="student-sidebar-section">Menu</div>

                <a href="{{ route('student.dashboard') }}"
                   class="student-nav-item s-nav-blue {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    {{-- Lucide: LayoutDashboard --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" style="color: #3b82f6;">
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    Home
                </a>

                <a href="{{ route('student.announcements') }}"
                   class="student-nav-item s-nav-orange {{ request()->routeIs('student.announcements') ? 'active' : '' }}">
                    {{-- Lucide: Megaphone --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" style="color: #f97316;">
                        <path d="M12 18H3a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h9l8-4v16l-8-4Z"/>
                        <path d="M12 10a4 4 0 0 1 0 8"/>
                    </svg>
                    Announcement
                    @if(Auth::check() && Auth::user()->first_login)
                        <span style="margin-left:auto;font-size:0.75rem;font-weight:900;color:white;background:#ef4444;padding:0.15rem 0.45rem;border-radius:999px;line-height:1;">1</span>
                    @endif
                </a>

                <div class="student-sidebar-section" style="margin-top:0.5rem;">Academic</div>

                <a href="{{ route('student.class-schedule') }}"
                   class="student-nav-item s-nav-teal {{ request()->routeIs('student.class-schedule', 'student.schedule', 'student.calendar') ? 'active' : '' }}">
                    {{-- Lucide: CalendarDays --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" style="color: #0d9488;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/>
                        <path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>
                    </svg>
                    Class Schedule
                </a>

                <a href="{{ route('student.teachers') }}"
                   class="student-nav-item s-nav-amber {{ request()->routeIs('student.teachers') ? 'active' : '' }}">
                    {{-- Lucide: Users --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" style="color: #d97706;">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Teachers
                </a>

                <a href="{{ route('student.grades') }}"
                   class="student-nav-item s-nav-purple {{ request()->routeIs('student.grades*') ? 'active' : '' }}">
                    {{-- Lucide: GraduationCap --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" style="color: #8b5cf6;">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                    Grades
                </a>

                <a href="{{ route('student.profile') }}"
                   class="student-nav-item s-nav-purple {{ request()->routeIs('student.profile*') ? 'active' : '' }}">
                    {{-- Lucide: UserCircle --}}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" style="color: #8b5cf6;">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="10" r="3"/>
                        <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>
                    </svg>
                    Profile
                </a>



            </nav>

            {{-- Footer — just logout --}}
            <div class="student-sidebar-footer">
                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button type="submit" style="display:flex;align-items:center;gap:0.85rem;width:100%;padding:0.9rem 1.15rem;border-radius:12px;background:transparent;border:1px solid #e8eaf0;cursor:pointer;font-family:inherit;font-size:1.125rem;font-weight:600;color:#ef4444;transition:background 0.15s,border-color 0.15s;"
                            onmouseover="this.style.background='#fef2f2';this.style.borderColor='#fecaca'"
                            onmouseout="this.style.background='transparent';this.style.borderColor='#e8eaf0'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>

        {{-- ══ MAIN ══ --}}
        <div class="student-main">
            <div class="student-container">

            {{-- Page header row --}}
            <div class="student-topbar-row" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;padding:1rem 1.5rem;background:white;border-radius:16px;border:1px solid #e8eaf0;box-shadow:0 1px 6px rgba(0,0,0,0.04); gap: 12px;">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                    <button type="button" @click="sidebarOpen = true" class="student-mobile-menu-btn" aria-label="Toggle Sidebar" style="display: none; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 12px; border: 1px solid var(--s-border); background: var(--s-surface); color: var(--t-secondary); cursor: pointer; flex-shrink: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div style="min-width:0;">
                        <div style="font-size:1.25rem;font-weight:800;color:#1a1d23;letter-spacing:-0.02em;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $title ?? 'Dashboard' }}</div>
                        <div class="date-hide-mobile" style="font-size:0.75rem;color:#9ca3af;margin-top:2px;font-weight:500;">
                            {{ now()->format('l, F j, Y') }}
                            @if($student && $student->school_year)
                                 &middot; School Year {{ $student->school_year }}
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    @if(session('success'))
                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)"
                             x-show="show"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             style="display:flex;align-items:center;gap:0.375rem;font-size:0.8125rem;color:#065f46;font-weight:600;background:#ecfdf5;padding:0.375rem 0.875rem;border-radius:999px;border:1px solid #a7f3d0;">
                            {{-- Lucide: Check --}}
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    {{-- Notification bell --}}
                    <a href="{{ route('student.announcements') }}"
                       style="width:38px;height:38px;border-radius:50%;background:white;border:1px solid #e8eaf0;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.05);transition:box-shadow 0.15s;text-decoration:none;position:relative;"
                       onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'"
                       onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.05)'">
                        {{-- Lucide: Bell --}}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4b5563" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                        @if(Auth::check() && Auth::user()->first_login)
                            <span style="position:absolute;top:2px;right:2px;width:8px;height:8px;border-radius:50%;background:#ef4444;border:1.5px solid white;"></span>
                        @endif
                    </a>

                    {{-- Grade | Name with dropdown --}}
                    <div x-data="{ open: false }" style="position:relative;">
                        <button @click="open = !open"
                                style="display:flex;align-items:center;gap:0.5rem;padding:0.4375rem 0.875rem;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:999px;cursor:pointer;font-family:inherit;transition:background 0.15s;"
                                onmouseover="this.style.background='#d1fae5'" onmouseout="this.style.background='#ecfdf5'">
                            <span class="hide-on-mobile" style="font-size:0.8125rem;font-weight:700;color:#059669;">{{ $grade }}</span>
                            <span class="hide-on-mobile" style="width:1px;height:14px;background:#a7f3d0;"></span>
                            <span style="font-size:0.8125rem;font-weight:700;color:#065f46;">{{ $student?->applicant?->last_name ?? '' }}</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak @click.outside="open=false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="s-dropdown" style="top:calc(100% + 8px);right:0;min-width:220px;">
                            <div style="padding:1rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:0.75rem;">
                                @if ($photo)
                                    <img src="{{ asset('storage/' . $photo) }}" alt=""
                                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #a7f3d0;">
                                @else
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#059669,#047857);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <span style="font-size:0.6875rem;font-weight:800;color:white;">{{ $initials }}</span>
                                    </div>
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-size:0.875rem;font-weight:700;color:#1a1d23;">{{ $fullName }}</div>
                                    <div style="font-size:0.6875rem;color:#9ca3af;margin-top:1px;">{{ Auth::user()->email }}</div>
                                    <div style="font-size:0.625rem;color:#059669;font-weight:600;margin-top:2px;">{{ $grade }} · {{ $studentNo }}</div>
                                </div>
                            </div>
                            <div style="padding:0.375rem;">
                                @if(count($testerSections) > 0)
                                    <form method="POST" action="{{ route('student.tester-override-section') }}" id="tester-override-form">
                                        @csrf
                                        <div style="padding: 0.5rem 0.85rem;">
                                            <label style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #0d9488; letter-spacing: 0.05em; display: block; margin-bottom: 0.25rem;">Tester: Switch Section</label>
                                            <select name="section_id" onchange="document.getElementById('tester-override-form').submit()" 
                                                    style="width: 100%; border: 1.5px solid #ccfbf1; border-radius: 8px; padding: 0.25rem; font-size: 11px; font-weight: 700; color: #0f766e; background: #f0fdfa; outline: none; cursor: pointer;">
                                                <option value="">Default (K2 - 260000)</option>
                                                @foreach($testerSections as $gradeLevel => $secList)
                                                    <optgroup label="{{ $gradeLevel }}" style="font-weight: 800; font-size: 10px;">
                                                        @foreach($secList as $sec)
                                                            <option value="{{ $sec->id }}" {{ session('tester_override_section_id') == $sec->id ? 'selected' : '' }}>
                                                                {{ $sec->name }} ({{ str_contains(strtolower($sec->learning_mode), 'face') ? 'F2F' : 'Online' }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                    </form>
                                    <div style="height:1px;background:#f3f4f6;margin:0.375rem 0;"></div>
                                @endif

                                <a href="{{ route('student.profile') }}" class="s-dropdown-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/></svg>
                                    Profile
                                </a>
                                <div style="height:1px;background:#f3f4f6;margin:0.375rem 0;"></div>
                                <form method="POST" action="{{ route('student.logout') }}">
                                    @csrf
                                    <button type="submit" class="s-dropdown-item danger">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <main class="student-content fade-up">
                {{ $slot }}
            </main>
            </div>
        </div>
    </div>
</div>

<script>
    const refreshStudentIcons = () => window.lucide?.createIcons();
    refreshStudentIcons();
    document.addEventListener('DOMContentLoaded', refreshStudentIcons);
    window.addEventListener('load', refreshStudentIcons);
    setTimeout(refreshStudentIcons, 100);
    setTimeout(refreshStudentIcons, 500);
</script>

<script>
    (function() {
        function getTeamsWebUrl(webUrl) {
            const email = "{{ Auth::user()->email }}";
            if (!webUrl) return webUrl;

            let separator = webUrl.includes('?') ? '&' : '?';
            let targetUrl = webUrl;

            if (!targetUrl.includes('prompt=')) {
                targetUrl += separator + 'prompt=select_account';
                separator = '&';
            }

            if (email && !targetUrl.includes('login_hint=')) {
                targetUrl += separator + 'login_hint=' + encodeURIComponent(email);
            }

            return targetUrl;
        }
        
        window.joinTeams = function(webUrl) {
            window.open(getTeamsWebUrl(webUrl), '_blank');
        };
    })();
</script>
</body>
</html>
