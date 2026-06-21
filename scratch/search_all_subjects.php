<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;

$names = ['GMRC', 'SHAF', 'AP', 'TLE', 'Araling Panlipunan', 'EP', 'ESP', 'Qur'];
foreach ($names as $name) {
    $found = Subject::where('name', 'like', "%{$name}%")->get();
    echo "--- Search for '{$name}' ---\n";
    foreach ($found as $s) {
        echo "ID: {$s->id} | Name: '{$s->name}' | Code: '{$s->code}' | Grade: '{$s->grade_level}'\n";
    }
}
