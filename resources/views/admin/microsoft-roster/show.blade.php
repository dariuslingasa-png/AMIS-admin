<x-admin-layout title="{{ $team->display_name }}">
    @include('admin.microsoft-roster._alerts')
    @php
        $isHalaqah = $team->team_category === 'halaqah';
        $themeColor = $isHalaqah ? 'emerald' : 'blue';
    @endphp
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            @if($isHalaqah)
                <a href="{{ route('admin.microsoft-roster.index', ['category' => 'halaqah']) }}" class="mb-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-800 transition-all">← Official Rosters</a>
            @else
                <a href="{{ route('admin.microsoft-roster.index') }}" class="mb-2 inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:text-blue-800 transition-all">← Teams Roster Sync</a>
            @endif
            <h1 class="text-2xl font-extrabold text-slate-950">{{ $team->display_name }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">{{ $team->description ?: 'No Microsoft Team description.' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.microsoft-roster.team.sync',$team) }}">@csrf
                <button class="rounded-xl bg-{{ $themeColor }}-700 hover:bg-{{ $themeColor }}-800 px-4 py-2.5 text-sm font-bold text-white transition-all">Sync This Team</button>
            </form>
            <a href="{{ route('admin.microsoft-roster.roster.export',[$team,'csv']) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold hover:bg-slate-50 transition-all">Export CSV</a>
            <a href="{{ route('admin.microsoft-roster.roster.export',[$team,'json']) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold hover:bg-slate-50 transition-all">Export JSON</a>
            <a href="{{ route('admin.microsoft-roster.raw',$team) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold hover:bg-slate-50 transition-all">Raw JSON</a>
        </div>
    </div>
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            'Microsoft Team ID'=>$team->microsoft_team_id,'Visibility'=>ucfirst($team->visibility ?? 'Unknown'),'Category'=>ucfirst($team->team_category ?? 'Unknown'),'Members / Owners'=>$team->member_count.' / '.$team->owner_count,'Last synchronized'=>$team->last_synced_at?->format('M j, Y g:i A') ?? 'Never',
            'Mapped grade'=>$team->mapping?->gradeLevel?->name ?? 'Not mapped','Mapped section'=>$team->mapping?->section?->name ?? 'Not mapped','Shift'=>$team->mapping?->shift ?? '—','School year'=>$team->mapping?->schoolYear?->code ?? '—','Mapping status'=>ucfirst($team->mapping?->mapping_status ?? 'pending')
        ] as $label=>$value)<div class="rounded-2xl border border-slate-100 bg-white p-4"><div class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $label }}</div><div class="mt-1 break-all text-sm font-extrabold text-slate-900">{{ $value }}</div></div>@endforeach
    </div>
    <x-card title="Team Roster" subtitle="Current and historical local membership snapshots">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input name="search" value="{{ request('search') }}" placeholder="Student name, AMIS ID, or Microsoft email" class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm">
            <select name="filter" class="rounded-xl border-slate-200 text-sm"><option value="">All</option>@foreach(['students'=>'Matched Students','faculty'=>'Faculty/Staff','unmatched'=>'Unmatched','owners'=>'Owners','members'=>'Members','inactive'=>'Inactive'] as $value=>$label)<option value="{{ $value }}" @selected(request('filter')===$value)>{{ $label }}</option>@endforeach</select>
            <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">Filter</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">AMIS ID</th><th class="px-4 py-3">Student / Account Name</th><th class="px-4 py-3">Microsoft Email</th><th class="px-4 py-3">Account Type</th><th class="px-4 py-3">Teams Role</th><th class="px-4 py-3">AMIS Grade</th><th class="px-4 py-3">AMIS Section</th><th class="px-4 py-3">Match Status</th><th class="px-4 py-3">First / Last Seen</th><th class="px-4 py-3">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse($memberships as $member)<tr class="{{ $member->is_active ? '' : 'bg-slate-50 opacity-70' }}">
                <td class="px-4 py-3 font-mono text-xs">{{ $member->student?->student_number ?? '—' }}</td><td class="px-4 py-3 font-bold">{{ $member->display_name }} @unless($member->is_active)<span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[9px] uppercase">Inactive</span>@endunless</td><td class="px-4 py-3">{{ $member->email ?? $member->user_principal_name ?? '—' }}</td><td class="px-4 py-3 capitalize">{{ $member->account_type }}</td><td class="px-4 py-3"><span class="rounded-full {{ $member->team_role==='owner'?'bg-violet-100 text-violet-800':'bg-blue-50 text-blue-800' }} px-2 py-1 text-[10px] font-black uppercase">{{ $member->team_role }}</span></td><td class="px-4 py-3">{{ $member->student?->grade_level ?? '—' }}</td><td class="px-4 py-3">{{ $member->student?->section ?? $member->student?->studentSection?->section ?? '—' }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-black uppercase">{{ str_replace('_',' ',$member->match_status) }}</span></td><td class="px-4 py-3 text-xs text-slate-500">{{ $member->first_seen_at?->format('M j, Y') ?? '—' }}<br>{{ $member->last_seen_at?->diffForHumans() ?? '—' }}</td>
                <td class="px-4 py-3">
                    <div class="space-y-1">
                        @if($member->student)
                            <a class="block font-bold text-{{ $themeColor }}-700 hover:underline" href="{{ route('admin.students.show',$member->student) }}">View AMIS Record</a>
                        @endif
                        <a class="block font-bold text-indigo-700 hover:underline" href="{{ route('admin.microsoft-roster.matches.review',$member) }}">{{ $member->match_method==='manual'?'Review Match':'Match Manually' }}</a>
                        @if($member->match_method==='manual')
                            <form method="POST" action="{{ route('admin.microsoft-roster.matches.destroy',$member) }}">@csrf @method('DELETE')<button class="font-bold text-rose-700 hover:underline" onclick="return confirm('Remove this manual link and re-run exact matching?')">Remove Manual Match</button></form>
                        @endif
                    </div>
                </td>
            </tr>@empty<tr><td colspan="10" class="px-4 py-12 text-center text-slate-500">No roster members match this filter.</td></tr>@endforelse</tbody>
        </table></div><div class="p-4">{{ $memberships->links() }}</div>
    </x-card>
</x-admin-layout>
