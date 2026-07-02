<div x-show="activeWorkspace === 'sections'" x-transition class="space-y-6">
    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Sections</div>
            <div class="text-3xl font-black text-slate-900">{{ $sectionsStats['total_sections'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Face-to-Face</div>
            <div class="text-3xl font-black text-slate-900">{{ $sectionsStats['f2f_count'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Flexible Online</div>
            <div class="text-3xl font-black text-slate-900">{{ $sectionsStats['flex_count'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Enrolled (MS)</div>
            <div class="text-3xl font-black text-slate-900">{{ number_format($sectionsStats['total_enrolled']) }}</div>
        </div>
    </div>

    {{-- Search + Action bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="relative w-full sm:max-w-xs">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"></i>
            <input type="search" x-model="search" placeholder="Search sections…"
                class="w-full bg-white border border-slate-200 text-slate-800 text-sm rounded-xl pl-9 pr-4 py-2 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none font-medium transition">
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.ms-teams.structure') }}"
               class="inline-flex items-center gap-2 border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-800 font-bold text-xs px-4 py-2 rounded-xl transition-all shadow-3xs">
                <i data-lucide="network" class="w-3.5 h-3.5"></i>
                Teams Structure
            </a>
            <button type="button" @click="createModal = true"
                class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-bold text-xs px-4 py-2 rounded-xl transition-all shadow-3xs cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                Add Section
            </button>
        </div>
    </div>

    {{-- Grade cards grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($groupedSections as $grade => $gradeSections)
            @php
                $firstSection = $gradeSections->first();
                $gradeAdvisor = $firstSection?->grade_advisor;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden"
                 x-show="search === '' || '{{ strtolower($grade . ' ' . $gradeSections->pluck('name')->implode(' ')) }}'.includes(search.toLowerCase())">

                {{-- Grade card header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-extrabold text-slate-900 text-sm tracking-tight uppercase">{{ $grade }}</div>
                            @if($gradeAdvisor)
                                <div class="text-[10px] font-bold text-teal-700 mt-0.5">{{ $gradeAdvisor->teacher_name }}</div>
                            @endif
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                        {{ $gradeSections->count() }} {{ Str::plural('section', $gradeSections->count()) }}
                    </span>
                </div>

                {{-- Sections list --}}
                <div class="divide-y divide-slate-50 p-3 space-y-1">
                    @foreach($gradeSections as $section)
                        @php
                            $isFlex    = str_contains($section->learning_mode ?? '', 'Flexible');
                            $modeBg    = $isFlex ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700';
                            $genderBg  = $section->gender === 'male' ? 'bg-indigo-50 text-indigo-700' : ($section->gender === 'female' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700');
                            $genderLbl = $section->gender === 'male' ? 'Boys' : ($section->gender === 'female' ? 'Girls' : 'Merge');
                            $sectionName = $section->name ?? null;
                        @endphp
                        <div class="flex items-center justify-between gap-3 rounded-xl px-3 py-3 hover:bg-slate-50 transition-colors"
                             x-show="search === '' || '{{ strtolower($section->grade_level . ' ' . $section->name) }}'.includes(search.toLowerCase())">

                            {{-- Left: name + badges --}}
                            <div class="min-w-0 flex-1">
                                <div class="font-extrabold text-slate-900 text-sm uppercase tracking-tight truncate">
                                    {{ $sectionName ?? '—' }}
                                </div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[9px] font-bold {{ $modeBg }}">
                                        {{ $isFlex ? 'FOL' : 'F2F' }}
                                        @if($section->shift) · {{ Str::before($section->shift, ' Shift') }}S @endif
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 rounded-md text-[9px] font-bold {{ $genderBg }}">{{ $genderLbl }}</span>
                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                        <i data-lucide="users" class="w-2.5 h-2.5"></i>{{ $section->enrolled_count }}
                                    </span>
                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                        <i data-lucide="book-open" class="w-2.5 h-2.5"></i>{{ $section->subjects_count }}
                                    </span>
                                </div>
                            </div>

                            {{-- Right: actions --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button"
                                    @click.stop="openEdit({{ $section->id }}, '{{ addslashes($section->name ?? '') }}')"
                                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-400 hover:text-slate-700 hover:border-slate-300 flex items-center justify-center transition cursor-pointer">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="{{ route('admin.ms-teams.show', $section) }}"
                                   class="inline-flex items-center gap-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-[10px] px-3 py-1.5 rounded-lg transition">
                                    Manage <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-100 rounded-2xl p-12 text-center shadow-xs">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mx-auto mb-4">
                    <i data-lucide="school" class="w-6 h-6"></i>
                </div>
                <div class="font-extrabold text-slate-900 text-sm mb-1">No Active Sections</div>
                <p class="text-xs text-slate-500 mb-4">Start by adding grade sections for the current school year.</p>
                <button type="button" @click="createModal = true"
                    class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs px-4 py-2 rounded-xl transition cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add First Section
                </button>
            </div>
        @endforelse
    </div>
</div>
