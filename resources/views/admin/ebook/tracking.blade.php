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
                    Track which teachers or staff uploaded eBooks. Click on any row to see the list of books they uploaded.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
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
                    <i data-lucide="users" class="h-5 w-5"></i>
                </span>
                <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">Staff</span>
            </div>
            <p class="mt-5 text-sm font-semibold text-slate-500">Total Uploaders</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-950">{{ number_format($totalUploaders) }}</p>
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

    {{-- Main Filter and Table Section --}}
    <section class="mt-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <form method="GET" action="{{ route('admin.ebook.tracking') }}" class="flex gap-2 max-w-lg">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search uploader by name or email" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    </div>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 text-sm font-black text-white transition hover:bg-slate-800">
                        <i data-lucide="search" class="h-4 w-4"></i>
                        Search
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-black">Uploader</th>
                            <th class="px-5 py-4 font-black">Role</th>
                            <th class="px-5 py-4 font-black">Uploaded</th>
                            <th class="px-5 py-4 font-black">Published</th>
                            <th class="px-5 py-4 font-black">Drafts</th>
                            <th class="px-5 py-4 text-right font-black">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" x-data="{ expandedRow: null }">
                        @forelse ($uploaders as $uploader)
                            {{-- Main Row --}}
                            <tr @click="expandedRow = expandedRow === {{ $uploader->id }} ? null : {{ $uploader->id }}" 
                                class="cursor-pointer transition hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 font-extrabold text-sm uppercase shadow-sm">
                                            {{ substr($uploader->name ?? $uploader->email, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-950">{{ $uploader->name ?? 'N/A' }}</p>
                                            <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $uploader->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 capitalize">{{ $uploader->role ?? 'N/A' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-extrabold text-slate-900">{{ $uploader->ebooks_count }}</span>
                                    <span class="text-xs font-semibold text-slate-400 ml-0.5">{{ Str::plural('book', $uploader->ebooks_count) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-extrabold text-emerald-700">
                                        {{ $uploader->published_ebooks_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-extrabold text-amber-700">
                                        {{ $uploader->draft_ebooks_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-slate-600 transition shadow-sm">
                                        <i data-lucide="chevron-down" class="h-4 w-4 transition duration-200" :class="expandedRow === {{ $uploader->id }} ? 'rotate-180' : ''"></i>
                                    </button>
                                </td>
                            </tr>

                            {{-- Accordion Details Row --}}
                            <tr x-show="expandedRow === {{ $uploader->id }}" x-cloak class="bg-slate-50/50">
                                <td colspan="6" class="px-8 py-5">
                                    <div class="rounded-2xl border border-slate-200/60 bg-white p-5 shadow-sm">
                                        <h4 class="text-xs font-black uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-1.5">
                                            <i data-lucide="book-open" class="h-4 w-4 text-emerald-600"></i>
                                            eBooks uploaded by {{ $uploader->name ?? 'Unknown' }}
                                        </h4>
                                        
                                        @if($uploader->ebooks->isEmpty())
                                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                                <i data-lucide="inbox" class="h-8 w-8 text-slate-300"></i>
                                                <p class="mt-2 text-xs font-bold text-slate-400">No eBooks uploaded.</p>
                                            </div>
                                        @else
                                            <div class="overflow-hidden rounded-xl border border-slate-200/50">
                                                <table class="w-full text-left text-xs">
                                                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-400">
                                                        <tr>
                                                            <th class="px-4 py-3 font-bold">Title</th>
                                                            <th class="px-4 py-3 font-bold">Author</th>
                                                            <th class="px-4 py-3 font-bold">Grade Level</th>
                                                            <th class="px-4 py-3 font-bold">Status</th>
                                                            <th class="px-4 py-3 text-right font-bold">Date Uploaded</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                                        @foreach ($uploader->ebooks as $book)
                                                            <tr class="hover:bg-slate-50/50 transition">
                                                                <td class="px-4 py-3 font-bold text-slate-900">{{ $book->title }}</td>
                                                                <td class="px-4 py-3 font-medium text-slate-600">{{ $book->author ?? '—' }}</td>
                                                                <td class="px-4 py-3">
                                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-600">{{ $book->grade_level }}</span>
                                                                </td>
                                                                <td class="px-4 py-3">
                                                                    @if($book->status === 'published')
                                                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-700">
                                                                            <i data-lucide="check-circle" class="h-3 w-3"></i> Published
                                                                        </span>
                                                                    @else
                                                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-extrabold text-amber-600">
                                                                            <i data-lucide="file-edit" class="h-3 w-3"></i> Draft
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-right font-medium text-slate-500">
                                                                    {{ $book->created_at?->format('M d, Y') ?? 'N/A' }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                            <i data-lucide="upload-cloud" class="h-7 w-7"></i>
                                        </span>
                                        <p class="mt-4 text-sm font-black text-slate-950">No uploaders found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($uploaders->hasPages())
                <div class="border-t border-slate-100 p-5">
                    {{ $uploaders->links() }}
                </div>
            @endif
        </div>
    </section>
</x-admin-layout>
