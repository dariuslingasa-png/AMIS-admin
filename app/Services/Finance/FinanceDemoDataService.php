<?php

namespace App\Services\Finance;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FinanceDemoDataService
{
    public function isEnabled(): bool
    {
        return (bool) config('finance.demo_data_enabled', false);
    }

    public function isDemoFamilyId(int|string|null $id): bool
    {
        if (! $this->isEnabled() || $id === null) {
            return false;
        }

        $idStr = (string) $id;

        return in_array($idStr, ['999001', '999002', 'demo-1', 'demo-2'], true);
    }

    public function searchFamilies(string $term): Collection
    {
        if (! $this->isEnabled()) {
            return collect();
        }

        $termLower = Str::lower(trim($term));
        if ($termLower === '') {
            return collect();
        }

        return $this->allDemoFamilies()->filter(function ($family) use ($termLower) {
            if (Str::contains(Str::lower($family['name']), $termLower)
                || Str::contains(Str::lower($family['email']), $termLower)) {
                return true;
            }

            if (isset($family['aliases'])) {
                foreach ($family['aliases'] as $alias) {
                    if (Str::contains(Str::lower($alias), $termLower) || Str::contains($termLower, Str::lower($alias))) {
                        return true;
                    }
                }
            }

            foreach ($family['children'] as $child) {
                if (Str::contains(Str::lower($child['name']), $termLower)
                    || Str::contains(Str::lower($child['grade_level']), $termLower)
                    || Str::contains(Str::lower($child['student_id']), $termLower)) {
                    return true;
                }
            }

            return false;
        })->map(fn ($f) => $this->toFamilyObject($f))->values();
    }

    public function getFamily(int|string|null $id): ?object
    {
        if (! $this->isEnabled() || $id === null) {
            return null;
        }

        $idStr = (string) $id;
        $data = $this->allDemoFamilies()->first(fn ($f) => (string) $f['id'] === $idStr || $f['demo_key'] === $idStr);

        return $data ? $this->toFamilyObject($data) : null;
    }

    public function getBalances(int|string $familyId): Collection
    {
        $family = $this->getRawFamily($familyId);
        if (! $family) {
            return collect();
        }

        $balances = collect();
        $dueDate = now()->startOfMonth();

        foreach ($family['children'] as $child) {
            $billing = (object) [
                'id' => 'demo-bill-'.$child['student_id'],
                'due_date' => $dueDate,
                'month_number' => now()->month,
                'amount_due' => $child['monthly_due'],
                'student' => (object) [
                    'id' => $child['student_id'],
                    'first_name' => $child['first_name'],
                    'last_name' => $child['last_name'],
                    'full_name' => $child['name'],
                    'grade_level' => $child['grade_level'],
                    'amis_student_id' => $child['student_id'],
                ],
            ];

            $balances->push([
                'billing' => $billing,
                'student' => $billing->student,
                'original' => $child['monthly_due'],
                'verified' => 0.00,
                'remaining' => $child['monthly_due'],
            ]);
        }

        return $balances;
    }

    public function getBillingSchedule(int|string $familyId): Collection
    {
        $family = $this->getRawFamily($familyId);
        if (! $family) {
            return collect();
        }

        $dueDate = now()->startOfMonth();
        $children = collect($family['children'])->map(fn ($child) => [
            'student' => (object) [
                'first_name' => $child['first_name'],
                'last_name' => $child['last_name'],
                'full_name' => $child['name'],
                'grade_level' => $child['grade_level'],
                'amis_student_id' => $child['student_id'],
            ],
            'original' => $child['monthly_due'],
            'verified' => 0.00,
            'remaining' => $child['monthly_due'],
        ]);

        $totalDue = round($children->sum('original'), 2);

        return collect([
            [
                'label' => $dueDate->format('F Y'),
                'due_date' => $dueDate,
                'children' => $children,
                'total_due' => $totalDue,
                'total_paid' => 0.00,
                'remaining' => $totalDue,
                'status' => 'CURRENT',
            ],
        ]);
    }

    public function previewAllocation(int|string $familyId, float $amount): array
    {
        $family = $this->getRawFamily($familyId);
        if (! $family || $amount <= 0) {
            return [
                'allocations' => [],
                'total_allocated' => 0.00,
                'advance_credit' => 0.00,
            ];
        }

        $remainingAmount = $amount;
        $allocations = [];

        foreach ($family['children'] as $child) {
            if ($remainingAmount <= 0) {
                break;
            }

            $due = $child['monthly_due'];
            $allocated = min($remainingAmount, $due);
            $remainingAmount = max(0, round($remainingAmount - $allocated, 2));

            $allocations[] = [
                'student_name' => $child['name'],
                'student_id' => $child['student_id'],
                'grade_level' => $child['grade_level'],
                'original_due' => $due,
                'allocated' => $allocated,
                'remaining_due' => max(0, round($due - $allocated, 2)),
                'status' => $allocated >= $due ? 'FULLY_PAID' : 'PARTIALLY_PAID',
            ];
        }

        return [
            'allocations' => $allocations,
            'total_allocated' => round($amount - $remainingAmount, 2),
            'advance_credit' => $remainingAmount > 0 ? round($remainingAmount, 2) : 0.00,
        ];
    }

