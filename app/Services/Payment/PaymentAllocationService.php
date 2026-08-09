<?php

namespace App\Services\Payment;

use App\Models\FamilyCredit;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentAllocationService
{
    /**
     * Auto Allocate payment across students based on current outstanding balances.
     *
     * @param float $paymentAmount Total payment amount.
     * @param array $students List of students with ['id' => int, 'name' => string, 'balance' => float].
     * @return array Calculated allocations.
     */
    
    /**
     * Auto Apply payment to the oldest unpaid student/month balance first.
     * Remaining amount rolls over to the next unpaid month.
     *
     * @param float $paymentAmount Total payment amount detected or entered.
     * @param array $studentsList List of students with monthly breakdown items sorted chronologically.
     * @return array Calculated allocations per student & month breakdown.
     */
    public function autoApplyOldestUnpaidFirst(float $paymentAmount, array $studentsList): array
    {
        $remainingCentavos = (int) round($paymentAmount * 100);
        $allocations = [];
        $allocatedTotalCentavos = 0;
        $monthBreakdown = [];

        foreach ($studentsList as $st) {
            $studentId = $st['id'];
            $studentName = $st['name'] ?? "Student #{$studentId}";
            $balanceCentavos = (int) round(($st['balance'] ?? 0) * 100);

            if ($remainingCentavos <= 0 || $balanceCentavos <= 0) {
                $allocCentavos = 0;
            } else {
                $allocCentavos = min($remainingCentavos, $balanceCentavos);
            }

            $remainingCentavos -= $allocCentavos;
            $allocatedTotalCentavos += $allocCentavos;

            $allocations[$studentId] = [
                'student_id' => $studentId,
                'name' => $studentName,
                'grade_level' => $st['grade_level'] ?? '',
                'amount' => round($allocCentavos / 100, 2),
                'balance_before' => round($balanceCentavos / 100, 2),
                'balance_after' => round(($balanceCentavos - $allocCentavos) / 100, 2),
            ];
        }

        $allocatedTotal = round($allocatedTotalCentavos / 100, 2);
        $excess = round($remainingCentavos / 100, 2);

        return [
            'method' => 'auto_oldest_first',
            'payment_amount' => $paymentAmount,
            'allocated_total' => $allocatedTotal,
            'excess_amount' => max(0, $excess),
            'allocations' => $allocations,
        ];
    }

    public function autoAllocate(float $paymentAmount, array $students): array
    {
        $remainingCentavos = (int) round($paymentAmount * 100);
        $allocations = [];
        $allocatedTotalCentavos = 0;

        foreach ($students as $st) {
            $studentId = $st['id'];
            $balanceCentavos = (int) round(($st['balance'] ?? 0) * 100);

            if ($remainingCentavos <= 0 || $balanceCentavos <= 0) {
                $allocCentavos = 0;
            } else {
                $allocCentavos = min($remainingCentavos, $balanceCentavos);
            }

            $remainingCentavos -= $allocCentavos;
            $allocatedTotalCentavos += $allocCentavos;

            $allocations[$studentId] = [
                'student_id' => $studentId,
                'name' => $st['name'] ?? "Student #{$studentId}",
                'grade_level' => $st['grade_level'] ?? '',
                'amount' => round($allocCentavos / 100, 2),
                'balance_before' => round($balanceCentavos / 100, 2),
                'balance_after' => round(($balanceCentavos - $allocCentavos) / 100, 2),
            ];
        }

        $allocatedTotal = round($allocatedTotalCentavos / 100, 2);
        $excess = round($remainingCentavos / 100, 2);

        return [
            'method' => 'auto',
            'payment_amount' => $paymentAmount,
            'allocated_total' => $allocatedTotal,
            'excess_amount' => max(0, $excess),
            'allocations' => $allocations,
        ];
    }

    /**
     * Equal Split payment evenly among selected students with centavo rounding safety.
     *
     * @param float $paymentAmount Total payment amount.
     * @param array $students List of students.
     * @return array Calculated allocations.
     */
    public function equalSplit(float $paymentAmount, array $students): array
    {
        $count = count($students);
        if ($count === 0) {
            return [
                'method' => 'equal',
                'payment_amount' => $paymentAmount,
                'allocated_total' => 0.00,
                'excess_amount' => $paymentAmount,
                'allocations' => [],
            ];
        }

        $totalCentavos = (int) round($paymentAmount * 100);
        $baseShareCentavos = (int) floor($totalCentavos / $count);
        $remainderCentavos = $totalCentavos - ($baseShareCentavos * $count);

        $allocations = [];
        $allocatedTotalCentavos = 0;

        foreach ($students as $index => $st) {
            $studentId = $st['id'];
            $balanceCentavos = (int) round(($st['balance'] ?? 0) * 100);

            // Add 1 centavo remainder to the first N students
            $shareCentavos = $baseShareCentavos + ($index < $remainderCentavos ? 1 : 0);
            $allocatedTotalCentavos += $shareCentavos;

            $allocations[$studentId] = [
                'student_id' => $studentId,
                'name' => $st['name'] ?? "Student #{$studentId}",
                'grade_level' => $st['grade_level'] ?? '',
                'amount' => round($shareCentavos / 100, 2),
                'balance_before' => round($balanceCentavos / 100, 2),
                'balance_after' => round(max(0, $balanceCentavos - $shareCentavos) / 100, 2),
            ];
        }

        return [
            'method' => 'equal',
            'payment_amount' => $paymentAmount,
            'allocated_total' => round($allocatedTotalCentavos / 100, 2),
            'excess_amount' => 0.00, // Total centavos equal payment total
            'allocations' => $allocations,
        ];
    }

    /**
     * Validate and calculate Custom Allocation per child.
     *
     * @param float $paymentAmount Total payment amount.
     * @param array $customAllocations Map of [student_id => custom_amount_float].
     * @param array $students List of students.
     * @return array Calculated allocations.
     */
    public function customAllocate(float $paymentAmount, array $customAllocations, array $students): array
    {
        $paymentCentavos = (int) round($paymentAmount * 100);
        $allocations = [];
        $allocatedTotalCentavos = 0;

        foreach ($students as $st) {
            $studentId = $st['id'];
            $balanceCentavos = (int) round(($st['balance'] ?? 0) * 100);
            $userCustomFloat = (float) ($customAllocations[$studentId] ?? 0);
            $allocCentavos = (int) round($userCustomFloat * 100);

            if ($allocCentavos < 0) {
                throw new InvalidArgumentException("Allocation amount for student ID #{$studentId} cannot be negative.");
            }

            $allocatedTotalCentavos += $allocCentavos;

            $allocations[$studentId] = [
                'student_id' => $studentId,
                'name' => $st['name'] ?? "Student #{$studentId}",
                'grade_level' => $st['grade_level'] ?? '',
                'amount' => round($allocCentavos / 100, 2),
                'balance_before' => round($balanceCentavos / 100, 2),
                'balance_after' => round(max(0, $balanceCentavos - $allocCentavos) / 100, 2),
            ];
        }

        if ($allocatedTotalCentavos > $paymentCentavos) {
            $overDiff = round(($allocatedTotalCentavos - $paymentCentavos) / 100, 2);
            throw new InvalidArgumentException("Allocation exceeds the payment amount by ₱" . number_format($overDiff, 2) . ".");
        }

        $allocatedTotal = round($allocatedTotalCentavos / 100, 2);
        $excess = round(($paymentCentavos - $allocatedTotalCentavos) / 100, 2);

        return [
            'method' => 'custom',
            'payment_amount' => $paymentAmount,
            'allocated_total' => $allocatedTotal,
            'excess_amount' => max(0, $excess),
            'allocations' => $allocations,
        ];
    }

    /**
     * Create Family Credit record for unallocated excess payment.
     */
    public function createFamilyCredit(int $userId, int $paymentId, float $excessAmount, ?string $familyId = null): ?FamilyCredit
    {
        if ($excessAmount <= 0.01) {
            return null;
        }

        return FamilyCredit::create([
            'user_id' => $userId,
            'family_application_id' => $familyId,
            'source_payment_id' => $paymentId,
            'original_amount' => $excessAmount,
            'remaining_amount' => $excessAmount,
            'status' => 'active',
        ]);
    }
}
