@php
    $teacherName = function ($subject): string {
        $raw = is_string($subject) ? $subject : (!empty($subject->teacher_display) ? $subject->teacher_display : ($subject->teacher_name ?? null));
        if (!$raw) return '—';

        $nameTrimmed = trim($raw);
        $nameLower = strtolower($nameTrimmed);

        $titleMap = [
            'teacher '  => 'Tchr.',
            'tchr. '    => 'Tchr.',
            'tchr '     => 'Tchr.',
            'ustadha '  => 'Ustadha',
            'ustadh '   => 'Ust.',
            'ustadz '   => 'Ust.',
            'ust. '     => 'Ust.',
            'ust '      => 'Ust.',
            'alimah '   => 'Alimah',
            'alima '    => 'Alima',
            'alim '     => 'Alim',
            'sir '      => 'Sir',
            'ma\'am '   => 'Ma\'am',
        ];

        foreach ($titleMap as $prefix => $shortTitle) {
            if (str_starts_with($nameLower, $prefix)) {
                $rest = trim(substr($nameTrimmed, strlen($prefix)));
                $firstName = ucfirst(strtolower(explode(' ', $rest)[0]));
                return $shortTitle . ' ' . $firstName;
            }
        }

        $parts = explode(' ', $nameTrimmed);
        return ucfirst(strtolower($parts[0]));
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
                        <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.85rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11.5px; font-weight: 850; color: #059669; text-transform: uppercase; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.5rem; border-radius: 6px;">
                                    {{ $entry->day }}
                                </span>
                                <span style="font-size: 13px; font-weight: 850; color: #0f172a; display: flex; align-items: center; gap: 0.3rem;">
                                    <i data-lucide="clock" style="width: 13px; height: 13px; color: #64748b;"></i>
                                    {{ $entry->time }}
                                </span>
                            </div>

                            <div>
                                <h4 style="font-size: 16px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 0; line-height: 1.3;">
                                    {{ $entry->subject_name }}
                                </h4>
                                
                                <div style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 12px; font-weight: 750; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.2rem 0.55rem; margin-top: 0.4rem;">
                                    <i data-lucide="user" style="width: 12px; height: 12px; color: #64748b;"></i>
                                    <span>{{ $teacherName($entry->teacher_display ?: $entry->teacher_name) }}</span>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; font-weight: 700; color: #64748b; padding-top: 0.5rem; border-top: 1px solid #f1f5f9;">
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
