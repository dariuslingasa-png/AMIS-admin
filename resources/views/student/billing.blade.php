<x-student-layout title="Statement of Account">
@php
    $soaTotal = (float) ($account->total_balance ?? 0);
    $soaPaid = (float) ($account->amount_paid ?? 0);
    $soaRemaining = (float) ($account->remaining_balance ?? 0);
    $soaProgress = $soaTotal > 0 ? min(100, max(0, ($soaPaid / $soaTotal) * 100)) : 0;
    $soaPaidInstallments = $billings->where('status', 'paid')->count();
    $soaUnpaidInstallments = $billings->where('status', 'unpaid');
    $soaOverdueCount = $soaUnpaidInstallments->filter(fn ($billing) => $billing->due_date->isPast())->count();
    $soaNextBilling = $soaUnpaidInstallments->sortBy('due_date')->first();
    $soaAccountStatus = !$account
        ? 'Unavailable'
        : ($soaRemaining <= 0 ? 'Fully paid' : ($soaPaid > 0 ? 'Payment ongoing' : 'Payment due'));
@endphp

<div class="soa-page"
     x-data="paymentWizard()"
     x-init="$watch('openPaymentModal', value => document.body.classList.toggle('student-modal-open', value)); initializeModal()">
    <!-- Validation Errors Display -->
    @if ($errors->any())
        <div class="soa-alert soa-alert--error" role="alert">
            <span class="soa-alert__icon"><i data-lucide="circle-alert"></i></span>
            <div>
                <strong>Payment submission needs attention</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @unless($account)
        <div class="soa-alert soa-alert--warning" role="status">
            <span class="soa-alert__icon"><i data-lucide="clock-3"></i></span>
            <div>
                <strong>Your Statement of Account is still being prepared</strong>
                <p>Please contact the Finance Office before submitting a payment proof.</p>
            </div>
        </div>
    @endunless

    <!-- Financial overview -->
    <section class="soa-hero" aria-labelledby="soa-page-title">
        <span class="soa-hero__orb soa-hero__orb--one" aria-hidden="true"></span>
        <span class="soa-hero__orb soa-hero__orb--two" aria-hidden="true"></span>

        <div class="soa-hero__content">
            <div class="soa-hero__eyebrow">
                <span><i data-lucide="shield-check"></i></span>
                Official student finance record
            </div>
            <h2 id="soa-page-title">Your school finances,<br><em>made clear.</em></h2>
            <p>Review tuition charges, follow your installment schedule, and submit receipts securely for Finance Office verification.</p>

            <div class="soa-hero__meta">
                <span><i data-lucide="graduation-cap"></i> {{ $student?->student_number ?? 'Student account' }}</span>
                @if($student?->school_year)
                    <span><i data-lucide="calendar-days"></i> School Year {{ $student->school_year }}</span>
                @endif
            </div>

            <div class="soa-hero__actions">
                @if($account)
                    <button type="button" @click="openModal()" class="soa-button soa-button--light">
                        <i data-lucide="upload-cloud"></i>
                        Upload payment proof
                    </button>
                @else
                    <button type="button" disabled class="soa-button soa-button--disabled">
                        <i data-lucide="lock-keyhole"></i>
                        Awaiting SOA
                    </button>
                @endif
                <button type="button" onclick="window.print()" class="soa-button soa-button--ghost">
                    <i data-lucide="printer"></i>
                    Print statement
                </button>
            </div>
        </div>

        <div class="soa-balance-card">
            <div class="soa-balance-card__top">
                <span>Remaining balance</span>
                <span class="soa-account-state {{ $soaRemaining <= 0 && $account ? 'is-settled' : '' }}">
                    <i></i>{{ $soaAccountStatus }}
                </span>
            </div>
            <strong><small>PHP</small> {{ number_format($soaRemaining, 2) }}</strong>
            <div class="soa-progress" role="progressbar" aria-label="Payment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($soaProgress) }}">
                <span style="width: {{ $soaProgress }}%"></span>
            </div>
            <div class="soa-balance-card__footer">
                <span><b>{{ number_format($soaProgress, 0) }}%</b> paid</span>
                <span>PHP {{ number_format($soaPaid, 2) }} of PHP {{ number_format($soaTotal, 2) }}</span>
            </div>
        </div>
    </section>

    <!-- Account snapshot -->
    <section class="soa-metrics" aria-label="Account summary">
        <article class="soa-metric">
            <span class="soa-metric__icon is-emerald"><i data-lucide="receipt-text"></i></span>
            <div><small>Total billed</small><strong>PHP {{ number_format($soaTotal, 2) }}</strong><span>Tuition and school fees</span></div>
        </article>
        <article class="soa-metric">
            <span class="soa-metric__icon is-blue"><i data-lucide="badge-check"></i></span>
            <div><small>Verified payments</small><strong>PHP {{ number_format($soaPaid, 2) }}</strong><span>Posted to your account</span></div>
        </article>
        <article class="soa-metric">
            <span class="soa-metric__icon is-violet"><i data-lucide="calendar-range"></i></span>
            <div><small>Installments</small><strong>{{ $soaPaidInstallments }} <i>of</i> {{ $billings->count() }}</strong><span>{{ $soaOverdueCount > 0 ? $soaOverdueCount.' overdue' : 'No overdue schedule' }}</span></div>
        </article>
        <article class="soa-metric">
            <span class="soa-metric__icon is-amber"><i data-lucide="calendar-clock"></i></span>
            <div><small>Next due date</small><strong>{{ $soaNextBilling ? $soaNextBilling->due_date->format('M d, Y') : ($account && $soaRemaining <= 0 ? 'All settled' : 'Not scheduled') }}</strong><span>{{ $soaNextBilling?->month_name ?? 'Account schedule' }}</span></div>
        </article>
    </section>

    <div class="soa-content-grid">
        <div class="soa-main-column">
            <!-- Fee Breakdown -->
            @if($account)
                <section class="soa-card soa-fees">
                    <header class="soa-card__header">
                        <div>
                            <span class="soa-section-kicker">Account charges</span>
                            <h3>Detailed fee breakdown</h3>
                            <p>A transparent summary of charges applied to this school year.</p>
                        </div>
                        <span class="soa-card__header-icon"><i data-lucide="landmark"></i></span>
                    </header>

                    <div class="soa-table-wrap">
                        <table class="soa-table">
                            <thead>
                                <tr>
                                    <th>Fee Item</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="soa-fee-icon"><i data-lucide="graduation-cap"></i></span><div><strong>Base Tuition Fee</strong><small>Core academic instruction</small></div></td>
                                    <td>PHP {{ number_format((float) $account->tuition_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><span class="soa-fee-icon"><i data-lucide="book-open"></i></span><div><strong>Books &amp; Learning Materials</strong><small>Required instructional resources</small></div></td>
                                    <td>PHP {{ number_format((float) $account->books_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><span class="soa-fee-icon"><i data-lucide="shapes"></i></span><div><strong>Miscellaneous Fees</strong><small>School services and activities</small></div></td>
                                    <td>PHP {{ number_format((float) $account->miscellaneous_fee, 2) }}</td>
                                </tr>
                                @if($account->discount_amount > 0)
                                    <tr class="soa-table__discount">
                                        <td><span class="soa-fee-icon"><i data-lucide="badge-percent"></i></span><div><strong>Sibling Discount</strong><small>{{ $account->discount_type }}</small></div></td>
                                        <td>− PHP {{ number_format((float) $account->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="soa-table__total">
                                    <td><strong>Gross Total Balance</strong><small>Net charges after applicable discounts</small></td>
                                    <td>PHP {{ number_format((float) $account->total_balance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            <!-- Monthly statement -->
            <section class="soa-card">
                <header class="soa-card__header">
                    <div>
                        <span class="soa-section-kicker">Payment plan</span>
                        <h3>Monthly billing schedule</h3>
                        <p>Track each installment and its current posting status.</p>
                    </div>
                    <span class="soa-count-pill">{{ $billings->count() }} installments</span>
                </header>

                @if($billings->isNotEmpty())
                    <div class="soa-installment-list">
                        @foreach($billings as $billing)
                            @php 
                                $isOverdue = $billing->status === 'unpaid' && $billing->due_date->isPast();
                                $billingState = $billing->status === 'paid' ? 'is-paid' : ($isOverdue ? 'is-overdue' : 'is-upcoming');
                            @endphp
                            <article class="soa-installment {{ $billingState }}">
                                <span class="soa-installment__line" aria-hidden="true"></span>
                                <div class="soa-installment__month">
                                    <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <strong>
                                        {{ mb_substr($billing->month_name, 0, 3) }}
                                    </strong>
                                </div>
                                <div class="soa-installment__details">
                                    <strong>{{ $billing->month_name }} dues</strong>
                                    <span>Due {{ $billing->due_date->format('F d, Y') }}</span>
                                </div>
                                <div class="soa-installment__amount"><small>Amount due</small><strong>PHP {{ number_format((float) $billing->amount_due, 2) }}</strong></div>
                                <span class="soa-status-badge">
                                    @if($billing->status === 'paid')
                                        <i data-lucide="check"></i> Paid
                                    @elseif($isOverdue)
                                        <i data-lucide="circle-alert"></i> Overdue
                                    @else
                                        <i data-lucide="clock-3"></i> Upcoming
                                    @endif
                                </span>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="soa-empty">
                        <span><i data-lucide="calendar-x-2"></i></span>
                        <strong>No installment schedule yet</strong>
                        <p>Your monthly billing plan will appear here once configured by the Finance Office.</p>
                    </div>
                @endif
            </section>
        </div>

        <!-- Payment action and history -->
        <aside class="soa-side-column">
            <section class="soa-upload-card">
                <span class="soa-upload-card__icon"><i data-lucide="scan-line"></i></span>
                <span class="soa-section-kicker">Payment verification</span>
                <h3>Already paid?</h3>
                <p>Upload a clear transaction receipt. AMIS will scan the details and send it securely to Finance for review.</p>
                @if($account)
                    <button type="button" @click="openModal()" class="soa-button soa-button--primary soa-button--full">
                        <i data-lucide="upload-cloud"></i>
                        Upload payment proof
                    </button>
                @else
                    <button type="button" disabled class="soa-button soa-button--disabled soa-button--full">
                        <i data-lucide="lock-keyhole"></i>
                        SOA required before payment
                    </button>
                @endif
                <div class="soa-secure-note"><i data-lucide="shield-check"></i><span><strong>Secure submission</strong>Your proof is reviewed before it is posted.</span></div>
            </section>

            <section class="soa-card soa-history">
                <header class="soa-card__header soa-card__header--compact">
                    <div>
                        <span class="soa-section-kicker">Recent activity</span>
                        <h3>Previous payments</h3>
                    </div>
                    <span class="soa-count-pill">{{ $payments->count() }}</span>
                </header>

                @if($payments->isNotEmpty())
                    <div class="soa-payment-list">
                        @foreach($payments as $pay)
                            <article class="soa-payment">
                                <div class="soa-payment__top">
                                    <span class="soa-payment__method"><i data-lucide="wallet-cards"></i></span>
                                    <div class="soa-payment__details">
                                        <strong>{{ ucfirst($pay->method) }}</strong>
                                        <span>{{ $pay->paid_at ? $pay->paid_at->format('M d, Y') : $pay->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <strong class="soa-payment__amount">PHP {{ number_format((float) $pay->amount, 2) }}</strong>
                                </div>
                                <div class="soa-payment__meta">
                                    <span>Ref: <b>{{ $pay->reference_no ?: 'Not provided' }}</b></span>
                                    @if($pay->status === 'verified')
                                        <span class="soa-payment-state is-verified"><i data-lucide="check"></i> Verified</span>
                                    @elseif($pay->status === 'rejected')
                                        <span class="soa-payment-state is-rejected" title="Remarks: {{ $pay->remarks ?? 'None' }}"><i data-lucide="x"></i> Rejected</span>
                                    @else
                                        <span class="soa-payment-state is-pending"><i data-lucide="clock-3"></i> Pending</span>
                                    @endif
                                </div>
                                @if($pay->receipt_url)
                                    <a href="{{ asset('storage/' . $pay->receipt_url) }}" target="_blank" rel="noopener" class="soa-receipt-link">
                                        View receipt <i data-lucide="arrow-up-right"></i>
                                    </a>
                                @endif
                                @if($pay->status === 'rejected' && $pay->remarks)
                                    <div class="soa-payment__remarks"><strong>Finance remarks</strong><span>{{ $pay->remarks }}</span></div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="soa-empty soa-empty--compact">
                        <span><i data-lucide="history"></i></span>
                        <strong>No payment history</strong>
                        <p>Your submitted payments will appear here.</p>
                    </div>
                @endif
            </section>
        </aside>
    </div>

    <!-- Payment Wizard Modal Backdrop -->
    <div x-show="openPaymentModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-ref="paymentModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="payment-dialog-title"
         @click.self="closeModal()"
         @keydown.escape.window="closeModal()"
         @keydown.tab="trapModalFocus($event)"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <!-- Modal Card Container -->
        <div class="soa-payment-modal bg-white rounded-3xl shadow-2xl border border-gray-150 w-full max-w-xl overflow-hidden relative"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
             
             <!-- Close Button -->
             <button type="button" @click="closeModal()" aria-label="Close payment dialog" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 cursor-pointer p-1.5 rounded-full hover:bg-gray-100 transition duration-200 z-10" style="position: absolute; top: 16px; right: 16px;">
                 <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
             </button>

             <!-- Scanning Loader Overlay -->
             <div x-show="scanning" 
                  class="absolute inset-0 z-50 flex flex-col items-center justify-center text-white p-6 rounded-3xl"
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0"
                  x-transition:enter-end="opacity-100"
                  x-transition:leave="transition ease-in duration-200"
                  x-transition:leave-start="opacity-100"
                  x-transition:leave-end="opacity-0"
                  x-cloak
                  style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(4px); display: flex; flex-direction: column; align-items: center; justify-content: center;">
                 
                 <!-- Bulletproof inline spinner loader -->
                 <style>
                     @keyframes absolute-wizard-spin {
                         0% { transform: rotate(0deg); }
                         100% { transform: rotate(360deg); }
                     }
                 </style>
                 <div style="width: 48px; height: 48px; border: 4px solid rgba(16, 185, 129, 0.2); border-top: 4px solid #10b981; border-radius: 50%; animation: absolute-wizard-spin 1s linear infinite; margin-bottom: 16px;"></div>

                 <div class="space-y-1 text-center">
                     <p class="text-base font-black text-white">Smart Receipt Scan</p>
                     <p class="text-xs text-emerald-300 font-extrabold uppercase tracking-wider">Analyzing transaction details, please wait...</p>
                 </div>
             </div>
             
             <!-- Wizard Content -->
             <div class="p-6 sm:p-8">
                 <!-- Header -->
                 <div class="border-b border-gray-150 pb-4 mb-4 flex items-center justify-between">
                     <div>
                         <span class="student-status-pill">Payment Gateway</span>
                         <h3 id="payment-dialog-title" class="font-black text-gray-900 text-lg mt-1" style="margin: 4px 0 2px;">Submit Payment</h3>
                     </div>
                     <span class="text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1 uppercase tracking-wider">
                         Step <span x-text="step"></span> of 4
                     </span>
                 </div>

                 <!-- Progress Bar -->
                 <div class="w-full bg-gray-100 h-1.5 rounded-full mb-6 overflow-hidden">
                     <div class="bg-emerald-600 h-full rounded-full transition-all duration-300" :style="'width: ' + ((step - 1) * 33.33) + '%'"></div>
                 </div>

                 <!-- Step 1: Choose Payment Type (Single vs Family) -->
                 <div x-show="step === 1" class="space-y-4">
                     <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 1: Choose Payment Mode</p>
                     
                     <div class="grid grid-cols-1 gap-3">
                         <!-- Option: Single Pay -->
                         <button type="button" @click="payMode = 'single'; step = 2;" 
                                 class="w-full flex items-center justify-between p-4 rounded-xl border transition-all duration-200 text-left cursor-pointer"
                                 :class="payMode === 'single' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/10' : 'border-gray-200 hover:border-gray-300 bg-white'">
                             <div class="flex items-center gap-3">
                                 <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700 shrink-0">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                 </div>
                                 <div>
                                     <p class="font-extrabold text-sm text-gray-900" style="margin:0;">Single Student Payment</p>
                                     <p class="text-[11px] text-gray-500 font-semibold mt-0.5">Submit payment for this student account only.</p>
                                 </div>
                             </div>
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right text-gray-400"><polyline points="9 18 15 12 9 6"/></svg>
                         </button>

                         <!-- Option: Family Pay -->
                         <button type="button" 
                                 @click="siblings.length > 0 ? (payMode = 'family') : null" 
                                 :disabled="siblings.length === 0"
                                 class="w-full flex items-center justify-between p-4 rounded-xl border transition-all duration-200 text-left cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                 :class="siblings.length === 0 ? 'bg-gray-50 border-gray-100' : (payMode === 'family' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/10' : 'border-gray-200 hover:border-gray-300 bg-white')">
                             <div class="flex items-center gap-3">
                                 <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700 shrink-0">
                                     <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                 </div>
                                 <div>
                                     <p class="font-extrabold text-sm text-gray-900" style="margin:0;">
                                         Family Payment (Multiple Siblings)
                                         <template x-if="siblings.length === 0">
                                             <span class="text-[10px] text-gray-400 font-bold ml-1.5 uppercase normal-case">(No siblings linked)</span>
                                         </template>
                                     </p>
                                     <p class="text-[11px] text-gray-500 font-semibold mt-0.5">Distribute payment across sibling accounts.</p>
                                 </div>
                             </div>
                             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right text-gray-400"><polyline points="9 18 15 12 9 6"/></svg>
                         </button>
                     </div>

                     <!-- Sibling selection checkboxes (only if family pay selected) -->
                     <div x-show="payMode === 'family'" class="mt-4 bg-slate-50 border border-gray-150 p-4 rounded-2xl space-y-3 transition duration-200">
                         <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Select Siblings to Include</p>
                         
                         <!-- Main student (Always selected) -->
                         <label class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-gray-150 shadow-sm">
                             <input type="checkbox" checked disabled class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500/20">
                             <div>
                                 <p class="text-xs font-black text-gray-900" x-text="student.applicant.first_name + ' ' + student.applicant.last_name"></p>
                                 <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider" x-text="student.student_number"></p>
                             </div>
                         </label>

                         <!-- Siblings -->
                         <template x-for="sib in siblings" :key="sib.id">
                             <label class="flex items-center gap-3 p-2.5 rounded-xl bg-white border border-gray-150 hover:bg-slate-50 cursor-pointer shadow-sm">
                                 <input type="checkbox" :value="sib.id" x-model="selectedSiblings" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500/20">
                                 <div>
                                     <p class="text-xs font-black text-gray-900" x-text="sib.applicant.first_name + ' ' + sib.applicant.last_name"></p>
                                     <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider" x-text="sib.student_number"></p>
                                 </div>
                             </label>
                         </template>

                         <template x-if="siblings.length === 0">
                             <p class="text-[11px] text-amber-600 font-bold">No registered sibling accounts found linked to your parent's details.</p>
                         </template>

                         <button type="button" @click="step = 2" class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider py-3 text-xs cursor-pointer mt-2">
                             Continue to Upload <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                         </button>
                     </div>
                 </div>

                  <!-- Step 2: Upload Proof & Choose Dropdown Method -->
                  <div x-show="step === 2" class="space-y-6">
                      <div class="flex items-center justify-between">
                          <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 2: Upload Receipt</p>
                      </div>

                      <!-- Manual Dropdown select method -->
                      <div class="space-y-1.5 bg-slate-50 border border-gray-150 p-4 rounded-2xl">
                          <label for="payment-channel" class="text-[11px] font-extrabold text-gray-500 uppercase block">Payment Channel / Method</label>
                          <select id="payment-channel" x-model="method" aria-describedby="payment-channel-hint" class="w-full rounded-xl border-gray-200 bg-white text-xs font-bold py-2.5 shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20">
                              <option value="gcash">GCash</option>
                              <option value="maya">Maya</option>
                              <option value="bdo">BDO Bank Transfer</option>
                              <option value="bpi">BPI Bank Transfer</option>
                              <option value="remittance">Remittance (STC, Baqr, Al Rajhi, Tahweel, WU, etc.)</option>
                              <option value="other">Other Bank / Channel</option>
                          </select>
                          <p id="payment-channel-hint" class="text-[9px] text-gray-400 font-semibold">AI scan will automatically update this dropdown on scan success.</p>
                      </div>

                      <!-- Drag and Drop Box -->
                      <div class="space-y-2">
                          <label for="payment-receipt-upload" class="text-xs font-bold text-gray-700 block">Upload Receipt Image</label>
                          <div class="border-2 border-dashed border-gray-200 hover:border-emerald-500/50 focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 rounded-2xl text-center bg-white transition duration-200 relative group"
                               @dragover.prevent
                               @drop.prevent="handleFileDrop">
                               
                              <input id="payment-receipt-upload" type="file" x-ref="fileInput" class="sr-only" accept="image/*" @change="handleFileSelect">
                              
                              <template x-if="!receiptFile">
                                  <label for="payment-receipt-upload" class="block space-y-3 p-6 cursor-pointer rounded-2xl">
                                      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 mx-auto group-hover:scale-105 transition duration-200">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                      </div>
                                      <div class="text-xs">
                                          <span class="font-extrabold text-emerald-600 hover:text-emerald-700">Click to upload</span> or drag and drop
                                          <p class="text-[10px] text-gray-400 font-semibold mt-1">PNG, JPG, JPEG up to 10MB</p>
                                      </div>
                                  </label>
                              </template>

                              <template x-if="receiptFile">
                                  <div class="space-y-2 p-6">
                                      <div class="w-16 h-16 rounded-lg overflow-hidden mx-auto border border-gray-200 shadow-sm">
                                          <img :src="receiptPreview" class="w-full h-full object-cover">
                                      </div>
                                      <p class="text-xs font-extrabold text-gray-800 truncate px-4" x-text="receiptFileName"></p>
                                      <button type="button" @click.stop="receiptFile = null; receiptPreview = '';" class="text-[10px] font-bold text-rose-500 hover:underline">
                                          Remove image
                                      </button>
                                  </div>
                              </template>
                          </div>
                      </div>

                      <div x-show="scanError" class="bg-rose-50 border border-rose-100 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-rose-700 font-semibold">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-circle shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                          <span x-text="scanError"></span>
                      </div>

                      <!-- Step 2 Bottom Navigation -->
                      <div class="flex items-center gap-3 mt-6">
                          <button type="button" @click="step = 1" class="flex-1 flex items-center justify-center gap-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 font-extrabold uppercase py-3.5 text-xs cursor-pointer bg-white transition duration-150 shadow-sm">
                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> Back
                          </button>
                          <button type="button" @click="handleStep2Continue()" class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider py-3.5 text-xs cursor-pointer transition duration-150 shadow-sm">
                              Continue <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                          </button>
                      </div>
                  </div>

                  <!-- Final Submission Form wrapping Steps 3 and 4 -->
                  <form action="{{ route('student.billing.pay') }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      <input type="hidden" name="pay_mode" :value="payMode">
                      <input type="hidden" name="student_ids[]" :value="student.id">
                      <template x-for="sibId in selectedSiblings">
                          <input type="hidden" name="student_ids[]" :value="sibId">
                      </template>
                      <input type="hidden" name="method" :value="method">
                      <input type="hidden" name="amount" :value="amount">
                      <input type="hidden" name="reference_no" :value="referenceNo">
                      <input type="hidden" name="custom_remarks" :value="getCompiledRemarks()">
                      
                      <!-- Uploaded file holder -->
                      <div class="hidden">
                          <input type="file" name="receipt" id="final_receipt_file">
                      </div>

                      <!-- Step 3: Review Details & Add Remarks -->
                      <div x-show="step === 3" class="space-y-4">
                          <div class="flex items-center justify-between">
                              <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 3: Enter Payment Details</p>
                          </div>

                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                              <!-- Payment Provider (Editable) -->
                              <div class="space-y-1.5">
                                  <label for="review-payment-method" class="text-[11px] font-bold text-gray-400 uppercase">Payment Method</label>
                                  <select id="review-payment-method" x-model="method" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2.5 shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20">
                                      <option value="gcash">GCash</option>
                                      <option value="maya">Maya</option>
                                      <option value="bdo">BDO Bank Transfer</option>
                                      <option value="bpi">BPI Bank Transfer</option>
                                      <option value="remittance">Remittance (STC, Baqr, Al Rajhi, Tahweel, WU, etc.)</option>
                                      <option value="other">Other Bank / Channel</option>
                                  </select>
                              </div>

                              <!-- Amount (Editable) -->
                              <div class="space-y-1.5">
                                  <label for="review-payment-amount" class="text-[11px] font-bold text-gray-400 uppercase">Amount (PHP)</label>
                                  <input id="review-payment-amount" type="number" step="any" x-model="amount" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Enter amount (PHP)">
                              </div>

                              <!-- Reference Number (Editable) -->
                              <div class="space-y-1.5 sm:col-span-2">
                                  <label for="review-reference-number" class="text-[11px] font-bold text-gray-400 uppercase">Transaction Reference Number</label>
                                  <input id="review-reference-number" type="text" x-model="referenceNo" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Enter reference number">
                              </div>

                              <!-- Sender Name (Editable) -->
                              <div class="space-y-1.5">
                                  <label for="review-sender-name" class="text-[11px] font-bold text-gray-400 uppercase">Sender Name (from Receipt)</label>
                                  <input id="review-sender-name" type="text" x-model="senderName" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Sender name">
                              </div>

                              <!-- Receiver Name (Editable) -->
                              <div class="space-y-1.5">
                                  <label for="review-receiver-name" class="text-[11px] font-bold text-gray-400 uppercase">Receiver Name (Optional)</label>
                                  <input id="review-receiver-name" type="text" x-model="receiverName" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Receiver name">
                              </div>

                              <!-- Payment Date (Editable) -->
                              <div class="space-y-1.5 sm:col-span-2">
                                  <label for="review-payment-date" class="text-[11px] font-bold text-gray-400 uppercase">Payment Date (from Receipt)</label>
                                  <input id="review-payment-date" type="date" x-model="dateTime" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20">
                              </div>

                              <!-- Custom Remarks Textbox (Optional) -->
                              <div class="space-y-1.5 sm:col-span-2">
                                  <label for="review-payment-remarks" class="text-[11px] font-bold text-gray-400 uppercase">Add More Remarks / Comments</label>
                                  <textarea id="review-payment-remarks" x-model="customRemarks" rows="2" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Enter additional details (e.g. Reservation balance, notes for finance office)"></textarea>
                              </div>
                          </div>

                          <!-- Conditionally show AI scan status alert -->
                          <div x-show="scanSuccess" class="bg-emerald-50 border border-emerald-100 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-emerald-800 font-semibold">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-sparkles shrink-0 mt-0.5 text-emerald-600 animate-pulse"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1 1.275-1.275L12 3Z"/></svg>
                              <span>AI pre-scan completed! You can review or edit the fields above if needed.</span>
                          </div>
                          <div x-show="!scanSuccess" class="bg-amber-50 border border-amber-100 rounded-xl p-3.5 flex items-start gap-2.5 text-xs text-amber-800 font-semibold">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-info shrink-0 mt-0.5 text-amber-600"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12.01" y2="16"/><line x1="12" y1="12" x2="12" y2="8"/></svg>
                              <span>AI pre-scan was unsuccessful. Please enter your receipt details manually below.</span>
                          </div>

                          <!-- Step 3 Bottom Navigation -->
                          <div class="flex items-center gap-3 mt-6">
                              <button type="button" @click="step = 2" class="flex-1 flex items-center justify-center gap-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 font-extrabold uppercase py-3.5 text-xs cursor-pointer bg-white transition duration-150 shadow-sm">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> Back
                              </button>
                              <button type="button" @click="autoAllocate(); step = 4;" class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider py-3.5 text-xs cursor-pointer transition duration-150 shadow-sm">
                                  Continue <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                              </button>
                          </div>
                      </div>

                      <!-- Step 4: Payment Allocation -->
                      <div x-show="step === 4" class="space-y-4">
                          <div class="flex items-center justify-between">
                              <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 4: Payment Allocation</p>
                          </div>

                          <div class="space-y-3.5 max-h-[250px] overflow-y-auto pr-1">
                              <!-- Loop through active students (main student + checked siblings) -->
                              <template x-for="stud in getActiveStudents()" :key="stud.id">
                                  <div class="p-3.5 rounded-2xl border border-gray-150 bg-gray-50/30 space-y-3">
                                      <!-- Student Profile Name Header -->
                                      <div class="flex items-center justify-between border-b border-gray-150/60 pb-1.5">
                                          <span class="text-xs font-black text-gray-900" x-text="stud.applicant.first_name + ' ' + stud.applicant.last_name"></span>
                                          <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full" x-text="stud.id === student.id ? 'Main Account' : 'Sibling'"></span>
                                      </div>

                                      <!-- Billings list -->
                                      <div class="space-y-2">
                                          <template x-if="getUnpaidBillingsForStudent(stud.id).length === 0">
                                              <p class="text-[11px] text-gray-400 font-semibold">No unpaid monthly billings found.</p>
                                          </template>
                                          
                                          <template x-for="b in getUnpaidBillingsForStudent(stud.id)" :key="b.id">
                                              <div class="flex items-center justify-between gap-3 text-xs">
                                                  <!-- Checkbox + Month Name -->
                                                  <label class="flex items-center gap-2 cursor-pointer select-none">
                                                      <input type="checkbox" 
                                                             :value="b.id" 
                                                             x-model="checkedBillings" 
                                                             @change="toggleBilling(b.id, b.amount_due)"
                                                             class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500/20">
                                                      <span class="font-bold text-gray-700 capitalize" x-text="b.month_name"></span>
                                                      <span class="text-gray-400 font-semibold" x-text="'(Due: ₱' + parseFloat(b.amount_due).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')'"></span>
                                                  </label>

                                                  <!-- Allocation input field -->
                                                  <div class="flex items-center gap-1.5">
                                                      <span class="text-gray-400 font-bold">₱</span>
                                                      <label class="sr-only" :for="'allocation-' + b.id" x-text="'Allocation amount for ' + b.month_name"></label>
                                                      <input type="number"
                                                             :id="'allocation-' + b.id"
                                                             step="0.01" 
                                                             x-model="allocations[b.id]"
                                                             placeholder="0.00"
                                                             class="w-24 rounded-lg border-gray-200 bg-white py-1 px-1.5 text-right font-bold text-xs focus:border-emerald-500 focus:ring-emerald-500/20"
                                                             :disabled="!checkedBillings.includes(b.id)">
                                                  </div>
                                              </div>
                                          </template>
                                      </div>
                                  </div>
                              </template>
                          </div>

                          <!-- Allocation Live Stats -->
                          <div class="bg-gray-50 border border-gray-150 rounded-2xl p-3.5 space-y-2 text-xs font-semibold">
                              <div class="flex justify-between items-center text-gray-600">
                                  <span>Receipt Total Amount:</span>
                                  <span class="font-extrabold text-gray-900" x-text="'₱' + (parseFloat(amount) || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                              </div>
                              <div class="flex justify-between items-center text-gray-600">
                                  <span>Total Allocated Amount:</span>
                                  <span class="font-extrabold text-emerald-700" x-text="'₱' + getTotalAllocated().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                              </div>
                              
                              <!-- Unallocated Warning/Helper -->
                              <div class="flex justify-between items-center pt-1.5 border-t border-gray-150">
                                  <span class="text-gray-500">Unallocated Balance:</span>
                                  <span class="font-black" 
                                        :class="getUnallocatedAmount() > 0 ? 'text-amber-600' : 'text-gray-700'"
                                        x-text="'₱' + getUnallocatedAmount().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                              </div>
                              <template x-if="getUnallocatedAmount() > 0">
                                  <p class="text-[10px] text-amber-600 font-bold leading-normal mt-1">
                                      ⚠️ Remaining ₱<span x-text="getUnallocatedAmount().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span> will be posted as general account credit.
                                  </p>
                              </template>
                          </div>

                          <!-- New Balance Projection -->
                          <div class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-3.5 flex justify-between items-center text-xs font-semibold">
                              <span class="text-emerald-800">Projected Remaining Balance:</span>
                              <span class="font-black text-emerald-900 text-sm" x-text="'₱' + getNewBalance().toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                          </div>

                          <!-- Authenticity Confirmation Checkbox -->
                          <div class="pt-1">
                              <label class="flex items-start gap-3 p-3.5 rounded-2xl bg-amber-50/50 border border-amber-100 cursor-pointer select-none">
                                  <input type="checkbox" x-model="confirmAuthentic" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500/20 mt-0.5">
                                  <span class="text-xs font-bold text-amber-800 leading-snug">
                                      I confirm that the uploaded payment receipt is authentic and belongs to this student.
                                  </span>
                              </label>
                          </div>

                          <!-- Step 4 Bottom Navigation -->
                          <div class="flex items-center gap-3 mt-6">
                              <button type="button" @click="step = 3" class="flex-1 flex items-center justify-center gap-2 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 font-extrabold uppercase py-3.5 text-xs cursor-pointer bg-white transition duration-150 shadow-sm">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> Back
                              </button>
                              <button type="submit" 
                                      :disabled="!confirmAuthentic" 
                                      class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black uppercase tracking-wider py-3.5 text-xs cursor-pointer transition duration-150 shadow-sm">
                                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><polyline points="20 6 9 17 4 12"/></svg> Confirm & Submit
                              </button>
                          </div>
                      </div>
                  </form>
             </div>
        </div>
    </div>
</div>

@php
    $unpaidBillingsData = $billings->where('status', 'unpaid')->map(fn($b) => [
        'id' => $b->id,
        'month_name' => $b->month_name,
        'amount_due_formatted' => number_format((float) $b->amount_due, 2),
        'amount_due' => (float) $b->amount_due
    ])->values();
@endphp

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('paymentWizard', () => ({
            step: 1,
            openPaymentModal: {{ request()->has('upload') ? 'true' : 'false' }},
            payMode: 'single',
            selectedSiblings: [],
            customRemarks: '',
            siblings: @json($siblings),
            student: @json($student),
            account: @json($account),
            
            paymentType: 'tuition',
            othersPurpose: '',
            installmentId: '',
            receiptFile: null,
            receiptFileName: '',
            receiptPreview: '',
            scanning: false,
            scanProgress: 0,
            scanError: '',
            scanSuccess: false,
            
            // Extracted details
            method: 'gcash',
            amount: '',
            referenceNo: '',
            senderName: '',
            receiverName: '',
            dateTime: '',
            
            hasBillings: @json($billings->isNotEmpty()),
            unpaidBillings: @json($unpaidBillingsData),
            checkedBillings: [],
            allocations: {},
            confirmAuthentic: false,
            lastFocusedElement: null,

            initializeModal() {
                document.body.classList.toggle('student-modal-open', this.openPaymentModal);

                if (this.openPaymentModal) {
                    this.$nextTick(() => this.focusModal());
                }
            },

            openModal() {
                this.lastFocusedElement = document.activeElement;
                this.resetWizard();
                this.openPaymentModal = true;
                this.$nextTick(() => this.focusModal());
            },

            closeModal() {
                if (!this.openPaymentModal) return;

                const focusTarget = this.lastFocusedElement;
                this.openPaymentModal = false;
                this.$nextTick(() => {
                    if (focusTarget && typeof focusTarget.focus === 'function') {
                        focusTarget.focus();
                    }
                });
            },

            focusModal() {
                const focusable = this.getModalFocusable();
                if (focusable.length > 0) focusable[0].focus();
            },

            getModalFocusable() {
                if (!this.$refs.paymentModal) return [];

                return Array.from(this.$refs.paymentModal.querySelectorAll(
                    'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )).filter(element => element.offsetParent !== null);
            },

            trapModalFocus(event) {
                if (!this.openPaymentModal) return;

                const focusable = this.getModalFocusable();
                if (focusable.length === 0) return;

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                } else if (!focusable.includes(document.activeElement)) {
                    event.preventDefault();
                    first.focus();
                }
            },

            resetWizard() {
                this.step = 1;
                this.payMode = 'single';
                this.selectedSiblings = [];
                this.customRemarks = '';
                
                this.paymentType = 'tuition';
                this.othersPurpose = '';
                this.installmentId = '';
                this.receiptFile = null;
                this.receiptFileName = '';
                this.receiptPreview = '';
                this.scanning = false;
                this.scanProgress = 0;
                this.scanError = '';
                this.scanSuccess = false;
                
                this.method = 'gcash';
                this.amount = '';
                this.referenceNo = '';
                this.senderName = '';
                this.receiverName = '';
                this.dateTime = '';
                this.checkedBillings = [];
                this.allocations = {};
                this.confirmAuthentic = false;
            },

            getInstallmentName() {
                if (!this.installmentId) return '-- General / Multiple months --';
                const b = this.unpaidBillings.find(x => x.id == this.installmentId);
                return b ? b.month_name : '';
            },

            handleFileSelect(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    this.processFile(files[0]);
                }
            },

            handleFileDrop(e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.processFile(files[0]);
                }
            },

            processFile(file) {
                if (!file.type.startsWith('image/')) {
                    this.scanError = 'Please upload a valid image file (PNG, JPG, JPEG).';
                    return;
                }
                this.receiptFile = file;
                this.receiptFileName = file.name;
                
                // Sync to the final form's file input
                const dt = new DataTransfer();
                dt.items.add(file);
                const finalInput = document.getElementById('final_receipt_file');
                if (finalInput) {
                    finalInput.files = dt.files;
                }

                // Create local preview
                const reader = new FileReader();
                reader.onload = e => {
                    this.receiptPreview = e.target.result;
                };
                reader.readAsDataURL(file);

             },

             handleStep2Continue() {
                 if (!this.receiptFile) {
                     this.scanError = 'Please upload a receipt image first.';
                     return;
                 }
                 this.runOcrScan();
             },

            runOcrScan() {
                this.scanning = true;
                this.scanError = '';
                this.scanSuccess = false;
                
                const formData = new FormData();
                formData.append('receipt', this.receiptFile);
                
                fetch('{{ route("student.billing.ocr") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(res => {
                    if (!res.ok) throw new Error('AI Scan request failed.');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        // Check if both reference and amount are null -> Reject
                        if (!data.detected_ref && !data.detected_amount) {
                            throw new Error('No transaction details detected.');
                        }

                        // Populate fields
                        this.amount = data.detected_amount || '';
                        this.referenceNo = data.detected_ref || '';
                        this.senderName = data.detected_sender || '';
                        this.receiverName = data.detected_receiver || '';
                        // Parse date to YYYY-MM-DD for HTML5 input compatibility
                        if (data.detected_date) {
                            try {
                                const parsedDate = new Date(data.detected_date);
                                if (!isNaN(parsedDate.getTime())) {
                                    const yyyy = parsedDate.getFullYear();
                                    const mm = String(parsedDate.getMonth() + 1).padStart(2, '0');
                                    const dd = String(parsedDate.getDate()).padStart(2, '0');
                                    this.dateTime = `${yyyy}-${mm}-${dd}`;
                                } else {
                                    this.dateTime = '';
                                }
                            } catch (e) {
                                this.dateTime = '';
                            }
                        } else {
                            this.dateTime = '';
                        }
                        
                        if (data.detected_method) {
                            const mLower = data.detected_method.toLowerCase();
                            if (mLower.includes('gcash')) {
                                this.method = 'gcash';
                            } else if (mLower.includes('maya')) {
                                this.method = 'maya';
                            } else if (mLower.includes('bdo') || mLower.includes('banco de oro')) {
                                this.method = 'bdo';
                            } else if (mLower.includes('bpi')) {
                                this.method = 'bpi';
                            } else if (
                                mLower.includes('stc') ||
                                mLower.includes('baqr') ||
                                mLower.includes('al rajhi') ||
                                mLower.includes('rajhi') ||
                                mLower.includes('tahweel') ||
                                mLower.includes('western union') ||
                                mLower.includes('moneygram') ||
                                mLower.includes('remittance')
                            ) {
                                this.method = 'remittance';
                            } else {
                                this.method = 'other';
                            }
                        }
                        
                        this.scanSuccess = true;
                    } else {
                        throw new Error(data.message || 'AI scanning unsuccessful.');
                    }
                })
                .catch(err => {
                    console.warn('AI pre-scan warning:', err.message);
                    this.scanSuccess = false;
                    
                    // Reset fields for clean manual input on Step 3 fallback
                    this.amount = '';
                    this.referenceNo = '';
                    this.senderName = '';
                    this.receiverName = '';
                    this.dateTime = '';
                })
                .finally(() => {
                    this.scanning = false;
                    this.step = 3;
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                });
            },

            getActiveStudents() {
                const list = [this.student];
                if (this.payMode === 'family') {
                    this.siblings.forEach(sib => {
                        if (this.selectedSiblings.includes(sib.id)) {
                            list.push(sib);
                        }
                    });
                }
                return list;
            },

            getUnpaidBillingsForStudent(studId) {
                if (studId === this.student.id) {
                    return this.unpaidBillings;
                }
                const sib = this.siblings.find(s => s.id === studId);
                if (sib && sib.account && sib.account.monthly_billings) {
                    return sib.account.monthly_billings.map(b => ({
                        id: b.id,
                        month_name: b.month_name,
                        amount_due: parseFloat(b.amount_due)
                    }));
                }
                return [];
            },

            toggleBilling(billingId, amountDue) {
                const idx = this.checkedBillings.indexOf(billingId);
                if (idx !== -1) {
                    const totalReceipt = parseFloat(this.amount) || 0;
                    const currentAllocated = Object.entries(this.allocations)
                        .filter(([id]) => id != billingId)
                        .reduce((sum, [_, val]) => sum + parseFloat(val || 0), 0);
                    const remaining = Math.max(0, totalReceipt - currentAllocated);
                    const allocate = Math.min(amountDue, remaining);
                    this.allocations[billingId] = allocate.toFixed(2);
                } else {
                    this.allocations[billingId] = '0.00';
                }
            },

            autoAllocate() {
                this.checkedBillings = [];
                this.allocations = {};
                
                const totalReceipt = parseFloat(this.amount) || 0;
                if (totalReceipt <= 0) return;
                
                const activeStudents = this.getActiveStudents();
                
                // Calculate outstanding balances for proportional distribution
                let totalOutstanding = 0;
                activeStudents.forEach(stud => {
                    const bal = stud.id === this.student.id 
                        ? parseFloat(this.account?.remaining_balance || 0) 
                        : parseFloat(stud.account?.remaining_balance || 0);
                    totalOutstanding += bal;
                });
                
                let remainingTotalReceipt = totalReceipt;
                
                // Proportional share per student
                activeStudents.forEach((stud, index) => {
                    const bal = stud.id === this.student.id 
                        ? parseFloat(this.account?.remaining_balance || 0) 
                        : parseFloat(stud.account?.remaining_balance || 0);
                    
                    let studentShare = 0;
                    if (index === activeStudents.length - 1) {
                        // Last student gets whatever is left to avoid rounding errors
                        studentShare = remainingTotalReceipt;
                    } else if (totalOutstanding > 0) {
                        studentShare = Math.min(remainingTotalReceipt, parseFloat((totalReceipt * (bal / totalOutstanding)).toFixed(2)));
                        remainingTotalReceipt = parseFloat((remainingTotalReceipt - studentShare).toFixed(2));
                    } else {
                        studentShare = Math.min(remainingTotalReceipt, parseFloat((totalReceipt / activeStudents.length).toFixed(2)));
                        remainingTotalReceipt = parseFloat((remainingTotalReceipt - studentShare).toFixed(2));
                    }
                    
                    // Allocate student's share to their unpaid billings oldest-to-newest
                    let remainingShare = studentShare;
                    const billings = this.getUnpaidBillingsForStudent(stud.id);
                    billings.forEach(b => {
                        if (remainingShare > 0) {
                            const due = parseFloat(b.amount_due) || 0;
                            const alloc = Math.min(due, remainingShare);
                            if (alloc > 0) {
                                this.allocations[b.id] = alloc.toFixed(2);
                                this.checkedBillings.push(b.id);
                                remainingShare = parseFloat((remainingShare - alloc).toFixed(2));
                            }
                        }
                    });
                });
            },

            getTotalAllocated() {
                return Object.values(this.allocations).reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
            },

            getUnallocatedAmount() {
                const totalReceipt = parseFloat(this.amount) || 0;
                return Math.max(0, totalReceipt - this.getTotalAllocated());
            },

            getNewBalance() {
                const currentBalance = this.getActiveStudents().reduce((sum, stud) => {
                    const bal = stud.id === this.student.id 
                        ? parseFloat(this.account?.remaining_balance || 0) 
                        : parseFloat(stud.account?.remaining_balance || 0);
                    return sum + bal;
                }, 0);
                const totalReceipt = parseFloat(this.amount) || 0;
                return Math.max(0, currentBalance - totalReceipt);
            },

            getCompiledRemarks() {
                let parts = [];
                if (this.senderName) {
                    parts.push('Sender: ' + this.senderName);
                }
                if (this.receiverName) {
                    parts.push('Receiver: ' + this.receiverName);
                }
                if (this.dateTime) {
                    parts.push('Payment Date: ' + this.dateTime);
                }
                
                // Add allocations
                let allocParts = [];
                this.getActiveStudents().forEach(stud => {
                    const billings = this.getUnpaidBillingsForStudent(stud.id);
                    billings.forEach(b => {
                        const amt = parseFloat(this.allocations[b.id] || 0);
                        if (amt > 0) {
                            const name = stud.applicant.first_name + ' ' + stud.applicant.last_name;
                            allocParts.push(`${name} - ${b.month_name}: PHP ${amt.toFixed(2)}`);
                        }
                    });
                });
                if (allocParts.length > 0) {
                    parts.push('Allocations: [' + allocParts.join(', ') + ']');
                }
                
                if (this.customRemarks) {
                    parts.push('Remarks: ' + this.customRemarks);
                }
                return parts.join(' | ');
            }
        }));
    });
</script>
</x-student-layout>
