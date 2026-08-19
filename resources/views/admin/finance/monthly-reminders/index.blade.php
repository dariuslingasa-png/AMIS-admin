<x-admin-layout title="Monthly Payment Reminder">
    <div class="space-y-6" x-data="{
        showConfirmModal: false,
        showPreviewModal: false,
        showTestModal: false,
        sendingBatch: false,
        sendingTest: false,
        forceResend: false,
        allParentEmails: {{ Js::from($allEligibleEmails ?? []) }},
        testEmailsInput: '{{ Auth::user()?->email ?? 'amisonlinesupport@gmail.com' }}',
        loadAllParents() {
            this.testEmailsInput = this.allParentEmails.join('\n');
        },
        clearEmails() {
            this.testEmailsInput = '';
        }
    }">

        <!-- ── HEADER & BREADCRUMBS ───────────────────────────────────────── -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">
                    <a href="{{ route('admin.finance.dashboard') }}" class="hover:underline">Finance</a>
                    <span>/</span>
                    <span>Monthly Payment Reminder</span>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Monthly Payment Reminders
                </h1>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">
                    Official general monthly payment reminder system for AMIS parents and guardians.
                </p>
            </div>

            <!-- Month Selector & Global Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <form method="GET" action="{{ route('admin.finance.monthly-reminders.index') }}" class="flex items-center">
                    <select name="month" onchange="this.form.submit()"
                            class="text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 shadow-xs text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                        @foreach($monthsList as $key => $label)
                            <option value="{{ $key }}" {{ $selectedMonth === $key ? 'selected' : '' }}>
                                📅 {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <button type="button" @click="showTestModal = true"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Send Test
                </button>

                <button type="button" @click="showPreviewModal = true"
                        class="px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Preview Reminder
                </button>

                <a href="{{ route('admin.finance.monthly-reminders.history', ['month' => $selectedMonth]) }}"
                   class="px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 shadow-xs transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Delivery Logs
                </a>

                <button type="button" @click="showConfirmModal = true"
                        class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send Monthly Reminders
                </button>
            </div>
        </div>

        <!-- ── FLASH NOTIFICATIONS ────────────────────────────────────────── -->
        @if(session('success'))
            <div class="flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-emerald-800 dark:text-emerald-200 text-xs font-bold shadow-xs">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl text-rose-800 dark:text-rose-200 text-xs font-bold space-y-1 shadow-xs">
                @foreach($errors->all() as $err)
                    <p class="flex items-center gap-2">
                        <span class="text-rose-500">⚠</span> {{ $err }}
                    </p>
                @endforeach
            </div>
        @endif

        <!-- ── STATS CARDS ────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
            <!-- Current Reminder Month -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Reminder Month</p>
                <p class="text-lg font-black text-slate-900 dark:text-white mt-1">
                    {{ Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}
                </p>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Active SY 2026-2027</p>
            </div>

            <!-- Eligible Families -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Eligible Families</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                    {{ number_format($metrics['eligible_families']) }}
                </p>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Approved Enrollments</p>
            </div>

            <!-- Already Sent -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Already Sent ✓</p>
                <p class="text-2xl font-black text-emerald-600 mt-1">
                    {{ number_format($metrics['already_sent']) }}
                </p>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Sent this cycle</p>
            </div>

            <!-- Pending / Will Receive -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
                <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Pending</p>
                <p class="text-2xl font-black text-indigo-600 mt-1">
                    {{ number_format($metrics['pending']) }}
                </p>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Will receive email</p>
            </div>

            <!-- Failed -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
                <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Failed / Retry</p>
                <p class="text-2xl font-black text-rose-600 mt-1">
                    {{ number_format($metrics['failed']) }}
                </p>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">SMTP errors</p>
            </div>
        </div>

        <!-- ── SENDER IDENTITY BANNER ─────────────────────────────────────── -->
        <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Sender Identity: <strong class="font-black text-slate-800 dark:text-white">{{ env('REMINDER_MAIL_FROM_NAME', 'AMIS Support Staff') }}</strong> (&lt;{{ env('REMINDER_MAIL_FROM_ADDRESS', config('mail.from.address', 'amisonlinesupport@gmail.com')) }}&gt;)</span>
            </div>
            <div class="flex items-center gap-3 text-slate-500">
                <span>Rule: <strong>1 Email per Family</strong></span>
                <span>•</span>
                <span>Rule: <strong>Send Only Once</strong> per monthly cycle</span>
                <span>•</span>
                <span>Rule: <strong>General Announcement (No student data in email)</strong></span>
            </div>
        </div>

        <!-- ── SEARCH & FILTER BAR ────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
            <form method="GET" action="{{ route('admin.finance.monthly-reminders.index') }}"
                  class="flex flex-col md:flex-row items-center justify-between gap-4">
                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                <!-- Filter Tabs -->
                <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto">
                    @php
                        $filterTabs = [
                            ''         => 'All Families (' . $metrics['eligible_families'] . ')',
                            'not_sent' => 'Pending (' . $metrics['pending'] . ')',
                            'sent'     => 'Sent (' . $metrics['already_sent'] . ')',
                            'failed'   => 'Failed (' . $metrics['failed'] . ')',
                        ];
                    @endphp

                    @foreach($filterTabs as $fVal => $fLabel)
                        <a href="{{ route('admin.finance.monthly-reminders.index', ['month' => $selectedMonth, 'filter' => $fVal, 'q' => $search]) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-bold transition {{ ($filter === $fVal || ($fVal === '' && empty($filter))) ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                            {{ $fLabel }}
                        </a>
                    @endforeach
                </div>

                <!-- Search box -->
                <div class="flex items-center gap-2 w-full md:w-72">
                    <div class="relative w-full">
                        <input type="text" name="q" value="{{ $search }}"
                               placeholder="Search parent name, email..."
                               class="w-full text-xs font-medium pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl outline-none focus:ring-2 focus:ring-emerald-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <button type="submit" class="px-3 py-2 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                        Find
                    </button>
                </div>
            </form>
        </div>

        <!-- ── MAIN RECIPIENTS TABLE (CLEAN & SIMPLE) ─────────────────────── -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/75 dark:bg-slate-900/50 text-slate-500 uppercase tracking-wider font-bold">
                            <th class="px-5 py-3.5">#</th>
                            <th class="px-4 py-3.5">Parent / Family</th>
                            <th class="px-4 py-3.5">Email Address</th>
                            <th class="px-4 py-3.5 text-center">Reminder Month</th>
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-right">Sent At</th>
                            <th class="px-4 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-medium">
                        @forelse($families as $index => $family)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-xs">
                                    {{ $families->firstItem() + $index }}
                                </td>

                                <td class="px-4 py-3.5 font-bold text-slate-900 dark:text-white">
                                    {{ $family->parent_name }}
                                </td>

                                <td class="px-4 py-3.5 font-mono text-xs text-slate-700 dark:text-slate-300">
                                    {{ $family->email }}
                                </td>

                                <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-600 dark:text-slate-300">
                                    {{ Carbon\Carbon::parse($selectedMonth . '-01')->format('M Y') }}
                                </td>

                                <td class="px-4 py-3.5 text-center">
                                    @if($family->status === 'SENT')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Sent
                                        </span>
                                    @elseif($family->status === 'PROCESSING')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Processing...
                                        </span>
                                    @elseif($family->status === 'FAILED' || $family->status === 'RETRY')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200" title="{{ $family->last_error }}">
                                            ⚠ {{ $family->status === 'RETRY' ? 'Retry' : 'Failed' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5 text-right text-slate-500 font-mono text-xs">
                                    {{ $family->sent_at ? Carbon\Carbon::parse($family->sent_at)->format('M d, Y H:i') : '—' }}
                                </td>

                                <td class="px-4 py-3.5 text-right">
                                    <form method="POST" action="{{ route('admin.finance.monthly-reminders.send-single', ['familyId' => base64_encode($family->email)]) }}" class="inline-block">
                                        @csrf
                                        <input type="hidden" name="billing_month" value="{{ $selectedMonth }}">
                                        <button type="submit"
                                                onclick="return confirm('Send {{ Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }} payment reminder to {{ $family->email }}?')"
                                                class="px-2.5 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-emerald-100 hover:text-emerald-800 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-bold transition cursor-pointer">
                                            {{ $family->status === 'SENT' ? 'Resend' : 'Send' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                    No parent records found for the selected filter and month.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($families->hasPages())
                <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-700">
                    {{ $families->links() }}
                </div>
            @endif
        </div>

        <!-- ── CONFIRM BATCH SEND MODAL ───────────────────────────────────── -->
        <div x-cloak x-show="showConfirmModal" style="display: none; z-index: 99999;"
             class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in text-slate-900"
             @click.outside="if(!sendingBatch) showConfirmModal = false">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white">
                                Send Monthly Payment Reminder?
                            </h3>
                            <p class="text-xs font-semibold text-slate-500 mt-0.5">
                                Reminder Month: {{ Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 space-y-2 border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Reminder Month:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Eligible Families:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ number_format($metrics['eligible_families']) }}</span>
                        </div>
                        <div class="flex justify-between" x-show="!forceResend">
                            <span class="text-slate-500">Already Sent (Will Skip):</span>
                            <span class="font-bold text-emerald-600">{{ number_format($metrics['already_sent']) }}</span>
                        </div>
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex justify-between text-sm">
                            <span class="font-black text-slate-900 dark:text-white">Will Receive Reminder:</span>
                            <span class="font-black text-emerald-600" x-text="forceResend ? '{{ number_format($metrics['eligible_families']) }} families' : '{{ number_format($metrics['will_receive_count']) }} families'">{{ number_format($metrics['will_receive_count']) }} families</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.finance.monthly-reminders.send') }}"
                          @submit="sendingBatch = true">
                        @csrf
                        <input type="hidden" name="billing_month" value="{{ $selectedMonth }}">

                        <!-- Force Resend Toggle -->
                        <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-2xl space-y-1 text-left">
                            <label class="flex items-start gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" name="force_resend" value="1" x-model="forceResend"
                                       class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 mt-0.5 cursor-pointer">
                                <div>
                                    <span class="font-bold text-xs text-amber-900 dark:text-amber-200">
                                        Resend to ALL {{ number_format($metrics['eligible_families']) }} Families
                                    </span>
                                    <p class="text-[11px] text-amber-700 dark:text-amber-300 font-medium mt-0.5 leading-snug">
                                        Check this to override the already-sent filter and send updated unthreaded reminders to all registered families.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showConfirmModal = false" :disabled="sendingBatch"
                                    class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" :disabled="sendingBatch || (!forceResend && {{ $metrics['will_receive_count'] }} === 0)"
                                    class="px-5 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <span x-show="!sendingBatch && !forceResend">✓ Send {{ number_format($metrics['will_receive_count']) }} Reminders</span>
                                <span x-show="!sendingBatch && forceResend" style="display: none;">✓ Resend to All {{ number_format($metrics['eligible_families']) }} Families</span>
                                <span x-show="sendingBatch" style="display: none;">Queueing Reminders...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── PREVIEW EMAIL MODAL (USES EXACT TEMPLATE) ───────────────────── -->
        <div x-cloak x-show="showPreviewModal" style="display: none; z-index: 99999;"
             class="fixed inset-0 flex items-center justify-center bg-slate-900/70 backdrop-blur-xs p-4 animate-fade-in text-slate-900"
             @click.outside="showPreviewModal = false">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-3xl w-full h-[90vh] overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col">
                <div class="p-4 px-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">
                            Official Monthly Payment Reminder Email Preview
                        </h3>
                        <p class="text-xs font-semibold text-slate-500">
                            Shows the exact final email with all 3 approved posters and receipt submission instructions.
                        </p>
                    </div>
                    <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                        ✕
                    </button>
                </div>
                <div class="flex-1 bg-slate-100 dark:bg-slate-950 p-4 overflow-auto">
                    <iframe src="{{ route('admin.finance.monthly-reminders.preview-email') }}"
                            class="w-full h-full min-h-[650px] bg-white rounded-xl border border-slate-200 shadow-xs"
                            title="Reminder Email Preview"></iframe>
                </div>
            </div>
        </div>

        <!-- ── SEND TEST EMAIL MODAL ──────────────────────────────────────── -->
        <div x-cloak x-show="showTestModal" style="display: none; z-index: 99999;"
             class="fixed inset-0 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 animate-fade-in text-slate-900"
             @click.outside="if(!sendingTest) showTestModal = false">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">
                        Send Test / Custom Reminder
                    </h3>
                    <p class="text-xs font-semibold text-slate-500 mt-0.5">
                        Dispatches reminder emails directly to the specified address(es). Does NOT alter regular monthly database tracking records.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.finance.monthly-reminders.send-test') }}"
                      @submit="sendingTest = true" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                Destination Email Address(es)
                            </label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="loadAllParents()"
                                        class="text-[11px] font-black px-2.5 py-1 rounded-lg bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/80 dark:hover:bg-emerald-900 text-emerald-800 dark:text-emerald-200 transition cursor-pointer flex items-center gap-1 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span>⚡ Load All {{ number_format(count($allEligibleEmails ?? [])) }} Parents</span>
                                </button>
                                <button type="button" @click="clearEmails()" x-show="testEmailsInput && testEmailsInput.length > 0"
                                        class="text-[11px] font-semibold text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition cursor-pointer">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <textarea name="test_email" required rows="5" x-model="testEmailsInput"
                                  placeholder="e.g. parent1@gmail.com, parent2@gmail.com&#10;or click '⚡ Load All Parents' button above"
                                  class="w-full text-xs font-mono font-medium px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl outline-none focus:ring-2 focus:ring-blue-500"></textarea>

                        <div class="flex items-center justify-between mt-1 text-[11px] text-slate-400">
                            <span>💡 You can paste 1 email, multiple emails, or click "Load All Parents".</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400"
                                  x-text="testEmailsInput ? (testEmailsInput.match(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g) || []).length + ' email(s) ready' : ''"></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showTestModal = false" :disabled="sendingTest"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" :disabled="sendingTest"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-50 flex items-center gap-1.5">
                            <span x-show="!sendingTest">Send Reminder(s)</span>
                            <span x-show="sendingTest" style="display: none;">Sending...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-admin-layout>
