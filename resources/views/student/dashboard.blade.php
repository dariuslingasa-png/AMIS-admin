<x-student-layout title="Student Dashboard" :heading="'Student Dashboard School Year ' . ($student?->school_year ? str_replace(['–', '-'], ' - ', preg_replace('/\s+/', '', $student->school_year)) : '2026 - 2027')">

@php
    $photo     = $student?->applicant?->photo_2x2_url;
    $firstName = $student?->applicant?->first_name ?? $user->name;
    $lastName  = $student?->applicant?->last_name ?? '';
    $fullName  = $student?->applicant
        ? $student->applicant->first_name . ' ' . $student->applicant->last_name
        : $user->name;
    $account   = $student?->account;
    $initials  = collect(explode(' ', $fullName))->map(fn($w) => strtoupper($w[0] ?? ''))->take(2)->join('');

    // Digital ID Card configuration variables
    $studentIdRaw = $student?->student_number;
    $displayStudentId = $studentIdRaw;
    if (is_numeric($studentIdRaw) && strlen($studentIdRaw) >= 6) {
        $year = '20' . substr($studentIdRaw, 0, 2);
        $seq = (int) substr($studentIdRaw, 2);
        $displayStudentId = 'AMIS-' . $year . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }
    $badgeStudentId = $studentIdRaw;
    if (str_starts_with(strtoupper($displayStudentId), 'AMIS-')) {
        $parts = explode('-', $displayStudentId);
        $badgeStudentId = substr($parts[1], 2, 2) . str_pad((int)$parts[2], 4, '0', STR_PAD_LEFT);
    }

    $father = trim(($student?->applicant?->father_first_name ?? '') . ' ' . ($student?->applicant?->father_last_name ?? ''));
    $mother = trim(($student?->applicant?->mother_first_name ?? '') . ' ' . ($student?->applicant?->mother_last_name ?? ''));
    $parent = $father ?: ($mother ?: ($student?->applicant?->emergency_name ?? ''));
    $contactNo = ($student?->applicant?->emergency_phone ?? null) ?: (($student?->applicant?->parent_mobile ?? null) ?: ($student?->applicant?->mobile_number ?? ''));
    $address = $student?->applicant?->address ?: ($student?->applicant?->home_address ?? '');

    $isParentMissing = empty(trim($parent)) || strtolower(trim($parent)) === 'emergency contact';
    $isAddressMissing = empty(trim($address)) || strtolower(trim($address)) === 'davao city, philippines';
    $isEmergencyMissing = $isParentMissing || $isAddressMissing;

    $hash = base64_encode((int)$student->student_number + 987654);
    $qrCodeUrl = 'https://quickchart.io/qr?text=' . urlencode('https://amis.edu.ph/v/' . $hash) . '&dark=000000&light=ffffff&margin=1&format=png&size=300';
    $fallbackPhoto = 'https://amis.edu.ph/student-photo/' . $hash . '.jpg';
    $photoUrl = \App\Support\EnrollmentStorage::url($photo);

    $learningMode = $student?->applicant?->learning_mode;
    if ($learningMode) {
        $learningMode = str_ireplace('Flexible Online Learning', 'ODL', $learningMode);
        $learningMode = str_ireplace('Face-to-Face', 'F2F', $learningMode);
    }

    $rawStudentType = strtolower(trim((string) ($student?->applicant?->student_type ?? '')));
    if (str_contains($rawStudentType, 'new') || str_contains($rawStudentType, 'transferee')) {
        $studentTypeDisplay = 'NEW STUDENT';
        $isOldStudent = false;
    } else {
        $studentTypeDisplay = 'OLD STUDENT';
        $isOldStudent = true;
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

    $nowManila = \Carbon\Carbon::now('Asia/Manila');
    $todayName = $nowManila->format('l');
    $countdownItems = $schedules
        ->filter(fn($item) => strcasecmp((string) $item->day, $todayName) === 0)
        ->sortBy('start_time')
        ->map(fn($item) => [
            'subject' => (string) $item->subject_name,
            'teacher' => $formatTeacherName($item->teacher_display ?? $item->teacher_name ?? null),
            'start' => substr((string) $item->start_time, 0, 5),
            'end' => substr((string) $item->end_time, 0, 5),
            'team_url' => (string) ($item->team_url ?? ''),
        ])->values();
    $isSchoolDay = in_array($todayName, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);

    if ($isSchoolDay) {
        $targetDayName = $todayName;
        $todayLabel = "Today's Schedule";
        $todaySub = $nowManila->format('l, F j, Y') . ' (PST)';
    } else {
        $targetDayName = 'Sunday';
        $todayLabel = "Next School Day Schedule";
        $nextSunday = $nowManila->copy()->next('Sunday');
        $todaySub = "Sunday, " . $nextSunday->format('F j, Y') . ' (PST)';
    }

    // Financial Statement of Account (SOA) Summary Data
    $soaTotal = (float) ($account->total_balance ?? 0);
    $soaPaid = (float) ($account->amount_paid ?? 0);
    $soaRemaining = (float) ($account->remaining_balance ?? 0);
    $soaProgress = $soaTotal > 0 ? min(100, max(0, ($soaPaid / $soaTotal) * 100)) : 0;

    $billings = $account?->monthlyBillings ?? collect();
    $soaPaidInstallments = $billings->where('status', 'paid')->count();
    $soaUnpaidInstallments = $billings->where('status', 'unpaid');
    $soaNextBilling = $soaUnpaidInstallments->sortBy('due_date')->first();

    $soaAccountStatus = !$account
        ? 'No Balance'
        : ($soaRemaining <= 0 ? 'Fully Paid' : ($soaPaid > 0 ? 'Partially Paid' : 'Payment Due'));

    $latestPayment = $payments->sortByDesc('created_at')->first();
    $m365Email = $student?->school_email ?? ($student?->ms_email ?? Auth::user()->email);
@endphp

@once
<style>
    /* Pulse Live Dot */
    @keyframes live-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.45; transform: scale(1.18); }
    }
    .live-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
        animation: live-pulse 1.8s ease-in-out infinite;
        display: inline-block;
    }

    /* 3D Card Flip styling for Digital ID */
    .perspective-1000 {
        perspective: 1200px;
    }
    .card-inner {
        transform-style: preserve-3d;
        transition: transform 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%;
        width: 100%;
        position: relative;
    }
    .card-front, .card-back {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        transform-style: preserve-3d;
    }
    .card-front {
        transform: rotateY(0deg);
        z-index: 2;
    }
    .card-back {
        transform: rotateY(180deg);
        z-index: 1;
    }
    .is-flipped {
        transform: rotateY(180deg);
    }
    .holo-overlay {
        background: linear-gradient(135deg, 
            rgba(255,255,255,0) 0%, 
            rgba(255,255,255,0) 40%, 
            rgba(255, 255, 255, 0.25) 50%, 
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

    /* Digital ID Modal Custom Classes */
    .id-modal-overlay {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 99999 !important;
        display: flex;
        align-items: center !important;
        justify-content: center !important;
        padding: 1rem !important;
        box-sizing: border-box !important;
    }
    .id-modal-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: rgba(15, 23, 42, 0.65) !important;
        backdrop-filter: blur(8px) !important;
        -webkit-backdrop-filter: blur(8px) !important;
        z-index: 1 !important;
    }
    .id-modal-card {
        position: relative !important;
        background: rgba(255, 255, 255, 0.98) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border-radius: 28px !important;
        padding: 1.75rem !important;
        box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.3) !important;
        max-width: 350px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 1.25rem !important;
        border: 1px solid rgba(255, 255, 255, 0.6) !important;
        z-index: 2 !important;
        text-align: center !important;
    }
    .id-modal-close-btn {
        position: absolute !important;
        top: 1.15rem !important;
        right: 1.15rem !important;
        z-index: 50 !important;
        border: none !important;
        background: #f1f5f9 !important;
        color: #64748b !important;
        width: 30px !important;
        height: 30px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }
    .id-modal-close-btn:hover {
        background: #e2e8f0 !important;
        color: #1e293b !important;
    }
    .id-card-front-content {
        background: linear-gradient(135deg, #064e3b 0%, #065f46 45%, #0d9488 100%) !important;
        box-sizing: border-box !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 22px !important;
        padding: 1.35rem !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        box-shadow: 0 16px 36px -10px rgba(6, 78, 59, 0.35) !important;
        color: white !important;
        position: relative !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }
    .id-card-back-content {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        box-sizing: border-box !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 22px !important;
        padding: 1.35rem !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        box-shadow: 0 16px 36px -10px rgba(15, 23, 42, 0.4) !important;
        color: white !important;
        position: relative !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }

    /* Live Countdown Banner inside SOA Card */
    .soa-live-bar {
        background: linear-gradient(135deg, #064e3b 0%, #047857 60%, #0d9488 100%);
        border-radius: 18px;
        padding: 18px 22px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        position: relative;
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: 0 12px 28px rgba(6, 78, 59, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.16);
    }
    .soa-live-bar::after {
        content: '';
        position: absolute;
        right: -30px;
        top: -40px;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }
    .soa-live-bar__clock {
        min-width: 115px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 14px;
        padding: 8px 14px;
        text-align: center;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .soa-live-bar__clock strong {
        display: block;
        font-variant-numeric: tabular-nums;
        font-size: 1.5rem;
        font-weight: 900;
        letter-spacing: 0.03em;
        line-height: 1.1;
    }
    .soa-live-bar__clock span {
        display: block;
        margin-top: 2px;
        color: #a7f3d0;
        font-size: 0.62rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    /* Small Tag Pills */
    .soa-tag-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .soa-tag-pill--live {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .soa-tag-pill--ended {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    .soa-tag-pill--upcoming {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    @media (max-width: 640px) {
        .soa-live-bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
        }
        .soa-live-bar__clock {
            width: 100%;
            box-sizing: border-box;
        }
    }
</style>
<script>
    window.scheduleCountdown = function(items) {
        return {
            showIdModal: false,
            isFlipped: false,
            showEnded: false,
            copiedEmail: false,
            items: items || [],
            activeClass: null,
            nextClass: null,
            phase: 'loading',
            remaining: '--:--',
            timer: null,
            init() {
                this.tick();
                this.timer = window.setInterval(() => this.tick(), 1000);
            },
            toDate(time) {
                if (!time) return new Date();
                const [hours, minutes] = time.split(':').map(Number);
                const date = new Date();
                date.setHours(hours, minutes, 0, 0);
                return date;
            },
            tick() {
                const now = new Date();
                const classes = this.items.map(item => ({
                    ...item,
                    startsAt: this.toDate(item.start),
                    endsAt: this.toDate(item.end)
                }));
                this.activeClass = classes.find(item => now >= item.startsAt && now < item.endsAt) || null;
                this.nextClass = classes.find(item => now < item.startsAt) || null;
                let target = null;
                if (!classes.length) {
                    this.phase = 'empty';
                } else if (this.activeClass) {
                    this.phase = 'active';
                    target = this.activeClass.endsAt;
                } else if (this.nextClass) {
                    this.phase = 'upcoming';
                    target = this.nextClass.startsAt;
                } else {
                    this.phase = 'finished';
                }
                if (target) {
                    const seconds = Math.max(0, Math.floor((target - now) / 1000));
                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const secs = seconds % 60;
                    this.remaining = `${hours ? String(hours).padStart(2,'0') + ':' : ''}${String(minutes).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
                } else {
                    this.remaining = '--:--';
                }
            },
            copyText(text) {
                if (!navigator.clipboard) return;
                navigator.clipboard.writeText(text);
                this.copiedEmail = true;
                setTimeout(() => this.copiedEmail = false, 2000);
            }
        };
    };
</script>
@endonce

<div class="soa-page" x-data="scheduleCountdown(@js($countdownItems))">

    {{-- Session Alerts --}}
    @if (session('success'))
        <div class="soa-alert" style="color: #065f46; border-color: #a7f3d0; background: #ecfdf5;" role="status">
            <span class="soa-alert__icon" style="color: #059669; background: #d1fae5;"><i data-lucide="check-circle-2"></i></span>
            <div>
                <strong>Success</strong>
                <p>{{ session('success') }}</p>
            </div>
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="soa-alert soa-alert--error" role="alert">
            <span class="soa-alert__icon"><i data-lucide="circle-alert"></i></span>
            <div>
                <strong>Attention Needed</strong>
                <p>{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    {{-- Coming Soon Banner for Face-to-Face Students --}}
    @php
        $isF2F = str_contains(strtolower($student?->applicant?->learning_mode ?? ''), 'face');
    @endphp
    @if($isF2F)
        <div class="soa-alert soa-alert--warning" role="status">
            <span class="soa-alert__icon"><i data-lucide="clock-3"></i></span>
            <div>
                <strong>Portal Coming Soon for Face-to-Face Students</strong>
                <p>We are currently setting up online records for Face-to-Face students. Your schedule, subjects, and grades will appear here shortly.</p>
            </div>
        </div>
    @endif

    {{-- ── 1. SOA-STYLE HERO HEADER ──────────────────────────────── --}}
    <section class="soa-hero" aria-labelledby="dashboard-welcome-title">
        <span class="soa-hero__orb soa-hero__orb--one" aria-hidden="true"></span>
        <span class="soa-hero__orb soa-hero__orb--two" aria-hidden="true"></span>

        <div class="soa-hero__content">
            <div class="soa-hero__eyebrow">
                <span><i data-lucide="sparkles"></i></span>
                AMIS Student Portal
            </div>

            <h2 id="dashboard-welcome-title">
                Assalamualaikum,<br>
                <em>{{ $firstName }}!</em>
            </h2>
            <p style="max-width: 620px; margin-top: 14px;">Your unified academic workspace. Follow your live daily classes, view official faculty instructions, monitor your report card, and track school finances.</p>

            <div class="soa-hero__meta" style="margin-top: 20px;">
                <span><i data-lucide="graduation-cap"></i> {{ $student?->grade_level ?: 'Grade 1' }} • {{ $section?->name ?? 'G1-AL-MUNAWWARA' }}</span>
                <span><i data-lucide="user-check"></i> {{ $studentTypeDisplay }}</span>
                <span><i data-lucide="id-card"></i> AMIS ID: <b>{{ $student?->student_number ?? '260000' }}</b></span>
            </div>
        </div>

        {{-- Hero Right Balance Card --}}
        <div class="soa-balance-card">
            <div class="soa-balance-card__top">
                <span>Remaining balance</span>
                <span class="soa-account-state {{ $soaRemaining <= 0 && $account ? 'is-settled' : '' }}">
                    <i></i>{{ $soaAccountStatus }}
                </span>
            </div>
            <strong><small>PHP</small> {{ number_format($soaRemaining, 2) }}</strong>
            <div class="soa-progress" role="progressbar" aria-label="Payment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($soaProgress) }}">
                <span style="width: {{ $soaProgress }}%"></span>
            </div>
            <div class="soa-balance-card__footer">
                <span><b>{{ number_format($soaProgress, 0) }}%</b> paid</span>
                <span>PHP {{ number_format($soaPaid, 2) }} of PHP {{ number_format($soaTotal, 2) }}</span>
            </div>
        </div>
    </section>

    {{-- ── 2. SOA TWO-COLUMN CONTENT GRID ────────────────────────── --}}
    <div class="soa-content-grid" id="today-classes">

        {{-- ── MAIN / LEFT COLUMN ────────────────────────────────── --}}
        <div class="soa-main-column">

            {{-- 1. Today's Classes & Live Timetable Card --}}
            <section class="soa-card">
                <header class="soa-card__header">
                    <div>
                        <span class="soa-section-kicker">Daily Timetable · {{ strtoupper($targetDayName) }}</span>
                        <h3>{{ $todayLabel }}</h3>
                        <p>{{ $todaySub }}</p>
                    </div>

                    @if($student && $student->ms_user_id && str_ends_with(strtolower($student->school_email), '@amis.edu.ph'))
                        <form method="POST" action="{{ route('student.sync-teams') }}" style="margin: 0; display: inline-block;">
                            @csrf
                            <button type="submit" class="soa-button soa-button--ghost" style="color: var(--soa-green); border-color: #a7f3d0; background: #ecfdf5; min-height: 36px; padding: 6px 13px; font-size: 11px;">
                                <i data-lucide="refresh-cw"></i>
                                Sync Teams
                            </button>
                        </form>
                    @endif
                </header>

                @php
                    $todaySchedules = $schedules->filter(fn($cs) => strcasecmp($cs->day, $targetDayName) === 0)->sortBy('start_time');
                    $nowForCollapse = \Carbon\Carbon::now('Asia/Manila');
                    $endedCount = $isSchoolDay
                        ? $todaySchedules->filter(function ($item) use ($nowForCollapse) {
                            $end = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nowForCollapse->format('Y-m-d').' '.$item->end_time, 'Asia/Manila');
                            return $nowForCollapse->greaterThanOrEqualTo($end);
                        })->count()
                        : 0;
                @endphp

                {{-- Live Classroom Banner (Active during current class) --}}
                <template x-if="phase === 'active' && activeClass">
                    <div class="soa-live-bar" role="region" aria-label="Class in session countdown">
                        <div>
                            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 800; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 4px;">
                                <span class="live-dot"></span> Live Class in Progress
                            </span>
                            <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #ffffff;" x-text="activeClass?.subject"></h3>
                            <p style="margin: 3px 0 0; font-size: 12px; color: #d1fae5;">
                                <span x-text="activeClass?.teacher"></span> · Ends at <span x-text="activeClass?.end"></span>
                            </p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="soa-live-bar__clock">
                                <strong x-text="remaining">--:--</strong>
                                <span>Remaining</span>
                            </div>
                            <template x-if="activeClass?.team_url">
                                <a :href="activeClass.team_url" target="_blank" rel="noopener noreferrer" class="soa-button soa-button--light" style="font-size: 11.5px; padding: 8px 14px; min-height: 38px;">
                                    <i data-lucide="video"></i>
                                    Join Teams
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                @if($todaySchedules->isNotEmpty())
                    <div class="soa-table-wrap">
                        <table class="soa-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Assigned Faculty</th>
                                    <th>Time</th>
                                    <th style="text-align: right;">Status / Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todaySchedules as $i => $sched)
                                    @php
                                        $subj = $subjects->firstWhere('subject_name', $sched->subject_name);
                                        $isSpecial = str_contains(strtolower($sched->subject_name), 'transition') || 
                                                     str_contains(strtolower($sched->subject_name), 'recess') || 
                                                     str_contains(strtolower($sched->subject_name), 'break') || 
                                                     str_contains(strtolower($sched->subject_name), 'general assembly') ||
                                                     str_contains(strtolower($sched->subject_name), 'homeroom');
                                        $rawTeacher = $isSpecial ? '—' : (!empty($sched->teacher_display) ? $sched->teacher_display : ($subj ? $subj->teacher_name : null));
                                        $currentTeacherName = $formatTeacherName($rawTeacher);
                                        $timeStr = date('g:i A', strtotime($sched->start_time)) . ' - ' . date('g:i A', strtotime($sched->end_time));
                                        $isLive = false;
                                        $isEnded = false;
                                        if ($isSchoolDay) {
                                            $startTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nowManila->format('Y-m-d') . ' ' . $sched->start_time, 'Asia/Manila');
                                            $endTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $nowManila->format('Y-m-d') . ' ' . $sched->end_time, 'Asia/Manila');
                                            if ($nowManila->between($startTime, $endTime)) {
                                                $isLive = true;
                                            } elseif ($nowManila->greaterThan($endTime)) {
                                                $isEnded = true;
                                            }
                                        }
                                    @endphp
                                    <tr style="{{ $isEnded ? 'opacity: 0.75; background: #fafafa;' : '' }} {{ $isLive ? 'background: #f0fdf4;' : '' }}">
                                        <td>
                                            <span class="soa-fee-icon" style="{{ $isLive ? 'background: #d1fae5; color: #047857;' : '' }}">
                                                <i data-lucide="{{ $isSpecial ? 'coffee' : 'book-open' }}"></i>
                                            </span>
                                            <div>
                                                <strong style="{{ $isEnded ? 'text-decoration: line-through;' : '' }}">{{ $sched->subject_name }}</strong>
                                                <small>{{ $section?->name ?? 'Official Curriculum' }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="font-weight: 700; color: #334155;">{{ $currentTeacherName }}</strong>
                                            <small style="color: #94a3b8;">Subject Teacher</small>
                                        </td>
                                        <td>
                                            <strong style="color: var(--soa-green); font-weight: 800; font-size: 11.5px;">{{ $timeStr }}</strong>
                                            <small style="color: #94a3b8;">PST (UTC+8)</small>
                                        </td>
                                        <td style="text-align: right;">
                                            @if($isLive)
                                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                    <span class="soa-tag-pill soa-tag-pill--live">
                                                        <span class="live-dot" style="width:6px; height:6px;"></span> LIVE
                                                    </span>
                                                    @if(!empty($sched->team_url))
                                                        <a href="{{ $sched->team_url }}" target="_blank" rel="noopener noreferrer" class="soa-button soa-button--primary" style="min-height: 30px; padding: 4px 10px; font-size: 10.5px; border-radius: 8px;">
                                                            <i data-lucide="video"></i> Join
                                                        </a>
                                                    @endif
                                                </div>
                                            @elseif($isEnded)
                                                <span class="soa-tag-pill soa-tag-pill--ended">
                                                    <i data-lucide="check" style="width: 11px; height: 11px;"></i> Completed
                                                </span>
                                            @else
                                                <div style="display: inline-flex; align-items: center; gap: 6px;">
                                                    <span class="soa-tag-pill soa-tag-pill--upcoming">Upcoming</span>
                                                    @if(!empty($sched->team_url))
                                                        <a href="{{ $sched->team_url }}" target="_blank" rel="noopener noreferrer" class="soa-button soa-button--ghost" style="min-height: 30px; padding: 4px 10px; font-size: 10.5px; border-radius: 8px; color: #2563eb; border-color: #cbdcfb; background: #eff6ff;">
                                                            <i data-lucide="external-link"></i> Teams
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="soa-empty">
                        <span><i data-lucide="calendar"></i></span>
                        <strong>No classes scheduled for {{ $targetDayName }}</strong>
                        <p>
                            @if(in_array($todayName, ['Friday', 'Saturday']))
                                Happy weekend! Take time to rest, review your lessons, and recharge.
                            @else
                                You have no official classes scheduled on this day. Happy studying!
                            @endif
                        </p>
                        <a href="{{ route('student.schedule') }}" class="soa-button soa-button--primary" style="margin-top: 14px;">
                            <i data-lucide="calendar-range"></i> View Full Weekly Timetable
                        </a>
                    </div>
                @endif
            </section>

            {{-- 2. School Announcements & Advisories Card --}}
            <section class="soa-card soa-history">
                <header class="soa-card__header soa-card__header--compact">
                    <div>
                        <span class="soa-section-kicker">Official Notices</span>
                        <h3>School Announcements &amp; Advisories</h3>
                        <p>Institutional reminders, administrative updates, and event schedules.</p>
                    </div>

                    <a href="{{ route('student.announcements') }}" class="soa-button soa-button--ghost" style="color: var(--soa-green); border-color: var(--soa-border); background: var(--soa-soft); min-height: 36px; padding: 6px 14px; font-size: 11px;">
                        All Notices →
                    </a>
                </header>

                @php
                    $recentAnnouncements = collect($announcements)->take(3);
                @endphp

                @if($recentAnnouncements->isNotEmpty())
                    <div class="soa-payment-list">
                        @foreach($recentAnnouncements as $notice)
                            @php
                                $toneColor = match($notice['tone'] ?? 'emerald') {
                                    'amber' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#b45309'],
                                    'sky' => ['bg' => '#eff6ff', 'border' => '#bfdbfe', 'text' => '#2563eb'],
                                    default => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#047857'],
                                };
                            @endphp
                            <a href="{{ route('student.announcements') }}" class="soa-payment" style="text-decoration: none; display: block;">
                                <div class="soa-payment__top">
                                    <span class="soa-payment__method" style="background: {{ $toneColor['bg'] }}; color: {{ $toneColor['text'] }};">
                                        <i data-lucide="megaphone"></i>
                                    </span>
                                    <div class="soa-payment__details">
                                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                            <span style="font-size: 9px; font-weight: 800; color: {{ $toneColor['text'] }}; background: {{ $toneColor['bg'] }}; border: 1px solid {{ $toneColor['border'] }}; padding: 1px 6px; border-radius: 4px; text-transform: uppercase;">
                                                {{ $notice['type'] }}
                                            </span>
                                            @if(!$notice['is_read'])
                                                <span style="font-size: 8.5px; font-weight: 850; color: white; background: #ef4444; padding: 1px 5px; border-radius: 4px; text-transform: uppercase;">
                                                    NEW
                                                </span>
                                            @endif
                                        </div>
                                        <strong style="color: var(--soa-ink); font-size: 13.5px;">{{ $notice['title'] }}</strong>
                                        <span style="color: var(--soa-muted); font-size: 11.5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">
                                            {{ $notice['summary'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="soa-payment__meta">
                                    <span>Posted: <b>{{ $notice['date'] }}</b></span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--soa-green); font-weight: 750;">
                                        Read advisory <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="soa-empty soa-empty--compact">
                        <span><i data-lucide="bell-off"></i></span>
                        <strong>No new announcements</strong>
                        <p>School notices will be posted here as they become available.</p>
                    </div>
                @endif
            </section>

        </div>

        {{-- ── SIDE / RIGHT COLUMN ───────────────────────────────── --}}
        <aside class="soa-side-column">

            {{-- Microsoft 365 Account Card --}}
            <section class="soa-card" style="padding: 22px;">
                <header class="soa-card__header soa-card__header--compact" style="margin-bottom: 14px;">
                    <div>
                        <span class="soa-section-kicker">Cloud Services</span>
                        <h3 style="font-size: 16px;">Microsoft 365</h3>
                    </div>
                    <span class="soa-card__header-icon" style="width: 36px; height: 36px;"><i data-lucide="cloud" style="width: 17px; height: 17px;"></i></span>
                </header>

                <div style="background: var(--soa-soft); border: 1px solid var(--soa-border); border-radius: 14px; padding: 12px 14px; margin-bottom: 14px;">
                    <span style="font-size: 9.5px; font-weight: 800; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 3px;">
                        Institutional Email
                    </span>
                    <strong style="display: block; font-size: 12px; color: var(--soa-green); font-weight: 800; word-break: break-all;">
                        {{ $m365Email }}
                    </strong>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
                        <span class="soa-tag-pill soa-tag-pill--live" style="font-size: 8.5px;">Active License</span>
                        <button type="button" @click="copyText('{{ $m365Email }}')" style="border: none; background: transparent; color: var(--soa-green); font-size: 10.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                            <i data-lucide="copy" style="width: 12px; height: 12px;"></i>
                            <span x-text="copiedEmail ? 'Copied!' : 'Copy'">Copy</span>
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="https://teams.microsoft.com" target="_blank" rel="noopener noreferrer" class="soa-button soa-button--ghost" style="color: #2563eb; border-color: #cbdcfb; background: #eff6ff; min-height: 36px; font-size: 11px;">
                        <i data-lucide="video"></i>
                        Launch Microsoft Teams
                    </a>
                    <a href="https://outlook.office.com" target="_blank" rel="noopener noreferrer" class="soa-button soa-button--ghost" style="color: #475569; border-color: var(--soa-border); background: var(--soa-surface); min-height: 36px; font-size: 11px;">
                        <i data-lucide="mail"></i>
                        Open Student Mail
                    </a>
                </div>
            </section>

        </aside>

    </div>



</div>
</x-student-layout>
