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
        </div>
    </section>
</x-guest-layout>
