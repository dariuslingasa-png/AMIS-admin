@php
    $order = ['Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
    
    $f2fList = $sections->where('is_f2f', true)->sortBy(function($s) use ($order) {
        $idx = array_search($s->grade_level, $order);
        return $idx !== false ? $idx : 99;
    });

    $flex1List = $sections->where('is_f2f', false)->filter(function($s) {
        $shiftLower = strtolower((string) $s->shift);
        $modeLower = strtolower((string) $s->learning_mode);
        return str_contains($shiftLower, '1st') || str_contains($modeLower, '1st') || str_contains($shiftLower, '1') || str_contains($modeLower, '1');
    })->sortBy(function($s) use ($order) {
        $idx = array_search($s->grade_level, $order);
        return $idx !== false ? $idx : 99;
    });

    $flex2List = $sections->where('is_f2f', false)->filter(function($s) {
        $shiftLower = strtolower((string) $s->shift);
        $modeLower = strtolower((string) $s->learning_mode);
        return str_contains($shiftLower, '2nd') || str_contains($modeLower, '2nd') || str_contains($shiftLower, '2') || str_contains($modeLower, '2');
    })->sortBy(function($s) use ($order) {
        $idx = array_search($s->grade_level, $order);
        return $idx !== false ? $idx : 99;
    });
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- 1. FACE-TO-FACE -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-[640px]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="rounded-xl bg-emerald-50 p-2 text-emerald-600">
                    <i data-lucide="door-open" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Face-to-Face (F2F)</h3>
                    <p class="text-[10px] text-slate-400 font-semibold">Seat Limit: 30 per section</p>
                </div>
            </div>
            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">
                {{ $f2fList->count() }} Classes
            </span>
        </div>

        <div class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
            @forelse($f2fList as $sec)
                @php
                    $fillColor = $sec->fill_rate >= 90 ? 'bg-rose-500' : ($sec->fill_rate >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-white hover:shadow-xs flex flex-col justify-between gap-3 cursor-pointer select-none group" onclick="showSectionRoster('{{ $sec->id }}')" title="Click to view Advisory & student roster details for {{ $sec->grade_level }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                {{ $sec->grade_level }}
                            </span>
                            
                            <div class="flex items-center gap-1.5 mt-2">
                                <!-- Gender Tag -->
                                @if($sec->gender === 'male')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-blue-50 text-blue-600">
                                        Boys
                                    </span>
                                @elseif($sec->gender === 'female')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-pink-50 text-pink-600">
                                        Girls
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-violet-50 text-violet-600">
                                        Co-Ed
                                    </span>
                                @endif

                                <span class="text-slate-350 font-light">&middot;</span>

                                <!-- Status indicator dot -->
                                <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase {{ $sec->fill_rate >= 90 ? 'text-rose-500' : ($sec->fill_rate >= 60 ? 'text-amber-500' : 'text-emerald-600') }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sec->fill_rate >= 90 ? 'bg-rose-500 animate-pulse' : ($sec->fill_rate >= 60 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                                    {{ $sec->fill_rate >= 90 ? 'Full' : ($sec->fill_rate >= 60 ? 'Fast' : 'Open') }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs font-black text-slate-900 block leading-none">
                                {{ $sec->occupied }}<span class="text-[10px] text-slate-400 font-medium">/{{ $sec->capacity_limit }}</span>
                            </span>
                            <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider mt-1">Seats</span>
                        </div>
                    </div>

                    <div class="mt-1 space-y-1.5">
                        <div class="flex items-center justify-between text-[9px] font-bold text-slate-400">
                            <span>{{ $sec->remaining }} open seats</span>
                            <span>{{ $sec->fill_rate }}% filled</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $fillColor }} transition-all duration-500" style="width: {{ $sec->fill_rate }}%;"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-xs text-slate-400 font-medium">No F2F sections configured.</div>
            @endforelse
        </div>
    </div>

    <!-- 2. FLEXIBLE ONLINE - 1ST SHIFT -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-[640px]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="rounded-xl bg-amber-50 p-2 text-amber-600">
                    <i data-lucide="monitor" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Flexible - 1st Shift</h3>
                    <p class="text-[10px] text-slate-400 font-semibold">Seat Limit: 45 per section</p>
                </div>
            </div>
            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-100">
                {{ $flex1List->count() }} Classes
            </span>
        </div>

        <div class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
            @forelse($flex1List as $sec)
                @php
                    $fillColor = $sec->fill_rate >= 90 ? 'bg-rose-500' : ($sec->fill_rate >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-white hover:shadow-xs flex flex-col justify-between gap-3 cursor-pointer select-none group" onclick="showSectionRoster('{{ $sec->id }}')" title="Click to view Advisory & student roster details for {{ $sec->grade_level }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                {{ $sec->grade_level }}
                            </span>
                            <h4 class="font-bold text-slate-900 text-sm mt-0.5 tracking-tight leading-tight">
                                {{ $sec->official_name ?: ($sec->name ?? 'Unnamed') }}
                            </h4>
                            
                            <div class="flex items-center gap-1.5 mt-2">
                                <!-- Gender Tag -->
                                @if($sec->gender === 'male')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-blue-50 text-blue-600">
                                        Boys
                                    </span>
                                @elseif($sec->gender === 'female')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-pink-50 text-pink-600">
                                        Girls
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-violet-50 text-violet-600">
                                        Co-Ed
                                    </span>
                                @endif

                                <span class="text-slate-350 font-light">&middot;</span>

                                <!-- Status indicator dot -->
                                <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase {{ $sec->fill_rate >= 90 ? 'text-rose-500' : ($sec->fill_rate >= 60 ? 'text-amber-500' : 'text-emerald-600') }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sec->fill_rate >= 90 ? 'bg-rose-500 animate-pulse' : ($sec->fill_rate >= 60 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                                    {{ $sec->fill_rate >= 90 ? 'Full' : ($sec->fill_rate >= 60 ? 'Fast' : 'Open') }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs font-black text-slate-900 block leading-none">
                                {{ $sec->occupied }}<span class="text-[10px] text-slate-400 font-medium">/{{ $sec->capacity_limit }}</span>
                            </span>
                            <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider mt-1">Seats</span>
                        </div>
                    </div>

                    <div class="mt-1 space-y-1.5">
                        <div class="flex items-center justify-between text-[9px] font-bold text-slate-400">
                            <span>{{ $sec->remaining }} open seats</span>
                            <span>{{ $sec->fill_rate }}% filled</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $fillColor }} transition-all duration-500" style="width: {{ $sec->fill_rate }}%;"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-xs text-slate-400 font-medium">No 1st Shift sections configured.</div>
            @endforelse
        </div>
    </div>

    <!-- 3. FLEXIBLE ONLINE - 2ND SHIFT -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col h-[640px]">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4 flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="rounded-xl bg-violet-50 p-2 text-violet-650">
                    <i data-lucide="moon" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm">Flexible - 2nd Shift</h3>
                    <p class="text-[10px] text-slate-400 font-semibold">Seat Limit: 45 per section</p>
                </div>
            </div>
            <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-100">
                {{ $flex2List->count() }} Classes
            </span>
        </div>

        <div class="space-y-3 overflow-y-auto flex-1 pr-1 custom-scrollbar">
            @forelse($flex2List as $sec)
                @php
                    $fillColor = $sec->fill_rate >= 90 ? 'bg-rose-500' : ($sec->fill_rate >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                @endphp
                <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-4 transition-all duration-200 hover:border-slate-200 hover:bg-white hover:shadow-xs flex flex-col justify-between gap-3 cursor-pointer select-none group" onclick="showSectionRoster('{{ $sec->id }}')" title="Click to view Advisory & student roster details for {{ $sec->grade_level }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-500">
                                {{ $sec->grade_level }}
                            </span>
                            <h4 class="font-bold text-slate-900 text-sm mt-0.5 tracking-tight leading-tight">
                                {{ $sec->official_name ?: ($sec->name ?? 'Unnamed') }}
                            </h4>
                            
                            <div class="flex items-center gap-1.5 mt-2">
                                <!-- Gender Tag -->
                                @if($sec->gender === 'male')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-blue-50 text-blue-600">
                                        Boys
                                    </span>
                                @elseif($sec->gender === 'female')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-pink-50 text-pink-600">
                                        Girls
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase bg-violet-50 text-violet-600">
                                        Co-Ed
                                    </span>
                                @endif

                                <span class="text-slate-350 font-light">&middot;</span>

                                <!-- Status indicator dot -->
                                <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase {{ $sec->fill_rate >= 90 ? 'text-rose-500' : ($sec->fill_rate >= 60 ? 'text-amber-500' : 'text-emerald-600') }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sec->fill_rate >= 90 ? 'bg-rose-500 animate-pulse' : ($sec->fill_rate >= 60 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500') }}"></span>
                                    {{ $sec->fill_rate >= 90 ? 'Full' : ($sec->fill_rate >= 60 ? 'Fast' : 'Open') }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs font-black text-slate-900 block leading-none">
                                {{ $sec->occupied }}<span class="text-[10px] text-slate-400 font-medium">/{{ $sec->capacity_limit }}</span>
                            </span>
                            <span class="text-[9px] font-bold text-slate-400 block uppercase tracking-wider mt-1">Seats</span>
                        </div>
                    </div>

                    <div class="mt-1 space-y-1.5">
                        <div class="flex items-center justify-between text-[9px] font-bold text-slate-400">
                            <span>{{ $sec->remaining }} open seats</span>
                            <span>{{ $sec->fill_rate }}% filled</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $fillColor }} transition-all duration-500" style="width: {{ $sec->fill_rate }}%;"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-xs text-slate-400 font-medium">No 2nd Shift sections configured.</div>
            @endforelse
        </div>
    </div>
</div>
