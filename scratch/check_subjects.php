<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use Illuminate\Contracts\Console\Kernel;

$subjects = Subject::where('status', 'active')->get();
foreach ($subjects as $s) {
    echo "ID: {$s->id} | Name: '{$s->name}' | Code: '{$s->code}' | Grade: '{$s->grade_level}'\n";
}
