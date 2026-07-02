<!-- Dashboard Header / Banner -->
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-md">
    <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
    <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
    
    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Students Workspace
            </span>
            <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Students Dashboard</h1>
            <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                Monitor student admissions, active learning modes, classroom capacity allocations, and Microsoft 365 AD sync coverage.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-950 font-black text-sm px-5 py-2.5 rounded-xl transition-all duration-150 shadow-md hover:scale-[1.02] cursor-pointer">
                <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                Student Records
            </a>
            <a href="{{ route('admin.students.accounts') }}" class="inline-flex items-center gap-2 border border-white/20 bg-white/10 px-5 py-2.5 rounded-xl text-white hover:bg-white/15 active:bg-white/20 transition-all duration-150 text-sm font-black hover:scale-[1.02] cursor-pointer shadow-sm shadow-indigo-950/10">
                <i data-lucide="user-cog" class="w-4 h-4"></i>
                Account Onboarding
            </a>
            <a href="{{ route('admin.students.reports') }}" class="inline-flex items-center gap-2 border border-white/20 bg-white/10 px-5 py-2.5 rounded-xl text-white hover:bg-white/15 active:bg-white/20 transition-all duration-150 text-sm font-black hover:scale-[1.02] cursor-pointer shadow-sm shadow-indigo-950/10">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Reports & Analytics
            </a>
        </div>
    </div>
</div>
