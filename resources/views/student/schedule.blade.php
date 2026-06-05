@extends('student.layout')
@section('content')
@php // Day grouping and parsing logic $days = [ 'Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 'Thursday' => [], 'Friday' => [] ]; $unscheduled = []; foreach ($subjects as $subj) { $sched = trim((string) $subj->schedule); if (empty($sched)) { $unscheduled[] = [ 'subject' => $subj, 'time' => 'To Be Announced' ]; continue; } $parts = explode(' ', $sched); $dayPart = $parts[0] ?? ''; // e.g., "M/W/F", "T/Th", "F" $isM = str_contains($dayPart, 'M'); $isW = str_contains($dayPart, 'W'); $isF = str_contains($dayPart, 'F'); $isTh = str_contains($dayPart, 'Th'); // Tuesday check (contains 'T' but not as part of 'Th') $cleanT = str_replace('Th', '', $dayPart); $isT = str_contains($cleanT, 'T'); // Clean up time part (e.g., "9:00 AM - 10:00 AM") $timePart = trim(str_replace($dayPart, '', $sched)); if (empty($timePart)) { $timePart = $sched; } $subjData = [ 'subject' => $subj, 'time' => $timePart ]; $matched = false; if ($isM) { $days['Monday'][] = $subjData; $matched = true; } if ($isT) { $days['Tuesday'][] = $subjData; $matched = true; } if ($isW) { $days['Wednesday'][] = $subjData; $matched = true; } if ($isTh) { $days['Thursday'][] = $subjData; $matched = true; } if ($isF) { $days['Friday'][] = $subjData; $matched = true; } if (!$matched) { $unscheduled[] = $subjData; } }
@endphp
<div class="space-y-8" x-data="{ currentTab: 'grid' }">
<!-- Top Summary Banner -->
<div class="bg-white border border-gray-100 rounded-2xl p-8 text-gray-900 shadow-sm flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
<div class="space-y-2">
<span class="bg-emerald-50 text-emerald-700 font-extrabold text-xs uppercase tracking-wider px-3.5 py-1.5 rounded-full border border-emerald-100/30 flex items-center gap-1.5 w-max">
<i data-lucide="calendar" class="w-3.5 h-3.5 text-emerald-600"></i> Student Timetable
</span>

<h2 class="text-3xl font-black text-gray-950 kid-font"> My Schedule & Subjects
</h2>

<p class="text-gray-500 text-sm font-semibold"> View your weekly class schedule, teachers, and connect to live virtual classrooms.
</p>

</div>

<div class="flex gap-4">
<!-- Subjects Count Box -->
<div class="bg-emerald-50/45 border border-emerald-100/40 p-4.5 rounded-3xl text-center min-w-[150px]">
<p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Enrolled Subjects</p>

<p class="text-2xl font-black text-gray-900 mt-1"> {{ $subjects->count() }}
</p>

</div>

<!-- Active Section Box -->
<div class="bg-emerald-50/45 border border-emerald-100/40 p-4.5 rounded-3xl text-center min-w-[150px]">
<p class="text-[10px] text-emerald-800 font-bold uppercase tracking-wider">Class Section</p>

<p class="text-base font-black text-emerald-700 mt-1.5 truncate max-w-[130px]" title="{{ $section ? $section->section_title : 'General' }}"> {{ $section ? $section->official_name : 'General' }}
</p>

</div>

</div>

</div>

<!-- Interactive Navigation Tab Control -->
<div class="flex items-center justify-between border-b border-gray-200 pb-2">
<div class="flex gap-2 bg-gray-100 p-1 rounded-2xl">
<button @click="currentTab = 'grid'" :class="currentTab === 'grid' ? 'bg-white text-emerald-800 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-800'" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition duration-300 flex items-center gap-2">
<i data-lucide="layout-grid" class="w-4 h-4"></i>
<span>Weekly Timetable</span>

</button>

<button @click="currentTab = 'list'" :class="currentTab === 'list' ? 'bg-white text-emerald-800 shadow-sm font-bold' : 'text-gray-500 hover:text-gray-800'" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition duration-300 flex items-center gap-2">
<i data-lucide="list" class="w-4 h-4"></i>
<span>Detailed Subject List</span>

</button>

</div>

<div class="hidden sm:block text-xs font-bold text-gray-400"> School Year {{ $student->school_year }}
</div>

</div>

