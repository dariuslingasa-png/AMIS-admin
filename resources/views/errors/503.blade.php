<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - Al-Munawwara Islamic School</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 selection:bg-emerald-600 selection:text-white">
    <!-- Top Spacer -->
    <div></div>

    <!-- Clean Plain Light Mode Maintenance Card -->
    <main class="w-full max-w-lg mx-auto my-auto">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm text-center space-y-6">
            <!-- Logo -->
            <div class="flex justify-center">
                <img src="https://admin.amis.edu.ph/images/AMIS_Logo.svg" alt="AMIS Logo" class="w-20 h-20 sm:w-24 sm:h-24 object-contain mx-auto" onerror="this.onerror=null; this.src='/images/AMIS_Logo.svg';">
            </div>

            <!-- Arabic & English Header -->
            <div class="space-y-1">
                <h2 class="font-arabic text-2xl sm:text-3xl text-emerald-800 font-bold leading-normal">
                    المدرسة المنورة الإسلامية
                </h2>
                <h1 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">
                    Al-Munawwara Islamic School
                </h1>
            </div>

            <!-- Status Tag Badge -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold uppercase tracking-wider">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>System Maintenance</span>
            </div>

            <!-- Maintenance Messages (English Only, No Glow) -->
            <div class="space-y-3 pt-2">
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    Portal Temporarily Offline
                </h3>

                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-medium max-w-md mx-auto">
                    We are currently performing scheduled system enhancements and upgrades to bring you an improved experience. We apologize for any inconvenience and will be back online shortly. Thank you for your patience.
                </p>
            </div>

            <!-- Simple Progress Line -->
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-600 h-full w-2/3 rounded-full"></div>
            </div>

            <!-- Contact & Domain Info -->
            <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500 font-semibold">
                <a href="mailto:info@amis.edu.ph" class="hover:text-emerald-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>info@amis.edu.ph</span>
                </a>
                <a href="https://amis.edu.ph" class="hover:text-emerald-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <span>amis.edu.ph</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer Copyright -->
    <footer class="text-center py-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">
        &copy; {{ date('Y') }} Al-Munawwara Islamic School. All rights reserved.
    </footer>
</body>
</html>
