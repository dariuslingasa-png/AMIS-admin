<x-admin-layout title="Compose Email">
    <div class="space-y-6" x-data="{
        recipientType: 'students',
        recipientFilter: 'all',
        subject: '',
        contentHtml: '',
        showPreviewModal: false,
        previewMode: 'desktop',
        showStudentModal: false,
        testEmail: '{{ auth()->user()->email ?? 'admin@amis.edu.ph' }}',

        loadTemplate(subjectVal, bodyVal) {
            this.subject = subjectVal;
            this.contentHtml = bodyVal;
            if (window.tinymce && window.tinymce.get('rich-editor')) {
                window.tinymce.get('rich-editor').setContent(bodyVal);
            } else {
                const el = document.getElementById('rich-editor');
                if (el) el.value = bodyVal;
            }
        },

        syncContent() {
            if (window.tinymce && window.tinymce.get('rich-editor')) {
                this.contentHtml = window.tinymce.get('rich-editor').getContent();
            } else {
                const el = document.getElementById('rich-editor');
                if (el) this.contentHtml = el.value;
            }
        }
    }">
        <!-- Top Workspace Banner -->
        <x-system-nav title="Compose & Dispatch Email" subtitle="Compose rich text HTML emails, select recipients from Student & Faculty records, attach documents, and dispatch bulk email queues with automatic Multi-SMTP failover." activeTab="email">
            <a href="{{ route('admin.email-composer.index') }}"
               class="inline-flex h-11 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 text-xs font-black uppercase tracking-wider text-white backdrop-blur-xs transition hover:bg-white/20 cursor-pointer shadow-sm">
                <i data-lucide="arrow-left" class="w-4 h-4 text-emerald-400"></i>
                <span>Back to Dashboard</span>
            </a>
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

        <form method="POST" action="{{ route('admin.email-composer.send-bulk') }}" enctype="multipart/form-data" @submit="syncContent()">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Main Column (Composer Form) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Campaign Details & Subject Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="edit-3" class="w-4 h-4 text-emerald-600"></i>
                                <span>Campaign Subject & Header</span>
                            </h2>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100">
                                Sender: AMIS Information Technology
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
                                Campaign Title <span class="text-slate-400 font-normal">(Internal Reference Name)</span>
                            </label>
                            <input type="text" name="title" required placeholder="e.g. Q1 Enrollment Credentials Advisory"
                                   class="w-full h-11 rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">
                                Email Subject Line <span class="text-slate-400 font-normal">(Displayed in Recipient Inbox)</span>
                            </label>
                            <input type="text" name="subject" x-model="subject" required placeholder="e.g. Official Notice: Your Student Portal Credentials"
                                   class="w-full h-11 rounded-2xl border border-slate-200 bg-slate-50/50 px-4 text-sm font-semibold text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10">
                        </div>
                    </div>

                    <!-- Rich Text Editor Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-emerald-600"></i>
                                <span>Rich Text Email Body</span>
                            </h2>
                            <button type="button" @click="syncContent(); showPreviewModal = true;"
                                    class="inline-flex h-9 items-center gap-2 rounded-xl bg-slate-900 px-4 text-xs font-black uppercase tracking-wider text-white shadow-sm hover:bg-slate-800 transition cursor-pointer">
                                <i data-lucide="eye" class="w-4 h-4 text-emerald-400"></i>
                                <span>Live HTML Preview</span>
                            </button>
                        </div>

                        <!-- Editor Textarea -->
                        <div>
                            <textarea id="rich-editor" name="body_html" rows="12"
                                      class="w-full rounded-2xl border border-slate-200 p-4 text-sm font-medium text-slate-900 bg-slate-50/30 outline-none transition focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10"
                                      placeholder="Type or paste your HTML email content here..."></textarea>
                        </div>
                    </div>

                    <!-- File & Image Attachments Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="paperclip" class="w-4 h-4 text-emerald-600"></i>
                                <span>Attachments & Documents Upload</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Supported formats: JPG, PNG, GIF, PDF, DOCX, XLSX, ZIP (Max 15MB size per file). Executable scripts (.exe, .php, .sh) are automatically blocked.</p>
                        </div>

                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center bg-slate-50/60 hover:bg-emerald-50/30 hover:border-emerald-300 transition cursor-pointer">
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-emerald-600 mx-auto mb-2"></i>
                            <span class="block text-xs font-black text-slate-800 uppercase tracking-wider mb-2">Select Documents or Drag Files Here</span>
                            <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                                   class="text-xs font-semibold text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Column (Target Recipients & Controls) -->
                <div class="space-y-6">
                    <!-- Recipient Selection Panel -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i>
                                <span>Target Audience</span>
                            </h2>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1.5">Recipient Group</label>
                            <select name="recipient_type" x-model="recipientType"
                                    class="w-full h-11 rounded-2xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-bold text-slate-900 outline-none transition focus:border-indigo-500 focus:bg-white">
                                <option value="students">🎓 Student Records (Auto-Loaded)</option>
                                <option value="faculty">👨‍🏫 Faculty & Instructors</option>
                                <option value="staff">💼 School Staff & Administrators</option>
                                <option value="alumni">📜 Alumni / Graduated Students</option>
                                <option value="custom_emails">✉️ Custom Email Address List</option>
                            </select>
                        </div>

                        <!-- Filter Options for Students -->
                        <div x-show="recipientType === 'students'" class="space-y-3 pt-1">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Grade Level Filter</label>
                            <select name="recipient_filter" x-model="recipientFilter"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-3 text-xs font-semibold text-slate-900 outline-none">
                                <option value="all">All Grade Levels</option>
                                @foreach($gradeLevels as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Custom Email List Input -->
                        <div x-show="recipientType === 'custom_emails'" class="space-y-2 pt-1" x-cloak>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-1">Enter Emails (Comma/Space Separated)</label>
                            <textarea name="recipient_filter" rows="4" placeholder="parent1@gmail.com, parent2@yahoo.com"
                                      class="w-full rounded-2xl border border-slate-200 bg-slate-50/50 p-3 text-xs font-medium text-slate-900 outline-none focus:bg-white"></textarea>
                        </div>
                    </div>

                    <!-- Presets & Templates Picker -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                                <i data-lucide="layout-template" class="w-4 h-4 text-indigo-600"></i>
                                <span>Template Presets</span>
                            </h2>
                            <a href="{{ route('admin.email-composer.templates') }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Manage</a>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
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

                    <!-- Dispatch Controls Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-3">
                        <button type="submit" onclick="return confirm('Send official email campaign now?')"
                                class="w-full inline-flex h-14 items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-emerald-600/30 transition hover:opacity-95 active:scale-[0.98] cursor-pointer">
                            <i data-lucide="send" class="w-5 h-5"></i>
                            <span>Send Email Campaign Now</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

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
                            <!-- Simulated Header -->
                            <div style="background: linear-gradient(135deg, #022c22 0%, #064e3b 50%, #0f172a 100%); padding: 24px; text-align: center; color: white; border-bottom: 4px solid #10b981;">
                                <img src="https://admin.amis.edu.ph/images/logo.png" alt="AMIS Logo" style="width: 56px; height: 56px; margin: 0 auto 10px auto; border-radius: 50%; background: white; padding: 3px;">
                                <div style="display: inline-block; background: rgba(16, 185, 129, 0.2); color: #6ee7b7; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.15em; padding: 3px 12px; border-radius: 50px; border: 1px solid rgba(52, 211, 153, 0.3); margin-bottom: 6px;">AMIS Information Technology</div>
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
