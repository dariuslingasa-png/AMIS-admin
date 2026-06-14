<x-admin-layout title="Role Assignment">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-indigo-700/30 bg-gradient-to-br from-indigo-900 via-indigo-700 to-blue-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Access Control</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Role Assignment</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Associate users with multiple security roles. Access permissions are dynamically aggregated across all active roles.
                    </p>
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

        <!-- Card -->
        <x-card title="User Role Mapping Directory" subtitle="Manage account access roles">
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end">
                    <form method="GET" action="{{ route('admin.access-control.assignment.index') }}" class="flex items-center gap-2">
                        <div class="relative w-full sm:w-[320px]">
                            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search user directory..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @if(filled($search))
                                <a href="{{ route('admin.access-control.assignment.index') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </a>
                            @endif
                        </div>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 cursor-pointer">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[850px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">Roles Assigned</th>
                            <th class="px-5 py-3 text-right">Assign / Map Roles</th>
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
                                            <span class="text-xs font-semibold text-slate-500 block mt-0.5">{{ $user->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($user->roles as $role)
                                            <span class="inline-flex rounded-full bg-slate-900 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-white">
                                                {{ $role->name }}
                                            </span>
                                        @empty
                                            <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-600">
                                                {{ $user->role }} (Legacy)
                                            </span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form method="POST" action="{{ route('admin.access-control.assignment.update', $user) }}" class="flex items-center justify-end gap-3 flex-wrap">
                                        @csrf
                                        @method('PATCH')
                                        
                                        <div class="flex items-center gap-3 bg-slate-50 border border-slate-200/60 rounded-xl px-3 py-1.5 flex-wrap">
                                            @foreach($roles as $role)
                                                @if ($role->slug === 'super_admin' && !auth()->user()->hasRole('super_admin'))
                                                    @continue
                                                @endif
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-700">
                                                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" 
                                                        @checked($user->roles->contains($role->id) || ($user->roles->count() === 0 && $user->role === $role->slug))
                                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span>{{ $role->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>

                                        <button type="submit" class="inline-flex h-9 items-center justify-center rounded-xl bg-slate-900 px-4 text-xs font-black uppercase tracking-wider text-white hover:bg-slate-800 transition cursor-pointer">
                                            Sync
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No accounts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                <p class="text-xs font-bold text-slate-500">
                    Showing {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} accounts
                </p>
                <div class="w-full sm:w-auto">{{ $users->links() }}</div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
