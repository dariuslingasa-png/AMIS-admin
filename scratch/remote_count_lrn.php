<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Total applicants
$total = DB::table('enrollment_applicants')->count();

// Count with LRN (not null, not empty, not NA, not N/A, not EMPTY)
$withLrn = DB::table('enrollment_applicants')
    ->whereNotNull('lrn')
    ->where('lrn', '!=', '')
    ->where('lrn', '!=', 'NA')
    ->where('lrn', '!=', 'N/A')
    ->where('lrn', '!=', 'EMPTY')
    ->whereRaw('length(lrn) >= 10')
    ->count();

$withoutLrn = $total - $withLrn;

echo "LRN Statistics:\n";
echo '  Total Students/Applicants: '.$total."\n";
echo '  Students with VALID LRN: '.$withLrn."\n";
echo '  Students WITHOUT LRN: '.$withoutLrn."\n";

// Show some examples of valid LRNs
$examples = DB::table('enrollment_applicants')
    ->whereNotNull('lrn')
    ->where('lrn', '!=', '')
    ->where('lrn', '!=', 'NA')
    ->where('lrn', '!=', 'N/A')
    ->where('lrn', '!=', 'EMPTY')
    ->whereRaw('length(lrn) >= 10')
    ->select('first_name', 'last_name', 'lrn')
    ->limit(5)
    ->get();

if ($examples->count() > 0) {
    echo "Examples of students with LRN:\n";
    foreach ($examples as $ex) {
        echo "  - {$ex->first_name} {$ex->last_name}: {$ex->lrn}\n";
    }
}
