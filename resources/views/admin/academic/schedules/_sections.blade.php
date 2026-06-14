<div x-show="activeWorkspace === 'sections'" x-transition class="space-y-6">
    <div class="bg-white border border-gray-150 rounded-2xl shadow-xs overflow-hidden">
        <div class="bg-slate-50/50 border-b border-gray-150 px-5 py-4 flex items-center justify-between">
            <span class="text-slate-900 font-extrabold text-sm tracking-wide uppercase">Active Sections Catalog</span>
        </div>
        <div class="premium-table-wrap">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Section Name</th>
                        <th>Grade Level</th>
                        <th>Learning Mode</th>
                        <th>Advisory Advisor</th>
                        <th>Students Enrolled</th>
                        <th style="text-align: right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sections as $section)
                        @php($advisory = $advisoryByGrade->get($section->grade_level))
                        <tr>
                            <td class="font-bold text-slate-900 text-sm">
                                {{ $section->grade_level }} — {{ $section->name ?? 'General' }}
                            </td>
                            <td class="font-semibold text-slate-500 text-xs">{{ $section->grade_level }}</td>
                            <td>
                                <x-badge color="{{ $section->learning_mode === 'Face-to-Face' ? 'blue' : 'purple' }}">
                                    {{ $section->learning_mode }}
                                </x-badge>
                            </td>
                            <td class="font-bold text-indigo-700 text-xs uppercase">{{ $advisory['teacher'] ?? 'Advisor pending' }}</td>
                            <td class="font-extrabold text-slate-800 text-xs">{{ $section->students_count }} students</td>
                            <td style="text-align: right;">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100/50 uppercase">
                                    Active
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
