<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Finance\FinanceDemoDataService;
use Illuminate\Support\Facades\Cache;

$demo = app(FinanceDemoDataService::class);
Cache::flush();

echo "=======================================================\n";
echo "   CLEAN ₱100 ROUND-ROBIN ALLOCATION TEST SUITE        \n";
echo "=======================================================\n\n";

$passedCount = 0;
$totalCount = 0;

function assertCondition(string $name, bool $condition, string $detail = ''): void {
    global $passedCount, $totalCount;
    $totalCount++;
    if ($condition) {
        $passedCount++;
        echo "✓ PASS: {$name}\n";
    } else {
        echo "✗ FAIL: {$name} - {$detail}\n";
    }
}

// 1. 4 children + ₱500
Cache::flush();
$sch1 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'C1', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C2', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C3', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C4', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
    ]]
];
$r1 = $demo->allocateScheduleRoundRobin($sch1, 500.0, 'FAM-TEST-1', true);
$c1 = collect($r1['allocations'])->keyBy('student_name');
assertCondition('4 children + ₱500: C1 gets ₱200', ($c1['C1']['allocated'] ?? 0) === 200.0);
assertCondition('4 children + ₱500: C2 gets ₱100', ($c1['C2']['allocated'] ?? 0) === 100.0);
assertCondition('4 children + ₱500: C3 gets ₱100', ($c1['C3']['allocated'] ?? 0) === 100.0);
assertCondition('4 children + ₱500: C4 gets ₱100', ($c1['C4']['allocated'] ?? 0) === 100.0);
assertCondition('4 children + ₱500: Total is ₱500', $r1['total_allocated'] === 500.0);

// 2. Next ₱500 (repeated ₱500 payment) starting at pointer Child 2
$r1b = $demo->allocateScheduleRoundRobin($sch1, 500.0, 'FAM-TEST-1', true);
$c1b = collect($r1b['allocations'])->keyBy('student_name');
assertCondition('Repeated ₱500: C1 gets +₱100', ($c1b['C1']['allocated'] ?? 0) === 100.0);
assertCondition('Repeated ₱500: C2 gets +₱200 (started at C2)', ($c1b['C2']['allocated'] ?? 0) === 200.0);
assertCondition('Repeated ₱500: C3 gets +₱100', ($c1b['C3']['allocated'] ?? 0) === 100.0);
assertCondition('Repeated ₱500: C4 gets +₱100', ($c1b['C4']['allocated'] ?? 0) === 100.0);

// 3. 4 children + ₱800
Cache::flush();
$sch2 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'C1', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C2', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C3', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C4', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
    ]]
];
$r2 = $demo->allocateScheduleRoundRobin($sch2, 800.0, 'FAM-TEST-2', true);
$c2 = collect($r2['allocations'])->keyBy('student_name');
assertCondition('4 children + ₱800: All 4 children get ₱200 each',
    ($c2['C1']['allocated'] ?? 0) === 200.0 &&
    ($c2['C2']['allocated'] ?? 0) === 200.0 &&
    ($c2['C3']['allocated'] ?? 0) === 200.0 &&
    ($c2['C4']['allocated'] ?? 0) === 200.0
);

// 4. 4 children + ₱1,000
Cache::flush();
$sch3 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'C1', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C2', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C3', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C4', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
    ]]
];
$r3 = $demo->allocateScheduleRoundRobin($sch3, 1000.0, 'FAM-TEST-3', true);
$c3 = collect($r3['allocations'])->keyBy('student_name');
assertCondition('4 children + ₱1,000: C1: ₱300, C2: ₱300, C3: ₱200, C4: ₱200',
    ($c3['C1']['allocated'] ?? 0) === 300.0 &&
    ($c3['C2']['allocated'] ?? 0) === 300.0 &&
    ($c3['C3']['allocated'] ?? 0) === 200.0 &&
    ($c3['C4']['allocated'] ?? 0) === 200.0
);

