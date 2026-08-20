@php
    $sectionDisplayName = $section->official_name ?: ($section->name ?: 'General Section');
    $enrolledCount = $section->students->count();
    $fillRate = $capacity > 0 ? min(100, round(($enrolledCount / $capacity) * 100)) : 0;

    // Helper function to build name with Middle Initial
    $buildStudentNameWithInitial = function($applicant, $studentNumber) {
        if (!$applicant) {
            return 'Student #' . $studentNumber;
        }
        $first = trim($applicant->first_name ?? '');
        $last = trim($applicant->last_name ?? '');
        $middle = trim($applicant->middle_name ?? '');
        $suffix = trim($applicant->suffix ?? '');
        $mInitial = ($init = \App\Models\EnrollmentApplicant::formatMiddleInitial($middle)) ? ' ' . $init : '';
        $sfx = $suffix ? ' ' . $suffix : '';
        return html_entity_decode(trim($first . $mInitial . ' ' . $last . $sfx), ENT_QUOTES, 'UTF-8');
    };

    // Group Enrolled Students into Boys, Girls, and Others
    $enrolledBoys = $section->students->filter(function($secSt) {
        $g = strtolower(trim($secSt->student?->applicant?->gender ?? ''));
        return str_contains($g, 'male') && !str_contains($g, 'female');
    });

    $enrolledGirls = $section->students->filter(function($secSt) {
        $g = strtolower(trim($secSt->student?->applicant?->gender ?? ''));
        return str_contains($g, 'female');
    });

    $enrolledUnspecified = $section->students->reject(function($secSt) use ($enrolledBoys, $enrolledGirls) {
        return $enrolledBoys->contains($secSt) || $enrolledGirls->contains($secSt);
    });

    // Group Available Students into Boys, Girls, and Others
    $availableBoys = $availableStudents->getCollection()->filter(function($st) {
        $g = strtolower(trim($st->applicant?->gender ?? ''));
        return str_contains($g, 'male') && !str_contains($g, 'female');
    });

    $availableGirls = $availableStudents->getCollection()->filter(function($st) {
        $g = strtolower(trim($st->applicant?->gender ?? ''));
        return str_contains($g, 'female');
    });

    $availableOthers = $availableStudents->getCollection()->reject(function($st) use ($availableBoys, $availableGirls) {
        return $availableBoys->contains($st) || $availableGirls->contains($st);
    });
@endphp

<x-admin-layout
    title="Manage Section - {{ $sectionDisplayName }}"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Class Occupancy', 'href' => route('admin.students.occupancy')],
        ['label' => $sectionDisplayName, 'href' => null],
    ]"
