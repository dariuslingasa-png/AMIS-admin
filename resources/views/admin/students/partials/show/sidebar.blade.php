<!-- Right Sidebar (Review Panel style) -->
<aside class="review-panel space-y-6">
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

            <!-- Print Workspace -->
            <div class="border-t border-slate-100 pt-4 mt-4 dark:border-slate-800 space-y-2">
                <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400">Print Workspace</label>
                <a href="{{ route('admin.students.index', ['search' => $student->student_number, 'print_info' => 1]) }}"
                   target="_blank"
                   class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                    <i data-lucide="printer" class="h-3.5 w-3.5 text-slate-500"></i>
                    <span>Print Official Info Sheet</span>
                </a>
            </div>

            <!-- Credentials Actions -->
            <div class="border-t border-slate-100 pt-4 mt-4 dark:border-slate-800 space-y-2">
                <label class="block text-xxs font-extrabold uppercase tracking-wider text-slate-400">Credentials & Password Workspace</label>
                
                <!-- Resend Current Credentials -->
                <form method="POST" action="{{ route('admin.students.resend', $student) }}">
                    @csrf
                    <input type="hidden" name="reset_format" value="none">
                    <button type="submit" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900 px-3 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                        <i data-lucide="mail" class="h-3.5 w-3.5"></i>
                        <span>Email Current Credentials</span>
                    </button>
                </form>

                <!-- Set Custom Password -->
                <form method="POST" action="{{ route('admin.students.resend', $student) }}" class="space-y-1.5">
                    @csrf
                    <div class="flex gap-2">
                        <input type="text" name="custom_password" placeholder="Type custom password..." required class="flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 px-3 text-xs font-bold text-white active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Set Custom Password">
                            <i data-lucide="key" class="h-3.5 w-3.5"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </form>

                <!-- Reset Password to default -->
                <form method="POST" action="{{ route('admin.students.resend', $student) }}">
                    @csrf
                    <input type="hidden" name="reset_format" value="default">
                    <button type="submit" class="w-full inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-950/20 dark:bg-amber-950/10 px-3 text-xs font-bold text-amber-700 dark:text-amber-400 hover:bg-amber-100/50 active:scale-[0.98] transition-all duration-200 cursor-pointer" title="Reset password to default format (Amis@12345)">
                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                        <span>Reset Password (Amis@12345)</span>
                    </button>
                </form>
            </div>

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
