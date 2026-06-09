<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplicant;
use App\Services\Admin\Enrollment\ApplicationQuery;
use App\Services\Admin\Enrollment\EnrollmentAnalyticsService;
use App\Services\Admin\Enrollment\EnrollmentReviewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentAnalyticsController extends Controller
{
    public function __construct(
        private readonly EnrollmentAnalyticsService $analyticsService
    ) {}

    public function analytics(ApplicationQuery $applications)
    {
        $schoolYear = (string) (EnrollmentApplicant::whereNotNull('school_year')->latest()->value('school_year') ?? config('services.school.year'));
        $total = EnrollmentApplicant::whereNotIn('status', ['draft'])->count();
        $approved = EnrollmentApplicant::where('status', 'approved')->count();
        $withPaymentProof = EnrollmentApplicant::whereNotIn('status', ['draft'])
            ->whereHas('payment', fn (Builder $query) => $query->whereNotNull('receipt_url'))
            ->count();

        $statusCounts = EnrollmentApplicant::select('status', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->groupBy('status')
            ->pluck('total', 'status');

        $gradeCounts = EnrollmentApplicant::select('grade_level', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->whereNotNull('grade_level')
            ->groupBy('grade_level')
            ->orderBy('grade_level')
            ->pluck('total', 'grade_level');

        $countryCounts = $this->analyticsService->locationCounts('country');
        $provinceCounts = $this->analyticsService->locationCounts('state_province');
        $cityCounts = $this->analyticsService->locationCounts('city');
        $gradeSlots = $this->analyticsService->gradeSlotData($schoolYear, $gradeCounts);
        $shiftSlots = $this->analyticsService->shiftSlotData($schoolYear);
        $demandCounts = $this->analyticsService->learningModeDemandData($schoolYear);
        $slotRows = $this->analyticsService->slotMatrixData($gradeSlots, $shiftSlots, $demandCounts);

        $slotTotals = [
            'capacity' => $slotRows->sum(fn ($row) => collect([$row['face_to_face'], $row['first_shift'], $row['second_shift']])->sum('capacity')),
            'enrolled' => $slotRows->sum(fn ($row) => collect([$row['face_to_face'], $row['first_shift'], $row['second_shift']])->sum('enrolled')),
            'available' => $slotRows->sum(fn ($row) => collect([$row['face_to_face'], $row['first_shift'], $row['second_shift']])->sum('available')),
            'full' => $slotRows->sum(fn ($row) => collect([$row['face_to_face'], $row['first_shift'], $row['second_shift']])->where('status', 'Full')->count()),
            'limited' => $slotRows->sum(fn ($row) => collect([$row['face_to_face'], $row['first_shift'], $row['second_shift']])->where('status', 'Limited')->count()),
        ];

        return view('admin.enrollment.analytics', [
            'summary' => [
                'total' => $total,
                'countries' => $countryCounts->count(),
                'cities' => $cityCounts->count(),
                'slot_capacity' => $slotTotals['capacity'],
                'slot_available' => $slotTotals['available'],
                'full_slots' => $slotTotals['full'],
                'limited_slots' => $slotTotals['limited'],
                'pending_review' => EnrollmentApplicant::whereIn('status', ['ready_for_submission', 'pending', 'submitted', 'under_review'])->count(),
                'approved' => $approved,
                'rejected' => EnrollmentApplicant::where('status', 'rejected')->count(),
                'with_payment_proof' => $withPaymentProof,
                'missing_payment_proof' => max(0, $total - $withPaymentProof),
                'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            ],
            'schoolYear' => $schoolYear,
            'statusCounts' => $statusCounts,
            'gradeCounts' => $gradeCounts,
            'countryCounts' => $countryCounts,
            'provinceCounts' => $provinceCounts,
            'cityCounts' => $cityCounts,
            'gradeSlots' => $gradeSlots,
            'shiftSlots' => $shiftSlots,
            'slotRows' => $slotRows,
            'slotTotals' => $slotTotals,
            'recent' => $applications->recentApplications(8),
            'statusBadges' => EnrollmentReviewService::STATUS_BADGES,
            'statusLabels' => EnrollmentReviewService::STATUS_LABELS,
            'pmBadges' => EnrollmentReviewService::PAYMENT_BADGES,
        ]);
    }

    public function reports(Request $request)
    {
        $query = $this->analyticsService->reportQuery($request);
        $filtered = clone $query;

        $isPrint = $request->filled('print');
        $reports = $isPrint ? $query->latest()->get() : $query->latest()->paginate(20);

        // Compute learning mode totals for the filtered set of enrollees
        $f2fCount = (clone $filtered)->where('learning_mode', 'Face-to-Face')->count();
        $flexible1stCount = (clone $filtered)->where('learning_mode', 'like', '%1st Shift%')->count();
        $flexible2ndCount = (clone $filtered)->where('learning_mode', 'like', '%2nd Shift%')->count();

        return view('admin.enrollment.reports', [
            'reports' => $reports,
            'isPrint' => $isPrint,
            'summary' => [
                'total' => (clone $filtered)->count(),
                'approved' => (clone $filtered)->where('status', 'approved')->count(),
                'under_review' => (clone $filtered)->where('status', 'under_review')->count(),
                'missing_payment' => (clone $filtered)->where(function (Builder $query) {
                    $query->whereDoesntHave('payment')
                        ->orWhereHas('payment', fn (Builder $payment) => $payment->whereNull('receipt_url'));
                })->count(),
                'f2f' => $f2fCount,
                'flexible_1st' => $flexible1stCount,
                'flexible_2nd' => $flexible2ndCount,
            ],
            'gradeLevels' => ApplicationQuery::GRADE_LEVELS,
            'statusLabels' => EnrollmentReviewService::STATUS_LABELS,
            'statusBadges' => EnrollmentReviewService::STATUS_BADGES,
            'pmBadges' => EnrollmentReviewService::PAYMENT_BADGES,
        ]);
    }

    public function export(Request $request)
    {
        $fileName = 'enrollment-report-'.now()->format('Ymd-His').'.csv';
        $rows = $this->analyticsService->reportQuery($request)->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Application ID', 'Applicant', 'Email', 'Grade', 'Type', 'Learning Mode', 'Status', 'Academic Proof', 'Payment Status', 'Submitted']);

            foreach ($rows as $applicant) {
                fputcsv($handle, [
                    str_pad((string) $applicant->id, 4, '0', STR_PAD_LEFT),
                    trim(strtoupper(($applicant->last_name ?? '').', '.($applicant->first_name ?? '').' '.($applicant->middle_name ?? ''))),
                    $applicant->user->email ?? $applicant->email ?? '',
                    $applicant->grade_level ?? '',
                    $applicant->student_type ?? '',
                    $applicant->learning_mode ?? '',
                    EnrollmentReviewService::STATUS_LABELS[$applicant->status] ?? $applicant->status,
                    $applicant->report_card_url ? 'Report Card' : ($applicant->affidavit_url ? 'Affidavit' : 'Missing'),
                    $applicant->payment?->status ? ucfirst($applicant->payment->status) : 'Missing',
                    $applicant->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function mastersList(Request $request)
    {
        $query = $this->analyticsService->reportQuery($request);
        $filtered = clone $query;

        $reports = $query->latest()->paginate(20);

        // Compute learning mode totals for the filtered set of enrollees
        $f2fCount = (clone $filtered)->where('learning_mode', 'Face-to-Face')->count();
        $flexible1stCount = (clone $filtered)->where('learning_mode', 'like', '%1st Shift%')->count();
        $flexible2ndCount = (clone $filtered)->where('learning_mode', 'like', '%2nd Shift%')->count();

        // Grouped cards data: all grades with type of learning
        $gradeModeSummary = EnrollmentApplicant::select('grade_level', 'learning_mode', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->whereNotNull('grade_level')
            ->groupBy('grade_level', 'learning_mode')
            ->get();

        $gradeSummaries = [];
        foreach (ApplicationQuery::GRADE_LEVELS as $grade) {
            $gradeSummaries[$grade] = [
                'total' => 0,
                'f2f' => 0,
                'flexible_1st' => 0,
                'flexible_2nd' => 0,
            ];
        }

        foreach ($gradeModeSummary as $row) {
            $grade = $row->grade_level;
            if (!isset($gradeSummaries[$grade])) {
                continue;
            }
            $mode = $row->learning_mode;
            $count = (int) $row->total;

            $gradeSummaries[$grade]['total'] += $count;

            if ($mode === 'Face-to-Face') {
                $gradeSummaries[$grade]['f2f'] += $count;
            } elseif (str_contains((string) $mode, '1st Shift')) {
                $gradeSummaries[$grade]['flexible_1st'] += $count;
            } elseif (str_contains((string) $mode, '2nd Shift')) {
                $gradeSummaries[$grade]['flexible_2nd'] += $count;
            }
        }

        $isGradeFocused = $request->filled('grade');
        $reports = null;

        if ($isGradeFocused) {
            $reports = $query->latest()->get();
        } else {
            $reports = $query->latest()->paginate(20);
        }

        return view('admin.enrollment.masters-list', [
            'reports' => $reports,
            'isGradeFocused' => $isGradeFocused,
            'summary' => [
                'total' => (clone $filtered)->count(),
                'approved' => (clone $filtered)->where('status', 'approved')->count(),
                'under_review' => (clone $filtered)->where('status', 'under_review')->count(),
                'missing_payment' => (clone $filtered)->where(function (Builder $query) {
                    $query->whereDoesntHave('payment')
                        ->orWhereHas('payment', fn (Builder $payment) => $payment->whereNull('receipt_url'));
                })->count(),
                'f2f' => $f2fCount,
                'flexible_1st' => $flexible1stCount,
                'flexible_2nd' => $flexible2ndCount,
            ],
            'gradeLevels' => ApplicationQuery::GRADE_LEVELS,
            'gradeSummaries' => $gradeSummaries,
            'statusLabels' => EnrollmentReviewService::STATUS_LABELS,
            'statusBadges' => EnrollmentReviewService::STATUS_BADGES,
            'pmBadges' => EnrollmentReviewService::PAYMENT_BADGES,
        ]);
    }

    public function mastersListExport(Request $request)
    {
        $fileName = 'enrollee-masters-list-'.now()->format('Ymd-His').'.csv';
        $rows = $this->analyticsService->reportQuery($request)->latest()->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Application ID', 'Applicant', 'Email', 'Grade', 'Type', 'Learning Mode', 'Status', 'Academic Proof', 'Payment Status', 'Submitted']);

            foreach ($rows as $applicant) {
                fputcsv($handle, [
                    str_pad((string) $applicant->id, 4, '0', STR_PAD_LEFT),
                    trim(strtoupper(($applicant->last_name ?? '').', '.($applicant->first_name ?? '').' '.($applicant->middle_name ?? ''))),
                    $applicant->user->email ?? $applicant->email ?? '',
                    $applicant->grade_level ?? '',
                    $applicant->student_type ?? '',
                    $applicant->learning_mode ?? '',
                    EnrollmentReviewService::STATUS_LABELS[$applicant->status] ?? $applicant->status,
                    $applicant->report_card_url ? 'Report Card' : ($applicant->affidavit_url ? 'Affidavit' : 'Missing'),
                    $applicant->payment?->status ? ucfirst($applicant->payment->status) : 'Missing',
                    $applicant->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
