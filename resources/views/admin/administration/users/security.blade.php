<x-admin-layout title="User Account Security">
    <div class="space-y-6">
        <div>
            <a href="{{ route('admin.administration.users.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                Back to User directory
            </a>
            <h1 class="mt-2 text-2xl font-black text-slate-900 tracking-tight">Security Center: {{ $user->name }}</h1>
            <p class="text-xs font-semibold text-slate-400 mt-1">Manage password credentials and review active session connections.</p>
        </div>

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

        <div class="grid gap-6 lg:grid-cols-[380px_1fr]">
            <!-- Change Password Card -->
            <x-card title="Credentials Lock" subtitle="Manually reset user credentials">
                <form method="POST" action="{{ route('admin.administration.users.security.update', $user) }}" class="p-6 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">New Password</label>
                        <input name="password" type="password" required placeholder="Minimum 8 characters" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-slate-500">Confirm New Password</label>
                        <input name="password_confirmation" type="password" required placeholder="Retype password" class="w-full rounded-xl border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 cursor-pointer" onclick="return confirm('Confirm credential override for {{ addslashes($user->name) }}?')">
                            Reset Password
                        </button>
                    </div>
                </form>
            </x-card>

            <!-- Sessions and Audit Logs -->
            <div class="space-y-6">
                <x-card title="Active Sessions" subtitle="Currently logged-in browser tokens in database">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                    <th class="px-4 py-2">IP Address</th>
                                    <th class="px-4 py-2">Browser / Agent</th>
                                    <th class="px-4 py-2">Last Active</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($sessions as $sess)
                                    <tr>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-700">
                                            {{ $sess->ip_address }}
                                        </td>
                                        <td class="px-4 py-3 text-xs font-semibold text-slate-500 max-w-[280px] truncate" title="{{ $sess->user_agent }}">
                                            {{ $sess->user_agent }}
                                        </td>
                                        <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                                            {{ \Carbon\Carbon::createFromTimestamp($sess->last_activity)->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if($sess->id === session()->getId())
                                                <span class="rounded bg-blue-50 px-2 py-0.5 text-[9px] font-black uppercase text-blue-700">Current</span>
                                            @else
                                                <form method="POST" action="{{ route('admin.security-workspace.sessions.revoke') }}">
                                                    @csrf
                                                    <input type="hidden" name="session_id" value="{{ $sess->id }}">
                                                    <button type="submit" class="rounded bg-rose-50 px-2.5 py-1 text-[10px] font-black uppercase text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                                        Revoke
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-xs font-bold text-slate-400">No active sessions mapped.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <x-card title="Recent Authentication Log" subtitle="Last login attempts and updates for this user">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                    <th class="px-4 py-2">Event</th>
                                    <th class="px-4 py-2">IP Address</th>
                                    <th class="px-4 py-2">Detail</th>
                                    <th class="px-4 py-2">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $log->successful ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                                {{ str_replace('_', ' ', $log->event) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs font-semibold text-slate-600">
                                            {{ $log->ip_address }}
                                        </td>
                                        <td class="px-4 py-3 text-xs font-semibold text-slate-500">
                                            {{ $log->message }}
                                        </td>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-400">
                                            {{ $log->created_at?->format('M d, Y H:i A') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-xs font-bold text-slate-400">No logs on record.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>
