<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apps = \DB::table('enrollment_applicants')
    ->where('first_name', 'like', '%AMIR%')
    ->orWhere('first_name', 'like', '%SHAHEEN%')
    ->orWhere('last_name', 'like', '%SACAR%')
    ->get();

foreach ($apps as $a) {
    echo "Applicant ID: {$a->id}, Name: {$a->first_name} {$a->last_name}, LRN: " . ($a->lrn ?: 'NULL') . "\n";
}
