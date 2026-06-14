<x-admin-layout title="Create Access Account">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('admin.administration.users.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                    Back to User directory
                </a>
                <h1 class="mt-2 text-2xl font-black text-slate-900 tracking-tight">Create Access Account</h1>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <x-card title="Account Identity" subtitle="Enter credentials and select user role">
            <form method="POST" action="{{ route('admin.administration.users.store') }}" class="p-6 space-y-4">
                @csrf
                
                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Full Name</label>
                    <input name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Email Address</label>
                    <input name="email" value="{{ old('email') }}" type="email" required placeholder="email@domain.com" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <span class="block text-xs font-black uppercase tracking-wider text-slate-500">Access Role Mapping</span>
                    <p class="text-xs font-semibold text-slate-400 mt-1">Select the primary profile role configuration for this account.</p>
                    
                    <div class="mt-3 grid gap-2.5">
                        @foreach ($roles as $role)
                            @if ($role->slug === 'super_admin' && !auth()->user()->hasRole('super_admin'))
                                @continue
                            @endif
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-indigo-200 hover:bg-indigo-50/40">
                                <input type="radio" name="role_id" value="{{ $role->id }}" @checked(old('role_id') == $role->id || (old('role_id') === null && $role->slug === 'staff')) class="mt-1 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-1.5">
                                        {{ $role->name }}
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-black text-slate-500">LVL {{ $role->hierarchy_level }}</span>
                                    </span>
                                    <span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">{{ $role->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Password</label>
                        <input name="password" type="password" required placeholder="Minimum 8 characters" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Confirm Password</label>
                        <input name="password_confirmation" type="password" required placeholder="Retype password" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>

                <div class="pt-2">
                    <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 cursor-pointer">
                        Create Account
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-admin-layout>
