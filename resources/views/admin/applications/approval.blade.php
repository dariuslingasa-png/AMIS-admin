<x-admin-layout
    title="Approval Workflow"
    :breadcrumbs="[
        ['label' => 'Applications', 'href' => route('admin.applications.enrollment')],
        ['label' => 'Approval Workflow', 'href' => null],
    ]"
>
    @php
        $inputClass = 'h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100';
        $statusColor = ['approved' => 'green', 'rejected' => 'red', 'under_review' => 'blue', 'ready_for_submission' => 'yellow', 'pending' => 'yellow', 'submitted' => 'purple'];
        $readiness = function ($applicant) use ($reviewService) {
            $docsReady = $reviewService->areAllDocumentsApproved($applicant);
            return match (true) {
                $applicant->status === 'approved' => ['Ready', 'green', 'Approved enrollment'],
                $applicant->status === 'rejected' => ['Blocked', 'red', 'Rejected application'],
                $docsReady => ['Ready', 'green', 'Ready for final approval'],
                default => ['Ready', 'green', 'Approval allowed; follow up documents/payment separately'],
            };
        };
    @endphp

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-700">Applications Workspace</p>
                <h1 class="mt-1 text-xl font-bold text-slate-950">Approval Workflow</h1>
                <p class="mt-1 text-sm text-slate-500">Final review queue for application approval and account creation.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.applications.requirements') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-4 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100">
                    <i data-lucide="list-checks" class="h-4 w-4"></i>
                    Requirements
                </a>
                <a href="{{ route('admin.settings.enrollment') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 text-sm font-bold text-amber-700 transition hover:bg-amber-100">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                    Settings
                </a>
            </div>
        </div>

        <div class="px-6 py-5">
            <form method="GET" class="mb-5 grid grid-cols-12 gap-3">
                <label class="relative col-span-6">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Search approval queue" class="{{ $inputClass }} w-full pl-9">
                </label>
                <select name="status" class="{{ $inputClass }} col-span-3 w-full" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="col-span-3 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800">
                    <i data-lucide="shield-check" class="h-4 w-4"></i>
                    Filter Queue
                </button>
            </form>

            <div class="grid grid-cols-3 gap-4">
                @foreach ([['submitted', 'Submitted'], ['under_review', 'Under Review'], ['approved', 'Approved']] as [$key, $label])
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-extrabold text-slate-950">{{ $applicants->getCollection()->where('status', $key)->count() }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4 font-bold">Applicant</th>
                            <th class="w-44 px-5 py-4 font-bold">Current Status</th>
                            <th class="w-48 px-5 py-4 font-bold">Readiness</th>
                            <th class="px-5 py-4 font-bold">Next Step</th>
                            <th class="w-36 px-5 py-4 text-right font-bold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($applicants as $applicant)
                            @php
                                $name = \Illuminate\Support\Str::upper(trim($applicant->first_name.' '.$applicant->middle_name.' '.$applicant->last_name));
                                [$readyLabel, $readyColor, $nextStep] = $readiness($applicant);
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <div class="font-extrabold text-slate-950">{{ $name }}</div>
                                    <div class="mt-0.5 text-xs font-medium text-slate-500">{{ $applicant->grade_level }} / Applicant #{{ str_pad($applicant->id, 4, '0', STR_PAD_LEFT) }}</div>
                                </td>
                                <td class="px-5 py-4"><x-badge :color="$statusColor[$applicant->status] ?? 'gray'">{{ $statusLabels[$applicant->status] ?? 'Under Review' }}</x-badge></td>
                                <td class="px-5 py-4"><x-badge :color="$readyColor">{{ $readyLabel }}</x-badge></td>
                                <td class="px-5 py-4 font-medium text-slate-600">{{ $nextStep }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.applicants.review', $applicant) }}" class="inline-flex h-9 items-center gap-2 rounded-md border border-emerald-100 bg-white px-3 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                                        <i data-lucide="shield-check" class="h-4 w-4"></i>
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">No applications in the approval queue.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $applicants->links() }}</div>
        </div>
    </section>
</x-admin-layout>
