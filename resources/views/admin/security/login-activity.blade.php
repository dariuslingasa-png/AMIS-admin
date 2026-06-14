<x-admin-layout title="Login Activity">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Security Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Login Activity</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Monitor admin login sessions, client IP addresses, browser types, devices, and session duration boundaries.
                    </p>
                </div>
            </div>
        </section>

        <!-- Sessions Log Card -->
        <x-card title="Authentication Sessions Log" subtitle="Track administrative user sign-ins">
            <!-- Filters Header -->
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('admin.security-workspace.login-activity', ['tab' => 'all', 'search' => $search]) }}" class="inline-flex h-9 items-center justify-center rounded-xl px-4 text-xs font-bold transition {{ $tab === 'all' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            All Logins
                        </a>
                        <a href="{{ route('admin.security-workspace.login-activity', ['tab' => 'success', 'search' => $search]) }}" class="inline-flex h-9 items-center justify-center rounded-xl px-4 text-xs font-bold transition {{ $tab === 'success' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            Successful
                        </a>
                        <a href="{{ route('admin.security-workspace.login-activity', ['tab' => 'failed', 'search' => $search]) }}" class="inline-flex h-9 items-center justify-center rounded-xl px-4 text-xs font-bold transition {{ $tab === 'failed' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                            Failed Attempts
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.security-workspace.login-activity') }}" class="flex items-center gap-2">
                            <input type="hidden" name="tab" value="{{ $tab }}">
                            <div class="relative w-full sm:w-[260px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Search user or IP..." class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                                @if(filled($search))
                                    <a href="{{ route('admin.security-workspace.login-activity', ['tab' => $tab]) }}" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-4 text-xs font-bold text-white transition hover:bg-slate-800 cursor-pointer">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">IP Address</th>
                            <th class="px-5 py-3">Browser</th>
                            <th class="px-5 py-3">Device</th>
                            <th class="px-5 py-3">Login Time</th>
                            <th class="px-5 py-3">Logout Time</th>
                            <th class="px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 ring-1 ring-slate-200/50">
                                            @if($log->user_id)
                                                <i data-lucide="user" class="h-4 w-4"></i>
                                            @else
                                                <i data-lucide="user-x" class="h-4 w-4 text-slate-400"></i>
                                            @endif
                                        </div>
                                        <div>
                                            @if($log->user_id)
                                                <span class="font-extrabold text-slate-900 block">{{ $log->user?->name ?: 'Portal Account' }}</span>
                                                <span class="text-xs font-semibold text-slate-500 block">{{ $log->email ?: $log->user?->email }}</span>
                                            @else
                                                <span class="font-extrabold text-slate-500 block">Unknown User</span>
                                                <span class="text-xs font-semibold text-slate-400 block">{{ $log->email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="globe" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $log->ip_address ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="chrome" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $log->browser }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600">
                                        @if($log->device === 'Mobile')
                                            <i data-lucide="smartphone" class="h-4 w-4 text-slate-400"></i>
                                        @elseif($log->device === 'Tablet')
                                            <i data-lucide="tablet" class="h-4 w-4 text-slate-400"></i>
                                        @else
                                            <i data-lucide="monitor" class="h-4 w-4 text-slate-400"></i>
                                        @endif
                                        <span>{{ $log->device }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">
                                    {{ $log->created_at?->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">
                                    @if($log->logout_time)
                                        {{ \Carbon\Carbon::parse($log->logout_time)->format('M d, Y h:i A') }}
                                    @else
                                        <span class="text-slate-400 font-semibold">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <x-badge :color="$log->successful ? 'green' : 'red'">
                                        {{ $log->successful ? 'SUCCESS' : 'FAILED' }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No login activity records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                <p class="text-xs font-bold text-slate-500">
                    Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} events
                </p>
                <div class="w-full sm:w-auto">{{ $logs->links() }}</div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
