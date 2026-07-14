<div x-show="activeTab === 'academic'" class="space-y-6" x-cloak>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h4 class="text-xxs font-extrabold uppercase tracking-wider text-slate-400">Section Classroom</h4>
            <p class="mt-1 text-base font-extrabold text-slate-900">{{ $student->studentSection?->section?->official_name ?? $student->studentSection?->section?->name ?? 'Unnamed Section' }}</p>
            <div class="mt-2.5 flex items-center gap-1.5">
                <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-xxs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">{{ $student->studentSection?->section?->learning_mode ?? '-' }}</span>
                @if($student->studentSection?->section?->shift)
                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xxs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $student->studentSection?->section?->shift }}</span>
                @endif
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h4 class="text-xxs font-extrabold uppercase tracking-wider text-slate-400">Microsoft AD Identity</h4>
            <p class="mt-1 text-xxs font-mono text-slate-600 overflow-x-auto select-all">{{ $student->ms_user_id ?? 'No AD object mapped' }}</p>
            <div class="mt-2">
                @php
                    $msStatus = $student->studentSection?->ms_status ?? 'pending';
                    $badgeColor = match($msStatus) { 'enrolled' => 'green', 'failed' => 'red', default => 'yellow' };
                    $badgeLabel = match($msStatus) { 'enrolled' => 'Synced', 'failed' => 'Failed', default => 'Pending' };
                @endphp
                <x-badge :color="$badgeColor">MS Sync: {{ $badgeLabel }}</x-badge>
            </div>
        </div>
    </div>

    <x-card title="Registered Subjects & Channels" subtitle="Academic subjects linked in Teams">
        <div class="overflow-hidden rounded-md border border-slate-200 mt-2">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-55 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-bold">Subject Name</th>
                        <th class="px-5 py-4 font-bold">Assigned Teacher</th>
                        <th class="px-5 py-4 font-bold">Schedule</th>
                        <th class="px-5 py-4 font-bold text-right">Teams Channel</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($student->studentSection?->section?->subjects ?? [] as $sub)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-5 py-4 font-extrabold text-slate-950">{{ $sub->subject_name }}</td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $sub->teacher_name ?? 'TBA' }}</td>
                            <td class="px-5 py-4 font-medium text-slate-500">{{ $sub->schedule ?? '-' }}</td>
                            <td class="px-5 py-4 text-right font-mono text-xxs text-slate-400 select-all">{{ $sub->ms_channel_id ?? 'No channel' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-sm font-medium text-slate-500">No subjects registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>



    <!-- History & Onboarding Logs Timeline -->
    <x-card title="Academic History & Onboarding Log" subtitle="Chronological audit trail of status transitions and sync events">
        <div class="flow-root mt-4">
            <ul role="list" class="-mb-8">
                @forelse($auditLogs ?? [] as $index => $log)
                    @php
                        $iconClass = match($log->event) {
                            'license_assigned', 'account_created' => 'bg-emerald-500 text-white',
                            'license_revoked', 'user_deleted' => 'bg-rose-500 text-white',
                            'credentials_sent', 'credentials_resent' => 'bg-amber-500 text-white',
                            'email_renamed' => 'bg-blue-500 text-white',
                            default => 'bg-slate-400 text-white'
                        };
                        $lucideIcon = match($log->event) {
                            'license_assigned', 'account_created' => 'check',
                            'license_revoked', 'user_deleted' => 'x',
                            'credentials_sent', 'credentials_resent' => 'key',
                            'email_renamed' => 'mail',
                            default => 'activity'
                        };
                    @endphp
                    <li>
                        <div class="relative pb-8">
                            @if ($index < count($auditLogs) - 1)
                                <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start space-x-3">
                                <div>
                                    <div class="relative flex h-10 w-10 items-center justify-center rounded-full {{ $iconClass }} ring-8 ring-white">
                                        <i data-lucide="{{ $lucideIcon }}" class="h-4 w-4"></i>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 py-1.5">
                                    <div class="text-xs font-bold text-slate-900 leading-normal">
                                        {{ $log->message }}
                                    </div>
                                    <div class="mt-1 flex items-center justify-between text-[10px] text-slate-400 font-bold">
                                        <span>Triggered by: <span class="text-slate-600 font-semibold">{{ $log->email ?: 'System' }}</span></span>
                                        <span>{{ $log->created_at ? $log->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <div class="text-center py-10 text-xs font-bold text-slate-400">
                        <i data-lucide="history" class="h-8 w-8 mx-auto mb-2 text-slate-350"></i>
                        No administrative history events logged for this student.
                    </div>
                @endforelse
            </ul>
        </div>
    </x-card>
</div>
