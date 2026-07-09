@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
@endphp

<x-admin-layout
    title="Verification CSV Comparison"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'CSV Comparison', 'href' => null],
    ]"
>
    <!-- Section Wrapper -->
    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-8">
        <!-- Header Banner -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 px-6 py-6 bg-gradient-to-r from-slate-50 to-slate-100/50">
            <div>
                <p class="text-xs font-black uppercase tracking-wider text-emerald-700">Students Workspace</p>
                <h1 class="mt-1 text-xl font-extrabold text-slate-950">Verification CSV Comparison</h1>
                <p class="mt-1 text-xs md:text-sm text-slate-500 font-medium">Compare portal database records against static fallback CSV files and track official student list registrations.</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.students.comparison.sync') }}" onsubmit="return confirm('Generate and overwrite the offline fallback CSV files with fresh database records?')" class="inline-block">
                    @csrf
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-95 cursor-pointer">
                        <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                        Sync & Overwrite CSV Fallbacks
                    </button>
                </form>
                <a href="{{ route('admin.students.index') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 active:scale-95">
                    <i data-lucide="user-check" class="h-4 w-4"></i>
                    Student Records
                </a>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Telemetry Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                <!-- Total Students -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-400">Total in Portal DB</span>
                        <div class="mt-1.5 text-2xl font-black text-slate-900">{{ number_format($totalDb) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                        <i data-lucide="database" class="h-5 w-5"></i>
                    </div>
                </div>

                <!-- Total In CSV -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-400">Synced in CSV</span>
                        <div class="mt-1.5 text-2xl font-black text-slate-900">{{ number_format($totalInCsv) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i data-lucide="file-check-2" class="h-5 w-5"></i>
                    </div>
                </div>

                <!-- Missing in CSV -->
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-black uppercase tracking-widest text-slate-400">Missing in Fallback</span>
                        <div class="mt-1.5 text-2xl font-black text-rose-600">{{ number_format($missingCount) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-250 p-4 text-sm font-bold text-emerald-800 flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-600"></i>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Main Split Grid layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- LEFT SIDE: AMIS DATABASE OFFICIAL -->
                <div class="lg:col-span-5 flex flex-col">
                    <div class="mb-4">
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i data-lucide="database" class="h-5 w-5 text-emerald-600"></i>
                            AMIS DATABASE OFFICIAL
                        </h2>
                        <p class="text-xs text-slate-500 font-medium">Verify student records currently saved in the school portal.</p>
                    </div>

                    <!-- Search & Filter Controls -->
                    <form method="GET" class="mb-4 space-y-3">
                        <!-- Keep Right text box value during filter -->
                        @if(!empty($officialList))
                            <input type="hidden" name="official_list" value="{{ $officialList }}">
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <div class="sm:col-span-6">
                                <label class="relative block">
                                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                                    <input name="search" value="{{ $search }}" placeholder="Search name or ID" class="{{ $inputClass }} w-full pl-9">
                                </label>
                            </div>
                            <div class="sm:col-span-3">
                                <select name="mode" class="{{ $inputClass }} w-full" onchange="this.form.submit()">
                                    <option value="all" {{ $modeFilter === 'all' ? 'selected' : '' }}>All Modes</option>
                                    <option value="f2f" {{ $modeFilter === 'f2f' ? 'selected' : '' }}>F2F Only</option>
                                    <option value="online" {{ $modeFilter === 'online' ? 'selected' : '' }}>Online Only</option>
                                </select>
                            </div>
                            <div class="sm:col-span-3">
                                <select name="filter" class="{{ $inputClass }} w-full" onchange="this.form.submit()">
                                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All DB</option>
                                    <option value="missing" {{ $filter === 'missing' ? 'selected' : '' }}>Missing CSV</option>
                                    <option value="insync" {{ $filter === 'insync' ? 'selected' : '' }}>In Sync</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <!-- DB Student List Table -->
                    <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white flex-1">
                        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                            <table class="w-full text-left text-sm relative">
                                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-100 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 bg-slate-50 font-black">ID</th>
                                        <th class="px-4 py-3 bg-slate-50 font-black">Full Name</th>
                                        <th class="px-4 py-3 bg-slate-50 font-black">Grade</th>
                                        <th class="px-4 py-3 bg-slate-50 font-black">Mode</th>
                                        <th class="px-4 py-3 bg-slate-50 font-black">CSV Coverage</th>
                                        <th class="px-4 py-3 bg-slate-50 font-black">Remarks</th>
                                        <th class="px-4 py-3 bg-slate-50 font-black text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($comparisonList as $row)
                                        <tr x-data="{ 
                                            remarks: '{{ addslashes($row['remarks'] ?? '') }}',
                                            updating: false,
                                            updateRemarks() {
                                                if (this.updating) return;
                                                this.updating = true;
                                                fetch('{{ route('admin.students.comparison.update-field') }}', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                    },
                                                    body: JSON.stringify({
                                                        student_number: '{{ $row['student_number'] }}',
                                                        field: 'remarks',
                                                        value: this.remarks
                                                    })
                                                })
                                                .then(res => res.json())
                                                .then(data => {
                                                    this.updating = false;
                                                    if (data.success) {
                                                        window.dispatchEvent(new CustomEvent('remarks-updated', { 
                                                            detail: { 
                                                                studentNumber: '{{ $row['student_number'] }}', 
                                                                remarks: this.remarks 
                                                            } 
                                                        }));
                                                    }
                                                })
                                                .catch(err => {
                                                    this.updating = false;
                                                    console.error(err);
                                                });
                                            }
                                        }" @remarks-updated.window="if ($event.detail.studentNumber === '{{ $row['student_number'] }}') { remarks = $event.detail.remarks; }" class="transition hover:bg-slate-50/50 text-xs" data-student-number="{{ $row['student_number'] }}" data-full-name="{{ $row['full_name'] }}" data-grade="{{ $row['grade_level'] }}" data-mode="{{ $row['learning_mode'] }}">
                                            <!-- Student ID -->
                                            <td class="px-4 py-3 font-mono font-bold text-slate-700 whitespace-nowrap">
                                                {{ $row['student_number'] }}
                                            </td>

                                            <!-- Full Name -->
                                            <td class="px-4 py-3 font-extrabold text-slate-900 leading-tight">
                                                {{ $row['full_name'] }}
                                            </td>

                                            <!-- Grade Level -->
                                            <td class="px-4 py-3 font-semibold text-slate-500 whitespace-nowrap">
                                                {{ $row['grade_level'] }}
                                            </td>

                                            <!-- Mode -->
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if(str_contains(strtolower($row['learning_mode']), 'face') || str_contains(strtolower($row['learning_mode']), 'f2f'))
                                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-150">
                                                        F2F
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-sky-50 text-sky-700 border border-sky-150">
                                                        ODL
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- CSV Fallback Coverage Badge -->
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if ($row['found_in_csv'])
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase bg-emerald-50 text-emerald-700 border border-emerald-150">
                                                        In CSV ({{ strtoupper($row['csv_type']) }})
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase bg-rose-50 text-rose-700 border border-rose-150">
                                                        Missing
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Remarks input field -->
                                            <td class="px-4 py-3 whitespace-nowrap min-w-[150px]">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="truncate max-w-[120px] font-semibold text-[11px]" :class="remarks ? 'text-indigo-600' : 'text-slate-400 italic'" x-text="remarks || 'No remarks'"></span>
                                                    <button type="button" @click="$dispatch('open-remarks-modal', { studentNumber: '{{ $row['student_number'] }}', studentName: '{{ addslashes($row['full_name']) }}', remarks: remarks })" class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-200 text-slate-500 hover:text-indigo-700 transition cursor-pointer" title="Edit Remarks">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                            <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Action Link -->
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <a href="{{ route('admin.students.show', $row['id']) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 hover:bg-emerald-50 text-slate-500 hover:text-emerald-700 border border-slate-100 hover:border-emerald-200 transition" title="View Student Profile">
                                                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                                                No records found matching filters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: OFFICIAL STUDENT LIST TRACKER -->
                <div class="lg:col-span-7 flex flex-col border-t lg:border-t-0 lg:border-l border-slate-200 lg:pl-6 pt-6 lg:pt-0">
                    <div class="mb-4">
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="h-5 w-5 text-indigo-600"></i>
                            OFFICIAL STUDENT LIST TRACKER
                        </h2>
                        <p class="text-xs text-slate-500 font-medium">Paste the student name list from Excel or type manually to check enrollment records.</p>
                    </div>

                    <!-- Input Form -->
                    <form method="GET" class="mb-4">
                        <div class="space-y-3">
                            <textarea
                                name="official_list"
                                rows="6"
                                placeholder="Pantaon, Jaynissa Catucag&#10;Dipatuan, Muhaina Uy&#10;Salman Espanol&#10;...paste list here (one name per line)"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs text-slate-800 outline-none transition placeholder:text-slate-450 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 resize-y"
                                required
                            >{{ $officialList }}</textarea>
                            
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 active:scale-[0.98] cursor-pointer">
                                    <i data-lucide="play-circle" class="h-4.5 w-4.5"></i>
                                    Track Pasted List
                                </button>
                                @if(!empty($officialList))
                                    <a href="{{ route('admin.students.comparison') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                                        Clear List
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <!-- Matching Results Table -->
                    @if(!empty($officialList))
                        <div class="mb-3 text-xs font-bold text-slate-700">
                            Matching results for {{ count(array_filter(explode("\n", $officialList))) }} names:
                        </div>
                        <div class="overflow-hidden rounded-2xl border border-slate-250 shadow-sm bg-white flex-1">
                            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                                <table class="w-full text-left text-xs relative">
                                    <thead class="bg-indigo-50 text-[9px] uppercase tracking-wider text-indigo-700 border-b border-indigo-100 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-1.5 py-3 bg-indigo-50 font-black text-center w-[70px]">Status</th>
                                            <th class="px-2 py-3 bg-indigo-50 font-black w-[110px]">Official Input Name</th>
                                            <th class="px-2 py-3 bg-indigo-50 font-black w-[130px]">Portal DB Match</th>
                                            <th class="px-1 py-3 bg-indigo-50 font-black text-center w-[40px]">Photo</th>
                                            <th class="px-1 py-3 bg-indigo-50 font-black text-center w-[40px]">LRN</th>
                                            <th class="px-1 py-3 bg-indigo-50 font-black text-center w-[40px]">Parents</th>
                                            <th class="px-1 py-3 bg-indigo-50 font-black text-center w-[40px]">Address</th>
                                            <th class="px-1 py-3 bg-indigo-50 font-black text-center w-[40px]">Docs</th>
                                            <th class="px-2 py-3 bg-indigo-50 font-black text-center w-[90px]">Remarks</th>
                                            <th class="px-1 py-3 bg-indigo-50 font-black text-center w-[40px]">Link</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-150 bg-white">
                                        @foreach ($trackedStudents as $row)
                                            <tr x-data="{ 
                                                checked: {{ $row['found'] && $row['learning_mode'] !== 'Face-to-Face' ? 'true' : ($row['found'] && $row['has_photo'] && $row['has_lrn'] && $row['has_parents'] && $row['has_address'] && $row['has_documents'] ? 'true' : 'false') }}, 
                                                photo: {{ $row['has_photo'] ? 'true' : 'false' }}, 
                                                lrn: {{ $row['has_lrn'] ? 'true' : 'false' }}, 
                                                parents: {{ $row['has_parents'] ? 'true' : 'false' }}, 
                                                address: {{ $row['has_address'] ? 'true' : 'false' }}, 
                                                docs: {{ $row['has_documents'] ? 'true' : 'false' }},
                                                remarks: '{{ addslashes($row['remarks'] ?? '') }}',
                                                updating: false,
                                                toggle(field) {
                                                    if (!{{ $row['found'] ? 'true' : 'false' }}) return;
                                                    if (this.updating) return;
                                                    this.updating = true;
                                                    
                                                    let nextValue;
                                                    if (field === 'status') {
                                                        nextValue = !this.checked;
                                                    } else {
                                                        nextValue = !this[field];
                                                    }

                                                    fetch('{{ route('admin.students.comparison.update-field') }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                        },
                                                        body: JSON.stringify({
                                                            student_number: '{{ $row['student_id'] }}',
                                                            field: field,
                                                            value: nextValue ? 1 : 0
                                                        })
                                                    })
                                                    .then(res => res.json())
                                                    .then(data => {
                                                        this.updating = false;
                                                        if (data.success) {
                                                            if (field === 'status') {
                                                                this.checked = nextValue;
                                                            } else {
                                                                this.photo = data.has_photo;
                                                                this.lrn = data.has_lrn;
                                                                this.parents = data.has_parents;
                                                                this.address = data.has_address;
                                                                this.docs = data.has_documents;

                                                                window.dispatchEvent(new CustomEvent('student-fields-sync', {
                                                                    detail: {
                                                                        studentNumber: '{{ $row['student_id'] }}',
                                                                        has_photo: data.has_photo,
                                                                        has_lrn: data.has_lrn,
                                                                        has_parents: data.has_parents,
                                                                        has_address: data.has_address,
                                                                        has_documents: data.has_documents
                                                                    }
                                                                }));
                                                            }
                                                        }
                                                    })
                                                    .catch(err => {
                                                        this.updating = false;
                                                        console.error(err);
                                                    });
                                                },
                                                updateRemarks() {
                                                    if (this.updating) return;
                                                    this.updating = true;
                                                    fetch('{{ route('admin.students.comparison.update-field') }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                        },
                                                        body: JSON.stringify({
                                                            student_number: '{{ $row['student_id'] }}',
                                                            field: 'remarks',
                                                            value: this.remarks
                                                        })
                                                    })
                                                    .then(res => res.json())
                                                    .then(data => {
                                                        this.updating = false;
                                                        if (data.success) {
                                                            window.dispatchEvent(new CustomEvent('remarks-updated', { 
                                                                detail: { 
                                                                    studentNumber: '{{ $row['student_id'] }}', 
                                                                    remarks: this.remarks 
                                                                } 
                                                            }));
                                                        }
                                                    })
                                                    .catch(err => {
                                                        this.updating = false;
                                                        console.error(err);
                                                    });
                                                }
                                            }" @remarks-updated.window="if ($event.detail.studentNumber === '{{ $row['student_id'] }}') { remarks = $event.detail.remarks; }" class="transition hover:bg-slate-50/50" data-student-number="{{ $row['student_id'] }}" data-full-name="{{ $row['full_name'] }}" data-grade="{{ $row['grade_level'] }}" data-mode="{{ $row['learning_mode'] }}">
                                                <!-- Status Custom Check Button -->
                                                <td class="px-1.5 py-3 whitespace-nowrap w-[70px]">
                                                    <div class="flex items-center gap-1">
                                                        <button type="button" @click="toggle('status')" class="flex items-center justify-center h-4.5 w-4.5 rounded-full border transition cursor-pointer" :class="checked ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-300 text-slate-300'">
                                                            <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" x-show="checked">
                                                                <polyline points="20 6 9 17 4 12"></polyline>
                                                            </svg>
                                                        </button>
                                                        <template x-if="checked">
                                                            <span class="inline-flex px-1 py-0.5 rounded text-[8.5px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-150">
                                                                MATCH
                                                            </span>
                                                        </template>
                                                        <template x-if="!checked">
                                                            <span class="inline-flex px-1 py-0.5 rounded text-[8.5px] font-black uppercase bg-slate-100 text-slate-500 border border-slate-200">
                                                                PEND
                                                            </span>
                                                        </template>
                                                    </div>
                                                </td>

                                                <!-- Pasted Input Name -->
                                                <td class="px-2 py-3 text-slate-600 font-bold max-w-[110px] truncate" title="{{ $row['input_name'] }}">
                                                    {{ $row['input_name'] }}
                                                </td>

                                                <!-- DB Match Details -->
                                                <td class="px-2 py-3 max-w-[130px]">
                                                    @if ($row['found'])
                                                        <div class="font-extrabold text-slate-900 leading-tight truncate" title="{{ $row['full_name'] }}">
                                                            {{ $row['full_name'] }}
                                                        </div>
                                                        <div class="mt-0.5 text-[9px] font-bold text-slate-500 font-mono">
                                                            ID: {{ $row['student_id'] }}
                                                        </div>
                                                    @else
                                                        <span class="inline-flex px-1 py-0.5 rounded text-[8.5px] font-black bg-rose-50 text-rose-600 border border-rose-150 uppercase whitespace-nowrap">
                                                            No Match
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- Photo Status Check -->
                                                <td class="px-1 py-3 text-center whitespace-nowrap w-[40px]">
                                                    @if ($row['found'])
                                                        <button type="button" @click="toggle('photo')" class="inline-flex items-center justify-center h-4.5 w-4.5 rounded-full border transition cursor-pointer text-[9.5px] font-black" :class="photo ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'">
                                                            <span x-text="photo ? '✓' : '✗'"></span>
                                                        </button>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>

                                                <!-- LRN Status Check -->
                                                <td class="px-1 py-3 text-center whitespace-nowrap w-[40px]">
                                                    @if ($row['found'])
                                                        <button type="button" @click="toggle('lrn')" class="inline-flex items-center justify-center h-4.5 w-4.5 rounded-full border transition cursor-pointer text-[9.5px] font-black" :class="lrn ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'">
                                                            <span x-text="lrn ? '✓' : '✗'"></span>
                                                        </button>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>

                                                <!-- Parents Status Check -->
                                                <td class="px-1 py-3 text-center whitespace-nowrap w-[40px]">
                                                    @if ($row['found'])
                                                        <button type="button" @click="toggle('parents')" class="inline-flex items-center justify-center h-4.5 w-4.5 rounded-full border transition cursor-pointer text-[9.5px] font-black" :class="parents ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'">
                                                            <span x-text="parents ? '✓' : '✗'"></span>
                                                        </button>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>

                                                <!-- Address Status Check -->
                                                <td class="px-1 py-3 text-center whitespace-nowrap w-[40px]">
                                                    @if ($row['found'])
                                                        <button type="button" @click="toggle('address')" class="inline-flex items-center justify-center h-4.5 w-4.5 rounded-full border transition cursor-pointer text-[9.5px] font-black" :class="address ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'">
                                                            <span x-text="address ? '✓' : '✗'"></span>
                                                        </button>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>

                                                <!-- Documents Status Check -->
                                                <td class="px-1 py-3 text-center whitespace-nowrap w-[40px]">
                                                    @if ($row['found'])
                                                        <button type="button" @click="toggle('docs')" class="inline-flex items-center justify-center h-4.5 w-4.5 rounded-full border transition cursor-pointer text-[9.5px] font-black" :class="docs ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'">
                                                            <span x-text="docs ? '✓' : '✗'"></span>
                                                        </button>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>

                                                <!-- Remarks Input -->
                                                <td class="px-2 py-3 whitespace-nowrap w-[90px]">
                                                    @if ($row['found'])
                                                        <div class="flex items-center gap-1">
                                                            <span class="truncate max-w-[65px] font-bold text-[9.5px]" :class="remarks ? 'text-indigo-650 font-black' : 'text-slate-400 italic'" x-text="remarks || 'None'"></span>
                                                            <button type="button" @click="$dispatch('open-remarks-modal', { studentNumber: '{{ $row['student_id'] }}', studentName: '{{ addslashes($row['full_name']) }}', remarks: remarks })" class="inline-flex h-5 w-5 items-center justify-center rounded bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-200 text-slate-500 hover:text-indigo-700 transition cursor-pointer" title="Edit Remarks">
                                                                <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                    <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>

                                                <!-- Link Action -->
                                                <td class="px-1 py-3 text-center whitespace-nowrap w-[40px]">
                                                    @if ($row['found'])
                                                        <a href="{{ $row['details_url'] }}" target="_blank" class="inline-flex h-5.5 w-5.5 items-center justify-center rounded bg-slate-50 hover:bg-indigo-50 text-slate-500 hover:text-indigo-700 border border-slate-100 hover:border-indigo-200 transition" title="Go to Details Page">
                                                            <i data-lucide="external-link" class="h-2.5 w-2.5"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <!-- Empty Guidance -->
                        <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center bg-slate-50/50">
                            <i data-lucide="clipboard-signature" class="h-8 w-8 text-slate-350 mx-auto mb-2"></i>
                            <div class="text-xs font-bold text-slate-500">Official Student List Tracker</div>
                            <div class="text-[10px] text-slate-400 mt-1 max-w-[280px] mx-auto leading-relaxed">
                                Paste names above (e.g. from Excel spreadsheets) and click "Track Pasted List" to compare them side-by-side with official database records.
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            <!-- REMARKS & REMINDERS TRACKER SECTION -->
            <div x-data="{
                reminders: @js($remindersList),
                init() {
                    window.addEventListener('remarks-updated', (e) => {
                        let studentNumber = e.detail.studentNumber;
                        let remarks = e.detail.remarks;

                        let idx = this.reminders.findIndex(r => r.student_number === studentNumber);
                        if (remarks.trim() === '') {
                            if (idx !== -1) {
                                this.reminders.splice(idx, 1);
                            }
                        } else {
                            if (idx !== -1) {
                                this.reminders[idx].remarks = remarks;
                            } else {
                                // Find name/grade from row element data attributes
                                let rowEl = document.querySelector('[data-student-number=\'' + studentNumber + '\']');
                                let fullName = rowEl ? rowEl.dataset.fullName : 'STUDENT';
                                let grade = rowEl ? rowEl.dataset.grade : 'N/A';
                                let mode = rowEl ? rowEl.dataset.mode : 'Face-to-Face';

                                this.reminders.push({
                                    student_number: studentNumber,
                                    full_name: fullName,
                                    grade_level: grade,
                                    learning_mode: mode,
                                    remarks: remarks,
                                    details_url: '/admin/students/comparison?search=' + studentNumber,
                                    has_photo: false,
                                    has_lrn: false,
                                    has_parents: false,
                                    has_address: false,
                                    has_documents: false
                                });
                            }
                        }
                    });

                    window.addEventListener('student-fields-sync', (e) => {
                        let studentNumber = e.detail.studentNumber;
                        let idx = this.reminders.findIndex(r => r.student_number === studentNumber);
                        if (idx !== -1) {
                            this.reminders[idx].has_photo = e.detail.has_photo;
                            this.reminders[idx].has_lrn = e.detail.has_lrn;
                            this.reminders[idx].has_parents = e.detail.has_parents;
                            this.reminders[idx].has_address = e.detail.has_address;
                            this.reminders[idx].has_documents = e.detail.has_documents;
                        }
                    });
                },
                updateRemarks(item) {
                    fetch('{{ route('admin.students.comparison.update-field') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            student_number: item.student_number,
                            field: 'remarks',
                            value: item.remarks
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (item.remarks.trim() === '') {
                            let idx = this.reminders.findIndex(r => r.student_number === item.student_number);
                            if (idx !== -1) this.reminders.splice(idx, 1);
                        }
                        window.dispatchEvent(new CustomEvent('remarks-updated', {
                            detail: {
                                studentNumber: item.student_number,
                                remarks: item.remarks
                            }
                        }));
                    });
                }
            }" class="mt-8 border-t border-slate-200 pt-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <i data-lucide="bell-ring" class="h-5 w-5 text-indigo-600"></i>
                            REMARKS & REMINDERS TRACKER
                        </h2>
                        <p class="text-xs text-slate-500 font-medium">Summary list of students with notes, spelling corrections, or missing items. No emails will be sent automatically.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 border border-indigo-150">
                        <span x-text="reminders.length"></span> Reminders
                    </span>
                </div>

                <!-- Summary Table -->
                <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white">
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                        <table class="w-full text-left text-xs relative">
                            <thead class="bg-indigo-50/50 text-[10px] uppercase tracking-wider text-slate-600 border-b border-slate-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 font-black">ID</th>
                                    <th class="px-4 py-3 font-black">Full Name</th>
                                    <th class="px-4 py-3 font-black">Grade & Mode</th>
                                    <th class="px-4 py-3 font-black">Photo</th>
                                    <th class="px-4 py-3 font-black">LRN</th>
                                    <th class="px-4 py-3 font-black">Parents</th>
                                    <th class="px-4 py-3 font-black">Address</th>
                                    <th class="px-4 py-3 font-black">Docs</th>
                                    <th class="px-4 py-3 font-black">Remarks / Reminders</th>
                                    <th class="px-4 py-3 font-black text-center w-24">Link</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-150 bg-white">
                                <template x-for="item in reminders" :key="item.student_number">
                                    <tr class="transition hover:bg-slate-50/50">
                                        <!-- ID -->
                                        <td class="px-4 py-3 font-mono font-bold text-slate-700 whitespace-nowrap" x-text="item.student_number"></td>
                                        
                                        <!-- Full Name -->
                                        <td class="px-4 py-3 font-extrabold text-slate-900 leading-tight" x-text="item.full_name"></td>
                                        
                                        <!-- Grade & Mode -->
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="text-slate-500 font-semibold" x-text="item.grade_level"></span>
                                            <span class="ml-1 text-[9px] font-black uppercase px-1 rounded bg-slate-50 border border-slate-150 text-slate-600" x-text="item.learning_mode.includes('face') || item.learning_mode.includes('F2F') ? 'F2F' : 'ODL'"></span>
                                        </td>
                                        
                                        <!-- Photo -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] font-black border" :class="item.has_photo ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'" x-text="item.has_photo ? '✓' : '✗'"></span>
                                        </td>

                                        <!-- LRN -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] font-black border" :class="item.has_lrn ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'" x-text="item.has_lrn ? '✓' : '✗'"></span>
                                        </td>

                                        <!-- Parents -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] font-black border" :class="item.has_parents ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'" x-text="item.has_parents ? '✓' : '✗'"></span>
                                        </td>

                                        <!-- Address -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] font-black border" :class="item.has_address ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'" x-text="item.has_address ? '✓' : '✗'"></span>
                                        </td>

                                        <!-- Docs -->
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full text-[10px] font-black border" :class="item.has_documents ? 'bg-emerald-50 text-emerald-600 border-emerald-150' : 'bg-rose-50 text-rose-600 border-rose-150'" x-text="item.has_documents ? '✓' : '✗'"></span>
                                        </td>

                                        <!-- Remarks/Notes input -->
                                        <td class="px-4 py-3 whitespace-nowrap min-w-[200px]">
                                            <div class="flex items-center gap-1.5">
                                                <span class="truncate max-w-[150px] font-semibold text-[11px] text-indigo-650" x-text="item.remarks"></span>
                                                <button type="button" @click="$dispatch('open-remarks-modal', { studentNumber: item.student_number, studentName: item.full_name, remarks: item.remarks })" class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-50 hover:bg-indigo-50 border border-slate-100 hover:border-indigo-200 text-slate-500 hover:text-indigo-700 transition cursor-pointer" title="Edit Remarks">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Actions -->
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <a :href="item.details_url" target="_blank" class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 hover:bg-indigo-50 text-slate-500 hover:text-indigo-700 border border-slate-100 hover:border-indigo-200 transition" title="Go to Details Page">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                                    <polyline points="15 3 21 3 21 9"></polyline>
                                                    <line x1="10" y1="14" x2="21" y2="3"></line>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="reminders.length === 0">
                                    <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-400">
                                        No active reminders. Add a remark to any student above to track them here.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- GLOBAL REMARKS MODAL -->
    <div 
        x-data="{ 
            open: false, 
            studentNumber: '', 
            studentName: '', 
            remarks: '',
            init() {
                window.addEventListener('open-remarks-modal', (e) => {
                    this.studentNumber = e.detail.studentNumber;
                    this.studentName = e.detail.studentName;
                    this.remarks = e.detail.remarks;
                    this.open = true;
                });
            },
            saveRemarks() {
                fetch('{{ route('admin.students.comparison.update-field') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        student_number: this.studentNumber,
                        field: 'remarks',
                        value: this.remarks
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.dispatchEvent(new CustomEvent('remarks-updated', { 
                            detail: { 
                                studentNumber: this.studentNumber, 
                                remarks: this.remarks 
                            } 
                        }));
                    }
                    this.open = false;
                });
            }
        }"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
        x-cloak
        style="display: none;"
    >
        <div 
            class="bg-white rounded-3xl border border-slate-150 max-w-md w-full p-6 shadow-2xl animate-in fade-in zoom-in-95 duration-200"
            @click.outside="open = false"
        >
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-605" x-text="'ID: ' + studentNumber"></span>
                    <h3 class="text-sm font-extrabold text-slate-950 mt-0.5" x-text="studentName"></h3>
                </div>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-650 transition cursor-pointer">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="mb-5">
                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Quick Remarks Templates (Auto-Save)</label>
                <div class="flex flex-wrap gap-1.5 mb-4">
                    <button type="button" @click="remarks = 'verified official'; saveRemarks();" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 hover:border-emerald-300 text-[10.5px] font-bold text-emerald-800 transition cursor-pointer">
                        VERIFIED (PERMANENT ACCOUNT)
                    </button>
                    <button type="button" @click="remarks = 'Missing: 2x2 Photo'; saveRemarks();" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-[10.5px] font-bold text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                        NO PROFILE PICTURE
                    </button>
                    <button type="button" @click="remarks = 'Missing: Birth Certificate / Report Card'; saveRemarks();" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-[10.5px] font-bold text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                        NO DOCUMENT
                    </button>
                    <button type="button" @click="remarks = 'Missing Payment Proof'; saveRemarks();" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-[10.5px] font-bold text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                        NO PAYMENT PROOF
                    </button>
                    <button type="button" @click="remarks = 'Wrong Spelling Name'; saveRemarks();" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-[10.5px] font-bold text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                        WRONG SPELLING
                    </button>
                    <button type="button" @click="remarks = 'Missing/Check LRN'; saveRemarks();" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 text-[10.5px] font-bold text-slate-700 hover:text-indigo-700 transition cursor-pointer">
                        CHECK LRN
                    </button>
                </div>

                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Remarks / Reminders Notes</label>
                <textarea 
                    x-model="remarks" 
                    rows="3" 
                    class="w-full rounded-2xl border border-slate-250 px-4 py-3 text-xs outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 resize-none font-semibold text-slate-800"
                    placeholder="e.g. wrong spelling name, missing photo, check LRN etc..."
                ></textarea>
            </div>

            <div class="flex gap-2">
                <button type="button" @click="open = false" class="flex-1 h-10 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" @click="saveRemarks()" class="flex-1 h-10 rounded-xl bg-indigo-650 text-xs font-bold text-white hover:bg-indigo-700 transition cursor-pointer">
                    Save Remarks
                </button>
            </div>
        </div>
    </div>
</x-admin-layout>
