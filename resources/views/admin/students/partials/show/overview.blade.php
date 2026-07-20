<div x-show="activeTab === 'overview'" class="space-y-6" x-cloak>
    @if ($student->applicant && $student->applicant->completion_percentage < 100)
        <div class="rounded-2xl border border-amber-200 bg-amber-50/50 p-4 text-amber-900 shadow-xs flex items-start gap-3">
            <i data-lucide="alert-triangle" class="h-5 w-5 shrink-0 text-amber-600 mt-0.5 animate-pulse"></i>
            <div>
                <h4 class="font-extrabold text-sm text-amber-850">Incomplete Registration Profile</h4>
                <p class="text-xs text-amber-700 mt-1">The following mandatory fields or documents are still missing for this student:</p>
                <ul class="list-disc list-inside text-xs mt-2 space-y-1 font-semibold text-amber-800">
                    @foreach ($student->applicant->incomplete_fields as $field)
                        <li>{{ $field }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <x-card title="Student Profile" subtitle="Core demographics and contact info">
        <x-slot:actions>
            @unless ($isTeacherAdminViewer)
                <button @click="openEditModal = true; editSection = 'all'"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition active:scale-95 cursor-pointer">
                    <i data-lucide="edit" class="h-3.5 w-3.5"></i>
                    <span>Edit All Details</span>
                </button>
            @endunless
        </x-slot:actions>
        <div class="detail-section-stack">
            @foreach ($studentSections as $section)
                <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :sectionKey="$section['key']" :fields="$section['fields']" />
            @endforeach
        </div>
    </x-card>

    @unless ($isTeacherAdminViewer)
        <x-card title="Residential Info" subtitle="Residence details from enrollment form">
            <x-slot:actions>
                <button @click="openEditModal = true; editSection = 'contact'"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition active:scale-95 cursor-pointer">
                    <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                    <span>Edit Residence</span>
                </button>
            </x-slot:actions>
            <div class="detail-section-stack">
                @foreach ($addressSections as $section)
                    <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :sectionKey="$section['key']" :fields="$section['fields']" />
                @endforeach
            </div>
        </x-card>

        <x-card title="Parent / Guardian Details" subtitle="Grouped parent contacts and home addresses">
            <x-slot:actions>
                <button @click="openEditModal = true; editSection = 'parents'"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition active:scale-95 cursor-pointer">
                    <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                    <span>Edit Parents</span>
                </button>
            </x-slot:actions>
            <div class="detail-section-stack">
                @foreach ($guardianSections as $section)
                    <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :sectionKey="$section['key']" :fields="$section['fields']" />
                @endforeach
            </div>

        </x-card>
    @endunless

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

    @unless ($isTeacherAdminViewer)
        <x-card title="Medical Background" subtitle="Health info and emergency response contacts">
            <div class="detail-section-stack">
                @foreach ($medicalSections as $section)
                    <x-applicant.detail-section :title="$section['title']" :icon="$section['icon']" :sectionKey="$section['key'] ?? ''" :fields="$section['fields']" />
                @endforeach
            </div>
        </x-card>
    @endunless
</div>
