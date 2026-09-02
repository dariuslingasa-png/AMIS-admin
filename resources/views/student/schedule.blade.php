<x-student-layout title="Class Schedule">
@include('student.schedule.partials._styles')

<div class="space-y-6"
     x-data="testerScheduleSwitcher({
         isTester: {{ $isTester ? 'true' : 'false' }},
         gradesAndSections: {{ json_encode($gradesAndSections) }},
         initialGrade: '{{ $currentGrade }}',
         initialSectionId: '{{ $currentSectionId }}',
         initialSectionName: '{{ addslashes($currentSectionName) }}'
     })"
     x-init="init()">

    <!-- Header card (EXACT ORIGINAL AMIS STUDENT PORTAL DESIGN) -->
    <div class="s-quick-actions-card" style="padding: 1.75rem; background: white; border-radius: 20px; border: 1.5px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 12px; font-weight: 600; line-height: 16px; color: #0d9488; background: #f0fdfa; border: 1px solid #ccfbf1; padding: 0.25rem 0.65rem; border-radius: 999px;">
                <i data-lucide="calendar-days" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                <span>Weekly Timetable</span>
            </div>
            <h1 style="font-size: 30px; font-weight: 700; line-height: 38px; color: #0f172a; margin: 0.5rem 0 0.25rem;">Class Schedule</h1>
            <p style="font-size: 15px; font-weight: 400; line-height: 24px; color: #475569; margin: 0;">Official weekly class timetable and daily schedule for your enrolled section.</p>
        </div>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap; flex-shrink: 0;">
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0.75rem 1.25rem; text-align: center; min-width: 130px;">
                <p style="font-size: 12px; font-weight: 600; line-height: 16px; color: #64748b; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Student</p>
                <p id="student-name-stat" style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; margin-top: 0.25rem;">{{ $studentInfo['name'] }}</p>
            </div>

            <div style="background: #f0fdfa; border: 1.5px solid #ccfbf1; border-radius: 14px; padding: 0.75rem 1.25rem; text-align: center; min-width: 150px;">
                <p style="font-size: 12px; font-weight: 600; line-height: 16px; color: #0f766e; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Class Section</p>
                <p id="student-section-stat" style="font-size: 15px; font-weight: 700; color: #0d9488; margin: 0; margin-top: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $studentInfo['section'] }}">
                    {{ $studentInfo['grade_level'] }} — {{ $studentInfo['section'] }}
                </p>
            </div>

            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 0.75rem 1.25rem; text-align: center; min-width: 130px;">
                <p style="font-size: 12px; font-weight: 600; line-height: 16px; color: #64748b; text-transform: uppercase; margin: 0; letter-spacing: 0.05em;">Modality / Shift</p>
                <p id="student-modality-stat" style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0; margin-top: 0.25rem;">{{ $studentInfo['modality'] }}</p>
            </div>
        </div>
    </div>

    <!-- Tab switcher bar & Tester Filter (ORIGINAL AMIS LAYOUT) -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 0.85rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; background: #e2e8f0; padding: 0.25rem; border-radius: 12px; gap: 0.25rem;">
            <button type="button" @click="currentTab = 'grid'; $nextTick(() => window.lucide && window.lucide.createIcons())" class="sched-tab-btn" :class="currentTab === 'grid' ? 'active' : ''">
                <i data-lucide="calendar-range" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                <span>Timetable Calendar</span>
            </button>

            <button type="button" @click="currentTab = 'list'; $nextTick(() => window.lucide && window.lucide.createIcons())" class="sched-tab-btn" :class="currentTab === 'list' ? 'active' : ''">
                <i data-lucide="list" style="width: 14px; height: 14px; flex-shrink: 0;"></i>
                <span>Daily Classes</span>
            </button>
        </div>

        @if($isTester)
            <!-- Connected Grade & Section Filter (Clean, compact, matching class-schedule.html) -->
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

    <!-- Active Indicator Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; background: #f0fdfa; border: 1.5px solid #ccfbf1; border-left: 5px solid #0d9488; border-radius: 12px; padding: 0.75rem 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
            <span style="width: 9px; height: 9px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px #10b981;"></span>
            <span style="font-size: 14px; font-weight: 750; color: #0f766e;">
                Viewing: <strong id="indicator-viewing-text" x-text="currentGrade + ' — ' + currentSectionName">{{ $currentGrade }} — {{ $currentSectionName }}</strong>
            </span>
        </div>
        <span style="font-size: 11px; font-weight: 800; color: #059669; background: white; border: 1px solid #a7f3d0; padding: 0.25rem 0.65rem; border-radius: 999px; text-transform: uppercase;">
            Official Class Schedule
        </span>
    </div>

    <!-- DYNAMIC SCHEDULE CONTENT CONTAINER -->
    <div id="schedule-content-container" :style="loading ? 'opacity: 0.4; pointer-events: none; transition: opacity 0.15s ease;' : 'transition: opacity 0.15s ease;'">
        @include('student.schedule.partials._schedule_content')
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

                    const sStat = document.getElementById('student-section-stat');
                    if (sStat && data.studentInfo) {
                        sStat.textContent = data.studentInfo.grade_level + ' — ' + data.studentInfo.section;
                    }

                    const mStat = document.getElementById('student-modality-stat');
                    if (mStat && data.studentInfo) {
                        mStat.textContent = data.studentInfo.modality + (data.studentInfo.shift ? ' • ' + data.studentInfo.shift : '');
                    }

                    const container = document.getElementById('schedule-content-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
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
                    
                    const sStat = document.getElementById('student-section-stat');
                    if (sStat && data.studentInfo) {
                        sStat.textContent = data.studentInfo.grade_level + ' — ' + data.studentInfo.section;
                    }

                    const mStat = document.getElementById('student-modality-stat');
                    if (mStat && data.studentInfo) {
                        mStat.textContent = data.studentInfo.modality + (data.studentInfo.shift ? ' • ' + data.studentInfo.shift : '');
                    }

                    const container = document.getElementById('schedule-content-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
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
