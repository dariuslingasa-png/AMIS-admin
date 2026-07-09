<x-admin-layout title="Edit Grade Level">
    <x-academic.page-header title="Edit Grade Level">
        Modify grade level properties.
    </x-academic.page-header>

    <x-academic.academic-card>
        <form method="POST" action="{{ route('admin.academic.grade-levels.update', $gradeLevel) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Grade Level Name (e.g. Grade 1)</label>
                <input type="text" name="name" value="{{ old('name', $gradeLevel->name) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $gradeLevel->sort_order) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Class Capacity</label>
                <input type="number" name="capacity" value="{{ old('capacity', $gradeLevel->capacity) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">School Year</label>
                <input type="text" name="school_year" value="{{ old('school_year', $gradeLevel->school_year) }}" required class="form-input">
            </div>
            <x-academic.form-actions :cancelRoute="route('admin.academic.grade-levels.index')" />
        </form>
    </x-academic.academic-card>
</x-admin-layout>
