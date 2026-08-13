<x-admin-layout title="Record Onsite Payment">
    <div class="finance-page mx-auto max-w-[1400px] p-5 lg:p-8">
        @include('admin.finance._nav', [
            'title' => 'Record Onsite Payment',
            'subtitle' => $family
                ? 'Confirm the payment details. AMIS will allocate the amount to the oldest family balance automatically.'
                : 'Start by finding the parent or family account receiving the payment.',
            'icon' => 'hand-coins',
        ])

        @if (! $family)
            <section class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5 sm:px-7">
                    <div class="flex items-start gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-800">1</span>
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Find the family account</h2>
                            <p class="mt-1 text-sm text-slate-500">Search using a parent name, student name, email address, or AMIS student ID.</p>
                        </div>
                    </div>

                    <form class="mt-5 flex flex-col gap-2.5 sm:flex-row">
                        <div class="relative min-w-0 flex-1">
                            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input name="q" value="{{ request('q') }}" autofocus placeholder="Example: Zhairel, parent@email.com, or AMIS-2026-001" class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-10 pr-4 text-sm focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100">
                        </div>
                        <button class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 text-sm font-extrabold text-white hover:bg-slate-800">
                            Search
                        </button>
                    </form>
                </div>

                @if ($families->isNotEmpty())
                    <div class="px-6 py-4 sm:px-7">
                        <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Search results</p>
                        <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                            @foreach ($families as $result)
                                <a href="{{ route('admin.finance.onsite.create', ['family' => $result->id, 'q' => request('q')]) }}" class="group flex items-center justify-between gap-4 px-4 py-3.5 hover:bg-emerald-50">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-black text-slate-600 group-hover:bg-emerald-100 group-hover:text-emerald-800">{{ mb_substr($result->name, 0, 1) }}</span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate font-bold text-slate-800">{{ $result->name }}</p>
                                                @if ($result->is_demo ?? false)
                                                    <span class="rounded-full border border-amber-300 bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase text-amber-800">DEMO DATA</span>
                                                @endif
                                            </div>
                                            <p class="truncate text-xs text-slate-500">{{ $result->email }} · {{ $result->enrollment_applicants_count }} student(s)</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center gap-1 text-sm font-bold text-emerald-700">Select <span>→</span></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif (request()->filled('q'))
                    <div class="px-7 py-10 text-center">
                        <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400"><i data-lucide="search-x" class="h-5 w-5"></i></span>
                        <p class="mt-3 font-bold text-slate-700">No family account found</p>
                        <p class="mt-1 text-sm text-slate-500">Check the spelling or try the parent email or student ID.</p>
                    </div>
                @else
                    <div class="grid gap-3 bg-slate-50 px-6 py-4 text-xs text-slate-500 sm:grid-cols-3 sm:px-7">
                        <div class="flex items-center gap-2"><i data-lucide="user-round" class="h-4 w-4 text-emerald-600"></i> Parent or guardian</div>
                        <div class="flex items-center gap-2"><i data-lucide="graduation-cap" class="h-4 w-4 text-emerald-600"></i> Student name or ID</div>
                        <div class="flex items-center gap-2"><i data-lucide="mail" class="h-4 w-4 text-emerald-600"></i> Registered email</div>
                    </div>
                @endif
            </section>
        @else
            <div class="space-y-5">
                <div class="grid items-start gap-5 xl:grid-cols-[minmax(360px,.78fr)_minmax(0,1.22fr)]">
                <aside>
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-black text-emerald-800">{{ mb_substr($family->name, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Selected family</p>
                                        @if ($family->is_demo ?? false)
                                            <span class="rounded-full border border-amber-300 bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase text-amber-800">DEMO DATA</span>
                                        @endif
                                    </div>
                                    <h2 class="truncate font-extrabold text-slate-900">{{ $family->name }}</h2>
                                    <p class="truncate text-xs text-slate-500">{{ $family->email }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.finance.onsite.create') }}" class="shrink-0 text-xs font-bold text-slate-500 hover:text-emerald-700">Change</a>
                        </div>

                        <div class="space-y-3 p-5">
                            <div class="flex items-center justify-between gap-4 text-sm"><span class="font-semibold text-slate-600"><strong class="text-slate-800">{{ $previousPeriodLabel }}</strong> · Previous Charge</span><strong class="shrink-0 text-slate-900">₱{{ number_format($previousBalance, 2) }}</strong></div>
                            <div class="flex items-center justify-between gap-4 text-sm"><span class="font-semibold text-slate-600"><strong class="text-emerald-800">{{ $currentPeriodLabel }}</strong> · Current Month</span><strong class="shrink-0 text-slate-900">₱{{ number_format($currentCharges, 2) }}</strong></div>
                            <div class="flex items-end justify-between border-t border-slate-200 pt-3">
                                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total amount due</p><p class="mt-0.5 text-[11px] text-slate-400">Previous + current month</p></div>
                                <p class="text-2xl font-black text-slate-900">₱{{ number_format($totalAmountDue, 2) }}</p>
                            </div>
                        </div>
                    </section>
                </aside>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex items-start gap-3 border-b border-slate-100 pb-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-800">2</span>
                        <div><h2 class="text-lg font-extrabold text-slate-900">Enter payment details</h2><p class="mt-0.5 text-sm text-slate-500">Cash needs no proof and records the confirmation time automatically. Digital and remittance payments require a clear transaction screenshot.</p></div>
                    </div>

                    @php
                        $initialMethod = old('payment_method', 'cash');
                        $initialPaymentType = in_array($initialMethod, ['gcash', 'maya', 'bdo', 'bank_transfer', 'other'], true)
                            ? 'digital'
                            : $initialMethod;
                    @endphp
                    <form id="financeOnsiteForm" method="POST" action="{{ route('admin.finance.onsite.store') }}" data-duplicate-url="{{ route('admin.finance.onsite.duplicate-check') }}" enctype="multipart/form-data" autocomplete="off" class="mt-5 space-y-4" x-data="{ method: {{ Js::from($initialMethod) }}, paymentType: {{ Js::from($initialPaymentType) }}, fileName: '' }" @finance-file-cleared.window="fileName = ''" @finance-provider-detected.window="method = $event.detail; paymentType = 'digital'">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $family->id }}">
                        <input type="hidden" name="payment_method" :value="method">

                        <div>
                            <p class="mb-2 text-sm font-bold text-slate-700">Payment method</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <button type="button" @click="paymentType = 'cash'; method = 'cash'" class="flex h-12 items-center justify-center rounded-xl border px-3 text-xs font-extrabold transition" :class="paymentType === 'cash' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">Cash</button>
                                <button type="button" @click="paymentType = 'digital'; if (!['gcash', 'maya', 'bdo', 'bank_transfer', 'other'].includes(method)) method = 'gcash'" class="flex h-12 items-center justify-center rounded-xl border px-3 text-xs font-extrabold transition" :class="paymentType === 'digital' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">Digital Payments</button>
                                <button type="button" @click="paymentType = 'remittance'; method = 'remittance'" class="flex h-12 items-center justify-center rounded-xl border px-3 text-xs font-extrabold transition" :class="paymentType === 'remittance' ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">Remittance</button>
                            </div>

                            <div x-show="paymentType === 'digital'" x-cloak class="mt-2.5 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">Choose digital channel</p>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    @foreach (['gcash' => 'GCash', 'maya' => 'Maya', 'bdo' => 'BDO', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'] as $value => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="digital_provider" value="{{ $value }}" x-model="method" class="peer sr-only">
                                            <span class="flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 transition peer-checked:border-emerald-600 peer-checked:bg-white peer-checked:text-emerald-800 peer-checked:shadow-sm">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div id="financePaymentDetailFields" class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-bold text-slate-700" :class="method === 'cash' ? 'sm:col-span-2' : ''">Amount received<input id="financeAmountInput" name="amount" type="text" inputmode="decimal" autocomplete="off" value="" required class="mt-1.5 block h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-lg font-extrabold leading-5 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" placeholder="0.00" aria-describedby="financeAmountHint"><span id="financeAmountHint" class="sr-only">Enter the payment amount. Thousands separators are added automatically.</span></label>
                            <label x-show="method !== 'cash'" x-cloak class="block text-sm font-bold text-slate-700">Transaction date and time<input id="financeTransactionAtInput" name="transaction_at" type="datetime-local" autocomplete="off" value="" :required="method !== 'cash'" :disabled="method === 'cash'" class="mt-1.5 block h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm leading-5 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100"></label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Transaction / reference number <span x-show="method === 'cash'" class="font-normal text-slate-400">(not needed)</span>
                                <input id="financeReferenceInput" name="reference_number" autocomplete="off" value="" :required="method !== 'cash'" :disabled="method === 'cash'" class="mt-1.5 block h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm leading-5 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" placeholder="Reference No. or Transaction ID">
                                <span x-show="method !== 'cash'" class="mt-1 block text-[11px] font-normal text-slate-500">OCR uses the reference number first, then the transaction ID when no reference is shown.</span>
                                @if (isset($errors) && $errors->has('reference_number'))<span class="mt-1.5 block text-xs font-bold text-rose-700">{{ $errors->first('reference_number') }}</span>@endif
                            </label>
                            <label class="block text-sm font-bold text-slate-700">Receiving account / counter<input id="financeAccountReceivedInput" name="account_received" autocomplete="off" value="" class="mt-1.5 block h-12 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm leading-5 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" placeholder="AMIS cashier or account"></label>
                        </div>

                        <div id="financePaymentProofSection" x-show="method !== 'cash'" x-cloak class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <i data-lucide="image-up" class="h-5 w-5"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-extrabold text-slate-900">Payment proof screenshot</h3>
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black uppercase text-rose-700">Required</span>
                                    </div>
                                    <p class="mt-0.5 text-xs leading-5 text-slate-500">Upload the successful transaction screen showing the amount and reference number.</p>
                                </div>
                            </div>

                            <label for="financeReceiptInput" class="mt-3 flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded-xl border-2 border-dashed border-slate-300 bg-white px-4 py-3 transition hover:border-emerald-500 hover:bg-emerald-50/40">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold text-slate-700" x-text="fileName || 'Choose a receipt screenshot'"></span>
                                    <span class="mt-0.5 block text-xs text-slate-500" x-text="fileName ? 'Screenshot selected and ready for OCR.' : 'Select one JPG, JPEG, or PNG image.'"></span>
                                </span>
                                <span class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-xs font-extrabold text-white shadow-sm">
                                    <i data-lucide="upload" class="h-4 w-4"></i>
                                    <span x-text="fileName ? 'Replace' : 'Browse'"></span>
                                </span>
                            </label>
                            <input id="financeReceiptInput" name="receipt" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" :required="method !== 'cash'" class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">

                            <div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold">
                                <span class="inline-flex items-center gap-1.5 text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>JPG, JPEG, PNG</span>
                                <span class="text-slate-500">Maximum 10 MB</span>
                                <span class="font-bold text-rose-700">PDF disabled</span>
                            </div>

                            <div id="financeOcrStatus" class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs leading-5 text-slate-600">Upload a screenshot to run the image quality check and OCR.</div>
                            <div id="financeOcrFields" class="mt-2.5 hidden grid-cols-2 gap-2">
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-400">Detected amount</span>
                                    <strong id="financeOcrAmountDisplay" class="mt-0.5 block truncate text-sm text-slate-800">Not found</strong>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-400">Reference / transaction no.</span>
                                    <strong id="financeOcrReferenceDisplay" class="mt-0.5 block truncate text-sm text-slate-800">Not found</strong>
                                </div>
                            </div>
                            <div id="financeDuplicateStatus" class="mt-2.5 hidden rounded-lg border px-3 py-2.5 text-xs font-bold" role="status" aria-live="polite"></div>
                            @if (isset($errors) && $errors->has('receipt'))<p class="mt-2 text-xs font-bold text-rose-700">{{ $errors->first('receipt') }}</p>@endif
                            <input id="financeOcrRaw" type="hidden" name="ocr_raw_text"><input id="financeOcrConfidence" type="hidden" name="ocr_confidence"><input id="financeOcrSender" type="hidden" name="ocr_sender"><input id="financeOcrReceiver" type="hidden" name="ocr_receiver"><input id="financeOcrType" type="hidden" name="ocr_document_type"><input id="financeOcrReference" type="hidden" name="ocr_reference"><input id="financeOcrAmount" type="hidden" name="ocr_amount">
                        </div>

                        <label x-show="method !== 'cash'" x-cloak class="block text-sm font-bold text-slate-700">OCR correction reason <span class="font-normal text-slate-400">(only when overriding OCR)</span><textarea name="correction_reason" rows="2" class="mt-1.5 block min-h-20 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm leading-5 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" placeholder="Explain any corrected amount or reference">{{ old('correction_reason') }}</textarea></label>
                        <label class="block text-sm font-bold text-slate-700">Internal remarks <span class="font-normal text-slate-400">(optional)</span><textarea name="remarks" rows="2" class="mt-1.5 block min-h-20 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm leading-5 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" placeholder="Add a note for the Finance audit trail">{{ old('remarks') }}</textarea></label>

                        <div class="flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                            <p class="max-w-sm text-xs leading-5 text-slate-500">Confirmation creates the transaction, applies the payment automatically, and issues an official receipt.</p>
                            <button id="financeOnsiteSubmit" class="shrink-0 rounded-xl bg-emerald-700 px-6 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-50">Confirm payment</button>
                        </div>
                    </form>

                    <div id="financeDuplicateModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="financeDuplicateModalTitle">
                        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                            <div class="border-b border-slate-100 px-6 py-5 sm:px-7">
                                <div class="flex items-start gap-4">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700" aria-hidden="true">
                                        <i data-lucide="shield-alert" class="h-5 w-5"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700">Receipt Verification Result</p>
                                        <h2 id="financeDuplicateModalTitle" class="mt-1 text-xl font-black text-slate-900"><span class="text-amber-600" aria-hidden="true">⚠</span> Duplicate Payment Detected</h2>
                                        <p class="mt-2 text-sm font-semibold text-slate-600">6 of 7 checks passed</p>
                                    </div>
                                    <button type="button" data-finance-duplicate-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Close duplicate warning">
                                        <i data-lucide="x" class="h-5 w-5"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="px-6 py-5 sm:px-7">
                                <div class="grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
                                    <div>
                                        <span class="block text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Transaction / reference no.</span>
                                        <strong id="financeDuplicateModalReference" class="mt-1 block break-all text-sm text-slate-900">Not available</strong>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Amount entered</span>
                                        <strong id="financeDuplicateModalAmount" class="mt-1 block text-sm text-slate-900">Not available</strong>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <span class="block text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Matching AMIS record</span>
                                        <strong id="financeDuplicateModalSource" class="mt-1 block text-sm text-slate-900">Existing payment record</strong>
                                    </div>
                                </div>

                                <p class="mt-4 text-sm leading-6 text-slate-600">To prevent the family from being charged twice, Confirm Payment is disabled. Review the existing transaction or use a different payment receipt.</p>

                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <a href="{{ route('admin.finance.transactions.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-extrabold text-slate-700 hover:bg-slate-50">Review Transactions</a>
                                    <button id="financeDuplicateReplaceReceipt" type="button" class="min-h-12 rounded-xl bg-emerald-700 px-4 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-800">Upload a Different Receipt</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                </div>

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-lg font-extrabold text-slate-900">Family billing schedule</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Same monthly view as the Family Payment portal. Open a month to view its student fee breakdown.</p>
                        </div>
                        <span class="shrink-0 text-xs font-bold text-emerald-700">Automatic oldest-first allocation</span>
                    </div>

                    <div class="space-y-2.5 bg-slate-50/70 p-3 sm:p-4">
                        @forelse ($billingSchedule as $period)
                            @php
                                $statusClasses = match ($period['status']) {
                                    'PAID' => 'bg-emerald-100 text-emerald-700',
                                    'OVERDUE' => 'bg-rose-100 text-rose-700',
                                    'CURRENT' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-slate-100 text-slate-600',
                                };
                                $displayAmount = $period['status'] === 'UPCOMING'
                                    ? $period['total_due']
                                    : $period['remaining'];
                            @endphp
                            <details class="group overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3.5 transition hover:bg-slate-50 sm:px-5">
                                    <div class="min-w-0">
                                        <p class="truncate font-extrabold text-slate-800">{{ $period['label'] }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Due {{ $period['due_date']->format('F Y') }} · {{ is_array($period['children']) ? count($period['children']) : $period['children']->count() }} student(s) · View fee breakdown
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <div class="text-right">
                                            <strong class="block text-base text-slate-900">₱{{ number_format($displayAmount, 2) }}</strong>
                                            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-black {{ $statusClasses }}">{{ $period['status'] }}</span>
                                        </div>
                                        <i data-lucide="chevron-down" class="h-5 w-5 text-slate-400 transition-transform group-open:rotate-180"></i>
                                    </div>
                                </summary>

                                <div class="border-t border-slate-100 px-4 py-2 sm:px-5">
                                    <div class="divide-y divide-slate-100">
                                        @foreach ($period['children'] as $child)
                                            @php
                                                $studentObj = $child['student'] ?? null;
                                                $studentName = $studentObj?->full_name
                                                    ?? (isset($studentObj->applicant) ? $studentObj->applicant?->full_name : null)
                                                    ?? 'Student';
                                                $studentId = $studentObj?->amis_student_id
                                                    ?? $studentObj?->student_number
                                                    ?? 'N/A';
                                            @endphp
                                            <div class="flex items-center justify-between gap-4 py-3">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-bold text-slate-800">{{ mb_strtoupper($studentName) }}</p>
                                                    <p class="truncate text-xs text-slate-500">
                                                        {{ $studentObj?->grade_level }} · ID {{ $studentId }}
                                                    </p>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <strong class="text-sm text-slate-900">₱{{ number_format($child['original'], 2) }}</strong>
                                                    @if ($child['remaining'] <= 0.01)
                                                        <p class="text-[10px] font-bold uppercase text-emerald-700">Paid</p>
                                                    @elseif ($child['verified'] > 0.01)
                                                        <p class="text-[10px] font-bold text-amber-700">₱{{ number_format($child['remaining'], 2) }} remaining</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-x-6 gap-y-1 border-t border-slate-200 py-3 text-xs text-slate-500">
                                        <span>Original <strong class="ml-1 text-slate-700">₱{{ number_format($period['total_due'], 2) }}</strong></span>
                                        <span>Verified paid <strong class="ml-1 text-emerald-700">₱{{ number_format($period['total_paid'], 2) }}</strong></span>
                                        <span>Remaining <strong class="ml-1 text-slate-900">₱{{ number_format($period['remaining'], 2) }}</strong></span>
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">No family billing schedule is available.</div>
                        @endforelse
                    </div>
                </section>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-900">
                    <strong>Automatic allocation:</strong> AMIS settles the oldest billing first, then carries any remaining amount forward. No student or month selection is needed.
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
