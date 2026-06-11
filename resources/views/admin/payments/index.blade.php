<x-admin-layout title="Enrollment Payment Approval">
    @php
        $sortLink = function (string $key) use ($sort, $direction) {
            $nextDirection = $sort === $key && $direction === 'asc' ? 'desc' : 'asc';

            return request()->fullUrlWithQuery([
                'sort' => $key,
                'direction' => $nextDirection,
                'page' => null,
            ]);
        };

        $sortIcon = fn (string $key) => $sort === $key
            ? ($direction === 'asc' ? 'arrow-up' : 'arrow-down')
            : 'arrow-up-down';
    @endphp

    <div x-data="{
        proofOpen: false,
        proofSrc: '',
        proofLabel: '',
        proofIsPdf: false,
        proofZoom: 1,
        panning: false,
        panEl: null,
        panX: 0,
        panY: 0,
        panLeft: 0,
        panTop: 0,
        
        proofStatus: '',
        proofAmount: '',
        proofMethod: '',
        proofPaymentDate: '',
        proofReferenceNo: '',
        proofInvoiceNo: '',
        predictedOr: '',
        remittanceSource: '',
        isSubmitting: false,
        showRejectForm: false,
        remarks: '',

        openProof(url, label, isPdf, status = '', amount = '', method = '', date = '', ref = '', invoiceNo = '', predictedOr = '', verifyUrl = '', rejectUrl = '') {
            this.proofSrc = url;
            this.proofLabel = label;
            this.proofIsPdf = isPdf;
            this.proofZoom = 1;
            
            this.proofStatus = status;
            this.proofAmount = amount;
            this.proofMethod = method;
            this.proofPaymentDate = date;
            this.proofReferenceNo = ref;
            this.proofInvoiceNo = invoiceNo;
            this.predictedOr = predictedOr;
            this.remittanceSource = '';
            this.isSubmitting = false;
            this.showRejectForm = false;
            this.remarks = '';
            
            this.proofOpen = true;

            setTimeout(() => {
                const approveForm = document.getElementById('modal-approve-form-index');
                const rejectForm = document.getElementById('modal-reject-form-index');
                if (approveForm) approveForm.action = verifyUrl;
                if (rejectForm) rejectForm.action = rejectUrl;
            }, 50);
        },
        closeProof() {
            this.proofOpen = false;
            this.proofZoom = 1;
            this.stopPan();
        },
        zoomIn() { this.proofZoom = Math.min(3, Number((this.proofZoom + 0.1).toFixed(2))); },
        zoomOut() { this.proofZoom = Math.max(0.1, Number((this.proofZoom - 0.1).toFixed(2))); },
        resetZoom() { this.proofZoom = 1; },
        startPan(event) {
            if (this.proofIsPdf) return;
            const point = event.touches ? event.touches[0] : event;
            this.panning = true;
            this.panEl = event.currentTarget;
            this.panX = point.pageX;
            this.panY = point.pageY;
            this.panLeft = this.panEl.scrollLeft;
            this.panTop = this.panEl.scrollTop;
            this.panEl.classList.add('cursor-grabbing');
        },
        movePan(event) {
            if (!this.panning || !this.panEl) return;
            event.preventDefault();
            const point = event.touches ? event.touches[0] : event;
            this.panEl.scrollLeft = this.panLeft - (point.pageX - this.panX);
            this.panEl.scrollTop = this.panTop - (point.pageY - this.panY);
        },
        stopPan() {
            if (this.panEl) this.panEl.classList.remove('cursor-grabbing');
            this.panning = false;
            this.panEl = null;
        }
    }"
    x-effect="document.body.classList.toggle('overflow-hidden', proofOpen)"
    @keydown.escape.window="closeProof()"
    @mouseup.window="stopPan()"
    @touchend.window="stopPan()">

    <x-card title="Enrollment Payment Approval" subtitle="Finance Management by {{ config('services.school.finance_reviewer_name', 'Finance Office') }}">
        <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
            <form method="GET" class="grid gap-3 xl:grid-cols-[minmax(280px,1fr)_180px_150px_120px_auto]">
                <label class="relative block">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search family, child, OR, reference, method..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'verified', 'rejected'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected(request('status') === $statusOption)>{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>

                <select name="sort" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    @foreach (['updated' => 'Latest update', 'family' => 'Family', 'children' => 'Children', 'amount' => 'Amount', 'method' => 'Method', 'status' => 'Status'] as $sortValue => $sortLabel)
                        <option value="{{ $sortValue }}" @selected($sort === $sortValue)>{{ $sortLabel }}</option>
                    @endforeach
                </select>

                <select name="per_page" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    @foreach ([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} rows</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-700 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-800">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Filter
                    </button>
                    @if (request()->hasAny(['search', 'status', 'sort', 'direction', 'per_page']))
                        <a href="{{ route('admin.payments.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-600 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Families</div>
                <div class="mt-1 text-xl font-black text-slate-950">{{ number_format($paymentSummary['families']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Children</div>
                <div class="mt-1 text-xl font-black text-slate-950">{{ number_format($paymentSummary['children']) }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400">Amount</div>
                <div class="mt-1 text-xl font-black text-slate-950">{{ number_format((float) $paymentSummary['amount'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-amber-500">Pending</div>
                <div class="mt-1 text-xl font-black text-amber-700">{{ number_format($paymentSummary['pending']) }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-emerald-500">Verified</div>
                <div class="mt-1 text-xl font-black text-emerald-700">{{ number_format($paymentSummary['verified']) }}</div>
            </div>
            <div class="rounded-xl border border-rose-100 bg-rose-50 p-3">
                <div class="text-[10px] font-black uppercase tracking-wider text-rose-500">Rejected</div>
                <div class="mt-1 text-xl font-black text-rose-700">{{ number_format($paymentSummary['rejected']) }}</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="bg-white text-[11px] font-black uppercase tracking-widest text-slate-400">
                    <tr class="border-b border-slate-100">
                        @foreach ([
                            'family' => 'Family / Applicant',
                            'children' => 'Children',
                            'grade' => 'Grade',
                            'amount' => 'Amount',
                            'method' => 'Method',
                            'status' => 'Status',
                            'updated' => 'Updated',
                        ] as $key => $label)
                            <th class="px-4 py-3">
                                <a href="{{ $sortLink($key) }}" class="inline-flex items-center gap-1.5 transition hover:text-emerald-700">
                                    {{ $label }}
                                    <i data-lucide="{{ $sortIcon($key) }}" class="h-3.5 w-3.5"></i>
                                </a>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($paymentFamilies as $family)
                        @php
                            $payment = $family['payment'];
                            $children = $family['children'];
                            $familyNo = $family['family_no'];
                            $familyLabel = $family['family_label'];
                            $familyStatus = $family['status'];
                            $statusColor = $familyStatus === 'verified' ? 'green' : ($familyStatus === 'rejected' ? 'red' : 'yellow');

                            // Find the first payment with a receipt_url for the View Proof button
                            $proofPayment = $family['payments']->first(fn ($p) => filled($p->receipt_url));
                            $proofUrl = $proofPayment ? \App\Support\EnrollmentStorage::url($proofPayment->receipt_url) : null;
                            $proofIsPdf = $proofPayment && $proofPayment->receipt_url && strtolower(pathinfo($proofPayment->receipt_url, PATHINFO_EXTENSION)) === 'pdf';
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-4 py-4 align-top">
                                <div class="font-black text-slate-950">{{ $familyLabel }}</div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">
                                    <span>Family #{{ str_pad((string) $familyNo, 4, '0', STR_PAD_LEFT) }}</span>
                                    @if ($children->count() > 1)
                                        <span>&middot; {{ $children->count() }} children</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex max-w-xl flex-wrap gap-1.5">
                                    @forelse ($children as $child)
                                        @php
                                            $childStatus = strtolower((string) ($child->payment?->status ?? 'missing'));
                                            $childChip = match ($childStatus) {
                                                'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                                                'rejected' => 'bg-rose-50 text-rose-700 ring-rose-100',
                                                'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
                                                default => 'bg-slate-100 text-slate-600 ring-slate-200',
                                            };
                                        @endphp
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-1 {{ $childChip }}">
                                            {{ $child->full_name ?: 'Applicant' }}
                                        </span>
                                    @empty
                                        <span class="text-xs font-semibold text-slate-400">No child record</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex max-w-xs flex-wrap gap-1.5">
                                    @forelse ($children as $child)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                            {{ $child->grade_level ?: 'N/A' }}
                                        </span>
                                    @empty
                                        <span class="text-xs font-semibold text-slate-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top font-semibold tabular-nums text-slate-700">{{ number_format((float) $family['amount'], 2) }}</td>
                            <td class="px-4 py-4 align-top font-semibold text-slate-700">{{ $family['methods']->isNotEmpty() ? $family['methods']->join(', ') : '-' }}</td>
                            <td class="px-4 py-4 align-top"><x-badge color="{{ $statusColor }}">{{ Str::upper($familyStatus) }}</x-badge></td>
                            <td class="px-4 py-4 align-top font-semibold text-slate-500">{{ optional($family['updated_at'])->format('M d, Y') }}</td>
                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($proofUrl)
                                        @php
                                            // Calculate predicted OR number for representative payment
                                            $representativeInvoice = \App\Models\Invoice::getOrCreateForFamily($payment->applicant);
                                            $baseOr = str_replace('INV-', config('services.school.or_prefix', 'OR-'), $representativeInvoice->invoice_no);
                                            $verifiedCount = $representativeInvoice->payments()->where('status', 'verified')->count();
                                            if ($verifiedCount === 0) {
                                                $isFull = ((float)$payment->amount >= (float)$representativeInvoice->total_amount);
                                                $pOr = $isFull ? $baseOr : $baseOr . '-1';
                                            } else {
                                                $pOr = $baseOr . '-' . ($verifiedCount + 1);
                                            }
                                        @endphp
                                        <button type="button"
                                                @click="openProof('{{ $proofUrl }}', '{{ addslashes($familyLabel) }}', {{ $proofIsPdf ? 'true' : 'false' }}, '{{ strtolower($familyStatus) }}', '{{ $payment->amount }}', '{{ in_array(strtolower($payment->method), ['remittance', 'gcash', 'bdo', 'maya', 'cash']) ? strtolower($payment->method) : 'other' }}', '{{ $payment->paid_at?->format('Y-m-d') ?: ($payment->created_at?->format('Y-m-d') ?: now()->format('Y-m-d')) }}', '{{ $payment->reference_no }}', '{{ $representativeInvoice->invoice_no }}', '{{ $pOr }}', '{{ route('admin.payments.verify', $payment) }}', '{{ route('admin.payments.reject', $payment) }}')"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-sky-50 px-3 py-2 text-xs font-black uppercase tracking-wider text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100 hover:text-sky-800 cursor-pointer">
                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                            View Proof
                                        </button>
                                    @endif
                                    @if ($payment->applicant)
                                        <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-black uppercase tracking-wider text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">
                                            Review
                                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-14 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <i data-lucide="search-x" class="h-6 w-6"></i>
                                    </span>
                                    <div class="mt-3 text-sm font-black text-slate-700">No payment families found</div>
                                    <div class="mt-1 text-xs font-semibold text-slate-400">Adjust the search or filters to see more records.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">{{ $paymentFamilies->links() }}</div>
    </x-card>

    {{-- Payment Proof Preview Modal --}}
    <div x-show="proofOpen" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
        <div class="relative max-h-[92vh] w-full max-w-7xl overflow-hidden rounded-3xl bg-white shadow-2xl flex flex-col">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 bg-white">
                <h2 class="font-black text-slate-950" x-text="proofLabel"></h2>
                <div class="ml-auto flex items-center gap-2">
                    <div class="flex items-center gap-2" x-show="!proofIsPdf">
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="zoomOut()">-</button>
                        <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(proofZoom * 100) + '%'"></span>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="zoomIn()">+</button>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="resetZoom()">Reset</button>
                    </div>
                    <a :href="proofSrc" target="_blank" rel="noopener noreferrer" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Open Full
                    </a>
                    <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 cursor-pointer" @click="closeProof()">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex flex-1 min-h-0 overflow-hidden">
                <!-- Left Side: Receipt Proof Viewer -->
                <div class="flex-1 cursor-grab select-none overflow-auto bg-slate-50 p-4"
                     @mousedown="startPan($event)"
                     @mousemove="movePan($event)"
                     @mouseleave="stopPan()"
                     @touchstart.passive="startPan($event)"
                     @touchmove="movePan($event)">
                    <template x-if="proofIsPdf">
                        <iframe :src="proofSrc" class="h-[72vh] w-full rounded-2xl bg-white border-0"></iframe>
                    </template>
                    <template x-if="!proofIsPdf">
                        <img :src="proofSrc" :alt="proofLabel" class="mx-auto rounded-2xl object-contain transition-all duration-150" :style="'max-width: none; width: ' + (proofZoom * 100) + '%; height: auto;'">
                    </template>
                </div>

                <!-- Right Side: Verify Form Panel -->
                <div x-show="proofStatus === 'pending'" class="w-96 border-l border-slate-200 bg-white p-5 flex flex-col justify-between overflow-y-auto select-text">
                    <!-- Verification Form -->
                    <form id="modal-approve-form-index" action="" method="POST" @submit="isSubmitting = true" class="space-y-4 text-left">
                        @csrf
                        @method('PATCH')

                        <div class="border-b border-slate-100 pb-3 mb-2">
                            <h3 class="font-black text-slate-900 uppercase tracking-wider text-[15px] flex items-center gap-1.5">
                                <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600"></i>
                                Verify Details
                            </h3>
                        </div>

                        <div>
                            <span class="text-[11.5px] text-slate-400 font-bold uppercase tracking-wider block">Invoice No</span>
                            <div class="font-black text-slate-900 mt-0.5 text-base" x-text="proofInvoiceNo"></div>
                        </div>

                        <div class="grid grid-cols-1 gap-3.5">
                            <div>
                                <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Method of Payment</label>
                                <select name="finance_method" x-model="proofMethod" required class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                                    <option value="remittance">Remittance</option>
                                    <option value="gcash">GCash</option>
                                    <option value="bdo">BDO Bank Transfer</option>
                                    <option value="maya">Maya</option>
                                    <option value="cash">Cash</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Payment Date</label>
                                <input type="date" name="finance_payment_date" x-model="proofPaymentDate" required class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Amount</label>
                                <input type="number" step="0.01" name="finance_amount" x-model="proofAmount" required class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            </div>
                            <div>
                                <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Reference No</label>
                                <input type="text" name="finance_reference_no" x-model="proofReferenceNo" placeholder="e.g. 105251011098847" class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                            </div>
                        </div>

                        <div x-show="proofMethod === 'remittance'" x-transition>
                            <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Remittance Source</label>
                            <input type="text" name="remittance_source" x-model="remittanceSource" placeholder="e.g. AL GHURAIR EXCHANGE" :required="proofMethod === 'remittance'" class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>

                        <div class="space-y-1.5 rounded-xl bg-emerald-50 border border-emerald-100 p-3.5 mt-2">
                            <span class="text-[11.5px] text-emerald-700 font-bold uppercase tracking-wider block">OR to be Generated:</span>
                            <div class="font-black text-emerald-800 text-lg" x-text="predictedOr"></div>
                            <p class="text-[11px] text-emerald-600 font-semibold mt-0.5 leading-normal">Automatically calculated based on payment sequence rules.</p>
                        </div>

                        <!-- Action Buttons inside Panel -->
                        <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
                            <button type="submit" :disabled="isSubmitting" class="w-full btn-premium btn-approve cursor-pointer justify-center py-2.5">
                                <span x-show="!isSubmitting">Confirm Verify</span>
                                <span x-show="isSubmitting" class="flex items-center gap-1.5 justify-center">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Reject Section -->
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <button type="button" x-show="!showRejectForm" @click="showRejectForm = true" class="w-full btn-premium btn-reject justify-center py-2 cursor-pointer">
                            Reject Payment
                        </button>

                        <div x-show="showRejectForm" x-transition class="space-y-3">
                            <form id="modal-reject-form-index" action="" method="POST" @submit="isSubmitting = true">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="block text-[11.5px] font-bold uppercase tracking-wider text-rose-800 mb-1">Rejection Remarks</label>
                                    <textarea name="remarks" x-model="remarks" required placeholder="Reason for rejection..." class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none" rows="2"></textarea>
                                </div>
                                <div class="flex gap-2 mt-2">
                                    <button type="button" @click="showRejectForm = false" class="flex-1 rounded-xl border border-slate-200 bg-white py-1.5 text-xs font-bold text-slate-700 cursor-pointer">Cancel</button>
                                    <button type="submit" class="flex-1 rounded-xl bg-rose-600 py-1.5 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700 cursor-pointer">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- close x-data --}}
</x-admin-layout>
