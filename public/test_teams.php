<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Http;

try {
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    $graphService = new \App\Services\MicrosoftGraphService();
    
    $reflection = new \ReflectionClass($graphService);
    $method = $reflection->getMethod('getDelegatedToken');
    $method->setAccessible(true);
    $token = $method->invoke($graphService);
    
    echo "Delegated Access Token retrieved successfully.\n\n";
    
    $response = Http::withToken($token)
        ->get('https://graph.microsoft.com/v1.0/groups?$filter=resourceProvisioningOptions/any(x:x eq \'Team\')&$select=id,displayName');
        
    if (!$response->successful()) {
        throw new \Exception("Failed to list teams: " . $response->body());
    }
    
    $teams = $response->json('value', []);
    echo "Found " . count($teams) . " Teams:\n";
    
    foreach ($teams as $team) {
        $teamId = $team['id'];
        $teamName = $team['displayName'];
        echo "==================================================\n";
        echo "TEAM: {$teamName} (ID: {$teamId})\n";
        
        $channelsResponse = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/teams/{$teamId}/channels");
            
        if ($channelsResponse->successful()) {
            $channels = $channelsResponse->json('value', []);
            echo "  CHANNELS (" . count($channels) . "):\n";
            foreach ($channels as $channel) {
                echo "    - " . $channel['displayName'] . " (ID: " . $channel['id'] . ")\n";
            }
        } else {
            echo "  CHANNELS: Failed to fetch (" . $channelsResponse->body() . ")\n";
        }
        
        $membersResponse = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/groups/{$teamId}/members?\$select=id,displayName,userPrincipalName");
            
        if ($membersResponse->successful()) {
            $members = $membersResponse->json('value', []);
            echo "  MEMBERS (" . count($members) . "):\n";
            foreach ($members as $member) {
                echo "    - " . $member['displayName'] . " (" . $member['userPrincipalName'] . ") [ID: " . $member['id'] . "]\n";
            }
        } else {
            echo "  MEMBERS: Failed to fetch (" . $membersResponse->body() . ")\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
