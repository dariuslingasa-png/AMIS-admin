<?php

use App\Services\MicrosoftGraphService;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$graph = new MicrosoftGraphService;
$users = $graph->listTenantStudents();
$user = collect($users)->firstWhere('userPrincipalName', '260267cali@amis.edu.ph');
print_r($user);
