@props([
    'src' => null,
    'alt' => '',
    'fallbackInitials' => null,
    'size' => '40',
    'rounded' => 'rounded-lg',
    'containerClass' => '',
    'eager' => false,
])

@php
    $px = (int) $size;
    $initials = $fallbackInitials ?: collect(explode(' ', $alt))->filter()->take(2)->map(fn ($p) => \Illuminate\Support\Str::substr($p, 0, 1))->join('');
@endphp

@if ($src)
    <div class="shrink-0 overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 {{ $rounded }} {{ $containerClass }}" style="width:{{ $px }}px;height:{{ $px }}px;">
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="w-full h-full object-cover block opacity-0 transition-opacity duration-300"
            width="{{ $px }}"
            height="{{ $px }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            decoding="async"
            onload="this.classList.remove('opacity-0')"
            onerror="this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='inline-flex';"
        >
        <span class="text-xs font-extrabold uppercase {{ str_contains($containerClass, 'text-') ? '' : 'text-slate-600' }}" style="display:none">{{ $initials ?: 'NA' }}</span>
    </div>
@else
    <div class="shrink-0 overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 {{ $rounded }} {{ $containerClass }}" style="width:{{ $px }}px;height:{{ $px }}px;">
        <span class="text-xs font-extrabold uppercase {{ str_contains($containerClass, 'text-') ? '' : 'text-slate-600' }}">{{ $initials ?: 'NA' }}</span>
    </div>
@endif
