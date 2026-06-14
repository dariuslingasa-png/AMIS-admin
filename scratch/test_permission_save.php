<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Faking a user login...\n";
    $admin = \App\Models\User::where('role', 'admin')->first();
    auth()->login($admin);
    echo "Logged in as: {$admin->email}\n";

    echo "Running matrix save simulation...\n";
    
    // Let's build a fake matrix request
    $roles = \App\Models\Role::all();
    $permissions = \App\Models\Permission::all();
    
    $matrix = [];
    foreach ($roles as $role) {
        if ($role->slug === 'super_admin') continue;
        // Assign first permission to each role
        if ($permissions->isNotEmpty()) {
            $matrix[$role->id][$permissions->first()->id] = 'on';
        }
    }
    
    $request = new \Illuminate\Http\Request();
    $request->merge(['matrix' => $matrix]);
    
    $controller = new \App\Http\Controllers\Admin\AccessControlController();
    $response = $controller->permissionsUpdate($request);
    
    echo "Simulation completed successfully! Response: " . get_class($response) . "\n";
} catch (\Throwable $e) {
    echo "ERROR IN UPDATE SIMULATION:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
