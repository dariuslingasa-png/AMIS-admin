<x-admin-layout
    title="Family Accounts"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Family Accounts', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner Header -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-md">
            <div class="absolute right-0 top-0 -mr-6 -mt-6 h-48 w-48 rounded-full bg-emerald-500/15 blur-3xl"></div>
            <div class="absolute left-1/3 bottom-0 -mb-10 h-60 w-60 rounded-full bg-teal-500/15 blur-3xl"></div>
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-emerald-100 rounded-full border border-white/10 backdrop-blur-xs mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Student Workspace
                </span>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Family Accounts</h1>
                <p class="mt-2 text-sm md:text-base text-emerald-100 max-w-2xl font-light">
                    Monitor aggregated billing balances and sections for grouped family structures linked to student enrollees.
                </p>
            </div>
        </div>

        <!-- Search Form -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.students.families') }}" class="relative w-full max-w-md" id="searchForm">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by parent name, parent email, child name..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                />
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </div>
            </form>
            @if(request()->filled('search'))
                <a href="{{ route('admin.students.families') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 transition">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i> Clear Search
                </a>
            @endif
        </div>

        <!-- Error Alert -->
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm flex items-start gap-3">
                <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-rose-600 mt-0.5"></i>
                <div>
                    <h4 class="font-bold text-sm">Failed to load data</h4>
                    <p class="text-xs text-rose-700 mt-0.5">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <!-- Families Table List -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm text-slate-500">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4">Family Representative</th>
                            <th class="px-6 py-4">Linked Children</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($families as $family)
                            @php
                                $children = $family->students;
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-900/50">
                                            <i data-lucide="home" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 uppercase tracking-wide">{{ $family->name }}</div>
                                            <div class="text-xs text-slate-400 mt-0.5">{{ $family->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-2.5">
                                        @foreach($children as $child)
                                            @php
                                                $studentName = $child->applicant ? strtoupper($child->applicant->first_name . ' ' . $child->applicant->last_name) : 'Student';
                                                $secName = $child->studentSection->section->name ?? 'No Section';
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="circle-user" class="h-4 w-4 text-emerald-500 shrink-0"></i>
                                                <a href="{{ route('admin.students.show', $child) }}" class="font-bold text-slate-800 hover:text-emerald-700 hover:underline transition">
                                                    {{ $studentName }}
                                                </a>
                                                <span class="text-xs text-slate-400 font-medium">— {{ $child->grade_level }} · {{ $secName }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                                            <i data-lucide="users-2" class="h-10 w-10"></i>
                                        </div>
                                        <div class="space-y-1">
                                            <h3 class="text-base font-bold text-slate-800">No family accounts found</h3>
                                            <p class="text-sm text-slate-500 max-w-sm mx-auto">We couldn't find any family records registered in the system. Check your search input or filters.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($families->hasPages())
            <div class="mt-8">
                {{ $families->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
