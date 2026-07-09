@props(['cancelRoute'])
<div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
    <a href="{{ $cancelRoute }}" class="btn-secondary">
        Cancel
    </a>
    <button type="submit" class="btn-primary">
        Save Changes
    </button>
</div>
