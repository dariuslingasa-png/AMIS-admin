<x-admin-layout title="Teacher Profile Details">
    <div class="analytics-page flex flex-col gap-6" x-data="{
        isResending: false,
        isToggling: false,
        isSavingSubjects: false,
        subjects: @js($teacher['subjects'] ?? []),
        loadTarget: @js($teacher['load_target'] ?? 8),
        customSubjectName: '',
        editingSubjectIndex: null,
        editingSubjectName: '',
        get subjectCount() {
            return this.subjects.length;
        },
        get loadPercent() {
            return this.loadTarget > 0 ? Math.min(100, Math.round((this.subjectCount / this.loadTarget) * 100)) : 0;
        },
        get loadStatus() {
            if (this.subjectCount >= this.loadTarget) return 'Full Load';
            return this.subjectCount >= 6 ? 'Balanced Load' : 'Needs Load';
        },
        addCustomSubject() {
            const value = this.customSubjectName.trim();
            if (!value || this.subjectCount >= this.loadTarget || this.subjects.includes(value)) return;
            this.subjects = [...this.subjects, value];
            this.customSubjectName = '';
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        startEditSubject(index) {
            this.editingSubjectIndex = index;
            this.editingSubjectName = this.subjects[index] || '';
            this.$nextTick(() => this.$refs.subjectEditInput?.focus());
        },
        saveEditedSubject() {
            if (this.editingSubjectIndex === null) return;
            const value = this.editingSubjectName.trim();
            if (!value || this.subjects.some((subject, index) => subject === value && index !== this.editingSubjectIndex)) return;
            this.subjects = this.subjects.map((subject, index) => index === this.editingSubjectIndex ? value : subject);
            this.cancelEditSubject();
            this.$nextTick(() => window.lucide?.createIcons?.());
        },
        cancelEditSubject() {
            this.editingSubjectIndex = null;
            this.editingSubjectName = '';
        },
        deleteSubject(index) {
            this.subjects = this.subjects.filter((_, subjectIndex) => subjectIndex !== index);
            if (this.editingSubjectIndex === index) {
                this.cancelEditSubject();
            }
            this.$nextTick(() => window.lucide?.createIcons?.());
        }
    }">
        <!-- Hero / Header Banner -->
        <div class="academic-hero-banner relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-900 to-indigo-950 p-6 md:p-8 text-white shadow-md">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-56 h-56 rounded-full bg-indigo-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-8 w-64 h-64 rounded-full bg-sky-500/10 blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                        Academic Workspace
                    </span>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">{{ $teacher['name'] }}</h1>
                    <p class="mt-1 text-sm text-indigo-150 font-light">
                        {{ $teacher['dept'] ?? 'Faculty Member' }} &bull; {{ $teacher['sections'] ?? 'No sections assigned' }}
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.academic.teachers') }}" class="inline-flex items-center gap-2 bg-white hover:bg-indigo-50 active:bg-indigo-100 text-indigo-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md shadow-indigo-950/20 hover:scale-[1.02] cursor-pointer">
                        <i data-lucide="arrow-left" class="w-4 h-4 text-indigo-700"></i>
                        Back to Directory
                    </a>
                </div>
            </div>
        </div>

        <!-- Notification Messages -->
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-extrabold flex items-center gap-2.5 shadow-3xs">
                <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if (session('temp_credentials'))
            <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 space-y-3 shadow-sm">
                <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wider text-amber-800">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Newly Generated Microsoft Credentials
                </div>
                <p class="text-xs font-semibold text-amber-700">Please copy and send these credentials to the teacher for their initial Microsoft login:</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                    <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-4">
                        <div class="min-w-0">
                            <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Microsoft Email</span>
                            <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ session('temp_credentials.email') }}</span>
                        </div>
                        <button type="button" id="copy-email-btn" onclick="copyToClipboard('{{ session('temp_credentials.email') }}', 'copy-email-btn')" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xxs font-black text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200 shrink-0 cursor-pointer">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            Copy
                        </button>
                    </div>
                    <div class="bg-white p-4 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-4">
                        <div class="min-w-0">
                            <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Temporary Password</span>
                            <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ session('temp_credentials.password') }}</span>
                        </div>
                        <button type="button" id="copy-pass-btn" onclick="copyToClipboard('{{ session('temp_credentials.password') }}', 'copy-pass-btn')" class="flex items-center gap-1.5 px-2.5 py-1.5 text-xxs font-black text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200 shrink-0 cursor-pointer">
                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Main Content Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Column: Profile Details -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-6 space-y-6 flex flex-col items-center text-center">
                    
                    <!-- Avatar Container -->
                    <div class="relative h-48 w-48 overflow-hidden rounded-full border-4 border-indigo-50 bg-slate-50 shadow-sm shrink-0">
                        @if ($teacher['photo'])
                            <img src="{{ asset(\App\Support\ImageHelper::thumb($teacher['photo'], 'large')) }}" alt="" class="h-full w-full object-cover object-center">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-indigo-50 text-4xl font-black text-indigo-700 select-none">
                                {{ $teacher['initials'] ?? 'TR' }}
                            </div>
                        @endif
                        <div class="absolute inset-x-0 bottom-0 bg-black/40 py-1.5">
                            <span class="text-[8px] font-black uppercase tracking-widest text-white block">Profile Image</span>
                        </div>
                    </div>

                    <div class="space-y-2 w-full">
                        <h2 class="text-lg font-black text-slate-900 leading-tight">{{ $teacher['name'] }}</h2>
                        <span class="text-xs text-slate-500 font-semibold block">{{ $teacher['email'] }}</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2 w-full pt-4 border-t border-slate-100">
                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $teacher['status'] === 'Active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                            {{ $teacher['status'] }}
                        </span>
                        
                        @if ($teacher['microsoft_sync'] ?? true)
                            <x-badge color="green">MS365 Sync Active</x-badge>
                        @else
                            <x-badge color="gray">MS365 Sync Disabled</x-badge>
                        @endif
                    </div>

                    <div class="w-full text-left bg-slate-50/50 p-4 rounded-xl border border-slate-200 space-y-3.5">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Department</span>
                            <span class="text-xs font-bold text-slate-800">{{ $teacher['dept'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block">Current Assignment</span>
                            <span class="text-xs font-bold text-slate-800">{{ $teacher['sections'] ?? 'N/A' }}</span>
                        </div>
                    </div>

                </div>


            </div>

            <!-- Right Column: Subject & Account Details -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Subject Load Tracker -->
                <form id="subject-load" method="POST" action="{{ route('admin.academic.teachers.subjects.update', $teacher['id']) }}" class="scroll-mt-6 bg-white border border-indigo-100 rounded-2xl shadow-xs p-6 space-y-5" @submit="isSavingSubjects = true">
                    @csrf
                    @method('PATCH')

                    <template x-for="subject in subjects" :key="'subject-input-' + subject">
                        <input type="hidden" name="subjects[]" :value="subject">
                    </template>

                    <div class="border-b border-slate-100 pb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                                <i data-lucide="activity" class="w-5 h-5"></i>
                            </span>
                            <div>
                                <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Subject Load Tracker</span>
                                <p class="mt-0.5 text-xs font-semibold text-slate-500">Assign and maintain this teacher's handled subjects.</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-indigo-700 ring-1 ring-indigo-100" x-text="loadStatus"></span>
                    </div>

                    <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-500">Current Load</span>
                                <div class="mt-1 text-3xl font-black text-indigo-950">
                                    <span x-text="subjectCount"></span>
                                    <span class="text-base text-indigo-500">/</span>
                                    <span x-text="loadTarget"></span>
                                    <span class="text-sm text-indigo-500">subjects</span>
                                </div>
                            </div>
                            <div class="hidden text-right sm:block">
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-indigo-400">Target Range</span>
                                <span class="mt-1 block text-xs font-black text-indigo-800">6 to 8 loads</span>
                            </div>
                        </div>
                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white ring-1 ring-indigo-100">
                            <div class="h-full rounded-full bg-indigo-600 transition-all duration-300" :style="'width: ' + loadPercent + '%'"></div>
                        </div>
                        <div class="mt-2 flex justify-between text-[10px] font-bold uppercase tracking-wider text-indigo-400">
                            <span>Minimum 6</span>
                            <span>Target 8</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-150 bg-slate-50/60 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Create New Subject</span>
                                <p class="mt-0.5 text-xs font-semibold text-slate-500">Add a custom subject to this teacher's load.</p>
                            </div>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500 ring-1 ring-slate-150" x-text="(loadTarget - subjectCount) + ' slots left'"></span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                            <input type="text" x-model="customSubjectName" @keydown.enter.prevent="addCustomSubject()" placeholder="Type subject name" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" :disabled="subjectCount >= loadTarget">
                            <button type="button" @click="addCustomSubject()" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="!customSubjectName.trim() || subjectCount >= loadTarget || subjects.includes(customSubjectName.trim())">
                                <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                                Create Subject
                            </button>
                        </div>
                    </div>

                    <div>
                        <span class="mb-2 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Handled Subjects</span>
                        <div class="grid gap-2" x-show="subjects.length > 0">
                            <template x-for="(subject, index) in subjects" :key="subject + index">
                                <div class="rounded-xl border border-slate-150 bg-white px-3 py-2 shadow-3xs">
                                    <div class="flex items-center gap-2" x-show="editingSubjectIndex !== index">
                                        <i data-lucide="book-open-check" class="h-3.5 w-3.5 text-indigo-600"></i>
                                        <span class="min-w-0 flex-1 truncate text-xs font-bold text-slate-700" x-text="subject"></span>
                                        <button type="button" @click="startEditSubject(index)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-indigo-700" title="Edit subject">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        </button>
                                        <button type="button" @click="deleteSubject(index)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-100 bg-white text-rose-500 hover:bg-rose-50" title="Delete subject">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    </div>
                                    <div class="grid gap-2 sm:grid-cols-[1fr_auto_auto]" x-show="editingSubjectIndex === index">
                                        <input x-ref="subjectEditInput" type="text" x-model="editingSubjectName" @keydown.enter.prevent="saveEditedSubject()" @keydown.escape.prevent="cancelEditSubject()" class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        <button type="button" @click="saveEditedSubject()" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-black text-white hover:bg-indigo-700">Save</button>
                                        <button type="button" @click="cancelEditSubject()" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-500 hover:bg-slate-50">Cancel</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-6 text-center text-xs font-bold text-slate-400" x-show="subjects.length === 0">
                            No subjects added yet.
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-100 pt-4">
                        <button type="submit" class="relative inline-flex min-w-[150px] items-center justify-center gap-2 rounded-xl bg-indigo-700 px-5 py-2.5 text-xs font-black text-white shadow-sm shadow-indigo-950/20 transition hover:bg-indigo-600 disabled:cursor-wait disabled:opacity-80" :disabled="isSavingSubjects">
                            <span class="btn-spinner" x-show="isSavingSubjects"></span>
                            <i data-lucide="save" class="h-4 w-4" x-show="!isSavingSubjects"></i>
                            Save Load
                        </button>
                    </div>
                </form>
                
                <!-- Account Credentials & Actions Card -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-xs p-6 space-y-6">
                    <div class="border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-5 h-5 text-indigo-600"></i>
                        <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Microsoft Credentials Status</span>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 rounded-xl border {{ $teacher['password_changed'] === 'Yes' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $teacher['password_changed'] === 'Yes' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                <i data-lucide="{{ $teacher['password_changed'] === 'Yes' ? 'key-round' : 'alert-circle' }}" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <span class="text-xs font-extrabold uppercase block tracking-wider">Has changed password?</span>
                                <span class="text-[11px] font-bold text-slate-500">
                                    Current Flag: <span class="font-extrabold {{ $teacher['password_changed'] === 'Yes' ? 'text-emerald-700' : 'text-rose-700' }}">{{ $teacher['password_changed'] }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($teacher['password_changed'] === 'Yes')
                                <form method="POST" action="{{ route('admin.academic.teachers.resend') }}" @submit="isResending = true">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $teacher['id'] }}">
                                    <button type="submit" :disabled="isResending" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xxs font-black bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white rounded-lg transition shadow-3xs cursor-pointer select-none border border-amber-600">
                                        <span class="btn-spinner" x-show="isResending"></span>
                                        <i data-lucide="key" class="w-3 h-3" x-show="!isResending"></i>
                                        Reset & Resend
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Conditional Credentials Section: Displayed if password_changed is No -->
                    @if ($teacher['password_changed'] === 'No')
                        <div class="bg-amber-50/40 border border-amber-200 rounded-xl p-4 md:p-5 space-y-4">
                            <div class="flex items-center gap-2 text-amber-800 font-extrabold text-xs uppercase tracking-wider">
                                <i data-lucide="key" class="w-4 h-4 text-amber-700"></i>
                                Microsoft Login Credentials (Temporary)
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-3">
                                    <div class="min-w-0">
                                        <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Microsoft Email</span>
                                        <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ $teacher['email'] }}</span>
                                    </div>
                                    <button type="button" id="copy-email-static" onclick="copyToClipboard('{{ $teacher['email'] }}', 'copy-email-static')" class="px-2 py-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200 cursor-pointer">
                                        Copy
                                    </button>
                                </div>
                                <div class="bg-white p-3.5 rounded-xl border border-amber-200 shadow-3xs flex justify-between items-center gap-3">
                                    <div class="min-w-0">
                                        <span class="text-[9px] font-black uppercase text-slate-400 block tracking-wider">Temporary Password</span>
                                        <span class="text-xs font-mono font-bold text-slate-800 block truncate select-all">{{ $teacher['temporary_password'] ?: 'No password set' }}</span>
                                    </div>
                                    @if ($teacher['temporary_password'])
                                        <button type="button" id="copy-pass-static" onclick="copyToClipboard('{{ $teacher['temporary_password'] }}', 'copy-pass-static')" class="px-2 py-1 text-[10px] font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-md border border-indigo-200 cursor-pointer">
                                            Copy
                                        </button>
                                    @endif
                                </div>
                            </div>
                            
                            <p class="text-[10px] text-amber-700 font-semibold italic mt-1 leading-normal">
                                Note: These credentials will display here until the teacher logs in and successfully changes their password, or until the admin marks the status as "Yes".
                            </p>
                        </div>
                    @endif

                    <!-- Account Reset Action -->
                    <div class="border-t border-slate-100 pt-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="font-extrabold text-slate-900 text-xs block tracking-wide">Reset Teacher Account & Microsoft Credentials</span>
                            <span class="text-[11px] text-slate-400 font-medium block">
                                This will generate a new random temporary password, clear the changed status back to "No", and update Microsoft Graph.
                            </span>
                        </div>
                        <form method="POST" action="{{ route('admin.academic.teachers.resend') }}" @submit="isResending = true">
                            @csrf
                            <input type="hidden" name="id" value="{{ $teacher['id'] }}">
                            <button type="submit" :disabled="isResending" class="relative inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white font-black text-xs px-5 py-2.5 shadow-sm transition min-w-[200px] cursor-pointer">
                                <span class="btn-spinner" x-show="isResending"></span>
                                <i data-lucide="key" class="w-4 h-4" x-show="!isResending"></i>
                                Reset & Resend Credentials
                            </button>
                        </form>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Inline script for clipboard copies and icons -->
    <script>
        function copyToClipboard(text, buttonId) {
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById(buttonId);
                const originalContent = btn.innerHTML;
                btn.innerHTML = `Copied!`;
                btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                setTimeout(() => {
                    btn.innerHTML = originalContent;
                    btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
            });
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</x-admin-layout>
