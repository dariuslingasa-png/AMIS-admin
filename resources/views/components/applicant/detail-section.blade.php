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
                    title="Edit Profile" 
                    class="p-1.5 rounded-xl hover:bg-emerald-50 dark:hover:bg-slate-800 text-slate-400 hover:text-emerald-600 transition-all active:scale-95 cursor-pointer flex items-center justify-center border border-transparent hover:border-emerald-100"
                    type="button">
                <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
            </button>
        @endif
    </h3>
    <dl class="detail-grid">
        @foreach ($fields as [$label, $value])
            <x-applicant.field :label="$label" :value="$value" />
        @endforeach
    </dl>
</section>