<!-- Tab Contents -->
<div>
<!-- Tab 1: Weekly Grid Timetable -->
<div x-show="currentTab === 'grid'" class="space-y-6">
@if($subjects->isNotEmpty()) <!-- Columns Grid for Mon-Fri -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-start">
@foreach($days as $dayName => $dayClasses)
<div class="bg-white border border-gray-100 rounded-3xl p-4 shadow-sm space-y-4">
<!-- Day Header -->
<div class="border-b border-gray-50 pb-2 flex items-center justify-between">
<h4 class="font-black text-gray-800 text-sm kid-font uppercase tracking-wider"> {{ $dayName }}
</h4>

<span class="bg-emerald-50 text-emerald-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-emerald-100/30"> {{ count($dayClasses) }}
</span>

</div>

<!-- Day Subjects List -->
<div class="space-y-3">
@if(count($dayClasses) > 0)
@foreach($dayClasses as $cls)
@php $subj = $cls['subject']; $subjLower = mb_strtolower($subj->subject_name); $iconName = 'file-text'; $accentColor = 'bg-emerald-50 text-emerald-700 border-emerald-100/40'; if (str_contains($subjLower, 'math')) { $iconName = 'binary'; } elseif (str_contains($subjLower, 'science')) { $iconName = 'beaker'; } elseif (str_contains($subjLower, 'english')) { $iconName = 'book-open'; } elseif (str_contains($subjLower, 'arabic') || str_contains($subjLower, 'qur') || str_contains($subjLower, 'islamic')) { $iconName = 'book'; } elseif (str_contains($subjLower, 'computer') || str_contains($subjLower, 'ict')) { $iconName = 'monitor'; } elseif (str_contains($subjLower, 'pe') || str_contains($subjLower, 'physical')) { $iconName = 'activity'; }
@endphp
<div class="p-3.5 rounded-2xl border border-gray-50 bg-gray-50/20 hover:bg-emerald-50/30 hover:border-emerald-100/40 transition duration-300 group">
<div class="flex items-center gap-2 mb-1.5">
<div class="w-7 h-7 rounded-lg bg-emerald-100/60 text-emerald-700 flex items-center justify-center shrink-0">
<i data-lucide="{{ $iconName }}" class="w-4 h-4"></i>
</div>

<h5 class="font-extrabold text-xs text-gray-900 group-hover:text-emerald-900 transition leading-snug truncate" title="{{ $subj->subject_name }}"> {{ $subj->subject_name }}
</h5>

</div>

<p class="text-[10px] text-gray-400 font-semibold truncate flex items-center gap-1">
<i data-lucide="user" class="w-3 h-3 text-emerald-600 shrink-0"></i> {{ $subj->teacher_name ?: 'TBA' }}
</p>

<div class="mt-2 text-[9px] font-black text-emerald-700 bg-emerald-50/70 border border-emerald-100/30 px-2 py-0.5 rounded-lg flex items-center gap-1 w-max">
<i data-lucide="clock" class="w-3 h-3 text-emerald-600 shrink-0"></i>
<span>{{ $cls['time'] }}</span>

</div>

</div>

@endforeach
@else <!-- No classes today fallback -->
<div class="py-8 text-center text-gray-300 font-semibold text-xs border border-dashed border-gray-100 rounded-2xl"> No Classes
</div>

@endif
</div>

</div>

@endforeach
</div>

@if(count($unscheduled) > 0) <!-- Unscheduled Subjects or Special schedules -->
<div class="bg-white border border-gray-100 rounded-3xl p-6 shadow-sm space-y-4">
<h4 class="font-black text-gray-800 text-base kid-font flex items-center gap-2">
<i data-lucide="help-circle" class="w-5 h-5 text-emerald-600"></i> Special / Unscheduled Subjects
</h4>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
@foreach($unscheduled as $u)
<div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/30 flex items-center gap-4">
<div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
<i data-lucide="book-open" class="w-5 h-5"></i>
</div>

<div>
<h5 class="font-extrabold text-sm text-gray-900 leading-snug"> {{ $u['subject']->subject_name }}
</h5>

<p class="text-xs font-semibold text-gray-400 mt-0.5"> Teacher: {{ $u['subject']->teacher_name ?: 'To Be Assigned' }}
</p>

<span class="text-[10px] font-bold text-emerald-700 bg-emerald-50/60 px-2 py-0.5 rounded-md mt-1.5 inline-block"> {{ $u['time'] }}
</span>

