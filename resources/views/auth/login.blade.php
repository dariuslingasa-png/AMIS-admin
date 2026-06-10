<x-guest-layout title="Admin Login">
    <section class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('images/AMIS_Logo.svg') }}" class="h-10 w-10 object-contain" alt="AMIS Logo">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">AMIS Admin Portal</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sign in to continue</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Password</label>
                    <input id="password" name="password" type="password" required class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <button type="submit" class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300">Sign in</button>
            </form>

            <div class="my-5 flex items-center justify-between">
                <span class="w-2/5 border-b dark:border-gray-700"></span>
                <span class="text-xs text-center text-gray-400 uppercase">or</span>
                <span class="w-2/5 border-b dark:border-gray-700"></span>
            </div>

            <a href="{{ route('admin.microsoft.redirect') }}" class="w-full flex items-center justify-center gap-2.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 px-5 py-2.5 text-center text-sm font-semibold text-gray-700 transition duration-150 shadow-sm focus:outline-none focus:ring-4 focus:ring-slate-100 dark:bg-gray-700 dark:border-gray-650 dark:text-white dark:hover:bg-gray-600">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="0" y="0" width="11" height="11" fill="#F25022"/>
                    <rect x="12" y="0" width="11" height="11" fill="#7FBA00"/>
                    <rect x="0" y="12" width="11" height="11" fill="#00A4EF"/>
                    <rect x="12" y="12" width="11" height="11" fill="#FFB900"/>
                </svg>
                <span>Sign in with Microsoft</span>
            </a>
        </div>
    </section>
</x-guest-layout>
