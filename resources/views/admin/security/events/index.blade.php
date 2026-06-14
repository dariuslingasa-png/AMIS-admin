<x-admin-layout title="Security Events">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Security Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Security Events</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Review system credential changes, status overrides, permissions configurations, and revoked active sessions.
                    </p>
                </div>
            </div>
        </section>

        <!-- Events List -->
        <x-card title="System Security Log" subtitle="Filtered logs representing status modifications and security overrides">
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.security-workspace.events.index') }}" class="flex items-center gap-2">
                            <div class="relative w-full sm:w-[320px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Search user or message..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                                @if(filled($search))
                                    <a href="{{ route('admin.security-workspace.events.index') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
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
                            <th class="px-5 py-3">Event</th>
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">IP Address</th>
                            <th class="px-5 py-3">Event Detail</th>
                            <th class="px-5 py-3">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($events as $event)
                            <tr class="align-middle">
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-700 ring-1 ring-rose-100">
                                        {{ str_replace('_', ' ', $event->event) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-700">
                                    {{ $event->email }}
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    {{ $event->ip_address }}
                                </td>
                                <td class="px-5 py-4 text-xs font-semibold text-slate-500">
                                    {{ $event->message }}
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">
                                    {{ $event->created_at?->format('M d, Y h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No security events found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                <p class="text-xs font-bold text-slate-500">
                    Showing {{ $events->firstItem() ?? 0 }}-{{ $events->lastItem() ?? 0 }} of {{ $events->total() }} events
                </p>
                <div class="w-full sm:w-auto">{{ $events->links() }}</div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
