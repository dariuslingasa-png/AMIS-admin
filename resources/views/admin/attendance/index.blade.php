<x-admin-layout
    title="Daily Attendance Overview"
    :breadcrumbs="[
        ['label' => 'Attendance', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Top Banner / Actions -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                    <i data-lucide="calendar-check" class="h-6 w-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Student Daily Attendance</h1>
                    <p class="text-xs font-semibold text-slate-500 mt-1">
                        Track daily school entrance checks, QR code scans, and manual logs for <strong class="text-slate-700">{{ date('l, M d, Y', strtotime($today)) }}</strong>.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.attendance.scanner') }}" class="inline-flex items-center gap-2 rounded-xl bg-cyan-600 px-4 py-2.5 text-xs font-black text-white hover:bg-cyan-700 active:scale-95 transition shadow-xs cursor-pointer">
                    <i data-lucide="qr-code" class="h-4 w-4"></i>
                    Live QR Scanner
                </a>
                <a href="{{ route('admin.attendance.manual') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 active:scale-95 transition shadow-xs cursor-pointer">
                    <i data-lucide="edit-3" class="h-4 w-4"></i>
                    Manual Entry
                </a>
                <a href="{{ route('admin.attendance.reports') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 active:scale-95 transition shadow-xs cursor-pointer">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    Attendance Reports
                </a>
            </div>
        </section>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Enrolled</p>
                <div class="text-2xl font-black text-slate-900 mt-2">{{ $stats['total'] }}</div>
                <p class="text-[9px] font-semibold text-slate-500 mt-1">Active students</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/10 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-emerald-700">Checked In Today</p>
                <div class="text-2xl font-black text-emerald-800 mt-2">{{ $stats['present'] }}</div>
                <p class="text-[9px] font-semibold text-emerald-600 mt-1">Present & Late</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50/10 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-amber-700">Late Arrivals</p>
                <div class="text-2xl font-black text-amber-800 mt-2">{{ $stats['late'] }}</div>
                <p class="text-[9px] font-semibold text-amber-600 mt-1">Checked in after 7:30 AM</p>
            </div>
            <div class="rounded-2xl border border-rose-100 bg-rose-50/10 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-rose-700">Absent Today</p>
                <div class="text-2xl font-black text-rose-800 mt-2">{{ $stats['absent'] }}</div>
                <p class="text-[9px] font-semibold text-rose-600 mt-1">No scan record yet</p>
            </div>
            <div class="rounded-2xl border border-cyan-100 bg-cyan-50/10 p-5 shadow-xs">
                <p class="text-[10px] font-black uppercase tracking-wider text-cyan-700">Attendance Rate</p>
                <div class="text-2xl font-black text-cyan-800 mt-2">{{ $stats['rate'] }}%</div>
                <p class="text-[9px] font-semibold text-cyan-600 mt-1">Of total enrolled</p>
            </div>
        </div>

        <!-- Today's Logs Table -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Today's Attendance Logs</h3>
                <p class="text-xs text-slate-500 mt-0.5">Real-time check-in and check-out logs captured by QR scanner and manual input today.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs align-middle">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th class="px-4 py-3 font-black">Student ID</th>
                            <th class="px-4 py-3 font-black">Student Name</th>
                            <th class="px-4 py-3 font-black">Grade & Section</th>
                            <th class="px-4 py-3 text-center font-black">Time In</th>
                            <th class="px-4 py-3 text-center font-black">Time Out</th>
                            <th class="px-4 py-3 font-black">Status</th>
                            <th class="px-4 py-3 font-black">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($todayLogs as $log)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3.5 font-extrabold text-slate-700">{{ $log->student_number }}</td>
                                <td class="px-4 py-3.5 font-extrabold text-slate-900 uppercase">
                                    {{ $log->last_name }}, {{ $log->first_name }} {{ $log->middle_name }}
                                </td>
                                <td class="px-4 py-3.5 font-semibold text-slate-600 uppercase">
                                    {{ $log->grade_level }} {{ $log->section_name ? '- ' . $log->section_name : '' }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-extrabold text-slate-800">
                                    {{ $log->time_in ? date('h:i A', strtotime($log->time_in)) : '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-center font-extrabold text-slate-800">
                                    {{ $log->time_out ? date('h:i A', strtotime($log->time_out)) : '—' }}
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($log->status === 'PRESENT')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-150 uppercase tracking-wide">
                                            PRESENT
                                        </span>
                                    @elseif($log->status === 'LATE')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-150 uppercase tracking-wide">
                                            LATE
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-150 uppercase tracking-wide">
                                            ABSENT
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-slate-500 font-medium">{{ $log->remarks ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-slate-400 font-bold">
                                    No attendance scans recorded yet for today. Click "Live QR Scanner" to begin scanning.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
