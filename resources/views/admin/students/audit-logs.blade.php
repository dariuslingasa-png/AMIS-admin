@php
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
@endphp

<x-admin-layout
    title="Student Audit Logs"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Audit Logs & History', 'href' => null],
    ]"
>
    <section class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Students Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950 dark:text-white">Student Audit Logs & Activity History</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Real-time tracking of photo uploads, profile updates, section transfers, and administrative modifications.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.students.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to Student Records
                </a>
            </div>
        </div>

        <div class="px-6 py-5 space-y-6">
            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.students.audit-logs') }}" class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by student name, UPN, student ID, admin name, or IP..." 
                           class="w-full h-10 pl-10 pr-4 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800/60 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
                </div>
                
                <div class="w-full md:w-56">
                    <select name="event" onchange="this.form.submit()" 
                            class="w-full h-10 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800/60 dark:border-slate-700 text-xs font-semibold text-slate-800 dark:text-slate-200 outline-none focus:border-emerald-500">
                        <option value="all" {{ $eventFilter === 'all' ? 'selected' : '' }}>All Event Types</option>
                        <option value="photo" {{ $eventFilter === 'photo' ? 'selected' : '' }}>📷 Photo Uploads / Edits</option>
                        <option value="profile" {{ $eventFilter === 'profile' ? 'selected' : '' }}>✏️ Profile / Name Updates</option>
                        <option value="section" {{ $eventFilter === 'section' ? 'selected' : '' }}>🏫 Section Assignments</option>
                        <option value="approval" {{ $eventFilter === 'approval' ? 'selected' : '' }}>✅ Enrollment Approvals</option>
                        <option value="delete" {{ $eventFilter === 'delete' ? 'selected' : '' }}>🗑️ Deletions / Resets</option>
                    </select>
                </div>

                <button type="submit" class="h-10 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition active:scale-[0.98] cursor-pointer">
                    Filter
                </button>
                @if($search || $eventFilter !== 'all')
                    <a href="{{ route('admin.students.audit-logs') }}" class="h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 font-bold text-xs flex items-center justify-center transition">
                        Reset
                    </a>
                @endif
            </form>

            <!-- Table of Audit Logs -->
            @if($logs->isEmpty())
                <div class="text-center py-16 bg-slate-50/50 dark:bg-slate-800/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                    <i data-lucide="history" class="h-12 w-12 mx-auto text-slate-300 dark:text-slate-600 mb-3"></i>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">No Audit Logs Found</h3>
                    <p class="text-xs text-slate-400 mt-1">Try clearing filters or search terms.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">Timestamp</th>
                                <th class="py-3.5 px-4">Admin / Staff</th>
                                <th class="py-3.5 px-4">Event</th>
                                <th class="py-3.5 px-4">Description / Action</th>
                                <th class="py-3.5 px-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900/10">
                            @foreach($logs as $log)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3.5 px-4 text-xs font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        {{ $log->created_at ? $log->created_at->format('M d, Y h:i:s A') : '—' }}
                                        <div class="text-[10px] text-slate-400 font-normal">{{ $log->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs">
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $log->user ? $log->user->name : ($log->email ?: 'System') }}
                                        </div>
                                        @if($log->user && $log->user->email)
                                            <div class="text-[10px] text-slate-400 font-medium">{{ $log->user->email }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-xs">
                                        @php
                                            $eventBadgeClass = match(true) {
                                                str_contains($log->event, 'photo') => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                                                str_contains($log->event, 'profile') => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                                                str_contains($log->event, 'section') => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                                str_contains($log->event, 'delete') => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                                                default => 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $eventBadgeClass }}">
                                            {{ str_replace('_', ' ', strtoupper($log->event)) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-xs font-medium text-slate-700 dark:text-slate-300">
                                        {{ $log->message ?: 'Action recorded' }}
                                        @if(!empty($log->metadata) && is_array($log->metadata))
                                            <details class="mt-1">
                                                <summary class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold cursor-pointer hover:underline">View Metadata</summary>
                                                <pre class="text-[10px] bg-slate-900 text-slate-200 p-2.5 rounded-lg mt-1 font-mono overflow-x-auto max-w-lg leading-relaxed">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-xs font-mono text-slate-500 whitespace-nowrap">
                                        {{ $log->ip_address ?: '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </section>
</x-admin-layout>
