<x-admin-layout title="SOA Review">
    @php
        $sort = request('sort', 'name');
        $dir  = request('dir', 'asc');
        $sortLink = fn($col) => route('admin.soa.index', array_merge(request()->except(['page', 'sort', 'dir']), ['sort' => $col, 'dir' => $sort === $col && $dir === 'asc' ? 'desc' : 'asc']));
        $sortIcon = fn($col) => $sort === $col ? ($dir === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevrons-up-down';
        $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:ring-4 focus:ring-amber-100';
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-amber-100 bg-gradient-to-br from-slate-950 via-amber-800 to-orange-700 p-6 text-white shadow-xl shadow-amber-900/10">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-amber-50">Finance Management</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Statement of Accounts</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-amber-50/90">Individual student accounts with tuition balances, payments, and printable SOA.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-2xl bg-white/20 px-5 py-3 text-sm font-black text-white backdrop-blur-sm transition hover:bg-white/30 no-print">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print PDF
                    </button>
                    <a href="{{ route('admin.finance.dashboard') }}" class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-amber-700 shadow-lg shadow-amber-900/20 transition hover:bg-amber-50 no-print">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        Finance Dashboard
                    </a>
                </div>
            </div>
        </section>

        <x-card title="Student Accounts" subtitle="Each row is an individual student account. Click name to open SOA. Use column headers to sort.">
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3">
                <label class="relative col-span-5">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search student name or number..." class="{{ $inputClass }} w-full pl-9">
                </label>
                <select name="grade" class="{{ $inputClass }} col-span-3 w-full" onchange="this.form.submit()">
                    <option value="">All Grades</option>
                    @foreach ($gradeLevels as $g)
                        <option value="{{ $g['value'] }}" @selected(request('grade') === $g['value'])>{{ $g['label'] }}</option>
                    @endforeach
                </select>
                <select name="status" class="{{ $inputClass }} col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                    <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                </select>
                <button class="col-span-2 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 no-print">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="amis-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="px-3 py-3">
                                <a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-1 text-xs font-extrabold uppercase tracking-wider text-slate-500 hover:text-slate-700 no-underline">
                                    Student Name <i data-lucide="{{ $sortIcon('name') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="px-3 py-3 w-32">
                                <a href="{{ $sortLink('grade') }}" class="inline-flex items-center gap-1 text-xs font-extrabold uppercase tracking-wider text-slate-500 hover:text-slate-700 no-underline">
                                    Grade <i data-lucide="{{ $sortIcon('grade') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="px-3 py-3 w-36 text-right">
                                <a href="{{ $sortLink('tuition') }}" class="inline-flex items-center gap-1 text-xs font-extrabold uppercase tracking-wider text-slate-500 hover:text-slate-700 no-underline">
                                    Tuition <i data-lucide="{{ $sortIcon('tuition') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="px-3 py-3 w-36 text-right">
                                <a href="{{ $sortLink('paid') }}" class="inline-flex items-center gap-1 text-xs font-extrabold uppercase tracking-wider text-slate-500 hover:text-slate-700 no-underline">
                                    Paid <i data-lucide="{{ $sortIcon('paid') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="px-3 py-3 w-36 text-right">
                                <a href="{{ $sortLink('balance') }}" class="inline-flex items-center gap-1 text-xs font-extrabold uppercase tracking-wider text-slate-500 hover:text-slate-700 no-underline">
                                    Balance <i data-lucide="{{ $sortIcon('balance') }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                            <th class="px-3 py-3 w-28 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accounts as $account)
                            @php
                                $studentName = $account->student?->applicant?->full_name ?: ($account->applicant?->full_name ?: 'Unknown');
                                $studentNumber = $account->student?->student_number ?: '-';
                                $grade = $account->grade_level ?: '-';
                                $acctStatus = strtolower((string) ($account->status ?? 'unpaid'));
                                $statusColor = match ($acctStatus) {
                                    'paid' => 'green',
                                    'partial' => 'yellow',
                                    default => 'red',
                                };
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-3 py-4">
                                    <a href="{{ route('admin.soa.show', $account) }}" class="font-extrabold text-slate-950 uppercase tracking-tight hover:text-amber-700 transition" style="font-size: 15px;">
                                        {{ $studentName }}
                                    </a>
                                    <div class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">#{{ $studentNumber }}</div>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-extrabold text-slate-700">{{ $grade }}</span>
                                </td>
                                <td class="px-3 py-4 text-right font-semibold text-slate-700" style="font-size: 14px;">
                                    PHP {{ number_format((float) ($account->total_balance ?? 0), 2) }}
                                </td>
                                <td class="px-3 py-4 text-right font-semibold text-emerald-700" style="font-size: 14px;">
                                    PHP {{ number_format((float) ($account->amount_paid ?? 0), 2) }}
                                </td>
                                <td class="px-3 py-4 text-right font-black text-amber-700" style="font-size: 15px;">
                                    PHP {{ number_format((float) ($account->remaining_balance ?? 0), 2) }}
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <x-badge :color="$statusColor">{{ Str::upper($acctStatus) }}</x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-10 text-center text-sm font-bold text-slate-400">
                                    No Statement of Accounts found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $accounts->links() }}</div>
        </x-card>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .amis-table, .amis-table * { visibility: visible; }
            .amis-table { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
            nav, aside, .breadcrumbs, footer, button, form { display: none !important; }
            @page { size: landscape; margin: 10mm; }
            .amis-table th { background: #f8fafc !important; color: #334155 !important; -webkit-print-color-adjust: exact; }
            .amis-table td, .amis-table th { font-size: 12px !important; padding: 6px 8px !important; }
        }
    </style>
</x-admin-layout>
