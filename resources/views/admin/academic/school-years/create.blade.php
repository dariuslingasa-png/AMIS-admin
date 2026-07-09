<x-admin-layout title="Create School Year">
    <x-academic.page-header title="Create School Year">
        Add a new academic school year term.
    </x-academic.page-header>

    <x-academic.academic-card>
        <form method="POST" action="{{ route('admin.academic.school-years.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">School Year Code (e.g. 2026-2027)</label>
                <input type="text" name="code" value="{{ old('code') }}" required class="form-input" placeholder="2026-2027">
            </div>
            <div>
                <label class="form-label">Display Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="School Year 2026-2027">
            </div>
            <div>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1">
                    <span class="text-sm font-semibold text-slate-700">Set as active school year immediately</span>
                </label>
            </div>
            <x-academic.form-actions :cancelRoute="route('admin.academic.school-years.index')" />
        </form>
    </x-academic.academic-card>
</x-admin-layout>
