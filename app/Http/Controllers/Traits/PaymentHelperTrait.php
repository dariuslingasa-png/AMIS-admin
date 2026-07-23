<?php

namespace App\Http\Controllers\Traits;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Carbon\Carbon;

trait PaymentHelperTrait
{
    /**
     * Build charts data for the finance dashboard.
     */
    protected function financeCharts(array $stats): array
    {
        $startDate = Carbon::today()->subDays(6);
        $labels = collect(range(0, 6))
            ->map(fn ($day) => $startDate->copy()->addDays($day)->format('M d'))
            ->values();

        $enrollmentPayments = Payment::whereNotNull('receipt_url')
            ->where('created_at', '>=', $startDate->copy()->startOfDay())
            ->get(['amount', 'created_at'])
            ->groupBy(fn ($payment) => $payment->created_at?->format('M d'));

        $soaPayments = StudentAccountPayment::where('created_at', '>=', $startDate->copy()->startOfDay())
            ->get(['amount', 'created_at'])
            ->groupBy(fn ($payment) => $payment->created_at?->format('M d'));

        return [
            'paymentStatus' => [
                'labels' => ['Pending Proofs', 'Approved', 'Rejected', 'Missing Proof'],
                'data' => [
                    (int) $stats['pending'],
                    (int) $stats['verified'],
                    (int) $stats['rejected'],
                    (int) $stats['missing'],
                ],
            ],
            'soaStatus' => [
                'labels' => ['Paid', 'Partial', 'Unpaid'],
                'data' => [
                    StudentAccount::where('status', 'paid')->count(),
                    (int) $stats['soa_partial'],
                    (int) $stats['soa_unpaid'],
                ],
            ],
            'collectionTrend' => [
                'labels' => $labels,
                'enrollment' => $labels
                    ->map(fn ($label) => (float) ($enrollmentPayments->get($label, collect())->sum('amount')))
                    ->values(),
                'soa' => $labels
                    ->map(fn ($label) => (float) ($soaPayments->get($label, collect())->sum('amount')))
                    ->values(),
            ],
            'soaMoney' => [
                'labels' => ['SOA Paid', 'SOA Balance'],
                'data' => [
                    (float) $stats['soa_paid'],
                    (float) $stats['soa_balance'],
                ],
            ],
        ];
    }

    /**
     * Gather unique family children/enrollee records for payments.
     */
    protected function familyChildrenByPayment($payments): array
    {
        $applicants = $payments->pluck('applicant')->filter();

        if ($applicants->isEmpty()) {
            return [];
        }

        $familyIds = $applicants
            ->pluck('family_application_id')
            ->filter()
            ->unique()
            ->values();

        $userIds = $applicants
            ->filter(fn ($applicant) => blank($applicant->family_application_id) && filled($applicant->user_id))
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($familyIds->isEmpty() && $userIds->isEmpty()) {
            return $payments->mapWithKeys(fn ($payment) => [
                $payment->id => collect([$payment->applicant])->filter(),
            ])->all();
        }

        $children = EnrollmentApplicant::with('payment')
            ->where(function ($query) use ($familyIds, $userIds) {
                if ($familyIds->isNotEmpty()) {
                    $query->whereIn('family_application_id', $familyIds);
                }

                if ($userIds->isNotEmpty()) {
                    $method = $familyIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('user_id', $userIds);
                }
            })
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($child) => $child->family_application_id ? 'family:'.$child->family_application_id : 'user:'.$child->user_id);

