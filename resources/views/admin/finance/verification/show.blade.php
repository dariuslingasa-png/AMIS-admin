<x-admin-layout title="Review Payment Proof">
    @php
        $displayAmount = (float) ($receipt->amount ?? $receipt->paymentSubmission?->total_amount ?? 0);
        $ocrConfidence = $receipt->ocr_confidence ?? $receipt->paymentSubmission?->ocr_confidence;
        $statusTone = match ($receipt->status) {
            'APPROVED' => 'bg-emerald-100 text-emerald-800',
            'REJECTED', 'REUPLOAD_REQUIRED' => 'bg-rose-100 text-rose-800',
            'NEEDS_REVIEW' => 'bg-amber-100 text-amber-800',
            default => 'bg-blue-100 text-blue-800',
        };
        $duplicateTone = in_array($receipt->duplicate_status, ['UNIQUE', 'CLEAR'], true)
            ? 'text-emerald-700'
            : 'text-rose-700';
        $familyLastName = collect($receipt->user?->enrollmentApplicants)
            ->pluck('last_name')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();
        if (! $familyLastName) {
            $familyNameParts = preg_split('/[\s.,]+/', trim((string) $receipt->user?->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $familyLastName = end($familyNameParts) ?: 'FAMILY ACCOUNT';
        }
        $transactionReference = $receipt->reference_number ?: $receipt->paymentSubmission?->reference_no;
        $childrenCount = $receipt->user?->students?->count() ?? 0;
    @endphp

    <div class="finance-page mx-auto max-w-[1420px] p-4 sm:p-5 lg:p-8">
        @include('admin.finance._nav', [
            'title' => 'Review Payment Proof',
            'subtitle' => 'Confirm the receipt, check the extracted details, then let AMIS allocate approved payments oldest-first.',
        ])

        <section class="mb-4 rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm lg:px-7 lg:py-6">
            <div class="grid gap-6 xl:grid-cols-3 xl:items-center">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-black uppercase tracking-[.14em] text-emerald-700">Family account</p>
                        <span class="rounded-full px-3 py-1.5 text-xs font-black {{ $statusTone }}">
                            {{ str_replace('_', ' ', $receipt->status) }}
                        </span>
                    </div>
                    <h2 class="mt-2 break-words text-2xl font-black uppercase tracking-tight text-slate-900 lg:text-3xl">{{ $familyLastName }}</h2>
                    <p class="mt-1 break-all text-base font-medium text-slate-500">{{ $receipt->user?->email }}</p>
                </div>

                <div class="py-3 text-center xl:px-6">
                    <p class="text-xs font-black uppercase tracking-[.1em] text-slate-500">Family outstanding</p>
                    <p class="mt-2 text-3xl font-black text-slate-950">₱{{ number_format($totalOutstanding, 2) }}</p>
                </div>

                <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2 xl:border-l xl:border-slate-200 xl:pl-8">
                    <div class="sm:col-span-2">
                        @php
                            $verificationOr = $receipt->paymentSubmission?->financeTransaction?->officialReceipt?->official_receipt_number;
                        @endphp
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">{{ $verificationOr ? 'Official Receipt No.' : 'Submission No.' }}</dt>
                        <dd class="mt-1.5 break-all text-base font-black text-slate-900">{{ $verificationOr ?: ($receipt->paymentSubmission?->submission_number ?? 'Submitted receipt') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Children</dt>
                        <dd class="mt-1.5 text-xl font-black text-slate-900">{{ $childrenCount }} {{ Str::plural('child', $childrenCount) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Duplicate check</dt>
                        <dd class="mt-1.5 text-base font-black {{ $duplicateTone }}">{{ str_replace('_', ' ', $receipt->duplicate_status ?: 'NOT CHECKED') }}</dd>
                    </div>
                </dl>
            </div>

            <dl class="mt-6 grid gap-5 border-t-2 border-slate-200 pt-5 sm:grid-cols-2 sm:items-end">
                <div class="min-w-0">
                    <dt class="text-sm font-black uppercase tracking-[.08em] text-slate-500">Transaction / Reference number</dt>
                    <dd class="mt-2 break-all text-xl font-black tracking-wide text-slate-950 lg:text-2xl">{{ $transactionReference ?: 'NOT RECORDED' }}</dd>
                </div>
                <div class="sm:text-right">
                    <dt class="text-sm font-black uppercase tracking-[.08em] text-slate-500">Amount received</dt>
                    <dd class="mt-2 text-3xl font-black text-emerald-800 lg:text-4xl">₱{{ number_format($displayAmount, 2) }}</dd>
                </div>
            </dl>
        </section>

        <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,.88fr)_minmax(460px,1.12fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:sticky xl:top-5">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Original payment proof</p>
                        <p class="mt-1 truncate font-extrabold text-slate-900">{{ $receipt->original_filename }}</p>
                    </div>
                    <a href="{{ route('admin.finance.verification.original', $receipt) }}" target="_blank" rel="noopener"
                       class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-extrabold text-slate-700 transition hover:border-emerald-600 hover:text-emerald-700">
                        Open full image ↗
                    </a>
                </div>

                @if (str_starts_with($receipt->original_mime, 'image/'))
                    <div x-data="{ previewFailed: false }" class="relative flex h-[320px] items-center justify-center overflow-hidden bg-slate-100 p-3 sm:h-[460px] sm:p-4 2xl:h-[560px]">
                        <a x-show="!previewFailed" href="{{ route('admin.finance.verification.original', $receipt) }}" target="_blank" rel="noopener" class="flex h-full w-full items-center justify-center">
                            <img src="{{ route('admin.finance.verification.original', $receipt) }}"
                                 alt="Original payment receipt"
                                 class="max-h-full max-w-full rounded-xl bg-white object-contain shadow-sm"
                                 x-on:error="previewFailed = true">
                        </a>
                        <div x-cloak x-show="previewFailed" class="max-w-sm rounded-2xl border border-amber-200 bg-white p-6 text-center shadow-sm">
                            <p class="font-extrabold text-slate-900">Preview could not be loaded</p>
                            <p class="mt-1 text-sm text-slate-500">Open the original file in a new tab to continue reviewing it.</p>
                        </div>
                    </div>
                @else
                    <div class="p-10 text-center text-sm text-rose-700">Unsupported legacy file. New uploads accept JPG, JPEG, and PNG only; PDF is disabled.</div>
                @endif

                <div class="grid grid-cols-2 gap-3 border-t border-slate-100 px-5 py-4 text-sm">
                    <div>
                        <p class="text-xs font-bold text-slate-500">OCR confidence</p>
                        <p class="mt-1 font-extrabold text-slate-900">{{ $ocrConfidence !== null ? number_format((float) $ocrConfidence * 100, 1).'%' : 'Not available' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500">Uploaded</p>
                        <p class="mt-1 font-extrabold text-slate-900">{{ optional($receipt->created_at)->format('M d, Y · h:i A') }}</p>
                    </div>
                </div>
            </section>

            <div class="space-y-4">
                <form method="POST" action="{{ route('admin.finance.verification.update', $receipt) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                    @csrf
                    @method('PATCH')

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">Step 1</p>
                            <h2 class="mt-1 text-lg font-black text-slate-900">Check transaction details</h2>
                            <p class="mt-1 text-sm text-slate-500">Correct only fields that do not match the original receipt.</p>
                            <div class="mt-2 flex flex-wrap gap-1.5"><span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700">Payment Source: Online Payment</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Payment Method: {{ $receipt->provider ?: 'Other' }}</span></div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-extrabold text-slate-700">
                            Payment provider
                            <input name="provider" value="{{ old('provider', $receipt->provider) }}"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 shadow-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                        </label>
                        <label class="block text-sm font-extrabold text-slate-700">
                            Transaction / Reference number
                            <input name="reference_number" value="{{ old('reference_number', $receipt->reference_number) }}"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 shadow-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                        </label>
                        <label class="block text-sm font-extrabold text-slate-700">
                            Amount received
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center font-black text-slate-500">₱</span>
                                <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $displayAmount) }}"
                                       class="h-12 w-full rounded-xl border border-slate-300 bg-white pl-9 pr-4 text-base font-extrabold text-slate-900 shadow-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                            </div>
                        </label>
                        <label class="block text-sm font-extrabold text-slate-700">
                            Transaction date
                            <input name="transaction_date" type="date" value="{{ old('transaction_date', optional($receipt->transaction_date)->format('Y-m-d')) }}"
                                   class="mt-1.5 h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-900 shadow-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                        </label>
                    </div>

                    <details class="group mt-4 rounded-xl border border-slate-200 bg-slate-50">
                        <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-extrabold text-slate-700">
                            Additional OCR details
                            <span class="text-slate-400 transition group-open:rotate-180">⌄</span>
                        </summary>
                        <dl class="grid gap-3 border-t border-slate-200 px-4 py-3 text-xs sm:grid-cols-2">
                            <div><dt class="text-slate-500">Currency / time</dt><dd class="mt-1 break-words font-bold text-slate-800">{{ $receipt->currency ?: 'PHP' }} · {{ $receipt->transaction_time ?: 'Not extracted' }}</dd></div>
                            <div><dt class="text-slate-500">AI validation</dt><dd class="mt-1 break-words font-bold text-slate-800">{{ data_get($receipt->validation_results, 'summary', data_get($receipt->structured_ocr, 'type', 'Review extracted fields')) }}</dd></div>
                            <div><dt class="text-slate-500">Sender text</dt><dd class="mt-1 break-words font-bold text-slate-800">{{ $receipt->sender_name ?: 'Not extracted' }}</dd></div>
                            <div><dt class="text-slate-500">Receiver text</dt><dd class="mt-1 break-words font-bold text-slate-800">{{ $receipt->receiver_name ?: 'Not extracted' }}</dd></div>
                        </dl>
                    </details>

                    @if ($receipt->review_reason)
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>Current review note:</strong> {{ $receipt->review_reason }}</p>
                    @endif

                    <label class="mt-4 block text-sm font-extrabold text-slate-700">
                        Correction reason
                        <textarea name="correction_reason" rows="2"
                                  class="mt-1.5 min-h-20 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                                  placeholder="Required when saving corrected OCR fields">{{ old('correction_reason') }}</textarea>
                    </label>

                    <div class="mt-4 flex justify-end">
                        <button class="w-full rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-extrabold text-slate-700 transition hover:border-emerald-600 hover:text-emerald-700 sm:w-auto"
                                @disabled($receipt->status === 'APPROVED')>
                            Save corrected fields
                        </button>
                    </div>
                </form>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">Automatic allocation preview</p>
                            <h2 class="mt-1 text-lg font-black text-slate-900">Oldest outstanding month first</h2>
                        </div>
                        <span class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 sm:inline">No manual selection</span>
                    </div>

                    @if ($preview)
                        <div class="mt-4 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200">
                            @forelse ($preview['allocations'] as $allocation)
                                <div class="grid gap-2 px-4 py-3 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div class="min-w-0">
                                        <p class="truncate font-extrabold text-slate-800">{{ $allocation['billing']->month_name }} · {{ $allocation['billing']->student?->applicant?->full_name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">₱{{ number_format($allocation['balance_before'], 2) }} before · ₱{{ number_format($allocation['remaining_after'], 2) }} remaining</p>
                                    </div>
                                    <p class="font-black text-emerald-800">₱{{ number_format($allocation['applied_amount'], 2) }}</p>
                                </div>
                            @empty
                                <div class="px-4 py-6 text-center text-sm text-slate-500">No outstanding billing. The full amount becomes advance credit.</div>
                            @endforelse
                        </div>

                        <dl class="mt-3 grid grid-cols-1 gap-2 text-center text-xs sm:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-3"><dt class="text-slate-500">Allocated</dt><dd class="mt-1 font-black text-slate-900">₱{{ number_format($preview['allocated_amount'], 2) }}</dd></div>
                            <div class="rounded-xl bg-violet-50 p-3"><dt class="text-violet-600">Advance credit</dt><dd class="mt-1 font-black text-violet-900">₱{{ number_format($preview['advance_credit'], 2) }}</dd></div>
                            <div class="rounded-xl bg-amber-50 p-3"><dt class="text-amber-700">Balance after</dt><dd class="mt-1 font-black text-amber-900">₱{{ number_format($preview['family_balance_after'], 2) }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Save a valid amount first to see the allocation preview.</p>
                    @endif
                </section>

                @if ($receipt->status !== 'APPROVED')
                    <section class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm lg:p-6">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">Step 2</p>
                            <h2 class="mt-1 text-lg font-black text-slate-900">Finance decision</h2>
                            <p class="mt-1 text-sm text-slate-500">Approve only when the proof and extracted details match.</p>
                        </div>

                        <form method="POST" action="{{ route('admin.finance.verification.action', $receipt) }}"
                              x-data="{ rejectOpen: false, rejectRemark: '' }"
                              x-on:keydown.escape.window="rejectOpen = false"
                              class="mt-4">
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px]">
                                <button name="action" value="approve"
                                        class="rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-emerald-800">
                                    APPROVE ₱{{ number_format($displayAmount, 2) }} AND ALLOCATE AUTOMATICALLY
                                </button>
                                <button type="button" x-on:click="rejectOpen = true; $nextTick(() => $refs.rejectRemark.focus())"
                                        class="rounded-xl bg-rose-700 px-5 py-3.5 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-rose-800">
                                    REJECT PAYMENT PROOF
                                </button>
                            </div>

                            <div x-cloak x-show="rejectOpen" x-transition.opacity
                                 x-on:click.self="rejectOpen = false"
                                 class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 p-4"
                                 role="dialog" aria-modal="true" aria-labelledby="reject-payment-title">
                                <div x-show="rejectOpen" x-transition
                                     class="max-h-[calc(100dvh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-4 shadow-2xl sm:p-6">
                                    <p class="text-xs font-black uppercase tracking-[.12em] text-rose-700">Finance decision</p>
                                    <h3 id="reject-payment-title" class="mt-1 text-2xl font-black text-slate-950">Reject payment proof</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Enter a clear remark for the parent. The payment will return to them for correction or re-upload.</p>

                                    <label class="mt-5 block text-sm font-black text-slate-800" for="reject-payment-remark">
                                        Rejection remark <span class="text-rose-700">Required</span>
                                    </label>
                                    <textarea id="reject-payment-remark" name="reason" x-ref="rejectRemark" x-model="rejectRemark"
                                              rows="4" minlength="8" maxlength="1000"
                                              class="mt-2 min-h-28 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 focus:border-rose-600 focus:ring-2 focus:ring-rose-100"
                                              placeholder="Explain why this payment proof is being rejected"></textarea>
                                    <p class="mt-1.5 text-xs text-slate-500">Minimum of 8 characters.</p>

                                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                        <button type="button" x-on:click="rejectOpen = false"
                                                class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-black uppercase text-slate-700 hover:bg-slate-50">
                                            CANCEL
                                        </button>
                                        <button name="action" value="reject"
                                                x-bind:disabled="rejectRemark.trim().length < 8"
                                                class="rounded-xl bg-rose-700 px-5 py-3 text-sm font-black uppercase text-white transition hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-45">
                                            CONFIRM REJECTION
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>
