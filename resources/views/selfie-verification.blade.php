<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Selfie Identity Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    <!-- Top Header Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-40 px-4 py-3.5">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-lg shadow-emerald-900/40">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-white leading-none">AMIS Verification Portal</h1>
                    <p class="text-[11px] font-semibold text-emerald-400 mt-0.5 uppercase tracking-wider">Selfie Identity Verification</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-950/80 text-emerald-300 border border-emerald-800/80 text-xs font-bold shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>System Active</span>
            </span>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto w-full p-4 my-auto py-8" x-data="selfieApp()">
        
        <!-- Step 1: Fill Up Student Info -->
        <div x-show="step === 1" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" class="bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 md:p-8 shadow-2xl backdrop-blur-md max-w-xl mx-auto">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-3">
                    <i data-lucide="user-check" class="w-7 h-7"></i>
                </div>
                <h2 class="text-2xl font-black text-white">Student Verification</h2>
                <p class="text-xs text-slate-400 font-medium mt-1">Please enter the student's full name and grade level to begin selfie verification.</p>
            </div>

            <form @submit.prevent="submitStudentInfo()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Full Name</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3 text-slate-500">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <input type="text" x-model="fullName" required placeholder="e.g. NORHADIYAH CASAN BAULO" class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-sm font-semibold text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 uppercase transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Grade Level</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-3 text-slate-500">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        </span>
                        <select x-model="gradeLevel" required class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-4 py-2.5 text-sm font-semibold text-white focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-all">
                            <option value="" disabled>Select Grade Level</option>
                            <option value="Kinder 1">Kinder 1</option>
                            <option value="Kinder 2">Kinder 2</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading" class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-emerald-950/50 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                        <span x-show="!loading" class="flex items-center gap-2">
                            <span>Proceed to Selfie Verification</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            <span>Initializing Session...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2: PC QR Code & Phone Connection Screen -->
        <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-300 transform" class="bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 md:p-8 shadow-2xl backdrop-blur-md max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-bold mb-2">
                    <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                    <span>Scan with Cellphone</span>
                </span>
                <h2 class="text-2xl font-black text-white">Connect Mobile Device</h2>
                <p class="text-xs text-slate-400 font-medium mt-1">If you are on PC, scan the QR code below with your phone camera to take the selfie live.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center bg-slate-900/60 p-5 rounded-2xl border border-slate-700/60">
                <!-- QR Code Box -->
                <div class="flex flex-col items-center justify-center p-4 bg-white rounded-2xl shadow-xl">
                    <img :src="qrCodeUrl" alt="Scan QR Code" class="w-48 h-48 object-contain">
                    <span class="text-[10px] font-bold text-slate-800 uppercase tracking-widest mt-2 flex items-center gap-1">
                        <i data-lucide="smartphone" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Scan Phone Camera</span>
                    </span>
                </div>

                <!-- Instructions & Polling Status -->
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold shrink-0">1</div>
                        <div>
                            <h4 class="text-xs font-bold text-white">Open Phone Camera</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Point your mobile phone camera at the QR code.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold shrink-0">2</div>
                        <div>
                            <h4 class="text-xs font-bold text-white">Align Face & Take Selfie</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Follow the green face guide oval and tap capture.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs font-bold shrink-0">3</div>
                        <div>
                            <h4 class="text-xs font-bold text-white">Automatic PC Sync</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">This screen will automatically update once completed.</p>
                        </div>
                    </div>

                    <!-- Live Sync Indicator -->
                    <div class="p-3 rounded-xl bg-slate-800 border border-slate-700 flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-emerald-400 animate-ping shrink-0"></div>
                        <span class="text-xs font-bold text-emerald-300">Listening for cellphone selfie capture...</span>
                    </div>
                </div>
            </div>

            <!-- Alternative Options -->
            <div class="mt-6 pt-4 border-t border-slate-700/60 flex flex-col md:flex-row items-center justify-between gap-3">
                <a :href="sessionUrl" target="_blank" class="text-xs font-bold text-emerald-400 hover:underline flex items-center gap-1">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span>Open Mobile Link directly in new tab</span>
                </a>
                <button type="button" @click="startWebcamOnPc()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="camera" class="w-4 h-4 text-emerald-400"></i>
                    <span>Use PC Webcam Directly</span>
                </button>
            </div>
        </div>

        <!-- Step 3: Verification Complete Screen -->
        <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-300 transform" class="bg-slate-800/80 border border-slate-700/80 rounded-3xl p-6 md:p-8 shadow-2xl backdrop-blur-md max-w-lg mx-auto text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 mb-4 shadow-lg shadow-emerald-950/60">
                <i data-lucide="check-circle-2" class="w-9 h-9"></i>
            </div>
            
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-950 text-emerald-300 border border-emerald-800 text-xs font-black uppercase tracking-wider mb-2">
                VERIFIED STUDENT IDENTITY
            </span>
            <h2 class="text-2xl font-black text-white" x-text="fullName"></h2>
            <p class="text-xs text-slate-400 font-bold mt-0.5 uppercase tracking-wider" x-text="gradeLevel"></p>

            <!-- Captured Selfie Display -->
            <div class="mt-6 relative inline-block">
                <div class="w-44 h-44 rounded-2xl overflow-hidden border-4 border-emerald-500 shadow-2xl mx-auto bg-slate-900 relative">
                    <img :src="selfieUrl" alt="Verified Selfie" class="w-full h-full object-cover">
                    <div class="absolute bottom-2 right-2 bg-emerald-600 text-white p-1 rounded-full shadow-md">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </div>
                </div>
            </div>

            <p class="text-xs text-slate-400 mt-4 font-semibold">Selfie verification completed at <span class="text-emerald-300 font-bold" x-text="completedAt"></span></p>

            <div class="mt-6 pt-4 border-t border-slate-700 flex gap-3">
                <button type="button" @click="resetForm()" class="w-full bg-slate-700 hover:bg-slate-600 text-white py-2.5 rounded-xl font-bold text-xs transition cursor-pointer">
                    Verify Another Student
                </button>
            </div>
        </div>

    </main>

    <footer class="border-t border-slate-800 py-4 text-center text-xs text-slate-500">
        AMIS Student Verification System &bull; Localhost Testing Environment
    </footer>

    <script>
        function selfieApp() {
            return {
                step: 1,
                fullName: '',
                gradeLevel: '',
                sessionId: '',
                sessionUrl: '',
                qrCodeUrl: '',
                selfieUrl: '',
                completedAt: '',
                loading: false,
                pollTimer: null,

                async submitStudentInfo() {
                    if (!this.fullName || !this.gradeLevel) return;
                    this.loading = true;

                    try {
                        const response = await fetch('{{ route("selfie.start") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                full_name: this.fullName,
                                grade_level: this.gradeLevel
                            })
                        });

                        const res = await response.json();
                        if (res.success) {
                            this.sessionId = res.session_id;
                            this.sessionUrl = res.session_url;
                            this.qrCodeUrl = res.qr_code_url;
                            this.step = 2;
                            this.startPolling();
                        }
                    } catch (e) {
                        alert('Error initializing session: ' + e.message);
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                startPolling() {
                    if (this.pollTimer) clearInterval(this.pollTimer);
                    this.pollTimer = setInterval(async () => {
                        try {
                            const res = await fetch(`/selfie-verification/status/${this.sessionId}`);
                            const data = await res.json();
                            if (data.status === 'completed') {
                                clearInterval(this.pollTimer);
                                this.selfieUrl = data.selfie_url;
                                this.completedAt = data.completed_at;
                                this.step = 3;
                                this.$nextTick(() => lucide.createIcons());
                            }
                        } catch (e) {}
                    }, 1500);
                },

                startWebcamOnPc() {
                    window.location.href = this.sessionUrl;
                },

                resetForm() {
                    if (this.pollTimer) clearInterval(this.pollTimer);
                    this.step = 1;
                    this.fullName = '';
                    this.gradeLevel = '';
                    this.sessionId = '';
                    this.sessionUrl = '';
                    this.qrCodeUrl = '';
                    this.selfieUrl = '';
                    this.$nextTick(() => lucide.createIcons());
                }
            }
        }
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
    </script>
</body>
</html>
