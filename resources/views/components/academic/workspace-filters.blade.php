@props(['placeholder' => 'Search...', 'grades' => false, 'status' => false])

<form method="GET" class="no-print flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center">
    <input type="hidden" name="school_year" value="{{ $schoolYear }}">
    <label class="relative min-w-0 flex-1">
        <span class="sr-only">Search</span>
        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
        <input name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm font-medium outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-100">
    </label>
    @if ($grades)
        <select name="grade" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none focus:border-indigo-400">
            <option value="">All grade levels</option>
            @foreach ($gradeOptions as $grade)
                <option value="{{ $grade }}" @selected(request('grade') === $grade)>{{ $grade }}</option>
            @endforeach
        </select>
    @endif
    @if ($status)
        <select name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none focus:border-indigo-400">
            <option value="">All statuses</option>
            <option value="active" @selected(strtolower((string) request('status')) === 'active')>Active</option>
            <option value="inactive" @selected(strtolower((string) request('status')) === 'inactive')>Inactive</option>
        </select>
    @endif
    {{ $slot }}
    <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
        <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>Apply
    </button>
    @if (request()->query())
        <a href="{{ url()->current() }}?school_year={{ urlencode($schoolYear) }}" class="inline-flex items-center justify-center rounded-xl px-3 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-100">Reset</a>
    @endif
</form>
