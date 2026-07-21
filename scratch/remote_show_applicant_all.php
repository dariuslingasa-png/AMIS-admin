<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$applicant = \DB::table('enrollment_applicants')->where('id', 44)->first();
if ($applicant) {
    foreach ((array)$applicant as $key => $val) {
        if ($val !== null && $val !== '') {
            echo "  {$key}: {$val}\n";
        }
    }
} else {
    echo "Applicant 44 not found!\n";
}
