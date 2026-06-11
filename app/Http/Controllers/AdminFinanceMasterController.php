<?php

namespace App\Http\Controllers;

use App\Models\FinanceMasterEntry;
use App\Models\FinanceMasterEntryStudent;
use App\Models\EnrollmentApplicant;
use Illuminate\Http\Request;

class AdminFinanceMasterController extends Controller
{
    /**
     * Display the Finance Masters List.
     */
    public function index(Request $request)
    {
        $this->ensurePaymentReviewer();

        $query = FinanceMasterEntry::with(['students', 'verifier', 'payment.applicant', 'payment.invoice.payments'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc');

        // Search filter
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('family_name', 'like', $search)
                  ->orWhere('reference_no', 'like', $search)
                  ->orWhere('remittance_source', 'like', $search)
                  ->orWhere('or_number', 'like', $search)
                  ->orWhereHas('students', function ($sq) use ($search) {
                      $sq->where('student_name', 'like', $search);
                  });
            });
        }

        // Method of Payment filter
        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        // Grade filter
        if ($request->filled('grade')) {
            $query->whereHas('students', function ($sq) use ($request) {
                $sq->where('grade_level', $request->input('grade'));
            });
        }

        // Fetch distinct grade levels for filter dropdown
        $gradeLevels = FinanceMasterEntryStudent::select('grade_level')
            ->whereNotNull('grade_level')
            ->distinct()
            ->orderByRaw("
                CASE grade_level
                    WHEN 'Kinder 1' THEN 1 WHEN 'Kinder 2' THEN 2
                    WHEN 'Grade 1' THEN 3 WHEN 'Grade 2' THEN 4
                    WHEN 'Grade 3' THEN 5 WHEN 'Grade 4' THEN 6
                    WHEN 'Grade 5' THEN 7 WHEN 'Grade 6' THEN 8
                    WHEN 'Grade 7' THEN 9 WHEN 'Grade 8' THEN 10
                    WHEN 'Grade 9' THEN 11 WHEN 'Grade 10' THEN 12
                    WHEN 'Grade 11' THEN 13 WHEN 'Grade 12' THEN 14
                    ELSE 99 END
            ")
            ->pluck('grade_level');

        // Calculate total stats BEFORE any sort join
        $statsQuery = clone $query;
        $totalEntries = $statsQuery->count();
        $totalAmount = (float) $statsQuery->sum('amount');

        $entryIds = (clone $statsQuery)->select('id')->get()->pluck('id');
        $totalStudents = $entryIds->isNotEmpty()
            ? (int) FinanceMasterEntryStudent::whereIn('finance_master_entry_id', $entryIds)->distinct('student_name')->count('student_name')
            : 0;
        $totalFamilies = $entryIds->isNotEmpty()
            ? (int) (clone $statsQuery)->distinct('family_name')->count('family_name')
            : 0;

        // Calculate total stats BEFORE sort modifications
        $statsQuery = clone $query;
        $totalEntries = $statsQuery->count();
        $totalAmount = (float) $statsQuery->sum('amount');

        $entryIds = (clone $statsQuery)->select('id')->get()->pluck('id');
        $totalStudents = $entryIds->isNotEmpty()
            ? (int) FinanceMasterEntryStudent::whereIn('finance_master_entry_id', $entryIds)->distinct('student_name')->count('student_name')
            : 0;
        $totalFamilies = $entryIds->isNotEmpty()
            ? (int) (clone $statsQuery)->distinct('family_name')->count('family_name')
            : 0;

        // Custom sort: reorder() clears default ordering for proper sort
        $sort = $request->input('sort');
        $dir  = $request->input('dir', 'asc');

        if (in_array($sort, ['grade', 'name', 'date', 'amount'])) {
            $query->reorder();
            if ($sort === 'grade') {
                $gradeOrder = "CASE grade_level WHEN 'Kinder 1' THEN 1 WHEN 'Kinder 2' THEN 2 WHEN 'Grade 1' THEN 3 WHEN 'Grade 2' THEN 4 WHEN 'Grade 3' THEN 5 WHEN 'Grade 4' THEN 6 WHEN 'Grade 5' THEN 7 WHEN 'Grade 6' THEN 8 WHEN 'Grade 7' THEN 9 WHEN 'Grade 8' THEN 10 WHEN 'Grade 9' THEN 11 WHEN 'Grade 10' THEN 12 WHEN 'Grade 11' THEN 13 WHEN 'Grade 12' THEN 14 ELSE 99 END";
                $query->orderByRaw("(SELECT MIN({$gradeOrder}) FROM finance_master_entry_students WHERE finance_master_entry_id = finance_master_entries.id) {$dir}");
            } elseif ($sort === 'name') {
                $query->orderByRaw("(SELECT MIN(student_name) FROM finance_master_entry_students WHERE finance_master_entry_id = finance_master_entries.id) {$dir}");
            } elseif ($sort === 'date') {
                $query->orderBy('payment_date', $dir);
            } elseif ($sort === 'amount') {
                $query->orderBy('amount', $dir);
            }
        }

        // Pagination (skip if print mode)
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        if ($request->input('print') === '1') {
            $entries = $query->get();
        } else {
            $entries = $query->paginate($perPage)->withQueryString();
        }

        $this->hydrateMissingStudentGenders($entries);

        return view('admin.finance.masters-list', compact(
            'entries',
            'totalEntries',
            'totalAmount',
            'totalStudents',
            'totalFamilies',
            'perPage',
            'gradeLevels'
        ));
    }

