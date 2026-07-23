<x-admin-layout title="Email Templates Directory">
    <div class="space-y-6" x-data="{ showCreateModal: false }">
        <!-- Top Action Bar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-black uppercase tracking-wider text-indigo-600">Email Templates Workspace</span>
                <h1 class="text-2xl font-black text-slate-900">Reusable Email Templates & Layouts</h1>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="showCreateModal = true"
                        class="inline-flex h-10 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-xs font-black uppercase tracking-wider text-white shadow-xs hover:bg-indigo-700 transition cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i> Create New Template
                </button>
                <a href="{{ route('admin.email-composer.index') }}" class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Templates Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($templates as $t)
                <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 text-[10px] font-black uppercase bg-indigo-100 text-indigo-700 rounded-md">{{ $t->category }}</span>
                            @if($t->is_preset)
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase bg-slate-100 text-slate-500 rounded-md">SYSTEM PRESET</span>
                            @endif
                        </div>
                        <h3 class="text-base font-black text-slate-900 mt-3">{{ $t->name }}</h3>
                        <p class="text-xs font-extrabold text-indigo-600 mt-1">Subject: {{ $t->subject }}</p>
                        <div class="mt-3 p-3 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-600 line-clamp-3 leading-relaxed font-sans">
                            {!! strip_tags($t->body_html) !!}
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <a href="{{ route('admin.email-composer.create') }}" class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1">
                            Use Template <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                        @if(!$t->is_preset)
                            <form method="POST" action="{{ route('admin.email-composer.templates.destroy', $t) }}" onsubmit="return confirm('Delete this email template?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700 cursor-pointer">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Create Template Modal -->
        <template x-teleport="body">
            <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs" x-cloak>
                <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-black uppercase text-slate-800">Create Custom Email Template</h3>
                        <button type="button" class="text-2xl font-bold text-slate-400 hover:text-slate-600" @click="showCreateModal = false">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.email-composer.templates.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-500 mb-1">Template Name</label>
                                <input type="text" name="name" required placeholder="e.g. Clearance Reminder" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-500 mb-1">Category</label>
                                <select name="category" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold">
                                    <option value="announcement">Announcement</option>
                                    <option value="event">Event</option>
                                    <option value="reminder">Reminder</option>
                                    <option value="verification">Verification</option>
                                    <option value="warning">Warning</option>
                                    <option value="certificate">Certificate</option>
                                    <option value="graduation">Graduation Notice</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase text-slate-500 mb-1">Default Subject</label>
                            <input type="text" name="subject" required placeholder="e.g. Official Notice from AMIS" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-semibold">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase text-slate-500 mb-1">HTML Body</label>
                            <textarea name="body_html" rows="6" required placeholder="<p>Dear Student...</p>" class="w-full rounded-xl border border-slate-200 p-3 text-xs font-mono"></textarea>
                        </div>

                        <div class="pt-2 flex justify-end gap-3">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl text-xs font-black uppercase bg-indigo-600 text-white hover:bg-indigo-700">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
