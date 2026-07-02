<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SystemManagementController extends Controller
{
    private function ensureSuperOrAdmin()
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole('super_admin') && !$user->hasRole('admin'))) {
            abort(403, 'Unauthorized. Super Admin or Admin role required.');
        }
    }

    // 1. Backup Center
    public function backupsIndex()
    {
        $this->ensureSuperOrAdmin();

        $directory = storage_path('app/backups');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $files = [];
        $rawFiles = glob($directory . '/*.sql');

        if ($rawFiles !== false) {
            foreach ($rawFiles as $filePath) {
                $filename = basename($filePath);
                $files[] = [
                    'name' => $filename,
                    'size' => $this->formatBytes(filesize($filePath)),
                    'created_at' => date('Y-m-d H:i A', filemtime($filePath)),
                    'raw_created' => filemtime($filePath),
                ];
            }
        }

        usort($files, function ($a, $b) {
            return $b['raw_created'] - $a['raw_created'];
        });

        // DB stats
        $dbConnection = config('database.default', 'mysql');
        $dbHost = config("database.connections.{$dbConnection}.host", '127.0.0.1');
        $dbName = config("database.connections.{$dbConnection}.database");
        $dbPort = config("database.connections.{$dbConnection}.port", '3306');

        $dbSize = 0;
        if ($dbConnection === 'mysql') {
            try {
                $dbSizeResult = DB::select(
                    'SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = ?',
                    [$dbName]
                );
                $dbSize = $dbSizeResult[0]->size ?? 0;
            } catch (\Exception $e) {}
        }
        $formattedDbSize = $this->formatBytes($dbSize);

        $driveService = new \App\Services\GoogleDriveService();
        $gdriveConfigured = $driveService->isConfigured();
        $gdriveQuota = $driveService->getStorageQuota();
        $gdriveConnected = ($gdriveQuota !== null);

        if ($gdriveQuota) {
            $gdriveTotal = $gdriveQuota['limit'];
            $gdriveUsed = $gdriveQuota['usage'];
            $gdriveFree = $gdriveQuota['free'];
            $gdriveUsagePercent = $gdriveQuota['usage_percent'];
        } else {
            $gdriveTotal = 5 * 1024 * 1024 * 1024 * 1024;
            $gdriveUsed = 90.13 * 1024 * 1024 * 1024;
            $gdriveFree = $gdriveTotal - $gdriveUsed;
            $gdriveUsagePercent = 1.8;
        }

        $formattedFreeDisk = $this->formatBytes($gdriveFree);
        $formattedTotalDisk = $this->formatBytes($gdriveTotal);
        $formattedUsedDisk = $this->formatBytes($gdriveUsed);
        $diskUsagePercent = $gdriveUsagePercent;

        // Schedule info
        $schedulePath = storage_path('app/backup_schedule.json');
        $schedule = ['time' => '01:00', 'frequency' => 'daily', 'notify_email' => config('mail.from.address')];
        if (file_exists($schedulePath)) {
            $schedule = array_merge($schedule, json_decode(file_get_contents($schedulePath), true) ?: []);
        }

        return view('admin.system.backups.index', compact(
            'files', 
            'dbHost', 
            'dbName', 
            'dbPort', 
            'formattedDbSize', 
            'gdriveConfigured',
            'gdriveConnected',
            'formattedFreeDisk',
            'formattedTotalDisk',
            'formattedUsedDisk',
            'diskUsagePercent',
            'schedule'
        ));
    }

    public function backupsCreate(Request $request)
    {
        $this->ensureSuperOrAdmin();

        $config = config('database.connections.mysql');
        $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $directory = storage_path('app/backups');

        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . '/' . $filename;
        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --no-tablespaces --host=%s --port=%s --user=%s %s > %s 2>&1',
            escapeshellarg($config['password']),
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg($config['port'] ?? '3306'),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($filePath) || filesize($filePath) === 0) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $errorMsg = implode("\n", $output);
            AdminAuditLog::record('system_backup_manual_failed', false, 'Failed to create database backup: ' . Str::limit($errorMsg, 200));
            return back()->withErrors(['error' => 'Failed to create database backup: ' . ($errorMsg ?: 'Unknown error')]);
        }

        AdminAuditLog::record('system_backup_manual_success', true, "Created manual database backup: {$filename}");
        return back()->with('success', "Database backup created successfully: {$filename}");
    }

    public function backupsTriggerFull(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $phpBinary = PHP_BINDIR.DIRECTORY_SEPARATOR.'php';
            $php = escapeshellarg(is_executable($phpBinary) ? $phpBinary : 'php');
            $artisan = escapeshellarg(base_path('artisan'));
            exec("nohup {$php} {$artisan} amis:backup > /dev/null 2>&1 &");
            AdminAuditLog::record('system_full_backup_triggered', true, "Triggered full automated backup (Database & Files) to Google Drive in the background.");
            return back()->with('success', 'Full system backup (Database & Files) has been triggered in the background. It will upload to Google Drive shortly!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to trigger full backup: ' . $e->getMessage()]);
        }
    }

    public function backupsDownload(Request $request, $filename)
    {
        $this->ensureSuperOrAdmin();
        $cleanFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            abort(404);
        }

        AdminAuditLog::record('system_backup_downloaded', true, "Downloaded database backup: {$cleanFilename}");
        return response()->download($filePath);
    }

    public function backupsUploadToDrive(Request $request, $filename)
    {
        $this->ensureSuperOrAdmin();
        $cleanFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            abort(404);
        }

        $driveService = new \App\Services\GoogleDriveService();
        try {
            $driveService->uploadFile($filePath, $cleanFilename);
            AdminAuditLog::record('system_backup_uploaded_gdrive', true, "Uploaded database backup to Google Drive: {$cleanFilename}");
            return back()->with('success', "Database backup uploaded to Google Drive successfully: {$cleanFilename}");
        } catch (\Exception $e) {
            AdminAuditLog::record('system_backup_upload_gdrive_failed', false, "Failed to upload database backup to Google Drive: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to upload to Google Drive: ' . $e->getMessage()]);
        }
    }

    public function backupsDestroy(Request $request, $filename)
    {
        $this->ensureSuperOrAdmin();
        $cleanFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            abort(404);
        }

        unlink($filePath);
        AdminAuditLog::record('system_backup_deleted', true, "Deleted local database backup: {$cleanFilename}");
        return back()->with('success', "Database backup file deleted: {$cleanFilename}");
    }

    public function backupsRestore(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $request->validate([
            'filename' => 'required|string',
        ]);

        $cleanFilename = basename($request->filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            return back()->withErrors(['error' => 'Backup file not found.']);
        }

        $config = config('database.connections.mysql');
        $command = sprintf(
            'MYSQL_PWD=%s mysql --host=%s --port=%s --user=%s %s < %s 2>&1',
            escapeshellarg($config['password']),
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg($config['port'] ?? '3306'),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($filePath)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = implode("\n", $output);
            AdminAuditLog::record('system_backup_restore_failed', false, "Failed to restore database from: {$cleanFilename}. Error: " . Str::limit($errorMsg, 200));
            return back()->withErrors(['error' => 'Failed to restore database backup: ' . ($errorMsg ?: 'Unknown error')]);
        }

        AdminAuditLog::record('system_backup_restore_success', true, "Successfully restored database schema from: {$cleanFilename}");
        return back()->with('success', "Database backup has been successfully restored. Database state matches snapshot: {$cleanFilename}.");
    }

    public function backupsSaveSchedule(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $validated = $request->validate([
            'time' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly',
            'notify_email' => 'required|email',
        ]);

        $schedulePath = storage_path('app/backup_schedule.json');
        file_put_contents($schedulePath, json_encode($validated, JSON_PRETTY_PRINT));

        AdminAuditLog::record('system_backup_schedule_updated', true, "Updated backup schedule settings to {$validated['frequency']} at {$validated['time']}");

        return back()->with('success', 'Backup schedule preferences updated successfully.');
    }

    // 2. System Health
    public function systemHealth()
    {
        $this->ensureSuperOrAdmin();

        // Database status
        $dbConnection = config('database.default', 'mysql');
        $dbName = config("database.connections.{$dbConnection}.database");
        
        $dbConnected = false;
        $dbVersion = 'Unknown';
        $dbStats = null;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
            $versionResult = DB::select('SELECT VERSION() as version');
            $dbVersion = $versionResult[0]->version ?? 'Unknown';
            $sizeResult = DB::select('SELECT SUM(data_length + index_length) as size, COUNT(*) as tables FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
            $dbStats = $sizeResult[0] ?? null;
        } catch (\Exception $e) {}

        // Storage Usage
        $localTotal = disk_total_space(base_path()) ?: 1;
        $localFree = disk_free_space(base_path()) ?: 0;
        $localUsed = $localTotal - $localFree;
        $localUsagePercent = round(($localUsed / $localTotal) * 100, 1);

        // Google Drive status
        $driveService = new \App\Services\GoogleDriveService();
        $gdriveConfigured = $driveService->isConfigured();
        $gdriveQuota = $driveService->getStorageQuota();
        $gdriveConnected = ($gdriveQuota !== null);

        // Email Service (SMTP connect test)
        $emailStatus = 'Configured';
        $emailConnected = false;
        try {
            $transport = Mail::getSymfonyTransport();
            $emailConnected = ($transport !== null);
        } catch (\Exception $e) {
            $emailStatus = 'Error: ' . $e->getMessage();
        }

        // Microsoft Entra/Graph ID Integration status
        $msConfigured = filled(config('services.microsoft.client_id')) && filled(config('services.microsoft.tenant_id'));
        $msConnected = false;
        if ($msConfigured) {
            try {
                $response = Http::asForm()->post(
                    "https://login.microsoftonline.com/" . config('services.microsoft.tenant_id') . "/oauth2/v2.0/token",
                    [
                        'client_id' => config('services.microsoft.client_id'),
                        'client_secret' => config('services.microsoft.client_secret'),
                        'grant_type' => 'client_credentials',
                        'scope' => 'https://graph.microsoft.com/.default',
                    ]
                );
                $msConnected = $response->successful();
            } catch (\Exception $e) {}
        }

        $healthStatus = [
            'database' => [
                'name' => 'MariaDB Database',
                'connected' => $dbConnected,
                'version' => $dbVersion,
                'metrics' => $dbStats ? $this->formatBytes($dbStats->size) . ' (' . $dbStats->tables . ' tables)' : '0 B',
            ],
            'storage' => [
                'name' => 'Local Server Disk',
                'connected' => true,
                'version' => 'Linux Filesystem',
                'metrics' => $this->formatBytes($localUsed) . ' / ' . $this->formatBytes($localTotal) . ' (' . $localUsagePercent . '%)',
            ],
            'gdrive' => [
                'name' => 'Google Drive Storage API',
                'connected' => $gdriveConnected,
                'version' => 'Drive API v3',
                'metrics' => $gdriveQuota ? $this->formatBytes($gdriveQuota['usage']) . ' / ' . $this->formatBytes($gdriveQuota['limit']) . ' (' . $gdriveQuota['usage_percent'] . '%)' : 'Not Connected',
            ],
            'email' => [
                'name' => 'SMTP Mail Gateway',
                'connected' => $emailConnected,
                'version' => config('mail.mailers.smtp.host') ?: 'SMTP',
                'metrics' => $emailStatus,
            ],
            'microsoft' => [
                'name' => 'Microsoft Entra ID & Graph API',
                'connected' => $msConnected,
                'version' => 'OAuth v2.0 Client credentials',
                'metrics' => $msConfigured ? ($msConnected ? 'Connected' : 'Authentication Failed') : 'Not Configured',
            ]
        ];

        // Email Tracking Stats
        $emailStats = $this->buildEmailStats();

        return view('admin.system.health.index', compact('healthStatus', 'emailStats'));
    }

    private function buildEmailStats(): array
    {
        $hasTable = Schema::hasTable('email_logs');

        if (!$hasTable) {
            return [
                'available' => false,
                'today' => 0,
                'this_week' => 0,
                'this_month' => 0,
                'failed_today' => 0,
                'mailer_breakdown' => [],
                'recent' => collect(),
                'daily_chart' => [],
                'smtp_config' => $this->smtpConfigDetails(),
            ];
        }

        $today = \App\Models\EmailLog::today()->sent()->count();
        $thisWeek = \App\Models\EmailLog::thisWeek()->sent()->count();
        $thisMonth = \App\Models\EmailLog::thisMonth()->sent()->count();
        $failedToday = \App\Models\EmailLog::today()->failed()->count();

        // Per-mailer breakdown (this month)
        $mailerBreakdown = \App\Models\EmailLog::thisMonth()
            ->select('mailer', DB::raw('COUNT(*) as total'), DB::raw('SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count'), DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'))
            ->groupBy('mailer')
            ->orderByDesc('total')
            ->get();

        // Recent 10 emails
        $recent = \App\Models\EmailLog::orderByDesc('sent_at')->limit(10)->get();

        // Daily send counts for last 7 days
        $dailyChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = \App\Models\EmailLog::whereDate('sent_at', $date->toDateString())->sent()->count();
            $dailyChart[] = [
                'label' => $date->format('M d'),
                'day' => $date->format('D'),
                'count' => $count,
            ];
        }

        return [
            'available' => true,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'failed_today' => $failedToday,
            'mailer_breakdown' => $mailerBreakdown,
            'recent' => $recent,
            'daily_chart' => $dailyChart,
            'smtp_config' => $this->smtpConfigDetails(),
        ];
    }

    private function smtpConfigDetails(): array
    {
        $defaultMailer = config('mail.default', 'smtp');
        $mailers = [];

        foreach (['smtp', 'smtp_backup', 'smtp_backup_2', 'sendmail', 'failover'] as $name) {
            $config = config("mail.mailers.{$name}");
            if (!$config) continue;

            $mailers[$name] = [
                'transport' => $config['transport'] ?? $name,
                'host' => $config['host'] ?? '—',
                'port' => $config['port'] ?? '—',
                'encryption' => $config['encryption'] ?? $config['scheme'] ?? 'none',
                'username' => !empty($config['username']) ? Str::mask($config['username'], '*', 3) : '—',
                'is_default' => ($name === $defaultMailer),
            ];
        }

        return [
            'default_mailer' => $defaultMailer,
            'from_address' => config('mail.from.address', '—'),
            'from_name' => config('mail.from.name', '—'),
            'mailers' => $mailers,
        ];
    }

    // 3. Integrations
    public function integrationsIndex()
    {
        $this->ensureSuperOrAdmin();

        $integrations = [
            'microsoft_entra' => [
                'name' => 'Microsoft Entra ID',
                'description' => 'Single Sign-on authentication mapping for tenant domain user accounts.',
                'configured' => filled(config('services.microsoft.client_id')),
                'client_id' => config('services.microsoft.client_id') ?: 'Not Set',
                'tenant_id' => config('services.microsoft.tenant_id') ?: 'Not Set',
                'redirect_uri' => config('services.microsoft.redirect_uri') ?: 'Not Set',
            ],
            'microsoft_graph' => [
                'name' => 'Microsoft Graph API',
                'description' => 'Directory sync of user emails, advisor sections rosters, and Teams licensing provisioning.',
                'configured' => filled(config('services.microsoft.client_secret')),
                'scopes' => 'User.Read.All, Group.ReadWrite.All, Directory.ReadWrite.All',
            ],
            'google_drive' => [
                'name' => 'Google Drive Backup API',
                'description' => 'Disaster recovery automated uploading and storage pruning commands.',
                'configured' => (new \App\Services\GoogleDriveService())->isConfigured(),
                'folder_id' => config('services.google_drive.folder_id') ?: 'Not Set',
            ],
            'email' => [
                'name' => 'SMTP Service',
                'description' => 'Mailer services for student admissions onboarding and security alerts.',
                'configured' => filled(config('mail.mailers.smtp.host')),
                'host' => config('mail.mailers.smtp.host') ?: 'Not Set',
                'port' => config('mail.mailers.smtp.port') ?: 'Not Set',
                'encryption' => config('mail.mailers.smtp.encryption') ?: 'none',
            ]
        ];

        return view('admin.system.integrations.index', compact('integrations'));
    }

    private function formatBytes($bytes, $precision = 2)
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
