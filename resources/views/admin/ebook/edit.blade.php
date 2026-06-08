<x-admin-layout
    title="Edit eBook"
    :breadcrumbs="[
        ['label' => 'eBook', 'href' => route('admin.ebook.index')],
        ['label' => 'Edit', 'href' => null],
    ]"
>
    <section class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="text-xs font-black uppercase tracking-wider text-emerald-700">eBook Library</span>
                <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Edit eBook</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $book->title }}</p>
            </div>
            <a href="{{ route('admin.ebook.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-black text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Back to Library
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @include('admin.ebook.partials.form', [
                'action' => route('admin.ebook.update', $book),
                'book' => $book,
                'gradeLevels' => $gradeLevels,
            ])
        </div>
    </section>
</x-admin-layout>
