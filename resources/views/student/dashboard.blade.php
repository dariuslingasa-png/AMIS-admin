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

    $getGradeColor = function ($grade) {
        if (!$grade) return '#6d28d9';
        $g = strtoupper($grade);
        if (str_contains($g, 'NURSERY') || str_contains($g, 'KINDER') || str_contains($g, 'PRE-')) return '#ea580c';
        if (str_contains($g, 'GRADE 1') || str_contains($g, 'GRADE 2') || str_contains($g, 'GRADE 3')) return '#0284c7';
        if (str_contains($g, 'GRADE 4') || str_contains($g, 'GRADE 5') || str_contains($g, 'GRADE 6')) return '#7c3aed';
        if (str_contains($g, 'GRADE 7') || str_contains($g, 'GRADE 8') || str_contains($g, 'GRADE 9') || str_contains($g, 'GRADE 10')) return '#dc2626';
        if (str_contains($g, 'GRADE 11') || str_contains($g, 'GRADE 12') || str_contains($g, 'GRADE XI') || str_contains($g, 'GRADE XII')) return '#4f46e5';
        return '#6d28d9';
    };
    
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
        ])->values();
    $isSchoolDay = in_array($todayName, ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']);
    $defaultViewMode = 'today';

    if ($isSchoolDay) {
        $targetDayName = $todayName;
        $todayLabel = "Today's Classes";
        $todaySub = $nowManila->format('l, F j, Y') . ' (PST)';
    } else {
        $targetDayName = 'Sunday';
        $todayLabel = "Next School Day (Sunday)";
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
        : ($soaRemaining <= 0 ? 'Paid' : ($soaPaid > 0 ? 'Partially Paid' : 'Unpaid'));

    $latestPayment = $payments->sortByDesc('created_at')->first();
@endphp

@once
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
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
        background: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(4px) !important;
        -webkit-backdrop-filter: blur(4px) !important;
        z-index: 1 !important;
    }
    .id-modal-card {
        position: relative !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(16px) !important;
        -webkit-backdrop-filter: blur(16px) !important;
        border-radius: 24px !important;
        padding: 1.75rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        max-width: 340px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 1.25rem !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        z-index: 2 !important;
        text-align: center !important;
    }
    .id-modal-close-btn {
        position: absolute !important;
        top: 1rem !important;
        right: 1rem !important;
        z-index: 50 !important;
        border: none !important;
        background: #f1f5f9 !important;
        color: #64748b !important;
        width: 28px !important;
        height: 28px !important;
        border-radius: 50% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        padding: 0 !important;
    }
    .id-modal-close-btn:hover {
        background: #e2e8f0 !important;
        color: #334155 !important;
    }
    .id-status-badge {
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        background: #f0fdf4 !important;
        border: 1px solid #dcfce7 !important;
        padding: 0.75rem 1rem !important;
        border-radius: 16px !important;
        box-sizing: border-box !important;
        text-align: left !important;
    }
    .id-status-icon {
        width: 36px !important;
        height: 36px !important;
        border-radius: 50% !important;
        background: rgba(16, 185, 129, 0.1) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #10b981 !important;
        flex-shrink: 0 !important;
    }
    .id-card-front-content {
        background: linear-gradient(135deg, #064e3b 0%, #0d9488 50%, #115e59 100%) !important;
        box-sizing: border-box !important;
        width: 100% !important;
        height: 100% !important;
        border-radius: 20px !important;
        padding: 1.25rem !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3) !important;
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
        border-radius: 20px !important;
        padding: 1.25rem !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3) !important;
        color: white !important;
        position: relative !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
<script>
    window.scheduleCountdown = function(items) {
        return {
            activeTab: 'overview',
            viewMode: '{{ $defaultViewMode }}', showIdModal: false, isFlipped: false, showEnded: false, countdownOpen: true,
            items, activeClass: null, nextClass: null, phase: 'loading', remaining: '--:--', timer: null,
            init() {
                this.tick();
                this.timer = window.setInterval(() => this.tick(), 1000);
            },
            toDate(time) {
                const [hours, minutes] = time.split(':').map(Number);
                const date = new Date();
                date.setHours(hours, minutes, 0, 0);
                return date;
            },
            tick() {
                const now = new Date();
                const classes = this.items.map(item => ({ ...item, startsAt: this.toDate(item.start), endsAt: this.toDate(item.end) }));
                this.activeClass = classes.find(item => now >= item.startsAt && now < item.endsAt) || null;
                this.nextClass = classes.find(item => now < item.startsAt) || null;
                let target = null;
                if (!classes.length) this.phase = 'empty';
                else if (this.activeClass) { this.phase = 'active'; target = this.activeClass.endsAt; }
                else if (this.nextClass) { this.phase = 'upcoming'; target = this.nextClass.startsAt; }
                else this.phase = 'finished';
                if (target) {
                    const seconds = Math.max(0, Math.floor((target - now) / 1000));
                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const secs = seconds % 60;
                    this.remaining = `${hours ? String(hours).padStart(2,'0') + ':' : ''}${String(minutes).padStart(2,'0')}:${String(secs).padStart(2,'0')}`;
                } else this.remaining = '--:--';
            },
            formatTime(time) {
                return this.toDate(time).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            }
        };
    };
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
@endonce

{{-- ── Main Dashboard Container with Alpine State ────────────────── --}}
<div x-data="scheduleCountdown(@js($countdownItems))" style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">

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

    {{-- ── 1. Centered Student Profile Header Card ──────────────────── --}}
    <div class="fade-up" style="
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 8px 24px -4px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
    ">
        {{-- Cover Photo Background --}}
        <div style="
            position: relative;
            height: 130px;
            background: linear-gradient(135deg, #064e3b 0%, #065f46 35%, #047857 70%, #0d9488 100%);
            overflow: hidden;
        ">
            {{-- Abstract Campus Architecture / Academic Geometric Mesh --}}
            <svg style="position: absolute; right: 0; top: 0; height: 100%; width: 50%; opacity: 0.12; pointer-events: none;" viewBox="0 0 400 150" fill="none" preserveAspectRatio="none">
                <path d="M0 150L120 40L240 100L360 20L400 40V150H0Z" fill="#ffffff"/>
                <circle cx="320" cy="40" r="60" stroke="#ffffff" stroke-width="2"/>
                <circle cx="200" cy="80" r="40" stroke="#ffffff" stroke-width="1.5"/>
            </svg>
            {{-- Subtle Dot Pattern Overlay --}}
            <div style="position: absolute; inset: 0; background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px); background-size: 16px 16px; pointer-events: none;"></div>
            {{-- Ambient Glowing Orb --}}
            <div style="position: absolute; left: 15%; top: -30px; width: 180px; height: 180px; border-radius: 50%; background: radial-gradient(circle, rgba(52, 211, 153, 0.3), transparent 70%); pointer-events: none;"></div>
            
            {{-- Subtle School Brand Tag on Cover --}}
            <div style="position: absolute; top: 1rem; right: 1.25rem; display: flex; align-items: center; gap: 0.4rem; background: rgba(0, 0, 0, 0.22); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); padding: 0.25rem 0.65rem; border-radius: 999px; border: 1px solid rgba(255, 255, 255, 0.15);">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #34d399;"></span>
                <span style="font-size: 0.68rem; font-weight: 700; color: #ffffff; letter-spacing: 0.05em; text-transform: uppercase;">AMIS Student Portal</span>
            </div>
        </div>

        {{-- Centered Profile Details Section --}}
        <div style="
            padding: 0 1.5rem 1.75rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        ">
            {{-- 1. Centered Overlapping Avatar (92px) --}}
            <div style="position: relative; width: 92px; height: 92px; margin-top: -46px; margin-bottom: 0.85rem; flex-shrink: 0; z-index: 2;">
                @if ($photoUrl)
                    <img src="{{ $photoUrl }}" alt="{{ $fullName }}"
                         onerror="if (!this.dataset.fallback) { this.dataset.fallback='1'; this.src='{{ $fallbackPhoto }}'; } else { this.style.display='none'; this.nextElementSibling.style.display='flex'; }"
                         style="width: 92px; height: 92px; object-fit: cover; border-radius: 50%; border: 4px solid #ffffff; box-shadow: 0 6px 20px rgba(0,0,0,0.12); display: block; background: #ffffff;"
                         loading="eager" decoding="async">
                    <div style="width: 92px; height: 92px; border-radius: 50%; background: linear-gradient(135deg, #059669 0%, #047857 100%); border: 4px solid #ffffff; box-shadow: 0 6px 20px rgba(5,150,105,0.25); display: none; align-items: center; justify-content: center; color: #ffffff; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.02em;">
                        {{ $initials }}
                    </div>
                @else
                    <div style="width: 92px; height: 92px; border-radius: 50%; background: linear-gradient(135deg, #059669 0%, #047857 100%); border: 4px solid #ffffff; box-shadow: 0 6px 20px rgba(5,150,105,0.25); display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 800; font-size: 1.85rem; letter-spacing: -0.02em;">
                        {{ $initials }}
                    </div>
                @endif
                <span style="position: absolute; bottom: 3px; right: 3px; width: 17px; height: 17px; border-radius: 50%; background: #10b981; border: 3.5px solid #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.12);"></span>
            </div>

            {{-- 2. Greeting --}}
            <span style="font-size: 0.72rem; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.25rem;">
                Assalamu Alaikum
            </span>

            {{-- 3. Student Full Name (Main Focus) --}}
            <h1 style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.4rem 0; line-height: 1.2; letter-spacing: -0.025em;">
                {{ $fullName }}
            </h1>

            {{-- 4. Primary Metadata Row: Grade • Section • 1 Status Chip --}}
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.55rem; flex-wrap: wrap; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">
                <span>{{ $student?->grade_level ?: 'Grade 1' }}</span>
                <span style="color: #cbd5e1;">•</span>
                <span>{{ $section?->name ?? 'G1-AL-MUNAWWARA' }}</span>
                <span style="color: #cbd5e1;">•</span>
                <span style="font-size: 0.72rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.55rem; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.04em; display: inline-flex; align-items: center; gap: 0.3rem;">
                    <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                    {{ ucfirst($student?->applicant?->student_type ?? 'Continuing') }}
                </span>
            </div>

            {{-- 5. Secondary Info Row: ID & Email with simple icons --}}
            <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; flex-wrap: wrap; font-size: 0.825rem; font-weight: 500; color: #64748b;">
                <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h.01"/><path d="M17 7h.01"/><path d="M7 17h.01"/><path d="M17 17h.01"/></svg>
                    <span>ID: <strong style="color: #1e293b; font-weight: 700;">{{ $student?->student_number ?? '260000' }}</strong></span>
                </span>
                <span style="color: #cbd5e1;">•</span>
                <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    <span style="color: #334155; font-weight: 600;">{{ $student?->school_email ?? Auth::user()->email }}</span>
                </span>
            </div>

            {{-- 6. Academic Year & My Schedule Button Row --}}
            <div style="
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 1.25rem;
                flex-wrap: wrap;
                margin-top: 1.15rem;
                padding-top: 1rem;
                border-top: 1px solid #f1f5f9;
                width: 100%;
                max-width: 480px;
            ">
                <div style="display: flex; align-items: center; gap: 0.45rem;">
                    <span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Academic Year:</span>
                    <span style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 0.95rem; font-weight: 800; color: #0f172a;">
                        SY {{ $student?->school_year ?? '2026–2027' }}
                    </span>
                </div>

                <button type="button" @click="activeTab = 'overview'" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 0.45rem;
                    padding: 0.5rem 1.15rem;
                    border-radius: 10px;
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    color: #ffffff;
                    font-size: 0.8125rem;
                    font-weight: 700;
                    border: none;
                    cursor: pointer;
                    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
                    transition: all 0.15s ease;
                " onmouseover="this.style.boxShadow='0 6px 18px rgba(5, 150, 105, 0.32)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 4px 12px rgba(5, 150, 105, 0.2)'; this.style.transform='none'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>My Schedule</span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </div>

        </div>
    </div>

    {{-- ── 2. Full-Width 2-Tab Navigation Bar ────────────────────────── --}}
    <div class="fade-up" style="
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 0.35rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        width: 100%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    ">
        <button type="button" 
                @click="activeTab = 'overview'" 
                :class="activeTab === 'overview' ? 'portal-tab-pill active' : 'portal-tab-pill'">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            <span>Student Overview</span>
        </button>

        <button type="button" 
                @click="activeTab = 'profile'" 
                :class="activeTab === 'profile' ? 'portal-tab-pill active' : 'portal-tab-pill'">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Personal Information</span>
        </button>
    </div>

    <style>
        .portal-tab-pill {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 12px;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s ease;
            font-family: inherit;
        }
        .portal-tab-pill:hover {
            color: #0f172a;
            background: #f8fafc;
        }
        .portal-tab-pill.active {
            background: #ecfdf5 !important;
            border-color: #a7f3d0 !important;
            color: #047857 !important;
            font-weight: 800 !important;
            box-shadow: 0 1px 3px rgba(5, 150, 105, 0.08);
        }
        @media(max-width: 640px) {
            .portal-tab-pill {
                padding: 0.65rem 0.35rem !important;
                font-size: 0.825rem !important;
                gap: 0.35rem !important;
            }
            .portal-tab-pill svg {
                width: 15px !important;
                height: 15px !important;
            }
        }
        .class-countdown-banner{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:1rem;overflow:hidden;border:1px solid #a7f3d0;border-radius:20px;background:linear-gradient(135deg,#064e3b 0%,#047857 55%,#0d9488 100%);padding:1.25rem 1.4rem;color:#fff;box-shadow:0 12px 28px rgba(5,150,105,.14)}
        .class-countdown-banner:after{content:'';position:absolute;right:-35px;top:-55px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.08)}
        .class-countdown-icon{display:flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:14px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18)}.class-countdown-icon svg{width:24px;height:24px}
        .class-countdown-copy{position:relative;z-index:1;min-width:0}.class-countdown-copy h2{margin:.12rem 0;color:#fff;font-size:1.15rem;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.class-countdown-copy p{margin:0;color:#d1fae5;font-size:.78rem;font-weight:700}.class-countdown-copy .class-countdown-label{color:#a7f3d0;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.1em}
        .class-countdown-clock{position:relative;z-index:1;min-width:120px;border-radius:15px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:.7rem 1rem;text-align:center}.class-countdown-clock strong{display:block;font-variant-numeric:tabular-nums;font-size:1.45rem;font-weight:950;letter-spacing:.03em}.class-countdown-clock span{display:block;margin-top:.1rem;color:#a7f3d0;font-size:.6rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em}
        .class-countdown-inline{border-width:0 0 1px;border-radius:0;padding:1rem 1.25rem;box-shadow:inset 4px 0 0 #34d399}
        .completed-subjects-toggle{display:flex;width:100%;align-items:center;gap:.75rem;border:0;border-bottom:1px solid #e2e8f0;background:#f8fafc;padding:.8rem 1.25rem;text-align:left;color:#475569;cursor:pointer;transition:background .15s ease}.completed-subjects-toggle:hover{background:#f1f5f9}.completed-subjects-toggle-icon{display:flex;width:32px;height:32px;flex:0 0 32px;align-items:center;justify-content:center;border-radius:10px;background:#dcfce7;color:#16a34a}.completed-subjects-toggle-icon svg{width:16px;height:16px}.completed-subjects-toggle-copy{display:flex;min-width:0;flex:1;flex-direction:column}.completed-subjects-toggle-copy strong{font-size:.78rem;font-weight:900;color:#334155}.completed-subjects-toggle-copy small{margin-top:1px;font-size:.66rem;font-weight:700;color:#94a3b8}.completed-subjects-chevron{width:16px;height:16px;transition:transform .2s ease}.completed-subjects-chevron.is-open{transform:rotate(180deg)}
        .s-two-col-grid {
            display: grid;
            grid-template-columns: 1.65fr 1fr;
            gap: 1.5rem;
            align-items: start;
        }
        @media(max-width: 1024px) {
            .s-two-col-grid {
                grid-template-columns: 1fr !important;
            }
        }
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem 2.5rem;
        }
        .profile-info-row {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px dashed #f1f5f9;
        }
        .profile-info-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .profile-info-value {
            font-size: 0.925rem;
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }
        @media(max-width: 768px) {
            .profile-info-grid {
                grid-template-columns: 1fr !important;
                gap: 0.85rem !important;
            }
        }
    </style>

    {{-- ── TAB 1: STUDENT OVERVIEW CONTENT ────────────────────────────── --}}
    <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">

        {{-- ── COMING SOON BANNER — F2F Students ─────────── --}}
        @php
            $isF2F = str_contains(strtolower($student?->applicant?->learning_mode ?? ''), 'face');
        @endphp
        @if($isF2F)
        <div class="fade-up" style="
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 60%, #fde68a 100%);
            border: 1.5px solid #f59e0b;
            border-radius: 20px;
            padding: 1.5rem 1.75rem;
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(245,158,11,0.12);
        ">
            <div style="position:absolute;right:-20px;bottom:-20px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(245,158,11,0.15),transparent 70%);pointer-events:none;"></div>
            <div style="
                width: 48px; height: 48px; border-radius: 14px;
                background: #f59e0b;
                display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
                box-shadow: 0 4px 12px rgba(245,158,11,0.35);
            ">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div style="flex:1; position:relative;">
                <div style="display:flex; align-items:center; gap:0.625rem; margin-bottom:0.4rem; flex-wrap:wrap;">
                    <span style="font-size:0.7rem; font-weight:800; color:#92400e; background:#fde68a; border:1.5px solid #f59e0b; padding:0.15rem 0.55rem; border-radius:999px; text-transform:uppercase; letter-spacing:0.08em;">
                        Face-to-Face
                    </span>
                    <span style="font-size:0.7rem; font-weight:800; color:#ffffff; background:#f59e0b; padding:0.15rem 0.55rem; border-radius:999px; text-transform:uppercase; letter-spacing:0.08em;">
                        {{ strtoupper($student?->applicant?->student_type ?? 'student') }}
                    </span>
                </div>
                <h3 style="font-size:1.15rem; font-weight:800; color:#78350f; margin:0 0 0.35rem; letter-spacing:-0.02em;">
                    🚧 Portal Coming Soon for F2F Students!
                </h3>
                <p style="font-size:0.875rem; font-weight:500; color:#92400e; margin:0; line-height:1.6;">
                    We are currently preparing the online portal for <strong>Face-to-Face</strong> students.
                    Your schedule, subjects, grades, and other features will be available here very soon.
                </p>
            </div>
        </div>
        @endif

        {{-- ── 2-Column Responsive Dashboard Grid ─────────────────────── --}}
        <div class="s-two-col-grid" style="width: 100%;">

            {{-- LEFT MAIN COLUMN: Today's Classes & Recent Announcements --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem; min-width: 0; width: 100%;">

                {{-- 1. Today's Classes Section --}}
                <div class="fade-up" style="display: flex; flex-direction: column; gap: 0.85rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 32px; height: 32px; border-radius: 9px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </div>
                            <div>
                                <h2 style="font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">
                                    {{ $todayLabel }}
                                </h2>
                                <span style="font-size: 0.78rem; color: #64748b; font-weight: 600;">{{ $todaySub }}</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            @if($student && $student->ms_user_id && str_ends_with(strtolower($student->school_email), '@amis.edu.ph'))
                                <form method="POST" action="{{ route('student.sync-teams') }}" style="margin: 0; display: inline-block;">
                                    @csrf
                                    <button type="submit" class="sched-tab-btn" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem !important; padding: 0.45rem 0.85rem !important; background: #059669 !important; color: white !important; border-radius: 8px !important; box-shadow: 0 2px 4px rgba(5,150,105,0.15) !important; cursor: pointer;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                        Sync MS Teams
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('student.schedule') }}" style="font-size: 0.78rem; font-weight: 700; color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.45rem 0.85rem; border-radius: 9px; transition: all 0.15s ease;">
                                <span>View Full Schedule</span>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                    {{-- Table Card --}}
                    <div class="s-table-card" style="background:white; border: 1.5px solid #e2e8f0; border-radius: 20px; overflow:hidden;">
                        <div>
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

                            @if($todaySchedules->isNotEmpty())
                                @if($endedCount > 0)
                                    <button type="button" @click="showEnded = !showEnded" class="completed-subjects-toggle">
                                        <span class="completed-subjects-toggle-icon"><i data-lucide="circle-check-big"></i></span>
                                        <span class="completed-subjects-toggle-copy">
                                            <strong>{{ $endedCount }} completed {{ \Illuminate\Support\Str::plural('subject', $endedCount) }}</strong>
                                            <small x-text="showEnded ? 'Hide completed classes' : 'Tap to review completed classes'"></small>
                                        </span>
                                        <i data-lucide="chevron-down" class="completed-subjects-chevron" :class="showEnded ? 'is-open' : ''"></i>
                                    </button>
                                @endif
                                <div class="s-table-header" style="grid-template-columns: 1.8fr 1.2fr 1.3fr; padding: 0.75rem 1.25rem;">
                                    <div class="s-table-header-label">Subject Name</div>
                                    <div class="s-table-header-label">Teacher</div>
                                    <div class="s-table-header-label">Class Time</div>
                                </div>

                                @php
                                    $colors = ['#059669','#0ea5e9','#8b5cf6','#f59e0b','#ec4899','#14b8a6','#ef4444','#f97316'];
                                    $bgs    = ['#ecfdf5','#eff6ff','#f5f3ff','#fffbeb','#fdf2f8','#f0fdfa','#fef2f2','#fff7ed'];
                                @endphp
                                @foreach ($todaySchedules as $i => $sched)
                                    @php
                                        $c = $colors[$i % count($colors)];
                                        $bg = $bgs[$i % count($bgs)];
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
                                    <div class="s-table-row" @if($isEnded) x-show="showEnded" x-transition.opacity.duration.150ms @endif @if($isLive) @click="countdownOpen = !countdownOpen" @endif style="grid-template-columns: 1.8fr 1.2fr 1.3fr; padding: 1rem 1.25rem; align-items: center; border-bottom: 1px solid #f1f5f9; position: relative; {{ $isEnded ? 'opacity: 0.55; background: #f8fafc;' : '' }} {{ $isLive ? 'cursor:pointer;' : '' }}">
                                        @if($isLive)
                                            <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: #10b981; border-top-left-radius: 4px; border-bottom-left-radius: 4px;"></div>
                                        @endif
                                        <div style="display:flex;align-items:center;gap:0.75rem;min-width:0;">
                                            <div style="width:8px;height:8px;border-radius:50%;background:{{ $isEnded ? '#94a3b8' : $c }};flex-shrink:0;box-shadow: 0 0 0 3px {{ $isEnded ? '#f1f5f9' : $bg }};"></div>
                                            <span class="s-table-cell-subject" style="font-weight: 800; color: {{ $isEnded ? '#64748b' : '#0f172a' }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; {{ $isEnded ? 'text-decoration: line-through;' : '' }}">
                                                {{ $sched->subject_name }}
                                                @if($isEnded)
                                                    <span style="font-size:0.6rem;font-weight:850;color:#64748b;background:#f1f5f9;border:1px solid #cbd5e1;padding:0.1rem 0.35rem;border-radius:5px;text-transform:uppercase;margin-left:0.35rem;display:inline-block;">Ended</span>
                                                @endif
                                            </span>
                                            @if($isLive)
                                                <i data-lucide="chevron-down" style="width:15px;height:15px;color:#059669;flex-shrink:0;transition:transform .2s;" :style="countdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                                            @endif
                                        </div>
                                        <div style="display:flex;align-items:center;gap:0.5rem;min-width:0;">
                                            <span class="s-table-cell-teacher" style="font-weight: 750; color: {{ $isEnded ? '#94a3b8' : '#475569' }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $currentTeacherName }}</span>
                                        </div>
                                        
                                        <div class="s-table-cell-schedule" style="color:{{ $isEnded ? '#94a3b8' : '#0d9488' }}; font-weight:800; white-space: nowrap; font-size: 0.78rem;">
                                            <div style="display:flex;align-items:center;gap:0.3rem;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                                <span style="letter-spacing: -0.01em;">{{ $timeStr }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @if($isLive)
                                        <section x-show="countdownOpen" x-transition.opacity.duration.150ms class="class-countdown-banner class-countdown-inline" aria-live="polite">
                                            <div class="class-countdown-icon"><i data-lucide="timer"></i></div>
                                            <div class="class-countdown-copy">
                                                <p class="class-countdown-label">Class in progress · ends in</p>
                                                <h2 x-text="activeClass?.subject ?? @js($sched->subject_name)"></h2>
                                                <p><span x-text="activeClass?.teacher ?? @js($currentTeacherName)"></span> · {{ $timeStr }}</p>
                                            </div>
                                            <div class="class-countdown-clock"><strong x-text="remaining">--:--</strong><span>remaining</span></div>
                                        </section>
                                    @endif
                                @endforeach
                            @else
                                <div class="s-empty-card" style="padding: 3.5rem 1.5rem; text-align:center;">
                                    <div class="s-empty-icon-wrapper" style="background: #f0fdfa; display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:50%; margin-bottom: 0.75rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    </div>
                                    <h3 class="s-empty-title" style="font-size: 1.15rem; font-weight:800; color:#1e293b; margin:0 0 0.25rem;">No classes scheduled for today.</h3>
                                    <p class="s-empty-text" style="font-size: 0.85rem; color:#64748b; max-width: 340px; margin: 0 auto 1.25rem auto; line-height: 1.5;">
                                        @if(in_array($todayName, ['Friday', 'Saturday']))
                                            Happy weekend! Enjoy your rest and recharge time. ☀️
                                        @else
                                            You have no classes scheduled for today. Happy studying! 🎈
                                        @endif
                                    </p>
                                    <a href="{{ route('student.schedule') }}" style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8125rem; font-weight: 700; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.5rem 1rem; border-radius: 10px; text-decoration: none;">
                                        <span>View Full Schedule</span>
                                        <i data-lucide="arrow-right" style="width: 13px; height: 13px;"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 2. Recent Announcements Section --}}
                <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 32px; height: 32px; border-radius: 9px; background: #fef3c7; display: flex; align-items: center; justify-content: center; color: #d97706;">
                                <i data-lucide="megaphone" style="width: 17px; height: 17px;"></i>
                            </div>
                            <div>
                                <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">Recent Announcements</h3>
                                <span style="font-size: 0.78rem; color: #64748b; font-weight: 600;">School notices, advisories, and official updates</span>
                            </div>
                        </div>

                        <a href="{{ route('student.announcements') }}" style="font-size: 0.78rem; font-weight: 700; color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.45rem 0.85rem; border-radius: 9px; transition: all 0.15s ease;">
                            <span>View All Announcements</span>
                            <i data-lucide="arrow-right" style="width: 13px; height: 13px;"></i>
                        </a>
                    </div>

                    {{-- Announcement Card List --}}
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        @php
                            $recentAnnouncements = collect($announcements)->take(3);
                        @endphp
                        @forelse($recentAnnouncements as $announcement)
                            @php
                                $toneColors = [
                                    'emerald' => ['#059669', '#ecfdf5', '#a7f3d0'],
                                    'sky' => ['#0284c7', '#f0f9ff', '#bae6fd'],
                                    'amber' => ['#d97706', '#fffbeb', '#fde68a']
                                ];
                                $tc = $toneColors[$announcement['tone']] ?? $toneColors['emerald'];
                            @endphp
                            <a href="{{ route('student.announcements') }}" style="text-decoration:none; padding:1.15rem; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0; display:flex; flex-direction:column; gap:0.5rem; transition:all 0.18s ease;"
                               onmouseover="this.style.borderColor='#cbd5e1';this.style.background='#ffffff';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.05)'; this.style.transform='translateY(-1px)'"
                               onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';this.style.boxShadow='none'; this.style.transform='none'">
                                
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                                    <div style="display:flex; align-items:center; gap:0.4rem;">
                                        <span style="font-size:0.68rem; font-weight:800; color:{{ $tc[0] }}; background:{{ $tc[1] }}; border:1px solid {{ $tc[2] }}; padding:0.15rem 0.5rem; border-radius:6px; text-transform:uppercase; letter-spacing:0.04em;">
                                            {{ $announcement['type'] }}
                                        </span>
                                        @if(!$announcement['is_read'])
                                            <span style="font-size:0.62rem; font-weight:800; color:white; background:#ef4444; padding:0.12rem 0.4rem; border-radius:5px; text-transform:uppercase; letter-spacing:0.03em;">
                                                NEW
                                            </span>
                                        @endif
                                    </div>
                                    <span style="font-size:0.75rem; font-weight:600; color:#64748b;">
                                        {{ $announcement['date'] }}
                                    </span>
                                </div>

                                <h4 style="font-size:0.95rem; font-weight:800; color:#0f172a; margin:0.15rem 0 0 0; line-height:1.35;">
                                    {{ $announcement['title'] }}
                                </h4>

                                <p style="font-size:0.8125rem; font-weight:400; color:#475569; margin:0; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    {{ $announcement['summary'] }}
                                </p>

                                <div style="display:flex; align-items:center; justify-content:space-between; font-size:0.72rem; font-weight:700; color:#059669; padding-top:0.45rem; margin-top:0.25rem; border-top:1px solid #f1f5f9;">
                                    <span>Read announcement →</span>
                                    <span style="color:#94a3b8; font-weight:600; display:inline-flex; align-items:center; gap:0.25rem;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>{{ $announcement['total_views'] }} views</span>
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div style="text-align:center; padding:2rem 1.5rem; color:#64748b; font-weight:600; font-size:0.85rem; background:#f8fafc; border-radius:14px; border:1px dashed #cbd5e1;">
                                No announcements posted at this time.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- RIGHT SIDE COLUMN: Account Balance / SOA Financial Summary --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem; min-width: 0;">

                {{-- Account Balance Card --}}
                <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 1.25rem;">
                    
                    {{-- Card Header --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 32px; height: 32px; border-radius: 9px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669;">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 5H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"/><path d="M16 12h.01"/></svg>
                            </div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0;">Account Balance</h3>
                        </div>
                        <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.55rem; border-radius: 6px;">
                            SY {{ $student?->school_year ?? '2026–2027' }}
                        </span>
                    </div>

                    {{-- Main Balance Banner --}}
                    <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 70%, #0d9488 100%); border-radius: 16px; padding: 1.25rem; color: #ffffff; display: flex; flex-direction: column; gap: 0.65rem; position: relative; overflow: hidden;">
                        <div style="position: absolute; right: -15px; bottom: -15px; width: 100px; height: 100px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%); pointer-events: none;"></div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.05em;">Remaining Balance</span>
                            @php
                                $statusStyle = match($soaAccountStatus) {
                                    'Paid' => ['bg' => '#10b981', 'text' => '#ffffff'],
                                    'Partially Paid' => ['bg' => '#f59e0b', 'text' => '#ffffff'],
                                    'Unpaid' => ['bg' => '#ef4444', 'text' => '#ffffff'],
                                    default => ['bg' => 'rgba(255,255,255,0.2)', 'text' => '#ffffff']
                                };
                            @endphp
                            <span style="font-size: 0.68rem; font-weight: 800; background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['text'] }}; padding: 0.15rem 0.55rem; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.03em;">
                                {{ $soaAccountStatus }}
                            </span>
                        </div>

                        <div style="font-size: 1.65rem; font-weight: 900; line-height: 1.1; letter-spacing: -0.02em;">
                            <small style="font-size: 0.95rem; font-weight: 700; opacity: 0.85;">PHP</small>
                            {{ number_format($soaRemaining, 2) }}
                        </div>

                        {{-- Progress bar --}}
                        <div style="margin-top: 0.25rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.68rem; font-weight: 700; color: #d1fae5; margin-bottom: 0.25rem;">
                                <span>{{ round($soaProgress) }}% Paid</span>
                                <span>PHP {{ number_format($soaPaid, 2) }} of {{ number_format($soaTotal, 2) }}</span>
                            </div>
                            <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.2); border-radius: 999px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $soaProgress }}%; background: #34d399; border-radius: 999px;"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Financial Breakdown List --}}
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.65rem; border-bottom: 1px dashed #f1f5f9;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Latest Payment</span>
                            <span style="font-size: 0.825rem; font-weight: 800; color: #0f172a;">
                                @if($latestPayment)
                                    PHP {{ number_format($latestPayment->amount, 2) }} <span style="font-size: 0.72rem; color: #64748b; font-weight: 600;">({{ \Carbon\Carbon::parse($latestPayment->payment_date)->format('M d, Y') }})</span>
                                @else
                                    <span style="color: #94a3b8; font-weight: 600;">No recent payments</span>
                                @endif
                            </span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.65rem; border-bottom: 1px dashed #f1f5f9;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Next Due Date</span>
                            <span style="font-size: 0.825rem; font-weight: 800; color: #0f172a;">
                                @if($soaNextBilling)
                                    {{ $soaNextBilling->due_date->format('M d, Y') }}
                                @else
                                    <span style="color: #059669; font-weight: 700;">No pending due</span>
                                @endif
                            </span>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.8rem; font-weight: 600; color: #64748b;">Installments</span>
                            <span style="font-size: 0.825rem; font-weight: 800; color: #0f172a;">
                                {{ $soaPaidInstallments }} paid of {{ $billings->count() ?: 0 }}
                            </span>
                        </div>
                    </div>

                    {{-- Action CTA --}}
                    <div style="margin-top: 0.25rem;">
                        <a href="{{ route('student.billing') }}" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.45rem; padding: 0.75rem 1rem; border-radius: 12px; background: #059669; color: #ffffff; font-size: 0.875rem; font-weight: 700; text-decoration: none; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2); transition: all 0.15s ease;"
                           onmouseover="this.style.background='#047857'; this.style.boxShadow='0 6px 16px rgba(5,150,105,0.3)';"
                           onmouseout="this.style.background='#059669'; this.style.boxShadow='0 4px 12px rgba(5, 150, 105, 0.2)';">
                            <span>View Statement of Account</span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- ── TAB 2: PERSONAL INFORMATION CONTENT (Single Continuous Panel) ────────── --}}
    <div x-show="activeTab === 'profile'" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="width: 100%;">
        
        {{-- Single Primary Content Surface --}}
        <div class="fade-up" style="
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 2.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 24px -4px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            gap: 2rem;
            width: 100%;
        ">

            {{-- ── 1. PERSONAL DETAILS SECTION ──────────────────────── --}}
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                {{-- Section Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 32px; height: 32px; border-radius: 9px; background: #f0f9ff; display: flex; align-items: center; justify-content: center; color: #0284c7; flex-shrink: 0;">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">Personal Details</h3>
                            <p style="font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0 0; font-weight: 500;">Basic identification and demographic information</p>
                        </div>
                    </div>
                </div>

                {{-- 2-Column Key-Value Grid --}}
                <div class="profile-info-grid">
                    <div class="profile-info-row">
                        <span class="profile-info-label">Full Name</span>
                        <span class="profile-info-value">{{ $fullName ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Student ID / LRN</span>
                        <span class="profile-info-value" style="font-family: monospace; font-size: 0.95rem;">{{ $student?->student_number ?? '260000' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Gender</span>
                        <span class="profile-info-value">{{ $student?->applicant?->gender ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Date of Birth</span>
                        <span class="profile-info-value">{{ $student?->applicant?->date_of_birth ? $student->applicant->date_of_birth->format('F d, Y') : '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Place of Birth</span>
                        <span class="profile-info-value">{{ $student?->applicant?->place_of_birth ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Religion</span>
                        <span class="profile-info-value">{{ $student?->applicant?->religion ?: 'Islam' }}</span>
                    </div>
                </div>
            </div>

            {{-- Subtle Horizontal Divider --}}
            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 0;">

            {{-- ── 2. ACADEMIC INFORMATION SECTION ──────────────────── --}}
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                {{-- Section Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 32px; height: 32px; border-radius: 9px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #059669; flex-shrink: 0;">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">Academic Information</h3>
                            <p style="font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0 0; font-weight: 500;">Current enrollment, grade level, and section assignment</p>
                        </div>
                    </div>
                </div>

                {{-- 2-Column Key-Value Grid --}}
                <div class="profile-info-grid">
                    <div class="profile-info-row">
                        <span class="profile-info-label">Grade Level</span>
                        <span class="profile-info-value">{{ $student?->grade_level ?: 'Grade 1' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Class Section</span>
                        <span class="profile-info-value">{{ $section?->name ?? 'G1-AL-MUNAWWARA' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">School Year</span>
                        <span class="profile-info-value">SY {{ $student?->school_year ?? '2026–2027' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Enrollment Status</span>
                        <span class="profile-info-value">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.6rem; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.04em; display: inline-flex; align-items: center; gap: 0.3rem;">
                                <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                                {{ ucfirst($student?->applicant?->student_type ?? 'Continuing') }}
                            </span>
                        </span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Learning Modality</span>
                        <span class="profile-info-value">{{ $learningMode ?: 'Online Learning' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Official Section Name</span>
                        <span class="profile-info-value">{{ $section?->official_name ?? ($section?->name ?? 'General') }}</span>
                    </div>
                </div>
            </div>

            {{-- Subtle Horizontal Divider --}}
            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 0;">

            {{-- ── 3. CONTACT & GUARDIAN SECTION ─────────────────────── --}}
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                {{-- Section Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 32px; height: 32px; border-radius: 9px; background: #fdf2f8; display: flex; align-items: center; justify-content: center; color: #db2777; flex-shrink: 0;">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">Contact & Guardian</h3>
                            <p style="font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0 0; font-weight: 500;">Parent, guardian, and emergency contact details</p>
                        </div>
                    </div>
                </div>

                {{-- 2-Column Key-Value Grid --}}
                <div class="profile-info-grid">
                    <div class="profile-info-row">
                        <span class="profile-info-label">Father's Name</span>
                        <span class="profile-info-value">{{ $father ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Mother's Name</span>
                        <span class="profile-info-value">{{ $mother ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Parent Contact Number</span>
                        <span class="profile-info-value">{{ $contactNo ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Parent Email Address</span>
                        <span class="profile-info-value">{{ $student?->applicant?->parent_email ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Emergency Contact</span>
                        <span class="profile-info-value">{{ $student?->applicant?->emergency_name ?: '—' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Home Address</span>
                        <span class="profile-info-value">{{ $student?->applicant?->street_address ?: ($student?->applicant?->address ?: '—') }}</span>
                    </div>
                </div>
            </div>

            {{-- Subtle Horizontal Divider --}}
            <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 0;">

            {{-- ── 4. MICROSOFT 365 ACCOUNT SECTION ─────────────────── --}}
            <div style="display: flex; flex-direction: column; gap: 1.25rem;" x-data="{ copied: false }">
                {{-- Section Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 32px; height: 32px; border-radius: 9px; background: #e0e7ff; display: flex; align-items: center; justify-content: center; color: #4338ca; flex-shrink: 0;">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M7 7h.01"/><path d="M17 7h.01"/><path d="M7 17h.01"/><path d="M17 17h.01"/></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: #0f172a; margin: 0; line-height: 1.2;">Microsoft 365 Account</h3>
                            <p style="font-size: 0.78rem; color: #64748b; margin: 0.15rem 0 0 0; font-weight: 500;">Institutional credentials for MS Teams, Outlook, and online apps</p>
                        </div>
                    </div>
                </div>

                {{-- 2-Column Key-Value Grid --}}
                @php
                    $m365Email = $student?->school_email ?? ($student?->ms_email ?? Auth::user()->email);
                @endphp
                <div class="profile-info-grid">
                    <div class="profile-info-row">
                        <span class="profile-info-label">M365 Username / Email</span>
                        <span class="profile-info-value" style="color: #047857; font-weight: 700;">{{ $m365Email }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-info-label">Account Status</span>
                        <span class="profile-info-value">
                            <span style="font-size: 0.75rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.6rem; border-radius: 6px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 0.3rem;">
                                <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                                Active
                            </span>
                        </span>
                    </div>
                    <div class="profile-info-row" style="grid-column: 1 / -1;">
                        <span class="profile-info-label">Password</span>
                        <span class="profile-info-value" style="color: #64748b; font-family: monospace; font-size: 0.875rem;">Password managed through Microsoft 365</span>
                    </div>
                </div>

                {{-- Actions in a compact row --}}
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.5rem;">
                    <button type="button" 
                            @click="navigator.clipboard.writeText('{{ $m365Email }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 1.15rem; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.8125rem; font-weight: 700; color: #334155; cursor: pointer; transition: all 0.15s ease;"
                            onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                        <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Username / Email'">Copy Username / Email</span>
                    </button>

                    <a href="https://login.microsoftonline.com/" target="_blank" rel="noopener noreferrer" 
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 1.15rem; border-radius: 10px; background: #059669; color: white; font-size: 0.8125rem; font-weight: 700; text-decoration: none; box-shadow: 0 2px 6px rgba(5,150,105,0.15); transition: all 0.15s ease;"
                       onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Open Microsoft 365</span>
                    </a>

                    <a href="https://account.activedirectory.windowsazure.com/ChangePassword.aspx" target="_blank" rel="noopener noreferrer"
                       style="display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.6rem 1.15rem; border-radius: 10px; border: 1px solid #e2e8f0; background: white; color: #475569; font-size: 0.8125rem; font-weight: 700; text-decoration: none; transition: all 0.15s ease;"
                       onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <span>Change Password</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
</x-student-layout>
