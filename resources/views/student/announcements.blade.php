@extends('student.layout')
@section('content')
@php $toneClasses = [ 'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-100', 'sky' => 'bg-sky-50 text-sky-700 border-sky-100', 'amber' => 'bg-amber-50 text-amber-700 border-amber-100', ];
@endphp
<div>
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
<div class="px-6 py-5 sm:px-8 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
<div>
<p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 flex items-center gap-2">
<i data-lucide="megaphone" class="w-4 h-4"></i> Student Announcements
</p>

<h2 class="mt-1 text-2xl font-black text-gray-900 kid-font">Latest School Updates</h2>

<p class="mt-1 text-sm font-semibold text-gray-500">Reminders from academics, finance, and the portal team.</p>

</div>

<div class="flex items-center gap-3 text-sm">
<span class="rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 font-extrabold text-emerald-700"> {{ count($announcements) }} posts
</span>

<span class="rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 font-extrabold text-gray-700"> {{ $section?->official_name ?? 'General' }}
</span>

</div>

</div>

<div class="p-6 sm:p-8 space-y-6">
@if(count($announcements) > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
@foreach ($announcements as $announcement)
@php $tone = $toneClasses[$announcement['tone']] ?? $toneClasses['emerald']; $toneIconBg = [ 'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-200/50', 'sky' => 'bg-sky-100 text-sky-700 border-sky-200/50', 'amber' => 'bg-amber-100 text-amber-700 border-amber-200/50', ][$announcement['tone']] ?? 'bg-emerald-100 text-emerald-700 border-emerald-200/50';
@endphp
<div class="p-6 rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 flex flex-col justify-between gap-6 group ">
<div class="space-y-4">
<!-- Top Bar (Badge + Date) -->
<div class="flex items-center justify-between gap-3">
<span class="inline-flex rounded-full border px-3.5 py-1.5 text-[10px] font-extrabold uppercase {{ $tone }}"> {{ $announcement['type'] }}
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
<h4 class="font-black text-gray-900 text-base kid-font truncate group-hover:text-emerald-800 transition"> {{ $announcement['title'] }}
</h4>

<p class="text-xs font-semibold text-gray-500 leading-relaxed"> {{ $announcement['summary'] }}
</p>

</div>

</div>

</div>

<!-- Expandable/Detailed section -->
<div class="pt-4 border-t border-gray-50 flex flex-col gap-3">
<div class="flex items-center justify-between text-xs font-semibold text-gray-500">
<span class="flex items-center gap-1">
<i data-lucide="users" class="w-3.5 h-3.5 text-emerald-600"></i>
<span>Audience: <strong class="text-gray-700">{{ $announcement['audience'] }}</strong></span>

</span>

</div>

<div class="p-3 bg-gray-50/50 rounded-xl text-xs text-gray-500 font-semibold leading-relaxed"> {{ $announcement['details'] }}
</div>

</div>

</div>

@endforeach
</div>

@else
<div class="p-12 text-center bg-gray-50 border-2 border-dashed border-gray-100 rounded-3xl space-y-4">
<div class="w-16 h-16 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600">
<i data-lucide="megaphone" class="w-8 h-8"></i>
</div>

<h5 class="font-bold text-gray-800 text-lg">No Announcements</h5>

<p class="text-gray-400 text-xs font-semibold max-w-md mx-auto leading-relaxed"> There are currently no school updates posted for your section. Check back later for academic, billing, or event notifications!
</p>

</div>

@endif
</div>

</section>

</div>

@endsection 