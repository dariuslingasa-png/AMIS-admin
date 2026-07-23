<x-admin-layout title="Email Activity Logs">
    <div class="space-y-6">
        <!-- Top Workspace Banner -->
        <x-system-nav title="Email Delivery Audit Logs" subtitle="Inspect full delivery audit trail, recipient addresses, CC/BCC logs, SMTP mailer choices, status flags, and error tracebacks." activeTab="email">
            <a href="{{ route('admin.email-composer.index') }}"
               class="inline-flex h-11 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 text-xs font-black uppercase tracking-wider text-white backdrop-blur-xs transition hover:bg-white/20 cursor-pointer shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4 text-emerald-400"></i>
                <span>Dashboard</span>
            </a>
        </x-system-nav>

        <!-- Filter Card -->
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.email-composer.logs') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Search Recipient or Subject</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search email address, subject..."
                           class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-900 outline-none focus:border-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Status</label>
                    <select name="status" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold text-slate-900">
                        <option value="">All Statuses</option>
                        <option value="sent" @selected(request('status') === 'sent')>Sent</option>
                        <option value="failed" @selected(request('status') === 'failed')>Failed</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wider shadow-md transition cursor-pointer">
                        Filter Logs
                    </button>
                    <a href="{{ route('admin.email-composer.logs') }}" class="h-10 px-4 rounded-xl border border-slate-200 bg-slate-50 text-slate-700 text-xs font-bold flex items-center justify-center hover:bg-slate-100">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-400 font-black uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Timestamp</th>
                            <th class="px-6 py-3">Recipient Email</th>
                            <th class="px-6 py-3">Subject</th>
                            <th class="px-6 py-3">Mailer Pool</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Details / Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-semibold text-slate-700">
                        @forelse($logs as $l)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-normal">
                                    {{ !empty($l->sent_at) ? \Carbon\Carbon::parse($l->sent_at)->format('M d, Y h:i A') : (!empty($l->created_at) ? \Carbon\Carbon::parse($l->created_at)->format('M d, Y h:i A') : '—') }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $l->to_addresses }}
                                    @if(!empty($l->cc_addresses))
                                        <span class="text-[10px] text-indigo-600 block font-normal">CC: {{ $l->cc_addresses }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate">
                                    {{ $l->subject }}
                                </td>
                                <td class="px-6 py-4 uppercase font-bold text-emerald-700">
                                    {{ $l->mailer }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($l->status === 'sent')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Sent
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-50 text-rose-700 border border-rose-200">
                                            Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500 max-w-xs truncate">
                                    {{ $l->error_message ?: 'Delivered cleanly.' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-400 font-bold">
                                    No email log entries found matching your search filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
