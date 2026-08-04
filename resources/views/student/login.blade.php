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
                        <p>Sign in with your Student ID / Username or Microsoft account to continue.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="student-error" style="margin-bottom: 16px;">
                        <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- Sleek Single Demo Account Badge -->
                <div class="mb-4 text-center">
                    <button type="button" onclick="fillTestAccount()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-300 text-emerald-800 text-[11px] font-bold hover:bg-emerald-100 transition cursor-pointer shadow-2xs">
                        <i data-lucide="key" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Testing Account: <strong class="font-mono text-emerald-950">shammy</strong> / <strong class="font-mono text-emerald-950">123sham</strong> (Grade 7)</span>
                    </button>
                </div>

                <!-- Username / Password Login Form -->
                <form method="POST" action="{{ route('student.login.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="login_id" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Student ID / Username / Email</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" id="login_id" name="login_id" value="{{ old('login_id', 'shammy') }}" required placeholder="e.g. shammy" class="w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="password" id="password" name="password" value="123sham" required placeholder="Enter password..." class="w-full rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-emerald-600">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-600">
                            <input type="checkbox" name="remember" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Remember Me</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md transition active:scale-95 cursor-pointer">
                        Sign In To Student Portal
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-4 text-center">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                    <span class="relative bg-white px-3 text-[11px] font-bold text-slate-400 uppercase">Or Continue With</span>
                </div>

                <!-- Microsoft Sign In Button -->
                <div class="space-y-3">
                    @if($microsoftConfigured)
                        <a href="{{ route('student.microsoft.redirect') }}" class="student-primary-btn w-full flex" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #fff; gap: 10px; height: 46px; font-size: 14px; border-radius: 10px; border: none; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); transition: all 0.2s;">
                            <svg width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#f25022"/>
                                <path d="M12 0h11v11H12z" fill="#7fba00"/>
                                <path d="M0 12h11v11H0z" fill="#00a4ef"/>
                                <path d="M12 12h11v11H12z" fill="#ffb900"/>
                            </svg>
                            <span style="font-weight: 700;">Sign in with Microsoft</span>
                        </a>
                    @else
                        <button type="button" disabled class="student-outline-btn w-full flex opacity-50 cursor-not-allowed" style="height: 46px; border-radius: 10px;">
                            <svg class="h-4 w-4 mr-2" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0 0h11v11H0z" fill="#888"/><path d="M12 0h11v11H12z" fill="#888"/>
                                <path d="M0 12h11v11H0z" fill="#888"/><path d="M12 12h11v11H12z" fill="#888"/>
                            </svg>
                            Microsoft sign-in not configured
                        </button>
                    @endif
                </div>

                <p class="mt-5 border-t border-slate-100 pt-4 text-center text-xs font-semibold text-slate-400">
                    Need help logging in? Please contact the registrar or school support.
                </p>
            </div>
        </section>
    </main>

    <script>
        function fillTestAccount() {
            document.getElementById('login_id').value = 'shammy';
            document.getElementById('password').value = '123sham';
        }
        window.lucide?.createIcons();
    </script>
</body>
</html>
