@php
    $cardColors = [
        'bg-purple-50/50 border-purple-100 text-purple-600',
        'bg-emerald-50/50 border-emerald-100 text-emerald-600',
        'bg-sky-50/50 border-sky-100 text-sky-600',
        'bg-amber-50/50 border-amber-100 text-amber-600',
        'bg-rose-50/50 border-rose-100 text-rose-600',
    ];
@endphp

<div x-show="activeWorkspace === 'schedule'" x-transition class="space-y-6">
    <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-xs">
        <label class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 block mb-2">Select Class Group Schedule</label>
        <div class="flex flex-wrap gap-1.5">
            @foreach($sections as $section)
                <button type="button" @click="activeSectionId = {{ $section->id }}"
                    :class="activeSectionId === {{ $section->id }} ? 'bg-indigo-700 text-white border-indigo-700 shadow-xs font-bold' : 'bg-gray-50 text-slate-600 hover:bg-gray-100 border-slate-200'"
                    class="px-3.5 py-2 text-xs rounded-xl border transition cursor-pointer shadow-3xs">
                    {{ $section->grade_level }} @if($section->name) — {{ $section->name }} @endif
                </button>
            @endforeach
        </div>
    </div>

    @foreach($sections as $section)
        @php($entries = $schedulesBySection->get($section->id, collect()))
        <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-6 space-y-5" x-show="activeSectionId === {{ $section->id }}" x-transition>
            <div class="border-b border-slate-100 pb-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <span class="text-slate-900 font-extrabold text-base block">
                        {{ $section->grade_level }} @if($section->name) — {{ $section->name }} @endif Timetable
                    </span>
                    <span class="mt-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        {{ $entries->count() }} scheduled classes
                    </span>
                </div>
                <x-badge color="indigo">{{ $section->learning_mode }}</x-badge>
            </div>

            @if($entries->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                    <i data-lucide="calendar-plus" class="mx-auto h-6 w-6 text-slate-400"></i>
                    <p class="mt-2 text-xs font-bold text-slate-500">No classes scheduled yet.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 text-xs font-semibold text-slate-700">
                    @foreach($entries as $entry)
                        @php
                            $tone = $cardColors[$loop->index % count($cardColors)];
                            $payload = base64_encode(json_encode($entry['payload'] + [
                                'update_url' => route('admin.academic.schedules.update', $entry['id']),
                                'destroy_url' => route('admin.academic.schedules.destroy', $entry['id']),
                            ]));
                        @endphp
                        <div class="p-4 border rounded-xl shadow-3xs {{ $tone }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span class="block text-[9px] uppercase tracking-wider font-bold mb-1">
                                        {{ $entry['day'] }} · {{ $entry['time_label'] }}
                                    </span>
                                    <span class="block font-extrabold text-slate-900 text-sm">{{ $entry['subject_name'] }}</span>
                                    <span class="text-[10px] text-slate-500 mt-1 block">{{ $entry['teacher_name'] }}</span>
                                </div>
                                <div class="flex gap-1">
                                    <button type="button" data-entry="{{ $payload }}" @click="openEdit(JSON.parse(atob($el.dataset.entry)))" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/80 text-slate-600 ring-1 ring-slate-200 hover:text-indigo-700" title="Edit schedule">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button type="button" data-entry="{{ $payload }}" @click="openDelete(JSON.parse(atob($el.dataset.entry)))" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/80 text-rose-500 ring-1 ring-rose-100 hover:bg-rose-50" title="Delete schedule">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
