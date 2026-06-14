<div x-show="activeWorkspace === 'advisory'" x-transition class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
    @foreach($advisories as $advisory)
        <div class="bg-white border border-gray-150 rounded-2xl shadow-xs p-5 hover:shadow-md transition duration-250 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-start">
                    <div>
                        <span class="font-extrabold text-slate-900 text-sm block tracking-wide">
                            {{ $advisory['grade'] }} - {{ $advisory['grade_level'] }}
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold block uppercase tracking-wider mt-0.5">
                            {{ $advisory['department'] }}
                        </span>
                    </div>
                    <x-badge color="blue">ADVISORY</x-badge>
                </div>
                <div class="mt-4 pt-3.5 border-t border-slate-100 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-indigo-650 font-black text-xxs flex items-center justify-center shrink-0">
                        {{ $advisory['initials'] }}
                    </div>
                    <div>
                        <span class="font-extrabold text-slate-900 text-xs block uppercase">{{ $advisory['teacher'] }}</span>
                        <span class="text-[10px] text-slate-400 font-semibold mt-0.5 block uppercase tracking-wide">Advisor assigned</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
