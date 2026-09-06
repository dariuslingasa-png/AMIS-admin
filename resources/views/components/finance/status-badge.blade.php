@props(['status'])
@php
    $normalized = strtoupper(str_replace(' ', '_', (string) $status));
    [$label, $classes] = match ($normalized) {
        'APPROVED' => ['Approved', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
        'PENDING', 'PENDING_VERIFICATION', 'OCR_COMPLETED', 'UPLOADED', 'PROCESSING' => ['Pending', 'border-amber-200 bg-amber-50 text-amber-800'],
        'NEEDS_REVIEW' => ['Needs Review', 'border-orange-200 bg-orange-50 text-orange-800'],
        'REUPLOAD_REQUIRED' => ['Reupload Required', 'border-violet-200 bg-violet-50 text-violet-800'],
        'VOIDED', 'REVERSED' => ['Voided', 'border-slate-300 bg-slate-100 text-slate-700'],
        'REJECTED' => ['Rejected', 'border-rose-200 bg-rose-50 text-rose-800'],
        default => [str($normalized)->replace('_', ' ')->title(), 'border-slate-200 bg-slate-50 text-slate-700'],
    };
@endphp
<span {{ $attributes->class("inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {$classes}") }}>{{ $label }}</span>
