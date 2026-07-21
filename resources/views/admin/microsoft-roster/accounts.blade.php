<x-admin-layout title="Microsoft Accounts">
    @include('admin.microsoft-roster._alerts')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="mb-1 text-[10px] font-black uppercase tracking-widest text-blue-700">Microsoft Integration</p><h1 class="text-2xl font-extrabold text-slate-950">Microsoft Accounts</h1><p class="mt-1 text-sm text-slate-500">Distinct Microsoft identities found in active Team rosters.</p></div>
        <form method="GET" class="flex gap-2"><input name="search" value="{{ request('search') }}" placeholder="Name or Microsoft email" class="w-72 rounded-xl border-slate-200 text-sm"><button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white">Search</button></form>
    </div>
    <x-card title="Account Directory" subtitle="One row per normalized Microsoft identity">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Microsoft Name</th><th class="px-4 py-3">Email / UPN</th><th class="px-4 py-3">Account Type</th><th class="px-4 py-3">Teams Joined</th><th class="px-4 py-3">Match Status</th><th class="px-4 py-3">Last Seen</th><th class="px-4 py-3">Action</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse($accounts as $account)<tr>
                <td class="px-4 py-3 font-bold text-slate-900">{{ $account->display_name }}</td><td class="px-4 py-3"><div>{{ $account->email ?? '—' }}</div>@if($account->user_principal_name && $account->user_principal_name !== $account->email)<div class="text-xs text-slate-400">{{ $account->user_principal_name }}</div>@endif</td><td class="px-4 py-3 capitalize">{{ $account->account_type }}</td><td class="px-4 py-3 font-bold">{{ $account->teams_joined }}</td><td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase">{{ str_replace('_',' ',$account->match_status) }}</span></td><td class="px-4 py-3 text-xs text-slate-500">{{ $account->last_seen_at ? \Illuminate\Support\Carbon::parse($account->last_seen_at)->diffForHumans() : '—' }}</td><td class="px-4 py-3"><a class="font-bold text-blue-700" href="{{ route('admin.microsoft-roster.matches.review',$account->id) }}">Review</a></td>
            </tr>@empty<tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No synchronized Microsoft accounts are available.</td></tr>@endforelse</tbody>
        </table></div><div class="p-4">{{ $accounts->links() }}</div>
    </x-card>
</x-admin-layout>
