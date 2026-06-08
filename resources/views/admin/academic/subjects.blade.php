<x-admin-layout title="Subjects Workspace">
    @php
        $grades = ['Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12'];
        $grouped = $subjects->groupBy('grade_level');
    @endphp

    <div class="analytics-page flex flex-col gap-6" x-data="{ createOpen: false, editId: null, search: '' }">
        <div class="academic-hero-banner">
            <div class="relative z-10 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-semibold text-indigo-100">
                        <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                        Academic Workspace
                    </span>
                    <h1 class="mt-3 text-2xl font-black tracking-tight text-white md:text-3xl">Subjects Directory</h1>
                    <p class="mt-2 max-w-2xl text-sm text-indigo-100 md:text-base">Create, update, archive, and monitor subject ownership across the AMIS Portal.</p>
                </div>
                <button type="button" @click="createOpen = true" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-black text-indigo-950 shadow-md transition hover:bg-indigo-50">
                    <i data-lucide="plus-circle" class="h-4 w-4 text-indigo-700"></i>
                    Create Subject
                </button>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-extrabold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-gray-150 border-t-4 border-t-emerald-500 bg-white p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Subjects</span>
                <div class="mt-3 text-3xl font-extrabold text-gray-900">{{ $subjects->count() }}</div>
            </div>
            <div class="rounded-2xl border border-gray-150 border-t-4 border-t-sky-500 bg-white p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Active</span>
                <div class="mt-3 text-3xl font-extrabold text-sky-700">{{ $subjects->where('status', 'active')->count() }}</div>
            </div>
            <div class="rounded-2xl border border-gray-150 border-t-4 border-t-amber-500 bg-white p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Inactive</span>
                <div class="mt-3 text-3xl font-extrabold text-amber-700">{{ $subjects->where('status', 'inactive')->count() }}</div>
            </div>
            <div class="rounded-2xl border border-gray-150 border-t-4 border-t-indigo-500 bg-white p-5 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Assigned Loads</span>
                <div class="mt-3 text-3xl font-extrabold text-indigo-700">{{ $subjects->sum('active_teacher_assignments_count') }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-150 bg-white p-4 shadow-xs">
            <div class="relative max-w-md">
                <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input x-model="search" type="search" placeholder="Search subject name, code, or grade" class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 pl-10 pr-4 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            @forelse($grouped as $grade => $items)
                <section class="rounded-2xl border border-gray-150 bg-white p-5 shadow-xs">
                    <div class="mb-4 flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                        <div>
                            <span class="rounded-full border border-sky-100 bg-sky-50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-sky-700">{{ $grade }}</span>
                            <h2 class="mt-2 text-base font-extrabold text-slate-900">{{ $grade }} Subjects</h2>
                        </div>
                        <span class="rounded-full border border-slate-150 bg-slate-50 px-3 py-1 text-xs font-black text-slate-600">{{ $items->count() }}</span>
                    </div>

                    <div class="grid gap-3">
                        @foreach($items as $subject)
                            <article x-show="'{{ strtolower($subject->name.' '.$subject->code.' '.$subject->grade_level) }}'.includes(search.toLowerCase())" class="rounded-xl border border-slate-150 bg-slate-50/60 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-extrabold text-slate-950">{{ $subject->name }}</h3>
                                            <x-badge color="{{ $subject->status === 'active' ? 'green' : 'gray' }}">{{ ucfirst($subject->status) }}</x-badge>
                                        </div>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $subject->description ?: 'No description added.' }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-wider text-slate-500">
                                            <span class="rounded-lg border border-slate-150 bg-white px-2.5 py-1">{{ $subject->code ?: 'No Code' }}</span>
                                            <span class="rounded-lg border border-slate-150 bg-white px-2.5 py-1">{{ $subject->school_year }}</span>
                                            <span class="rounded-lg border border-slate-150 bg-white px-2.5 py-1">{{ $subject->active_teacher_assignments_count }} Teachers</span>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <button type="button" @click="editId = {{ $subject->id }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-indigo-700" title="Edit subject">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </button>
                                        @if ($subject->status === 'active')
                                            <form method="POST" action="{{ route('admin.academic.subjects.archive', $subject) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-100 bg-white text-rose-500 hover:bg-rose-50" title="Archive subject">
                                                    <i data-lucide="archive" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.academic.subjects.restore', $subject) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-100 bg-white text-emerald-600 hover:bg-emerald-50" title="Restore subject">
                                                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div x-show="editId === {{ $subject->id }}" x-cloak class="mt-4 border-t border-slate-200 pt-4">
                                    @include('admin.academic.partials.subject-form', ['subject' => $subject, 'grades' => $grades])
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-2xl border border-gray-150 bg-white p-8 text-center text-slate-400 shadow-xs">
                    <i data-lucide="book-open" class="mx-auto mb-2 h-8 w-8"></i>
                    <p class="text-sm font-semibold">No subjects cataloged.</p>
                </div>
            @endforelse
        </div>

        <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4">
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-xl" @click.away="createOpen = false">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <h2 class="text-base font-extrabold text-slate-950">Create Subject</h2>
                    <button type="button" @click="createOpen = false" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                @include('admin.academic.partials.subject-form', ['subject' => null, 'grades' => $grades])
            </div>
        </div>
    </div>
</x-admin-layout>
