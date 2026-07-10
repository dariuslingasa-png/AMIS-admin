<div class="grid grid-cols-1 md:grid-cols-5 gap-6">
    <!-- Left Column: Large Timetable Preview Grid (3 cols) -->
    <div class="md:col-span-3 space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 flex flex-col justify-between">
        <div>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">Timetable Preview</span>
            
            <!-- Table is always visible, only body rows change dynamically -->
            <div class="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-3xs">
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
                        <!-- Loop intervals if populated -->
                        <template x-for="interval in getPreviewIntervals()">
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="p-2 border-r border-slate-200 font-extrabold text-slate-500 text-center text-[8px]" x-text="interval.label"></td>
                                <template x-for="d in ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']">
                                    <td x-show="getPreviewCellColspan(d, interval) > 0"
                                        :colspan="getPreviewCellColspan(d, interval)"
                                        class="p-2 border-r border-slate-100 last:border-0 text-center relative group min-h-[30px] cursor-pointer transition"
                                        :class="getClassCellBg(d, interval)"
                                        @click="clickPreviewCell(d, interval)">
                                        
                                        <!-- Show class name or draft -->
                                        <span class="block truncate max-w-[150px] mx-auto text-[8.5px] font-bold"
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

                        <!-- Empty state placeholder inside table body if no intervals -->
                        <tr x-show="getPreviewIntervals().length === 0">
                            <td colspan="6" class="p-10 text-center text-slate-400 bg-white">
                                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-350 mx-auto mb-3 border border-slate-100">
                                    <i data-lucide="clock" class="w-4.5 h-4.5"></i>
                                </div>
                                <span class="text-[10px] font-extrabold text-slate-700 uppercase block mb-1"
                                      x-text="getSectionSchedules().length > 0 ? 'Schedule Loaded' : 'No Schedules Yet'"></span>
                                <p class="text-[9px] text-slate-400 max-w-[200px] mx-auto leading-normal"
                                   x-text="getSectionSchedules().length > 0 ? 'Set a Start & End time on the right to preview your new slot alongside existing classes.' : 'Set a Start & End time on the right to preview your schedule block.'"></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
            <select name="section_id" x-model="editForm.section_id" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none truncate max-w-full">
                @foreach($sections as $section)
                    @php
                        $shortMode = str_contains(strtolower($section->learning_mode), 'online') ? 'Online' : 'F2F';
                        $genderName = ucfirst($section->gender === 'male' ? 'Boys' : ($section->gender === 'female' ? 'Girls' : 'Merge'));
                    @endphp
                    <option value="{{ $section->id }}">
                        {{ $section->grade_level }} - {{ $section->official_name ?: ($section->name ?: 'General') }} ({{ $shortMode }} · {{ $genderName }})
                    </option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Subject Name *</span>
            <input type="text" name="subject_name" x-model="editForm.subject_name" placeholder="e.g. Science" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
        </label>

        <div class="flex flex-col gap-1 relative" @click.away="teacherDropdownOpen = false">
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Teacher</span>
            <input type="hidden" name="teacher_name" :value="editForm.teacher_name">
            
            <!-- Dropdown Trigger Button -->
            <button type="button" 
                @click="teacherDropdownOpen = !teacherDropdownOpen; teacherSearch = ''" 
                class="w-full bg-slate-50 border border-gray-300 hover:border-gray-400 text-gray-950 text-sm rounded-xl px-3 py-2 flex items-center justify-between outline-none transition duration-150 cursor-pointer h-10 select-none">
                
                <div class="flex items-center gap-2 min-w-0">
                    <!-- Selected Teacher Profile Pic -->
                    <template x-if="getSelectedTeacher()">
                        <div class="flex items-center gap-2 min-w-0">
                            <template x-if="getSelectedTeacher().photo_url">
                                <img :src="getSelectedTeacher().photo_url" class="rounded-full object-cover shrink-0 border border-slate-200" style="width: 26px; height: 26px;">
                            </template>
                            <template x-if="!getSelectedTeacher().photo_url">
                                <div class="rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-[9px] font-black text-indigo-700 shrink-0 uppercase" style="width: 26px; height: 26px;" x-text="getInitials(getSelectedTeacher().name)"></div>
                            </template>
                            <span class="font-bold truncate text-slate-900" x-text="getSelectedTeacher().short_name"></span>
                        </div>
                    </template>
                    <template x-if="!getSelectedTeacher()">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 shrink-0" style="width: 26px; height: 26px;">
                                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                            </div>
                            <span class="font-bold text-slate-400">Teacher pending</span>
                        </div>
                    </template>
                </div>
                
                <!-- Chevron Icon -->
                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-150" :class="teacherDropdownOpen ? 'rotate-180' : ''"></i>
            </button>
            
            <!-- Dropdown Content Card -->
            <div x-show="teacherDropdownOpen" 
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl z-50 flex flex-col max-h-72 overflow-hidden" 
                x-cloak>
                
                <!-- Search Input Header -->
                <div class="p-2 border-b border-slate-150 bg-slate-50/50">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="search" 
                            x-model="teacherSearch" 
                            placeholder="Search teacher..." 
                            class="w-full bg-white border border-slate-200 text-xs rounded-lg pl-8.5 pr-3 py-1.5 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 transition">
                    </div>
                </div>
                
                <!-- Options Roster List -->
                <div class="flex-1 overflow-y-auto py-1 divide-y divide-slate-100/50">
                    <!-- Default Pending Option -->
                    <button type="button" 
                        @click="editForm.teacher_name = ''; teacherDropdownOpen = false" 
                        class="w-full px-3 py-2 flex items-center gap-2 hover:bg-slate-50 text-left transition select-none cursor-pointer">
                        <div class="rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 shrink-0" style="width: 26px; height: 26px;">
                            <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="text-xs font-bold text-slate-400">Teacher pending</span>
                    </button>
                    
                    <!-- Loop Mapped Teachers -->
                    <template x-for="t in teachers.filter(t => !teacherSearch || t.name.toLowerCase().includes(teacherSearch.toLowerCase()) || t.short_name.toLowerCase().includes(teacherSearch.toLowerCase()))" :key="t.id">
                        <button type="button" 
                            @click="editForm.teacher_name = t.name; teacherDropdownOpen = false" 
                            class="w-full px-3 py-2 flex items-center gap-2.5 hover:bg-indigo-50/50 text-left transition select-none cursor-pointer group"
                            :class="editForm.teacher_name === t.name ? 'bg-indigo-50/30' : ''">
                            
                            <!-- Profile image / initials -->
                            <template x-if="t.photo_url">
                                <img :src="t.photo_url" class="rounded-full object-cover shrink-0 border border-slate-200 shadow-3xs" style="width: 28px; height: 28px;">
                            </template>
                            <template x-if="!t.photo_url">
                                <div class="rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-[10px] font-black text-indigo-700 shrink-0 uppercase shadow-3xs" style="width: 28px; height: 28px;" x-text="getInitials(t.name)"></div>
                            </template>
                            
                            <div class="min-w-0">
                                <span class="block text-xs font-black text-slate-900 group-hover:text-indigo-900" x-text="t.short_name"></span>
                                <span class="block text-[9px] font-bold text-slate-400 mt-0.5 uppercase tracking-wide truncate" x-text="t.name"></span>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

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
