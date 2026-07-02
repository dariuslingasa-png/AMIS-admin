<div class="space-y-4">
    <label class="flex flex-col gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Class Section *</span>
        <select name="section_id" x-model="editForm.section_id" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
            @foreach($sections as $section)
                <option value="{{ $section->id }}">
                    {{ $section->grade_level }} - {{ $section->official_name ?: ($section->name ?: 'General') }} ({{ $section->formatted_learning_mode }} - {{ ucfirst($section->gender === 'male' ? 'Boys' : ($section->gender === 'female' ? 'Girls' : 'Merge')) }})
                </option>
            @endforeach
        </select>
    </label>

    <label class="flex flex-col gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Name *</span>
        <input type="text" name="subject_name" x-model="editForm.subject_name" placeholder="e.g. Science" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
    </label>

    <label class="flex flex-col gap-1">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Teacher</span>
        <input type="text" name="teacher_name" x-model="editForm.teacher_name" list="schedule-teachers" placeholder="Teacher name" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Day *</span>
            <select name="day" x-model="editForm.day" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                @foreach($days as $day)
                    <option value="{{ $day }}">{{ $day }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Start *</span>
            <input type="time" name="start_time" x-model="editForm.start_time" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">End *</span>
            <input type="time" name="end_time" x-model="editForm.end_time" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
        </label>
    </div>
</div>
