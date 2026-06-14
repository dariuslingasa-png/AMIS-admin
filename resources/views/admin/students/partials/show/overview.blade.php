<div x-show="activeTab === 'overview'" class="space-y-6" x-cloak>
    <x-card title="Student Profile" subtitle="Core demographics and contact info">
        <div class="detail-section-stack">
            @foreach ($studentSections as $section)
                <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
            @endforeach
        </div>
    </x-card>

    <x-card title="Residential Info" subtitle="Residence details from enrollment form">
        <div class="detail-section-stack">
            @foreach ($addressSections as $section)
                <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
            @endforeach
        </div>
    </x-card>

    <x-card title="Parent / Guardian Details" subtitle="Grouped parent contacts and home addresses">
        <div class="detail-section-stack">
            @foreach ($guardianSections as $section)
                <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
            @endforeach
        </div>
    </x-card>

    @if(isset($siblings) && $siblings->isNotEmpty())
    <x-card title="Family & Siblings" subtitle="Other children enrolled under the same parent account">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-slate-500">
                    <tr>
                        <th class="py-2 pr-4 font-medium">Name</th>
                        <th class="py-2 pr-4 font-medium">Grade</th>
                        <th class="py-2 pr-4 font-medium">Status / Completion</th>
                        <th class="py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($siblings as $sibling)
                    <tr>
                        <td class="py-3 pr-4 font-bold text-slate-900">{{ Str::upper(html_entity_decode($sibling->full_name, ENT_QUOTES, 'UTF-8')) }}</td>
                        <td class="py-3 pr-4">{{ $sibling->grade_level ?: '-' }}</td>
                        <td class="py-3 pr-4">
                            @if(in_array($sibling->status, ['draft', 'pending', 'ready_for_submission']))
                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Missing Details / Incomplete</span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">{{ $statusLabels[$sibling->status] ?? ucfirst($sibling->status) }}</span>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($sibling->status === 'approved' && $sibling->student)
                                <a href="{{ route('admin.students.show', $sibling->student) }}" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                    View Student Profile <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                </a>
                            @else
                                <a href="{{ route('admin.applicants.show', $sibling) }}" class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-700 font-medium">
                                    View Applicant File <i data-lucide="arrow-right" class="h-3 w-3"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
    @endif

    <x-card title="Medical Background" subtitle="Health info and emergency response contacts">
        <div class="detail-section-stack">
            @foreach ($medicalSections as $section)
                <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :fields="$section['fields']" />
            @endforeach
        </div>
    </x-card>
</div>
