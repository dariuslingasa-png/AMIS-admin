        <div x-show="approveModal" x-cloak x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl p-6 space-y-4" @click.away="!isSubmitting && (approveModal = false)">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-black text-slate-950 uppercase tracking-wider flex items-center gap-2" style="font-size: 19.5px !important;">
                        <i data-lucide="check-circle" class="h-6 w-6 text-emerald-600"></i>
                        Verify Payment Proof
                    </h3>
                    <button type="button" @click="approveModal = false" :disabled="isSubmitting" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 transition disabled:opacity-50">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                
                <!-- Body Form -->
                <form id="approve-form" action="" method="POST" @submit="isSubmitting = true" class="space-y-4 text-left">
                    @csrf
                    @method('PATCH')
                    
                    <div>
                        <span class="text-[13.5px] text-slate-400 font-bold uppercase tracking-wider">Invoice No</span>
                        <div class="font-black text-slate-900 mt-0.5" style="font-size: 19.5px !important;" x-text="currentInvoice"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Method of Payment</label>
                            <select name="finance_method" x-model="financeMethod" required class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                                <option value="remittance">Remittance</option>
                                <option value="gcash">GCash</option>
                                <option value="bdo">BDO Bank Transfer</option>
                                <option value="maya">Maya</option>
                                <option value="cash">Cash</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Payment Date</label>
                            <input type="date" name="finance_payment_date" x-model="financePaymentDate" required class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                        </div>
                    </div>

                    <div>
                        <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Reference No</label>
                        <input type="text" name="finance_reference_no" x-model="financeReferenceNo" placeholder="e.g. 105251011098847" class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>

                    <div x-show="financeMethod === 'remittance'" x-transition>
                        <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Remittance Source</label>
                        <input type="text" name="remittance_source" x-model="remittanceSource" placeholder="e.g. AL GHURAIR EXCHANGE, RAKBANK" :required="financeMethod === 'remittance'" class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-2.5 text-base text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    </div>
                    
                    <div class="space-y-1.5 rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                        <span class="text-[13.5px] text-emerald-700 font-bold uppercase tracking-wider block">Official Receipt (OR) to be Generated:</span>
                        <div class="font-black text-emerald-800" style="font-size: 21.5px !important;" x-text="predictedOr"></div>
                        <p class="text-[13.5px] text-emerald-600 font-semibold mt-1">This OR number is automatically calculated based on the payment sequence rules and will be locked upon verification.</p>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" @click="approveModal = false" :disabled="isSubmitting" class="btn-premium btn-cancel">
                            Cancel
                        </button>
                        <button type="submit" class="btn-premium btn-approve">
                            <span x-show="!isSubmitting">Confirm Verify</span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5 justify-center">
                                <svg class="animate-spin h-4.5 w-4.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Payment Modal -->
        <div x-show="rejectModal" x-cloak x-transition class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="relative w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl p-6 space-y-4" @click.away="!isSubmitting && (rejectModal = false)">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-black text-slate-950 uppercase tracking-wider flex items-center gap-2" style="font-size: 19.5px !important;">
                        <i data-lucide="alert-circle" class="h-6 w-6 text-rose-600"></i>
                        Reject Payment
                    </h3>
                    <button type="button" @click="rejectModal = false" :disabled="isSubmitting" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 transition disabled:opacity-50">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                
                <!-- Body Form -->
                <form id="reject-form" action="" method="POST" @submit="isSubmitting = true" class="space-y-4 text-left">
                    @csrf
                    @method('PATCH')
                    
                    <div>
                        <span class="text-[13.5px] text-slate-400 font-bold uppercase tracking-wider">Invoice No</span>
                        <div class="font-black text-slate-900 mt-0.5" style="font-size: 19.5px !important;" x-text="currentInvoice"></div>
                    </div>
                    
                    <!-- Predefined quick remarks pills -->
                    <div class="space-y-1.5">
                        <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block">Quick Rejection Reasons:</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="remarks = 'Malabo / Unreadable Payment Proof'" :disabled="isSubmitting" class="px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-[13.5px] font-bold text-slate-700 transition cursor-pointer disabled:opacity-50">
                                Malabo Proof
                            </button>
                            <button type="button" @click="remarks = 'Missing Receipt / Invalid Upload'" :disabled="isSubmitting" class="px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-[13.5px] font-bold text-slate-700 transition cursor-pointer disabled:opacity-50">
                                Missing Receipt
                            </button>
                            <button type="button" @click="remarks = 'Incorrect Amount Paid'" :disabled="isSubmitting" class="px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-[13.5px] font-bold text-slate-700 transition cursor-pointer disabled:opacity-50">
                                Incorrect Amount
                            </button>
                            <button type="button" @click="remarks = 'Wrong Reference Number Entered'" :disabled="isSubmitting" class="px-3.5 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-[13.5px] font-bold text-slate-700 transition cursor-pointer disabled:opacity-50">
                                Wrong Ref Number
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-[13.5px] text-slate-500 font-bold uppercase tracking-wider block">Description / Remarks:</label>
                        <textarea name="remarks" x-model="remarks" required placeholder="Describe the reason for rejection (e.g. malabo, missing receipt)..." class="w-full rounded-2xl border border-slate-250 bg-white px-4 py-3 text-base text-black focus:outline-none focus:ring-2 focus:ring-rose-500 transition disabled:opacity-75 disabled:bg-slate-50" rows="3"></textarea>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                        <button type="button" @click="rejectModal = false" :disabled="isSubmitting" class="btn-premium btn-cancel">
                            Cancel
                        </button>
                        <button type="submit" class="btn-premium btn-reject">
                            <span x-show="!isSubmitting">Confirm Reject</span>
                            <span x-show="isSubmitting" class="flex items-center gap-1.5 justify-center">
                                <svg class="animate-spin h-4.5 w-4.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Processing...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
