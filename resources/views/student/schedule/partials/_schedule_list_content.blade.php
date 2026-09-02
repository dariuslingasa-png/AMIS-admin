@php
    $getSubjectStyle = function ($subjectName) {
        $subjectLower = mb_strtolower((string) $subjectName);
        if (str_contains($subjectLower, 'math')) {
            return ['bg' => '#e0e7ff', 'border' => '#c7d2fe', 'text' => '#312e81', 'badge' => '#4338ca'];
        }
        if (str_contains($subjectLower, 'science')) {
            return ['bg' => '#f3e8ff', 'border' => '#e9d5ff', 'text' => '#581c87', 'badge' => '#7e22ce'];
        }
        if (str_contains($subjectLower, 'english') || str_contains($subjectLower, 'reading')) {
            return ['bg' => '#e0f2fe', 'border' => '#bae6fd', 'text' => '#0c4a6e', 'badge' => '#0284c7'];
        }
        if (str_contains($subjectLower, 'quran') || str_contains($subjectLower, 'qur')) {
            return ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#064e3b', 'badge' => '#059669'];
        }
        if (str_contains($subjectLower, 'arabic')) {
            return ['bg' => '#ecfdf5', 'border' => '#6ee7b7', 'text' => '#064e3b', 'badge' => '#047857'];
        }
        if (str_contains($subjectLower, 'hadith') || str_contains($subjectLower, 'islamic') || str_contains($subjectLower, 'shaf')) {
            return ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#166534', 'badge' => '#15803d'];
        }
        if (str_contains($subjectLower, 'circle time') || str_contains($subjectLower, 'circle')) {
            return ['bg' => '#f0fdfa', 'border' => '#99f6e4', 'text' => '#134e4a', 'badge' => '#0f766e'];
        }
        if (str_contains($subjectLower, 'meeting time') || str_contains($subjectLower, 'wrap-up')) {
            return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'badge' => '#475569'];
        }
        if (str_contains($subjectLower, 'filipino')) {
            return ['bg' => '#fef3c7', 'border' => '#fde68a', 'text' => '#78350f', 'badge' => '#b45309'];
        }
        if (str_contains($subjectLower, 'mapeh')) {
            return ['bg' => '#ffe4e6', 'border' => '#fecdd3', 'text' => '#881337', 'badge' => '#be123c'];
        }
        if (str_contains($subjectLower, 'ap') || str_contains($subjectLower, 'araling')) {
            return ['bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12', 'badge' => '#c2410c'];
        }
        if (str_contains($subjectLower, 'tle') || str_contains($subjectLower, 'computer') || str_contains($subjectLower, 'ict')) {
            return ['bg' => '#f0fdfa', 'border' => '#ccfbf1', 'text' => '#115e59', 'badge' => '#0f766e'];
        }
        if (str_contains($subjectLower, 'assembly') || str_contains($subjectLower, 'transition') || str_contains($subjectLower, 'break') || str_contains($subjectLower, 'recess')) {
            return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#475569', 'badge' => '#64748b'];
        }
        return ['bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'badge' => '#475569'];
    };
@endphp

@if($hasSchedule)
    <!-- Day selector tabs -->
    <div style="display: flex; overflow-x: auto; background: #f1f5f9; padding: 0.35rem; border-radius: 14px; gap: 0.35rem;">
        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
            <button type="button"
                    @click="activeDay = '{{ $dayName }}'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    :class="activeDay === '{{ $dayName }}' ? 'day-tab-btn active' : 'day-tab-btn'"
                    style="flex: 1; text-align: center; white-space: nowrap; padding: 0.6rem 1rem; border-radius: 10px; font-size: 13.5px; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s ease;">
                {{ $dayName }}
                <span style="font-size: 11px; opacity: 0.75; font-weight: 600;">
                    ({{ count($weeklySchedule[$dayName] ?? []) }})
                </span>
            </button>
        @endforeach
    </div>

    <!-- Day cards -->
    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
        <div x-show="activeDay === '{{ $dayName }}'" class="space-y-3">
            @php
                $classesForDay = $weeklySchedule[$dayName] ?? [];
            @endphp

            @if(empty($classesForDay))
                <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 16px; padding: 2.5rem; text-align: center;">
                    <p style="font-size: 14px; font-weight: 600; color: #64748b; margin: 0;">
                        No classes scheduled on {{ $dayName }}.
                    </p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                    @foreach($classesForDay as $entry)
                        @php
                            $style = $getSubjectStyle($entry->subject_name);
                        @endphp
                        <div style="background: {{ $style['bg'] }}; border: 1.5px solid {{ $style['border'] }}; border-radius: 16px; padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.85rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 12px; font-weight: 850; color: {{ $style['text'] }}; text-transform: uppercase;">
                                    {{ $entry->day }}
                                </span>
                                <span style="font-size: 13px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 0.3rem;">
                                    <i data-lucide="clock" style="width: 13px; height: 13px; color: #64748b;"></i>
                                    {{ $entry->time }}
                                </span>
                            </div>

                            <div>
                                <h4 style="font-size: 16px; font-weight: 900; color: {{ $style['text'] }}; text-transform: uppercase; margin: 0; line-height: 1.3;">
                                    {{ $entry->subject_name }}
                                </h4>
                                <p style="font-size: 13px; font-weight: 750; color: {{ $style['text'] }}; opacity: 0.9; margin: 0.35rem 0 0; display: flex; align-items: center; gap: 0.35rem;">
                                    <i data-lucide="user" style="width: 13px; height: 13px;"></i>
                                    <span>{{ $entry->teacher_display ?: '—' }}</span>
                                </p>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; color: {{ $style['text'] }}; opacity: 0.85; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06);">
                                <span>{{ $entry->room }}</span>
                                <span>{{ $entry->modality }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
@else
    <div class="s-empty-card" style="background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; padding: 4rem 2rem; text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #ecfdf5; border: 1.5px solid #a7f3d0; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
            <i data-lucide="calendar-x" style="width: 28px; height: 28px; color: #059669;"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem;">No Official Class Schedule Available</h3>
        <p style="font-size: 14px; font-weight: 500; color: #64748b; margin: 0 auto; max-width: 500px;">
            No official class schedule is currently available for your section. Please check again later.
        </p>
    </div>
@endif
