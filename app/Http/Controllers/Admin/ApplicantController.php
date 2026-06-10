<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Services\Admin\Enrollment\ApplicationQuery;
use App\Services\Admin\Enrollment\EnrollmentAnalyticsService;
use App\Services\Admin\Enrollment\EnrollmentReviewService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicantController extends Controller
{
    public function __construct(
        private readonly ApplicationQuery $applications,
        private readonly EnrollmentReviewService $reviewService,
        private readonly EnrollmentAnalyticsService $analyticsService,
    ) {}

    public function dashboard(Request $request)
    {
        $schoolYear = (string) (EnrollmentApplicant::whereNotNull('school_year')->latest()->value('school_year') ?? config('services.school.year', '2026-2027'));
        $gradeCounts = EnrollmentApplicant::select('grade_level', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->whereNotNull('grade_level')
            ->groupBy('grade_level')
            ->orderBy('grade_level')
            ->pluck('total', 'grade_level');

        $gradeSlots = $this->analyticsService->gradeSlotData($schoolYear, $gradeCounts);
        $shiftSlots = $this->analyticsService->shiftSlotData($schoolYear);
        $demandCounts = $this->analyticsService->learningModeDemandData($schoolYear);

        $slotRows = $this->analyticsService->slotMatrixData($gradeSlots, $shiftSlots, $demandCounts);
        $slotCollections = $slotRows->flatMap(fn ($row) => [$row['face_to_face'], $row['first_shift'], $row['second_shift']]);
        $capacity = (int) $slotCollections->sum('capacity');
        $enrolled = (int) $slotCollections->sum('enrolled');

        $available = max(0, $capacity - $enrolled);
        $utilization = $capacity > 0 ? min(100, round(($enrolled / $capacity) * 100)) : 0;

        return view('admin.applications.dashboard', [
            'schoolYear' => $schoolYear,
            'familiesCount' => $this->applications->paginateFamilies($request, 1)->total(),
            'totalApplications' => EnrollmentApplicant::whereNotIn('status', ['draft'])->count(),
            'reviewQueue' => EnrollmentApplicant::whereIn('status', ['ready_for_submission', 'pending', 'submitted', 'under_review'])->count(),
            'approvedCount' => EnrollmentApplicant::where('status', 'approved')->count(),
            'capacityStats' => compact('capacity', 'enrolled', 'available', 'utilization'),
            'gradeSlots' => $gradeSlots,
            'slotRows' => $slotRows,
            'applicationCharts' => $this->applicationCharts($gradeSlots),
        ]);
    }

    public function index(Request $request)
    {
        return view('admin.applicants.index', $this->registryData($request));
    }

    public function enrollment(Request $request)
    {
        return view('admin.applications.enrollment', $this->registryData($request));
    }

    public function review(Request $request)
    {
        return view('admin.applications.review', $this->applicantData($request));
    }

    public function requirements(Request $request)
    {
        return view('admin.applications.requirements', $this->applicantData($request));
    }

    public function approval(Request $request)
    {
        return view('admin.applications.approval', $this->applicantData($request));
    }

    private function registryData(Request $request): array
    {
        return [
            'families' => $this->applications->paginateFamilies($request),
            'gradeLevels' => ApplicationQuery::GRADE_LEVELS,
            'statusLabels' => EnrollmentReviewService::STATUS_LABELS,
            'statusBadges' => EnrollmentReviewService::STATUS_BADGES,
            'pmLabels' => EnrollmentReviewService::PAYMENT_LABELS,
            'pmBadges' => EnrollmentReviewService::PAYMENT_BADGES,
            'approvedCount' => EnrollmentApplicant::where('status', 'approved')->count(),
            'reviewQueueCount' => EnrollmentApplicant::whereIn('status', ['ready_for_submission', 'pending', 'submitted', 'under_review'])->count(),
            'rejectedCount' => EnrollmentApplicant::where('status', 'rejected')->count(),
        ];
    }

    private function applicantData(Request $request): array
    {
        return [
            'applicants' => $this->applications->paginateApplicants($request, 15),
            'gradeLevels' => ApplicationQuery::GRADE_LEVELS,
            'statusLabels' => EnrollmentReviewService::STATUS_LABELS,
            'statusBadges' => EnrollmentReviewService::STATUS_BADGES,
            'reviewService' => $this->reviewService,
        ];
    }

    private function applicationCharts($gradeSlots): array
    {
        $capacity = (int) $gradeSlots->sum('capacity');
        $enrolled = (int) $gradeSlots->sum('enrolled');
        $months = collect(range(5, 0))->map(fn ($i) => CarbonImmutable::now()->startOfMonth()->subMonths($i));
        $rows = EnrollmentApplicant::whereNotIn('status', ['draft'])
            ->where('created_at', '>=', $months->first())
            ->get(['created_at'])
            ->groupBy(fn ($row) => CarbonImmutable::parse($row->created_at)->format('Y-m'));
        $typeCounts = EnrollmentApplicant::select('student_type', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->groupBy('student_type')
            ->pluck('total', 'student_type');

        return [
            'capacity' => [
                'series' => [$capacity > 0 ? min(100, round(($enrolled / $capacity) * 100)) : 0],
                'capacity' => $capacity,
                'enrolled' => $enrolled,
            ],
            'gradeCapacity' => [
                'labels' => $gradeSlots->pluck('grade')->values(),
                'enrolled' => $gradeSlots->pluck('enrolled')->values(),
                'available' => $gradeSlots->pluck('available')->values(),
            ],
            'applicationFlow' => [
                'labels' => $months->map(fn ($month) => $month->format('M'))->values(),
                'data' => $months->map(fn ($month) => $rows->get($month->format('Y-m'), collect())->count())->values(),
            ],
            'typeBreakdown' => [
                'labels' => $typeCounts->keys()->map(fn ($type) => strtoupper((string) ($type ?: 'Not Set')))->values(),
                'data' => $typeCounts->values(),
            ],
        ];
    }

    public function show(EnrollmentApplicant $applicant)
    {
        if ($applicant->status === 'submitted') {
            $applicant->update(['status' => 'under_review']);
        }

        $applicant->load('user', 'payment', 'student');

        $siblings = EnrollmentApplicant::where('user_id', $applicant->user_id)
            ->where('id', '!=', $applicant->id)
            ->whereNotIn('status', ['draft'])
            ->get();

        return view('admin.applicants.show', [
            'applicant' => $applicant,
            'siblings'  => $siblings,
            'enrollmentSetting' => EnrollmentSetting::current(),
            ...$this->reviewService->detailData($applicant),
        ]);
    }

    public function updateDiscount(Request $request, EnrollmentApplicant $applicant)
    {
        $this->reviewService->updateDiscount($request, $applicant);

        return back()->with('success', 'Sibling discount override saved.');
    }
}