</div>

</div>

@endforeach
</div>

</div>

@endif
@else <!-- Class Roster Empty Fallback -->
<div class="bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-sm space-y-4">
<div class="w-16 h-16 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600">
<i data-lucide="calendar" class="w-8 h-8"></i>
</div>

<h5 class="font-bold text-gray-800 text-lg">Your Schedule is Empty</h5>

<p class="text-gray-400 text-xs font-semibold max-w-md mx-auto leading-relaxed"> The administrative staff is finalizing your section assignment and class timetable. You'll see your daily subject schedules displayed here shortly!
</p>

</div>

@endif
</div>

<!-- Tab 2: Detailed Subject List View -->
<div x-show="currentTab === 'list'" class="space-y-6">
@if($subjects->isNotEmpty())
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
@foreach($subjects as $subj)
@php $subjLower = mb_strtolower($subj->subject_name); $iconName = 'file-text'; if (str_contains($subjLower, 'math')) { $iconName = 'binary'; } elseif (str_contains($subjLower, 'science')) { $iconName = 'beaker'; } elseif (str_contains($subjLower, 'english') || str_contains($subjLower, 'reading')) { $iconName = 'book-open'; } elseif (str_contains($subjLower, 'arabic') || str_contains($subjLower, 'qur') || str_contains($subjLower, 'islamic')) { $iconName = 'book'; } elseif (str_contains($subjLower, 'art') || str_contains($subjLower, 'drawing')) { $iconName = 'palette'; } elseif (str_contains($subjLower, 'pe') || str_contains($subjLower, 'physical')) { $iconName = 'activity'; } elseif (str_contains($subjLower, 'computer') || str_contains($subjLower, 'ict')) { $iconName = 'monitor'; }
@endphp
<div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm transition duration-300 flex flex-col justify-between gap-6">
<div class="flex items-start gap-4">
<div class="w-12 h-12 bg-emerald-600 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-sm">
<i data-lucide="{{ $iconName }}" class="w-6 h-6"></i>
</div>

<div class="space-y-1 overflow-hidden">
<h4 class="font-black text-gray-900 text-lg truncate kid-font"> {{ $subj->subject_name }}
</h4>

<p class="text-sm font-semibold text-gray-500 flex items-center gap-1.5">
<i data-lucide="user" class="w-4 h-4 text-emerald-600"></i>
<span>Primary Teacher: {{ $subj->teacher_name ?: 'To Be Assigned' }}</span>

</p>

<p class="text-xs font-semibold text-gray-400 flex items-center gap-1.5 mt-1">
<i data-lucide="hash" class="w-4 h-4 text-emerald-600"></i>
<span>Subject ID: SUB-{{ str_pad($subj->id, 4, '0', STR_PAD_LEFT) }}</span>

</p>

</div>

</div>

<div class="pt-4 border-t border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
<div>
<p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Weekly Schedule</p>

<div class="flex items-center gap-1.5 text-xs font-extrabold text-emerald-800 mt-1 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-100/30">
<i data-lucide="clock" class="w-4 h-4 text-emerald-600 shrink-0"></i>
<span>{{ $subj->schedule ?: 'To Be Announced' }}</span>

</div>

</div>

<div>
@if($subj->ms_channel_id)
<a href="{{ $section->ms_team_url ?? 'https://teams.microsoft.com/' }}" target="_blank" class="inline-flex items-center gap-2 px-4.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition duration-300 shrink-0">
<i data-lucide="video" class="w-3.5 h-3.5"></i>
<span>Join Class Channel</span>

</a>

@else
<span class="text-[10px] font-bold text-gray-400 bg-gray-50 px-3 py-2 rounded-xl"> Live room unavailable
</span>

@endif
</div>

</div>

</div>

@endforeach
</div>

@else <!-- Class Roster Empty Fallback -->
<div class="bg-white border border-gray-100 rounded-2xl p-12 text-center shadow-sm space-y-4">
<div class="w-16 h-16 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600">
<i data-lucide="book-open" class="w-8 h-8"></i>
</div>

<h5 class="font-bold text-gray-800 text-lg">No Subjects Enrolled</h5>

<p class="text-gray-400 text-xs font-semibold max-w-md mx-auto leading-relaxed"> There are currently no subjects listed for your account. Please consult the registrar or your section adviser to resolve enrollment.
</p>

</div>

@endif
</div>

</div>

</div>

@endsection
