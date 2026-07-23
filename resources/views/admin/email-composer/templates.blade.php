<x-admin-layout title="Email Templates Directory">
    <div class="space-y-6" x-data="{ showCreateModal: false, activeTab: 'all' }">
        <!-- Banner Header Component -->
        <x-system-nav title="Institutional Email Templates Directory" subtitle="Browse, create, duplicate, and manage institutional email templates for Finance, Registrar, Student Affairs, Guidance, Library, HR, and General communication." activeTab="email">
            <div class="flex items-center gap-3">
                <button type="button" @click="showCreateModal = true"
                        class="inline-flex h-11 items-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-indigo-600 px-5 text-xs font-black uppercase tracking-wider text-white shadow-lg transition hover:scale-[1.02] cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>New Custom Template</span>
                </button>
                <a href="{{ route('admin.email-composer.index') }}"
                   class="inline-flex h-11 items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 text-xs font-black uppercase tracking-wider text-white backdrop-blur-xs transition hover:bg-white/20 cursor-pointer shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-emerald-400"></i>
                    <span>Dashboard</span>
                </a>
            </div>
        </x-system-nav>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 flex items-center gap-2 shadow-xs">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Category Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-slate-200">
            <button type="button" @click="activeTab = 'all'"
                    class="px-4 py-2 text-xs font-black uppercase tracking-wider rounded-xl transition cursor-pointer"
                    :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'">
                All Templates ({{ count($templates) }})
            </button>
            @foreach(['Finance', 'Registrar', 'Student Affairs', 'Guidance', 'Library', 'Human Resources', 'General'] as $cat)
                <button type="button" @click="activeTab = '{{ $cat }}'"
                        class="px-3.5 py-2 text-xs font-black uppercase tracking-wider rounded-xl transition cursor-pointer"
                        :class="activeTab === '{{ $cat }}' ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'">
                    {{ $cat }}
                </button>
            @endforeach
        </div>

        <!-- Templates Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($templates as $t)
                <div x-show="activeTab === 'all' || activeTab === '{{ $t->category }}'"
                     class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm flex flex-col justify-between space-y-4 hover:border-emerald-300 transition">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 rounded-md border border-emerald-200">{{ $t->category }}</span>
                            @if($t->is_preset)
                                <span class="px-2 py-0.5 text-[9px] font-black uppercase bg-slate-100 text-slate-500 rounded-md">SYSTEM PRESET</span>
                            @endif
                        </div>
                        <h3 class="text-base font-black text-slate-900 mt-3">{{ $t->name }}</h3>
                        <p class="text-xs font-extrabold text-emerald-700 mt-1 truncate">Subject: {{ $t->subject }}</p>
                        <div class="mt-3 p-3 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-600 line-clamp-3 leading-relaxed font-sans">
                            {!! strip_tags($t->body_html) !!}
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                        <a href="{{ route('admin.email-composer.create') }}" class="text-xs font-extrabold text-emerald-700 hover:text-emerald-900 hover:underline flex items-center gap-1">
                            Use Template <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>

                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('admin.email-composer.templates.duplicate', $t) }}">
                                @csrf
                                <button type="submit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition cursor-pointer" title="Duplicate Template">
                                    Duplicate
                                </button>
                            </form>

                            @if(!$t->is_preset)
                                <form method="POST" action="{{ route('admin.email-composer.templates.destroy', $t) }}" onsubmit="return confirm('Delete this custom email template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-700 cursor-pointer">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Create Template Modal -->
        <template x-teleport="body">
            <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs" x-cloak>
                <div class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-black uppercase text-slate-900">Create Custom Institutional Email Template</h3>
                        <button type="button" class="text-2xl font-bold text-slate-400 hover:text-slate-600" @click="showCreateModal = false">&times;</button>
                    </div>
                    <form method="POST" action="{{ route('admin.email-composer.templates.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Template Name</label>
                                <input type="text" name="name" required placeholder="e.g. Finance Refund Notice" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Department Category</label>
                                <select name="category" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold text-slate-900">
                                    <option value="Finance">Finance</option>
                                    <option value="Registrar">Registrar</option>
                                    <option value="Student Affairs">Student Affairs</option>
                                    <option value="Guidance">Guidance</option>
                                    <option value="Library">Library</option>
                                    <option value="Human Resources">Human Resources</option>
                                    <option value="General">General</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">Default Subject Line</label>
                            <input type="text" name="subject" required placeholder="e.g. Official Refund Statement - AMIS" class="w-full h-10 rounded-xl border border-slate-200 px-3 text-xs font-semibold">
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold uppercase text-slate-600 mb-1">HTML Content</label>
                            <textarea name="body_html" rows="6" required placeholder="<p>Dear {student_name}...</p>" class="w-full rounded-2xl border border-slate-200 p-3 text-xs font-mono"></textarea>
                        </div>

                        <div class="pt-2 flex justify-end gap-3">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200">Cancel</button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-black uppercase bg-emerald-600 text-white hover:bg-emerald-700 shadow-md">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
