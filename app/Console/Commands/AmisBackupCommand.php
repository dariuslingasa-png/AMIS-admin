<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\AdminAuditLog;
use App\Mail\BackupFailedMail;
use ZipArchive;
use Symfony\Component\Process\Process;

class AmisBackupCommand extends Command
{
    protected $signature = 'amis:backup';
    protected $description = 'Create a full SQL snapshot and media zip backup and upload to Google Drive via rclone';

    public function handle()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $startTime = microtime(true);
        $dateStr = date('Y-m-d');
        $this->info("Starting AMIS backup process for {$dateStr}...");

        $tempDir = storage_path('app/backups/temp_' . time());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $dbFile = $tempDir . '/database.sql';
        $storageZipFile = $tempDir . '/storage.zip';
        $fullBackupZipFile = $tempDir . '/full-backup.zip';

        try {
            // 1. Ensure Rclone Binary is Available
            $rclonePath = $this->ensureRcloneBinary();

            // 2. Backup Database (SQL)
            $this->info('Dumping database...');
            $this->dumpDatabase($dbFile);

            // 3. Backup Uploads and Media (Zip)
            $this->info('Creating storage archive...');
            $this->createStorageZip($storageZipFile);

            // 4. Create Full Backup (Combine SQL & Storage Zip)
            $this->info('Creating full backup archive...');
            $this->createFullBackupZip($fullBackupZipFile, $dbFile, $storageZipFile);

            // 5. Generate Rclone config dynamically
            $this->info('Configuring rclone...');
            $configPath = $this->generateRcloneConfig($tempDir);

            // 6. Upload to Google Drive using rclone
            $this->info('Uploading backups to Google Drive...');
            $this->uploadToDrive($rclonePath, $configPath, $tempDir, $dateStr);

            // 7. Run grandfather-father-son backup rotation policy
            $this->info('Running Google Drive retention rotation policy...');
            $this->rotateBackups($rclonePath, $configPath);

            // 8. Clean up local files
            $this->info('Cleaning up local temp files...');
            $this->cleanLocalDirectory($tempDir);
            if (file_exists($configPath)) {
                unlink($configPath);
            }

            $executionTime = round(microtime(true) - $startTime, 2);
            $totalSize = file_exists($fullBackupZipFile) ? filesize($fullBackupZipFile) : 0;
            $formattedSize = $this->formatBytes($totalSize);

            // 9. Log Success Audit
            $this->audit(
                'database_backup_automated_success',
                true,
                "Automated backup completed successfully. Total Size: {$formattedSize}. Time: {$executionTime}s."
            );

            $this->info('Backup process finished successfully!');
        } catch (\Exception $e) {
            $executionTime = round(microtime(true) - $startTime, 2);
            Log::error('Automated backup failed: ' . $e->getMessage());

            // Clean up whatever we can locally
            if (file_exists($tempDir)) {
                $this->cleanLocalDirectory($tempDir);
            }
            $configPath = storage_path('app/backups/rclone.conf');
            if (file_exists($configPath)) {
                unlink($configPath);
            }

            // Log Failure Audit
            $this->audit(
                'database_backup_automated_failed',
                false,
                'Automated backup failed: ' . $e->getMessage() . " (Time: {$executionTime}s)"
            );

            // Send Email Notifications
            $this->sendFailureEmail($e->getMessage(), $executionTime);

            $this->error('Backup process failed!');
            return 1;
        }

