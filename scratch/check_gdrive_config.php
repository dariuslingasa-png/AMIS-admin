<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new \App\Services\GoogleDriveService();
echo "Is Configured: " . ($service->isConfigured() ? "YES" : "NO") . "\n";
echo "Client ID: " . (env('GOOGLE_DRIVE_CLIENT_ID') ? "SET" : "NOT SET") . "\n";
echo "Client Secret: " . (env('GOOGLE_DRIVE_CLIENT_SECRET') ? "SET" : "NOT SET") . "\n";
echo "Refresh Token: " . (env('GOOGLE_DRIVE_REFRESH_TOKEN') ? "SET" : "NOT SET") . "\n";
echo "Folder ID: " . (env('GOOGLE_DRIVE_FOLDER_ID') ?: "NOT SET") . "\n";

if ($service->isConfigured()) {
    try {
        echo "Testing token refresh and storage quota...\n";
        $quota = $service->getStorageQuota();
        if ($quota) {
            echo "Quota Limit: " . round($quota['limit'] / 1024 / 1024 / 1024, 2) . " GB\n";
            echo "Quota Usage: " . round($quota['usage'] / 1024 / 1024 / 1024, 2) . " GB\n";
            echo "Quota Free: " . round($quota['free'] / 1024 / 1024 / 1024, 2) . " GB\n";
        } else {
            echo "Failed to get quota.\n";
        }
    } catch (\Exception $e) {
        echo "Error testing credentials: " . $e->getMessage() . "\n";
    }
}
