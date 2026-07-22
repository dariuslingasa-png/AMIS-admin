@props(['label', 'value' => null])

@php
    $rawValue = is_string($value) ? trim($value) : $value;
    $isEmpty = blank($rawValue) || in_array(strtoupper((string) $rawValue), ['-', 'N/A', 'NOT PROVIDED', 'NONE', 'NOT SPECIFIED'], true);
    $displayValue = $isEmpty ? 'MISSING INFO' : html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $isEmail = str_contains((string)$displayValue, '@');
    $displayValue = ($isEmail || $isEmpty) ? $displayValue : \Illuminate\Support\Str::upper($displayValue);
@endphp

<div class="detail-field">
    <dt>{{ $label }}</dt>
    <dd @class(['detail-empty' => $isEmpty])>
        @if ($isEmpty)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 text-[11px] font-black border border-rose-200/80 dark:border-rose-800/80 uppercase tracking-wider">
                <i data-lucide="alert-circle" class="h-3 w-3 text-rose-600"></i>
                <span>MISSING INFO</span>
            </span>
        @else
            {{ $displayValue }}
        @endif
    </dd>
</div>
