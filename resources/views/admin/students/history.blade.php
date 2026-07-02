@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
    $msStatusBadge = [
        'enrolled' => 'green',
        'failed' => 'red',
        'pending' => 'yellow'
    ];
    $msStatusLabel = [
        'enrolled' => 'Microsoft Active',
        'failed' => 'Sync Error',
        'pending' => 'Sync Pending'
    ];
@endphp

<x-admin-layout
    title="Enrollment History"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Enrollment History', 'href' => null],
    ]"
>
    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 px-6 py-6 bg-gradient-to-r from-slate-50 to-slate-100/50">
            <div>
                <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Students Workspace</p>
                <h1 class="mt-1 text-xl font-extrabold text-slate-950">Enrollment & Onboarding History</h1>
                <p class="mt-1 text-xs md:text-sm text-slate-500 font-medium">A chronological audit log of student registrations, auto-generated emails, OR numbers, and portal credentials.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.students.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-95">
                    <i data-lucide="user-check" class="h-4 w-4"></i>
                    Student Records
                </a>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Filter Bar Form -->
            <form method="GET" class="mb-5 flex gap-3 max-w-lg">
                <label class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search name, student number, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98]">
                    <i data-lucide="search-code" class="h-4 w-4"></i>
                    Search Logs
                </button>
            </form>


            <!-- Audit Log Table -->
            <div id="tableContainer" class="overflow-hidden rounded-2xl border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-4 font-black">Date & Time</th>
                            <th class="w-48 px-5 py-4 font-black">Event Action</th>
                            <th class="px-5 py-4 font-black">Audit Message</th>
                            <th class="w-64 px-5 py-4 font-black">Performed By</th>
                            <th class="w-36 px-5 py-4 font-black">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($logs as $log)
                            @php
                                $badgeColor = match($log->event) {
                                    'application_approved', 'license_assigned', 'account_created' => 'green',
                                    'license_revoked', 'user_deleted' => 'red',
                                    'credentials_sent', 'credentials_resent' => 'yellow',
                                    'email_renamed' => 'blue',
                                    default => 'gray'
                                };
                                $eventLabel = match($log->event) {
                                    'application_approved' => 'Approved',
                                    'application_status_updated' => 'Status Update',
                                    'onboarding_email_resent' => 'Resend Mail',
                                    'section_verified' => 'Section Verified',
                                    'documents_approved' => 'Docs Approved',
                                    'documents_rejected' => 'Docs Rejected',
                                    'document_approved' => 'Doc Approved',
                                    'document_rejected' => 'Doc Rejected',
                                    'license_assigned' => 'License Sync',
                                    'credentials_sent' => 'Credentials Set',
                                    'credentials_resent' => 'Credentials Resent',
                                    'email_renamed' => 'Email Updated',
                                    default => ucfirst(str_replace('_', ' ', $log->event))
                                };
                            @endphp
                            <tr class="transition hover:bg-slate-50/50">
                                <!-- Date & Time -->
                                <td class="px-5 py-4 text-xs font-bold text-slate-700 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></div>
                                        <span>{{ $log->created_at ? $log->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') : 'NA' }}</span>
                                    </div>
                                </td>

                                <!-- Event Action Badge -->
                                <td class="px-5 py-4">
                                    <x-badge :color="$badgeColor">{{ $eventLabel }}</x-badge>
                                </td>

                                <!-- Audit Message -->
                                <td class="px-5 py-4">
                                    <div class="text-sm font-extrabold text-slate-900 leading-normal">
                                        {{ $log->message }}
                                    </div>
                                </td>

                                <!-- Performed By -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-700 text-xs flex items-center gap-1">
                                        <i data-lucide="user" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $log->email ?: 'System Process' }}
                                    </div>
                                </td>

                                <!-- IP Address -->
                                <td class="px-5 py-4 font-mono text-xs text-slate-500 whitespace-nowrap">
                                    {{ $log->ip_address ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">
                                    No administrative history events logged.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination links -->
            <div class="mt-5">{{ $logs->links() }}</div>
        </div>
    </section>

</x-admin-layout>
