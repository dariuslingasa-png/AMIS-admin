<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\EnrollmentApplicant;
use Illuminate\Contracts\Console\Kernel;

$apps = EnrollmentApplicant::select('id', 'first_name', 'last_name', 'family_application_id')->get();
$groups = [];
foreach ($apps as $a) {
    $groups[$a->family_application_id][] = $a->id.':'.$a->first_name.' '.$a->last_name;
}

echo "Family ID counts:\n";
foreach ($groups as $famId => $members) {
    if (count($members) > 1) {
        echo "Family ID: $famId - ".count($members).' members: '.implode(', ', $members)."\n";
    }
}
