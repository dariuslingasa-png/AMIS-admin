<x-academic.workspace-shell
    title="Academic Portal Hub"
    description="Configure interactive timetables, track faculty workload ceilings, and manage the official academic catalog from one native Admin Portal workspace."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Faculty Members', $stats['teachers'], 'users', 'bg-indigo-50 text-indigo-600'],
            ['Active Subjects', $stats['subjects'], 'book-open', 'bg-violet-50 text-violet-600'],
            ['Class Sections', $stats['sections'], 'layers', 'bg-emerald-50 text-emerald-600'],
            ['Scheduled Hours', $stats['scheduled_hours'], 'clock-3', 'bg-amber-50 text-amber-600'],
        ] as [$label, $value, $icon, $toneClasses])
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] font-black uppercase tracking-[.14em] text-slate-500">{{ $label }}</p>
                    <span class="rounded-xl p-2 {{ $toneClasses }}"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></span>
                </div>
                <p class="mt-4 text-3xl font-black tracking-tight text-slate-900">{{ $value }}</p>
                <p class="mt-1 text-xs font-medium text-slate-400">Live portal data</p>
            </article>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($departments as $department)
            @php
                $isViolet = $department['tone'] === 'violet';
                $departmentBorder = $isViolet ? 'hover:border-violet-200' : 'hover:border-indigo-200';
                $departmentGradient = $isViolet ? 'from-violet-500 to-fuchsia-500' : 'from-indigo-500 to-cyan-500';
                $departmentIcon = $isViolet ? 'bg-violet-50 text-violet-600' : 'bg-indigo-50 text-indigo-600';
                $departmentText = $isViolet ? 'group-hover:text-violet-700 text-violet-700' : 'group-hover:text-indigo-700 text-indigo-700';
            @endphp
            <a href="{{ $department['route'] }}" class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-lg {{ $departmentBorder }}">
                <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r {{ $departmentGradient }}"></div>
                <div class="flex items-start justify-between gap-4">
                    <span class="rounded-2xl p-3 {{ $departmentIcon }}"><i data-lucide="graduation-cap" class="h-7 w-7"></i></span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-600">{{ $department['sections'] }} sections</span>
                </div>
                <h2 class="mt-6 text-xl font-black text-slate-900 {{ $departmentText }}">{{ $department['title'] }}</h2>
                <p class="mt-2 text-sm font-medium leading-6 text-slate-500">{{ $department['description'] }}</p>
                <div class="mt-5 flex flex-wrap gap-1.5">
                    @foreach ($department['grades'] as $grade)
                        <span class="rounded-lg border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-600">{{ $grade }}</span>
                    @endforeach
                </div>
                <div class="mt-7 inline-flex items-center gap-2 text-xs font-black {{ $departmentText }}">Open schedule workspace <i data-lucide="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1"></i></div>
            </a>
        @endforeach
    </div>

    <section class="grid gap-4 md:grid-cols-2">
        <div class="flex items-center gap-4 rounded-2xl border {{ $unmatchedTeachers ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5">
            <i data-lucide="{{ $unmatchedTeachers ? 'triangle-alert' : 'badge-check' }}" class="h-6 w-6 {{ $unmatchedTeachers ? 'text-amber-600' : 'text-emerald-600' }}"></i>
            <div><p class="text-sm font-black text-slate-900">{{ $unmatchedTeachers }} unmatched teacher assignments</p><p class="text-xs font-medium text-slate-500">Resolve teacher records before publishing schedules.</p></div>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <i data-lucide="file-clock" class="h-6 w-6 text-indigo-600"></i>
            <div><p class="text-sm font-black text-slate-900">{{ $unpublishedSections }} draft section schedules</p><p class="text-xs font-medium text-slate-500">Publishing remains controlled per section.</p></div>
        </div>
    </section>
</x-academic.workspace-shell>