        return 0;
    }

    private function dumpDatabase(string $outputPath)
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

    private function createStorageZip(string $outputPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create storage zip archive.');
        }

        // 1. Zip local public storage directory
        $publicStorage = storage_path('app/public');
        if (file_exists($publicStorage)) {
            $this->addFolderToZip($zip, $publicStorage, 'storage_public');
        }

        // 2. Zip Ebooks Private Storage (Disk: ebook_private)
        $ebookPrivateRoot = config('filesystems.disks.ebook_private.root');
        if ($ebookPrivateRoot && file_exists($ebookPrivateRoot)) {
            $this->addFolderToZip($zip, $ebookPrivateRoot, 'ebooks_private');
        }

        // 3. Zip Ebooks Public Covers Directory
        if ($ebookPrivateRoot) {
            $coversDir = dirname(rtrim($ebookPrivateRoot, '/')) . '/public/covers';
            if (file_exists($coversDir)) {
                $this->addFolderToZip($zip, $coversDir, 'ebook_covers');
            }
        }

        $zip->close();

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new \Exception('Storage zip file is empty or could not be generated.');
        }
    }

    private function addFolderToZip(ZipArchive $zip, string $folderPath, string $zipSubFolder)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folderPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipSubFolder . '/' . substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function createFullBackupZip(string $outputPath, string $dbFile, string $storageZipFile)
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create full backup zip archive.');
        }

        if (file_exists($dbFile)) {
            $zip->addFile($dbFile, 'database.sql');
        }
        if (file_exists($storageZipFile)) {
            $zip->addFile($storageZipFile, 'storage.zip');
        }

        $zip->close();

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new \Exception('Full backup zip file is empty or could not be generated.');
        }
    }

    private function generateRcloneConfig(string $tempDir): string
    {
        $clientId = config('services.google_drive.client_id');
        $clientSecret = config('services.google_drive.client_secret');
        $refreshToken = config('services.google_drive.refresh_token');
        $folderId = config('services.google_drive.folder_id');

        if (empty($clientId) || empty($clientSecret) || empty($refreshToken)) {
            throw new \Exception('Google Drive credentials are not fully configured in config/services.php.');
        }

        $configPath = storage_path('app/backups/rclone.conf');
        $tokenJson = json_encode([
            'access_token' => 'init_dummy_value',
            'token_type' => 'Bearer',
            'refresh_token' => $refreshToken,
            'expiry' => '2000-01-01T00:00:00Z',
        ]);

        $configContent = "[gdrive]\n" .
            "type = drive\n" .
            "client_id = {$clientId}\n" .
            "client_secret = {$clientSecret}\n" .
            "token = {$tokenJson}\n";

        if (!empty($folderId)) {
            $configContent .= "root_folder_id = {$folderId}\n";
        }

        file_put_contents($configPath, $configContent);
        return $configPath;
    }

    private function ensureRcloneBinary(): string
    {
        $binDir = storage_path('app/bin');
        if (!file_exists($binDir)) {
            mkdir($binDir, 0755, true);
        }
        $rclonePath = $binDir . '/rclone';
        if (file_exists($rclonePath)) {
            return $rclonePath;
        }

        $zipPath = $binDir . '/rclone.zip';
        $url = 'https://downloads.rclone.org/rclone-current-linux-386.zip';

        // Download rclone zip
        $fileContent = @file_get_contents($url);
        if ($fileContent === false) {
            throw new \Exception("Failed to download rclone from {$url}");
        }
        file_put_contents($zipPath, $fileContent);

        // Extract zip
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (basename($filename) === 'rclone' && !str_ends_with($filename, '/')) {
                    copy('zip://' . $zipPath . '#' . $filename, $rclonePath);
                    break;
                }
            }
            $zip->close();
        }

        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        if (file_exists($rclonePath)) {
            chmod($rclonePath, 0755);
            return $rclonePath;
        }

        throw new \Exception('Failed to install rclone binary on server.');
    }

    private function uploadToDrive(string $rclonePath, string $configPath, string $tempDir, string $dateStr)
    {
        // Target folder in drive: AMIS-Backups/YYYY-MM-DD/
        $driveTarget = "gdrive:AMIS-Backups/{$dateStr}";

        // Run rclone copy
        $cmd = sprintf(
            '%s --config %s copy %s %s',
            escapeshellarg($rclonePath),
            escapeshellarg($configPath),
            escapeshellarg($tempDir),
            escapeshellarg($driveTarget)
        );

        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = implode("\n", $output);
            throw new \Exception('rclone upload failed: ' . ($errorMsg ?: 'Unknown rclone execution error.'));
        }
    }

    private function rotateBackups(string $rclonePath, string $configPath)
    {
        $cmd = sprintf(
            '%s --config %s lsf gdrive:AMIS-Backups/',
            escapeshellarg($rclonePath),
            escapeshellarg($configPath)
        );
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0) {
            Log::warning('Grandfather-father-son rotation listing failed.');
            return;
        }

        foreach ($output as $line) {
            $folderName = rtrim($line, '/');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderName)) {
                continue;
            }

            $dateTimestamp = strtotime($folderName);
            if (!$dateTimestamp) {
                continue;
            }

            $ageInDays = (time() - $dateTimestamp) / 86400;
            $keep = false;

            // 1. Keep all daily backups for 30 days
            if ($ageInDays <= 30) {
                $keep = true;
            }

            // 2. Keep weekly backups (Sundays) for 12 weeks
            $dayOfWeek = date('w', $dateTimestamp); // 0 = Sunday
            if ($dayOfWeek == 0 && $ageInDays <= 84) {
                $keep = true;
            }

            // 3. Keep monthly backups (1st of month) for 12 months
            $dayOfMonth = date('j', $dateTimestamp);
            if ($dayOfMonth == 1 && $ageInDays <= 365) {
                $keep = true;
            }

            if (!$keep) {
                Log::info("Grandfather-father-son: Deleting expired cloud backup folder {$folderName}");
                $purgeCmd = sprintf(
                    '%s --config %s purge gdrive:AMIS-Backups/%s',
                    escapeshellarg($rclonePath),
                    escapeshellarg($configPath),
                    escapeshellarg($folderName)
                );
                exec($purgeCmd);
            }
        }
    }

    private function cleanLocalDirectory(string $tempDir)
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

    private function sendFailureEmail(string $errorMsg, float $executionTime)
    {
        try {
            $recipient = env('SCHOOL_EMAIL', 'almunawwaraislamicschool@gmail.com');
            $adminEmail = env('MAIL_FROM_ADDRESS', 'amisonlinesupport@gmail.com');

            Mail::to([$recipient, $adminEmail])->send(new BackupFailedMail($errorMsg, $executionTime));
        } catch (\Exception $e) {
            Log::error('Failed to send backup failure notification: ' . $e->getMessage());
        }
    }

    private function audit(string $event, bool $successful, ?string $message = null): void
    {
        if (!Schema::hasTable('admin_audit_logs')) {
            return;
        }

        try {
            AdminAuditLog::create([
                'user_id' => null, // system action
                'event' => $event,
                'email' => 'system@amis.edu.ph',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'AMIS-Artisan-Backup-Cron',
                'successful' => $successful,
                'message' => $message,
                'metadata' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write automated backup audit log: ' . $e->getMessage());
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
