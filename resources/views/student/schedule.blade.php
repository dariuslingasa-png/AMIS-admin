<x-student-layout title="Class Schedule">

@include('student.schedule.partials._styles')

<style>
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }
        .no-print, .student-sidebar, header, nav, footer, .student-header-bar {
            display: none !important;
        }
        .print-only {
            display: block !important;
        }
        .report-card-print-box {
            border: 2px solid #000000 !important;
            box-shadow: none !important;
            padding: 1.5rem !important;
            border-radius: 0 !important;
        }
    }
    .print-only {
        display: none;
    }
</style>

<div class="space-y-6"
     x-data="testerScheduleSwitcher({
         isTester: {{ $isTester ? 'true' : 'false' }},
         gradesAndSections: {{ json_encode($gradesAndSections) }},
         initialGrade: '{{ $currentGrade }}',
         initialSectionId: '{{ $currentSectionId }}',
         initialSectionName: '{{ addslashes($currentSectionName) }}'
     })"
     x-init="init()">

    {{-- ── 1. Page Header & Action Bar (MATCHES GRADES PAGE) ───────── --}}
    <div class="no-print" style="display: flex; flex-direction: column; gap: 1.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span style="font-size: 0.78rem; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.08em; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>
                        Academic Schedule & Timetable
                    </span>
                </div>
                <h1 style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 1.75rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2; letter-spacing: -0.025em;">
                    Class Schedule
                </h1>
                <p style="font-size: 0.85rem; color: #64748b; margin: 0.25rem 0 0 0; font-weight: 500;">
                    Official weekly timetable, scheduled periods, and faculty assignments for SY {{ $studentInfo['school_year'] }}.
                </p>
            </div>

            {{-- Action Buttons --}}
            <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                <button type="button" onclick="window.print()" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 0.45rem;
                    padding: 0.6rem 1.15rem;
                    border-radius: 12px;
                    background: #ffffff;
                    color: #0f172a;
                    font-size: 0.825rem;
                    font-weight: 700;
                    border: 1.5px solid #e2e8f0;
                    cursor: pointer;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
                    transition: all 0.15s ease;
                " onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e2e8f0';">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    <span>Print Schedule</span>
                </button>

                <a href="{{ route('student.grades') }}" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 0.45rem;
                    padding: 0.6rem 1.15rem;
                    border-radius: 12px;
                    background: linear-gradient(135deg, #059669 0%, #047857 100%);
                    color: #ffffff;
                    font-size: 0.825rem;
                    font-weight: 700;
                    text-decoration: none;
                    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
                    transition: all 0.15s ease;
                " onmouseover="this.style.boxShadow='0 6px 18px rgba(5, 150, 105, 0.32)'; this.style.transform='translateY(-1px)'" onmouseout="this.style.boxShadow='0 4px 12px rgba(5, 150, 105, 0.2)'; this.style.transform='none'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <span>Report Card & Grades</span>
                </a>
            </div>
        </div>

        {{-- ── 2. Academic Summary Metrics Grid (MATCHES GRADES PAGE) ─── --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
            
            {{-- Card 1: Section Gradient Card --}}
            <div class="fade-up" style="background: linear-gradient(135deg, #064e3b 0%, #047857 70%, #0d9488 100%); border-radius: 20px; padding: 1.35rem; color: #ffffff; position: relative; overflow: hidden; box-shadow: 0 10px 24px rgba(5, 150, 105, 0.15);">
                <div style="position: absolute; right: -15px; bottom: -15px; width: 90px; height: 90px; border-radius: 50%; background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%); pointer-events: none;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                    <span style="font-size: 0.72rem; font-weight: 800; color: #a7f3d0; text-transform: uppercase; letter-spacing: 0.08em;">Current Class Section</span>
                    <span style="font-size: 0.68rem; font-weight: 800; background: rgba(255,255,255,0.22); color: #ffffff; padding: 0.15rem 0.55rem; border-radius: 999px;">Active Schedule</span>
                </div>
                <div style="margin-bottom: 0.35rem;">
                    <span id="metric-grade" style="font-size: 1.75rem; font-weight: 950; line-height: 1.1; letter-spacing: -0.02em; display: block;" x-text="currentGrade">{{ $currentGrade }}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 700; color: #a7f3d0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span id="metric-section" x-text="currentSectionName">{{ $currentSectionName }}</span>
                </div>
            </div>

            {{-- Card 2: Learning Modality & Shift --}}
            <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">Learning Modality</span>
                    <div id="metric-modality" style="font-size: 1.15rem; font-weight: 900; color: #0f172a; margin-bottom: 0.2rem;">{{ $studentInfo['modality'] }}</div>
                    <span style="font-size: 0.78rem; font-weight: 600; color: #059669; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span style="width: 5px; height: 5px; border-radius: 50%; background: #10b981;"></span>
                        Official AMIS Timetable
                    </span>
                </div>
                <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; font-size: 0.75rem;">
                    <span style="color: #64748b; font-weight: 600;">Shift / Timing:</span>
                    <strong id="metric-shift" style="color: #0f172a; font-weight: 800;">{{ $studentInfo['shift'] ?: 'Regular Day Shift' }}</strong>
                </div>
            </div>

            {{-- Card 3: Scheduled Time Blocks --}}
            <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">Weekly Schedule</span>
                    <div style="font-size: 1.55rem; font-weight: 950; color: #0f172a; margin-bottom: 0.2rem;">
                        {{ count($matrix) }} <small style="font-size: 0.85rem; font-weight: 700; color: #64748b;">Time Blocks</small>
                    </div>
                    <span style="font-size: 0.78rem; font-weight: 600; color: #64748b;">
                        DepEd K-12 MATATAG Curriculum
                    </span>
                </div>
                <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; font-size: 0.75rem;">
                    <span style="color: #64748b; font-weight: 600;">Timetable Format:</span>
                    <strong style="color: #059669; font-weight: 800;">Sunday – Thursday</strong>
                </div>
            </div>

            {{-- Card 4: School Year Info --}}
            <div class="fade-up" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; padding: 1.35rem; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                <div>
                    <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">School Year & Term</span>
                    <div style="font-size: 1.25rem; font-weight: 900; color: #0f172a; margin-bottom: 0.2rem;">
                        SY {{ $studentInfo['school_year'] }}
                    </div>
                    <span style="font-size: 0.78rem; font-weight: 600; color: #d97706; background: #fef3c7; padding: 0.15rem 0.55rem; border-radius: 6px; display: inline-block;">
                        1st Term Ongoing
                    </span>
                </div>
                <div style="margin-top: 0.75rem; padding-top: 0.65rem; border-top: 1px dashed #f1f5f9; display: flex; justify-content: space-between; font-size: 0.75rem;">
                    <span style="color: #64748b; font-weight: 600;">Student LRN:</span>
                    <strong style="color: #0f172a; font-family: monospace;">{{ $student->student_number ?? '260000' }}</strong>
                </div>
            </div>

        </div>
    </div>

    {{-- ── 3. Main Schedule Panel (EXACT MATCH TO GRADES PANEL) ────── --}}
    <div class="fade-up report-card-print-box" style="
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        padding: 2.25rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 24px -4px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        gap: 1.75rem;
        width: 100%;
    ">

        {{-- Printable Official Header --}}
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; padding-bottom: 1.5rem; border-bottom: 2px solid #0f172a;">
            <div style="display: flex; align-items: center; gap: 1.15rem;">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS Logo" style="width: 62px; height: 62px; object-fit: contain;">
                <div>
                    <div style="font-family: 'Amiri', 'Traditional Arabic', serif; font-size: 1.2rem; font-weight: 700; color: #047857; line-height: 1.25; direction: rtl; text-align: left;" dir="rtl">
                        المدرسة المنورة الإسلامية
                    </div>
                    <h2 style="font-family: 'Plus Jakarta Sans', sans-serif !important; font-size: 1.25rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2; letter-spacing: -0.01em;">
                        AL MUNAWWARA ISLAMIC SCHOOL
                    </h2>
                    <p style="font-size: 0.78rem; font-weight: 600; color: #64748b; margin: 0.15rem 0 0 0;">
                        Official Class Schedule & Weekly Timetable · School Year {{ $studentInfo['school_year'] }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Student Information Banner --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 1.25rem;">
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Learner Name</span>
                <span style="font-size: 0.95rem; font-weight: 900; color: #0f172a;">{{ $studentInfo['name'] }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Student ID / LRN</span>
                <span style="font-size: 0.95rem; font-weight: 800; color: #0f172a; font-family: monospace;">{{ $student->student_number ?? '260000' }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Grade & Section</span>
                <span id="banner-grade-sec" style="font-size: 0.95rem; font-weight: 800; color: #0f172a;" x-text="currentGrade + ' — ' + currentSectionName">{{ $currentGrade }} — {{ $currentSectionName }}</span>
            </div>
            <div>
                <span style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; display: block;">Shift & Modality</span>
                <span id="banner-curriculum" style="font-size: 0.925rem; font-weight: 700; color: #059669;">{{ $studentInfo['modality'] }} {{ $studentInfo['shift'] ? '• ' . $studentInfo['shift'] : '' }}</span>
            </div>
        </div>

        {{-- Controls Bar (Tabs + Tester Switcher) --}}
        <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; background: #e2e8f0; padding: 0.25rem; border-radius: 12px; gap: 0.25rem;">
                <button type="button" 
                        @click="currentTab = 'grid'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                        class="sched-tab-btn" 
                        :class="(!currentTab || currentTab === 'grid') ? 'active' : ''">
                    <i data-lucide="calendar-range" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                    <span>Timetable Calendar</span>
                </button>

                <button type="button" 
                        @click="currentTab = 'list'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                        class="sched-tab-btn" 
                        :class="currentTab === 'list' ? 'active' : ''">
                    <i data-lucide="list" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                    <span>Daily Classes</span>
                </button>
            </div>

            @if($isTester)
                <!-- Connected Grade & Section Filter for Tester -->
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; background: #ffffff; border: 1.5px solid #0d9488; border-radius: 12px; padding: 0.35rem 0.75rem; box-shadow: 0 2px 6px rgba(13,148,136,0.1);">
                    <span style="font-size: 11px; font-weight: 800; color: #0f766e; text-transform: uppercase; display: inline-flex; align-items: center; gap: 0.3rem;">
                        <i data-lucide="flask-conical" style="width: 13px; height: 13px; color: #0d9488;"></i>
                        Grade:
                    </span>
                    <select id="testerGradeSelect" x-model="selectedGrade" @change="onGradeChange()"
                            style="font-size: 12.5px; font-weight: 700; color: #0f172a; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.35rem 0.6rem; cursor: pointer; outline: none;">
                        @foreach($gradesAndSections as $gradeName => $secs)
                            <option value="{{ $gradeName }}" {{ $currentGrade === $gradeName ? 'selected' : '' }}>
                                {{ $gradeName }}
                            </option>
                        @endforeach
                    </select>

                    <span style="font-size: 11px; font-weight: 800; color: #0f766e; text-transform: uppercase; margin-left: 0.25rem;">
                        Section:
                    </span>
                    <select id="testerSectionSelect" x-model="selectedSectionId" @change="onSectionChange()"
                            style="font-size: 12.5px; font-weight: 700; color: #0f172a; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.35rem 0.6rem; cursor: pointer; outline: none; max-width: 260px;">
                        @foreach($gradesAndSections[$currentGrade] ?? [] as $secOpt)
                            <option value="{{ $secOpt['id'] }}" {{ $currentSectionId === $secOpt['id'] ? 'selected' : '' }}>
                                {{ $secOpt['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" @click="resetToMySection()" title="Reset to default section"
                            style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.35rem 0.65rem; font-size: 11px; font-weight: 750; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <i data-lucide="rotate-ccw" style="width: 12px; height: 12px;"></i>
                        Reset
                    </button>

                    <span x-show="loading" style="font-size: 11px; font-weight: 700; color: #0d9488; display: inline-flex; align-items: center; gap: 0.25rem;">
                        <span style="width: 12px; height: 12px; border: 2px solid #0d9488; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                    </span>
                </div>
            @endif

            <div style="font-size: 12px; font-weight: 600; line-height: 16px; color: #64748b;">
                School Year {{ $studentInfo['school_year'] }}
            </div>
        </div>

        {{-- ── 4. Dynamic Timetable Content ────────────────────────────── --}}
        <!-- Tab 1: Timetable Calendar Grid -->
        <div x-show="!currentTab || currentTab === 'grid'" class="space-y-4" :style="loading ? 'opacity: 0.4; pointer-events: none; transition: opacity 0.15s ease;' : 'transition: opacity 0.15s ease;'">
            <div id="schedule-grid-container">
                @include('student.schedule.partials._schedule_grid_content')
            </div>
        </div>

        <!-- Tab 2: Daily Classes List -->
        <div x-show="currentTab === 'list'" class="space-y-4" style="display: none;" :style="loading ? 'opacity: 0.4; pointer-events: none; transition: opacity 0.15s ease;' : 'transition: opacity 0.15s ease;'">
            <div id="schedule-list-container">
                @include('student.schedule.partials._schedule_list_content')
            </div>
        </div>

    </div>

    @include('student.schedule.partials._preview-modal')
</div>

<script>
function testerScheduleSwitcher(config) {
    return {
        isTester: config.isTester,
        gradesAndSections: config.gradesAndSections || {},
        selectedGrade: config.initialGrade || '',
        selectedSectionId: config.initialSectionId || '',
        currentGrade: config.initialGrade || '',
        currentSectionName: config.initialSectionName || '',
        currentTab: 'grid',
        activeDay: 'Sunday',
        isFullscreen: false,
        previewPhoto: null,
        loading: false,

        init() {
            if (!this.isTester) return;
            this.syncSectionSelect();
        },

        syncSectionSelect() {
            const select = document.getElementById('testerSectionSelect');
            if (!select) return;
            select.innerHTML = '';
            const secs = this.gradesAndSections[this.selectedGrade] || [];
            secs.forEach(sec => {
                const opt = document.createElement('option');
                opt.value = sec.id;
                opt.textContent = sec.name + ' (' + sec.shift + ')';
                if (sec.id === this.selectedSectionId) opt.selected = true;
                select.appendChild(opt);
            });
        },

        onGradeChange() {
            this.syncSectionSelect();
            const secs = this.gradesAndSections[this.selectedGrade] || [];
            if (secs.length > 0) {
                this.selectedSectionId = secs[0].id;
                const select = document.getElementById('testerSectionSelect');
                if (select) select.value = this.selectedSectionId;
                this.fetchSchedule(this.selectedSectionId);
            }
        },

        onSectionChange() {
            const select = document.getElementById('testerSectionSelect');
            if (select) {
                this.selectedSectionId = select.value;
            }
            if (!this.selectedSectionId) return;
            this.fetchSchedule(this.selectedSectionId);
        },

        resetToMySection() {
            this.loading = true;
            fetch('{{ route("student.class-schedule") }}?reset_section=1', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.selectedGrade = data.currentGrade;
                    this.selectedSectionId = data.currentSectionId;
                    this.currentGrade = data.currentGrade;
                    this.currentSectionName = data.currentSectionName;
                    
                    const gSelect = document.getElementById('testerGradeSelect');
                    if (gSelect) gSelect.value = this.selectedGrade;
                    this.syncSectionSelect();

                    const mGrade = document.getElementById('metric-grade');
                    if (mGrade) mGrade.textContent = data.currentGrade;
                    const mSec = document.getElementById('metric-section');
                    if (mSec) mSec.textContent = data.currentSectionName;
                    const bGradeSec = document.getElementById('banner-grade-sec');
                    if (bGradeSec) bGradeSec.textContent = data.currentGrade + ' — ' + data.currentSectionName;

                    const gridContainer = document.getElementById('schedule-grid-container');
                    if (gridContainer && data.gridHtml) {
                        gridContainer.innerHTML = data.gridHtml;
                        if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(gridContainer);
                    }

                    const listContainer = document.getElementById('schedule-list-container');
                    if (listContainer && data.listHtml) {
                        listContainer.innerHTML = data.listHtml;
                        if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(listContainer);
                    }

                    window.history.pushState(null, '', '{{ route("student.class-schedule") }}');
                    if (window.lucide) window.lucide.createIcons();
                }
            })
            .catch(err => console.error('Reset error:', err))
            .finally(() => {
                this.loading = false;
            });
        },

        fetchSchedule(sectionId) {
            this.loading = true;
            const url = '{{ route("student.class-schedule") }}?section_id=' + encodeURIComponent(sectionId);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    this.currentGrade = data.currentGrade;
                    this.currentSectionName = data.currentSectionName;
                    
                    const mGrade = document.getElementById('metric-grade');
                    if (mGrade) mGrade.textContent = data.currentGrade;
                    const mSec = document.getElementById('metric-section');
                    if (mSec) mSec.textContent = data.currentSectionName;
                    const bGradeSec = document.getElementById('banner-grade-sec');
                    if (bGradeSec) bGradeSec.textContent = data.currentGrade + ' — ' + data.currentSectionName;

                    if (data.studentInfo) {
                        const mModality = document.getElementById('metric-modality');
                        if (mModality) mModality.textContent = data.studentInfo.modality;
                        const mShift = document.getElementById('metric-shift');
                        if (mShift) mShift.textContent = data.studentInfo.shift || 'Regular Day Shift';
                        const bCurr = document.getElementById('banner-curriculum');
                        if (bCurr) bCurr.textContent = data.studentInfo.modality + (data.studentInfo.shift ? ' • ' + data.studentInfo.shift : '');
                    }

                    const gridContainer = document.getElementById('schedule-grid-container');
                    if (gridContainer && data.gridHtml) {
                        gridContainer.innerHTML = data.gridHtml;
                        if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(gridContainer);
                    }

                    const listContainer = document.getElementById('schedule-list-container');
                    if (listContainer && data.listHtml) {
                        listContainer.innerHTML = data.listHtml;
                        if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(listContainer);
                    }

                    window.history.pushState(null, '', url);
                    if (window.lucide) window.lucide.createIcons();
                }
            })
            .catch(err => {
                console.error('Failed to switch schedule:', err);
                window.location.href = url;
            })
            .finally(() => {
                this.loading = false;
            });
        }
    };
}
</script>
</x-student-layout>
