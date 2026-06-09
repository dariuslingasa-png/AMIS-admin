<table class="premium-table w-full">
    <thead>
        <tr>
            <th class="w-12 px-4 py-3 text-center">#</th>
            <th class="px-4 py-3">Applicant</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Grade</th>
            <th class="px-4 py-3">Learning Mode</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Submitted</th>
        </tr>
    </thead>
    <tbody>
        @php
            $startNumber = ($applicants instanceof \Illuminate\Pagination\LengthAwarePaginator) 
                ? ($applicants->currentPage() - 1) * $applicants->perPage() 
                : 0;
        @endphp
        @forelse ($applicants as $applicant)
            <tr class="hover:bg-gray-50/50 transition-colors">
                <!-- Row Number -->
                <td class="px-4 py-4 text-center font-bold text-slate-400 text-xs">
                    {{ $startNumber + $loop->iteration }}
                </td>

                <!-- Applicant Name -->
                <td class="px-4 py-4">
                    <span class="font-extrabold text-slate-900 uppercase tracking-wide text-[11px]">
                        {{ html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8') ?: 'Applicant' }}
                    </span>
                </td>

                <!-- Email -->
                <td class="px-4 py-4">
                    <span class="font-semibold text-slate-600 text-xs">
                        {{ $applicant->user->email ?? $applicant->email ?? '-' }}
                    </span>
                </td>

                <!-- Grade -->
                <td class="px-4 py-4">
                    <span class="font-bold text-slate-700 text-xs">
                        {{ $applicant->grade_level ?? '-' }}
                    </span>
                </td>

                <!-- Learning Mode Badge -->
                <td class="px-4 py-4">
                    @if(empty($applicant->learning_mode))
                        <span class="text-slate-400 font-medium text-xs">-</span>
                    @elseif($applicant->learning_mode === 'Face-to-Face')
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-extrabold text-emerald-700 border border-emerald-100">
                            <i data-lucide="school" class="h-3 w-3"></i>
                            F2F
                        </span>
                    @elseif(str_contains($applicant->learning_mode, '1st Shift'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-700 border border-blue-100">
                            <i data-lucide="sun" class="h-3 w-3"></i>
                            Flex (1st)
                        </span>
                    @elseif(str_contains($applicant->learning_mode, '2nd Shift'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold text-amber-700 border border-amber-100">
                            <i data-lucide="moon" class="h-3 w-3"></i>
                            Flex (2nd)
                        </span>
                    @else
                        <span class="text-slate-650 font-semibold text-xs">{{ $applicant->learning_mode }}</span>
                    @endif
                </td>

                <!-- Status Badge -->
                <td class="px-4 py-4">
                    @php
                        $status = $applicant->status;
                        $label = $statusLabels[$status] ?? $status;
                    @endphp
                    @if($status === 'approved')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            {{ $label }}
                        </span>
                    @elseif($status === 'rejected' || $status === 'cancelled')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-100">
                            {{ $label }}
                        </span>
                    @elseif($status === 'under_review')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100">
                            {{ $label }}
                        </span>
                    @elseif($status === 'submitted' || $status === 'ready_for_submission')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-100">
                            {{ $label }}
                        </span>
                    @elseif($status === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-100">
                            {{ $label }}
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-600 border border-gray-150">
                            {{ $label }}
                        </span>
                    @endif
                </td>

                <!-- Date Submitted -->
                <td class="px-4 py-4">
                    <span class="font-semibold text-slate-500 text-xs">
                        {{ optional($applicant->created_at)->format('M d, Y') }}
                    </span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                    <div class="empty-state">
                        <i data-lucide="inbox" class="h-8 w-8 text-slate-350"></i>
                        <p class="font-semibold text-sm">No enrollees found in this list.</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
