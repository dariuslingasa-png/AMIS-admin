<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AdminBackupController extends Controller
{
    // Routes are protected via web/admin middleware group

    public function index()
    {
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

        // Sort by created time descending
        usort($files, function ($a, $b) {
            return $b['raw_created'] - $a['raw_created'];
        });

        // Get DB details
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
            } catch (\Exception $e) {
                // Fail-safe
            }
        }
        $formattedDbSize = $this->formatBytes($dbSize);

        // Get disk space info
        $totalDiskSpace = @disk_total_space(base_path()) ?: 0;
        $freeDiskSpace = @disk_free_space(base_path()) ?: 0;
        $usedDiskSpace = $totalDiskSpace - $freeDiskSpace;

        $formattedFreeDisk = $this->formatBytes($freeDiskSpace);
        $formattedTotalDisk = $this->formatBytes($totalDiskSpace);
        $formattedUsedDisk = $this->formatBytes($usedDiskSpace);
        $diskUsagePercent = $totalDiskSpace > 0 ? round(($usedDiskSpace / $totalDiskSpace) * 100, 1) : 0;

        $driveService = new \App\Services\GoogleDriveService();
        $gdriveConfigured = $driveService->isConfigured();

        return view('admin.admins.backups', compact(
            'files', 
            'dbHost', 
            'dbName', 
            'dbPort', 
            'formattedDbSize', 
            'gdriveConfigured',
            'formattedFreeDisk',
            'formattedTotalDisk',
            'formattedUsedDisk',
            'diskUsagePercent'
        ));
    }

    public function runFullBackup(Request $request)
    {
        try {
            $phpBinary = PHP_BINDIR.DIRECTORY_SEPARATOR.'php';
            $php = escapeshellarg(is_executable($phpBinary) ? $phpBinary : 'php');
            $artisan = escapeshellarg(base_path('artisan'));
            
            // Execute the automated backup command in the background
            exec("nohup {$php} {$artisan} amis:backup > /dev/null 2>&1 &");
            
            $this->audit($request, 'database_full_backup_triggered', auth()->user(), true, "Triggered full automated backup (Database & Files) to Google Drive in the background.");
            
            return back()->with('success', 'Full system backup (Database & Files) has been triggered in the background. It will appear in your Google Drive shortly!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to trigger full backup: ' . $e->getMessage()]);
        }
    }

    public function create(Request $request)
    {
        $config = config('database.connections.mysql');
        
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        
        $filename = 'database_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $directory = storage_path('app/backups');
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filePath = $directory . '/' . $filename;
        
        // Pass password securely through MYSQL_PWD
        $command = sprintf(
            'MYSQL_PWD=%s mysqldump --no-tablespaces --host=%s --port=%s --user=%s %s > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
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
            
            $this->audit($request, 'database_backup_failed', auth()->user(), false, 'Failed to create database backup: ' . Str::limit($errorMsg, 200));
            return back()->withErrors(['error' => 'Failed to create database backup: ' . ($errorMsg ?: 'Unknown error')]);
        }

        $this->audit($request, 'database_backup_created', auth()->user(), true, "Created database backup: {$filename}");
        return back()->with('success', "Database backup created successfully: {$filename}");
    }

    public function download(Request $request, $filename)
    {
        $cleanFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            abort(404);
        }

        $this->audit($request, 'database_backup_downloaded', auth()->user(), true, "Downloaded database backup: {$cleanFilename}");

        return response()->download($filePath);
    }

    public function uploadToDrive(Request $request, $filename)
    {
        $cleanFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            abort(404);
        }

        $driveService = new \App\Services\GoogleDriveService();
        
        try {
            $driveService->uploadFile($filePath, $cleanFilename);
            $this->audit($request, 'database_backup_uploaded_gdrive', auth()->user(), true, "Uploaded database backup to Google Drive: {$cleanFilename}");
            return back()->with('success', "Database backup uploaded to Google Drive successfully: {$cleanFilename}");
        } catch (\Exception $e) {
            $this->audit($request, 'database_backup_upload_gdrive_failed', auth()->user(), false, "Failed to upload database backup to Google Drive: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to upload to Google Drive: ' . $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $filename)
    {
        $cleanFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $cleanFilename);

        if (!file_exists($filePath) || !Str::endsWith($cleanFilename, '.sql')) {
            abort(404);
        }

        unlink($filePath);

        $this->audit($request, 'database_backup_deleted', auth()->user(), true, "Deleted database backup: {$cleanFilename}");

        return back()->with('success', "Database backup file deleted: {$cleanFilename}");
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

    private function audit(Request $request, string $event, ?User $user, bool $successful, ?string $message = null): void
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return;
        }

        AdminAuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'email' => $user?->email,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'successful' => $successful,
            'message' => $message,
            'metadata' => null,
        ]);
    }
}
