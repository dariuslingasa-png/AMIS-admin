<!-- DESKTOP TIMETABLE VIEW (Accessible, Senior & High School Friendly AMIS Layout) -->
<div :class="isFullscreen ? 'calendar-wrapper is-fullscreen' : 'calendar-wrapper'" style="background: transparent; border: none; padding: 0; box-shadow: none;">
    
    <!-- Modern Card Container -->
    <div style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 20px; box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.06); overflow: hidden;">
        
        <!-- Emerald Gradient Section Banner Header -->
        <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 55%, #0d9488 100%); color: #ffffff; padding: 1.15rem 1.75rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; backdrop-filter: blur(6px); border: 1.5px solid rgba(255,255,255,0.3); flex-shrink: 0;">
                    <i data-lucide="calendar" style="width: 24px; height: 24px; color: #ffffff;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 900; letter-spacing: 0.01em; text-transform: uppercase; color: #ffffff; line-height: 1.2;">
                        {{ strtoupper($studentInfo['official_section_name'] ?: $studentInfo['section']) }}
                    </h3>
                    <div style="display: inline-flex; align-items: center; gap: 0.45rem; font-size: 14px; color: #e6fffa; font-weight: 700; margin-top: 3px;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #34d399; box-shadow: 0 0 8px #34d399;"></span>
                        <span>Active Official Timetable</span>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <span style="font-size: 14px; font-weight: 800; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1.5px solid rgba(255,255,255,0.35); color: #ffffff; padding: 0.45rem 1rem; border-radius: 999px; letter-spacing: 0.02em;">
                    {{ $studentInfo['grade_level'] }} • {{ $studentInfo['shift'] ?: $studentInfo['modality'] }}
                </span>
                <button type="button" @click="isFullscreen = !isFullscreen; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                        style="background: rgba(255,255,255,0.2); border: 1.5px solid rgba(255,255,255,0.35); color: #ffffff; padding: 0.45rem 1rem; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 14px; font-weight: 800; transition: all 0.15s ease;">
                    <template x-if="!isFullscreen">
                        <span style="display: inline-flex; align-items: center; gap: 0.4rem;"><i data-lucide="maximize-2" style="width: 15px; height: 15px;"></i> Full Screen</span>
                    </template>
                    <template x-if="isFullscreen">
                        <span style="display: inline-flex; align-items: center; gap: 0.4rem;"><i data-lucide="minimize-2" style="width: 15px; height: 15px;"></i> Exit Full Screen</span>
                    </template>
                </button>
            </div>
        </div>

        <!-- Accessible Timetable Grid Table -->
        <div style="overflow-x: auto; background: #ffffff;">
            <table style="width: 100%; border-collapse: collapse; min-width: 960px; text-align: left;">
                <thead>
                    @php
                        $todayDayName = $todayName ?? now()->format('l');
                        $daysList = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                    @endphp
                    <tr style="background: #f1f5f9; border-bottom: 2.5px solid #cbd5e1;">
                        <th style="padding: 1rem 0.75rem; border-right: 1.5px solid #cbd5e1; width: 145px; text-align: center; color: #0f172a; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">
                            Time
                        </th>
                        <th style="padding: 1rem 0.5rem; border-right: 1.5px solid #cbd5e1; width: 90px; text-align: center; color: #0f172a; font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">
                            Duration
                        </th>
                        @foreach($daysList as $dayHeader)
                            @php
                                $isTodayCol = (strcasecmp($dayHeader, $todayDayName) === 0);
                            @endphp
                            <th style="padding: 1rem 0.65rem; border-right: 1.5px solid #cbd5e1; text-align: center; {{ $isTodayCol ? 'background: #dcfce7; color: #166534; border-bottom: 3px solid #16a34a;' : 'color: #0f172a;' }}">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;">
                                    <span style="font-weight: 900; font-size: 15px; text-transform: uppercase; letter-spacing: 0.04em;">{{ $dayHeader }}</span>
                                    @if($isTodayCol)
                                        <span style="font-size: 11.5px; font-weight: 900; color: #ffffff; background: #15803d; padding: 0.15rem 0.6rem; border-radius: 999px; letter-spacing: 0.05em; box-shadow: 0 1px 3px rgba(21,128,61,0.3);">
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
                            $durationText = $duration > 0 ? "{$duration} mins" : "—";

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

                        <tr style="border-bottom: 1.5px solid #e2e8f0; transition: background 0.15s ease;">
                            <!-- Time Column (Big, Bold, Legible for Elderly/Parents) -->
                            <td style="padding: 0.85rem 0.5rem; text-align: center; vertical-align: middle; border-right: 1.5px solid #e2e8f0; background: #fafbfc; white-space: nowrap;">
                                @if($start === $end)
                                    <span style="font-weight: 900; font-size: 15.5px; color: #0f172a;">
                                        {{ date('g:i A', strtotime($start)) }}
                                    </span>
                                @else
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px;">
                                        <span style="font-weight: 900; font-size: 15px; color: #0f172a; line-height: 1.2;">
                                            {{ date('g:i A', strtotime($start)) }}
                                        </span>
                                        <span style="font-size: 12px; font-weight: 750; color: #64748b; text-transform: uppercase;">
                                            to
                                        </span>
                                        <span style="font-weight: 850; font-size: 14.5px; color: #0f172a; line-height: 1.2;">
                                            {{ date('g:i A', strtotime($end)) }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <!-- Duration Column -->
                            <td style="padding: 0.85rem 0.4rem; text-align: center; vertical-align: middle; border-right: 1.5px solid #e2e8f0; background: #ffffff; white-space: nowrap;">
                                <span style="display: inline-block; font-size: 13.5px; font-weight: 800; color: #334155; background: #f1f5f9; padding: 0.3rem 0.65rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                                    {{ $durationText }}
                                </span>
                            </td>

                            @if($isAllSameSpecial)
                                <!-- Merged Special Activity Row (Recess, Assembly, Salah, Departure, Transition) -->
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
                                <td colspan="5" style="padding: 0.65rem 0.85rem; vertical-align: middle; background: #ffffff;">
                                    <div class="sched-special-strip" style="
                                        background: {{ $specStyle['bg'] }};
                                        border: 1.5px solid {{ $specStyle['border'] }};
                                        border-left: 5px solid {{ $specStyle['accent'] }};
                                        border-radius: 12px;
                                        padding: 0.85rem 1.25rem;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        gap: 0.75rem;
                                        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
                                    ">
                                        <div style="width: 28px; height: 28px; border-radius: 50%; background: {{ $specStyle['accent'] }}; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0;">
                                            <i data-lucide="{{ $specIcon }}" style="width: 16px; height: 16px;"></i>
                                        </div>
                                        <span style="font-weight: 900; font-size: 15px; text-transform: uppercase; letter-spacing: 0.04em; color: #0f172a;">
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
                                            style="padding: 0.55rem 0.65rem; vertical-align: middle; border-right: 1.5px solid #e2e8f0; background: #ffffff;">
                                            <div class="sched-grid-card" style="
                                                background: {{ $style['bg'] }};
                                                border: 1.5px solid {{ $style['border'] }};
                                                border-left: 5px solid {{ $style['accent'] }};
                                                border-radius: 12px;
                                                padding: 0.85rem 0.9rem;
                                                min-height: 72px;
                                                display: flex;
                                                flex-direction: column;
                                                justify-content: center;
                                                align-items: center;
                                                text-align: center;
                                                box-shadow: 0 1px 3px rgba(0,0,0,0.03);
                                            ">
                                                <!-- Subject Name (Big, High-Contrast Dark Text) -->
                                                <div style="font-weight: 900; font-size: 15.5px; color: #0f172a; line-height: 1.3; letter-spacing: -0.01em;">
                                                    {{ $entry->subject_name }}
                                                </div>

                                                <!-- Teacher Name (High-Contrast Clean Pill) -->
                                                @if($tName && $tName !== '—')
                                                    <div style="display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 0.45rem; font-size: 13px; font-weight: 800; text-transform: uppercase; color: #334155; background: #ffffff; padding: 0.22rem 0.7rem; border-radius: 999px; border: 1.5px solid rgba(0,0,0,0.08); box-shadow: 0 1px 2px rgba(0,0,0,0.03); letter-spacing: 0.02em;">
                                                        <i data-lucide="user" style="width: 13px; height: 13px; color: {{ $style['accent'] }}; flex-shrink: 0;"></i>
                                                        <span>{{ strtoupper($tName) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    @else
                                        <td colspan="{{ $span }}" style="padding: 0.55rem; text-align: center; vertical-align: middle; border-right: 1.5px solid #e2e8f0; background: #f8fafc;">
                                            <div style="font-size: 1rem; color: #94a3b8; font-weight: 700;">—</div>
                                        </td>
                                    @endif
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Accessible Subject Categories (Legend) & School Footer Note -->
        <div style="padding: 1.25rem 1.75rem; background: #f8fafc; border-top: 1.5px solid #e2e8f0; display: flex; flex-direction: column; gap: 1rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; color: #0f172a; font-size: 15px; font-weight: 900;">
                    <i data-lucide="palette" style="width: 18px; height: 18px; color: #059669;"></i>
                    <span>Subject Categories:</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap; font-size: 13.5px;">
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #581c87; font-weight: 800; background: #f3e8ff; border: 1.5px solid #c084fc; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #9333ea;"></span>
                        Arabic / Qur'an / Islamic
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #14532d; font-weight: 800; background: #dcfce7; border: 1.5px solid #86efac; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #16a34a;"></span>
                        GMRC / Values
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #1e1b4b; font-weight: 800; background: #e0e7ff; border: 1.5px solid #a5b4fc; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #4f46e5;"></span>
                        Math / Physics
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #134e4a; font-weight: 800; background: #ccfbf1; border: 1.5px solid #5eead4; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #0d9488;"></span>
                        Science / Biology
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #713f12; font-weight: 800; background: #fef9c3; border: 1.5px solid #fde047; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #ca8a04;"></span>
                        English / Reading
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #7c2d12; font-weight: 800; background: #ffedd5; border: 1.5px solid #fdba74; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #ea580c;"></span>
                        AP / Social / Filipino
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; color: #831843; font-weight: 800; background: #fce7f3; border: 1.5px solid #f472b6; padding: 0.3rem 0.75rem; border-radius: 999px;">
                        <span style="width: 9px; height: 9px; border-radius: 50%; background: #db2777;"></span>
                        MAPEH / TLE
                    </span>
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; color: #64748b; font-weight: 700; border-top: 1.5px solid #e2e8f0; padding-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <span style="display: inline-flex; align-items: center; gap: 0.4rem;">
                    <i data-lucide="shield-check" style="width: 16px; height: 16px; color: #059669;"></i>
                    Al Munawwara Islamic School Official Academic Timetable
                </span>
                <span>Generated on {{ now()->format('F j, Y') }}</span>
            </div>
        </div>
    </div>
</div>
