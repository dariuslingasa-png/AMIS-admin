@php
    $teacherEditorPayload = function (array $teacher) {
        $initials = collect(explode(' ', str_replace(['Ust. ', 'Tchr. ', 'TEACHER '], '', $teacher['name'])))
            ->filter()
            ->map(fn ($part) => substr($part, 0, 1))
            ->take(2)
            ->implode('');
        $photoPath = $teacher['photo'] ?? null;
        $hasPhoto = !empty($photoPath);

        return [
            'id' => $teacher['id'],
            'name' => $teacher['name'],
            'first_name' => $teacher['first_name'] ?? '',
            'middle_name' => $teacher['middle_name'] ?? '',
            'last_name' => $teacher['last_name'] ?? '',
            'gender' => $teacher['gender'] ?? 'Male',
            'birthdate' => $teacher['birthdate'] ?? '',
            'contact_number' => $teacher['contact_number'] ?? '',
            'address' => $teacher['address'] ?? '',
            'email' => $teacher['email'],
            'dept' => $teacher['dept'],
            'sections' => $teacher['sections'],
            'status' => $teacher['status'],
            'license' => $teacher['license'] ?? 'faculty_a1',
            'microsoft_sync' => (bool) ($teacher['microsoft_sync'] ?? true),
            'subjects' => $teacher['subjects'] ?? [],
            'subject_options' => $teacher['subject_options'] ?? [],
            'subject_count' => $teacher['subject_count'] ?? 0,
            'load_target' => $teacher['load_target'] ?? 8,
            'load_percent' => $teacher['load_percent'] ?? 0,
            'load_status' => $teacher['load_status'] ?? 'Needs Load',
            'initials' => $initials,
            'photoUrl' => $hasPhoto ? asset($photoPath) : null,
        ];
    };

    $teacherEditorPayloadAttribute = fn (array $teacher) => base64_encode(json_encode($teacherEditorPayload($teacher)));

    if (! $selectedTeacher && old('id')) {
        $selectedTeacher = collect($teachers)->firstWhere('id', old('id'));
    }

    $selectedTeacherPayload = $selectedTeacher ? $teacherEditorPayload($selectedTeacher) : null;

    $blankTeacherPayload = [
        'id' => '',
        'name' => '',
        'first_name' => '',
        'middle_name' => '',
        'last_name' => '',
        'gender' => 'Male',
        'birthdate' => '',
        'contact_number' => '',
        'address' => '',
        'email' => '',
        'dept' => '',
        'sections' => '',
        'status' => 'Active',
        'license' => 'faculty_a1',
        'microsoft_sync' => true,
        'subjects' => [],
        'subject_options' => [],
        'subject_count' => 0,
        'load_target' => 8,
        'load_percent' => 0,
        'load_status' => 'Needs Load',
        'initials' => '',
        'photoUrl' => null,
    ];

    $createTeacherModalOpen = $errors->any() && old('form') === 'create';

    if ($errors->any() && old('form') !== 'create') {
        $selectedTeacherPayload = array_merge($selectedTeacherPayload ?? $blankTeacherPayload, [
            'id' => old('id', $selectedTeacherPayload['id'] ?? ''),
            'name' => old('name', $selectedTeacherPayload['name'] ?? ''),
            'email' => old('email', $selectedTeacherPayload['email'] ?? ''),
            'dept' => old('dept', $selectedTeacherPayload['dept'] ?? ''),
            'sections' => old('sections', $selectedTeacherPayload['sections'] ?? ''),
            'status' => old('status', $selectedTeacherPayload['status'] ?? 'Active'),
            'microsoft_sync' => old('form') ? (old('microsoft_sync') !== null) : ($selectedTeacherPayload['microsoft_sync'] ?? true),
        ]);
    }
@endphp

