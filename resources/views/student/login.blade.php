<!DOCTYPE html>
@php
    $microsoftConfigured = filled(config('services.microsoft.client_id')) && filled(config('services.microsoft.client_secret'));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - AMIS Student Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                        <p>Sign in with your Student ID / Username or Microsoft account to continue.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="student-error">
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- Form using ONLY native global CSS classes from app.css -->
                <form method="POST" action="{{ route('student.login.store') }}" class="student-form">
                    @csrf
                    <label>
                        <span>Student ID / Username / Email</span>
                        <input type="text" name="login_id" value="{{ old('login_id', 'shammy') }}" required placeholder="e.g. shammy or email">
                    </label>

                    <label>
                        <span>Password</span>
                        <input type="password" name="password" value="123sham" required placeholder="Enter password">
                    </label>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: -4px;">
                        <label style="display: flex; align-items: center; gap: 8px; flex-direction: row; cursor: pointer;">
                            <input type="checkbox" name="remember" class="student-remember-checkbox">
                            <span style="font-size: 13px; font-weight: 500; text-transform: none; letter-spacing: 0;">Remember Me</span>
                        </label>
                    </div>

                    <button type="submit" class="student-primary-btn" style="width: 100%;">
                        Sign In To Student Portal
                    </button>
                </form>

                <div class="student-divider">
                    <span>Or Continue With</span>
                </div>

                <div>
                    @if($microsoftConfigured)
                        <a href="{{ route('student.microsoft.redirect') }}" class="student-primary-btn" style="width: 100%; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-weight: 700; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#f25022"/>
                                <path d="M12 0h11v11H12z" fill="#7fba00"/>
                                <path d="M0 12h11v11H0z" fill="#00a4ef"/>
                                <path d="M12 12h11v11H12z" fill="#ffb900"/>
                            </svg>
                            <span>Sign in with Microsoft</span>
                        </a>
                    @else
                        <button type="button" disabled class="student-outline-btn" style="width: 100%; opacity: 0.5; cursor: not-allowed; gap: 10px;">
                            <svg width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#888"/><path d="M12 0h11v11H12z" fill="#888"/>
                                <path d="M0 12h11v11H0z" fill="#888"/><path d="M12 12h11v11H12z" fill="#888"/>
                            </svg>
                            <span>Microsoft sign-in not configured</span>
                        </button>
                    @endif
                </div>

                <p style="margin-top: 1.75rem; text-align: center; font-size: 12px; color: var(--t-tertiary);">
                    Need help logging in? Please contact the registrar or school support.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
