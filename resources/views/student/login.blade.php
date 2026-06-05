<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - AMIS Student Portal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/AMIS_Logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/lucide@latest"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    @php
        $googleConfigured = filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    @endphp

    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('images/AMIS_Logo.png') }}" class="h-10 w-10 object-contain" alt="AMIS Logo">
                <div>
                    <h1 class="text-xl font-semibold text-gray-950">AMIS Student Portal</h1>
                    <p class="text-sm font-medium text-gray-500">Sign in to continue</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('student.login.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="login_id" class="mb-2 block text-sm font-medium text-gray-900">School Email or Student ID</label>
                    <input id="login_id"
                           name="login_id"
                           type="text"
                           value="{{ old('login_id') }}"
                           required
                           autofocus
                           placeholder="2026-0001 or email@amis.edu.ph"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500">
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-900">Portal Password</label>
                    <input id="password"
                           name="password"
                           type="password"
                           required
                           placeholder="Password"
                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500">
                </div>

                <label class="flex items-center gap-2 text-sm font-medium text-gray-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    Remember me
                </label>

                <button type="submit" class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300">
                    Sign in
                </button>
            </form>

            <div class="my-5 flex items-center gap-3">
                <div class="h-px flex-1 bg-gray-100"></div>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">or</span>
                <div class="h-px flex-1 bg-gray-100"></div>
            </div>

            @if($googleConfigured)
                <a href="{{ route('student.login.google.redirect') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100">
                    <i data-lucide="chrome" class="h-4 w-4 text-emerald-600"></i>
                    Sign in with Google
                </a>
                <p class="mt-2 text-center text-[11px] font-medium text-gray-400">
                    Works after you bind your Google account in Settings.
                </p>
            @else
                <button type="button" disabled class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-5 py-2.5 text-sm font-bold text-gray-400">
                    <i data-lucide="chrome" class="h-4 w-4"></i>
                    Google sign-in not configured
                </button>
            @endif

            <p class="mt-6 border-t border-gray-100 pt-5 text-center text-xs font-medium text-gray-500">
                Need help logging in? Please contact the registrar or school support.
            </p>
        </div>
    </main>



    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
