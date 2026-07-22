<div x-show="activeTab === 'overview'" class="space-y-6" x-cloak>
    @if ($isRequirementsComplete)
        <!-- COMPLETED REQUIREMENTS LOCKED CARD -->
        <div class="rounded-2xl border border-emerald-300/80 bg-gradient-to-r from-emerald-900 via-teal-900 to-emerald-950 p-4 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-start gap-3">
                <div class="rounded-xl bg-white/10 p-2.5 text-emerald-300 ring-1 ring-white/20 shrink-0">
                    <i data-lucide="lock" class="h-6 w-6 text-emerald-400"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/20 px-2.5 py-0.5 text-xs font-black text-emerald-200 border border-emerald-400/30 uppercase tracking-wider">
                            🔒 COMPLETED REQUIREMENTS
                        </span>
                        <span class="text-xs text-emerald-200 font-bold uppercase tracking-wider">Clearance Locked & Verified</span>
                    </div>
                    <p class="text-xs text-emerald-100/90 mt-1 font-medium leading-relaxed">
                        All mandatory documents (2x2 Photo, Birth Cert, Report Card / Academic Proof), LRN status, and enrollment payments have been fully submitted and verified for this {{ strtoupper($student->applicant->student_type ?? 'Student') }}.
                    </p>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3.5 py-2 text-xs font-black text-white border border-white/20 shadow-xs">
                    <i data-lucide="shield-check" class="h-4 w-4 text-emerald-400"></i>
                    <span>Requirements Locked</span>
                </span>
            </div>
        </div>
    @else
        <!-- INCOMPLETE REQUIREMENTS REMINDER BANNER -->
        <div class="rounded-2xl border border-amber-300/80 bg-amber-50 dark:bg-amber-950/40 p-4 text-amber-950 dark:text-amber-100 shadow-xs space-y-3 mb-6">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="rounded-xl bg-amber-100 dark:bg-amber-900/60 p-2 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5">
                        <i data-lucide="alert-triangle" class="h-5 w-5 animate-pulse"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h4 class="font-black text-sm text-amber-900 dark:text-amber-200">INCOMPLETE REQUIREMENTS REMINDER</h4>
                            <span class="inline-flex items-center rounded-full bg-amber-200/80 dark:bg-amber-900/80 px-2.5 py-0.5 text-[10px] font-black text-amber-900 dark:text-amber-200 uppercase tracking-wider">
                                {{ count($missingRequirements) }} Missing Item(s)
                            </span>
                        </div>
                        <p class="text-xs text-amber-800 dark:text-amber-300 mt-1 font-medium">
                            The following mandatory requirements need attention for this <strong class="uppercase text-amber-950 dark:text-amber-100 font-extrabold">{{ $student->applicant->student_type ?? 'Student' }}</strong> ({{ $student->grade_level }}):
                        </p>
                    </div>
                </div>
            </div>

            <!-- Missing Checklist Pills -->
            <div class="flex flex-wrap gap-2 pt-1 border-t border-amber-200/60 dark:border-amber-900/40">
                @foreach($missingRequirements as $missingItem)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-rose-100 dark:bg-rose-950/60 px-2.5 py-1 text-xs font-bold text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-900/40">
                        <i data-lucide="x-circle" class="h-3.5 w-3.5 text-rose-600"></i>
                        {{ $missingItem }}
                    </span>
                @endforeach
                @if($isKinder1or2)
                    <span class="inline-flex items-center gap-1 rounded-lg bg-sky-100 dark:bg-sky-950/60 px-2.5 py-1 text-xs font-bold text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-900/40">
                        <i data-lucide="info" class="h-3.5 w-3.5 text-sky-600"></i>
                        Kinder 1 & 2: LRN Exempt
                    </span>
                @endif
            </div>

            <!-- Specific Reminders -->
            @if(!empty($reminders) || !empty($lrnNote))
                <div class="bg-white/80 dark:bg-slate-900/80 rounded-xl p-3 border border-amber-200 dark:border-amber-900/40 text-xs space-y-1.5 font-semibold text-slate-700 dark:text-slate-300">
                    @if($lrnNote)
                        <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-extrabold">
                            <i data-lucide="info" class="h-4 w-4 shrink-0 text-amber-600"></i>
                            <span>{{ $lrnNote }}</span>
                        </div>
                    @endif
                    @foreach($reminders as $rem)
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                            <i data-lucide="chevron-right" class="h-3.5 w-3.5 shrink-0 text-amber-500"></i>
                            <span>{{ $rem }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
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
