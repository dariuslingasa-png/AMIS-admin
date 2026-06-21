@props([
    'src' => null,
    'alt' => '',
    'class' => '',
    'containerClass' => '',
    'eager' => true,
])

<div class="relative w-full h-full overflow-hidden flex items-center justify-center bg-slate-50 {{ $containerClass }}">
    @if ($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            class="w-full h-full block {{ $class ?: 'object-cover' }}"
            loading="{{ $eager ? 'eager' : 'lazy' }}"
            decoding="{{ $eager ? 'async' : 'auto' }}"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
        >
        <span class="absolute inset-0 flex-col items-center justify-center gap-2 bg-slate-50 text-slate-400 text-xs font-bold" style="display:none">
            <i data-lucide="image-off" class="h-8 w-8"></i>
            <span>Image unavailable</span>
        </span>
    @else
        <span class="flex flex-col items-center justify-center gap-2 text-slate-400 text-xs font-bold">
            <i data-lucide="image-off" class="h-8 w-8"></i>
            <span>No image</span>
        </span>
    @endif
</div>
