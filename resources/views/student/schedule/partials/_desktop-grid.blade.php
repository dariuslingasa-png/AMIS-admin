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

                 @php
                     $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                     $groups = [];
                     $i = 0;
                     while ($i < 5) {
                         $day = $daysList[$i];
                         $class = $row['days'][$day];
                         
                         if (!$class) {
                             $span = 1;
                             $groups[] = [
                                 'day' => $day,
                                 'span' => $span,
                                 'class' => null
                             ];
                             $i++;
                             continue;
                         }
                         
                         $span = 1;
                         $j = $i + 1;
                         while ($j < 5) {
                             $nextDay = $daysList[$j];
                             $nextClass = $row['days'][$nextDay];
                             
                             if ($nextClass) {
                                 $sameSubject = $nextClass->subject_name === $class->subject_name;
                                 
                                 $teacher1 = !empty($class->teacher_display) ? $class->teacher_display : $class->teacher_name;
                                 $teacher2 = !empty($nextClass->teacher_display) ? $nextClass->teacher_display : $nextClass->teacher_name;
                                 $sameTeacher = $teacher1 === $teacher2;
                                 
                                 if ($sameSubject && $sameTeacher) {
                                     $span++;
                                     $j++;
                                 } else {
                                     break;
                                 }
                             } else {
                                 break;
                             }
                         }
                         
                         $groups[] = [
                             'day' => $day,
                             'span' => $span,
                             'class' => $class
                         ];
                         
                         $i = $j;
                     }
                 @endphp

                 @foreach($groups as $group)
                     @php
                         $span = $group['span'];
                         $s = $group['class'];
                         $day = $group['day'];
                         
                         // Determine date-based day label for popup preview modal
                         $dayLabel = $day;
                         if ($span > 1) {
                             $startIndex = array_search($day, $daysList);
                             $endDay = $daysList[$startIndex + $span - 1];
                             $dayLabel = $day . ' - ' . $endDay;
                         }
                         
                         // Calculate live/completed status
                         $classState = 'upcoming';
                         if ($s) {
                             $startTime = strtotime(date('Y-m-d') . ' ' . $s->start_time);
                             $endTime = strtotime(date('Y-m-d') . ' ' . $s->end_time);
                             if ($startTime !== false && $endTime !== false) {
                                 $now = time();
                                 // Check if today falls within any of the days in the spanned range
                                 $spannedDays = array_slice($daysList, array_search($day, $daysList), $span);
                                 if (in_array($todayName, $spannedDays)) {
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

                     <div class="calendar-cell" style="{{ $span > 1 ? 'grid-column: span ' . $span . ';' : '' }}">
                         @if($s)
                             @if($isSpecialWord)
                                 <div class="calendar-class-card class-special" style="width: 100%;" title="{{ $s->subject_name }}">
                                     <div style="display: flex; align-items: center; gap: 0.35rem; justify-content: center; text-align: center; width: 100%;">
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
                                      style="background: {{ $style['bg'] }} !important; border-color: {{ $style['border'] }} !important; color: {{ $style['text'] }} !important; display: flex; flex-direction: row; gap: 0.5rem; align-items: center; {{ $span > 1 ? 'justify-content: center; text-align: center; padding: 0.65rem 1rem;' : '' }}">
                                     
                                     <!-- Left: Teacher photo in squircle or circle -->
                                     <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: '{{ $dayLabel }}' }" @endif
                                          style="width: 44px; height: 44px; border-radius: {{ $span > 1 ? '10px' : '50%' }}; background: white; border: 1.5px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer;"
                                          title="Click to view teacher photo">
                                         @if($photoUrl)
                                             <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: {{ $span > 1 ? '10px' : '50%' }};">
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
                                     <div style="{{ $span > 1 ? 'display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;' : 'flex: 1; min-width: 0; display: flex; align-items: center; justify-content: space-between;' }}">
                                         <div style="min-width: 0; display: flex; flex-direction: column; justify-content: center; {{ $span > 1 ? 'text-align: center;' : 'text-align: left;' }}">
                                             <h4 style="font-size: 15px; font-weight: 800; line-height: 1.25; color: {{ $style['text'] }} !important; margin: 0; {{ $span > 1 ? 'text-align: center;' : 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;' }}" title="{{ $s->subject_name }}">
                                                  {{ $s->subject_name }}
                                             </h4>
                                             <p style="font-size: 13px; font-weight: 600; line-height: 1.35; color: {{ $style['text'] }} !important; opacity: 0.9; margin: 0.05rem 0 0; {{ $span > 1 ? 'text-align: center;' : 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;' }}" title="{{ $currentTeacherName }}">
                                                  {{ $currentTeacherName }}
                                             </p>
                                         </div>
                                     </div>
                                 </div>
                             @endif
                         @else
                             <div style="flex: 1; border: 1px dashed #cbd5e1; border-radius: 16px; background: #fafbfc; min-height: 85px;"></div>
                         @endif
                     </div>
                 @endforeach
            </div>
        @endforeach
    </div>
</div>
