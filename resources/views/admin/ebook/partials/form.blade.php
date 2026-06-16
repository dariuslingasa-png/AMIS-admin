@php
    $isEdit = filled($book);
@endphp

@if ($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">
        <div class="flex items-center gap-2">
            <i data-lucide="circle-alert" class="h-4 w-4"></i>
            <span>Please check the highlighted fields.</span>
        </div>
        <ul class="mt-2 list-inside list-disc text-xs">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div x-data="ebookUploadForm()">
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" @submit.prevent="submitForm($event)">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <fieldset :disabled="showModal" class="space-y-6">
        <div class="grid gap-5 lg:grid-cols-2">
            <label class="block lg:col-span-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Book Title</span>
                <input type="text" name="title" value="{{ old('title', $book?->title) }}" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-950 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="e.g. Islamic Studies Grade 4">
            </label>

            <label class="block lg:col-span-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Author / Teacher <span class="text-slate-400 font-bold normal-case">(Optional)</span></span>
                <input type="text" name="author" value="{{ old('author', $book?->author) }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-950 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="e.g. Ustadz John Doe">
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Grade Level</span>
                <select name="grade_level" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-950 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">Select grade</option>
                    @foreach ($gradeLevels as $grade)
                        <option value="{{ $grade }}" @selected(old('grade_level', $book?->grade_level) === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">PDF Document</span>
                <input type="file" name="pdf_file" accept="application/pdf" @required(! $isEdit) class="mt-2 block h-12 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-black file:text-emerald-700 hover:file:bg-emerald-100">
                <span class="mt-2 block text-xs font-semibold text-slate-500">
                    {{ $isEdit ? 'Leave blank to keep the current PDF.' : 'PDF only, maximum 1GB.' }}
                </span>
            </label>

            <label class="block">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Cover Image <span class="text-slate-400 font-bold normal-case">(Optional)</span></span>
                @if($isEdit && $book?->cover_image_path)
                    <div class="mt-2 mb-2">
                        <img src="{{ asset('storage/' . $book->cover_image_path) }}" alt="Current cover" class="h-24 w-auto rounded-lg border border-slate-200 object-cover shadow-sm">
                        <span class="mt-1 block text-xs font-semibold text-emerald-600">Current cover image</span>
                    </div>
                @endif
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="mt-2 block h-12 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-black file:text-emerald-700 hover:file:bg-emerald-100">
                <span class="mt-2 block text-xs font-semibold text-slate-500">
                    A cover is auto-generated from the PDF. Upload here to override. JPG, PNG, or WebP, max 5MB.
                </span>
            </label>

            <label class="block lg:col-span-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Description</span>
                <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Optional short description">{{ old('description', $book?->description) }}</textarea>
            </label>
        </div>

        <div class="grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
            <div>
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Publishing Status</span>
                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach (['published' => 'Published', 'draft' => 'Draft'] as $value => $label)
                        <label class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700">
                            <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $book?->status ?? 'published') === $value) class="h-4 w-4 accent-emerald-600">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <span class="text-xs font-black uppercase tracking-wider text-slate-500">Downloads</span>
                <label class="mt-3 inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="is_downloadable" value="1" @checked(old('is_downloadable', $book?->is_downloadable)) class="h-4 w-4 rounded accent-emerald-600">
                    Enable PDF download
                </label>
            </div>
        </div>
    </fieldset>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.ebook.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-black text-slate-700 transition hover:bg-slate-50">
            Cancel
        </a>
        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-black text-white shadow-sm transition hover:bg-emerald-700">
            <i data-lucide="{{ $isEdit ? 'save' : 'upload-cloud' }}" class="h-4 w-4"></i>
            {{ $isEdit ? 'Save eBook' : 'Upload eBook' }}
        </button>
    </div>
</form>

