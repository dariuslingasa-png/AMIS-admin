<div class="grid grid-cols-1 md:grid-cols-5 gap-6">
    <!-- Left Column: Weekly Calendar selector (2 cols) -->
    <div class="md:col-span-2 space-y-4 bg-slate-50 p-4 rounded-2xl border border-slate-100 flex flex-col justify-between">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Weekly Calendar</span>
                <button type="button" 
                    @click="editForm.spans_all_days = !editForm.spans_all_days"
                    :class="editForm.spans_all_days ? 'bg-emerald-600 text-white font-extrabold shadow-3xs' : 'bg-slate-200 text-slate-700 font-bold hover:bg-slate-300'"
                    class="text-[9px] uppercase px-2 py-1 rounded-md transition flex items-center gap-1 cursor-pointer">
                    <i data-lucide="calendar-check" class="w-3 h-3"></i>
                    Daily
                </button>
            </div>
            
            <!-- Day Selection Buttons -->
            <div class="space-y-1.5" x-show="!editForm.spans_all_days" x-transition>
                @foreach($days as $d)
                    <button type="button" 
                        @click="toggleDaySelection('{{ $d }}')"
                        :class="isDaySelected('{{ $d }}') ? 'bg-indigo-700 text-white border-indigo-700 font-extrabold' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100 font-bold'"
                        class="w-full text-left px-3 py-2 text-xs rounded-xl border transition flex items-center justify-between cursor-pointer shadow-3xs">
                        <span>{{ $d }}</span>
                        <div class="w-3.5 h-3.5 rounded-full flex items-center justify-center border" :class="isDaySelected('{{ $d }}') ? 'border-white bg-indigo-900 text-white' : 'border-slate-300 bg-slate-50'">
                            <span class="text-[8px] font-black" x-show="isDaySelected('{{ $d }}')">✓</span>
                        </div>
                    </button>
                @endforeach
            </div>

            <!-- If Daily is active -->
            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-center space-y-2" x-show="editForm.spans_all_days" x-transition>
                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-600 mx-auto"></i>
                <div class="text-[11px] font-extrabold text-emerald-800 uppercase">Daily Class Active</div>
                <div class="text-[9px] text-emerald-600 leading-normal">This class will automatically span all days (Sunday to Thursday).</div>
            </div>

            <!-- Calendar Preview Grid (compact) -->
            <div class="mt-4 border-t border-slate-200/50 pt-3">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">Timetable Preview</span>
                <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-3xs">
                    <table class="w-full text-[8px] border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200">
                                <th class="p-1 border-r border-slate-200 font-extrabold text-slate-500 w-10 text-center">Time</th>
                                <template x-for="d in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']">
                                    <th class="p-1 border-r border-slate-200 font-black text-slate-700 text-center" x-text="d.substring(0,3)"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="hour in [8, 9, 10, 11, 12, 13, 14, 15, 16]">
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="p-1 border-r border-slate-200 font-bold text-slate-400 text-center" x-text="formatHourLabel(hour)"></td>
                                    <template x-for="d in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']">
                                        <td class="p-1 border-r border-slate-100 last:border-0 text-center relative group min-h-[22px] cursor-pointer transition"
                                            :class="getClassCellBg(d, hour)"
                                            @click="clickPreviewCell(d, hour)">
                                            
                                            <!-- Show class name -->
                                            <span class="block truncate max-w-[42px] mx-auto text-[7.5px]"
                                                  :title="getClassCellTitle(d, hour)"
                                                  x-text="getClassCellSubject(d, hour)"></span>
                                                  
                                            <!-- Plus icon on hover -->
                                            <template x-if="!hasClassCell(d, hour)">
                                                <span class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 text-indigo-600 font-black text-[9px] bg-indigo-50/50">+</span>
                                            </template>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-[9px] text-slate-400 font-light leading-normal border-t border-slate-200/50 pt-2 mt-2">
            * Click any empty slot in the preview to select that day and time.
        </div>
    </div>

    <!-- Right Column: Form Inputs (3 cols) -->
    <div class="md:col-span-3 space-y-4">
        <!-- Hidden input for day/spans_all_days values -->
        <input type="hidden" name="day" :value="getSelectedDaysString()">
        <input type="hidden" name="spans_all_days" :value="editForm.spans_all_days ? 1 : 0">

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

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
</div>
