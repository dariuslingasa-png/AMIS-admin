<x-admin-layout title="Admin Accounts">
    @php
        $isSystemAdmin = auth()->user()?->role === 'admin';
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-900 via-emerald-700 to-teal-600 p-6 text-white shadow-xl shadow-emerald-900/10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-emerald-50">Security Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Admin Accounts</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50/90">
                        View admin portal users and edit access from each account profile.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-emerald-100">Portal Users</span>
                        <span class="mt-1 block text-2xl font-black">{{ $stats['total'] }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-emerald-100">Verified</span>
                        <span class="mt-1 block text-2xl font-black">{{ $stats['verified'] }}</span>
                    </div>
                    <div class="col-span-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur sm:col-span-1">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-emerald-100">Your Role</span>
                        <span class="mt-1 block text-lg font-black uppercase">{{ auth()->user()->role ?? 'admin' }}</span>
                    </div>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 {{ $isSystemAdmin ? 'xl:grid-cols-[1fr_360px]' : '' }}">
            <x-card title="Access Directory" subtitle="Open an account to edit the permission matrix">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                <th class="px-3 py-3">Account</th>
                                <th class="px-3 py-3">Role</th>
                                <th class="px-3 py-3">Permissions</th>
                                <th class="px-3 py-3">Status</th>
                                <th class="px-3 py-3">Created</th>
                                <th class="px-3 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($admins as $admin)
                                <tr class="align-middle">
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-sm font-black uppercase text-emerald-700 ring-1 ring-emerald-100">
                                                {{ \Illuminate\Support\Str::substr($admin->name ?: $admin->email, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-black text-slate-950">{{ $admin->name }}</div>
                                                <div class="text-xs font-semibold text-slate-500">{{ $admin->email }}</div>
                                                @if ($admin->id === auth()->id())
                                                    <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-blue-700">Current User</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="inline-flex rounded-full bg-slate-950 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white">{{ $admin->role }}</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @if ($admin->isViewOnlyAccess())
                                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-blue-700">View Only</span>
                                            @endif
                                            @if ($admin->canReviewEnrollmentPayments())
                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-amber-700">Payment Approval</span>
                                            @endif
                                            @if ($admin->canReviewEnrollmentApplications())
                                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-700">Document Approval</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        @php
                                            $status = $admin->account_status ?? 'verified';
                                            $badgeClasses = match ($status) {
                                                'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                                'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                                'disabled' => 'bg-rose-50 text-rose-700 ring-rose-100',
                                                default => 'bg-slate-50 text-slate-700 ring-slate-100',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase tracking-wider ring-1 {{ $badgeClasses }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-sm font-semibold text-slate-600">
                                        {{ optional($admin->created_at)->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="px-3 py-4 text-right">
                                        @if ($isSystemAdmin)
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @if (($admin->account_status ?? 'verified') !== 'verified')
                                                    <form method="POST" action="{{ route('admin.admins.accept', $admin) }}" onsubmit="return confirm('Verify and grant access for {{ addslashes($admin->name) }}?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-black uppercase tracking-wider text-white transition hover:bg-emerald-700">
                                                            Accept Access
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('admin.admins.edit', $admin) }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-black uppercase tracking-wider text-white transition hover:bg-emerald-800">Edit</a>
                                                @if ($admin->id !== auth()->id())
                                                    <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}" onsubmit="return confirm('Remove admin account for {{ addslashes($admin->name) }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-black uppercase tracking-wider text-rose-600 transition hover:bg-rose-50">
                                                            Remove
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs font-bold text-slate-400">View Only</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10 text-center text-sm font-bold text-slate-400">No admin accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            @if ($isSystemAdmin)
                <x-card title="Create Access Account" subtitle="Choose default access role">
                    <form method="POST" action="{{ route('admin.admins.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Full Name</label>
                            <input name="name" value="{{ old('name') }}" required placeholder="Admin full name" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Email</label>
                            <input name="email" value="{{ old('email') }}" type="email" required placeholder="admin@amis.edu.ph" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Access Role</span>
                            <div class="mt-3 grid gap-2">
                                @foreach (['admin' => ['ADMIN', 'Full admin portal access, including account management.'], 'finance' => ['FINANCE', 'Finance access with payment review by default.'], 'staff' => ['STAFF', 'View-only portal access by default.']] as $roleValue => [$roleLabel, $roleDescription])
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-emerald-200 hover:bg-emerald-50/40">
                                        <input type="radio" name="role" value="{{ $roleValue }}" @checked(old('role', 'admin') === $roleValue) class="mt-1 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500">
                                        <span>
                                            <span class="block text-xs font-black uppercase tracking-wider text-slate-950">{{ $roleLabel }}</span>
                                            <span class="mt-1 block text-xs font-semibold leading-5 text-slate-500">{{ $roleDescription }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Password</label>
                            <input name="password" type="password" required placeholder="Minimum 8 characters" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Confirm Password</label>
                            <input name="password_confirmation" type="password" required placeholder="Retype password" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                        </div>
                        <button class="w-full rounded-2xl bg-emerald-700 px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800">
                            Create Access Account
                        </button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</x-admin-layout>
