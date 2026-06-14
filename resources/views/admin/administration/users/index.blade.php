<x-admin-layout title="User Accounts Directory">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-violet-700/30 bg-gradient-to-br from-violet-900 via-violet-700 to-fuchsia-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Administration</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">User Accounts</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Manage administrative access, status controls, and credentials security settings.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Total Accounts</span>
                        <span class="mt-1 block text-2xl font-black">{{ $stats['total'] }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-emerald-400">Verified</span>
                        <span class="mt-1 block text-2xl font-black text-emerald-400">{{ $stats['verified'] }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-amber-400">Pending</span>
                        <span class="mt-1 block text-2xl font-black text-amber-400">{{ $stats['pending'] }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-rose-400">Suspended</span>
                        <span class="mt-1 block text-2xl font-black text-rose-400">{{ $stats['disabled'] }}</span>
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

        <!-- Access Directory Table Card -->
        <x-card title="Access Directory" subtitle="Manage account privileges and verify user listings">
            <!-- Filter Actions Header -->
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.administration.users.index') }}" class="inline-flex h-9 items-center justify-center rounded-xl bg-white border border-slate-200 px-4 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                            All Users
                        </a>
                        <a href="{{ route('admin.administration.users.index', ['status' => 'verified']) }}" class="inline-flex h-9 items-center justify-center rounded-xl bg-white border border-slate-200 px-4 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50/40">
                            Verified
                        </a>
                        <a href="{{ route('admin.administration.users.index', ['status' => 'pending']) }}" class="inline-flex h-9 items-center justify-center rounded-xl bg-white border border-slate-200 px-4 text-xs font-bold text-amber-700 transition hover:bg-amber-50/40">
                            Pending
                        </a>
                        <a href="{{ route('admin.administration.users.index', ['status' => 'disabled']) }}" class="inline-flex h-9 items-center justify-center rounded-xl bg-white border border-slate-200 px-4 text-xs font-bold text-rose-700 transition hover:bg-rose-50/40">
                            Suspended
                        </a>
                    </div>
                    <div class="flex items-center gap-3">
                        <form method="GET" action="{{ route('admin.administration.users.index') }}" class="flex items-center gap-2">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            <div class="relative w-full sm:w-[260px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Search user directory..." class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-fuchsia-400 focus:ring-4 focus:ring-fuchsia-100">
                                @if(filled($search))
                                    <a href="{{ route('admin.administration.users.index', request()->only('status')) }}" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-fuchsia-600 px-4 text-xs font-bold text-white transition hover:bg-fuchsia-700 cursor-pointer">
                                Search
                            </button>
                        </form>
                        <a href="{{ route('admin.administration.users.create') }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-fuchsia-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-fuchsia-700">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Create User
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">Active Roles</th>
                            <th class="px-5 py-3">User Status</th>
                            <th class="px-5 py-3">Created</th>
                            <th class="px-5 py-3 text-right">Account Options</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="align-middle">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-extrabold uppercase ring-1 ring-slate-200/50">
                                            {{ \Illuminate\Support\Str::substr($user->name ?: $user->email, 0, 1) }}
                                        </div>
                                        <div>
                                            <span class="font-extrabold text-slate-900 block">{{ $user->name ?: 'Portal Account' }}</span>
                                            <span class="text-xs font-semibold text-slate-500 block">{{ $user->email }}</span>
                                            @if ($user->id === auth()->id())
                                                <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-blue-700">You</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($user->roles as $role)
                                            <span class="inline-flex rounded-full bg-fuchsia-600 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-white">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <!-- Fallback role -->
                                            <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-600">
                                                {{ $user->role }}
                                            </span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $status = $user->account_status ?: 'verified';
                                        $badgeClasses = match ($status) {
                                            'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                            'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                            'disabled' => 'bg-rose-50 text-rose-700 ring-rose-100',
                                            default => 'bg-slate-50 text-slate-700 ring-slate-100',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider ring-1 {{ $badgeClasses }}">
                                        {{ $status === 'disabled' ? 'SUSPENDED' : strtoupper($status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">
                                    {{ $user->created_at?->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('admin.administration.users.security', $user) }}" class="inline-flex h-8 items-center justify-center rounded-lg bg-indigo-50 px-3 text-xs font-black uppercase tracking-wider text-indigo-700 hover:bg-indigo-100 transition">
                                            Account Security
                                        </a>

                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.administration.users.status', $user) }}" class="inline-block">
                                                @csrf
                                                @method('PATCH')
                                                @if ($user->account_status !== 'disabled')
                                                    <input type="hidden" name="account_status" value="disabled">
                                                    <button type="submit" class="inline-flex h-8 items-center justify-center rounded-lg bg-rose-50 px-3 text-xs font-black uppercase tracking-wider text-rose-700 hover:bg-rose-100 transition cursor-pointer" onclick="return confirm('Suspend administrative access for {{ addslashes($user->name) }}?')">
                                                        Suspend
                                                    </button>
                                                @else
                                                    <input type="hidden" name="account_status" value="verified">
                                                    <button type="submit" class="inline-flex h-8 items-center justify-center rounded-lg bg-emerald-50 px-3 text-xs font-black uppercase tracking-wider text-emerald-700 hover:bg-emerald-100 transition cursor-pointer">
                                                        Activate
                                                    </button>
                                                @endif
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No administrative accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                <p class="text-xs font-bold text-slate-500">
                    Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} accounts
                </p>
                <div class="w-full sm:w-auto">{{ $users->links() }}</div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
