<x-admin-layout title="Professional Email Composer">
    <div class="space-y-6" x-data="{
        showCc: false,
        showBcc: false,
        recipientType: '{{ old('recipient_type', $selectedDraft->recipient_type ?? 'students') }}',
        recipientFilter: '{{ old('recipient_filter', $selectedDraft->recipient_filter ?? 'all') }}',
        ccEmails: '{{ old('cc_emails', $selectedDraft->cc_emails ?? '') }}',
        bccEmails: '{{ old('bcc_emails', $selectedDraft->bcc_emails ?? '') }}',
        senderName: 'AMIS Information Technology',
        subject: '{{ addslashes(old('subject', $selectedDraft->subject ?? '')) }}',
        contentHtml: '{!! addslashes(old('body_html', $selectedDraft->body_html ?? '')) !!}',
        showPreviewModal: false,
        showTestModal: false,
        showRecipientModal: false,
        previewMode: 'desktop',
        testEmail: '{{ auth()->user()->email ?? 'admin@amis.edu.ph' }}',
        searchTerm: '',
        selectedStudents: [],
        selectAll: false,

        loadTemplate(subjectVal, bodyVal) {
            this.subject = subjectVal;
            this.contentHtml = bodyVal;
            this.updateEditorContent(bodyVal);
        },

        insertVariable(tag) {
            if (window.tinymce && window.tinymce.get('rich-editor')) {
                window.tinymce.get('rich-editor').insertContent(tag);
            } else {
                this.contentHtml += ' ' + tag;
                const el = document.getElementById('rich-editor');
                if (el) el.value += ' ' + tag;
            }
        },

        execCmd(cmd, value = null) {
            document.execCommand(cmd, false, value);
            this.syncContent();
        },

        updateEditorContent(val) {
            if (window.tinymce && window.tinymce.get('rich-editor')) {
                window.tinymce.get('rich-editor').setContent(val);
            } else {
                const el = document.getElementById('rich-editor');
                if (el) el.value = val;
            }
        },

        syncContent() {
            if (window.tinymce && window.tinymce.get('rich-editor')) {
                this.contentHtml = window.tinymce.get('rich-editor').getContent();
            } else {
                const el = document.getElementById('rich-editor');
                if (el) this.contentHtml = el.value;
            }
        },

        toggleSelectAll(students) {
            this.selectAll = !this.selectAll;
            if (this.selectAll) {
                this.selectedStudents = students.map(s => s.email || s.school_email).filter(Boolean);
            } else {
                this.selectedStudents = [];
            }
        },

        applySelectedRecipients() {
            if (this.selectedStudents.length > 0) {
                this.recipientType = 'custom_emails';
                this.recipientFilter = this.selectedStudents.join(', ');
                this.showRecipientModal = false;
            }
        }
    }">
        <!-- Header Banner Component -->
        <x-system-nav title="Professional Email Composer" subtitle="Compose rich text HTML emails, insert dynamic variables, upload attachments, manage CC/BCC, and send institutional announcements." activeTab="email">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.email-composer.index') }}"
                   class="inline-flex h-11 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 text-xs font-black uppercase tracking-wider text-white backdrop-blur-xs transition hover:bg-white/20 cursor-pointer shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-emerald-400"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </x-system-nav>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 flex items-center gap-2 shadow-xs">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600 flex-shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 flex items-center gap-2 shadow-xs">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Composer Layout -->
        <form method="POST" action="{{ route('admin.email-composer.send-bulk') }}" enctype="multipart/form-data" @submit="syncContent()">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Email Form (2 Columns) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Envelope Card (To, CC, BCC, Sender, Subject) -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="send" class="w-4 h-4 text-emerald-600"></i>
                                <span>Message Envelope & Headers</span>
                            </h2>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="showCc = !showCc"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 hover:bg-slate-50 transition"
                                        :class="showCc ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'text-slate-600'">
                                    + CC
                                </button>
                                <button type="button" @click="showBcc = !showBcc"
                                        class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 hover:bg-slate-50 transition"
                                        :class="showBcc ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'text-slate-600'">
                                    + BCC
                                </button>
                            </div>
                        </div>

                        <!-- From Sender Name -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">From Sender Name</label>
                                <input type="text" name="sender_name" x-model="senderName" required
                                       class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-bold text-slate-900 outline-none focus:bg-white focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Campaign Reference Title</label>
                                <input type="text" name="title" required value="{{ old('title', $selectedDraft->title ?? '') }}" placeholder="e.g. Q1 Student Account Advisory"
                                       class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-bold text-slate-900 outline-none focus:bg-white focus:border-emerald-500">
                            </div>
                        </div>

                        <!-- CC Field -->
                        <div x-show="showCc" x-cloak class="space-y-1">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600">CC (Carbon Copy)</label>
                            <input type="text" name="cc_emails" x-model="ccEmails" placeholder="cc1@amis.edu.ph, cc2@amis.edu.ph"
                                   class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-semibold text-slate-900 outline-none focus:bg-white focus:border-indigo-500">
                        </div>

                        <!-- BCC Field -->
                        <div x-show="showBcc" x-cloak class="space-y-1">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600">BCC (Blind Carbon Copy)</label>
                            <input type="text" name="bcc_emails" x-model="bccEmails" placeholder="bcc1@amis.edu.ph, bcc2@amis.edu.ph"
                                   class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-semibold text-slate-900 outline-none focus:bg-white focus:border-indigo-500">
                        </div>

                        <!-- Subject Line -->
                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">
                                Email Subject Line
                            </label>
                            <input type="text" name="subject" x-model="subject" required placeholder="e.g. Official Announcement: Student Enrollment & Class Schedule"
                                   class="w-full h-11 rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-semibold text-slate-900 outline-none focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10">
                        </div>
                    </div>

                    <!-- WYSIWYG Rich Text Editor Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-emerald-600"></i>
                                <span>Rich Text Email Body</span>
                            </h2>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="syncContent(); showPreviewModal = true;"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-slate-900 px-3.5 text-xs font-black uppercase tracking-wider text-white shadow-xs hover:bg-slate-800 transition cursor-pointer">
                                    <i data-lucide="eye" class="w-3.5 h-3.5 text-emerald-400"></i>
                                    <span>Preview</span>
                                </button>
                            </div>
                        </div>

                        <!-- Dynamic Variable Tags Toolbar -->
                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-200/70 space-y-2">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 block">Click to Insert Dynamic Student Variable Tag:</span>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <button type="button" @click="insertVariable('{student_name}')" class="px-2.5 py-1 text-xs font-bold bg-white border border-slate-200 rounded-lg hover:border-emerald-500 hover:text-emerald-700 transition shadow-2xs">
                                    👤 {student_name}
                                </button>
                                <button type="button" @click="insertVariable('{student_id}')" class="px-2.5 py-1 text-xs font-bold bg-white border border-slate-200 rounded-lg hover:border-emerald-500 hover:text-emerald-700 transition shadow-2xs">
                                    🆔 {student_id}
                                </button>
                                <button type="button" @click="insertVariable('{grade_level}')" class="px-2.5 py-1 text-xs font-bold bg-white border border-slate-200 rounded-lg hover:border-emerald-500 hover:text-emerald-700 transition shadow-2xs">
                                    🎓 {grade_level}
                                </button>
                                <button type="button" @click="insertVariable('{school_year}')" class="px-2.5 py-1 text-xs font-bold bg-white border border-slate-200 rounded-lg hover:border-emerald-500 hover:text-emerald-700 transition shadow-2xs">
                                    📅 {school_year}
                                </button>
                                <button type="button" @click="insertVariable('{current_date}')" class="px-2.5 py-1 text-xs font-bold bg-white border border-slate-200 rounded-lg hover:border-emerald-500 hover:text-emerald-700 transition shadow-2xs">
                                    🕒 {current_date}
                                </button>
                            </div>
                        </div>

                        <!-- Emojis & Table Templates Bar -->
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <span class="font-bold text-slate-400">Quick Emojis:</span>
                            <button type="button" @click="insertVariable('😊')" class="hover:scale-125 transition">😊</button>
                            <button type="button" @click="insertVariable('📢')" class="hover:scale-125 transition">📢</button>
                            <button type="button" @click="insertVariable('📌')" class="hover:scale-125 transition">📌</button>
                            <button type="button" @click="insertVariable('🎓')" class="hover:scale-125 transition">🎓</button>
                            <button type="button" @click="insertVariable('⚠️')" class="hover:scale-125 transition">⚠️</button>
                            <button type="button" @click="insertVariable('✅')" class="hover:scale-125 transition">✅</button>
                            <button type="button" @click="insertVariable('📜')" class="hover:scale-125 transition">📜</button>
                            <button type="button" @click="insertVariable('💳')" class="hover:scale-125 transition">💳</button>
                        </div>

                        <!-- Textarea Body -->
                        <div>
                            <textarea id="rich-editor" name="body_html" rows="14" x-model="contentHtml"
                                      class="w-full rounded-2xl border border-slate-200 p-4 text-sm font-medium text-slate-900 bg-slate-50/30 outline-none transition focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                                      placeholder="Write your email content or select a template preset from the sidebar..."></textarea>
                        </div>
                    </div>

                    <!-- File & Media Attachments Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="paperclip" class="w-4 h-4 text-emerald-600"></i>
                                <span>Attachments & Documents</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Allowed files: JPG, PNG, PDF, DOCX, XLSX, ZIP (Max 15MB file size). Executables (.exe, .sh, .php) are restricted.</p>
                        </div>

                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center bg-slate-50/60 hover:bg-emerald-50/30 hover:border-emerald-300 transition cursor-pointer">
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-emerald-600 mx-auto mb-2"></i>
                            <span class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Drag & Drop Documents or Choose Files</span>
                            <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                                   class="text-xs font-semibold text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Column (Audience, Presets & Actions) -->
                <div class="space-y-6">
                    <!-- Recipient Selection Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
                                <span>Target Recipients</span>
                            </h2>
                            <button type="button" @click="showRecipientModal = true" class="text-xs font-bold text-indigo-600 hover:underline">
                                Multi-Select
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Target Group</label>
                            <select name="recipient_type" x-model="recipientType"
                                    class="w-full h-11 rounded-2xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-bold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white">
                                <option value="students">🎓 Student Records (Auto-Loaded)</option>
                                <option value="faculty">👨‍🏫 Faculty & Instructors</option>
                                <option value="staff">💼 School Staff & Administrators</option>
                                <option value="parents">👨‍👩‍👧 Parents & Guardians</option>
                                <option value="alumni">📜 Alumni / Graduated Students</option>
                                <option value="custom_emails">✉️ Custom Email Address List</option>
                            </select>
                        </div>

                        <div x-show="recipientType === 'students'" class="space-y-2">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600">Grade Level / Section Filter</label>
                            <select name="recipient_filter" x-model="recipientFilter"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-semibold text-slate-900 outline-none">
                                <option value="all">All Grade Levels & Sections</option>
                                @foreach($gradeLevels as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="recipientType === 'custom_emails'" x-cloak class="space-y-1">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600">Custom Recipient Emails</label>
                            <textarea name="recipient_filter" x-model="recipientFilter" rows="4" placeholder="email1@gmail.com, email2@yahoo.com"
                                      class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 p-3 text-xs font-medium text-slate-900 outline-none focus:bg-white"></textarea>
                        </div>
                    </div>

                    <!-- Presets Directory Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="layout-template" class="w-4 h-4 text-indigo-600"></i>
                                <span>Template Directory</span>
                            </h2>
                            <span class="text-xs font-bold text-slate-400">{{ count($templates) }} presets</span>
                        </div>

                        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                            @foreach($templates as $t)
                                <div class="p-3 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-emerald-50/60 hover:border-emerald-300 transition cursor-pointer"
                                     @click="loadTemplate('{{ addslashes($t->subject) }}', '{{ addslashes($t->body_html) }}')">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold text-slate-900 block truncate">{{ $t->name }}</span>
                                        <span class="px-2 py-0.5 text-[9px] font-black uppercase bg-emerald-100 text-emerald-800 rounded-md">{{ $t->category }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500 block truncate mt-0.5">{{ $t->subject }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Action Controls Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-3">
                        <button type="submit" onclick="return confirm('Dispatch official bulk email campaign now?')"
                                class="w-full inline-flex h-14 items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-600/30 transition hover:opacity-95 active:scale-[0.98] cursor-pointer">
                            <i data-lucide="send" class="w-5 h-5"></i>
                            <span>Send Email Campaign Now</span>
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="showTestModal = true"
                                    class="w-full h-11 rounded-xl border border-indigo-200 bg-indigo-50 text-xs font-black uppercase tracking-wider text-indigo-700 hover:bg-indigo-100 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="mail-check" class="w-4 h-4"></i> Test Email
                            </button>

                            <button type="submit" formaction="{{ route('admin.email-composer.drafts.save') }}"
                                    class="w-full h-11 rounded-xl border border-slate-200 bg-slate-50 text-xs font-black uppercase tracking-wider text-slate-700 hover:bg-slate-100 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i> Save Draft
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Test Email Modal -->
        <template x-teleport="body">
            <div x-show="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs" x-cloak>
                <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
                    <form method="POST" action="{{ route('admin.email-composer.send-test') }}" @submit="syncContent()">
                        @csrf
                        <input type="hidden" name="subject" :value="subject">
                        <input type="hidden" name="body_html" :value="contentHtml">
                        <input type="hidden" name="sender_name" :value="senderName">
                        <input type="hidden" name="cc_emails" :value="ccEmails">
                        <input type="hidden" name="bcc_emails" :value="bccEmails">

                        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-900 text-white">
                            <h3 class="text-sm font-black uppercase tracking-wider flex items-center gap-2">
                                <i data-lucide="mail-check" class="w-4 h-4 text-emerald-400"></i> Dispatch Test Email
                            </h3>
                            <button type="button" @click="showTestModal = false" class="text-xl font-bold text-slate-400 hover:text-white">&times;</button>
                        </div>
                        <div class="p-6 space-y-4 text-slate-800">
                            <p class="text-xs text-slate-500">Send an instant test email to verify layout and delivery in your mailbox before dispatching to bulk recipients.</p>
                            <div>
                                <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Target Test Email Address</label>
                                <input type="email" name="test_email" x-model="testEmail" required
                                       class="w-full h-11 rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-900 outline-none focus:border-indigo-500">
                            </div>
                            <button type="submit" class="w-full h-12 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider shadow-md transition cursor-pointer">
                                Send Test Email Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Recipient Multi-Select Modal -->
        <template x-teleport="body">
            <div x-show="showRecipientModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs" x-cloak>
                <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-900 text-white">
                        <h3 class="text-sm font-black uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4 text-emerald-400"></i> Select Recipients from Records
                        </h3>
                        <button type="button" @click="showRecipientModal = false" class="text-xl font-bold text-slate-400 hover:text-white">&times;</button>
                    </div>
                    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-4">
                        <input type="text" x-model="searchTerm" placeholder="Search student name or email..."
                               class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-900 outline-none">
                        <button type="button" @click="toggleSelectAll({{ json_encode($students) }})" class="px-4 py-2 text-xs font-extrabold rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700">
                            Select All
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto flex-1 divide-y divide-slate-100">
                        @foreach($students as $st)
                            @php $emailVal = $st->email ?? $st->school_email; @endphp
                            @if($emailVal)
                                <label class="flex items-center justify-between py-2.5 px-3 hover:bg-slate-50 rounded-xl cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" value="{{ $emailVal }}" x-model="selectedStudents" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                        <div>
                                            <span class="text-xs font-extrabold text-slate-900 block">{{ $st->full_name ?? trim(($st->first_name ?? '') . ' ' . ($st->last_name ?? '')) ?: 'Student' }}</span>
                                            <span class="text-[11px] text-slate-400 block">{{ $emailVal }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase bg-slate-100 text-slate-600 rounded-md">{{ $st->grade_level ?? 'N/A' }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600" x-text="selectedStudents.length + ' recipients selected'"></span>
                        <button type="button" @click="applySelectedRecipients()" class="px-6 py-2.5 text-xs font-black uppercase bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-md">
                            Load Selected Recipients
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Preview Modal -->
        <template x-teleport="body">
            <div x-show="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs" x-cloak>
                <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-900 text-white">
                        <div class="flex items-center gap-3">
                            <i data-lucide="eye" class="w-5 h-5 text-emerald-400"></i>
                            <h3 class="text-sm font-black uppercase tracking-wider text-white">Live Email Render Preview</h3>
                            <div class="flex rounded-xl bg-white/10 p-1 text-xs font-bold text-white">
                                <button type="button" class="px-3 py-1 rounded-lg transition" :class="previewMode === 'desktop' ? 'bg-emerald-500 text-white shadow-xs' : 'text-slate-300'" @click="previewMode = 'desktop'">Desktop</button>
                                <button type="button" class="px-3 py-1 rounded-lg transition" :class="previewMode === 'mobile' ? 'bg-emerald-500 text-white shadow-xs' : 'text-slate-300'" @click="previewMode = 'mobile'">Mobile</button>
                            </div>
                        </div>
                        <button type="button" class="text-2xl font-bold text-slate-400 hover:text-white" @click="showPreviewModal = false">&times;</button>
                    </div>
                    <div class="p-6 overflow-y-auto flex-1 bg-slate-100 flex justify-center">
                        <div :class="previewMode === 'mobile' ? 'w-[375px]' : 'w-full max-w-2xl'" class="bg-white rounded-2xl border border-slate-200 shadow-md overflow-hidden">
                            <!-- Simulated Institutional Header -->
                            <div style="background: linear-gradient(135deg, #022c22 0%, #064e3b 50%, #0f172a 100%); padding: 24px; text-align: center; color: white; border-bottom: 4px solid #10b981;">
                                <img src="https://admin.amis.edu.ph/images/logo.png" alt="AMIS Logo" style="width: 56px; height: 56px; margin: 0 auto 10px auto; border-radius: 50%; background: white; padding: 3px;">
                                <div style="display: inline-block; background: rgba(16, 185, 129, 0.2); color: #6ee7b7; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; padding: 3px 12px; border-radius: 50px; border: 1px solid rgba(52, 211, 153, 0.3); margin-bottom: 6px;" x-text="senderName || 'AMIS Information Technology'"></div>
                                <h4 style="margin: 0; font-size: 18px; font-weight: 900; color: white;">Al-Munawwara Islamic School</h4>
                                <p style="margin: 4px 0 0 0; font-size: 11px; font-weight: 600; color: #a7f3d0;">Official Institutional Communication</p>
                            </div>
                            <div class="p-6">
                                <div class="border-b border-slate-100 pb-3 mb-4">
                                    <span class="text-xs text-slate-400 block font-bold uppercase tracking-wider">Subject:</span>
                                    <h4 class="text-base font-black text-slate-900" x-text="subject || '(No Subject Specified)'"></h4>
                                </div>
                                <div class="text-sm text-slate-800 leading-relaxed font-sans" x-html="contentHtml || '<p class=text-slate-400 italic>Email body content will render here...</p>'"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
