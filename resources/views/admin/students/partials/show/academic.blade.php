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
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
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
</div>
