<!-- Serialized Roster & Sections Data -->
<script type="application/json" id="sections-roster-data">
    @json($sections)
</script>

<!-- Independent ApexCharts & Interactive Modal Script -->
<script>
    window.currentRosterSectionId = null;

    window.exportRosterToPdf = function() {
        if (!window.currentRosterSectionId) return;

        const exportBtn = document.getElementById('exportPdfBtn');
        if (exportBtn) {
            exportBtn.disabled = true;
            exportBtn.innerHTML = `<i data-lucide="file-down" class="w-4 h-4"></i> Opening PDF...`;
        }

        const printUrl = @json(route('admin.students.roster-print', ['section' => '__SECTION_ID__']));
        const targetUrl = printUrl.replace('__SECTION_ID__', encodeURIComponent(window.currentRosterSectionId)) + '?print=1';
        const win = window.open(targetUrl, '_blank');

        if (!win) {
            const link = document.createElement('a');
            link.href = targetUrl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            document.body.appendChild(link);
            link.click();
            link.remove();
        }

        setTimeout(() => {
            if (exportBtn) {
                exportBtn.disabled = false;
                exportBtn.innerHTML = `<i data-lucide="file-down" class="w-4 h-4"></i> Export Official PDF`;
                if (typeof lucide !== 'undefined' && lucide.createIcons) {
                    lucide.createIcons();
                }
            }
        }, 500);
    };

    window.showSectionRoster = function(sectionId) {
        window.currentRosterSectionId = sectionId;

        const dataNode = document.getElementById('sections-roster-data');
        if (!dataNode) return;

        let sectionsList = [];
        try {
            sectionsList = JSON.parse(dataNode.textContent);
        } catch (e) {
            console.error("Failed to parse sections roster JSON", e);
            return;
        }

        const sec = sectionsList.find(s => s.id == sectionId);
        if (!sec) return;

        // Populate text elements
        document.getElementById('modalGradeLevel').textContent = sec.grade_level;
        document.getElementById('modalAdvisoryName').textContent = `Advisory: ${sec.official_name || sec.name || 'General'}`;
        document.getElementById('modalOccupiedSeats').textContent = `${sec.occupied} / ${sec.capacity_limit}`;
        document.getElementById('modalRemainingSeats').textContent = `${sec.remaining} open`;
        document.getElementById('modalFillRate').textContent = `${sec.fill_rate}%`;
        
        // Format gender
        let genderText = 'Co-Ed';
        if (sec.gender === 'male') genderText = 'Boys Only';
        else if (sec.gender === 'female') genderText = 'Girls Only';
        document.getElementById('modalGender').textContent = genderText;

        // Format shift
        document.getElementById('modalShift').textContent = sec.shift || sec.learning_mode || 'F2F Column';

        // Format roster count
        document.getElementById('modalRosterCount').textContent = `${sec.occupied} ${sec.occupied == 1 ? 'Student' : 'Students'}`;

        // Populate students list roster
        const listContainer = document.getElementById('modalRosterList');
        listContainer.innerHTML = '';

        if (sec.students && sec.students.length > 0) {
            sec.students.forEach((stuSec, idx) => {
                const student = stuSec.student;
                const applicant = student?.applicant;
                if (!student || !applicant) return;

                const fullName = `${applicant.first_name} ${applicant.last_name}`;
                const studentNumber = student.student_number || 'N/A';
                const email = student.school_email || 'N/A';

                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3 rounded-2xl bg-white border border-slate-100 shadow-2xs hover:bg-slate-50/50 transition';
                item.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-xs text-slate-650 border border-slate-200/50">
                            ${idx + 1}
                        </div>
                        <div>
                            <h5 class="text-xs font-extrabold text-slate-900">${fullName}</h5>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-wider">${studentNumber}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-semibold text-slate-500">${email}</span>
                    </div>
                `;
                listContainer.appendChild(item);
            });
        } else {
            const empty = document.createElement('div');
            empty.className = 'py-8 text-center text-xs text-slate-400 font-medium';
            empty.textContent = 'No students currently enrolled in this section.';
            listContainer.appendChild(empty);
        }

        // Open Modal
        const modal = document.getElementById('advisoryRosterModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Trigger Lucide icons inside the modal
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    };

    window.closeAdvisoryModal = function() {
        const modal = document.getElementById('advisoryRosterModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    // Close on ESC or click outside
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAdvisoryModal();
    });

    document.addEventListener('DOMContentLoaded', () => {
        // Setup outside click listener
        const modalEl = document.getElementById('advisoryRosterModal');
        if (modalEl) {
            modalEl.addEventListener('click', (e) => {
                if (e.target.id === 'advisoryRosterModal') closeAdvisoryModal();
            });
        }
        const chartsDataNode = document.getElementById('students-dashboard-chart-data');
        if (!chartsDataNode) return;

        let chartsData = null;
        try {
            chartsData = JSON.parse(chartsDataNode.textContent);
        } catch (e) {
            console.error("Failed to parse students dashboard chart JSON data", e);
            return;
        }

        const chartTheme = {
            blue: '#2563eb',
            green: '#059669',
            amber: '#d97706',
            emerald: '#10b981',
            slate: '#64748b',
            grid: '#eef2f7'
        };

        const baseChart = {
            chart: {
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                toolbar: { show: false },
                animations: { enabled: true, speed: 600 },
                foreColor: chartTheme.slate,
            },
            grid: {
                borderColor: chartTheme.grid,
                strokeDashArray: 4,
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                fontWeight: 600,
                labels: { colors: chartTheme.slate },
            },
            tooltip: {
                theme: 'light',
            },
        };

        // Render Gender Chart
        const genderEl = document.querySelector('#studentGenderChart');
        if (genderEl && chartsData.gender?.data?.length) {
            new ApexCharts(genderEl, {
                ...baseChart,
                chart: { ...baseChart.chart, type: 'donut', height: 280 },
                series: chartsData.gender.data,
                labels: chartsData.gender.labels,
                colors: [chartTheme.blue, chartTheme.green],
                stroke: { width: 0 },
                plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Genders' } } } } },
            }).render();
        }

        // Render Learning Mode Chart
        const modeEl = document.querySelector('#studentLearningModeChart');
        if (modeEl && chartsData.mode?.data?.length) {
            new ApexCharts(modeEl, {
                ...baseChart,
                chart: { ...baseChart.chart, type: 'donut', height: 280 },
                series: chartsData.mode.data,
                labels: chartsData.mode.labels,
                colors: [chartTheme.amber, chartTheme.emerald],
                stroke: { width: 0 },
                plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Students' } } } } },
            }).render();
        }

        // Render Grade Level Distribution Chart
        const gradeEl = document.querySelector('#studentGradeDistributionChart');
        if (gradeEl && chartsData.gradeDistribution?.data?.length) {
            new ApexCharts(gradeEl, {
                ...baseChart,
                chart: { ...baseChart.chart, type: 'bar', height: 280 },
                series: [{ name: 'Students', data: chartsData.gradeDistribution.data }],
                xaxis: { categories: chartsData.gradeDistribution.labels },
                colors: [chartTheme.emerald],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
            }).render();
        }
    });
</script>
