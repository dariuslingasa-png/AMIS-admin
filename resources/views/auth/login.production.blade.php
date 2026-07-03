<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student Portal — Sign In</title>
    <style>
        [x-cloak]{display:none!important}

        /* ── INTRO SPLASH ─────────────────────────────────────── */
        @keyframes introFadeIn  { from{opacity:0;transform:scale(0.92)} to{opacity:1;transform:scale(1)} }
        @keyframes introFadeOut { from{opacity:1;transform:scale(1)}    to{opacity:0;transform:scale(1.04)} }
        @keyframes logoPulse    { 0%,100%{transform:scale(1)} 50%{transform:scale(1.06)} }
        @keyframes barFill      { from{width:0%} to{width:100%} }
        @keyframes dotBounce    { 0%,80%,100%{transform:translateY(0);opacity:0.4} 40%{transform:translateY(-8px);opacity:1} }
        @keyframes ringExpand   { 0%{transform:translate(-50%,-50%) scale(0.6);opacity:0.6} 100%{transform:translate(-50%,-50%) scale(1.8);opacity:0} }
        @keyframes textSlideUp  { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
        @keyframes contentReveal{ from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        #intro-splash {
            position:fixed; inset:0; z-index:99999;
            background:linear-gradient(160deg,#059669 0%,#047857 55%,#065f46 100%);
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            animation:introFadeIn 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        #intro-splash.hiding {
            animation:introFadeOut 0.55s cubic-bezier(0.4,0,0.2,1) forwards;
            pointer-events:none;
        }

        /* Rings */
        .intro-ring {
            position:absolute; border-radius:50%;
            top:50%; left:50%;
            transform:translate(-50%,-50%);
            border:1px solid rgba(255,255,255,0.12);
            pointer-events:none;
        }
        .intro-ring-pulse {
            position:absolute; border-radius:50%;
            top:50%; left:50%;
            border:1.5px solid rgba(255,255,255,0.25);
            pointer-events:none;
            animation:ringExpand 2.4s ease-out infinite;
        }
        .intro-ring-pulse:nth-child(2) { animation-delay:0.8s; }
        .intro-ring-pulse:nth-child(3) { animation-delay:1.6s; }

        /* Dot grid */
        .intro-dots-bg {
            position:absolute; inset:0;
            background-image:radial-gradient(circle,rgba(255,255,255,0.07) 1px,transparent 1px);
            background-size:30px 30px;
            pointer-events:none;
        }

        /* Content */
        .intro-content {
            position:relative; z-index:2;
            display:flex; flex-direction:column; align-items:center; gap:0;
            text-align:center;
        }
        .intro-logo-wrap {
            animation:introFadeIn 0.6s 0.2s cubic-bezier(0.22,1,0.36,1) both,
                       logoPulse 2.5s 0.8s ease-in-out infinite;
            margin-bottom:1.75rem;
        }
        .intro-logo-wrap img {
            width:100px; height:100px; object-fit:contain;
            filter:drop-shadow(0 8px 24px rgba(0,0,0,0.3));
        }
        .intro-arabic {
            font-size:1.375rem; font-weight:700; color:rgba(255,255,255,0.85);
            margin-bottom:0.5rem;
            animation:textSlideUp 0.6s 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        .intro-school-name {
            font-size:clamp(1.75rem,4vw,2.75rem); font-weight:900; color:white;
            letter-spacing:-0.02em; line-height:1.2; white-space:nowrap;
            animation:textSlideUp 0.6s 0.65s cubic-bezier(0.22,1,0.36,1) both;
        }
        @media (max-width:480px) {
            .intro-school-name { white-space:normal; font-size:1.75rem; }
            .intro-logo-wrap img { width:80px; height:80px; }
            .intro-progress-wrap { width:140px; }
        }
        .intro-divider {
            width:40px; height:2px; background:rgba(255,255,255,0.4);
            border-radius:999px; margin:1.25rem auto;
            animation:textSlideUp 0.5s 0.8s cubic-bezier(0.22,1,0.36,1) both;
        }
        .intro-portal-label {
            font-size:1rem; font-weight:600; color:rgba(255,255,255,0.7);
            letter-spacing:0.18em; text-transform:uppercase;
            animation:textSlideUp 0.5s 0.9s cubic-bezier(0.22,1,0.36,1) both;
        }

        /* Progress bar */
        .intro-progress-wrap {
            margin-top:3rem;
            width:180px;
            animation:textSlideUp 0.5s 1s cubic-bezier(0.22,1,0.36,1) both;
        }
        .intro-progress-track {
            height:3px; background:rgba(255,255,255,0.2); border-radius:999px; overflow:hidden;
        }
        .intro-progress-bar {
            height:100%; background:rgba(255,255,255,0.8); border-radius:999px;
            width:0%;
            animation:barFill 0.7s 0.2s cubic-bezier(0.4,0,0.2,1) forwards;
        }
        .intro-loading-text {
            font-size:0.6875rem; color:rgba(255,255,255,0.45); font-weight:500;
            letter-spacing:0.1em; text-transform:uppercase; margin-top:0.75rem;
            text-align:center;
        }

        /* Login page hidden until splash done */
        #login-page {
            opacity:0;
            animation:contentReveal 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
            animation-play-state:paused;
            min-height:100vh;
            display:grid;
            grid-template-columns:1fr 1fr;
        }
        #login-page.visible {
            animation-play-state:running;
        }

        /* Tablet: shrink green panel */
        @media (max-width:1024px) {
            #login-page { grid-template-columns:0.8fr 1fr; }
        }

        /* Mobile: hide green panel, show only form */
        @media (max-width:768px) {
            #login-page { grid-template-columns:1fr; }
            .login-green-panel { display:none !important; }
            .login-form-panel {
                padding:2.5rem 1.5rem !important;
                min-height:100vh;
                background:white;
            }
            .login-form-panel > div { max-width:100% !important; }
        }

        /* Small phones (iPhone SE, etc.) */
        @media (max-width:390px) {
            .login-form-panel { padding:2rem 1.25rem !important; }
            .login-form-panel h1 { font-size:1.75rem !important; }
        }

        /* Show mobile branding on small screens */
        @media (max-width:768px) {
            .mobile-brand-header { display:block !important; }
        }
    </style>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Amiri:wght@400;700&display=swap');
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',sans-serif; -webkit-font-smoothing:antialiased; }

        /* Background blob morphing */
        @keyframes morphBlob1 {
            0%,100% { border-radius:60% 40% 70% 30% / 50% 60% 40% 50%; transform:translate(0,0) scale(1); }
            33%      { border-radius:40% 60% 30% 70% / 60% 40% 60% 40%; transform:translate(20px,-15px) scale(1.05); }
            66%      { border-radius:70% 30% 50% 50% / 40% 70% 30% 60%; transform:translate(-15px,20px) scale(0.97); }
        }
        @keyframes morphBlob2 {
            0%,100% { border-radius:40% 60% 50% 50% / 60% 40% 60% 40%; transform:translate(0,0) scale(1); }
            33%      { border-radius:60% 40% 40% 60% / 40% 60% 40% 60%; transform:translate(-20px,10px) scale(1.08); }
            66%      { border-radius:50% 50% 60% 40% / 50% 50% 50% 50%; transform:translate(15px,-20px) scale(0.95); }
        }
        @keyframes morphBlob3 {
            0%,100% { border-radius:50% 50% 40% 60% / 40% 60% 50% 50%; transform:translate(0,0); }
            50%      { border-radius:30% 70% 60% 40% / 60% 30% 70% 40%; transform:translate(10px,15px); }
        }

        /* Rotating geometric rings */
        @keyframes spinCW  { from{transform:translate(-50%,-50%) rotate(0deg)}   to{transform:translate(-50%,-50%) rotate(360deg)} }
        @keyframes spinCCW { from{transform:translate(-50%,-50%) rotate(0deg)}   to{transform:translate(-50%,-50%) rotate(-360deg)} }

        /* Particle drift */
        @keyframes drift1 { 0%{transform:translate(0,0) scale(1);opacity:0.6} 50%{transform:translate(30px,-40px) scale(1.2);opacity:1} 100%{transform:translate(0,0) scale(1);opacity:0.6} }
        @keyframes drift2 { 0%{transform:translate(0,0);opacity:0.4} 50%{transform:translate(-25px,35px);opacity:0.8} 100%{transform:translate(0,0);opacity:0.4} }
        @keyframes drift3 { 0%{transform:translate(0,0) scale(0.8);opacity:0.5} 50%{transform:translate(20px,25px) scale(1.1);opacity:0.9} 100%{transform:translate(0,0) scale(0.8);opacity:0.5} }
        @keyframes drift4 { 0%{transform:translate(0,0);opacity:0.3} 50%{transform:translate(-30px,-20px);opacity:0.7} 100%{transform:translate(0,0);opacity:0.3} }

        /* Shimmer sweep */
        @keyframes shimmerSweep { 0%{transform:translateX(-100%) skewX(-15deg)} 100%{transform:translateX(300%) skewX(-15deg)} }

        .blob1 { animation: morphBlob1 12s ease-in-out infinite; }
        .blob2 { animation: morphBlob2 15s ease-in-out infinite; }
        .blob3 { animation: morphBlob3 10s ease-in-out infinite; }
        .ring-cw  { animation: spinCW  25s linear infinite; }
        .ring-ccw { animation: spinCCW 18s linear infinite; }
        .ring-cw2 { animation: spinCW  40s linear infinite; }
        .p1 { animation: drift1 8s ease-in-out infinite; }
        .p2 { animation: drift2 11s ease-in-out infinite; animation-delay:1.5s; }
        .p3 { animation: drift3 9s ease-in-out infinite; animation-delay:3s; }
        .p4 { animation: drift4 13s ease-in-out infinite; animation-delay:0.8s; }
        .p5 { animation: drift1 7s ease-in-out infinite; animation-delay:4s; }
        .p6 { animation: drift2 10s ease-in-out infinite; animation-delay:2s; }
        .shimmer { animation: shimmerSweep 6s ease-in-out infinite; animation-delay:2s; }

        /* Login form entrance animations */
        @keyframes formFadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .form-enter {
            opacity: 0;
            animation: formFadeUp 0.55s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        .form-enter:nth-child(1) { animation-delay: 0.1s; }
        .form-enter:nth-child(2) { animation-delay: 0.2s; }
        .form-enter:nth-child(3) { animation-delay: 0.32s; }
        .form-enter:nth-child(4) { animation-delay: 0.44s; }
        .form-enter:nth-child(5) { animation-delay: 0.56s; }
        .form-enter:nth-child(6) { animation-delay: 0.68s; }
        .form-enter:nth-child(7) { animation-delay: 0.80s; }

        /* Green panel entrance — each group fades+slides up */
        .panel-enter {
            opacity: 0;
            animation: formFadeUp 0.7s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        .panel-enter:nth-child(1) { animation-delay: 0.1s; }
        .panel-enter:nth-child(2) { animation-delay: 0.25s; }
        .panel-enter:nth-child(3) { animation-delay: 0.4s; }
        .panel-enter:nth-child(4) { animation-delay: 0.55s; }
        .panel-enter:nth-child(5) { animation-delay: 0.7s; }

        /* Per-letter fade-up reveal — plays once on load, stays visible */
        @keyframes letterReveal {
            0%   { opacity: 0; transform: translateY(16px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .letter-reveal {
            display: inline-block;
            white-space: nowrap;
        }
        .letter-reveal span {
            display: inline-block;
            opacity: 0;
            animation: letterReveal 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        .letter-reveal .sp { display: inline-block; width: 0.3em; }
        .letter-reveal span:nth-child(1)  { animation-delay: 0.30s; }
        .letter-reveal span:nth-child(2)  { animation-delay: 0.36s; }
        .letter-reveal span:nth-child(3)  { animation-delay: 0.42s; }
        .letter-reveal span:nth-child(4)  { animation-delay: 0.48s; }
        .letter-reveal span:nth-child(5)  { animation-delay: 0.54s; }
        .letter-reveal span:nth-child(6)  { animation-delay: 0.60s; }
        .letter-reveal span:nth-child(7)  { animation-delay: 0.66s; }
        .letter-reveal span:nth-child(8)  { animation-delay: 0.72s; }
        .letter-reveal span:nth-child(9)  { animation-delay: 0.78s; }
        .letter-reveal span:nth-child(10) { animation-delay: 0.84s; }
        .letter-reveal span:nth-child(11) { animation-delay: 0.90s; }
        .letter-reveal span:nth-child(12) { animation-delay: 0.96s; }
        .letter-reveal span:nth-child(13) { animation-delay: 1.02s; }
        .letter-reveal span:nth-child(14) { animation-delay: 1.08s; }
        .letter-reveal span:nth-child(15) { animation-delay: 1.14s; }
        .letter-reveal span:nth-child(16) { animation-delay: 1.20s; }
        .letter-reveal span:nth-child(17) { animation-delay: 1.26s; }
        .letter-reveal span:nth-child(18) { animation-delay: 1.32s; }
        .letter-reveal span:nth-child(19) { animation-delay: 1.38s; }
        .letter-reveal span:nth-child(20) { animation-delay: 1.44s; }
        .letter-reveal span:nth-child(21) { animation-delay: 1.50s; }
        .letter-reveal span:nth-child(22) { animation-delay: 1.56s; }
        .letter-reveal span:nth-child(23) { animation-delay: 1.62s; }
        .letter-reveal span:nth-child(24) { animation-delay: 1.68s; }
        .letter-reveal span:nth-child(25) { animation-delay: 1.74s; }
        .letter-reveal span:nth-child(26) { animation-delay: 1.80s; }
        .letter-reveal span:nth-child(27) { animation-delay: 1.86s; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

{{-- ── INTRO SPLASH ── --}}
<div id="intro-splash">
    {{-- Dot grid bg --}}
    <div class="intro-dots-bg"></div>

    {{-- Pulsing rings --}}
    <div class="intro-ring-pulse" style="width:300px;height:300px;"></div>
    <div class="intro-ring-pulse" style="width:300px;height:300px;"></div>
    <div class="intro-ring-pulse" style="width:300px;height:300px;"></div>

    {{-- Static decorative rings --}}
    <div class="intro-ring" style="width:500px;height:500px;opacity:0.06;"></div>
    <div class="intro-ring" style="width:700px;height:700px;opacity:0.04;border-style:dashed;"></div>

    <div class="intro-content">
        <div class="intro-logo-wrap">
            <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS">
        </div>
        <div class="intro-arabic">المدرسة المنورة الإسلامية</div>
        <div class="intro-school-name">Al Munawwara Islamic School</div>
        <div class="intro-divider"></div>
        <div class="intro-portal-label">Student Portal</div>

        <div class="intro-progress-wrap">
            <div class="intro-progress-track">
                <div class="intro-progress-bar" id="intro-bar"></div>
            </div>
            <div class="intro-loading-text">Loading your portal...</div>
        </div>
    </div>
</div>

<div id="login-page">

    {{-- ── LEFT: Green branding panel ── --}}
    <div class="login-green-panel" style="background:linear-gradient(160deg,#059669 0%,#047857 60%,#065f46 100%);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem 3.5rem;color:white;position:relative;overflow:hidden;">

        {{-- ── Morphing blobs ── --}}
        <div class="blob1" style="position:absolute;width:420px;height:420px;background:radial-gradient(circle,rgba(255,255,255,0.08) 0%,transparent 70%);top:-120px;left:-100px;pointer-events:none;"></div>
        <div class="blob2" style="position:absolute;width:380px;height:380px;background:radial-gradient(circle,rgba(255,255,255,0.07) 0%,transparent 70%);bottom:-100px;right:-80px;pointer-events:none;"></div>
        <div class="blob3" style="position:absolute;width:260px;height:260px;background:radial-gradient(circle,rgba(255,255,255,0.06) 0%,transparent 70%);top:40%;left:10%;pointer-events:none;"></div>

        {{-- ── Rotating geometric rings (Islamic-inspired) ── --}}
        <div class="ring-cw" style="position:absolute;width:560px;height:560px;border:1px solid rgba(255,255,255,0.07);border-radius:50%;top:50%;left:50%;pointer-events:none;"></div>
        <div class="ring-ccw" style="position:absolute;width:420px;height:420px;border:1.5px dashed rgba(255,255,255,0.06);border-radius:50%;top:50%;left:50%;pointer-events:none;"></div>
        <div class="ring-cw2" style="position:absolute;width:680px;height:680px;border:1px dotted rgba(255,255,255,0.04);border-radius:50%;top:50%;left:50%;pointer-events:none;"></div>

        {{-- ── Geometric diamond shapes ── --}}
        <div style="position:absolute;width:80px;height:80px;border:1.5px solid rgba(255,255,255,0.12);transform:rotate(45deg);top:60px;right:60px;border-radius:6px;pointer-events:none;"></div>
        <div style="position:absolute;width:50px;height:50px;border:1px solid rgba(255,255,255,0.09);transform:rotate(45deg);top:80px;right:80px;border-radius:4px;pointer-events:none;"></div>
        <div style="position:absolute;width:60px;height:60px;border:1.5px solid rgba(255,255,255,0.1);transform:rotate(45deg);bottom:80px;left:50px;border-radius:5px;pointer-events:none;"></div>
        <div style="position:absolute;width:36px;height:36px;border:1px solid rgba(255,255,255,0.08);transform:rotate(45deg);bottom:100px;left:68px;border-radius:3px;pointer-events:none;"></div>

        {{-- ── Dot grid ── --}}
        <div style="position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,0.08) 1px,transparent 1px);background-size:32px 32px;pointer-events:none;opacity:0.6;"></div>

        {{-- ── Shimmer sweep ── --}}
        <div class="shimmer" style="position:absolute;inset:0;background:linear-gradient(105deg,transparent 40%,rgba(255,255,255,0.04) 50%,transparent 60%);pointer-events:none;"></div>

        {{-- ── Floating particles ── --}}
        <div class="p1" style="position:absolute;width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,0.35);top:20%;left:15%;pointer-events:none;"></div>
        <div class="p2" style="position:absolute;width:4px;height:4px;border-radius:50%;background:rgba(255,255,255,0.25);top:35%;right:18%;pointer-events:none;"></div>
        <div class="p3" style="position:absolute;width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,0.2);bottom:25%;left:20%;pointer-events:none;"></div>
        <div class="p4" style="position:absolute;width:5px;height:5px;border-radius:50%;background:rgba(255,255,255,0.3);bottom:40%;right:12%;pointer-events:none;"></div>
        <div class="p5" style="position:absolute;width:3px;height:3px;border-radius:50%;background:rgba(255,255,255,0.4);top:55%;left:8%;pointer-events:none;"></div>
        <div class="p6" style="position:absolute;width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,0.15);top:15%;right:30%;pointer-events:none;"></div>

        <div style="position:relative;text-align:center;width:100%;">
            {{-- Logo --}}
            <div class="panel-enter" style="margin-bottom:1.75rem;">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS"
                     style="width:160px;height:160px;object-fit:contain;display:block;margin:0 auto;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.25));">
            </div>

            {{-- School name --}}
            <div class="panel-enter" style="font-size:1.5rem;font-weight:700;opacity:1;margin-bottom:0.5rem;letter-spacing:0.01em;line-height:1.4;white-space:nowrap;">
                المدرسة المنورة الإسلامية
            </div>
            <div class="letter-reveal panel-enter" style="font-size:clamp(1.5rem,3.2vw,2.5rem);font-weight:900;letter-spacing:-0.01em;line-height:1.25;text-align:center;">
                <span>A</span><span>l</span><span class="sp"> </span><span>M</span><span>u</span><span>n</span><span>a</span><span>w</span><span>w</span><span>a</span><span>r</span><span>a</span><span class="sp"> </span><span>I</span><span>s</span><span>l</span><span>a</span><span>m</span><span>i</span><span>c</span><span class="sp"> </span><span>S</span><span>c</span><span>h</span><span>o</span><span>o</span><span>l</span>
            </div>

            {{-- Divider --}}
            <div class="panel-enter" style="width:40px;height:2px;background:rgba(255,255,255,0.35);border-radius:999px;margin:1.25rem auto;"></div>

            {{-- Portal title + tagline --}}
            <div class="panel-enter">
                <div style="font-size:1.75rem;font-weight:900;letter-spacing:-0.02em;line-height:1.1;margin-bottom:0.625rem;opacity:0.9;">
                    Student Portal
                </div>
                <div style="font-size:0.875rem;opacity:0.65;line-height:1.65;max-width:260px;margin:0 auto;">
                    Enabling our students to learn in fid dunya wal akhira
                </div>
            </div>

            {{-- Powered by --}}
            <div class="panel-enter" style="margin-top:3rem;opacity:0.6;">
                <div style="font-size:0.75rem;font-weight:500;margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.1em;">Powered by</div>
                <div style="display:flex;align-items:center;justify-content:center;gap:0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="1" y="1" width="9" height="9" fill="rgba(255,255,255,0.8)"/>
                        <rect x="11" y="1" width="9" height="9" fill="rgba(255,255,255,0.6)"/>
                        <rect x="1" y="11" width="9" height="9" fill="rgba(255,255,255,0.6)"/>
                        <rect x="11" y="11" width="9" height="9" fill="rgba(255,255,255,0.8)"/>
                    </svg>
                    <span style="font-size:0.875rem;font-weight:600;">Microsoft Azure</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Login form ── --}}
    <div class="login-form-panel" style="background:white;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem 2rem;">
        <div style="max-width:400px;width:100%;">

            {{-- Mobile-only branding header --}}
            <div class="mobile-brand-header" style="display:none;text-align:center;margin-bottom:2rem;">
                <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS"
                     style="width:80px;height:80px;object-fit:contain;margin:0 auto 1rem;display:block;filter:drop-shadow(0 2px 8px rgba(5,150,105,0.15));">
                <div style="font-size:1.25rem;font-weight:700;color:#374151;margin-bottom:0.375rem;font-family:'Amiri',serif;line-height:1.4;">المدرسة المنورة الإسلامية</div>
                <div style="font-size:1.5rem;font-weight:900;color:#059669;letter-spacing:-0.02em;line-height:1.2;margin-bottom:0.625rem;">Al Munawwara Islamic School</div>
                <div style="display:inline-block;font-size:0.8125rem;color:#059669;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;background:#ecfdf5;border:1px solid #a7f3d0;padding:0.3rem 1rem;border-radius:999px;">Student Portal</div>
            </div>

            <div class="form-enter">
                <h1 style="font-size:1.625rem;font-weight:800;color:#111827;margin-bottom:0.5rem;letter-spacing:-0.02em;line-height:1.2;">
                    Assalamualaikum!
                </h1>
                <p style="font-size:0.9375rem;color:#6b7280;margin-bottom:2rem;line-height:1.6;">
                    Sign in to access your dashboard
                </p>
            </div>

            @if ($errors->any())
                <div class="form-enter" style="padding:0.875rem 1rem;background:#fff1f2;border:1px solid #fecdd3;border-radius:10px;color:#be123c;font-size:0.9rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Microsoft button --}}
            <div class="form-enter">
            <a href="{{ route('student.microsoft.redirect') }}"
               style="width:100%;display:flex;align-items:center;justify-content:center;gap:0.75rem;padding:0.875rem;background:white;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.9375rem;font-weight:600;color:#374151;text-decoration:none;font-family:inherit;transition:border-color 0.15s,box-shadow 0.15s;"
               onmouseover="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
               onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                <svg width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1" y="1" width="9" height="9" fill="#F25022"/>
                    <rect x="11" y="1" width="9" height="9" fill="#7FBA00"/>
                    <rect x="1" y="11" width="9" height="9" fill="#00A4EF"/>
                    <rect x="11" y="11" width="9" height="9" fill="#FFB900"/>
                </svg>
                Sign in with Microsoft
            </a>
            </div>

            <p class="form-enter" style="text-align:center;font-size:0.8125rem;color:#6b7280;margin-top:1rem;line-height:1.5;">
                Use your official @amis.edu.ph Microsoft account.
            </p>

            <p class="form-enter" style="text-align:center;font-size:0.8125rem;color:#9ca3af;margin-top:2rem;">
                &copy; {{ date('Y') }} Al Munawwara Islamic School. All rights reserved.
            </p>
        </div>
    </div>

</div>

<script>
    window.addEventListener('DOMContentLoaded', function () {
        var splash = document.getElementById('intro-splash');
        var page   = document.getElementById('login-page');
        setTimeout(function () {
            splash.classList.add('hiding');
            page.classList.add('visible');
            setTimeout(function () { splash.style.display = 'none'; }, 600);
        }, 1000);
    });
</script>

</body>
</html>
