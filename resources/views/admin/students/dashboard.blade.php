<x-admin-layout
    title="Students Dashboard"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Dashboard', 'href' => null],
    ]"
>
    <!-- ApexCharts Library directly -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script type="application/json" id="students-dashboard-chart-data">
        @json($studentsCharts ?? [])
    </script>

    <div class="space-y-6">
        <!-- Dashboard Header / Banner -->
        @include('admin.students.partials.dashboard.header')

        <!-- Telemetry Statistics Grid -->
        @include('admin.students.partials.dashboard.telemetry')

        <!-- ApexCharts Analytics Section -->
        @include('admin.students.partials.dashboard.charts')

        <!-- Section Classroom Capacity list -->
        @include('admin.students.partials.dashboard.sections')
    </div>

    <!-- Beautiful Interactive Advisory & Roster Modal -->
    @include('admin.students.partials.dashboard.modal')

    <!-- Serialized Roster & Sections Data & Scripts -->
    @include('admin.students.partials.dashboard.scripts')
</x-admin-layout>
