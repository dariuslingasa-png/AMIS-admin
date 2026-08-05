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

        @php
            $isPrint = request()->filled('print');
        @endphp

        <!-- Hidden Print Header -->
        <div class="hidden print:block mb-6 text-center border-b-2 border-slate-300 pb-4">
            <h1 class="uppercase tracking-tight text-slate-900 font-bold" style="font-family: Arial, sans-serif; font-size: 14px;">ENROLLEE MASTERS LIST</h1>
            <h2 class="uppercase tracking-wide text-slate-700 font-bold mt-1" style="font-family: Arial, sans-serif; font-size: 11px;">
                @if(request('grade'))
                    Grade Level: {{ request('grade') }}
                @else
                    All Grades
                @endif
                @if(request('learning_mode'))
                    | Mode: {{ request('learning_mode') === 'f2f' ? 'Face-to-Face' : (request('learning_mode') === 'flexible_1st' ? 'Flexible (1st Shift)' : 'Flexible (2nd Shift)') }}
                @endif
            </h2>
            <div class="mt-3 flex justify-center gap-6 text-slate-500 font-normal" style="font-family: Arial, sans-serif; font-size: 9px;">
                <span>Total Enrollees: {{ $summary['total'] }}</span>
                <span>Face-to-Face: {{ $summary['f2f'] }}</span>
                <span>Flexible (1st Shift): {{ $summary['flexible_1st'] }}</span>
                <span>Flexible (2nd Shift): {{ $summary['flexible_2nd'] }}</span>
            </div>
        </div>

        <x-card title="Enrollment Reports" subtitle="Filtered enrollment export and masters list">
            <x-slot:actions>
                <a href="{{ route('admin.enrollment.reports', array_merge(request()->query(), ['print' => 1])) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-slate-700 hover:bg-slate-800 text-white font-extrabold text-[11px] px-4 py-2 transition shadow-3xs cursor-pointer print:hidden uppercase tracking-wider">
                    <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                    Print / Save PDF
                </a>
            </x-slot:actions>

            <!-- Filter Form -->
            <form method="GET" class="mb-6 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end print:hidden">
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
                        @foreach (\App\Services\Admin\Enrollment\EnrollmentReviewService::FILTER_STATUS_LABELS as $value => $label)
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
                    <a href="{{ route('admin.enrollment.reports.export', request()->query()) }}" class="flex h-10 w-1/2 items-center justify-center rounded-lg bg-emerald-700 hover:bg-emerald-805 text-white font-semibold text-xs transition shadow-3xs" title="Export CSV">
                        <i data-lucide="download" class="h-4 w-4"></i>
                    </a>
                </div>
            </form>

            <div class="premium-table-wrap border border-slate-100 rounded-xl overflow-hidden print:border-slate-350 print:rounded-none">
                <table class="amis-table w-full">
                    <thead>
                        <tr>
                            <th class="w-12 px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3">Applicant</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Grade</th>
                            <th class="px-4 py-3">Learning Mode</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $startNumber = ($reports instanceof \Illuminate\Pagination\LengthAwarePaginator) 
                                ? ($reports->currentPage() - 1) * $reports->perPage() 
                                : 0;
                        @endphp
                        @forelse ($reports as $applicant)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <!-- Row Number -->
                                <td class="px-4 py-4 text-center font-bold text-slate-400 text-xs">
                                    {{ $startNumber + $loop->iteration }}
                                </td>

                                <!-- Applicant Name -->
                                <td class="px-4 py-4">
                                    <span class="font-extrabold text-slate-900 uppercase tracking-wide text-[11px]">
                                        {{ html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8') ?: 'Applicant' }}
                                    </span>
                                </td>

                                <!-- Email -->
                                <td class="px-4 py-4">
                                    <span class="font-semibold text-slate-600 text-xs">
                                        {{ $applicant->user->email ?? $applicant->email ?? '-' }}
                                    </span>
                                </td>

                                <!-- Grade -->
                                <td class="px-4 py-4">
                                    <span class="font-bold text-slate-700 text-xs">
                                        {{ $applicant->grade_abbr ?? '-' }}
                                    </span>
                                </td>

                                <!-- Learning Mode Badge -->
                                <td class="px-4 py-4">
                                    @if(empty($applicant->learning_mode))
                                        <span class="text-slate-400 font-medium text-xs">-</span>
                                    @elseif($applicant->learning_mode === 'Face-to-Face')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-extrabold text-emerald-700 border border-emerald-100">
                                            <i data-lucide="school" class="h-3 w-3"></i>
                                            F2F
                                        </span>
                                    @elseif(str_contains($applicant->learning_mode, '1st Shift'))
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-700 border border-blue-100">
                                            <i data-lucide="sun" class="h-3 w-3"></i>
                                            Flex (1st)
                                        </span>
                                    @elseif(str_contains($applicant->learning_mode, '2nd Shift'))
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-extrabold text-amber-700 border border-amber-100">
                                            <i data-lucide="moon" class="h-3 w-3"></i>
                                            Flex (2nd)
                                        </span>
                                    @else
                                        <span class="text-slate-650 font-semibold text-xs">{{ $applicant->learning_mode }}</span>
                                    @endif
                                </td>

                                <!-- Status Badge -->
                                <td class="px-4 py-4">
                                    @php
                                        $status = $applicant->status;
                                        $label = $statusLabels[$status] ?? $status;
                                    @endphp
                                    @if($status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            {{ $label }}
                                        </span>
                                    @elseif($status === 'rejected' || $status === 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-100">
                                            {{ $label }}
                                        </span>
                                    @elseif($status === 'under_review')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $label }}
                                        </span>
                                    @elseif($status === 'submitted' || $status === 'ready_for_submission')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {{ $label }}
                                        </span>
                                    @elseif($status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-50 text-amber-700 border border-amber-100">
                                            {{ $label }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-gray-50 text-gray-600 border border-gray-150">
                                            {{ $label }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Date Submitted -->
                                <td class="px-4 py-4">
                                    <span class="font-semibold text-slate-500 text-xs">
                                        {{ optional($applicant->created_at)->format('M d, Y') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    <div class="empty-state">
                                        <i data-lucide="inbox" class="h-8 w-8 text-slate-350"></i>
                                        <p class="font-semibold text-sm">No report rows found.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(!$isPrint)
                <div class="mt-4">{{ $reports->links() }}</div>
            @endif
        </x-card>
    </div>

    <!-- Print styling configuration -->
    <style>
        @media print {
            /* Hide all navigation, sidebars, dashboard links, buttons, and filters */
            #default-sidebar, 
            .admin-sidebar, 
            .admin-topbar, 
            topbar, 
            aside, 
            form, 
            nav, 
            .breadcrumbs, 
            .flash-messages, 
            footer, 
            .print\:hidden,
            .module-dashboard-link,
            .sidebar-section-container,
            .sidebar-profile-card,
            .admin-shell > a,
            [data-lucide="arrow-left"] {
                display: none !important;
            }

            /* Reset container styling for standard page layout */
            .admin-content, 
            .admin-shell, 
            body, 
            main, 
            .mx-auto,
            .space-y-6 {
                margin: 0 !important;
                padding: 0 !important;
                background: transparent !important;
                min-width: auto !important;
                width: 100% !important;
                box-shadow: none !important;
                font-family: Arial, sans-serif !important;
            }

            .admin-content {
                margin-left: 0 !important;
            }

            /* Make the hidden print block visible */
            .print\:block {
                display: block !important;
            }

            /* Remove boxes, shadows, and borders from page wraps */
            .bg-white, .amis-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                background: transparent !important;
            }

            .border-b {
                border-bottom: none !important;
            }

            /* Expand scrolled tables to print full rows without clipping */
            .premium-table-wrap {
                max-height: none !important;
                overflow: visible !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 0 !important;
            }

            .amis-table {
                border-collapse: collapse !important;
                width: 100% !important;
                font-family: Arial, sans-serif !important;
            }

            /* Table Header: 10px Bold */
            .amis-table th {
                position: static !important;
                background: #f8fafc !important;
                border-bottom: 2px solid #475569 !important;
                color: #000000 !important;
                font-family: Arial, sans-serif !important;
                font-weight: bold !important;
                font-size: 10px !important;
                padding: 6px 8px !important;
                text-transform: uppercase !important;
            }

            /* Table Content: 9px Regular */
            .amis-table td,
            .amis-table td span,
            .amis-table td span.font-extrabold,
            .amis-table td span.font-bold,
            .amis-table td span.font-semibold {
                font-family: Arial, sans-serif !important;
                font-weight: normal !important;
                font-size: 9px !important;
                color: #000000 !important;
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                text-transform: none !important;
                letter-spacing: normal !important;
            }

            /* Keep some padding on the td cells for readability */
            .amis-table td {
                border-bottom: 1px solid #e2e8f0 !important;
                padding: 6px 8px !important;
            }

            /* Remove icons from learning mode badges in print to keep it clean */
            .amis-table td svg,
            .amis-table td i,
            .amis-table td [data-lucide] {
                display: none !important;
            }

            /* Page break prevention rules for clean printing */
            .space-y-8 > div {
                page-break-inside: avoid !important;
            }
        }
    </style>

    @if($isPrint)
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        </script>
    @endif
</x-admin-layout>
