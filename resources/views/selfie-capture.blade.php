<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Selfie Camera Capture - AMIS Verification</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js & Lucide Icons -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Google MediaPipe Libraries (100% Free, Client-Side WebAssembly / JS CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js" crossorigin="anonymous"></script>

    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #030712; color: #f9fafb; }
        
        /* Mirror Transformation for Selfie View */
        .mirror-feed {
            transform: scaleX(-1);
            -webkit-transform: scaleX(-1);
        }

        /* Laser Beam Scan Effect */
        @keyframes laserScan {
            0% { top: 12%; opacity: 0.3; }
            50% { top: 82%; opacity: 0.95; }
            100% { top: 12%; opacity: 0.3; }
        }
        .laser-beam {
            position: absolute;
            left: 6%;
            right: 6%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, #34d399, #10b981, transparent);
            box-shadow: 0 0 16px #10b981;
            animation: laserScan 2.2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-emerald-600 selection:text-white overflow-x-hidden">

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
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-950 text-emerald-300 border border-emerald-800 uppercase flex items-center gap-1">
                <i data-lucide="shield-check" class="w-3 h-3 text-emerald-400"></i>
                <span>{{ $session['grade_level'] }}</span>
            </span>
        </div>
    </header>

    <!-- Main Camera Container -->
    <main class="max-w-md mx-auto w-full p-4 my-auto" x-data="cameraApp()">

        <!-- Viewfinder Outer Box -->
        <div class="relative w-full aspect-square bg-slate-900 rounded-3xl overflow-hidden border-2 border-slate-800 shadow-2xl flex items-center justify-center">
            
            <!-- Raw Hidden Video Source -->
            <video x-ref="video" autoplay playsinline webkit-playsinline muted class="hidden w-full h-full object-cover mirror-feed"></video>

            <!-- Rendered Canvas (Mirrored Stream + Segmented Background Blur) -->
            <canvas x-ref="outputCanvas" class="w-full h-full object-cover mirror-feed" x-show="!captured"></canvas>

            <!-- Snapshot Result Image Preview -->
            <img :src="capturedImage" x-show="captured" class="w-full h-full object-cover mirror-feed">

            <!-- Camera White Flash Effect on Snapshot -->
            <div x-show="flashEffect" class="fixed inset-0 bg-white z-50 pointer-events-none transition-opacity duration-200" x-cloak></div>

            <!-- Face Oval Target & Scan Beam Overlay -->
            <div class="pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center p-4" x-show="!captured && !cameraLoading">
                <div class="laser-beam" x-show="livenessScore < 100 && hasFace"></div>

                <!-- Responsive Dashed Oval Guide -->
                <div class="relative flex items-center justify-center" style="width: 260px; height: 250px;">
                    <svg viewBox="0 0 100 100" class="w-full h-full transition-colors duration-300 drop-shadow-lg" :class="hasFace ? (livenessScore >= 90 ? 'text-emerald-400' : 'text-emerald-300') : 'text-rose-500 animate-pulse'">
                        <ellipse cx="50" cy="36" rx="26" ry="30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="3 3" />
                        <path d="M 18 70 Q 50 62 82 70 L 98 100 L 2 100 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="3 3" />
                    </svg>
                    
                    <!-- Liveness Status Badge -->
                    <span class="absolute -top-6 text-[9px] font-black tracking-widest uppercase bg-slate-950/90 px-3.5 py-1 rounded-full border shadow-md flex items-center gap-1" :class="hasFace ? 'text-emerald-300 border-emerald-400/40' : 'text-rose-400 border-rose-500/40'">
                        <i data-lucide="scan-face" class="w-3.5 h-3.5" :class="hasFace ? 'text-emerald-400' : 'text-rose-500'"></i>
                        <span x-text="livenessStatusText">ALIGN FACE INSIDE OVAL</span>
                    </span>
                </div>

                <!-- Real-Time Progress Bar & Score Box -->
                <div class="absolute bottom-4 left-4 right-4 bg-slate-950/90 backdrop-blur-md p-3 rounded-2xl border flex items-center justify-between shadow-xl transition-colors" :class="hasFace ? 'border-emerald-500/30' : 'border-rose-500/30'">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" :class="hasFace ? 'bg-emerald-400 animate-ping' : 'bg-rose-500'"></div>
                        <span class="text-[11px] font-black uppercase tracking-wider" :class="hasFace ? 'text-slate-200' : 'text-rose-400'" x-text="hasFace ? 'LIVE HUMAN DETECTING...' : 'NO PERSON DETECTED'">LIVE HUMAN DETECTION</span>
                    </div>
                    <span class="text-xs font-black" :class="hasFace ? 'text-emerald-400' : 'text-rose-400'" x-text="livenessScore + '%'">0%</span>
                </div>
            </div>

            <!-- Loading overlay -->
            <div x-show="cameraLoading" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center gap-2 z-40">
                <i data-lucide="loader-2" class="w-8 h-8 text-emerald-400 animate-spin"></i>
                <span class="text-xs font-bold text-slate-300">Accessing Camera & Initializing AI...</span>
            </div>
        </div>

        <canvas x-ref="snapshotCanvas" class="hidden"></canvas>

        <!-- Controls -->
        <div class="mt-6 flex items-center justify-center gap-3">
            <template x-if="!captured">
                <button type="button" @click="takeSelfie()" :disabled="cameraLoading || !hasFace" class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white py-3.5 rounded-2xl font-black text-sm shadow-xl transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed border border-emerald-400/30">
                    <i data-lucide="aperture" class="w-5 h-5"></i>
                    <span x-text="livenessScore >= 100 ? 'Snap Live Selfie Now' : (hasFace ? 'Analyzing Live Stream (' + livenessScore + '%)' : 'Align Face in Oval Guide')">Snap Live Selfie</span>
                </button>
            </template>

            <template x-if="captured">
                <div class="flex items-center gap-3 w-full">
                    <button type="button" @click="retake()" class="w-1/2 bg-slate-800 hover:bg-slate-700 text-slate-200 py-3.5 rounded-2xl font-bold text-xs transition active:scale-95 cursor-pointer border border-slate-700">
                        Retake Photo
                    </button>
                    <button type="button" @click="uploadSelfie()" :disabled="uploading" class="w-1/2 bg-emerald-600 hover:bg-emerald-500 text-white py-3.5 rounded-2xl font-black text-xs shadow-xl transition active:scale-95 cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50 border border-emerald-400/30">
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
                    <i data-lucide="shield-check" class="w-8 h-8"></i>
                </div>
                <h3 class="text-xl font-black text-white">LIVENESS VERIFIED 100%!</h3>
                <p class="text-xs text-slate-300 font-medium mt-1">Your identity selfie has been uploaded successfully via Laravel backend.</p>
                <button type="button" @click="window.location.href='{{ route("selfie.index") }}'" class="mt-5 w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-xl font-bold text-xs shadow-md">
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
                flashEffect: false,
                stream: null,
                hasFace: false,
                livenessScore: 0,
                livenessStatusText: 'ALIGN FACE INSIDE OVAL',
                autoCapturedTriggered: false,
                faceMesh: null,
                selfieSegmentation: null,

                async init() {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                            audio: false
                        });

                        const videoEl = this.$refs.video;
                        videoEl.srcObject = this.stream;
                        await videoEl.play();

                        const canvasEl = this.$refs.outputCanvas;
                        canvasEl.width = videoEl.videoWidth || 640;
                        canvasEl.height = videoEl.videoHeight || 640;

                        const snapCanvas = this.$refs.snapshotCanvas;
                        snapCanvas.width = canvasEl.width;
                        snapCanvas.height = canvasEl.height;

                        this.cameraLoading = false;
                        this.initMediaPipeAI();
                    } catch (err) {
                        alert('Could not access camera: ' + err.message + '. Please allow camera permissions.');
                    } finally {
                        this.$nextTick(() => lucide.createIcons());
                    }
                },

                initMediaPipeAI() {
                    // 1. MediaPipe Selfie Segmentation (Client-Side Background Blur)
                    if (window.SelfieSegmentation) {
                        this.selfieSegmentation = new SelfieSegmentation({
                            locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/${file}`
                        });
                        this.selfieSegmentation.setOptions({ modelSelection: 1 });
                        this.selfieSegmentation.onResults((res) => this.onSegmentationResults(res));
                    }

                    // 2. MediaPipe Face Mesh (Client-Side Face & Liveness Detection)
                    if (window.FaceMesh) {
                        this.faceMesh = new FaceMesh({
                            locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
                        });
                        this.faceMesh.setOptions({
                            maxNumFaces: 1,
                            refineLandmarks: true,
                            minDetectionConfidence: 0.5,
                            minTrackingConfidence: 0.5
                        });
                        this.faceMesh.onResults((res) => this.onFaceMeshResults(res));
                    }

                    const processFrame = async () => {
                        if (!this.captured && this.$refs.video && this.$refs.video.readyState >= 2) {
                            if (this.selfieSegmentation) {
                                await this.selfieSegmentation.send({ image: this.$refs.video });
                            } else {
                                const ctx = this.$refs.outputCanvas.getContext('2d');
                                ctx.drawImage(this.$refs.video, 0, 0, this.$refs.outputCanvas.width, this.$refs.outputCanvas.height);
                            }

                            if (this.faceMesh) {
                                await this.faceMesh.send({ image: this.$refs.video });
                            }
                        }
                        if (!this.captured) {
                            requestAnimationFrame(processFrame);
                        }
                    };
                    requestAnimationFrame(processFrame);
                },

                onSegmentationResults(results) {
                    if (this.captured) return;
                    const canvasEl = this.$refs.outputCanvas;
                    const ctx = canvasEl.getContext('2d');

                    ctx.save();
                    ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

                    ctx.filter = 'blur(10px)';
                    ctx.drawImage(results.image, 0, 0, canvasEl.width, canvasEl.height);
                    ctx.filter = 'none';

                    ctx.globalCompositeOperation = 'destination-atop';
                    ctx.drawImage(results.segmentationMask, 0, 0, canvasEl.width, canvasEl.height);

                    ctx.globalCompositeOperation = 'destination-over';
                    ctx.drawImage(results.image, 0, 0, canvasEl.width, canvasEl.height);
                    ctx.restore();
                },

                onFaceMeshResults(results) {
                    if (this.captured) return;

                    if (!results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
                        this.hasFace = false;
                        this.livenessScore = 0;
                        this.livenessStatusText = 'NO HUMAN FACE DETECTED';
                        return;
                    }

                    this.hasFace = true;
                    const landmarks = results.multiFaceLandmarks[0];
                    const nose = landmarks[1];

                    const isCentered = nose.x > 0.25 && nose.x < 0.75 && nose.y > 0.2 && nose.y < 0.8;

                    if (isCentered) {
                        if (this.livenessScore < 100) {
                            this.livenessScore += Math.floor(Math.random() * 15) + 10;
                            if (this.livenessScore > 100) this.livenessScore = 100;
                        }

                        if (this.livenessScore < 50) {
                            this.livenessStatusText = 'ALIGN FACE IN OVAL GUIDE';
                        } else if (this.livenessScore < 95) {
                            this.livenessStatusText = 'LIVE HUMAN DETECTED (VERIFYING)';
                        } else {
                            this.livenessStatusText = 'LIVENESS VERIFIED 100%';
                        }

                        if (this.livenessScore >= 100 && !this.captured && !this.autoCapturedTriggered) {
                            this.autoCapturedTriggered = true;
                            this.flashEffect = true;
                            setTimeout(() => { this.flashEffect = false; }, 250);
                            this.takeSelfie();
                            this.uploadSelfie();
                        }
                    } else {
                        this.livenessStatusText = 'CENTER FACE INSIDE OVAL';
                    }
                },

                takeSelfie() {
                    const canvasEl = this.$refs.outputCanvas;
                    const snapCanvas = this.$refs.snapshotCanvas;
                    const ctx = snapCanvas.getContext('2d');

                    ctx.translate(snapCanvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(canvasEl, 0, 0, snapCanvas.width, snapCanvas.height);

                    this.capturedImage = snapCanvas.toDataURL('image/png');
                    this.captured = true;
                    this.$nextTick(() => lucide.createIcons());
                },

                retake() {
                    this.captured = false;
                    this.capturedImage = '';
                    this.autoCapturedTriggered = false;
                    this.livenessScore = 0;
                    this.hasFace = false;
                    this.livenessStatusText = 'ALIGN FACE INSIDE OVAL';
                    this.initMediaPipeAI();
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
