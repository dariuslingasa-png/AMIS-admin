<x-admin-layout title="MS Teams Structure Explorer">
    @php
        $breadcrumbs = [
            ['label' => 'Class Sections', 'href' => route('admin.ms-teams.index')],
            ['label' => 'Teams Structure', 'href' => null],
        ];

        $gradeOrder = [
            'Kinder 1' => 1, 'Kinder 2' => 2,
            'Grade 1'  => 3,  'Grade 2'  => 4,  'Grade 3'  => 5,  'Grade 4'  => 6,
            'Grade 5'  => 7,  'Grade 6'  => 8,  'Grade 7'  => 9,  'Grade 8'  => 10,
            'Grade 9'  => 11, 'Grade 10' => 12, 'Grade 11' => 13, 'Grade 12' => 14,
        ];
        $grouped = $sections->groupBy('grade_level')
            ->sortBy(fn($v, $k) => $gradeOrder[$k] ?? 99);
    @endphp

    {{-- ─── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-purple-700 mb-1">Microsoft Teams</p>
            <h1 class="text-2xl font-extrabold text-slate-950 tracking-tight">Teams Structure Explorer</h1>
            <p class="mt-1 text-sm text-slate-500">Live view of every class Team — its channels and members — pulled directly from Microsoft Graph.</p>
        </div>
        <a href="{{ route('admin.ms-teams.index') }}"
           class="inline-flex items-center gap-2 shrink-0 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm px-4 py-2.5 rounded-xl transition shadow-xs">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
        </a>
    </div>

    {{-- ─── Summary Stats ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Teams Linked</div>
            <div class="text-3xl font-black text-slate-900">{{ $stats['total_sections'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">No Team Yet</div>
            <div class="text-3xl font-black text-rose-600">{{ $stats['no_team'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs">
            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Grade Groups</div>
            <div class="text-3xl font-black text-slate-900">{{ $grouped->count() }}</div>
        </div>
    </div>

    {{-- ─── Search ───────────────────────────────────────────────────────────── --}}
    <div x-data="{ search: '' }" class="mb-6">
        <div class="relative max-w-sm">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
            <input type="search" x-model="search" placeholder="Search by grade, section, shift…"
                class="w-full bg-white border border-slate-200 text-sm rounded-xl pl-10 pr-4 py-2.5 outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 font-medium transition">
        </div>

        {{-- ─── Org Tree ──────────────────────────────────────────────────────── --}}
        <div class="space-y-8 mt-6">
            @forelse ($grouped as $grade => $gradeSections)
                @php $gradeSlug = Str::slug($grade); @endphp

                <div x-show="search === '' || '{{ strtolower($grade) }}'.includes(search.toLowerCase())">

                    {{-- Grade Row --}}
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center shrink-0">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide">{{ $grade }}</h2>
                        <div class="flex-1 h-px bg-slate-100"></div>
                        <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-full">
                            {{ $gradeSections->count() }} {{ Str::plural('team', $gradeSections->count()) }}
                        </span>
                    </div>

                    {{-- Section Cards --}}
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 ml-4 pl-4 border-l-2 border-slate-100">
                        @foreach ($gradeSections as $section)
                            @php
                                $isFlex     = str_contains($section->learning_mode ?? '', 'Flexible');
                                $isGirls    = $section->gender === 'female';
                                $shiftLabel = $section->shift ? ($section->shift === '1st Shift' ? '1st Shift' : '2nd Shift') : null;
                                $modeBg     = $isFlex
                                    ? 'bg-purple-50 text-purple-700 border-purple-100'
                                    : 'bg-blue-50 text-blue-700 border-blue-100';
                                $genderBg   = $isGirls
                                    ? 'bg-rose-50 text-rose-700'
                                    : 'bg-indigo-50 text-indigo-700';
                                $avatarGrad = $isGirls
                                    ? 'from-rose-400 to-pink-600'
                                    : 'from-indigo-500 to-blue-700';
                            @endphp

                            {{-- Team Card --}}
                            <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden"
                                 x-data="{
                                     sectionId: {{ $section->id }},
                                     open: false,
                                     loading: false,
                                     loaded: false,
                                     error: '',
                                     data: null,
                                     async toggle() {
                                         if (!this.loaded) {
                                             this.open    = true;
                                             this.loading = true;
                                             this.error   = '';
                                             try {
                                                 const res  = await fetch('/ms-teams/structure/data?section_id=' + this.sectionId, {
                                                     headers: { 'Accept': 'application/json' }
                                                 });
                                                 const json = await res.json();
                                                 if (json.success) {
                                                     this.data   = json;
                                                     this.loaded = true;
                                                 } else {
                                                     this.error = json.message || 'Failed to load.';
                                                     this.open  = false;
                                                 }
                                             } catch(e) {
                                                 this.error = 'Network error. Try again.';
                                                 this.open  = false;
                                             }
                                             this.loading = false;
                                         } else {
                                             this.open = !this.open;
                                         }
                                     }
                                 }"
                                 x-show="search === '' || '{{ strtolower($grade . ' ' . ($section->name ?? '') . ' ' . ($section->shift ?? '')) }}'.includes(search.toLowerCase())">

                                {{-- ── Header Button ──────────────────────────── --}}
                                <button type="button" @click="toggle()"
                                    class="w-full flex items-start justify-between gap-3 px-5 py-4 hover:bg-slate-50/70 transition text-left group">

                                    <div class="flex items-start gap-3 min-w-0">
                                        {{-- Avatar --}}
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $avatarGrad }} flex items-center justify-center text-white shrink-0 shadow-sm">
                                            <i data-lucide="users" class="w-5 h-5"></i>
                                        </div>

                                        <div class="min-w-0">
                                            {{-- Team name --}}
                                            <div class="font-extrabold text-slate-900 text-sm uppercase tracking-tight">
                                                {{ $section->grade_level }}@if($section->name) &mdash; {{ $section->name }}@endif
                                            </div>

                                            {{-- Badges --}}
                                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold border {{ $modeBg }}">
                                                    {{ $isFlex ? 'ODL' : 'F2F' }}@if($shiftLabel) &middot; {{ $shiftLabel }}@endif
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold {{ $genderBg }}">
                                                    {{ $isGirls ? 'Girls' : 'Boys' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 text-[9px] font-mono text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                                                    {{ Str::limit($section->ms_team_id, 8, '') }}…
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right: status/toggle --}}
                                    <div class="flex items-center gap-2 shrink-0 pt-1">
                                        <template x-if="loading">
                                            <svg class="animate-spin h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </template>
                                        <template x-if="!loading && loaded">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] font-bold text-slate-500" x-text="(data?.total_members ?? 0) + ' member' + ((data?.total_members ?? 0) !== 1 ? 's' : '')"></span>
                                                <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                            </div>
                                        </template>
                                        <template x-if="!loading && !loaded">
                                            <span class="text-[9px] font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-lg border border-purple-100 group-hover:bg-purple-100 transition">
                                                Load ↓
                                            </span>
                                        </template>
                                    </div>
                                </button>

                                {{-- Error strip --}}
                                <div x-show="error" x-transition
                                     class="mx-5 mb-3 rounded-xl bg-rose-50 border border-rose-100 px-4 py-2 text-xs font-bold text-rose-700"
                                     x-text="error"></div>

                                {{-- ── Channels Expanded ───────────────────────── --}}
                                <div x-show="open && loaded && data" x-transition class="border-t border-slate-100">

                                    {{-- Channels header bar --}}
                                    <div class="flex items-center justify-between px-5 py-2.5 bg-slate-50/70 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="layers" class="w-3.5 h-3.5 text-slate-400"></i>
                                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Channels</span>
                                            <span class="text-[9px] font-bold text-slate-400 bg-white border border-slate-100 px-1.5 py-0.5 rounded-full"
                                                  x-text="data?.channels?.length ?? 0"></span>
                                        </div>
                                        <a :href="data?.team_url || '#'" target="_blank"
                                           class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-700 hover:text-purple-900 transition bg-purple-50 hover:bg-purple-100 px-2.5 py-1 rounded-lg border border-purple-100">
                                            <i data-lucide="external-link" class="w-3 h-3"></i> Open in Teams
                                        </a>
                                    </div>

                                    {{-- Channel List --}}
                                    <div class="divide-y divide-slate-50">
                                        <template x-for="ch in (data?.channels ?? [])" :key="ch.id">
                                            <div x-data="{ membersOpen: false }">

                                                {{-- Channel Row --}}
                                                <button type="button" @click="membersOpen = !membersOpen"
                                                    class="w-full flex items-center justify-between px-5 py-3 hover:bg-slate-50/50 transition text-left">

                                                    <div class="flex items-center gap-3 min-w-0">
                                                        {{-- Channel icon --}}
                                                        <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                                             :class="{
                                                                'bg-amber-100 text-amber-700': ch.type === 'private',
                                                                'bg-slate-100 text-slate-500': ch.type !== 'private'
                                                             }">
                                                            <template x-if="ch.type === 'private'">
                                                                <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                                                            </template>
                                                            <template x-if="ch.type !== 'private'">
                                                                <i data-lucide="hash" class="w-3.5 h-3.5"></i>
                                                            </template>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <div class="font-bold text-slate-800 text-xs truncate" x-text="ch.name"></div>
                                                            <div class="flex items-center gap-2 mt-0.5">
                                                                <span class="text-[9px] font-semibold"
                                                                      :class="ch.type === 'private' ? 'text-amber-600' : 'text-slate-400'"
                                                                      x-text="ch.type === 'private' ? '🔒 Private Channel' : '# Standard Channel'"></span>
                                                                <template x-if="ch.teacher_name">
                                                                    <span class="text-[9px] font-bold text-teal-700"
                                                                          x-text="'· ' + ch.teacher_name"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Member count + toggle --}}
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <span class="text-[9px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full"
                                                              x-text="ch.member_count + ' member' + (ch.member_count !== 1 ? 's' : '')"></span>
                                                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200"
                                                           :class="membersOpen ? 'rotate-180' : ''"></i>
                                                    </div>
                                                </button>

                                                {{-- Member List (expandable) --}}
                                                <div x-show="membersOpen" x-transition
                                                     class="bg-gradient-to-b from-slate-50/80 to-white border-t border-slate-100/80 px-4 pb-3 pt-2">

                                                    <template x-if="ch.members.length === 0">
                                                        <p class="text-center text-[10px] text-slate-400 font-bold py-3">
                                                            No members in this channel.
                                                        </p>
                                                    </template>

                                                    <div class="space-y-1 max-h-52 overflow-y-auto">
                                                        <template x-for="(m, idx) in ch.members" :key="idx">
                                                            <div class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-white hover:shadow-xs transition-all border border-transparent hover:border-slate-100">
                                                                <div class="flex items-center gap-2.5 min-w-0">
                                                                    {{-- Avatar --}}
                                                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-black shrink-0 uppercase border"
                                                                         :class="m.role === 'owner'
                                                                            ? 'bg-amber-100 text-amber-700 border-amber-200'
                                                                            : 'bg-slate-100 text-slate-600 border-slate-200'"
                                                                         x-text="m.displayName.split(' ').filter(Boolean).map(w => w[0]).join('').toUpperCase().slice(0,2)">
                                                                    </div>
                                                                    <div class="min-w-0">
                                                                        <div class="font-bold text-slate-800 text-[10px] truncate uppercase"
                                                                             x-text="m.displayName"></div>
                                                                        <div class="text-[9px] font-mono text-slate-400 truncate"
                                                                             x-text="m.email ?? '—'"></div>
                                                                    </div>
                                                                </div>
                                                                <span class="shrink-0 text-[8px] font-black px-2 py-0.5 rounded-full uppercase"
                                                                      :class="m.role === 'owner'
                                                                         ? 'bg-amber-100 text-amber-700 border border-amber-200'
                                                                         : 'bg-slate-100 text-slate-500'"
                                                                      x-text="m.role"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                            </div>
                                        </template>
                                    </div>

                                    {{-- No channels --}}
                                    <template x-if="!data?.channels?.length">
                                        <div class="px-5 py-6 text-center">
                                            <p class="text-xs font-bold text-slate-400">No channels found in this team.</p>
                                        </div>
                                    </template>
                                </div>

                                {{-- Unloaded hint --}}
                                <div x-show="!loaded && !loading && !error" class="px-5 py-3 border-t border-slate-50">
                                    <p class="text-center text-[10px] text-slate-400 font-bold">
                                        Click to load from Microsoft Graph
                                    </p>
                                </div>

                            </div>{{-- /team card --}}
                        @endforeach
                    </div>

                </div>{{-- /grade block --}}
            @empty
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-400 mx-auto mb-4">
                        <i data-lucide="network" class="w-6 h-6"></i>
                    </div>
                    <div class="font-extrabold text-slate-900 text-sm mb-1">No Teams Linked</div>
                    <p class="text-xs text-slate-500">No sections have an MS Team ID assigned yet.</p>
                </div>
            @endforelse
        </div>
    </div>{{-- /search wrapper --}}

</x-admin-layout>
