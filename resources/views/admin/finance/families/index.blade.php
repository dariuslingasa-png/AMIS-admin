<x-admin-layout title="Family Accounts / SOA">
    <div class="finance-page mx-auto max-w-[1450px] p-5 lg:p-8">
        @include('admin.finance._nav', ['title' => 'Family Accounts / SOA', 'subtitle' => 'View one consolidated family balance across every enrolled child.'])
        <form class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row"><input name="q" value="{{ request('q') }}" placeholder="Search parent, student, email, or AMIS student ID" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm"><button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white">Search</button></form>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($families as $family)
                @php
                    $accounts = $family->enrollmentApplicants->map(fn($applicant) => $applicant->student?->account)->filter();
                @endphp
                <a href="{{ route('admin.finance.families.show',$family) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-extrabold text-slate-900">{{ $family->name }}</p><p class="mt-1 break-all text-xs text-slate-500">{{ $family->email }}</p></div><span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $accounts->count() }} students</span></div>
                    <div class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4"><div><p class="text-xs font-bold uppercase text-slate-500">Account status</p><p class="mt-1 text-sm font-bold text-amber-700">{{ $accounts->where('remaining_balance','>',0)->count() }} open account(s)</p></div><div class="text-right"><p class="text-xs text-slate-500">Remaining balance</p><p class="text-xl font-black text-slate-900">₱{{ number_format($accounts->sum(fn($a)=>(float)$a->remaining_balance),2) }}</p></div></div>
                </a>
            @empty<div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">No family accounts found.</div>@endforelse
        </div>
        <div class="mt-5">{{ $families->links() }}</div>
    </div>
</x-admin-layout>
