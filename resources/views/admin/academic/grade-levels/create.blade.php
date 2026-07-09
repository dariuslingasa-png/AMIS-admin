<x-admin-layout title="Create Grade Level">
    <x-academic.page-header title="Create Grade Level">
        Add a new school grade level entry.
    </x-academic.page-header>

    <x-academic.academic-card>
        <form method="POST" action="{{ route('admin.academic.grade-levels.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Grade Level Name (e.g. Grade 1)</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Grade 1">
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">Class Capacity</label>
                <input type="number" name="capacity" value="{{ old('capacity', 40) }}" required class="form-input">
            </div>
            <div>
                <label class="form-label">School Year</label>
                <input type="text" name="school_year" value="{{ old('school_year', '2026-2027') }}" required class="form-input">
            </div>
            <x-academic.form-actions :cancelRoute="route('admin.academic.grade-levels.index')" />
        </form>
    </x-academic.academic-card>
</x-admin-layout>
