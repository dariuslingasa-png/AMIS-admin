<?php

use App\Services\MicrosoftGraphService;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$graph = new MicrosoftGraphService;

// Let's modify the listTenantStudents logic temporarily to fetch with signInActivity
try {
    $url = '/users?$select=id,displayName,userPrincipalName,userType,accountEnabled,assignedLicenses,givenName,surname,lastPasswordChangeDateTime,createdDateTime,signInActivity&$top=10';

    // We can call the graph API directly using reflection or a quick HTTP request
    // Since graph() is private, we can use reflection to access it
    $reflector = new ReflectionClass($graph);
    $method = $reflector->getMethod('graph');
    $method->setAccessible(true);
    $http = $method->invoke($graph);

    $response = $http->get($url);
    if ($response->successful()) {
        $data = $response->json();
        print_r($data['value'][0] ?? 'No users found');
    } else {
        echo 'Error: status '.$response->status()."\n";
        echo $response->body()."\n";
    }
} catch (Exception $e) {
    echo 'Exception: '.$e->getMessage()."\n";
}
