<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\Admin\Academic\TeacherDirectoryService;
use Illuminate\Contracts\Console\Kernel;

$service = app(TeacherDirectoryService::class);
$payload = $service->indexPayload();
$teachers = $payload['teachers'];

echo 'TOTAL REGISTERED TEACHERS: '.count($teachers)."\n\n";

foreach ($teachers as $t) {
    echo 'ID: '.$t['id']."\n";
    echo 'Name: '.$t['name']."\n";
    echo 'Email: '.$t['email']."\n";
    echo 'Dept: '.$t['dept']."\n";
    echo 'First Name: '.($t['first_name'] ?? '')."\n";
    echo 'Last Name: '.($t['last_name'] ?? '')."\n";
    echo 'MS Sync: '.($t['microsoft_sync'] ? 'Yes' : 'No')."\n";

    // Check if User exists in DB
    $user = User::where('email', $t['email'])->first();
    if ($user) {
        echo "DB User: Found (ID: {$user->id}, Role: {$user->role})\n";
    } else {
        echo "DB User: NOT FOUND\n";
    }
    echo "----------------------------------------\n";
}
