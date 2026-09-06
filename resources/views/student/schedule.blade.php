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

    {{-- ── Main Schedule Panel ────────────────────────────────────── --}}
    <div class="fade-up report-card-print-box" style="
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        width: 100%;
    ">

        {{-- Controls Bar (Tabs + Context + Accessible Tester Switcher) --}}
        <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 1.15rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap;">
                <div style="display: inline-flex; background: #f1f5f9; padding: 5px; border-radius: 14px; border: 1.5px solid #cbd5e1; gap: 6px;">
                    <button type="button" 
                            @click="currentTab = 'grid'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                            class="sched-tab-btn" 
                            :class="(!currentTab || currentTab === 'grid') ? 'active' : ''">
                        <i data-lucide="calendar-range" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span>Timetable Calendar</span>
                    </button>

                    <button type="button" 
                            @click="currentTab = 'list'; $nextTick(() => window.lucide && window.lucide.createIcons())" 
                            class="sched-tab-btn" 
                            :class="currentTab === 'list' ? 'active' : ''">
                        <i data-lucide="list" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <span>Daily Classes</span>
                    </button>
                </div>

                <div style="font-size: 15px; color: #334155; font-weight: 600;">
                    Weekly timetable for <strong id="banner-grade-sec" style="color: #0f172a; font-weight: 900;" x-text="currentGrade + ' — ' + currentSectionName">{{ $currentGrade }} — {{ $currentSectionName }}</strong> · School Year {{ $studentInfo['school_year'] }}
                </div>
            </div>

            @if($isTester)
                <!-- Connected Grade & Section Filter for Tester -->
                <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 14px; padding: 0.45rem 0.85rem;">
                    <span style="font-size: 13px; font-weight: 900; color: #047857; text-transform: uppercase; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="flask-conical" style="width: 15px; height: 15px; color: #059669;"></i>
                        Grade:
                    </span>
                    <select id="testerGradeSelect" x-model="selectedGrade" @change="onGradeChange()"
                            style="font-size: 13.5px; font-weight: 750; color: #0f172a; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 0.45rem 0.75rem; cursor: pointer; outline: none;">
                        @foreach($gradesAndSections as $gradeName => $secs)
                            <option value="{{ $gradeName }}" {{ $currentGrade === $gradeName ? 'selected' : '' }}>
                                {{ $gradeName }}
                            </option>
                        @endforeach
                    </select>

                    <span style="font-size: 13px; font-weight: 900; color: #047857; text-transform: uppercase; margin-left: 0.25rem;">
                        Section:
                    </span>
                    <select id="testerSectionSelect" x-model="selectedSectionId" @change="onSectionChange()"
                            style="font-size: 13.5px; font-weight: 750; color: #0f172a; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 0.45rem 0.75rem; cursor: pointer; outline: none; max-width: 280px;">
                        @foreach($gradesAndSections[$currentGrade] ?? [] as $secOpt)
                            <option value="{{ $secOpt['id'] }}" {{ $currentSectionId === $secOpt['id'] ? 'selected' : '' }}>
                                {{ $secOpt['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <button type="button" @click="resetToMySection()" title="Reset to default section"
                            style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 0.45rem 0.75rem; font-size: 13px; font-weight: 800; color: #334155; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="rotate-ccw" style="width: 14px; height: 14px;"></i>
                        Reset
                    </button>

                    <span x-show="loading" style="font-size: 13px; font-weight: 800; color: #059669; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <span style="width: 14px; height: 14px; border: 2px solid #059669; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
                    </span>
                </div>
            @endif
        </div>

        {{-- ── Dynamic Timetable Content ────────────────────────────── --}}
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
            .then(res => res.json())
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
            .catch(err => console.error('Schedule fetch error:', err))
            .finally(() => {
                this.loading = false;
            });
        }
    };
}
</script>
</x-student-layout>
