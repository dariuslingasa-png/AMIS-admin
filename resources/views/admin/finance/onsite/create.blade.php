<x-admin-layout title="Record Onsite Payment">
    <div class="finance-page mx-auto max-w-[1400px] p-4 sm:p-6 lg:p-8 space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Record Onsite Payment',
            'subtitle' => $family
                ? 'Confirm the payment details. AMIS will allocate the amount to the oldest family balance automatically.'
                : 'Start by finding the parent or family account receiving the payment.',
            'icon' => 'hand-coins',
        ])

        @if (! $family)
            {{-- STEP 1: Find Family Account (Search Mode) --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="border-b border-slate-100 p-6 sm:p-7">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-black text-base shadow-2xs">
                            1
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Step 1: Find the family account</h2>
                            <p class="mt-1 text-sm text-slate-500">Search using a parent name, student name, email address, or AMIS student ID.</p>
                        </div>
                    </div>

                    <form class="mt-6 flex flex-col gap-3 sm:flex-row">
                        <div class="relative min-w-0 flex-1">
                            <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input
                                name="q"
                                value="{{ request('q') }}"
                                autofocus
                                placeholder="Example: Zhairel, parent@email.com, or AMIS-2026-001"
                                class="h-12 w-full rounded-xl border border-slate-300 bg-slate-50/60 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition"
                            >
                        </div>
                        <button class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 text-sm font-extrabold text-white shadow-xs hover:bg-slate-800 active:scale-[0.99] transition cursor-pointer">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            Search
                        </button>
                    </form>
                </div>

                @if ($families->isNotEmpty())
                    <div class="p-6 sm:p-7">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Search results ({{ $families->count() }})</p>
                            <span class="text-xs text-slate-400">Select an account to proceed</span>
                        </div>
                        <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                            @foreach ($families as $result)
                                <a href="{{ route('admin.finance.onsite.create', ['family' => $result->id, 'q' => request('q')]) }}" class="group flex items-center justify-between gap-4 p-4 transition-colors hover:bg-emerald-50/50">
                                    <div class="flex min-w-0 items-center gap-3.5">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-black text-slate-700 group-hover:bg-emerald-100 group-hover:text-emerald-900 transition-colors">
                                            {{ mb_substr($result->name, 0, 1) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="truncate font-bold text-slate-900 group-hover:text-emerald-950">{{ $result->name }}</p>
                                                @if ($result->is_demo ?? false)
                                                    <span class="rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-black uppercase text-amber-800">DEMO DATA</span>
                                                @endif
                                            </div>
                                            <p class="truncate text-xs text-slate-500 mt-0.5">{{ $result->email }} · <strong class="font-semibold text-slate-700">{{ $result->enrollment_applicants_count }} student(s)</strong></p>
                                        </div>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-extrabold text-emerald-800 ring-1 ring-emerald-200/60 group-hover:bg-emerald-700 group-hover:text-white transition">
                                        Select family <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif (request()->filled('q'))
                    <div class="p-10 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i data-lucide="search-x" class="h-6 w-6"></i>
                        </div>
                        <p class="mt-3.5 font-extrabold text-slate-800">No family account found</p>
                        <p class="mt-1 text-sm text-slate-500">Check the spelling or try searching with the parent email or student ID.</p>
                    </div>
                @else
                    <div class="grid gap-3 bg-slate-50/70 p-5 sm:p-6 text-xs text-slate-600 sm:grid-cols-3">
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-white p-3 shadow-2xs">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <i data-lucide="user-round" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block font-bold text-slate-800">Parent or guardian</span>
                                <span class="text-[11px] text-slate-400">Search by full or partial name</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-white p-3 shadow-2xs">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block font-bold text-slate-800">Student name or ID</span>
                                <span class="text-[11px] text-slate-400">Matches any enrolled sibling</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-white p-3 shadow-2xs">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                <i data-lucide="mail" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block font-bold text-slate-800">Registered email</span>
                                <span class="text-[11px] text-slate-400">Finds official login email</span>
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        @else
            {{-- FAMILY SELECTED: 2-Column Grid (Family Summary Aside + Payment Form) --}}
            <div class="space-y-6">
                <div class="grid items-start gap-6 xl:grid-cols-[minmax(340px,380px)_minmax(0,1fr)]">
                    {{-- ASIDE: Step 1 Selected Family Card --}}
                    <aside class="space-y-4">
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                            {{-- Header --}}
                            <div class="border-b border-slate-100 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-800 text-base shadow-2xs">
                                            {{ mb_substr($family->name, 0, 1) }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                                    Step 1 · Selected Family
                                                </span>
                                                @if ($family->is_demo ?? false)
                                                    <span class="rounded-md border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-[9px] font-black uppercase text-amber-800">DEMO</span>
                                                @endif
                                            </div>
                                            <h2 class="truncate font-extrabold text-slate-900 text-base mt-1">{{ $family->name }}</h2>
                                            <p class="truncate text-xs text-slate-500">{{ $family->email }}</p>
                                        </div>
                                    </div>
                                    <a
                                        href="{{ route('admin.finance.onsite.create') }}"
                                        class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-600 hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900 transition"
                                        title="Search another family account"
                                    >
                                        <i data-lucide="user-round-pen" class="h-3.5 w-3.5"></i>
                                        Change
                                    </a>
                                </div>
                            </div>

                            {{-- Balance Breakdown --}}
                            <div class="space-y-3.5 p-5">
                                <div class="rounded-xl bg-slate-50/80 border border-slate-150 p-3.5 space-y-2.5 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-slate-600 font-medium truncate">
                                            <strong class="font-bold text-slate-800">{{ $previousPeriodLabel }}</strong> · Previous
                                        </span>
                                        <strong class="shrink-0 font-bold text-slate-900">₱{{ number_format($previousBalance, 2) }}</strong>
                                    </div>
                                    <div class="flex items-center justify-between gap-2 border-t border-slate-200/60 pt-2">
                                        <span class="text-slate-600 font-medium truncate">
                                            <strong class="font-bold text-emerald-800">{{ $currentPeriodLabel }}</strong> · Current
                                        </span>
                                        <strong class="shrink-0 font-bold text-slate-900">₱{{ number_format($currentCharges, 2) }}</strong>
                                    </div>
                                </div>

                                {{-- Total Amount Due Card --}}
                                <div class="rounded-xl border border-emerald-200/90 bg-emerald-50/60 p-4">
                                    <div class="flex items-end justify-between gap-2">
                                        <div>
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800">Total amount due</p>
                                            <p class="text-[11px] text-emerald-700/80 mt-0.5">Previous + current balance</p>
                                        </div>
                                        <p class="text-2xl font-black text-emerald-950 tracking-tight">₱{{ number_format($totalAmountDue, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </aside>

                    {{-- MAIN: Step 2 Enter Payment Details Card --}}
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7 shadow-xs">
                        <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-black text-base shadow-2xs">
                                2
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Step 2: Enter payment details</h2>
                                <p class="mt-1 text-sm text-slate-500">Cash needs no proof and records confirmation time automatically. Digital & remittance payments require a clear transaction screenshot.</p>
                            </div>
                        </div>

                        @php
                            $initialMethod = old('payment_method', 'cash');
                            $initialPaymentType = in_array($initialMethod, ['gcash', 'maya', 'bdo', 'bank_transfer', 'other'], true)
                                ? 'digital'
                                : $initialMethod;
                        @endphp

                        <form
                            id="financeOnsiteForm"
                            method="POST"
                            action="{{ route('admin.finance.onsite.store') }}"
                            data-duplicate-url="{{ route('admin.finance.onsite.duplicate-check') }}"
                            enctype="multipart/form-data"
                            autocomplete="off"
                            class="mt-6 space-y-6"
                            x-data="{
                                method: {{ Js::from($initialMethod) }},
                                paymentType: {{ Js::from($initialPaymentType) }},
                                fileName: ''
                            }"
                            @finance-file-cleared.window="fileName = ''"
                            @finance-provider-detected.window="method = $event.detail; paymentType = 'digital'"
                        >
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $family->id }}">
                            <input type="hidden" name="payment_method" :value="method">

                            {{-- Payment Method Selector --}}
                            <div class="space-y-3">
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700">Payment method</label>
                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                                    {{-- Cash Option --}}
                                    <button
                                        type="button"
                                        @click="paymentType = 'cash'; method = 'cash'"
                                        class="flex h-12 items-center justify-center gap-2 rounded-xl border px-4 text-xs font-extrabold transition cursor-pointer shadow-2xs"
                                        :class="paymentType === 'cash'
                                            ? 'border-emerald-600 bg-emerald-50/80 text-emerald-900 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                    >
                                        <i data-lucide="banknote" class="h-4 w-4 shrink-0" :class="paymentType === 'cash' ? 'text-emerald-700' : 'text-slate-400'"></i>
                                        <span>Cash</span>
                                    </button>

                                    {{-- Digital Payments Option --}}
                                    <button
                                        type="button"
                                        @click="paymentType = 'digital'; if (!['gcash', 'maya', 'bdo', 'bank_transfer', 'other'].includes(method)) method = 'gcash'"
                                        class="flex h-12 items-center justify-center gap-2 rounded-xl border px-4 text-xs font-extrabold transition cursor-pointer shadow-2xs"
                                        :class="paymentType === 'digital'
                                            ? 'border-emerald-600 bg-emerald-50/80 text-emerald-900 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                    >
                                        <i data-lucide="smartphone" class="h-4 w-4 shrink-0" :class="paymentType === 'digital' ? 'text-emerald-700' : 'text-slate-400'"></i>
                                        <span>Digital Payments</span>
                                    </button>

                                    {{-- Remittance Option --}}
                                    <button
                                        type="button"
                                        @click="paymentType = 'remittance'; method = 'remittance'"
                                        class="flex h-12 items-center justify-center gap-2 rounded-xl border px-4 text-xs font-extrabold transition cursor-pointer shadow-2xs"
                                        :class="paymentType === 'remittance'
                                            ? 'border-emerald-600 bg-emerald-50/80 text-emerald-900 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                    >
                                        <i data-lucide="receipt" class="h-4 w-4 shrink-0" :class="paymentType === 'remittance' ? 'text-emerald-700' : 'text-slate-400'"></i>
                                        <span>Remittance</span>
                                    </button>
                                </div>

                                {{-- Digital Channel Sub-panel --}}
                                <div x-show="paymentType === 'digital'" x-cloak class="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5 space-y-2.5">
                                    <p class="text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Choose digital channel</p>
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                        @foreach (['gcash' => 'GCash', 'maya' => 'Maya', 'bdo' => 'BDO', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'] as $value => $label)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="digital_provider" value="{{ $value }}" x-model="method" class="peer sr-only">
                                                <span class="flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-bold text-slate-700 transition peer-checked:border-emerald-600 peer-checked:bg-emerald-50 peer-checked:text-emerald-900 peer-checked:ring-1 peer-checked:ring-emerald-500/20 shadow-2xs">
                                                    {{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Detail Fields (Row 1: Amount Received, Row 2: Reference/Date/Account) --}}
                            <div id="financePaymentDetailFields" class="space-y-4">
                                {{-- Row 1: Amount Received (High visual emphasis) --}}
                                <div class="rounded-xl border border-slate-200/90 bg-slate-50/40 p-4 sm:p-5 space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <label for="financeAmountInput" class="block text-xs font-black uppercase tracking-wider text-slate-800">
                                            Amount received <span class="text-rose-600">*</span>
                                        </label>
                                        <span class="text-[11px] font-medium text-slate-500">Oldest balance settled first</span>
                                    </div>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-black text-slate-400">₱</span>
                                        <input
                                            id="financeAmountInput"
                                            name="amount"
                                            type="text"
                                            inputmode="decimal"
                                            autocomplete="off"
                                            value=""
                                            required
                                            placeholder="0.00"
                                            aria-describedby="financeAmountHint"
                                            class="h-14 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-4 text-2xl font-black text-slate-900 placeholder:text-slate-300 focus:border-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-500/15 transition shadow-2xs"
                                        >
                                    </div>
                                    <span id="financeAmountHint" class="sr-only">Enter the payment amount. Thousands separators are added automatically.</span>
                                </div>

                                {{-- Row 2: Reference Number, Date/Time (if non-cash), and Receiving Account --}}
                                <div class="grid gap-4 sm:grid-cols-2" :class="method !== 'cash' ? 'lg:grid-cols-3' : 'lg:grid-cols-2'">
                                    {{-- Reference Number --}}
                                    <div class="space-y-1.5">
                                        <label for="financeReferenceInput" class="block text-xs font-bold text-slate-700">
                                            Transaction / reference no.
                                            <span x-show="method === 'cash'" class="font-normal text-slate-400">(not needed for cash)</span>
                                        </label>
                                        <input
                                            id="financeReferenceInput"
                                            name="reference_number"
                                            autocomplete="off"
                                            value=""
                                            :required="method !== 'cash'"
                                            :disabled="method === 'cash'"
                                            placeholder="Reference No. or Transaction ID"
                                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 text-sm text-slate-900 placeholder:text-slate-400 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition shadow-2xs"
                                        >
                                        <span x-show="method !== 'cash'" x-cloak class="block text-[11px] text-slate-500 leading-tight">OCR uses reference first, then transaction ID.</span>
                                        @if (isset($errors) && $errors->has('reference_number'))
                                            <span class="block text-xs font-bold text-rose-700">{{ $errors->first('reference_number') }}</span>
                                        @endif
                                    </div>

                                    {{-- Transaction Date and Time (Non-Cash only) --}}
                                    <div x-show="method !== 'cash'" x-cloak class="space-y-1.5">
                                        <label for="financeTransactionAtInput" class="block text-xs font-bold text-slate-700">
                                            Transaction date & time
                                        </label>
                                        <input
                                            id="financeTransactionAtInput"
                                            name="transaction_at"
                                            type="datetime-local"
                                            autocomplete="off"
                                            value=""
                                            :required="method !== 'cash'"
                                            :disabled="method === 'cash'"
                                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 text-sm text-slate-900 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition shadow-2xs"
                                        >
                                    </div>

                                    {{-- Receiving Account / Counter --}}
                                    <div class="space-y-1.5" :class="method === 'cash' ? 'sm:col-span-1' : ''">
                                        <label for="financeAccountReceivedInput" class="block text-xs font-bold text-slate-700">
                                            Receiving account / counter
                                        </label>
                                        <input
                                            id="financeAccountReceivedInput"
                                            name="account_received"
                                            autocomplete="off"
                                            value=""
                                            placeholder="AMIS cashier or account"
                                            class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition shadow-2xs"
                                        >
                                    </div>
                                </div>
                            </div>

                            {{-- Payment Proof Section (Digital & Remittance only) --}}
                            <div id="financePaymentProofSection" x-show="method !== 'cash'" x-cloak class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 space-y-4">
                                <div class="flex items-start gap-3.5">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 shadow-2xs">
                                        <i data-lucide="image-up" class="h-5 w-5"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-extrabold text-slate-900">Payment proof screenshot</h3>
                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black uppercase text-rose-700 border border-rose-200">Required</span>
                                        </div>
                                        <p class="mt-0.5 text-xs leading-relaxed text-slate-500">Upload the successful transaction screen showing the amount and reference number.</p>
                                    </div>
                                </div>

                                {{-- Dropzone --}}
                                <label for="financeReceiptInput" class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded-xl border-2 border-dashed border-slate-300 bg-white px-4 py-3.5 transition hover:border-emerald-500 hover:bg-emerald-50/30 shadow-2xs">
                                    <div class="min-w-0">
                                        <span class="block truncate text-sm font-bold text-slate-800" x-text="fileName || 'Choose a receipt screenshot'"></span>
                                        <span class="mt-0.5 block text-xs text-slate-500" x-text="fileName ? 'Screenshot selected and ready for OCR analysis.' : 'Select one JPG, JPEG, or PNG image.'"></span>
                                    </div>
                                    <span class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-xs font-extrabold text-white shadow-xs hover:bg-emerald-800 transition">
                                        <i data-lucide="upload" class="h-4 w-4"></i>
                                        <span x-text="fileName ? 'Replace' : 'Browse'"></span>
                                    </span>
                                </label>
                                <input
                                    id="financeReceiptInput"
                                    name="receipt"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                                    :required="method !== 'cash'"
                                    class="sr-only"
                                    @change="fileName = $event.target.files[0]?.name || ''"
                                >

                                {{-- File Criteria Specs --}}
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-slate-500">
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>JPG, JPEG, PNG</span>
                                    <span>Maximum 10 MB</span>
                                    <span class="font-bold text-rose-700">PDF disabled</span>
                                </div>

                                {{-- OCR Status & Extracted Fields --}}
                                <div id="financeOcrStatus" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs leading-relaxed text-slate-600 shadow-2xs">
                                    Upload a screenshot to run the image quality check and OCR.
                                </div>

                                <div id="financeOcrFields" class="hidden grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-2xs">
                                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Detected amount</span>
                                        <strong id="financeOcrAmountDisplay" class="mt-1 block truncate text-sm font-black text-slate-900">Not found</strong>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-2xs">
                                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Reference / transaction no.</span>
                                        <strong id="financeOcrReferenceDisplay" class="mt-1 block truncate text-sm font-black text-slate-900">Not found</strong>
                                    </div>
                                </div>

                                <div id="financeDuplicateStatus" class="hidden rounded-xl border px-3.5 py-2.5 text-xs font-bold" role="status" aria-live="polite"></div>

                                @if (isset($errors) && $errors->has('receipt'))
                                    <p class="text-xs font-bold text-rose-700">{{ $errors->first('receipt') }}</p>
                                @endif

                                {{-- Hidden OCR Payload Fields --}}
                                <input id="financeOcrRaw" type="hidden" name="ocr_raw_text">
                                <input id="financeOcrConfidence" type="hidden" name="ocr_confidence">
                                <input id="financeOcrSender" type="hidden" name="ocr_sender">
                                <input id="financeOcrReceiver" type="hidden" name="ocr_receiver">
                                <input id="financeOcrType" type="hidden" name="ocr_document_type">
                                <input id="financeOcrReference" type="hidden" name="ocr_reference">
                                <input id="financeOcrAmount" type="hidden" name="ocr_amount">
                            </div>

                            {{-- OCR Correction Reason (Only when overriding OCR) --}}
                            <div x-show="method !== 'cash'" x-cloak class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    OCR correction reason <span class="font-normal text-slate-400">(only required when overriding OCR fields)</span>
                                </label>
                                <textarea
                                    name="correction_reason"
                                    rows="2"
                                    placeholder="Explain any corrected amount or reference"
                                    class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 p-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition shadow-2xs"
                                >{{ old('correction_reason') }}</textarea>
                            </div>

                            {{-- Row 3: Internal Remarks (Full width) --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700">
                                    Internal remarks <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    name="remarks"
                                    rows="2"
                                    placeholder="Add a note for the Finance audit trail"
                                    class="block w-full rounded-xl border border-slate-300 bg-slate-50/50 p-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 transition shadow-2xs"
                                >{{ old('remarks') }}</textarea>
                            </div>

                            {{-- Form Action Area --}}
                            <div class="flex flex-col gap-4 border-t border-slate-150 pt-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-2.5 max-w-md">
                                    <i data-lucide="info" class="h-4 w-4 shrink-0 text-slate-400 mt-0.5"></i>
                                    <p class="text-xs leading-relaxed text-slate-500">
                                        Confirmation creates the transaction, applies the payment automatically, and issues an official receipt.
                                    </p>
                                </div>
                                <button
                                    id="financeOnsiteSubmit"
                                    class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-7 text-sm font-extrabold text-white shadow-xs hover:bg-emerald-800 active:scale-[0.99] transition cursor-pointer disabled:cursor-wait disabled:opacity-50"
                                >
                                    <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                    Confirm payment
                                </button>
                            </div>
                        </form>

                        {{-- Duplicate Payment Modal --}}
                        <div id="financeDuplicateModal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-labelledby="financeDuplicateModalTitle">
                            <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                                <div class="border-b border-slate-100 px-6 py-5 sm:px-7">
                                    <div class="flex items-start gap-4">
                                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700" aria-hidden="true">
                                            <i data-lucide="shield-alert" class="h-6 w-6"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-700">Receipt Verification Result</p>
                                            <h2 id="financeDuplicateModalTitle" class="mt-1 text-xl font-black text-slate-900">Duplicate Payment Detected</h2>
                                            <p class="mt-1 text-xs font-semibold text-slate-600">6 of 7 checks passed</p>
                                        </div>
                                        <button type="button" data-finance-duplicate-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 transition" aria-label="Close duplicate warning">
                                            <i data-lucide="x" class="h-5 w-5"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="px-6 py-5 sm:px-7 space-y-4">
                                    <div class="grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2 text-xs">
                                        <div>
                                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Transaction / reference no.</span>
                                            <strong id="financeDuplicateModalReference" class="mt-1 block break-all text-sm font-bold text-slate-900">Not available</strong>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Amount entered</span>
                                            <strong id="financeDuplicateModalAmount" class="mt-1 block text-sm font-bold text-slate-900">Not available</strong>
                                        </div>
                                        <div class="sm:col-span-2 border-t border-slate-200/60 pt-2.5">
                                            <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Matching AMIS record</span>
                                            <strong id="financeDuplicateModalSource" class="mt-1 block text-sm font-bold text-slate-900">Existing payment record</strong>
                                        </div>
                                    </div>

                                    <p class="text-xs leading-relaxed text-slate-600">
                                        To prevent the family from being charged twice, Confirm Payment is disabled. Review the existing transaction or use a different payment receipt.
                                    </p>

                                    <div class="grid gap-3 sm:grid-cols-2 pt-2">
                                        <a href="{{ route('admin.finance.transactions.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-xs font-extrabold text-slate-700 hover:bg-slate-50 transition">
                                            Review Transactions
                                        </a>
                                        <button id="financeDuplicateReplaceReceipt" type="button" class="min-h-11 rounded-xl bg-emerald-700 px-4 text-xs font-extrabold text-white shadow-xs hover:bg-emerald-800 transition">
                                            Upload a Different Receipt
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- STEP 3: Family Billing Schedule Card --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                    {{-- Header --}}
                    <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                        <div class="flex items-start gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 font-black text-base shadow-2xs">
                                3
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-lg font-extrabold text-slate-900 tracking-tight">Step 3: Family billing schedule</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Same monthly view as the Family Payment portal. Open a month to view its student fee breakdown.</p>
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 border border-emerald-200/60">
                            <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                            Automatic oldest-first allocation
                        </span>
                    </div>

                    {{-- Schedule Rows --}}
                    <div class="space-y-3 bg-slate-50/50 p-4 sm:p-6">
                        @forelse ($billingSchedule as $period)
                            @php
                                $statusBadge = match (strtoupper($period['status'] ?? '')) {
                                    'PAID' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'OVERDUE' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'CURRENT' => 'bg-sky-100 text-sky-800 border-sky-200',
                                    'PARTIALLY PAID' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                                $displayAmount = $period['status'] === 'UPCOMING'
                                    ? $period['total_due']
                                    : $period['remaining'];
                                $childCount = is_array($period['children']) ? count($period['children']) : $period['children']->count();
                            @endphp
                            <details class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-slate-300 shadow-2xs">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 transition-colors hover:bg-slate-50/80 sm:px-5">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="truncate font-extrabold text-slate-900 text-sm sm:text-base">{{ $period['label'] }}</p>
                                            <span class="inline-flex rounded-md border px-2 py-0.5 text-[10px] font-black uppercase {{ $statusBadge }}">
                                                {{ $period['status'] }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Due {{ $period['due_date']->format('F Y') }} · {{ $childCount }} student(s) · <span class="font-semibold text-emerald-700 group-hover:underline">View fee breakdown</span>
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-4">
                                        <div class="text-right">
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Balance</span>
                                            <strong class="block text-base font-black text-slate-900 sm:text-lg">₱{{ number_format($displayAmount, 2) }}</strong>
                                        </div>
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500 group-hover:bg-slate-200 transition">
                                            <i data-lucide="chevron-down" class="h-4 w-4 transition-transform duration-200 group-open:rotate-180"></i>
                                        </div>
                                    </div>
                                </summary>

                                {{-- Expanded Breakdown --}}
                                <div class="border-t border-slate-100 bg-slate-50/30 p-4 sm:p-5 space-y-4">
                                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 bg-white overflow-hidden shadow-2xs">
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
                                            <div class="flex items-center justify-between gap-4 p-3.5 sm:px-4">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-xs font-black text-emerald-800">
                                                        {{ mb_substr($studentName, 0, 1) }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-xs font-bold text-slate-900">{{ mb_strtoupper($studentName) }}</p>
                                                        <p class="truncate text-[11px] text-slate-500">
                                                            {{ $studentObj?->grade_level }} · ID {{ $studentId }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <strong class="text-xs font-extrabold text-slate-900">₱{{ number_format($child['original'], 2) }}</strong>
                                                    @if ($child['remaining'] <= 0.01)
                                                        <p class="text-[10px] font-bold uppercase text-emerald-700">Fully Paid</p>
                                                    @elseif ($child['verified'] > 0.01)
                                                        <p class="text-[10px] font-bold text-amber-700">₱{{ number_format($child['remaining'], 2) }} remaining</p>
                                                    @else
                                                        <p class="text-[10px] font-bold text-slate-400">Unpaid</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Month Totals Summary Bar --}}
                                    <div class="flex flex-wrap items-center justify-end gap-x-5 gap-y-2 rounded-xl bg-slate-100/70 px-4 py-2.5 text-xs text-slate-600">
                                        <span>Original Total: <strong class="font-bold text-slate-800">₱{{ number_format($period['total_due'], 2) }}</strong></span>
                                        <span>Verified Paid: <strong class="font-bold text-emerald-700">₱{{ number_format($period['total_paid'], 2) }}</strong></span>
                                        <span>Remaining: <strong class="font-black text-slate-900">₱{{ number_format($period['remaining'], 2) }}</strong></span>
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="rounded-xl border border-dashed border-emerald-300 bg-emerald-50/50 p-8 text-center text-sm font-bold text-emerald-800">
                                No outstanding payment is currently due for this family.
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Automatic Allocation Notice Callout --}}
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-4 sm:p-5 text-xs leading-relaxed text-amber-950 shadow-2xs">
                    <div class="flex items-start gap-3">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800 mt-0.5">
                            <i data-lucide="layers" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <strong class="font-extrabold text-amber-950">Automatic allocation:</strong>
                            AMIS settles the oldest billing first, then carries any remaining amount forward. No student or month selection is needed.
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
