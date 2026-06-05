@extends('student.layout')
@section('content')
<div>
<section class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
<div class="px-6 py-6 sm:px-8 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
<div>
<p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 flex items-center gap-2">
<i data-lucide="chart-no-axes-combined" class="w-4 h-4"></i> Academic Records
</p>

<h2 class="mt-1 text-2xl font-black text-gray-900 kid-font">My Grades</h2>

<p class="mt-1 text-sm font-semibold text-gray-500">Grade status for {{ $student->school_year }}. Official marks appear once released by teachers.</p>

</div>

<div class="flex items-center gap-2">
<span class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-2 text-xs font-extrabold text-emerald-800">{{ $student->grade_level ?: 'Grade pending' }}</span>

<span class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-2 text-xs font-extrabold text-amber-700">Pending release</span>

</div>

</div>

@if($subjects->isNotEmpty())
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead class="bg-emerald-50/20 text-[11px] font-extrabold text-gray-500 uppercase tracking-wider border-b border-emerald-100/30">
<tr>
<th class="px-6 py-4">Subject</th>

<th class="px-6 py-4">Teacher</th>

<th class="px-6 py-4 text-center">Q1</th>

<th class="px-6 py-4 text-center">Q2</th>

<th class="px-6 py-4 text-center">Q3</th>

<th class="px-6 py-4 text-center">Q4</th>

<th class="px-6 py-4 text-center">Final</th>

<th class="px-6 py-4">Remarks</th>

</tr>

</thead>

<tbody class="divide-y divide-gray-50 text-sm">
@foreach($subjects as $subject)
<tr class="hover:bg-emerald-50/15 transition duration-200">
<td class="px-6 py-5">
<span class="font-black text-gray-955 kid-font">{{ $subject->subject_name }}</span>

<span class="block text-[10px] font-bold text-emerald-600 mt-0.5">SUB-{{ str_pad($subject->id, 4, '0', STR_PAD_LEFT) }}</span>

</td>

<td class="px-6 py-5 font-semibold text-gray-600">{{ $subject->teacher_name ?: 'To Be Assigned' }}</td>

@foreach(range(1, 5) as $column)
<td class="px-6 py-5 text-center">
<span class="inline-flex items-center justify-center min-w-10 rounded-lg bg-gray-50 text-gray-400 border border-gray-100 px-2.5 py-1.5 text-xs font-black">--</span>

</td>

@endforeach
<td class="px-6 py-5">
<span class="rounded-full bg-amber-50 text-amber-700 border border-amber-100 px-3.5 py-1.5 text-[10px] font-extrabold uppercase inline-flex items-center gap-1">
<i data-lucide="clock" class="w-3.5 h-3.5"></i> Not posted
</span>

</td>

</tr>

@endforeach
</tbody>

</table>

</div>

@else
<div class="p-12 text-center bg-gray-50 border-2 border-dashed border-gray-100 rounded-3xl space-y-4 m-6 sm:m-8">
<div class="w-16 h-16 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600">
<i data-lucide="file-question" class="w-8 h-8"></i>
</div>

<h5 class="font-bold text-gray-800 text-lg">No Subjects Registered</h5>

<p class="text-gray-400 text-xs font-semibold max-w-md mx-auto leading-relaxed"> Grades will be grouped by subject and displayed here after section assignment is finalized.
</p>

</div>

@endif
</section>

</div>

@endsection 