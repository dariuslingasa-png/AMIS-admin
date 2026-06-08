@php
    $isEdit = filled($book);
@endphp

@if ($errors->any())
    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700">
        <div class="flex items-center gap-2">
            <i data-lucide="circle-alert" class="h-4 w-4"></i>
            <span>Please check the highlighted fields.</span>
        </div>
        <ul class="mt-2 list-inside list-disc text-xs">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" x-data="{ uploading: false }" @submit="uploading = true">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <label class="block lg:col-span-2">
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Book Title</span>
            <input type="text" name="title" value="{{ old('title', $book?->title) }}" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-950 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="e.g. Islamic Studies Grade 4">
        </label>

        <label class="block">
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Grade Level</span>
            <select name="grade_level" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-950 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                <option value="">Select grade</option>
                @foreach ($gradeLevels as $grade)
                    <option value="{{ $grade }}" @selected(old('grade_level', $book?->grade_level) === $grade)>{{ $grade }}</option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">PDF Document</span>
            <input type="file" name="pdf_file" accept="application/pdf" @required(! $isEdit) class="mt-2 block h-12 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-black file:text-emerald-700 hover:file:bg-emerald-100">
            <span class="mt-2 block text-xs font-semibold text-slate-500">
                {{ $isEdit ? 'Leave blank to keep the current PDF.' : 'PDF only, maximum 50MB.' }}
            </span>
        </label>

        <label class="block lg:col-span-2">
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Description</span>
            <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" placeholder="Optional short description">{{ old('description', $book?->description) }}</textarea>
        </label>
    </div>

    <div class="grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
        <div>
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Publishing Status</span>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach (['published' => 'Published', 'draft' => 'Draft'] as $value => $label)
                    <label class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700">
                        <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $book?->status ?? 'published') === $value) class="h-4 w-4 accent-emerald-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <span class="text-xs font-black uppercase tracking-wider text-slate-500">Downloads</span>
            <label class="mt-3 inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700">
                <input type="checkbox" name="is_downloadable" value="1" @checked(old('is_downloadable', $book?->is_downloadable)) class="h-4 w-4 rounded accent-emerald-600">
                Enable PDF download
            </label>
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.ebook.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-sm font-black text-slate-700 transition hover:bg-slate-50">
            Cancel
        </a>
        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-black text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-75" :disabled="uploading">
            <i data-lucide="{{ $isEdit ? 'save' : 'upload-cloud' }}" class="h-4 w-4"></i>
            <span x-text="uploading ? 'Saving...' : '{{ $isEdit ? 'Save eBook' : 'Upload eBook' }}'"></span>
        </button>
    </div>
</form>
