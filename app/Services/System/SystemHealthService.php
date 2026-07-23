<?php

namespace App\Services\System;

use App\Services\GoogleDriveService;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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
            $files = glob($backupDir . '/*.*');
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

        $m365Configured = false;
        try {
            $m365Configured = filled(config('services.microsoft.tenant_id')) &&
                              filled(config('services.microsoft.client_id')) &&
                              filled(config('services.microsoft.client_secret'));
        } catch (\Exception $e) {}

        // Construct healthStatus array required by Blade view
        $healthStatus = [
            'mariadb' => [
                'name' => 'MariaDB Database',
                'connected' => $dbConnected,
                'version' => 'MariaDB 10.x / MySQL',
                'metrics' => $dbConnected ? "{$dbLatencyMs} ms latency ({$tableCount} tables, " . $this->formatBytes($totalDatabaseSizeBytes) . ")" : 'Disconnected',
            ],
            'server_disk' => [
                'name' => 'Server Storage',
                'connected' => $diskUsagePercent < 90,
                'version' => 'Local File System',
                'metrics' => "{$this->formatBytes($usedSpaceBytes)} / {$this->formatBytes($totalSpaceBytes)} ({$diskUsagePercent}% used)",
            ],
            'gdrive' => [
                'name' => 'Google Drive API',
                'connected' => $gdriveConfigured,
                'version' => 'Google Drive OAuth v2',
                'metrics' => $gdriveConfigured ? 'Connected & Configured' : 'Not Connected / Token Expired',
            ],
            'm365' => [
                'name' => 'Microsoft Graph API',
                'connected' => $m365Configured,
                'version' => 'Azure AD Tenant',
                'metrics' => $m365Configured ? 'Tenant Credentials Valid' : 'Missing Tenant Secrets',
            ],
            'php_engine' => [
                'name' => 'PHP Runtime Engine',
                'connected' => true,
                'version' => 'PHP ' . PHP_VERSION,
                'metrics' => 'Laravel ' . app()->version(),
            ],
        ];

        // Email Tracking Stats
        $hasEmailLogs = Schema::hasTable('email_logs');
        $emailStats = [
            'available' => $hasEmailLogs,
            'today' => $hasEmailLogs ? DB::table('email_logs')->whereDate('created_at', now()->today())->count() : 0,
            'failed_today' => $hasEmailLogs ? DB::table('email_logs')->whereDate('created_at', now()->today())->where('status', 'failed')->count() : 0,
            'this_week' => $hasEmailLogs ? DB::table('email_logs')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count() : 0,
            'this_month' => $hasEmailLogs ? DB::table('email_logs')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count() : 0,
            'daily_chart' => [],
            'mailer_breakdown' => $hasEmailLogs ? DB::table('email_logs')->select('mailer', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status="sent" THEN 1 ELSE 0 END) as sent_count'), DB::raw('SUM(CASE WHEN status="failed" THEN 1 ELSE 0 END) as failed_count'))->groupBy('mailer')->get() : collect([]),
            'recent' => $hasEmailLogs ? DB::table('email_logs')->latest()->take(10)->get() : collect([]),
            'smtp_config' => [
                'default_mailer' => config('mail.default', 'smtp'),
                'from_address' => config('mail.from.address', 'support@amis.edu.ph'),
                'from_name' => config('mail.from.name', 'AMIS Portal'),
                'mailers' => [
                    'smtp' => [
                        'is_default' => config('mail.default') === 'smtp',
                        'host' => config('mail.mailers.smtp.host', 'smtp.gmail.com'),
                        'port' => config('mail.mailers.smtp.port', 587),
                        'encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                        'transport' => 'smtp',
                    ],
                ],
            ],
        ];

        if ($hasEmailLogs) {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $cnt = DB::table('email_logs')->whereDate('created_at', $date->toDateString())->count();
                $emailStats['daily_chart'][] = [
                    'day' => $date->format('D'),
                    'label' => $date->format('M d'),
                    'count' => $cnt,
                ];
            }
        }

        return [
            'healthStatus' => $healthStatus,
            'emailStats' => $emailStats,
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
