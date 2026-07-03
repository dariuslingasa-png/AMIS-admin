<!DOCTYPE html>
@php
    $googleConfigured = filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
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
                        <p>Sign in with your official school Microsoft account to continue.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="student-error" style="margin-bottom: 20px;">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="space-y-3" style="margin-top: 10px;">
                    @if($microsoftConfigured)
                        <a href="{{ route('student.microsoft.redirect') }}" class="student-primary-btn w-full flex" style="background: #2f2f2f; color: #fff; gap: 10px; height: 50px; font-size: 15px; border-radius: 10px;">
                            <svg width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#f25022"/>
                                <path d="M12 0h11v11H12z" fill="#7fba00"/>
                                <path d="M0 12h11v11H0z" fill="#00a4ef"/>
                                <path d="M12 12h11v11H12z" fill="#ffb900"/>
                            </svg>
                            <span>Sign in with Microsoft</span>
                        </a>
                    @else
                        <button type="button" disabled class="student-outline-btn w-full flex opacity-50 cursor-not-allowed" style="height: 50px; border-radius: 10px;">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#888"/><path d="M12 0h11v11H12z" fill="#888"/>
                                <path d="M0 12h11v11H0z" fill="#888"/><path d="M12 12h11v11H12z" fill="#888"/>
                            </svg>
                            Microsoft sign-in not configured
                        </button>
                    @endif
                </div>

                <p class="mt-6 border-t border-gray-100 pt-5 text-center text-xs font-semibold text-gray-400" style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 1.25rem;">
                    Need help logging in? Please contact the registrar or school support.
                </p>
            </div>
        </section>
    </main>
    <script>window.lucide?.createIcons();</script>
</body>
</html>
