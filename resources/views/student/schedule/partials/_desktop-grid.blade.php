<!-- DESKTOP TIMETABLE VIEW -->
<div :class="isFullscreen ? 'calendar-wrapper is-fullscreen' : 'calendar-wrapper'">
     <!-- Fullscreen Toggle Button -->
     <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
         <button type="button" @click="isFullscreen = !isFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                 class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 px-3.5 py-2 rounded-xl transition cursor-pointer shadow-3xs"
                 style="font-size: 15px; font-weight: 600; line-height: 22px;">
             <template x-if="!isFullscreen">
                 <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                     <i data-lucide="maximize-2" style="width: 14px; height: 14px;"></i> Full Screen
                 </span>
             </template>
             <template x-if="isFullscreen">
                 <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                     <i data-lucide="minimize-2" style="width: 14px; height: 14px;"></i> Exit Full Screen
                 </span>
             </template>
         </button>
     </div>

    <div class="calendar-grid">
        <!-- Headers -->
        <div class="calendar-grid-header calendar-time-header">Time Block</div>
        <div class="calendar-grid-header">Sunday</div>
        <div class="calendar-grid-header">Monday</div>
        <div class="calendar-grid-header">Tuesday</div>
        <div class="calendar-grid-header">Wednesday</div>
        <div class="calendar-grid-header">Thursday</div>

        <!-- Matrix Rows -->
        @foreach($matrix as $timeKey => $row)
            @php
                $startMin = $row['slot']['start'];
                $endMin = $row['slot']['end'];
                $duration = (strtotime($endMin) - strtotime($startMin)) / 60;

                // Check if all 5 days have the exact same subject
                $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                $firstDayClass = $row['days'][$daysList[0]];
                $allSame = true;
                if (!$firstDayClass) {
                    $allSame = false;
                } else {
                    foreach ($daysList as $d) {
                        $curr = $row['days'][$d];
                        if (!$curr || $curr->subject_name !== $firstDayClass->subject_name) {
                            $allSame = false;
                            break;
                        }
                    }
                }
            @endphp
            <div class="calendar-grid-row">
                <!-- Time column cell -->
                <div class="calendar-time-block">
                     <span style="font-size: 14px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ date('g:i A', strtotime($row['slot']['start'])) }}</span>
                     <span style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin: 0.15rem 0; letter-spacing: 0.05em;">to</span>
                     <span style="font-size: 14px; font-weight: 800; color: #0f172a; line-height: 1.2;">{{ date('g:i A', strtotime($row['slot']['end'])) }}</span>
                 </div>

                @if($allSame)
                    <!-- MERGED CELL SPANNING 5 COLUMNS -->
                    @php
                        $s = $firstDayClass;
                        
                        // Calculate live/completed status
                        $classState = 'upcoming';
                        $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                        $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                        if ($startTime !== false && $endTime !== false) {
                            $now = time();
                            // Check if today matches any school day since it's on all days
                            if (in_array($todayName, $daysList)) {
                                if ($now > $endTime) {
                                    $classState = 'completed';
                                } elseif ($now >= $startTime && $now <= $endTime) {
                                    $classState = 'live';
                                }
                            }
                        }

                        $isSpecialWord = str_contains(strtolower($s->subject_name), 'transition') || 
                                         str_contains(strtolower($s->subject_name), 'recess') || 
                                         str_contains(strtolower($s->subject_name), 'break') ||
                                         str_contains(strtolower($s->subject_name), 'general assembly');
                    @endphp
                    <div class="calendar-cell" style="grid-column: span 5;">
                        @if($isSpecialWord)
                            <div class="calendar-class-card class-special" style="width: 100%;" title="{{ $s->subject_name }}">
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <i data-lucide="coffee" style="width: 16px; height: 16px; color: #64748b; flex-shrink: 0;"></i>
                                    <p class="class-special-title" style="font-size: 15px !important; font-weight: 800 !important; line-height: 1.25 !important;">{{ $s->subject_name }}</p>
                                </div>
                            </div>
                        @else
                            @php
                                $currentTeacherName = $teacherName($s);
                                $photoUrl = $getPhotoUrl($s->teacher_photo, $s->teacher_key, $s->teacher_display ?: $s->teacher_name);
                                $style = $getSubjectStyle($s->subject_name);
                            @endphp
                            <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}"
                                 style="background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.75rem; align-items: center; padding: 0.65rem 1rem;">
                                
                                <!-- Left: Teacher photo in squircle (border-radius: 12px) -->
                                <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: 'Sunday - Thursday' }" @endif
                                     style="width: 44px; height: 44px; border-radius: 10px; background: white; border: 1px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;"
                                     title="Click to view teacher photo">
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        @php
                                            $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                 ->take(2)
                                                ->implode('');
                                        @endphp
                                        <span style="font-size: 0.75rem; font-weight: 850; color: {{ $style['text'] }} !important;">{{ $initials ?: '?' }}</span>
                                    @endif
                                </div>

                                <!-- Right: Details -->
                                <div style="flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <h4 style="font-size: 15px; font-weight: 800; line-height: 1.25; color: {{ $style['text'] }} !important; margin: 0;">
                                            {{ $s->subject_name }}
                                        </h4>
                                        <p style="font-size: 13px; font-weight: 600; line-height: 1.35; color: {{ $style['text'] }} !important; opacity: 0.9; margin: 0.05rem 0 0;">
                                            {{ $currentTeacherName }}
                                        </p>
                                    </div>

                                    @if($s->ms_channel_id)
                                         @if($s->is_joinable)
                                             <a href="{{ $s->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $s->team_url ?? 'https://teams.microsoft.com/' }}');" style="width: 24px; height: 24px; border-radius: 50%; background: {{ $style['icon_color'] }}; display: inline-flex; align-items: center; justify-content: center; color: white; text-decoration: none; cursor: pointer;" title="Join Class">
                                                 <i data-lucide="video" style="width: 11px; height: 11px;"></i>
                                             </a>
                                         @else
                                             <button type="button" disabled style="width: 24px; height: 24px; border-radius: 50%; background: #cbd5e1; border: none; display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: not-allowed;" title="{{ $s->membership_status_label }}">
                                                 <i data-lucide="lock" style="width: 11px; height: 11px;"></i>
                                             </button>
                                         @endif
                                     @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- INDIVIDUAL COLUMN CELLS -->
                    @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'] as $day)
                        @php
                            $s = $row['days'][$day];
                            
                            // Calculate live/completed status
                            $classState = 'upcoming';
                            if ($s) {
                                $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                                $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                                if ($startTime !== false && $endTime !== false) {
                                    $now = time();
                                    if ($day === $todayName) {
                                        if ($now > $endTime) {
                                            $classState = 'completed';
                                        } elseif ($now >= $startTime && $now <= $endTime) {
                                            $classState = 'live';
                                        }
                                    }
                                }
                            }

                            $isSpecialWord = $s && (
                                str_contains(strtolower($s->subject_name), 'transition') || 
                                str_contains(strtolower($s->subject_name), 'recess') || 
                                str_contains(strtolower($s->subject_name), 'break') ||
                                str_contains(strtolower($s->subject_name), 'general assembly')
                            );
                        @endphp

                        <div class="calendar-cell">
                            @if($s)
                                @if($isSpecialWord)
                                    <div class="calendar-class-card class-special" title="{{ $s->subject_name }}">
                                        <div style="display: flex; align-items: center; gap: 0.35rem; justify-content: center; text-align: center;">
                                            <i data-lucide="coffee" style="width: 14px; height: 14px; color: #94a3b8; flex-shrink: 0;"></i>
                                            <p class="class-special-title" style="font-size: 15px !important; font-weight: 800 !important; line-height: 1.25 !important;">{{ $s->subject_name }}</p>
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $currentTeacherName = $teacherName($s);
                                        $photoUrl = $getPhotoUrl($s->teacher_photo, $s->teacher_key, $s->teacher_display ?: $s->teacher_name);
                                        $style = $getSubjectStyle($s->subject_name);
                                    @endphp
                                    <div class="calendar-class-card {{ $classState === 'completed' ? 'class-completed' : ($classState === 'live' ? 'class-live' : '') }}"
                                         style="background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.5rem; align-items: center;">
                                        
                                        <!-- Left: Teacher photo in circle -->
                                        <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: '{{ $day }}' }" @endif
                                             style="width: 44px; height: 44px; border-radius: 50%; background: white; border: 1.5px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;"
                                             title="Click to view teacher photo">
                                            @if($photoUrl)
                                                <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            @else
                                                @php
                                                    $initials = collect(explode(' ', str_ireplace('TEACHER ', '', $currentTeacherName)))
                                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                        ->take(2)
                                                        ->implode('');
                                                @endphp
                                                <span style="font-size: 0.75rem; font-weight: 850; color: {{ $style['text'] }} !important; display: flex; align-items: center; justify-content: center; text-align: center; width: 100%; height: 100%;">{{ $initials ?: '?' }}</span>
                                            @endif
                                        </div>

                                        <!-- Right: Details -->
                                        <div style="flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between;">
                                            <div style="min-width: 0; display: flex; flex-direction: column; justify-content: center; text-align: left;">
                                                <h4 style="font-size: 15px; font-weight: 800; line-height: 1.25; color: {{ $style['text'] }} !important; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;" title="{{ $s->subject_name }}">
                                                     {{ $s->subject_name }}
                                                </h4>
                                                <p style="font-size: 13px; font-weight: 600; line-height: 1.35; color: {{ $style['text'] }} !important; opacity: 0.9; margin: 0.05rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;" title="{{ $currentTeacherName }}">
                                                     {{ $currentTeacherName }}
                                                </p>
                                            </div>
                                            @if($s->ms_channel_id)
                                                <div style="display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-left: 0.25rem;">
                                                    @if($s->is_joinable)
                                                        <a href="{{ $s->team_url ?? 'https://teams.microsoft.com/' }}" onclick="event.preventDefault(); window.joinTeams('{{ $s->team_url ?? 'https://teams.microsoft.com/' }}');" style="width: 20px; height: 20px; border-radius: 50%; background: {{ $style['icon_color'] }}; display: inline-flex; align-items: center; justify-content: center; color: white; transition: background 0.15s; text-decoration: none; cursor: pointer;" title="Join Class">
                                                            <i data-lucide="video" style="width: 9px; height: 9px;"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" disabled style="width: 20px; height: 20px; border-radius: 50%; background: #cbd5e1; border: none; display: inline-flex; align-items: center; justify-content: center; color: #64748b; cursor: not-allowed;" title="{{ $s->membership_status_label }}">
                                                            <i data-lucide="lock" style="width: 9px; height: 9px;"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div style="flex: 1; border: 1px dashed #cbd5e1; border-radius: 16px; background: #fafbfc; min-height: 85px;"></div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
</div>
