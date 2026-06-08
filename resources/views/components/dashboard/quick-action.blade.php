@props(['href', 'icon', 'label', 'meta'])

<a href="{{ $href }}" class="quick-action">
    <span class="quick-action-icon">
        <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
    </span>
    <span>
        <span class="block text-sm font-semibold text-slate-950">{{ $label }}</span>
        <span class="mt-0.5 block text-xs text-slate-500">{{ $meta }}</span>
    </span>
    <i data-lucide="arrow-up-right" class="ms-auto h-4 w-4 text-slate-400 quick-action-arrow"></i>
</a>
