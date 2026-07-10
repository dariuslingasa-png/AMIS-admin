<x-student-layout title="Dashboard">

@php
    $photo     = $student?->applicant?->photo_2x2_url;
    $firstName = $student?->applicant?->first_name ?? $user->name;
    $lastName  = $student?->applicant?->last_name ?? '';
    $fullName  = $student?->applicant
        ? $student->applicant->first_name . ' ' . $student->applicant->last_name
        : $user->name;
    $account   = $student?->account;
    $initials  = collect(explode(' ', $fullName))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');
    
    $learningMode = $student?->applicant?->learning_mode;
    if ($learningMode) {
        $learningMode = str_ireplace('Flexible Online Learning', 'ODL', $learningMode);
        $learningMode = str_ireplace('Face-to-Face', 'F2F', $learningMode);
    }

    $formatTeacherName = function ($name) {
        $name = $name ?: 'To Be Assigned';
        if ($name === 'To Be Assigned' || $name === '—') {
            return $name;
        }
        $nameTrimmed = trim($name);
        $nameLower = strtolower($nameTrimmed);
        
        $titles = ['tchr', 'teacher', 'ust', 'ustadz', 'ustadh', 'ustadha', 'alim', 'alima', 'alimah'];
        
        $hasTitle = false;
        foreach ($titles as $title) {
            if (str_starts_with($nameLower, $title)) {
                $hasTitle = true;
                break;
            }
        }
        
        if ($hasTitle) {
            if (str_starts_with($nameLower, 'teacher ')) {
                return 'Tchr. ' . ucwords(strtolower(trim(substr($nameTrimmed, 8))));
            }
            return ucwords(strtolower($nameTrimmed));
        }
        
        return 'Tchr. ' . ucwords(strtolower($nameTrimmed));
    };

    // Format weekly schedule string for each subject
    $subjectSchedules = [];
    foreach ($schedules as $cs) {
        $dayAbbrev = substr($cs->day, 0, 3);
        $timeStr = date('g:i A', strtotime($cs->start_time)) . ' - ' . date('g:i A', strtotime($cs->end_time));
        $subjectSchedules[$cs->subject_name][$timeStr][] = $dayAbbrev;
    }

    $formattedSchedules = [];
    foreach ($subjectSchedules as $subName => $timeSlots) {
        $parts = [];
        foreach ($timeSlots as $timeStr => $days) {
            if (count($days) === 5) {
                $dayStr = 'Sun-Thu';
            } else {
                $dayStr = implode(', ', $days);
            }
            $parts[] = $dayStr . ' ' . $timeStr;
        }
        $formattedSchedules[$subName] = implode(' | ', $parts);
    }

    $todayName = now()->format('l');
    $isSchoolDay = in_array($todayName, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);
    $defaultViewMode = 'today';

    if ($isSchoolDay) {
        $targetDayName = $todayName;
        $todayLabel = "Today's Classes";
        $todaySub = now()->format('l, F j, Y');
    } else {
        $targetDayName = 'Sunday';
        $todayLabel = "Next School Day (Sunday)";
        $nextSunday = now()->next('Sunday');
        $todaySub = "Sunday, " . $nextSunday->format('F j, Y');
    }
@endphp

