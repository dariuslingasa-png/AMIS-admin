<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$teachers = User::where('role', 'teacher')->get();
foreach ($teachers as $t) {
    echo "Name: '{$t->name}' | Email: '{$t->email}'\n";
}
