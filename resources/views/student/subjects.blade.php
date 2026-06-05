@extends('student.layout')
@section('content')
<div>
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
<div class="px-6 py-5 sm:px-8 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
<div>
<p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 flex items-center gap-2">
<i data-lucide="book-open-check" class="w-4 h-4"></i> Registered Subjects
</p>

<h2 class="mt-1 text-2xl font-black text-gray-900 kid-font">My Subjects</h2>

<p class="mt-1 text-sm font-semibold text-gray-500">{{ $section?->official_name ?? 'General Section' }} / {{ $student->school_year }}</p>

</div>

<span class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700"> {{ $subjects->count() }} subject(s)
</span>

</div>

<div class="p-6 sm:p-8 space-y-6">
@if($subjects->isNotEmpty())
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
@foreach($subjects as $subject)
@php $subjLower = mb_strtolower($subject->subject_name); $iconName = 'file-text'; if (str_contains($subjLower, 'math')) { $iconName = 'binary'; } elseif (str_contains($subjLower, 'science')) { $iconName = 'beaker'; } elseif (str_contains($subjLower, 'english') || str_contains($subjLower, 'reading')) { $iconName = 'book-open'; } elseif (str_contains($subjLower, 'arabic') || str_contains($subjLower, 'qur') || str_contains($subjLower, 'islamic')) { $iconName = 'book'; } elseif (str_contains($subjLower, 'art') || str_contains($subjLower, 'drawing')) { $iconName = 'palette'; } elseif (str_contains($subjLower, 'pe') || str_contains($subjLower, 'physical')) { $iconName = 'activity'; } elseif (str_contains($subjLower, 'computer') || str_contains($subjLower, 'ict')) { $iconName = 'monitor'; }
@endphp
<div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm transition duration-300 flex flex-col justify-between gap-6 group ">
<div class="flex items-start gap-4">
<div class="w-12 h-12 bg-emerald-600 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-sm ">
<i data-lucide="{{ $iconName }}" class="w-6 h-6"></i>
</div>

<div class="space-y-1.5 overflow-hidden min-w-0 flex-1">
<h4 class="font-black text-gray-900 text-lg truncate kid-font group-hover:text-emerald-800 transition"> {{ $subject->subject_name }}
</h4>

<p class="text-xs font-bold text-emerald-600"> Code: SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}
</p>

<div class="flex flex-col gap-1.5 mt-2">
<p class="text-xs font-semibold text-gray-500 flex items-center gap-1.5">
<i data-lucide="user" class="w-4 h-4 text-emerald-600 shrink-0"></i>
<span>Teacher: <strong class="text-gray-700">{{ $subject->teacher_name ?: 'To Be Assigned' }}</strong></span>

</p>

<p class="text-xs font-semibold text-gray-500 flex items-center gap-1.5">
<i data-lucide="layout" class="w-4 h-4 text-emerald-600 shrink-0"></i>
<span>Section: <strong class="text-gray-700">{{ $section?->official_name ?? 'General' }}</strong></span>

</p>

</div>

</div>

</div>

<div class="pt-4 border-t border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<div>
<p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">Weekly Schedule</p>

<div class="flex items-center gap-1.5 text-xs font-extrabold text-emerald-800 mt-1 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-100/30">
<i data-lucide="clock" class="w-4 h-4 text-emerald-600 shrink-0"></i>
<span>{{ $subject->schedule ?: 'To Be Announced' }}</span>

</div>

</div>

<div>
@if($subject->ms_channel_id && $section?->ms_team_url)
<a href="{{ $section->ms_team_url }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition duration-300 shrink-0">
<i data-lucide="video" class="w-3.5 h-3.5"></i>
<span>Join Class Channel</span>

</a>

@else
<span class="rounded-full bg-gray-50 text-gray-400 border border-gray-100 px-3.5 py-1.5 text-xs font-extrabold uppercase inline-flex items-center gap-1.5">
<i data-lucide="clock" class="w-3.5 h-3.5"></i> Pending Room
</span>

@endif
</div>

</div>

</div>

@endforeach
</div>

@else
<div class="p-12 text-center bg-gray-50 border-2 border-dashed border-gray-100 rounded-3xl space-y-4">
<div class="w-16 h-16 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600">
<i data-lucide="book-open" class="w-8 h-8"></i>
</div>

<h5 class="font-bold text-gray-800 text-lg">No Subjects Registered</h5>

<p class="text-gray-400 text-xs font-semibold max-w-md mx-auto leading-relaxed"> Your subject list will appear here once your section allocation is finalized by the administrative staff.
</p>

</div>

@endif
</div>

</section>

</div>

@endsection 