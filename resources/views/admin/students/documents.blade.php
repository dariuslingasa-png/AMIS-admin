<x-admin-layout
    title="Student Documents"
    :breadcrumbs="[
        ['label' => 'Students', 'href' => route('admin.students.index')],
        ['label' => 'Student Documents', 'href' => null],
    ]"
>
    <div class="space-y-6">
        <!-- Banner -->
        <section class="overflow-hidden rounded-3xl border border-emerald-700/30 bg-gradient-to-br from-emerald-800 via-emerald-900 to-teal-950 p-6 text-white shadow-xl shadow-slate-900/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase tracking-[0.22em] text-slate-200">Students Workspace</span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight">Student Documents</h1>
                    <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-emerald-100">
                        Manage required registration files, birth certificates, report cards, and good moral clearances.
                    </p>
                </div>
            </div>
        </section>

        <!-- Main Card -->
        <x-card title="Registration Documents Directory" subtitle="Review uploaded student credentials and certificates">
            <!-- Search Filter -->
            <div class="px-4 py-4 sm:px-6 border-b border-slate-200/60 bg-slate-50/50">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div></div>
                    <div class="flex items-center gap-2">
                        <form method="GET" action="{{ route('admin.students.documents') }}" class="flex items-center gap-2">
                            <div class="relative w-full sm:w-[320px]">
                                <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-3.5 h-4 w-4 text-slate-400"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student or ID..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-xs font-semibold text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-450 focus:ring-4 focus:ring-emerald-100">
                                @if(request()->filled('search'))
                                    <a href="{{ route('admin.students.documents') }}" class="absolute right-3 top-3.5 text-slate-400 hover:text-slate-600 transition" title="Clear search">
                                        <i data-lucide="x" class="h-4 w-4"></i>
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="inline-flex h-11 items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 cursor-pointer">
                                Search
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[850px]">
                    <thead>
                        <tr class="border-b border-slate-100 text-[11px] font-black uppercase tracking-widest text-slate-400">
                            <th class="px-5 py-3">Student</th>
                            <th class="px-5 py-3">Grade Level</th>
                            <th class="px-5 py-3">Birth Certificate</th>
                            <th class="px-5 py-3">Report Card (SF9)</th>
                            <th class="px-5 py-3">Good Moral Certificate</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $student)
                            @php
                                $applicant = $student->applicant;
                                $fullName = $applicant ? html_entity_decode(trim(($applicant->first_name ?? '').' '.($applicant->middle_name ?? '').' '.($applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8') : 'Unknown Student';
                            @endphp
                            <tr class="align-middle">
                                <td class="px-5 py-4">
                                    <span class="font-extrabold text-slate-900 block uppercase">{{ $fullName }}</span>
                                    <span class="text-xs font-bold text-slate-400 mt-1 block">{{ $student->student_number }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs font-bold text-slate-600">
                                    {{ $student->grade_level }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($applicant && $applicant->birth_cert_url)
                                        <a href="{{ $applicant->birth_cert_url }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            View Birth Cert
                                        </a>
                                    @else
                                        <span class="text-xs font-bold text-slate-450 italic">Not Uploaded</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($applicant && $applicant->report_card_url)
                                        <a href="{{ $applicant->report_card_url }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            View SF9
                                        </a>
                                    @else
                                        <span class="text-xs font-bold text-slate-450 italic">Not Uploaded</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if($applicant && $applicant->good_moral_url)
                                        <a href="{{ $applicant->good_moral_url }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition">
                                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                            View Certificate
                                        </a>
                                    @else
                                        <span class="text-xs font-bold text-slate-450 italic">Not Uploaded</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center justify-center gap-1 rounded-xl bg-slate-50 border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                                        <i data-lucide="folder-open" class="w-3.5 h-3.5"></i>
                                        Verify Files
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm font-bold text-slate-400">No student documents found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-4 sm:px-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50/30">
                <p class="text-xs font-bold text-slate-500">
                    Showing {{ $students->firstItem() ?? 0 }}-{{ $students->lastItem() ?? 0 }} of {{ $students->total() }} students
                </p>
                <div class="w-full sm:w-auto">{{ $students->links() }}</div>
            </div>
        </x-card>
    </div>
</x-admin-layout>
