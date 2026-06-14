<x-admin-layout title="Backup Center">
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
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
                        <div class="flex items-center gap-2">
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
                <x-card title="Backup Scheduler" subtitle="Configure automated execution options">
                    <form method="POST" action="{{ route('admin.system-management.backups.schedule') }}" class="space-y-4 p-2">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500">Execution Time</label>
                                <input name="time" type="time" value="{{ $schedule['time'] }}" required class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500">Frequency</label>
                                <select name="frequency" class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                                    <option value="daily" @selected($schedule['frequency'] === 'daily')>Daily Rotation</option>
                                    <option value="weekly" @selected($schedule['frequency'] === 'weekly')>Weekly Rotation</option>
                                    <option value="monthly" @selected($schedule['frequency'] === 'monthly')>Monthly Rotation</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500">Notify Email</label>
                                <input name="notify_email" type="email" value="{{ $schedule['notify_email'] }}" required placeholder="alerts@domain.com" class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs font-semibold text-slate-700 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-6 text-xs font-bold text-white transition hover:bg-slate-800 cursor-pointer">
                                Update Schedule
                            </button>
                        </div>
                    </form>
                </x-card>
            </div>

            <!-- Google Drive Storage Telemetry widget -->
            <div class="space-y-6">
                <!-- Google Drive Widget -->
                <div class="overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-white backdrop-blur">
                            <i data-lucide="cloud" class="h-6 w-6"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-wider">Google Drive Storage</h3>
                            <p class="text-[10px] font-semibold text-slate-400">Cloud backup storage tracker</p>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
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
                    </div>
                </div>

                <x-card title="System Database Info" subtitle="Primary connection metrics">
                    <div class="space-y-3 text-xs font-semibold text-slate-500 p-1">
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
