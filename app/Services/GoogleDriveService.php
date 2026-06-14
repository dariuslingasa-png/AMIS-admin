<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    private ?string $clientId;
    private ?string $clientSecret;
    private ?string $refreshToken;
    private ?string $folderId;

    public function __construct()
    {
        $this->clientId = env('GOOGLE_DRIVE_CLIENT_ID');
        $this->clientSecret = env('GOOGLE_DRIVE_CLIENT_SECRET');
        $this->refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');
        $this->folderId = env('GOOGLE_DRIVE_FOLDER_ID');
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId) && filled($this->clientSecret) && filled($this->refreshToken);
    }

    public function uploadFile(string $filePath, string $filename): bool
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Google Drive credentials are not fully configured in your .env file.');
        }

        // 1. Get Access Token via OAuth2 Token Refresh
        $accessToken = $this->getAccessToken();

        // 2. Read file contents
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \Exception("Failed to read backup file at: {$filePath}");
        }

        $metadata = [
            'name' => $filename,
        ];
        if (filled($this->folderId)) {
            $metadata['parents'] = [$this->folderId];
        }

        $boundary = 'amis_gdrive_upload_boundary_' . time();
        
        $multipartBody = "--{$boundary}\r\n" .
            "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
            json_encode($metadata) . "\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: text/plain\r\n\r\n" .
            $fileContent . "\r\n" .
            "--{$boundary}--";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => "multipart/related; boundary={$boundary}",
        ])->withBody($multipartBody, 'multipart/related')->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if (!$response->successful()) {
            Log::error('Google Drive Upload Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Google Drive API returned error: ' . $response->body());
        }

        return true;
    }

    private function getAccessToken(): string
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::error('Google Drive OAuth Token Refresh Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh Google Drive access token: ' . $response->body());
        }

        return $response->json('access_token');
    }
}
