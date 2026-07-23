<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$applicant = DB::table('enrollment_applicants')->where('id', 44)->first();
if ($applicant) {
    echo 'EXACT LRN VALUE: ';
    var_dump($applicant->lrn);
} else {
    echo "Applicant 44 not found!\n";
}
