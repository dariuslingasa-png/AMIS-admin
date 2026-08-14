<?php

function allocateScheduleRoundRobin(array $schedule, float $payment, int|string $familyId, bool $persistPointer = false): array {
    $remainingPayment = round($payment, 2);
    $allocations = [];
    $updatedSchedule = [];

    foreach ($schedule as $monthGroup) {
        $monthLabel = $monthGroup['label'];
        $childrenState = $monthGroup['children'];
        $monthTotalRemaining = array_sum(array_column($childrenState, 'remaining'));

        if ($remainingPayment <= 0.001 || $monthTotalRemaining <= 0.001) {
            $updatedSchedule[] = $monthGroup;
            continue;
        }

        $numChildren = count($childrenState);
        $monthKey = "demo_rr_ptr_{$familyId}_" . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($monthLabel));
        $pointer = $persistPointer ? (int) ($GLOBALS[$monthKey] ?? 0) : 0;
        if ($pointer < 0 || $pointer >= $numChildren) {
            $pointer = 0;
        }

        // If payment covers the entire month's remaining balance exactly or with excess
        if ($remainingPayment >= ($monthTotalRemaining - 0.001)) {
            foreach ($childrenState as $idx => &$c) {
                $toPay = (float) $c['remaining'];
                if ($toPay > 0.001) {
                    $c['allocated'] = round(($c['allocated'] ?? 0) + $toPay, 2);
                    $c['verified'] = round(($c['verified'] ?? 0) + $toPay, 2);
                    $c['remaining'] = 0.0;
                    $c['status'] = 'FULLY_PAID';
                    $remainingPayment = max(0.0, round($remainingPayment - $toPay, 2));

                    $allocations[] = [
                        'month' => $monthLabel,
                        'student_name' => $c['name'],
                        'allocated' => $toPay,
                        'remaining_due' => 0.0,
                        'status' => 'FULLY_PAID',
                    ];
                }
            }
            unset($c);

            if ($persistPointer) {
                $GLOBALS[$monthKey] = 0;
            }
        } else {
            // ₱100 Round-robin allocation loop
            $safety = 50000;
            while ($remainingPayment > 0.001 && $safety-- > 0) {
                $eligible = [];
                for ($i = 0; $i < $numChildren; $i++) {
                    if (($childrenState[$i]['remaining'] ?? 0) > 0.001) {
                        $eligible[] = $i;
                    }
                }
                if (empty($eligible)) break;

                $targetIndex = null;
                for ($step = 0; $step < $numChildren; $step++) {
                    $check = ($pointer + $step) % $numChildren;
                    if (in_array($check, $eligible, true)) {
                        $targetIndex = $check;
                        break;
                    }
                }
                if ($targetIndex === null) break;

                $c = &$childrenState[$targetIndex];
                $rem = (float) $c['remaining'];

                if ($rem < 100.0) {
                    $unit = min($rem, $remainingPayment);
                } elseif ($remainingPayment < 100.0) {
                    $unit = min($remainingPayment, $rem);
                } else {
                    $unit = min(100.0, $rem, $remainingPayment);
                }
                $unit = round($unit, 2);
                if ($unit <= 0.0001) break;

                $c['allocated'] = round(($c['allocated'] ?? 0) + $unit, 2);
                $c['verified'] = round(($c['verified'] ?? 0) + $unit, 2);
                $c['remaining'] = max(0.0, round($rem - $unit, 2));
                $c['status'] = $c['remaining'] <= 0.01 ? 'FULLY_PAID' : 'PARTIALLY_PAID';
                $remainingPayment = max(0.0, round($remainingPayment - $unit, 2));

                $pointer = ($targetIndex + 1) % $numChildren;
            }
            unset($c);

            if ($persistPointer) {
                $GLOBALS[$monthKey] = $pointer;
            }

            foreach ($childrenState as $c) {
                if (($c['allocated'] ?? 0) > 0.001) {
                    $allocations[] = [
                        'month' => $monthLabel,
                        'student_name' => $c['name'],
                        'allocated' => $c['allocated'],
                        'remaining_due' => $c['remaining'],
                        'status' => $c['status'],
                    ];
                }
            }
        }

        $monthGroup['children'] = $childrenState;
        $monthGroup['remaining'] = array_sum(array_column($childrenState, 'remaining'));
        $monthGroup['total_paid'] = array_sum(array_column($childrenState, 'verified'));
        $updatedSchedule[] = $monthGroup;
    }

    return [
        'allocations' => $allocations,
        'total_allocated' => round($payment - $remainingPayment, 2),
        'advance_credit' => $remainingPayment > 0 ? round($remainingPayment, 2) : 0.0,
        'schedule' => $updatedSchedule,
    ];
}

