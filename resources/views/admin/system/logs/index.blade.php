<x-admin-layout title="System Logs">
    <div class="space-y-6" x-data="{ levelFilter: '' }">
        <!-- Reusable Workspace Header Component -->
        <x-system-nav title="Live System Error Log Viewer" subtitle="Parses real-time laravel.log events with timestamp filters, environment tags, and severity level badges." activeTab="logs" />

        <div class="flex justify-end">
            <form method="POST" action="{{ route('admin.system-management.logs.clear') }}" onsubmit="return confirm('Are you sure you want to clear and truncate laravel.log? This cannot be undone!')" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-rose-600 hover:bg-rose-700 px-4 py-2 text-xs font-black text-white shadow-md transition cursor-pointer">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Clear Log File</span>
                </button>
            </form>
        </div>

        <!-- Stats Bar & Level Filters -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-400">Filter Level:</span>
                <button type="button" @click="levelFilter = ''" :class="!levelFilter ? 'bg-slate-900 text-white font-black' : 'bg-slate-100 text-slate-600 font-bold hover:bg-slate-200'" class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                    All Entries ({{ count($logEntries) }})
                </button>
                <button type="button" @click="levelFilter = 'ERROR'" :class="levelFilter === 'ERROR' ? 'bg-rose-600 text-white font-black' : 'bg-rose-50 text-rose-700 font-bold hover:bg-rose-100'" class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                    Errors
                </button>
                <button type="button" @click="levelFilter = 'WARNING'" :class="levelFilter === 'WARNING' ? 'bg-amber-600 text-white font-black' : 'bg-amber-50 text-amber-700 font-bold hover:bg-amber-100'" class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                    Warnings
                </button>
                <button type="button" @click="levelFilter = 'INFO'" :class="levelFilter === 'INFO' ? 'bg-blue-600 text-white font-black' : 'bg-blue-50 text-blue-700 font-bold hover:bg-blue-100'" class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                    Info
                </button>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-slate-500">Log File Size: <strong class="text-slate-900">{{ $formattedLogSize }}</strong></span>
            </div>
        </div>

        <!-- Log Viewer Container -->
        <div class="rounded-3xl border border-slate-800 bg-slate-950 p-4 shadow-2xl overflow-hidden text-emerald-400 font-mono text-xs leading-relaxed">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3 px-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Log Output Stream (<span x-text="levelFilter || 'ALL'"></span>)</span>
                <span class="text-[11px] text-slate-500 font-medium truncate max-w-md" title="{{ $logPath }}">{{ basename($logPath) }}</span>
            </div>

            @if(empty($logEntries))
                <div class="p-12 text-center text-slate-500 italic">
                    No log entries found. The log file is empty or cleanly truncated.
                </div>
            @else
                <div class="max-h-[600px] overflow-y-auto space-y-2 pr-2">
                    @foreach($logEntries as $entry)
                        <div x-show="!levelFilter || levelFilter === '{{ $entry['level'] }}'" 
                             class="p-3 rounded-xl border border-slate-900 bg-slate-900/80 hover:bg-slate-900 transition flex flex-col gap-1">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-slate-500 font-bold text-[10px]">#{{ $entry['index'] }}</span>
                                    <span class="text-slate-400 text-[11px]">{{ $entry['timestamp'] }}</span>
                                    @if($entry['level'] === 'ERROR' || $entry['level'] === 'CRITICAL' || $entry['level'] === 'EMERGENCY')
                                        <span class="px-2 py-0.5 rounded bg-rose-500/20 border border-rose-500/40 text-rose-300 font-black text-[10px] uppercase tracking-wider">
                                            {{ $entry['level'] }}
                                        </span>
                                    @elseif($entry['level'] === 'WARNING')
                                        <span class="px-2 py-0.5 rounded bg-amber-500/20 border border-amber-500/40 text-amber-300 font-black text-[10px] uppercase tracking-wider">
                                            {{ $entry['level'] }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-blue-500/20 border border-blue-500/40 text-blue-300 font-black text-[10px] uppercase tracking-wider">
                                            {{ $entry['level'] }}
                                        </span>
                                    @endif
                                    <span class="text-slate-600 text-[10px] font-semibold lowercase">({{ $entry['env'] ?? 'production' }})</span>
                                </div>
                            </div>
                            <div class="mt-1 text-slate-200 whitespace-pre-wrap break-all font-mono leading-relaxed select-all">
                                {{ $entry['message'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
