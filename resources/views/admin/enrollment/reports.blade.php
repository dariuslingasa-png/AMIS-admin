<x-admin-layout title="Enrollment Reports">
    <div class="space-y-6">
        <!-- Metrics Tracking Panel -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Enrollees -->
            <div class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Total Enrollees</span>
                        <h3 class="mt-2 text-3xl font-black tracking-tight text-slate-900">{{ number_format($summary['total']) }}</h3>
                    </div>
                    <div class="rounded-lg bg-slate-100 p-3 text-slate-700 transition-transform duration-300 group-hover:scale-110">
                        <i data-lucide="users" class="h-6 w-6"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-slate-400 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            </div>

            <!-- Face to Face -->
            <div class="group relative overflow-hidden rounded-xl border border-emerald-100 bg-white p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-600">Face-to-Face</span>
                        <h3 class="mt-2 text-3xl font-black tracking-tight text-emerald-950">{{ number_format($summary['f2f']) }}</h3>
                    </div>
                    <div class="rounded-lg bg-emerald-100 p-3 text-emerald-700 transition-transform duration-300 group-hover:scale-110">
                        <i data-lucide="school" class="h-6 w-6"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-emerald-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            </div>

            <!-- Flexible 1st Shift -->
            <div class="group relative overflow-hidden rounded-xl border border-blue-100 bg-white p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600">Flexible (1st Shift)</span>
                        <h3 class="mt-2 text-3xl font-black tracking-tight text-blue-950">{{ number_format($summary['flexible_1st']) }}</h3>
                    </div>
                    <div class="rounded-lg bg-blue-100 p-3 text-blue-700 transition-transform duration-300 group-hover:scale-110">
                        <i data-lucide="sun" class="h-6 w-6"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            </div>

            <!-- Flexible 2nd Shift -->
            <div class="group relative overflow-hidden rounded-xl border border-amber-100 bg-white p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-wider text-amber-600">Flexible (2nd Shift)</span>
                        <h3 class="mt-2 text-3xl font-black tracking-tight text-amber-950">{{ number_format($summary['flexible_2nd']) }}</h3>
                    </div>
                    <div class="rounded-lg bg-amber-100 p-3 text-amber-700 transition-transform duration-300 group-hover:scale-110">
                        <i data-lucide="moon" class="h-6 w-6"></i>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-amber-500/80 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            </div>
        </div>

        <x-card title="Enrollment Reports" subtitle="Filtered enrollment export and masters list">
            <!-- Filter Form -->
            <form method="GET" class="mb-6 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <!-- Search -->
                <label class="relative col-span-1 sm:col-span-3">
                    <span class="block text-xs font-bold text-slate-500 mb-1.5">Search</span>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-400"></i>
                        <input name="search" value="{{ request('search') }}" placeholder="Search name or email" class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                    </div>
                </label>

                <!-- Status -->
                <label class="col-span-1 sm:col-span-2">
                    <span class="block text-xs font-bold text-slate-500 mb-1.5">Status</span>
                    <select name="status" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach ($statusLabels ?? [] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <!-- Grade -->
                <label class="col-span-1 sm:col-span-2">
                    <span class="block text-xs font-bold text-slate-500 mb-1.5">Grade Level</span>
                    <select name="grade" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" onchange="this.form.submit()">
                        <option value="">All grades</option>
                        @foreach ($gradeLevels ?? [] as $grade)
                            <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                </label>

                <!-- Learning Mode -->
                <label class="col-span-1 sm:col-span-3">
                    <span class="block text-xs font-bold text-slate-500 mb-1.5">Learning Mode</span>
                    <select name="learning_mode" class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" onchange="this.form.submit()">
                        <option value="">All modes</option>
                        <option value="f2f" @selected(request('learning_mode') === 'f2f')>Face-to-Face</option>
                        <option value="flexible_1st" @selected(request('learning_mode') === 'flexible_1st')>Flexible - 1st Shift</option>
                        <option value="flexible_2nd" @selected(request('learning_mode') === 'flexible_2nd')>Flexible - 2nd Shift</option>
                    </select>
                </label>

                <!-- Reset / Export -->
                <div class="col-span-1 sm:col-span-2 flex gap-2">
                    <a href="{{ route('admin.enrollment.reports') }}" class="flex h-10 w-1/2 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold text-xs transition" title="Clear Filters">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('admin.enrollment.reports.export', request()->query()) }}" class="flex h-10 w-1/2 items-center justify-center rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-xs transition shadow-3xs" title="Export CSV">
                        <i data-lucide="download" class="h-4 w-4"></i>
                    </a>
                </div>
            </form>

            <table class="amis-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Grade</th>
                        <th>Learning Mode</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $applicant)
                        <tr>
                            <td>{{ trim(($applicant->first_name ?? '').' '.($applicant->last_name ?? '')) ?: 'Applicant' }}</td>
                            <td>{{ $applicant->user->email ?? $applicant->email ?? '-' }}</td>
                            <td>{{ $applicant->grade_level ?? '-' }}</td>
                            <td>
                                @if(empty($applicant->learning_mode))
                                    <span class="text-slate-400 font-medium">-</span>
                                @elseif($applicant->learning_mode === 'Face-to-Face')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 border border-emerald-100">
                                        <i data-lucide="school" class="h-3 w-3"></i>
                                        F2F
                                    </span>
                                @elseif(str_contains($applicant->learning_mode, '1st Shift'))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 border border-blue-100">
                                        <i data-lucide="sun" class="h-3 w-3"></i>
                                        Flex (1st)
                                    </span>
                                @elseif(str_contains($applicant->learning_mode, '2nd Shift'))
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 border border-amber-100">
                                        <i data-lucide="moon" class="h-3 w-3"></i>
                                        Flex (2nd)
                                    </span>
                                @else
                                    <span class="text-slate-600 font-medium">{{ $applicant->learning_mode }}</span>
                                @endif
                            </td>
                            <td>{{ $statusLabels[$applicant->status] ?? $applicant->status ?? '-' }}</td>
                            <td>{{ optional($applicant->created_at)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-500">No report rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $reports->links() }}</div>
        </x-card>
    </div>
</x-admin-layout>
