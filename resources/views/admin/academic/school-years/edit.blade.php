<x-admin-layout title="Edit School Year">
    <x-academic.page-header title="Edit School Year">
        Modify properties of the school year period.
    </x-academic.page-header>

    <x-academic.academic-card>
        <form method="POST" action="{{ route('admin.academic.school-years.update', $schoolYear) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">School Year Code (e.g. 2026-2027)</label>
                <input type="text" name="code" value="{{ old('code', $schoolYear->code) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Display Name</label>
                <input type="text" name="name" value="{{ old('name', $schoolYear->name) }}" required class="form-input">
            </div>
            <x-academic.form-actions :cancelRoute="route('admin.academic.school-years.index')" />
        </form>
    </x-academic.academic-card>
</x-admin-layout>
