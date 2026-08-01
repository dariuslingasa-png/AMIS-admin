<x-admin-layout
    title="Enrollment Applications"
    :breadcrumbs="[
        ['label' => 'Applications', 'href' => route('admin.applications.enrollment')],
        ['label' => 'Enrollment', 'href' => null],
    ]"
>
    @php
        $currentSort = request('sort', 'number');
        $currentDir = request('dir', 'desc');
        $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
        $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
        $childStatusColor = ['approved' => 'green', 'rejected' => 'red', 'under_review' => 'purple', 'ready_for_submission' => 'blue', 'pending' => 'blue', 'submitted' => 'blue', 'draft' => 'yellow', 'for_correction' => 'red'];
        $familyPaymentChip = fn ($label) => match ($label) {
            'Paid' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-slate-200 bg-white/80 text-slate-600',
        };
        $familyStatusChip = fn ($status) => match ($status) {
            'Approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'Rejected' => 'border-rose-200 bg-rose-50 text-rose-700',
            'Pending' => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'border-slate-200 bg-white/80 text-slate-600',
        };
        $typeLabel = fn ($type) => match (\Illuminate\Support\Str::of((string) $type)->lower()->replace(['_', '-'], ' ')->squish()->toString()) {
            'old', 'old student', 'returning', 'returnee', 'existing' => 'OLD STUDENT',
            'transferee', 'transfer', 'transferee student' => 'TRANSFEREE STUDENT',
            'new', 'new student' => 'NEW STUDENT',
            default => 'NOT SET',
        };
        $typeClass = fn ($label) => match ($label) {
            'OLD STUDENT' => 'bg-green-100 text-green-800',
            'TRANSFEREE STUDENT' => 'bg-amber-100 text-amber-800',
            'NEW STUDENT' => 'bg-blue-100 text-blue-800',
            default => 'bg-slate-100 text-slate-600',
        };
        $familyAccents = [
            ['wrap' => 'border-l-green-600 border-green-100 bg-green-50', 'icon' => 'bg-green-100 text-green-700', 'text' => 'text-green-800', 'badge' => 'bg-white text-green-700 ring-1 ring-green-200'],
            ['wrap' => 'border-l-blue-600 border-blue-100 bg-blue-50', 'icon' => 'bg-blue-100 text-blue-700', 'text' => 'text-blue-800', 'badge' => 'bg-white text-blue-700 ring-1 ring-blue-200'],
            ['wrap' => 'border-l-amber-500 border-amber-100 bg-amber-50', 'icon' => 'bg-amber-100 text-amber-700', 'text' => 'text-amber-800', 'badge' => 'bg-white text-amber-700 ring-1 ring-amber-200'],
            ['wrap' => 'border-l-violet-600 border-violet-100 bg-violet-50', 'icon' => 'bg-violet-100 text-violet-700', 'text' => 'text-violet-800', 'badge' => 'bg-white text-violet-700 ring-1 ring-violet-200'],
            ['wrap' => 'border-l-rose-600 border-rose-100 bg-rose-50', 'icon' => 'bg-rose-100 text-rose-700', 'text' => 'text-rose-800', 'badge' => 'bg-white text-rose-700 ring-1 ring-rose-200'],
        ];
    @endphp

    <div x-data="{
        quickReviewModal: false,
        reviewChild: null,
        openQuickReview(data) {
            this.reviewChild = data;
            this.quickReviewModal = true;
        },
        emailModalOpen: false,
        isSendingEmail: false,
        emailSuccess: null,
        emailError: null,
        recipientEmail: 'almunawwaraislamic@gmail.com',
        paymentFilter: 'all',
        limitCount: '',
        messageBody: 'Assalamualaikum Sir Cabel,\n\nHere is the list of enrollment families.\n\n- IT Staff\nMon Zhairel Lingasa',
        progressText: '',
        progressPercent: 0,
        progressCurrent: 0,
        progressTotal: 0,
        familiesList: [],
        async sendEmailReport() {
            this.isSendingEmail = true;
            this.emailSuccess = null;
            this.emailError = null;
            this.progressText = 'Fetching families list, please wait...';
            this.progressPercent = 0;
            this.progressCurrent = 0;
            this.progressTotal = 0;
            this.familiesList = [];

            try {
                const listRes = await fetch('{{ route('admin.applicants.email-registry') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        recipient_email: this.recipientEmail,
                        payment_filter: this.paymentFilter,
                        limit_count: this.limitCount ? parseInt(this.limitCount) : null,
                        message_body: this.messageBody,
                        fetch_families_only: true
                    })
                });
                
                const listData = await listRes.json();
                if (!listData.success) {
                    throw new Error(listData.message || 'Failed to fetch families.');
                }

                const families = listData.families || [];
                this.progressTotal = families.length;

                if (families.length === 0) {
                    this.progressText = 'Sending fallback report (No Families Found)...';
                    this.progressPercent = 50;
                    
                    const res = await fetch('{{ route('admin.applicants.email-registry') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            recipient_email: this.recipientEmail,
                            payment_filter: this.paymentFilter,
                            limit_count: this.limitCount ? parseInt(this.limitCount) : null,
                            message_body: this.messageBody
                        })
                    });
                    const data = await res.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to send fallback report.');
                    }
                    this.progressPercent = 100;
                    this.emailSuccess = data.message;
                } else {
                    // Populate familiesList for tracking
                    this.familiesList = families.map(f => ({
                        family_no: f.family_no,
                        family_label: f.family_label,
                        status: 'pending'
                    }));

                    const sleep = ms => new Promise(r => setTimeout(r, ms));

                    for (let i = 0; i < families.length; i++) {
                        const family = families[i];
                        this.progressCurrent = i + 1;
                        this.progressPercent = Math.round((i / families.length) * 100);
                        this.progressText = `Sending email to ${this.recipientEmail} for family '${family.family_label}'... (${this.progressCurrent} of ${this.progressTotal})`;

                        // Update status to 'sending'
                        const trackItem = this.familiesList.find(item => item.family_no === family.family_no);
                        if (trackItem) {
                            trackItem.status = 'sending';
                        }

                        try {
                            const res = await fetch('{{ route('admin.applicants.email-registry') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    recipient_email: this.recipientEmail,
                                    payment_filter: this.paymentFilter,
                                    limit_count: this.limitCount ? parseInt(this.limitCount) : null,
                                    message_body: this.messageBody,
                                    family_no: family.family_no
                                })
                            });
                            const data = await res.json();
                            if (!data.success) {
                                throw new Error(data.message || `Failed to send email for family '${family.family_label}'.`);
                            }
                            
                            // Update status to 'sent'
                            if (trackItem) {
                                trackItem.status = 'sent';
                            }
                        } catch (itemErr) {
                            console.error(itemErr);
                            // Update status to 'failed'
                            if (trackItem) {
                                trackItem.status = 'failed';
                            }
                        }

                        // Throttle 300ms to respect SMTP rate limits
                        await sleep(300);
                    }

                    const sentCount = this.familiesList.filter(f => f.status === 'sent').length;
                    const failedCount = this.familiesList.filter(f => f.status === 'failed').length;
                    this.progressPercent = 100;

                    if (failedCount > 0) {
                        this.progressText = `Dispatch finished: ${sentCount} sent, ${failedCount} failed.`;
                        this.emailError = `Registry report dispatch finished with ${failedCount} failure(s). Check the log below.`;
                    } else {
                        this.progressText = `Successfully sent ${sentCount} families registry report(s) done!`;
                        this.emailSuccess = `Successfully sent all ${sentCount} family email report(s) done!`;
                    }
                }

                this.isSendingEmail = false;
                setTimeout(() => {
                    this.emailModalOpen = false;
                    this.emailSuccess = null;
                }, 5000);

            } catch (err) {
                this.isSendingEmail = false;
                this.emailError = err.message || 'An unexpected error occurred. Please try again.';
            }
        }
    }">
    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-950">Applications</h1>
                    <p class="mt-1 text-sm text-slate-500">Family enrollment registry grouped by child applicants</p>
                </div>
                <div class="flex items-center gap-3">
                    @unless ($isTeacherAdminViewer)
                    <button type="button" @click="emailModalOpen = true; familiesList = []; emailSuccess = null; emailError = null; isSendingEmail = false;" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs px-4 py-2 transition shadow-3xs cursor-pointer select-none">
                        <i data-lucide="mail" class="h-4 w-4"></i>
                        Email Families Registry
                    </button>
                    @endunless
                    <a href="{{ route('admin.applications.print-no-payment', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs px-4 py-2 transition shadow-3xs cursor-pointer select-none">
                        <i data-lucide="printer" class="h-4 w-4"></i>
                        Print No Payment List
                    </a>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700" data-total-count="{{ $families->total() }}">
                        {{ number_format($families->total()) }} families
                    </span>
                </div>
            </div>
        </div>

        <div class="px-6 py-5">
            <!-- Applications Metrics Tracking Panel -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-5">
                <!-- Approved Card -->
                <div class="group relative overflow-hidden rounded-xl border border-emerald-100 bg-emerald-50/30 p-5 transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-50/50 hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-600">Approved Applications</span>
                            <h3 class="mt-2 text-3xl font-black tracking-tight text-emerald-950">{{ number_format($approvedCount) }}</h3>
                        </div>
                        <div class="rounded-lg bg-emerald-100/80 p-3 text-emerald-700 transition-transform duration-300 group-hover:scale-110">
                            <i data-lucide="check-circle-2" class="h-6 w-6"></i>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-emerald-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                </div>

                <!-- Review Queue Card -->
                <div class="group relative overflow-hidden rounded-xl border border-blue-100 bg-blue-50/30 p-5 transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-50/50 hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600">Review Queue</span>
                            <h3 class="mt-2 text-3xl font-black tracking-tight text-blue-950">{{ number_format($reviewQueueCount) }}</h3>
                        </div>
                        <div class="rounded-lg bg-blue-100/80 p-3 text-blue-700 transition-transform duration-300 group-hover:scale-110">
                            <i data-lucide="clock-4" class="h-6 w-6"></i>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                </div>

                <!-- Rejected Card -->
                <div class="group relative overflow-hidden rounded-xl border border-rose-100 bg-rose-50/30 p-5 transition-all duration-300 hover:-translate-y-0.5 hover:bg-rose-50/50 hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-extrabold uppercase tracking-wider text-rose-600">Rejected Applications</span>
                            <h3 class="mt-2 text-3xl font-black tracking-tight text-rose-950">{{ number_format($rejectedCount) }}</h3>
                        </div>
                        <div class="rounded-lg bg-rose-100/80 p-3 text-rose-700 transition-transform duration-300 group-hover:scale-110">
                            <i data-lucide="x-circle" class="h-6 w-6"></i>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-rose-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                </div>

                <!-- Duplicate Detection Card (read-only) -->
                <div class="group relative overflow-hidden rounded-xl border border-amber-100 bg-amber-50/30 p-5 transition-all duration-300 hover:-translate-y-0.5 hover:bg-amber-50/50 hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-extrabold uppercase tracking-wider text-amber-600">Possible Duplicates</span>
                            <h3 class="mt-2 text-3xl font-black tracking-tight text-amber-950">{{ number_format($duplicateCount) }}</h3>
                            <p class="mt-1 text-[10px] font-bold text-amber-500 leading-snug">Same name &amp; birthdate detected</p>
                        </div>
                        <div class="rounded-lg bg-amber-100/80 p-3 text-amber-700 transition-transform duration-300 group-hover:scale-110">
                            <i data-lucide="copy" class="h-6 w-6"></i>
                        </div>
                    </div>
                    @if($duplicateCount > 0)
                        <div class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-black text-amber-700 ring-1 ring-amber-200">
                            <i data-lucide="alert-triangle" class="h-3 w-3"></i>
                            Search by name to review
                        </div>
                    @endif
                    <div class="absolute bottom-0 left-0 h-1 w-full bg-amber-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                </div>
            </div>

            <form method="GET" class="mb-5 grid grid-cols-12 gap-3">
                <label class="relative col-span-4">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search family, child, or email" class="{{ $inputClass }} w-full pl-9">
                </label>
                <select name="status" class="{{ $inputClass }} col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statusLabels ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="grade" class="{{ $inputClass }} col-span-2 w-full" onchange="this.form.submit()">
                    @unless ($isTeacherAdminViewer)
                        <option value="">All grades</option>
                    @endunless
                    @foreach ($gradeLevels ?? [] as $grade)
                        <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
                <label class="relative col-span-2">
                    <select name="sort" class="{{ $inputClass }} w-full" onchange="this.form.submit()">
                        <option value="number" @selected($currentSort === 'number')>Family no.</option>
                        <option value="parent" @selected($currentSort === 'parent')>Family name</option>
                        <option value="children" @selected($currentSort === 'children')>Children count</option>
                        <option value="progress" @selected($currentSort === 'progress')>Approved progress</option>
                        <option value="payment" @selected($currentSort === 'payment')>Payment status</option>
                        <option value="status" @selected($currentSort === 'status')>Overall status</option>
                    </select>
                </label>
                <select name="dir" class="{{ $inputClass }} col-span-1 w-full px-3" onchange="this.form.submit()">
                    <option value="desc" @selected($currentDir === 'desc')>Desc</option>
                    <option value="asc" @selected($currentDir === 'asc')>Asc</option>
                </select>
                <button class="col-span-1 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    Apply
                </button>
            </form>

            <div class="overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-36 px-5 py-4 font-bold">Child</th>
                            <th class="px-5 py-4 font-bold">Student Name</th>
                            <th class="w-36 px-5 py-4 font-bold">Student Type</th>
                            <th class="w-36 px-5 py-4 font-bold">Grade</th>
                            <th class="w-44 px-5 py-4 font-bold">Enrollment Status</th>
                            <th class="w-36 px-5 py-4 text-right font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($families as $family)
                            @php
                                $representative = $family['representative'];
                                [$familyLastName, $familyFirstName] = array_pad(explode(', ', \Illuminate\Support\Str::upper(html_entity_decode($family['family_label'], ENT_QUOTES, 'UTF-8')), 2), 2, 'GUARDIAN');
                                $familyHeader = $isTeacherAdminViewer ? 'CHILDREN IN FAMILY' : 'FAMILY OF '.$familyLastName.', '.$familyFirstName;
                                $initials = $isTeacherAdminViewer ? 'CH' : collect([$familyLastName, $familyFirstName])->filter()->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                $accent = $familyAccents[$family['family_no'] % count($familyAccents)];
                                $maxDiscount = $family['children']->max(fn ($child) => (float) ($child->discount_percentage ?? 0));
                                $discountLabel = $maxDiscount > 0 ? 'SIBLINGS DISCOUNT '.rtrim(rtrim(number_format($maxDiscount, 2), '0'), '.').'%' : 'SIBLINGS DISCOUNT';
                            @endphp
                            <tr>
                                <td colspan="6" class="px-0 py-0">
                                    <div class="border-l-4 px-5 py-3 {{ $accent['wrap'] }}">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-md text-xs font-extrabold {{ $accent['icon'] }}">{{ $initials ?: 'FA' }}</span>
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h3 class="text-sm font-extrabold tracking-wide {{ $accent['text'] }}">{{ $familyHeader }}</h3>
                                                        @if ($representative->user)
                                                            @if ($representative->user->isActive())
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-800 ring-1 ring-emerald-250" title="Active on Portal: {{ $representative->user->last_active_at ? $representative->user->last_active_at->diffForHumans() : 'now' }}">
                                                                    <span class="relative flex h-1.5 w-1.5">
                                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                                                    </span>
                                                                    Active
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-600 ring-1 ring-slate-200" title="Inactive: {{ $representative->user->last_active_at ? $representative->user->last_active_at->diffForHumans() : 'never' }}">
                                                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                                                    Inactive
                                                                </span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">FAMILY APPLICATION #{{ str_pad($family['family_no'], 4, '0', STR_PAD_LEFT) }}</p>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center justify-end gap-2">
                                                @unless ($isTeacherAdminViewer)
                                                @if ($family['children_count'] > 1)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/80 px-3 py-1.5 text-xs font-black uppercase tracking-wide shadow-sm {{ $accent['text'] }}">
                                                        <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                                        {{ $family['approved_count'] }}/{{ $family['children_count'] }} Approved
                                                    </span>
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-sky-700 shadow-sm">
                                                        <i data-lucide="percent" class="h-3.5 w-3.5"></i>
                                                        {{ $discountLabel }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/80 px-3 py-1.5 text-xs font-black uppercase tracking-wide shadow-sm {{ $accent['text'] }}">
                                                        <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                                        {{ $family['approved_count'] }}/1 Approved
                                                    </span>
                                                @endif
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-black uppercase tracking-wide shadow-sm {{ $familyStatusChip($family['overall_status']) }}">
                                                    <i data-lucide="info" class="h-3.5 w-3.5"></i>
                                                    {{ $family['overall_status'] }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-black uppercase tracking-wide shadow-sm {{ $familyPaymentChip($family['payment_status']) }}">
                                                    <i data-lucide="receipt" class="h-3.5 w-3.5"></i>
                                                    {{ $family['payment_status'] }}
                                                </span>
                                                @if ($family['email_sent_at'])
                                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-indigo-700 shadow-sm" title="Registry Emailed at: {{ $family['email_sent_at']->format('M d, Y h:i A') }}">
                                                        <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                                                        Emailed
                                                    </span>
                                                @endif
                                                @endunless
                                                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/80 px-3 py-1.5 text-xs font-black uppercase tracking-wide shadow-sm {{ $accent['text'] }}">
                                                    <i data-lucide="users" class="h-3.5 w-3.5"></i>
                                                    {{ $family['children_count'] }} {{ \Illuminate\Support\Str::plural('Child', $family['children_count']) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @foreach ($family['children'] as $index => $child)
                                @php
                                    $childName = \Illuminate\Support\Str::upper(html_entity_decode(implode(' ', array_filter([trim($child->first_name ?? ''), trim($child->middle_name ?? ''), trim($child->last_name ?? '')])), ENT_QUOTES, 'UTF-8') ?: 'STUDENT');
                                    $childInitials = collect(explode(' ', $childName))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                                    $photoUrl = \App\Support\EnrollmentStorage::url($child->photo_2x2_url, 'medium');
                                    $statusLabel = $statusLabels[$child->status] ?? \Illuminate\Support\Str::headline($child->status ?? 'under_review');
                                    $studentType = $typeLabel($child->student_type);

                                    $hasPhoto = filled($child->photo_2x2_url);
                                    $hasBirthCert = filled($child->birth_cert_url);
                                    $hasReportCard = filled($child->report_card_url) || filled($child->affidavit_url);
                                    $docCount = ($hasPhoto ? 1 : 0) + ($hasBirthCert ? 1 : 0) + ($hasReportCard ? 1 : 0);

                                    $familyPayments = $family['family_payments'] ?? collect();
                                    $paymentObj = $familyPayments->first() ?? $child->payment;
                                    $hasReceipt = ($paymentObj && filled($paymentObj->receipt_url)) || $familyPayments->contains(fn($p) => filled($p->receipt_url));
                                    $receiptUrl = $paymentObj && filled($paymentObj->receipt_url) ? \App\Support\EnrollmentStorage::url($paymentObj->receipt_url) : null;
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <span class="font-extrabold uppercase tracking-wide {{ $accent['text'] }}">Child {{ $index + 1 }}</span>
                                    </td>
                                    <td class="px-5 py-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <x-smart-image
                                                :src="$photoUrl"
                                                :alt="$childName"
                                                :fallback-initials="$childInitials ?: 'ST'"
                                                size="40"
                                                rounded="rounded-lg"
                                                containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-extrabold"
                                                :eager="false"
                                            />
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-extrabold text-slate-950">{{ $childName }}</span>
                                                    @php
                                                        $regTime = $child->created_at ? \Illuminate\Support\Carbon::parse($child->created_at) : null;
                                                        $isNewRegistration = $regTime && ($regTime->greaterThanOrEqualTo(now()->subHours(24)) || $regTime->isYesterday() || $regTime->isToday());
                                                    @endphp
                                                    @if ($isNewRegistration)
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-white shadow-3xs" title="Registered {{ $regTime->diffForHumans() }}">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse"></span>
                                                            NEW
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-0.5 text-xs font-medium text-slate-500">Applicant #{{ str_pad($child->id, 4, '0', STR_PAD_LEFT) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-md px-2.5 py-1 text-xs font-extrabold {{ $typeClass($studentType) }}">{{ $studentType }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-bold text-slate-700">{{ $child->grade_abbr ?? 'Not provided' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col gap-1.5 items-start">
                                            <x-badge :color="$childStatusColor[$child->status] ?? 'blue'">{{ $statusLabel }}</x-badge>

                                            <div class="flex flex-wrap items-center gap-1 text-[10px] font-black">
                                                @if ($docCount >= 3)
                                                    <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-emerald-700 border border-emerald-200" title="Photo, Birth Cert & Report Card/Affidavit uploaded">
                                                        <i data-lucide="file-check-2" class="h-3 w-3"></i> Docs (3/3)
                                                    </span>
                                                @elseif ($docCount > 0)
                                                    <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-2 py-0.5 text-amber-700 border border-amber-200" title="{{ $docCount }}/3 documents uploaded">
                                                        <i data-lucide="file-text" class="h-3 w-3"></i> Docs ({{ $docCount }}/3)
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-0.5 text-rose-700 border border-rose-200">
                                                        <i data-lucide="file-x" class="h-3 w-3"></i> No Docs
                                                    </span>
                                                @endif

                                                @if ($hasReceipt)
                                                    <span class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-blue-700 border border-blue-200" title="Proof of Payment Attached">
                                                        <i data-lucide="receipt" class="h-3 w-3"></i> Receipt Attached
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-slate-500 border border-slate-200" title="No payment proof uploaded">
                                                        <i data-lucide="alert-circle" class="h-3 w-3"></i> No Receipt
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" 
                                                @click="openQuickReview({
                                                    id: '{{ $child->id }}',
                                                    formatted_id: '{{ str_pad($child->id, 4, '0', STR_PAD_LEFT) }}',
                                                    name: '{{ e($childName) }}',
                                                    grade: '{{ e($child->grade_abbr ?? 'N/A') }}',
                                                    type: '{{ e($studentType) }}',
                                                    status: '{{ e($child->status) }}',
                                                    status_label: '{{ e($statusLabel) }}',
                                                    photo_url: '{{ $photoUrl }}',
                                                    birth_cert_url: '{{ $child->birth_cert_url ? \App\Support\EnrollmentStorage::url($child->birth_cert_url) : '' }}',
                                                    report_card_url: '{{ $child->report_card_url ? \App\Support\EnrollmentStorage::url($child->report_card_url) : '' }}',
                                                    affidavit_url: '{{ $child->affidavit_url ? \App\Support\EnrollmentStorage::url($child->affidavit_url) : '' }}',
                                                    receipt_url: '{{ $receiptUrl ?: '' }}',
                                                    facebook: '{{ e($child->facebook) }}',
                                                    whatsapp: '{{ e($child->whatsapp) }}',
                                                    facebook_screenshot_url: '{{ $child->facebook_screenshot_url ? \App\Support\EnrollmentStorage::url($child->facebook_screenshot_url) : '' }}',
                                                    payment_remarks: '{{ e($paymentObj?->remarks ?? "") }}',
                                                    parent_name: '{{ e($family['parent_name']) }}',
                                                    parent_email: '{{ e($family['parent_email']) }}',
                                                    parent_mobile: '{{ e($family['parent_mobile']) }}',
                                                    show_url: '{{ route('admin.applicants.show', $child) }}'
                                                })" 
                                                class="inline-flex h-9 items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 shadow-3xs transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer">
                                                <i data-lucide="scan-eye" class="h-4 w-4 text-indigo-600"></i>
                                                Quick View
                                            </button>

                                            <a href="{{ route('admin.applicants.show', $child) }}" title="View full child application" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-emerald-100 bg-emerald-50 px-3 text-xs font-bold text-emerald-700 transition hover:border-emerald-200 hover:bg-emerald-100">
                                                <i data-lucide="eye" class="h-4 w-4"></i>
                                                View Details
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No family applications found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex items-center justify-between gap-4">
                <p class="text-sm font-medium text-slate-500">
                    Showing {{ $families->firstItem() ?? 0 }}-{{ $families->lastItem() ?? 0 }} of {{ $families->total() }} family applications
                </p>
                <div>{{ $families->links() }}</div>
            </div>
        </div>
    </section>

        @unless ($isTeacherAdminViewer)
        <!-- Email Families Registry Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-xs"
             x-show="emailModalOpen" x-cloak x-transition>
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl space-y-4">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-950 uppercase tracking-wider">Email Families Registry</h2>
                        <p class="mt-0.5 text-xs font-semibold text-slate-500">Send compiled applicants report to the administrative office.</p>
                    </div>
                    <button type="button" @click="emailModalOpen = false" class="text-xl font-bold text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <!-- Success / Error / Sending Banners -->
                <div x-show="isSendingEmail" class="p-3.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-800 text-xs font-semibold space-y-2.5 shadow-3xs" x-cloak>
                    <div class="flex items-center gap-2.5 font-extrabold">
                        <span class="btn-spinner animate-spin border-2 border-indigo-600 border-t-transparent rounded-full w-4 h-4 inline-block"></span>
                        <span x-text="progressText"></span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="w-full bg-indigo-200/50 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" :style="{ width: progressPercent + '%' }"></div>
                    </div>
                </div>
                <div x-show="emailSuccess" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-extrabold flex items-center gap-2.5 shadow-3xs" x-cloak>
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                    <span x-text="emailSuccess"></span>
                </div>
                <div x-show="emailError" class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-extrabold flex items-center gap-2.5 shadow-3xs" x-cloak>
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600"></i>
                    <span x-text="emailError"></span>
                </div>

                <!-- Families Registry Dispatch Log (Track per-family send status) -->
                <div x-show="familiesList.length > 0" class="rounded-xl border border-slate-150 bg-slate-50/50 p-3 space-y-2 animate-fade-in" x-cloak>
                    <div class="flex justify-between items-center text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                        <span>Dispatch Log Status</span>
                        <span x-text="`${familiesList.filter(f => f.status === 'sent').length} / ${familiesList.length} Sent`"></span>
                    </div>
                    <div class="max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-white p-1 divide-y divide-slate-100">
                        <template x-for="item in familiesList" :key="item.family_no">
                            <div class="flex items-center justify-between px-2.5 py-1.5 text-[11px]">
                                <span class="font-bold text-slate-700" x-text="item.family_label"></span>
                                <div>
                                    <span x-show="item.status === 'pending'" class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-1.5 py-0.5 font-bold text-slate-500 border border-slate-150">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Pending
                                    </span>
                                    <span x-show="item.status === 'sending'" class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-1.5 py-0.5 font-bold text-indigo-600 border border-indigo-100 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-ping"></span>
                                        Sending
                                    </span>
                                    <span x-show="item.status === 'sent'" class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-1.5 py-0.5 font-bold text-emerald-600 border border-emerald-150">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Sent
                                    </span>
                                    <span x-show="item.status === 'failed'" class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-1.5 py-0.5 font-bold text-rose-600 border border-rose-150">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Failed
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="space-y-4" x-show="!emailSuccess && !isSendingEmail && familiesList.length === 0">
                    <!-- Recipient Email -->
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">1. Recipient Email *</span>
                        <input type="email" x-model="recipientEmail" placeholder="e.g. office@amis.edu.ph" class="mt-1.5 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <!-- Enrollment Fee Payment Status Filter -->
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">2. Enrollment Fee Status Filter *</span>
                        <select x-model="paymentFilter" class="mt-1.5 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            <option value="all">All Families (Paid & Unpaid)</option>
                            <option value="paid">Paid / Verified Only</option>
                            <option value="pending">Pending Payment Only</option>
                            <option value="no_payment">No Payment Only</option>
                        </select>
                    </div>

                    <!-- Families Limit -->
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">3. Families Limit (Leave blank for all)</span>
                        <input type="number" x-model="limitCount" min="1" placeholder="e.g. 50" class="mt-1.5 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <!-- Message Body -->
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">4. Email Message (Intro)</span>
                        <textarea x-model="messageBody" rows="4" placeholder="Enter message intro..." class="mt-1.5 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-bold text-gray-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 resize-none"></textarea>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100" x-show="!emailSuccess && !isSendingEmail">
                    <button type="button" @click="emailModalOpen = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-500 transition hover:bg-slate-50 cursor-pointer">Close</button>
                    <button type="button" @click="sendEmailReport()" :disabled="isSendingEmail || !recipientEmail" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer">
                        <span class="btn-spinner" x-show="isSendingEmail"></span>
                        <i data-lucide="send" class="h-4 w-4" x-show="!isSendingEmail"></i>
                        Send Registry Report
                    </button>
                </div>
            </div>
        </div>
        <!-- Quick Review Modal -->
        <div class="admin-modal-overlay fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-xs"
             x-show="quickReviewModal" x-cloak x-transition>
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-5">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <template x-if="reviewChild?.photo_url">
                            <img :src="reviewChild.photo_url" class="h-12 w-12 rounded-xl object-cover ring-2 ring-emerald-400">
                        </template>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-black text-slate-950" x-text="reviewChild?.name"></h2>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600" x-text="'#' + reviewChild?.formatted_id"></span>
                            </div>
                            <p class="text-xs font-semibold text-slate-500" x-text="(reviewChild?.grade || 'N/A') + ' • ' + (reviewChild?.type || 'Student') + ' • Family of ' + (reviewChild?.parent_name || 'N/A')"></p>
                        </div>
                    </div>
                    <button type="button" @click="quickReviewModal = false" class="text-2xl font-bold text-slate-400 hover:text-slate-600 cursor-pointer">&times;</button>
                </div>

                <!-- Quick Summary Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Left: Contact & Parent Info -->
                    <div class="rounded-xl border border-slate-150 bg-slate-50/60 p-3.5 space-y-2">
                        <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Parent / Contact Info</h3>
                        <div class="text-xs space-y-1">
                            <p><span class="font-bold text-slate-500">Parent:</span> <span class="font-black text-slate-900" x-text="reviewChild?.parent_name"></span></p>
                            <p><span class="font-bold text-slate-500">Email:</span> <span class="font-bold text-indigo-600" x-text="reviewChild?.parent_email || 'None'"></span></p>
                            <p><span class="font-bold text-slate-500">Mobile:</span> <span class="font-bold text-slate-900" x-text="reviewChild?.parent_mobile || 'None'"></span></p>
                            <p><span class="font-bold text-slate-500">Facebook:</span> <span class="font-bold text-slate-900 normal-case" x-text="reviewChild?.facebook || 'None'"></span></p>
                            <p><span class="font-bold text-slate-500">WhatsApp:</span> <span class="font-bold text-slate-900" x-text="reviewChild?.whatsapp || 'None'"></span></p>
                        </div>
                    </div>

                    <!-- Right: Application Status -->
                    <div class="rounded-xl border border-slate-150 bg-slate-50/60 p-3.5 space-y-2 flex flex-col justify-between">
                        <div>
                            <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Application Status</h3>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-800" x-text="reviewChild?.status_label"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attached Files & Receipts Side-by-Side -->
                <div class="space-y-2.5">
                    <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Uploaded Requirements & Proof of Payment</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Documents List -->
                        <div class="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
                            <span class="text-[10px] font-extrabold uppercase text-slate-500 block">Student Documents</span>
                            <div class="space-y-1.5 text-xs">
                                <template x-if="reviewChild?.birth_cert_url">
                                    <a :href="reviewChild.birth_cert_url" target="_blank" class="flex items-center justify-between rounded-lg border border-slate-150 p-2 hover:bg-slate-50 text-indigo-600 font-bold">
                                        <span class="flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Birth Certificate</span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                </template>
                                <template x-if="reviewChild?.report_card_url">
                                    <a :href="reviewChild.report_card_url" target="_blank" class="flex items-center justify-between rounded-lg border border-slate-150 p-2 hover:bg-slate-50 text-indigo-600 font-bold">
                                        <span class="flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Report Card</span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                </template>
                                <template x-if="reviewChild?.affidavit_url">
                                    <a :href="reviewChild.affidavit_url" target="_blank" class="flex items-center justify-between rounded-lg border border-slate-150 p-2 hover:bg-slate-50 text-indigo-600 font-bold">
                                        <span class="flex items-center gap-1.5"><i data-lucide="file-text" class="w-3.5 h-3.5"></i> Affidavit (Temporary)</span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                </template>
                                <template x-if="reviewChild?.facebook_screenshot_url">
                                    <a :href="reviewChild.facebook_screenshot_url" target="_blank" class="flex items-center justify-between rounded-lg border border-slate-150 p-2 hover:bg-slate-50 text-indigo-600 font-bold">
                                        <span class="flex items-center gap-1.5"><i data-lucide="image" class="w-3.5 h-3.5"></i> Facebook Screenshot</span>
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                    </a>
                                </template>
                                <template x-if="!reviewChild?.birth_cert_url && !reviewChild?.report_card_url && !reviewChild?.affidavit_url && !reviewChild?.facebook_screenshot_url">
                                    <p class="text-slate-400 italic text-[11px] py-1">No student documents attached</p>
                                </template>
                            </div>
                        </div>

                        <!-- Payment Receipt Preview -->
                        <div class="rounded-xl border border-slate-200 bg-white p-3 space-y-2">
                            <span class="text-[10px] font-extrabold uppercase text-slate-500 block">Proof of Payment</span>
                            <template x-if="reviewChild?.receipt_url">
                                <a :href="reviewChild.receipt_url" target="_blank" class="block group relative overflow-hidden rounded-lg border border-emerald-200 bg-emerald-50/50 p-2.5 hover:border-emerald-300">
                                    <div class="flex items-center justify-between text-xs font-bold text-emerald-800">
                                        <span class="flex items-center gap-1.5"><i data-lucide="receipt" class="w-3.5 h-3.5 text-emerald-600"></i> Receipt Attached</span>
                                        <span class="text-[10px] text-emerald-600 underline">Open Receipt</span>
                                    </div>
                                </a>
                            </template>
                            <template x-if="!reviewChild?.receipt_url">
                                <div class="rounded-lg border border-dashed border-slate-200 p-3 text-center text-xs text-slate-400 font-medium">
                                    No proof of payment uploaded yet
                                </div>
                            </template>
                            <template x-if="reviewChild?.payment_remarks">
                                <div class="mt-2 text-xs border-t border-slate-100 pt-2">
                                    <span class="text-[10px] font-extrabold uppercase text-slate-400 block">Payment Remarks / Notes</span>
                                    <p class="text-slate-800 normal-case font-medium mt-0.5" x-text="reviewChild.payment_remarks"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <button type="button" @click="quickReviewModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-50 cursor-pointer">Close</button>

                    <a :href="reviewChild?.show_url" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:bg-emerald-700 cursor-pointer">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                        Open Full Review Page
                    </a>
                </div>
            </div>
        </div>
        @endunless
    </div>

    <!-- New applicant polling notification -->
    <div id="new-applicant-banner" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden">
        <div class="flex items-center gap-3 bg-emerald-600 text-white px-5 py-3 rounded-xl shadow-lg">
            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
            <span class="text-sm font-semibold" id="new-applicant-text">New applications received</span>
            <button onclick="location.reload()" class="ml-2 bg-white/20 hover:bg-white/30 px-3 py-1 rounded-lg text-xs font-bold transition">Refresh</button>
            <button onclick="document.getElementById('new-applicant-banner').classList.add('hidden')" class="ml-1 text-white/70 hover:text-white text-lg leading-none">&times;</button>
        </div>
    </div>

    <script>
        (function() {
            let lastCount = {{ $families->total() ?? 0 }};
            setInterval(async () => {
                try {
                    const res = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const text = await res.text();
                    const match = text.match(/data-total-count="(\d+)"/);
                    if (match) {
                        const newCount = parseInt(match[1]);
                        if (newCount > lastCount) {
                            const diff = newCount - lastCount;
                            document.getElementById('new-applicant-text').textContent = diff + ' new application' + (diff > 1 ? 's' : '') + ' received';
                            document.getElementById('new-applicant-banner').classList.remove('hidden');
                            lastCount = newCount;
                        }
                    }
                } catch(e) {}
            }, 30000);
        })();
    </script>
</x-admin-layout>
