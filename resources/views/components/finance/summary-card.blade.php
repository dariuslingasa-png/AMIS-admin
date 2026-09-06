@props(['label', 'value', 'hint' => null, 'icon' => 'circle-dollar-sign', 'href' => null, 'tone' => 'slate'])
@php
    $toneClasses = match ($tone) {
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'violet' => 'bg-violet-50 text-violet-700 border-violet-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class(['block rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition', 'hover:border-slate-300 hover:shadow-sm' => $href]) }}>
    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-black tracking-tight text-slate-950">{{ $value }}</p>@if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif</div><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border {{ $toneClasses }}"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></span></div>
</{{ $tag }}>
