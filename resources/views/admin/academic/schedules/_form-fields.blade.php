@php($isEdit = $prefix === 'edit')

<div class="space-y-4">
    <label class="flex flex-col gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Class Section *</span>
        <select name="section_id" @if($isEdit) x-model="editForm.section_id" @else x-model="activeSectionId" @endif class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
            @foreach($sections as $section)
                <option value="{{ $section->id }}" @selected((int) old('section_id', $activeSectionId ?? 0) === $section->id)>
                    {{ $section->grade_level }} - {{ $section->official_name ?: ($section->name ?: 'General') }} ({{ $section->formatted_learning_mode }} - {{ ucfirst($section->gender === 'male' ? 'Boys' : 'Girls') }})
                </option>
            @endforeach
        </select>
    </label>

    <label class="flex flex-col gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Name *</span>
        <input type="text" name="subject_name" value="{{ old('subject_name') }}" @if($isEdit) x-model="editForm.subject_name" @endif placeholder="e.g. Science" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
    </label>

    <label class="flex flex-col gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Teacher</span>
        <input type="text" name="teacher_name" value="{{ old('teacher_name') }}" @if($isEdit) x-model="editForm.teacher_name" @endif list="schedule-teachers" placeholder="Teacher name" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Day *</span>
            <select name="day" @if($isEdit) x-model="editForm.day" @endif class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                @foreach($days as $day)
                    <option value="{{ $day }}" @selected(old('day', 'Sunday') === $day)>{{ $day }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Start *</span>
            <input type="time" name="start_time" value="{{ old('start_time', '08:00') }}" @if($isEdit) x-model="editForm.start_time" @endif class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">End *</span>
            <input type="time" name="end_time" value="{{ old('end_time', '09:00') }}" @if($isEdit) x-model="editForm.end_time" @endif class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
        </label>
    </div>
</div>
