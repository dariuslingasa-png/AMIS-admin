<section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
    <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Finance</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950">{{ $title }}</h1>
            @isset($subtitle)
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{{ $subtitle }}</p>
            @endisset
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @if($showSearch ?? true)
        <div class="bg-slate-50/70 px-4 py-3 sm:px-5">
            <form method="GET" action="{{ route('admin.finance.families.index') }}" class="flex flex-col gap-2 sm:flex-row" role="search">
                <label for="finance-global-search" class="sr-only">Search Finance records</label>
                <div class="relative min-w-0 flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input id="finance-global-search" name="q" value="{{ request()->routeIs('admin.finance.families.*') ? request('q') : '' }}" placeholder="Search official student, parent, student ID, OR number, or reference number..." class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20">
                </div>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">Find Student</button>
            </form>
        </div>
    @endif
</section>

@if (session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950" role="status">
        <i data-lucide="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"></i><span class="font-semibold">{{ session('success') }}</span>
    </div>
@endif
@if (isset($errors) && $errors->any())
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-950" role="alert">
        <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0 text-rose-700"></i>
        <div><p class="text-sm font-bold">Please review the following:</p><ul class="mt-1 list-disc space-y-1 pl-5 text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    </div>
@endif
