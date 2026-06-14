<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Fetching roles...\n";
    $roles = \App\Models\Role::orderBy('hierarchy_level', 'desc')->get();
    echo "Roles count: " . $roles->count() . "\n";

    echo "Fetching permissions...\n";
    $permissions = \App\Models\Permission::all()->groupBy('category');
    echo "Categories count: " . $permissions->count() . "\n";

    echo "Attempting to eager-load permissions on roles...\n";
    foreach ($roles as $role) {
        echo "Role: {$role->name} (Permissions loaded: " . $role->permissions->count() . ")\n";
    }

    view()->share('errors', new \Illuminate\Support\ViewErrorBag);

    echo "Rendering view...\n";
    $html = view('admin.access-control.permissions.index', compact('roles', 'permissions'))
        ->with('errors', new \Illuminate\Support\ViewErrorBag)
        ->render();
    echo "Render successful! HTML length: " . strlen($html) . "\n";

} catch (\Throwable $e) {
    echo "ERROR ENCOUNTERED:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
