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
            activeTab: 'schedule',
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

                <button type="button" @click="activeTab = 'schedule'" style="
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

    {{-- ── 2. Full-Width Segmented Tabs Bar ──────────────────────────── --}}
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
                @click="activeTab = 'schedule'" 
                :class="activeTab === 'schedule' ? 'portal-tab-pill active' : 'portal-tab-pill'">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Class Schedule</span>
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
                padding: 0.65rem 0.5rem !important;
                font-size: 0.825rem !important;
            }
        }
        .class-countdown-banner{position:relative;display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:1rem;overflow:hidden;border:1px solid #a7f3d0;border-radius:20px;background:linear-gradient(135deg,#064e3b 0%,#047857 55%,#0d9488 100%);padding:1.25rem 1.4rem;color:#fff;box-shadow:0 12px 28px rgba(5,150,105,.14)}
        .class-countdown-banner:after{content:'';position:absolute;right:-35px;top:-55px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.08)}
        .class-countdown-icon{display:flex;width:48px;height:48px;align-items:center;justify-content:center;border-radius:14px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18)}.class-countdown-icon svg{width:24px;height:24px}
        .class-countdown-copy{position:relative;z-index:1;min-width:0}.class-countdown-copy h2{margin:.12rem 0;color:#fff;font-size:1.15rem;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.class-countdown-copy p{margin:0;color:#d1fae5;font-size:.78rem;font-weight:700}.class-countdown-copy .class-countdown-label{color:#a7f3d0;font-size:.65rem;font-weight:900;text-transform:uppercase;letter-spacing:.1em}
        .class-countdown-clock{position:relative;z-index:1;min-width:120px;border-radius:15px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);padding:.7rem 1rem;text-align:center}.class-countdown-clock strong{display:block;font-variant-numeric:tabular-nums;font-size:1.45rem;font-weight:950;letter-spacing:.03em}.class-countdown-clock span{display:block;margin-top:.1rem;color:#a7f3d0;font-size:.6rem;font-weight:850;text-transform:uppercase;letter-spacing:.08em}
        .class-countdown-inline{border-width:0 0 1px;border-radius:0;padding:1rem 1.25rem;box-shadow:inset 4px 0 0 #34d399}
        @media(max-width:640px){.class-countdown-banner{grid-template-columns:auto minmax(0,1fr);padding:1rem}.class-countdown-clock{grid-column:1/-1;width:100%}.class-countdown-copy h2{font-size:1rem}}
    </style>

    {{-- ── TAB 1: CLASS SCHEDULE CONTENT ──────────────────────────────── --}}
    <div x-show="activeTab === 'schedule'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="s-two-col-grid" style="width: 100%;">

        {{-- LEFT COLUMN --}}
        <div style="display:flex;flex-direction:column;gap:1.5rem;min-width:0;width:100%;">

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
                <div style="position:absolute;right:-20px;bottom:-20px;width:140px;height:140px;border-radius:50%;background:radial-gradient(circle,rgba(245,158,11,0.15),transparent 70%);pointer-events:none;"></div>
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

            {{-- Schedule Table Section --}}
            <div class="fade-up" style="display:flex;flex-direction:column;gap:0.85rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                    <div>
                        <h2 style="font-size:1.35rem;font-weight:900;color:#0f172a;margin:0;letter-spacing:-0.02em;">
                            {{ $todayLabel }}
                        </h2>
                        <div style="font-size:0.95rem;color:#475569;margin-top:4px;font-weight:700;">
                            {{ $todaySub }}
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
                    </div>
                </div>

                {{-- Table card --}}
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
                                    $teamUrl = $subj->team_url ?? 'https://teams.microsoft.com/';
                                    $isLive = false;
                                    $isEnded = false;
                                    if ($isSchoolDay) {
                                        $nowManila = \Carbon\Carbon::now('Asia/Manila');
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
                            <div class="s-empty-card" style="padding: 4rem 1.5rem; text-align:center;">
                                <div class="s-empty-icon-wrapper" style="background: #f0fdfa; display:inline-flex; align-items:center; justify-content:center; width:48px; height:48px; border-radius:50%; margin-bottom: 0.75rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
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
                </div>
            </div>

            <style>
                .completed-subjects-toggle{display:flex;width:100%;align-items:center;gap:.75rem;border:0;border-bottom:1px solid #e2e8f0;background:#f8fafc;padding:.8rem 1.25rem;text-align:left;color:#475569;cursor:pointer;transition:background .15s ease}.completed-subjects-toggle:hover{background:#f1f5f9}.completed-subjects-toggle-icon{display:flex;width:32px;height:32px;flex:0 0 32px;align-items:center;justify-content:center;border-radius:10px;background:#dcfce7;color:#16a34a}.completed-subjects-toggle-icon svg{width:16px;height:16px}.completed-subjects-toggle-copy{display:flex;min-width:0;flex:1;flex-direction:column}.completed-subjects-toggle-copy strong{font-size:.78rem;font-weight:900;color:#334155}.completed-subjects-toggle-copy small{margin-top:1px;font-size:.66rem;font-weight:700;color:#94a3b8}.completed-subjects-chevron{width:16px;height:16px;transition:transform .2s ease}.completed-subjects-chevron.is-open{transform:rotate(180deg)}
            </style>

        </div>

        {{-- RIGHT PANEL: Announcements --}}
        <div style="display:flex;flex-direction:column;gap:1.5rem;" class="fade-up">
            <div class="s-quick-actions-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:1.15rem; box-shadow:0 1px 4px rgba(0,0,0,0.04);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:1rem;padding-bottom:0.65rem;border-bottom:1px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <div style="width:30px;height:30px;border-radius:8px;background:#fef3c7;display:flex;align-items:center;justify-content:center;color:#d97706;flex-shrink:0;">
                            <i data-lucide="megaphone" style="width:15px;height:15px;"></i>
                        </div>
                        <span style="font-size:0.95rem;font-weight:700;color:#0f172a;letter-spacing:0;">Announcements</span>
                    </div>
                    <a href="{{ route('student.announcements') }}" style="font-size:0.72rem;font-weight:700;color:#059669;text-decoration:none;display:inline-flex;align-items:center;gap:0.2rem;flex-shrink:0;">
                        <span>View all</span>
                        <i data-lucide="arrow-right" style="width:11px;height:11px;"></i>
                    </a>
    </div>

{{-- Legacy in-page ID preview disabled: the verified public ID route is faster and authoritative. --}}
@if(false)
{{-- Digital ID Modal Overlay --}}
<div x-show="showIdModal" 
     class="id-modal-overlay"
     @click="showIdModal = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
    
    {{-- Backdrop blur overlay --}}
    <div class="id-modal-backdrop"></div>
    
    {{-- Modal Content Card --}}
    <div class="id-modal-card"
         @click.stop
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4">

        {{-- Verification Status --}}
        <div class="id-status-badge">
            <div class="id-status-icon">
                <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
            </div>
            <div>
                <h4 style="font-size: 0.75rem; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Officially Enrolled</h4>
                <p style="font-size: 0.625rem; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.05em; margin: 2px 0 0 0;">Active AMIS ID</p>
            </div>
        </div>

        {{-- 3D Flipping Card Container --}}
        <div class="perspective-1000"
             @click="isFlipped = !isFlipped"
             style="width: 340px; height: 538px; cursor: pointer; position: relative; border-radius: 24px;">
            
            <div class="card-inner"
                 :class="isFlipped ? 'is-flipped' : ''"
                 style="width: 100%; height: 100%; position: relative;">
                
                {{-- FRONT OF THE ID CARD --}}
                <div class="card-front" style="width: 340px; height: 538px; position: absolute; left: 0; top: 0; border-radius: 24px; overflow: hidden; background: #064e3b; box-shadow: 0 15px 35px rgba(0,0,0,0.3);">
                    <img src="{{ asset('assets/amis-id-template.png') }}?v=3" crossorigin="anonymous" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; border-radius: 24px;" alt="AMIS ID Template">

                    <!-- Student Photo Overlay -->
                    <img src="{{ $photoUrl ?: $fallbackPhoto }}"
                         id="photo-img-front"
                         crossorigin="anonymous"
                         style="position: absolute; left: 81px; top: 114px; width: 178px; height: 172px; overflow: hidden; border-radius: 14px; z-index: 10; object-fit: cover;"
                         onerror="if (!this.dataset.fallback && this.src !== '{{ $fallbackPhoto }}') { this.dataset.fallback='1'; this.src='{{ $fallbackPhoto }}'; } else { this.style.display='none'; document.getElementById('photo-warning-front').style.display='flex'; }"
                         alt="Student Photo">

                    <!-- Yellow Photo Warning Stamp (Anti-edit) -->
                    <div id="photo-warning-front"
                         class="absolute" 
                         style="position: absolute; left: 81px; top: 114px; width: 178px; height: 172px; z-index: 10; border-radius: 14px; background: #fef08a; border: 2.5px dashed #ca8a04; box-sizing: border-box; display: none; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 12px;">
                        <svg class="text-amber-600 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 24px; height: 24px; margin: 0 auto 4px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 10.5px; color: #451a03; text-transform: uppercase; line-height: 1.2; display: block;">Warning</span>
                        <span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 8.5px; color: #78350f; text-transform: uppercase; line-height: 1.2; display: block; margin-top: 2px;">Incomplete ID Data</span>
                        <span style="font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 8px; color: #92400e; line-height: 1.3; display: block; margin-top: 6px;">Photo is missing.<br>Please re-upload.</span>
                    </div>

                    <!-- Student ID Badge text overlay -->
                    <div style="position: absolute; left: 121px; top: 295px; width: 95px; height: 15px; z-index: 10; background: transparent; line-height: 1; text-align: center; display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-weight: 900; letter-spacing: 0.05em; font-size: 12.5px; color: white;">
                        {{ $badgeStudentId }}
                    </div>

                    <!-- Last Name Overlay -->
                    <div style="position: absolute; left: 15px; top: 334px; width: 310px; height: 32px; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; padding: 0 16px; box-sizing: border-box;">
                        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 900; text-transform: uppercase; color: #0f172a; margin: 0; line-height: 1; letter-spacing: -0.5px;
                                   {{ strlen($lastName) > 20 ? 'font-size: 16px;' : (strlen($lastName) > 15 ? 'font-size: 19px;' : (strlen($lastName) > 10 ? 'font-size: 23px;' : 'font-size: 26px;')) }}">
                            {{ $lastName }}
                        </h3>
                    </div>

                    <!-- First Name Overlay -->
                    <div style="position: absolute; left: 15px; top: 366px; width: 310px; height: 22px; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; padding: 0 16px; box-sizing: border-box;">
                        <h4 style="font-family: 'Outfit', sans-serif; font-weight: 700; text-transform: uppercase; color: #334155; margin: 0; line-height: 1;
                                   {{ strlen($firstName) > 25 ? 'font-size: 11px;' : (strlen($firstName) > 18 ? 'font-size: 13px;' : 'font-size: 15px;') }}">
                            {{ $firstName }}
                        </h4>
                    </div>

                    <!-- Grade Level -->
                    @php
                        $displayGrade = $section ? ($section->grade_level ?? $student?->grade_level) : $student?->grade_level;
                    @endphp
                    <div style="position: absolute; left: 15px; top: 406px; width: 310px; height: 30px; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; padding: 0 16px; box-sizing: border-box;">
                        <span style="font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 900; line-height: 1; letter-spacing: 0.5px; text-transform: uppercase; text-shadow: 0 1px 1px rgba(0,0,0,0.05); color: {{ $getGradeColor($displayGrade) }};">
                            {{ $displayGrade }}
                        </span>
                    </div>

                    <!-- LRN Overlay -->
                    @if($student?->applicant?->lrn && !in_array(strtoupper($student->applicant->lrn), ['N/A', 'NA', 'EMPTY', '']))
                        <div style="position: absolute; font-family: 'Outfit', sans-serif; font-size: 15.5px; font-weight: 700; z-index: 10; right: 8px; top: 405px; width: 22px; height: 130px; display: flex; align-items: center; justify-content: center; transform: rotate(-90deg); transform-origin: center; white-space: nowrap; letter-spacing: 0.05em; color: #1e293b;">
                            LRN: <span style="margin-left: 4px;">{{ $student->applicant->lrn }}</span>
                        </div>
                    @endif

                    <!-- QR Code -->
                    <div style="position: absolute; left: 134.5px; top: 458px; width: 71px; height: 71px; z-index: 10; padding: 2.5px; border-radius: 2px; background: white; box-sizing: border-box;">
                        <img src="{{ $qrCodeUrl }}" crossorigin="anonymous" style="width: 100%; height: 100%; object-fit: contain;" alt="QR Verification">
                    </div>
                </div>

                {{-- BACK OF THE ID CARD --}}
                <div class="card-back" style="width: 340px; height: 538px; position: absolute; left: 0; top: 0; border-radius: 24px; overflow: hidden; background: #0f172a; box-shadow: 0 15px 35px rgba(0,0,0,0.3);">
                    <img src="{{ asset('assets/amis-id-template-back.png') }}?v=3" crossorigin="anonymous" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 1; border-radius: 24px;" alt="AMIS ID Template Back">

                    <!-- Parent Name Overlay -->
                    <div style="position: absolute; left: 15px; top: 85px; width: 310px; height: 28px; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; padding: 0 16px; box-sizing: border-box;">
                        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 900; text-transform: uppercase; color: #0f172a; margin: 0; line-height: 1.1;
                                   {{ strlen($parent) > 20 ? 'font-size: 18px;' : (strlen($parent) > 14 ? 'font-size: 21px;' : 'font-size: 25px;') }}">
                            {{ $parent }}
                        </h3>
                    </div>

                    <!-- Contact Number Overlay -->
                    <div style="position: absolute; left: 15px; top: 118px; width: 310px; height: 20px; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; padding: 0 16px; box-sizing: border-box;">
                        <h4 style="font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: #1e293b; margin: 0; line-height: 1;">
                            {{ $contactNo }}
                        </h4>
                    </div>

                    <!-- Address Overlay -->
                    <div style="position: absolute; left: 20px; top: 144px; width: 300px; height: 42px; z-index: 10; text-align: center; display: flex; flex-direction: column; justify-content: center; padding: 0 20px; box-sizing: border-box;">
                        <p style="font-family: 'Outfit', sans-serif; font-weight: 700; text-transform: uppercase; color: #475569; margin: 0; line-height: 1.25;
                                   {{ strlen($address) > 60 ? 'font-size: 10.5px;' : (strlen($address) > 40 ? 'font-size: 12px;' : 'font-size: 13.5px;') }}">
                            {{ $address }}
                        </p>
                    </div>

                    <!-- Yellow Back Warning Stamp (Anti-edit / Missing Label) -->
                    @if($isEmergencyMissing)
                        <div style="position: absolute; left: 15px; top: 83px; width: 310px; height: 104px; z-index: 15; border-radius: 12px; box-sizing: border-box; background: rgba(254, 243, 199, 0.95); border: 2px dashed #f59e0b; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 12px;">
                            <svg class="text-amber-600 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="width: 20px; height: 20px; margin-bottom: 2px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span style="font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 11px; color: #451a03; text-transform: uppercase; tracking-spacing: 0.05em;">MISSING</span>
                            <span style="font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 8.5px; color: #78350f; line-height: 1.3; margin-top: 4px;">Emergency details are missing.<br>Please update student profile.</span>
                        </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- Manual Flip Action Helper --}}
        <button type="button" @click="isFlipped = !isFlipped"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-200 hover:border-slate-300 text-slate-600 font-bold text-xs rounded-xl hover:bg-slate-50 transition-all duration-200 cursor-pointer shadow-sm bg-white" style="display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
            <i data-lucide="refresh-cw" style="width: 12px; height: 12px; color: #64748b;"></i>
            <span>Flip ID Card</span>
        </button>

        {{-- Close button --}}
        <button type="button" @click="showIdModal = false" class="id-modal-close-btn">
            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
        </button>
    </div>
</div>
@endif

</div>
</x-student-layout>
