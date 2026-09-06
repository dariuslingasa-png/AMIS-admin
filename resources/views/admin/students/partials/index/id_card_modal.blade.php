<!-- Official Student ID Card Quick Pop-up Modal -->
<div id="student-id-card-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/65 p-3 sm:p-6 backdrop-blur-md transition-all duration-200 overflow-y-auto" onclick="if(event.target === this) closeStudentIdCardModal()">
    <div class="relative w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900 my-auto animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100/70 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-300/40">
                    <i data-lucide="contact-round" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 id="id-modal-title-name" class="text-sm sm:text-base font-black text-slate-900 dark:text-white tracking-tight uppercase">STUDENT ID CARD</h3>
                    <p class="text-xs font-semibold text-slate-500 flex items-center gap-1.5 mt-0.5">
                        <span id="id-modal-badge-number" class="font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded text-[11px] ring-1 ring-emerald-200/60">#260000</span>
                        <span>•</span>
                        <span id="id-modal-badge-grade" class="font-bold text-slate-700 dark:text-slate-300">Grade Level</span>
                        <span>•</span>
                        <span id="id-modal-badge-section" class="text-slate-500">Section</span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeStudentIdCardModal()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 transition cursor-pointer">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <!-- Body: Interactive Side-by-Side ID Preview -->
        <div class="px-6 py-6 overflow-x-auto flex justify-center bg-slate-100/50 dark:bg-slate-950/40">
            <div class="flex flex-col md:flex-row items-center justify-center gap-8 py-2">
                
                <!-- Front Side Card -->
                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Front Side</span>
                    <div style="transform: scale(0.85); transform-origin: top center; margin-bottom: -80px;">
                        <div id="index-id-front-box" class="relative overflow-hidden" style="width: 340px; height: 538px; background-color: #064e3b; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);">
                            <!-- Template Background -->
                            <img src="{{ asset('images/id/amis_frontid.png') }}?v=1" class="absolute inset-0 w-full h-full object-cover pointer-events-none" style="z-index: 10;" alt="AMIS Front Template">
                            
                            <!-- Student Photo -->
                            <div class="photo-clip" style="position: absolute; left: 71px; top: 144px; width: 198px; height: 192px; border-radius: 6px; z-index: 5; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center;">
                                <img id="id-modal-photo" src="" crossorigin="anonymous" style="width: 100%; height: 100%; object-fit: cover; object-position: center center;" alt="Student Photo">
                            </div>

                            <!-- Student ID -->
                            <div id="id-modal-num-text" class="absolute text-white font-black tracking-wide text-center uppercase" style="left: 0; top: 325px; width: 340px; height: 15px; z-index: 20; font-size: 12.5px; line-height: 15px;">260000</div>

                            <!-- Last Name -->
                            <div id="id-modal-last-name" class="absolute text-center font-black text-[#0f172a] uppercase tracking-tight flex flex-col justify-center items-center" style="left: 15px; top: 352px; width: 310px; height: 32px; z-index: 20; padding: 0 16px; line-height: 1; letter-spacing: -0.5px; font-size: 22px; font-family: 'Outfit', sans-serif;">LASTNAME</div>

                            <!-- First Name -->
                            <div id="id-modal-first-name" class="absolute text-center font-bold text-[#334155] uppercase leading-none flex flex-col justify-center items-center" style="left: 15px; top: 386px; width: 310px; height: 22px; z-index: 20; padding: 0 16px; line-height: 1; font-size: 16px; font-family: 'Outfit', sans-serif;">FIRSTNAME</div>

                            <!-- Grade Level -->
                            <div id="id-modal-grade-text" class="absolute text-center font-black uppercase tracking-wide flex flex-col justify-center items-center" style="left: 15px; top: 412px; width: 310px; height: 30px; z-index: 20; padding: 0 16px; line-height: 1; letter-spacing: 0.5px; text-shadow: 0 1px 1px rgba(0,0,0,0.05); color: #10b981; font-size: 31px; font-family: 'Outfit', sans-serif;">GRADE 1</div>

                            <!-- Vertical LRN -->
                            <div id="id-modal-lrn-container" class="absolute font-bold text-[#1e293b] whitespace-nowrap" style="left: 239px; top: 394px; width: 170px; height: 22px; z-index: 20; font-size: 15.5px; transform: rotate(-90deg); transform-origin: center; display: flex; align-items: center; justify-content: flex-start; letter-spacing: 0.05em; font-family: 'Outfit', sans-serif;">
                                LRN: <span id="id-modal-lrn-val" style="margin-left: 4px;">400000000000</span>
                            </div>

                            <!-- Front QR -->
                            <div class="absolute p-0.5 rounded bg-white" style="left: 134.5px; top: 458px; width: 71px; height: 71px; z-index: 20;">
                                <img id="id-modal-front-qr" src="" crossorigin="anonymous" alt="Front QR" class="w-full h-full object-contain">
                            </div>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold mt-1">Front ID Card (300 DPI)</span>
                </div>

                <!-- Back Side Card -->
                <div class="flex flex-col items-center gap-2">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Back Side</span>
                    <div style="transform: scale(0.85); transform-origin: top center; margin-bottom: -80px;">
                        <div id="index-id-back-box" class="relative overflow-hidden" style="width: 340px; height: 538px; background-color: #064e3b; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);">
                            <!-- Template Background -->
                            <img src="{{ asset('images/id/amis_backid.png') }}?v=1" class="absolute inset-0 w-full h-full object-cover pointer-events-none" style="z-index: 1;" alt="AMIS Back Template">

                            <!-- Emergency Details -->
                            <div class="emergency-info" style="position: absolute; left: 28px; top: 85px; width: 284px; z-index: 10; display: flex; flex-direction: column; gap: 7px;">
                                <!-- Contact Name -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div id="id-modal-emg-name" class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 900; text-transform: uppercase; color: #0f172a; line-height: 1.1;">
                                        PARENT / GUARDIAN
                                    </div>
                                </div>

                                <!-- Relationship -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                                    </span>
                                    <div id="id-modal-emg-rel" class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1;">
                                        MOTHER
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 1.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="M1.5 4.5a3 3 0 0 1 3-3h1.372c.86 0 1.61.586 1.819 1.42l.589 2.356a1.75 1.75 0 0 1-.607 1.89l-1.077.808a12.983 12.983 0 0 0 5.753 5.753l.808-1.077a1.75 1.75 0 0 1 1.89-.607l2.356.589c.834.209 1.42.959 1.42 1.82V19.5a3 3 0 0 1-3 3h-2.25C8.552 22.5 1.5 15.448 1.5 6.75V4.5Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div id="id-modal-emg-phone" class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: #1e293b; line-height: 1;">
                                        09000000000
                                    </div>
                                </div>

                                <!-- Address -->
                                <div class="emerg-row" style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="emerg-icon" style="flex-shrink: 0; width: 14px; height: 14px; color: #047857; margin-top: 2.5px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:100%; height:100%;"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 3.58-2.977c2.2-2.384 4.19-5.462 4.19-8.923 0-4.82-3.855-8.5-8.5-8.5-8.5 0-8.5 3.68-8.5 8.5c0 3.461 1.99 6.54 4.19 8.923a16.975 16.975 0 0 0 3.58 2.977Zm3.71-12.851a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" clip-rule="evenodd" /></svg>
                                    </span>
                                    <div id="id-modal-emg-address" class="emerg-text" style="text-align: left; font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 700; text-transform: uppercase; color: #475569; line-height: 1.25;">
                                        ADDRESS
                                    </div>
                                </div>
                            </div>

                            <!-- Signature QR -->
                            <div class="back-signature-qr" style="position: absolute; left: 142.5px; top: 422px; width: 55px; height: 55px; z-index: 25; padding: 1.5px; border-radius: 2px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                <img id="id-modal-back-qr" src="" crossorigin="anonymous" alt="Signature QR" class="w-full h-full object-contain">
                            </div>
                        </div>
                    </div>
                    <span class="text-[10px] text-slate-400 font-semibold mt-1">Back Emergency Info Sheet</span>
                </div>
            </div>
        </div>

        <input type="hidden" id="id-modal-current-slug" value="STUDENT-ID">

        <!-- Footer Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 gap-3">
            <p class="text-xs text-slate-400 font-medium hidden sm:block">Smart ID Printer PNG images (300 DPI high-res).</p>
            
            <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto justify-end">
                <!-- 1. Print Card Link -->
                <a id="id-modal-print-btn" href="#" target="_blank" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-2 text-xs font-bold shadow-md transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print Card</span>
                </a>

                <!-- 2. Download PNG Dropdown -->
                <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" @click="open = !open" class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-3.5 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Download PNG</span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 opacity-80"></i>
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top.right.duration.150ms class="absolute right-0 bottom-full mb-2 w-56 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-1.5 shadow-xl z-50">
                        <button type="button" @click="open = false; downloadIndexIdPng('front', false, false)" class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition cursor-pointer">
                            <i data-lucide="image" class="w-4 h-4 text-emerald-600"></i>
                            <span>Front PNG (Color)</span>
                        </button>
                        <button type="button" @click="open = false; downloadIndexIdPng('back', false, false)" class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:text-emerald-700 transition cursor-pointer">
                            <i data-lucide="image" class="w-4 h-4 text-emerald-600"></i>
                            <span>Back PNG (Color)</span>
                        </button>
                        <button type="button" @click="open = false; downloadIndexIdPng('back', true, false)" class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer">
                            <i data-lucide="file-badge-2" class="w-4 h-4 text-slate-900 dark:text-slate-100"></i>
                            <span>Back PNG (Black Only 🖤)</span>
                        </button>
                        <button type="button" @click="open = false; downloadIndexIdPng('front', false, false); setTimeout(() => downloadIndexIdPng('back', true, false), 500)" class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition cursor-pointer">
                            <i data-lucide="layers" class="w-4 h-4 text-emerald-600"></i>
                            <span>Front Color + Back Black</span>
                        </button>
                        <div class="my-1 border-t border-slate-100 dark:border-slate-800"></div>
                        <button type="button" @click="open = false; downloadIndexIdPng('front', false, false); setTimeout(() => downloadIndexIdPng('back', false, false), 500)" class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition cursor-pointer">
                            <i data-lucide="image" class="w-4 h-4 text-slate-500"></i>
                            <span>Both Sides Full Color</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Edit Layout Link -->
                <a id="id-modal-edit-btn" href="#" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3.5 py-2 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition active:scale-[0.98] cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="layout-template" class="w-4 h-4 text-emerald-600"></i>
                    <span>Edit ID Layout</span>
                </a>

                <!-- 4. Close Button -->
                <button type="button" onclick="closeStudentIdCardModal()" class="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 transition hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modern HTML-to-Image & HTML2Canvas CDN for Fast Browser PNG Rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
