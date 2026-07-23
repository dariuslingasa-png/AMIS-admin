<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\AdminAuditLog;
use App\Mail\BackupSuccessMail;
use App\Mail\BackupFailedMail;
use ZipArchive;

class AmisBackupCommand extends Command
{
    protected $signature = 'amis:backup';
    protected $description = 'Create a complete timestamped ZIP backup of the MySQL database and application assets with integrity verification and email notifications.';

    public function handle()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $startTime = microtime(true);
        $dateStr = date('Y-m-d_H-i-s');
        $this->info("Starting full AMIS application backup process for {$dateStr}...");

        $tempDir = storage_path('app/backups/temp_' . time());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $dbFile = $tempDir . '/database.sql';
        $fullBackupZipFile = $tempDir . "/amis_backup_{$dateStr}.zip";

        try {
            // 1. Dump Database (MySQL / MariaDB)
            $this->info('1. Dumping MySQL database...');
            $this->dumpDatabase($dbFile);

            // 2. Verify Database Dump Integrity
            $this->verifyDatabaseDump($dbFile);

            // 3. Create Compressed Full ZIP Archive
            $this->info('2. Compressing application assets, uploads, and database into ZIP archive...');
            $includedItems = $this->createFullBackupZip($fullBackupZipFile, $dbFile);

            // 4. Verify Archive Integrity
            $this->info('3. Verifying archive integrity and completeness...');
            $this->verifyArchiveIntegrity($fullBackupZipFile);

            // 5. Store in Local Non-Public Backup Storage
            $localBackupDir = storage_path('app/backups');
            if (!file_exists($localBackupDir)) {
                mkdir($localBackupDir, 0755, true);
            }
            $finalBackupPath = $localBackupDir . "/amis_backup_{$dateStr}.zip";
            rename($fullBackupZipFile, $finalBackupPath);

            // Save raw SQL dump copy as well for instant SQL restoration
            $permanentSqlFile = $localBackupDir . "/amis_backup_{$dateStr}.sql";
            if (file_exists($dbFile)) {
                copy($dbFile, $permanentSqlFile);
            }

            // 6. Clean up temp files
            $this->info('4. Cleaning temporary working files...');
            $this->cleanLocalDirectory($tempDir);

            $executionTime = round(microtime(true) - $startTime, 2);
            $totalSize = filesize($finalBackupPath);
            $formattedSize = $this->formatBytes($totalSize);
            $timestamp = date('M d, Y h:i A');

            // 7. Auto-Prune Old Backups (Retain 30 Days)
            $this->pruneOldBackups(30);

            // 8. Log Success Audit
            $this->audit(
                'database_backup_automated_success',
                true,
                "Automated backup completed successfully. File: " . basename($finalBackupPath) . " ({$formattedSize}). Time: {$executionTime}s."
            );

            // 9. Dispatch Success Email to target recipient
            $this->sendSuccessEmail(basename($finalBackupPath), $formattedSize, $executionTime, $timestamp, $includedItems);

            $this->info("=== Backup Process Completed Successfully! Size: {$formattedSize} ({$executionTime}s) ===");
            return 0;

        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error('Automated backup failed: ' . $e->getMessage());

            if (file_exists($tempDir)) {
                $this->cleanLocalDirectory($tempDir);
            }

            $this->audit(
                'database_backup_automated_failed',
                false,
                'Automated backup failed: ' . $e->getMessage() . " (Time: {$executionTime}s)"
            );

            $this->sendFailureEmail($e->getMessage(), $executionTime);

            $this->error('Backup process failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function dumpDatabase(string $outputPath): void
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

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($outputPath) || filesize($outputPath) === 0) {
            $errorMsg = implode("\n", $output);
            throw new \Exception('mysqldump failed: ' . ($errorMsg ?: 'Empty SQL dump file generated.'));
        }
    }

    private function verifyDatabaseDump(string $sqlFilePath): void
    {
        if (!file_exists($sqlFilePath) || filesize($sqlFilePath) < 100) {
            throw new \Exception('Database dump verification failed: File is missing or too small.');
        }

        $sample = file_get_contents($sqlFilePath, false, null, 0, 2048);
        if (!str_contains($sample, 'MySQL dump') && !str_contains($sample, 'CREATE TABLE') && !str_contains($sample, 'INSERT INTO')) {
            throw new \Exception('Database dump verification failed: Invalid SQL header.');
        }
    }

