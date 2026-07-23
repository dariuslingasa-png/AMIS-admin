<?php

namespace App\Services\System;

use App\Services\GoogleDriveService;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SystemHealthService
{
    public function getSystemHealthMetrics(): array
    {
        $dbName = config('database.connections.mysql.database');
        
        // Database stats
        $dbConnected = false;
        $dbLatencyMs = 0;
        $tableCount = 0;
        $totalDatabaseSizeBytes = 0;

        $dbStartTime = microtime(true);
        try {
            DB::connection()->getPdo();
            $dbLatencyMs = round((microtime(true) - $dbStartTime) * 1000, 2);
            $dbConnected = true;

            if ($dbName && config('database.default') === 'mysql') {
                $tablesStats = DB::select(
                    'SELECT COUNT(*) as tbl_count, COALESCE(SUM(data_length + index_length), 0) as total_size FROM information_schema.tables WHERE table_schema = ?',
                    [$dbName]
                );
                if (!empty($tablesStats)) {
                    $tableCount = (int) ($tablesStats[0]->tbl_count ?? 0);
                    $totalDatabaseSizeBytes = (float) ($tablesStats[0]->total_size ?? 0);
                }
            }
        } catch (\Exception $e) {
            $dbConnected = false;
            Log::warning('SystemHealthService DB check failed: ' . $e->getMessage());
        }

        // Server Storage / Disk Space
        $diskPath = base_path();
        $totalSpaceBytes = @disk_total_space($diskPath) ?: 0;
        $freeSpaceBytes = @disk_free_space($diskPath) ?: 0;
        $usedSpaceBytes = max(0, $totalSpaceBytes - $freeSpaceBytes);
        $diskUsagePercent = $totalSpaceBytes > 0 ? round(($usedSpaceBytes / $totalSpaceBytes) * 100, 1) : 0;

        // Local SQL Snapshots Count & Size
        $backupDir = storage_path('app/backups');
        $localSnapshotsCount = 0;
        $localSnapshotsSizeBytes = 0;
        if (file_exists($backupDir)) {
            $files = glob($backupDir . '/*.sql');
            if ($files) {
                $localSnapshotsCount = count($files);
                foreach ($files as $file) {
                    $localSnapshotsSizeBytes += filesize($file);
                }
            }
        }

        // External Integrations Status
        $gdriveService = app(GoogleDriveService::class);
        $gdriveConfigured = $gdriveService->isConfigured();
        $gdriveQuota = $gdriveConfigured ? $gdriveService->getStorageQuota() : null;

        $m365Service = app(MicrosoftGraphService::class);
        $m365Configured = false;
        try {
            $m365Configured = filled(config('services.microsoft.tenant_id')) &&
                              filled(config('services.microsoft.client_id')) &&
                              filled(config('services.microsoft.client_secret'));
        } catch (\Exception $e) {}

        return [
            'dbConnected' => $dbConnected,
            'dbLatencyMs' => $dbLatencyMs,
            'tableCount' => $tableCount,
            'totalDatabaseSize' => $this->formatBytes($totalDatabaseSizeBytes),
            'totalSpace' => $this->formatBytes($totalSpaceBytes),
            'freeSpace' => $this->formatBytes($freeSpaceBytes),
            'usedSpace' => $this->formatBytes($usedSpaceBytes),
            'diskUsagePercent' => $diskUsagePercent,
            'localSnapshotsCount' => $localSnapshotsCount,
            'localSnapshotsSize' => $this->formatBytes($localSnapshotsSizeBytes),
            'gdriveConfigured' => $gdriveConfigured,
            'gdriveQuota' => $gdriveQuota,
            'm365Configured' => $m365Configured,
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
        ];
    }

    public function runDiagnosticPings(): array
    {
        $results = [];

        // 1. MariaDB Ping
        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000, 2);
            $results['mariadb'] = ['status' => 'online', 'latency' => $ms, 'message' => 'MariaDB database connection responsive.'];
        } catch (\Exception $e) {
            $results['mariadb'] = ['status' => 'offline', 'latency' => null, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }

        // 2. Google Drive API Ping
        $gdriveService = app(GoogleDriveService::class);
        if ($gdriveService->isConfigured()) {
            $start = microtime(true);
            try {
                $quota = $gdriveService->getStorageQuota();
                $ms = round((microtime(true) - $start) * 1000, 2);
                if ($quota !== null) {
                    $results['gdrive'] = ['status' => 'online', 'latency' => $ms, 'message' => 'Google Drive API authenticated & responsive.'];
                } else {
                    $results['gdrive'] = ['status' => 'warning', 'latency' => $ms, 'message' => 'Google Drive OAuth token expired or revoked (invalid_grant).'];
                }
            } catch (\Exception $e) {
                $results['gdrive'] = ['status' => 'offline', 'latency' => null, 'message' => 'Google Drive ping failed: ' . $e->getMessage()];
            }
        } else {
            $results['gdrive'] = ['status' => 'not_configured', 'latency' => null, 'message' => 'Google Drive API credentials not configured.'];
        }

        // 3. Microsoft Graph API Ping
        $authService = app(\App\Services\Microsoft\MicrosoftGraphAuthService::class);
        $start = microtime(true);
        try {
            $token = $authService->accessToken();
            $ms = round((microtime(true) - $start) * 1000, 2);
            if (!empty($token)) {
                $results['m365'] = ['status' => 'online', 'latency' => $ms, 'message' => 'Microsoft Graph API OAuth token acquired successfully.'];
            } else {
                $results['m365'] = ['status' => 'offline', 'latency' => null, 'message' => 'Microsoft Graph API returned empty token.'];
            }
        } catch (\Exception $e) {
            $results['m365'] = ['status' => 'offline', 'latency' => null, 'message' => 'Microsoft Graph API connection failed: ' . $e->getMessage()];
        }

        return $results;
    }

    public function sendTestEmail(string $recipientEmail): void
    {
        $appName = config('app.name', 'AMIS');
        $smtpHost = config('mail.mailers.smtp.host', 'smtp.gmail.com');
        $smtpPort = config('mail.mailers.smtp.port', '587');

        Mail::raw(
            "Hello!\n\nThis is a live diagnostic test message sent from the {$appName} Admin Portal.\n\n" .
            "SMTP Host: {$smtpHost}:{$smtpPort}\n" .
            "Recipient: {$recipientEmail}\n" .
            "Timestamp: " . now()->toDateTimeString() . "\n\n" .
            "If you received this message, your server email configuration is operating 100% correctly.",
            function ($message) use ($recipientEmail, $appName) {
                $message->to($recipientEmail)
                    ->subject("{$appName} - Live SMTP Diagnostic Test");
            }
        );
    }

    public function clearCaches(): void
    {
        Artisan::call('optimize:clear');
    }

    public function warmupCaches(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }

    public function formatBytes($bytes, $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
