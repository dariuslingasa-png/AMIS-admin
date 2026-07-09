@props(['status'])
@if($status === 'active' || $status === true || $status === 1 || $status === '1')
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
        Active
    </span>
@else
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
        Inactive
    </span>
@endif
