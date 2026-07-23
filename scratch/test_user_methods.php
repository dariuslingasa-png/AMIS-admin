<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $users = User::all();
    echo 'Found '.$users->count()." users.\n";
    foreach ($users as $user) {
        echo "User: {$user->email} | Role column: {$user->role}\n";
        echo '  - hasAdminPortalAccess: '.($user->hasAdminPortalAccess() ? 'Yes' : 'No')."\n";
        echo "  - hasRole('admin'): ".($user->hasRole('admin') ? 'Yes' : 'No')."\n";
        echo "  - hasPermission('payment_review'): ".($user->hasPermission('payment_review') ? 'Yes' : 'No')."\n";
    }
    echo "All user check successful!\n";
} catch (Throwable $e) {
    echo "ERROR:\n".$e->getMessage()."\n".$e->getTraceAsString()."\n";
}