@once
<style>
    .sched-tab-btn {
        border: none !important;
        border-radius: 8px !important;
        padding: 0.4rem 0.85rem !important;
        font-size: 0.75rem !important;
        font-weight: 850 !important;
        cursor: pointer !important;
        transition: all 0.15s ease !important;
        background: transparent !important;
        color: #64748b !important;
    }
    .sched-tab-btn.active {
        background: white !important;
        color: #0d9488 !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(1.05); }
    }

    /* 3D Card Flip styling for Digital ID */
    .perspective-1000 {
        perspective: 1200px;
    }
    .card-inner {
        transform-style: preserve-3d;
        transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%;
        width: 100%;
    }
    .card-front, .card-back {
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
    }
    .card-back {
        transform: rotateY(180deg);
    }
    .is-flipped {
        transform: rotateY(180deg);
    }
    .holo-overlay {
        background: linear-gradient(135deg, 
            rgba(255,255,255,0) 0%, 
            rgba(255,255,255,0) 40%, 
            rgba(255, 255, 255, 0.3) 50%, 
            rgba(255,255,255,0) 60%, 
            rgba(255,255,255,0) 100%
        );
        background-size: 250% 250%;
        background-position: 0% 0%;
        transition: background-position 0.6s ease;
    }
    .holo-card:hover .holo-overlay {
        background-position: 100% 100%;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
@endonce

{{-- ── 2-col layout: main + right panel ──────────────────────────── --}}
<div class="s-two-col-grid" x-data="{ viewMode: '{{ $defaultViewMode }}', showIdModal: false, isFlipped: false }">

    {{-- ── LEFT COLUMN ──────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:1.5rem;min-width:0;width:100%;">

        @if (session('success'))
            <div class="student-alert fade-up" style="margin-bottom: 0;">
                <i data-lucide="check-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="student-error fade-up" style="margin-bottom: 0;">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Hero Banner --}}
        <div class="s-dash-hero fade-up">
            {{-- Dot mesh --}}
            <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.06) 1px,transparent 1px);background-size:22px 22px;pointer-events:none;"></div>
            {{-- Glow orb --}}
            <div style="position:absolute;right:-30px;top:-30px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(52,211,153,0.2),transparent 65%);pointer-events:none;"></div>

            {{-- Avatar --}}
            <div style="position:relative;width:80px;height:80px;flex-shrink:0;">
                @if ($photo)
                    <img src="{{ asset('storage/' . $photo) }}" alt="Photo"
                         style="width:80px;height:80px;object-fit:cover;border-radius:50%;border:4px solid #ffffff;box-shadow: 0 4px 12px rgba(0,0,0,0.15);display:block;">
                @else
                    <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.2);border:4px solid #ffffff;box-shadow: 0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;justify-content:center;box-sizing:border-box;">
                        <span style="font-size:1.6rem;font-weight:900;color:white;line-height:1;display:flex;align-items:center;justify-content:center;text-align:center;">{{ $initials }}</span>
                    </div>
                @endif
                <span style="position:absolute;bottom:2px;right:2px;width:14px;height:14px;border-radius:50%;background:#10b981;border:2.5px solid #ffffff;box-shadow:0 2px 4px rgba(0,0,0,0.1);z-index:5;"></span>
            </div>

            {{-- Text --}}
            <div style="position:relative;">
                <div class="s-dash-hero-meta">
                    {{ $student?->grade_level }}
                    · {{ strtoupper($student?->applicant?->student_type ?? 'new') }}
                    @if($learningMode)
                        · {{ $learningMode }}
                    @endif
                    · SY {{ $student?->school_year }}
                </div>
                <div style="font-size: 1.1rem !important; font-weight: 800 !important; color: rgba(255, 255, 255, 0.8) !important; margin-bottom: 0.25rem !important; text-transform: uppercase; letter-spacing: 0.05em;">
                    Assalamualaikum,
                </div>
                <h1 class="s-dash-hero-title" style="margin-top: 0 !important; font-size: 2.2rem !important; margin-bottom: 0.5rem !important;">
                    {{ $fullName }}!
                </h1>
                <div class="s-dash-hero-sub">
                    Student ID: <span style="text-decoration: underline;">{{ $student?->student_number }}</span> · {{ $student?->school_email }}
                </div>
            </div>
        </div>

        {{-- ── COMING SOON BANNER — F2F Students (Old & New) ─────────── --}}
        @php
            $isF2F = str_contains(strtolower($student?->applicant?->learning_mode ?? ''), 'face');
        @endphp
        @if($isF2F)
        <div class="fade-up" style="
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 60%, #fde68a 100%);
            border: 1.5px solid #f59e0b;
            border-radius: 20px;
            padding: 1.75rem 2rem;
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(245,158,11,0.12);
        ">
            {{-- Background decoration --}}
            <div style="position:absolute;right:-20px;bottom:-20px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(245,158,11,0.15),transparent 70%);pointer-events:none;"></div>

            {{-- Icon --}}
            <div style="
                width: 52px; height: 52px; border-radius: 14px;
                background: #f59e0b;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
                box-shadow: 0 4px 12px rgba(245,158,11,0.35);
            ">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>

            {{-- Text content --}}
            <div style="flex:1; position:relative;">
                <div style="display:flex; align-items:center; gap:0.625rem; margin-bottom:0.5rem; flex-wrap:wrap;">
                    <span style="font-size:0.7rem; font-weight:800; color:#92400e; background:#fde68a; border:1.5px solid #f59e0b; padding:0.2rem 0.6rem; border-radius:999px; text-transform:uppercase; letter-spacing:0.08em;">
                        Face-to-Face
                    </span>
                    <span style="font-size:0.7rem; font-weight:800; color:#ffffff; background:#f59e0b; padding:0.2rem 0.6rem; border-radius:999px; text-transform:uppercase; letter-spacing:0.08em;">
                        {{ strtoupper($student?->applicant?->student_type ?? 'student') }}
                    </span>
                </div>
                <h3 style="font-size:1.2rem; font-weight:800; color:#78350f; margin:0 0 0.5rem; letter-spacing:-0.02em;">
                    🚧 Portal Coming Soon for F2F Students!
                </h3>
                <p style="font-size:0.92rem; font-weight:500; color:#92400e; margin:0; line-height:1.7;">
                    We are currently preparing the online portal for <strong>Face-to-Face</strong> students.
                    Your schedule, subjects, grades, and other features will be available here very soon.
                    In the meantime, please coordinate with your class adviser for any concerns.
                </p>
                <div style="margin-top:1rem; display:flex; align-items:center; gap:0.5rem;">
                    <div style="width:8px; height:8px; border-radius:50%; background:#f59e0b; animation: pulse-dot 1.5s infinite;"></div>
                    <span style="font-size:0.8rem; font-weight:700; color:#b45309;">We'll notify you once your portal is ready.</span>
                </div>
            </div>
        </div>
        <style>
            @keyframes pulse-dot {
                0%, 100% { opacity:1; transform:scale(1); }
                50% { opacity:0.5; transform:scale(1.4); }
            }
        </style>
        @endif

        {{-- Billing / SOA Card --}}
        <a href="{{ route('student.billing') }}" class="fade-up" style="background: white; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.25rem 1.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; text-decoration: none; transition: all 0.2s ease-in-out;" onmouseover="this.style.transform='translateY(-2px)';this.style.borderColor='#a7f3d0';" onmouseout="this.style.transform='none';this.style.borderColor='#e2e8f0';">
            <div style="display: flex; align-items: center; gap: 1rem; min-width: 0;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; color: #059669; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wallet"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M16 12a2 2 0 0 0 0 4h5v-4z"/></svg>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <h3 style="font-size: 1.05rem; font-weight: 850; color: #0f172a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Billing & Statement of Account</h3>
                    <p style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin: 0.15rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">View your tuition balance, official receipts, and monthly billing statements.</p>
                </div>
            </div>
            <span style="font-size: 0.725rem; font-weight: 850; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.25rem 0.65rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; display: inline-flex; align-items: center; gap: 0.25rem;">
                View Account <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </span>
        </a>

        {{-- Schedule Table Section --}}
        <div class="fade-up" style="display:flex;flex-direction:column;gap:0.85rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                <div>
                    <h2 style="font-size:1.35rem;font-weight:900;color:#0f172a;margin:0;letter-spacing:-0.02em;">
                        <span x-show="viewMode === 'today'">{{ $todayLabel }}</span>
                        <span x-show="viewMode === 'weekly'">Weekly Schedule</span>
                    </h2>
                    <div style="font-size:0.95rem;color:#475569;margin-top:4px;font-weight:700;">
                        <span x-show="viewMode === 'today'">{{ $todaySub }}</span>
                        <span x-show="viewMode === 'weekly'">{{ now()->format('l, F j, Y') }}</span>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                    @if($student && $student->ms_user_id && str_ends_with(strtolower($student->school_email), '@amis.edu.ph'))
                        <form method="POST" action="{{ route('student.sync-teams') }}" style="margin: 0; display: inline-block;">
                            @csrf
                            <button type="submit" class="sched-tab-btn" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem !important; padding: 0.45rem 0.85rem !important; background: #059669 !important; color: white !important; border-radius: 8px !important; box-shadow: 0 2px 4px rgba(5,150,105,0.15) !important; cursor: pointer;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                Sync MS Teams
                            </button>
                        </form>
                    @endif

                    <!-- Today / Weekly toggle buttons -->
                    <div style="display:flex;background:#e2e8f0;padding:0.25rem;border-radius:10px;gap:0.25rem;">
                        <button type="button" @click="viewMode = 'today'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                :class="viewMode === 'today' ? 'active' : ''"
                                class="sched-tab-btn" style="font-size:0.75rem !important;padding:0.35rem 0.85rem !important;">
                            Today
                        </button>
                        <button type="button" @click="viewMode = 'weekly'; $nextTick(() => window.lucide && window.lucide.createIcons())"
                                :class="viewMode === 'weekly' ? 'active' : ''"
                                class="sched-tab-btn" style="font-size:0.75rem !important;padding:0.35rem 0.85rem !important;">
                            Weekly
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table card --}}
            <div class="s-table-card" style="background:white; border: 1.5px solid #e2e8f0; border-radius: 20px; overflow:hidden;">

                {{-- TODAY'S CLASSES VIEW --}}
                <div x-show="viewMode === 'today'">
                    @php
                        $todaySchedules = $schedules->filter(fn($cs) => strcasecmp($cs->day, $targetDayName) === 0)->sortBy('start_time');
                    @endphp

                    @if($todaySchedules->isNotEmpty())
                        {{-- Table header --}}
                        <div class="s-table-header" style="grid-template-columns: 1.8fr 1.2fr 1.6fr auto; padding: 0.75rem 1.25rem;">
                            <div class="s-table-header-label">Subject Name</div>
                            <div class="s-table-header-label">Teacher</div>
                            <div class="s-table-header-label">Class Time</div>
                            <div class="s-table-header-label" style="text-align:right;">Join Link</div>
                        </div>

                        @php
                            $colors = ['#059669','#0ea5e9','#8b5cf6','#f59e0b','#ec4899','#14b8a6','#ef4444','#f97316'];
                            $bgs    = ['#ecfdf5','#eff6ff','#f5f3ff','#fffbeb','#fdf2f8','#f0fdfa','#fef2f2','#fff7ed'];
                        @endphp
                        @foreach ($todaySchedules as $i => $sched)
                            @php
                                $c = $colors[$i % count($colors)];
                                $bg = $bgs[$i % count($bgs)];
                                // Find associated subject to get details
                                $subj = $subjects->firstWhere('subject_name', $sched->subject_name);
                                $isSpecial = str_contains(strtolower($sched->subject_name), 'transition') || 
                                             str_contains(strtolower($sched->subject_name), 'recess') || 
                                             str_contains(strtolower($sched->subject_name), 'break') || 
                                             str_contains(strtolower($sched->subject_name), 'general assembly') ||
                                             str_contains(strtolower($sched->subject_name), 'homeroom');
                                $rawTeacher = $isSpecial ? '—' : (!empty($sched->teacher_display) ? $sched->teacher_display : ($subj ? $subj->teacher_name : null));
                                $currentTeacherName = $formatTeacherName($rawTeacher);
                                $timeStr = date('g:i A', strtotime($sched->start_time)) . ' - ' . date('g:i A', strtotime($sched->end_time));
                                $teamUrl = $subj->team_url ?? 'https://teams.microsoft.com/';
                                $isLive = false;
                                $startTime = strtotime(date('Y-m-d') . ' ' . $sched->start_time);
                                $endTime = strtotime(date('Y-m-d') . ' ' . $sched->end_time);
                                if ($startTime !== false && $endTime !== false) {
                                    $now = time();
                                    if ($now >= $startTime && $now <= $endTime) {
                                        $isLive = true;
                                    }
                                }
                            @endphp
                            <div class="s-table-row" style="grid-template-columns: 1.8fr 1.2fr 1.8fr 1.4fr auto; padding: 1rem 1.25rem; align-items: center; border-bottom: 1px solid #f1f5f9; position: relative;">
                                @if($isLive)
                                    <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #10b981; border-top-left-radius: 4px; border-bottom-left-radius: 4px;"></div>
                                @endif
                                <div style="display:flex;align-items:center;gap:0.75rem;min-width:0;">
                                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $c }};flex-shrink:0;box-shadow: 0 0 0 3px {{ $bg }};"></div>
                                    <span class="s-table-cell-subject" style="font-weight: 800; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $sched->subject_name }}
                                        @if($isLive)
                                            <span style="font-size:0.65rem;font-weight:850;color:white;background:#ef4444;padding:0.1rem 0.35rem;border-radius:5px;text-transform:uppercase;margin-left:0.35rem;display:inline-block;animation: pulse-dot 1.5s infinite;">LIVE</span>
                                        @endif
                                    </span>
                                </div>
                                <div style="display:flex;align-items:center;gap:0.5rem;min-width:0;">
                                    <span class="s-table-cell-teacher" style="font-weight: 750; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $currentTeacherName }}</span>
                                </div>
                                
                                {{-- Microsoft Team Name & Status --}}
                                <div style="display:flex;flex-direction:column;gap:2px;min-width:0;padding-right:0.5rem;">
                                     <span style="font-size:0.8rem;font-weight:800;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $sched->ms_team_name }}">{{ $sched->ms_team_name }}</span>
                                     <div>
                                         @if($sched->membership_status === 'enrolled')
                                             <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;">
                                                 <span style="width:4px;height:4px;background:#16a34a;border-radius:50%;"></span>Enrolled
                                             </span>
                                         @elseif($sched->membership_status === 'not_enrolled')
                                             <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Not yet enrolled in Microsoft Teams. Click 'Sync MS Teams' to retry.">
                                                 <span style="width:4px;height:4px;background:#ea580c;border-radius:50%;"></span>Not Enrolled
                                             </span>
                                         @else
                                             <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fca5a5;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Section has no Microsoft Team ID.">
                                                 <span style="width:4px;height:4px;background:#dc2626;border-radius:50%;"></span>No Team ID
                                             </span>
                                         @endif
                                     </div>
                                </div>
                                <div class="s-table-cell-schedule" style="color:#0d9488; font-weight:800; white-space: nowrap; font-size: 0.78rem;">
                                    <div style="display:flex;align-items:center;gap:0.3rem;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <span style="letter-spacing: -0.01em;">{{ $timeStr }}</span>
                                    </div>
                                </div>
                                <div>
                                    @if($subj && $subj->ms_channel_id)
                                        @if($sched->is_joinable)
                                             <a href="{{ $teamUrl }}" onclick="event.preventDefault(); window.joinTeams('{{ $teamUrl }}');"
                                                style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:900;color:#ffffff;background:#5865f2;padding:0.45rem 0.9rem;border-radius:8px;text-decoration:none;transition:all 0.15s;cursor:pointer;"
                                                onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#5865f2'">
                                                 <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg>
                                                 <span>Join</span>
                                             </a>
                                         @else
                                             <button type="button" disabled
                                                style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:900;color:#94a3b8;background:#f1f5f9;border:1px solid #e2e8f0;padding:0.45rem 0.9rem;border-radius:8px;cursor:not-allowed;opacity:0.8;"
                                                title="{{ $sched->membership_status_label }}">
                                                 <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                 <span>Join</span>
                                             </button>
                                         @endif
                                    @else
                                        <span style="font-size:0.75rem;color:#94a3b8;font-weight:700;">No Link</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="s-empty-card" style="padding: 4rem 1.5rem; text-align:center;">
                            <div class="s-empty-icon-wrapper" style="background: #f0fdfa; display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:50%; margin-bottom: 0.75rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                            </div>
                            <h3 class="s-empty-title" style="font-size: 1.15rem; font-weight:800; color:#1e293b; margin:0 0 0.25rem;">No Classes Scheduled Today</h3>
                            <p class="s-empty-text" style="font-size: 0.85rem; color:#64748b; max-width: 340px; margin: 0 auto; line-height: 1.5;">
                                @if(in_array($todayName, ['Friday', 'Saturday']))
                                    Happy weekend! Enjoy your rest and recharge time. ☀️
                                @else
                                    You have no classes scheduled for today. Happy studying! 🎈
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                {{-- WEEKLY OVERVIEW VIEW --}}
                <div x-show="viewMode === 'weekly'">
                    @if($subjects->isNotEmpty())
                        <div class="s-table-header" style="grid-template-columns: 1.8fr 1.2fr 1.4fr 1.5fr auto; padding: 0.75rem 1.25rem;">
                            <div class="s-table-header-label">Subject Name</div>
                            <div class="s-table-header-label">Teacher</div>
                            <div class="s-table-header-label">MS Team & Status</div>
                            <div class="s-table-header-label">Weekly Schedule</div>
                            <div class="s-table-header-label" style="text-align:right;">Join Link</div>
                        </div>

                        @php
                            $colors = ['#059669','#0ea5e9','#8b5cf6','#f59e0b','#ec4899','#14b8a6','#ef4444','#f97316'];
                            $bgs    = ['#ecfdf5','#eff6ff','#f5f3ff','#fffbeb','#fdf2f8','#f0fdfa','#fef2f2','#fff7ed'];
                        @endphp
                        @foreach ($subjects as $i => $subject)
                            @php
                                $c = $colors[$i % count($colors)];
                                $bg = $bgs[$i % count($bgs)];
                                $currentTeacherName = $formatTeacherName($subject->teacher_name);
                                $schedStr = $formattedSchedules[$subject->subject_name] ?? 'To Be Announced';
                            @endphp
                            <div class="s-table-row" style="grid-template-columns: 1.8fr 1.2fr 1.4fr 1.5fr auto; padding: 1rem 1.25rem; align-items: center; border-bottom: 1px solid #f1f5f9;">
                                <div style="display:flex;align-items:center;gap:0.75rem;min-width:0;">
                                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $c }};flex-shrink:0;box-shadow: 0 0 0 3px {{ $bg }};"></div>
                                    <span class="s-table-cell-subject" style="font-weight: 800; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $subject->subject_name }}">{{ $subject->subject_name }}</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:0.5rem;min-width:0;">
                                    <span class="s-table-cell-teacher" style="font-weight: 750; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $currentTeacherName }}">{{ $currentTeacherName }}</span>
                                </div>
                                
                                {{-- Microsoft Team Name & Status --}}
                                <div style="display:flex;flex-direction:column;gap:2px;min-width:0;padding-right:0.5rem;">
                                     <span style="font-size:0.8rem;font-weight:800;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $subject->ms_team_name }}">{{ $subject->ms_team_name }}</span>
                                     <div>
                                         @if($subject->membership_status === 'enrolled')
                                             <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#15803d;background:#f0fdf4;border:1px solid #bbf7d0;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;">
                                                 <span style="width:4px;height:4px;background:#16a34a;border-radius:50%;"></span>Enrolled
                                             </span>
                                         @elseif($subject->membership_status === 'not_enrolled')
                                             <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Not yet enrolled in Microsoft Teams. Click 'Sync MS Teams' to retry.">
                                                 <span style="width:4px;height:4px;background:#ea580c;border-radius:50%;"></span>Not Enrolled
                                             </span>
                                         @else
                                             <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.65rem;font-weight:700;color:#b91c1c;background:#fef2f2;border:1px solid #fca5a5;padding:0.1rem 0.4rem;border-radius:5px;white-space:nowrap;" title="Section has no Microsoft Team ID.">
                                                 <span style="width:4px;height:4px;background:#dc2626;border-radius:50%;"></span>No Team ID
                                             </span>
                                         @endif
                                     </div>
                                </div>

                                <div class="s-table-cell-schedule" style="color: #0d9488; font-weight: 800; font-size: 0.825rem;">
                                    <div style="display:flex;align-items:center;gap:0.35rem;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <span title="{{ $schedStr }}">{{ $schedStr }}</span>
                                    </div>
                                </div>
                                <div>
                                    @if($subject->ms_channel_id)
                                        @if($subject->is_joinable)
                                             <a href="{{ $subject->team_url ?? 'https://teams.microsoft.com' }}" onclick="event.preventDefault(); window.joinTeams('{{ $subject->team_url ?? 'https://teams.microsoft.com' }}');"
                                                style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:900;color:#ffffff;background:#5865f2;padding:0.45rem 0.9rem;border-radius:8px;text-decoration:none;transition:all 0.15s;cursor:pointer;"
                                                onmouseover="this.style.background='#4752c4'" onmouseout="this.style.background='#5865f2'">
                                                 <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect width="14" height="12" x="2" y="6" rx="2" ry="2"/></svg>
                                                 <span>Join</span>
                                             </a>
                                        @else
                                             <button type="button" disabled
                                                style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.75rem;font-weight:900;color:#94a3b8;background:#f1f5f9;border:1px solid #e2e8f0;padding:0.45rem 0.9rem;border-radius:8px;cursor:not-allowed;opacity:0.8;"
                                                title="{{ $subject->membership_status_label }}">
                                                 <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                 <span>Join</span>
                                             </button>
                                        @endif
                                    @else
                                        <span style="font-size:0.75rem;color:#94a3b8;font-weight:700;">No Link</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="s-empty-card" style="padding: 4rem 1.5rem; text-align:center;">
                            <div class="s-empty-icon-wrapper" style="background: #f0fdfa; display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:50%; margin-bottom: 0.75rem;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                            </div>
                            <h3 class="s-empty-title" style="font-size: 1.15rem; font-weight:800; color:#1e293b; margin:0 0 0.25rem;">No Subjects Assigned Yet</h3>
                            <p class="s-empty-text" style="font-size: 0.85rem; color:#64748b; max-width: 340px; margin: 0 auto; line-height: 1.5;">
                                Welcome to your portal! We are currently setting up your sections, schedule, and subjects. Please check back soon! 🎈
                            </p>
                        </div>
                    @endif
                </div>

            </div>
        </div>

    </div>

    {{-- ── RIGHT PANEL ───────────────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:1.5rem;" class="fade-up">

        {{-- Announcement Card --}}
        <div class="s-quick-actions-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
                <h3 class="s-quick-actions-title" style="margin-bottom:0 !important;">Announcement</h3>
            </div>
            
            <div style="display:flex;flex-direction:column;gap:1rem;">
                @forelse(array_slice($announcements, 0, 3) as $announcement)
                    @php
                        $toneColors = [
                            'emerald' => ['#059669', '#ecfdf5'],
                            'sky' => ['#0ea5e9', '#f0f9ff'],
                            'amber' => ['#d97706', '#fffbeb']
                        ];
                        $tc = $toneColors[$announcement['tone']] ?? $toneColors['emerald'];
                    @endphp
                    <div style="padding: 1rem; border-radius: 12px; background: #f8fafc; border: 1.5px solid #e2e8f0; display:flex; flex-direction:column; gap:0.5rem;">
                        <div style="display:flex; align-items:center; justify-content: space-between;">
                            <div style="display:flex; align-items:center; gap:0.375rem;">
                                <span style="font-size: 0.75rem; font-weight: 850; color: {{ $tc[0] }}; background: {{ $tc[1] }}; padding: 0.2rem 0.5rem; border-radius: 6px; text-transform: uppercase;">
                                    {{ $announcement['type'] }}
                                </span>
                                @if(!$announcement['is_read'])
                                    <span style="font-size: 0.65rem; font-weight: 850; color: white; background: #ef4444; padding: 0.15rem 0.4rem; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.03em;">
                                        New
                                    </span>
                                @endif
                            </div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; display:inline-flex; align-items:center; gap:0.375rem;">
                                <span>{{ $announcement['date'] }}</span>
                                <span style="display:inline-flex; align-items:center; gap:0.15rem;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:#94a3b8; vertical-align:middle;"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    {{ $announcement['total_views'] }}
                                </span>
                            </span>
                        </div>
                        <h4 style="font-size: 0.95rem; font-weight: 850; color: #0f172a; margin: 0; line-height: 1.3;">
                            {{ $announcement['title'] }}
                        </h4>
                        <p style="font-size: 0.85rem; font-weight: 650; color: #475569; margin: 0; line-height: 1.5;">
                            {{ $announcement['summary'] }}
                        </p>
                    </div>
                @empty
                    <div style="text-align:center;padding:1.5rem;color:#475569;font-weight:700;font-size:0.9rem;">
                        No announcements yet.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Digital Student ID Card --}}
        <div class="s-quick-actions-card" style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%); color: white; border: none; padding: 1.5rem; position: relative; overflow: hidden; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.3);">
            {{-- Subtle background decoration --}}
            <div style="position: absolute; right: -20px; bottom: -20px; width: 120px; height: 120px; border-radius: 50%; background: rgba(255, 255, 255, 0.08); pointer-events: none;"></div>
            
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; color: white;">
                    <i data-lucide="contact" style="width: 20px; height: 20px;"></i>
                </div>
                <h3 style="font-size: 1.15rem; font-weight: 850; color: white; margin: 0; letter-spacing: -0.01em;">Digital Student ID</h3>
            </div>
            
            <p style="font-size: 0.85rem; font-weight: 600; line-height: 1.5; color: rgba(255, 255, 255, 0.9); margin: 0 0 1.25rem;">
                Use Digital ID? Can't find your physical ID card? Instantly access your official digital student ID.
            </p>
            
            <button type="button" @click="showIdModal = true; isFlipped = false" 
               style="display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 850; color: #0f766e; background: white; border: none; padding: 0.55rem 1.15rem; border-radius: 10px; text-decoration: none; width: 100%; text-align: center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); transition: all 0.2s; cursor: pointer;"
               onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 15px rgba(0, 0, 0, 0.15)';"
               onmouseout="this.style.transform='none';this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)';">
                <i data-lucide="qr-code" style="width: 14px; height: 14px;"></i>
                <span>View Digital ID</span>
            </button>
        </div>

    </div>

