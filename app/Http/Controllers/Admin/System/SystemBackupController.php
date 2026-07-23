<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\GoogleDriveService;
use App\Services\System\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemBackupController extends Controller
{
    protected SystemHealthService $healthService;

    public function __construct(SystemHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    private function ensureSuperOrAdmin(): void
    {
        $role = auth()->user()?->role;
        if (!in_array($role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized. Backup operations are restricted to Administrators.');
        }
    }

    public function index()
    {
        $this->ensureSuperOrAdmin();

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backups = [];
        $files = glob($backupDir . '/*.sql');

        if ($files) {
            usort($files, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $mtime = filemtime($file);

                $backups[] = [
                    'filename' => $filename,
                    'size' => $this->healthService->formatBytes($size),
                    'size_bytes' => $size,
                    'created_at' => date('M d, Y h:i A', $mtime),
                    'timestamp' => $mtime,
                ];
            }
        }

        $gdriveService = app(GoogleDriveService::class);
        $gdriveConfigured = $gdriveService->isConfigured();
        $gdriveQuota = $gdriveConfigured ? $gdriveService->getStorageQuota() : null;

        $dbName = config('database.connections.mysql.database');
        $dbSize = '0 B';
        if (config('database.default') === 'mysql' && $dbName) {
            try {
                $tablesStats = DB::select(
                    'SELECT COALESCE(SUM(data_length + index_length), 0) as total_size FROM information_schema.tables WHERE table_schema = ?',
                    [$dbName]
                );
                if (!empty($tablesStats)) {
                    $dbSize = $this->healthService->formatBytes((float)$tablesStats[0]->total_size);
                }
            } catch (\Exception $e) {}
        }

        return view('admin.system.backups.index', compact('backups', 'gdriveConfigured', 'gdriveQuota', 'dbSize'));
    }

    public function create(Request $request)
    {
        $this->ensureSuperOrAdmin();

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'amis_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $outputPath = $backupDir . '/' . $filename;

        $config = config('database.connections.mysql');
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --no-tablespaces --host=%s --port=%s --user=%s %s > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            $errorMsg = implode(' ', $output);
            AdminAuditLog::record('database_backup_created_failed', false, 'Database backup creation failed: ' . $errorMsg);
            return back()->withErrors(['error' => 'Database backup failed: ' . ($errorMsg ?: 'Unknown error executing mysqldump.')]);
        }

        $formattedSize = $this->healthService->formatBytes(filesize($outputPath));
        AdminAuditLog::record('database_backup_created', true, "Created SQL database snapshot: {$filename} ({$formattedSize})");

        return back()->with('success', "Database snapshot created successfully! File: {$filename} ({$formattedSize})");
    }

    public function triggerFull(Request $request)
    {
        $this->ensureSuperOrAdmin();

        try {
            Artisan::call('amis:backup');
            AdminAuditLog::record('database_backup_triggered_full', true, "Triggered automated backup via Artisan.");
            return back()->with('success', 'Full system backup triggered successfully!');
        } catch (\Exception $e) {
            AdminAuditLog::record('database_backup_triggered_failed', false, "Trigger full backup failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'Full backup failed: ' . $e->getMessage()]);
        }
    }

    public function download(string $filename)
    {
        $this->ensureSuperOrAdmin();

        $path = storage_path('app/backups/' . basename($filename));
        if (!file_exists($path)) {
            abort(404, 'Backup file not found.');
        }

        AdminAuditLog::record('database_backup_downloaded', true, "Downloaded backup snapshot: {$filename}");
        return response()->download($path);
    }

    public function uploadToDrive(string $filename)
    {
        $this->ensureSuperOrAdmin();

        $path = storage_path('app/backups/' . basename($filename));
        if (!file_exists($path)) {
            return back()->withErrors(['error' => 'Backup file not found locally.']);
        }

        $gdriveService = app(GoogleDriveService::class);
        if (!$gdriveService->isConfigured()) {
            return back()->withErrors(['error' => 'Google Drive is not connected. Please authorize Google Drive first.']);
        }

        try {
            $gdriveService->uploadFile($path, $filename);
            AdminAuditLog::record('database_backup_uploaded_gdrive', true, "Uploaded backup snapshot to Google Drive: {$filename}");
            return back()->with('success', "Backup file {$filename} successfully uploaded to Google Drive!");
        } catch (\Exception $e) {
            AdminAuditLog::record('database_backup_upload_gdrive_failed', false, "Failed uploading backup {$filename} to Google Drive: " . $e->getMessage());
            return back()->withErrors(['error' => 'Google Drive Upload Failed: ' . $e->getMessage()]);
        }
    }

    public function destroy(string $filename)
    {
        $this->ensureSuperOrAdmin();

        $path = storage_path('app/backups/' . basename($filename));
        if (file_exists($path)) {
            unlink($path);
            AdminAuditLog::record('database_backup_deleted', true, "Deleted backup snapshot: {$filename}");
            return back()->with('success', "Backup file {$filename} deleted successfully.");
        }

        return back()->withErrors(['error' => 'Backup file not found.']);
    }

    public function restore(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $request->validate([
            'filename' => 'required|string',
            'confirmation' => 'required|string|in:RESTORE',
        ]);

        $filename = basename($request->filename);
        $path = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            return back()->withErrors(['error' => 'Selected backup snapshot file does not exist.']);
        }

        $config = config('database.connections.mysql');
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        $command = sprintf(
            'MYSQL_PWD=%s mysql --host=%s --port=%s --user=%s %s < %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($path)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = implode(' ', $output);
            AdminAuditLog::record('database_restore_failed', false, 'Database restore failed: ' . $errorMsg);
            return back()->withErrors(['error' => 'Database restore failed: ' . ($errorMsg ?: 'Unknown error executing mysql restore.')]);
        }

        Artisan::call('cache:clear');
        AdminAuditLog::record('database_restored', true, "Restored database snapshot: {$filename}");

        return back()->with('success', "Database successfully restored from snapshot {$filename}!");
    }

    public function saveSchedule(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $request->validate([
            'backup_frequency' => 'required|in:daily,weekly,monthly,disabled',
            'backup_time' => 'required|string',
            'retention_days' => 'required|integer|min:1|max:365',
        ]);

        AdminAuditLog::record('database_backup_schedule_updated', true, "Updated backup schedule frequency to {$request->backup_frequency}");
        return back()->with('success', 'Backup schedule preferences updated successfully.');
    }

    public function pruneOldBackups(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $request->validate([
            'days' => 'required|integer|in:14,30,60',
        ]);

        $days = (int) $request->days;
        $cutoffTime = time() - ($days * 86400);

        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/*.sql');

        $deletedCount = 0;
        $freedBytes = 0;

        if ($files) {
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    $freedBytes += filesize($file);
                    unlink($file);
                    $deletedCount++;
                }
            }
        }

        $formattedFreed = $this->healthService->formatBytes($freedBytes);
        AdminAuditLog::record('database_backups_pruned', true, "Pruned {$deletedCount} backups older than {$days} days. Freed {$formattedFreed}.");

        return back()->with('success', "Pruned {$deletedCount} old backup snapshots older than {$days} days. Freed {$formattedFreed} disk space!");
    }
}
