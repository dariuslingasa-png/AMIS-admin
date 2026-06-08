@extends('student.layout', ['heading' => 'Announcements'])

@section('content')
@php 
    $toneClasses = [ 
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100', 
        'sky' => 'bg-sky-50 text-sky-700 border-sky-100', 
        'amber' => 'bg-amber-50 text-amber-700 border-amber-100', 
    ];
@endphp

<section class="student-panel">
    <div class="student-panel-header">
        <div>
            <h2>Latest School Updates</h2>
            <span>Reminders from academics, finance, and the portal team.</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="student-status-pill">{{ count($announcements) }} posts</span>
            <span class="student-status-pill bg-gray-50 text-gray-500 border-gray-200">{{ $section?->official_name ?? 'General' }}</span>
        </div>
    </div>

    <div class="pt-6">
        @if(count($announcements) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($announcements as $announcement)
                    @php 
                        $tone = $toneClasses[$announcement['tone']] ?? $toneClasses['emerald']; 
                        $toneIconBg = [ 
                            'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-200/50', 
                            'sky' => 'bg-sky-100 text-sky-700 border-sky-200/50', 
                            'amber' => 'bg-amber-100 text-amber-700 border-amber-200/50', 
                        ][$announcement['tone']] ?? 'bg-emerald-100 text-emerald-700 border-emerald-200/50';
                    @endphp
                    <div class="student-subject-card flex flex-col justify-between gap-6 group">
                        <div class="space-y-4">
                            <!-- Top Bar (Badge + Date) -->
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex rounded-full border px-3.5 py-1.5 text-[10px] font-extrabold uppercase {{ $tone }}">
                                    {{ $announcement['type'] }}
                                </span>
                                <span class="text-xs font-bold text-gray-400 flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i> {{ $announcement['date'] }}
                                </span>
                            </div>

                            <!-- Title + Summary -->
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 border {{ $toneIconBg }}">
                                    <i data-lucide="{{ $announcement['icon'] }}" class="w-5 h-5"></i>
                                </div>
                                <div class="space-y-1 min-w-0">
                                    <h3 class="font-extrabold text-gray-900 text-base truncate group-hover:text-emerald-700 transition" style="margin: 0;">
                                        {{ $announcement['title'] }}
                                    </h3>
                                    <p class="text-xs font-semibold text-gray-500 leading-relaxed">
                                        {{ $announcement['summary'] }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Expandable/Detailed section -->
                        <div class="pt-4 border-t border-gray-150 flex flex-col gap-3">
                            <div class="flex items-center justify-between text-xs font-semibold text-gray-500">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="users" class="w-3.5 h-3.5 text-emerald-600"></i>
                                    <span>Audience: <strong class="text-gray-700 font-bold">{{ $announcement['audience'] }}</strong></span>
                                </span>
                            </div>
                            <div class="p-3 bg-gray-50/50 rounded-xl text-xs text-gray-500 font-semibold leading-relaxed">
                                {{ $announcement['details'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="dash-empty">
                <i data-lucide="megaphone"></i>
                <p>No announcements yet</p>
            </div>
        @endif
    </div>
</section>
@endsection