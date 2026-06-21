<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SectionSubject;

$schedules = SectionSubject::whereIn('section_id', [7, 21, 47, 53])->get();

foreach ($schedules as $s) {
    echo "ID: {$s->id} | Section ID: {$s->section_id} | Subject: {$s->subject_name} | Teacher: {$s->teacher_name} | Schedule: {$s->schedule}\n";
}