let currentStudentIdData = null;

function openStudentIdCardModal(data) {
    currentStudentIdData = data;
    const modal = document.getElementById('student-id-card-modal');
    if (!modal) return;

    // Header info
    document.getElementById('id-modal-title-name').textContent = data.full_name || 'STUDENT ID CARD';
    document.getElementById('id-modal-badge-number').textContent = '#' + (data.student_number || '-');
    document.getElementById('id-modal-badge-grade').textContent = data.grade || 'Grade Level';
    document.getElementById('id-modal-badge-section').textContent = data.section || 'Unassigned';

    // Front card fields
    const photoEl = document.getElementById('id-modal-photo');
    if (photoEl) {
        photoEl.src = data.photo_url || '';
        photoEl.style.display = data.photo_url ? 'block' : 'none';
    }
    document.getElementById('id-modal-num-text').textContent = data.student_number || '';
    
    // Last name styling
    const lastNameEl = document.getElementById('id-modal-last-name');
    const lastName = data.last_name || '';
    const lastNameLen = lastName.length;
    let lastNameSize = '22px';
    let lastNameStyle = 'white-space: nowrap;';
    if (lastNameLen <= 8) { lastNameSize = '36px'; }
    else if (lastNameLen <= 12) { lastNameSize = '28px'; }
    else if (lastNameLen <= 15) { lastNameSize = '22px'; }
    else if (lastNameLen <= 18) { lastNameSize = '17px'; }
    else if (lastNameLen <= 21) { lastNameSize = '14px'; }
    else if (lastNameLen <= 25) { lastNameSize = '12.5px'; }
    else { lastNameSize = '13.5px'; lastNameStyle = 'white-space: normal; line-height: 1.05; word-break: normal;'; }
    
    lastNameEl.textContent = lastName;
    lastNameEl.style.fontSize = lastNameSize;
    lastNameEl.style.whiteSpace = lastNameStyle.includes('nowrap') ? 'nowrap' : 'normal';

    // First name styling
    const firstNameEl = document.getElementById('id-modal-first-name');
    const displayFirst = [data.first_name, data.middle_initial, data.suffix].filter(Boolean).join(' ');
    const firstLen = displayFirst.length;
    firstNameEl.textContent = displayFirst;
    firstNameEl.style.fontSize = firstLen > 25 ? '14px' : (firstLen > 18 ? '16px' : '18px');

    // Grade level text
    const gradeEl = document.getElementById('id-modal-grade-text');
    gradeEl.textContent = (data.grade || 'GRADE 1').toUpperCase();
    gradeEl.style.color = data.grade_color || '#10b981';

    // LRN
    const lrnContainer = document.getElementById('id-modal-lrn-container');
    const lrnVal = document.getElementById('id-modal-lrn-val');
    if (data.lrn && !['N/A', 'NA', 'EMPTY', ''].includes(String(data.lrn).toUpperCase())) {
        lrnContainer.style.display = 'flex';
        lrnVal.textContent = data.lrn;
    } else {
        lrnContainer.style.display = 'none';
    }

    // QR Code
    document.getElementById('id-modal-front-qr').src = data.qr_url || '';

    // Back card fields
    document.getElementById('id-modal-emg-name').textContent = data.emergency_name || 'PARENT / GUARDIAN';
    document.getElementById('id-modal-emg-rel').textContent = data.emergency_relationship || 'MOTHER';

    const phoneEl = document.getElementById('id-modal-emg-phone');
    const phoneVal = data.emergency_phone || '-';
    phoneEl.textContent = phoneVal;
    phoneEl.style.fontSize = phoneVal.length > 22 ? '11.5px' : (phoneVal.length > 15 ? '13px' : '15px');
    
    const addrEl = document.getElementById('id-modal-emg-address');
    const addr = data.emergency_address || 'ADDRESS';
    addrEl.textContent = addr;
    addrEl.style.fontSize = addr.length > 60 ? '11.5px' : (addr.length > 40 ? '12.5px' : '13.5px');

    document.getElementById('id-modal-back-qr').src = data.signature_qr || '';

    // Links & Slug
    document.getElementById('id-modal-print-btn').href = data.print_url || '#';
    document.getElementById('id-modal-edit-btn').href = data.edit_layout_url || '#';
    document.getElementById('id-modal-current-slug').value = [data.last_name, data.first_name, data.suffix, (data.grade || '').replace(/\s+/g, '')].filter(Boolean).join('-');

    modal.classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
}

