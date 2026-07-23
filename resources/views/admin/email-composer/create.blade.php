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
        <!-- Top Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-black uppercase tracking-wider text-indigo-600">Email Composer Workspace</span>
                <h1 class="text-2xl font-black text-slate-900">Compose & Dispatch Email</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.email-composer.index') }}" class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-600"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.email-composer.send-bulk') }}" enctype="multipart/form-data" @submit="syncContent()">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Main Column (Composer Form) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Campaign Details & Subject -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <i data-lucide="edit-3" class="w-4 h-4 text-indigo-600"></i> Campaign Header Details
                        </h2>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Campaign Title (Internal Reference)</label>
                            <input type="text" name="title" required placeholder="e.g. Q1 Enrollment Advisory Announcement"
                                   class="w-full h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Email Subject (Recipient Inbox)</label>
                            <input type="text" name="subject" x-model="subject" required placeholder="e.g. Official Notice: Student Enrollment Credentials"
                                   class="w-full h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100">
                        </div>
                    </div>

                    <!-- Rich Text Editor Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4 text-indigo-600"></i> Rich Text Email Body
                            </h2>
                            <button type="button" @click="syncContent(); showPreviewModal = true;"
                                    class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 text-xs font-black uppercase tracking-wider text-indigo-700 transition hover:bg-indigo-100 cursor-pointer">
                                <i data-lucide="eye" class="w-4 h-4"></i> Live Preview
                            </button>
                        </div>

                        <!-- Editor Textarea -->
                        <div>
                            <textarea id="rich-editor" name="body_html" rows="12"
                                      class="w-full rounded-2xl border border-slate-200 p-4 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                      placeholder="Type your HTML or rich text email content here..."></textarea>
                        </div>
                    </div>

                    <!-- File & Image Attachments Card -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <i data-lucide="paperclip" class="w-4 h-4 text-indigo-600"></i> Attachments & Media Upload
                        </h2>
                        <p class="text-xs text-slate-500">Supported types: JPG, PNG, GIF, PDF, DOCX, XLSX, ZIP (Max 15MB file size). Executable script extensions (.exe, .php, .sh) are automatically blocked.</p>

                        <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center bg-slate-50/50 hover:bg-slate-50 transition cursor-pointer">
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-indigo-500 mx-auto mb-2"></i>
                            <span class="block text-xs font-black text-slate-700 uppercase tracking-wider">Drag & drop files or click to choose</span>
                            <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="mt-2 text-xs text-slate-500 cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar Column (Target Recipients & Presets) -->
                <div class="space-y-6">
                    <!-- Recipient Selection Panel -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-black uppercase tracking-wider text-indigo-700 flex items-center gap-2">
                            <i data-lucide="users" class="w-4 h-4"></i> Target Recipients
                        </h2>

                        <div>
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Target Group</label>
                            <select name="recipient_type" x-model="recipientType"
                                    class="w-full h-11 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-800 outline-none transition focus:border-indigo-500">
                                <option value="students">🎓 Student Records (Auto-Loaded)</option>
                                <option value="faculty">👨‍🏫 Faculty & Instructors</option>
                                <option value="staff">💼 School Staff & Administrators</option>
                                <option value="alumni">📜 Alumni / Graduated Students</option>
                                <option value="custom_emails">✉️ Custom Email Addresses List</option>
                            </select>
                        </div>

                        <!-- Filter Options for Students -->
                        <div x-show="recipientType === 'students'" class="space-y-3 pt-2">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Grade Level Filter</label>
                            <select name="recipient_filter" x-model="recipientFilter"
                                    class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 outline-none">
                                <option value="all">All Grade Levels</option>
                                @foreach($gradeLevels as $g)
                                    <option value="{{ $g }}">{{ $g }}</option>
                                @endforeach
                            </select>

                            <button type="button" @click="showStudentModal = true" class="w-full inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                                <i data-lucide="search" class="w-3.5 h-3.5"></i> Select Specific Students
                            </button>
                        </div>

                        <!-- Custom Email List Input -->
                        <div x-show="recipientType === 'custom_emails'" class="space-y-2 pt-2">
                            <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-1">Enter Emails (Comma/Space Separated)</label>
                            <textarea name="recipient_filter" rows="4" placeholder="parent1@gmail.com, parent2@yahoo.com"
                                      class="w-full rounded-xl border border-slate-200 bg-white p-3 text-xs font-medium text-slate-800 outline-none"></textarea>
                        </div>
                    </div>

                    <!-- Presets & Templates Picker -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-4">
                        <h2 class="text-sm font-black uppercase tracking-wider text-indigo-700 flex items-center gap-2">
                            <i data-lucide="layout-template" class="w-4 h-4"></i> Reusable Templates
                        </h2>

                        <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                            @foreach($templates as $t)
                                <div class="p-3 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-indigo-50/50 hover:border-indigo-200 transition cursor-pointer"
                                     @click="loadTemplate('{{ addslashes($t->subject) }}', '{{ addslashes($t->body_html) }}')">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-extrabold text-slate-900 block truncate">{{ $t->name }}</span>
                                        <span class="px-2 py-0.5 text-[9px] font-black uppercase bg-indigo-100 text-indigo-700 rounded-md">{{ $t->category }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400 block truncate mt-0.5">{{ $t->subject }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dispatch Controls -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm space-y-3">
                        <button type="submit" onclick="return confirm('Queue and dispatch bulk email campaign now?')"
                                class="w-full inline-flex h-12 items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-600 text-xs font-black uppercase tracking-wider text-white shadow-lg shadow-indigo-950/20 transition hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                            <i data-lucide="send" class="w-5 h-5"></i> Dispatch Bulk Email Queue
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Preview Modal -->
        <template x-teleport="body">
            <div x-show="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs" x-cloak>
                <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-black uppercase text-slate-800">Email Live Preview</h3>
                            <div class="flex rounded-xl bg-slate-200 p-1 text-xs font-bold text-slate-600">
                                <button type="button" class="px-3 py-1 rounded-lg" :class="previewMode === 'desktop' ? 'bg-white text-indigo-700 shadow-xs' : ''" @click="previewMode = 'desktop'">Desktop</button>
                                <button type="button" class="px-3 py-1 rounded-lg" :class="previewMode === 'mobile' ? 'bg-white text-indigo-700 shadow-xs' : ''" @click="previewMode = 'mobile'">Mobile</button>
                            </div>
                        </div>
                        <button type="button" class="text-2xl font-bold text-slate-400 hover:text-slate-600" @click="showPreviewModal = false">&times;</button>
                    </div>
                    <div class="p-6 overflow-y-auto flex-1 bg-slate-100 flex justify-center">
                        <div :class="previewMode === 'mobile' ? 'w-[375px]' : 'w-full max-w-2xl'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-md">
                            <div class="border-b border-slate-100 pb-3 mb-4">
                                <span class="text-xs text-slate-400 block font-bold">Subject:</span>
                                <h4 class="text-lg font-black text-slate-900" x-text="subject || '(No Subject Specified)'"></h4>
                            </div>
                            <div class="text-sm text-slate-800 leading-relaxed font-sans" x-html="contentHtml || '<p class=text-slate-400 italic>Email body is empty...</p>'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
