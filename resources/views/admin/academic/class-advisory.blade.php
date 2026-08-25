<x-academic.workspace-shell
    title="Class Advisers"
    description="Review the official advisory assignments for Elementary and High School sections."
    :school-year="$schoolYear"
    :school-years="$schoolYears"
>
    @php
        $groups = [
            ['Elementary Department', $elementaryAdvisories, 'emerald'],
            ['High School Department', $highSchoolAdvisories, 'indigo'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-[10px] font-black uppercase tracking-[.14em] text-emerald-700">Elementary advisers</p>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ $elementaryAdvisories->count() }}</p>
        </article>
        <article class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <p class="text-[10px] font-black uppercase tracking-[.14em] text-indigo-700">High School advisers</p>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ $highSchoolAdvisories->count() }}</p>
        </article>
        <article class="rounded-2xl border border-violet-200 bg-violet-50 p-5">
            <p class="text-[10px] font-black uppercase tracking-[.14em] text-violet-700">Active assignments</p>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ $activeAdvisories->count() }}</p>
        </article>
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-[10px] font-black uppercase tracking-[.14em] text-amber-700">Available teachers</p>
            <p class="mt-3 text-3xl font-black text-slate-900">{{ $teacherOptions->count() }}</p>
        </article>
    </div>

    @foreach ($groups as [$departmentTitle, $departmentRows, $tone])
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <header class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p @class([
                        'text-[10px] font-black uppercase tracking-[.14em]',
                        'text-emerald-600' => $tone === 'emerald',
                        'text-indigo-600' => $tone === 'indigo',
                    ])>Official advisory registry</p>
                    <h2 class="mt-1 text-base font-black text-slate-900">{{ $departmentTitle }}</h2>
                </div>
                <span @class([
                    'w-fit rounded-full border px-3 py-1 text-[10px] font-black uppercase',
                    'border-emerald-200 bg-emerald-50 text-emerald-700' => $tone === 'emerald',
                    'border-indigo-200 bg-indigo-50 text-indigo-700' => $tone === 'indigo',
                ])>{{ $departmentRows->count() }} advisers</span>
            </header>

            @if ($departmentRows->isEmpty())
                <div class="px-6 py-14 text-center">
                    <i data-lucide="contact-round" class="mx-auto h-9 w-9 text-slate-300"></i>
                    <p class="mt-3 text-sm font-bold text-slate-500">No advisory assignments found.</p>
                </div>
            @else
                <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($departmentRows as $row)
                        @php
                            $teacherName = trim((string) ($row['teacher'] ?? 'Unassigned'));
                            $displayName = preg_replace('/^TEACHER\s+/i', '', $teacherName);
                            $initials = collect(preg_split('/\s+/', $displayName))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('');
                        @endphp
                        <article @class([
                            'group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-md',
                            'hover:border-emerald-200' => $tone === 'emerald',
                            'hover:border-indigo-200' => $tone === 'indigo',
                        ])>
                            <div class="flex items-start gap-4">
                                <div @class([
                                    'flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-sm font-black',
                                    'bg-emerald-50 text-emerald-700' => $tone === 'emerald',
                                    'bg-indigo-50 text-indigo-700' => $tone === 'indigo',
                                ])>
                                    @if (!empty($row['photo']))
                                        <img src="{{ asset($row['photo']) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        {{ $initials ?: '—' }}
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="truncate text-base font-black text-slate-900">{{ $displayName }}</h3>
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-[9px] font-black uppercase text-emerald-700">Assigned</span>
                                    </div>
                                    <p @class([
                                        'mt-1 text-xs font-bold',
                                        'text-emerald-700' => $tone === 'emerald',
                                        'text-indigo-700' => $tone === 'indigo',
                                    ])>{{ $row['grade'] ?? 'Grade level' }}</p>
                                    <p class="mt-2 text-sm font-semibold leading-5 text-slate-500">{{ $row['grade_level'] ?? 'Section not specified' }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
</x-academic.workspace-shell>
