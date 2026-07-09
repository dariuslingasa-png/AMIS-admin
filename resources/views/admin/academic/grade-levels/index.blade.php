<x-admin-layout title="Grade Levels">
    <x-academic.page-header title="Grade Levels">
        Manage grade levels, sort ordering, and class capacities.
        <x-slot name="actions">
            <a href="{{ route('admin.academic.grade-levels.create') }}" class="btn-primary">Add Grade Level</a>
        </x-slot>
    </x-academic.page-header>

    <x-academic.academic-card>
        <div class="premium-table-wrap">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Sort Order</th>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Enrolled Count</th>
                        <th>Status</th>
                        <th>School Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gradeLevels as $gl)
                        <tr>
                            <td>{{ $gl->sort_order }}</td>
                            <td><strong>{{ $gl->name }}</strong></td>
                            <td>{{ $gl->capacity }}</td>
                            <td>{{ $gl->enrolled_count }}</td>
                            <td>
                                <x-academic.status-badge :status="$gl->is_active" />
                            </td>
                            <td>{{ $gl->school_year }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.academic.grade-levels.edit', $gl) }}" class="btn-secondary py-1 px-3 text-xs">Edit</a>
                                    <form method="POST" action="{{ route('admin.academic.grade-levels.toggle-active', $gl) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-secondary py-1 px-3 text-xs">Toggle Status</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-400">No grade levels defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-academic.academic-card>
</x-admin-layout>
