<x-admin-layout title="Audit Logs">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Security Audit</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Audit Logs</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Login, logout, failed login, and session security events.
                    </p>
                </div>
                <a href="{{ route('admin.admins.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-900 shadow-lg shadow-slate-900/20 transition hover:bg-slate-100">
                    <i data-lucide="users" class="h-4 w-4"></i>
                    Admin Accounts
                </a>
            </div>
        </section>

        <x-card title="Recent Security Events" subtitle="Latest portal authentication activity">
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <!-- Tabs Segmented Control -->
                    <div class="flex flex-wrap gap-1.5 p-1 bg-slate-100 border border-slate-200/60 rounded-2xl shadow-3xs w-fit">
                        <a href="{{ route('admin.admins.audit-logs', ['tab' => 'login', 'search' => $search]) }}"
                           class="px-4 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider {{ $tab === 'login' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold' }}">
                            <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
                            Login Logs
                        </a>
                        <a href="{{ route('admin.admins.audit-logs', ['tab' => 'approve', 'search' => $search]) }}"
                           class="px-4 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider {{ $tab === 'approve' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold' }}">
                            <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                            Approvals
                        </a>
                        <a href="{{ route('admin.admins.audit-logs', ['tab' => 'system', 'search' => $search]) }}"
                           class="px-4 py-2 text-xs rounded-xl transition duration-200 cursor-pointer flex items-center justify-center gap-1.5 uppercase tracking-wider {{ $tab === 'system' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold' }}">
                            <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                            CRUD / System
                        </a>
                    </div>

                    <!-- Search Input Form -->
                    <form method="GET" action="{{ route('admin.admins.audit-logs') }}" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <div class="relative w-full sm:w-[320px]">
                            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search event, email, message..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            @if(filled($search))
                                <a href="{{ route('admin.admins.audit-logs', ['tab' => $tab]) }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
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

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">Time</th>
                            <th class="px-5 py-3">Event</th>
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">Result</th>
                            <th class="px-5 py-3">IP Address</th>
                            <th class="px-5 py-3">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">{{ $log->created_at?->format('M d, Y h:i A') }}</td>
                                <td class="px-5 py-4 font-black uppercase text-slate-950 text-xs tracking-wider">{{ Str::headline($log->event) }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-900">{{ $log->user?->name ?: 'Unknown User' }}</div>
                                    <div class="text-xs font-semibold text-slate-500">{{ $log->email ?: $log->user?->email ?: 'No email' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-badge :color="$log->successful ? 'green' : 'red'">{{ $log->successful ? 'SUCCESS' : 'FAILED' }}</x-badge>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">{{ $log->ip_address ?: '-' }}</td>
                                <td class="px-5 py-4 text-xs font-semibold text-slate-500">{{ $log->message ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No audit logs found.</td>
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