echo "=== TEST 8: Cross-Month Payment (July remaining ₱1,806.66 + August ₱11,806.66, Payment ₱5,000) ===\n";
$schedule = [
    [
        'label' => 'JULY 2026',
        'children' => [
            ['name' => 'Ahmad', 'original' => 3803.33, 'verified' => 3000.0, 'remaining' => 803.33, 'allocated' => 0.0],
            ['name' => 'Maryam', 'original' => 3926.11, 'verified' => 3000.0, 'remaining' => 926.11, 'allocated' => 0.0],
            ['name' => 'Yusuf', 'original' => 4077.22, 'verified' => 4000.0, 'remaining' => 77.22, 'allocated' => 0.0],
        ]
    ],
    [
        'label' => 'AUGUST 2026',
        'children' => [
            ['name' => 'Ahmad', 'original' => 3803.33, 'verified' => 0.0, 'remaining' => 3803.33, 'allocated' => 0.0],
            ['name' => 'Maryam', 'original' => 3926.11, 'verified' => 0.0, 'remaining' => 3926.11, 'allocated' => 0.0],
            ['name' => 'Yusuf', 'original' => 4077.22, 'verified' => 0.0, 'remaining' => 4077.22, 'allocated' => 0.0],
        ]
    ]
];

$res8 = allocateScheduleRoundRobin($schedule, 5000.0, 'DEMO-001', true);
echo "Total Allocated: ₱" . $res8['total_allocated'] . ", Advance Credit: ₱" . $res8['advance_credit'] . "\n";
foreach ($res8['allocations'] as $a) {
    echo "  [{$a['month']}] {$a['student_name']}: Allocated ₱{$a['allocated']}, Remaining ₱{$a['remaining_due']} ({$a['status']})\n";
}

echo "\n=== TEST 9: Repeated ₱1,000 Payments (4 children) ===\n";
$sch9 = [
    [
        'label' => 'JULY 2026',
        'children' => [
            ['name' => 'C1', 'original' => 3500.0, 'verified' => 0.0, 'remaining' => 3500.0, 'allocated' => 0.0],
            ['name' => 'C2', 'original' => 3500.0, 'verified' => 0.0, 'remaining' => 3500.0, 'allocated' => 0.0],
            ['name' => 'C3', 'original' => 3500.0, 'verified' => 0.0, 'remaining' => 3500.0, 'allocated' => 0.0],
            ['name' => 'C4', 'original' => 3500.0, 'verified' => 0.0, 'remaining' => 3500.0, 'allocated' => 0.0],
        ]
    ]
];

$res9a = allocateScheduleRoundRobin($sch9, 1000.0, 'DEMO-002', true);
echo "Tx 1 (₱1,000):\n";
foreach ($res9a['allocations'] as $a) {
    echo "  {$a['student_name']}: ₱{$a['allocated']}\n";
}

// Second payment with updated schedule
$res9b = allocateScheduleRoundRobin($res9a['schedule'], 1000.0, 'DEMO-002', true);
echo "Tx 2 (₱1,000):\n";
foreach ($res9b['allocations'] as $a) {
    echo "  {$a['student_name']}: ₱{$a['allocated']}\n";
}

echo "\n=== TEST 10: Excess Creating Family Credit ===\n";
$sch10 = [
    [
        'label' => 'JULY 2026',
        'children' => [
            ['name' => 'C1', 'original' => 5000.0, 'verified' => 0.0, 'remaining' => 5000.0, 'allocated' => 0.0],
            ['name' => 'C2', 'original' => 5000.0, 'verified' => 0.0, 'remaining' => 5000.0, 'allocated' => 0.0],
        ]
    ]
];
$res10 = allocateScheduleRoundRobin($sch10, 10500.0, 'DEMO-003', true);
echo "Payment ₱10,500 on ₱10,000 Total Dues:\n";
echo "Total Allocated: ₱{$res10['total_allocated']}, Advance Credit Created: ₱{$res10['advance_credit']}\n";
