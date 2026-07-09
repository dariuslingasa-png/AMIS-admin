<x-admin-layout title="School Years">
    <x-academic.page-header title="School Years">
        Manage academic school years and set active terms.
        <x-slot name="actions">
            <a href="{{ route('admin.academic.school-years.create') }}" class="btn-primary">Add School Year</a>
        </x-slot>
    </x-academic.page-header>

    <x-academic.academic-card>
        <div class="premium-table-wrap">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schoolYears as $sy)
                        <tr>
                            <td><strong>{{ $sy->code }}</strong></td>
                            <td>{{ $sy->name }}</td>
                            <td>
                                <x-academic.status-badge :status="$sy->status === 'active'" />
                            </td>
                            <td>
                                @if($sy->is_active)
                                    <span class="badge badge-success">Active SY</span>
                                @else
                                    <span class="text-slate-450 text-xs">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.academic.school-years.edit', $sy) }}" class="btn-secondary py-1 px-3 text-xs">Edit</a>
                                    @if(!$sy->is_active)
                                        <form method="POST" action="{{ route('admin.academic.school-years.toggle-active', $sy) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-primary py-1 px-3 text-xs">Set Active</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.academic.school-years.toggle-status', $sy) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn-secondary py-1 px-3 text-xs text-rose-600 border-rose-250">Toggle Status</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-400">No school years defined yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-academic.academic-card>
</x-admin-layout>
