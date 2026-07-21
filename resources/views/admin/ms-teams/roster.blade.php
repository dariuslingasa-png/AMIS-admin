<x-admin-layout title="Student Roster by Grade & Class">
    @php
        $breadcrumbs = [
            ['label' => 'Class Sections', 'href' => route('admin.ms-teams.index')],
            ['label' => 'Student Roster', 'href' => null],
        ];
        $unassignedCount = $students->count() - $assignedCount;
    @endphp

    <div x-data="{ search: '' }">
        <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-1 text-[10px] font-black uppercase tracking-widest text-emerald-700">Academic Workspace</p>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-950">Student Roster</h1>
                <p class="mt-1 text-sm text-slate-500">Students grouped by grade with their official AMIS class-section assignment.</p>
            </div>
            <a href="{{ route('admin.ms-teams.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="school" class="h-4 w-4"></i> Class & Section List
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                <i data-lucide="check-circle-2" class="h-4 w-4"></i>{{ session('success') }}
            </div>
        @endif

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            @foreach($gradeCounts as $grade)
                <a href="{{ route('admin.ms-teams.roster', ['grade' => $grade->grade_level]) }}"
                   class="rounded-2xl border p-3 transition {{ $selectedGrade === $grade->grade_level ? 'border-sky-300 bg-sky-50 ring-2 ring-sky-100' : 'border-slate-100 bg-white hover:border-sky-200' }}">
                    <div class="text-[10px] font-black uppercase tracking-wide {{ $selectedGrade === $grade->grade_level ? 'text-sky-700' : 'text-slate-500' }}">{{ $grade->grade_level }}</div>
                    <div class="mt-1 text-xl font-black text-slate-950">{{ number_format($grade->student_count) }}</div>
                    <div class="text-[9px] font-bold uppercase text-slate-400">Students</div>
                </a>
            @endforeach
        </div>

        <div class="mb-6 rounded-2xl border border-slate-100 bg-white p-5 shadow-xs">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-extrabold uppercase text-slate-900">{{ $selectedGrade }} Classes</h2>
                    <p class="text-xs text-slate-500">Open a class to manage its assigned students and subjects.</p>
                </div>
                <div class="flex gap-2 text-[10px] font-bold">
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">{{ $assignedCount }} Assigned</span>
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">{{ $unassignedCount }} Unassigned</span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @forelse($sections as $section)
                    <a href="{{ route('admin.ms-teams.show', $section) }}" class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/60 p-3 transition hover:border-emerald-200 hover:bg-emerald-50/50">
                        <div class="min-w-0">
                            <div class="truncate text-xs font-extrabold uppercase text-slate-900">{{ $section->name ?? 'Unnamed Section' }}</div>
                            <div class="mt-1 text-[9px] font-bold text-slate-500">{{ $section->formatted_learning_mode }}</div>
                        </div>
                        <span class="ml-3 shrink-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-black text-slate-700">{{ $section->students_count }}</span>
                    </a>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed border-slate-200 p-5 text-center text-xs font-bold text-slate-400">No sections created for this grade.</div>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-xs">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900">{{ $selectedGrade }} Student List</h2>
                    <p class="text-xs text-slate-500">{{ $students->count() }} students for school year {{ config('services.school.year') }}</p>
                </div>
                <div class="relative w-full sm:w-72">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" x-model="search" placeholder="Search name, ID, or section..." class="w-full rounded-xl border border-slate-200 py-2 pl-9 pr-3 text-xs font-semibold outline-none focus:border-emerald-400 focus:ring-2 focus:ring-emerald-100">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr class="text-left text-[9px] font-black uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">Student ID</th>
                            <th class="px-5 py-3">Current Class Section</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($students as $student)
                            @php
                                $studentName = $student->applicant?->full_name ?? $student->user?->name ?? 'Unnamed Student';
                                $currentSection = $student->studentSection?->section;
                                $searchText = strtolower($studentName.' '.$student->student_number.' '.($currentSection?->name ?? 'unassigned'));
                            @endphp
                            <tr class="hover:bg-slate-50/60" x-show='search === "" || @js($searchText).includes(search.toLowerCase())'>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.students.show', $student) }}" class="text-xs font-extrabold uppercase text-slate-900 hover:text-emerald-700">{{ $studentName }}</a>
                                    <div class="mt-0.5 text-[9px] text-slate-400">{{ $student->school_email }}</div>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-[10px] font-bold text-slate-500">{{ $student->student_number }}</td>
                                <td class="px-5 py-3">
                                    @if($currentSection)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700">{{ $currentSection->name ?? 'Unnamed Section' }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @unless(auth()->user()->isTeacherAdminViewer())
                                        <form method="POST" action="{{ route('admin.students.update-section', $student) }}" class="flex items-center justify-end gap-2">
                                            @csrf
                                            <select name="section_id" class="max-w-48 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-700 outline-none focus:border-emerald-400">
                                                <option value="">No Section</option>
                                                @foreach($sections as $section)
                                                    <option value="{{ $section->id }}" @selected($currentSection?->id === $section->id)>{{ $section->name ?? 'Unnamed' }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-[10px] font-bold text-white hover:bg-emerald-800">Save</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.students.show', $student) }}" class="float-right text-[10px] font-bold text-sky-700 hover:underline">View Profile</a>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-12 text-center text-xs font-bold text-slate-400">No students found for this grade.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
