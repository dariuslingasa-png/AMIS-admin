<style>
    .chart-grid-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
    }
    .chart-card-grade {
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .chart-card-gender, .chart-card-mode {
        width: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    @media (min-width: 1024px) {
        .chart-grid-container {
            flex-direction: row;
        }
        .chart-card-grade {
            width: 50% !important;
        }
        .chart-card-gender, .chart-card-mode {
            width: 25% !important;
        }
    }
    /* Custom Modern Scrollbar style for pipeline columns */
    .custom-scrollbar::-webkit-scrollbar {
        width: 5px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
        transition: background 0.2s ease;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<!-- ApexCharts Analytics Section -->
<div class="chart-grid-container">
    <!-- Grade level distribution -->
    <div class="chart-card-grade rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Grade Level Enrollment Distribution</h3>
                <p class="text-[11px] text-slate-400 font-medium">Students enrolled per grade level (K1 to Grade 12)</p>
            </div>
            <i data-lucide="bar-chart-3" class="h-5 w-5 text-slate-400"></i>
        </div>
        <div id="studentGradeDistributionChart" class="w-full"></div>
    </div>

    <!-- Gender Distribution -->
    <div class="chart-card-gender rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Gender Division</h3>
                <p class="text-[11px] text-slate-400 font-medium">Male vs Female students breakdown</p>
            </div>
            <i data-lucide="pie-chart" class="h-5 w-5 text-slate-400"></i>
        </div>
        <div id="studentGenderChart" class="w-full flex justify-center"></div>
    </div>

    <!-- Learning Mode distribution -->
    <div class="chart-card-mode rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Learning Mode Ratios</h3>
                <p class="text-[11px] text-slate-400 font-medium">F2F vs Flexible Online Learning</p>
            </div>
            <i data-lucide="donut" class="h-5 w-5 text-slate-400"></i>
        </div>
        <div id="studentLearningModeChart" class="w-full flex justify-center"></div>
    </div>
</div>
