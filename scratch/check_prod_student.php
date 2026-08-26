<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SECTION 96 SCHEDULES ===\n";
$s96 = DB::table('class_schedules')->where('section_id', 96)->get();
print_r($s96->toArray());

echo "\n=== SECTION 96 SUBJECTS ===\n";
$subj96 = DB::table('section_subjects')->where('section_id', 96)->get();
print_r($subj96->toArray());
