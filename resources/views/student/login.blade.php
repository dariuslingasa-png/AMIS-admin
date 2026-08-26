<!DOCTYPE html>
@php
    $microsoftConfigured = filled(config('services.microsoft.client_id')) && filled(config('services.microsoft.client_secret'));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Sign In - AMIS Student Portal</title>
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
                        <h2>Student Sign In</h2>
                        <p>Sign in using your official school Microsoft 365 student account.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="student-error" style="margin-bottom: 1.5rem;">
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div style="margin-top: 1.5rem;">
                    @if($microsoftConfigured)
                        <a href="{{ route('student.microsoft.redirect') }}" class="student-primary-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-weight: 800; font-size: 15px; padding: 14px 20px; border-radius: 14px; gap: 12px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); text-decoration: none; color: #ffffff;">
                            <svg width="20" height="20" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#f25022"/>
                                <path d="M12 0h11v11H12z" fill="#7fba00"/>
                                <path d="M0 12h11v11H0z" fill="#00a4ef"/>
                                <path d="M12 12h11v11H12z" fill="#ffb900"/>
                            </svg>
                            <span>Sign in with Microsoft</span>
                        </a>
                    @else
                        <a href="{{ route('student.microsoft.redirect') }}" class="student-primary-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-weight: 800; font-size: 15px; padding: 14px 20px; border-radius: 14px; gap: 12px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25); text-decoration: none; color: #ffffff;">
                            <svg width="20" height="20" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#f25022"/>
                                <path d="M12 0h11v11H12z" fill="#7fba00"/>
                                <path d="M0 12h11v11H0z" fill="#00a4ef"/>
                                <path d="M12 12h11v11H12z" fill="#ffb900"/>
                            </svg>
                            <span>Sign in with Microsoft</span>
                        </a>
                    @endif
                </div>

                <div style="margin-top: 2rem; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-align: center;">
                    <p style="margin: 0; font-size: 12px; color: #64748b; font-weight: 600;">
                        Use your <strong>@amis.edu.ph</strong> school account to log in.
                    </p>
                </div>

                <p style="margin-top: 1.75rem; text-align: center; font-size: 12px; color: var(--t-tertiary);">
                    Need help with your account? Please contact the registrar or IT support.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
