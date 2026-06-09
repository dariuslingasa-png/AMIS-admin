@php
    $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
@endphp

<x-admin-layout title="Enrollee Masters List">
    <div class="space-y-6">
        <!-- Metrics Tracking Panel -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 print:hidden">
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

        <!-- Grade Level Summary Cards with Type of Learning -->
        <div class="bg-white rounded-2xl border border-slate-200/70 p-6 shadow-3xs print:hidden">
            <div class="mb-4">
                <h2 class="text-base font-black text-slate-900 tracking-tight">Grade Level & Type of Learning Groupings</h2>
                <p class="text-xs text-slate-500 mt-1">Select a grade title or specific learning mode under it to view the filtered list of enrollees.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                @foreach ($gradeSummaries ?? [] as $gradeName => $counts)
                    @php
                        $isGradeActive = request('grade') === $gradeName;
                        $hasEnrollees = $counts['total'] > 0;
                    @endphp
                    <div class="rounded-xl border p-4 transition-all duration-200 {{ $isGradeActive ? 'border-emerald-500 bg-emerald-50/25 shadow-xs ring-1 ring-emerald-500/25' : ($hasEnrollees ? 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-2xs' : 'border-slate-100 bg-slate-50/50 opacity-60') }}">
                        <!-- Grade Title & Total -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                            <a href="{{ route('admin.enrollment.masters-list', array_merge(request()->query(), ['grade' => $gradeName, 'learning_mode' => ''])) }}" class="group flex items-center gap-1 font-bold text-slate-800 hover:text-emerald-700 transition text-xs uppercase tracking-wider">
                                <span>{{ $gradeName }}</span>
                                <i data-lucide="arrow-right" class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity text-emerald-600"></i>
                            </a>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-black text-slate-600 uppercase tracking-wide">
                                {{ $counts['total'] }}
                            </span>
                        </div>

                        <!-- Learning Modes Links -->
                        <div class="space-y-1.5">
                            <!-- Face to Face -->
                            @php
                                $isF2fActive = $isGradeActive && request('learning_mode') === 'f2f';
                            @endphp
                            <a href="{{ route('admin.enrollment.masters-list', array_merge(request()->query(), ['grade' => $gradeName, 'learning_mode' => 'f2f'])) }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[10px] font-extrabold border transition {{ $isF2fActive ? 'bg-emerald-600 border-emerald-600 text-white shadow-xs' : 'bg-slate-50 border-slate-100 text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center gap-1 uppercase tracking-wider">
                                    <i data-lucide="school" class="h-3 w-3"></i>
                                    F2F
                                </span>
                                <span class="font-black {{ $isF2fActive ? 'text-white' : 'text-slate-500' }}">{{ $counts['f2f'] }}</span>
                            </a>

                            <!-- Flex 1st Shift -->
                            @php
                                $isFlex1Active = $isGradeActive && request('learning_mode') === 'flexible_1st';
                            @endphp
                            <a href="{{ route('admin.enrollment.masters-list', array_merge(request()->query(), ['grade' => $gradeName, 'learning_mode' => 'flexible_1st'])) }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[10px] font-extrabold border transition {{ $isFlex1Active ? 'bg-blue-600 border-blue-600 text-white shadow-xs' : 'bg-slate-50 border-slate-100 text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center gap-1 uppercase tracking-wider">
                                    <i data-lucide="sun" class="h-3 w-3"></i>
                                    Flex (1st)
                                </span>
                                <span class="font-black {{ $isFlex1Active ? 'text-white' : 'text-slate-500' }}">{{ $counts['flexible_1st'] }}</span>
                            </a>

                            <!-- Flex 2nd Shift -->
                            @php
                                $isFlex2Active = $isGradeActive && request('learning_mode') === 'flexible_2nd';
                            @endphp
                            <a href="{{ route('admin.enrollment.masters-list', array_merge(request()->query(), ['grade' => $gradeName, 'learning_mode' => 'flexible_2nd'])) }}" 
                               class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-[10px] font-extrabold border transition {{ $isFlex2Active ? 'bg-amber-600 border-amber-600 text-white shadow-xs' : 'bg-slate-50 border-slate-100 text-slate-700 hover:bg-slate-100' }}">
                                <span class="flex items-center gap-1 uppercase tracking-wider">
                                    <i data-lucide="moon" class="h-3 w-3"></i>
                                    Flex (2nd)
                                </span>
                                <span class="font-black {{ $isFlex2Active ? 'text-white' : 'text-slate-500' }}">{{ $counts['flexible_2nd'] }}</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/70 p-6 shadow-3xs space-y-6 print:border-none print:shadow-none print:p-0">
            <div class="print:hidden">
                <h2 class="text-base font-black text-slate-900 tracking-tight">Enrollee Search & Filters</h2>
            </div>
            <!-- Filter Form (Horizontal Row layout matching applicants registry) -->
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3 print:hidden">
                <!-- Keep workspace tracking if present -->
                @if(request()->filled('workspace'))
                    <input type="hidden" name="workspace" value="{{ request('workspace') }}">
                @endif

                <!-- Search -->
                <label class="relative col-span-4">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search name or email" class="{{ $inputClass }} w-full pl-9">
                </label>

                <!-- Status -->
                <select name="status" class="{{ $inputClass }} col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statusLabels ?? [] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <!-- Grade -->
                <select name="grade" class="{{ $inputClass }} col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All grades</option>
                    @foreach ($gradeLevels ?? [] as $grade)
                        <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>

                <!-- Learning Mode -->
                <select name="learning_mode" class="{{ $inputClass }} col-span-2 w-full" onchange="this.form.submit()">
                    <option value="">All modes</option>
                    <option value="f2f" @selected(request('learning_mode') === 'f2f')>Face-to-Face</option>
                    <option value="flexible_1st" @selected(request('learning_mode') === 'flexible_1st')>Flexible - 1st Shift</option>
                    <option value="flexible_2nd" @selected(request('learning_mode') === 'flexible_2nd')>Flexible - 2nd Shift</option>
                </select>

                <!-- Reset / Export -->
                <div class="col-span-2 flex gap-2">
                    <a href="{{ route('admin.enrollment.masters-list', request()->filled('workspace') ? ['workspace' => request('workspace')] : []) }}" class="flex h-11 w-1/2 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold transition" title="Clear Filters">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                    <a href="{{ route('admin.enrollment.masters-list.export', request()->query()) }}" class="flex h-11 w-1/2 items-center justify-center rounded-lg bg-emerald-700 hover:bg-emerald-805 text-white font-semibold transition shadow-3xs" title="Export CSV">
                        <i data-lucide="download" class="h-4 w-4"></i>
                    </a>
                </div>
            </form>

            <!-- Conditional List Displays -->
            @if ($isGradeFocused)
                <!-- Hidden Print Header -->
                <div class="hidden print:block mb-8 text-center border-b-2 border-slate-300 pb-5">
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase">ENROLLEE MASTERS LIST</h1>
                    <h2 class="text-base font-extrabold text-slate-700 uppercase tracking-wide mt-2">
                        Grade Level: {{ request('grade') }}
                    </h2>
                    <div class="mt-4 flex justify-center gap-6 text-xs font-bold text-slate-500">
                        <span>Total Enrollees: {{ $summary['total'] }}</span>
                        <span>Face-to-Face: {{ $summary['f2f'] }}</span>
                        <span>Flexible (1st Shift): {{ $summary['flexible_1st'] }}</span>
                        <span>Flexible (2nd Shift): {{ $summary['flexible_2nd'] }}</span>
                    </div>
                </div>

                <div class="space-y-4 pt-4 border-t border-slate-100 print:border-none print:pt-0">
                    <div class="flex items-center justify-between print:hidden mb-2">
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                            Active Grade Focus: {{ request('grade') }} ({{ $reports->count() }} total)
                        </h3>
                        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-[11px] px-4 py-2 transition shadow-3xs cursor-pointer uppercase tracking-wider">
                            <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                            Print / Save PDF
                        </button>
                    </div>

                    <div class="premium-table-wrap border border-slate-100 rounded-xl overflow-hidden print:border-slate-350 print:rounded-none">
                        @include('admin.enrollment.partials.masters-table', ['applicants' => $reports])
                    </div>
                </div>
            @else
                <!-- Show the standard global flat paginated list -->
                <div class="premium-table-wrap border border-slate-100 rounded-xl overflow-hidden">
                    @include('admin.enrollment.partials.masters-table', ['applicants' => $reports])
                </div>
                <div class="mt-4">{{ $reports->links() }}</div>
            @endif
        </div>
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
            }

            .admin-content {
                margin-left: 0 !important;
            }

            /* Make the hidden print block visible */
            .print\:block {
                display: block !important;
            }

            /* Remove boxes, shadows, and borders from page wraps */
            .bg-white {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }

            /* Expand scrolled tables to print full rows without clipping */
            .premium-table-wrap {
                max-height: none !important;
                overflow: visible !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 0 !important;
            }

            .premium-table {
                border-collapse: collapse !important;
                width: 100% !important;
            }

            .premium-table th {
                position: static !important;
                background: #f1f5f9 !important;
                border-bottom: 2px solid #94a3b8 !important;
                color: #0f172a !important;
                font-weight: 800 !important;
                font-size: 10px !important;
                padding: 6px 8px !important;
            }

            .premium-table td {
                border-bottom: 1px solid #e2e8f0 !important;
                color: #000000 !important;
                font-size: 11px !important;
                padding: 8px !important;
            }

            /* Page break prevention rules for clean printing */
            .space-y-8 > div {
                page-break-inside: avoid !important;
            }
        }
    </style>
</x-admin-layout>
