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

    $existingJsonMap = [];
    $allStudentsArray = [];

    if (isset($studentsByGrade)) {
        foreach ($studentsByGrade as $gradeKey => $gradeStudents) {
            $gradeArray = [];
            foreach ($gradeStudents as $s) {
                $app = $s->applicant;
                $fatherName = trim(($app->father_first_name ?? '') . ' ' . ($app->father_last_name ?? ''));
                $motherName = trim(($app->mother_first_name ?? '') . ' ' . ($app->mother_last_name ?? ''));
                $pName = !empty($fatherName) ? $fatherName : (!empty($motherName) ? $motherName : ($app->father_name ?? $app->mother_name ?? ''));
                
                $item = [
                    'lrn' => (string)($app->lrn ?? $s->student_number ?? ''),
                    'first_name' => mb_strtoupper($app->first_name ?? ''),
                    'middle_name' => mb_strtoupper($app->middle_name ?? ''),
                    'last_name' => mb_strtoupper($app->last_name ?? ''),
                    'grade_level' => (string)($s->grade_level ?? $app->grade_level ?? $gradeKey),
                    'gender' => (string)($app->gender ?? ''),
                    'address' => mb_strtoupper($app->address ?? $app->street_address ?? ''),
                    'date_of_birth' => $app?->date_of_birth ? $app->date_of_birth->format('Y-m-d') : '',
                    'place_of_birth' => mb_strtoupper($app->place_of_birth ?? ''),
                    'religion' => mb_strtoupper($app->religion ?? 'ISLAM'),
                    'parent_name' => mb_strtoupper($pName),
                    'parent_mobile' => (string)($app->parent_mobile ?? $app->mobile_number ?? ''),
                    'parent_email' => strtolower($app->parent_email ?? ''),
                ];
                $gradeArray[] = $item;
                $allStudentsArray[] = $item;
            }
            $existingJsonMap[$gradeKey] = json_encode($gradeArray, JSON_PRETTY_PRINT);
        }
    }

    $allJsonPretty = json_encode($allStudentsArray, JSON_PRETTY_PRINT);

    $sampleStudentArray = [
        [
            'lrn' => '127168190019',
            'first_name' => 'AZHAR',
            'middle_name' => 'IBRAHIM',
            'last_name' => 'SALINDAWAN',
            'grade_level' => 'Grade 1',
            'gender' => 'Male',
            'address' => '6921 ALKHAZNAH ISHBILIYAH RIYADH, 13225, SAUDI ARABIA',
            'date_of_birth' => '2018-06-13',
            'place_of_birth' => 'RIYADH KSA',
            'religion' => 'ISLAM',
            'parent_name' => 'SAHARODIN G. SALINDAWAN',
            'parent_mobile' => '50 073 8648',
            'parent_email' => 'angel_10178@yahoo.com',
        ]
    ];
    $sampleJsonPretty = json_encode($sampleStudentArray, JSON_PRETTY_PRINT);
@endphp

