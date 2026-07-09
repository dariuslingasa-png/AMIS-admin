@props(['title'])
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl md:text-3xl font-black tracking-tight text-slate-950">{{ $title }}</h1>
        @if ($slot->isNotEmpty())
            <div class="mt-1 text-sm text-slate-500 font-light">{{ $slot }}</div>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex items-center gap-3">{{ $actions }}</div>
    @endif
</div>
