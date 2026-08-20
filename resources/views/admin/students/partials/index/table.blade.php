@php
    $msSyncColor = ['enrolled' => 'green', 'failed' => 'red', 'pending' => 'yellow'];
    $msSyncLabel = ['enrolled' => 'Synced', 'failed' => 'Sync Failed', 'pending' => 'Pending Teams'];
    $isTeacherAdminViewer = auth()->user()?->isTeacherAdminViewer() ?? false;
    $actionButtonClass = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400';
@endphp

<!-- Main Table Container -->
<div id="tableContainer">
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="w-full min-w-[1180px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
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
                        $suffix = trim($student->applicant->suffix ?? '');
                        $middleInitial = '';
                        if ($middleName !== '') {
                            $middleInitial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($middleName, 0, 1)) . '.';
                        }
                        $fullName = html_entity_decode(implode(' ', array_filter([$firstName, $middleInitial, $lastName, $suffix])), ENT_QUOTES, 'UTF-8');
                        $name = $fullName ? \Illuminate\Support\Str::upper($fullName) : 'STUDENT PROFILE';
                        $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->join('');
                        $photoUrl = \App\Support\EnrollmentStorage::url($student->applicant->photo_2x2_url ?? null);
                        $msStatus = $student->studentSection->ms_status ?? ($student->ms_user_id && $student->ms_license_active ? 'enrolled' : 'pending');
                        $gender = strtolower((string) ($student->applicant->gender ?? ''));
                        $genderLabel = $gender === 'male' ? 'Male' : ($gender === 'female' ? 'Female' : 'Not Set');
                        
                        $studentType = $student->applicant ? $student->applicant->student_type : 'New';
                        $learningMode = $student->applicant ? $student->applicant->learning_mode : 'F2F';
                        $modeAbbr = 'F2F';
                        $lmLower = strtolower($learningMode);
                        if (str_contains($lmLower, 'online') || str_contains($lmLower, 'flexible') || str_contains($lmLower, 'odl') || str_contains($lmLower, 'shift')) {
                            $modeAbbr = 'ODL';
                        }
                        $isHashed = str_starts_with($student->temp_password ?? '', '$');

                        $studentTypeLabel = \Illuminate\Support\Str::headline($studentType ?: 'Not set');
                    @endphp
                    <tr class="transition-colors duration-100 ease-in-out hover:bg-slate-50">
                        <!-- Student Number -->
                        <td class="whitespace-nowrap px-5 py-4">
                            <span class="inline-flex items-center font-mono font-black text-xs text-emerald-800 bg-emerald-50 border border-emerald-200/80 px-2.5 py-1 rounded-lg tracking-tight">
                                {{ $student->student_number ?? '-' }}
                            </span>
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
                                    <div class="font-extrabold text-slate-950 break-words max-w-[240px] leading-tight">{{ $name }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs font-medium text-slate-500 print:hidden">
                                         <span>SY {{ $student->school_year ?? '-' }}</span>
                                         @if (!$student->applicant || $student->applicant->completion_percentage < 100)
                                             <span class="text-slate-300">•</span>
                                             @php
                                                 $missingList = $student->applicant ? implode(', ', $student->applicant->incomplete_fields) : 'No profile';
                                             @endphp
                                             <span class="inline-flex cursor-help items-center gap-1 font-semibold text-amber-700" title="Missing: {{ $missingList }}">
                                                 <i data-lucide="triangle-alert" class="h-3 w-3"></i>
                                                 Profile incomplete
                                             </span>
                                         @endif
                                     </div>
                                </div>
                            </div>
                        </td>

                        <!-- Student Type -->
                        <td class="whitespace-nowrap px-5 py-4">
                            @php
                                $typeLower = strtolower($studentType ?: '');
                                $typeBadgeClass = match (true) {
                                    str_contains($typeLower, 'transferee') => 'bg-sky-50 text-sky-700 border-sky-200',
                                    str_contains($typeLower, 'new') => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    str_contains($typeLower, 'old') => 'bg-slate-100 text-slate-700 border-slate-200',
                                    str_contains($typeLower, 'returnee') => 'bg-amber-50 text-amber-800 border-amber-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold border {{ $typeBadgeClass }}">
                                {{ $studentTypeLabel }}
                            </span>
                        </td>

                        <!-- Mode -->
                        <td class="whitespace-nowrap px-5 py-4">
                            @if ($modeAbbr === 'ODL')
                                <span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 border border-indigo-200" title="Online Distance Learning">
                                    <i data-lucide="laptop" class="h-3 w-3"></i>
                                    ODL
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-md bg-teal-50 px-2.5 py-1 text-xs font-bold text-teal-700 border border-teal-200" title="Face to Face">
                                    <i data-lucide="building-2" class="h-3 w-3"></i>
                                    F2F
                                </span>
                            @endif
                        </td>

                        <!-- Gender -->
                        <td class="whitespace-nowrap px-5 py-4">
                            @if ($gender === 'female')
                                <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700 border border-rose-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                    Female
                                </span>
                            @elseif ($gender === 'male')
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 border border-blue-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                    Male
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-400 border border-slate-200">
                                    Not Set
                                </span>
                            @endif
                        </td>

                        <!-- Grade -->
                        <td class="whitespace-nowrap px-5 py-4">
                            @php
                                $gradeRaw = $student->grade_level ?? '-';
                                $gradeAbbr = preg_replace(
                                    ['/^Kinder\s*1$/i', '/^Kinder\s*2$/i', '/^Grade\s*(\d+)$/i'],
                                    ['K1', 'K2', 'G$1'],
                                    $gradeRaw
                                );
                            @endphp
                            <span class="inline-flex items-center justify-center font-black text-xs text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-md min-w-[38px]">
                                {{ $gradeAbbr }}
                            </span>
                        </td>

                        <!-- Section / Class Occupancy -->
                        <td class="whitespace-nowrap px-5 py-4">
                            @if($student->studentSection && $student->studentSection->section)
                                <span class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-1 text-xs font-bold text-slate-800 border border-slate-200 shadow-2xs">
                                    <i data-lucide="users" class="h-3 w-3 text-slate-400"></i>
                                    {{ $student->studentSection->section->name }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-400 border border-slate-200/60 italic">
                                    Unassigned
                                </span>
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
                         <td class="whitespace-nowrap px-5 py-4 text-right print:hidden">
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
                                ]) }})" class="{{ $actionButtonClass }} cursor-pointer" title="View Microsoft Account Credentials & Password">
                                    <i data-lucide="key-round" class="h-4 w-4 text-amber-600"></i>
                                </button>
                                <a href="{{ route('admin.students.download-docs-zip', ['search' => $student->student_number]) }}" download class="{{ $actionButtonClass }}" title="Download Student Documents ZIP (2x2 Photo, Birth Cert, Report Card, etc.)">
                                    <i data-lucide="archive" class="h-4 w-4 text-sky-600"></i>
                                </a>
                                <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_blank" class="{{ $actionButtonClass }}" title="Print Enrollment Application Form">
                                    <i data-lucide="file-signature" class="h-4 w-4 text-emerald-600"></i>
                                </a>
                                <a href="{{ route('admin.students.show', $student) }}" class="{{ $actionButtonClass }}" title="{{ $isTeacherAdminViewer ? 'View' : 'Manage' }}">
                                    <i data-lucide="file-search" class="h-4 w-4 text-indigo-600"></i>
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
