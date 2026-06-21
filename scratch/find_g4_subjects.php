<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;

$subjects = Subject::where('grade_level', 'Grade 4')->get();
foreach ($subjects as $s) {
    echo "ID: {$s->id} | Name: '{$s->name}' | Code: '{$s->code}'\n";
}
