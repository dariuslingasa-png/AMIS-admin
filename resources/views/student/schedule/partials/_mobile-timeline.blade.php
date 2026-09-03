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

                    $rawSubj = strtolower($s->subject_name);
                    $isRecess = str_contains($rawSubj, 'recess');
                    $isAssembly = str_contains($rawSubj, 'assembly');
                    $isSalah = str_contains($rawSubj, 'salah') || str_contains($rawSubj, 'departure') || str_contains($rawSubj, 'lunch');
                    $isTransition = str_contains($rawSubj, 'transition') || str_contains($rawSubj, 'short break') || str_contains($rawSubj, 'break');
                    $isSpecialWord = ($isRecess || $isAssembly || $isSalah || $isTransition);
                @endphp

                <div class="mobile-timeline-item">
                    <!-- Left Time slot -->
                    <div class="mobile-time">
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
                                    $specialIcon = 'coffee';
                                } elseif ($isAssembly) {
                                    $specialIcon = 'flag';
                                    $specialText = '#0f172a';
                                } elseif ($isSalah) {
                                    $specialIcon = 'sun';
                                    $specialText = '#065f46';
                                }
                            @endphp
                            <div style="background: {{ $specialBg }}; border: 1.5px dashed {{ $specialBorder }}; border-radius: 12px; padding: 0.85rem; display: flex; align-items: center; justify-content: center; height: 100%; color: {{ $specialText }};">
                                <div style="display: flex; align-items: center; gap: 0.4rem; justify-content: center; text-align: center;">
                                    <i data-lucide="{{ $specialIcon }}" style="width: 14px; height: 14px;"></i>
                                    <p style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin: 0;">{{ $s->subject_name }}</p>
                                </div>
                            </div>
                        @else
                            @php
                                $style = $getSubjectStyle($s->subject_name);
                                $currentTeacherName = $teacherName($s);
                                $photoUrl = $getPhotoUrl($s->teacher_photo ?? null, $s->teacher_key ?? null, $s->teacher_display ?: ($s->teacher_name ?? ''));
                            @endphp
                            <div class="calendar-class-card" 
                                 style="min-height: 80px; background: {{ $style['bg'] }} !important; border: 1.5px solid {{ $style['border'] }} !important; border-left: 4.5px solid {{ $style['accent'] }} !important; display: flex; flex-direction: row; gap: 0.45rem; align-items: center; border-radius: 12px; padding: 0.5rem 0.65rem; width: 100%; box-sizing: border-box; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                
                                <!-- Left: Teacher photo in circle -->
                                <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: '{{ $dayName }}' }" @endif
                                     style="width: 34px; height: 34px; border-radius: 50%; background: #ffffff; border: 2px solid {{ $style['accent'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.04);"
                                     title="Click to view teacher details">
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    @else
                                        @php
                                            $initials = collect(explode(' ', str_ireplace(['TEACHER ', 'TCHR. ', 'USTADH ', 'USTADZ ', 'USTADHA ', 'ALIM '], '', $currentTeacherName)))
                                                ->filter()->map(fn($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                                        @endphp
                                        <span style="font-size: 0.7rem; font-weight: 850; color: {{ $style['accent'] }};">{{ $initials ?: '?' }}</span>
                                    @endif
                                </div>

                                <!-- Right: Subject details -->
                                <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; overflow: hidden;">
                                    <h4 style="font-size: 13.5px; font-weight: 850; color: {{ $style['text'] }} !important; margin: 0; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $s->subject_name }}">
                                        {{ $s->subject_name }}
                                    </h4>
                                    
                                    <div style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 11px; font-weight: 750; color: {{ $style['badge_text'] }}; background: {{ $style['badge_bg'] }}; border: 1px solid {{ $style['border'] }}; border-radius: 5px; padding: 0.1rem 0.4rem; margin-top: 0.2rem; max-width: 100%; box-sizing: border-box; overflow: hidden; width: fit-content;">
                                        <i data-lucide="user" style="width: 10px; height: 10px; flex-shrink: 0; color: {{ $style['accent'] }};"></i>
                                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">{{ $currentTeacherName }}</span>
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
