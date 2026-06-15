@php
    $msSyncColor = ['enrolled' => 'green', 'failed' => 'red', 'pending' => 'yellow'];
    $msSyncLabel = ['enrolled' => 'Synced', 'failed' => 'Sync Failed', 'pending' => 'Pending Teams'];
@endphp

<!-- Main Table Container -->
<div id="tableContainer">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                <tr class="border-b border-slate-100">
                    <th class="w-72 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('name') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            Student Name
                            <i data-lucide="{{ $sortIcon('name') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">Student Name</span>
                    </th>
                    <th class="w-40 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('student_id') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            AMIS ID
                            <i data-lucide="{{ $sortIcon('student_id') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">AMIS ID</span>
                    </th>
                    <th class="w-32 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('gender') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            Gender
                            <i data-lucide="{{ $sortIcon('gender') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">Gender</span>
                    </th>
                    <th class="w-32 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('grade') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            Grade
                            <i data-lucide="{{ $sortIcon('grade') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">Grade</span>
                    </th>
                    <th class="w-40 px-5 py-4 font-bold print:hidden">Section</th>
                    <th class="w-48 px-5 py-4 font-bold">School Email / Temp Pass</th>
                    <th class="w-40 px-5 py-4 font-bold print:hidden">MS Sync State</th>
                    <th class="w-36 px-5 py-4 text-right font-bold print:hidden">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($students as $student)
                     @php
                        $fullName = html_entity_decode(trim(($student->applicant->first_name ?? '').' '.($student->applicant->middle_name ?? '').' '.($student->applicant->last_name ?? '')), ENT_QUOTES, 'UTF-8');
                        $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                        $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                        $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url ?? null);
                        $msStatus = $student->studentSection->ms_status ?? 'pending';
                        $gender = strtolower((string) ($student->applicant->gender ?? ''));
                        $genderLabel = $gender === 'male' ? 'Male' : ($gender === 'female' ? 'Female' : 'Not Set');
                        $genderClass = $gender === 'male' ? 'bg-blue-50 text-blue-700 ring-blue-100' : ($gender === 'female' ? 'bg-violet-50 text-violet-700 ring-violet-100' : 'bg-slate-50 text-slate-500 ring-slate-100');
                        
                        $studentType = $student->applicant ? $student->applicant->student_type : 'New';
                        $learningMode = $student->applicant ? $student->applicant->learning_mode : 'F2F';
                        $modeAbbr = 'F2F';
                        $lmLower = strtolower($learningMode);
                        if (str_contains($lmLower, 'online') || str_contains($lmLower, 'flexible') || str_contains($lmLower, 'odl') || str_contains($lmLower, 'shift')) {
                            $modeAbbr = 'ODL';
                        }
                    @endphp
                    <tr class="transition hover:bg-slate-50">
                        <!-- Student Photo & Name -->
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-smart-image
                                    :src="$photoUrl"
                                    :alt="$name"
                                    :fallback-initials="$initials ?: 'ST'"
                                    size="40"
                                    rounded="rounded-md"
                                    containerClass="bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 font-extrabold print:hidden"
                                    :eager="false"
                                />
                                <div>
                                    <div class="font-extrabold text-slate-950">{{ $name }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400 font-extrabold print:hidden">
                                         <span class="text-slate-400">SY {{ $student->school_year ?? '-' }}</span>
                                         <span class="text-slate-350">•</span>
                                         <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 font-bold text-slate-600 uppercase">{{ $studentType }}</span>
                                         <span class="text-slate-350">•</span>
                                         <span class="inline-flex items-center rounded bg-slate-100 px-1.5 py-0.5 font-bold text-slate-600 uppercase">{{ $modeAbbr }}</span>
                                         @if (!$student->applicant || $student->applicant->completion_percentage < 100)
                                             <span class="text-slate-355">•</span>
                                             <span class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 font-bold text-amber-700 ring-1 ring-amber-100 uppercase">Incomplete</span>
                                         @endif
                                     </div>
                                </div>
                            </div>
                        </td>

                        <!-- Student Number -->
                        <td class="px-5 py-4 font-extrabold text-slate-600">
                            {{ $student->student_number ?? '-' }}
                        </td>

                        <!-- Gender -->
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-extrabold ring-1 {{ $genderClass }}">{{ $genderLabel }}</span>
                        </td>

                        <!-- Grade -->
                        <td class="px-5 py-4 font-extrabold text-slate-700">
                            {{ $student->grade_level ?? '-' }}
                        </td>

                        <!-- Section -->
                        <td class="px-5 py-4 font-medium text-slate-600 print:hidden">
                            {{ $student->studentSection->section->official_name ?? $student->studentSection->section->name ?? 'No Section' }}
                        </td>

                         <!-- School Email / Temp Pass -->
                         <td class="px-5 py-4 text-xs">
                             <div class="flex items-start justify-between gap-1.5">
                                 <div class="min-w-0 flex-1">
                                     <div class="font-semibold text-slate-800 break-all select-all">{{ $student->school_email ?? '-' }}</div>
                                     <div class="mt-1 flex flex-wrap items-center gap-1.5 print:hidden">
                                         <span class="text-slate-400 font-extrabold uppercase tracking-wider text-[10px]">Pass:</span>
                                         @php
                                             $isHashed = str_starts_with($student->temp_password ?? '', '$');
                                         @endphp
                                         @if ($isHashed || blank($student->temp_password))
                                             <span class="text-slate-550 font-semibold text-[10px]">-</span>
                                         @else
                                             <span class="font-mono bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-[11px] text-slate-800 dark:text-slate-200 select-all font-semibold">{{ $student->temp_password }}</span>
                                         @endif
                                         @if ($student->password_changed_at)
                                             <span class="inline-flex items-center gap-0.5 rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-extrabold text-emerald-700 ring-1 ring-emerald-100 uppercase" title="Password changed on {{ $student->password_changed_at->format('M d, Y h:i A') }}">
                                                 <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check inline-block"><path d="M20 6 9 17l-5-5"/></svg>
                                                 Changed
                                             </span>
                                         @else
                                             <span class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-extrabold text-amber-700 ring-1 ring-amber-100 uppercase">
                                                 Temporary
                                             </span>
                                         @endif
                                     </div>
                                 </div>
                                 @if ($student->school_email)
                                     @php
                                         $emailVal = $student->school_email;
                                         $passVal = ($isHashed || blank($student->temp_password)) ? '' : $student->temp_password;
                                         $copyVal = $passVal ? "Email: {$emailVal}\nPassword: {$passVal}" : $emailVal;
                                     @endphp
                                     <button onclick="copyToClipboard(this.getAttribute('data-copy'), this)" 
                                             data-copy="{{ $copyVal }}" 
                                             class="text-slate-400 hover:text-slate-655 transition cursor-pointer p-1 rounded hover:bg-slate-100 print:hidden flex items-center justify-center border-0 bg-transparent shrink-0 mt-0.5" 
                                             title="Copy Email & Password">
                                         <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-copy"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                     </button>
                                 @endif
                             </div>
                         </td>

                        <!-- MS Sync status -->
                        <td class="px-5 py-4 print:hidden">
                            @if ($student->ms_user_id && $student->ms_license_active === false)
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 ring-1 ring-rose-100">
                                    <i data-lucide="shield-alert" class="h-3.5 w-3.5"></i>
                                    No License
                                </span>
                            @else
                                <x-badge :color="$msSyncColor[$msStatus] ?? 'gray'">
                                    {{ $msSyncLabel[$msStatus] ?? 'Pending' }}
                                </x-badge>
                            @endif
                        </td>

                        <!-- Action -->
                         <td class="px-5 py-4 text-right print:hidden">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($student->ms_user_id && ($msStatus !== 'enrolled' || $student->ms_license_active === false))
                                    <form method="POST" action="{{ route('admin.ms-sync.student', $student) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-emerald-100 bg-emerald-50 px-2.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-100 cursor-pointer" title="Sync Microsoft Account & License">
                                            <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                            <span>Sync License</span>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 items-center gap-2 rounded-md border border-emerald-100 bg-white px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                                    <i data-lucide="file-search" class="h-4 w-4"></i>
                                    Manage
                                </a>
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                      onsubmit="return confirm('Delete {{ $student->student_number }} ({{ $student->school_email }})?\n\nThis will permanently delete the student from the portal and Microsoft 365. This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-rose-200 bg-white text-rose-500 transition hover:bg-rose-50" title="Delete Student">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <!-- Premium Empty State -->
                    <tr>
                        <td colspan="8" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <div class="rounded-full bg-slate-50 p-4 text-slate-400 ring-8 ring-slate-50/50">
                                    <i data-lucide="users-2" class="h-10 w-10"></i>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="text-base font-bold text-slate-800">No student records found</h3>
                                    <p class="text-sm text-slate-500 max-w-sm mx-auto">We couldn't find any students matching your filters or search query. Try adjusting your parameters.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- Pagination links -->
    <div class="mt-5">{{ $students->links() }}</div>
</div>

@include('admin.students.partials.index.styles')