    /**
     * Update a specific Finance Master Entry.
     */
    public function update(Request $request, FinanceMasterEntry $entry)
    {
        $this->ensurePaymentReviewer();

        $request->validate([
            'payment_date'      => 'required|date',
            'method'            => 'required|string|in:remittance,gcash,bdo,maya,cash,other',
            'reference_no'      => 'nullable|string|max:100',
            'remittance_source' => 'nullable|string|max:100',
            'amount'            => 'required|numeric|min:0',
            'or_number'         => 'nullable|string|max:100',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $entry) {
            $entry->update([
                'payment_date'      => $request->input('payment_date'),
                'method'            => $request->input('method'),
                'reference_no'      => $request->input('reference_no'),
                'remittance_source' => $request->input('method') === 'remittance' ? $request->input('remittance_source') : null,
                'amount'            => $request->input('amount'),
                'or_number'         => $request->input('or_number'),
            ]);

            // Sync with associated Payment record if exists
            if ($entry->payment_id) {
                $payment = \App\Models\Payment::find($entry->payment_id);
                if ($payment) {
                    $payment->update([
                        'paid_at'      => $request->input('payment_date'),
                        'method'       => $request->input('method'),
                        'reference_no' => $request->input('reference_no'),
                        'amount'       => $request->input('amount'),
                        'or_number'    => $request->input('or_number'),
                    ]);

                    // Recalculate associated invoice if any
                    if ($payment->invoice_id) {
                        $payment->invoice?->recalculate();
                    }
                }
            }
        });

        return back()->with('success', 'Finance master entry updated successfully.');
    }

    /**
     * Abort if the user doesn't have finance administrative reviewer clearance.
     */
    private function ensurePaymentReviewer(): void
    {
        abort_unless(auth()->user()?->canReviewEnrollmentPayments(), 403);
    }

    /**
     * Old finance-master rows were created before gender was stored on the ledger
     * student rows. Fill the display value from the linked family application.
     */
    private function hydrateMissingStudentGenders($entries): void
    {
        $collection = method_exists($entries, 'getCollection')
            ? $entries->getCollection()
            : collect($entries);

        $needsGender = $collection->filter(function ($entry) {
            return $entry->payment?->applicant
                && $entry->students->contains(fn ($student) => blank($student->gender));
        });

        if ($needsGender->isEmpty()) {
            return;
        }

        $seedApplicants = $needsGender
            ->pluck('payment.applicant')
            ->filter();

        $familyIds = $seedApplicants
            ->pluck('family_application_id')
            ->filter()
            ->unique()
            ->values();

        $userIds = $seedApplicants
            ->filter(fn ($applicant) => blank($applicant->family_application_id))
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        if ($familyIds->isEmpty() && $userIds->isEmpty()) {
            return;
        }

        $familyApplicants = EnrollmentApplicant::query()
            ->select('id', 'user_id', 'family_application_id', 'first_name', 'middle_name', 'last_name', 'gender')
            ->whereNotNull('gender')
            ->where(function ($query) use ($familyIds, $userIds) {
                if ($familyIds->isNotEmpty()) {
                    $query->whereIn('family_application_id', $familyIds);
                }

                if ($userIds->isNotEmpty()) {
                    $query->orWhere(function ($userQuery) use ($userIds) {
                        $userQuery->whereIn('user_id', $userIds)
                            ->whereNull('family_application_id');
                    });
                }
            })
            ->get();

        $byFamily = $familyApplicants->whereNotNull('family_application_id')->groupBy('family_application_id');
        $byUser = $familyApplicants->whereNull('family_application_id')->groupBy('user_id');

        foreach ($needsGender as $entry) {
            $applicant = $entry->payment?->applicant;
            $applicants = $applicant?->family_application_id
                ? ($byFamily[$applicant->family_application_id] ?? collect())
                : ($byUser[$applicant?->user_id] ?? collect());

            if ($applicants->isEmpty()) {
                continue;
            }

            $genderByName = $applicants->flatMap(function ($applicant) {
                $fullName = $this->studentNameKey(collect([
                    $applicant->first_name,
                    $applicant->middle_name,
                    $applicant->last_name,
                ])->filter()->join(' '));

                $firstLast = $this->studentNameKey(collect([
                    $applicant->first_name,
                    $applicant->last_name,
                ])->filter()->join(' '));

                return collect([$fullName, $firstLast])
                    ->filter()
                    ->unique()
                    ->mapWithKeys(fn ($name) => [$name => $applicant->gender]);
            });

            foreach ($entry->students as $student) {
                if (filled($student->gender)) {
                    continue;
                }

                $gender = $genderByName[$this->studentNameKey($student->student_name)] ?? null;
                if ($gender) {
                    $student->setAttribute('gender', $gender);
                }
            }
        }
    }

    private function studentNameKey(?string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim((string) $name)));
    }
}
