<x-admin-layout
    title="Upload Tracking"
    :breadcrumbs="[
        ['label' => 'eBook', 'href' => route('admin.ebook.index')],
        ['label' => 'Upload Tracking', 'href' => null],
    ]"
>
    {{-- Header Section --}}
    <section class="overflow-hidden rounded-2xl border border-emerald-700/20 bg-gradient-to-r from-emerald-800 to-teal-950 p-6 text-white shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/15 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-emerald-100">LMS Module</span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight">eBook Upload Tracking</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50/90">
                    Track uploaded eBooks per grade level. See which grades are complete and which are still missing.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ebook.tracking', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="inline-flex h-11 items-center gap-2 rounded-xl bg-emerald-600 border border-emerald-500 px-4 text-sm font-black text-white shadow-sm transition hover:bg-emerald-550">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    Generate Report
                </a>
                <a href="{{ route('admin.ebook.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-white/10 border border-white/20 px-4 text-sm font-black text-white shadow-sm transition hover:bg-white/15">
                    <i data-lucide="book-open" class="h-4 w-4"></i>
                    Back to Library
                </a>
            </div>
        </div>
    </section>

    {{-- Stats Cards --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                    <i data-lucide="layers" class="h-5 w-5"></i>
                </span>
                <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">Grades</span>
            </div>
            <p class="mt-5 text-sm font-semibold text-slate-500">Grades with eBooks</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-950">{{ $gradesWithBooks }} <span class="text-base font-bold text-slate-400">/ {{ count($gradeLevels) }}</span></p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                    <i data-lucide="book-marked" class="h-5 w-5"></i>
                </span>
                <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">All</span>
            </div>
            <p class="mt-5 text-sm font-semibold text-slate-500">Total eBooks</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-950">{{ number_format($totalBooksCount) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                    <i data-lucide="check-circle" class="h-5 w-5"></i>
                </span>
                <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">Live</span>
            </div>
            <p class="mt-5 text-sm font-semibold text-slate-500">Published eBooks</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-950">{{ number_format($publishedCount) }}</p>
        </div>
    </section>

    {{-- Search --}}
    <section class="mt-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.ebook.tracking') }}" class="flex gap-2 max-w-lg">
                <div class="relative flex-1">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by title, author, or grade level" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                </div>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 text-sm font-black text-white transition hover:bg-slate-800">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    Search
                </button>
            </form>
        </div>
    </section>

    {{-- Grade Level Groups --}}
    <section class="mt-6 space-y-4">
        @foreach ($gradeGroups as $grade => $books)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                {{-- Grade Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div class="flex items-center gap-3">
                        @if($books->isNotEmpty())
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i data-lucide="check-circle" class="h-5 w-5"></i>
                            </span>
                        @else
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-400">
                                <i data-lucide="x-circle" class="h-5 w-5"></i>
                            </span>
                        @endif
                        <div>
                            <h3 class="text-sm font-black text-slate-950">{{ $grade }}</h3>
                            <p class="text-xs font-semibold text-slate-400 mt-0.5">
                                {{ $books->count() }} {{ Str::plural('eBook', $books->count()) }} uploaded
                            </p>
                        </div>
                    </div>
                    @if($books->isNotEmpty())
                        <span class="rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-black text-emerald-700">
                            {{ $books->count() }} {{ Str::plural('Book', $books->count()) }}
                        </span>
                    @else
                        <span class="rounded-full bg-red-50 border border-red-200 px-3 py-1 text-xs font-black text-red-500">
                            No eBooks
                        </span>
                    @endif
                </div>

                {{-- Book List --}}
                @if($books->isNotEmpty())
                    <div class="grid grid-cols-1 gap-4 p-5 bg-slate-50/30 border-t border-slate-100 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($books as $book)
                            <div class="group relative flex flex-col justify-between overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                                {{-- Card Content: Icon and Title --}}
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-4 ring-emerald-50/50 transition-colors group-hover:bg-emerald-100">
                                        <i data-lucide="check" class="h-4 w-4 stroke-[3]"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-950 transition-colors line-clamp-2 leading-tight" title="{{ $book->title }}">
                                            {{ $book->title }}
                                        </h4>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                            <span class="text-xs font-semibold text-emerald-600 truncate">
                                                by {{ $book->author ?? $book->creator?->name ?? 'Unknown' }}
                                            </span>
                                            <span class="inline-flex items-center rounded-md bg-slate-50 border border-slate-200 px-1.5 py-0.5 text-[9px] font-black text-slate-400">
                                                {{ $book->grade_level }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Divider --}}
                                <div class="my-3 border-t border-slate-100"></div>

                                {{-- Card Footer: Date and Uploader Metadata --}}
                                <div class="flex items-center justify-between text-[10px] font-semibold text-slate-400">
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ $book->created_at?->format('M d, Y') }}
                                    </span>
                                    <span class="flex items-center gap-1 max-w-[130px] truncate" title="Uploaded by {{ in_array(strtoupper($book->creator?->name ?? ''), ['ADMIN', 'AMIS ADMIN']) ? 'IT STAFF MON' : ($book->creator?->name ?? 'Unknown') }}">
                                        <i data-lucide="user" class="h-3.5 w-3.5 text-slate-400"></i>
                                        {{ in_array(strtoupper($book->creator?->name ?? ''), ['ADMIN', 'AMIS ADMIN']) ? 'IT STAFF MON' : ($book->creator?->name ?? 'Unknown') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-5 py-6 text-center">
                        <p class="text-xs font-bold text-slate-400">No eBooks uploaded for this grade level yet.</p>
                    </div>
                @endif
            </div>
        @endforeach
    </section>
</x-admin-layout>
