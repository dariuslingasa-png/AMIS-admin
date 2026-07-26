<x-admin-layout title="Halaqah Parents Registrations">
    <style>
        .premium-table-wrap {
            max-height: none !important;
            overflow: visible !important;
        }
        @media print {
            body {
                background: white !important;
                color: black !important;
                font-family: 'Inter', sans-serif !important;
            }
            .admin-sidebar, .module-dashboard-link, .print-hide, aside, nav, header, footer {
                display: none !important;
            }
            .admin-main, main, .analytics-page {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }
            .premium-table-wrap {
                border: none !important;
                box-shadow: none !important;
                overflow: visible !important;
            }
            .premium-table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            .premium-table th, .premium-table td {
                border: 1px solid #cbd5e1 !important;
                padding: 8px 10px !important;
                text-align: left !important;
                font-size: 11px !important;
                color: #0f172a !important;
            }
            .premium-table th {
                background-color: #f1f5f9 !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
            }
        }
    </style>

    <!-- Print Only Header -->
    <div class="hidden print:block mb-6 border-b-2 border-slate-900 pb-4">
        <h1 class="text-xl font-black uppercase text-slate-900 tracking-wide text-center">Al Munawwara Islamic School</h1>
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest text-center mt-1">Halaqah Parents Registrations Report</p>
        <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold mt-4">
            <span>Generated on: {{ now()->timezone('Asia/Manila')->format('M d, Y h:i A') }}</span>
            <span>Total Records: {{ method_exists($registrations, 'total') ? $registrations->total() : $registrations->count() }}</span>
        </div>
    </div>

    <!-- Header Banner -->
    <div class="relative overflow-hidden p-6 md:p-8 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-2xl border border-emerald-700/30 shadow-sm text-white print-hide">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-teal-500/10 blur-3xl"></div>
        
        <div class="relative z-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-emerald-500/20 text-emerald-300 rounded-full border border-emerald-500/30 backdrop-blur-xs mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Parent Program Registrations
            </span>
            <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white font-outfit">Halaqah Parents Registrations</h1>
            <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                Manage parent and guardian registration submissions for the AMIS Halaqah Parents Islamic education & family guidance program.
            </p>
        </div>
    </div>

    <!-- Feedback alerts -->
    @if (session('status'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-extrabold text-emerald-800 print-hide">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-extrabold text-rose-800 print-hide">
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid gap-4 mt-6 print-hide" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Parents</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $totalCount }}</span>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">New</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $newCount }}</span>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 rounded-xl">
                <i data-lucide="user-plus" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Approved</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $approvedCount }}</span>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/30 text-indigo-650 dark:text-indigo-400 rounded-xl">
                <i data-lucide="user-check" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Beginner</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $cannotReadCount }}</span>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-450 rounded-xl">
                <i data-lucide="book-open" class="w-6 h-6"></i>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Advance</span>
                <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $canReadCount }}</span>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-xl">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Toolbar Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 p-5 shadow-sm mt-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 print-hide">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 w-full lg:w-auto">
            <form method="GET" action="{{ route('admin.registrations.halaqah-parents') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-400"></i>
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search name, email, mobile, FB account..." class="table-control pl-10 w-full h-10 rounded-xl border border-slate-200 focus:border-emerald-500 outline-none transition text-xs font-semibold bg-slate-50/50">
                </div>
                <select name="status" class="table-control h-10 rounded-xl border border-slate-200 focus:border-emerald-500 outline-none transition text-xs font-semibold px-3 bg-white cursor-pointer">
                    <option value="">All Status</option>
                    <option value="new" {{ $status === 'new' ? 'selected' : '' }}>New</option>
                    <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                </select>
                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-white hover:bg-slate-50 border border-slate-200 active:scale-95 text-slate-700 font-extrabold text-xs px-5 py-2.5 rounded-xl transition shadow-xs cursor-pointer h-10">
                        Filter
                    </button>
                    @if($search || $status || request('level'))
                        <a href="{{ route('admin.registrations.halaqah-parents') }}" class="text-xs text-slate-500 hover:text-slate-850 font-bold whitespace-nowrap px-2">Clear</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="flex items-center gap-3 self-end lg:self-auto">
            <a href="{{ route('admin.registrations.halaqah-parents', ['print' => true, 'search' => $search]) }}" target="_blank" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer h-10 border border-slate-200/50">
                <i data-lucide="printer" class="w-4 h-4 text-slate-600"></i>
                Print List
            </a>
        </div>
    </div>

    <!-- Data List -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm mt-6 overflow-hidden">
        <div class="premium-table-wrap" style="overflow-x: visible;">
            <table class="premium-table w-full" style="width: 100%; table-layout: fixed;">
                <colgroup>
                    <col style="width: 13%;">
                    <col style="width: 25%;">
                    <col style="width: 22%;">
                    <col style="width: 20%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="px-5 py-3.5">Registration Date</th>
                        <th class="px-5 py-3.5">Parent Details</th>
                        <th class="px-5 py-3.5">Registration Details</th>
                        <th class="px-5 py-3.5">FB Account & Contact</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right print-hide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $reg)
                        @php
                            $lines = explode("\n", $reg->message);
                            $details = [];
                            foreach ($lines as $line) {
                                if (str_contains($line, ':')) {
                                    [$k, $v] = explode(':', $line, 2);
                                    $details[trim($k)] = trim($v);
                                }
                            }
                            $age = $details['Age'] ?? '';
                            $sex = $details['Sex'] ?? ($details['Gender'] ?? '');
                            $statusVal = $details['Civil Status'] ?? '';
                            $level = $details['Learning Level'] ?? '';
                            $fbAccount = $details['FB Account Link'] ?? '';
                            $mobile = $details['Mobile Number'] ?? $reg->phone;
                            $email = $details['Email'] ?? $reg->email;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <!-- Registration Date -->
                            <td class="px-5 py-4 text-xs font-semibold text-slate-400">
                                {{ date('M d, Y', strtotime($reg->created_at)) }}
                                <div class="text-[10px] text-slate-355 font-light mt-0.5">{{ date('h:i A', strtotime($reg->created_at)) }}</div>
                            </td>

                            <!-- Parent Details -->
                            <td class="px-5 py-4">
                                <span class="font-extrabold text-slate-900 text-sm block uppercase tracking-wide">
                                    {{ $reg->name }}
                                </span>
                                <div class="flex flex-col gap-0.5 mt-1">
                                    <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5 break-all">
                                        <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400 print-hide"></i>
                                        {{ $email }}
                                    </span>
                                    <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                        <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400 print-hide"></i>
                                        {{ $mobile }}
                                    </span>
                                </div>
                            </td>

                            <!-- Registration Details -->
                            <td class="px-5 py-4">
                                <div class="text-xs text-slate-700 font-semibold space-y-1">
                                    @if($age || $sex)
                                        <div><span class="text-slate-400 uppercase text-[10px] font-bold">Age/Gender:</span> <strong class="text-slate-800">{{ $age ?: 'N/A' }} / {{ $sex ?: 'N/A' }}</strong></div>
                                    @endif
                                    @if($statusVal)
                                        <div><span class="text-slate-400 uppercase text-[10px] font-bold">Civil Status:</span> <strong class="text-slate-800">{{ $statusVal }}</strong></div>
                                    @endif
                                    @if($level)
                                        <div>
                                            <span class="text-slate-400 uppercase text-[10px] font-bold">Level:</span> 
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ str_contains(strtoupper($level), 'ADVANCE') ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : 'bg-amber-50 text-amber-700 border border-amber-100' }}">
                                                {{ $level }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- FB Account & Contact -->
                            <td class="px-5 py-4">
                                @if($fbAccount)
                                    @php
                                        $fbUrl = $fbAccount;
                                        if (!str_starts_with($fbUrl, 'http://') && !str_starts_with($fbUrl, 'https://')) {
                                            $fbUrl = 'https://' . $fbUrl;
                                        }
                                    @endphp
                                    <a href="{{ $fbUrl }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition shadow-3xs">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        FB Profile
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400 italic">No FB link</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                @if($reg->status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Approved
                                    </span>
                                    @if($reg->responded_at)
                                        <div class="text-[9px] text-slate-400 font-medium mt-0.5">On {{ date('M d, Y', strtotime($reg->responded_at)) }}</div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        New Submission
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right print-hide">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Toggle Status Button -->
                                    <form method="POST" action="{{ route('admin.registrations.halaqah.toggle', ['id' => $reg->id, 'source' => $reg->source]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="tab" value="{{ $tab }}">
                                        @if($reg->status === 'approved')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-660 font-bold text-xs rounded-xl transition cursor-pointer" title="Undo Approval">
                                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                                Undo
                                            </button>
                                        @else
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl transition shadow-3xs cursor-pointer" title="Approve Registration">
                                                <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                                                Approve
                                            </button>
                                        @endif
                                    </form>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ route('admin.registrations.halaqah.destroy', ['id' => $reg->id, 'source' => $reg->source]) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this registration?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="tab" value="{{ $tab }}">
                                        <button type="submit" class="inline-flex items-center justify-center p-2 text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-100 rounded-xl transition cursor-pointer" title="Delete Registration">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <i data-lucide="inbox" class="w-8 h-8 text-slate-350"></i>
                                    <span class="font-extrabold text-sm text-slate-500">No parent registrations found matching criteria.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Links -->
        @if($registrations->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50 print-hide">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
