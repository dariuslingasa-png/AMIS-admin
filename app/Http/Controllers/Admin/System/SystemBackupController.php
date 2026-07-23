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
use ZipArchive;

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

    private function ensureSuperAdmin(): void
    {
        $role = auth()->user()?->role;
        if ($role !== 'super_admin') {
            abort(403, 'Unauthorized. Destructive operations require Super Admin privileges.');
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
        $files = glob($backupDir . '/*.*');

        $lastSuccessful = null;
        $lastFailed = null;

        // Fetch last audit log statuses
        $lastSuccessLog = AdminAuditLog::where('event', 'database_backup_automated_success')->latest()->first();
        $lastFailedLog = AdminAuditLog::where('event', 'database_backup_automated_failed')->latest()->first();

        if ($lastSuccessLog) {
            $lastSuccessful = [
                'created_at' => $lastSuccessLog->created_at->format('M d, Y h:i A'),
                'message' => $lastSuccessLog->message,
            ];
        }

        if ($lastFailedLog) {
            $lastFailed = [
                'created_at' => $lastFailedLog->created_at->format('M d, Y h:i A'),
                'message' => $lastFailedLog->message,
            ];
        }

        if ($files) {
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

            foreach ($files as $file) {
                $filename = basename($file);
                if (str_starts_with($filename, 'pre_restore_safety_') || str_starts_with($filename, 'temp_')) {
                    continue; // Skip internal safety snapshots
                }

                $size = filesize($file);
                $mtime = filemtime($file);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);

                $backups[] = [
                    'filename' => $filename,
                    'extension' => strtoupper($ext),
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

        return view('admin.system.backups.index', compact(
            'backups',
            'gdriveConfigured',
            'gdriveQuota',
            'dbSize',
            'lastSuccessful',
            'lastFailed'
        ));
    }

    public function create(Request $request)
    {
        $this->ensureSuperOrAdmin();

        try {
            Artisan::call('amis:backup');
            AdminAuditLog::record('database_backup_created_manual', true, "Manually triggered full system backup snapshot.");
            return back()->with('success', 'Full system backup snapshot created successfully!');
        } catch (\Exception $e) {
            AdminAuditLog::record('database_backup_created_failed', false, 'Manual database backup failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Backup failed: ' . $e->getMessage()]);
        }
    }

    public function triggerFull(Request $request)
    {
        $this->ensureSuperOrAdmin();

        try {
            \App\Jobs\ProcessAmisBackupJob::dispatch();
            AdminAuditLog::record('database_backup_triggered_full', true, "Dispatched background backup job via Queue.");
            return back()->with('success', 'Full system backup job queued in the background successfully! You will receive an email upon completion.');
        } catch (\Exception $e) {
            AdminAuditLog::record('database_backup_triggered_failed', false, "Trigger full backup failed: " . $e->getMessage());
            return back()->withErrors(['error' => 'Full backup dispatch failed: ' . $e->getMessage()]);
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

    public function destroy(string $filename)
    {
        $this->ensureSuperAdmin();

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
        $this->ensureSuperAdmin();
        $request->validate([
            'filename' => 'required|string',
            'confirmation' => 'required|string|in:RESTORE',
        ]);

        $filename = basename($request->filename);
        $path = storage_path('app/backups/' . $filename);

        if (!file_exists($path)) {
            return back()->withErrors(['error' => 'Selected backup snapshot file does not exist.']);
        }

        // 1. Validate Backup File Integrity Before Restoring
        $sqlPathToRestore = $path;
        $tempExtractDir = null;

        if (str_ends_with(strtolower($filename), '.zip')) {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                return back()->withErrors(['error' => 'Restore failed: Backup ZIP archive is corrupt or unreadable.']);
            }

            $tempExtractDir = storage_path('app/backups/temp_restore_' . time());
            mkdir($tempExtractDir, 0755, true);
            $zip->extractTo($tempExtractDir);
            $zip->close();

            $extractedSql = $tempExtractDir . '/database.sql';
            if (!file_exists($extractedSql) || filesize($extractedSql) === 0) {
                $this->cleanLocalDirectory($tempExtractDir);
                return back()->withErrors(['error' => 'Restore failed: database.sql was not found inside ZIP archive.']);
            }

            $sqlPathToRestore = $extractedSql;
        }

        // 2. Create Safety Snapshot BEFORE Restoration
        $safetyFile = storage_path('app/backups/pre_restore_safety_' . time() . '.sql');
        $this->createDatabaseSnapshot($safetyFile);

        // 3. Apply Restore
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
            escapeshellarg($sqlPathToRestore)
        );

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        // Clean up temp extraction if created
        if ($tempExtractDir && file_exists($tempExtractDir)) {
            $this->cleanLocalDirectory($tempExtractDir);
        }

        if ($returnVar !== 0) {
            $errorMsg = implode(' ', $output);
            Log::error("Database restore failed! Rolling back using safety snapshot: {$safetyFile}");
            
            // Automatic Rollback
            $rollbackCmd = sprintf(
                'MYSQL_PWD=%s mysql --host=%s --port=%s --user=%s %s < %s 2>&1',
                escapeshellarg($password),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($database),
                escapeshellarg($safetyFile)
            );
            exec($rollbackCmd);

            AdminAuditLog::record('database_restore_failed_rolled_back', false, 'Database restore failed and auto-rolled back: ' . $errorMsg);
            return back()->withErrors(['error' => 'Database restore failed! Automatically rolled back to pre-restore state: ' . ($errorMsg ?: 'SQL execution error.')]);
        }

        Artisan::call('cache:clear');
        AdminAuditLog::record('database_restored', true, "Restored database from snapshot: {$filename}");

        return back()->with('success', "Database successfully restored from backup {$filename}! Pre-restore safety snapshot saved.");
    }

    public function pruneOldBackups(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'days' => 'required|integer|in:14,30,60',
        ]);

        $days = (int) $request->days;
        $cutoffTime = time() - ($days * 86400);

        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/*.*');

        $deletedCount = 0;
        $freedBytes = 0;

        if ($files && count($files) > 1) {
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
            array_shift($files); // Always preserve latest backup

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

    private function createDatabaseSnapshot(string $outputPath): void
    {
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

        exec($command);
    }

    private function cleanLocalDirectory(string $tempDir): void
    {
        if (!file_exists($tempDir)) {
            return;
        }

        $files = array_diff(scandir($tempDir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $tempDir . '/' . $file;
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        rmdir($tempDir);
    }
}
