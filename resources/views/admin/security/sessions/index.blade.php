<x-admin-layout title="Active User Sessions">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Security Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Active Sessions</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        View current administrative portal sessions and trigger force logouts to revoke compromised tokens.
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

        <!-- Sessions Card -->
        <x-card title="Active Sessions Directory" subtitle="Current browser session tokens matching authenticated administrators">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[850px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">IP Address</th>
                            <th class="px-5 py-3">Device / Browser</th>
                            <th class="px-5 py-3">Last Active</th>
                            <th class="px-5 py-3 text-right">Session Token Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($sessions as $sess)
                            <tr class="align-middle">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-extrabold uppercase ring-1 ring-slate-200/50">
                                            @if($sess->user)
                                                {{ \Illuminate\Support\Str::substr($sess->user->name ?: $sess->user->email, 0, 1) }}
                                            @else
                                                <i data-lucide="help-circle" class="h-4 w-4 text-slate-400"></i>
                                            @endif
                                        </div>
                                        <div>
                                            @if($sess->user)
                                                <span class="font-extrabold text-slate-900 block">{{ $sess->user->name }}</span>
                                                <span class="text-xs font-semibold text-slate-500 block">{{ $sess->user->email }}</span>
                                            @else
                                                <span class="font-extrabold text-slate-500 block">Guest Attempt</span>
                                                <span class="text-xs font-semibold text-slate-400 block">Session ID: {{ Str::limit($sess->id, 10) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="globe" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $sess->ip_address }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    <div class="flex items-center gap-1.5">
                                        @if($sess->device === 'Mobile')
                                            <i data-lucide="smartphone" class="h-4 w-4 text-slate-400"></i>
                                        @elseif($sess->device === 'Tablet')
                                            <i data-lucide="tablet" class="h-4 w-4 text-slate-400"></i>
                                        @else
                                            <i data-lucide="monitor" class="h-4 w-4 text-slate-400"></i>
                                        @endif
                                        <span>{{ $sess->browser }} on {{ $sess->device }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-500">
                                    {{ $sess->last_activity->diffForHumans() }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($sess->is_current)
                                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-blue-700 ring-1 ring-blue-100">
                                            Current Session
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.security-workspace.sessions.revoke') }}" onsubmit="return confirm('Force logout and terminate this user session?')">
                                            @csrf
                                            <input type="hidden" name="session_id" value="{{ $sess->id }}">
                                            <button type="submit" class="inline-flex h-8 items-center justify-center rounded-lg bg-rose-50 px-3 text-xs font-black uppercase tracking-wider text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                                Terminate Session
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No active sessions mapped in database.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-admin-layout>
