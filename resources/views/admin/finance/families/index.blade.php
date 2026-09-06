<x-admin-layout title="Student Accounts & SOA" :breadcrumbs="[['label' => 'Finance', 'href' => route('admin.finance.dashboard')], ['label' => 'Student Accounts & SOA', 'href' => null]]">
    <div class="space-y-6">
        @include('admin.finance._nav', [
            'title' => 'Student Accounts & SOA',
            'subtitle' => 'All approved official students appear here, including students with no payment record. Open a student directly to view or manage the official SOA.',
            'showSearch' => false,
        ])

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs">
            <div class="mb-4 flex flex-wrap gap-2 border-b border-slate-100 pb-4">
                <a href="{{ route('admin.finance.families.index', ['tab' => 'official']) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ ($activeTab ?? 'official') === 'official' ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Official Students ({{ number_format($officialCount ?? 0) }})</a>
                @if(($demoCount ?? 0) > 0)<a href="{{ route('admin.finance.families.index', ['tab' => 'demo']) }}" class="rounded-lg px-3 py-2 text-xs font-bold {{ ($activeTab ?? 'official') === 'demo' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Demo Families ({{ number_format($demoCount) }})</a>@endif
            </div>
            <form method="GET" action="{{ route('admin.finance.families.index') }}" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="tab" value="{{ $activeTab ?? 'official' }}">
                <label for="student-account-search" class="sr-only">Search student accounts</label>
                <div class="relative min-w-0 flex-1"><i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i><input id="student-account-search" name="q" value="{{ request('q') }}" placeholder="Search official student, parent, student ID, grade, OR, or reference..." class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-4 text-sm text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"></div>
                <button class="h-11 rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white hover:bg-emerald-800">Find Student</button>
                @if(request()->filled('q'))<a href="{{ route('admin.finance.families.index', ['tab' => $activeTab ?? 'official']) }}" class="inline-flex h-11 items-center justify-center px-2 text-sm font-bold text-slate-600 hover:text-slate-950">Clear</a>@endif
            </form>
        </section>

        @if(($activeTab ?? 'official') === 'official')
            <div class="flex flex-col gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 sm:flex-row sm:items-center sm:justify-between">
                <p><strong>{{ number_format($officialCount ?? 0) }} approved official students</strong> are listed whether or not a payment has been recorded.</p>
                <span class="text-xs font-semibold text-emerald-800">Primary action: Open SOA</span>
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="finance-mobile-table min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-600"><tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Student ID</th><th class="px-5 py-3">Grade / Section</th><th class="px-5 py-3">Parent / Guardian</th><th class="px-5 py-3 text-right">Outstanding Balance</th><th class="px-5 py-3">SOA Status</th><th class="px-5 py-3">Payment Record</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($families as $record)
                            @php
                                $studentRows = collect();
                                if (($activeTab ?? 'official') === 'official') {
                                    $family = $record->user;
                                    $student = $record->student;
                                    $studentId = $record->amis_student_id ?: $student?->student_number ?: 'Not assigned';
                                    $studentRows->push([
                                        'name' => $record->full_name ?: 'Student',
                                        'id' => $studentId,
                                        'grade' => $record->grade_level ?: $student?->grade_level ?: 'Not assigned',
                                        'section' => $student?->section,
                                        'account' => $student?->account,
                                        'family_id' => $family?->id ?: $record->user_id,
                                        'parent_name' => $family?->name ?: trim(($record->father_first_name ?? '').' '.($record->father_last_name ?? '')) ?: 'Not recorded',
                                        'parent_email' => $family?->email ?: $record->parent_email,
                                        'soa_url' => $student ? route('admin.finance.students.official-soa', ['studentIdentifier' => $student->student_number ?: $student->id]) : null,
                                        'is_demo' => false,
                                    ]);
                                } else {
                                    $family = $record;
                                    $applicants = is_iterable($family->enrollmentApplicants ?? null) ? collect($family->enrollmentApplicants) : collect();
                                    foreach ($applicants as $applicant) {
                                        $student = $applicant->student ?? null;
                                        $studentId = $applicant->amis_student_id ?: $student?->student_number ?: (string) $applicant->id;
                                        $studentRows->push([
                                            'name' => $applicant->full_name ?: 'Student',
                                            'id' => $studentId,
                                            'grade' => $applicant->grade_level ?: $student?->grade_level ?: 'Not assigned',
                                            'section' => $student?->section,
                                            'account' => $student?->account ?? ($applicant->account ?? null),
                                            'family_id' => $family->id,
                                            'parent_name' => $family->name,
                                            'parent_email' => $family->email,
                                            'soa_url' => route('admin.finance.families.show', ['family' => $family->id, 'student' => $studentId]),
                                            'is_demo' => true,
                                        ]);
                                    }
                                }
                            @endphp
                            @foreach($studentRows as $studentRow)
                                @php
                                    $balance = (float)($studentRow['account']?->remaining_balance ?? 0);
                                    $paid = (float)($studentRow['account']?->amount_paid ?? 0);
                                    $status = ! $studentRow['account'] ? 'SOA Not Set Up' : ($balance <= 0.01 ? 'Fully Settled' : ($paid > 0 ? 'Partially Paid' : 'Balance Due'));
                                    $hasPayment = $paid > 0.005;
                                @endphp
                                <tr class="hover:bg-slate-50/70">
                                    <td data-label="Student" class="px-5 py-4">@if($studentRow['soa_url'])<a href="{{ $studentRow['soa_url'] }}" class="font-bold text-slate-950 hover:text-emerald-700 hover:underline">{{ mb_strtoupper($studentRow['name']) }}</a>@else<span class="font-bold text-slate-950">{{ mb_strtoupper($studentRow['name']) }}</span>@endif @if($studentRow['is_demo'])<span class="ml-2 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-800">Demo</span>@endif</td>
                                    <td data-label="Student ID" class="px-5 py-4 font-mono text-xs font-semibold text-slate-700">{{ $studentRow['id'] }}</td>
                                    <td data-label="Grade / Section" class="px-5 py-4 text-slate-700">{{ $studentRow['grade'] }}{{ $studentRow['section'] ? ' / '.$studentRow['section'] : '' }}</td>
                                    <td data-label="Parent / Guardian" class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $studentRow['parent_name'] }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $studentRow['parent_email'] }}</p></td>
                                    <td data-label="Outstanding Balance" class="px-5 py-4 text-right text-base font-black {{ $balance > 0.01 ? 'text-slate-950' : 'text-emerald-700' }}">{{ $studentRow['account'] ? '₱'.number_format($balance, 2) : '—' }}</td>
                                    <td data-label="SOA Status" class="px-5 py-4"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $status === 'Fully Settled' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : ($status === 'SOA Not Set Up' ? 'border-slate-200 bg-slate-50 text-slate-600' : 'border-amber-200 bg-amber-50 text-amber-800') }}">{{ $status }}</span></td>
                                    <td data-label="Payment Record" class="px-5 py-4"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $hasPayment ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-600' }}">{{ $hasPayment ? 'Payment Recorded' : 'No Payment Recorded' }}</span></td>
                                    <td data-label="Action" class="px-5 py-4 text-right">@if($studentRow['soa_url'])<a href="{{ $studentRow['soa_url'] }}" class="inline-flex min-h-9 items-center rounded-lg bg-emerald-700 px-3 text-xs font-bold text-white hover:bg-emerald-800">Open SOA</a>@else<span class="inline-flex min-h-9 items-center rounded-lg bg-slate-100 px-3 text-xs font-bold text-slate-500">Student Record Pending</span>@endif</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td data-label="" colspan="8" class="px-5 py-14 text-center"><i data-lucide="search-x" class="mx-auto h-8 w-8 text-slate-400"></i><p class="mt-2 font-bold text-slate-800">No official students found.</p><p class="mt-1 text-sm text-slate-500">Check the spelling or try a parent email, student ID, grade, OR number, or payment reference.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($families->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $families->links() }}</div>@endif
        </section>
    </div>
</x-admin-layout>
