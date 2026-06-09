<table class="amis-table">
    <thead>
        <tr>
            <th>Applicant</th>
            <th>Email</th>
            <th>Grade</th>
            <th>Learning Mode</th>
            <th>Status</th>
            <th>Submitted</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($applicants as $applicant)
            <tr>
                <td>{{ trim(($applicant->first_name ?? '').' '.($applicant->last_name ?? '')) ?: 'Applicant' }}</td>
                <td>{{ $applicant->user->email ?? $applicant->email ?? '-' }}</td>
                <td>{{ $applicant->grade_level ?? '-' }}</td>
                <td>
                    @if(empty($applicant->learning_mode))
                        <span class="text-slate-400 font-medium">-</span>
                    @elseif($applicant->learning_mode === 'Face-to-Face')
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 border border-emerald-100">
                            <i data-lucide="school" class="h-3 w-3"></i>
                            F2F
                        </span>
                    @elseif(str_contains($applicant->learning_mode, '1st Shift'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 border border-blue-100">
                            <i data-lucide="sun" class="h-3 w-3"></i>
                            Flex (1st)
                        </span>
                    @elseif(str_contains($applicant->learning_mode, '2nd Shift'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 border border-amber-100">
                            <i data-lucide="moon" class="h-3 w-3"></i>
                            Flex (2nd)
                        </span>
                    @else
                        <span class="text-slate-600 font-medium">{{ $applicant->learning_mode }}</span>
                    @endif
                </td>
                <td>{{ $statusLabels[$applicant->status] ?? $applicant->status ?? '-' }}</td>
                <td>{{ optional($applicant->created_at)->format('M d, Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-gray-500">No enrollees found in this list.</td></tr>
        @endforelse
    </tbody>
</table>
