<?php

use App\Http\Controllers\Admin\AccessControlController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    echo "Faking a user login...\n";
    $admin = User::where('role', 'admin')->first();
    auth()->login($admin);
    echo "Logged in as: {$admin->email}\n";

    echo "Running matrix save simulation...\n";

    // Let's build a fake matrix request
    $roles = Role::all();
    $permissions = Permission::all();

    $matrix = [];
    foreach ($roles as $role) {
        if ($role->slug === 'super_admin') {
            continue;
        }
        // Assign first permission to each role
        if ($permissions->isNotEmpty()) {
            $matrix[$role->id][$permissions->first()->id] = 'on';
        }
    }

    $request = new Request;
    $request->merge(['matrix' => $matrix]);

    $controller = new AccessControlController;
    $response = $controller->permissionsUpdate($request);

    echo 'Simulation completed successfully! Response: '.get_class($response)."\n";
} catch (Throwable $e) {
    echo "ERROR IN UPDATE SIMULATION:\n";
    echo $e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}
