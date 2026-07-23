<x-admin-layout title="Backup Center">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-700/30 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">System Management</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Backup Center</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-300">
                        Disaster recovery backups, local snapshots, automated cron scheduling, and Google Drive cloud backups.
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

        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            <!-- Snapshots list and Scheduling -->
            <div class="space-y-6">
                <!-- Backup files card -->
                <x-card title="Backup Snapshots" subtitle="Local SQL dumps stored in storage/app/backups">
                    <!-- Actions header -->
                    <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs font-extrabold text-slate-500 uppercase">Snapshot Files</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.system-management.backups.prune') }}" onsubmit="return confirm('Prune older SQL backups from server disk storage?')" class="flex items-center gap-1">
                                @csrf
                                <select name="days" class="h-9 px-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 outline-none">
                                    <option value="14">Prune &gt; 14 Days</option>
                                    <option value="30" selected>Prune &gt; 30 Days</option>
                                    <option value="60">Prune &gt; 60 Days</option>
                                </select>
                                <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-amber-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-amber-700 cursor-pointer" title="Auto-delete old backups to free server disk space">
                                    <i data-lucide="scissors" class="h-3.5 w-3.5"></i>
                                    Prune Disk
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.system-management.backups.create') }}">
                                @csrf
                                <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-4 text-xs font-bold text-white transition hover:bg-slate-800 cursor-pointer">
                                    <i data-lucide="database" class="h-4 w-4"></i>
                                    Dump DB
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.system-management.backups.trigger-full') }}">
                                @csrf
                                <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 cursor-pointer">
                                    <i data-lucide="folder-archive" class="h-4 w-4"></i>
                                    Trigger Full System
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm min-w-[650px]">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                    <th class="px-4 py-3">File Name</th>
                                    <th class="px-4 py-3">Size</th>
                                    <th class="px-4 py-3">Created</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($files as $file)
                                    <tr class="align-middle">
                                        <td class="px-4 py-4 text-xs font-bold text-slate-700">
                                            {{ $file['name'] }}
                                        </td>
                                        <td class="px-4 py-4 text-xs font-semibold text-slate-500">
                                            {{ $file['size'] }}
                                        </td>
                                        <td class="px-4 py-4 text-xs font-bold text-slate-500">
                                            {{ $file['created_at'] }}
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                                <a href="{{ route('admin.system-management.backups.download', $file['name']) }}" class="rounded bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-700 hover:bg-slate-200 transition">
                                                    Download
                                                </a>
                                                
                                                @if ($gdriveConfigured)
                                                    <form method="POST" action="{{ route('admin.system-management.backups.google-drive', $file['name']) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="rounded bg-indigo-50 px-2 py-1 text-[10px] font-black uppercase text-indigo-700 hover:bg-indigo-100 transition cursor-pointer">
                                                            Drive Sync
                                                        </button>
                                                    </form>
                                                @endif

                                                <form method="POST" action="{{ route('admin.system-management.backups.restore') }}" class="inline" onsubmit="return confirm('WARNING: Restoring will overwrite your database state with this snapshot. Continue?')">
                                                    @csrf
                                                    <input type="hidden" name="filename" value="{{ $file['name'] }}">
                                                    <button type="submit" class="rounded bg-amber-50 px-2 py-1 text-[10px] font-black uppercase text-amber-700 hover:bg-amber-100 transition cursor-pointer">
                                                        Restore
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.system-management.backups.destroy', $file['name']) }}" class="inline" onsubmit="return confirm('Delete local SQL snapshot?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded bg-rose-50 px-2 py-1 text-[10px] font-black uppercase text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-xs font-bold text-slate-400">No SQL backup snapshots found on disk.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <!-- Scheduling Card -->
                <x-card title="Backup Scheduler" subtitle="Automated execution options (Twice-Daily Active)">
                    <div class="p-6 space-y-4 text-xs font-semibold text-slate-500">
                        <div class="rounded-2xl bg-indigo-50/50 border border-indigo-100 p-4 space-y-2 text-indigo-900">
                            <div class="font-extrabold text-sm flex items-center gap-1.5">
                                <i data-lucide="clock" class="h-4.5 w-4.5 text-indigo-600"></i>
                                Twice-Daily Sync Active
                            </div>
                            <p class="text-xs text-indigo-700/90 leading-relaxed font-medium">
                                Automated system snapshots are executed twice every day. Snapshots are instantly synced to Google Drive and cleaned locally and in the cloud based on the retention policy.
                            </p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3 border-t border-slate-100 pt-4">
                            <div>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 text-xs">Execution Times</span>
                                <span class="text-xs font-bold text-slate-800 mt-1 block">12:00 AM (Midnight) &amp; 12:00 PM (Noon)</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 text-xs">Timezone</span>
                                <span class="text-xs font-bold text-slate-800 mt-1 block">Philippine Standard Time (Asia/Manila)</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 text-xs">Frequency</span>
                                <span class="text-xs font-bold text-slate-800 mt-1 block">Daily (Twice per day)</span>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Google Drive Storage Telemetry widget -->
            <div class="space-y-6">
                <!-- Google Drive Widget -->
                <div class="overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-white backdrop-blur">
                                <i data-lucide="cloud" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider">Google Drive Storage</h3>
                                <p class="text-[10px] font-semibold text-slate-400">Cloud backup storage tracker</p>
                            </div>
                        </div>
                        <div>
                            @if ($gdriveConnected)
                                <span class="inline-flex rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-400 border border-emerald-500/30">Connected</span>
                            @else
                                <span class="inline-flex rounded-full bg-rose-500/20 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-rose-400 border border-rose-500/30">Disconnected</span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        @if ($gdriveConnected)
                            <div class="relative w-full bg-white/10 rounded-full h-3.5 overflow-hidden">
                                <div class="h-3.5 rounded-full transition-all duration-500 bg-gradient-to-r from-violet-500 to-indigo-500" style="width: {{ min($diskUsagePercent, 100) }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-xs font-bold text-slate-300">
                                <span>Usage: {{ $diskUsagePercent }}%</span>
                                <span>{{ $formattedUsedDisk }} used</span>
                            </div>
                            <div class="border-t border-white/10 pt-4 grid grid-cols-2 gap-2 text-xs font-semibold text-slate-400">
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Total Quota</span>
                                    <span class="text-sm font-bold text-white mt-1 block">{{ $formattedTotalDisk }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Available</span>
                                    <span class="text-sm font-bold text-white mt-1 block">{{ $formattedFreeDisk }}</span>
                                </div>
                            </div>
                            <div class="border-t border-white/10 pt-4">
                                <a href="{{ route('admin.google-drive.auth', ['back_to' => 'backups']) }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-white/15 hover:bg-white/5 py-2.5 text-xs font-bold text-slate-300 transition cursor-pointer">
                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                    Reconnect Google Drive
                                </a>
                            </div>
                        @else
                            <div class="rounded-2xl border border-rose-500/20 bg-rose-500/5 p-4 space-y-3 text-xs text-slate-300">
                                <div class="font-bold text-rose-400 flex items-center gap-1.5">
                                    <i data-lucide="alert-triangle" class="h-4 w-4 shrink-0 text-rose-450"></i>
                                    Authorization Required
                                </div>
                                <p class="text-[11px] leading-relaxed text-slate-400 font-medium">
                                    The Google Drive storage API connection is inactive, expired, or revoked. You must re-authorize the account to perform cloud backups.
                                </p>
                                <a href="{{ route('admin.google-drive.auth', ['back_to' => 'backups']) }}" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-indigo-650 hover:bg-indigo-700 py-2.5 text-xs font-bold text-white transition cursor-pointer">
                                    <i data-lucide="key-round" class="h-4 w-4"></i>
                                    Connect Google Drive
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <x-card title="System Database Info" subtitle="Primary connection metrics">
                    <div class="p-6 space-y-3 text-xs font-semibold text-slate-500">
                        <div class="flex justify-between">
                            <span>Database Host:</span>
                            <span class="font-bold text-slate-800">{{ $dbHost }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Database Name:</span>
                            <span class="font-bold text-slate-800">{{ $dbName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Database Port:</span>
                            <span class="font-bold text-slate-800">{{ $dbPort }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-sm">
                            <span class="font-bold text-slate-700">Schema Size:</span>
                            <span class="font-extrabold text-indigo-600">{{ $formattedDbSize }}</span>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>
