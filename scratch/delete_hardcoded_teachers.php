<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Repositories\TeacherRepository;

$idsToDelete = [
    'ust-raffy-lingasa',
    'ust-ahmad-al-jamil',
    'ust-omar-mukhtar',
    'ustadha-isal',
];

$emailsToDelete = [
    'tr.rlingasa@amis.edu.ph',
    'tr.ajamil@amis.edu.ph',
    'tr.omukhtar@amis.edu.ph',
    'tr.isal@amis.edu.ph',
];

$repo = app(TeacherRepository::class);
$overrides = $repo->overrides();

// 1. Remove from overrides
foreach ($idsToDelete as $id) {
    if (isset($overrides[$id])) {
        unset($overrides[$id]);
        echo "Removed {$id} from overrides.\n";
    }
}

$repo->saveOverrides($overrides);

// 2. Delete from database users
User::whereIn('username', $idsToDelete)
    ->orWhereIn('email', $emailsToDelete)
    ->delete();

echo "Deleted database user accounts.\n";
