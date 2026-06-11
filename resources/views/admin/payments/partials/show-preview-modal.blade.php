        <div x-show="preview" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="relative max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h2 class="font-black text-slate-950" x-text="label"></h2>
                    <div class="ml-auto flex items-center gap-2">
                        <div class="flex items-center gap-2" x-show="!pdf">
                            <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomOut()">-</button>
                            <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(zoom * 100) + '%'"></span>
                            <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomIn()">+</button>
                            <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100" @click="resetZoom()">Reset</button>
                        </div>
                        <button id="download-pdf-btn" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadPdf()">
                            <i data-lucide="download" class="h-3.5 w-3.5"></i> Download PDF
                        </button>
                        <button type="button" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200" @click="closePreview()">
                            <i data-lucide="x" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>
                <div class="max-h-[70vh] cursor-grab select-none overflow-auto bg-slate-50 p-4"
                     @mousedown="startPan($event)"
                     @mousemove="movePan($event)"
                     @mouseleave="stopPan()"
                     @touchstart.passive="startPan($event)"
                     @touchmove="movePan($event)">
                    <template x-if="pdf">
                        <iframe :src="src" class="h-[65vh] w-full rounded-2xl bg-white"></iframe>
                    </template>
                    <template x-if="!pdf">
                        <img :src="src" :alt="label" class="mx-auto rounded-2xl object-contain transition-all duration-150" :style="'max-width: none; width: ' + (zoom * 100) + '%; height: auto;'">
                    </template>
                </div>

                @if ($canReviewPayments)
                    <!-- Modal Verification Footer in Finance -->
                    <div x-show="currentPayment" class="bg-amber-50 border-t border-amber-200 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-left select-text">
                        <div class="text-[13.5px] text-amber-900 font-bold uppercase tracking-wider">
                            Verify Payment Proof
                            <p class="text-[11.5px] text-amber-700 font-semibold normal-case mt-0.5">Please check the receipt amount and reference number before verifying.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="approveModal = true; document.getElementById('approve-form').action = document.getElementById('modal-approve-form-finance').action;" class="btn-premium btn-approve cursor-pointer">
                                Verify Payment
                            </button>
                            <form id="modal-approve-form-finance" action="" method="POST" class="hidden">
                                @csrf
                                @method('PATCH')
                            </form>
                            <button type="button" onclick="document.getElementById('modal-reject-form-finance-container').classList.toggle('hidden')" class="btn-premium btn-reject cursor-pointer">
                                Reject
                            </button>
                        </div>
                    </div>
                    
                    <!-- Modal Reject Form in Finance -->
                    <div id="modal-reject-form-finance-container" x-show="currentPayment" class="hidden bg-rose-50 border-t border-rose-250 p-4 text-left select-text">
                        <form id="modal-reject-form-finance" action="" method="POST" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-rose-800 mb-1">Rejection Remarks</label>
                                <textarea name="remarks" x-model="remarks" required placeholder="Reason for rejection..." class="w-full rounded-xl border border-slate-250 bg-white px-4 py-3 text-sm text-black focus:outline-none" rows="2"></textarea>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="button" onclick="document.getElementById('modal-reject-form-finance-container').classList.add('hidden')" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">Cancel</button>
                                <button type="submit" class="rounded-xl bg-rose-600 px-4 py-1.5 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700">Submit Rejection</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
