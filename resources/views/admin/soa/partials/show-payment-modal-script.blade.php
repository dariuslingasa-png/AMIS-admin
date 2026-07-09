    <!-- JavaScript to toggle Modal with premium fade/scale animations -->
    <script>
        let currentPaymentBreakdownTemplate = null;
        let currentPaymentBreakdownMonth = null;

        function openPaymentModal() {
            const modal = document.getElementById('paymentModal');
            const card = document.getElementById('modalCard');
            
            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100');
            
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
            
            // Re-render Lucide icons dynamically in modal
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function closePaymentModal() {
            const modal = document.getElementById('paymentModal');
            const card = document.getElementById('modalCard');
            
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0', 'pointer-events-none');
        }

        function openPaymentBreakdownModal(templateId, monthName) {
            const template = document.getElementById(templateId);
            const modal = document.getElementById('paymentBreakdownModal');
            const card = document.getElementById('paymentBreakdownModalCard');
            const body = document.getElementById('paymentBreakdownBody');
            const title = document.getElementById('paymentBreakdownTitle');
            const back = document.getElementById('paymentBreakdownBack');

            if (!template || !modal || !card || !body || !title) {
                return;
            }

            currentPaymentBreakdownTemplate = templateId;
            currentPaymentBreakdownMonth = monthName;
            body.innerHTML = template.innerHTML;
            title.replaceChildren();
            title.insertAdjacentHTML('beforeend', '<i data-lucide="receipt-text" class="h-6 w-6 text-emerald-600"></i>');
            title.appendChild(document.createTextNode(`${monthName} Payment Details`));
            back?.classList.add('hidden');
            back?.classList.remove('flex');

            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100');

            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function openPaymentProofInBreakdown(proofUrl) {
            const modal = document.getElementById('paymentBreakdownModal');
            const card = document.getElementById('paymentBreakdownModalCard');
            const body = document.getElementById('paymentBreakdownBody');
            const title = document.getElementById('paymentBreakdownTitle');
            const back = document.getElementById('paymentBreakdownBack');

            if (!modal || !card || !body || !title) {
                return;
            }

            title.replaceChildren();
            title.insertAdjacentHTML('beforeend', '<i data-lucide="image" class="h-6 w-6 text-emerald-600"></i>');
            title.appendChild(document.createTextNode('Payment Proof'));

            const wrapper = document.createElement('div');
            wrapper.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-4';

            const image = document.createElement('img');
            image.src = proofUrl;
            image.alt = 'Payment proof';
            image.className = 'mx-auto max-h-[68vh] max-w-full rounded-xl border border-slate-200 bg-white object-contain shadow-sm';

            wrapper.appendChild(image);
            body.replaceChildren(wrapper);
            if (currentPaymentBreakdownTemplate) {
                back?.classList.remove('hidden');
                back?.classList.add('flex');
            } else {
                back?.classList.add('hidden');
                back?.classList.remove('flex');
            }

            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100');

            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function restorePaymentBreakdownList() {
            if (currentPaymentBreakdownTemplate && currentPaymentBreakdownMonth) {
                openPaymentBreakdownModal(currentPaymentBreakdownTemplate, currentPaymentBreakdownMonth);
            }
        }

        function closePaymentBreakdownModal() {
            const modal = document.getElementById('paymentBreakdownModal');
            const card = document.getElementById('paymentBreakdownModalCard');
            const body = document.getElementById('paymentBreakdownBody');
            const back = document.getElementById('paymentBreakdownBack');

            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');

            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0', 'pointer-events-none');

            if (body) {
                body.innerHTML = '';
            }
            back?.classList.add('hidden');
            back?.classList.remove('flex');
            currentPaymentBreakdownTemplate = null;
            currentPaymentBreakdownMonth = null;
        }

        function openPaymentVerifyWorkspace(payment, receiptUrl, forceApprove = false, forceReject = false) {
            const modal = document.getElementById('paymentBreakdownModal');
            const card = document.getElementById('paymentBreakdownModalCard');
            const body = document.getElementById('paymentBreakdownBody');
            const title = document.getElementById('paymentBreakdownTitle');
            const back = document.getElementById('paymentBreakdownBack');

            if (!modal || !card || !body || !title) {
                return;
            }

            title.replaceChildren();
            title.insertAdjacentHTML('beforeend', '<i data-lucide="shield-check" class="h-6 w-6 text-emerald-600"></i>');
            title.appendChild(document.createTextNode('Payment Verification Workspace'));

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const verifyUrl = `/soa-payments/${payment.id}/verify`;
            const rejectUrl = `/soa-payments/${payment.id}/reject`;

            // OCR Pre-Scan status block
            let ocrHtml = '';
            if (payment.ocr_status) {
                const ocrLabels = {
                    'matched': { text: 'Matched & Verified', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
                    'partial_match': { text: 'Partial Match', class: 'bg-amber-100 text-amber-800 border-amber-200' },
                    'mismatch': { text: 'Mismatch / Alert', class: 'bg-rose-100 text-rose-800 border-rose-200' }
                };
                const ocr = ocrLabels[payment.ocr_status] || { text: 'Not Scanned', class: 'bg-slate-100 text-slate-700' };
                
                // Compare values
                const isAmountMatch = parseFloat(payment.amount) === parseFloat(payment.ocr_scanned_amount);
                const isRefMatch = payment.reference_no && payment.ocr_scanned_ref && 
                    payment.reference_no.toString().trim() === payment.ocr_scanned_ref.toString().trim();

                ocrHtml = `
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">AI Pre-Scan Report</span>
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${ocr.class}">${ocr.text}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 text-xs">
                            <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-slate-150">
                                <span class="font-bold text-slate-500">Amount Match:</span>
                                <span class="font-bold ${isAmountMatch ? 'text-emerald-700' : 'text-amber-700'} flex items-center gap-1">
                                    ₱${Number(payment.amount).toLocaleString(undefined, {minimumFractionDigits:2})} vs ₱${payment.ocr_scanned_amount ? Number(payment.ocr_scanned_amount).toLocaleString(undefined, {minimumFractionDigits:2}) : 'Not detected'}
                                    <i data-lucide="${isAmountMatch ? 'check-circle' : 'alert-triangle'}" class="h-3.5 w-3.5"></i>
                                </span>
                            </div>
                            <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-slate-150">
                                <span class="font-bold text-slate-500">Reference No:</span>
                                <span class="font-bold ${isRefMatch ? 'text-emerald-700' : 'text-amber-700'} flex items-center gap-1">
                                    ${payment.reference_no || '-'} vs ${payment.ocr_scanned_ref || 'Not detected'}
                                    <i data-lucide="${isRefMatch ? 'check-circle' : 'alert-triangle'}" class="h-3.5 w-3.5"></i>
                                </span>
                            </div>
                        </div>
                        ${payment.ocr_raw_text ? `
                            <details class="text-[11px] text-slate-500 font-semibold cursor-pointer">
                                <summary class="hover:text-slate-700">Show Extracted Raw OCR Text</summary>
                                <pre class="mt-2 bg-slate-900 text-slate-100 p-3 rounded-lg overflow-x-auto whitespace-pre-wrap max-h-32 text-[10px] font-mono select-text">${payment.ocr_raw_text}</pre>
                            </details>
                        ` : ''}
                    </div>
                `;
            }

            // Right column content based on status
            let rightSideHtml = '';
            const paymentStatus = payment.status ? payment.status.toLowerCase() : 'pending';

            if (paymentStatus === 'pending') {
                rightSideHtml = `
                    <div class="space-y-4">
                        ${ocrHtml}
                        
                        <!-- Verify Form -->
                        <form action="${verifyUrl}" method="POST" id="verify-tuition-form" class="space-y-3.5">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <input type="hidden" name="_method" value="PATCH">
                            
                            <div class="border-b border-slate-100 pb-2 mb-1">
                                <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider">Verify & Record Ledger Entry</span>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Payment Method</label>
                                    <select name="method" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-950 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                        <option value="cash" ${payment.method === 'cash' ? 'selected' : ''}>Cash</option>
                                        <option value="gcash" ${payment.method === 'gcash' ? 'selected' : ''}>GCash</option>
                                        <option value="maya" ${payment.method === 'maya' ? 'selected' : ''}>Maya</option>
                                        <option value="bdo" ${payment.method === 'bdo' ? 'selected' : ''}>BDO Bank</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Amount</label>
                                    <input type="number" step="0.01" name="amount" value="${payment.amount}" required class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-950 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Reference No.</label>
                                    <input type="text" name="reference_no" value="${payment.reference_no || ''}" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-950 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                </div>
                                <div>
                                    <label class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1">Official Receipt (OR)</label>
                                    <input type="text" name="or_number" required placeholder="e.g. 70105712" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-950 focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                </div>
                            </div>

                            <div class="flex gap-2 pt-3 border-t border-slate-100">
                                <button type="submit" class="flex-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider py-2 text-xs cursor-pointer flex items-center justify-center gap-1.5">
                                    Confirm Verify
                                </button>
                                <button type="button" onclick="toggleTuitionRejectSection(true)" class="rounded-xl border border-rose-200 hover:bg-rose-50 text-rose-700 font-black uppercase tracking-wider px-4 py-2 text-xs cursor-pointer">
                                    Reject
                                </button>
                            </div>
                        </form>

                        <!-- Reject Section (Hidden by default) -->
                        <div id="tuition-reject-section" class="hidden rounded-xl border border-rose-100 bg-rose-50/50 p-4 space-y-3">
                            <form action="${rejectUrl}" method="POST" class="space-y-3">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="_method" value="PATCH">
                                
                                <span class="text-[11px] font-black text-rose-800 uppercase tracking-wider block">Rejection Details</span>
                                
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" onclick="setTuitionRejectRemarks('Malabo / Unreadable Payment Proof')" class="px-2.5 py-1 rounded-full bg-white border border-rose-200 text-[10px] font-bold text-rose-700 transition cursor-pointer hover:bg-rose-100">Malabo Proof</button>
                                    <button type="button" onclick="setTuitionRejectRemarks('Wrong Reference Number')" class="px-2.5 py-1 rounded-full bg-white border border-rose-200 text-[10px] font-bold text-rose-700 transition cursor-pointer hover:bg-rose-100">Wrong Ref</button>
                                    <button type="button" onclick="setTuitionRejectRemarks('Incorrect Amount Paid')" class="px-2.5 py-1 rounded-full bg-white border border-rose-200 text-[10px] font-bold text-rose-700 transition cursor-pointer hover:bg-rose-100">Incorrect Amt</button>
                                </div>

                                <textarea name="remarks" id="tuition-reject-remarks" required placeholder="Reason for rejection..." class="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-black focus:outline-none focus:ring-2 focus:ring-rose-500" rows="2"></textarea>
                                
                                <div class="flex gap-2">
                                    <button type="button" onclick="toggleTuitionRejectSection(false)" class="flex-1 rounded-xl border border-slate-200 bg-white py-1.5 text-xs font-bold text-slate-700 cursor-pointer">Cancel</button>
                                    <button type="submit" class="flex-1 rounded-xl bg-rose-600 py-1.5 text-xs font-black uppercase tracking-wider text-white hover:bg-rose-700 cursor-pointer">Submit Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
            } else if (paymentStatus === 'verified') {
                rightSideHtml = `
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5 space-y-4">
                        <div class="flex items-center gap-2 text-emerald-800">
                            <i data-lucide="check-circle" class="h-6 w-6"></i>
                            <span class="text-base font-black uppercase tracking-wider">Payment Approved</span>
                        </div>
                        <div class="space-y-2 text-xs font-bold text-slate-800">
                            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                                <span class="text-slate-500">OR Generated:</span>
                                <span class="font-black text-slate-950">${payment.or_number || '-'}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                                <span class="text-slate-500">Amount Verified:</span>
                                <span class="font-black text-slate-950">PHP ${Number(payment.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                                <span class="text-slate-500">Method:</span>
                                <span class="text-slate-950 uppercase">${payment.method || '-'}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                                <span class="text-slate-500">Reference No:</span>
                                <span class="text-slate-950">${payment.reference_no || '-'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Verified Date:</span>
                                <span class="text-slate-950">${payment.verified_at ? new Date(payment.verified_at).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}) : '-'}</span>
                            </div>
                        </div>
                        <div class="pt-2">
                            <a href="/finance/receipt/${payment.id}" target="_blank" class="w-full flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-wider py-2 text-xs">
                                <i data-lucide="printer" class="h-4 w-4"></i> Print Official Receipt
                            </a>
                        </div>
                    </div>
                `;
            } else if (paymentStatus === 'rejected') {
                rightSideHtml = `
                    <div class="rounded-xl border border-rose-200 bg-rose-50/50 p-5 space-y-4">
                        <div class="flex items-center gap-2 text-rose-800">
                            <i data-lucide="x-circle" class="h-6 w-6"></i>
                            <span class="text-base font-black uppercase tracking-wider">Payment Rejected</span>
                        </div>
                        <div class="space-y-2 text-xs font-bold text-slate-800">
                            <div class="flex justify-between border-b border-slate-200/60 pb-1.5">
                                <span class="text-slate-500">Rejection Date:</span>
                                <span class="text-slate-950">${payment.updated_at ? new Date(payment.updated_at).toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}) : '-'}</span>
                            </div>
                            <div class="border-b border-slate-200/60 pb-2">
                                <span class="text-slate-500 block mb-1">Remarks:</span>
                                <p class="text-rose-900 bg-white p-2.5 border border-rose-100 rounded-lg select-text font-semibold italic">${payment.remarks || 'No remarks provided.'}</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            body.innerHTML = `
                <div class="flex flex-col lg:flex-row gap-6 min-h-[50vh] max-h-[75vh]">
                    <!-- Left: Proof image -->
                    <div class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-center overflow-auto min-h-[350px]">
                        <img src="${receiptUrl}" class="max-h-[60vh] max-w-full rounded-xl object-contain shadow-sm bg-white">
                    </div>
                    <!-- Right: Forms workspace -->
                    <div class="w-full lg:w-[420px] flex flex-col justify-between pr-1 overflow-y-auto">
                        ${rightSideHtml}
                    </div>
                </div>
            `;

            back?.classList.add('hidden');
            back?.classList.remove('flex');

            modal.classList.remove('opacity-0', 'pointer-events-none');
            modal.classList.add('opacity-100');

            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            if (forceReject) {
                toggleTuitionRejectSection(true);
            }
        }

        function toggleTuitionRejectSection(show) {
            const el = document.getElementById('tuition-reject-section');
            if (el) {
                if (show) {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            }
        }

        function setTuitionRejectRemarks(text) {
            const textarea = document.getElementById('tuition-reject-remarks');
            if (textarea) {
                textarea.value = text;
            }
        }

        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', () => {
                openPaymentModal();
            });
        @endif
    </script>