    public function storeOnsitePayment(array $validated): object
    {
        $family = $this->getFamily($validated['user_id']);
        $amount = (float) $validated['amount'];
        $preview = $this->previewAllocation($validated['user_id'], $amount);

        $receiptNumber = 'DEMO-OR-'.now()->format('Ymd').'-'.rand(1000, 9999);
        $transactionNumber = 'DEMO-TX-'.now()->format('Ymd').'-'.rand(1000, 9999);

        $officialReceipt = (object) [
            'id' => 99999,
            'official_receipt_number' => $receiptNumber,
            'status' => 'ISSUED',
            'issued_at' => now(),
            'is_demo' => true,
        ];

        $transaction = (object) [
            'id' => 99999,
            'transaction_number' => $transactionNumber,
            'official_receipt_number' => $receiptNumber,
            'officialReceipt' => $officialReceipt,
            'family' => $family,
            'amount' => $amount,
            'payment_method' => strtoupper($validated['payment_method']),
            'reference_number' => $validated['reference_number'] ?? 'N/A',
            'transaction_at' => now(),
            'advance_credit' => $preview['advance_credit'],
            'allocations' => $preview['allocations'],
            'is_demo' => true,
            'watermark' => 'TEST / DEMO — NOT AN OFFICIAL SCHOOL RECEIPT',
        ];

        return $transaction;
    }

    private function getRawFamily(int|string $id): ?array
    {
        $idStr = (string) $id;

        return $this->allDemoFamilies()->first(fn ($f) => (string) $f['id'] === $idStr || $f['demo_key'] === $idStr);
    }

    private function toFamilyObject(array $data): object
    {
        $childrenObj = collect($data['children'])->map(fn ($c) => (object) [
            'id' => $c['student_id'],
            'first_name' => $c['first_name'],
            'last_name' => $c['last_name'],
            'full_name' => $c['name'],
            'grade_level' => $c['grade_level'],
            'amis_student_id' => $c['student_id'],
            'account' => (object) [
                'remaining_balance' => $c['monthly_due'],
            ],
        ]);

        return (object) [
            'id' => $data['id'],
            'demo_key' => $data['demo_key'],
            'name' => $data['name'],
            'email' => $data['email'],
            'is_demo' => true,
            'enrollment_applicants_count' => count($data['children']),
            'enrollmentApplicants' => $childrenObj->map(fn ($c) => (object) [
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'amis_student_id' => $c->amis_student_id,
                'student' => $c,
            ]),
        ];
    }

    private function allDemoFamilies(): Collection
    {
        return collect([
            [
                'id' => 999001,
                'demo_key' => 'demo-1',
                'name' => 'DEMO PARENT 1',
                'email' => 'demo.parent1@example.test',
                'aliases' => ['zhairel.lingasa@gmail.com', 'zhairel', 'lingasa'],
                'children' => [
                    [
                        'student_id' => 'DEMO-2026-001',
                        'first_name' => 'DEMO',
                        'last_name' => 'CHILD 1',
                        'name' => 'DEMO CHILD 1',
                        'grade_level' => 'Grade 1',
                        'monthly_due' => 3222.22,
                    ],
                    [
                        'student_id' => 'DEMO-2026-002',
                        'first_name' => 'DEMO',
                        'last_name' => 'CHILD 2',
                        'name' => 'DEMO CHILD 2',
                        'grade_level' => 'Grade 4',
                        'monthly_due' => 5444.44,
                    ],
                ],
            ],
            [
                'id' => 999002,
                'demo_key' => 'demo-2',
                'name' => 'DEMO PARENT 2',
                'email' => 'demo.parent2@example.test',
                'children' => [
                    [
                        'student_id' => 'DEMO-2026-003',
                        'first_name' => 'DEMO',
                        'last_name' => 'CHILD 3',
                        'name' => 'DEMO CHILD 3',
                        'grade_level' => 'Grade 3',
                        'monthly_due' => 4000.00,
                    ],
                    [
                        'student_id' => 'DEMO-2026-004',
                        'first_name' => 'DEMO',
                        'last_name' => 'CHILD 4',
                        'name' => 'DEMO CHILD 4',
                        'grade_level' => 'Grade 7',
                        'monthly_due' => 6000.00,
                    ],
                ],
            ],
        ]);
    }
}
