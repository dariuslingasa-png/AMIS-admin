<x-admin-layout
    title="eBook Library"
    :breadcrumbs="[
        ['label' => 'eBook', 'href' => route('admin.ebook.index')],
        ['label' => 'Library Dashboard', 'href' => null],
    ]"
>
    <section class="overflow-hidden rounded-2xl border border-emerald-700/20 bg-gradient-to-r from-emerald-800 to-teal-950 p-6 text-white shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <span class="inline-flex rounded-full border border-emerald-400/30 bg-emerald-400/15 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-emerald-100">LMS Module</span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight">eBook Library</h1>
                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-50/90">
                    Upload PDF learning materials, assign grade access, and publish books to the public eBook catalog.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ebook.create') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-white px-4 text-sm font-black text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                    Upload eBook
                </a>
                <a href="{{ $publicCatalogUrl }}" target="_blank" rel="noopener" class="inline-flex h-11 items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-4 text-sm font-bold text-white transition hover:bg-white/15">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                    Public Catalog
                </a>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
        @foreach ([
            ['label' => 'Total Books', 'value' => number_format($stats['total']), 'icon' => 'books', 'classes' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Published', 'value' => number_format($stats['published']), 'icon' => 'badge-check', 'classes' => 'bg-teal-50 text-teal-700'],
            ['label' => 'Drafts', 'value' => number_format($stats['drafts']), 'icon' => 'file-pen-line', 'classes' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Reader Opens', 'value' => number_format($stats['views'] + $stats['streams']), 'icon' => 'activity', 'classes' => 'bg-sky-50 text-sky-700'],
            ['label' => 'PDF Storage', 'value' => $stats['storage_used'], 'icon' => 'database', 'classes' => 'bg-violet-50 text-violet-700'],
        ] as $stat)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl {{ $stat['classes'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">Live</span>
                </div>
                <p class="mt-5 text-sm font-semibold text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-3xl font-extrabold text-slate-950">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <form method="GET" action="{{ route('admin.ebook.index') }}" class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by title, author, or grade..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    </div>
                    <div class="w-36 sm:w-44">
                        <select name="grade" onchange="this.form.submit()" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 cursor-pointer">
                            <option value="">All grades</option>
                            @foreach ($gradeLevels as $grade)
                                <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-32 sm:w-36">
                        <select name="status" onchange="this.form.submit()" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 cursor-pointer">
                            <option value="">All status</option>
                            <option value="published" @selected(request('status') === 'published')>Published</option>
                            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        </select>
                    </div>
                    <div class="w-40 sm:w-44">
                        <select name="downloadable" onchange="this.form.submit()" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 cursor-pointer">
                            <option value="">All downloads</option>
                            <option value="1" @selected(request('downloadable') === '1')>Downloads Enabled</option>
                            <option value="0" @selected(request('downloadable') === '0')>Downloads Disabled</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 text-sm font-black text-white transition hover:bg-slate-800 shadow-xs cursor-pointer">
                            <i data-lucide="filter" class="h-4 w-4"></i>
                            Filter
                        </button>
                        @if(request()->filled('search') || request()->filled('grade') || request()->filled('status') || request()->filled('downloadable'))
                            <a href="{{ route('admin.ebook.index') }}" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-rose-600 transition hover:bg-rose-50 shadow-xs" title="Reset all filters">
                                <i data-lucide="x" class="h-4 w-4"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </form>

                @if(request()->filled('search') || request()->filled('grade') || request()->filled('status') || request()->filled('downloadable'))
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500">
                        <span>Filtered: <strong class="text-emerald-700 font-black">{{ $books->total() }}</strong> {{ Str::plural('book', $books->total()) }} found</span>
                        @if(request('search'))
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-600">Search: "{{ request('search') }}"</span>
                        @endif
                        @if(request('grade'))
                            <span class="rounded-md bg-emerald-50 text-emerald-700 px-2 py-0.5 border border-emerald-200">Grade: {{ request('grade') }}</span>
                        @endif
                        @if(request('status'))
                            <span class="rounded-md bg-teal-50 text-teal-700 px-2 py-0.5 border border-teal-200">Status: {{ ucfirst(request('status')) }}</span>
                        @endif
                        @if(request()->filled('downloadable'))
                            <span class="rounded-md bg-indigo-50 text-indigo-700 px-2 py-0.5 border border-indigo-200">Download: {{ request('downloadable') === '1' ? 'Enabled' : 'Disabled' }}</span>
                        @endif
                    </div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[840px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-black">Book</th>
                            <th class="px-5 py-4 font-black">Grade</th>
                            <th class="px-5 py-4 font-black">Status</th>
                            <th class="px-5 py-4 font-black">Readers</th>
                            <th class="px-5 py-4 font-black">Size</th>
                            <th class="px-5 py-4 font-black">Downloads</th>
                            <th class="px-5 py-4 font-black">Uploaded</th>
                            <th class="px-5 py-4 text-right font-black">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($books as $book)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="font-black text-slate-950">{{ $book->title }}</p>
                                    @if($book->author)
                                        <p class="text-xs font-bold text-emerald-600 mt-0.5">by {{ $book->author }}</p>
                                    @endif
                                    <p class="mt-1 max-w-xl truncate text-xs font-semibold text-slate-500">{{ $book->description ?: 'No description provided.' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $book->grade_level }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-black {{ $book->status === 'published' ? 'bg-teal-50 text-teal-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ ucfirst($book->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if($book->readers_count > 0)
                                        <button @click="$dispatch('open-readers-modal', { id: {{ $book->id }}, title: '{{ addslashes($book->title) }}' })" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700 hover:bg-emerald-100 transition">
                                            <i data-lucide="users" class="h-3 w-3"></i>
                                            {{ $book->readers_count }} Readers
                                        </button>
                                    @else
                                        <span class="text-xs font-semibold text-slate-400">0 Readers</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs font-extrabold text-slate-700">{{ $book->pdf_size }}</td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">{{ $book->is_downloadable ? 'Enabled' : 'Disabled' }}</td>
                                <td class="px-5 py-4 text-xs font-semibold text-slate-500">{{ optional($book->created_at)->format('M d, Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.ebook.download', $book) }}" class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-black text-emerald-700 transition hover:bg-emerald-100" title="Download eBook PDF">
                                            <i data-lucide="download" class="h-3.5 w-3.5"></i>
                                            Download
                                        </a>
                                        <a href="{{ route('admin.ebook.edit', $book) }}" class="inline-flex h-9 items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.ebook.destroy', $book) }}" onsubmit="return confirm('Delete {{ addslashes($book->title) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 items-center gap-1 rounded-lg border border-rose-100 bg-rose-50 px-3 text-xs font-black text-rose-700 transition hover:bg-rose-100">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                            <i data-lucide="book-open" class="h-7 w-7"></i>
                                        </span>
                                        <p class="mt-4 text-sm font-black text-slate-950">No eBooks found.</p>
                                        <a href="{{ route('admin.ebook.create') }}" class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-black text-white transition hover:bg-emerald-700">
                                            <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                                            Upload eBook
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($books->hasPages())
                <div class="border-t border-slate-100 p-5">
                    {{ $books->links() }}
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <!-- Storage Distribution (Pie Chart) -->
            @if(count($chartData) > 0)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 class="text-base font-black text-slate-950">Storage Breakdown</h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">PDF size distribution per Grade Level</p>
                    </div>
                    <div class="mt-4 flex justify-center">
                        <div id="storage-pie-chart" class="w-full"></div>
                    </div>
                </div>
            @endif

            <!-- Recent Activity -->
            <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-black text-slate-950">Recent Activity</h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Public reader access logs</p>
                    </div>
                    <span class="rounded-full bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">{{ number_format($stats['views']) }} views</span>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($recentLogs as $log)
                        <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                            <p class="truncate text-sm font-black text-slate-900">{{ $log->ebook->title ?? 'Deleted eBook' }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ ucfirst($log->action) }} by {{ $log->user->name ?? 'Public reader' }}</p>
                            <p class="mt-1 text-[11px] font-bold text-slate-400">{{ optional($log->created_at)->diffForHumans() }}</p>
                        </div>
                    @empty
                        <div class="flex min-h-[180px] flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 text-center">
                            <i data-lucide="inbox" class="h-8 w-8 text-slate-300"></i>
                            <p class="mt-3 text-sm font-bold text-slate-500">No reader activity yet.</p>
                        </div>
                    @endforelse
                </div>
            </aside>
        </div>
    </section>

    <!-- ApexCharts Script for Storage Pie Chart -->
    @if(count($chartData) > 0)
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const chartData = @json($chartData);
                
                const options = {
                    series: chartData.map(item => item.size),
                    labels: chartData.map(item => item.title),
                    chart: {
                        type: 'pie',
                        height: 340,
                        fontFamily: 'Inter, sans-serif',
                        toolbar: {
                            show: false
                        }
                    },
                    stroke: {
                        colors: ['#ffffff']
                    },
                    colors: [
                        '#0f766e', '#0d9488', '#14b8a6', '#2dd4bf', 
                        '#4f46e5', '#6366f1', '#8b5cf6', '#a855f7', 
                        '#d97706', '#f59e0b', '#10b981', '#3b82f6'
                    ],
                    legend: {
                        position: 'bottom',
                        fontSize: '11px',
                        fontWeight: 600,
                        labels: {
                            colors: '#64748b'
                        },
                        markers: {
                            radius: 12
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val, opts) {
                            return opts.w.globals.series[opts.seriesIndex].toFixed(1) + ' MB';
                        },
                        style: {
                            fontSize: '10px',
                            fontWeight: 'bold',
                            colors: ['#ffffff']
                        },
                        dropShadow: {
                            enabled: false
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return value + " MB";
                            }
                        }
                    }
                };

                const chart = new ApexCharts(document.querySelector("#storage-pie-chart"), options);
                chart.render();
            });
        </script>
    @endif

    <!-- Readers Completion Modal -->
    <div x-data="{
            open: false,
            bookId: null,
            bookTitle: '',
            readers: [],
            loading: false,
            init() {
                window.addEventListener('open-readers-modal', (e) => {
                    this.bookId = e.detail.id;
                    this.bookTitle = e.detail.title;
                    this.open = true;
                    this.fetchReaders();
                });
            },
            async fetchReaders() {
                this.loading = true;
                this.readers = [];
                try {
                    const response = await fetch(`/admin/ebook/${this.bookId}/readers`);
                    if (response.ok) {
                        this.readers = await response.json();
                    }
                } catch (error) {
                    console.error('Error fetching readers:', error);
                } finally {
                    this.loading = false;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                }
            }
         }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/55 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
    >
        <div class="mx-4 w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl" @click.outside="open = false">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900" x-text="bookTitle"></h3>
                    <p class="text-xs font-semibold text-slate-500">Users who have completed/accessed this eBook</p>
                </div>
                <button @click="open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="mt-4 max-h-[360px] overflow-y-auto">
                <div x-show="loading" class="flex flex-col items-center justify-center py-12">
                    <i data-lucide="loader-2" class="h-8 w-8 animate-spin text-emerald-600"></i>
                    <p class="mt-2 text-xs font-bold text-slate-500">Loading readers data...</p>
                </div>

                <div x-show="!loading && readers.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                    <i data-lucide="inbox" class="h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 text-sm font-bold text-slate-500">No readers found for this eBook yet.</p>
                </div>

                <div x-show="!loading && readers.length > 0" class="space-y-2">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-black">Reader</th>
                                <th class="px-4 py-3 font-black text-center">Reads</th>
                                <th class="px-4 py-3 text-right font-black">Last Accessed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                            <template x-for="(reader, idx) in readers" :key="idx">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-900" x-text="reader.name"></p>
                                        <p class="text-[10px] font-semibold text-slate-400" x-text="reader.email"></p>
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-600" x-text="reader.actions_count"></td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-500" x-text="reader.last_access"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer --}}
            <div class="mt-5 flex justify-end border-t border-slate-100 pt-4">
                <button @click="open = false" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 transition hover:bg-slate-50">
                    Close
                </button>
            </div>
        </div>
    </div>
</x-admin-layout>
