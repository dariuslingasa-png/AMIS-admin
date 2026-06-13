<?php

namespace App\Services\Admin\Enrollment;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
        $query = EnrollmentApplicant::with('user', 'payment', 'student')
            ->whereNotIn('enrollment_applicants.status', ['draft']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $cleanSearch = preg_replace('/\s+/', ' ', $search);
            $searchWithoutCommaClean = preg_replace('/\s+/', ' ', str_replace(',', '', $cleanSearch));

            $query->where(function ($q) use ($cleanSearch, $searchWithoutCommaClean) {
                $q->where('enrollment_applicants.first_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.last_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.middle_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.father_first_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.father_last_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.mother_first_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.mother_last_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('enrollment_applicants.parent_email', 'like', "%{$cleanSearch}%")
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$cleanSearch}%"));

                $q->orWhereRaw("CONCAT(enrollment_applicants.first_name, ' ', enrollment_applicants.last_name) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(enrollment_applicants.last_name, ' ', enrollment_applicants.first_name) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(enrollment_applicants.last_name, ', ', enrollment_applicants.first_name) LIKE ?", ["%{$cleanSearch}%"])
                    ->orWhereRaw("CONCAT(enrollment_applicants.first_name, ' ', COALESCE(enrollment_applicants.middle_name, ''), ' ', enrollment_applicants.last_name) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(enrollment_applicants.last_name, ' ', enrollment_applicants.first_name, ' ', COALESCE(enrollment_applicants.middle_name, '')) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(enrollment_applicants.last_name, ', ', enrollment_applicants.first_name, ' ', COALESCE(enrollment_applicants.middle_name, '')) LIKE ?", ["%{$cleanSearch}%"]);

                $tokens = explode(' ', $searchWithoutCommaClean);
                if (count($tokens) > 1) {
                    $q->orWhere(function ($sub) use ($tokens) {
                        $sub->where(function ($inner) use ($tokens) {
                            foreach ($tokens as $token) {
                                $tokenClean = rtrim($token, '.');
                                $inner->where(function ($tokenQ) use ($tokenClean) {
                                    $tokenQ->where('enrollment_applicants.first_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('enrollment_applicants.last_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('enrollment_applicants.father_first_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('enrollment_applicants.father_last_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('enrollment_applicants.mother_first_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('enrollment_applicants.mother_last_name', 'like', "%{$tokenClean}%");

                                    if (strlen($tokenClean) === 1) {
                                        $tokenQ->orWhere('enrollment_applicants.middle_name', 'like', "{$tokenClean}%")
                                            ->orWhere('enrollment_applicants.father_middle_name', 'like', "{$tokenClean}%")
                                            ->orWhere('enrollment_applicants.mother_middle_name', 'like', "{$tokenClean}%");
                                    } else {
                                        $tokenQ->orWhere('enrollment_applicants.middle_name', 'like', "%{$tokenClean}%")
                                            ->orWhere('enrollment_applicants.father_middle_name', 'like', "%{$tokenClean}%")
                                            ->orWhere('enrollment_applicants.mother_middle_name', 'like', "%{$tokenClean}%");
                                    }
                                });
                            }
                        });
                    });
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('enrollment_applicants.status', $request->status);
        }

        if ($request->filled('grade')) {
            $query->where('enrollment_applicants.grade_level', $request->grade);
        }

        if ($request->filled('inbox_status') && Schema::hasColumn('enrollment_applicants', 'onboarding_email_status')) {
            match ((string) $request->inbox_status) {
                'sent' => $query->where('enrollment_applicants.onboarding_email_status', 'sent'),
                'failed' => $query->where('enrollment_applicants.onboarding_email_status', 'failed'),
                'missing' => $query->where('enrollment_applicants.status', 'approved')
                    ->where(function ($q) {
                        $q->whereNull('enrollment_applicants.onboarding_email_status')
                            ->orWhereIn('enrollment_applicants.onboarding_email_status', [
                                'failed',
                                'missing_recipient',
                                'missing_payment_proof',
                                'disabled',
                            ]);
                    }),
                default => null,
            };
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
            $cleanSearch = preg_replace('/\s+/', ' ', $search);
            $searchWithoutCommaClean = preg_replace('/\s+/', ' ', str_replace(',', '', $cleanSearch));

            $query->where(function ($q) use ($cleanSearch, $searchWithoutCommaClean) {
                $q->where('first_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('last_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('middle_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('father_first_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('father_last_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('mother_first_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('mother_last_name', 'like', "%{$cleanSearch}%")
                    ->orWhere('parent_email', 'like', "%{$cleanSearch}%")
                    ->orWhereHas('user', fn ($user) => $user->where('email', 'like', "%{$cleanSearch}%"));

                $q->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ["%{$cleanSearch}%"])
                    ->orWhereRaw("CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(last_name, ' ', first_name, ' ', COALESCE(middle_name, '')) LIKE ?", ["%{$searchWithoutCommaClean}%"])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name, ' ', COALESCE(middle_name, '')) LIKE ?", ["%{$cleanSearch}%"]);

                $tokens = explode(' ', $searchWithoutCommaClean);
                if (count($tokens) > 1) {
                    $q->orWhere(function ($sub) use ($tokens) {
                        $sub->where(function ($inner) use ($tokens) {
                            foreach ($tokens as $token) {
                                $tokenClean = rtrim($token, '.');
                                $inner->where(function ($tokenQ) use ($tokenClean) {
                                    $tokenQ->where('first_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('last_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('father_first_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('father_last_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('mother_first_name', 'like', "%{$tokenClean}%")
                                        ->orWhere('mother_last_name', 'like', "%{$tokenClean}%");

                                    if (strlen($tokenClean) === 1) {
                                        $tokenQ->orWhere('middle_name', 'like', "{$tokenClean}%")
                                            ->orWhere('father_middle_name', 'like', "{$tokenClean}%")
                                            ->orWhere('mother_middle_name', 'like', "{$tokenClean}%");
                                    } else {
                                        $tokenQ->orWhere('middle_name', 'like', "%{$tokenClean}%")
                                            ->orWhere('father_middle_name', 'like', "%{$tokenClean}%")
                                            ->orWhere('mother_middle_name', 'like', "%{$tokenClean}%");
                                    }
                                });
                            }
                        });
                    });
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('grade')) {
            $query->where('grade_level', $request->grade);
        }

        if ($request->filled('learning_mode')) {
            $mode = (string) $request->input('learning_mode');
            if ($mode === 'f2f') {
                $query->where('learning_mode', 'Face-to-Face');
            } elseif ($mode === 'flexible_1st') {
                $query->where('learning_mode', 'like', '%1st Shift%');
            } elseif ($mode === 'flexible_2nd') {
                $query->where('learning_mode', 'like', '%2nd Shift%');
            }
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
            $query->whereIn('enrollment_applicant_id', $children->pluck('id'));
            if ($representative->user_id) {
                $query->orWhere('user_id', $representative->user_id);
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
