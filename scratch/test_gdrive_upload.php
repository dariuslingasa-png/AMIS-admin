<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

$clientId = env('GOOGLE_DRIVE_CLIENT_ID');
$clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
$refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
$baseFolderId = env('GOOGLE_DRIVE_FOLDER_ID');

// Refresh Token
echo "Refreshing token...\n";
$res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'refresh_token' => $refreshToken,
    'grant_type' => 'refresh_token',
]);

if (! $res->successful()) {
    exit('OAuth Refresh Failed: '.$res->body()."\n");
}
$accessToken = $res->json('access_token');
echo 'OAuth Access Token: '.substr($accessToken, 0, 10)."...\n";

// Test Folder Creation
echo "Creating test folder under base folder ($baseFolderId)...\n";
$folderName = 'TEST_GRADE_K1';
$res = Http::withHeaders([
    'Authorization' => "Bearer {$accessToken}",
    'Content-Type' => 'application/json',
])->post('https://www.googleapis.com/drive/v3/files', [
    'name' => $folderName,
    'mimeType' => 'application/vnd.google-apps.folder',
    'parents' => [$baseFolderId],
]);

if (! $res->successful()) {
    exit('Folder Creation Failed: '.$res->body()."\n");
}
$folderId = $res->json('id');
echo "Folder Created! ID: $folderId\n";

// Test File Upload inside this folder
echo "Uploading test file...\n";
$metadata = [
    'name' => 'test_payment_proof.txt',
    'parents' => [$folderId],
];
$fileContent = 'Test proof of payment file uploaded at '.date('Y-m-d H:i:s');
$boundary = 'test_upload_boundary_'.time();
$multipartBody = "--{$boundary}\r\n".
    "Content-Type: application/json; charset=UTF-8\r\n\r\n".
    json_encode($metadata)."\r\n".
    "--{$boundary}\r\n".
    "Content-Type: text/plain\r\n\r\n".
    $fileContent."\r\n".
    "--{$boundary}--";

$res = Http::withHeaders([
    'Authorization' => "Bearer {$accessToken}",
])->withBody($multipartBody, "multipart/related; boundary={$boundary}")
    ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

if (! $res->successful()) {
    exit('File Upload Failed: '.$res->body()."\n");
}
echo 'File Uploaded Successfully! ID: '.$res->json('id')."\n";
