<x-student-layout title="Student Dashboard">

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
                Assalamu Alaikum · AMIS Student Portal
            </div>
            <h2 id="dashboard-welcome-title">
                Welcome back,<br>
                <em>{{ $firstName }}!</em>
            </h2>
            <p>Your unified academic workspace. Follow your live daily classes, view official faculty instructions, monitor your report card, and track school finances.</p>

            <div class="soa-hero__meta">
                <span><i data-lucide="graduation-cap"></i> {{ $student?->grade_level ?: 'Grade 1' }} • {{ $section?->name ?? 'G1-AL-MUNAWWARA' }}</span>
                <span><i data-lucide="id-card"></i> AMIS ID: <b>{{ $student?->student_number ?? '260000' }}</b></span>
                <span><i data-lucide="calendar-days"></i> School Year {{ $student?->school_year ?? '2026–2027' }}</span>
            </div>

            <div class="soa-hero__actions">
                <a href="{{ route('student.schedule') }}" class="soa-button soa-button--light">
                    <i data-lucide="calendar-days"></i>
                    Class Schedule
                </a>
                <a href="{{ route('student.grades') }}" class="soa-button soa-button--ghost">
                    <i data-lucide="award"></i>
                    Report Card
                </a>
                <button type="button" @click="showIdModal = true" class="soa-button soa-button--ghost">
                    <i data-lucide="badge-check"></i>
                    Digital ID Card
                </button>
            </div>
        </div>

        {{-- Hero Right Academic Standing Badge --}}
        <div class="soa-balance-card">
            <div class="soa-balance-card__top">
                <span>Academic Standing</span>
                <span class="soa-account-state is-settled">
                    <i></i>{{ ucfirst($student?->applicant?->student_type ?? 'Enrolled') }}
                </span>
            </div>
            <strong>
                @if(!is_null($latestApprovedAverage))
                    <small>GWA</small> {{ $latestApprovedAverage }}%
                @else
                    <small>STATUS</small> Active
                @endif
            </strong>
            <div class="soa-progress" role="progressbar" aria-label="Academic standing progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ !is_null($latestApprovedAverage) ? min(100, $latestApprovedAverage) : 100 }}">
                <span style="width: {{ !is_null($latestApprovedAverage) ? min(100, $latestApprovedAverage) : 100 }}%"></span>
            </div>
            <div class="soa-balance-card__footer">
                <span><b>{{ $academicSubjects->count() }}</b> Enrolled Subjects</span>
                <span>{{ $section?->name ?? 'Class Section' }}</span>
            </div>
        </div>
    </section>

    {{-- ── 2. SOA METRICS ROW ────────────────────────────────────── --}}
    <section class="soa-metrics" aria-label="Dashboard metrics summary">
        <a href="{{ route('student.subjects') }}" class="soa-metric" style="text-decoration: none;">
            <span class="soa-metric__icon is-emerald"><i data-lucide="book-open"></i></span>
            <div>
                <small>My Subjects</small>
                <strong>{{ $academicSubjects->count() }} <i>Subjects</i></strong>
                <span>Official DepEd curriculum</span>
            </div>
        </a>
        <a href="{{ route('student.subjects') }}" class="soa-metric" style="text-decoration: none;">
            <span class="soa-metric__icon is-blue"><i data-lucide="users"></i></span>
            <div>
                <small>My Teachers</small>
                <strong>{{ $uniqueTeachers->count() }} <i>Assigned</i></strong>
                <span>Official faculty roster</span>
            </div>
        </a>
        <a href="#today-classes" class="soa-metric" style="text-decoration: none;">
            <span class="soa-metric__icon is-violet"><i data-lucide="calendar-clock"></i></span>
            <div>
                <small>Today's Schedule</small>
                <strong>{{ $schedules->filter(fn($item) => strcasecmp((string) $item->day, $todayName) === 0)->count() }} <i>Classes</i></strong>
                <span>{{ $todayLabel }}</span>
            </div>
        </a>
        <a href="{{ route('student.billing') }}" class="soa-metric" style="text-decoration: none;">
            <span class="soa-metric__icon is-amber"><i data-lucide="wallet"></i></span>
            <div>
                <small>Account Balance</small>
                <strong>PHP {{ number_format($soaRemaining, 2) }}</strong>
                <span>{{ $soaAccountStatus }}</span>
            </div>
        </a>
    </section>

    {{-- ── 3. SOA TWO-COLUMN CONTENT GRID ────────────────────────── --}}
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

                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        @if($student && $student->ms_user_id && str_ends_with(strtolower($student->school_email), '@amis.edu.ph'))
                            <form method="POST" action="{{ route('student.sync-teams') }}" style="margin: 0; display: inline-block;">
                                @csrf
                                <button type="submit" class="soa-button soa-button--ghost" style="color: var(--soa-green); border-color: #a7f3d0; background: #ecfdf5; min-height: 36px; padding: 6px 13px; font-size: 11px;">
                                    <i data-lucide="refresh-cw"></i>
                                    Sync Teams
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('student.schedule') }}" class="soa-button soa-button--ghost" style="color: var(--soa-green); border-color: var(--soa-border); background: var(--soa-soft); min-height: 36px; padding: 6px 13px; font-size: 11px;">
                            Full Schedule →
                        </a>
                    </div>
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
                    @if($endedCount > 0)
                        <button type="button" @click="showEnded = !showEnded" style="display: flex; width: 100%; align-items: center; justify-content: space-between; border: 1px dashed var(--soa-border); border-radius: 12px; background: var(--soa-soft); padding: 10px 14px; margin-bottom: 12px; cursor: pointer; color: var(--soa-muted); font-size: 11.5px; font-weight: 700; transition: background 0.15s ease;">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <i data-lucide="check-circle-2" style="width: 15px; height: 15px; color: var(--soa-green);"></i>
                                <span>{{ $endedCount }} completed {{ \Illuminate\Support\Str::plural('class', $endedCount) }} today</span>
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--soa-green);">
                                <span x-text="showEnded ? 'Hide completed' : 'Show completed'"></span>
                                <i data-lucide="chevron-down" style="width: 14px; height: 14px; transition: transform 0.2s ease;" :style="showEnded ? 'transform: rotate(180deg)' : ''"></i>
                            </span>
                        </button>
                    @endif

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
                                    <tr @if($isEnded) x-show="showEnded" x-cloak x-transition.opacity @endif style="{{ $isEnded ? 'opacity: 0.5; background: #fafafa;' : '' }} {{ $isLive ? 'background: #f0fdf4;' : '' }}">
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

            {{-- 2. Enrolled Academic Subjects Card --}}
            <section class="soa-card">
                <header class="soa-card__header">
                    <div>
                        <span class="soa-section-kicker">Curriculum &amp; Instruction</span>
                        <h3>Enrolled Academic Subjects</h3>
                        <p>Core subjects, specialized courses, and designated faculty instructors.</p>
                    </div>

                    <a href="{{ route('student.subjects') }}" class="soa-button soa-button--ghost" style="color: var(--soa-green); border-color: var(--soa-border); background: var(--soa-soft); min-height: 36px; padding: 6px 14px; font-size: 11px;">
                        View All Subjects →
                    </a>
                </header>

                <div class="soa-table-wrap">
                    <table class="soa-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Assigned Teacher</th>
                                <th>Schedule Timetable</th>
                                <th style="text-align: right;">Classroom</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($academicSubjects as $subj)
                                @php
                                    $schedStr = $formattedSchedules[$subj->subject_name] ?? 'Schedule arranged per section';
                                    $teacherDisp = $formatTeacherName($subj->teacher_name);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="soa-fee-icon">
                                            <i data-lucide="book-marked"></i>
                                        </span>
                                        <div>
                                            <strong>{{ $subj->subject_name }}</strong>
                                            <small>{{ $subj->subject_code ?: ($section?->name ?? 'Grade Academic') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: #334155; font-weight: 700;">{{ $teacherDisp }}</strong>
                                        <small style="color: #94a3b8;">Faculty Member</small>
                                    </td>
                                    <td>
                                        <strong style="font-size: 11.5px; color: #475569; font-weight: 700;">{{ $schedStr }}</strong>
                                        <small style="color: #94a3b8;">Weekly Sessions</small>
                                    </td>
                                    <td style="text-align: right;">
                                        @if(!empty($subj->team_url))
                                            <a href="{{ $subj->team_url }}" target="_blank" rel="noopener noreferrer" class="soa-button soa-button--ghost" style="min-height: 32px; padding: 4px 11px; font-size: 11px; border-radius: 8px; color: var(--soa-green); border-color: #a7f3d0; background: #ecfdf5;">
                                                <i data-lucide="video"></i> Teams
                                            </a>
                                        @else
                                            <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Standard Room</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 30px; color: var(--soa-muted);">
                                        No subjects assigned yet for your section.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- 3. School Announcements & Advisories Card --}}
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

            {{-- 1. Digital Student ID Card Widget --}}
            <section class="soa-card" style="padding: 22px;">
                <header class="soa-card__header soa-card__header--compact" style="margin-bottom: 14px;">
                    <div>
                        <span class="soa-section-kicker">Student Credential</span>
                        <h3 style="font-size: 16px;">Digital Student ID</h3>
                    </div>
                    <span class="soa-card__header-icon" style="width: 36px; height: 36px;"><i data-lucide="badge-check" style="width: 17px; height: 17px;"></i></span>
                </header>

                {{-- Interactive 3D Card Miniature Preview --}}
                <div class="perspective-1000" style="width: 100%; height: 180px; margin-bottom: 14px; cursor: pointer;" @click="isFlipped = !isFlipped" title="Click to flip card">
                    <div class="card-inner holo-card" :class="isFlipped ? 'is-flipped' : ''">
                        
                        {{-- Front Miniature --}}
                        <div class="card-front id-card-front-content" style="padding: 16px; border-radius: 18px;">
                            <div class="holo-overlay" style="position: absolute; inset: 0; pointer-events: none;"></div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; z-index: 1;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" style="width: 24px; height: 24px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));">
                                    <div style="text-align: left;">
                                        <div style="font-size: 9.5px; font-weight: 900; letter-spacing: 0.05em; color: #f4d77d;">AMIS STUDENT ID</div>
                                        <div style="font-size: 7.5px; color: #a7f3d0; font-weight: 700;">Davao City, Philippines</div>
                                    </div>
                                </div>
                                <span style="font-size: 8px; font-weight: 800; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 999px;">SY {{ $student?->school_year ?? '2026–2027' }}</span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 12px; z-index: 1;">
                                <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; border: 2px solid #ffffff; background: #ffffff; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.18);">
                                    @if($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $fullName }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #047857; color: white; font-weight: 900; font-size: 16px;">
                                            {{ $initials }}
                                        </div>
                                    @endif
                                </div>
                                <div style="min-width: 0; text-align: left;">
                                    <div style="font-size: 13px; font-weight: 900; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;">
                                        {{ $fullName }}
                                    </div>
                                    <div style="font-size: 10px; font-weight: 700; color: #f4d77d; margin-top: 2px;">
                                        {{ $student?->grade_level ?: 'Grade Level' }}
                                    </div>
                                    <div style="font-size: 8.5px; font-weight: 600; color: #d1fae5;">
                                        {{ $section?->name ?? 'General' }}
                                    </div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: flex-end; z-index: 1; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 6px;">
                                <div style="text-align: left;">
                                    <span style="font-size: 7.5px; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.05em; display: block;">Student ID No.</span>
                                    <span style="font-size: 11px; font-weight: 900; letter-spacing: 0.05em; font-family: monospace;">{{ $student?->student_number ?? '260000' }}</span>
                                </div>
                                <span style="font-size: 8px; color: #f4d77d; font-weight: 700; display: inline-flex; align-items: center; gap: 3px;">
                                    <i data-lucide="rotate-cw" style="width: 10px; height: 10px;"></i> Tap to flip
                                </span>
                            </div>
                        </div>

                        {{-- Back Miniature --}}
                        <div class="card-back id-card-back-content" style="padding: 16px; border-radius: 18px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; z-index: 1;">
                                <span style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">QR Verification</span>
                                <span style="font-size: 8px; color: #6ee7b7; font-weight: 700;">AMIS Secure</span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 12px; z-index: 1;">
                                <div style="background: white; padding: 4px; border-radius: 8px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
                                    <img src="{{ $qrCodeUrl }}" alt="QR" style="width: 44px; height: 44px; display: block;">
                                </div>
                                <div style="text-align: left; font-size: 8.5px; color: #cbd5e1; line-height: 1.4;">
                                    <div><strong style="color: white;">Parent:</strong> {{ $parent ?: 'On file' }}</div>
                                    <div><strong style="color: white;">Contact:</strong> {{ $contactNo ?: 'On file' }}</div>
                                    <div style="color: #94a3b8; font-size: 7.5px; margin-top: 2px;">Scan to verify credentials</div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; z-index: 1; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 6px;">
                                <span style="font-size: 8px; color: #94a3b8; font-family: monospace;">AMIS-VALID-2026</span>
                                <span style="font-size: 8px; color: #f4d77d; font-weight: 700; display: inline-flex; align-items: center; gap: 3px;">
                                    <i data-lucide="rotate-cw" style="width: 10px; height: 10px;"></i> Tap to flip
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Action Button --}}
                <button type="button" @click="showIdModal = true" class="soa-button soa-button--primary soa-button--full" style="min-height: 38px; font-size: 11.5px;">
                    <i data-lucide="expand"></i>
                    View Full Digital ID
                </button>
            </section>

            {{-- 2. Statement of Account Financial Widget --}}
            <section class="soa-upload-card" style="padding: 22px;">
                <span class="soa-upload-card__icon" style="width: 44px; height: 44px; margin-bottom: 14px;">
                    <i data-lucide="wallet-cards" style="width: 20px; height: 20px;"></i>
                </span>
                <span class="soa-section-kicker">Financial Record</span>
                <h3 style="font-size: 18px;">Statement of Account</h3>
                <p style="margin: 6px 0 16px; font-size: 11.5px; line-height: 1.6;">
                    Follow tuition charges, verified payments, and installment due dates issued by the Finance Office.
                </p>

                <div style="background: rgba(255,255,255,0.75); border: 1px solid #d8e7df; border-radius: 14px; padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 9.5px; font-weight: 800; color: #64748b; text-transform: uppercase;">Remaining Balance</span>
                        <span class="soa-tag-pill {{ $soaRemaining <= 0 ? 'soa-tag-pill--live' : 'soa-tag-pill--upcoming' }}" style="font-size: 8.5px;">
                            {{ $soaAccountStatus }}
                        </span>
                    </div>
                    <strong style="display: block; font-size: 22px; font-weight: 900; color: var(--soa-ink); line-height: 1.1;">
                        <small style="font-size: 11px; font-weight: 800; color: var(--soa-green);">PHP</small>
                        {{ number_format($soaRemaining, 2) }}
                    </strong>

                    <div class="soa-progress" style="margin-top: 10px; height: 6px;" role="progressbar" aria-label="Tuition paid progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($soaProgress) }}">
                        <span style="width: {{ $soaProgress }}%"></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 10px; font-weight: 700; color: #64748b;">
                        <span><b>{{ number_format($soaProgress, 0) }}%</b> Paid</span>
                        <span>PHP {{ number_format($soaPaid, 2) }} of {{ number_format($soaTotal, 2) }}</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; font-size: 11px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed #e2e8f0; padding-bottom: 6px;">
                        <span style="color: #64748b;">Next Due Date</span>
                        <strong style="color: #0f172a; font-weight: 800;">
                            {{ $soaNextBilling ? $soaNextBilling->due_date->format('M d, Y') : ($soaRemaining <= 0 ? 'Fully Settled' : 'Not Scheduled') }}
                        </strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #64748b;">Installments</span>
                        <strong style="color: #0f172a; font-weight: 800;">
                            {{ $soaPaidInstallments }} paid of {{ $billings->count() }}
                        </strong>
                    </div>
                </div>

                <a href="{{ route('student.billing') }}" class="soa-button soa-button--primary soa-button--full" style="min-height: 38px; font-size: 11.5px;">
                    <i data-lucide="receipt"></i>
                    View Statement of Account
                </a>

                <div class="soa-secure-note" style="margin-top: 14px; padding-top: 12px;">
                    <i data-lucide="shield-check"></i>
                    <span><strong>Official Finance Record</strong>Submit receipts online for Finance verification.</span>
                </div>
            </section>

            {{-- 3. Microsoft 365 Account Card --}}
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

    {{-- ── 4. FULL DIGITAL STUDENT ID MODAL ───────────────────────── --}}
    <div x-show="showIdModal" 
         class="id-modal-overlay" 
         x-cloak 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="id-modal-backdrop" @click="showIdModal = false"></div>

        <div class="id-modal-card" 
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             @keydown.escape.window="showIdModal = false">
            
            <button type="button" @click="showIdModal = false" class="id-modal-close-btn" aria-label="Close modal">
                <i data-lucide="x" style="width: 16px; height: 16px;"></i>
            </button>

            <div style="text-align: center; width: 100%;">
                <span class="soa-section-kicker" style="margin-bottom: 2px;">Official Student Credential</span>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--soa-ink);">Digital Student ID</h3>
            </div>

            {{-- 3D ID Card Container --}}
            <div class="perspective-1000" style="width: 100%; height: 380px; cursor: pointer;" @click="isFlipped = !isFlipped">
                <div class="card-inner holo-card" :class="isFlipped ? 'is-flipped' : ''">
                    
                    {{-- Front Face --}}
                    <div class="card-front id-card-front-content">
                        <div class="holo-overlay" style="position: absolute; inset: 0; pointer-events: none;"></div>

                        <div style="display: flex; align-items: center; justify-content: space-between; z-index: 1;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" style="width: 32px; height: 32px; object-fit: contain;">
                                <div style="text-align: left;">
                                    <div style="font-size: 11px; font-weight: 900; letter-spacing: 0.04em; color: #f4d77d;">AMIS STUDENT ID</div>
                                    <div style="font-size: 8.5px; color: #a7f3d0; font-weight: 700;">Asian Muslim Integrated School</div>
                                </div>
                            </div>
                            <span style="font-size: 9px; font-weight: 800; background: rgba(255,255,255,0.18); padding: 3px 8px; border-radius: 999px;">
                                SY {{ $student?->school_year ?? '2026–2027' }}
                            </span>
                        </div>

                        <div style="display: flex; flex-direction: column; align-items: center; text-align: center; z-index: 1; margin: 10px 0;">
                            <div style="width: 88px; height: 88px; border-radius: 50%; overflow: hidden; border: 3px solid #ffffff; background: #ffffff; box-shadow: 0 8px 20px rgba(0,0,0,0.25); margin-bottom: 10px;">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="{{ $fullName }}" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #047857; color: white; font-weight: 900; font-size: 28px;">
                                        {{ $initials }}
                                    </div>
                                @endif
                            </div>

                            <h4 style="margin: 0; font-size: 16px; font-weight: 900; color: #ffffff; line-height: 1.2;">
                                {{ $fullName }}
                            </h4>
                            <div style="font-size: 12px; font-weight: 800; color: #f4d77d; margin-top: 3px;">
                                {{ $student?->grade_level ?: 'Grade 1' }}
                            </div>
                            <div style="font-size: 10.5px; font-weight: 600; color: #d1fae5;">
                                {{ $section?->name ?? 'G1-AL-MUNAWWARA' }}
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end; z-index: 1; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 10px;">
                            <div style="text-align: left;">
                                <span style="font-size: 8.5px; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.05em; display: block;">Student Number</span>
                                <span style="font-size: 13px; font-weight: 900; letter-spacing: 0.05em; font-family: monospace;">{{ $student?->student_number ?? '260000' }}</span>
                            </div>
                            <span style="font-size: 9.5px; color: #f4d77d; font-weight: 750; display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="rotate-cw" style="width: 12px; height: 12px;"></i> Tap to flip
                            </span>
                        </div>
                    </div>

                    {{-- Back Face --}}
                    <div class="card-back id-card-back-content">
                        <div style="display: flex; justify-content: space-between; align-items: center; z-index: 1;">
                            <span style="font-size: 9.5px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Credential Verification</span>
                            <span style="font-size: 9.5px; color: #6ee7b7; font-weight: 800;">Official AMIS ID</span>
                        </div>

                        <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 8px; z-index: 1; margin: 10px 0;">
                            <div style="background: white; padding: 6px; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.3);">
                                <img src="{{ $qrCodeUrl }}" alt="QR Verification" style="width: 90px; height: 90px; display: block;">
                            </div>
                            <span style="font-size: 8.5px; color: #94a3b8;">Scan with smartphone camera to verify authenticity</span>

                            <div style="width: 100%; text-align: left; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 8px 12px; font-size: 9.5px; color: #cbd5e1; line-height: 1.5;">
                                <div><strong style="color: white;">Parent/Guardian:</strong> {{ $parent ?: 'On official record' }}</div>
                                <div><strong style="color: white;">Emergency Phone:</strong> {{ $contactNo ?: 'On official record' }}</div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; z-index: 1; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 8px;">
                            <span style="font-size: 9.5px; color: #94a3b8; font-family: monospace;">{{ $displayStudentId }}</span>
                            <span style="font-size: 9.5px; color: #f4d77d; font-weight: 750; display: inline-flex; align-items: center; gap: 4px;">
                                <i data-lucide="rotate-cw" style="width: 12px; height: 12px;"></i> Tap to flip
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <div style="display: flex; gap: 10px; width: 100%;">
                <button type="button" @click="isFlipped = !isFlipped" class="soa-button soa-button--ghost" style="flex: 1; color: var(--soa-ink); border-color: var(--soa-border); background: var(--soa-soft); min-height: 38px; font-size: 11.5px;">
                    <i data-lucide="rotate-cw"></i> Flip Card
                </button>
                <button type="button" onclick="window.print()" class="soa-button soa-button--light" style="flex: 1; border: 1px solid var(--soa-border); min-height: 38px; font-size: 11.5px;">
                    <i data-lucide="printer"></i> Print
                </button>
            </div>

        </div>
    </div>

</div>
</x-student-layout>