// 5. 4 children + ₱1,500
Cache::flush();
$sch4 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'C1', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C2', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C3', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
        ['student_name' => 'C4', 'original' => 3500.0, 'remaining' => 3500.0, 'verified' => 0.0],
    ]]
];
$r4 = $demo->allocateScheduleRoundRobin($sch4, 1500.0, 'FAM-TEST-4', true);
$c4 = collect($r4['allocations'])->keyBy('student_name');
assertCondition('4 children + ₱1,500: C1: ₱400, C2: ₱400, C3: ₱400, C4: ₱300',
    ($c4['C1']['allocated'] ?? 0) === 400.0 &&
    ($c4['C2']['allocated'] ?? 0) === 400.0 &&
    ($c4['C3']['allocated'] ?? 0) === 400.0 &&
    ($c4['C4']['allocated'] ?? 0) === 300.0
);

// 6. 3 children + ₱5,000
Cache::flush();
$sch5 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'Ahmad', 'original' => 3803.33, 'remaining' => 3803.33, 'verified' => 0.0],
        ['student_name' => 'Maryam', 'original' => 3926.11, 'remaining' => 3926.11, 'verified' => 0.0],
        ['student_name' => 'Yusuf', 'original' => 4077.22, 'remaining' => 4077.22, 'verified' => 0.0],
    ]]
];
$r5 = $demo->allocateScheduleRoundRobin($sch5, 5000.0, 'FAM-TEST-5', true);
$c5 = collect($r5['allocations'])->keyBy('student_name');
assertCondition('3 children + ₱5,000: Ahmad: ₱1,700, Maryam: ₱1,700, Yusuf: ₱1,600',
    ($c5['Ahmad']['allocated'] ?? 0) === 1700.0 &&
    ($c5['Maryam']['allocated'] ?? 0) === 1700.0 &&
    ($c5['Yusuf']['allocated'] ?? 0) === 1600.0
);
assertCondition('3 children + ₱5,000: Balances are exact (₱2,103.33, ₱2,226.11, ₱2,477.22)',
    ($c5['Ahmad']['remaining_due'] ?? 0) === 2103.33 &&
    ($c5['Maryam']['remaining_due'] ?? 0) === 2226.11 &&
    ($c5['Yusuf']['remaining_due'] ?? 0) === 2477.22
);

// 7. Child remaining under ₱100 (₱77.22) with ₱500 payment
Cache::flush();
$sch6 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'C1', 'original' => 1000.0, 'remaining' => 77.22, 'verified' => 922.78],
        ['student_name' => 'C2', 'original' => 1000.0, 'remaining' => 1000.0, 'verified' => 0.0],
        ['student_name' => 'C3', 'original' => 1000.0, 'remaining' => 1000.0, 'verified' => 0.0],
    ]]
];
$r6 = $demo->allocateScheduleRoundRobin($sch6, 500.0, 'FAM-TEST-6', true);
$c6 = collect($r6['allocations'])->keyBy('student_name');
assertCondition('Child under ₱100: C1 gets exact ₱77.22 and becomes FULLY_PAID',
    ($c6['C1']['allocated'] ?? 0) === 77.22 && ($c6['C1']['remaining_due'] ?? 0) === 0.0
);
assertCondition('Child under ₱100: C2 gets ₱222.78 and C3 gets ₱200.00',
    ($c6['C2']['allocated'] ?? 0) === 222.78 && ($c6['C3']['allocated'] ?? 0) === 200.0
);
assertCondition('Child under ₱100: Total is exactly ₱500.00', $r6['total_allocated'] === 500.0);