<x-admin-layout title="Teachers Workspace">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        viewMode: 'card',
        search: '',
        addModal: @js($createTeacherModalOpen),
        showSkeleton: false,
        isRegistering: false,
        editModal: @js((bool) $selectedTeacherPayload && ! $createTeacherModalOpen),
        isSavingTeacher: false,
        photoPreview: null,
        editTeacher: @js($selectedTeacherPayload ?? $blankTeacherPayload),
        viewModalOpen: false,
        viewTeacher: @js($blankTeacherPayload),
        registerEmail: @js(old('form') === 'create' ? old('email', '') : ''),
        registerName: @js(old('form') === 'create' ? old('name', '') : ''),
        isEmailValid(email) {
            if (!email) return false;
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email) && email.toLowerCase().endsWith('@amis.edu.ph');
        },
        suggestEmail(name) {
            if (!name) return '';
            let clean = name.replace(/^(teacher|ust\.|ustadz\.?|ustadh\.?|sir\.?|ma'am\.?|maam\.?|ms\.?|mrs\.?|mr\.?)\s+/i, '');
            clean = clean.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            clean = clean.toLowerCase().replace(/[^a-z\s]/g, '').trim().replace(/\s+/g, ' ');
            let parts = clean.split(' ');
            if (parts.length >= 2) {
                return 'tr.' + parts[0].substring(0, 1) + parts[parts.length - 1] + '@amis.edu.ph';
            }
            return clean ? 'tr.' + clean + '@amis.edu.ph' : '';
        },
        triggerSearch(val) {
            this.showSkeleton = true;
            setTimeout(() => { this.showSkeleton = false; }, 300);
        },
        clearTeacherPhotoPreview() {
            if (this.photoPreview) {
                URL.revokeObjectURL(this.photoPreview);
            }
            this.photoPreview = null;
        },
        openTeacherEditor(teacher) {
            this.clearTeacherPhotoPreview();
            this.isSavingTeacher = false;
            this.editTeacher = { ...teacher };
            this.editModal = true;
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        openTeacherEditorPayload(payload) {
            this.openTeacherEditor(JSON.parse(atob(payload)));
        },
        openTeacherViewer(teacher) {
            this.viewTeacher = { ...teacher };
            this.viewModalOpen = true;
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        openTeacherViewerPayload(payload) {
            this.openTeacherViewer(JSON.parse(atob(payload)));
        },
        openTeacherCreator() {
            this.clearTeacherPhotoPreview();
            this.registerEmail = '';
            this.registerName = '';
            this.isRegistering = false;
            this.addModal = true;
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        closeTeacherCreator() {
            if (this.isRegistering) return;
            this.clearTeacherPhotoPreview();
            this.addModal = false;
        },
        closeTeacherEditor() {
            if (this.isSavingTeacher) return;
            this.clearTeacherPhotoPreview();
            this.editModal = false;
        },
        previewTeacherPhoto(event) {
            const file = event.target.files?.[0];
            this.clearTeacherPhotoPreview();
            this.photoPreview = file ? URL.createObjectURL(file) : null;
        }
    }">
        <!-- Hero / Header Banner -->
        <div class="academic-hero-banner">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Teachers & Faculty</h1>
                    <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                        Monitor teaching workload distributions, map course assignments, and view the faculty directory.
                    </p>
                </div>
                <button type="button" @click="openTeacherCreator()" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-indigo-700"></i>
                    Register Teacher
                </button>
            </div>
        </div>

        @php
            $activeCount = collect($teachers)->where('status', 'Active')->count();
            $inactiveCount = count($teachers) - $activeCount;
            $islamicStaff = collect($teachers)->where('dept', 'Islamic School and Arabic Language Department')->count();
            $acadStaff = count($teachers) - $islamicStaff;
            $averageSubjectLoad = count($teachers) ? round(collect($teachers)->avg('subject_count'), 1) : 0;
        @endphp

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 shadow-3xs">
                {{ session('status') }}
            </div>
        @endif

        @if (session('temp_credentials'))
            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5 shadow-3xs mt-4">
                <h4 class="text-sm font-black text-amber-900 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i data-lucide="key" class="w-4 h-4 text-amber-700"></i>
                    Microsoft Account Generated
                </h4>
                <div class="space-y-1.5 text-xs font-semibold text-amber-800">
                    <p>A new Microsoft 365 and Teacher Portal account has been automatically provisioned and licensed.</p>
                    <div class="bg-white border border-amber-100/50 rounded-xl p-3.5 mt-3 space-y-2 select-all font-mono">
                        <div><span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-0.5">School Email</span> {{ session('temp_credentials')['email'] }}</div>
                        <div><span class="text-slate-400 font-bold uppercase tracking-wider text-[10px] block mb-0.5">Temporary Password</span> {{ session('temp_credentials')['password'] }}</div>
                    </div>
                    <p class="mt-3 text-[11px] text-amber-700/80">Please copy and send these temporary credentials securely to the teacher. They will be prompted to change their password upon first login.</p>
                </div>
            </div>
        @endif

        <!-- Telemetry Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-purple-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Active Faculty</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-purple-50 text-purple-650 group-hover:scale-110 transition-transform">
                        <i data-lucide="users" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-900 group-hover:text-purple-655 transition-colors">{{ $activeCount }}</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Currently teaching</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-emerald-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Islamic & Arabic</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-655 group-hover:scale-110 transition-transform">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-emerald-700 group-hover:text-emerald-650 transition-colors">{{ $islamicStaff }}</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Arabic & IS specialists</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-blue-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">General Academics</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-655 group-hover:scale-110 transition-transform">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-blue-700 group-hover:text-blue-650 transition-colors">{{ $acadStaff }}</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Elementary academics</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-150 p-5 shadow-xs hover:shadow-md transition-all duration-200 hover:-translate-y-1 relative overflow-hidden group border-t-4 border-t-amber-500">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-gray-400 text-xs tracking-wider uppercase">Average Subject Load</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-50 text-amber-655 group-hover:scale-110 transition-transform">
                        <i data-lucide="activity" class="w-4 h-4"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="text-2xl md:text-3xl font-extrabold text-gray-900 group-hover:text-amber-650 transition-colors">{{ $averageSubjectLoad }} / 8</span>
                    <p class="text-[11px] text-gray-400 mt-1 font-medium">Target subject load</p>
                </div>
            </div>
        </div>

        <!-- Faculty Roster + Subject Load Tracking -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-xs flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="relative w-full sm:max-w-xs">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </span>
                    <input type="search" x-model="search" @input="triggerSearch($event.target.value)" placeholder="Search teacher by name..." class="w-full bg-gray-50 border border-gray-200 text-slate-900 text-sm rounded-xl pl-10 pr-4 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all duration-150">
                </div>
                <div class="inline-flex w-full md:w-auto gap-1.5 rounded-2xl border border-slate-200 bg-slate-100 p-1 shadow-3xs">
                    <button type="button"
                        @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2 text-xs uppercase tracking-wider transition">
                        <i data-lucide="list" class="w-3.5 h-3.5"></i>
                        List
                    </button>
                    <button type="button"
                        @click="viewMode = 'card'"
                        :class="viewMode === 'card' ? 'bg-white text-indigo-800 shadow-sm font-black' : 'text-slate-500 hover:text-slate-900 font-bold'"
                        class="flex-1 md:flex-none inline-flex items-center justify-center gap-1.5 rounded-xl px-4 py-2 text-xs uppercase tracking-wider transition">
                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                        Card
                    </button>
                </div>
            </div>

            <div x-show="viewMode === 'list'" x-transition.opacity class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
                <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between">
                    <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Staff Roster</span>
                    <x-badge color="purple">{{ count($teachers) }} Registered</x-badge>
                </div>
                <div class="premium-table-wrap">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Teacher Name</th>
                                <th>School Email</th>
                                <th>Department</th>
                                <th>Assigned Class Section</th>
                                <th>Status</th>
                                <th>MS365 Sync</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Skeletons -->
                            <template x-if="showSkeleton">
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full skeleton-box shrink-0"></div>
                                            <div class="h-4 w-32 skeleton-box"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><div class="h-3.5 w-44 skeleton-box"></div></td>
                                    <td class="px-6 py-4"><div class="h-3.5 w-28 skeleton-box"></div></td>
                                    <td class="px-6 py-4"><div class="h-5 w-24 skeleton-box"></div></td>
                                    <td class="px-6 py-4"><div class="h-5 w-16 skeleton-box"></div></td>
                                    <td class="px-6 py-4"><div class="h-5 w-24 skeleton-box"></div></td>
                                    <td class="px-6 py-4 text-right"><div class="inline-block h-8 w-24 skeleton-box"></div></td>
                                </tr>
                            </template>
                            <!-- Real Data -->
                            @foreach ($teachers as $t)
                                @php
                                    $initials = collect(explode(' ', str_replace(['Ust. ', 'Tchr. ', 'TEACHER '], '', $t['name'])))
                                        ->filter()
                                        ->map(fn($part) => substr($part, 0, 1))
                                        ->take(2)
                                        ->implode('');
                                    $editPayload = $teacherEditorPayloadAttribute($t);
                                @endphp
                                <tr x-show="!showSkeleton && (search === '' || '{{ strtolower($t['name']) }}'.includes(search.toLowerCase()))">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-655 font-black text-xxs flex items-center justify-center shrink-0 shadow-3xs">
                                                {{ $initials }}
                                            </div>
                                            <span class="font-extrabold text-slate-900 text-sm tracking-wide uppercase">{{ $t['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-xs font-semibold font-mono text-slate-500">{{ $t['email'] }}</td>
                                    <td class="text-xs font-semibold text-slate-500">{{ $t['dept'] }}</td>
                                    <td>
                                        <span class="inline-flex rounded bg-slate-50 border border-slate-150 px-2 py-0.5 text-xs font-bold text-slate-700 shadow-3xs">{{ $t['sections'] }}</span>
                                    </td>
                                    <td>
                                        <x-badge color="{{ $t['status'] === 'Active' ? 'green' : 'gray' }}">{{ Str::upper($t['status']) }}</x-badge>
                                    </td>
                                    <td>
                                        @if ($t['microsoft_sync'] ?? true)
                                            <x-badge color="green">Sync Active</x-badge>
                                        @else
                                            <x-badge color="gray">Disabled</x-badge>
                                        @endif
                                    </td>
                                    <td style="text-align: right;" class="space-x-1.5">
                                        <button type="button" data-teacher="{{ $editPayload }}" @click.prevent="openTeacherViewerPayload($el.dataset.teacher)" class="inline-flex px-3 py-1.5 text-xxs font-bold text-indigo-700 hover:bg-indigo-50 rounded-lg border border-indigo-150 transition cursor-pointer shadow-3xs">View</button>
                                        <a href="{{ route('admin.academic.teachers', ['edit' => $t['id']]) }}" data-teacher="{{ $editPayload }}" @click.prevent="openTeacherEditorPayload($el.dataset.teacher)" class="inline-flex px-3 py-1.5 text-xxs font-bold text-slate-700 hover:bg-slate-100 rounded-lg border border-slate-200 transition cursor-pointer shadow-3xs">Edit</a>
                                        <form method="POST" action="{{ route('admin.academic.teachers.destroy', $t['id']) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this teacher? This will permanently delete their account, subject assignments, and portal access.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex px-3 py-1.5 text-xxs font-bold text-rose-700 hover:bg-rose-50 rounded-lg border border-rose-150 transition cursor-pointer shadow-3xs">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div x-show="viewMode === 'card'" x-transition.opacity class="space-y-5">
                @foreach ($teacherGroups as $group)
                    <section class="rounded-2xl border border-slate-150 bg-white p-4 shadow-3xs">
                        <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-black uppercase tracking-wide text-slate-900">{{ $group['name'] }}</h2>
                                <p class="mt-0.5 text-xs font-semibold text-slate-500">{{ $group['teachers']->count() }} teacher{{ $group['teachers']->count() === 1 ? '' : 's' }}</p>
                            </div>
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-xs font-black text-slate-700 ring-1 ring-slate-150">
                                {{ $group['teachers']->count() }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            @forelse ($group['teachers'] as $t)
                                @php
                                    $initials = collect(explode(' ', str_replace(['Ust. ', 'Tchr. ', 'TEACHER '], '', $t['name'])))
                                        ->filter()
                                        ->map(fn($part) => substr($part, 0, 1))
                                        ->take(2)
                                        ->implode('');
                                    $isHighSchool = str_contains($t['dept'], 'High');
                                    $isIslamicArabic = str_contains($t['dept'], 'Islamic School');
                                    $photoPath = $t['photo'] ?? null;
                                    $hasPhoto = !empty($photoPath);
                                    $editPayload = $teacherEditorPayloadAttribute($t);
                                @endphp
                                <article
                                    x-show="!showSkeleton && (search === '' || '{{ strtolower($t['name']) }}'.includes(search.toLowerCase()))"
                                    @class([
                                        'group flex min-h-[168px] overflow-hidden rounded-2xl border border-slate-150 bg-white shadow-3xs transition-colors hover:shadow-sm',
                                        'hover:border-indigo-200' => $isHighSchool,
                                        'hover:border-amber-200' => $isIslamicArabic,
                                        'hover:border-emerald-200' => ! $isHighSchool && ! $isIslamicArabic,
                                    ])>
                                    <div class="relative w-32 shrink-0 overflow-hidden bg-slate-100 sm:w-40" x-data="{ imgLoaded: false, imgError: false }">
                                        @if ($hasPhoto)
                                            <div x-show="!imgLoaded && !imgError" class="absolute inset-0 animate-pulse bg-slate-200"></div>
                                            <div x-show="!imgError" class="h-full w-full">
                                                <img
                                                    src="{{ asset(\App\Support\ImageHelper::thumb($photoPath, 'medium')) }}"
                                                    alt="{{ $t['name'] }}"
                                                    class="h-full w-full object-cover object-center transition-opacity duration-300"
                                                    :class="imgLoaded ? 'opacity-100' : 'opacity-0'"
                                                    @load="imgLoaded = true"
                                                    x-on:error="imgError = true"
                                                    loading="lazy"
                                                >
                                            </div>
                                            <div x-show="imgError" class="absolute inset-0">
                                                <div @class([
                                                    'flex h-full w-full items-center justify-center text-3xl font-black',
                                                    'bg-indigo-50 text-indigo-700' => $isHighSchool,
                                                    'bg-amber-50 text-amber-700' => $isIslamicArabic,
                                                    'bg-emerald-50 text-emerald-700' => ! $isHighSchool && ! $isIslamicArabic,
                                                ])>
                                                    {{ $initials }}
                                                </div>
                                            </div>
                                        @else
                                            <div @class([
                                                'flex h-full w-full items-center justify-center text-3xl font-black',
                                                'bg-indigo-50 text-indigo-700' => $isHighSchool,
                                                'bg-amber-50 text-amber-700' => $isIslamicArabic,
                                                'bg-emerald-50 text-emerald-700' => ! $isHighSchool && ! $isIslamicArabic,
                                            ])>
                                                {{ $initials }}
                                            </div>
                                        @endif
                                        <div class="absolute inset-y-0 right-0 w-12 bg-gradient-to-r from-transparent to-white"></div>
                                    </div>

                                    <div class="flex min-w-0 flex-1 flex-col justify-between p-4 pl-2 sm:p-5 sm:pl-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <h3 class="truncate text-sm font-extrabold text-slate-900 sm:text-base uppercase">{{ $t['name'] }}</h3>
                                                <p class="mt-1 truncate text-xs font-semibold text-slate-500">{{ $t['email'] }}</p>
                                            </div>
                                            <x-badge color="{{ $t['status'] === 'Active' ? 'green' : 'gray' }}">{{ Str::upper($t['status']) }}</x-badge>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-3 border-t border-slate-100 pt-4 sm:grid-cols-3">
                                            <div class="min-w-0">
                                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Department</span>
                                                <span class="mt-1 block truncate text-xs font-bold text-slate-800">{{ $t['dept'] }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Assignment</span>
                                                <span class="mt-1 inline-flex max-w-full rounded bg-slate-50 border border-slate-150 px-2 py-0.5 text-xs font-bold text-slate-700 shadow-3xs">{{ $t['sections'] }}</span>
                                            </div>
                                            <div class="min-w-0">
                                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">MS365 Sync</span>
                                                <span class="mt-1 block">
                                                    @if ($t['microsoft_sync'] ?? true)
                                                        <x-badge color="green">Sync Active</x-badge>
                                                    @else
                                                        <x-badge color="gray">Disabled</x-badge>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <span class="block text-[10px] font-extrabold uppercase tracking-wider text-indigo-400">Subject Load</span>
                                                    <strong class="mt-1 block text-sm font-black text-indigo-950">
                                                        {{ $t['subject_count'] ?? 0 }} / {{ $t['load_target'] ?? 8 }} subjects
                                                    </strong>
                                                </div>
                                                <span class="inline-flex rounded-full border border-indigo-100 bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-indigo-700">
                                                    {{ $t['load_status'] ?? 'Needs Load' }}
                                                </span>
                                            </div>
                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-white ring-1 ring-indigo-100">
                                                <div class="h-full rounded-full bg-indigo-600" style="width: {{ $t['load_percent'] ?? 0 }}%"></div>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach(array_slice($t['subjects'] ?? [], 0, 4) as $subjectName)
                                                    @php
                                                        $subjectLower = strtolower($subjectName);
                                                        $badgeColor = 'bg-emerald-50 text-emerald-700 border-emerald-100 ring-emerald-100';
                                                        if (str_contains($subjectLower, 'qur')) {
                                                            $badgeColor = 'bg-sky-50 text-sky-700 border-sky-100 ring-sky-100';
                                                        } elseif (str_contains($subjectLower, 'hadith')) {
                                                            $badgeColor = 'bg-amber-50 text-amber-700 border-amber-100 ring-amber-100';
                                                        } elseif (str_contains($subjectLower, 'arabic')) {
                                                            $badgeColor = 'bg-pink-50 text-pink-700 border-pink-100 ring-pink-100';
                                                        } elseif (str_contains($subjectLower, 'recess')) {
                                                            $badgeColor = 'bg-rose-50 text-rose-700 border-rose-100 ring-rose-100';
                                                        } elseif (str_contains($subjectLower, 'meeting') || str_contains($subjectLower, 'circle') || str_contains($subjectLower, 'wrap')) {
                                                            $badgeColor = 'bg-violet-50 text-violet-700 border-violet-100 ring-violet-100';
                                                        }
                                                    @endphp
                                                    <span class="rounded-lg px-2 py-1 text-[10px] font-bold ring-1 uppercase tracking-wide {{ $badgeColor }}">{{ $subjectName }}</span>
                                                @endforeach
                                                @if(($t['subject_count'] ?? 0) > 4)
                                                    <button type="button" data-teacher="{{ $editPayload }}" @click.prevent="openTeacherViewerPayload($el.dataset.teacher)" class="rounded-lg bg-indigo-100 px-2 py-1 text-[10px] font-black text-indigo-700 hover:bg-indigo-200 transition cursor-pointer select-none">+{{ ($t['subject_count'] ?? 0) - 4 }}</button>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="mt-4 flex justify-end gap-1.5">
                                            <a href="{{ route('admin.academic.teachers.view', $t['id']) }}#subject-load" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xxs font-bold text-emerald-700 hover:bg-emerald-50 rounded-lg border border-emerald-150 transition cursor-pointer shadow-3xs">
                                                <i data-lucide="activity" class="h-3.5 w-3.5"></i>
                                                Subject Load
                                            </a>
                                            <button type="button" data-teacher="{{ $editPayload }}" @click.prevent="openTeacherViewerPayload($el.dataset.teacher)" class="inline-flex px-3 py-1.5 text-xxs font-bold text-indigo-700 hover:bg-indigo-50 rounded-lg border border-indigo-150 transition cursor-pointer shadow-3xs">View</button>
                                            <a href="{{ route('admin.academic.teachers', ['edit' => $t['id']]) }}" data-teacher="{{ $editPayload }}" @click.prevent="openTeacherEditorPayload($el.dataset.teacher)" class="inline-flex px-3 py-1.5 text-xxs font-bold text-slate-700 hover:bg-slate-100 rounded-lg border border-slate-200 transition cursor-pointer shadow-3xs">Edit</a>
                                            <form method="POST" action="{{ route('admin.academic.teachers.destroy', $t['id']) }}" onsubmit="return confirm('Are you sure you want to delete this teacher? This will permanently delete their account, subject assignments, and portal access.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex px-3 py-1.5 text-xxs font-bold text-rose-700 hover:bg-rose-50 rounded-lg border border-rose-150 transition cursor-pointer shadow-3xs">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-xs font-bold text-slate-500">
                                    No teachers listed yet.
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

        <!-- Edit Teacher Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="editModal" @click.self="closeTeacherEditor()" @keydown.escape.window="closeTeacherEditor()" @if (! $selectedTeacherPayload) x-cloak style="display: none;" @endif x-transition>
            <form method="POST" action="{{ route('admin.academic.teachers.update') }}" enctype="multipart/form-data" class="admin-modal-card flex max-h-[92vh] w-full max-w-2xl flex-col gap-4 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl" @submit="isSavingTeacher = true">
                @csrf
                @method('PATCH')
                <input type="hidden" name="form" value="edit">
                <input type="hidden" name="id" value="{{ old('id', $selectedTeacherPayload['id'] ?? '') }}" x-model="editTeacher.id">

                <div class="admin-modal-header flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <span class="admin-modal-title text-base font-extrabold text-slate-950">Edit Teacher Profile</span>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500" x-text="editTeacher.name"></p>
                    </div>
                    <a href="{{ route('admin.academic.teachers') }}" class="text-xl font-bold text-slate-400 hover:text-slate-655" @click.prevent="closeTeacherEditor()">&times;</a>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-bold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="grid gap-5 md:grid-cols-[180px_1fr]">
                    <div class="space-y-3">
                        <div class="relative h-56 overflow-hidden rounded-2xl border border-slate-150 bg-slate-50">
                            <template x-if="photoPreview || editTeacher.photoUrl">
                                <img :src="photoPreview || editTeacher.photoUrl" alt="" class="h-full w-full object-cover object-center">
                            </template>
                            <template x-if="!photoPreview && !editTeacher.photoUrl">
                                <div class="flex h-full w-full items-center justify-center bg-emerald-50 text-4xl font-black text-emerald-700" x-text="editTeacher.initials"></div>
                            </template>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/55 to-transparent p-3">
                                <span class="text-xs font-black uppercase text-white">Teacher Photo</span>
                            </div>
                        </div>
                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Upload Picture</span>
                            <input type="file" name="photo" accept="image/*" @change="previewTeacherPhoto($event)" class="block w-full text-xs font-semibold text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-black file:text-indigo-700 hover:file:bg-indigo-100">
                        </label>
                    </div>

                    <div class="grid gap-4">
                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Full Name *</span>
                            <input type="text" name="name" value="{{ old('name', $selectedTeacherPayload['name'] ?? '') }}" x-model="editTeacher.name" required class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Email *</span>
                            <div class="relative w-full">
                                <input type="email" name="email" value="{{ old('email', $selectedTeacherPayload['email'] ?? '') }}" x-model="editTeacher.email" required list="email-bank"
                                       class="w-full pr-10 rounded-xl border bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:ring-2 transition-all duration-200"
                                       :class="isEmailValid(editTeacher.email) ? 'border-emerald-500 focus:border-emerald-600 focus:ring-emerald-500/20' : 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20'">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <template x-if="isEmailValid(editTeacher.email)">
                                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                    <template x-if="!isEmailValid(editTeacher.email)">
                                        <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </template>
                                </div>
                            </div>
                            <template x-if="editTeacher.name && editTeacher.email !== suggestEmail(editTeacher.name)">
                                <div class="mt-1.5 text-xxs font-bold text-slate-500">
                                    Suggested school email: 
                                    <button type="button" @click="editTeacher.email = suggestEmail(editTeacher.name)" class="text-indigo-600 hover:underline select-none">
                                        <span x-text="suggestEmail(editTeacher.name)"></span>
                                    </button>
                                </div>
                            </template>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Department</span>
                            <select name="dept" x-model="editTeacher.dept" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="" @selected(old('dept', $selectedTeacherPayload['dept'] ?? '') === '')>Blank</option>
                                <option value="Elementary Department" @selected(old('dept', $selectedTeacherPayload['dept'] ?? '') === 'Elementary Department')>Elementary Department</option>
                                <option value="High School Department" @selected(old('dept', $selectedTeacherPayload['dept'] ?? '') === 'High School Department')>High School Department</option>
                                <option value="Islamic School and Arabic Language Department" @selected(old('dept', $selectedTeacherPayload['dept'] ?? '') === 'Islamic School and Arabic Language Department')>Islamic School and Arabic Language Department</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Assignment</span>
                            <input type="text" name="sections" value="{{ old('sections', $selectedTeacherPayload['sections'] ?? '') }}" x-model="editTeacher.sections" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2 items-center">
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Status</span>
                                <select name="status" x-model="editTeacher.status" required class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <option @selected(old('status', $selectedTeacherPayload['status'] ?? 'Active') === 'Active')>Active</option>
                                    <option @selected(old('status', $selectedTeacherPayload['status'] ?? 'Active') === 'Inactive')>Inactive</option>
                                </select>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer mt-4">
                                <input type="checkbox" name="microsoft_sync" value="1" x-model="editTeacher.microsoft_sync" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-700 select-none">Linked to MS365 Sync</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="admin-modal-footer mt-2 flex justify-end gap-2 border-t border-slate-50 pt-3">
                    <a href="{{ route('admin.academic.teachers') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-50" @click.prevent="closeTeacherEditor()">Cancel</a>
                    <button type="submit" class="relative inline-flex min-w-[128px] items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-950/20 transition hover:bg-indigo-600" :class="isSavingTeacher ? 'btn-loading' : ''" :disabled="isSavingTeacher">
                        <span class="btn-spinner" x-show="isSavingTeacher"></span>
                        <i data-lucide="save" class="h-4 w-4"></i>
                        <span class="btn-text-content">Save Changes</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Register Modal -->
        <div class="admin-modal-overlay flex items-center justify-center fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-xs"
             x-show="addModal" @click.self="closeTeacherCreator()" @keydown.escape.window="closeTeacherCreator()" @if (! $createTeacherModalOpen) x-cloak style="display: none;" @endif x-transition>
            <form method="POST" action="{{ route('admin.academic.teachers.store') }}" enctype="multipart/form-data" class="admin-modal-card flex max-h-[92vh] w-full max-w-2xl flex-col gap-4 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-xl" @submit="isRegistering = true">
                @csrf
                <input type="hidden" name="form" value="create">

                <div class="admin-modal-header border-b border-slate-100 pb-3 flex items-center justify-between">
                    <div>
                        <span class="admin-modal-title text-base font-extrabold text-slate-950">Register Teacher</span>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Create a faculty profile for the roster.</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-655 text-xl font-bold" @click="closeTeacherCreator()">&times;</button>
                </div>

                @if ($createTeacherModalOpen)
                    <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-bold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="grid gap-5 md:grid-cols-[180px_1fr]">
                    <div class="space-y-3">
                        <div class="relative h-56 overflow-hidden rounded-2xl border border-slate-150 bg-slate-50">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" alt="" class="h-full w-full object-cover object-center">
                            </template>
                            <template x-if="!photoPreview">
                                <div class="flex h-full w-full items-center justify-center bg-indigo-50 text-indigo-700">
                                    <i data-lucide="user-plus" class="h-12 w-12"></i>
                                </div>
                            </template>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/55 to-transparent p-3">
                                <span class="text-xs font-black uppercase text-white">Teacher Photo</span>
                            </div>
                        </div>
                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Upload Picture</span>
                            <input type="file" name="photo" accept="image/*" @change="previewTeacherPhoto($event)" class="block w-full text-xs font-semibold text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-black file:text-indigo-700 hover:file:bg-indigo-100">
                        </label>
                    </div>

                    <div class="grid gap-4">
                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Full Name *</span>
                            <input type="text" name="name" value="{{ old('form') === 'create' ? old('name') : '' }}" x-model="registerName" required placeholder="e.g. Ust. Bilal Al-Madani" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Email *</span>
                            <div class="relative w-full">
                                <input type="email" name="email" value="{{ old('form') === 'create' ? old('email') : '' }}" x-model="registerEmail" required placeholder="e.g. bilal@amis.edu.ph" list="email-bank"
                                       class="w-full pr-10 rounded-xl border bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:ring-2 transition-all duration-200"
                                       :class="isEmailValid(registerEmail) ? 'border-emerald-500 focus:border-emerald-600 focus:ring-emerald-500/20' : 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20'">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <template x-if="isEmailValid(registerEmail)">
                                        <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                    <template x-if="!isEmailValid(registerEmail)">
                                        <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </template>
                                </div>
                            </div>
                            <template x-if="registerName && registerEmail !== suggestEmail(registerName)">
                                <div class="mt-1.5 text-xxs font-bold text-slate-500">
                                    Suggested school email: 
                                    <button type="button" @click="registerEmail = suggestEmail(registerName)" class="text-indigo-600 hover:underline select-none">
                                        <span x-text="suggestEmail(registerName)"></span>
                                    </button>
                                </div>
                            </template>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Department</span>
                            <select name="dept" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                <option value="" @selected(old('form') !== 'create' || old('dept') === '')>Blank</option>
                                <option value="Elementary Department" @selected(old('form') === 'create' && old('dept') === 'Elementary Department')>Elementary Department</option>
                                <option value="High School Department" @selected(old('form') === 'create' && old('dept') === 'High School Department')>High School Department</option>
                                <option value="Islamic School and Arabic Language Department" @selected(old('form') === 'create' && old('dept') === 'Islamic School and Arabic Language Department')>Islamic School and Arabic Language Department</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Assignment</span>
                            <input type="text" name="sections" value="{{ old('form') === 'create' ? old('sections') : '' }}" placeholder="Subject / Section" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2 items-center">
                            <label class="block">
                                <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Status</span>
                                <select name="status" required class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <option @selected(old('form') !== 'create' || old('status') === 'Active')>Active</option>
                                    <option @selected(old('form') === 'create' && old('status') === 'Inactive')>Inactive</option>
                                </select>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer mt-4">
                                <input type="checkbox" name="microsoft_sync" value="1" @checked(old('form') !== 'create' || old('microsoft_sync') == '1') class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                <span class="text-xs font-bold text-slate-700 select-none">Linked to MS365 Sync</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="admin-modal-footer flex justify-end gap-2 pt-3 border-t border-slate-50 mt-2">
                    <button type="button" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 border border-slate-200 rounded-xl transition cursor-pointer" @click="closeTeacherCreator()">Cancel</button>
                    <button type="submit" class="relative inline-flex items-center justify-center px-5 py-2 text-xs font-bold text-white bg-indigo-700 hover:bg-indigo-600 rounded-xl transition cursor-pointer min-w-[140px] shadow-sm shadow-indigo-950/20"
                            :class="isRegistering ? 'btn-loading' : ''" :disabled="isRegistering">
                        <span class="btn-spinner" x-show="isRegistering"></span>
                        <span class="btn-text-content">Register Teacher</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- View Teacher Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="viewModalOpen" @click.self="viewModalOpen = false" @keydown.escape.window="viewModalOpen = false" x-cloak x-transition>
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-950 uppercase tracking-wider">Teacher Profile Details</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Quick view of teacher info and subject load.</p>
                    </div>
                    <button type="button" @click="viewModalOpen = false" class="text-xl font-bold text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200/60">
                    <div class="h-16 w-16 rounded-full overflow-hidden border-2 border-indigo-100 bg-white flex items-center justify-center font-black text-indigo-700 text-lg shrink-0">
                        <template x-if="viewTeacher.photoUrl">
                            <img :src="viewTeacher.photoUrl" class="h-full w-full object-cover object-center">
                        </template>
                        <template x-if="!viewTeacher.photoUrl">
                            <span x-text="viewTeacher.initials"></span>
                        </template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base font-black text-slate-900 truncate" x-text="viewTeacher.name"></h3>
                        <p class="text-xs font-semibold text-slate-500 truncate" x-text="viewTeacher.email"></p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-100 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-700" x-text="viewTeacher.status"></span>
                            <template x-if="viewTeacher.microsoft_sync">
                                <span class="inline-flex items-center rounded-full bg-blue-50 border border-blue-100 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-blue-700">MS365 Sync Active</span>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs font-semibold text-slate-500">
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Department</span>
                        <span class="mt-1 block font-bold text-slate-800" x-text="viewTeacher.dept || 'N/A'"></span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Advisory Assignment</span>
                        <span class="mt-1 inline-flex rounded bg-slate-50 border border-slate-150 px-2 py-0.5 text-xs font-bold text-slate-700 shadow-3xs" x-text="viewTeacher.sections || 'None'"></span>
                    </div>
                </div>

                <!-- Subject Load Section -->
                <div class="space-y-3.5 border-t border-slate-100 pt-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Current Subject Load</span>
                        <span class="rounded-full bg-indigo-50 border border-indigo-100 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-indigo-700" x-text="viewTeacher.load_status"></span>
                    </div>

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/20 p-3.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black text-indigo-950">
                                <span x-text="viewTeacher.subject_count"></span> / <span x-text="viewTeacher.load_target"></span> subjects assigned
                            </span>
                            <span class="text-[10px] font-bold text-indigo-400">Target 8</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white ring-1 ring-indigo-100">
                            <div class="h-full rounded-full bg-indigo-600 transition-all duration-300" :style="'width: ' + viewTeacher.load_percent + '%'"></div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Assigned Subjects List</span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[160px] overflow-y-auto pr-1">
                            <template x-for="subjectName in viewTeacher.subjects" :key="subjectName">
                                <div class="flex items-center gap-2 rounded-lg border border-slate-150 bg-slate-50 px-2.5 py-1.5 shadow-3xs">
                                    <i data-lucide="book-open" class="w-3.5 h-3.5 text-indigo-500 shrink-0"></i>
                                    <span class="text-xs font-bold text-slate-700 truncate" x-text="subjectName"></span>
                                </div>
                            </template>
                            <template x-if="viewTeacher.subjects.length === 0">
                                <div class="col-span-2 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-center text-xs font-bold text-slate-400">
                                    No subjects assigned yet.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="pt-3 flex justify-between items-center gap-2 border-t border-slate-100">
                    <a :href="'/academic/teachers/' + viewTeacher.id" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 px-4 py-2.5 text-xs font-bold text-indigo-700 transition">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                        Open Detail Profile
                    </a>
                    <div class="flex gap-2">
                        <button type="button" @click="viewModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-50">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Local System Microsoft Email Bank Datalist -->
        <datalist id="email-bank">
            @foreach ($emailBank as $bankEmail)
                <option value="{{ $bankEmail }}"></option>
            @endforeach
        </datalist>

    </div>
</x-admin-layout>
