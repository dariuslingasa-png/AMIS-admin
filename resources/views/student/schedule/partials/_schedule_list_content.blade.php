@php
    $getSubjectStyle = function ($subjectName) {
        $s = mb_strtolower(trim((string) $subjectName));
        if (str_contains($s, 'qur') || str_contains($s, 'arab') || str_contains($s, 'shaf') || str_contains($s, 'hadith') || str_contains($s, 'islam')) {
            return ['cat' => 'Islamic & Arabic', 'accent' => '#059669', 'bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#064e3b', 'badge_bg' => '#d1fae5', 'badge_text' => '#065f46'];
        }
        if (str_contains($s, 'math')) {
            return ['cat' => 'Mathematics', 'accent' => '#4f46e5', 'bg' => '#eef2ff', 'border' => '#c7d2fe', 'text' => '#1e1b4b', 'badge_bg' => '#e0e7ff', 'badge_text' => '#3730a3'];
        }
        if (str_contains($s, 'sci') || str_contains($s, 'bio') || str_contains($s, 'phys') || str_contains($s, 'res')) {
            return ['cat' => 'Science', 'accent' => '#9333ea', 'bg' => '#faf5ff', 'border' => '#e9d5ff', 'text' => '#3b0764', 'badge_bg' => '#f3e8ff', 'badge_text' => '#6b21a8'];
        }
        if (str_contains($s, 'eng') || str_contains($s, 'read') || str_contains($s, 'lit') || str_contains($s, 'lang') || $s === 'r & l' || $s === 'lcs' || $s === 'mil') {
            return ['cat' => 'English & Reading', 'accent' => '#0284c7', 'bg' => '#f0f9ff', 'border' => '#bae6fd', 'text' => '#0c4a6e', 'badge_bg' => '#e0f2fe', 'badge_text' => '#0369a1'];
        }
        if (str_contains($s, 'fili') || str_contains($s, 'makabansa') || str_contains($s, 'gmrc') || str_contains($s, 'esp')) {
            return ['cat' => 'Filipino & Values', 'accent' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#78350f', 'badge_bg' => '#fef3c7', 'badge_text' => '#92400e'];
        }
        if (str_contains($s, 'ap') || str_contains($s, 'araling') || str_contains($s, 'soc')) {
            return ['cat' => 'Araling Panlipunan', 'accent' => '#ea580c', 'bg' => '#fff7ed', 'border' => '#fed7aa', 'text' => '#7c2d12', 'badge_bg' => '#ffedd5', 'badge_text' => '#c2410c'];
        }
        if (str_contains($s, 'mapeh') || str_contains($s, 'music') || str_contains($s, 'art') || str_contains($s, 'pe') || str_contains($s, 'phys')) {
            return ['cat' => 'MAPEH & Arts', 'accent' => '#e11d48', 'bg' => '#fff1f2', 'border' => '#fecdd3', 'text' => '#881337', 'badge_bg' => '#ffe4e6', 'badge_text' => '#be123c'];
        }
        if (str_contains($s, 'tle') || str_contains($s, 'comp') || str_contains($s, 'ict') || $s === 'ec') {
            return ['cat' => 'TLE & ICT', 'accent' => '#0d9488', 'bg' => '#f0fdfa', 'border' => '#99f6e4', 'text' => '#134e4a', 'badge_bg' => '#ccfbf1', 'badge_text' => '#0f766e'];
        }
        if (str_contains($s, 'circle') || str_starts_with($s, 'ct ') || $s === 'ct 1' || $s === 'ct 2') {
            return ['cat' => 'Circle Time', 'accent' => '#db2777', 'bg' => '#fdf2f8', 'border' => '#fbcfe8', 'text' => '#831843', 'badge_bg' => '#fce7f3', 'badge_text' => '#be185d'];
        }
        if (str_contains($s, 'meeting')) {
            return ['cat' => 'Meeting Time', 'accent' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#1e3a8a', 'badge_bg' => '#dbeafe', 'badge_text' => '#1d4ed8'];
        }
        if (str_contains($s, 'wrap-up')) {
            return ['cat' => 'Wrap-Up Time', 'accent' => '#ca8a04', 'bg' => '#fefce8', 'border' => '#fef08a', 'text' => '#713f12', 'badge_bg' => '#fef9c3', 'badge_text' => '#854d0e'];
        }
        if (str_contains($s, 'homeroom') || $s === 'hg') {
            return ['cat' => 'Homeroom Guidance', 'accent' => '#0891b2', 'bg' => '#ecfeff', 'border' => '#a5f3fc', 'text' => '#164e63', 'badge_bg' => '#cffafe', 'badge_text' => '#0e7490'];
        }
        return ['cat' => 'General', 'accent' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0', 'text' => '#334155', 'badge_bg' => '#f1f5f9', 'badge_text' => '#475569'];
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
                        @php
                            $style = $getSubjectStyle($entry->subject_name);
                        @endphp
                        <div style="background: {{ $style['bg'] }}; border: 1.5px solid {{ $style['border'] }}; border-radius: 16px; padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between; gap: 0.85rem; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 11.5px; font-weight: 850; color: {{ $style['accent'] }}; text-transform: uppercase; background: {{ $style['badge_bg'] }}; padding: 0.15rem 0.5rem; border-radius: 6px;">
                                    {{ $entry->day }}
                                </span>
                                <span style="font-size: 13px; font-weight: 850; color: #0f172a; display: flex; align-items: center; gap: 0.3rem;">
                                    <i data-lucide="clock" style="width: 13px; height: 13px; color: #64748b;"></i>
                                    {{ $entry->time }}
                                </span>
                            </div>

                            <div>
                                <h4 style="font-size: 16px; font-weight: 900; color: {{ $style['text'] }}; text-transform: uppercase; margin: 0; line-height: 1.3;">
                                    {{ $entry->subject_name }}
                                </h4>
                                
                                <div style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 12px; font-weight: 750; color: {{ $style['badge_text'] }}; background: {{ $style['badge_bg'] }}; border: 1px solid {{ $style['border'] }}; border-radius: 6px; padding: 0.2rem 0.55rem; margin-top: 0.4rem;">
                                    <i data-lucide="user" style="width: 12px; height: 12px;"></i>
                                    <span>{{ $entry->teacher_display ?: '—' }}</span>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-weight: 700; color: {{ $style['text'] }}; opacity: 0.85; padding-top: 0.5rem; border-top: 1px solid rgba(0,0,0,0.06);">
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
