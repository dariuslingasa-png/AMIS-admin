@props(['searchPlaceholder' => 'Search...'])
<div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-100 pb-4">
    <div class="flex items-center gap-3">
        <label class="relative">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
            <input type="search" placeholder="{{ $searchPlaceholder }}" class="table-control pl-9">
        </label>
        {{ $slot }}
    </div>
</div>