function closeStudentIdCardModal() {
    const modal = document.getElementById('student-id-card-modal');
    if (modal) modal.classList.add('hidden');
}

async function downloadIndexIdPng(side, isMonochrome = false, isWatermark = false) {
    const boxId = side === 'front' ? 'index-id-front-box' : 'index-id-back-box';
    const cardEl = document.getElementById(boxId);
    if (!cardEl) return;

    const rawSlug = document.getElementById('id-modal-current-slug')?.value || 'STUDENT-ID';
    const filename = `${rawSlug}-${side.toUpperCase()}${isMonochrome ? '-BLACK-ONLY' : ''}${isWatermark ? '-SAMPLE-WATERMARK' : ''}.png`;

    try {
        let dataUrl = '';
        
        // Primary: htmlToImage (supports modern CSS color spaces without throwing oklch error)
        if (typeof htmlToImage !== 'undefined') {
            try {
                dataUrl = await htmlToImage.toPng(cardEl, {
                    pixelRatio: 3,
                    cacheBust: true,
                    backgroundColor: isMonochrome ? '#ffffff' : (side === 'front' ? '#064e3b' : '#ffffff')
                });
            } catch (err) {
                console.warn('htmlToImage fallback:', err);
            }
        }

        // Secondary: html2canvas
        if (!dataUrl && typeof html2canvas !== 'undefined') {
            const canvas = await html2canvas(cardEl, {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                backgroundColor: isMonochrome ? '#ffffff' : (side === 'front' ? '#064e3b' : '#ffffff'),
                logging: false
            });
            dataUrl = canvas.toDataURL('image/png', 1.0);
        }

        if (dataUrl) {
            if (isMonochrome) {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.src = dataUrl;
                await new Promise(res => img.onload = res);
                
                const cvs = document.createElement('canvas');
                cvs.width = img.width;
                cvs.height = img.height;
                const ctx = cvs.getContext('2d');
                ctx.drawImage(img, 0, 0);
                
                const imgData = ctx.getImageData(0, 0, cvs.width, cvs.height);
                const d = imgData.data;
                for (let i = 0; i < d.length; i += 4) {
                    const gray = 0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2];
                    const val = gray > 215 ? 255 : (gray < 165 ? 0 : Math.round(gray));
                    d[i] = val;
                    d[i+1] = val;
                    d[i+2] = val;
                }
                ctx.putImageData(imgData, 0, 0);
                dataUrl = cvs.toDataURL('image/png', 1.0);
            }

            const link = document.createElement('a');
            link.download = filename;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    } catch (e) {
        console.error('Download error:', e);
        alert('PNG Generation Error: ' + (e.message || e));
    }
}
</script>
