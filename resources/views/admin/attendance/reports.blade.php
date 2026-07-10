<x-admin-layout
    title="Attendance Reports"
    :breadcrumbs="[
        ['label' => 'Attendance', 'href' => route('admin.attendance.index')],
        ['label' => 'Reports', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Filter Panel -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5"></i>
                    Report Filters
                </h2>
                <a href="{{ route('admin.attendance.reports') }}" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 transition">
                    Reset Filters
                </a>
            </div>

            <form action="{{ route('admin.attendance.reports') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- Start Date -->
                <div>
                    <label for="start_date" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Start Date</label>
                    <input 
                        type="date" 
                        name="start_date" 
                        id="start_date" 
                        value="{{ request('start_date', date('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                </div>

                <!-- End Date -->
                <div>
                    <label for="end_date" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">End Date</label>
                    <input 
                        type="date" 
                        name="end_date" 
                        id="end_date" 
                        value="{{ request('end_date', date('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                </div>

                <!-- Grade Level -->
                <div>
                    <label for="grade_level" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Grade Level</label>
                    <select 
                        name="grade_level" 
                        id="grade_level" 
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                        <option value="">All Grade Levels</option>
                        @foreach($gradeLevels as $gl)
                            <option value="{{ $gl }}" {{ request('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                    <select 
                        name="status" 
                        id="status" 
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                        <option value="">All Statuses</option>
                        <option value="PRESENT" {{ request('status') === 'PRESENT' ? 'selected' : '' }}>PRESENT</option>
                        <option value="LATE" {{ request('status') === 'LATE' ? 'selected' : '' }}>LATE</option>
                        <option value="ABSENT" {{ request('status') === 'ABSENT' ? 'selected' : '' }}>ABSENT</option>
                    </select>
                </div>

                <!-- Actions / Search button -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full justify-center inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white hover:bg-indigo-700 active:scale-95 transition shadow-xs cursor-pointer">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        Filter Logs
                    </button>
                </div>
            </form>
        </section>

        <!-- Logs Directory Table -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Attendance Log Directory</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Historical logs based on selected dates and filter preferences.</p>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-center">
                    <!-- Export button passing all current query strings -->
                    <a 
                        href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" 
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                    >
                        <i data-lucide="download" class="h-3.5 w-3.5"></i> Export CSV Report
                    </a>
                </div>
            </div>

            <!-- Search Field -->
            <form action="{{ route('admin.attendance.reports') }}" method="GET" class="flex items-center justify-between gap-4">
                <!-- Keep existing query string values -->
                <input type="hidden" name="start_date" value="{{ request('start_date') }}">
                <input type="hidden" name="end_date" value="{{ request('end_date') }}">
                <input type="hidden" name="grade_level" value="{{ request('grade_level') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">

                <div class="relative w-full max-w-sm">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i data-lucide="search" class="h-4 w-4"></i>
                    </span>
                    <input 
                        type="text" 
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search student ID, name..." 
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-4 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 focus:bg-white transition"
                    >
                </div>
                <div class="text-xxs font-semibold text-slate-400">
                    {{ $logs->total() }} total matching records
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs align-middle">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th class="px-4 py-3 font-black">Date</th>
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
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3.5 font-bold text-slate-800">{{ date('M d, Y', strtotime($log->date)) }}</td>
                                <td class="px-4 py-3.5 font-extrabold text-slate-700">{{ $log->student_number }}</td>
                                <td class="px-4 py-3.5 font-extrabold text-slate-900 uppercase">
                                    {{ $log->last_name }}, {{ $log->first_name }}
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
                                <td colspan="8" class="p-8 text-center text-slate-400 font-bold">
                                    No attendance records found matching selected filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            <div class="mt-4 pt-4 border-t border-slate-100">
                {{ $logs->appends(request()->except('page'))->links() }}
            </div>
        </section>
    </div>
</x-admin-layout>
