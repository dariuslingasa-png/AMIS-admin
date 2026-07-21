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
    <div class="relative shrink-0 overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 {{ $rounded }} {{ $containerClass }}" style="width:{{ $px }}px;height:{{ $px }}px;">
        <div class="smart-image-skeleton"></div>
        <span class="relative text-xs font-extrabold uppercase {{ str_contains($containerClass, 'text-') ? '' : 'text-slate-600' }} z-10">{{ $initials ?: 'NA' }}</span>
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="absolute inset-0 h-full w-full object-cover block opacity-0 transition-opacity duration-300 z-20"
            width="{{ $px }}"
            height="{{ $px }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            decoding="async"
            onload="this.classList.remove('opacity-0'); if(this.previousElementSibling) this.previousElementSibling.style.display='none'; if(this.parentElement.querySelector('.smart-image-skeleton')) this.parentElement.querySelector('.smart-image-skeleton').remove();"
            onerror="this.style.display='none'; if(this.parentElement.querySelector('.smart-image-skeleton')) this.parentElement.querySelector('.smart-image-skeleton').remove();"
        >
    </div>
@else
    <div class="shrink-0 overflow-hidden bg-slate-100 flex items-center justify-center border border-slate-200 {{ $rounded }} {{ $containerClass }}" style="width:{{ $px }}px;height:{{ $px }}px;">
        <span class="text-xs font-extrabold uppercase {{ str_contains($containerClass, 'text-') ? '' : 'text-slate-600' }}">{{ $initials ?: 'NA' }}</span>
    </div>
@endif
