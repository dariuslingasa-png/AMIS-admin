<x-admin-layout title="Academic Calendar">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        // Month navigation
        month: new Date().getMonth(),
        year: new Date().getFullYear(),
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        blankDays: [],
        daysInMonth: [],
        selectedDate: '', // Format 'YYYY-MM-DD'
        
        // Add Event Modal
        showAddModal: false,
        newEvent: {
            title: '',
            date: '',
            type: 'Academic',
            description: ''
        },
        
        // Events list
        events: [
            { title: 'First Day of Classes', date: '2026-06-15', type: 'Academic', desc: 'Opening ceremony and orientation for new students.' },
            { title: 'PTA General Assembly', date: '2026-06-18', type: 'Co-curricular', desc: 'General meeting for parents and teachers.' },
            { title: 'Qur\'an Memorization Competition', date: '2026-06-25', type: 'Co-curricular', desc: 'Annual school-wide ISAL Qur\'an recital.' },
            { title: 'Eid al-Adha Celebration', date: '2026-07-10', type: 'Holiday', desc: 'Public holiday observation.' },
            { title: 'Midterm Examination Week', date: '2026-07-15', type: 'Exam', desc: 'First semester midterm exam series.' },
            { title: 'Islamic Hijri New Year', date: '2026-08-05', type: 'Holiday', desc: 'Observation of Muharram 1st.' },
            { title: 'Buwan ng Wika Cultural Day', date: '2026-08-20', type: 'Co-curricular', desc: 'Filipino language and cultural presentations.' }
        ],
        
        init() {
            this.getCalendarDays();
            let today = new Date();
            this.selectedDate = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
        },
        
        getCalendarDays() {
            let firstDayOfMonth = new Date(this.year, this.month, 1).getDay();
            let blanks = [];
            for (let i = 0; i < firstDayOfMonth; i++) {
                blanks.push(i);
            }
            this.blankDays = blanks;
            
            let totalDays = new Date(this.year, this.month + 1, 0).getDate();
            let days = [];
            for (let i = 1; i <= totalDays; i++) {
                days.push(i);
            }
            this.daysInMonth = days;
        },
        
        prevMonth() {
            if (this.month === 0) {
                this.month = 11;
                this.year--;
            } else {
                this.month--;
            }
            this.getCalendarDays();
        },
        
        nextMonth() {
            if (this.month === 11) {
                this.month = 0;
                this.year++;
            } else {
                this.month++;
            }
            this.getCalendarDays();
        },
        
        isToday(day) {
            let today = new Date();
            return today.getDate() === day && today.getMonth() === this.month && today.getFullYear() === this.year;
        },
        
        isSelected(day) {
            let dateStr = this.year + '-' + String(this.month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            return this.selectedDate === dateStr;
        },
        
        selectDay(day) {
            this.selectedDate = this.year + '-' + String(this.month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        },
        
        getEventsForDay(day) {
            let dateStr = this.year + '-' + String(this.month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            return this.events.filter(e => e.date === dateStr);
        },
        
        getEventsForActiveMonth() {
            return this.events.filter(e => {
                let parts = e.date.split('-');
                return parseInt(parts[0]) === this.year && parseInt(parts[1]) === (this.month + 1);
            }).sort((a,b) => new Date(a.date) - new Date(b.date));
        },
        
        getFilteredEvents() {
            if (this.selectedDate) {
                let filtered = this.events.filter(e => e.date === this.selectedDate);
                if (filtered.length > 0) return filtered;
            }
            return this.getEventsForActiveMonth();
        },
        
        getEventBadgeColor(type) {
            switch(type) {
                case 'Holiday': return 'bg-rose-50 text-rose-700 border-rose-100/50';
                case 'Exam': return 'bg-amber-50 text-amber-700 border-amber-100/50';
                case 'Academic': return 'bg-blue-50 text-blue-700 border-blue-100/50';
                case 'Co-curricular': return 'bg-purple-50 text-purple-700 border-purple-100/50';
                default: return 'bg-slate-50 text-slate-700 border-slate-100';
            }
        },
        
        getEventBorderColor(type) {
            switch(type) {
                case 'Holiday': return 'border-l-rose-500';
                case 'Exam': return 'border-l-amber-500';
                case 'Academic': return 'border-l-blue-500';
                case 'Co-curricular': return 'border-l-purple-500';
                default: return 'border-l-slate-400';
            }
        },
        
        addEvent() {
            if (!this.newEvent.title || !this.newEvent.date) return;
            this.events.push({
                title: this.newEvent.title,
                date: this.newEvent.date,
                type: this.newEvent.type,
                desc: this.newEvent.description || 'No description provided.'
            });
            
            this.newEvent.title = '';
            this.newEvent.date = '';
            this.newEvent.type = 'Academic';
            this.newEvent.description = '';
            this.showAddModal = false;
            
            this.getCalendarDays();
        }
    }">
        <!-- Hero Banner -->
        <div class="academic-hero-banner relative overflow-hidden">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                    Academic Workspace
                </span>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Academic Calendar</h1>
                <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                    Track daily school events, grading windows, holiday schedules, and academic deadlines.
                </p>
            </div>
        </div>

        <!-- Two Column Interactive Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Side: Calendar Grid -->
            <div class="lg:col-span-8 bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <!-- Calendar Control Header -->
                <div class="bg-slate-50/50 border-b border-gray-150 px-6 py-5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h2 class="text-slate-900 font-extrabold text-sm tracking-wide uppercase" x-text="monthNames[month] + ' ' + year"></h2>
                    </div>
                    <div class="flex gap-1.5">
                        <button type="button" @click="prevMonth()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-655 hover:bg-slate-50 transition cursor-pointer">
                            <i data-lucide="chevron-left" class="h-4 w-4"></i>
                        </button>
                        <button type="button" @click="month = new Date().getMonth(); year = new Date().getFullYear(); getCalendarDays();" class="inline-flex h-8 px-3 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                            Today
                        </button>
                        <button type="button" @click="nextMonth()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-655 hover:bg-slate-50 transition cursor-pointer">
                            <i data-lucide="chevron-right" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Monthly Grid -->
                <div class="grid grid-cols-7 gap-1 p-4 bg-slate-50/45">
                    <!-- Weekday Labels -->
                    <template x-for="dayName in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']">
                        <div class="text-center font-extrabold text-[10px] text-slate-400 uppercase tracking-wider py-2" x-text="dayName"></div>
                    </template>
                    
                    <!-- Blank cells for padding -->
                    <template x-for="blank in blankDays">
                        <div class="aspect-square bg-slate-50/25"></div>
                    </template>
                    
                    <!-- Calendar days -->
                    <template x-for="day in daysInMonth">
                        <button type="button" 
                            @click="selectDay(day)"
                            :class="[
                                isSelected(day) ? 'bg-indigo-600 text-white shadow-xs font-black border-indigo-600' : 'bg-white hover:bg-slate-50 border-slate-150',
                                isToday(day) && !isSelected(day) ? 'text-indigo-600 font-black ring-2 ring-indigo-600/20' : ''
                            ]"
                            class="aspect-square border rounded-xl flex flex-col items-center justify-between p-2 cursor-pointer transition relative group">
                            <span class="text-xs font-extrabold" x-text="day"></span>
                            
                            <!-- Event indicator dots -->
                            <div class="flex gap-0.5 justify-center mt-1 w-full overflow-hidden">
                                <template x-for="event in getEventsForDay(day)">
                                    <span :class="[
                                        event.type === 'Holiday' ? 'bg-rose-500' : '',
                                        event.type === 'Exam' ? 'bg-amber-500' : '',
                                        event.type === 'Academic' ? 'bg-blue-500' : '',
                                        event.type === 'Co-curricular' ? 'bg-purple-500' : ''
                                    ]" class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                </template>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right Side: Agenda Agenda & Controls -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Active events Agenda panel -->
                <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-5">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase block">Agenda</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5" x-text="selectedDate ? 'Selected Day' : 'This Month'"></span>
                        </div>
                        <button type="button" @click="showAddModal = true" class="inline-flex h-7 px-3 items-center gap-1.5 bg-indigo-700 hover:bg-indigo-600 text-white font-extrabold text-[10px] rounded-lg transition shadow-3xs cursor-pointer uppercase tracking-wider">
                            <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                            Add Event
                        </button>
                    </div>
                    
                    <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                        <template x-for="event in getFilteredEvents()">
                            <div :class="getEventBorderColor(event.type)" class="group flex flex-col p-4 bg-white border border-gray-150 rounded-2xl hover:bg-slate-50/55 transition shadow-3xs border-l-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <span class="font-extrabold text-slate-900 text-xs block tracking-wide group-hover:text-indigo-850 transition-colors" x-text="event.title"></span>
                                        <span class="text-[9px] text-gray-400 font-semibold mt-1 block" x-text="event.date"></span>
                                    </div>
                                    <span :class="getEventBadgeColor(event.type)" class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-extrabold border uppercase tracking-wider" x-text="event.type"></span>
                                </div>
                                <p class="mt-2 text-[10px] text-slate-500 font-medium leading-relaxed" x-text="event.desc"></p>
                            </div>
                        </template>
                        
                        <template x-if="getFilteredEvents().length === 0">
                            <div class="py-8 text-center text-slate-400 flex flex-col items-center justify-center gap-2">
                                <i data-lucide="info" class="w-6 h-6 text-slate-350"></i>
                                <p class="font-semibold text-xs">No events scheduled for this view.</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Legend Card -->
                <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <span class="text-slate-900 font-extrabold text-xs tracking-wide uppercase">Category Legend</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-[10px] font-extrabold text-slate-700">
                        <div class="flex items-center gap-2 px-3 py-2 bg-blue-50/40 border border-blue-100/50 rounded-xl">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>Academic</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-rose-50/40 border border-rose-100/50 rounded-xl">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span>Holiday</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-amber-50/40 border border-amber-100/50 rounded-xl">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Exam</span>
                        </div>
                        <div class="flex items-center gap-2 px-3 py-2 bg-purple-50/40 border border-purple-100/50 rounded-xl">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                            <span>Co-curricular</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Event Modal Simulation -->
        <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs" x-show="showAddModal" x-cloak x-transition>
            <div class="admin-modal-card bg-white rounded-2xl shadow-xl w-full max-w-md p-6 flex flex-col gap-4 border border-slate-200" @click.away="showAddModal = false">
                <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <span class="admin-modal-title text-base font-extrabold text-slate-950">Add Calendar Event</span>
                        <div class="text-[11px] text-slate-400 font-light mt-0.5">Mock-register an academic event</div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold cursor-pointer" @click="showAddModal = false">&times;</button>
                </div>
                <div class="space-y-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Event Title *</label>
                        <input type="text" x-model="newEvent.title" placeholder="e.g. Eid al-Fitr Festival" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Event Date *</label>
                            <input type="date" x-model="newEvent.date" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Event Type *</label>
                            <select x-model="newEvent.type" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none">
                                <option value="Academic">Academic</option>
                                <option value="Holiday">Holiday</option>
                                <option value="Exam">Exam</option>
                                <option value="Co-curricular">Co-curricular</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Description</label>
                        <textarea x-model="newEvent.description" placeholder="Describe the event details..." rows="3" class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"></textarea>
                    </div>
                </div>
                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button type="button" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="showAddModal = false">Cancel</button>
                    <button type="button" class="px-5 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer" @click="addEvent">Save Event</button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
