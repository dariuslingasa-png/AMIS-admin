<!-- MOBILE/TABLET TIMETABLE VIEW (Tab Switching + Timeline list) -->
<div class="mobile-timeline space-y-4">
    <!-- Day selection tabs -->
    <div style="display: flex; overflow-x: auto; background: #f1f5f9; padding: 0.35rem; border-radius: 16px; gap: 0.25rem;">
        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
            <button type="button" 
                    @click="activeDay = '{{ $dayName }}'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                    :class="activeDay === '{{ $dayName }}' ? 'day-tab-btn active' : 'day-tab-btn'"
                    style="flex: 1; text-align: center; white-space: nowrap;">
                {{ substr($dayName, 0, 3) }}
            </button>
        @endforeach
    </div>

    <!-- Daily Classes Timeline -->
    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $dayName)
        <div x-show="activeDay === '{{ $dayName }}'" class="space-y-4 fade-up">
            @php
                $dayClasses = $days[$dayName] ?? [];
            @endphp

            @forelse($dayClasses as $cls)
                @php
                    $s = $cls['subject'];
                    
                    // Calculate live/completed status
                    $classState = 'upcoming';
                    $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                    $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                    if ($startTime !== false && $endTime !== false) {
                        $now = time();
                        if ($dayName === $todayName) {
                            if ($now > $endTime) {
                                $classState = 'completed';
                            } elseif ($now >= $startTime && $now <= $endTime) {
                                $classState = 'live';
                            }
                        }
                    }

                    $isSpecialWord = (
                        str_contains(strtolower($s->subject_name), 'transition') || 
                        str_contains(strtolower($s->subject_name), 'recess') || 
                        str_contains(strtolower($s->subject_name), 'break') ||
                        str_contains(strtolower($s->subject_name), 'general assembly')
                    );
                @endphp

                <div class="mobile-timeline-item">
                    <!-- Left Time slot -->
                    <div class="mobile-time {{ $classState === 'live' ? 'live-time' : '' }}">
                        <span style="color: #0f172a; font-weight: 900;">{{ date('g:i A', strtotime($s->start_time)) }}</span>
                        <span style="font-size: 0.6rem; color: #94a3b8; font-weight: 700; margin: 0.05rem 0;">to</span>
                        <span style="color: #64748b; font-weight: 750;">{{ date('g:i A', strtotime($s->end_time)) }}</span>
                    </div>

                    <!-- Right Content card -->
                    <div style="flex: 1; min-width: 0;">
                        @if($isSpecialWord)
                            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 0.85rem; display: flex; align-items: center; justify-content: center; height: 100%;">
                                <p style="font-size: 0.8rem; font-weight: 850; color: #64748b; margin: 0;">{{ $s->subject_name }}</p>
                            </div>
                        @else
                            @php
                                $currentTeacherName = $teacherName($s);
                                $photoUrl = $getPhotoUrl($s->teacher_photo, $s->teacher_key, $s->teacher_display ?: $s->teacher_name);
                                $style = $getSubjectStyle($s->subject_name);
                            @endphp
                            <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}" 
                                 style="min-height: 85px; background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.5rem; align-items: center;">
                                
                                <!-- Left: Teacher photo in circle -->
                                <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: '{{ $dayName }}' }" @endif
                                     style="width: 38px; height: 38px; border-radius: 50%; background: white; border: 1.5px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;">
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    @else
                                        @php
                                            $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                ->take(2)
                                                ->implode('');
                                        @endphp
                                        <span style="font-size: 0.7rem; font-weight: 850; color: {{ $style['text'] }} !important; display: flex; align-items: center; justify-content: center; text-align: center; width: 100%; height: 100%;">{{ $initials ?: '?' }}</span>
                                    @endif
                                </div>

                                <!-- Right: Subject details -->
                                <div style="flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <h4 style="font-size: 0.9rem; font-weight: 850; color: {{ $style['text'] }} !important; margin: 0; line-height: 1.3;">
                                            {{ $s->subject_name }}
                                        </h4>
                                        <p style="font-size: 0.75rem; font-weight: 750; color: {{ $style['text'] }} !important; opacity: 0.85; margin: 0.2rem 0 0;">
                                            {{ $currentTeacherName }}
                                        </p>
                                    </div>

                                    @if($s->ms_channel_id)
                                         @if($s->is_joinable)
                                             <a href="{{ $s->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $s->team_url ?? 'https://teams.microsoft.com/' }}');" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 900; color: white; background: {{ $style['icon_color'] }}; padding: 0.35rem 0.75rem; border-radius: 10px; text-decoration: none; cursor: pointer;"
                                                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                                 <i data-lucide="video" style="width: 12px; height: 12px;"></i>
                                                 <span>Join Room</span>
                                             </a>
                                         @else
                                             <button type="button" disabled style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 900; color: #94a3b8; background: #cbd5e1; border: none; padding: 0.35rem 0.75rem; border-radius: 10px; cursor: not-allowed; opacity: 0.85;"
                                                title="{{ $s->membership_status_label }}">
                                                 <i data-lucide="lock" style="width: 12px; height: 12px;"></i>
                                                 <span>Join Room</span>
                                             </button>
                                         @endif
                                     @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="display: flex; min-height: 8rem; align-items: center; justify-content: center; border-radius: 16px; border: 1.5px dashed #e2e8f0; background: #f8fafc; font-size: 0.8rem; font-weight: 750; color: #94a3b8; text-align: center;">
                    No classes scheduled for {{ $dayName }}
                </div>
            @endforelse
        </div>
    @endforeach
</div>
