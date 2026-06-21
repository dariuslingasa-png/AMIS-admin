<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$service = app(\App\Services\Admin\Academic\TeacherDirectoryService::class);
$names = collect($service->indexPayload()['teachers'])->pluck('name')->sort()->values();
foreach ($names as $idx => $name) {
    echo ($idx + 1) . ". " . $name . "\n";
}
