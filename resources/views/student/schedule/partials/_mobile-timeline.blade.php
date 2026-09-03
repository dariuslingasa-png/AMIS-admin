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
                $dayClasses = !empty($weeklySchedule[$dayName]) ? $weeklySchedule[$dayName] : ($days[$dayName] ?? []);
            @endphp

            @forelse($dayClasses as $cls)
                @php
                    $s = is_array($cls) ? ($cls['subject'] ?? (object)$cls) : ($cls->subject ?? $cls);
                    
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

                    $rawSubj = strtolower($s->subject_name);
                    $isRecess = str_contains($rawSubj, 'recess');
                    $isAssembly = str_contains($rawSubj, 'assembly');
                    $isSalah = str_contains($rawSubj, 'salah') || str_contains($rawSubj, 'departure') || str_contains($rawSubj, 'lunch');
                    $isTransition = str_contains($rawSubj, 'transition') || str_contains($rawSubj, 'short break') || str_contains($rawSubj, 'break');
                    $isSpecialWord = ($isRecess || $isAssembly || $isSalah || $isTransition);
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
                            @php
                                $specialBg = '#f8fafc';
                                $specialBorder = '#cbd5e1';
                                $specialText = '#475569';
                                $specialIcon = 'coffee';
                                if ($isRecess) {
                                    $specialBg = '#fffbeb';
                                    $specialBorder = '#fde68a';
                                    $specialText = '#78350f';
                                    $specialIcon = 'coffee';
                                } elseif ($isAssembly) {
                                    $specialBg = '#eff6ff';
                                    $specialBorder = '#bfdbfe';
                                    $specialText = '#1e40af';
                                    $specialIcon = 'flag';
                                } elseif ($isSalah) {
                                    $specialBg = '#ecfdf5';
                                    $specialBorder = '#a7f3d0';
                                    $specialText = '#065f46';
                                    $specialIcon = 'sun';
                                }
                            @endphp
                            <div style="background: {{ $specialBg }}; border: 1.5px dashed {{ $specialBorder }}; border-radius: 14px; padding: 0.85rem; display: flex; align-items: center; justify-content: center; height: 100%; color: {{ $specialText }};">
                                <div style="display: flex; align-items: center; gap: 0.4rem; justify-content: center; text-align: center;">
                                    <i data-lucide="{{ $specialIcon }}" style="width: 14px; height: 14px;"></i>
                                    <p style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin: 0;">{{ $s->subject_name }}</p>
                                </div>
                            </div>
                        @else
                            @php
                                $currentTeacherName = $teacherName($s);
                                $photoUrl = $getPhotoUrl($s->teacher_photo ?? null, $s->teacher_key ?? null, $s->teacher_display ?: ($s->teacher_name ?? ''));
                                $style = $getSubjectStyle($s->subject_name);
                            @endphp
                            <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}" 
                                 style="min-height: 85px; background: {{ $style['bg'] }} !important; border: 1.5px solid {{ $style['border'] }} !important; border-left: 5px solid {{ $style['accent'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.65rem; align-items: center; border-radius: 14px; padding: 0.65rem 0.75rem;">
                                
                                <!-- Left: Teacher photo in circle with accent ring -->
                                <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: '{{ $dayName }}' }" @endif
                                     style="width: 40px; height: 40px; border-radius: 50%; background: white; border: 2.5px solid {{ $style['accent'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.06);">
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    @else
                                        @php
                                            $initials = collect(explode(' ', str_ireplace(['TEACHER ', 'TCHR. ', 'USTADH ', 'USTADZ ', 'USTADHA '], '', $currentTeacherName)))
                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                ->take(2)
                                                ->implode('');
                                        @endphp
                                        <span style="font-size: 0.75rem; font-weight: 850; color: {{ $style['accent'] }} !important; display: flex; align-items: center; justify-content: center; text-align: center; width: 100%; height: 100%;">{{ $initials ?: '?' }}</span>
                                    @endif
                                </div>

                                <!-- Right: Subject details -->
                                <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center;">
                                    <h4 style="font-size: 14px; font-weight: 850; color: {{ $style['text'] }} !important; margin: 0; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $s->subject_name }}">
                                        {{ $s->subject_name }}
                                    </h4>
                                    
                                    <div style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 11px; font-weight: 750; color: {{ $style['badge_text'] }}; background: {{ $style['badge_bg'] }}; border: 1px solid {{ $style['border'] }}; border-radius: 6px; padding: 0.1rem 0.45rem; margin-top: 0.25rem; width: fit-content;">
                                        <i data-lucide="user" style="width: 10px; height: 10px;"></i>
                                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 140px;">{{ $currentTeacherName }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="display: flex; min-height: 8rem; align-items: center; justify-content: center; border-radius: 16px; border: 1.5px dashed #e2e8f0; background: #f8fafc; font-size: 0.85rem; font-weight: 750; color: #94a3b8; text-align: center;">
                    No classes scheduled for {{ $dayName }}
                </div>
            @endforelse
        </div>
    @endforeach
</div>
