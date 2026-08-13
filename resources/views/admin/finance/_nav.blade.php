<div class="mb-5 rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm lg:px-6">
    <div class="flex items-start gap-4">
        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
            <i data-lucide="{{ $icon ?? 'wallet-cards' }}" class="h-5 w-5"></i>
        </div>
        <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-emerald-700">Finance workspace</p>
            <h1 class="mt-0.5 text-2xl font-extrabold text-slate-900">{{ $title }}</h1>
            @isset($subtitle)<p class="mt-1 max-w-3xl text-sm leading-5 text-slate-500">{{ $subtitle }}</p>@endisset
        </div>
    </div>
</div>

@if (session('success'))
    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <p class="font-bold">Please fix the following:</p>
        <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
