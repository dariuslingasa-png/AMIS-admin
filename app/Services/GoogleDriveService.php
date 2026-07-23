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
        $this->clientId = config('services.google_drive.client_id');
        $this->clientSecret = config('services.google_drive.client_secret');
        $this->refreshToken = config('services.google_drive.refresh_token');
        $this->folderId = config('services.google_drive.folder_id');
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId) && filled($this->clientSecret) && filled($this->refreshToken);
    }

    public function uploadFile(string $filePath, string $filename): bool
    {
        return $this->uploadFileToFolder($filePath, $filename, $this->folderId ?: 'root');
    }

    public function findOrCreateFolder(string $folderName, ?string $parentId = null): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Google Drive credentials are not fully configured.');
        }

        $accessToken = $this->getAccessToken();
        $parent = $parentId ?: $this->folderId ?: 'root';

        // Search for existing folder
        $query = "name='" . str_replace("'", "\\'", $folderName) . "' and mimeType='application/vnd.google-apps.folder' and '{$parent}' in parents and trashed = false";
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->get('https://www.googleapis.com/drive/v3/files', [
            'q' => $query,
            'fields' => 'files(id, name)',
            'pageSize' => 1
        ]);

        if ($response->successful()) {
            $files = $response->json('files');
            if (!empty($files)) {
                return $files[0]['id'];
            }
        }

        // Create folder
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ])->post('https://www.googleapis.com/drive/v3/files', [
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parent]
        ]);

        if (!$response->successful()) {
            Log::error('Google Drive Create Folder Failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Google Drive API returned error: ' . $response->body());
        }

        return $response->json('id');
    }

    public function uploadFileToFolder(string $filePath, string $filename, string $parentId): bool
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Google Drive credentials are not fully configured.');
        }

        $accessToken = $this->getAccessToken();

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new \Exception("Failed to read file at: {$filePath}");
        }

        $metadata = [
            'name' => $filename,
            'parents' => [$parentId]
        ];

        $boundary = 'amis_gdrive_upload_boundary_' . time();
        
        $mimeType = 'application/octet-stream';
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $mimeType = 'application/pdf';
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            $mimeType = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
        }

        $multipartBody = "--{$boundary}\r\n" .
            "Content-Type: application/json; charset=UTF-8\r\n\r\n" .
            json_encode($metadata) . "\r\n" .
            "--{$boundary}\r\n" .
            "Content-Type: {$mimeType}\r\n\r\n" .
            $fileContent . "\r\n" .
            "--{$boundary}--";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->withBody($multipartBody, "multipart/related; boundary={$boundary}")
          ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

        if (!$response->successful()) {
            Log::error('Google Drive Upload File to Folder Failed', [
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
            $body = $response->body();
            if (!str_contains($body, 'invalid_grant')) {
                Log::warning('Google Drive OAuth Token Refresh Failed', [
                    'status' => $response->status(),
                    'body' => $body,
                ]);
            }
            throw new \Exception('Failed to refresh Google Drive access token: ' . $body);
        }

        return $response->json('access_token');
    }

    public function getStorageQuota(): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $accessToken = $this->getAccessToken();
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get('https://www.googleapis.com/drive/v3/about?fields=storageQuota');

            if ($response->successful()) {
                $quota = $response->json('storageQuota');
                if ($quota) {
                    $limit = (float) ($quota['limit'] ?? 0);
                    $usage = (float) ($quota['usage'] ?? 0);
                    $free = max(0, $limit - $usage);
                    
                    return [
                        'limit' => $limit,
                        'usage' => $usage,
                        'free' => $free,
                        'usage_percent' => $limit > 0 ? round(($usage / $limit) * 100, 2) : 0,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently return null when token is invalid or quota call fails to prevent laravel.log spamming
        }

        return null;
    }
}
