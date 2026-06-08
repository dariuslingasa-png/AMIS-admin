@php
    $editing = filled($subject);
@endphp

<form method="POST" action="{{ $editing ? route('admin.academic.subjects.update', $subject) : route('admin.academic.subjects.store') }}" class="grid gap-4">
    @csrf
    @if($editing)
        @method('PATCH')
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <label class="grid gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Name</span>
            <input name="name" value="{{ old('name', $subject->name ?? '') }}" required class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
        </label>
        <label class="grid gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Code</span>
            <input name="code" value="{{ old('code', $subject->code ?? '') }}" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
        </label>
    </div>

    <label class="grid gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Description</span>
        <textarea name="description" rows="3" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">{{ old('description', $subject->description ?? '') }}</textarea>
    </label>

    <div class="grid gap-4 md:grid-cols-3">
        <label class="grid gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Grade Level</span>
            <select name="grade_level" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                @foreach($grades as $grade)
                    <option value="{{ $grade }}" @selected(old('grade_level', $subject->grade_level ?? 'Grade 1') === $grade)>{{ $grade }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">School Year</span>
            <input name="school_year" value="{{ old('school_year', $subject->school_year ?? config('services.school.year')) }}" required class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
        </label>
        <label class="grid gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Status</span>
            <select name="status" class="rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                <option value="active" @selected(old('status', $subject->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $subject->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
        </label>
    </div>

    <div class="flex justify-end gap-2">
        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2 text-xs font-black text-white hover:bg-emerald-600">
            <i data-lucide="save" class="h-4 w-4"></i>
            {{ $editing ? 'Save Changes' : 'Save Subject' }}
        </button>
    </div>
</form>