<script>
    window.AMIS_EXISTING_JSON_MAP = @json($existingJsonMap);
    window.AMIS_ALL_JSON = @json($allJsonPretty);
    window.AMIS_SAMPLE_JSON = @json($sampleStudentArray);

    window.copyGradeJsonToClipboard = function(gradeLevel) {
        let txt = '';
        if (gradeLevel && window.AMIS_EXISTING_JSON_MAP && window.AMIS_EXISTING_JSON_MAP[gradeLevel]) {
            txt = window.AMIS_EXISTING_JSON_MAP[gradeLevel];
        } else if (window.AMIS_ALL_JSON && window.AMIS_ALL_JSON !== '[]') {
            txt = window.AMIS_ALL_JSON;
        } else {
            txt = JSON.stringify(window.AMIS_SAMPLE_JSON, null, 4);
        }

        navigator.clipboard.writeText(txt).then(() => {
            alert('Copied ' + (gradeLevel || 'All') + ' student JSON payload to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    };

    window.fillSampleJson = function(gradeLevel) {
        let txt = '';
        if (gradeLevel && window.AMIS_EXISTING_JSON_MAP && window.AMIS_EXISTING_JSON_MAP[gradeLevel]) {
            txt = window.AMIS_EXISTING_JSON_MAP[gradeLevel];
        } else if (window.AMIS_ALL_JSON && window.AMIS_ALL_JSON !== '[]') {
            txt = window.AMIS_ALL_JSON;
        } else {
            txt = JSON.stringify(window.AMIS_SAMPLE_JSON, null, 4);
        }
        if (window.AMIS_OCCUPANCY) {
            window.AMIS_OCCUPANCY.jsonInputText = txt;
        }
        return txt;
    };

    document.addEventListener('alpine:init', () => {
        Alpine.data('occupancyManager', () => ({
            openCreateModal: false,
            openJsonModal: false,
            jsonStep: 1,
            selectedTargetSection: '',
            selectedGradeLevel: '',
            jsonInputText: '',
            previewList: [],
            previewTotals: { total: 0, update: 0, create: 0 },
            loadingPreview: false,
            previewError: '',
            previewSearch: '',
            jsonSample: window.AMIS_SAMPLE_JSON || '',

            init() {
                window.AMIS_OCCUPANCY = this;
                
                this.$watch('openJsonModal', value => {
                    if (value && !this.jsonInputText) {
                        window.fillSampleJson(this.selectedGradeLevel);
                    }
                });
            },

            async generatePreview() {
                this.previewError = '';
                const jsonText = (this.jsonInputText || '').trim();
                const fileInput = this.$refs.jsonFileInput;
                
                if (!jsonText && (!fileInput || !fileInput.files.length)) {
                    this.previewError = 'Please paste a JSON array or select a JSON file to preview.';
                    return;
                }

                this.loadingPreview = true;
                try {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    formData.append('json_data', jsonText);
                    if (fileInput && fileInput.files.length) {
                        formData.append('json_file', fileInput.files[0]);
                    }

                    const response = await fetch('{{ route("admin.students.occupancy.preview-json-import") }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'Accept': 'application/json' }
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to parse JSON payload.');
                    }

                    this.previewList = data.students || [];
                    this.previewTotals = {
                        total: data.total || 0,
                        update: data.update_count || 0,
                        create: data.create_count || 0
                    };
                    this.jsonStep = 2;
                } catch (err) {
                    this.previewError = err.message || 'Error generating preview.';
                } finally {
                    this.loadingPreview = false;
                }
            },

            filteredPreviewList() {
                if (!this.previewSearch) return this.previewList;
                const q = this.previewSearch.toLowerCase();
                return this.previewList.filter(s => 
                    (s.name || '').toLowerCase().includes(q) ||
                    (s.lrn || '').toLowerCase().includes(q) ||
                    (s.parent || '').toLowerCase().includes(q) ||
                    (s.status || '').toLowerCase().includes(q)
                );
            }
        }));
    });

    window.fillSampleJson = function(gradeLevel = null) {
        let payload = '';
        const activeGrade = gradeLevel || (window.AMIS_OCCUPANCY ? window.AMIS_OCCUPANCY.selectedGradeLevel : '');
        
        if (activeGrade && window.AMIS_EXISTING_JSON_MAP && window.AMIS_EXISTING_JSON_MAP[activeGrade]) {
            payload = window.AMIS_EXISTING_JSON_MAP[activeGrade];
        } else if (window.AMIS_ALL_JSON && window.AMIS_ALL_JSON.length > 2) {
            payload = window.AMIS_ALL_JSON;
        } else {
            payload = window.AMIS_SAMPLE_JSON;
        }

        if (window.AMIS_OCCUPANCY) {
            window.AMIS_OCCUPANCY.jsonInputText = payload;
            window.AMIS_OCCUPANCY.jsonSample = payload;
        }
        const txtArea = document.querySelector('textarea[name="json_data"]');
        if (txtArea) {
            txtArea.value = payload;
            txtArea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    window.addEventListener('open-json-sync', (e) => {
        if (window.AMIS_OCCUPANCY) {
            if (e.detail && e.detail.gradeLevel) {
                window.AMIS_OCCUPANCY.selectedGradeLevel = e.detail.gradeLevel;
            }
            if (e.detail && e.detail.sectionId) {
                window.AMIS_OCCUPANCY.selectedTargetSection = e.detail.sectionId;
            }
            window.fillSampleJson(window.AMIS_OCCUPANCY.selectedGradeLevel);
        }
    });
</script>

<x-admin-layout
    title="Section Occupancy"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Section Occupancy', 'href' => null],
    ]"
>
    <div class="space-y-6" x-data="occupancyManager" @open-json-sync.window="openJsonModal = true; jsonStep = 1; previewError = ''; selectedTargetSection = $event.detail.sectionId || ''; selectedGradeLevel = $event.detail.gradeLevel || '';">
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
                    <button type="button" @click="openJsonModal = true; jsonStep = 1; previewError = '';"
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
        <div x-cloak x-show="openCreateModal"
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
                    <button type="button" @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600 transition">
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

        <!-- JSON Bulk Student Import & Auto-Match Modal -->
        <div x-cloak x-show="openJsonModal"
             style="display: none; z-index: 99999;"
             class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-3xl w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[92vh]"
                 @click.outside="openJsonModal = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i data-lucide="file-json" class="w-5 h-5 text-emerald-600"></i>
                            <span x-text="jsonStep === 1 ? 'Batch JSON Student Sync (Input)' : '🔍 Review & Double-Check Student Records'"></span>
                            <template x-if="selectedGradeLevel">
                                <span class="inline-flex items-center gap-1 bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider">
                                    <i data-lucide="filter" class="w-3 h-3"></i>
                                    <span x-text="selectedGradeLevel"></span>
                                </span>
                            </template>
                        </h3>
                        <p class="text-xs font-semibold text-slate-500 mt-0.5" x-text="jsonStep === 1 ? 'Paste or upload student JSON data to preview matching before syncing.' : 'Verify how each student will be updated or created in the database.'"></p>
                    </div>
                    <button type="button" @click="openJsonModal = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.students.occupancy.bulk-json-import') }}" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    
                    <!-- STEP 1: INPUT & PASTE -->
                    <div x-show="jsonStep === 1" class="p-6 space-y-4 overflow-y-auto">
                        <!-- Target Section Selector -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Assign to Section <span class="text-slate-400 font-medium lowercase">(optional)</span>
                            </label>
                            <select name="target_section_id" x-model="selectedTargetSection" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-800 dark:border-slate-700 text-xs font-bold text-slate-900 dark:text-white outline-none focus:border-emerald-500">
                                <option value="">Do Not Assign Section Automatically</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->grade_level }} - {{ $sec->name ?: 'F2F' }} ({{ $sec->occupied }}/{{ $sec->capacity_limit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Grade Selector Quick Switcher Pills -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                Select Grade JSON to Auto-Fill & Review:
                            </label>
                            <div class="flex flex-wrap gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl overflow-x-auto max-h-24">
                                <button type="button" @click="selectedGradeLevel = ''; window.fillSampleJson('');"
                                        :class="!selectedGradeLevel ? 'bg-emerald-600 text-white shadow-xs font-black' : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 font-bold'"
                                        class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                                    All Grades
                                </button>
                                @foreach(['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'] as $gChoice)
                                    <button type="button" @click="selectedGradeLevel = '{{ $gChoice }}'; window.fillSampleJson('{{ $gChoice }}');"
                                            :class="selectedGradeLevel === '{{ $gChoice }}' ? 'bg-emerald-600 text-white shadow-xs font-black' : 'text-slate-700 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 font-bold'"
                                            class="px-2.5 py-1 rounded-lg text-xs transition cursor-pointer">
                                        {{ $gChoice }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- JSON Textarea -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                                    Paste JSON Payload (40+ Students Array)
                                </label>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="if (window.copyGradeJsonToClipboard) { window.copyGradeJsonToClipboard(selectedGradeLevel); }" class="text-[11px] font-bold text-sky-600 hover:text-sky-700 underline cursor-pointer flex items-center gap-1">
                                        <i data-lucide="copy" class="w-3 h-3"></i>
                                        Copy JSON to Clipboard
                                    </button>
                                    <button type="button" @click="window.fillSampleJson(selectedGradeLevel);" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 underline cursor-pointer flex items-center gap-1">
                                        Auto-Fill Existing DB (<span x-text="selectedGradeLevel || 'All'"></span>)
                                    </button>
                                </div>
                            </div>
                            <textarea name="json_data" x-model="jsonInputText" rows="8" placeholder="Paste your student JSON array here..."
                                      class="w-full p-3.5 rounded-xl border border-slate-200 bg-slate-900 font-mono text-xs text-emerald-400 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 leading-relaxed shadow-inner"></textarea>
                        </div>

                        <!-- JSON File Upload -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-3">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Or Upload JSON File (.json / .txt)
                            </label>
                            <input type="file" x-ref="jsonFileInput" name="json_file" accept=".json,.txt"
                                   class="w-full text-xs font-semibold text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                        </div>

                        <!-- Error Message Alert -->
                        <template x-if="previewError">
                            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs font-bold text-rose-700 flex items-center gap-2">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                                <span x-text="previewError"></span>
                            </div>
                        </template>

                        <!-- Step 1 Footer -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="openJsonModal = false" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                                Cancel
                            </button>
                            <button type="button" @click="generatePreview()" :disabled="loadingPreview"
                                    class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-black transition shadow-md cursor-pointer flex items-center gap-2">
                                <template x-if="loadingPreview">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                </template>
                                <span>🔍 Preview & Double-Check Data</span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: LIVE PREVIEW & VERIFICATION TABLE -->
                    <div x-show="jsonStep === 2" class="flex flex-col flex-1 overflow-hidden p-6 space-y-4">
                        <!-- Stats Header Banner -->
                        <div class="grid grid-cols-3 gap-3 p-3.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl text-center">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Total Parsed</span>
                                <div class="text-lg font-black text-slate-900 dark:text-white" x-text="previewTotals.total + ' Students'"></div>
                            </div>
                            <div class="border-x border-slate-200 dark:border-slate-700 px-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600">Will Update (Exist)</span>
                                <div class="text-lg font-black text-blue-700" x-text="previewTotals.update + ' Existing'"></div>
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600">Will Create (New)</span>
                                <div class="text-lg font-black text-emerald-700" x-text="previewTotals.create + ' New'"></div>
                            </div>
                        </div>

                        <!-- Filter Input -->
                        <div class="flex items-center justify-between gap-2">
                            <input type="text" x-model="previewSearch" placeholder="Search preview list by Name, LRN, Parent..."
                                   class="w-full h-9 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl outline-none focus:border-blue-500">
                        </div>

                        <!-- Preview Table -->
                        <div class="flex-1 overflow-y-auto border border-slate-200 dark:border-slate-800 rounded-2xl max-h-[350px]">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-100 dark:bg-slate-800/90 sticky top-0 font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[10px] border-b border-slate-200 dark:border-slate-700">
                                    <tr>
                                        <th class="p-2.5">#</th>
                                        <th class="p-2.5">Action</th>
                                        <th class="p-2.5">Student Name</th>
                                        <th class="p-2.5">LRN</th>
                                        <th class="p-2.5">Grade</th>
                                        <th class="p-2.5">Parent Details</th>
                                        <th class="p-2.5">Address</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-800 dark:text-slate-200">
                                    <template x-for="item in filteredPreviewList()" :key="item.index">
                                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                            <td class="p-2.5 font-bold text-slate-400" x-text="item.index"></td>
                                            <td class="p-2.5">
                                                <span x-show="item.status === 'UPDATE'" class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 px-2 py-0.5 rounded-md text-[10px] font-black uppercase" title="Existing Student Record Found - Will update LRN, Address, Section">
                                                    🔄 UPDATE
                                                </span>
                                                <span x-show="item.status === 'CREATE'" class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md text-[10px] font-black uppercase" title="New Student - Will auto-create Student ID and User Account">
                                                    🆕 CREATE
                                                </span>
                                            </td>
                                            <td class="p-2.5 font-bold" x-text="item.name"></td>
                                            <td class="p-2.5 font-mono text-slate-600 dark:text-slate-400" x-text="item.lrn"></td>
                                            <td class="p-2.5 font-bold text-emerald-700" x-text="item.grade_level"></td>
                                            <td class="p-2.5 text-slate-600 dark:text-slate-400" x-text="item.parent"></td>
                                            <td class="p-2.5 text-[11px] text-slate-500 max-w-[180px] truncate" x-text="item.address" :title="item.address"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Safety Banner -->
                        <div class="p-3 bg-amber-50/90 border border-amber-200 rounded-xl text-[11px] font-medium leading-relaxed text-amber-950 flex items-start gap-2">
                            <i data-lucide="shield-check" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                            <div>
                                <strong>Safety Double-Check:</strong> Reviewing records above. Existing students will be updated without affecting past records; new students will receive automatic accounts and section placement.
                            </div>
                        </div>

                        <!-- Step 2 Footer Buttons -->
                        <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="jsonStep = 1" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer flex items-center gap-1.5">
                                <span>✏️ Edit JSON Input</span>
                            </button>
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black transition shadow-lg cursor-pointer flex items-center gap-2 active:scale-95">
                                <i data-lucide="zap" class="w-4 h-4"></i>
                                <span>⚡ CONFIRM & SYNC ALL STUDENTS TO DATABASE</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

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
