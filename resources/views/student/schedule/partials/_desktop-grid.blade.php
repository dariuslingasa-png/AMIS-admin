<!-- COLOR LEGEND BAR -->
<div class="calendar-color-legend" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0.65rem 1rem; margin-bottom: 1rem; font-size: 11.5px; font-weight: 750; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
    <span style="color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 850; letter-spacing: 0.05em; display: inline-flex; align-items: center; gap: 0.25rem; margin-right: 0.25rem;">
        <i data-lucide="palette" style="width: 13px; height: 13px; color: #0d9488;"></i> Subject Colors:
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #064e3b; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #059669;"></span> Islamic & Arabic
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #1e1b4b; background: #eef2ff; border: 1px solid #c7d2fe; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #4f46e5;"></span> Math
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #3b0764; background: #faf5ff; border: 1px solid #e9d5ff; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #9333ea;"></span> Science
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #0c4a6e; background: #f0f9ff; border: 1px solid #bae6fd; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #0284c7;"></span> English & Reading
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #d97706;"></span> Filipino & Values
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #7c2d12; background: #fff7ed; border: 1px solid #fed7aa; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #ea580c;"></span> AP / Social Sci
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #881337; background: #fff1f2; border: 1px solid #fecdd3; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #e11d48;"></span> MAPEH
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #134e4a; background: #f0fdfa; border: 1px solid #99f6e4; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #0d9488;"></span> TLE & ICT
    </span>
    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #831843; background: #fdf2f8; border: 1px solid #fbcfe8; padding: 0.2rem 0.6rem; border-radius: 999px;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: #db2777;"></span> Circle / Early Childhood
    </span>
</div>

