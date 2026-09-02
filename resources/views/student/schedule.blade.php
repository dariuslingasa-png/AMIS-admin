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

    @if($isTester)
        <!-- TESTER GRADE & SECTION FILTER / SWITCHER TOOLBAR -->
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 20px; padding: 1.25rem 1.5rem; color: white; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25); border: 1.5px solid #334155; position: relative; overflow: hidden;">
            <!-- Ambient Glow -->
            <div style="position: absolute; right: -40px; top: -40px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(13, 148, 136, 0.35) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                
                <!-- Top Row: Badge & Current Viewing Indicator -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; background: #0d9488; color: white; padding: 0.25rem 0.6rem; border-radius: 999px; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i data-lucide="flask-conical" style="width: 13px; height: 13px;"></i>
                            Tester Portal Inspector
                        </span>
                        <span style="font-size: 12px; color: #94a3b8; font-weight: 500;">
                            (Account: mon.lingasa@amis.edu.ph)
                        </span>
                    </div>

                    <!-- Clear Indicator -->
                    <div style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 12px; padding: 0.35rem 0.85rem; display: flex; align-items: center; gap: 0.45rem;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px #10b981;"></span>
                        <span style="font-size: 13px; font-weight: 750; color: #f8fafc;">
                            Viewing: <span style="color: #5eead4;" x-text="currentGrade + ' — ' + currentSectionName">Viewing: {{ $currentGrade }} — {{ $currentSectionName }}</span>
                        </span>
                    </div>
                </div>

                <!-- Bottom Row: Connected Grade and Section Selectors -->
                <div style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
                    
                    <!-- 1. Select Grade Level -->
                    <div style="flex: 1; min-width: 180px;">
                        <label style="font-size: 11px; font-weight: 750; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.3rem;">
                            1. Select Grade Level
                        </label>
                        <select x-model="selectedGrade"
                                @change="onGradeChange()"
                                style="width: 100%; font-size: 13px; font-weight: 700; padding: 0.55rem 0.75rem; border-radius: 10px; border: 1.5px solid #475569; background: #0f172a; color: white; cursor: pointer; outline: none;">
                            <template x-for="grade in Object.keys(gradesAndSections)" :key="grade">
                                <option :value="grade" x-text="grade" :selected="grade === selectedGrade"></option>
                            </template>
                        </select>
                    </div>

                    <!-- 2. Select Section (connected to Grade) -->
                    <div style="flex: 2; min-width: 260px;">
                        <label style="font-size: 11px; font-weight: 750; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.3rem;">
                            2. Select Section (<span x-text="availableSections.length"></span> available)
                        </label>
                        <div style="position: relative;">
                            <select x-model="selectedSectionId"
                                    @change="onSectionChange()"
                                    :disabled="loading"
                                    style="width: 100%; font-size: 13px; font-weight: 700; padding: 0.55rem 0.75rem; border-radius: 10px; border: 1.5px solid #0d9488; background: #0f172a; color: #5eead4; cursor: pointer; outline: none;">
                                <template x-for="sec in availableSections" :key="sec.id">
                                    <option :value="sec.id" x-text="sec.name + ' (' + sec.shift + ')'" :selected="sec.id === selectedSectionId"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Reset Button -->
                    <div style="align-self: flex-end;">
                        <button type="button"
                                @click="resetToMySection()"
                                :disabled="loading"
                                style="font-size: 12px; font-weight: 750; color: #94a3b8; background: rgba(255,255,255,0.05); border: 1px solid #475569; border-radius: 10px; padding: 0.55rem 0.95rem; cursor: pointer; transition: all 0.15s ease; display: inline-flex; align-items: center; gap: 0.35rem;">
                            <i data-lucide="rotate-ccw" style="width: 13px; height: 13px;"></i>
                            <span>Reset</span>
                        </button>
                    </div>

                    <!-- Real-time Loading Indicator -->
                    <div x-show="loading" style="display: flex; align-items: center; gap: 0.4rem; font-size: 12px; font-weight: 700; color: #5eead4; align-self: flex-end; padding-bottom: 0.5rem;">
                        <span style="width: 14px; height: 14px; border: 2px solid #5eead4; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite; display: inline-block;"></span>
                        <span>Updating schedule...</span>
                    </div>

                </div>

            </div>
        </div>
    @endif

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
        availableSections: [],
        loading: false,

        init() {
            if (!this.isTester) return;
            this.updateAvailableSections();
        },

        updateAvailableSections() {
            if (this.gradesAndSections && this.gradesAndSections[this.selectedGrade]) {
                this.availableSections = this.gradesAndSections[this.selectedGrade];
            } else {
                this.availableSections = [];
            }
        },

        onGradeChange() {
            this.updateAvailableSections();
            if (this.availableSections.length > 0) {
                // Automatically select first section of this grade and refresh
                this.selectedSectionId = this.availableSections[0].id;
                this.fetchSchedule(this.selectedSectionId);
            }
        },

        onSectionChange() {
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
                    this.updateAvailableSections();
                    
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
                    
                    const container = document.getElementById('schedule-content-container');
                    if (container && data.html) {
                        container.innerHTML = data.html;
                    }

                    // Update URL without reloading page
                    window.history.pushState(null, '', url);

                    // Reinitialize Lucide icons
                    if (window.lucide) window.lucide.createIcons();
                }
            })
            .catch(err => {
                console.error('Failed to switch schedule:', err);
                // Fallback to regular navigation if fetch fails
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
