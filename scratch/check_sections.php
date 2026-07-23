<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Section;
use Illuminate\Contracts\Console\Kernel;

$sections = Section::where('grade_level', 'like', '%Grade 4%')
    ->orWhere('grade_level', 'like', '%G4%')
    ->get();

foreach ($sections as $s) {
    echo "ID: {$s->id} | Name: {$s->name} | Grade: {$s->grade_level} | Shift: {$s->shift} | Gender: {$s->gender} | Official: {$s->official_name}\n";
}
