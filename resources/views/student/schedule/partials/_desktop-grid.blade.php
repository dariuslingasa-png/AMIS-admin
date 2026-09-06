<!-- DESKTOP TIMETABLE VIEW (Modern AMIS Layout) -->
<div :class="isFullscreen ? 'calendar-wrapper is-fullscreen' : 'calendar-wrapper'" style="background: transparent; border: none; padding: 0; box-shadow: none;">
    
    <!-- Modern Card Container -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 18px; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05); overflow: hidden;">
        
        <!-- Modern Emerald Gradient Section Banner Header -->
        <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 55%, #0d9488 100%); color: #ffffff; padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.2); flex-shrink: 0;">
                    <i data-lucide="calendar" style="width: 20px; height: 20px; color: #ffffff;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.05rem; font-weight: 850; letter-spacing: 0.01em; text-transform: uppercase; color: #ffffff; line-height: 1.2;">
                        {{ strtoupper($studentInfo['official_section_name'] ?: $studentInfo['section']) }}
                    </h3>
                    <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: rgba(255,255,255,0.85); font-weight: 600; margin-top: 2px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399; box-shadow: 0 0 6px #34d399;"></span>
                        <span>Active Official Timetable</span>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.65rem;">
                <span style="font-size: 0.75rem; font-weight: 700; background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; padding: 0.35rem 0.85rem; border-radius: 999px; letter-spacing: 0.02em;">
                    {{ $studentInfo['grade_level'] }} • {{ $studentInfo['shift'] ?: $studentInfo['modality'] }}
                </span>
                <button type="button" @click="isFullscreen = !isFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                        style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); color: #ffffff; padding: 0.35rem 0.75rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 700; transition: all 0.15s ease;">
                    <template x-if="!isFullscreen">
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem;"><i data-lucide="maximize-2" style="width: 13px; height: 13px;"></i> Full Screen</span>
                    </template>
                    <template x-if="isFullscreen">
                        <span style="display: inline-flex; align-items: center; gap: 0.3rem;"><i data-lucide="minimize-2" style="width: 13px; height: 13px;"></i> Exit</span>
                    </template>
                </button>
            </div>
        </div>

        <!-- Modern Timetable Grid Table -->
        <div style="overflow-x: auto; background: #ffffff;">
            <table style="width: 100%; border-collapse: collapse; min-width: 900px; text-align: left;">
                <thead>
                    @php
                        $todayDayName = $todayName ?? now()->format('l');
                        $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                    @endphp
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-size: 0.78rem; letter-spacing: 0.04em;">
                        <th style="padding: 0.85rem 0.75rem; border-right: 1px solid #edf2f7; width: 140px; text-align: center; color: #475569; font-weight: 800; text-transform: uppercase;">
                            Time
                        </th>
                        <th style="padding: 0.85rem 0.5rem; border-right: 1px solid #edf2f7; width: 80px; text-align: center; color: #475569; font-weight: 800; text-transform: uppercase;">
                            Duration
                        </th>
                        @foreach($daysList as $dayHeader)
                            @php
                                $isTodayCol = (strcasecmp($dayHeader, $todayDayName) === 0);
                            @endphp
                            <th style="padding: 0.85rem 0.6rem; border-right: 1px solid #edf2f7; text-align: center; {{ $isTodayCol ? 'background: #ecfdf5; color: #047857;' : 'color: #334155;' }}">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                                    <span style="font-weight: 850; font-size: 0.8rem; text-transform: uppercase;">{{ $dayHeader }}</span>
                                    @if($isTodayCol)
                                        <span style="font-size: 0.65rem; font-weight: 800; color: #059669; background: #d1fae5; padding: 0.1rem 0.45rem; border-radius: 999px; letter-spacing: 0.04em;">
                                            TODAY
                                        </span>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix as $timeKey => $row)
                        @php
                            $start = $row['slot']['start'];
                            $end = $row['slot']['end'];
                            $duration = (strtotime($end) - strtotime($start)) / 60;
                            $durationText = $duration > 0 ? "{$duration} min." : "—";

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

                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                            <!-- Time Column -->
                            <td style="padding: 0.75rem 0.5rem; text-align: center; vertical-align: middle; border-right: 1px solid #f1f5f9; background: #ffffff; white-space: nowrap;">
                                @if($start === $end)
                                    <span style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 800; font-size: 0.82rem; color: #0f172a;">
                                        {{ date('h:i A', strtotime($start)) }}
                                    </span>
                                @else
                                    <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 800; font-size: 0.82rem; color: #0f172a; line-height: 1.2;">
                                        {{ date('h:i A', strtotime($start)) }}
                                    </div>
                                    <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 600; font-size: 0.72rem; color: #94a3b8; margin-top: 2px;">
                                        to {{ date('h:i A', strtotime($end)) }}
                                    </div>
                                @endif
                            </td>

                            <!-- Duration Column -->
                            <td style="padding: 0.75rem 0.5rem; text-align: center; vertical-align: middle; border-right: 1px solid #f1f5f9; background: #ffffff; white-space: nowrap;">
                                <span style="display: inline-block; font-size: 0.72rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                                    {{ $durationText }}
                                </span>
                            </td>

                            @if($isAllSameSpecial)
                                <!-- Merged Special Activity Row (Assembly, Recess, Departure, Transition, Salah, etc.) -->
                                @php
                                    $specStyle = $getSubjectStyle($specialTitle);
                                    $specLower = strtolower($specialTitle);
                                    $specIcon = 'sparkles';
                                    if (str_contains($specLower, 'recess') || str_contains($specLower, 'break') || str_contains($specLower, 'lunch')) {
                                        $specIcon = 'coffee';
                                    } elseif (str_contains($specLower, 'assembly') || str_contains($specLower, 'meeting') || str_contains($specLower, 'homeroom')) {
                                        $specIcon = 'users';
                                    } elseif (str_contains($specLower, 'salah') || str_contains($specLower, 'prayer')) {
                                        $specIcon = 'sun';
                                    } elseif (str_contains($specLower, 'departure') || str_contains($specLower, 'dismissal')) {
                                        $specIcon = 'door-open';
                                    } elseif (str_contains($specLower, 'transition')) {
                                        $specIcon = 'arrow-right-circle';
                                    }
                                @endphp
                                <td colspan="5" style="padding: 0.5rem 0.75rem; vertical-align: middle; background: #ffffff;">
                                    <div class="sched-special-strip" style="
                                        background: {{ $specStyle['bg'] }};
                                        border: 1px dashed {{ $specStyle['border'] }};
                                        border-left: 4px solid {{ $specStyle['accent'] }};
                                        border-radius: 10px;
                                        padding: 0.65rem 1rem;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        gap: 0.55rem;
                                        color: {{ $specStyle['text'] }};
                                        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
                                    ">
                                        <i data-lucide="{{ $specIcon }}" style="width: 15px; height: 15px; opacity: 0.9; flex-shrink: 0;"></i>
                                        <span style="font-weight: 850; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                            {{ strtoupper($specialTitle) }}
                                        </span>
                                    </div>
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
                                        @endphp
                                        <td colspan="{{ $span }}" 
                                            style="padding: 0.45rem 0.5rem; vertical-align: middle; border-right: 1px solid #f1f5f9; background: #ffffff;">
                                            <div class="sched-grid-card" style="
                                                background: {{ $style['bg'] }};
                                                border: 1px solid {{ $style['border'] }};
                                                border-left: 3.5px solid {{ $style['accent'] }};
                                                border-radius: 10px;
                                                padding: 0.65rem 0.75rem;
                                                min-height: 58px;
                                                display: flex;
                                                flex-direction: column;
                                                justify-content: center;
                                                align-items: center;
                                                text-align: center;
                                                box-shadow: 0 1px 2px rgba(0,0,0,0.02);
                                                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                                            ">
                                                <div style="font-weight: 850; font-size: 0.85rem; color: {{ $style['text'] }}; line-height: 1.25; letter-spacing: -0.01em;">
                                                    {{ $entry->subject_name }}
                                                </div>
                                                @if($tName && $tName !== '—')
                                                    <div style="display: inline-flex; align-items: center; gap: 0.3rem; margin-top: 0.35rem; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; color: {{ $style['teacher'] }}; background: rgba(255, 255, 255, 0.85); padding: 0.15rem 0.55rem; border-radius: 999px; border: 1px solid rgba(0,0,0,0.04); letter-spacing: 0.02em;">
                                                        <i data-lucide="user" style="width: 10px; height: 10px; opacity: 0.75;"></i>
                                                        <span>{{ strtoupper($tName) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    @else
                                        <td colspan="{{ $span }}" style="padding: 0.45rem; text-align: center; vertical-align: middle; border-right: 1px solid #f1f5f9; background: #fafbfc;">
                                            <div style="font-size: 0.85rem; color: #cbd5e1; font-weight: 500;">—</div>
                                        </td>
                                    @endif
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Modern Subject Categories (Legend) & School Footer Note -->
        <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #eef2f6; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.4rem; color: #334155; font-size: 0.78rem; font-weight: 800;">
                    <i data-lucide="palette" style="width: 14px; height: 14px; color: #059669;"></i>
                    <span>Subject Categories:</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: 0.75rem;">
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #6b21a8; font-weight: 700; background: #f3e8ff; border: 1px solid #d8b4fe; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #9333ea;"></span>
                        Arabic / Qur'an / Islamic
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #166534; font-weight: 700; background: #dcfce7; border: 1px solid #86efac; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #16a34a;"></span>
                        GMRC / Values
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #3730a3; font-weight: 700; background: #e0e7ff; border: 1px solid #a5b4fc; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #4f46e5;"></span>
                        Math / Physics
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #0f766e; font-weight: 700; background: #ccfbf1; border: 1px solid #5eead4; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #0d9488;"></span>
                        Science / Biology
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #854d0e; font-weight: 700; background: #fef9c3; border: 1px solid #fde047; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #ca8a04;"></span>
                        English / Reading
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #9a3412; font-weight: 700; background: #ffedd5; border: 1px solid #fdba74; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #ea580c;"></span>
                        AP / Social / Filipino
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: #9d174d; font-weight: 700; background: #fce7f3; border: 1px solid #f472b6; padding: 0.2rem 0.55rem; border-radius: 999px;">
                        <span style="width: 7px; height: 7px; border-radius: 50%; background: #db2777;"></span>
                        MAPEH / TLE
                    </span>
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.72rem; color: #94a3b8; font-weight: 600; border-top: 1px solid #eef2f6; padding-top: 0.6rem; flex-wrap: wrap; gap: 0.5rem;">
                <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                    <i data-lucide="shield-check" style="width: 13px; height: 13px; color: #059669;"></i>
                    Al Munawwara Islamic School Official Academic Timetable
                </span>
                <span>Generated on {{ now()->format('F j, Y') }}</span>
            </div>
        </div>
    </div>
</div>
