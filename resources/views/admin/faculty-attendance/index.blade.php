<x-admin-layout
    title="Faculty Attendance logs"
    :breadcrumbs="[
        ['label' => 'Faculty Attendance', 'href' => null],
    ]"
>
    <div class="space-y-6" x-data="{ 
        activeTab: '{{ request()->query('tab', $myBiometricId ? 'individual' : 'logs') }}',
        showAddUserForm: false,
        employee_id: '',
        name: '',
        department_id: 0,
        card_number: '',
        privilege: 0,
        password: '',
        status: 0,
        isEditMode: false,
        currentView: 'list', // individual report view type: 'list' or 'calendar'
        attlog_filename: '',
        user_filename: '',
        department_filename: ''
    }">
        <!-- Top Banner -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="clipboard-check" class="h-6 w-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Faculty & Staff Attendance</h1>
                    <p class="text-xs font-semibold text-slate-500 mt-1">
                        Manage biometric machine log imports, view staff report summaries, and link teacher biometric accounts.
                    </p>
                </div>
            </div>

            <!-- Tabs Header Navigation -->
            <div class="flex bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
                <button @click="activeTab = 'logs'; switchTab('logs')" :class="activeTab === 'logs' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-bold rounded-xl transition duration-150 cursor-pointer">
                    Roster Logs
                </button>
                <button @click="activeTab = 'individual'; switchTab('individual')" :class="activeTab === 'individual' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-bold rounded-xl transition duration-150 cursor-pointer">
                    Individual Reports
                </button>
                <button @click="activeTab = 'directory'; switchTab('directory')" :class="activeTab === 'directory' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-bold rounded-xl transition duration-150 cursor-pointer">
                    Biometric Directory
                </button>
                <button @click="activeTab = 'import'; switchTab('import')" :class="activeTab === 'import' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'" class="px-4 py-2 text-xs font-bold rounded-xl transition duration-150 cursor-pointer">
                    Upload & Parse
                </button>
            </div>
        </section>

        <!-- Tab 1: Roster Logs -->
        <div x-show="activeTab === 'logs'" class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                        <i data-lucide="check-square" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <strong class="block text-2xl font-black text-slate-900">{{ number_format($totalLogs) }}</strong>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Biometric Logs</span>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                        <i data-lucide="users" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <strong class="block text-2xl font-black text-slate-900">{{ number_format($totalUsers) }}</strong>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Registered Biometric IDs</span>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex items-center gap-4">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-xl">
                        <i data-lucide="briefcase" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <strong class="block text-2xl font-black text-slate-900">{{ number_format($totalDepts) }}</strong>
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Departments Registered</span>
                    </div>
                </div>
            </div>

            <!-- Filter Roster / Schedule Config Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Roster Logs Table Container -->
                <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Active Biometric Logs (All Faculty)</h3>
                            <p class="text-xs text-slate-500 mt-0.5">List of logs parsed from the uploaded *.dat files sorted chronologically.</p>
                        </div>
                        <form method="GET" action="{{ route('admin.faculty-attendance.index') }}" class="flex items-center gap-2">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Search name or ID..." class="rounded-xl border border-slate-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            <button type="submit" class="rounded-xl bg-slate-900 px-3.5 py-1.5 text-xs font-bold text-white hover:bg-slate-800 transition cursor-pointer">Search</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs align-middle">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th class="px-4 py-3 font-black">Biometric ID</th>
                                    <th class="px-4 py-3 font-black">Staff Name</th>
                                    <th class="px-4 py-3 font-black">Dept</th>
                                    <th class="px-4 py-3 font-black">Date</th>
                                    <th class="px-4 py-3 text-center font-black">Time In</th>
                                    <th class="px-4 py-3 text-center font-black">Time Out</th>
                                    <th class="px-4 py-3 font-black">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($report as $row)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3.5 font-extrabold text-slate-700">#{{ $row['employee_id'] }}</td>
                                        <td class="px-4 py-3.5 font-extrabold text-slate-900 uppercase">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3.5 font-semibold text-slate-500 uppercase">{{ $row['department'] }}</td>
                                        <td class="px-4 py-3.5 font-bold text-slate-800">{{ date('M d, Y', strtotime($row['date'])) }}</td>
                                        <td class="px-4 py-3.5 text-center font-extrabold text-slate-800">{{ $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '—' }}</td>
                                        <td class="px-4 py-3.5 text-center font-extrabold text-slate-800">{{ $row['time_out'] && $row['time_out'] !== '—' ? date('h:i A', strtotime($row['time_out'])) : '—' }}</td>
                                        <td class="px-4 py-3.5">
                                            @if($row['status'] === 'PRESENT')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-150 uppercase tracking-wide">
                                                    PRESENT
                                                </span>
                                            @elseif($row['status'] === 'LATE')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-150 uppercase tracking-wide">
                                                    LATE
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-slate-50 text-slate-700 border border-slate-150 uppercase tracking-wide">
                                                    {{ $row['status'] }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="p-8 text-center text-slate-400 font-bold">
                                            No biometric logs verified. Go to the "Upload & Parse" tab to import your dat files first.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($totalPages > 1)
                        <div class="flex items-center justify-between border-t border-slate-100 pt-4 text-xs font-semibold text-slate-500">
                            <div>Showing page {{ $page }} of {{ $totalPages }}</div>
                            <div class="flex items-center gap-2">
                                @if($page > 1)
                                    <a href="{{ route('admin.faculty-attendance.index', ['page' => $page - 1, 'search' => $search]) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 hover:bg-slate-50 transition active:scale-95 cursor-pointer">Previous</a>
                                @endif
                                @if($page < $totalPages)
                                    <a href="{{ route('admin.faculty-attendance.index', ['page' => $page + 1, 'search' => $search]) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 hover:bg-slate-50 transition active:scale-95 cursor-pointer">Next</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Schedule Policy Config -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4 self-start">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Biometric Office Schedule Policy</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Define expected duty hours to automatically compute lates and work hour logs.</p>
                    </div>

                    <form method="GET" action="{{ route('admin.faculty-attendance.index') }}" class="space-y-4">
                        @if($search)
                            <input type="hidden" name="search" value="{{ $search }}">
                        @endif
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Expected Time In</label>
                            <input type="time" name="time_in" value="{{ $timeIn }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Expected Time Out</label>
                            <input type="time" name="time_out" value="{{ $timeOut }}" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 text-xs font-black shadow-md transition active:scale-[0.98] cursor-pointer">
                            Apply Schedule Policy
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tab 2: Individual Reports -->
        <div x-show="activeTab === 'individual'" class="space-y-6">
            <!-- Scoping & Selector card -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 mb-1.5">Select Faculty Member</h3>
                    <form method="GET" action="{{ route('admin.faculty-attendance.index') }}" class="flex items-center gap-3">
                        <select name="user_id" onchange="this.form.submit()" class="flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            <option value="" disabled @selected(!$selectedUserId)>-- Select teacher user --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" @selected($selectedUserId == $t->id)>
                                    {{ strtoupper($t->name) }} (Biometric ID: {{ $t->biometric_id ?: 'NONE' }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <!-- Link/Unlink Biometric Profile Form -->
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 mb-1.5">Link Biometric Account ID</h3>
                    <form method="POST" action="{{ route('admin.faculty-attendance.link') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                        <select name="biometric_id" required class="flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            <option value="" disabled selected>-- Select biometric profile --</option>
                            @foreach($users as $u)
                                <option value="{{ $u['employee_id'] }}" @selected($myBiometricId == $u['employee_id'])>
                                    {{ $u['name'] }} (ID: {{ $u['employee_id'] }})
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" @disabled(!$selectedUserId) class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 text-xs font-bold shadow-md transition active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer">
                            Link Biometric ID
                        </button>
                    </form>
                </div>
            </div>

            @if($myBiometricId)
                <!-- 15-Day Personal Summary Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs text-center">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Days Present</span>
                        <div class="text-3xl font-black text-slate-900 mt-2">{{ $mySummary['present'] }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs text-center">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Late Minutes</span>
                        <div class="text-3xl font-black text-amber-600 mt-2">{{ $mySummary['late_minutes'] }}m</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs text-center">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Overtime Hours</span>
                        <div class="text-3xl font-black text-emerald-600 mt-2">{{ number_format($mySummary['overtime_minutes'] / 60, 1) }}h</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs text-center">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Hours Worked</span>
                        <div class="text-3xl font-black text-slate-900 mt-2">{{ number_format($mySummary['hours_worked'], 1) }}h</div>
                    </div>
                </div>

                <!-- Report Detail Card -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <!-- Cutoff Pagination -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-emerald-600"></i> Individual Attendance Report
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Logs for <strong>{{ $displayName }}</strong> from <strong>{{ $myStartDate }}</strong> to <strong>{{ $myEndDate }}</strong>.</p>
                        </div>

                        <div class="flex items-center gap-4 flex-wrap">
                            <!-- Paginator buttons -->
                            <div class="inline-flex items-center gap-2 border border-slate-200 rounded-xl p-1 bg-slate-50">
                                <a href="{{ route('admin.faculty-attendance.index', ['my_month' => $prevMonth, 'my_year' => $prevYear, 'my_cutoff' => $prevCutoff, 'biometric_id' => $myBiometricId, 'user_id' => $selectedUserId]) }}" class="text-slate-600 hover:text-slate-900 hover:bg-white w-7 h-7 flex items-center justify-center rounded-lg transition" title="Previous Cutoff">
                                    <i data-lucide="chevrons-left" class="w-4 h-4"></i>
                                </a>
                                <span class="text-xs font-bold text-slate-700 min-w-[140px] text-center">
                                    @php
                                        $monthName = date('F', mktime(0, 0, 0, $myMonth, 1));
                                        $cutoffLabel = $myCutoff === '1-15' ? '1 - 15' : '16 - ' . date('t', strtotime("{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-01"));
                                    @endphp
                                    {{ $monthName }} {{ $cutoffLabel }}, {{ $myYear }}
                                </span>
                                @if($isNextDisabled)
                                    <span class="text-slate-300 cursor-not-allowed w-7 h-7 flex items-center justify-center" title="Next period not completed yet">
                                        <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                                    </span>
                                @else
                                    <a href="{{ route('admin.faculty-attendance.index', ['my_month' => $nextMonth, 'my_year' => $nextYear, 'my_cutoff' => $nextCutoff, 'biometric_id' => $myBiometricId, 'user_id' => $selectedUserId]) }}" class="text-slate-600 hover:text-slate-900 hover:bg-white w-7 h-7 flex items-center justify-center rounded-lg transition" title="Next Cutoff">
                                        <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                                    </a>
                                @endif
                            </div>

                            <!-- List/Calendar View toggle -->
                            <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                                <button type="button" @click="currentView = 'list'" :class="currentView === 'list' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500'" class="px-3 py-1.5 text-xs font-extrabold rounded-lg flex items-center gap-1.5 transition">
                                    <i data-lucide="list" class="w-3.5 h-3.5"></i> List
                                </button>
                                <button type="button" @click="currentView = 'calendar'" :class="currentView === 'calendar' ? 'bg-white text-slate-800 shadow-xs' : 'text-slate-500'" class="px-3 py-1.5 text-xs font-extrabold rounded-lg flex items-center gap-1.5 transition">
                                    <i data-lucide="calendar-days" class="w-3.5 h-3.5"></i> Calendar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Individual List View Container -->
                    <div x-show="currentView === 'list'" class="overflow-x-auto">
                        <table class="w-full text-left text-xs align-middle">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th class="px-4 py-3 font-black">Date</th>
                                    <th class="px-4 py-3 font-black">Time In</th>
                                    <th class="px-4 py-3 font-black">Time Out</th>
                                    <th class="px-4 py-3 font-black">Total Hours</th>
                                    <th class="px-4 py-3 font-black">Status</th>
                                    <th class="px-4 py-3 font-black">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($myLogs as $log)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3.5 font-extrabold text-slate-800">{{ date('D, M d, Y', strtotime($log['date'])) }}</td>
                                        <td class="px-4 py-3.5 font-bold text-slate-800">{{ $log['time_in'] && $log['time_in'] !== '—' ? date('h:i A', strtotime($log['time_in'])) : '—' }}</td>
                                        <td class="px-4 py-3.5 font-bold text-slate-800">{{ $log['time_out'] && $log['time_out'] !== '—' ? date('h:i A', strtotime($log['time_out'])) : '—' }}</td>
                                        <td class="px-4 py-3.5 font-extrabold text-slate-700">{{ $log['total_hours_formatted'] ?? '—' }}</td>
                                        <td class="px-4 py-3.5">
                                            @if($log['status'] === 'PRESENT')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-150 uppercase tracking-wide">
                                                    PRESENT
                                                </span>
                                            @elseif($log['status'] === 'LATE')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-150 uppercase tracking-wide">
                                                    LATE
                                                </span>
                                            @elseif($log['status'] === 'ABSENT')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-150 uppercase tracking-wide">
                                                    ABSENT
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-slate-50 text-slate-700 border border-slate-150 uppercase tracking-wide">
                                                    —
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <div class="flex items-center justify-between gap-4">
                                                <span class="text-slate-500 font-medium">{{ $log['remarks'] }}</span>
                                                <button type="button" onclick="openRemarksModal('{{ $log['date'] }}', '{{ $myBiometricId }}', '{{ addslashes($myRemarks[$log['date']] ?? '') }}')" class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:text-blue-700 bg-blue-50/50 border border-blue-200 px-2.5 py-1 rounded-lg transition active:scale-95">
                                                    <i data-lucide="message-square" class="w-3 h-3"></i> Note
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="p-8 text-center text-slate-400 font-bold">
                                            No logs registered in this cutoff range.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Individual Calendar View Container -->
                    <div x-show="currentView === 'calendar'" class="mt-4">
                        @php
                            $firstDay = new \DateTime("{$myYear}-" . str_pad($myMonth, 2, '0', STR_PAD_LEFT) . "-01");
                            $daysInMonth = (int)$firstDay->format('t');
                            $startOfWeek = (int)$firstDay->format('w'); // 0=Sun, ..., 6=Sat
                            $logsByDate = [];
                            foreach ($myLogs as $log) {
                                $logsByDate[$log['date']] = $log;
                            }
                        @endphp
                        <div class="grid grid-cols-7 gap-2 text-center text-[10px] font-black uppercase text-slate-400 mb-2">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                                <div>{{ $dayName }}</div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7 gap-2">
                            @for($i = 0; $i < $startOfWeek; $i++)
                                <div class="bg-slate-50/40 border border-dashed border-slate-100 rounded-xl h-24"></div>
                            @endfor
                            @for($dayNum = 1; $dayNum <= $daysInMonth; $dayNum++)
                                @php
                                    $dateStr = sprintf('%04d-%02d-%02d', $myYear, $myMonth, $dayNum);
                                    $isDateActive = ($dateStr >= $myStartDate && $dateStr <= $myEndDate);
                                    $log = $logsByDate[$dateStr] ?? null;
                                @endphp
                                <div class="border rounded-xl p-2.5 h-24 flex flex-col justify-between text-left {{ $isDateActive ? 'bg-white border-slate-200' : 'bg-slate-50/30 border-slate-100 opacity-50' }}">
                                    <div class="flex justify-between items-center text-[10px] font-black">
                                        <span class="text-slate-400">{{ (new \DateTime($dateStr))->format('D') }}</span>
                                        <span class="{{ $isDateActive ? 'text-slate-800' : 'text-slate-400' }}">{{ $dayNum }}</span>
                                    </div>
                                    <div class="my-1.5 flex flex-col gap-1 text-[9px] font-semibold text-center">
                                        @if($isDateActive && $log)
                                            @if($log['time_in'] && $log['time_in'] !== '—')
                                                <div class="text-emerald-700 bg-emerald-50 rounded px-1 py-0.5 truncate">
                                                    In: {{ date('h:i A', strtotime($log['time_in'])) }}
                                                </div>
                                            @endif
                                            @if($log['time_out'] && $log['time_out'] !== '—')
                                                <div class="text-blue-700 bg-blue-50 rounded px-1 py-0.5 truncate">
                                                    Out: {{ date('h:i A', strtotime($log['time_out'])) }}
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="text-center">
                                        @if($isDateActive && $log && $log['status'])
                                            <span class="text-[8px] font-black uppercase tracking-wide px-1.5 py-0.5 rounded {{ $log['status'] === 'PRESENT' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($log['status'] === 'LATE' ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-rose-50 text-rose-700 border border-rose-100') }}">
                                                {{ $log['status'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endfor
                            @php
                                $totalCells = $startOfWeek + $daysInMonth;
                                $remaining = (7 - ($totalCells % 7)) % 7;
                            @endphp
                            @for($i = 0; $i < $remaining; $i++)
                                <div class="bg-slate-50/40 border border-dashed border-slate-100 rounded-xl h-24"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-12 text-center shadow-xs">
                    <i data-lucide="user-x" class="w-12 h-12 text-slate-400 mx-auto mb-4"></i>
                    <h3 class="text-sm font-extrabold text-slate-900">No Biometric Account Selected</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Please select a faculty member above to view their attendance metrics and link their biometric profile ID.</p>
                </div>
            @endif
        </div>

        <!-- Tab 3: Biometric Directory -->
        <div x-show="activeTab === 'directory'" class="space-y-6">
            <!-- Add User Form -->
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Biometric Machine Accounts Roster</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Manage virtual profile copies hosted on the device. Syncs directly back via user.dat compiling.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showAddUserForm = !showAddUserForm; isEditMode = false; employee_id = ''; name = ''; department_id = 0; card_number = ''; privilege = 0; password = ''; status = 0;" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-black text-slate-700 hover:bg-slate-50 transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="user-plus" class="w-4 h-4"></i> Add Account
                        </button>
                        <a href="{{ route('admin.faculty-attendance.users.download') }}" class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 text-xs font-black shadow-md transition active:scale-95 flex items-center gap-1.5 cursor-pointer">
                            <i data-lucide="download" class="w-4 h-4"></i> Compile & Download user.dat
                        </a>
                    </div>
                </div>

                <!-- Add/Edit form Modal -->
                <div x-show="showAddUserForm" x-cloak class="fixed inset-0 z-100 flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300" style="display: none;">
                    <div class="relative w-full max-w-2xl scale-95 transform rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900" @click.away="showAddUserForm = false">
                        <h3 class="text-base font-black text-slate-900 dark:text-white mb-4" x-text="isEditMode ? 'Edit Biometric Account' : 'Register New Biometric Profile'"></h3>
                        <form method="POST" action="{{ route('admin.faculty-attendance.users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Employee ID (PIN)</label>
                                <input type="number" name="employee_id" x-model="employee_id" :readonly="isEditMode" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Name (Max 24 chars)</label>
                                <input type="text" name="name" x-model="name" maxlength="24" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none uppercase">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Department ID</label>
                                <input type="number" name="department_id" x-model="department_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Card Number (Optional)</label>
                                <input type="text" name="card_number" x-model="card_number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Privilege Level</label>
                                <select name="privilege" x-model="privilege" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    <option value="0">0 - Normal User</option>
                                    <option value="14">14 - Administrator</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Device Password (Optional)</label>
                                <input type="password" name="password" x-model="password" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Account Status</label>
                                <select name="status" x-model="status" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none">
                                    <option value="0">Active</option>
                                    <option value="1">Inactive</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-4 border-t border-slate-100 mt-2">
                                <button type="button" @click="showAddUserForm = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-750 hover:bg-slate-50 transition active:scale-95 cursor-pointer">Cancel</button>
                                <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-xs font-black shadow-md transition active:scale-[0.98] cursor-pointer">Save Profile</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs align-middle">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                <th class="px-4 py-3 font-black">PIN</th>
                                <th class="px-4 py-3 font-black">Name</th>
                                <th class="px-4 py-3 font-black">Department Code</th>
                                <th class="px-4 py-3 font-black">Card Number</th>
                                <th class="px-4 py-3 font-black">Privilege</th>
                                <th class="px-4 py-3 font-black">Status</th>
                                <th class="px-4 py-3 text-center font-black">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($users as $user)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-4 py-3.5 font-extrabold text-slate-750">#{{ $user['employee_id'] }}</td>
                                    <td class="px-4 py-3.5 font-extrabold text-slate-900 uppercase">{{ $user['name'] }}</td>
                                    <td class="px-4 py-3.5 font-semibold text-slate-500">Group/Dept {{ $user['department_id'] }}</td>
                                    <td class="px-4 py-3.5 font-medium text-slate-600">{{ $user['card_number'] ?: '—' }}</td>
                                    <td class="px-4 py-3.5">
                                        @if($user['privilege'] == 14)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black bg-amber-50 text-amber-700 border border-amber-100">ADMIN</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black bg-slate-50 text-slate-600 border border-slate-100">USER</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if(($user['status'] ?? 0) == 0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100">ACTIVE</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black bg-rose-50 text-rose-700 border border-rose-100">INACTIVE</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" @click="
                                                showAddUserForm = true;
                                                isEditMode = true;
                                                employee_id = '{{ $user['employee_id'] }}';
                                                name = '{{ addslashes($user['name']) }}';
                                                department_id = '{{ $user['department_id'] }}';
                                                card_number = '{{ $user['card_number'] }}';
                                                privilege = '{{ $user['privilege'] }}';
                                                password = '{{ $user['password'] }}';
                                                status = '{{ $user['status'] }}';
                                            " class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[10px] font-bold text-slate-700 hover:bg-slate-50 transition active:scale-95 cursor-pointer">
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('admin.faculty-attendance.users.delete', $user['employee_id']) }}" onsubmit="return confirm('Are you sure you want to delete this biometric account? This will compile out of user.dat on download.')" class="inline">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-[10px] font-bold text-rose-600 hover:bg-rose-50 transition active:scale-95 cursor-pointer">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 font-bold">No biometric profiles imported yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Upload & Parse -->
        <div x-show="activeTab === 'import'" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- ZKTeco File Uploader -->
                <div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-1.5">
                            <i data-lucide="upload-cloud" class="text-emerald-600"></i> Import ZKTeco Data Files
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Upload plain binary or text files exported directly from biometric machine devices.</p>
                    </div>
                    <form action="{{ route('admin.faculty-attendance.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Attendance Logs -->
                            <div :class="attlog_filename ? 'border-emerald-500 bg-emerald-50/10' : 'border-slate-200 hover:bg-slate-50/50'" class="border-2 border-dashed rounded-2xl p-5 text-center relative transition cursor-pointer">
                                <input type="file" name="attlog_file" accept=".dat" @change="attlog_filename = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                <i data-lucide="file-spreadsheet" :class="attlog_filename ? 'text-emerald-600' : 'text-slate-400'" class="w-8 h-8 mx-auto mb-2"></i>
                                <div class="text-xs font-bold" :class="attlog_filename ? 'text-emerald-800' : 'text-slate-700'">Attendance Log</div>
                                <p class="text-[9px] mt-1" :class="attlog_filename ? 'text-emerald-600 font-extrabold' : 'text-slate-400'" x-text="attlog_filename ? 'Selected: ' + attlog_filename : 'Select attlog.dat / *_attlog.dat'"></p>
                            </div>

                            <!-- User Profiles -->
                            <div :class="user_filename ? 'border-emerald-500 bg-emerald-50/10' : 'border-slate-200 hover:bg-slate-50/50'" class="border-2 border-dashed rounded-2xl p-5 text-center relative transition cursor-pointer">
                                <input type="file" name="user_file" accept=".dat" @change="user_filename = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                <i data-lucide="users" :class="user_filename ? 'text-emerald-600' : 'text-slate-400'" class="w-8 h-8 mx-auto mb-2"></i>
                                <div class="text-xs font-bold" :class="user_filename ? 'text-emerald-800' : 'text-slate-700'">User Profiles</div>
                                <p class="text-[9px] mt-1" :class="user_filename ? 'text-emerald-600 font-extrabold' : 'text-slate-400'" x-text="user_filename ? 'Selected: ' + user_filename : 'Select user.dat'"></p>
                            </div>

                            <!-- Departments -->
                            <div :class="department_filename ? 'border-emerald-500 bg-emerald-50/10' : 'border-slate-200 hover:bg-slate-50/50'" class="border-2 border-dashed rounded-2xl p-5 text-center relative transition cursor-pointer">
                                <input type="file" name="department_file" accept=".dat" @change="department_filename = $event.target.files[0] ? $event.target.files[0].name : ''" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                                <i data-lucide="network" :class="department_filename ? 'text-emerald-600' : 'text-slate-400'" class="w-8 h-8 mx-auto mb-2"></i>
                                <div class="text-xs font-bold" :class="department_filename ? 'text-emerald-800' : 'text-slate-700'">Departments</div>
                                <p class="text-[9px] mt-1" :class="department_filename ? 'text-emerald-600 font-extrabold' : 'text-slate-400'" x-text="department_filename ? 'Selected: ' + department_filename : 'Select department.dat'"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 text-xs font-black shadow-md transition active:scale-[0.98] cursor-pointer">
                                Start Import & Parse
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Help Guide -->
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4 self-start">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">How does file parsing work?</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Biometric logs are exported to USB flash drives on machines that have no active network cable connection.</p>
                    </div>
                    <div class="text-xs text-slate-600 leading-relaxed space-y-2">
                        <p><strong>Step 1:</strong> Plug a USB drive into the biometric device and select "Download logs" / "Download users".</p>
                        <p><strong>Step 2:</strong> Upload the matching files (*_attlog.dat, user.dat, department.dat) in this parser dashboard.</p>
                        <p><strong>Step 3:</strong> Once imported, you can match faculty accounts under the "Individual Reports" tab to display their respective pay period logs.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Remarks Modal -->
    <div id="remarksModal" class="fixed inset-0 z-100 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs transition-all duration-300 hidden">
        <div class="relative w-full max-w-md scale-95 transform rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-2">Faculty Attendance Note / Remark</h3>
            <p class="text-xs font-semibold text-slate-500 mb-4">Set a custom remark status for this date (<span id="modalDateStr" class="font-extrabold text-slate-800"></span>).</p>
            <input type="hidden" id="modalDate">
            <input type="hidden" id="modalEmployeeId">
            <textarea id="modalRemarkText" rows="3" placeholder="Enter reason, leave status note, rest day context, etc..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none mb-4 uppercase"></textarea>
            
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeRemarksModal()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-750 hover:bg-slate-50 transition active:scale-95 cursor-pointer">Cancel</button>
                <button type="button" onclick="saveRemarksModal()" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-xs font-black shadow-md transition active:scale-[0.98] cursor-pointer">Save Remarks</button>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState(null, '', url.pathname + url.search);
        }

        function openRemarksModal(date, employeeId, remark) {
            document.getElementById('modalDate').value = date;
            document.getElementById('modalEmployeeId').value = employeeId;
            document.getElementById('modalDateStr').textContent = new Date(date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('modalRemarkText').value = remark;
            
            const modal = document.getElementById('remarksModal');
            modal.classList.remove('hidden');
        }

        function closeRemarksModal() {
            const modal = document.getElementById('remarksModal');
            modal.classList.add('hidden');
        }

        function saveRemarksModal() {
            const date = document.getElementById('modalDate').value;
            const employeeId = document.getElementById('modalEmployeeId').value;
            const remark = document.getElementById('modalRemarkText').value;

            fetch("{{ route('admin.faculty-attendance.remarks.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    date: date,
                    employee_id: employeeId,
                    remark: remark
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.showToast('Attendance remarks saved successfully!', 'success');
                    closeRemarksModal();
                    // Reload page after a delay to reflect update
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    window.alert('Failed to save remarks: ' + data.message);
                }
            })
            .catch(err => {
                window.alert('Network error while saving remarks.');
            });
        }
    </script>
</x-admin-layout>
