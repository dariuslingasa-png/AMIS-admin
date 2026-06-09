<?php

namespace App\Services\Admin\Enrollment;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

class ApplicationQuery
{
    public const GRADE_LEVELS = [
        'Kinder 1',
        'Kinder 2',
        'Grade 1',
        'Grade 2',
        'Grade 3',
        'Grade 4',
        'Grade 5',
        'Grade 6',
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10',
        'Grade 11',
        'Grade 12',
    ];

    public function dashboardStats(): array
    {
        return [
            'total' => EnrollmentApplicant::whereNotIn('status', ['draft'])->count(),
            'pending' => EnrollmentApplicant::whereIn('status', ['ready_for_submission', 'pending', 'submitted'])->count(),
            'under_review' => EnrollmentApplicant::where('status', 'under_review')->count(),
            'approved' => EnrollmentApplicant::where('status', 'approved')->count(),
            'rejected' => EnrollmentApplicant::where('status', 'rejected')->count(),
            'payments_pending' => Payment::where('status', 'pending')->whereNotNull('receipt_url')->count(),
            'students' => Student::count(),
        ];
    }

    public function recentApplications(int $limit = 10): Collection
    {
        return EnrollmentApplicant::with('user', 'payment')
            ->whereNotIn('status', ['draft'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function paginateApplicants(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $query = EnrollmentApplicant::with('user', 'payment')
            ->whereNotIn('enrollment_applicants.status', ['draft']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('enrollment_applicants.first_name', 'like', "%{$search}%")
                    ->orWhere('enrollment_applicants.last_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('enrollment_applicants.status', $request->status);
        }

        if ($request->filled('grade')) {
            $query->where('enrollment_applicants.grade_level', $request->grade);
        }

        $sort = (string) $request->query('sort', 'number');
        $direction = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        if ($sort === 'payment') {
            $query->leftJoin('payments', 'payments.enrollment_applicant_id', '=', 'enrollment_applicants.id')
                ->select('enrollment_applicants.*')
                ->orderBy('payments.status', $direction);
        } elseif ($sort === 'applicant') {
            $query->orderBy('enrollment_applicants.last_name', $direction)
                ->orderBy('enrollment_applicants.first_name', $direction);
        } elseif (in_array($sort, ['grade_level', 'status'], true)) {
            $query->orderBy('enrollment_applicants.'.$sort, $direction);
        } else {
            $query->orderBy('enrollment_applicants.id', $direction);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginateFamilies(Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $families = $this->filteredApplicants($request)
            ->get()
            ->groupBy(fn ($applicant) => $this->familyKey($applicant))
            ->map(fn ($children) => $this->familyRow($children))
            ->values();

        $sort = (string) $request->query('sort', 'number');
        $desc = $request->query('dir', 'desc') !== 'asc';
        $families = $families->sortBy(fn ($family) => match ($sort) {
            'parent' => $family['family_label'],
            'children' => $family['children_count'],
            'progress' => $family['approved_count'],
            'payment' => $family['payment_status'],
            'status' => $family['overall_status'],
            default => $family['family_no'],
        }, SORT_REGULAR, $desc)->values();

        $page = Paginator::resolveCurrentPage();
        return new Paginator(
            $families->forPage($page, $perPage),
            $families->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );
    }

    public function familyChildren(EnrollmentApplicant $applicant): SupportCollection
    {
        return EnrollmentApplicant::with('user', 'payment')
            ->whereNotIn('status', ['draft'])
            ->get()
            ->filter(fn ($child) => $this->familyKey($child) === $this->familyKey($applicant))
            ->sortBy('id')
            ->values();
    }

    private function filteredApplicants(Request $request)
    {
        $query = EnrollmentApplicant::with('user', 'payment')
            ->whereNotIn('status', ['draft']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('father_first_name', 'like', "%{$search}%")
                    ->orWhere('mother_first_name', 'like', "%{$search}%")
                    ->orWhere('parent_email', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('grade')) {
            $query->where('grade_level', $request->grade);
        }

        return $query->orderByDesc('id');
    }

    private function familyRow(SupportCollection $children): array
    {
        $children = $children->sortBy('id')->values();
        $first = $children->first();
        $familyId = $first->family_application_id ?: $first->id;
        $representative = $children->firstWhere('id', $familyId) ?: $first;
        $approved = $children->where('status', 'approved')->count();
        $rejected = $children->where('status', 'rejected')->count();

        // Fetch family payments directly
        $familyPayments = \App\Models\Payment::where(function ($query) use ($children, $representative) {
            if ($representative->user_id) {
                $query->where('user_id', $representative->user_id);
            } else {
                $query->whereIn('enrollment_applicant_id', $children->pluck('id'));
            }
        })->get();

        return [
            'family_no' => $familyId,
            'family_label' => $this->familyLabel($representative),
            'parent_name' => $this->parentName($representative),
            'parent_email' => $representative->user->email ?? ($representative->parent_email ?: $representative->email),
            'parent_mobile' => trim(($representative->parent_country_code ? $representative->parent_country_code.' ' : '').($representative->parent_mobile ?? '')),
            'children_count' => $children->count(),
            'approved_count' => $approved,
            'pending_count' => $children->count() - $approved - $rejected,
            'payment_status' => $this->familyPaymentStatus($children, $familyPayments),
            'overall_status' => $rejected > 0 ? 'Rejected' : ($approved === $children->count() ? 'Approved' : 'Under Review'),
            'email_sent_at' => $children->max('registry_email_sent_at'),
            'representative' => $representative,
            'children' => $children,
            'family_payments' => $familyPayments,
        ];
    }

    private function familyKey(EnrollmentApplicant $applicant): string
    {
        if ($applicant->family_application_id) {
            return 'family:'.$applicant->family_application_id;
        }

        if ($applicant->user_id) {
            return 'user:'.$applicant->user_id;
        }

        $email = strtolower(trim((string) $applicant->parent_email));
        if ($email !== '') {
            return 'email:'.$email;
        }

        $phone = preg_replace('/\D+/', '', (string) $applicant->parent_mobile);
        return $phone !== '' ? 'phone:'.$phone : 'applicant:'.$applicant->id;
    }

    private function parentName(EnrollmentApplicant $applicant): string
    {
        $mother = trim(($applicant->mother_first_name ?? '').' '.($applicant->mother_middle_name ?? '').' '.($applicant->mother_last_name ?? ''));
        $father = trim(($applicant->father_first_name ?? '').' '.($applicant->father_middle_name ?? '').' '.($applicant->father_last_name ?? ''));
        return $mother ?: ($father ?: 'Parent / Guardian');
    }

    private function familyLabel(EnrollmentApplicant $applicant): string
    {
        $last = $applicant->mother_last_name ?: $applicant->father_last_name ?: $applicant->last_name ?: 'Family';
        $first = $applicant->mother_first_name ?: $applicant->father_first_name ?: strtok($this->parentName($applicant), ' ') ?: 'Guardian';

        return trim($last).', '.trim($first);
    }

    private function familyPaymentStatus(SupportCollection $children, $familyPayments = null): string
    {
        $statuses = $familyPayments ? $familyPayments->pluck('status') : $children->map(fn ($child) => $child->payment->status ?? null);
        if ($statuses->contains('verified')) {
            return 'Paid';
        }
        if ($statuses->contains('pending')) {
            return 'Pending';
        }
        return 'No Payment';
    }
}
