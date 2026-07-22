@php
    $totalCapacity = $sections->sum('capacity_limit');
    $totalOccupied = $sections->sum('occupied');
    $overallFillRate = $totalCapacity > 0 ? min(100, round(($totalOccupied / $totalCapacity) * 100)) : 0;
    
    $f2fCapacity = $sections->where('is_f2f', true)->sum('capacity_limit');
    $f2fOccupied = $sections->where('is_f2f', true)->sum('occupied');
    $f2fFillRate = $f2fCapacity > 0 ? min(100, round(($f2fOccupied / $f2fCapacity) * 100)) : 0;

    $flexCapacity = $sections->where('is_f2f', false)->sum('capacity_limit');
    $flexOccupied = $sections->where('is_f2f', false)->sum('occupied');
    $flexFillRate = $flexCapacity > 0 ? min(100, round(($flexOccupied / $flexCapacity) * 100)) : 0;
@endphp

<x-admin-layout
    title="Section Occupancy"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Section Occupancy', 'href' => null],
    ]"
>
    <div class="space-y-6" x-data="{ 
        openCreateModal: false, 
        openJsonModal: false,
        jsonSample: `[\n  {\n    \"lrn\": \"127168190019\",\n    \"first_name\": \"AZHAR\",\n    \"middle_name\": \"IBRAHIM\",\n    \"last_name\": \"SALINDAWAN\",\n    \"grade_level\": \"Grade 7\",\n    \"gender\": \"Male\",\n    \"address\": \"6921 ALKHAZNAH ISHBILIYAH RIYADH, 13225, SAUDI ARABIA\",\n    \"date_of_birth\": \"2014-06-13\",\n    \"place_of_birth\": \"RIYADH KSA\",\n    \"religion\": \"ISLAM\",\n    \"parent_name\": \"SAHARODIN G. SALINDAWAN\",\n    \"parent_mobile\": \"50 073 8648\",\n    \"parent_email\": \"angel_10178@yahoo.com\"\n  }\n]`
    }">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Section Occupancy & Class Roster</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Monitor class sizes, advisor assignments, create new sections, and auto-match & insert JSON student records.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="openJsonModal = true"
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 px-5 py-3 text-xs font-black text-white shadow-lg hover:bg-emerald-500/30 transition active:scale-95 cursor-pointer backdrop-blur-xs">
                        <i data-lucide="file-json" class="w-4 h-4 text-emerald-300"></i>
                        <span>⚡ JSON Batch Sync</span>
                    </button>
                    <button type="button" @click="openCreateModal = true"
                            class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-xs font-black text-emerald-900 shadow-lg hover:bg-emerald-50 transition active:scale-95 cursor-pointer">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-emerald-600"></i>
                        <span>Create New Section</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Create New Section Modal -->
        <template x-teleport="body">
            <div x-show="openCreateModal"
                 style="display: none; z-index: 99999;"
                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col"
                     @click.outside="openCreateModal = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="plus-circle" class="w-5 h-5 text-emerald-600"></i>
                            <span>Create New Class Section</span>
                        </h3>
                        <button @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600 transition">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('admin.students.occupancy.store-section') }}" class="p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Section Name</label>
                            <input type="text" name="name" placeholder="e.g. ALI IBN ABI TALIB or HUDHAYFAH"
                                   class="w-full h-11 px-3.5 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Grade Level</label>
                                <select name="grade_level" required class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500">
                                    @foreach(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $g)
                                        <option value="{{ $g }}">{{ $g }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Learning Mode <span class="text-slate-400 font-medium lowercase">(optional)</span></label>
                                <select name="learning_mode" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500">
                                    <option value="">None / Default</option>
                                    <option value="Flexible Online Learning">Flexible Online Learning</option>
                                    <option value="Face-to-Face">Face-to-Face</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Shift <span class="text-slate-400 font-medium lowercase">(optional)</span></label>
                                <select name="shift" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500">
                                    <option value="">None / No Shift</option>
                                    <option value="1st Shift">1st Shift</option>
                                    <option value="2nd Shift">2nd Shift</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Gender Allocation <span class="text-slate-400 font-medium lowercase">(optional)</span></label>
                                <select name="gender" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500">
                                    <option value="merge">Co-Ed (Merge / Default)</option>
                                    <option value="female">Girls Only</option>
                                    <option value="male">Boys Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="openCreateModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md cursor-pointer">
                                Create Section
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- JSON Bulk Student Import & Auto-Match Modal -->
        <template x-teleport="body">
            <div x-show="openJsonModal"
                 style="display: none; z-index: 99999;"
                 class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
                <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh]"
                     @click.outside="openJsonModal = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <i data-lucide="file-json" class="w-5 h-5 text-emerald-600"></i>
                                <span>Batch JSON Student Sync (Auto-Match & Insert)</span>
                            </h3>
                            <p class="text-xs font-semibold text-slate-500 mt-0.5">Auto-updates existing students by Name / LRN or creates new student records automatically.</p>
                        </div>
                        <button @click="openJsonModal = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.students.occupancy.bulk-json-import') }}" enctype="multipart/form-data" class="p-6 space-y-4 overflow-y-auto">
                        @csrf
                        
                        <!-- Target Section Selector (Optional) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Assign to Section <span class="text-slate-400 font-medium lowercase">(optional)</span>
                            </label>
                            <select name="target_section_id" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500">
                                <option value="">Do Not Assign Section Automatically</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->grade_level }} - {{ $sec->name ?: 'F2F' }} ({{ $sec->occupied }}/{{ $sec->capacity_limit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- JSON Textarea -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                    Paste JSON Payload (40+ Students Array)
                                </label>
                                <button type="button" @click="$refs.jsonArea.value = jsonSample" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 underline cursor-pointer">
                                    Fill Sample Format
                                </button>
                            </div>
                            <textarea x-ref="jsonArea" name="json_data" rows="9" placeholder="Paste your student JSON array here..."
                                      class="w-full p-3.5 rounded-xl border border-slate-200 bg-slate-900 font-mono text-xs text-emerald-400 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 leading-relaxed shadow-inner"></textarea>
                        </div>

                        <!-- JSON File Upload Alternative -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Or Upload JSON File (.json / .txt)
                            </label>
                            <input type="file" name="json_file" accept=".json,.txt"
                                   class="w-full text-xs font-semibold text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="openJsonModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-md cursor-pointer flex items-center gap-1.5">
                                <i data-lucide="zap" class="w-4 h-4"></i>
                                <span>Process & Auto-Sync Batch</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Occupancy Container -->
        <div id="occupancyContainer" class="space-y-6">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 lg:gap-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Official Students</p>
                        <div class="rounded-xl bg-emerald-50 p-1.5 text-emerald-600">
                            <i data-lucide="users" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($totalOfficial) }}</span>
                        <span class="text-xs font-bold text-emerald-600">Verified</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Registered Active Accounts
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Enrolled</p>
                        <div class="rounded-xl bg-blue-50 p-1.5 text-blue-600">
                            <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($totalOccupied) }}</span>
                        <span class="text-xs font-bold text-slate-500">/ {{ number_format($totalCapacity) }} Seats</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Overall Fill Rate: <span class="font-extrabold text-emerald-600">{{ $overallFillRate }}%</span>
                    </div>
                </div>
                
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">F2F Occupancy</p>
                        <div class="rounded-xl bg-sky-50 p-1.5 text-sky-600">
                            <i data-lucide="school" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($f2fOccupied) }}</span>
                        <span class="text-xs font-bold text-slate-500">/ {{ number_format($f2fCapacity) }} Seats</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        F2F Fill Rate: <span class="font-extrabold text-emerald-600">{{ $f2fFillRate }}%</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Flexible Occupancy</p>
                        <div class="rounded-xl bg-amber-50 p-1.5 text-amber-600">
                            <i data-lucide="laptop" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($flexOccupied) }}</span>
                        <span class="text-xs font-bold text-slate-500">/ {{ number_format($flexCapacity) }} Seats</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Flexible Fill Rate: <span class="font-extrabold text-amber-600">{{ $flexFillRate }}%</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Sections</p>
                        <div class="rounded-xl bg-indigo-50 p-1.5 text-indigo-600">
                            <i data-lucide="grid" class="h-4 w-4"></i>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <span class="text-2xl font-black text-slate-900">{{ $sections->count() }}</span>
                        <span class="text-xs font-bold text-emerald-600">Active</span>
                    </div>
                    <div class="mt-2 text-[10px] font-semibold text-slate-500">
                        Average: <span class="font-extrabold text-slate-700">{{ $sections->count() > 0 ? round($totalOccupied / $sections->count(), 1) : 0 }} / sec</span>
                    </div>
                </div>
            </div>

            <!-- Grade Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @forelse($sectionsGrouped as $gradeLevel => $gradeSections)
                    @include('admin.students.partials.occupancy.card', ['gradeLevel' => $gradeLevel, 'gradeSections' => $gradeSections, 'studentsByGrade' => $studentsByGrade ?? collect()])
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center bg-white">
                        <p class="text-sm font-bold text-slate-500">No school sections configured.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
