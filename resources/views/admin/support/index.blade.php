@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-rose-400 focus:ring-4 focus:ring-rose-100';
@endphp

<x-admin-layout
    title="Support Center"
    :breadcrumbs="[
        ['label' => 'Support Center', 'href' => route('admin.support.index')],
        ['label' => 'Inquiries List', 'href' => null],
    ]"
>
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <!-- Section Header -->
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-rose-700">Support Center Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Concerns & Inquiries</h1>
                <p class="mt-1 text-sm text-slate-500">View and manage inquiries, account recovery issues, and technical support requests from students and parents.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.support.settings') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                    Settings
                </a>
                <a href="{{ route('admin.dashboard') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-rose-100 bg-rose-50 px-4 text-sm font-bold text-rose-700 transition hover:bg-rose-100">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Dashboard
                </a>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Telemetry Summary Cards -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Inquiries -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Tickets</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-50 text-slate-600">
                            <i data-lucide="message-square" class="h-4 w-4"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($kpis['total']) }}</span>
                    </div>
                    <div class="mt-2 text-xs text-slate-400 font-medium">All submitted inquiries</div>
                </div>

                <!-- Open -->
                <div class="rounded-2xl border border-rose-200 bg-white p-5 shadow-xs transition hover:shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Open Tickets</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                            <i data-lucide="alert-circle" class="h-4 w-4"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-rose-900">{{ number_format($kpis['open']) }}</span>
                    </div>
                    <div class="mt-2 text-xs text-rose-500/80 font-medium">Awaiting administrator response</div>
                </div>

                <!-- In Progress -->
                <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-xs transition hover:shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">In Progress</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-amber-900">{{ number_format($kpis['in_progress']) }}</span>
                    </div>
                    <div class="mt-2 text-xs text-amber-500/80 font-medium">Currently being investigated</div>
                </div>

                <!-- Resolved -->
                <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-xs transition hover:shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Resolved</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <i data-lucide="check-circle" class="h-4 w-4"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-black text-emerald-900">{{ number_format($kpis['resolved']) }}</span>
                    </div>
                    <div class="mt-2 text-xs text-emerald-500/80 font-medium">Successfully completed</div>
                </div>
            </div>

            <!-- Filters form -->
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3" id="filterForm">
                <!-- Search -->
                <label class="relative col-span-12 lg:col-span-3">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search Reference, Name, Email, Msg..." class="{{ $inputClass }} w-full pl-9">
                </label>

                <!-- Status Filter -->
                <select name="status" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach($statusLabels as $key => $lbl)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $lbl }}</option>
                    @endforeach
                </select>

                <!-- Concern Type Filter -->
                <select name="concern_type" class="{{ $inputClass }} col-span-6 lg:col-span-3 w-full" onchange="this.form.submit()">
                    <option value="">All concern types</option>
                    @foreach($concernTypes as $type)
                        <option value="{{ $type }}" @selected(request('concern_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>

                <!-- Grade Filter -->
                <select name="grade" class="{{ $inputClass }} col-span-6 lg:col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All grades</option>
                    @foreach($gradeLevels as $level)
                        <option value="{{ $level }}" @selected(request('grade') === $level)>{{ $level }}</option>
                    @endforeach
                </select>

                <button type="submit" class="col-span-6 lg:col-span-2 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-rose-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800 cursor-pointer">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    Filter / Search
                </button>
            </form>

            <!-- Table -->
            <div class="premium-table-wrap border border-slate-100 rounded-xl overflow-hidden shadow-xs">
                <table class="premium-table w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-3">Reference No</th>
                            <th class="px-4 py-3">Submitter</th>
                            <th class="px-4 py-3">Student Context</th>
                            <th class="px-4 py-3">Type & Subject</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3">Date Submitted</th>
                            <th class="px-4 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-4 font-bold text-slate-800">
                                    <a href="{{ route('admin.support.show', $ticket) }}" class="text-rose-600 hover:text-rose-800 hover:underline">
                                        {{ $ticket->reference_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="block font-bold text-slate-900">{{ $ticket->full_name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $ticket->email }}</span>
                                    @if($ticket->contact_number)
                                        <span class="block text-xs text-slate-400">{{ $ticket->contact_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if($ticket->student_full_name)
                                        <span class="block font-semibold text-slate-800 uppercase tracking-wide text-xs">{{ $ticket->student_full_name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $ticket->grade_level ?? 'No Grade' }}</span>
                                        @if($ticket->amis_id)
                                            <span class="inline-block mt-0.5 rounded-sm bg-slate-100 px-1 py-0.5 text-[10px] font-mono text-slate-600">ID: {{ $ticket->amis_id }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400 italic">None</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 max-w-xs">
                                    <span class="inline-flex items-center rounded-sm bg-slate-150 px-1.5 py-0.5 text-[10px] font-extrabold uppercase text-slate-600 tracking-wider">{{ $ticket->concern_type }}</span>
                                    <p class="mt-1 text-sm font-bold text-slate-800 truncate" title="{{ $ticket->subject }}">{{ $ticket->subject }}</p>
                                    <p class="text-xs text-slate-500 truncate" title="{{ $ticket->description }}">{{ $ticket->description }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @php
                                        $badgeColor = $statusBadges[$ticket->status] ?? 'slate';
                                        $lbl = $statusLabels[$ticket->status] ?? $ticket->status;
                                    @endphp
                                    @if($badgeColor === 'emerald')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            {{ $lbl }}
                                        </span>
                                    @elseif($badgeColor === 'amber')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                            {{ $lbl }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                            {{ $lbl }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-400 font-medium">
                                    {{ $ticket->created_at ? $ticket->created_at->format('M d, Y h:i A') : 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="{{ route('admin.support.show', $ticket) }}" class="inline-flex items-center gap-1 text-sm font-bold text-rose-600 hover:text-rose-800">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                            View
                                        </a>
                                        @if($ticket->status !== 'resolved')
                                            <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="inline" onsubmit="return confirm('Mark ticket {{ $ticket->reference_number }} as Resolved?')">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="resolved">
                                                <button type="submit" class="inline-flex items-center gap-1 text-sm font-bold text-emerald-600 hover:text-emerald-800 cursor-pointer">
                                                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                                                    Resolve
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <i data-lucide="inbox" class="h-10 w-10 text-slate-300 mb-2"></i>
                                        <p class="font-bold text-sm text-slate-700">No tickets found.</p>
                                        <p class="text-xs text-slate-400 mt-1">Try modifying your search or filter settings.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $tickets->links() }}
            </div>
        </div>
    </section>
</x-admin-layout>
