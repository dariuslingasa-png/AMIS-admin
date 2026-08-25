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
        </section>

        {{ $slot }}
    </div>
</x-admin-layout>
