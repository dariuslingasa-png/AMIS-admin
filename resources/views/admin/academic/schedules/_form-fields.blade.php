<div class="grid grid-cols-1 md:grid-cols-5 gap-6">
    <!-- Left Column: Large Timetable Preview Grid (3 cols) -->
    <div class="md:col-span-3 space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 flex flex-col justify-between">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">Timetable Preview</span>
            
            <!-- When there are intervals to show -->
            <div x-show="getPreviewIntervals().length > 0" x-transition class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-3xs">
                <table class="w-full text-[9px] border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200">
                            <th class="p-2 border-r border-slate-200 font-extrabold text-slate-500 w-24 text-center">Time</th>
                            <template x-for="d in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']">
                                <th class="p-2 border-r border-slate-200 font-black text-slate-700 text-center" x-text="d.substring(0,3)"></th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="interval in getPreviewIntervals()">
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="p-2 border-r border-slate-200 font-extrabold text-slate-500 text-center text-[8px]" x-text="interval.label"></td>
                                <template x-for="d in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']">
                                    <td class="p-2 border-r border-slate-100 last:border-0 text-center relative group min-h-[30px] cursor-pointer transition"
                                        :class="getClassCellBg(d, interval)"
                                        @click="clickPreviewCell(d, interval)">
                                        
                                        <!-- Show class name or draft -->
                                        <span class="block truncate max-w-[55px] mx-auto text-[8.5px] font-bold"
                                              :title="isDraftCell(d, interval) ? 'Draft selection' : getClassCellTitle(d, interval)"
                                              x-text="isDraftCell(d, interval) ? (editForm.subject_name || 'Draft') : getClassCellSubject(d, interval)"></span>
                                              
                                        <!-- Plus icon on hover -->
                                        <template x-if="!hasClassCell(d, interval) && !isDraftCell(d, interval)">
                                            <span class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 text-indigo-600 font-black text-[11px] bg-indigo-50/50">+</span>
                                        </template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty Placeholder (when times are blank) -->
            <div x-show="getPreviewIntervals().length === 0" x-transition class="border border-dashed border-slate-200 rounded-xl bg-white p-12 text-center flex flex-col items-center justify-center min-h-[250px]">
                <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-350 mb-3 border border-slate-100">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <span class="text-[11px] font-extrabold text-slate-700 uppercase block mb-1">Enter Schedule Times</span>
                <p class="text-[9.5px] text-slate-400 max-w-[200px] leading-normal">Type a **Start** and **End** time on the right to preview your schedule block here.</p>
            </div>
        </div>

        <div class="text-[9px] text-slate-400 font-light leading-normal border-t border-slate-200/50 pt-2" x-show="getPreviewIntervals().length > 0">
            * Click any empty cell in the preview to select or deselect that day.
        </div>
    </div>

    <!-- Right Column: Weekly Day Selector & Form Fields (2 cols) -->
    <div class="md:col-span-2 space-y-4">
        <!-- Hidden input for day/spans_all_days values -->
        <input type="hidden" name="day" :value="getSelectedDaysString()">
        <input type="hidden" name="spans_all_days" :value="editForm.spans_all_days ? 1 : 0">

        <!-- Weekly Days Selector on the right -->
        <div class="space-y-2 bg-slate-50/50 p-3 rounded-xl border border-slate-150">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Weekly Calendar</span>
                <button type="button" 
                    @click="editForm.spans_all_days = !editForm.spans_all_days"
                    :class="editForm.spans_all_days ? 'bg-emerald-600 text-white font-extrabold shadow-3xs' : 'bg-slate-200 text-slate-700 font-bold hover:bg-slate-300'"
                    class="text-[9px] uppercase px-2 py-0.5 rounded-md transition flex items-center gap-1 cursor-pointer">
                    <i data-lucide="calendar-check" class="w-3 h-3"></i>
                    Daily
                </button>
            </div>
            
            <!-- Horizontal Day Selector Buttons -->
            <div class="grid grid-cols-5 gap-1" x-show="!editForm.spans_all_days" x-transition>
                @foreach($days as $d)
                    <button type="button" 
                        @click="toggleDaySelection('{{ $d }}')"
                        :class="isDaySelected('{{ $d }}') ? 'bg-indigo-700 text-white border-indigo-700 font-black' : 'bg-white text-slate-655 border-slate-200 hover:bg-slate-100 font-bold'"
                        class="text-[10px] text-center py-2.5 rounded-lg border transition cursor-pointer shadow-3xs"
                        title="{{ $d }}">
                        {{ substr($d, 0, 3) }}
                    </button>
                @endforeach
            </div>

            <!-- Daily Active Status banner -->
            <div class="p-2 bg-emerald-50 border border-emerald-100 rounded-lg text-center" x-show="editForm.spans_all_days" x-transition>
                <span class="text-[9px] font-bold text-emerald-800 uppercase block">Daily (All Days Selected)</span>
            </div>
        </div>

        <!-- Form fields on the right -->
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
