<x-admin-layout
    title="Manual Attendance Entry"
    :breadcrumbs="[
        ['label' => 'Attendance', 'href' => route('admin.attendance.index')],
        ['label' => 'Manual Entry', 'href' => null],
    ]"
>
    <div class="max-w-2xl mx-auto space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
            <div class="flex items-center gap-4 border-b border-slate-100 pb-4">
                <div class="h-10 w-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shrink-0">
                    <i data-lucide="edit-3" class="h-5 w-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Record Manual Attendance Log</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Manually log or correct daily attendance entries for any student.</p>
                </div>
            </div>

            <form action="{{ route('admin.attendance.manual.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Select Student -->
                <div>
                    <label for="student_id" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Select Student</label>
                    <select 
                        name="student_id" 
                        id="student_id" 
                        required 
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:border-cyan-500 focus:bg-white transition uppercase"
                    >
                        <option value="">-- Choose a Student --</option>
                        @foreach($students as $student)
                            @php
                                $fullName = ($student->applicant->last_name ?? '') . ', ' . ($student->applicant->first_name ?? '');
                                $sectionName = $student->studentSection?->section?->name ? ' - ' . $student->studentSection->section->name : '';
                            @endphp
                            <option value="{{ $student->id }}">
                                {{ $student->student_number }} - {{ $fullName }} ({{ $student->grade_level }}{{ $sectionName }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Date -->
                    <div>
                        <label for="date" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Attendance Date</label>
                        <input 
                            type="date" 
                            name="date" 
                            id="date" 
                            required 
                            value="{{ date('Y-m-d') }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:border-cyan-500 focus:bg-white transition"
                        >
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                        <select 
                            name="status" 
                            id="status" 
                            required 
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:border-cyan-500 focus:bg-white transition"
                        >
                            <option value="PRESENT">PRESENT</option>
                            <option value="LATE">LATE</option>
                            <option value="ABSENT">ABSENT</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Time In -->
                    <div>
                        <label for="time_in" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Time In (Optional)</label>
                        <input 
                            type="time" 
                            name="time_in" 
                            id="time_in" 
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:border-cyan-500 focus:bg-white transition"
                        >
                    </div>

                    <!-- Time Out -->
                    <div>
                        <label for="time_out" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Time Out (Optional)</label>
                        <input 
                            type="time" 
                            name="time_out" 
                            id="time_out" 
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:border-cyan-500 focus:bg-white transition"
                        >
                    </div>
                </div>

                <!-- Remarks -->
                <div>
                    <label for="remarks" class="block text-[10px] font-black uppercase tracking-wider text-slate-400 mb-1.5">Remarks</label>
                    <input 
                        type="text" 
                        name="remarks" 
                        id="remarks" 
                        placeholder="e.g. Excused, Left early for medical checkup..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 p-2.5 text-xs font-bold text-slate-700 outline-none focus:border-cyan-500 focus:bg-white transition"
                    >
                </div>

                <!-- Action buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('admin.attendance.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 transition cursor-pointer">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-2.5 text-xs font-black text-white hover:bg-cyan-700 active:scale-95 transition shadow-xs cursor-pointer">
                        Save Record
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-admin-layout>