// 8. Fully paid child is skipped
Cache::flush();
$sch7 = [
    ['label' => 'JULY 2026', 'children' => [
        ['student_name' => 'C1', 'original' => 1000.0, 'remaining' => 500.0, 'verified' => 500.0],
        ['student_name' => 'C2', 'original' => 1000.0, 'remaining' => 0.0, 'verified' => 1000.0], // FULLY PAID
        ['student_name' => 'C3', 'original' => 1000.0, 'remaining' => 500.0, 'verified' => 500.0],
        ['student_name' => 'C4', 'original' => 1000.0, 'remaining' => 500.0, 'verified' => 500.0],
    ]]
];
$r7 = $demo->allocateScheduleRoundRobin($sch7, 500.0, 'FAM-TEST-7', true);
$c7 = collect($r7['allocations'])->keyBy('student_name');
assertCondition('Fully paid child C2 is skipped: C2 gets ₱0', !isset($c7['C2']) || ($c7['C2']['allocated'] ?? 0) === 0.0);
assertCondition('Remaining children C1, C3, C4 share the ₱500: C1: ₱200, C3: ₱200, C4: ₱100',
    ($c7['C1']['allocated'] ?? 0) === 200.0 &&
    ($c7['C3']['allocated'] ?? 0) === 200.0 &&
    ($c7['C4']['allocated'] ?? 0) === 100.0
);

// 9. Month close with centavos & cross-month (July rem ₱1,806.66 + August ₱11,806.66, Payment ₱5,000)
Cache::flush();
$sch8 = [
    [
        'label' => 'JULY 2026',
        'children' => [
            ['student_name' => 'Ahmad', 'original' => 3803.33, 'remaining' => 803.33, 'verified' => 3000.0],
            ['student_name' => 'Maryam', 'original' => 3926.11, 'remaining' => 926.11, 'verified' => 3000.0],
            ['student_name' => 'Yusuf', 'original' => 4077.22, 'remaining' => 77.22, 'verified' => 4000.0],
        ]
    ],
    [
        'label' => 'AUGUST 2026',
        'children' => [
            ['student_name' => 'Ahmad', 'original' => 3803.33, 'remaining' => 3803.33, 'verified' => 0.0],
            ['student_name' => 'Maryam', 'original' => 3926.11, 'remaining' => 3926.11, 'verified' => 0.0],
            ['student_name' => 'Yusuf', 'original' => 4077.22, 'remaining' => 4077.22, 'verified' => 0.0],
        ]
    ]
];
$r8 = $demo->allocateScheduleRoundRobin($sch8, 5000.0, 'FAM-TEST-8', true);
$julyAllocs = collect($r8['allocations'])->where('month', 'JULY 2026')->keyBy('student_name');
$augAllocs = collect($r8['allocations'])->where('month', 'AUGUST 2026')->keyBy('student_name');
assertCondition('Cross-month: July closed exactly with ₱1,806.66',
    ($julyAllocs['Ahmad']['allocated'] ?? 0) === 803.33 &&
    ($julyAllocs['Maryam']['allocated'] ?? 0) === 926.11 &&
    ($julyAllocs['Yusuf']['allocated'] ?? 0) === 77.22
);
assertCondition('Cross-month: August received ₱3,193.34 in ₱100 round-robin + leftover',
    ($augAllocs['Ahmad']['allocated'] ?? 0) === 1100.0 &&
    ($augAllocs['Maryam']['allocated'] ?? 0) === 1093.34 &&
    ($augAllocs['Yusuf']['allocated'] ?? 0) === 1000.0
);
assertCondition('Cross-month: Total across months is exactly ₱5,000.00', $r8['total_allocated'] === 5000.0);

// 10. Excess creating Family Credit
Cache::flush();
$sch9 = [
    [
        'label' => 'JULY 2026',
        'children' => [
            ['student_name' => 'C1', 'original' => 5000.0, 'remaining' => 5000.0, 'verified' => 0.0],
            ['student_name' => 'C2', 'original' => 5000.0, 'remaining' => 5000.0, 'verified' => 0.0],
        ]
    ]
];
$r9 = $demo->allocateScheduleRoundRobin($sch9, 10500.0, 'FAM-TEST-9', true);
assertCondition('Family Credit: Total allocated is ₱10,000 and Advance Credit is ₱500.00',
    $r9['total_allocated'] === 10000.0 && $r9['advance_credit'] === 500.0
);

echo "\n=======================================================\n";
echo "   RESULTS: {$passedCount} / {$totalCount} TESTS PASSED (" . round(($passedCount / $totalCount) * 100) . "%)\n";
echo "=======================================================\n";
