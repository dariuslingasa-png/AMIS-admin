@props(['applicant', 'docKey', 'doc', 'status' => 'pending'])

@php
    $url = $doc['url'] ?? null;
    $label = $doc['label'] ?? 'Document';
    $assetUrl = \App\Support\EnrollmentStorage::url($url);
    $isPdf = $url && strtolower(pathinfo($url, PATHINFO_EXTENSION)) === 'pdf';
@endphp

<article class="group relative rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden {{ $url ? '' : 'bg-slate-50/50 border-dashed' }}">
    <div>
        {{-- Preview Box --}}
        <div class="relative w-full h-28 rounded-lg bg-slate-100 overflow-hidden flex items-center justify-center border border-slate-100">
            @if ($assetUrl && !$isPdf)
                <img src="{{ $assetUrl }}" alt="{{ $label }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer" @click="openPreview('{{ $assetUrl }}', '{{ $label }}', false)" />
            @elseif ($assetUrl && $isPdf)
                <div class="flex flex-col items-center gap-1 cursor-pointer text-rose-500 hover:text-rose-600 transition-colors p-2 text-center" @click="openPreview('{{ $assetUrl }}', '{{ $label }}', true)">
                    <i data-lucide="file-text" class="h-8 w-8"></i>
                    <span class="text-[9px] font-black tracking-wider uppercase text-slate-600">PDF Document</span>
                </div>
            @else
                <div class="flex flex-col items-center gap-1 text-slate-300 p-2 text-center">
                    <i data-lucide="upload-cloud" class="h-6 w-6"></i>
                    <span class="text-[9px] font-bold tracking-wider uppercase text-slate-400">Not Uploaded</span>
                </div>
            @endif
        </div>

        {{-- Label & Status Badge --}}
        <div class="mt-2 flex items-center justify-between gap-1">
            <h4 class="text-[11px] font-extrabold text-slate-800 truncate" title="{{ $label }}">{{ $label }}</h4>
            <span class="px-1.5 py-0.5 text-[9px] font-extrabold uppercase rounded-md tracking-wider shrink-0 {{ $status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                {{ ucfirst($status) }}
            </span>
        </div>
    </div>

    {{-- Actions --}}
    @if ($assetUrl)
        <div class="mt-2 pt-1.5 border-t border-slate-100 flex items-center justify-between text-[10px]">
            <button type="button" @click="openPreview('{{ $assetUrl }}', '{{ $label }}', {{ $isPdf ? 'true' : 'false' }})" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold rounded transition-colors flex items-center gap-1">
                <i data-lucide="eye" class="w-3 h-3"></i> Open
            </button>
            <a href="{{ $assetUrl }}" target="_blank" class="font-bold text-emerald-600 hover:underline">Full Size ↗</a>
        </div>
    @endif
</article>
