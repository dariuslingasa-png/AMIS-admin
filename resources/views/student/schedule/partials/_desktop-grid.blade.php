<!-- DESKTOP TIMETABLE VIEW (Official AMIS Layout) -->
<div :class="isFullscreen ? 'calendar-wrapper is-fullscreen' : 'calendar-wrapper'" style="background: transparent; border: none; padding: 0; box-shadow: none;">
    
    <!-- Top Green Section Banner Header (Matches Reference Design) -->
    <div style="background: #064e3b; color: #ffffff; padding: 0.85rem 1.25rem; border-radius: 16px 16px 0 0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 900; letter-spacing: 0.02em; text-transform: uppercase; color: #ffffff;">
                {{ strtoupper($studentInfo['official_section_name'] ?: $studentInfo['section']) }}
            </h3>
        </div>
        
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <span style="font-size: 0.75rem; font-weight: 750; background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; padding: 0.25rem 0.85rem; border-radius: 999px; letter-spacing: 0.02em;">
                {{ $studentInfo['grade_level'] }} • {{ $studentInfo['shift'] ?: $studentInfo['modality'] }}
            </span>
            <button type="button" @click="isFullscreen = !isFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                    style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; padding: 0.35rem 0.65rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; font-weight: 700; transition: all 0.15s ease;">
                <template x-if="!isFullscreen">
                    <span style="display: inline-flex; align-items: center; gap: 0.3rem;"><i data-lucide="maximize-2" style="width: 13px; height: 13px;"></i> Full Screen</span>
                </template>
                <template x-if="isFullscreen">
                    <span style="display: inline-flex; align-items: center; gap: 0.3rem;"><i data-lucide="minimize-2" style="width: 13px; height: 13px;"></i> Exit</span>
                </template>
            </button>
        </div>
    </div>

    <!-- Official Timetable Grid Table -->
    <div style="overflow-x: auto; border: 1.5px solid #064e3b; border-top: none; background: #ffffff;">
        <table style="width: 100%; border-collapse: collapse; min-width: 900px; text-align: left;">
            <thead>
                <tr style="background: #064e3b; color: #ffffff; font-size: 0.82rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em;">
                    <th style="padding: 0.8rem 0.6rem; border: 1px solid rgba(255,255,255,0.2); width: 145px; text-align: center;">Time</th>
                    <th style="padding: 0.8rem 0.5rem; border: 1px solid rgba(255,255,255,0.2); width: 85px; text-align: center;">Minutes</th>
                    <th style="padding: 0.8rem 0.5rem; border: 1px solid rgba(255,255,255,0.2); text-align: center;">Sunday</th>
                    <th style="padding: 0.8rem 0.5rem; border: 1px solid rgba(255,255,255,0.2); text-align: center;">Monday</th>
                    <th style="padding: 0.8rem 0.5rem; border: 1px solid rgba(255,255,255,0.2); text-align: center;">Tuesday</th>
                    <th style="padding: 0.8rem 0.5rem; border: 1px solid rgba(255,255,255,0.2); text-align: center;">Wednesday</th>
                    <th style="padding: 0.8rem 0.5rem; border: 1px solid rgba(255,255,255,0.2); text-align: center;">Thursday</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matrix as $timeKey => $row)
                    @php
                        $start = $row['slot']['start'];
                        $end = $row['slot']['end'];
                        $duration = (strtotime($end) - strtotime($start)) / 60;
                        $durationText = $duration > 0 ? "{$duration} min." : "—";
                        $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

                        // Check if all 5 days have the exact same special title or break
                        $firstEntry = $row['days']['Sunday'];
                        $isAllSameSpecial = false;
                        $specialTitle = '';
                        if ($firstEntry) {
                            $rawFirst = strtolower($firstEntry->subject_name);
                            $isSpecialType = str_contains($rawFirst, 'recess') || str_contains($rawFirst, 'assembly') || str_contains($rawFirst, 'salah') || str_contains($rawFirst, 'departure') || str_contains($rawFirst, 'transition') || str_contains($rawFirst, 'break') || str_contains($rawFirst, 'homeroom');
                            
                            $allSame = true;
                            foreach ($daysList as $d) {
                                if (!$row['days'][$d] || $row['days'][$d]->subject_name !== $firstEntry->subject_name) {
                                    $allSame = false;
                                    break;
                                }
                            }
                            if ($allSame && $isSpecialType) {
                                $isAllSameSpecial = true;
                                $specialTitle = $firstEntry->subject_name;
                            }
                        }
                    @endphp

                    <tr style="border-bottom: 1px solid #cbd5e1;">
                        <!-- Time Column -->
                        <td style="padding: 0.75rem 0.5rem; text-align: center; font-weight: 850; font-size: 0.85rem; color: #0f172a; border: 1px solid #cbd5e1; background: #ffffff; white-space: nowrap;">
                            @if($start === $end)
                                {{ date('h:i A', strtotime($start)) }}
                            @else
                                {{ date('h:i', strtotime($start)) }} – {{ date('h:i A', strtotime($end)) }}
                            @endif
                        </td>

                        <!-- Minutes Column -->
                        <td style="padding: 0.75rem 0.5rem; text-align: center; font-weight: 700; font-size: 0.8rem; color: #475569; border: 1px solid #cbd5e1; background: #f8fafc; white-space: nowrap;">
                            {{ $durationText }}
                        </td>

                        @if($isAllSameSpecial)
                            <!-- Merged Special Activity Row (Homeroom Guidance, General Assembly, Transition, Salah, etc.) -->
                            @php
                                $specStyle = $getSubjectStyle($specialTitle);
                            @endphp
                            <td colspan="5" style="padding: 0.75rem 1rem; text-align: center; font-weight: 900; font-size: 0.85rem; color: {{ $specStyle['text'] }}; background: {{ $specStyle['bg'] }}; border: 1px solid #cbd5e1; text-transform: uppercase; letter-spacing: 0.05em;">
                                {{ strtoupper($specialTitle) }}
                            </td>
                        @else
                            <!-- Day Columns (Sunday to Thursday) -->
                            @php
                                $groups = [];
                                $i = 0;
                                while ($i < 5) {
                                    $day = $daysList[$i];
                                    $entry = $row['days'][$day];
                                    if (!$entry) {
                                        $groups[] = ['day' => $day, 'span' => 1, 'entry' => null];
                                        $i++;
                                        continue;
                                    }
                                    $span = 1;
                                    $j = $i + 1;
                                    while ($j < 5) {
                                        $nextDay = $daysList[$j];
                                        $nextEntry = $row['days'][$nextDay];
                                        if ($nextEntry && $nextEntry->subject_name === $entry->subject_name && $nextEntry->teacher_name === $entry->teacher_name) {
                                            $span++;
                                            $j++;
                                        } else {
                                            break;
                                        }
                                    }
                                    $groups[] = ['day' => $day, 'span' => $span, 'entry' => $entry];
                                    $i = $j;
                                }
                            @endphp

                            @foreach($groups as $group)
                                @php
                                    $entry = $group['entry'];
                                    $span = $group['span'];
                                @endphp

                                @if($entry)
                                    @php
                                        $style = $getSubjectStyle($entry->subject_name);
                                        $tName = $teacherName($entry);
                                        $photoUrl = $getPhotoUrl($entry->teacher_photo ?? null, $entry->teacher_key ?? null, $entry->teacher_display ?: ($entry->teacher_name ?? ''));
                                    @endphp
                                    <td colspan="{{ $span }}" 
                                        style="padding: 0.65rem 0.75rem; text-align: center; background: {{ $style['bg'] }}; border: 1px solid #cbd5e1; vertical-align: middle;">
                                        <div style="font-weight: 900; font-size: 0.875rem; color: {{ $style['text'] }}; line-height: 1.25;">
                                            {{ $entry->subject_name }}
                                        </div>
                                        @if($tName && $tName !== '—')
                                            <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: {{ $style['teacher'] }}; margin-top: 0.2rem; letter-spacing: 0.03em;">
                                                {{ strtoupper($tName) }}
                                            </div>
                                        @endif
                                    </td>
                                @else
                                    <td colspan="{{ $span }}" style="padding: 0.65rem 0.5rem; text-align: center; background: #ffffff; border: 1px solid #cbd5e1; color: #cbd5e1;">
                                        —
                                    </td>
                                @endif
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Official Subject Keys (Legend) & School Footer Note (Matches Reference) -->
    <div style="margin-top: 1rem; padding: 0.85rem 1.25rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; flex-direction: column; gap: 0.65rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; font-size: 0.78rem;">
            <strong style="color: #0f172a; font-weight: 850;">Subject Keys:</strong>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #6b21a8; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #f3e8ff; border: 1.5px solid #d8b4fe;"></span>
                Arabic / Qur'an / Islamic
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #166534; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #dcfce7; border: 1.5px solid #86efac;"></span>
                GMRC / Values / ESP
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #3730a3; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #e0e7ff; border: 1.5px solid #a5b4fc;"></span>
                Math / Physics
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #0f766e; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #ccfbf1; border: 1.5px solid #5eead4;"></span>
                Science / Biology
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #854d0e; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #fef9c3; border: 1.5px solid #fde047;"></span>
                English / Reading
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #9a3412; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #ffedd5; border: 1.5px solid #fdba74;"></span>
                AP / Social / Filipino
            </span>
            <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #9d174d; font-weight: 700;">
                <span style="width: 14px; height: 14px; border-radius: 3px; background: #fce7f3; border: 1.5px solid #f472b6;"></span>
                MAPEH / TLE
            </span>
        </div>
        <div style="font-size: 0.72rem; color: #64748b; font-weight: 600; border-top: 1px dashed #e2e8f0; padding-top: 0.4rem;">
            Al Munawwara Islamic School • Generated on {{ now()->format('F j, Y') }}
        </div>
    </div>
</div>
