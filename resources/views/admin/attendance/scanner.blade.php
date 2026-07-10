<x-admin-layout
    title="Live QR Attendance Scanner"
    :breadcrumbs="[
        ['label' => 'Attendance', 'href' => route('admin.attendance.index')],
        ['label' => 'Live Scanner', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Panel: Scanner Form & Live Feedback -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Scanner Input Card -->
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Gate Attendance QR Scanner</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Please scan student ID barcode/QR code or type Student Number.</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-[10px] font-bold text-slate-500 flex items-center gap-1.5">
                            <i data-lucide="keyboard" class="w-3.5 h-3.5"></i>
                            Input Autofocused
                        </span>
                    </div>

                    <!-- Scan Input Form -->
                    <form id="scanner-form" class="space-y-4">
                        @csrf
                        <div class="relative">
                            <input 
                                type="text" 
                                id="student_number_input" 
                                name="student_number" 
                                placeholder="Waiting for scanner input..." 
                                autocomplete="off"
                                autofocus
                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50/50 py-4 pl-12 pr-4 text-sm font-black text-slate-900 placeholder-slate-400 outline-none focus:border-cyan-500 focus:bg-white transition uppercase tracking-wider text-center"
                            >
                            <span class="absolute inset-y-0 left-4 flex items-center text-slate-400 pointer-events-none">
                                <i data-lucide="qr-code" class="h-6 w-6"></i>
                            </span>
                        </div>
                    </form>
                </section>

                <!-- Feedback Display Panel (Glassmorphism & Alerts) -->
                <div id="feedback-panel" class="hidden rounded-3xl p-8 border text-center transition-all duration-300 relative overflow-hidden min-h-[220px] flex flex-col justify-center items-center">
                    <div id="feedback-bg-glow" class="absolute inset-0 opacity-10 blur-3xl pointer-events-none"></div>
                    
                    <div id="feedback-icon-container" class="h-16 w-16 rounded-full flex items-center justify-center shrink-0 mb-4 transition duration-300 shadow-md">
                        <!-- Icon will be inserted here dynamically -->
                    </div>
                    
                    <h2 id="feedback-status" class="text-xs font-black uppercase tracking-widest mb-1"></h2>
                    <h1 id="feedback-name" class="text-2xl font-black text-slate-950 uppercase tracking-wide leading-tight mb-1"></h1>
                    <p id="feedback-id" class="text-sm font-mono font-bold text-slate-600 mb-2"></p>
                    <p id="feedback-meta" class="text-sm font-bold uppercase text-slate-700"></p>
                    <p id="feedback-time" class="text-xs font-bold text-slate-400 mt-1"></p>
                </div>
            </div>

            <!-- Right Panel: Today's Live Scan History -->
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="history" class="w-3.5 h-3.5"></i>
                            Recent Scans Today
                        </h3>
                        <span class="rounded-full bg-cyan-50 text-cyan-700 text-[10px] font-bold px-2 py-0.5">Live Feed</span>
                    </div>

                    <div class="flow-root">
                        <ul id="scans-list" role="list" class="-my-5 divide-y divide-slate-100">
                            @forelse($recentScans as $scan)
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full {{ $scan->time_out ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100' }} font-bold text-xs uppercase">
                                                {{ $scan->time_out ? 'OUT' : 'IN' }}
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-extrabold text-slate-900 truncate uppercase">
                                                {{ $scan->last_name }}, {{ $scan->first_name }}
                                            </p>
                                            <p class="text-[10px] text-slate-500 font-mono font-semibold uppercase truncate">
                                                {{ $scan->grade_level }} {{ $scan->section_name ? '- ' . $scan->section_name : '' }}
                                            </p>
                                        </div>
                                        <div class="inline-flex items-center text-xs font-extrabold text-slate-800">
                                            {{ $scan->time_out ? date('h:i A', strtotime($scan->time_out)) : date('h:i A', strtotime($scan->time_in)) }}
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li id="empty-history-item" class="py-8 text-center text-xs font-bold text-slate-400">
                                    Waiting for first scan...
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <!-- Web Audio & Scanner logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputField = document.getElementById('student_number_input');
            const form = document.getElementById('scanner-form');
            const feedbackPanel = document.getElementById('feedback-panel');
            const feedbackBgGlow = document.getElementById('feedback-bg-glow');
            const feedbackIconContainer = document.getElementById('feedback-icon-container');
            const feedbackStatus = document.getElementById('feedback-status');
            const feedbackName = document.getElementById('feedback-name');
            const feedbackId = document.getElementById('feedback-id');
            const feedbackMeta = document.getElementById('feedback-meta');
            const feedbackTime = document.getElementById('feedback-time');
            const scansList = document.getElementById('scans-list');
            const emptyHistoryItem = document.getElementById('empty-history-item');

            // Refocus input field on blur or click anywhere to make sure scanner is always captured
            function keepAutofocus() {
                inputField.focus();
            }
            
            keepAutofocus();
            document.addEventListener('click', keepAutofocus);
            setInterval(keepAutofocus, 2000);

            // Beep Sound Synthesizer via Web Audio API (to avoid loading static sound assets)
            function playBeep(type = 'success') {
                try {
                    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();

                    oscillator.connect(gainNode);
                    gainNode.connect(audioCtx.destination);

                    if (type === 'success') {
                        oscillator.frequency.value = 880; // High A
                        gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                        oscillator.start();
                        oscillator.stop(audioCtx.currentTime + 0.1);
                    } else if (type === 'checkout') {
                        // Double beep
                        oscillator.frequency.value = 660; 
                        gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime);
                        oscillator.start();
                        oscillator.stop(audioCtx.currentTime + 0.08);
                        
                        setTimeout(() => {
                            const osc2 = audioCtx.createOscillator();
                            const gain2 = audioCtx.createGain();
                            osc2.connect(gain2);
                            gain2.connect(audioCtx.destination);
                            osc2.frequency.value = 880;
                            gain2.gain.setValueAtTime(0.15, audioCtx.currentTime);
                            osc2.start();
                            osc2.stop(audioCtx.currentTime + 0.1);
                        }, 120);
                    } else {
                        oscillator.frequency.value = 220; // Low error buzz
                        gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime);
                        oscillator.start();
                        oscillator.stop(audioCtx.currentTime + 0.35);
                    }
                } catch (e) {
                    console.warn("Web Audio API not supported or blocked by user gesture:", e);
                }
            }

            // Speak Voice synthesizers via Web Speech API
            function announceName(name, action) {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel(); // Cancel any ongoing speech
                    const cleanName = name.toLowerCase();
                    const actionStr = action === 'check_in' ? 'checked in' : 'checked out';
                    const utterance = new SpeechSynthesisUtterance(`${cleanName} ${actionStr}`);
                    utterance.rate = 0.95;
                    utterance.pitch = 1.0;
                    window.speechSynthesis.speak(utterance);
                }
            }

            // Handle scan submit
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const studentNumber = inputField.value.trim();
                if (!studentNumber) return;

                // Clear input immediately to allow next scan
                inputField.value = '';

                // Call AJAX Scanner API
                fetch("{{ route('admin.attendance.scan') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ student_number: studentNumber })
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(res => {
                    const data = res.body;
                    
                    feedbackPanel.classList.remove('hidden');
                    
                    if (res.status === 200 && data.success) {
                        // Success Feedback
                        const isCheckIn = data.type === 'check_in';
                        
                        playBeep(isCheckIn ? 'success' : 'checkout');
                        announceName(data.student_name, data.type);

                        // Update Feedback Panel Theme
                        feedbackPanel.className = isCheckIn 
                            ? "rounded-3xl p-8 border border-emerald-200 bg-emerald-50/20 text-center transition-all duration-300 relative overflow-hidden min-h-[220px] flex flex-col justify-center items-center"
                            : "rounded-3xl p-8 border border-blue-200 bg-blue-50/20 text-center transition-all duration-300 relative overflow-hidden min-h-[220px] flex flex-col justify-center items-center";

                        feedbackBgGlow.className = isCheckIn
                            ? "absolute inset-0 bg-emerald-500 opacity-10 blur-3xl pointer-events-none"
                            : "absolute inset-0 bg-blue-500 opacity-10 blur-3xl pointer-events-none";

                        feedbackIconContainer.className = isCheckIn
                            ? "h-16 w-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mb-4 shadow-xs"
                            : "h-16 w-16 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mb-4 shadow-xs";

                        feedbackIconContainer.innerHTML = isCheckIn
                            ? '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>'
                            : '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>';

                        feedbackStatus.className = isCheckIn 
                            ? "text-[10px] font-black uppercase tracking-widest text-emerald-750" 
                            : "text-[10px] font-black uppercase tracking-widest text-blue-750";
                        feedbackStatus.textContent = isCheckIn ? "CHECK-IN SUCCESS" : "CHECK-OUT SUCCESS";

                        feedbackName.textContent = data.student_name;
                        feedbackId.textContent = studentNumber;
                        feedbackMeta.textContent = data.grade_section;
                        feedbackTime.innerHTML = isCheckIn 
                            ? `ARRIVED AT <strong class="text-slate-900">${data.time}</strong> (${data.status})`
                            : `DEPARTED AT <strong class="text-slate-900">${data.time}</strong>`;

                        // Add item to recent scans list UI
                        if (emptyHistoryItem) {
                            emptyHistoryItem.remove();
                        }

                        // Remove existing matching scan row to avoid duplication
                        const existingRows = Array.from(scansList.querySelectorAll('li'));
                        existingRows.forEach(row => {
                            if (row.querySelector('p.text-xs').textContent.trim().toUpperCase() === data.student_name.toUpperCase()) {
                                row.remove();
                            }
                        });

                        const li = document.createElement('li');
                        li.className = 'py-4';
                        li.innerHTML = `
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full ${isCheckIn ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-blue-50 text-blue-700 border border-blue-100'} font-bold text-xs uppercase">
                                        ${isCheckIn ? 'IN' : 'OUT'}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-extrabold text-slate-900 truncate uppercase">
                                        ${data.student_name}
                                    </p>
                                    <p class="text-[10px] text-slate-500 font-mono font-semibold uppercase truncate">
                                        ${data.grade_section}
                                    </p>
                                </div>
                                <div class="inline-flex items-center text-xs font-extrabold text-slate-800">
                                    ${data.time}
                                </div>
                            </div>
                        `;
                        scansList.insertBefore(li, scansList.firstChild);

                        // Cap recent scans at 10 items
                        const updatedRows = scansList.querySelectorAll('li');
                        if (updatedRows.length > 10) {
                            updatedRows[updatedRows.length - 1].remove();
                        }
                    } else {
                        // Error/Fail Feedback
                        playBeep('error');

                        feedbackPanel.className = "rounded-3xl p-8 border border-rose-250 bg-rose-50/20 text-center transition-all duration-300 relative overflow-hidden min-h-[220px] flex flex-col justify-center items-center";
                        feedbackBgGlow.className = "absolute inset-0 bg-rose-500 opacity-10 blur-3xl pointer-events-none";
                        feedbackIconContainer.className = "h-16 w-16 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mb-4 shadow-xs";
                        feedbackIconContainer.innerHTML = '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';
                        
                        feedbackStatus.className = "text-[10px] font-black uppercase tracking-widest text-rose-750";
                        feedbackStatus.textContent = "SCAN ERROR";
                        
                        feedbackName.textContent = data.message || "Failed to process scan.";
                        feedbackId.textContent = studentNumber;
                        feedbackMeta.textContent = "INVALID ID";
                        feedbackTime.textContent = "";
                    }
                })
                .catch(err => {
                    playBeep('error');
                    feedbackPanel.classList.remove('hidden');
                    feedbackPanel.className = "rounded-3xl p-8 border border-rose-250 bg-rose-50/20 text-center transition-all duration-300 relative overflow-hidden min-h-[220px] flex flex-col justify-center items-center";
                    feedbackBgGlow.className = "absolute inset-0 bg-rose-500 opacity-10 blur-3xl pointer-events-none";
                    feedbackIconContainer.className = "h-16 w-16 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 mb-4 shadow-xs";
                    feedbackIconContainer.innerHTML = '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>';
                    
                    feedbackStatus.className = "text-[10px] font-black uppercase tracking-widest text-rose-750";
                    feedbackStatus.textContent = "NETWORK ERROR";
                    feedbackName.textContent = "Unable to connect to the gate gateway server.";
                    feedbackId.textContent = studentNumber;
                    feedbackMeta.textContent = "";
                    feedbackTime.textContent = "";
                });
            });
        });
    </script>
</x-admin-layout>
