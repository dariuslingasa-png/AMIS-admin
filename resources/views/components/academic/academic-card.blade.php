@props(['title' => null, 'subtitle' => null])
<div class="bg-white rounded-2xl border border-slate-200/70 p-6 shadow-xs relative overflow-hidden">
    @if ($title || $subtitle)
        <div class="mb-4">
            @if ($title)
                <h2 class="text-base font-bold text-slate-950">{{ $title }}</h2>
            @endif
            @if ($subtitle)
                <p class="text-xs text-slate-500 font-light">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <div>
        {{ $slot }}
    </div>
</div>
