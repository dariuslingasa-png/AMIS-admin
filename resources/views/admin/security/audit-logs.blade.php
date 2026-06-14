<x-admin-layout title="Audit Logs">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-rose-700/30 bg-gradient-to-br from-rose-900 via-rose-700 to-pink-600 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Security Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Audit Trail</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        View portal actions, configuration adjustments, and user profiles modifications log.
                    </p>
                </div>
            </div>
        </section>

        <!-- Audit Table Card -->
        <x-card title="System Activity Audit Trail" subtitle="Query administrative action log history">
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.security-workspace.audit-logs') }}" class="flex items-center gap-2">
                            <div class="relative w-full sm:w-[320px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Search audit logs..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                                @if(filled($search))
                                    <a href="{{ route('admin.security-workspace.audit-logs') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
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
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[850px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">Event ID</th>
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">IP Address</th>
                            <th class="px-5 py-3">Description</th>
                            <th class="px-5 py-3">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                            <tr class="align-middle">
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-slate-700">
                                        {{ str_replace('_', ' ', $log->event) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    {{ $log->email ?: '-' }}
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    {{ $log->ip_address ?: '-' }}
                                </td>
                                <td class="px-5 py-4 text-xs font-semibold text-slate-500 leading-relaxed">
                                    {{ $log->message }}
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">
                                    {{ $log->created_at?->format('M d, Y h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No audit logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                <p class="text-xs font-bold text-slate-500">
                    Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
                </p>
                <div class="w-full sm:w-auto">{{ $logs->links() }}</div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
