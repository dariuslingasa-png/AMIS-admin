<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'AMIS Student Portal' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">
    <style>[x-cloak]{display:none!important}</style>
    <!-- Typography: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .portal-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .portal-nav-link:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .portal-nav-link.active {
            background-color: #ecfdf5;
            color: #047857;
            font-weight: 700;
        }
        .portal-nav-link.active svg {
            color: #059669;
        }
        .portal-section-label {
            font-size: 0.6875rem;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.75rem 0.85rem 0.35rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900" x-data="{ sidebarOpen: false, userMenuOpen: false, notifOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" 
         class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs transition-opacity lg:hidden"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            <!-- Sidebar Header / School Branding -->
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3 text-decoration-none">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="h-10 w-10 object-contain">
                    <div>
                        <div class="font-heading text-base font-extrabold leading-tight text-slate-900">AMIS</div>
                        <div class="text-[11px] font-bold tracking-wide text-emerald-700">Student Portal</div>
                        @if(isset($student) && $student?->school_year)
                            <div class="mt-0.5 inline-block rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">
                                SY {{ $student->school_year }}
                            </div>
                        @endif
                    </div>
                </a>
                <button type="button" @click="sidebarOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                
                <div class="portal-section-label">Menu</div>
                <a href="{{ route('student.dashboard') }}" class="portal-nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="h-4.5 w-4.5"></i>
                    <span>Home</span>
                </a>
                <a href="{{ route('student.announcements') }}" class="portal-nav-link {{ request()->routeIs('student.announcements') ? 'active' : '' }}">
                    <i data-lucide="megaphone" class="h-4.5 w-4.5 text-amber-500"></i>
                    <span class="flex-1">Announcements</span>
                </a>

                <div class="portal-section-label">Academic</div>
                <a href="{{ route('student.schedule') }}" class="portal-nav-link {{ request()->routeIs('student.schedule*') || request()->routeIs('student.calendar*') ? 'active' : '' }}">
                    <i data-lucide="calendar-days" class="h-4.5 w-4.5 text-teal-600"></i>
                    <span>Schedule</span>
                </a>
                <a href="{{ route('student.subjects') }}" class="portal-nav-link {{ request()->routeIs('student.subjects*') ? 'active' : '' }}">
                    <i data-lucide="book-open-check" class="h-4.5 w-4.5 text-emerald-600"></i>
                    <span>Subjects</span>
                </a>
                <a href="{{ route('student.teachers') }}" class="portal-nav-link {{ request()->routeIs('student.teachers*') ? 'active' : '' }}">
                    <i data-lucide="users" class="h-4.5 w-4.5 text-blue-600"></i>
                    <span>Teachers</span>
                </a>
                <a href="{{ route('student.ebooks') }}" class="portal-nav-link {{ request()->routeIs('student.ebooks*') ? 'active' : '' }}">
                    <i data-lucide="book-open" class="h-4.5 w-4.5 text-indigo-600"></i>
                    <span>E-Books Library</span>
                </a>
                <a href="{{ route('student.grades') }}" class="portal-nav-link {{ request()->routeIs('student.grades*') ? 'active' : '' }}">
                    <i data-lucide="graduation-cap" class="h-4.5 w-4.5 text-violet-600"></i>
                    <span>Grades</span>
                </a>
                <a href="{{ route('student.profile') }}" class="portal-nav-link {{ request()->routeIs('student.profile*') ? 'active' : '' }}">
                    <i data-lucide="user-round" class="h-4.5 w-4.5 text-purple-600"></i>
                    <span>Profile</span>
                </a>
            </nav>

            <!-- Bottom Sign Out Action -->
            <div class="border-t border-slate-100 p-3">
                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 cursor-pointer">
                        <i data-lucide="log-out" class="h-4.5 w-4.5"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="flex flex-1 flex-col overflow-hidden">
            
            <!-- Topbar Header -->
            <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur-md sm:px-6 lg:px-8">
                
                <!-- Left: Mobile menu toggle + Page title -->
                <div class="flex items-center gap-3">
                    <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <div>
                        <h1 class="font-heading text-lg font-bold text-slate-900">{{ $title ?? 'Dashboard' }}</h1>
                        <p class="hidden text-xs font-medium text-slate-500 sm:block">
                            {{ now()->format('l, F j, Y') }} • School Year {{ $student?->school_year ?? '2026-2027' }}
                        </p>
                    </div>
                </div>

                <!-- Right: Notification Bell & Student Profile Capsule -->
                <div class="flex items-center gap-3">
                    
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button type="button" @click="notifOpen = !notifOpen" @click.away="notifOpen = false" 
                                class="relative rounded-xl border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 focus:outline-none">
                            <i data-lucide="bell" class="h-4.5 w-4.5"></i>
                            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-emerald-600 ring-2 ring-white"></span>
                        </button>

                        <!-- Notification Dropdown -->
                        <div x-cloak x-show="notifOpen" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl z-50">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <span class="font-heading text-sm font-bold text-slate-900">Notifications</span>
                                <a href="{{ route('student.announcements') }}" class="text-xs font-bold text-emerald-700 hover:underline">View all</a>
                            </div>
                            <div class="divide-y divide-slate-50 py-2">
                                <div class="py-2.5">
                                    <p class="text-xs font-bold text-slate-900">Welcome to AMIS Student Portal!</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Check your schedule and learning materials.</p>
                                    <span class="text-[10px] font-semibold text-emerald-600 mt-1 inline-block">Today</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.away="open = false" 
                                class="flex items-center gap-2.5 rounded-full border border-slate-200 bg-slate-50 py-1 pl-1.5 pr-3 transition hover:bg-slate-100 focus:outline-none">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">
                                {{ strtoupper(substr(Auth::user()->name ?? 'S', 0, 2)) }}
                            </div>
                            <div class="text-left">
                                <span class="hidden text-xs font-bold text-slate-800 sm:inline-block max-w-[120px] truncate">
                                    {{ Auth::user()->name }}
                                </span>
                            </div>
                            <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-slate-400"></i>
                        </button>

                        <div x-cloak x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl z-50">
                            <div class="px-3 py-2 border-b border-slate-100">
                                <div class="text-xs font-bold text-slate-900 truncate">{{ Auth::user()->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ Auth::user()->email }}</div>
                                <div class="mt-1 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                                    {{ $student?->grade_level ?? 'Grade 1' }} · Student
                                </div>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('student.profile') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    <i data-lucide="user" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span>My Profile</span>
                                </a>
                                <a href="{{ route('student.settings') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    <i data-lucide="settings" class="h-3.5 w-3.5 text-slate-400"></i>
                                    <span>Settings</span>
                                </a>
                            </div>
                            <div class="border-t border-slate-100 pt-1">
                                <form method="POST" action="{{ route('student.logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                        <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Main Page Content Slot -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
