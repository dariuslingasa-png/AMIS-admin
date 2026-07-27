<x-admin-layout title="Halaqah Online Registrations">
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
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest text-center mt-1">Halaqah Online Registrations Report</p>
        <div class="flex items-center justify-between text-[10px] text-slate-400 font-semibold mt-4">
            <span>Generated on: {{ now()->timezone('Asia/Manila')->format('M d, Y h:i A') }}</span>
            <span>Total Records: {{ method_exists($registrations, 'total') ? $registrations->total() : $registrations->count() }}</span>
        </div>
    </div>

    <!-- Header Banner -->
    <div class="relative overflow-hidden p-6 md:p-8 bg-gradient-to-r from-emerald-800 to-teal-950 rounded-2xl border border-emerald-700/30 shadow-sm text-white print-hide">
        <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-emerald-500/10 blur-3xl"></div>
        <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-teal-500/10 blur-3xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-emerald-500/20 text-emerald-300 rounded-full border border-emerald-500/30 backdrop-blur-xs mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Public registrations
                </span>
                <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight text-white font-outfit">Halaqah Online Registrations</h1>
                <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                    Manage public inquiries and student/parent registration submissions for the Halaqah Online Islamic study program.
                </p>
            </div>

            <!-- Student | Parents Program Filter Pills -->
            <div class="flex items-center gap-1 bg-emerald-950/60 p-1.5 rounded-2xl border border-emerald-500/30 backdrop-blur-md shrink-0">
                <a href="{{ route('admin.registrations.halaqah') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-black rounded-xl transition bg-emerald-500 text-slate-950 shadow-md">
                    <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                    <span>Students</span>
                </a>
                <a href="{{ route('admin.registrations.halaqah-parents') }}" class="flex items-center gap-2 px-4 py-2 text-xs font-bold rounded-xl transition text-emerald-200 hover:text-white hover:bg-emerald-800/50">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>Parents</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex gap-4 border-b border-slate-200 dark:border-gray-700 mt-6 pb-px print-hide">
        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'submissions']) }}" 
           class="pb-3 text-xs {{ $tab === 'submissions' ? 'font-extrabold text-emerald-600 dark:text-emerald-450 border-b-2 border-emerald-500' : 'font-bold text-slate-500 hover:text-slate-850' }} transition-all relative">
            Registration Submissions
        </a>
        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'students']) }}" 
           class="pb-3 text-xs {{ $tab === 'students' ? 'font-extrabold text-emerald-600 dark:text-emerald-450 border-b-2 border-emerald-500' : 'font-bold text-slate-500 hover:text-slate-850' }} transition-all relative">
            Official Student List (2026 - 2027)
        </a>
        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'teams']) }}" 
           class="pb-3 text-xs {{ $tab === 'teams' ? 'font-extrabold text-emerald-600 dark:text-emerald-450 border-b-2 border-emerald-500' : 'font-bold text-slate-500 hover:text-slate-850' }} transition-all relative">
            Microsoft Teams Rosters
        </a>
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

    @if($tab === 'teams')
        @if($team)
            <!-- Team details view -->
            <div class="mt-6">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'teams']) }}" class="mb-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline">← Official Rosters List</a>
                        <h2 class="text-xl font-extrabold text-slate-950 font-outfit">{{ $team->display_name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $team->description ?: 'No Microsoft Team description.' }}</p>
                    </div>
                </div>

                <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
                    @foreach([
                        'Microsoft Team ID'=>$team->microsoft_team_id,
                        'Visibility'=>ucfirst($team->visibility ?? 'Unknown'),
                        'Members / Owners'=>$team->member_count.' / '.$team->owner_count,
                        'Last synchronized'=>$team->last_synced_at?->timezone('Asia/Manila')->format('M j, Y g:i A') ?? 'Never',
                        'Mapping status'=>ucfirst($team->mapping?->mapping_status ?? 'pending')
                    ] as $label=>$value)
                        <div class="rounded-2xl border border-slate-200/60 bg-white p-4 shadow-3xs">
                            <div class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $label }}</div>
                            <div class="mt-1 break-all text-sm font-extrabold text-slate-900">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm overflow-hidden mt-6">
                    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Team Roster</h3>
                        <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                            <input type="hidden" name="tab" value="teams">
                            <input type="hidden" name="team_id" value="{{ $team->id }}">
                            <input name="member_search" value="{{ request('member_search') }}" placeholder="Search members..." class="table-control w-full sm:w-64 h-10 rounded-xl border border-slate-200 focus:border-emerald-500 outline-none transition text-xs font-medium px-4">
                            <button class="bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer h-10">Search</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="premium-table w-full">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">AMIS ID</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Student / Account Name</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Microsoft Email</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Account Type</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Teams Role</th>
                                    <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Match Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($memberships as $member)
                                    <tr class="{{ $member->is_active ? '' : 'bg-slate-50 opacity-70' }} hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-3.5 font-mono text-xs">{{ $member->student?->student_number ?? '—' }}</td>
                                        <td class="px-5 py-3.5 font-bold text-xs">
                                            {{ $member->display_name }}
                                            @unless($member->is_active)
                                                <span class="ml-1 rounded bg-slate-200 px-1.5 py-0.5 text-[9px] uppercase">Inactive</span>
                                            @endunless
                                        </td>
                                        <td class="px-5 py-3.5 text-xs">{{ $member->email ?? $member->user_principal_name ?? '—' }}</td>
                                        <td class="px-5 py-3.5 capitalize text-xs">{{ $member->account_type }}</td>
                                        <td class="px-5 py-3.5">
                                            <span class="rounded-full {{ $member->team_role==='owner'?'bg-violet-100 text-violet-800':'bg-blue-50 text-blue-800' }} px-2.5 py-1 text-[10px] font-black uppercase">
                                                {{ $member->team_role }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase">
                                                {{ str_replace('_',' ',$member->match_status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-12 text-center text-slate-400">No roster members match search criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($memberships->hasPages())
                        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                            {{ $memberships->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Teams directory list -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm mt-6 overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="text-sm font-black uppercase tracking-wider text-slate-400">Halaqah Teams Directory</h3>
                    <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="hidden" name="tab" value="teams">
                        <input name="team_search" value="{{ request('team_search') }}" placeholder="Search team name..." class="table-control w-full sm:w-64 h-10 rounded-xl border border-slate-200 focus:border-emerald-500 outline-none transition text-xs font-medium px-4">
                        <button class="bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer h-10">Search</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="premium-table w-full">
                        <thead>
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Team Name</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Microsoft Team ID</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Visibility</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Members</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Owners</th>
                                <th class="px-5 py-3.5 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Last Synced</th>
                                <th class="px-5 py-3.5 text-right text-xs font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($teams as $team)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-5 py-3.5 font-bold text-xs">
                                        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'teams', 'team_id' => $team->id]) }}" class="text-emerald-700 hover:underline">
                                            {{ $team->display_name }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-xs text-slate-400">{{ $team->microsoft_team_id }}</td>
                                    <td class="px-5 py-3.5 capitalize text-xs">{{ $team->visibility ?? '—' }}</td>
                                    <td class="px-5 py-3.5 font-bold text-xs">{{ $team->member_count }}</td>
                                    <td class="px-5 py-3.5 font-bold text-xs">{{ $team->owner_count }}</td>
                                    <td class="px-5 py-3.5 text-xs text-slate-500">{{ $team->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                                    <td class="px-5 py-3.5 text-right">
                                        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'teams', 'team_id' => $team->id]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                                            View Roster
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-slate-400">No synchronized Teams match search filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($teams->hasPages())
                    <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
                        {{ $teams->links() }}
                    </div>
                @endif
            </div>
        @endif
    @else
        @if($tab === 'students')
            <div class="mt-6 bg-emerald-50/50 border border-emerald-100 rounded-2xl p-4 flex items-center justify-between print-hide">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-emerald-600 text-white rounded-xl">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Official Student List (2026 - 2027)</h4>
                        <p class="text-xs text-slate-500 mt-0.5">This list compiles all approved public inquiries and online registration submissions.</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-black text-emerald-700 font-outfit">{{ $approvedCount }}</span>
                    <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider mt-0.5">Approved Students</span>
                </div>
            </div>
        @else
            <!-- Statistics Cards -->
            <div class="grid gap-4 mt-6 print-hide" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total</span>
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
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Cannot Read</span>
                        <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $cannotReadCount }}</span>
                    </div>
                    <div class="p-3 bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-450 rounded-xl">
                        <i data-lucide="book-x" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Can Read</span>
                        <span class="text-2xl font-black text-slate-900 dark:text-white font-outfit mt-1 block">{{ $canReadCount }}</span>
                    </div>
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 rounded-xl">
                        <i data-lucide="book-open" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        @endif

        <!-- Toolbar Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-slate-200/70 dark:border-gray-700/50 p-5 shadow-sm mt-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 print-hide">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 w-full lg:w-auto">
                <!-- Program Category Filter: Student | Parents -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl shrink-0">
                    <a href="{{ route('admin.registrations.halaqah', ['tab' => $tab]) }}" class="px-3.5 py-2 text-xs font-extrabold rounded-lg transition bg-white dark:bg-gray-700 text-emerald-700 dark:text-emerald-400 shadow-xs flex items-center gap-1.5">
                        <i data-lucide="graduation-cap" class="w-3.5 h-3.5"></i>
                        <span>Students</span>
                    </a>
                    <a href="{{ route('admin.registrations.halaqah-parents') }}" class="px-3.5 py-2 text-xs font-extrabold rounded-lg transition text-slate-500 hover:text-slate-900 dark:hover:text-slate-300 flex items-center gap-1.5">
                        <i data-lucide="users" class="w-3.5 h-3.5"></i>
                        <span>Parents</span>
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.registrations.halaqah') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    @if(request('level'))
                        <input type="hidden" name="level" value="{{ request('level') }}">
                    @endif
                    <div class="relative w-full sm:w-72">
                        <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3 h-4 w-4 text-slate-400"></i>
                        <input type="search" name="search" value="{{ $search }}" placeholder="Search name, email, phone..." class="table-control pl-10 w-full h-10 rounded-xl border border-slate-200 focus:border-emerald-500 outline-none transition text-xs font-semibold bg-slate-50/50">
                    </div>
                    @if($tab === 'submissions')
                        <select name="status" class="table-control h-10 rounded-xl border border-slate-200 focus:border-emerald-500 outline-none transition text-xs font-semibold px-3 bg-white cursor-pointer">
                            <option value="">All Status</option>
                            <option value="new" {{ $status === 'new' ? 'selected' : '' }}>New</option>
                            <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
                        </select>
                    @endif
                    <div class="flex items-center gap-2">
                        <!-- No color filter button -->
                        <button type="submit" class="bg-white hover:bg-slate-50 border border-slate-200 active:scale-95 text-slate-700 font-extrabold text-xs px-5 py-2.5 rounded-xl transition shadow-xs cursor-pointer h-10">
                            Filter
                        </button>
                        @if($search || ($tab === 'submissions' && $status) || ($tab === 'students' && request('level') && request('level') !== 'all'))
                            <a href="{{ route('admin.registrations.halaqah', ['tab' => $tab]) }}" class="text-xs text-slate-500 hover:text-slate-850 font-bold whitespace-nowrap px-2">Clear</a>
                        @endif
                    </div>
                </form>
                
                @if($tab === 'students')
                    <!-- Beginner and Advanced Buttons -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl shrink-0">
                        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'students', 'level' => 'all', 'search' => $search]) }}" class="px-3.5 py-1.5 text-xs font-extrabold rounded-lg transition {{ !request('level') || request('level') === 'all' ? 'bg-white dark:bg-gray-700 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-slate-300' }}">
                            All
                        </a>
                        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'students', 'level' => 'beginner', 'search' => $search]) }}" class="px-3.5 py-1.5 text-xs font-extrabold rounded-lg transition {{ request('level') === 'beginner' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-500 hover:text-amber-600' }}">
                            Beginner
                        </a>
                        <a href="{{ route('admin.registrations.halaqah', ['tab' => 'students', 'level' => 'advanced', 'search' => $search]) }}" class="px-3.5 py-1.5 text-xs font-extrabold rounded-lg transition {{ request('level') === 'advanced' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-500 hover:text-indigo-600' }}">
                            Advanced
                        </a>
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-3 self-end lg:self-auto">
                <a href="{{ route('admin.registrations.halaqah', ['print' => true, 'tab' => $tab, 'search' => $search, 'level' => request('level')]) }}" target="_blank" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold text-xs px-4 py-2.5 rounded-xl transition cursor-pointer h-10 border border-slate-200/50">
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
                        @if($tab === 'students')
                            <col style="width: 5%;">
                            <col style="width: 35%;">
                            <col style="width: 15%;">
                            <col style="width: 20%;">
                            <col style="width: 25%;">
                        @else
                            <col style="width: 13%;">
                            <col style="width: 25%;">
                            <col style="width: 20%;">
                            <col style="width: 22%;">
                            <col style="width: 10%;">
                            <col style="width: 10%;">
                        @endif
                    </colgroup>
                    <thead>
                        <tr>
                            @if($tab === 'students')
                                <th class="px-5 py-3.5 text-center">#</th>
                                <th class="px-5 py-3.5">Full Name</th>
                                <th class="px-5 py-3.5">Grade</th>
                                <th class="px-5 py-3.5">Level</th>
                                <th class="px-5 py-3.5">Microsoft Team Accounts</th>
                            @else
                                <th class="px-5 py-3.5">Registration Date</th>
                                <th class="px-5 py-3.5">Applicant Details</th>
                                <th class="px-5 py-3.5">Halaqah Program Details</th>
                                <th class="px-5 py-3.5">Message / Goals</th>
                                <th class="px-5 py-3.5">Status</th>
                                <th class="px-5 py-3.5 text-right print-hide">Actions</th>
                            @endif
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
                                $address = $details['Address'] ?? '';
                                $msTeams = $details['MS Teams Account'] ?? '';
                                $level = $details['Learning Level'] ?? '';
                                $gradeLevel = $details['Grade Level'] ?? '';
                                
                                $msgParts = explode('--- Halaqah Registration Details ---', $reg->message);
                                $actualMessage = trim($msgParts[0]);
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                @if($tab === 'students')
                                    <!-- Index # -->
                                    <td class="px-5 py-4 text-xs font-bold text-slate-400 text-center">
                                        {{ $loop->iteration + ($registrations->currentPage() - 1) * $registrations->perPage() }}
                                    </td>

                                    <!-- Full Name -->
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold text-slate-900 text-sm block uppercase tracking-wide">
                                            {{ $reg->name }}
                                        </span>
                                    </td>

                                    <!-- Grade -->
                                    <td class="px-5 py-4">
                                        @if($gradeLevel)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-slate-100 text-slate-700 border border-slate-200">
                                                Grade {{ str_replace('GRADE', '', strtoupper($gradeLevel)) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <!-- Level -->
                                    <td class="px-5 py-4">
                                        @if($level)
                                            @php
                                                $isBeginner = str_contains(strtolower($level), 'beginner');
                                                $badgeColor = $isBeginner ? 'amber' : 'indigo';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-{{ $badgeColor }}-50 text-{{ $badgeColor }}-700 border border-{{ $badgeColor }}-100">
                                                {{ $level }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <!-- Microsoft Team Accounts -->
                                    <td class="px-5 py-4">
                                        @if($msTeams)
                                            <code class="px-2 py-1 bg-slate-100 rounded-md text-slate-800 text-[11px] font-semibold tracking-wide break-all">{{ $msTeams }}</code>
                                        @else
                                            <span class="text-xs text-slate-400 font-medium italic">No Teams Account</span>
                                        @endif
                                    </td>
                                @else
                                    <!-- Registration Date -->
                                    <td class="px-5 py-4 text-xs font-semibold text-slate-400">
                                        {{ date('M d, Y', strtotime($reg->created_at)) }}
                                        <div class="text-[10px] text-slate-355 font-light mt-0.5">{{ date('h:i A', strtotime($reg->created_at)) }}</div>
                                    </td>

                                    <!-- Applicant Details -->
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold text-slate-900 text-sm block uppercase tracking-wide">
                                            {{ $reg->name }}
                                        </span>
                                        <div class="flex flex-col gap-0.5 mt-1">
                                            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5 break-all">
                                                <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-400 print-hide"></i>
                                                {{ $reg->email }}
                                            </span>
                                            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                                <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-400 print-hide"></i>
                                                {{ $reg->phone }}
                                            </span>
                                            @if($address)
                                                <span class="text-[10px] text-slate-400 font-semibold uppercase flex items-center gap-1.5 mt-0.5" style="white-space: normal; word-break: break-word;">
                                                    <i data-lucide="map-pin" class="w-3 h-3 text-slate-455 print-hide"></i>
                                                    {{ $address }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Program Details -->
                                    <td class="px-5 py-4">
                                        @if($level)
                                            @php
                                                $isBeginner = str_contains(strtolower($level), 'beginner');
                                                $badgeColor = $isBeginner ? 'amber' : 'indigo';
                                            @endphp
                                            <div class="mb-1.5">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-{{ $badgeColor }}-50 text-{{ $badgeColor }}-700 border border-{{ $badgeColor }}-100">
                                                    {{ $level }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($gradeLevel)
                                            <div class="text-xs text-slate-655 font-semibold flex items-center gap-1.5 mb-1.5">
                                                <span class="text-[10px] font-bold uppercase text-slate-400">Grade:</span>
                                                <span class="text-slate-800 font-extrabold text-[11px] uppercase tracking-wide">{{ $gradeLevel }}</span>
                                            </div>
                                        @endif
                                        @if($msTeams)
                                            <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                                <span class="text-[10px] font-bold uppercase text-slate-400">Teams:</span>
                                                <code class="px-1.5 py-0.5 bg-slate-100 rounded-md text-slate-800 text-[10px] font-semibold tracking-wide">{{ $msTeams }}</code>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Message -->
                                    <td class="px-5 py-4" style="white-space: normal; word-break: break-word;">
                                        <span class="text-xs text-slate-600 font-medium line-clamp-3" title="{{ $actualMessage }}">
                                            {{ $actualMessage ?: '-' }}
                                        </span>
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
                                                @if(request('level'))
                                                    <input type="hidden" name="level" value="{{ request('level') }}">
                                                @endif
                                                @if($reg->status === 'approved')
                                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-660 font-bold text-xs rounded-xl transition cursor-pointer" title="Undo Approval">
                                                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                                        Undo Approval
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
                                                @if(request('level'))
                                                    <input type="hidden" name="level" value="{{ request('level') }}">
                                                @endif
                                                <button type="submit" class="inline-flex items-center justify-center p-2 text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-100 rounded-xl transition cursor-pointer" title="Delete Registration">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $tab === 'students' ? 3 : 6 }}" class="px-5 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-350"></i>
                                        <span class="font-extrabold text-sm text-slate-500">No registrations found matching the filters.</span>
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
    @endif
</x-admin-layout>
