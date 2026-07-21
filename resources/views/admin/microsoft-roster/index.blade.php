<x-admin-layout :title="request('category') === 'halaqah' ? 'Halaqah Official Rosters' : 'Microsoft Teams Roster Sync'">
    @include('admin.microsoft-roster._alerts')

    @php
        $isHalaqah = request('category') === 'halaqah';
        $themeColor = $isHalaqah ? 'emerald' : 'blue';
    @endphp

    @if($isHalaqah)
        <!-- Header Banner -->
        <div class="relative overflow-hidden p-6 md:p-8 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-2xl border border-emerald-700/30 shadow-sm text-white print-hide mb-6">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-emerald-500/10 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-teal-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-emerald-500/20 text-emerald-300 rounded-full border border-emerald-500/30 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Official Rosters
                    </span>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white font-outfit">Halaqah Official Rosters</h1>
                    <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                        Synchronize and review official Microsoft Teams class rosters for Halaqah Online.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 print-hide self-start md:self-center">
                    <form method="POST" action="{{ route('admin.microsoft-roster.sync') }}">@csrf
                        <button class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white px-4 py-2.5 text-sm font-bold transition shadow-sm" {{ in_array($currentRun?->status, ['queued', 'running'], true) ? 'disabled' : '' }}>
                            <i data-lucide="refresh-cw" class="w-4 h-4 text-white"></i> Sync All Teams
                        </button>
                    </form>
                    <a href="{{ route('admin.microsoft-roster.index', ['category' => 'halaqah']) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition"><i data-lucide="rotate-cw" class="h-4 w-4"></i> Refresh</a>
                    <a href="{{ route('admin.microsoft-roster.export', ['format' => 'csv', 'category' => 'halaqah']) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Export CSV</a>
                    <a href="{{ route('admin.microsoft-roster.export', ['format' => 'json', 'category' => 'halaqah']) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition">Export JSON</a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex gap-4 border-b border-slate-200 dark:border-gray-700 mb-6 pb-px print-hide">
            <a href="{{ route('admin.registrations.halaqah') }}" class="pb-3 text-xs font-bold text-slate-500 hover:text-slate-800 dark:text-gray-450 dark:hover:text-gray-200 transition-all relative">
                Registration Submissions
            </a>
            <a href="{{ route('admin.microsoft-roster.index', ['category' => 'halaqah']) }}" class="pb-3 text-xs font-extrabold text-emerald-650 dark:text-emerald-450 border-b-2 border-emerald-500 transition-all relative">
                Official Rosters
            </a>
        </div>
    @else
        <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <p class="mb-1 text-[10px] font-black uppercase tracking-[0.2em] text-blue-700">Microsoft Integration</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-950">Microsoft Teams Roster Sync</h1>
                <p class="mt-1 text-sm text-slate-500">Synchronize and review Microsoft Teams class rosters from the AMIS Microsoft 365 tenant.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.microsoft-roster.sync') }}">@csrf
                    <button class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800" {{ in_array($currentRun?->status, ['queued', 'running'], true) ? 'disabled' : '' }}>
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Sync All Teams
                    </button>
                </form>
                <a href="{{ route('admin.microsoft-roster.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50"><i data-lucide="rotate-cw" class="h-4 w-4"></i> Refresh</a>
                <a href="{{ route('admin.microsoft-roster.export', 'csv') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Export CSV</a>
                <a href="{{ route('admin.microsoft-roster.export', 'json') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Export JSON</a>
                <a href="{{ route('admin.microsoft-roster.history') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">View Sync History</a>
            </div>
        </div>
    @endif

    @php
        $cards = [
            ['Total Teams', $stats['teams'], 'users-round', 'blue'],
            ['Active Memberships', $stats['memberships'], 'contact-round', 'emerald'],
            ['Team Owners', $stats['owners'], 'shield-check', 'violet'],
            ['Matched Students', $stats['matched_students'], 'graduation-cap', 'cyan'],
            ['Matched Faculty/Staff', $stats['matched_faculty'], 'briefcase-business', 'amber'],
            ['Unmatched Accounts', $stats['unmatched'], 'user-round-x', 'rose'],
            ['Confirmed Mappings', $stats['confirmed_mappings'], 'git-compare-arrows', 'indigo'],
            ['Last Successful Sync', $stats['last_successful_sync']?->completed_at?->diffForHumans() ?? 'Never', 'clock-3', 'slate'],
        ];
    @endphp
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as [$label, $value, $icon, $color])
            <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between"><span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ $label }}</span><i data-lucide="{{ $icon }}" class="h-4 w-4 text-{{ $color }}-600"></i></div>
                <div class="text-2xl font-black text-slate-900">{{ is_numeric($value) ? number_format($value) : $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="mb-6 rounded-2xl border border-{{ $themeColor }}-100 bg-{{ $themeColor }}-50/60 p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-black uppercase tracking-widest text-{{ $themeColor }}-950">Synchronization Status</h2>
            <span class="rounded-full px-3 py-1 text-xs font-bold {{ in_array($currentRun?->status, ['queued','running'], true) ? 'bg-amber-100 text-amber-800' : (($currentRun?->status === 'failed') ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800') }}">{{ str_replace('_', ' ', ucfirst($currentRun?->status ?? 'not started')) }}</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4 xl:grid-cols-7">
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Progress</span><strong>{{ $currentRun ? $currentRun->teams_processed.' / '.$currentRun->teams_discovered.' teams' : '—' }}</strong></div>
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Teams Processed</span><strong>{{ $currentRun?->teams_processed ?? 0 }}</strong></div>
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Members Retrieved</span><strong>{{ $currentRun?->members_discovered ?? 0 }}</strong></div>
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Started</span><strong>{{ $currentRun?->started_at?->format('M j, Y g:i A') ?? '—' }}</strong></div>
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Completed</span><strong>{{ $currentRun?->completed_at?->format('M j, Y g:i A') ?? '—' }}</strong></div>
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Started By</span><strong>{{ $currentRun?->startedBy?->name ?? ($currentRun ? 'CLI / System' : '—') }}</strong></div>
            <div><span class="block text-[10px] font-black uppercase text-slate-400">Warnings</span><strong>{{ $currentRun?->failed_teams ?? 0 }}</strong></div>
        </div>
        @if ($currentRun?->error_summary)<p class="mt-4 whitespace-pre-line rounded-xl bg-white/70 px-4 py-3 text-xs font-semibold text-rose-700">{{ $currentRun->error_summary }}</p>@endif
    </div>

    <x-card title="Teams Directory" subtitle="Local read-only snapshots retrieved from Microsoft Graph">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 md:grid-cols-3 xl:grid-cols-6">
            <input name="search" value="{{ request('search') }}" placeholder="Search Team name" class="rounded-xl border-slate-200 text-sm">
            @if($isHalaqah)
                <input type="hidden" name="category" value="halaqah">
            @else
                <select name="category" class="rounded-xl border-slate-200 text-sm"><option value="">All categories</option>@foreach(['academic','isal','halaqah','general','other'] as $value)<option value="{{ $value }}" @selected(request('category')===$value)>{{ ucfirst($value) }}</option>@endforeach</select>
            @endif
            <select name="mapping_status" class="rounded-xl border-slate-200 text-sm"><option value="">All mappings</option>@foreach(['pending','suggested','confirmed','ignored'] as $value)<option value="{{ $value }}" @selected(request('mapping_status')===$value)>{{ ucfirst($value) }}</option>@endforeach</select>
            <select name="school_year_id" class="rounded-xl border-slate-200 text-sm"><option value="">All school years</option>@foreach($schoolYears as $year)<option value="{{ $year->id }}" @selected((string)request('school_year_id')===(string)$year->id)>{{ $year->code }}</option>@endforeach</select>
            <select name="grade_level_id" class="rounded-xl border-slate-200 text-sm"><option value="">All grade levels</option>@foreach($gradeLevels as $grade)<option value="{{ $grade->id }}" @selected((string)request('grade_level_id')===(string)$grade->id)>{{ $grade->name }}</option>@endforeach</select>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">Apply Filters</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr>
                    <th class="px-4 py-3"><a href="{{ request()->fullUrlWithQuery(['sort'=>'display_name','direction'=>request('direction')==='asc'?'desc':'asc']) }}">Team Name</a></th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Visibility</th><th class="px-4 py-3"><a href="{{ request()->fullUrlWithQuery(['sort'=>'member_count','direction'=>'desc']) }}">Members</a></th><th class="px-4 py-3">Owners</th><th class="px-4 py-3">AMIS Grade & Section</th><th class="px-4 py-3">Mapping</th><th class="px-4 py-3"><a href="{{ request()->fullUrlWithQuery(['sort'=>'last_synced_at','direction'=>'desc']) }}">Last Synced</a></th><th class="px-4 py-3">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teams as $team)
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="px-4 py-3">
                                @if($isHalaqah)
                                    <a class="font-extrabold text-emerald-700 hover:underline" href="{{ route('admin.microsoft-roster.show',$team) }}">{{ $team->display_name }}</a>
                                @else
                                    <a class="font-extrabold text-blue-700 hover:underline" href="{{ route('admin.microsoft-roster.show',$team) }}">{{ $team->display_name }}</a>
                                @endif
                                <div class="mt-1 font-mono text-[10px] text-slate-400">{{ $team->microsoft_team_id }}</div>
                            </td>
                            <td class="px-4 py-3 capitalize">{{ $team->team_category ?? 'Unknown' }}</td><td class="px-4 py-3 capitalize">{{ $team->visibility ?? '—' }}</td><td class="px-4 py-3 font-bold">{{ $team->member_count }}</td><td class="px-4 py-3 font-bold">{{ $team->owner_count }}</td>
                            <td class="px-4 py-3">{{ $team->mapping?->gradeLevel?->name ?? '—' }} @if($team->mapping?->section) · {{ $team->mapping->section->name }} @endif</td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase">{{ $team->mapping?->mapping_status ?? 'pending' }}</span></td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $team->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    @if($isHalaqah)
                                        <a class="font-bold text-emerald-700 hover:underline" href="{{ route('admin.microsoft-roster.show',$team) }}">Roster</a>
                                        <a class="font-bold text-teal-700 hover:underline" href="{{ route('admin.microsoft-roster.mappings.edit',$team) }}">Map</a>
                                    @else
                                        <a class="font-bold text-blue-700 hover:underline" href="{{ route('admin.microsoft-roster.show',$team) }}">Roster</a>
                                        <a class="font-bold text-indigo-700 hover:underline" href="{{ route('admin.microsoft-roster.mappings.edit',$team) }}">Map</a>
                                    @endif
                                    <a class="font-bold text-slate-600 hover:underline" href="{{ route('admin.microsoft-roster.raw',$team) }}">JSON</a>
                                    <form method="POST" action="{{ route('admin.microsoft-roster.team.sync',$team) }}">@csrf<button class="font-bold text-emerald-700 hover:underline">Sync</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty<tr><td colspan="9" class="px-4 py-12 text-center text-slate-500">No synchronized Teams match these filters.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $teams->links() }}</div>
    </x-card>
</x-admin-layout>
