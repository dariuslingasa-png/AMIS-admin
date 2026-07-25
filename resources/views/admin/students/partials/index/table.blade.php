@php
    $msSyncColor = ['enrolled' => 'green', 'failed' => 'red', 'pending' => 'yellow'];
    $msSyncLabel = ['enrolled' => 'Synced', 'failed' => 'Sync Failed', 'pending' => 'Pending Teams'];
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
@endphp

<!-- Main Table Container -->
<div id="tableContainer">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                <tr class="border-b border-slate-100">
                    <th class="w-32 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('student_id') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            AMIS ID
                            <i data-lucide="{{ $sortIcon('student_id') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">AMIS ID</span>
                    </th>
                    <th class="w-80 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('name') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            Student Name
                            <i data-lucide="{{ $sortIcon('name') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">Student Name</span>
                    </th>
                    <th class="w-24 px-5 py-4 font-bold">Type</th>
                    <th class="w-24 px-5 py-4 font-bold">Mode</th>
                    <th class="w-28 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('gender') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            Gender
                            <i data-lucide="{{ $sortIcon('gender') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">Gender</span>
                    </th>
                    <th class="w-28 px-5 py-4 font-bold">
                        <a href="{{ $sortUrl('grade') }}" class="flex items-center gap-1 hover:text-slate-600 transition print:hidden">
                            Grade
                            <i data-lucide="{{ $sortIcon('grade') }}" class="h-3 w-3"></i>
                        </a>
                        <span class="hidden print:inline">Grade</span>
                    </th>
                    <th class="w-36 px-5 py-4 font-bold">Section</th>
                    <th class="w-40 px-5 py-4 font-bold print:hidden">MS Sync State</th>
                    <th class="w-36 px-5 py-4 text-right font-bold print:hidden">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                @forelse ($students as $student)
                     @php
                        $firstName = trim($student->applicant->first_name ?? '');
                        $middleName = trim($student->applicant->middle_name ?? '');
                        $lastName = trim($student->applicant->last_name ?? '');
                        $middleInitial = '';
                        if ($middleName !== '') {
                            $middleInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($middleName, 0, 1)) . '.';
                        }
                        $fullName = html_entity_decode(implode(' ', array_filter([$firstName, $middleInitial, $lastName])), ENT_QUOTES, 'UTF-8');
                        $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                        $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                        $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url ?? null);
                        $msStatus = $student->studentSection->ms_status ?? ($student->ms_user_id && $student->ms_license_active ? 'enrolled' : 'pending');
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
                        $isHashed = str_starts_with($student->temp_password ?? '', '$');

                        $typeLower = strtolower($studentType);
                        $typeClass = $typeLower === 'new' 
                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' 
                            : ($typeLower === 'old' ? 'bg-slate-100 text-slate-700 ring-slate-200' : 'bg-amber-50 text-amber-700 ring-amber-100');

                        $modeClass = $modeAbbr === 'F2F' 
                            ? 'bg-blue-50 text-blue-700 ring-blue-100' 
                            : 'bg-rose-50 text-rose-700 ring-rose-100';
                    @endphp
                    <tr class="transition-colors duration-100 ease-in-out hover:bg-slate-50">
                        <!-- Student Number -->
                        <td class="px-5 py-4 font-extrabold text-slate-600">
                            {{ $student->student_number ?? '-' }}
                        </td>

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
                                    <div class="font-extrabold text-slate-950 whitespace-nowrap">{{ $name }}</div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[10px] text-slate-400 font-extrabold print:hidden">
                                         <span class="text-slate-400">SY {{ $student->school_year ?? '-' }}</span>
                                         @if (!$student->applicant || $student->applicant->completion_percentage < 100)
                                             <span class="text-slate-355">•</span>
                                             @php
                                                 $missingList = $student->applicant ? implode(', ', $student->applicant->incomplete_fields) : 'No profile';
                                             @endphp
                                             <span class="inline-flex items-center rounded bg-amber-50 px-1.5 py-0.5 font-bold text-amber-700 ring-1 ring-amber-100 uppercase cursor-help" title="Missing: {{ $missingList }}">Incomplete</span>
                                         @endif
                                     </div>
                                </div>
                            </div>
                        </td>

                        <!-- Student Type -->
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-bold ring-1 uppercase {{ $typeClass }}">{{ $studentType }}</span>
                        </td>

                        <!-- Mode -->
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-bold ring-1 uppercase {{ $modeClass }}">{{ $modeAbbr }}</span>
                        </td>

                        <!-- Gender -->
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-extrabold ring-1 {{ $genderClass }}">{{ $genderLabel }}</span>
                        </td>

                        <!-- Grade -->
                        <td class="px-5 py-4 font-extrabold text-slate-700">
                            @php
                                $gradeRaw = $student->grade_level ?? '-';
                                $gradeAbbr = preg_replace(
                                    ['/^Kinder\s*1$/i', '/^Kinder\s*2$/i', '/^Grade\s*(\d+)$/i'],
                                    ['K1', 'K2', 'G$1'],
                                    $gradeRaw
                                );
                            @endphp
                            {{ $gradeAbbr }}
                        </td>

                        <!-- Section / Class Occupancy -->
                        <td class="px-5 py-4 font-extrabold text-slate-700">
                            @if($student->studentSection && $student->studentSection->section)
                                <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-extrabold text-emerald-800 ring-1 ring-emerald-200/80">
                                    {{ $student->studentSection->section->name }}
                                </span>
                            @else
                                <span class="text-slate-400 font-normal text-xs">-</span>
                            @endif
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
                                <button type="button" onclick="openStudentCredentialsModal({{ json_encode([
                                    'id' => $student->id,
                                    'student_number' => $student->student_number ?? '-',
                                    'name' => $name,
                                    'photo_url' => $photoUrl,
                                    'grade' => $student->grade_level ?? '-',
                                    'section' => $student->studentSection?->section?->name ?? 'Unassigned',
                                    'email' => $student->school_email ?? 'No Email Set',
                                    'temp_password' => $student->temp_password ?? 'N/A',
                                    'password_changed' => !empty($student->password_changed_at),
                                    'password_changed_at' => $student->password_changed_at ? $student->password_changed_at->format('M d, Y h:i A') : null,
                                    'ms_user_id' => $student->ms_user_id,
                                    'resend_url' => route('admin.students.resend', $student),
                                    'sync_url' => route('admin.ms-sync.student', $student),
                                    'print_url' => route('admin.students.index', ['search' => $student->student_number, 'print_credentials' => 1]),
                                ]) }})" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-700 transition-colors duration-100 hover:bg-amber-100 cursor-pointer shadow-sm" title="View Microsoft Account Credentials & Password">
                                    <i data-lucide="key-round" class="h-4 w-4"></i>
                                </button>
                                <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_blank" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-200 bg-emerald-50 text-emerald-700 transition-colors duration-100 hover:bg-emerald-100" title="Print Enrollment Application Form">
                                    <i data-lucide="file-signature" class="h-4 w-4"></i>
                                </a>
                                <a href="{{ route('admin.students.show', $student) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-emerald-100 bg-white text-emerald-700 transition-colors duration-100 hover:bg-emerald-50" title="{{ $isTeacherAdminViewer ? 'View' : 'Manage' }}">
                                    <i data-lucide="file-search" class="h-4 w-4"></i>
                                </a>
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
