@props(['label', 'value' => null])

@php
    $isEmpty = blank($value);
    $displayValue = $isEmpty ? 'Not provided' : html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $isEmail = str_contains($displayValue, '@');
    $displayValue = $isEmail || $isEmpty ? $displayValue : \Illuminate\Support\Str::upper($displayValue);
@endphp

<div class="detail-field">
    <dt>{{ $label }}</dt>
    <dd @class(['detail-empty' => $isEmpty])>{{ $displayValue }}</dd>
</div>