>
    <!-- Load SortableJS for Fluid Drag and Drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <div class="space-y-6" x-data="{ openEditModal: false, isHoveringRoster: false }">
        <!-- Top Header Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-widest text-slate-200">
                            {{ $section->grade_level }}
                        </span>
                        @if($section->learning_mode)
                            <span class="inline-flex rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-200">
                                {{ $section->learning_mode }}
                            </span>
                        @endif
                        @if($section->shift)
                            <span class="inline-flex rounded-full bg-teal-500/20 px-3 py-1 text-xs font-bold text-teal-200">
                                {{ $section->shift }}
                            </span>
                        @endif
                        @if($section->gender)
                            <span class="inline-flex rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-200 uppercase">
                                {{ $section->gender === 'female' ? 'Girls Only' : ($section->gender === 'male' ? 'Boys Only' : 'Co-Ed Merge') }}
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-3 text-3xl font-black tracking-tight">{{ $sectionDisplayName }}</h1>
                    <p class="mt-1 text-sm font-medium text-emerald-100 flex items-center gap-3">
                        <span>Enrolled: <strong class="text-white font-extrabold">{{ $enrolledCount }} / {{ $capacity }}</strong> Seats ({{ $fillRate }}%)</span>
                        <span>&bull; Boys: <strong>{{ $enrolledBoys->count() }}</strong></span>
                        <span>&bull; Girls: <strong>{{ $enrolledGirls->count() }}</strong></span>
                        @if($section->grade_advisor)
                            <span>&bull; Advisor: <strong class="text-white font-extrabold">{{ str_ireplace('TEACHER ', '', $section->grade_advisor->teacher_name ?? '') }}</strong></span>
                        @endif
                    </p>
                    <!-- Section Switcher Dropdown -->
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-xs font-bold text-emerald-200">Switch Section:</span>
                        <select onchange="if(this.value) window.location.href='/admin/students/occupancy/sections/'+this.value+'/manage'" class="h-8 px-3 rounded-xl bg-white/15 border border-white/25 text-xs font-extrabold text-white outline-none focus:ring-2 focus:ring-emerald-300">
                            @foreach($allSectionsGrouped as $gLevel => $secs)
                                <optgroup label="{{ $gLevel }}" class="text-slate-900 font-black">
                                    @foreach($secs as $s)
                                        <option value="{{ $s->id }}" class="text-slate-900 font-extrabold" @selected($s->id === $section->id)>
                                            {{ $s->grade_level }} - {{ $s->official_name ?: $s->name }} ({{ $s->occupied }} students)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="openEditModal = true" class="inline-flex items-center gap-1.5 rounded-2xl bg-amber-500 hover:bg-amber-600 px-4 py-2.5 text-xs font-black text-white shadow-md transition active:scale-95 cursor-pointer">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                        <span>Rename / Edit Section</span>
                    </button>
                    <a href="{{ route('admin.students.occupancy') }}" class="inline-flex items-center gap-1.5 rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-bold text-white hover:bg-white/20 transition active:scale-95">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Back to Occupancy</span>
                    </a>
                    <!-- Export ID Cards Dropdown -->
                    <div class="relative inline-block text-left" x-data="{ openIdDropdown: false }" @click.outside="openIdDropdown = false">
                        <button type="button" @click="openIdDropdown = !openIdDropdown" class="inline-flex items-center gap-1.5 rounded-2xl bg-purple-600 px-4 py-2.5 text-xs font-black text-white shadow-md hover:bg-purple-700 transition active:scale-95">
                            <i data-lucide="contact" class="w-4 h-4"></i>
                            <span>Export ID Cards Sheet</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                        </button>
                        <div x-cloak x-show="openIdDropdown" x-transition.origin.top.right 
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl z-50 text-left">
                            <a href="{{ route('admin.students.id-roster-print', $section) }}" target="_blank" 
                               class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700 transition">
                                <i data-lucide="shield-check" class="w-4 h-4 text-purple-600"></i>
                                <span>Official ID Cards</span>
                            </a>
                            <a href="{{ route('admin.students.id-roster-print', $section) }}?watermark=1" target="_blank" 
                               class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-800 transition">
                                <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                                <span>Watermark Copy</span>
                            </a>
                        </div>
                    </div>

                    <!-- Print Roster Dropdown -->
                    <div class="relative inline-block text-left" x-data="{ openPrintDropdown: false }" @click.outside="openPrintDropdown = false">
                        <button type="button" @click="openPrintDropdown = !openPrintDropdown" class="inline-flex items-center gap-1.5 rounded-2xl bg-white px-4 py-2.5 text-xs font-black text-emerald-900 shadow-md hover:bg-emerald-50 transition active:scale-95">
                            <i data-lucide="printer" class="w-4 h-4 text-emerald-600"></i>
                            <span>Print Roster PDF</span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                        </button>
                        <div x-cloak x-show="openPrintDropdown" x-transition.origin.top.right 
                             class="absolute right-0 mt-2 w-44 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl z-50 text-left">
                            <a href="{{ route('admin.students.roster-print', $section) }}" target="_blank" 
                               class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 transition">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
                                <span>Official Roster</span>
                            </a>
                            <a href="{{ route('admin.students.roster-print', $section) }}?watermark=1" target="_blank" 
                               class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 hover:bg-amber-50 hover:text-amber-800 transition">
                                <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                                <span>Watermark Copy</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Settings Bar -->
        <div class="rounded-2xl bg-white p-4 border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4 text-xs font-bold text-slate-700">
                <span class="text-slate-400 font-extrabold uppercase tracking-wider text-[10px]">Quick Settings:</span>

                <!-- Quick Learning Mode Dropdown -->
                <form method="POST" action="{{ route('admin.students.occupancy.update-section', $section) }}" class="flex items-center gap-1.5">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $section->name }}">
                    <input type="hidden" name="grade_level" value="{{ $section->grade_level }}">
                    <input type="hidden" name="shift" value="{{ $section->shift }}">
                    <input type="hidden" name="gender" value="{{ $section->gender }}">
                    <label class="text-slate-500 font-bold">Mode:</label>
                    <select name="learning_mode" onchange="this.form.submit()" class="h-8 px-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 cursor-pointer">
                        <option value="">None / Default</option>
                        <option value="Flexible Online Learning" @selected($section->learning_mode === 'Flexible Online Learning')>Flexible Online Learning</option>
                        <option value="Face-to-Face" @selected($section->learning_mode === 'Face-to-Face')>Face-to-Face</option>
                    </select>
                </form>

                <!-- Quick Shift Dropdown -->
                <form method="POST" action="{{ route('admin.students.occupancy.update-section', $section) }}" class="flex items-center gap-1.5">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $section->name }}">
                    <input type="hidden" name="grade_level" value="{{ $section->grade_level }}">
                    <input type="hidden" name="learning_mode" value="{{ $section->learning_mode }}">
                    <input type="hidden" name="gender" value="{{ $section->gender }}">
                    <label class="text-slate-500 font-bold">Shift:</label>
                    <select name="shift" onchange="this.form.submit()" class="h-8 px-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 cursor-pointer">
                        <option value="">None / No Shift</option>
                        <option value="1st Shift" @selected($section->shift === '1st Shift')>1st Shift</option>
                        <option value="2nd Shift" @selected($section->shift === '2nd Shift')>2nd Shift</option>
                    </select>
                </form>

                <!-- Quick Gender Dropdown -->
                <form method="POST" action="{{ route('admin.students.occupancy.update-section', $section) }}" class="flex items-center gap-1.5">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $section->name }}">
                    <input type="hidden" name="grade_level" value="{{ $section->grade_level }}">
                    <input type="hidden" name="learning_mode" value="{{ $section->learning_mode }}">
                    <input type="hidden" name="shift" value="{{ $section->shift }}">
                    <label class="text-slate-500 font-bold">Gender Allocation:</label>
                    <select name="gender" onchange="this.form.submit()" class="h-8 px-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-800 outline-none focus:border-emerald-500 cursor-pointer">
                        <option value="merge" @selected(($section->gender ?? 'merge') === 'merge')>Co-Ed (Merge)</option>
                        <option value="female" @selected($section->gender === 'female')>Girls Only</option>
                        <option value="male" @selected($section->gender === 'male')>Boys Only</option>
                    </select>
                </form>
            </div>

            <div class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200 flex items-center gap-1.5">
                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-emerald-600"></i>
                <span>Drag student cards onto Left Roster to assign!</span>
            </div>
        </div>

        <!-- Hidden Form for Drag & Drop Student Assignment -->
        <form x-ref="dragAssignForm" method="POST" action="{{ route('admin.students.occupancy.assign-students', $section) }}" class="hidden">
            @csrf
            <input type="hidden" name="student_ids[]" x-ref="dragStudentInput">
        </form>

        <!-- Edit Section Details Modal -->
        <template x-teleport="body">
            <div x-show="openEditModal"
                 style="display: none; z-index: 99999;"
                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col"
                     @click.outside="openEditModal = false">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="edit" class="w-5 h-5 text-emerald-600"></i>
                            <span>Rename & Edit Section</span>
                        </h3>
                        <button @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('admin.students.occupancy.update-section', $section) }}" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Section Name</label>
                            <input type="text" name="name" value="{{ $section->name }}" required placeholder="e.g. ALI IBN ABI TALIB"
                                   class="w-full h-11 px-3.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Grade Level</label>
                                <select name="grade_level" required class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                    @foreach(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g)
                                        <option value="{{ $g }}" @selected($section->grade_level === $g)>{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Learning Mode <span class="text-slate-400 font-normal">(optional)</span></label>
                                <select name="learning_mode" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                    <option value="">None / Default</option>
                                    <option value="Flexible Online Learning" @selected($section->learning_mode === 'Flexible Online Learning')>Flexible Online Learning</option>
                                    <option value="Face-to-Face" @selected($section->learning_mode === 'Face-to-Face')>Face-to-Face</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Shift <span class="text-slate-400 font-normal">(optional)</span></label>
                                <select name="shift" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                    <option value="">None / No Shift</option>
                                    <option value="1st Shift" @selected($section->shift === '1st Shift')>1st Shift</option>
                                    <option value="2nd Shift" @selected($section->shift === '2nd Shift')>2nd Shift</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Gender Allocation <span class="text-slate-400 font-normal">(optional)</span></label>
                                <select name="gender" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500">
                                    <option value="merge" @selected(($section->gender ?? 'merge') === 'merge')>Co-Ed (Merge)</option>
                                    <option value="female" @selected($section->gender === 'female')>Girls Only</option>
                                    <option value="male" @selected($section->gender === 'male')>Boys Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="openEditModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md cursor-pointer">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Main Workspace: 2 Columns -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- LEFT COLUMN: Current Enrolled Roster (Grouped by Gender) (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200"
                     @dragover.prevent="isHoveringRoster = true"
                     @dragleave="isHoveringRoster = false"
                     @drop.prevent="
                        isHoveringRoster = false;
                        const stId = $event.dataTransfer.getData('text/plain');
                        if (stId) {
                            $refs.dragStudentInput.value = stId;
                            $refs.dragAssignForm.submit();
                        }
                     "
                     :class="isHoveringRoster ? 'ring-4 ring-emerald-400/60 bg-emerald-50/50 border-emerald-400 scale-[1.01]' : ''">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                                <span>Current Roster</span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium">{{ $enrolledCount }} student(s) in section</p>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                            {{ $fillRate }}% Filled
                        </span>
                    </div>

                    @if($section->students->isEmpty())
                        <div class="py-12 text-center rounded-2xl border-2 border-dashed border-emerald-300 bg-emerald-50/20 p-6 transition">
                            <i data-lucide="download-cloud" class="w-8 h-8 text-emerald-600 mx-auto mb-2 animate-bounce"></i>
                            <p class="text-xs font-extrabold text-emerald-900">No students assigned yet.</p>
                            <p class="text-[11px] font-bold text-emerald-700 mt-1">Drag student cards from the right panel & drop them here!</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-1">
                            
                            <!-- BOYS SECTION -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-blue-50/80 rounded-xl border border-blue-200/80 text-blue-900">
                                    <span class="text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                                        <span>👦 BOYS</span>
                                    </span>
                                    <span class="text-[11px] font-bold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded-full">
                                        {{ $enrolledBoys->count() }} Student(s)
                                    </span>
                                </div>
                                <div id="roster-boys-sortable" class="space-y-1.5 min-h-[40px]">
                                    @forelse($enrolledBoys as $secStudent)
                                        @php
                                            $st = $secStudent->student;
                                            $app = $st?->applicant;
                                            $stName = $buildStudentNameWithInitial($app, $st->student_number);
                                            $stType = $app?->student_type ?: 'NEW';
                                        @endphp
                                        <div class="p-2.5 rounded-xl border border-slate-200/80 bg-white flex items-center justify-between gap-3 group hover:bg-slate-50 transition shadow-2xs">
                                            <div class="min-w-0 flex items-center gap-2">
                                                <i data-lucide="grip-vertical" class="drag-handle w-4 h-4 text-slate-300 group-hover:text-blue-600 shrink-0 cursor-grab active:cursor-grabbing"></i>
                                                <div class="min-w-0">
                                                    <a href="{{ route('admin.students.show', $st) }}" target="_blank" class="font-black text-xs text-slate-900 hover:text-blue-700 transition uppercase block truncate">
                                                        {{ $stName }}
                                                    </a>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400 font-bold mt-0.5">
                                                        <span>ID: {{ $st->student_number }}</span>
                                                        @if($app?->lrn)
                                                            <span>&bull; LRN: {{ $app->lrn }}</span>
                                                        @endif
                                                        <span class="text-blue-600 font-black">&bull; MALE</span>
                                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ strtolower($stType) === 'old' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $stType }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.students.occupancy.remove-student', $secStudent) }}" onsubmit="return confirm('Remove {{ addslashes($stName) }} from {{ addslashes($sectionDisplayName) }}?')" class="shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 px-2 py-1 rounded-lg transition cursor-pointer" title="Remove from section">
                                                    <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                                    <span>Remove</span>
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-[11px] font-semibold text-slate-400 italic px-2 py-1">No boys enrolled in this section.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- GIRLS SECTION -->
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between px-3 py-1.5 bg-pink-50/80 rounded-xl border border-pink-200/80 text-pink-900">
                                    <span class="text-xs font-black uppercase tracking-wider flex items-center gap-1.5">
                                        <span>👧 GIRLS</span>
                                    </span>
                                    <span class="text-[11px] font-bold text-pink-700 bg-pink-100 px-2.5 py-0.5 rounded-full">
                                        {{ $enrolledGirls->count() }} Student(s)
                                    </span>
                                </div>
                                <div id="roster-girls-sortable" class="space-y-1.5 min-h-[40px]">
                                    @forelse($enrolledGirls as $secStudent)
                                        @php
                                            $st = $secStudent->student;
                                            $app = $st?->applicant;
                                            $stName = $buildStudentNameWithInitial($app, $st->student_number);
                                            $stType = $app?->student_type ?: 'NEW';
                                        @endphp
                                        <div class="p-2.5 rounded-xl border border-slate-200/80 bg-white flex items-center justify-between gap-3 group hover:bg-slate-50 transition shadow-2xs">
                                            <div class="min-w-0 flex items-center gap-2">
                                                <i data-lucide="grip-vertical" class="drag-handle w-4 h-4 text-slate-300 group-hover:text-pink-600 shrink-0 cursor-grab active:cursor-grabbing"></i>
                                                <div class="min-w-0">
                                                    <a href="{{ route('admin.students.show', $st) }}" target="_blank" class="font-black text-xs text-slate-900 hover:text-pink-700 transition uppercase block truncate">
                                                        {{ $stName }}
                                                    </a>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400 font-bold mt-0.5">
                                                        <span>ID: {{ $st->student_number }}</span>
                                                        @if($app?->lrn)
                                                            <span>&bull; LRN: {{ $app->lrn }}</span>
                                                        @endif
                                                        <span class="text-pink-600 font-black">&bull; FEMALE</span>
                                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ strtolower($stType) === 'old' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $stType }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('admin.students.occupancy.remove-student', $secStudent) }}" onsubmit="return confirm('Remove {{ addslashes($stName) }} from {{ addslashes($sectionDisplayName) }}?')" class="shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 px-2 py-1 rounded-lg transition cursor-pointer" title="Remove from section">
                                                    <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                                    <span>Remove</span>
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-[11px] font-semibold text-slate-400 italic px-2 py-1">No girls enrolled in this section.</p>
                                    @endforelse
                                </div>
                            </div>

                            @if($enrolledUnspecified->isNotEmpty())
                                <!-- UNSPECIFIED / OTHER GENDER SECTION -->
                                <div class="space-y-2 pt-2 border-t border-slate-100">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100 rounded-xl border border-slate-200 text-slate-700">
                                        <span class="text-xs font-black uppercase tracking-wider">👥 OTHER / UNASSIGNED GENDER</span>
                                        <span class="text-[11px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded-full">
                                            {{ $enrolledUnspecified->count() }} Student(s)
                                        </span>
                                    </div>
                                    <div class="space-y-1.5">
                                        @foreach($enrolledUnspecified as $secStudent)
                                            @php
                                                $st = $secStudent->student;
                                                $app = $st?->applicant;
                                                $stName = $buildStudentNameWithInitial($app, $st->student_number);
                                                $stType = $app?->student_type ?: 'NEW';
                                            @endphp
                                            <div class="p-2.5 rounded-xl border border-slate-200 bg-white flex items-center justify-between gap-3 group hover:bg-slate-50 transition">
                                                <div class="min-w-0 flex items-center gap-2">
                                                    <i data-lucide="grip-vertical" class="drag-handle w-4 h-4 text-slate-300 shrink-0"></i>
                                                    <div class="min-w-0">
                                                        <a href="{{ route('admin.students.show', $st) }}" target="_blank" class="font-black text-xs text-slate-900 hover:text-emerald-700 transition uppercase block truncate">
                                                            {{ $stName }}
                                                        </a>
                                                        <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400 font-bold mt-0.5">
                                                            <span>ID: {{ $st->student_number }}</span>
                                                            @if($app?->lrn)
                                                                <span>&bull; LRN: {{ $app->lrn }}</span>
                                                            @endif
                                                            <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ strtolower($stType) === 'old' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                                {{ $stType }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <form method="POST" action="{{ route('admin.students.occupancy.remove-student', $secStudent) }}" onsubmit="return confirm('Remove {{ addslashes($stName) }} from {{ addslashes($sectionDisplayName) }}?')" class="shrink-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 px-2 py-1 rounded-lg transition cursor-pointer" title="Remove from section">
                                                        <i data-lucide="user-minus" class="w-3.5 h-3.5"></i>
                                                        <span>Remove</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endif
                </div>
            </div>

            <!-- RIGHT COLUMN: Add Students Workspace (7 Cols) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i data-lucide="user-plus" class="w-5 h-5 text-emerald-600"></i>
                                <span>Add Students to {{ $sectionDisplayName }}</span>
                            </h2>
                            <p class="text-xs text-slate-500 font-medium">Search, select, or drag-and-drop official student records</p>
                        </div>
                    </div>

                    <!-- Filter & Search Form -->
                    <form method="GET" action="{{ route('admin.students.occupancy.manage-section', $section) }}" class="space-y-3 mb-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Student Name, ID Number, or LRN..."
                                       class="w-full h-11 pl-10 pr-4 rounded-2xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-900 outline-none focus:border-emerald-500 focus:bg-white transition">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5"></i>
                            </div>
                            <button type="submit" class="h-11 px-5 rounded-2xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold transition shadow-sm cursor-pointer shrink-0 flex items-center justify-center gap-1.5">
                                <span>Search Records</span>
                            </button>
                        </div>

                        <!-- Grade Filter Tabs -->
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <span class="text-xs font-bold text-slate-500">Grade:</span>
                            <a href="{{ route('admin.students.occupancy.manage-section', [$section, 'grade_filter' => 'matching', 'search' => request('search')]) }}"
                               class="px-3 py-1 rounded-xl text-xs font-bold transition {{ $gradeFilter === 'matching' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                {{ $section->grade_level ?: 'Matching Grade' }} Students Only
                            </a>
                            <a href="{{ route('admin.students.occupancy.manage-section', [$section, 'grade_filter' => 'all', 'search' => request('search')]) }}"
                               class="px-3 py-1 rounded-xl text-xs font-bold transition {{ $gradeFilter === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                                All School Records (Search Entire Database)
                            </a>
                        </div>
                    </form>

                    <!-- Student Selection Form -->
                    <form method="POST" action="{{ route('admin.students.occupancy.assign-students', $section) }}" x-data="{ selectAll: false }">
                        @csrf
                        
                        <div class="flex items-center justify-between px-3 py-2 bg-slate-50 rounded-2xl border border-slate-200/80 mb-3 text-xs font-bold text-slate-600">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" @click="selectAll = !selectAll; $el.closest('form').querySelectorAll('input[type=checkbox]').forEach(c => c.checked = selectAll)"
                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span>Select All Visible Students</span>
                            </label>
                            <span class="text-slate-400 font-semibold">{{ $availableStudents->count() }} Student Records Found</span>
                        </div>

                        <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1 border border-slate-200 rounded-2xl p-3 bg-slate-50/30">
                            
                            <!-- AVAILABLE BOYS -->
                            @if($availableBoys->isNotEmpty())
                                <div class="space-y-1.5">
                                    <div class="text-[11px] font-black text-blue-900 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200/60 uppercase tracking-wider flex items-center justify-between">
                                        <span>👦 BOYS ({{ $availableBoys->count() }})</span>
                                        <span class="text-[10px] font-bold text-blue-600">Drag or check to assign</span>
                                    </div>
                                    @foreach($availableBoys as $st)
                                        @php
                                            $stApp = $st->applicant;
                                            $stName = $buildStudentNameWithInitial($stApp, $st->student_number);
                                            $stType = $stApp?->student_type ?: 'NEW';
                                            $currentSec = $st->studentSection?->section;
                                            $isEnrolledInThis = $currentSec && $currentSec->id === $section->id;
                                        @endphp
                                        <label draggable="true"
                                               @dragstart="$event.dataTransfer.setData('text/plain', '{{ $st->id }}')"
                                               class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200/80 hover:border-blue-400 hover:shadow-xs transition cursor-grab active:cursor-grabbing group">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <i data-lucide="grip-vertical" class="drag-handle w-4 h-4 text-slate-300 group-hover:text-blue-500 shrink-0"></i>
                                                <input type="checkbox" name="student_ids[]" value="{{ $st->id }}" @checked($isEnrolledInThis)
                                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                <div class="min-w-0">
                                                    <span class="block text-xs font-black text-slate-900 uppercase truncate leading-tight">{{ $stName }}</span>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-400 mt-0.5">
                                                        <span>ID: {{ $st->student_number }}</span>
                                                        @if($stApp?->lrn)
                                                            <span>&bull; LRN: {{ $stApp->lrn }}</span>
                                                        @endif
                                                        <span class="text-blue-600 font-extrabold">&bull; MALE</span>
                                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ strtolower($stType) === 'old' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $stType }}
                                                        </span>
                                                        <span>&bull; Grade: <strong>{{ $st->grade_level }}</strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                @if($isEnrolledInThis)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                                                        <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                                                        <span>Assigned Here</span>
                                                    </span>
                                                @elseif($currentSec)
                                                    <span class="text-[10px] font-black text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full uppercase" title="Already assigned to another class">
                                                        Exist: {{ $currentSec->official_name ?: $currentSec->name }}
                                                    </span>
                                                @else
                                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">
                                                        Unassigned
                                                    </span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <!-- AVAILABLE GIRLS -->
                            @if($availableGirls->isNotEmpty())
                                <div class="space-y-1.5 pt-2 border-t border-slate-100">
                                    <div class="text-[11px] font-black text-pink-900 bg-pink-50 px-2.5 py-1 rounded-lg border border-pink-200/60 uppercase tracking-wider flex items-center justify-between">
                                        <span>👧 GIRLS ({{ $availableGirls->count() }})</span>
                                        <span class="text-[10px] font-bold text-pink-600">Drag or check to assign</span>
                                    </div>
                                    @foreach($availableGirls as $st)
                                        @php
                                            $stApp = $st->applicant;
                                            $stName = $buildStudentNameWithInitial($stApp, $st->student_number);
                                            $stType = $stApp?->student_type ?: 'NEW';
                                            $currentSec = $st->studentSection?->section;
                                            $isEnrolledInThis = $currentSec && $currentSec->id === $section->id;
                                        @endphp
                                        <label draggable="true"
                                               @dragstart="$event.dataTransfer.setData('text/plain', '{{ $st->id }}')"
                                               class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200/80 hover:border-pink-400 hover:shadow-xs transition cursor-grab active:cursor-grabbing group">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <i data-lucide="grip-vertical" class="drag-handle w-4 h-4 text-slate-300 group-hover:text-pink-500 shrink-0"></i>
                                                <input type="checkbox" name="student_ids[]" value="{{ $st->id }}" @checked($isEnrolledInThis)
                                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                <div class="min-w-0">
                                                    <span class="block text-xs font-black text-slate-900 uppercase truncate leading-tight">{{ $stName }}</span>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-400 mt-0.5">
                                                        <span>ID: {{ $st->student_number }}</span>
                                                        @if($stApp?->lrn)
                                                            <span>&bull; LRN: {{ $stApp->lrn }}</span>
                                                        @endif
                                                        <span class="text-pink-600 font-extrabold">&bull; FEMALE</span>
                                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ strtolower($stType) === 'old' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $stType }}
                                                        </span>
                                                        <span>&bull; Grade: <strong>{{ $st->grade_level }}</strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                @if($isEnrolledInThis)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                                                        <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                                                        <span>Assigned Here</span>
                                                    </span>
                                                @elseif($currentSec)
                                                    <span class="text-[10px] font-black text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full uppercase" title="Already assigned to another class">
                                                        Exist: {{ $currentSec->official_name ?: $currentSec->name }}
                                                    </span>
                                                @else
                                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">
                                                        Unassigned
                                                    </span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            <!-- AVAILABLE OTHERS / UNSPECIFIED GENDER -->
                            @if($availableOthers->isNotEmpty())
                                <div class="space-y-1.5 pt-2 border-t border-slate-100">
                                    <div class="text-[11px] font-black text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200 uppercase tracking-wider">
                                        👥 OTHER / UNASSIGNED GENDER ({{ $availableOthers->count() }})
                                    </div>
                                    @foreach($availableOthers as $st)
                                        @php
                                            $stApp = $st->applicant;
                                            $stName = $buildStudentNameWithInitial($stApp, $st->student_number);
                                            $stType = $stApp?->student_type ?: 'NEW';
                                            $currentSec = $st->studentSection?->section;
                                            $isEnrolledInThis = $currentSec && $currentSec->id === $section->id;
                                        @endphp
                                        <label draggable="true"
                                               @dragstart="$event.dataTransfer.setData('text/plain', '{{ $st->id }}')"
                                               class="flex items-center justify-between p-2.5 rounded-xl bg-white border border-slate-200/80 hover:border-slate-400 hover:shadow-xs transition cursor-grab active:cursor-grabbing group">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <i data-lucide="grip-vertical" class="drag-handle w-4 h-4 text-slate-300 shrink-0"></i>
                                                <input type="checkbox" name="student_ids[]" value="{{ $st->id }}" @checked($isEnrolledInThis)
                                                       class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                <div class="min-w-0">
                                                    <span class="block text-xs font-black text-slate-900 uppercase truncate leading-tight">{{ $stName }}</span>
                                                    <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-slate-400 mt-0.5">
                                                        <span>ID: {{ $st->student_number }}</span>
                                                        @if($stApp?->lrn)
                                                            <span>&bull; LRN: {{ $stApp->lrn }}</span>
                                                        @endif
                                                        <span class="inline-flex rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ strtolower($stType) === 'old' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                            {{ $stType }}
                                                        </span>
                                                        <span>&bull; Grade: <strong>{{ $st->grade_level }}</strong></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right shrink-0">
                                                @if($isEnrolledInThis)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                                                        <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                                                        <span>Assigned Here</span>
                                                    </span>
                                                @elseif($currentSec)
                                                    <span class="text-[10px] font-black text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-0.5 rounded-full uppercase" title="Already assigned to another class">
                                                        Exist: {{ $currentSec->official_name ?: $currentSec->name }}
                                                    </span>
                                                @else
                                                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2.5 py-0.5 rounded-full">
                                                        Unassigned
                                                    </span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif

                            @if($availableStudents->isEmpty())
                                <div class="py-12 text-center text-slate-400 italic">
                                    <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-2 text-slate-300"></i>
                                    <p class="text-xs font-bold text-slate-500">No student records found matching your search.</p>
                                    @if($gradeFilter === 'matching')
                                        <p class="text-[11px] text-slate-400 mt-1">Try switching to "All School Records" tab above to search across all grade levels.</p>
                                    @endif
                                </div>
                            @endif

                        </div>

                        <!-- Pagination Links -->
                        <div class="mt-4 px-2">
                            {{ $availableStudents->links() }}
                        </div>

                        <div class="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-xs text-slate-500 font-medium">Check the boxes or drag student cards to assign.</span>
                            <button type="submit" class="h-11 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-md active:scale-95 cursor-pointer flex items-center gap-2">
                                <i data-lucide="user-check" class="w-4 h-4"></i>
                                <span>Assign Selected Students to {{ $sectionDisplayName }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Initialize SortableJS for fluid drag-and-drop -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            ['roster-boys-sortable', 'roster-girls-sortable'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) {
                    new Sortable(el, {
                        animation: 150,
                        handle: '.drag-handle',
                        ghostClass: 'bg-emerald-100'
                    });
                }
            });
        });
    </script>
</x-admin-layout>
