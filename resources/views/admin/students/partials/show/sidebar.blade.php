<!-- Right Sidebar (Review Panel style) -->
<aside class="review-panel space-y-6">
    <!-- Account Information Card -->
    <x-card title="Account Summary">
        <dl class="space-y-4 text-xs">
            <div>
                <dt class="font-extrabold uppercase tracking-wider text-slate-400">Student ID Number</dt>
                <dd class="mt-1.5 font-extrabold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ $student->student_number ?? 'Pending' }}</span>
                    <button @click="navigator.clipboard.writeText('{{ $student->student_number }}'); copySuccess = true; setTimeout(() => copySuccess = false, 2000)" class="text-slate-400 hover:text-emerald-600 focus:outline-none transition-colors" title="Copy Student ID">
                        <i data-lucide="copy" class="h-4 w-4" x-show="!copySuccess"></i>
                        <i data-lucide="check" class="h-4 w-4 text-emerald-600" x-show="copySuccess"></i>
                    </button>
                </dd>
            </div>
            <div class="border-t border-slate-100 pt-3.5 dark:border-slate-800">
                <dt class="font-extrabold uppercase tracking-wider text-slate-400">School Email / Username</dt>
                <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-200 select-all break-all">{{ $student->school_email ?? '-' }}</dd>
            </div>
            <div class="border-t border-slate-100 pt-3.5 dark:border-slate-800">
                <dt class="font-extrabold uppercase tracking-wider text-slate-400">Temporary Password</dt>
                <dd class="mt-1 select-all break-all">
                    @php
                        $isHashed = str_starts_with($student->temp_password ?? '', '$');
                    @endphp
                    @if ($isHashed || blank($student->temp_password))
                        <span class="text-slate-500 font-semibold">-</span>
                    @else
                        <span class="font-mono bg-slate-50 dark:bg-slate-800 px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200">{{ $student->temp_password }}</span>
                    @endif
                </dd>
            </div>
            <div class="border-t border-slate-100 pt-3.5 dark:border-slate-800">
                <dt class="font-extrabold uppercase tracking-wider text-slate-400">Password Status</dt>
                <dd class="mt-1.5 flex flex-wrap items-center gap-1.5">
                    @if ($student->password_changed_at)
                        <span class="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700 ring-1 ring-emerald-100 uppercase">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check inline-block"><path d="M20 6 9 17l-5-5"/></svg>
                            Changed / Set by Student
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold">on {{ $student->password_changed_at->format('M d, Y h:i A') }}</span>
                    @elseif ($student->ms_user_id)
                        <span class="inline-flex items-center rounded bg-amber-50 px-2 py-0.5 text-[10px] font-extrabold text-amber-700 ring-1 ring-amber-100 uppercase">
                            Still Temporary
                        </span>
                    @else
                        <span class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold text-slate-500 ring-1 ring-slate-200 uppercase">
                            No Account
                        </span>
                    @endif
                </dd>
            </div>
            <div class="border-t border-slate-100 pt-3.5 dark:border-slate-800">
                <dt class="font-extrabold uppercase tracking-wider text-slate-400">Classroom Section</dt>
                <dd class="mt-1 font-semibold text-slate-800 dark:text-slate-200">{{ $student->studentSection->section->name ?? 'No Section' }}</dd>
            </div>
        </dl>
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

            <!-- Resend credentials form -->
            <form method="POST" action="{{ route('admin.students.resend', $student) }}">
                @csrf
                <button type="submit" class="w-full inline-flex h-11 items-center justify-center gap-2.5 rounded-xl bg-amber-500 px-4 text-sm font-bold text-white hover:bg-amber-600 active:scale-[0.98] transition-all duration-200 cursor-pointer">
                    <i data-lucide="key" class="h-4 w-4"></i>
                    <span>Resend Credentials</span>
                </button>
            </form>

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
                if ($student->applicant) {
                    $hasPayment = \App\Models\Payment::where('user_id', $student->applicant->user_id)
                        ->whereNotNull('receipt_url')
                        ->whereNotIn('receipt_url', ['', '[]', '[""]'])
                        ->exists();
                }
            @endphp
            <div class="flex justify-between items-center py-1 border-t border-slate-100 dark:border-slate-800 pt-2">
                <span class="text-slate-600 dark:text-slate-400 text-sm font-medium">Payment Proof</span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 ring-inset {{ $hasPayment ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/20 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/20 dark:text-rose-400 dark:ring-rose-500/20' }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $hasPayment ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                    {{ $hasPayment ? 'Uploaded' : 'Missing' }}
                </span>
            </div>
        </div>
    </x-card>
</aside>
