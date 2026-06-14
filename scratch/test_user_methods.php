<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $users = \App\Models\User::all();
    echo "Found " . $users->count() . " users.\n";
    foreach ($users as $user) {
        echo "User: {$user->email} | Role column: {$user->role}\n";
        echo "  - hasAdminPortalAccess: " . ($user->hasAdminPortalAccess() ? 'Yes' : 'No') . "\n";
        echo "  - hasRole('admin'): " . ($user->hasRole('admin') ? 'Yes' : 'No') . "\n";
        echo "  - hasPermission('payment_review'): " . ($user->hasPermission('payment_review') ? 'Yes' : 'No') . "\n";
    }
    echo "All user check successful!\n";
} catch (\Throwable $e) {
    echo "ERROR:\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
