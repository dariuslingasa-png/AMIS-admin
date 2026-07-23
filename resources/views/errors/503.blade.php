<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - Al-Munawwara Islamic School</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,700;1,400&family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        arabic: ['Amiri', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 relative overflow-hidden selection:bg-emerald-500 selection:text-white">
    <!-- Ambient Background Radial Glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-600/10 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="absolute top-1/4 right-1/4 w-[350px] h-[350px] bg-teal-500/10 rounded-full blur-[100px] pointer-events-none"></div>

    <!-- Header Spacer -->
    <div></div>

    <!-- Main Clean Maintenance Card -->
    <main class="w-full max-w-xl mx-auto z-10 my-auto">
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 backdrop-blur-2xl p-8 sm:p-10 shadow-2xl text-center space-y-6 relative overflow-hidden">
            <!-- Subtle Top Emerald Accent Line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-600"></div>

            <!-- Logo -->
            <div class="flex justify-center">
                <div class="relative group">
                    <div class="absolute -inset-1 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 opacity-30 blur-md group-hover:opacity-60 transition duration-300"></div>
                    <img src="https://admin.amis.edu.ph/images/AMIS_Logo.svg" alt="AMIS Logo" class="relative w-20 h-20 sm:w-24 sm:h-24 object-contain mx-auto filter drop-shadow-lg" onerror="this.onerror=null; this.src='/images/AMIS_Logo.svg';">
                </div>
            </div>

            <!-- Arabic & English Header -->
            <div class="space-y-1">
                <h2 class="font-arabic text-2xl sm:text-3xl text-emerald-400 font-bold leading-normal">
                    المدرسة المنورة الإسلامية
                </h2>
                <h1 class="text-xs sm:text-sm font-black uppercase tracking-[0.25em] text-slate-300">
                    Al-Munawwara Islamic School
                </h1>
            </div>

            <!-- Status Tag Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px] font-black uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                <span>System Maintenance & Upgrade</span>
            </div>

            <!-- Maintenance Messages -->
            <div class="space-y-3 pt-2">
                <h3 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                    Portal Temporarily Offline
                </h3>

                <p class="font-arabic text-sm text-slate-300 leading-relaxed dir-rtl" dir="rtl">
                    نحن نقوم حالياً بتحديث وتطوير النظام لتقديم خدمة أفضل. نعتذر عن هذا الانقطاع المؤقت وستعاود الخدمة قريباً إن شاء الله.
                </p>

                <p class="text-xs sm:text-sm text-slate-400 leading-relaxed font-normal max-w-md mx-auto">
                    We are currently performing scheduled system enhancements to improve performance and security. We apologize for any inconvenience and will be back online shortly.
                </p>
            </div>

            <!-- Animated Status Progress Bar -->
            <div class="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-400 h-full w-2/3 rounded-full animate-pulse"></div>
            </div>

            <!-- Contact & Domain Info -->
            <div class="pt-4 border-t border-slate-800/80 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-400 font-semibold">
                <a href="mailto:info@amis.edu.ph" class="hover:text-emerald-400 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>info@amis.edu.ph</span>
                </a>
                <a href="https://amis.edu.ph" class="hover:text-emerald-400 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <span>amis.edu.ph</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer Copyright -->
    <footer class="z-10 text-center py-4 text-[11px] font-semibold text-slate-500 tracking-wider uppercase">
        &copy; {{ date('Y') }} Al-Munawwara Islamic School. All rights reserved.
    </footer>
</body>
</html>
