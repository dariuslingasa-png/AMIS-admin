<x-student-layout title="Statement of Account">
<div class="space-y-8" x-data="paymentWizard()" x-cloak>
    <!-- Validation Errors Display -->
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-xs text-rose-700 font-semibold space-y-1">
            <p class="font-extrabold uppercase tracking-wider mb-1">Payment Submission Failed:</p>
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Top Summary Banner -->
    <div class="student-panel flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="space-y-2 relative z-10">
            <span class="student-status-pill">
                <i data-lucide="credit-card" class="w-3.5 h-3.5 mr-1 text-emerald-600"></i> Statement of Account
            </span>
            <h2 class="text-2xl font-black text-gray-900" style="margin: 8px 0 4px;">Tuition & School Fees Overview</h2>
            <p class="text-gray-500 text-sm font-semibold">Keep track of school dues, review monthly plans, and upload new payment screenshots.</p>
        </div>

        <div class="flex gap-4 relative z-10">
            <!-- Outstanding Box -->
            <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl text-center min-w-[150px]">
                <p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Remaining Balance</p>
                <p class="text-xl font-black text-gray-900 mt-1">
                    PHP {{ number_format((float) ($account->remaining_balance ?? 0), 2) }}
                </p>
            </div>

            <!-- Paid Box -->
            <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-xl text-center min-w-[150px]">
                <p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Total Amount Paid</p>
                <p class="text-xl font-black text-emerald-700 mt-1">
                    PHP {{ number_format((float) ($account->amount_paid ?? 0), 2) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Column 1 & 2: Billing details, Monthly schedule, History -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Fee Breakdown card -->
            @if($account)
                <div class="student-panel">
                    <div class="student-panel-header">
                        <h2>Detailed Fee Breakdown</h2>
                    </div>
                    
                    <div class="student-table-scroll mt-4">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fee Item</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Base Tuition Fee</td>
                                    <td class="text-right font-semibold text-gray-700">PHP {{ number_format((float) $account->tuition_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Books & Learning Materials</td>
                                    <td class="text-right font-semibold text-gray-700">PHP {{ number_format((float) $account->books_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Miscellaneous Fees</td>
                                    <td class="text-right font-semibold text-gray-700">PHP {{ number_format((float) $account->miscellaneous_fee, 2) }}</td>
                                </tr>
                                @if($account->discount_amount > 0)
                                    <tr class="text-emerald-700 bg-emerald-50 font-bold">
                                        <td>Sibling Discount ({{ $account->discount_type }})</td>
                                        <td class="text-right">- PHP {{ number_format((float) $account->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="bg-emerald-50/50 text-emerald-950 font-extrabold text-base border-t border-emerald-100/50">
                                    <td>Gross Total Balance</td>
                                    <td class="text-right text-emerald-700">PHP {{ number_format((float) $account->total_balance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Monthly Statement waterfall -->
            <div class="student-panel">
                <div class="student-panel-header">
                    <h2>Monthly Billing Schedule</h2>
                </div>

                @if($billings->isNotEmpty())
                    <div class="space-y-4 pt-4">
                        @foreach($billings as $billing)
                            @php 
                                $isOverdue = $billing->status === 'unpaid' && $billing->due_date->isPast();
                            @endphp
                            <div class="p-4 rounded-xl border border-gray-150 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-gray-50/50 transition duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-sm shadow-sm shrink-0 {{ $billing->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($isOverdue ? 'bg-rose-100 text-rose-700 animate-pulse' : 'bg-emerald-50 text-emerald-700') }}">
                                        {{ mb_substr($billing->month_name, 0, 3) }}
                                    </div>
                                    <div>
                                        <h5 class="font-extrabold text-gray-900 text-sm sm:text-base" style="margin:0;">
                                            {{ $billing->month_name }} Dues
                                        </h5>
                                        <p class="text-xs font-semibold text-gray-500 mt-0.5">Due on {{ $billing->due_date->format('F d, Y') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between sm:justify-end gap-6">
                                    <div class="text-left sm:text-right">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase">Amount Due</p>
                                        <p class="font-extrabold text-sm sm:text-base text-gray-800 mt-0.5">PHP {{ number_format((float) $billing->amount_due, 2) }}</p>
                                    </div>

                                    <div>
                                        @if($billing->status === 'paid')
                                            <span class="student-status-pill">
                                                <i data-lucide="check" class="w-3 h-3 mr-1"></i> Paid
                                            </span>
                                        @elseif($isOverdue)
                                            <span class="student-status-pill bg-rose-105 text-rose-700 border-rose-200">
                                                <i data-lucide="alert-circle" class="w-3 h-3 mr-1"></i> Overdue
                                            </span>
                                        @else
                                            <span class="student-status-pill bg-sky-50 text-sky-700 border-sky-100">
                                                <i data-lucide="clock" class="w-3 h-3 mr-1"></i> Upcoming
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty">
                        <i data-lucide="calendar"></i>
                        <p>No monthly installments configured</p>
                    </div>
                @endif
            </div>

            <!-- Uploaded history list (Moved to Right Column sidebar) -->
        </div>

        <!-- Column 3: Finance Actions & History -->
        <div class="lg:col-span-1 space-y-8">
            <!-- CTA Upload Box -->
            <div class="student-panel text-center p-6 space-y-4">
                <div class="w-14 h-14 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload-cloud"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><polyline points="16 16 12 12 8 16"/></svg>
                </div>
                <div class="space-y-1.5">
                    <h3 class="font-black text-gray-900 text-base" style="margin: 0;">Submit Payment Proof</h3>
                    <p class="text-[11px] text-gray-500 font-semibold leading-relaxed">Have you paid? Upload your transaction receipt (GCash, Maya, Bank or Remittance) to submit to finance for verification.</p>
                </div>
                <button type="button" @click="openPaymentModal = true; resetWizard();" class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider py-3.5 text-xs cursor-pointer shadow-md shadow-emerald-600/10 hover:shadow-lg hover:shadow-emerald-600/20 transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus-circle"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>
                    Upload Payment Proof
                </button>
            </div>

            <!-- Uploaded history list -->
            <div class="student-panel">
                <div class="student-panel-header">
                    <h2>Previous Payments</h2>
                </div>

                @if($payments->isNotEmpty())
                    <div class="space-y-4 pt-4">
                        @foreach($payments as $pay)
                            <div class="p-4 rounded-xl border border-gray-150 bg-gray-50/20 hover:bg-gray-50/40 transition duration-300">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h5 class="font-extrabold text-sm text-gray-900 capitalize" style="margin:0;">
                                                    {{ $pay->method }}
                                                </h5>
                                                @if($pay->receipt_url)
                                                    <a href="{{ asset('storage/' . $pay->receipt_url) }}" target="_blank" class="text-[9px] bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded transition duration-300">
                                                        View Receipt
                                                    </a>
                                                @endif
                                            </div>
                                            <p class="text-[11px] text-gray-500 font-semibold mt-1">
                                                Ref: <span class="font-bold text-gray-700">{{ $pay->reference_no }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="font-black text-sm text-gray-900">PHP {{ number_format((float) $pay->amount, 2) }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-gray-100 pt-2 text-[11px] font-semibold text-gray-500">
                                        <span>{{ $pay->paid_at ? $pay->paid_at->format('M d, Y') : $pay->created_at->format('M d, Y') }}</span>
                                        <div>
                                            @if($pay->status === 'verified')
                                                <span class="student-status-pill text-[10px] px-2 py-0.5">
                                                    Verified
                                                </span>
                                            @elseif($pay->status === 'rejected')
                                                <span class="student-status-pill bg-rose-50 text-rose-700 border-rose-200 text-[10px] px-2 py-0.5" title="Remarks: {{ $pay->remarks ?? 'None' }}">
                                                    Rejected
                                                </span>
                                            @else
                                                <span class="student-status-pill bg-amber-50 text-amber-700 border-amber-100 text-[10px] px-2 py-0.5">
                                                    Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($pay->status === 'rejected' && $pay->remarks)
                                    <div class="mt-2 text-[11px] font-bold text-rose-600 border-t border-rose-100/50 pt-2">
                                        Remarks: <span class="font-normal text-rose-500">{{ $pay->remarks }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dash-empty py-8 text-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-history mx-auto mb-2 text-gray-300"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                        <p class="text-xs">No payment history recorded</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Payment Wizard Modal Backdrop -->
    <div x-show="openPaymentModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <!-- Modal Card Container -->
        <div class="bg-white rounded-3xl shadow-2xl border border-gray-150 w-full max-w-xl overflow-hidden relative"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
             
             <!-- Close Button -->
             <button type="button" @click="openPaymentModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 cursor-pointer p-1.5 rounded-full hover:bg-gray-100 transition duration-200 z-10" style="position: absolute; top: 16px; right: 16px;">
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
                         <h3 class="font-black text-gray-900 text-lg mt-1" style="margin: 4px 0 2px;">Submit Payment</h3>
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
                          <label class="text-[11px] font-extrabold text-gray-500 uppercase block">Payment Channel / Method</label>
                          <select x-model="method" class="w-full rounded-xl border-gray-200 bg-white text-xs font-bold py-2.5 shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20">
                              <option value="gcash">GCash</option>
                              <option value="maya">Maya</option>
                              <option value="bdo">BDO Bank Transfer</option>
                              <option value="bpi">BPI Bank Transfer</option>
                              <option value="remittance">Remittance (STC, Baqr, Al Rajhi, Tahweel, WU, etc.)</option>
                              <option value="other">Other Bank / Channel</option>
                          </select>
                          <p class="text-[9px] text-gray-400 font-semibold">AI scan will automatically update this dropdown on scan success.</p>
                      </div>

                      <!-- Drag and Drop Box -->
                      <div class="space-y-2">
                          <label class="text-xs font-bold text-gray-700 block">Upload Receipt Image</label>
                          <div class="border-2 border-dashed border-gray-200 hover:border-emerald-500/50 rounded-2xl p-6 text-center cursor-pointer bg-white transition duration-200 relative group"
                               @click="$refs.fileInput.click()"
                               @dragover.prevent
                               @drop.prevent="handleFileDrop">
                               
                              <input type="file" x-ref="fileInput" class="hidden" accept="image/*" @change="handleFileSelect">
                              
                              <template x-if="!receiptFile">
                                  <div class="space-y-3">
                                      <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 mx-auto group-hover:scale-105 transition duration-200">
                                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                      </div>
                                      <div class="text-xs">
                                          <span class="font-extrabold text-emerald-600 hover:text-emerald-700">Click to upload</span> or drag and drop
                                          <p class="text-[10px] text-gray-400 font-semibold mt-1">PNG, JPG, JPEG up to 10MB</p>
                                      </div>
                                  </div>
                              </template>

                              <template x-if="receiptFile">
                                  <div class="space-y-2">
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
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Payment Method</label>
                                  <select x-model="method" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2.5 shadow-sm focus:border-emerald-500 focus:ring-emerald-500/20">
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
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Amount (PHP)</label>
                                  <input type="number" step="any" x-model="amount" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Enter amount (PHP)">
                              </div>

                              <!-- Reference Number (Editable) -->
                              <div class="space-y-1.5 sm:col-span-2">
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Transaction Reference Number</label>
                                  <input type="text" x-model="referenceNo" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Enter reference number">
                              </div>

                              <!-- Sender Name (Editable) -->
                              <div class="space-y-1.5">
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Sender Name (from Receipt)</label>
                                  <input type="text" x-model="senderName" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Sender name">
                              </div>

                              <!-- Receiver Name (Editable) -->
                              <div class="space-y-1.5">
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Receiver Name (Optional)</label>
                                  <input type="text" x-model="receiverName" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Receiver name">
                              </div>

                              <!-- Payment Date (Editable) -->
                              <div class="space-y-1.5 sm:col-span-2">
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Payment Date (from Receipt)</label>
                                  <input type="date" x-model="dateTime" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20">
                              </div>

                              <!-- Custom Remarks Textbox (Optional) -->
                              <div class="space-y-1.5 sm:col-span-2">
                                  <label class="text-[11px] font-bold text-gray-400 uppercase">Add More Remarks / Comments</label>
                                  <textarea x-model="customRemarks" rows="2" class="w-full rounded-xl border-gray-200 bg-white text-xs font-semibold py-2 focus:border-emerald-500 focus:ring-emerald-500/20" placeholder="Enter additional details (e.g. Reservation balance, notes for finance office)"></textarea>
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
                                                      <input type="number" 
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