<!-- DESKTOP TIMETABLE VIEW -->
<div :class="isFullscreen ? 'calendar-wrapper is-fullscreen' : 'calendar-wrapper'">
     <!-- Fullscreen Toggle Button -->
     <div style="display: flex; justify-content: flex-end; margin-bottom: 0.75rem;">
         <button type="button" @click="isFullscreen = !isFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                 class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 px-3.5 py-1.5 rounded-xl transition cursor-pointer shadow-3xs"
                 style="font-size: 13.5px; font-weight: 700; line-height: 20px;">
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
                $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
            @endphp
            <div class="calendar-grid-row">
                <!-- Time column cell -->
                <div class="calendar-time-block" style="background: #f8fafc; border-right: 2px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.65rem 0.35rem;">
                     <span style="font-size: 14px; font-weight: 850; color: #0f172a; line-height: 1.2;">{{ date('g:i A', strtotime($row['slot']['start'])) }}</span>
                     <span style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin: 0.15rem 0; letter-spacing: 0.05em;">to</span>
                     <span style="font-size: 14px; font-weight: 850; color: #0f172a; line-height: 1.2;">{{ date('g:i A', strtotime($row['slot']['end'])) }}</span>
                 </div>

                 @php
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
                                 $teacher1 = !empty($class->teacher_display) ? $class->teacher_display : ($class->teacher_name ?? '');
                                 $teacher2 = !empty($nextClass->teacher_display) ? $nextClass->teacher_display : ($nextClass->teacher_name ?? '');
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
                         
                         // Determine day label for popup
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

                         $rawSubj = $s ? strtolower($s->subject_name) : '';
                         $isRecess = str_contains($rawSubj, 'recess');
                         $isAssembly = str_contains($rawSubj, 'assembly');
                         $isSalah = str_contains($rawSubj, 'salah') || str_contains($rawSubj, 'departure') || str_contains($rawSubj, 'lunch');
                         $isTransition = str_contains($rawSubj, 'transition') || str_contains($rawSubj, 'short break') || str_contains($rawSubj, 'break');
                         $isSpecialWord = $s && ($isRecess || $isAssembly || $isSalah || $isTransition);
                     @endphp

                     <div class="calendar-cell" style="{{ $span > 1 ? 'grid-column: span ' . $span . ';' : '' }}">
                         @if($s)
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
                                 <div class="calendar-class-card" 
                                      style="width: 100%; min-height: 85px; background: {{ $specialBg }} !important; border: 1.5px dashed {{ $specialBorder }} !important; color: {{ $specialText }} !important; display: flex; align-items: center; justify-content: center; text-align: center; border-radius: 14px;" 
                                      title="{{ $s->subject_name }}">
                                     <div style="display: flex; align-items: center; gap: 0.4rem; justify-content: center; text-align: center; width: 100%; padding: 0.5rem;">
                                         <i data-lucide="{{ $specialIcon }}" style="width: 15px; height: 15px; flex-shrink: 0;"></i>
                                         <p style="font-size: 13.5px !important; font-weight: 800 !important; line-height: 1.25 !important; margin: 0; text-transform: uppercase;">{{ $s->subject_name }}</p>
                                     </div>
                                 </div>
                             @else
                                 @php
                                     $currentTeacherName = $teacherName($s);
                                     $photoUrl = $getPhotoUrl($s->teacher_photo ?? null, $s->teacher_key ?? null, $s->teacher_display ?: ($s->teacher_name ?? ''));
                                     $style = $getSubjectStyle($s->subject_name);
                                 @endphp
                                 <div class="calendar-class-card"
                                      style="background: {{ $style['bg'] }} !important; 
                                             border: 1.5px solid {{ $style['border'] }} !important; 
                                             color: {{ $style['text'] }} !important; 
                                             box-shadow: 0 1px 3px rgba(0,0,0,0.03); 
                                             display: flex; flex-direction: row; gap: 0.45rem; align-items: center; 
                                             border-radius: 12px;
                                             box-sizing: border-box;
                                             width: 100%;
                                             overflow: hidden;
                                             {{ $span > 1 ? 'justify-content: center; text-align: center; padding: 0.65rem 1.25rem;' : 'padding: 0.5rem 0.65rem;' }}">
                                     
                                     <!-- Left: Teacher photo -->
                                     <div @if($photoUrl) @click="previewPhoto = { url: '{{ $photoUrl }}', name: '{{ $currentTeacherName }}', role: 'Official Teacher', subject: '{{ $s->subject_name }}', time: '{{ date('g:i A', strtotime($s->start_time)) }} - {{ date('g:i A', strtotime($s->end_time)) }}', day: '{{ $dayLabel }}' }" @endif
                                          style="width: 34px; height: 34px; border-radius: 50%; background: white; border: 1.5px solid {{ $style['border'] }} !important; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05);"
                                          title="Click to view teacher details">
                                         @if($photoUrl)
                                             <img src="{{ $photoUrl }}" alt="{{ $currentTeacherName }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                         @else
                                             @php
                                                 $initials = collect(explode(' ', str_ireplace(['TEACHER ', 'TCHR. ', 'USTADH ', 'USTADZ ', 'USTADHA ', 'ALIM '], '', $currentTeacherName)))
                                                     ->filter()->map(fn($part) => strtoupper(substr($part, 0, 1)))->take(2)->implode('');
                                             @endphp
                                             <span style="font-size: 0.7rem; font-weight: 850; color: {{ $style['accent'] }} !important;">{{ $initials ?: '?' }}</span>
                                         @endif
                                     </div>

                                     <!-- Middle / Right: Subject and Teacher Details -->
                                     <div style="{{ $span > 1 ? 'display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;' : 'flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: center; overflow: hidden;' }}">
                                         <h4 style="font-size: 13.5px; font-weight: 850; line-height: 1.2; color: {{ $style['text'] }} !important; margin: 0; letter-spacing: 0.01em; {{ $span > 1 ? 'text-align: center;' : 'overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left;' }}" title="{{ $s->subject_name }}">
                                              {{ $s->subject_name }}
                                         </h4>
                                         
                                         <!-- Teacher Badge with safe padding and no overflow -->
                                         <div style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 11px; font-weight: 750; color: {{ $style['badge_text'] }}; background: {{ $style['badge_bg'] }}; border: 1px solid {{ $style['border'] }}; border-radius: 5px; padding: 0.1rem 0.4rem; margin-top: 0.2rem; max-width: 100%; box-sizing: border-box; overflow: hidden; {{ $span > 1 ? 'margin: 0.25rem auto 0;' : 'width: fit-content;' }}" title="Teacher: {{ $currentTeacherName }}">
                                             <i data-lucide="user" style="width: 10px; height: 10px; flex-shrink: 0; opacity: 0.85;"></i>
                                             <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">{{ $currentTeacherName }}</span>
                                         </div>
                                     </div>
                                 </div>
                             @endif
                         @else
                             <div style="flex: 1; border: 1px dashed #cbd5e1; border-radius: 14px; background: #fafbfc; min-height: 85px;"></div>
                         @endif
                     </div>
                 @endforeach
            </div>
        @endforeach
    </div>
</div>
