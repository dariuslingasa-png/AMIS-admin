<!DOCTYPE html>
@php
    $googleConfigured = filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    $microsoftConfigured = filled(config('services.azure.client_id')) && filled(config('services.azure.client_secret'));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - AMIS Student Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Naskh+Arabic:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="student-login-body">
    <main class="student-login">
        <section class="student-login-grid">
            <div class="student-login-identity">
                <div class="student-login-lockup">
                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo">
                    <div class="student-login-wordmark">
                        <p class="student-login-arabic" lang="ar" dir="rtl">المدرسة الإسلامية المنورة</p>
                        <h1>AL MUNAWWARA ISLAMIC SCHOOL</h1>
                        <strong>Student Portal</strong>
                    </div>
                </div>
                <p>Access your classes, schedule, billing, profile records, and learning resources in one place.</p>
            </div>

            <div class="student-login-panel">
                <div class="student-login-brand">
                    <div>
                        <h2>Sign in</h2>
                        <p>Use your AMIS student account to continue.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="student-error">
                        <i data-lucide="alert-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form action="{{ route('student.login.store') }}" method="POST" class="student-form">
                    @csrf
                    <label>
                        <span>School Email or Student ID</span>
                        <input id="login_id" name="login_id" type="text" value="{{ old('login_id', 'student@amis.edu.ph') }}" required autofocus placeholder="2026-0001 or email@amis.edu.ph">
                    </label>

                    <label>
                        <span>Portal Password</span>
                        <input id="password" name="password" type="password" value="123" required placeholder="Password">
                    </label>

                    <div class="flex items-center justify-between py-1">
                        <label class="flex items-center gap-2 font-semibold text-gray-600" style="flex-direction:row; font-size:15px; cursor:pointer;">
                            <input type="checkbox" name="remember" value="1" class="student-remember-checkbox">
                            Remember me
                        </label>
                    </div>

                    <button type="submit" class="student-primary-btn w-full">
                        <i data-lucide="log-in" class="w-4 h-4 mr-1"></i> Sign In
                    </button>
                </form>

                <div class="student-divider">or continue with</div>

                <div class="space-y-2">
                    @if($microsoftConfigured)
                        <a href="{{ route('student.login.microsoft.redirect') }}" class="student-outline-btn w-full flex">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#f25022"/>
                                <path d="M12 0h11v11H12z" fill="#7fba00"/>
                                <path d="M0 12h11v11H0z" fill="#00a4ef"/>
                                <path d="M12 12h11v11H12z" fill="#ffb900"/>
                            </svg>
                            Sign in with Microsoft
                        </a>
                    @else
                        <button type="button" disabled class="student-outline-btn w-full flex opacity-50 cursor-not-allowed">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#888"/><path d="M12 0h11v11H12z" fill="#888"/>
                                <path d="M0 12h11v11H0z" fill="#888"/><path d="M12 12h11v11H12z" fill="#888"/>
                            </svg>
                            Microsoft sign-in not configured
                        </button>
                    @endif

                    @if($googleConfigured)
                        <a href="{{ route('student.login.google.redirect') }}" class="student-outline-btn w-full flex">
                            <i data-lucide="chrome" class="h-4 w-4 mr-2 text-emerald-600"></i>
                            Sign in with Google
                        </a>
                    @else
                        <button type="button" disabled class="student-outline-btn w-full flex opacity-50 cursor-not-allowed">
                            <i data-lucide="chrome" class="h-4 w-4 mr-2"></i>
                            Google sign-in not configured
                        </button>
                    @endif
                </div>

                <p class="mt-6 border-t border-gray-100 pt-5 text-center text-xs font-semibold text-gray-400">
                    Need help logging in? Please contact the registrar or school support.
                </p>
            </div>
        </section>
    </main>
    <script>window.lucide?.createIcons();</script>
</body>
</html>
