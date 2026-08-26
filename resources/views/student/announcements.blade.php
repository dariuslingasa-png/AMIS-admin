<x-student-layout title="Announcements">

@php 
    $toneClasses = [ 
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 
        'sky'     => 'bg-sky-50 text-sky-700 border-sky-200', 
        'amber'   => 'bg-amber-50 text-amber-700 border-amber-200', 
    ];
@endphp

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between rounded-2xl bg-white p-6 border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-emerald-700 font-bold text-xs uppercase tracking-wider">
                <i data-lucide="megaphone" class="h-4 w-4"></i>
                <span>School Notice Board</span>
            </div>
            <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">Latest Announcements</h2>
            <p class="text-xs font-medium text-slate-500">Official notices, reminders, and updates for students and parents.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                {{ count($announcements) }} {{ count($announcements) === 1 ? 'Notice' : 'Notices' }}
            </span>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 border border-slate-200">
                {{ $student?->grade_level ?? 'All Students' }}
            </span>
        </div>
    </div>

    <!-- Announcements Grid -->
    @if(count($announcements) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach ($announcements as $announcement)
                @php 
                    $tone = $toneClasses[$announcement['tone']] ?? $toneClasses['emerald']; 
                    $toneIconBg = [ 
                        'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 
                        'sky'     => 'bg-sky-100 text-sky-700 border-sky-200', 
                        'amber'   => 'bg-amber-100 text-amber-700 border-amber-200', 
                    ][$announcement['tone']] ?? 'bg-emerald-100 text-emerald-700 border-emerald-200';
                @endphp
                <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:shadow-md hover:border-slate-300">
                    <div class="space-y-4">
                        <!-- Top Meta -->
                        <div class="flex items-center justify-between gap-3">
                            <span class="inline-flex rounded-md border px-2.5 py-0.5 text-[10px] font-extrabold uppercase {{ $tone }}">
                                {{ $announcement['type'] }}
                            </span>
                            <span class="text-xs font-semibold text-slate-400 flex items-center gap-1.5">
                                <i data-lucide="calendar" class="h-3.5 w-3.5"></i> {{ $announcement['date'] }}
                            </span>
                        </div>

                        <!-- Title + Summary -->
                        <div class="flex items-start gap-3.5">
                            <div class="h-11 w-11 rounded-xl flex items-center justify-center shrink-0 border {{ $toneIconBg }}">
                                <i data-lucide="{{ $announcement['icon'] ?? 'megaphone' }}" class="h-5 w-5"></i>
                            </div>
                            <div class="space-y-1 min-w-0">
                                <h3 class="font-heading font-extrabold text-slate-900 text-base leading-snug">
                                    {{ $announcement['title'] }}
                                </h3>
                                <p class="text-xs font-medium text-slate-600 leading-relaxed">
                                    {{ $announcement['summary'] }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Details Section -->
                    @if(!empty($announcement['details']))
                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
                            @if(!empty($announcement['audience']))
                                <div class="text-[11px] font-semibold text-slate-500 flex items-center gap-1">
                                    <i data-lucide="users" class="h-3 w-3 text-emerald-600"></i>
                                    <span>Target: <strong class="text-slate-700">{{ $announcement['audience'] }}</strong></span>
                                </div>
                            @endif
                            <div class="p-3 bg-slate-50 rounded-xl text-xs text-slate-600 font-medium leading-relaxed">
                                {{ $announcement['details'] }}
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center">
            <i data-lucide="megaphone" class="mx-auto h-10 w-10 text-slate-300"></i>
            <h3 class="mt-3 font-heading text-base font-bold text-slate-700">No Announcements at this time</h3>
            <p class="mt-1 text-xs text-slate-500">Check back later for new school notices and updates.</p>
        </div>
    @endif
</div>

</x-student-layout>