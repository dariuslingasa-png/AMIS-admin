<x-guest-layout title="Admin Login">
    <div class="min-h-screen bg-slate-950 lg:grid lg:grid-cols-[1.05fr_.95fr]">
        <section class="relative hidden overflow-hidden bg-emerald-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-32 -top-32 h-96 w-96 rounded-full bg-emerald-400/10"></div>
            <div class="absolute -bottom-40 -left-24 h-[30rem] w-[30rem] rounded-full bg-teal-300/10"></div>
            <div class="relative flex items-center gap-4">
                <img src="{{ asset('images/AMIS_Logo.svg') }}" class="h-14 w-14 rounded-2xl bg-white p-2" alt="AMIS Logo">
                <div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">AMIS Secure Access</p><h1 class="mt-1 text-xl font-extrabold">Admin Portal</h1></div>
            </div>
            <div class="relative max-w-xl">
                <span class="inline-flex rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-emerald-200">Protected workspace</span>
                <h2 class="mt-5 text-4xl font-black leading-tight xl:text-5xl">School operations.<br>One secure workspace.</h2>
                <p class="mt-5 max-w-lg text-base leading-7 text-emerald-100/70">Access enrollment, student records, academics, Finance verification, family accounts, receipts, and reports.</p>
            </div>
            <div class="relative grid grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-white/10 bg-white/5 p-4"><strong class="block text-sm text-white">Direct Access</strong><span class="mt-1 block text-emerald-100/60">Admin password sign-in</span></div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4"><strong class="block text-sm text-white">Admin only</strong><span class="mt-1 block text-emerald-100/60">Role verified access</span></div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4"><strong class="block text-sm text-white">Audited</strong><span class="mt-1 block text-emerald-100/60">Security events recorded</span></div>
            </div>
        </section>

        <section class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8 sm:px-8">
            <div class="w-full max-w-md">
                <div class="mb-7 flex items-center gap-3 lg:hidden"><img src="{{ asset('images/AMIS_Logo.svg') }}" class="h-11 w-11" alt="AMIS Logo"><div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">AMIS Secure Access</p><h1 class="font-extrabold text-slate-900">Admin Portal</h1></div></div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Authorized personnel only</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-900">Sign in to AMIS Admin</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Enter your Admin email address and password.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-sm font-semibold text-rose-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-sm font-semibold text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4" x-data="{ showPassword: false }">
                        @csrf

                        <div>
                            <label for="email" class="block text-sm font-bold text-slate-700">Admin email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="name@school.edu.ph" class="mt-2 w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-emerald-600">
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-bold text-slate-700">Password</label>
                            </div>
                            <div class="relative mt-2">
                                <input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" placeholder="••••••••" class="w-full rounded-xl border-slate-300 bg-slate-50 px-4 py-3 pr-11 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-emerald-600">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 focus:outline-none" tabindex="-1">
                                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 transition-all">
                                Sign in with password
                            </button>
                        </div>
                    </form>
                </div>
                <p class="mt-5 text-center text-xs text-slate-400">Sign-in attempts are rate-limited and audited.</p>
            </div>
        </section>
    </div>
</x-guest-layout>
