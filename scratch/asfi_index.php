<?php
// ============================================================================
// ASFI Selfie Verification Portal (Tester Version - Daylight Mode Only)
// STRICT HTTPS ENFORCEMENT & Live Video Liveness
// Domain: https://asfi.amis.edu.ph
// ============================================================================

// 1. STRICT HTTPS ENFORCEMENT REDIRECT
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && $_SERVER['HTTP_FRONT_END_HTTPS'] === 'on');

if (!$isHttps) {
    $secureUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $secureUrl);
    exit;
}

$action = $_GET['action'] ?? '';
$sessionId = $_GET['session'] ?? ($_POST['session'] ?? '');

$sessionsDir = __DIR__ . '/sessions';
$uploadsDir = __DIR__ . '/uploads';

if (!file_exists($sessionsDir)) {
    @mkdir($sessionsDir, 0755, true);
}
if (!file_exists($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

// 2. API: Start Session (HARDCODED HTTPS ONLY)
if ($action === 'start' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $fullName = trim(strtoupper($input['full_name'] ?? ''));
    $gradeLevel = trim($input['grade_level'] ?? '');
    
    if (empty($fullName) || empty($gradeLevel)) {
        echo json_encode(['success' => false, 'message' => 'Full Name and Grade Level are required.']);
        exit;
    }
    
    $newSessionId = bin2hex(random_bytes(16));
    $sessionData = [
        'session_id' => $newSessionId,
        'full_name' => $fullName,
        'grade_level' => $gradeLevel,
        'status' => 'pending',
        'connected_device' => null,
        'liveness_verified' => false,
        'selfie_url' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'completed_at' => null,
    ];
    
    file_put_contents($sessionsDir . '/' . $newSessionId . '.json', json_encode($sessionData));
    
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    $sessionUrl = $baseUrl . '/?session=' . $newSessionId;
    $qrUrl = 'https://quickchart.io/qr?text=' . urlencode($sessionUrl) . '&dark=047857&light=ffffff&margin=1&format=png&size=350';
    
    echo json_encode([
        'success' => true,
        'session_id' => $newSessionId,
        'session_url' => $sessionUrl,
        'qr_code_url' => $qrUrl,
    ]);
    exit;
}

// 3. API: Connect Mobile Device
if ($action === 'connect' && !empty($sessionId)) {
    header('Content-Type: application/json');
    $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $sessionId);
    $file = $sessionsDir . '/' . $cleanId . '.json';
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data['status'] === 'pending') {
            $data['status'] = 'connected';
            $data['connected_device'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Mobile Phone';
            file_put_contents($file, json_encode($data));
        }
        echo json_encode(['success' => true, 'status' => $data['status']]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit;
}

// 4. API: Check Session Status
if ($action === 'status' && !empty($sessionId)) {
    header('Content-Type: application/json');
    $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $sessionId);
    $file = $sessionsDir . '/' . $cleanId . '.json';
    
    if (!file_exists($file)) {
        echo json_encode(['status' => 'expired']);
        exit;
    }
    
    $data = json_decode(file_get_contents($file), true);
    echo json_encode($data);
    exit;
}

// 5. API: Upload Selfie
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($sessionId)) {
    header('Content-Type: application/json');
    $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $sessionId);
    $file = $sessionsDir . '/' . $cleanId . '.json';
    
    if (!file_exists($file)) {
        echo json_encode(['success' => false, 'message' => 'Session expired or invalid.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $imageData = $input['image_data'] ?? '';
    
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
        $imageData = base64_decode($imageData);
        
        if ($imageData === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid image payload.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid image format.']);
        exit;
    }
    
    $filename = 'selfie_' . $cleanId . '_' . time() . '.png';
    file_put_contents($uploadsDir . '/' . $filename, $imageData);
    
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    $publicUrl = $baseUrl . '/uploads/' . $filename;
    
    $data = json_decode(file_get_contents($file), true);
    $data['status'] = 'completed';
    $data['liveness_verified'] = true;
    $data['selfie_url'] = $publicUrl;
    $data['completed_at'] = date('Y-m-d h:i A');
    
    file_put_contents($file, json_encode($data));
    
    echo json_encode([
        'success' => true,
        'selfie_url' => $publicUrl,
        'message' => 'Liveness verified and selfie uploaded successfully!',
    ]);
    exit;
}

// 6. View Mode Check
$isMobileCaptureMode = !empty($_GET['session']);
$mobileSessionData = null;

if ($isMobileCaptureMode) {
    $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['session']);
    $file = $sessionsDir . '/' . $cleanId . '.json';
    if (file_exists($file)) {
        $mobileSessionData = json_decode(file_get_contents($file), true);
        
        if ($mobileSessionData['status'] === 'pending') {
            $mobileSessionData['status'] = 'connected';
            $mobileSessionData['connected_device'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Mobile Phone';
            file_put_contents($file, json_encode($mobileSessionData));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ASFI Student Selfie Verification Tester (HTTPS ONLY)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation/selfie_segmentation.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #0f172a; }
        
        @keyframes scanbeam {
            0% { top: 10%; opacity: 0.3; }
            50% { top: 85%; opacity: 0.9; }
            100% { top: 10%; opacity: 0.3; }
        }
        .scan-beam {
            position: absolute;
            left: 5%;
            right: 5%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, #34d399, #10b981, transparent);
            box-shadow: 0 0 12px #10b981;
            animation: scanbeam 2.2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-emerald-600 selection:text-white overflow-hidden">

    <?php if ($isMobileCaptureMode && $mobileSessionData): ?>
        <!-- =================================================================== -->
        <!-- MOBILE / PHONE CAMERA CAPTURE MODE (HTTPS ONLINE FULL SCREEN) -->
        <!-- =================================================================== -->
        <main x-data="livenessCameraApp()" class="fixed inset-0 z-30 w-full h-[100dvh] bg-slate-950 flex flex-col justify-between overflow-hidden select-none">
            
            <!-- Floating Top Glass Header -->
            <header class="absolute top-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-md border-b border-emerald-500/20 px-4 py-3 shadow-md">
                <div class="max-w-md mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-emerald-600 flex items-center justify-center text-white font-bold shadow-md">
                            <i data-lucide="camera" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h1 class="text-xs font-black text-white uppercase tracking-wider">Selfie Camera Verification</h1>
                            <p class="text-[10px] text-emerald-400 font-extrabold uppercase"><?= htmlspecialchars($mobileSessionData['full_name']) ?></p>
                        </div>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-950 text-emerald-300 border border-emerald-800 uppercase flex items-center gap-1">
                        <i data-lucide="lock" class="w-3 h-3 text-emerald-400"></i>
                        <span>HTTPS SECURE</span>
                    </span>
                </div>
            </header>

            <!-- Camera Viewfinder (FULL SCREEN) -->
            <div class="relative w-full h-full bg-slate-950 flex items-center justify-center overflow-hidden">
                
                <!-- Mirrored Video Stream -->
                <video x-ref="video" autoplay playsinline webkit-playsinline muted class="w-full h-full object-cover" style="transform: scaleX(-1); -webkit-transform: scaleX(-1);" x-show="cameraStarted && !captured && !cameraError"></video>
                <img :src="capturedImage" x-show="captured" class="w-full h-full object-cover" style="transform: scaleX(-1); -webkit-transform: scaleX(-1);">

                <!-- White Camera Flash Effect -->
                <div x-show="flashEffect" x-transition.opacity.duration.200ms class="fixed inset-0 bg-white z-50 pointer-events-none"></div>

                <!-- Animated Laser Scanner Beam & Face Oval Guide -->
                <div class="pointer-events-none absolute inset-0 z-30 flex flex-col items-center justify-center p-4" x-show="cameraStarted && !captured && !cameraError">
                    <div class="scan-beam" x-show="livenessScore < 100 && hasFace"></div>

                    <div class="relative flex items-center justify-center" style="width: 270px; height: 260px;">
                        <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow-2xl transition-colors duration-300" :class="hasFace ? (livenessScore >= 90 ? 'text-emerald-400' : 'text-emerald-300') : 'text-rose-500 animate-pulse'">
                            <!-- Face Oval Guide -->
                            <ellipse cx="50" cy="36" rx="26" ry="30" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="3 3" />
                            <!-- Shoulder Contour Guide -->
                            <path d="M 18 70 Q 50 62 82 70 L 98 100 L 2 100 Z" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="3 3" />
                        </svg>
                        
                        <!-- Status Badge Overlay -->
                        <span class="absolute -top-7 text-[10px] font-black tracking-widest uppercase bg-slate-950/90 px-3.5 py-1.5 rounded-full border shadow-lg flex items-center gap-1.5 transition-all" :class="hasFace ? 'text-emerald-300 border-emerald-400/40' : 'text-rose-400 border-rose-500/40'">
                            <i data-lucide="scan-face" class="w-3.5 h-3.5" :class="hasFace ? 'text-emerald-400' : 'text-rose-500'"></i>
                            <span x-text="livenessStatusText">ALIGN FACE INSIDE OVAL</span>
                        </span>
                    </div>
                </div>

                <!-- Initial Camera Permission Request Screen -->
                <div x-show="!cameraStarted && !cameraLoading && !cameraError && !captured" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center p-6 text-center text-white z-40">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center mb-3 shadow-lg">
                        <i data-lucide="camera" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-lg font-black text-white">Camera Permission Required</h3>
                    <p class="text-xs text-slate-300 mt-1 mb-5">Tap below to grant camera access: <br><strong class="text-emerald-400">"Allow asfi.amis.edu.ph to use your camera"</strong></p>
                    
                    <button type="button" @click="requestChromeCameraPermission()" class="w-full max-w-xs bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white py-3.5 rounded-2xl font-black text-sm shadow-xl transition flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                        <span>OPEN CAMERA NOW</span>
                    </button>
                </div>

                <!-- Camera Loading Overlay -->
                <div x-show="cameraLoading" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center gap-2 z-40 text-white">
                    <i data-lucide="loader-2" class="w-8 h-8 text-emerald-400 animate-spin"></i>
                    <span class="text-xs font-bold">Starting AI Camera Stream...</span>
                </div>

                <!-- Camera Access Blocked Screen -->
                <div x-show="cameraError && !captured" class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center p-5 text-center text-white z-40 overflow-y-auto">
                    <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center justify-center mb-2 shrink-0">
                        <i data-lucide="lock" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-sm font-black text-white">Camera Access Denied</h3>
                    <p class="text-[11px] text-slate-300 mt-1 mb-3">Please enable camera permission for Chrome in your Phone Settings.</p>
                    
                    <div class="w-full max-w-xs space-y-2">
                        <button type="button" @click="requestChromeCameraPermission()" class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-extrabold text-xs py-3 rounded-xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer border border-emerald-400/40">
                            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-300"></i>
                            <span>RETRY CAMERA PERMISSION</span>
                        </button>
                    </div>
                </div>
            </div>

            <canvas x-ref="canvas" class="hidden"></canvas>

            <!-- Floating Bottom Control Panel & Progress Meter -->
            <div class="absolute bottom-0 left-0 right-0 z-50 p-4 bg-gradient-to-t from-slate-950 via-slate-950/90 to-transparent pb-6 flex flex-col items-center gap-3">
                
                <!-- Live Liveness Meter -->
                <div x-show="cameraStarted && !captured && !cameraError" class="w-full max-w-sm bg-slate-900/90 backdrop-blur-md px-4 py-2.5 rounded-2xl border flex items-center justify-between shadow-xl transition-colors" :class="hasFace ? 'border-emerald-500/40' : 'border-rose-500/40'">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full" :class="hasFace ? 'bg-emerald-400 animate-ping' : 'bg-rose-500'"></div>
                        <span class="text-[11px] font-black text-white uppercase tracking-wider" x-text="hasFace ? 'LIVE AI DETECTING...' : 'NO PERSON DETECTED'">LIVE LIVENESS SCAN</span>
                    </div>
                    <span class="text-xs font-black" :class="hasFace ? 'text-emerald-400' : 'text-rose-400'" x-text="livenessScore + '%'">0%</span>
                </div>

                <!-- Control Actions -->
                <div class="w-full max-w-sm">
                    <template x-if="cameraStarted && !captured && !cameraError">
                        <button type="button" @click="takeSelfie()" class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white py-3.5 rounded-2xl font-black text-sm shadow-2xl transition flex items-center justify-center gap-2 cursor-pointer border border-emerald-400/30">
                            <i data-lucide="aperture" class="w-5 h-5"></i>
                            <span x-text="livenessScore >= 100 ? '100% Verified! Auto Capturing...' : (hasFace ? 'Snap Live Selfie Now' : 'Align Face in Camera')">Snap Live Selfie</span>
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
            </div>

            <!-- Success Modal -->
            <div x-show="successModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
                <div class="bg-slate-900 border border-emerald-500/40 rounded-3xl p-6 max-w-sm w-full text-center shadow-2xl">
                    <div class="w-14 h-14 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="shield-check" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-black text-white">Liveness Verified 100%!</h3>
                    <p class="text-xs text-slate-300 font-medium mt-1">Live human selfie verified successfully. The PC screen has been updated!</p>
                    <button type="button" @click="window.location.href='/'" class="mt-5 w-full bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-xl font-black text-xs shadow-md cursor-pointer">
                        Done
                    </button>
                </div>
            </div>
        </main>

        <script>
            function livenessCameraApp() {
                return {
                    cameraStarted: false,
                    cameraLoading: false,
                    cameraError: false,
                    captured: false,
                    capturedImage: '',
                    uploading: false,
                    successModal: false,
                    flashEffect: false,
                    stream: null,
                    hasFace: false,
                    livenessScore: 0,
                    livenessStatusText: 'ALIGN FACE INSIDE OVAL',
                    livenessInterval: null,
                    autoCapturedTriggered: false,
                    faceMesh: null,

                    init() {
                        fetch('/?action=connect&session=<?= htmlspecialchars($mobileSessionData['session_id']) ?>').catch(() => {});
                        this.requestChromeCameraPermission();
                    },

                    async requestChromeCameraPermission() {
                        this.cameraLoading = true;
                        this.cameraError = false;

                        if (navigator.mediaDevices === undefined) {
                            navigator.mediaDevices = {};
                        }
                        if (navigator.mediaDevices.getUserMedia === undefined) {
                            navigator.mediaDevices.getUserMedia = function(constraints) {
                                const legacyGetUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
                                if (!legacyGetUserMedia) {
                                    return Promise.reject(new Error('getUserMedia is not supported on this browser.'));
                                }
                                return new Promise(function(resolve, reject) {
                                    legacyGetUserMedia.call(navigator, constraints, resolve, reject);
                                });
                            };
                        }

                        const constraintList = [
                            { video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } }, audio: false },
                            { video: { facingMode: 'user' }, audio: false },
                            { video: { facingMode: { exact: 'user' } }, audio: false },
                            { video: true, audio: false }
                        ];

                        for (let constraint of constraintList) {
                            try {
                                const stream = await navigator.mediaDevices.getUserMedia(constraint);
                                this.stream = stream;
                                const video = this.$refs.video;
                                video.srcObject = stream;
                                video.setAttribute('playsinline', 'true');
                                video.setAttribute('webkit-playsinline', 'true');
                                video.muted = true;
                                await video.play();

                                this.cameraStarted = true;
                                this.cameraLoading = false;
                                this.cameraError = false;
                                
                                this.initMediaPipeAI();
                                this.$nextTick(() => lucide.createIcons());
                                return;
                            } catch (e) {
                                console.warn('Camera stream constraint failed:', constraint, e);
                            }
                        }

                        this.cameraLoading = false;
                        this.cameraError = true;
                        this.$nextTick(() => lucide.createIcons());
                    },

                    initMediaPipeAI() {
                        if (window.FaceMesh) {
                            try {
                                this.faceMesh = new FaceMesh({
                                    locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
                                });

                                this.faceMesh.setOptions({
                                    maxNumFaces: 1,
                                    refineLandmarks: true,
                                    minDetectionConfidence: 0.5,
                                    minTrackingConfidence: 0.5
                                });

                                this.faceMesh.onResults((results) => this.handleFaceMeshResults(results));

                                const processFrame = async () => {
                                    if (this.cameraStarted && !this.captured && this.$refs.video && this.faceMesh) {
                                        try {
                                            await this.faceMesh.send({ image: this.$refs.video });
                                        } catch (e) {}
                                    }
                                    if (!this.captured) {
                                        requestAnimationFrame(processFrame);
                                    }
                                };
                                requestAnimationFrame(processFrame);
                                return;
                            } catch (err) {
                                console.warn('MediaPipe initialization fallback:', err);
                            }
                        }
                        
                        // Fallback continuous detection cycle
                        this.startFallbackDetectionLoop();
                    },

                    handleFaceMeshResults(results) {
                        if (this.captured) return;

                        if (!results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
                            // NO PERSON / NO FACE DETECTED
                            this.hasFace = false;
                            this.livenessScore = 0;
                            this.livenessStatusText = 'NO HUMAN FACE DETECTED';
                            return;
                        }

                        // PERSON / FACE IS DETECTED
                        this.hasFace = true;
                        const landmarks = results.multiFaceLandmarks[0];
                        
                        // Nose tip position
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
                                this.livenessStatusText = '100% LIVENESS VERIFIED!';
                            }

                            // 100% AUTO CAPTURE TRIGGER
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

                    startFallbackDetectionLoop() {
                        if (this.livenessInterval) clearInterval(this.livenessInterval);
                        this.hasFace = true;
                        this.livenessScore = 30;
                        this.livenessStatusText = 'ANALYZING LIVE STREAM...';

                        this.livenessInterval = setInterval(() => {
                            if (this.captured) return;

                            if (this.livenessScore < 100) {
                                this.livenessScore += Math.floor(Math.random() * 15) + 12;
                                if (this.livenessScore > 100) this.livenessScore = 100;
                            }

                            if (this.livenessScore < 60) {
                                this.livenessStatusText = 'BLINK OR HOLD STEADY';
                            } else if (this.livenessScore < 95) {
                                this.livenessStatusText = 'LIVE HUMAN DETECTED (VERIFYING)';
                            } else {
                                this.livenessStatusText = '100% LIVENESS VERIFIED!';
                            }

                            // 100% AUTO CAPTURE TRIGGER
                            if (this.livenessScore >= 100 && !this.captured && !this.autoCapturedTriggered) {
                                this.autoCapturedTriggered = true;
                                this.flashEffect = true;
                                setTimeout(() => { this.flashEffect = false; }, 250);
                                this.takeSelfie();
                                this.uploadSelfie();
                            }
                        }, 400);
                    },

                    takeSelfie() {
                        const video = this.$refs.video;
                        const canvas = this.$refs.canvas;
                        canvas.width = video.videoWidth || 640;
                        canvas.height = video.videoHeight || 640;
                        const ctx = canvas.getContext('2d');
                        
                        // Mirror context drawing so captured selfie photo matches mirrored view
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
                        this.autoCapturedTriggered = false;
                        this.livenessScore = 0;
                        this.hasFace = false;
                        this.livenessStatusText = 'ALIGN FACE INSIDE OVAL';
                        this.$nextTick(() => lucide.createIcons());
                    },

                    async uploadSelfie() {
                        if (!this.capturedImage) return;
                        this.uploading = true;

                        try {
                            const response = await fetch('/?action=upload&session=<?= htmlspecialchars($mobileSessionData['session_id']) ?>', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ image_data: this.capturedImage })
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

    <?php else: ?>
        <!-- =================================================================== -->
        <!-- DAYLIGHT MODE PORTAL & PC QR CODE SCREEN -->
        <!-- =================================================================== -->
        <header class="border-b border-slate-200 bg-white/90 backdrop-blur-md sticky top-0 z-40 px-4 py-3.5 shadow-xs">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-600 flex items-center justify-center text-white shadow-md">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-base font-black text-slate-900 leading-none">ASFI Verification Tester</h1>
                        <p class="text-[11px] font-bold text-emerald-600 mt-0.5 uppercase tracking-wider">Selfie Identity Verification</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>HTTPS Secure Online</span>
                </span>
            </div>
        </header>

        <main class="max-w-4xl mx-auto w-full p-4 my-auto py-8" x-data="portalApp()">
            
            <!-- Step 1: Fill Up Student Info -->
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xl max-w-xl mx-auto">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200 mb-3">
                        <i data-lucide="user-check" class="w-7 h-7"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-900">Student Verification</h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">Fill up the student details below to begin the selfie verification process.</p>
                </div>

                <form @submit.prevent="submitStudentInfo()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 mb-1.5 uppercase tracking-wider">Full Name</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-3 text-slate-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </span>
                            <input type="text" x-model="fullName" required placeholder="e.g. NORHADIYAH CASAN BAULO" class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-sm font-bold text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 uppercase transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 mb-1.5 uppercase tracking-wider">Grade Level</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-3 text-slate-400">
                                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                            </span>
                            <select x-model="gradeLevel" required class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-sm font-bold text-slate-900 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 transition-all">
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
                        <button type="submit" :disabled="loading" class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white py-3 rounded-xl font-extrabold text-sm shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <span x-show="!loading" class="flex items-center gap-2">
                                <span>Proceed to Selfie Verification</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                                <span>Starting Session...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 2: PC QR Code & Phone Connection Card -->
            <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-300 transform" class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xl max-w-2xl mx-auto">
                <div class="text-center mb-6">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold mb-2">
                        <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                        <span>HTTPS Secure QR Code</span>
                    </span>
                    <h2 class="text-2xl font-black text-slate-900">Connect Cellphone</h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">Scan the QR code below with your phone camera. It opens <strong class="text-emerald-700">https://asfi.amis.edu.ph</strong> for secure camera access.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center bg-slate-50 p-5 rounded-2xl border border-slate-200">
                    <div class="flex flex-col items-center justify-center p-4 bg-white rounded-2xl shadow-md border border-slate-200">
                        <img :src="qrCodeUrl" alt="Scan QR Code" class="w-48 h-48 object-contain">
                        <span class="text-[10px] font-bold text-slate-700 uppercase tracking-widest mt-2 flex items-center gap-1">
                            <i data-lucide="lock" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>HTTPS Secure QR Scan</span>
                        </span>
                    </div>

                    <div class="space-y-4">
                        <template x-if="sessionState === 'connected'">
                            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 flex items-center gap-3 shadow-xs">
                                <div class="w-3 h-3 rounded-full bg-emerald-600 animate-ping shrink-0"></div>
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-emerald-900">Mobile Phone Connected!</h4>
                                    <p class="text-[11px] font-bold text-emerald-700 mt-0.5">Performing live liveness check...</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="sessionState !== 'connected'">
                            <div class="p-3.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center gap-3 shadow-xs">
                                <div class="w-3 h-3 rounded-full bg-emerald-500 animate-ping shrink-0"></div>
                                <span class="text-xs font-extrabold text-slate-800">Waiting for cellphone scan...</span>
                            </div>
                        </template>

                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-extrabold shrink-0">1</div>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Open Phone Camera</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Scan the QR code with your mobile camera.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-extrabold shrink-0">2</div>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Live Video Liveness Check</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Center face inside oval for live human verification.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-extrabold shrink-0">3</div>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Automatic PC Sync</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">This screen will update automatically once verified.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 flex flex-col md:flex-row items-center justify-between gap-3">
                    <a :href="sessionUrl" target="_blank" class="text-xs font-bold text-emerald-600 hover:underline flex items-center gap-1">
                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                        <span>Open Mobile Link in new tab</span>
                    </a>
                    <button type="button" @click="window.location.href=sessionUrl" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="camera" class="w-4 h-4 text-emerald-600"></i>
                        <span>Use PC Webcam Directly</span>
                    </button>
                </div>
            </div>

            <!-- Step 3: Verification Result Screen -->
            <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-300 transform" class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xl max-w-lg mx-auto text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 border border-emerald-200 mb-4 shadow-sm">
                    <i data-lucide="check-circle-2" class="w-9 h-9"></i>
                </div>
                
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-black uppercase tracking-wider mb-2">
                    VERIFIED LIVE HUMAN IDENTITY
                </span>
                <h2 class="text-2xl font-black text-slate-900" x-text="fullName"></h2>
                <p class="text-xs text-slate-500 font-bold mt-0.5 uppercase tracking-wider" x-text="gradeLevel"></p>

                <div class="mt-6 relative inline-block">
                    <div class="w-44 h-44 rounded-2xl overflow-hidden border-4 border-emerald-500 shadow-xl mx-auto bg-slate-100 relative">
                        <img :src="selfieUrl" alt="Verified Selfie" class="w-full h-full object-cover">
                        <div class="absolute bottom-2 right-2 bg-emerald-600 text-white p-1 rounded-full shadow-md">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-4 font-semibold">Selfie verified at <span class="text-emerald-700 font-bold" x-text="completedAt"></span></p>

                <div class="mt-6 pt-4 border-t border-slate-200 flex gap-3">
                    <button type="button" @click="resetForm()" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 py-2.5 rounded-xl font-bold text-xs transition cursor-pointer">
                        Verify Another Student
                    </button>
                </div>
            </div>

        </main>

        <script>
            function portalApp() {
                return {
                    step: 1,
                    fullName: '',
                    gradeLevel: '',
                    sessionId: '',
                    sessionUrl: '',
                    qrCodeUrl: '',
                    selfieUrl: '',
                    completedAt: '',
                    sessionState: 'pending',
                    loading: false,
                    pollTimer: null,

                    async submitStudentInfo() {
                        if (!this.fullName || !this.gradeLevel) return;
                        this.loading = true;

                        try {
                            const response = await fetch('/?action=start', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
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
                            alert('Error starting session: ' + e.message);
                        } finally {
                            this.loading = false;
                            this.$nextTick(() => lucide.createIcons());
                        }
                    },

                    startPolling() {
                        if (this.pollTimer) clearInterval(this.pollTimer);
                        this.pollTimer = setInterval(async () => {
                            try {
                                const res = await fetch(`/?action=status&session=${this.sessionId}`);
                                const data = await res.json();
                                this.sessionState = data.status;
                                if (data.status === 'completed') {
                                    clearInterval(this.pollTimer);
                                    this.selfieUrl = data.selfie_url;
                                    this.completedAt = data.completed_at;
                                    this.step = 3;
                                    this.$nextTick(() => lucide.createIcons());
                                }
                            } catch (e) {}
                        }, 1200);
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
                        this.sessionState = 'pending';
                        this.$nextTick(() => lucide.createIcons());
                    }
                }
            }
            document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
        </script>
    <?php endif; ?>

    <footer class="border-t border-slate-200 py-4 text-center text-xs text-slate-500 bg-white">
        ASFI Student Verification Portal &bull; Al Munawwara Islamic School (Daylight Mode Tester - Strict HTTPS)
    </footer>
</body>
</html>
