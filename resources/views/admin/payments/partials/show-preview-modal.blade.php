        <div x-show="preview" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="relative max-h-[92vh] w-full max-w-7xl overflow-hidden rounded-3xl bg-white shadow-2xl flex flex-col">
                <!-- Header -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 bg-white">
                    <h2 class="font-black text-slate-950" x-text="label"></h2>
                    <div class="ml-auto flex items-center gap-2">
                        <div class="flex items-center gap-2" x-show="!pdf">
                            <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="zoomOut()">-</button>
                            <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(zoom * 100) + '%'"></span>
                            <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="zoomIn()">+</button>
                            <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100 cursor-pointer" @click="resetZoom()">Reset</button>
                        </div>
                        <button id="download-pdf-btn" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadPdf()">
                            <i data-lucide="download" class="h-3.5 w-3.5"></i> Download PDF
                        </button>
                        <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 cursor-pointer" @click="closePreview()">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Split Body -->
                <div class="flex flex-1 min-h-0 overflow-hidden">
                    <!-- Left Side: Receipt Proof Viewer -->
                    <div class="flex-1 cursor-grab select-none overflow-auto bg-slate-50 p-4"
                         @mousedown="startPan($event)"
                         @mousemove="movePan($event)"
                         @mouseleave="stopPan()"
                         @touchstart.passive="startPan($event)"
                         @touchmove="movePan($event)">
                        <template x-if="pdf">
                            <iframe :src="src" class="h-[72vh] w-full rounded-2xl bg-white border-0"></iframe>
                        </template>
                        <template x-if="!pdf">
                            <img :src="src" :alt="label" class="mx-auto rounded-2xl object-contain transition-all duration-150" :style="'max-width: none; width: ' + (zoom * 100) + '%; height: auto;'">
                        </template>
                    </div>

                    <!-- Right Side: Verify Form Panel -->
                    @if ($canReviewPayments)
                        <div x-show="currentPayment" class="w-96 border-l border-slate-200 bg-white p-5 flex flex-col justify-between overflow-y-auto select-text">
                            <!-- Verification Form -->
                            <form id="modal-approve-form-finance" action="" method="POST" @submit="isSubmitting = true" class="space-y-4 text-left">
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
                                    <div class="font-black text-slate-900 mt-0.5 text-base" x-text="currentInvoice"></div>
                                </div>

                                <div class="grid grid-cols-1 gap-3.5">
                                    <div>
                                        <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Method of Payment</label>
                                        <select name="finance_method" x-model="financeMethod" required class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
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
                                        <input type="date" name="finance_payment_date" x-model="financePaymentDate" required class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Amount</label>
                                        <input type="number" step="0.01" name="finance_amount" x-model="financeAmount" required class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                                    </div>
                                    <div>
                                        <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Reference No</label>
                                        <input type="text" name="finance_reference_no" x-model="financeReferenceNo" placeholder="e.g. 105251011098847" class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                                    </div>
                                </div>

                                <div x-show="financeMethod === 'remittance'" x-transition>
                                    <label class="text-[12.5px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Remittance Source</label>
                                    <input type="text" name="remittance_source" x-model="remittanceSource" placeholder="e.g. AL GHURAIR EXCHANGE" :required="financeMethod === 'remittance'" class="w-full rounded-xl border border-slate-250 bg-white px-3.5 py-2 text-sm text-black focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
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
                                    <form id="modal-reject-form-finance" action="" method="POST" @submit="isSubmitting = true">
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
                    @endif
                </div>
            </div>
        </div>