</div>

{{-- Digital ID Modal Overlay --}}
<div x-show="showIdModal" 
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
    
    {{-- Backdrop blur overlay --}}
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="showIdModal = false"></div>
    
    {{-- Modal Content Card --}}
    <div class="relative bg-white/95 backdrop-blur-md rounded-3xl p-6 shadow-2xl max-w-sm w-full mx-auto flex flex-col items-center gap-5 border border-white/20 z-10"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">
        
        {{-- Close button --}}
        <button type="button" @click="showIdModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 p-1.5 rounded-full transition cursor-pointer border-none" style="display: flex; align-items: center; justify-content: center;">
            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
        </button>

        {{-- Verification Status --}}
        <div class="w-full flex items-center gap-3 bg-emerald-50/80 border border-emerald-100/50 p-3 rounded-2xl" style="display: flex; flex-direction: row; text-align: left;">
            <div class="h-9 w-9 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-600 flex-shrink-0">
                <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
            </div>
            <div>
                <h4 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider" style="margin: 0;">Officially Enrolled</h4>
                <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider mt-0.5" style="margin: 0;">Active AMIS ID</p>
            </div>
        </div>

        {{-- 3D Flipping Card Container --}}
        <div class="perspective-1000 w-[290px] h-[450px] cursor-pointer"
             @click="isFlipped = !isFlipped">
            
            <div class="card-inner w-full h-full relative"
                 :class="isFlipped ? 'is-flipped' : ''">
                
                {{-- FRONT OF THE ID CARD --}}
                <div class="card-front holo-card absolute inset-0 w-full h-full rounded-[24px] bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-900 shadow-2xl p-5 border border-emerald-600/30 text-white flex flex-col justify-between overflow-hidden" style="box-sizing: border-box; text-align: center;">
                    <div class="absolute inset-0 holo-overlay opacity-30 mix-blend-overlay"></div>
                    
                    {{-- Header --}}
                    <div class="flex items-center gap-2 border-b border-emerald-500/30 pb-2.5 relative z-10" style="display: flex; flex-direction: row; text-align: left;">
                        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" class="h-9 w-auto bg-white/10 p-1 rounded-lg">
                        <div>
                            <span class="font-bold text-[9px] tracking-wider block uppercase opacity-85 leading-tight">AL-MUNAWWARAH</span>
                            <span class="text-[10px] font-extrabold tracking-wider block text-emerald-300 uppercase leading-none">International School</span>
                        </div>
                    </div>

                    {{-- Student Photo & ID Info --}}
                    <div class="flex flex-col items-center my-3 relative z-10" style="display: flex; align-items: center; justify-content: center;">
                        <div class="h-[120px] w-[120px] rounded-2xl overflow-hidden border-2 border-emerald-400/40 shadow-inner bg-slate-900/50 flex items-center justify-center">
                            @if($photo)
                                <img src="{{ asset('storage/' . $photo) }}" alt="Student Photo" class="h-full w-full object-cover">
                            @else
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; background: rgba(255,255,255,0.05);">
                                    <span style="font-size: 2.25rem; font-weight: 900; color: white;">{{ $initials }}</span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Holo Badge Seal --}}
                        <div class="absolute top-[85px] right-[45px] h-8 w-8 rounded-full bg-gradient-to-tr from-cyan-400 via-pink-400 to-yellow-300 opacity-60 border border-white/20 flex items-center justify-center shadow-lg mix-blend-screen animate-pulse">
                            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="Seal" class="h-5 w-auto opacity-75">
                        </div>
                    </div>

                    {{-- Student Details --}}
                    <div class="text-center relative z-10 flex-grow flex flex-col justify-center" style="display: flex; flex-direction: column; align-items: center;">
                        <span class="text-[8px] uppercase tracking-widest text-emerald-300 font-bold block mb-0.5">Student Name</span>
                        <div class="px-1">
                            <h3 class="text-base font-extrabold tracking-tight truncate leading-tight" style="margin: 0;">{{ mb_strtoupper($fullName) }}</h3>
                        </div>
                        
                        <div class="mt-2.5 grid grid-cols-2 gap-2 border-t border-emerald-500/20 pt-2 mx-auto w-full" style="display: grid; grid-template-columns: 1fr 1fr; width: 100%;">
                            <div>
                                <span class="text-[7px] uppercase tracking-wider text-emerald-300 font-bold block opacity-75">Grade Level</span>
                                <span class="text-[11px] font-bold">{{ $student?->grade_level }}</span>
                            </div>
                            <div>
                                <span class="text-[7px] uppercase tracking-wider text-emerald-300 font-bold block opacity-75">School Year</span>
                                <span class="text-[11px] font-bold">{{ $student?->school_year }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer & QR Code --}}
                    <div class="flex items-end justify-between border-t border-emerald-500/30 pt-2.5 relative z-10 mt-auto" style="display: flex; flex-direction: row; align-items: flex-end; text-align: left;">
                        <div class="text-left">
                            <span class="text-[7px] uppercase tracking-widest text-emerald-300 font-bold block opacity-85">Student Number</span>
                            @php
                                $displayId = $student?->student_number;
                                if (is_numeric($displayId) && strlen($displayId) >= 6) {
                                    $year = '20' . substr($displayId, 0, 2);
                                    $seq = (int) substr($displayId, 2);
                                    $displayId = 'AMIS-' . $year . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
                                }
                            @endphp
                            <span class="text-xs font-extrabold tracking-wider text-white">{{ $displayId }}</span>
                        </div>
                        <div class="bg-white p-1 rounded-lg shadow-md border border-white/10 flex-shrink-0" style="display: flex; align-items: center; justify-content: center;">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode('https://amis.edu.ph/id?id=' . ($student?->student_number ?? '')) }}&size=150x150&color=000000&light=ffffff&margin=1&format=png&size=300" alt="QR Verification" class="h-9 w-9">
                        </div>
                    </div>
                </div>

                {{-- BACK OF THE ID CARD --}}
                <div class="card-back absolute inset-0 w-full h-full rounded-[24px] bg-gradient-to-br from-slate-900 to-slate-800 shadow-2xl p-5 border border-slate-700/50 text-white flex flex-col justify-between overflow-hidden" style="box-sizing: border-box;">
                    
                    {{-- Header Info --}}
                    <div class="text-center border-b border-slate-700/60 pb-2">
                        <h4 class="text-[9px] font-bold text-emerald-400 uppercase tracking-widest" style="margin: 0;">Student Information & Security</h4>
                    </div>

                    {{-- Back Card Details --}}
                    @php
                        $father = trim(($student?->applicant?->father_first_name ?? '') . ' ' . ($student?->applicant?->father_last_name ?? ''));
                        $mother = trim(($student?->applicant?->mother_first_name ?? '') . ' ' . ($student?->applicant?->mother_last_name ?? ''));
                        $parent = $father ?: ($mother ?: ($student?->applicant?->emergency_name ?? 'Registrar Office'));
                        $address = $student?->applicant?->address ?: ($student?->applicant?->home_address ?: ($student?->applicant?->street_address ?? 'Davao City, Philippines'));
                    @endphp
                    <div class="space-y-3.5 my-3 flex-grow flex flex-col justify-center text-left" style="display: flex; flex-direction: column; text-align: left;">
                        <div>
                            <span class="text-[7px] uppercase tracking-widest text-slate-400 font-bold block mb-0.5">Parent / Guardian</span>
                            <span class="text-[11px] font-semibold block text-slate-200">{{ $parent }}</span>
                        </div>

                        <div>
                            <span class="text-[7px] uppercase tracking-widest text-slate-400 font-bold block mb-0.5">Home Address</span>
                            <span class="text-[11px] font-semibold block text-slate-200 leading-relaxed max-w-[240px]">{{ $address }}</span>
                        </div>

                        <div class="bg-slate-900/40 border border-slate-800 p-2 rounded-xl text-[8px] text-slate-400 leading-relaxed text-center">
                            This card is non-transferable and must be worn at all times while on school premises. Loss must be reported to the registrar office immediately.
                        </div>
                    </div>

                    {{-- Barcode & Contacts --}}
                    <div class="border-t border-slate-700/60 pt-3 flex flex-col items-center gap-2" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        {{-- Simulated Barcode --}}
                        <div class="bg-white p-1.5 rounded-md w-full flex items-center justify-center">
                            <div class="flex items-stretch justify-center h-8 w-full bg-slate-900 max-w-[180px]" style="background: repeating-linear-gradient(90deg, #0f172a 0px, #0f172a 2px, #ffffff 2px, #ffffff 5px, #0f172a 5px, #0f172a 7px);"></div>
                        </div>
                        <div class="text-center text-[7px] text-slate-400">
                            <span>registrar@amis.edu.ph</span>
                            <span class="mx-1">•</span>
                            <span>+63 900 000 0000</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Manual Flip Action Helper --}}
        <button type="button" @click="isFlipped = !isFlipped"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 hover:border-slate-300 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 transition-all duration-200 cursor-pointer shadow-sm bg-white" style="display: inline-flex; align-items: center; justify-content: center;">
            <i data-lucide="refresh-cw" style="width: 12px; height: 12px; color: #64748b;"></i>
            <span>Flip ID Card</span>
        </button>
    </div>
</div>

</x-student-layout>
