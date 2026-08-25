@props(['title', 'description', 'schoolYear', 'schoolYears' => collect([$schoolYear])])

<x-admin-layout :title="$title">
    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-indigo-100 bg-white shadow-sm">
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
            <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between lg:p-7">
                <div>
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-[.16em] text-indigo-700">AMIS Academic Portal</span>
                        <label class="no-print inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold text-slate-600">
                            <i data-lucide="calendar-days" class="h-3.5 w-3.5 text-indigo-500"></i>
                            <span class="sr-only">Academic year</span>
                            <select
                                aria-label="Academic year"
                                class="cursor-pointer border-0 bg-transparent p-0 pr-5 text-[10px] font-black text-slate-700 outline-none focus:ring-0"
                                onchange="const target = new URL(window.location.href); target.searchParams.set('school_year', this.value); window.location.assign(target.toString())"
                            >
                                @foreach ($schoolYears as $year)
                                    <option value="{{ $year }}" @selected($schoolYear === $year)>SY {{ str_replace('-', '–', $year) }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl">{{ $title }}</h1>
                    <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-slate-500">{{ $description }}</p>
                </div>
                @isset($actions)
                    <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
                @endisset
            </div>
            <nav class="no-print flex gap-1 overflow-x-auto border-t border-slate-100 bg-slate-50/80 px-3 py-2" aria-label="Academic workspace">
                @php
                    $links = [
                        ['Dashboard', 'layout-dashboard', 'admin.academic.dashboard', request()->routeIs('admin.academic.dashboard*')],
                        ['SY '.str_replace('-', '–', $schoolYear).' Schedule', 'calendar-clock', 'admin.academic.schedule-copy', request()->routeIs('admin.academic.schedule-copy')],
                        ['Schedule Builder', 'calendar-range', 'admin.academic.builder', request()->routeIs('admin.academic.builder') || request()->routeIs('admin.academic.schedules')],
                        ['Teachers', 'user-check', 'admin.academic.teachers', request()->routeIs('admin.academic.teachers*')],
                        ['Subjects', 'book-open', 'admin.academic.subjects', request()->routeIs('admin.academic.subjects*')],
                        ['Sections', 'layers', 'admin.academic.sections', request()->routeIs('admin.academic.sections*')],
                        ['Rooms', 'school', 'admin.academic.rooms', request()->routeIs('admin.academic.rooms*')],
                        ['Workload', 'chart-pie', 'admin.academic.workload', request()->routeIs('admin.academic.workload')],
                        ['Reports', 'file-chart-column', 'admin.academic.reports', request()->routeIs('admin.academic.reports*')],
                    ];
                @endphp
                @foreach ($links as [$label, $icon, $routeName, $active])
                    <a href="{{ route($routeName, ['school_year' => $schoolYear]) }}" class="inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold transition {{ $active ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-white hover:text-indigo-700' }}">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $label }}
                    </a>
                @endforeach
            </nav>
        </section>

        {{ $slot }}
    </div>
</x-admin-layout>
