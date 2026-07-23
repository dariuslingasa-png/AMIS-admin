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
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col justify-between items-center p-4 sm:p-6 overflow-hidden selection:bg-emerald-600 selection:text-white">
    
    <!-- Top Spacer -->
    <div></div>

    <!-- Main Center Maintenance Section -->
    <main class="w-full max-w-xl mx-auto my-auto py-6 flex flex-col items-center justify-center text-center space-y-6">
        
        <!-- Centered Logo & Branding Header -->
        <div class="flex flex-col items-center justify-center space-y-2">
            <img src="https://admin.amis.edu.ph/images/AMIS_Logo.svg" alt="AMIS Logo" class="w-20 h-20 sm:w-24 sm:h-24 object-contain mx-auto" onerror="this.onerror=null; this.src='/images/AMIS_Logo.svg';">
            <h2 class="font-arabic text-2xl sm:text-3xl text-emerald-800 font-bold leading-none mt-1">المدرسة المنورة الإسلامية</h2>
            <h1 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">Al-Munawwara Islamic School</h1>
        </div>

        <!-- Soft Emerald Circular Background Container -->
        <div class="w-[340px] h-[340px] sm:w-[440px] sm:h-[440px] rounded-full bg-emerald-50/90 border border-emerald-100/80 flex flex-col items-center justify-center text-center p-6 sm:p-10 relative z-10 shadow-sm mx-auto">
            
            <!-- Main Text Header -->
            <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight max-w-xs sm:max-w-md">
                This site is under maintenance
            </h2>
            <p class="text-xs sm:text-base font-semibold text-slate-500 mt-3 max-w-xs sm:max-w-sm">
                We're preparing to serve you better.
            </p>

            <!-- Unplugged Power Cable Illustration in Emerald Green -->
            <div class="relative w-full max-w-xs sm:max-w-sm flex items-center justify-center mt-8">
                <!-- Cable Wire Left -->
                <div class="h-1 bg-emerald-300 rounded-full flex-1"></div>

                <!-- Plug Connectors -->
                <div class="flex items-center gap-2.5 mx-2">
                    <!-- Male Plug -->
                    <svg class="w-14 h-10 text-emerald-600" viewBox="0 0 54 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 17C2 15.3431 3.34315 14 5 14H18C19.6569 14 21 15.3431 21 17V23C21 24.6569 19.6569 26 18 26H5C3.34315 26 2 24.6569 2 23V17Z" fill="#34D399" stroke="#059669" stroke-width="2.5"/>
                        <rect x="21" y="7" width="18" height="26" rx="4" fill="#A7F3D0" stroke="#059669" stroke-width="3"/>
                        <rect x="39" y="12" width="11" height="4" rx="1.5" fill="#059669"/>
                        <rect x="39" y="24" width="11" height="4" rx="1.5" fill="#059669"/>
                    </svg>

                    <!-- Female Socket -->
                    <svg class="w-14 h-10 text-emerald-600" viewBox="0 0 54 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="7" width="20" height="26" rx="4" fill="#A7F3D0" stroke="#059669" stroke-width="3"/>
                        <path d="M22 17C22 15.3431 23.3431 14 25 14H38C39.6569 14 41 15.3431 41 17V23C41 24.6569 39.6569 26 38 26H25C23.3431 26 22 24.6569 22 23V17Z" fill="#34D399" stroke="#059669" stroke-width="2.5"/>
                        <line x1="2" y1="14" x2="6" y2="14" stroke="#059669" stroke-width="2.5" stroke-linecap="round"/>
                        <line x1="2" y1="26" x2="6" y2="26" stroke="#059669" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- Cable Wire Right -->
                <div class="h-1 bg-emerald-300 rounded-full flex-1"></div>
            </div>
        </div>
    </main>

    <!-- Bottom Contact & Copyright Footer -->
    <footer class="w-full max-w-xl mx-auto py-4 text-center space-y-2">
        <div class="flex items-center justify-center gap-4 text-xs font-semibold text-slate-500">
            <a href="mailto:info@amis.edu.ph" class="hover:text-emerald-700 transition">info@amis.edu.ph</a>
            <span>•</span>
            <a href="https://amis.edu.ph" class="hover:text-emerald-700 transition">amis.edu.ph</a>
        </div>
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
            &copy; {{ date('Y') }} Al-Munawwara Islamic School. All rights reserved.
        </p>
    </footer>

</body>
</html>
