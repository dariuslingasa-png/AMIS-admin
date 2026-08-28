<x-admin-layout
    title="Family Accounts / SOA"
    :breadcrumbs="[
        ['label' => 'Finance', 'href' => route('admin.finance.dashboard')],
        ['label' => 'Family Accounts', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner Header -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 sm:p-8 text-white shadow-md">
            <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Finance Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Family Accounts / SOA</h1>
                    <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                        Monitor consolidated family ledgers, student fee allocations, official statements of account, and payment records.
                    </p>
                </div>
                <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                    <a href="{{ route('admin.finance.onsite.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white text-emerald-950 px-4 py-2.5 text-xs font-black shadow-sm hover:bg-emerald-50 transition">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-700"></i>
                        <span>Record Onsite Payment</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- View Tabs & Search Filter Form -->
        <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs space-y-4">
            {{-- RECORD TYPE TABS --}}
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3 flex-wrap">
                <a
                    href="{{ route('admin.finance.families.index', ['tab' => 'official']) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black transition {{ ($activeTab ?? 'official') === 'official' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}"
                >
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>Official Database Records ({{ $officialCount ?? 916 }} Families · 1,155+ Students)</span>
                </a>
                @if ($demoCount ?? 0 > 0)
                    <a
                        href="{{ route('admin.finance.families.index', ['tab' => 'demo']) }}"
                        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-black transition {{ ($activeTab ?? 'official') === 'demo' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}"
                    >
                        <i data-lucide="flask-conical" class="w-4 h-4"></i>
                        <span>Demo Testing Accounts ({{ $demoCount }} Families)</span>
                    </a>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.finance.families.index') }}" class="flex flex-col md:flex-row items-center gap-3 w-full" id="filterForm">
                <input type="hidden" name="tab" value="{{ $activeTab ?? 'official' }}">

                <!-- Search Input -->
                <div class="relative w-full md:flex-1">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search by student name, student #, LRN, parent name, or email..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                    />
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </div>
                </div>

                <!-- Action Buttons -->
                <button type="submit" class="h-11 w-full md:w-28 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-100">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Search
                </button>

                @if(request()->filled('q'))
                    <a href="{{ route('admin.finance.families.index', ['tab' => $activeTab ?? 'official']) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 transition px-2 py-2">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i> Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Error Alert -->
        @if (isset($errors) && $errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm flex items-start gap-3">
                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-rose-600 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm">Action Failed</h4>
                    <ul class="mt-1 list-disc pl-4 text-xs text-rose-700 space-y-0.5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- Families Table List -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm text-slate-500">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Family Representative</th>
                            <th class="px-6 py-4">Linked Students</th>
                            <th class="px-6 py-4">Account Status</th>
                            <th class="px-6 py-4 text-right">Consolidated Remaining</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($families as $family)
                            @php
                                $applicants = is_iterable($family->enrollmentApplicants ?? null) ? collect($family->enrollmentApplicants) : collect();
                                $accounts = $applicants->map(fn($applicant) => is_object($applicant) ? ($applicant->student?->account ?? ($applicant->account ?? null)) : null)->filter();
                                $openCount = $accounts->filter(fn($a) => (float)($a->remaining_balance ?? 0) > 0.01)->count();
                                $totalRemaining = (float) $accounts->sum(fn($a) => (float)($a->remaining_balance ?? 0));
                                $familyId = $family->id ?? ($family->user_id ?? 999001);
                                $familyInitials = collect(explode(' ', $family->name))->filter()->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('');
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition group">
                                <!-- 1. Family Representative -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/80 font-black text-xs shrink-0 shadow-2xs">
                                            {{ $familyInitials ?: 'FA' }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.finance.families.show', $familyId) }}" class="font-extrabold text-slate-900 uppercase tracking-tight group-hover:text-emerald-700 hover:underline transition">
                                                    {{ $family->name }}
                                                </a>
                                                @if ($family->is_demo ?? false)
                                                    <span class="inline-flex items-center rounded-md bg-amber-100 border border-amber-200 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-800">
                                                        DEMO
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $family->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Linked Students -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1.5">
                                        @php
                                            $allChildren = collect();
                                            foreach ($applicants as $app) {
                                                $st = is_object($app) ? ($app->student ?? null) : null;
                                                $allChildren->push([
                                                    'name' => is_object($app) && isset($app->full_name) ? $app->full_name : (is_object($app) && isset($app->first_name) ? trim("{$app->first_name} {$app->last_name}") : 'Student'),
                                                    'grade' => $app->grade_level ?? ($st?->grade_level ?? ''),
                                                    'id' => $app->amis_student_id ?? ($st?->student_number ?? ''),
                                                    'balance' => (float) ($st?->account?->remaining_balance ?? 0),
                                                ]);
                                            }
                                            if ($allChildren->isEmpty() && isset($family->students)) {
                                                foreach ($family->students as $st) {
                                                    $allChildren->push([
                                                        'name' => $st->full_name ?: 'Student',
                                                        'grade' => $st->grade_level ?? '',
                                                        'id' => $st->student_number ?? '',
                                                        'balance' => (float) ($st->account?->remaining_balance ?? 0),
                                                    ]);
                                                }
                                            }
                                        @endphp
                                        @forelse($allChildren as $child)
                                            <div class="flex items-center gap-2 text-xs">
                                                <i data-lucide="circle-user" class="h-3.5 w-3.5 text-emerald-500 shrink-0"></i>
                                                <span class="font-bold text-slate-800 uppercase">{{ $child['name'] }}</span>
                                                @if($child['grade'])
                                                    <span class="text-slate-400 font-medium">({{ $child['grade'] }})</span>
                                                @endif
                                                @if($child['balance'] > 0.01)
                                                    <span class="text-[11px] font-bold text-rose-700 ml-1">₱{{ number_format($child['balance'], 2) }}</span>
                                                @else
                                                    <span class="text-[11px] font-bold text-emerald-700 ml-1">Settled</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-xs text-slate-400 italic">No linked students</span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- 3. Account Status -->
                                <td class="px-6 py-4">
                                    @if($openCount > 0)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-bold text-amber-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            {{ $openCount }} Open Account{{ $openCount > 1 ? 's' : '' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Fully Settled
                                        </span>
                                    @endif
                                </td>

                                <!-- 4. Consolidated Remaining -->
                                <td class="px-6 py-4 text-right">
                                    <span class="text-base font-black tracking-tight {{ $totalRemaining > 0.01 ? 'text-slate-900' : 'text-emerald-700' }}">
                                        ₱{{ number_format($totalRemaining, 2) }}
                                    </span>
                                </td>

                                <!-- 5. Actions -->
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.finance.families.show', $familyId) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
                                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                        <span>View SOA</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                                            <i data-lucide="users-2" class="h-10 w-10"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-base font-bold text-slate-800">No family accounts found</h3>
                                            <p class="text-sm text-slate-500 max-w-sm mx-auto">We couldn't find any family records matching your search query.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($families->hasPages())
            <div class="mt-6">
                {{ $families->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