        return $payments->mapWithKeys(function ($payment) use ($children) {
            $applicant = $payment->applicant;

            if (! $applicant) {
                return [$payment->id => collect()];
            }

            $key = $applicant->family_application_id ? 'family:'.$applicant->family_application_id : 'user:'.$applicant->user_id;

            return [$payment->id => $children->get($key, collect([$applicant]))];
        })->all();
    }

    /**
     * Gather family name/surnames for payments.
     */
    protected function familyLabelsByPayment($payments, array $familyChildrenByPayment): array
    {
        return $payments->mapWithKeys(function ($payment) use ($familyChildrenByPayment) {
            $children = $familyChildrenByPayment[$payment->id] ?? collect([$payment->applicant])->filter();

            return [$payment->id => $this->familyLabel($children, $payment->applicant)];
        })->all();
    }

    /**
     * Map payments into unified parent family batches for administrative indices.
     */
    protected function paymentFamilyRows($payments)
    {
        $familyChildrenByPayment = $this->familyChildrenByPayment($payments);

        return $payments
            ->filter(fn ($payment) => $payment->applicant)
            ->groupBy(fn ($payment) => $this->paymentFamilyKey($payment))
            ->map(function ($familyPayments) use ($familyChildrenByPayment) {
                $representative = $familyPayments
                    ->sortByDesc(fn ($payment) => optional($payment->updated_at)->timestamp ?? 0)
                    ->first();
                $applicant = $representative->applicant;
                $children = $familyChildrenByPayment[$representative->id] ?? collect([$applicant])->filter();
                $paymentsForTotal = $children
                    ->pluck('payment')
                    ->filter(fn ($payment) => $payment && filled($payment->receipt_url));
                $paymentsForStatus = $paymentsForTotal->isNotEmpty() ? $paymentsForTotal : $familyPayments;
                $statuses = $paymentsForStatus
                    ->pluck('status')
                    ->map(fn ($status) => strtolower((string) ($status ?: 'pending')))
                    ->filter()
                    ->values();

                return [
                    'key' => $this->paymentFamilyKey($representative),
                    'payment' => $representative,
                    'payments' => $familyPayments->values(),
                    'children' => $children,
                    'family_no' => $applicant?->family_application_id ?: $applicant?->id,
                    'family_label' => $this->familyLabel($children, $applicant),
                    'amount' => $paymentsForTotal->isNotEmpty()
                        ? $paymentsForTotal->sum(fn ($payment) => (float) ($payment->amount ?? 0))
                        : $familyPayments->sum(fn ($payment) => (float) ($payment->amount ?? 0)),
                    'methods' => $paymentsForStatus
                        ->pluck('method')
                        ->filter()
                        ->map(fn ($method) => strtoupper((string) $method))
                        ->unique()
                        ->values(),
                    'status' => $this->familyPaymentStatus($statuses),
                    'updated_at' => $paymentsForStatus
                        ->sortByDesc(fn ($payment) => optional($payment->updated_at)->timestamp ?? 0)
                        ->first()?->updated_at,
                ];
            })
            ->sortByDesc(fn ($row) => optional($row['updated_at'])->timestamp ?? 0)
            ->values();
    }

    /**
     * Compute robust family map grouping keys.
     */
    protected function paymentFamilyKey(Payment $payment): string
    {
        $applicant = $payment->applicant;

        if (! $applicant) {
            return 'payment:'.$payment->id;
        }

        if (filled($applicant->family_application_id)) {
            return 'family:'.$applicant->family_application_id;
        }

        return filled($applicant->user_id) ? 'user:'.$applicant->user_id : 'applicant:'.$applicant->id;
    }

    /**
     * Compute unified status badge labels.
     */
    protected function familyPaymentStatus($statuses): string
    {
        if ($statuses->isEmpty()) {
            return 'pending';
        }

        if ($statuses->contains('rejected')) {
            return 'rejected';
        }

        if ($statuses->every(fn ($status) => $status === 'verified')) {
            return 'verified';
        }

        return 'pending';
    }

    /**
     * Build family header labels, filtering keyboard mash placeholders and prioritising child surnames.
     */
    protected function familyLabel($children, ?EnrollmentApplicant $fallback = null): string
    {
        $representative = $children->first() ?: $fallback;

        if (! $representative) {
            return 'FAMILY';
        }

        $isPlaceholder = function ($str) {
            if (blank($str)) {
                return true;
            }
            $s = strtolower(trim($str));
            if (in_array($s, ['asdf', 'fasdfasd', 'fasdf', 'asd', 'qwer', 'test', 'draft', 'placeholder', 'none', 'null', 'na', 'n/a'])) {
                return true;
            }
            if (preg_match('/^[a-z]{1,4}$/', $s) && ! in_array($s, ['ali', 'abu', 'omar', 'aisha', 'nora', 'yusuf', 'sali'])) {
                return true;
            }

            return false;
        };

        $lastName = null;
        $firstName = null;

        if (! $isPlaceholder($representative->father_last_name) && ! $isPlaceholder($representative->father_first_name)) {
            $lastName = $representative->father_last_name;
            $firstName = $representative->father_first_name;
        } elseif (! $isPlaceholder($representative->mother_last_name) && ! $isPlaceholder($representative->mother_first_name)) {
            $lastName = $representative->mother_last_name;
            $firstName = $representative->mother_first_name;
        }

        if (blank($lastName)) {
            $lastName = ! $isPlaceholder($representative->last_name) ? $representative->last_name : null;
        }

        if (blank($lastName)) {
            $lastName = $representative->last_name
                ?: $representative->father_last_name
                ?: $representative->mother_last_name;
        }

        $labelName = trim(($lastName ?? '').' '.($firstName ?? ''));

        if (blank($labelName)) {
            $labelName = $representative->full_name ?: $representative->emergency_name ?: $representative->user?->name ?: $representative->first_name ?: 'GUARDIAN';
        }

        return 'FAMILY OF '.strtoupper(trim($labelName));
    }
}