    private function createFullBackupZip(string $outputPath, string $dbFile): array
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP archive.');
        }

        $includedItems = [];

        // 1. Add SQL Dump
        if (file_exists($dbFile)) {
            $zip->addFile($dbFile, 'database.sql');
            $includedItems[] = 'MySQL Database Dump (database.sql)';
        }

        // 2. Add Core Code & Configurations
        $codeDirectories = ['app', 'bootstrap', 'config', 'database', 'resources', 'routes'];
        foreach ($codeDirectories as $dir) {
            $fullPath = base_path($dir);
            if (file_exists($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, "code/{$dir}");
                $includedItems[] = "Source Code ({$dir}/)";
            }
        }

        // 3. Add Storage & Uploaded Assets
        $storagePaths = [
            'storage/app/public' => 'uploads/storage_public',
            'public/uploads' => 'uploads/public_uploads',
            'public/images' => 'uploads/public_images',
            'public/documents' => 'uploads/public_documents',
            'public/signatures' => 'uploads/public_signatures',
            'public/qr' => 'uploads/public_qr',
        ];

        foreach ($storagePaths as $localRel => $zipSubDir) {
            $fullPath = base_path($localRel);
            if (file_exists($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $zipSubDir);
                $includedItems[] = "Media/Uploads Asset ({$localRel}/)";
            }
        }

        $zip->close();

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new \Exception('ZIP archive file is empty or could not be generated.');
        }

        return array_unique($includedItems);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $folderPath, string $zipSubFolder): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $excludedNames = ['vendor', 'node_modules', 'bootstrap/cache', '.git', 'laravel.log'];

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                
                // Exclude vendor, node_modules, .git
                $skip = false;
                foreach ($excludedNames as $ex) {
                    if (str_contains($filePath, '/' . $ex . '/')) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) {
                    continue;
                }

                $relativePath = $zipSubFolder . '/' . substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function verifyArchiveIntegrity(string $zipPath): void
    {
        $zip = new ZipArchive();
        $res = $zip->open($zipPath, ZipArchive::CHECKCONS);
        if ($res !== true) {
            throw new \Exception("ZIP archive integrity check failed with error code {$res}.");
        }

        $hasSql = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat['name'] === 'database.sql' && $stat['size'] > 0) {
                $hasSql = true;
                break;
            }
        }
        $zip->close();

        if (!$hasSql) {
            throw new \Exception('ZIP archive integrity check failed: database.sql is missing inside archive.');
        }
    }

    private function pruneOldBackups(int $retentionDays = 30): void
    {
        $backupDir = storage_path('app/backups');
        $cutoffTime = time() - ($retentionDays * 86400);

        $files = glob($backupDir . '/*.*');
        if (!$files || count($files) <= 1) {
            return; // Never delete if 1 or 0 backups remaining
        }

        // Sort files by mtime descending (newest first)
        usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

        // Always keep the latest backup
        $latestBackup = array_shift($files);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                @unlink($file);
                Log::info("Auto-pruned expired backup file: " . basename($file));
            }
        }
    }

    private function sendSuccessEmail(string $filename, string $formattedSize, float $executionTime, string $timestamp, array $includedItems): void
    {
        try {
            $targetEmail = 'darius.lingasa@gmail.com';
            Mail::to($targetEmail)->send(new BackupSuccessMail($filename, $formattedSize, $executionTime, $timestamp, $includedItems));
            Log::info("Backup success notification dispatched to {$targetEmail}");
        } catch (\Exception $e) {
            Log::error('Failed to send backup success email: ' . $e->getMessage());
        }
    }

    private function sendFailureEmail(string $errorMsg, float $executionTime): void
    {
        try {
            $targetEmail = 'darius.lingasa@gmail.com';
            Mail::to($targetEmail)->send(new BackupFailedMail($errorMsg, $executionTime));
            Log::info("Backup failure alert dispatched to {$targetEmail}");
        } catch (\Exception $e) {
            Log::error('Failed to send backup failure notification: ' . $e->getMessage());
        }
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

    private function audit(string $event, bool $successful, ?string $message = null): void
    {
        if (!Schema::hasTable('admin_audit_logs')) {
            return;
        }

        try {
            AdminAuditLog::create([
                'user_id' => null,
                'event' => $event,
                'email' => 'system@amis.edu.ph',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'AMIS-Artisan-Backup-Cron',
                'successful' => $successful,
                'message' => $message,
                'metadata' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write backup audit log: ' . $e->getMessage());
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
