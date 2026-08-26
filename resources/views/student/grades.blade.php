<x-student-layout title="Grades">

<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center gap-2">
                    <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                    <span>Academic Records</span>
                </div>
                <h2 class="mt-1 font-heading text-2xl font-black text-slate-900">My Grades</h2>
                <p class="text-xs font-medium text-slate-500">
                    Grade records for SY {{ $student?->school_year ?? '2026-2027' }}. Official quarter marks appear once published by subject teachers.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1 text-xs font-extrabold text-emerald-800">
                    {{ $student?->grade_level ?: 'Grade 1' }}
                </span>
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1 text-xs font-extrabold text-amber-700">
                    Q1 In Progress
                </span>
            </div>
        </div>

        @if($subjects->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead class="bg-slate-50 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Teacher</th>
                            <th class="px-6 py-4 text-center">Q1</th>
                            <th class="px-6 py-4 text-center">Q2</th>
                            <th class="px-6 py-4 text-center">Q3</th>
                            <th class="px-6 py-4 text-center">Q4</th>
                            <th class="px-6 py-4 text-center">Final</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($subjects as $subject)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $subject->subject_name }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-600">
                                    {{ $subject->teacher_name ?: 'Assigned Faculty' }}
                                </td>
                                @foreach(range(1, 5) as $col)
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 text-slate-400 px-2 py-1 text-xs font-bold">--</span>
                                    </td>
                                @endforeach
                                <td class="px-6 py-4">
                                    <span class="rounded-md bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-0.5 text-[10px] font-bold uppercase inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="h-3 w-3"></i> Pending
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <i data-lucide="book-x" class="mx-auto h-10 w-10 text-slate-300"></i>
                <h3 class="mt-3 font-heading text-base font-bold text-slate-700">No Subjects Registered</h3>
                <p class="mt-1 text-xs text-slate-500">Grades will appear here after class subject enrollment is finalized.</p>
            </div>
        @endif
    </section>
</div>

</x-student-layout>