{{-- Optimization Modal --}}
<div x-show="showModal" x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">
    <div class="mx-4 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl"
         @click.outside="">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-5">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl" 
                 :class="phase === 'done' ? 'bg-emerald-100' : phase === 'error' ? 'bg-rose-100' : 'bg-amber-100'">
                <i :data-lucide="phase === 'done' ? 'check-circle' : phase === 'error' ? 'x-circle' : 'hard-drive'" 
                   class="h-5 w-5"
                   :class="phase === 'done' ? 'text-emerald-600' : phase === 'error' ? 'text-rose-600' : 'text-amber-600'"></i>
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900" x-text="modalTitle"></h3>
                <p class="text-xs font-semibold text-slate-500" x-text="modalSubtitle"></p>
            </div>
        </div>

        {{-- File Info --}}
        <div class="mb-4 rounded-xl bg-slate-50 p-3" x-show="fileName">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100">
                    <i data-lucide="file-text" class="h-4 w-4 text-rose-600"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-slate-800" x-text="fileName"></p>
                    <p class="text-xs font-semibold text-slate-400" x-text="fileSizeText"></p>
                </div>
                <span class="text-xs font-black text-slate-500" x-text="elapsedText"></span>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mb-3">
            <div class="mb-1.5 flex items-center justify-between text-xs font-black uppercase tracking-wider">
                <span x-text="statusText" :class="phase === 'error' ? 'text-rose-600' : 'text-emerald-700'"></span>
                <span class="text-slate-400" x-text="Math.round(Math.max(0, Math.min(100, displayProgress))) + '%'"></span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full transition-all duration-300 ease-out"
                     :class="{
                         'bg-emerald-500': phase === 'done',
                         'bg-rose-500': phase === 'error',
                         'bg-gradient-to-r from-emerald-500 via-emerald-400 to-emerald-500 animate-pulse': phase === 'optimizing',
                         'bg-emerald-600': phase === 'uploading'
                     }"
                     :style="'width: ' + Math.max(0, Math.min(100, displayProgress)) + '%'"></div>
            </div>
        </div>

        {{-- Stage Steps --}}
        <div class="space-y-2 mb-5">
            <template x-for="(step, index) in stages" :key="index">
                <div class="flex items-center gap-2.5 text-xs font-semibold"
                     :class="step.status === 'done' ? 'text-emerald-600' : step.status === 'active' ? 'text-slate-800' : 'text-slate-300'">
                    <div class="flex h-5 w-5 items-center justify-center rounded-full border"
                         :class="{
                             'border-emerald-500 bg-emerald-500 text-white': step.status === 'done',
                             'border-emerald-500 bg-emerald-50 text-emerald-600': step.status === 'active',
                             'border-slate-200 bg-white text-slate-300': step.status === 'pending'
                         }">
                        <i x-show="step.status === 'done'" data-lucide="check" class="h-3 w-3"></i>
                        <span x-show="step.status !== 'done'" class="text-[10px] font-black" x-text="index + 1"></span>
                    </div>
                    <span x-text="step.label"></span>
                    <i x-show="step.status === 'active'" data-lucide="loader-2" class="h-3 w-3 animate-spin ml-auto"></i>
                </div>
            </template>
        </div>

        {{-- Action --}}
        <div x-show="phase === 'error'" class="flex justify-end">
            <button @click="showModal = false; phase = 'idle'" 
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function ebookUploadForm() {
        return {
            showModal: false,
            displayProgress: 0,
            phase: 'idle',
            statusText: '',
            modalTitle: '',
            modalSubtitle: '',
            elapsedText: '',
            fileName: '',
            fileSizeText: '',
            startTime: null,
            timerInterval: null,
            optimizeInterval: null,
            stages: [
                { label: 'Uploading PDF to server', status: 'pending' },
                { label: 'Saving eBook record', status: 'pending' },
                { label: 'Starting background cover and optimization', status: 'pending' },
            ],

            submitForm(event) {
                const form = event.target;
                const fileInput = form.querySelector('input[name="pdf_file"]');
                const file = fileInput?.files?.[0];

                // Show modal with file info
                this.showModal = true;
                this.displayProgress = 0;
                this.phase = 'uploading';
                this.modalTitle = 'Uploading eBook';
                this.modalSubtitle = 'Please wait while we upload your PDF';
                this.statusText = 'Uploading...';
                this.elapsedText = '0s';
                this.startTime = Date.now();
                this.startTimer();

                if (file) {
                    this.fileName = file.name;
                    const sizeMB = (file.size / 1048576).toFixed(1);
                    this.fileSizeText = sizeMB + ' MB';
                } else {
                    this.fileName = '{{ $isEdit ? "Updating eBook..." : "" }}';
                    this.fileSizeText = '';
                }

                this.resetStages();
                this.setStageStatus(0, 'active');

                // Re-init lucide icons inside modal
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });

                const formData = new FormData(form);
                const xhr = new XMLHttpRequest();

                // Upload progress → 0-70% of bar
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable && e.total > 0) {
                        const pct = Math.max(0, Math.min(100, Math.round((e.loaded / e.total) * 100)));
                        this.displayProgress = Math.round(pct * 0.7);
                        const loadedMB = (e.loaded / 1048576).toFixed(1);
                        const totalMB = (e.total / 1048576).toFixed(1);
                        this.statusText = `Uploading — ${loadedMB} / ${totalMB} MB`;
                    }
                });

                // Upload done → server saving phase
                xhr.upload.addEventListener('load', () => {
                    this.setStageStatus(0, 'done');
                    this.setStageStatus(1, 'active');
                    this.phase = 'optimizing';
                    this.displayProgress = 80;
                    this.statusText = 'Saving eBook...';
                    this.startOptimizingAnimation();
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                });

                // Server response → done
                xhr.addEventListener('load', () => {
                    this.stopTimer();
                    this.stopOptimizing();

                    if (xhr.status >= 200 && xhr.status < 400) {
                        this.phase = 'done';
                        this.displayProgress = 100;
                        this.statusText = 'Complete!';
                        this.modalTitle = 'eBook Uploaded Successfully';
                        this.modalSubtitle = 'Cover generation and optimization are running in the background. Redirecting...';
                        this.stages.forEach(s => s.status = 'done');
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                        setTimeout(() => {
                            window.location.href = xhr.responseURL || "{{ route('admin.ebook.index') }}";
                        }, 800);
                    } else {
                        this.phase = 'error';
                        this.statusText = 'Upload failed';
                        this.modalTitle = 'Upload Failed';
                        this.modalSubtitle = 'Something went wrong';
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.errors) {
                                let msgs = [];
                                for (const field in response.errors) {
                                    msgs.push(...response.errors[field]);
                                }
                                this.modalSubtitle = msgs.join(', ');
                            }
                        } catch (e) {}
                    }
                });

                xhr.addEventListener('error', () => {
                    this.stopTimer();
                    this.stopOptimizing();
                    this.phase = 'error';
                    this.statusText = 'Connection error';
                    this.modalTitle = 'Upload Failed';
                    this.modalSubtitle = 'Connection error. Please try again.';
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                });

                xhr.addEventListener('timeout', () => {
                    this.stopTimer();
                    this.stopOptimizing();
                    this.phase = 'error';
                    this.statusText = 'Timed out';
                    this.modalTitle = 'Upload Timed Out';
                    this.modalSubtitle = 'The file may be too large. Please try again.';
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                });

                xhr.open('POST', form.action);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.timeout = 600000; // 10 minutes
                xhr.send(formData);
            },

            startOptimizingAnimation() {
                let tick = 0;
                this.optimizeInterval = setInterval(() => {
                    if (this.phase !== 'optimizing') {
                        this.stopOptimizing();
                        return;
                    }
                    tick++;

                    // Creep from 70% to 99%
                    const remaining = 99 - this.displayProgress;
                    if (remaining > 0.5) {
                        this.displayProgress += remaining * 0.025;
                    }

                    // Update stages based on time
                    if (tick >= 3 && this.stages[1]?.status === 'active') {
                        this.setStageStatus(1, 'done');
                        this.setStageStatus(2, 'active');
                        this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                    }

                    const messages = [
                        'Saving eBook...',
                        'Starting background cover generation...',
                        'Starting background optimization...',
                        'Almost done...',
                    ];
                    const idx = Math.min(Math.floor(tick / 8), messages.length - 1);
                    this.statusText = messages[idx];
                }, 1000);
            },

            stopOptimizing() {
                if (this.optimizeInterval) {
                    clearInterval(this.optimizeInterval);
                    this.optimizeInterval = null;
                }
            },

            startTimer() {
                this.timerInterval = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                    const mins = Math.floor(elapsed / 60);
                    const secs = elapsed % 60;
                    this.elapsedText = mins > 0
                        ? `${mins}m ${secs.toString().padStart(2, '0')}s`
                        : `${secs}s`;
                }, 1000);
            },

            stopTimer() {
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                    this.timerInterval = null;
                }
            },

            resetStages() {
                this.stages.forEach((stage) => {
                    stage.status = 'pending';
                });
            },

            setStageStatus(index, status) {
                if (this.stages[index]) {
                    this.stages[index].status = status;
                }
            }
        };
    }
</script>
</div>
