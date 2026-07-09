<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Http;

try {
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    $adminEnvPath = '/home2/amisdavc/admin.amis.edu.ph/.env';
    if (!file_exists($adminEnvPath)) {
        throw new \Exception("Admin .env not found at {$adminEnvPath}");
    }
    
    $envContent = file_get_contents($adminEnvPath);
    
    $keys = [
        'MICROSOFT_CLIENT_ID' => null,
        'MICROSOFT_CLIENT_SECRET' => null,
        'MICROSOFT_TENANT_ID' => null,
    ];
    
    foreach (array_keys($keys) as $key) {
        if (preg_match("/^{$key}=(.*)$/m", $envContent, $matches)) {
            $keys[$key] = trim($matches[1]);
        }
    }
    
    // Obtain App Token (Client Credentials)
    $response = Http::asForm()->post(
        "https://login.microsoftonline.com/{$keys['MICROSOFT_TENANT_ID']}/oauth2/v2.0/token",
        [
            'grant_type' => 'client_credentials',
            'client_id' => $keys['MICROSOFT_CLIENT_ID'],
            'client_secret' => $keys['MICROSOFT_CLIENT_SECRET'],
            'scope' => 'https://graph.microsoft.com/.default',
        ]
    );
    
    if (!$response->successful()) {
        throw new \Exception("Failed to get app token: " . $response->body());
    }
    
    $token = $response->json('access_token');
    echo "App Access Token obtained successfully.\n\n";
    
    $teamId = "bfa7e316-446b-4b8f-b8d9-d8f505ec4acb"; // Kinder 2 Abu Bakr
    
    $membersResponse = Http::withToken($token)
        ->get("https://graph.microsoft.com/v1.0/groups/{$teamId}/members?\$select=id,displayName,userPrincipalName");
        
    echo "Members Status: " . $membersResponse->getStatusCode() . "\n";
    if ($membersResponse->successful()) {
        $members = $membersResponse->json('value', []);
        echo "Found " . count($members) . " members:\n";
        foreach ($members as $member) {
            echo "  - " . $member['displayName'] . " (" . ($member['userPrincipalName'] ?? 'No UPN') . ")\n";
        }
    } else {
        echo "Error: " . $membersResponse->body() . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
