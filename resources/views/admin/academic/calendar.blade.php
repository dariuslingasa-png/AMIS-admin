<x-admin-layout title="Academic Calendar">
    <div class="analytics-page flex flex-col gap-6">
        <div class="academic-hero-banner">
            <div class="relative z-10">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-white/10 text-indigo-100 rounded-full border border-white/10 mb-3">
                    Academic Workspace
                </span>
                <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">Academic Calendar</h1>
                <p class="mt-2 text-sm md:text-base text-indigo-100 max-w-2xl font-light">
                    Track school events, grading windows, and academic deadlines.
                </p>
            </div>
        </div>

        <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-8 text-center">
            @if(empty($events))
                <i data-lucide="calendar-days" class="mx-auto h-8 w-8 text-slate-400"></i>
                <p class="mt-3 text-sm font-extrabold text-slate-700">No scheduled events found.</p>
                <p class="mt-1 text-xs font-semibold text-slate-400">Calendar events can be connected here when the academic event source is ready.</p>
            @else
                <div class="grid gap-3">
                    @foreach($events as $event)
                        <div class="rounded-xl border border-slate-150 bg-slate-50 p-4 text-left">
                            <span class="text-sm font-extrabold text-slate-900">{{ $event['title'] ?? 'Academic Event' }}</span>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $event['date'] ?? '-' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
