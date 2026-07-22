<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Selfie Camera Capture - AMIS Verification</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-emerald-500 selection:text-white">

    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur-md px-4 py-3 sticky top-0 z-40">
        <div class="max-w-md mx-auto flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold shadow-md">
                    <i data-lucide="camera" class="w-4 h-4"></i>
                </div>
                <div>
                    <h1 class="text-xs font-black text-white uppercase tracking-wider">Selfie Camera Verification</h1>
                    <p class="text-[10px] text-emerald-400 font-bold uppercase">{{ $session['full_name'] }}</p>
                </div>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-950 text-emerald-300 border border-emerald-800 uppercase">
                {{ $session['grade_level'] }}
            </span>
        </div>
    </header>

    <!-- Main Camera Container -->
    <main class="max-w-md mx-auto w-full p-4 my-auto" x-data="cameraApp()">

        <!-- Camera Viewfinder Box -->
        <div class="relative w-full aspect-square bg-slate-900 rounded-3xl overflow-hidden border-2 border-slate-800 shadow-2xl flex items-center justify-center">
            
            <!-- Video Stream -->
            <video x-ref="video" autoplay playsinline class="w-full h-full object-cover" style="transform: scaleX(-1); -webkit-transform: scaleX(-1);" x-show="!captured"></video>

            <!-- Captured Image Canvas / Preview -->
            <img :src="capturedImage" x-show="captured" class="w-full h-full object-cover" style="transform: scaleX(-1); -webkit-transform: scaleX(-1);">

            <!-- Face Oval & Shoulder Contour Guide Overlay -->
            <div class="pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center" x-show="!captured">
                <div class="relative flex items-center justify-center opacity-85" style="width: 250px; height: 240px;">
                    <svg viewBox="0 0 100 100" class="w-full h-full text-emerald-400 drop-shadow-md">
                        <!-- Face Oval Guide -->
                        <ellipse cx="50" cy="36" rx="26" ry="30" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 3" />
                        <!-- Shoulder Guide -->
                        <path d="M 18 70 Q 50 62 82 70 L 98 100 L 2 100 Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 3" />
                    </svg>
                    <span class="absolute -top-5 text-[9px] font-black tracking-widest text-emerald-300 uppercase bg-slate-900/80 px-2.5 py-0.5 rounded-full shadow-xs border border-emerald-400/30">
                        Center Head & Shoulders
                    </span>
                </div>
            </div>

            <!-- Loading overlay -->
            <div x-show="cameraLoading" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center gap-2 z-40">
                <i data-lucide="loader-2" class="w-8 h-8 text-emerald-500 animate-spin"></i>
                <span class="text-xs font-bold text-slate-300">Accessing Camera...</span>
            </div>
        </div>

        <canvas x-ref="canvas" class="hidden"></canvas>

        <!-- Controls -->
        <div class="mt-6 flex items-center justify-center gap-3">
            <template x-if="!captured">
                <button type="button" @click="takeSelfie()" :disabled="cameraLoading" class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white py-3.5 rounded-2xl font-black text-sm shadow-xl shadow-emerald-950/60 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <i data-lucide="aperture" class="w-5 h-5"></i>
                    <span>Take Selfie</span>
                </button>
            </template>

            <template x-if="captured">
                <div class="flex items-center gap-3 w-full">
                    <button type="button" @click="retake()" class="w-1/2 bg-slate-800 hover:bg-slate-700 text-slate-200 py-3.5 rounded-2xl font-bold text-xs transition active:scale-95 cursor-pointer">
                        Retake Photo
                    </button>
                    <button type="button" @click="uploadSelfie()" :disabled="uploading" class="w-1/2 bg-emerald-600 hover:bg-emerald-500 text-white py-3.5 rounded-2xl font-black text-xs shadow-xl transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50">
                        <span x-show="!uploading" class="flex items-center gap-1.5">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Submit & Verify</span>
                        </span>
                        <span x-show="uploading" class="flex items-center gap-1.5">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                            <span>Uploading...</span>
                        </span>
                    </button>
                </div>
            </template>
        </div>

        <!-- Success Toast Modal -->
        <div x-show="successModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-emerald-500/40 rounded-3xl p-6 max-w-sm w-full text-center shadow-2xl">
                <div class="w-14 h-14 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="check-circle" class="w-8 h-8"></i>
                </div>
                <h3 class="text-xl font-black text-white">Selfie Verified!</h3>
                <p class="text-xs text-slate-400 font-medium mt-1">Your identity selfie has been uploaded successfully. You can now close this page or return to the main screen.</p>
                <button type="button" @click="window.location.href='{{ route("selfie.index") }}'" class="mt-5 w-full bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 rounded-xl font-bold text-xs shadow-md">
                    Return to Portal
                </button>
            </div>
        </div>

    </main>

    <footer class="border-t border-slate-900 py-3 text-center text-[11px] text-slate-600">
        AMIS Mobile Selfie Capture &bull; Session ID: {{ substr($session['session_id'], 0, 8) }}
    </footer>

    <script>
        function cameraApp() {
            return {
                cameraLoading: true,
                captured: false,
                capturedImage: '',
                uploading: false,
                successModal: false,
                stream: null,

                async init() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                            audio: false
                        });
                        this.$refs.video.srcObject = this.stream;
                        this.cameraLoading = false;
                    } catch (err) {
                        alert('Could not access camera: ' + err.message + '. Please allow camera permissions.');
                    } finally {
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                takeSelfie() {
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 640;
                    const ctx = canvas.getContext('2d');
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.capturedImage = canvas.toDataURL('image/png');
                    this.captured = true;
                    this.$nextTick(() => lucide.createIcons());
                },

                retake() {
                    this.captured = false;
                    this.capturedImage = '';
                    this.$nextTick(() => lucide.createIcons());
                },

                async uploadSelfie() {
                    if (!this.capturedImage) return;
                    this.uploading = true;

                    try {
                        const response = await fetch('/selfie-verification/upload/{{ $session["session_id"] }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                image_data: this.capturedImage
                            })
                        });

                        const res = await response.json();
                        if (res.success) {
                            this.successModal = true;
                        } else {
                            alert(res.message || 'Failed to upload selfie.');
                        }
                    } catch (e) {
                        alert('Upload failed: ' + e.message);
                    } finally {
                        this.uploading = false;
                        this.$nextTick(() => lucide.createIcons());
                    }
                }
            }
        }
        document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
    </script>
</body>
</html>
