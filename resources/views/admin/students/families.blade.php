<x-admin-layout
    title="Family Accounts"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Family Accounts', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner Header -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-md">
            <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Student Workspace
                </span>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Family Accounts</h1>
                <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                    Monitor aggregated billing balances and sections for grouped family structures linked to student enrollees.
                </p>
            </div>
        </div>

        <!-- Search Form -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.students.families') }}" class="relative w-full max-w-md" id="searchForm" onsubmit="showSkeleton()">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by parent name, parent email, child name..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                />
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </div>
            </form>
            @if(request()->filled('search'))
                <a href="{{ route('admin.students.families') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 transition">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear Search
                </a>
            @endif
        </div>

        <!-- Error Alert -->
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm flex items-start gap-3">
                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-rose-600 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm">Failed to load data</h4>
                    <p class="text-xs text-rose-700 mt-0.5">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <!-- Loading Skeleton (hidden by default) -->
        <div id="loadingSkeleton" class="hidden grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @for ($i = 0; $i < 6; $i++)
                <div class="animate-pulse rounded-3xl border border-slate-200 bg-white p-6 space-y-4">
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <div class="h-4 w-32 rounded bg-slate-200"></div>
                            <div class="h-3 w-40 rounded bg-slate-100"></div>
                        </div>
                        <div class="h-10 w-10 rounded-2xl bg-slate-100"></div>
                    </div>
                    <div class="h-px bg-slate-100"></div>
                    <div class="space-y-2">
                        <div class="h-3 w-20 rounded bg-slate-100"></div>
                        <div class="flex gap-2">
                            <div class="h-6 w-16 rounded-md bg-slate-100"></div>
                            <div class="h-6 w-16 rounded-md bg-slate-100"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-2">
                        <div class="h-10 rounded-xl bg-slate-50"></div>
                        <div class="h-10 rounded-xl bg-slate-50"></div>
                        <div class="h-10 rounded-xl bg-slate-50"></div>
                    </div>
                </div>
            @endfor
        </div>

        <!-- Families Card Grid -->
        <div id="familiesContainer" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($families as $family)
                @php
                    $children = $family->students;
                    $totalTuition = $children->sum(fn($st) => $st->account->tuition_fee ?? 0);
                    $totalPaid = $children->sum(fn($st) => $st->account->amount_paid ?? 0);
                    $remainingBalance = $children->sum(fn($st) => $st->account->remaining_balance ?? 0);
                    
                    $hasUnpaid = $children->contains(fn($st) => ($st->account->remaining_balance ?? 0) > 0);
                    $hasPartial = $children->contains(fn($st) => ($st->account->amount_paid ?? 0) > 0 && ($st->account->remaining_balance ?? 0) > 0);
                    $isFullyPaid = !$hasUnpaid;

                    $statusClass = $isFullyPaid ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($hasPartial ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-rose-50 text-rose-700 ring-rose-100');
                    $statusLabel = $isFullyPaid ? 'Fully Paid' : ($hasPartial ? 'Partial' : 'Unpaid');
                @endphp
                <div class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:shadow-md border-t-4 border-t-emerald-600">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-slate-900 group-hover:text-emerald-700 transition truncate uppercase">
                                {{ $family->name }}
                            </h3>
                            <p class="text-xs font-medium text-slate-400 mt-1 truncate">
                                {{ $family->email }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-2.5 text-emerald-600 ring-1 ring-emerald-100 shrink-0">
                            <i data-lucide="users" class="h-5 w-5"></i>
                        </div>
                    </div>

                    <div class="my-4 h-px bg-slate-100"></div>

                    <!-- Linked Students -->
                    <div class="space-y-2">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Linked Students ({{ $children->count() }})</span>
                        <div class="flex flex-wrap gap-2">
                            @foreach($children as $child)
                                @php
                                    $studentName = $child->applicant ? strtoupper($child->applicant->first_name . ' ' . $child->applicant->last_name) : 'Student';
                                    $secName = $child->studentSection->section->name ?? 'No Section';
                                @endphp
                                <a href="{{ route('admin.students.show', $child) }}" class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-200/60 hover:bg-slate-100 transition">
                                    <span>{{ $studentName }}</span>
                                    <span class="text-[10px] text-slate-400 font-medium">({{ $child->grade_level }} · {{ $secName }})</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="my-4 h-px bg-slate-100"></div>

                    <!-- Ledger Summary -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Balance</span>
                            <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-extrabold ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 bg-slate-50/50 rounded-2xl p-3 text-center border border-slate-100">
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Gross</span>
                                <p class="text-xs font-extrabold text-slate-700 mt-1">₱{{ number_format($totalTuition, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Paid</span>
                                <p class="text-xs font-extrabold text-emerald-600 mt-1">₱{{ number_format($totalPaid, 2) }}</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Balance</span>
                                <p class="text-xs font-extrabold text-rose-600 mt-1">₱{{ number_format($remainingBalance, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="col-span-full py-16 text-center">
                    <div class="flex flex-col items-center justify-center space-y-4">
                        <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                            <i data-lucide="users-2" class="h-10 w-10"></i>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-base font-bold text-slate-800">No family accounts found</h3>
                            <p class="text-sm text-slate-500 max-w-sm mx-auto">We couldn't find any family records registered in the system. Check your search input or filters.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($families->hasPages())
            <div class="mt-8">
                {{ $families->links() }}
            </div>
        @endif
    </div>

    <script>
        function showSkeleton() {
            document.getElementById('familiesContainer').classList.add('hidden');
            document.getElementById('loadingSkeleton').classList.remove('hidden');
        }
    </script>
</x-admin-layout>
