<!-- Preview Modal Portal -->
<template x-teleport="body">
    <div x-show="preview" class="preview-modal" x-cloak>
        <button type="button" class="preview-backdrop" style="cursor: default;"></button>
        <div class="preview-panel">
            <div class="preview-head gap-3">
                <strong x-text="label"></strong>
                <div class="ml-auto flex items-center gap-2">
                    <div class="flex items-center gap-2" x-show="!pdf && label !== '2x2 Photo'">
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomOut()">-</button>
                        <span class="min-w-14 rounded-full bg-slate-100 px-3 py-1 text-center text-xs font-black text-slate-700" x-text="Math.round(zoom * 100) + '%'"></span>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-100" @click="zoomIn()">+</button>
                        <button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-slate-500 shadow-sm transition hover:bg-slate-100" @click="resetZoom()">Reset</button>
                    </div>
                    <template x-if="label === '2x2 Photo' || label === '2x2 Photo ID'">
                        <button type="button" class="rounded-full border border-emerald-600 bg-emerald-600 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-white shadow-sm transition hover:bg-emerald-700 flex items-center gap-1.5 cursor-pointer" @click="closePreview(); openPhotoOptionsModal();">
                            <i data-lucide="camera" class="h-3.5 w-3.5"></i> Replace / Edit Photo
                        </button>
                    </template>
                    <button id="download-pdf-btn" type="button" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.14em] text-emerald-700 shadow-sm transition hover:bg-emerald-100 flex items-center gap-1 cursor-pointer" @click="downloadPdf()">
                        <i data-lucide="download" class="h-3.5 w-3.5"></i> Download PDF
                    </button>
                    <button type="button" class="text-2xl leading-none text-slate-500" @click="closePreview()">&times;</button>
                </div>
            </div>
            <div class="preview-body cursor-grab select-none overflow-auto"
                 @mousedown="startPan($event)"
                 @mousemove="movePan($event)"
                 @mouseleave="stopPan()"
                 @touchstart.passive="startPan($event)"
                 @touchmove="movePan($event)">
                <template x-if="!pdf">
                    <img :src="src" 
                         :alt="label" 
                         class="transition-all duration-150" 
                         :class="label === '2x2 Photo' ? 'rounded-xl shadow-lg border-4 border-white' : ''" 
                         :style="label === '2x2 Photo' 
                             ? 'margin: auto; max-width: 280px; max-height: 280px; width: auto; height: auto; object-fit: contain;' 
                             : 'max-width: none; width: ' + (zoom * 100) + '%; height: auto;'">
                </template>
                <template x-if="pdf"><iframe :src="src"></iframe></template>
            </div>
        </div>
    </div>
</template>

<!-- Sync Loading Modal -->
<div id="sync-loading-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/60 backdrop-blur-md transition-all duration-300">
    <div class="relative w-full max-w-md scale-95 transform rounded-2xl border border-slate-200/80 bg-white p-8 shadow-2xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900 text-center">
        <!-- Spinner -->
        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-950/30">
            <svg class="h-8 w-8 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        
        <!-- Text -->
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Syncing Student Account</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Updating status, teams enrollment, and Microsoft license for this student. Please wait...</p>
        
        <!-- Progress bar simulation -->
        <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
            <div class="h-full rounded-full bg-emerald-600 animate-[loading-bar_2s_infinite_ease-in-out]" style="width: 30%"></div>
        </div>
    </div>
</div>

<style>
@keyframes loading-bar {
    0% { transform: translateX(-100%); width: 30%; }
    50% { width: 60%; }
    100% { transform: translateX(350%); width: 30%; }
}
</style>

<script>
document.querySelectorAll('form').forEach(form => {
    if (form.action.includes('ms-sync/students')) {
        form.addEventListener('submit', function() {
            document.getElementById('sync-loading-modal').classList.remove('hidden');
        });
    }
});
</script>
