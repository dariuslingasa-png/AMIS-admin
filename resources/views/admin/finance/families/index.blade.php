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
                            <th class="px-5 py-4">Family ID</th>
                            <th class="px-5 py-4">Family Name / Representative</th>
                            <th class="px-5 py-4 text-center">Child / Children</th>
                            <th class="px-5 py-4">Account Status</th>
                            <th class="px-5 py-4 text-right">Consolidated Remaining</th>
                            <th class="px-5 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($families as $family)
                            @php
                                $applicants = is_iterable($family->enrollmentApplicants ?? null) ? collect($family->enrollmentApplicants) : collect();
                                $directStudents = is_iterable($family->students ?? null) ? collect($family->students) : collect();
                                $accounts = $applicants->map(fn($applicant) => is_object($applicant) ? ($applicant->student?->account ?? ($applicant->account ?? null)) : null)->filter();
                                if ($accounts->isEmpty() && $directStudents->isNotEmpty()) {
                                    $accounts = $directStudents->map(fn($st) => $st->account)->filter();
                                }
                                $hasAccounts = $accounts->isNotEmpty();
                                $openCount = $accounts->filter(fn($a) => (float)($a->remaining_balance ?? 0) > 0.01)->count();
                                $totalRemaining = (float) $accounts->sum(fn($a) => (float)($a->remaining_balance ?? 0));
                                $familyId = $family->id ?? ($family->user_id ?? 999001);

                                // Extract and build Family Surname(s)
                                $childSurnames = collect();
                                foreach ($applicants as $app) {
                                    if (!empty($app->last_name)) {
                                        $childSurnames->push(trim(strtoupper($app->last_name)));
                                    } elseif (!empty($app->full_name)) {
                                        $parts = explode(' ', trim($app->full_name));
                                        $childSurnames->push(trim(strtoupper(end($parts))));
                                    }
                                }
                                if ($childSurnames->isEmpty()) {
                                    foreach ($directStudents as $st) {
                                        if (!empty($st->last_name)) {
                                            $childSurnames->push(trim(strtoupper($st->last_name)));
                                        } elseif (!empty($st->full_name)) {
                                            $parts = explode(' ', trim($st->full_name));
                                            $childSurnames->push(trim(strtoupper(end($parts))));
                                        }
                                    }
                                }
                                $uniqueSurnames = $childSurnames->filter()->unique()->values();
                                if ($uniqueSurnames->isNotEmpty()) {
                                    $familyDisplayName = $uniqueSurnames->join(' / ') . ' Family';
                                    $avatarInitials = mb_substr($uniqueSurnames->first(), 0, 2);
                                } else {
                                    $cleanName = trim(preg_replace('/[0-9_.-]+/', ' ', $family->name ?: 'Family'));
                                    $familyDisplayName = (mb_strlen($cleanName) > 2 ? strtoupper($cleanName) : strtoupper($family->name)) . ' Family';
                                    $avatarInitials = mb_substr(preg_replace('/[^A-Za-z]/', '', $family->name) ?: 'FA', 0, 2);
                                }

                                $totalChildrenCount = $applicants->isNotEmpty() ? $applicants->count() : $directStudents->count();
                                if ($totalChildrenCount === 0 && isset($family->children_count)) {
                                    $totalChildrenCount = (int) $family->children_count;
                                }
                                $totalChildrenCount = max(1, $totalChildrenCount);
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition group">
                                <!-- 1. Family ID -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 font-mono text-xs font-black text-slate-700 bg-slate-100 border border-slate-200/80 px-2.5 py-1 rounded-lg">
                                        #{{ $familyId }}
                                    </span>
                                </td>

                                <!-- 2. Family Name & Representative -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200/80 font-black text-xs shrink-0 shadow-2xs">
                                            {{ strtoupper($avatarInitials ?: 'FA') }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('admin.finance.families.show', $familyId) }}" class="font-black text-slate-900 uppercase tracking-tight group-hover:text-emerald-700 hover:underline transition text-sm">
                                                    {{ $familyDisplayName }}
                                                </a>
                                                @if ($family->is_demo ?? false)
                                                    <span class="inline-flex items-center rounded-md bg-amber-100 border border-amber-200 px-1.5 py-0.5 text-[10px] font-black uppercase text-amber-800">
                                                        DEMO
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5 truncate">
                                                <span class="font-bold text-slate-600 truncate max-w-[140px]">{{ $family->name }}</span>
                                                <span>·</span>
                                                <span class="text-slate-400 truncate">{{ $family->email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- 3. Child / Children (Total Count only) -->
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 border border-indigo-200/80 px-3 py-1.5 text-xs font-bold text-indigo-900 shadow-2xs">
                                        <i data-lucide="users" class="h-3.5 w-3.5 text-indigo-600"></i>
                                        <span>{{ $totalChildrenCount }} {{ \Illuminate\Support\Str::plural('Child', $totalChildrenCount) }}</span>
                                    </span>
                                </td>

                                <!-- 4. Account Status -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if(! $hasAccounts)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Draft Application
                                        </span>
                                    @elseif($openCount > 0)
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

                                <!-- 5. Consolidated Remaining -->
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    @if(! $hasAccounts)
                                        <span class="text-xs font-bold text-slate-400 italic">No Assessment</span>
                                    @else
                                        <span class="text-base font-black tracking-tight {{ $totalRemaining > 0.01 ? 'text-slate-900' : 'text-emerald-700' }}">
                                            ₱{{ number_format($totalRemaining, 2) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- 6. Actions -->
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <a href="{{ route('admin.finance.families.show', $familyId) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-800 transition">
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
