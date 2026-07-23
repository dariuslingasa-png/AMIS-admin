<aside class="review-panel space-y-6" style="max-height: none; overflow: visible; position: relative;">
    <!-- Onboarding Checklist -->
    <x-card title="Onboarding Checklist">
        <div class="space-y-3">
            <div class="flex justify-between items-center py-1">
                <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">User Created</span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $student->ms_user_id ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-400 dark:ring-rose-500/20' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $student->ms_user_id ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                    {{ $student->ms_user_id ? 'Yes' : 'No' }}
                </span>
            </div>
            <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800 pt-2">
                <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">Teams Enrolled</span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $student->ms_teams_enrolled_at ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/20 dark:text-amber-400 dark:ring-amber-500/20' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $student->ms_teams_enrolled_at ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                    {{ $student->ms_teams_enrolled_at ? 'Enrolled' : 'Pending' }}
                </span>
            </div>
            @php
                $hasPayment = false;
                $paymentProofUrl = null;
                $paymentProofIsPdf = false;
                if ($student->applicant) {
                    $familyUserIds = \App\Models\EnrollmentApplicant::where('user_id', $student->applicant->user_id)
                        ->orWhere(function($q) use ($student) {
                            if ($student->applicant->family_application_id) {
                                $q->where('family_application_id', $student->applicant->family_application_id);
                            } else {
                                $q->where('id', -1);
                            }
                        })
                        ->pluck('user_id')
                        ->filter()
                        ->unique()
                        ->toArray();

                    $paymentRecord = \App\Models\Payment::whereIn('user_id', $familyUserIds)
                        ->whereNotNull('receipt_url')
                        ->whereNotIn('receipt_url', ['', '[]', '[""]'])
                        ->first();

                    if ($paymentRecord) {
                        $hasPayment = true;
                        $receiptsList = $paymentRecord->receipt_urls ?? [];
                        $validReceiptsList = array_filter($receiptsList, fn($u) => filled($u) && $u !== '[]' && $u !== '[""]');
                        if (!empty($validReceiptsList)) {
                            $firstReceipt = reset($validReceiptsList);
                            $paymentProofUrl = \App\Support\EnrollmentStorage::url($firstReceipt);
                            $paymentProofIsPdf = $firstReceipt && strtolower(pathinfo($firstReceipt, PATHINFO_EXTENSION)) === 'pdf';
                        }
                    }
                }
            @endphp
            <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800 pt-2">
                <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">Payment Proof</span>
                <div class="flex flex-col items-end gap-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $hasPayment ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-400 dark:ring-rose-500/20' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $hasPayment ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                        {{ $hasPayment ? 'Uploaded' : 'Missing' }}
                    </span>
                    @if($hasPayment && $paymentProofUrl)
                        <button type="button" @click="openPreview('{{ $paymentProofUrl }}', 'Payment Receipt', {{ $paymentProofIsPdf ? 'true' : 'false' }})" class="text-[10px] text-emerald-600 hover:text-emerald-700 font-extrabold flex items-center gap-0.5 mt-0.5 transition cursor-pointer">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <span>View Receipt</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- LRN Status -->
            <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800 pt-2">
                <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">LRN Status</span>
                @if(isset($isKinder1or2) && $isKinder1or2 && !($hasLrn ?? false))
                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-950/20 dark:text-sky-400 px-2.5 py-0.5 text-xs font-bold">
                        <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                        Exempt (K1/K2)
                    </span>
                @elseif($hasLrn ?? false)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 px-2.5 py-0.5 text-xs font-bold">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Verified
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-400 px-2.5 py-0.5 text-xs font-bold">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                        Missing LRN
                    </span>
                @endif
            </div>

            <!-- Requirements Lock Status & Super Admin Lock Action -->
            <div class="flex flex-col gap-2 border-t border-slate-100 dark:border-slate-800 pt-2.5">
                <div class="flex justify-between items-center">
                    <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">Requirements Clearance</span>
                    @if(isset($isRequirementsComplete) && $isRequirementsComplete)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 px-2.5 py-0.5 text-xs font-bold">
                            <i data-lucide="lock" class="h-3 w-3 text-emerald-600"></i>
                            <span>{{ ($student->is_requirements_locked ?? false) ? 'COMPLETED INFORMATION' : 'Locked & Complete' }}</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-950/20 dark:text-amber-400 px-2.5 py-0.5 text-xs font-bold">
                            <i data-lucide="unlock" class="h-3 w-3 text-amber-600"></i>
                            <span>Pending ({{ count($missingRequirements ?? []) }})</span>
                        </span>
                    @endif
                </div>

                @unless ($isTeacherAdminViewer)
                    <form method="POST" action="{{ route('admin.students.toggle-requirements-lock', $student) }}" class="mt-1">
                        @csrf
                        @if($student->is_requirements_locked)
                            <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 text-xs font-bold text-amber-800 hover:bg-amber-100 transition active:scale-[0.98] cursor-pointer" title="Unlock Requirements Profile">
                                <i data-lucide="unlock" class="h-3.5 w-3.5 text-amber-600"></i>
                                <span>Unlock Requirements Profile</span>
                            </button>
                        @else
                            <button type="submit" class="w-full inline-flex h-9 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-700 transition active:scale-[0.98] cursor-pointer" title="Lock as COMPLETED INFORMATION">
                                <i data-lucide="lock" class="h-3.5 w-3.5 text-white"></i>
                                <span>Lock as COMPLETED INFORMATION</span>
                            </button>
                        @endif
                    </form>
                @endunless
            </div>
        </div>
    </x-card>

    <!-- Print & Action Checklist -->
    <x-card title="Print & Action Checklist">
        <div class="space-y-2">
            <!-- 0. Print Enrollment Application Form -->
            <a href="{{ route('admin.students.print-enrolment-form', $student) }}" target="_blank"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-600 text-white dark:bg-emerald-600 px-3 text-xs font-bold hover:bg-emerald-700 dark:hover:bg-emerald-700 active:scale-[0.98] transition cursor-pointer shadow-sm">
                <i data-lucide="file-text" class="h-4 w-4 text-white"></i>
                <span>Print Enrollment Application Form</span>
            </a>

            <!-- 1. Print Official Sheet -->
            <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_info' => 1]) }}" target="_blank"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                <i data-lucide="printer" class="h-4 w-4 text-slate-500"></i>
                <span>Print Official Sheet</span>
            </a>

            <!-- 2. Print ID Card -->
            <button type="button" @click="showIdPreview = true"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                <i data-lucide="contact" class="h-4 w-4 text-slate-500"></i>
                <span>Print ID Card</span>
            </button>

            <!-- 2b. ID Editor -->
            <a href="{{ route('admin.students.id-editor', $student) }}"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 px-3 text-xs font-bold text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition active:scale-[0.98] cursor-pointer">
                <i data-lucide="edit-3" class="h-4 w-4 text-emerald-600"></i>
                <span>Open ID Editor</span>
            </a>

            @if($student->studentSection?->section)
                <!-- 2b. Section ID Roster Document -->
                <a href="{{ route('admin.students.id-roster-print', $student->studentSection->section) }}" target="_blank"
                   class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/40 px-3 text-xs font-bold text-purple-700 dark:text-purple-300 hover:bg-purple-100 transition active:scale-[0.98] cursor-pointer">
                    <i data-lucide="layers" class="h-4 w-4 text-purple-600"></i>
                    <span>Export Section ID Cards Sheet</span>
                </a>
            @endif

            <!-- 4. Print Credentials Slip -->
            <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_credentials' => 1]) }}" target="_blank"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                <i data-lucide="key" class="h-4 w-4 text-slate-500"></i>
                <span>Print Credentials Slip</span>
            </a>

            <!-- 5. Print Document Checklist -->
            <button type="button" onclick="printDocumentChecklist()"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                <i data-lucide="clipboard-list" class="h-4 w-4 text-slate-500"></i>
                <span>Print Document Checklist</span>
            </button>

            <!-- 6. Print Verification QR Code -->
            <button type="button" onclick="printQrCode('{{ $student->obfuscated_id }}', '{{ $student->student_number }}')"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                <i data-lucide="qr-code" class="h-4 w-4 text-slate-500"></i>
                <span>Print Verification QR Code</span>
            </button>

            <!-- 7. Download Documents ZIP -->
            <a href="{{ route('admin.students.download-docs-zip', ['search' => $student->student_number]) }}"
               class="w-full inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition cursor-pointer">
                <i data-lucide="folder-archive" class="h-4 w-4 text-slate-500"></i>
                <span>Download Documents ZIP</span>
            </a>
        </div>
    </x-card>

    <!-- Actions Panel -->
    <x-card title="Actions Workspace">
        <div class="space-y-3.5">
            <!-- Update Status Form -->
            <form method="POST" action="{{ route('admin.students.update-status', $student) }}" class="border-b border-slate-100 pb-4 mb-4 dark:border-slate-800">
                @csrf
                <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Administrative Status</label>
                <div class="flex gap-2">
                    <select name="status" class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <option value="verified" @selected(($student->user->account_status ?? 'verified') === 'verified')>Active / Verified</option>
                        <option value="suspended" @selected(($student->user->account_status ?? 'verified') === 'suspended')>Suspended / Deactivated</option>
                        <option value="graduated" @selected(($student->user->account_status ?? 'verified') === 'graduated')>Graduated</option>
                        <option value="transferred" @selected(($student->user->account_status ?? 'verified') === 'transferred')>Transferred</option>
                        <option value="withdrawn" @selected(($student->user->account_status ?? 'verified') === 'withdrawn')>Withdrawn</option>
                    </select>
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Save Status">
                        Save
                    </button>
                </div>
            </form>

            <!-- Update Microsoft Email Form -->
            <form method="POST" action="{{ route('admin.students.update-email', $student) }}" class="border-b border-slate-100 pb-4 mb-4 dark:border-slate-800">
                @csrf
                <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Microsoft / School Email</label>
                <div class="flex gap-2">
                    <input type="email" name="email" value="{{ $student->school_email }}" required class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Save Email">
                        Rename
                    </button>
                </div>
            </form>

            <!-- Classroom Section Form -->
            @unless ($isTeacherAdminViewer)
            <form method="POST" action="{{ route('admin.students.update-section', $student) }}" class="border-b border-slate-100 pb-4 mb-4 dark:border-slate-800">
                @csrf
                <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400 mb-1.5">Classroom Section</label>
                <div class="flex flex-col gap-2">
                    <select name="section_id" class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <option value="">No Section</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->id }}" @selected(($student->studentSection->section_id ?? null) === $s->id)>
                                {{ $s->grade_level }} - {{ $s->name ?? 'Unnamed' }} ({{ $s->learning_mode }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full inline-flex h-10 items-center justify-center rounded-xl bg-emerald-600 px-4 text-xs font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Save Section">
                        Reassign Section
                    </button>
                </div>
            </form>
            @endunless



            <!-- Force Teams & License Sync -->
            @if($student->ms_user_id)
                <form method="POST" action="{{ route('admin.ms-sync.student', $student) }}">
                    @csrf
                    <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2.5 rounded-xl bg-emerald-600 px-4 text-sm font-bold text-white hover:bg-emerald-700 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                        <span>Sync Microsoft License</span>
                    </button>
                </form>
            @endif

            <!-- Delete Student -->
            <div class="border-t border-rose-100 pt-4 mt-4 dark:border-rose-900/30">
                <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                      onsubmit="return confirm('Delete {{ $student->student_number }} ({{ $student->school_email }})?\n\nThis will permanently delete the student from the portal and Microsoft 365. This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2.5 rounded-xl border border-rose-200 bg-white px-4 text-sm font-bold text-rose-600 hover:bg-rose-50 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        <span>Delete Student</span>
                    </button>
                </form>
            </div>
        </div>
    </x-card>
</aside>
