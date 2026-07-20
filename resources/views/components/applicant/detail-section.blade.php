@props(['title', 'icon' => 'folder', 'fields' => [], 'sectionKey' => ''])

<section class="detail-section">
    <h3 class="flex items-center justify-between w-full">
        <span class="flex items-center gap-[.65rem]">
            <span class="detail-section-icon">
                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
            </span>
            <span>{{ $title }}</span>
        </span>
        @if(!auth()->user()?->isTeacherAdminViewer() && $sectionKey)
            <button @click="openEditModal = true; editSection = '{{ $sectionKey }}'" 
                    title="Edit {{ $title }}" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-bold transition-all active:scale-95 cursor-pointer shadow-2xs"
                    type="button">
                <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                <span>Edit</span>
            </button>
        @endif
    </h3>
    <dl class="detail-grid">
        @foreach ($fields as [$label, $value])
            <x-applicant.field :label="$label" :value="$value" />
        @endforeach
    </dl>
</section>
