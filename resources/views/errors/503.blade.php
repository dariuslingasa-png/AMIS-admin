<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>This site is under maintenance - Al-Munawwara Islamic School</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
<body class="bg-white text-slate-800 font-sans min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 overflow-hidden selection:bg-sky-500 selection:text-white">
    
    <!-- Top Branding Bar -->
    <header class="w-full max-w-4xl mx-auto flex items-center justify-between py-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <img src="https://admin.amis.edu.ph/images/AMIS_Logo.svg" alt="AMIS Logo" class="w-10 h-10 object-contain" onerror="this.onerror=null; this.src='/images/AMIS_Logo.svg';">
            <div>
                <h2 class="font-arabic text-lg text-emerald-800 font-bold leading-none">المدرسة المنورة الإسلامية</h2>
                <h1 class="text-[10px] font-black uppercase tracking-wider text-slate-400 mt-0.5">Al-Munawwara Islamic School</h1>
            </div>
        </div>
        <div class="flex items-center gap-4 text-xs font-semibold text-slate-500">
            <a href="mailto:info@amis.edu.ph" class="hover:text-sky-600 transition">info@amis.edu.ph</a>
            <span class="hidden sm:inline">•</span>
            <a href="https://amis.edu.ph" class="hidden sm:inline hover:text-sky-600 transition">amis.edu.ph</a>
        </div>
    </header>

    <!-- Main Center Maintenance Section -->
    <main class="w-full max-w-3xl mx-auto my-auto py-8 relative flex flex-col items-center justify-center">
        <!-- Soft Circular Background Container -->
        <div class="w-[340px] h-[340px] sm:w-[460px] sm:h-[460px] rounded-full bg-sky-50/80 border border-sky-100/60 flex flex-col items-center justify-center text-center p-6 sm:p-10 relative z-10 shadow-sm">
            
            <!-- Main Text Header -->
            <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-800 tracking-tight leading-tight max-w-xs sm:max-w-md">
                This site is under maintenance
            </h2>
            <p class="text-xs sm:text-base font-medium text-slate-500 mt-3 max-w-xs sm:max-w-sm">
                We're preparing to serve you better.
            </p>

            <!-- Unplugged Power Cable Illustration -->
            <div class="relative w-full max-w-xs sm:max-w-sm flex items-center justify-center mt-8">
                <!-- Cable Wire Left -->
                <div class="h-1 bg-sky-300 rounded-full flex-1"></div>

                <!-- Plug Connectors -->
                <div class="flex items-center gap-2.5 mx-2">
                    <!-- Male Plug -->
                    <svg class="w-14 h-10 text-sky-600" viewBox="0 0 54 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 17C2 15.3431 3.34315 14 5 14H18C19.6569 14 21 15.3431 21 17V23C21 24.6569 19.6569 26 18 26H5C3.34315 26 2 24.6569 2 23V17Z" fill="#38BDF8" stroke="#0284C7" stroke-width="2.5"/>
                        <rect x="21" y="7" width="18" height="26" rx="4" fill="#BAE6FD" stroke="#0284C7" stroke-width="3"/>
                        <rect x="39" y="12" width="11" height="4" rx="1.5" fill="#0284C7"/>
                        <rect x="39" y="24" width="11" height="4" rx="1.5" fill="#0284C7"/>
                    </svg>

                    <!-- Female Socket -->
                    <svg class="w-14 h-10 text-sky-600" viewBox="0 0 54 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="7" width="20" height="26" rx="4" fill="#BAE6FD" stroke="#0284C7" stroke-width="3"/>
                        <path d="M22 17C22 15.3431 23.3431 14 25 14H38C39.6569 14 41 15.3431 41 17V23C41 24.6569 39.6569 26 38 26H25C23.3431 26 22 24.6569 22 23V17Z" fill="#38BDF8" stroke="#0284C7" stroke-width="2.5"/>
                        <line x1="2" y1="14" x2="6" y2="14" stroke="#0284C7" stroke-width="2.5" stroke-linecap="round"/>
                        <line x1="2" y1="26" x2="6" y2="26" stroke="#0284C7" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- Cable Wire Right -->
                <div class="h-1 bg-sky-300 rounded-full flex-1"></div>
            </div>
        </div>
    </main>

    <!-- Bottom Copyright & Footer -->
    <footer class="w-full max-w-4xl mx-auto py-4 border-t border-slate-100 text-center text-xs font-semibold text-slate-400 uppercase tracking-wider">
        &copy; {{ date('Y') }} Al-Munawwara Islamic School. All rights reserved.
    </footer>

</body>
</html>
