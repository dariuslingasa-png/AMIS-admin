<x-admin-layout title="Backup Center">
    <div class="space-y-6">
        <!-- Reusable Workspace Header Component -->
        <x-system-nav title="Database & System Backup Center" subtitle="Disaster recovery backups, local snapshots, automated cron scheduling, and Google Drive cloud backups." activeTab="backups" />

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Quick Disaster Recovery Actions Banner -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 rounded-3xl border border-indigo-700/40 bg-gradient-to-r from-slate-950 via-indigo-950 to-slate-900 p-6 text-white shadow-xl shadow-indigo-950/20">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-black uppercase tracking-wider text-indigo-300 border border-indigo-400/30">
                        ⚡ Disaster Recovery Controls
                    </span>
                </div>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-white">Full Application & Database Backup</h2>
                <p class="text-xs text-slate-300 mt-1 max-w-xl leading-relaxed">
                    Generate an instant full backup archive (Complete MySQL Database dump + Project source code + All uploaded images & documents) with email report to <strong>darius.lingasa@gmail.com</strong>.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <form method="POST" action="{{ route('admin.system-management.backups.trigger-full') }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Start a full application & database backup now?')"
                            class="inline-flex h-12 items-center gap-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-600 px-6 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-900/40 transition hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        <i data-lucide="archive" class="w-5 h-5"></i>
                        <span>Create Full Application Backup Now</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.system-management.backups.create') }}">
                    @csrf
                    <button type="submit" onclick="return confirm('Create an instant local SQL database snapshot?')"
                            class="inline-flex h-12 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 text-xs font-black uppercase tracking-wider text-white backdrop-blur-xs transition hover:bg-white/20 cursor-pointer">
                        <i data-lucide="database" class="w-4 h-4 text-slate-300"></i>
                        <span>Quick SQL Snapshot</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Disaster Recovery Metric Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Last Successful -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-bold uppercase tracking-wider">Last Success</span>
                    <div class="rounded-2xl bg-emerald-50 p-2 text-emerald-600">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg font-black text-slate-900 truncate">
                        {{ $lastSuccessful['created_at'] ?? 'No Records Yet' }}
                    </div>
                    <p class="mt-1 text-xs text-slate-500 truncate">Recipient: darius.lingasa@gmail.com</p>
                </div>
            </div>

            <!-- Last Failed -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-bold uppercase tracking-wider">Last Failed</span>
                    <div class="rounded-2xl bg-rose-50 p-2 text-rose-600">
                        <i data-lucide="alert-octagon" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg font-black text-slate-900 truncate">
                        {{ $lastFailed['created_at'] ?? 'None' }}
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Immediate Error Alerts Enabled</p>
                </div>
            </div>

            <!-- Next Scheduled -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-bold uppercase tracking-wider">Next Schedule</span>
                    <div class="rounded-2xl bg-blue-50 p-2 text-blue-600">
                        <i data-lucide="clock" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg font-black text-slate-900">
                        12:00 AM / 12:00 PM
                    </div>
                    <p class="mt-1 text-xs text-slate-500">PHT (Asia/Manila) Cron Active</p>
                </div>
            </div>

            <!-- DB Storage Size -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-500">
                    <span class="text-xs font-bold uppercase tracking-wider">Live Database</span>
                    <div class="rounded-2xl bg-purple-50 p-2 text-purple-600">
                        <i data-lucide="database" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg font-black text-slate-900">
                        {{ $dbSize }}
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Information Schema Allocation</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <!-- Snapshots list -->
            <div class="space-y-6">
                <x-card title="Backup Snapshots & History" subtitle="Disaster recovery zip archives and SQL snapshots stored securely in storage/app/backups">
                    <!-- Actions header -->
                    <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs font-extrabold text-slate-500 uppercase">Archive Snapshots</span>
                        <div class="flex flex-wrap items-center gap-2">
                            @if(auth()->user()->role === 'super_admin')
                                <form method="POST" action="{{ route('admin.system-management.backups.prune') }}" onsubmit="return confirm('Prune older SQL backups from server disk storage?')" class="flex items-center gap-1">
                                    @csrf
                                    <select name="days" class="h-9 px-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 outline-none">
                                        <option value="14">Prune &gt; 14 Days</option>
                                        <option value="30" selected>Prune &gt; 30 Days</option>
                                        <option value="60">Prune &gt; 60 Days</option>
                                    </select>
                                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-amber-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-amber-700 cursor-pointer">
                                        <i data-lucide="scissors" class="h-3.5 w-3.5"></i>
                                        Prune Disk
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.system-management.backups.create') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-4 text-xs font-bold text-white transition hover:bg-slate-800 cursor-pointer">
                                        <i data-lucide="database" class="h-4 w-4"></i>
                                        Snapshot DB
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.system-management.backups.trigger-full') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 cursor-pointer">
                                        <i data-lucide="archive" class="h-4 w-4"></i>
                                        Run Full Backup
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm min-w-[650px]">
                            <thead>
                                <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">File Name</th>
                                    <th class="px-4 py-3">Size</th>
                                    <th class="px-4 py-3">Created</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($backups as $file)
                                    <tr class="align-middle">
                                        <td class="px-4 py-4 text-xs">
                                            @if($file['extension'] === 'ZIP')
                                                <span class="px-2 py-1 rounded bg-emerald-50 border border-emerald-200 text-emerald-700 font-black text-[10px]">ZIP</span>
                                            @else
                                                <span class="px-2 py-1 rounded bg-blue-50 border border-blue-200 text-blue-700 font-black text-[10px]">SQL</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-xs font-bold text-slate-700">
                                            {{ $file['filename'] }}
                                        </td>
                                        <td class="px-4 py-4 text-xs font-semibold text-slate-500">
                                            {{ $file['size'] }}
                                        </td>
                                        <td class="px-4 py-4 text-xs font-bold text-slate-500">
                                            {{ $file['created_at'] }}
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2 flex-wrap" x-data="{ showRestoreModal: false, confirmText: '' }">
                                                @if(auth()->user()->role === 'super_admin')
                                                    <a href="{{ route('admin.system-management.backups.download', $file['filename']) }}" class="rounded bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-700 hover:bg-slate-200 transition">
                                                        Download
                                                    </a>

                                                    <button type="button" @click="showRestoreModal = true" class="rounded bg-amber-50 px-2 py-1 text-[10px] font-black uppercase text-amber-700 hover:bg-amber-100 transition cursor-pointer">
                                                        Restore
                                                    </button>

                                                    <form method="POST" action="{{ route('admin.system-management.backups.destroy', $file['filename']) }}" onsubmit="return confirm('Delete backup file permanently?')" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded bg-rose-50 px-2 py-1 text-[10px] font-black uppercase text-rose-700 hover:bg-rose-100 transition cursor-pointer">
                                                            Delete
                                                        </button>
                                                    </form>

                                                    <!-- Restore Modal -->
                                                    <div x-show="showRestoreModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 text-left" x-cloak>
                                                        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-slate-200 space-y-4">
                                                            <div class="flex items-center gap-3 text-amber-600">
                                                                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                                                                <h3 class="text-lg font-black text-slate-900">Confirm System Restore</h3>
                                                            </div>
                                                            <p class="text-xs text-slate-600 leading-relaxed">
                                                                You are about to restore <strong class="text-slate-900">{{ $file['filename'] }}</strong>. This will overwrite your current database. A pre-restore safety snapshot will be created automatically.
                                                            </p>
                                                            <p class="text-xs font-bold text-slate-700">
                                                                Type <code class="bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">RESTORE</code> below to confirm:
                                                            </p>
                                                            <form method="POST" action="{{ route('admin.system-management.backups.restore') }}">
                                                                @csrf
                                                                <input type="hidden" name="filename" value="{{ $file['filename'] }}">
                                                                <input type="text" name="confirmation" x-model="confirmText" placeholder="RESTORE" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 outline-none focus:border-amber-500 mb-4">
                                                                <div class="flex items-center justify-end gap-2">
                                                                    <button type="button" @click="showRestoreModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-600 hover:bg-slate-200 cursor-pointer">Cancel</button>
                                                                    <button type="submit" :disabled="confirmText !== 'RESTORE'" :class="confirmText === 'RESTORE' ? 'bg-amber-600 text-white hover:bg-amber-700 cursor-pointer' : 'bg-slate-200 text-slate-400 cursor-not-allowed'" class="px-4 py-2 rounded-xl text-xs font-black shadow-sm transition">Execute Restore</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-xs text-slate-400 italic">
                                            No local backup snapshots found in storage/app/backups.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            <!-- Sidebar info & Cloud configuration -->
            <div class="space-y-6">
                <x-card title="Retention & Email Settings" subtitle="Automated Backup Configuration">
                    <div class="p-4 space-y-4 text-xs text-slate-600">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="font-bold text-slate-900 block">Notification Target:</span>
                            <code class="text-emerald-700 font-mono font-bold">darius.lingasa@gmail.com</code>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="font-bold text-slate-900 block">Scheduled Times:</span>
                            <p class="text-slate-600">12:00 AM Midnight &amp; 12:00 PM Noon PHT</p>
                        </div>

                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                            <span class="font-bold text-slate-900 block">Retention Policy:</span>
                            <p class="text-slate-600">Snapshots older than 30 days are safely pruned automatically. The latest backup is never deleted.</p>
                        </div>
                    </div>
                </x-card>

                <!-- Cloud Backup Status -->
                <x-card title="Google Drive Cloud Sync" subtitle="Secondary Disaster Storage">
                    <div class="p-4 space-y-3 text-xs">
                        @if($gdriveConfigured)
                            <div class="flex items-center gap-2 text-emerald-600 font-bold">
                                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                                <span>Google Drive Connected</span>
                            </div>
                            @if($gdriveQuota)
                                <div class="text-slate-500 font-medium">
                                    Drive Space Used: <strong class="text-slate-900">{{ $gdriveQuota['usageFormatted'] ?? 'N/A' }}</strong> / {{ $gdriveQuota['limitFormatted'] ?? 'Unlimited' }}
                                </div>
                            @endif
                        @else
                            <div class="flex items-center gap-2 text-amber-600 font-bold">
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                <span>Google Drive Disconnected / Not Configured</span>
                            </div>
                            <p class="text-slate-500 leading-relaxed">
                                Local backups remain 100% active in <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-800">storage/app/backups/</code>.
                            </p>
                        @endif
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-admin-layout>
