<div class="mb-6 relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 sm:p-8 text-white shadow-md">
    <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
    <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Finance Workspace
            </span>
            <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">{{ $title }}</h1>
            @isset($subtitle)
                <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light leading-relaxed">{{ $subtitle }}</p>
            @endisset
        </div>
        @if(isset($actions))
            <div class="flex items-center gap-2.5 shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-900 shadow-xs flex items-center gap-2.5">
        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if (isset($errors) && $errors->any())
    <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-xs flex items-start gap-3">
        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5"></i>
        <div>
            <p class="font-bold text-sm">Please fix the following issues:</p>
            <ul class="mt-1 list-disc pl-5 text-xs text-rose-800 space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
@endif
