        <!-- Top Toolbar with Navigation & Print Commands (Hidden when printing) -->
        <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm print:hidden">
            <div class="flex items-center gap-3">
                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-800 uppercase tracking-wider">Official Document</span>
                <h1 class="text-lg font-black text-slate-900">Fee & Discount SY {{ config('services.school.year') }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-slate-800 transition">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Print Sheet (Ctrl+P)
                </button>
                <a href="{{ route('admin.finance.dashboard') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-350 bg-slate-50 px-5 py-3 text-xs font-black uppercase tracking-wider text-slate-800 hover:bg-slate-150 transition">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Finance Dashboard
                </a>
                <a href="{{ route('admin.payments.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-3 text-xs font-black uppercase tracking-wider text-white hover:bg-amber-700 transition">
                    <i data-lucide="credit-card" class="h-4 w-4"></i>
                    Payments Index
                </a>
            </div>
        </div>
