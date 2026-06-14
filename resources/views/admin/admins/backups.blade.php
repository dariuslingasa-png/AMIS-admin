<x-admin-layout title="Database Backups">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-violet-100 bg-violet-600 p-6 text-white shadow-xl shadow-violet-900/10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-indigo-50">System Snapshots</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Security Backups</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-indigo-50/90">
                        Create full database snapshots and manage offline storage archives for disaster recovery.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-100">Database Name</span>
                        <span class="mt-1 block text-base font-black truncate max-w-[120px]" title="{{ $dbName }}">{{ $dbName }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-100">Host IP</span>
                        <span class="mt-1 block text-base font-black truncate max-w-[120px]" title="{{ $dbHost }}">{{ $dbHost }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-100">Port</span>
                        <span class="mt-1 block text-base font-black">{{ $dbPort }}</span>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-100">Data Size</span>
                        <span class="mt-1 block text-base font-black">{{ $formattedDbSize }}</span>
                    </div>
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

        @if (!$gdriveConfigured)
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/50 p-4 text-xs font-semibold text-indigo-950 flex items-start gap-3 shadow-3xs leading-relaxed">
                <i data-lucide="cloud-off" class="h-5 w-5 text-indigo-600 shrink-0 mt-0.5"></i>
                <div>
                    <span class="block font-black uppercase tracking-wider text-indigo-800 mb-1">Google Drive Backups Disabled</span>
                    Configure Google Drive API parameters in your <code class="bg-indigo-100 px-1.5 py-0.5 rounded text-indigo-700 font-bold">.env</code> file to enable cloud archives:
                    <div class="mt-2 grid gap-1 font-mono text-[10px] text-indigo-700 bg-white/60 p-2.5 rounded-lg border border-indigo-100/50 w-fit">
                        <div>GOOGLE_DRIVE_CLIENT_ID="..."</div>
                        <div>GOOGLE_DRIVE_CLIENT_SECRET="..."</div>
                        <div>GOOGLE_DRIVE_REFRESH_TOKEN="..."</div>
                        <div>GOOGLE_DRIVE_FOLDER_ID="..." (optional)</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-card title="Backup Files Directory" subtitle="All generated database snapshots stored on disk">
                    <x-slot name="actions">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($gdriveConfigured)
                                <form method="POST" action="{{ route('admin.admins.backups.full') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-lg transition hover:bg-violet-700 cursor-pointer" title="Backup database and all upload files to Google Drive">
                                        <i data-lucide="cloud-upload" class="h-4 w-4"></i>
                                        Backup Full System
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.admins.backups.create') }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black uppercase tracking-wider text-white shadow-lg transition hover:bg-indigo-700 cursor-pointer">
                                    <i data-lucide="database" class="h-4 w-4"></i>
                                    Create DB Snapshot
                                </button>
                            </form>
                        </div>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                    <th class="px-5 py-3">Filename</th>
                                    <th class="px-5 py-3">Date Created</th>
                                    <th class="px-5 py-3">Size</th>
                                    <th class="px-5 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($files as $file)
                                    <tr class="align-middle">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                                                    <i data-lucide="file-database" class="h-5 w-5"></i>
                                                </div>
                                                <div>
                                                    <span class="font-extrabold text-slate-900 block">{{ $file['name'] }}</span>
                                                    <span class="text-[10px] font-semibold text-slate-400">SQL Dump</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                            {{ $file['created_at'] }}
                                        </td>
                                        <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                            {{ $file['size'] }}
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($gdriveConfigured)
                                                    <form method="POST" action="{{ route('admin.admins.backups.google-drive', $file['name']) }}">
                                                        @csrf
                                                        <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-indigo-100 bg-white px-3 text-xs font-bold text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-50 cursor-pointer" title="Upload copy to Google Drive">
                                                            <i data-lucide="cloud-upload" class="h-4 w-4"></i>
                                                            Upload to Drive
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('admin.admins.backups.download', $file['name']) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-indigo-100 bg-white px-3 text-xs font-bold text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-50">
                                                    <i data-lucide="download" class="h-4 w-4"></i>
                                                    Download
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-12 text-center text-sm font-bold text-slate-400">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <i data-lucide="folder-open" class="h-8 w-8 text-slate-300"></i>
                                                No backup snapshots created yet.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                        <p class="text-xs font-bold text-slate-500">
                            Showing {{ count($files) }} snapshots stored locally
                        </p>
                    </div>
                </x-card>
            </div>
            <div>
                <x-card title="Local Disk Storage" subtitle="Server partition disk space tracker">
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                            <span>Used Space ({{ $diskUsagePercent }}%)</span>
                            <span>{{ $formattedFreeDisk }} Free of {{ $formattedTotalDisk }}</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-600 rounded-full transition-all duration-500" style="width: {{ $diskUsagePercent }}%"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[10px] font-black uppercase text-slate-400">Used Disk</span>
                                <span class="text-sm font-black text-slate-900 mt-1 block">{{ $formattedUsedDisk }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="block text-[10px] font-black uppercase text-slate-400">Available</span>
                                <span class="text-sm font-black text-slate-950 mt-1 block">{{ $formattedFreeDisk }}</span>
                            </div>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>
