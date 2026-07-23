<?php

namespace App\Services\System;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemDevOpsService
{
    public function getDevOpsMetrics(): array
    {
        // 1. Environment Config Audit
        $envAudit = [
            'app_env' => config('app.env', 'production'),
            'app_debug' => config('app.debug', false),
            'mail_driver' => config('mail.default', 'smtp'),
            'queue_driver' => config('queue.default', 'sync'),
            'cache_driver' => config('cache.default', 'file'),
            'db_connection' => config('database.default', 'mysql'),
            'session_driver' => config('session.driver', 'file'),
            'force_https' => config('app.url') && Str::startsWith(config('app.url'), 'https'),
        ];

        // 2. Database Table Metrics
        $dbTables = [];
        $dbName = config('database.connections.mysql.database');
        if (config('database.default') === 'mysql' && $dbName) {
            try {
                $rawTables = DB::select(
                    'SELECT table_name, table_rows, data_length, index_length, engine FROM information_schema.tables WHERE table_schema = ? ORDER BY (data_length + index_length) DESC',
                    [$dbName]
                );
                foreach ($rawTables as $t) {
                    $dbTables[] = [
                        'name' => $t->table_name,
                        'rows' => (int)$t->table_rows,
                        'data_size' => $this->formatBytes($t->data_length),
                        'index_size' => $this->formatBytes($t->index_length),
                        'total_size' => $this->formatBytes($t->data_length + $t->index_length),
                        'engine' => $t->engine,
                    ];
                }
            } catch (\Exception $e) {}
        }

        // 3. Maintenance Mode Status for All Portals
        $portalsMaintenance = [
            'admin' => [
                'name' => 'Admin Portal',
                'domain' => 'admin.amis.edu.ph',
                'is_down' => app()->isDownForMaintenance(),
                'secret' => session('maintenance_secret_url_admin'),
                'badge' => 'Admin System',
            ],
            'enrollment' => [
                'name' => 'Enrollment Portal',
                'domain' => 'enrollment.amis.edu.ph',
                'is_down' => $this->isPortalDown('enrollment'),
                'secret' => $this->getPortalSecret('enrollment'),
                'badge' => 'Public Applicants',
            ],
            'teacher' => [
                'name' => 'Teacher Portal',
                'domain' => 'teacher.amis.edu.ph',
                'is_down' => $this->isPortalDown('teacher'),
                'secret' => $this->getPortalSecret('teacher'),
                'badge' => 'Faculty & Staff',
            ],
            'student' => [
                'name' => 'Student Portal',
                'domain' => 'student.amis.edu.ph',
                'is_down' => $this->isPortalDown('student'),
                'secret' => $this->getPortalSecret('student'),
                'badge' => 'Students & Guardians',
            ],
        ];

        // Legacy compatibility
        $isMaintenanceMode = app()->isDownForMaintenance();
        $maintenanceSecret = session('maintenance_secret_url_admin');

        // 4. Queue Jobs Stats
        $pendingJobs = 0;
        $failedJobs = 0;
        try {
            if (Schema::hasTable('jobs')) {
                $pendingJobs = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (\Exception $e) {}

        // 5. Active User Sessions
        $activeSessionsCount = 0;
        try {
            if (Schema::hasTable('sessions')) {
                $activeSessionsCount = DB::table('sessions')->count();
            } else {
                $activeSessionsCount = User::whereNotNull('updated_at')->where('updated_at', '>=', now()->subMinutes(30))->count();
            }
        } catch (\Exception $e) {}

        return [
            'envAudit' => $envAudit,
            'dbTables' => $dbTables,
            'portalsMaintenance' => $portalsMaintenance,
            'isMaintenanceMode' => $isMaintenanceMode,
            'maintenanceSecret' => $maintenanceSecret,
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'activeSessionsCount' => $activeSessionsCount,
        ];
    }

    public function getPortalPath(string $key): string
    {
        if ($key === 'admin') {
            return base_path();
        }

        $candidates = [
            "/home2/amisdavc/{$key}.amis.edu.ph",
            base_path("../AMIS-{$key}"),
            base_path("../{$key}.amis.edu.ph"),
            base_path("../{$key}"),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path) && is_dir($path)) {
                return realpath($path);
            }
        }

        return "/home2/amisdavc/{$key}.amis.edu.ph";
    }

    public function isPortalDown(string $key): bool
    {
        if ($key === 'admin') {
            return app()->isDownForMaintenance();
        }

        $downFile = $this->getPortalPath($key) . '/storage/framework/down';
        return file_exists($downFile);
    }

    public function getPortalSecret(string $key): ?string
    {
        if ($key === 'admin') {
            return session('maintenance_secret_url_admin');
        }

        $downFile = $this->getPortalPath($key) . '/storage/framework/down';
        if (file_exists($downFile)) {
            $content = @file_get_contents($downFile);
            if ($content) {
                $data = json_decode($content, true);
                if (!empty($data['secret'])) {
                    return "https://{$key}.amis.edu.ph/" . $data['secret'];
                }
            }
        }

        return null;
    }

    public function togglePortalMaintenance(string $key): array
    {
        $secretToken = 'amis_override_' . Str::random(12);

        if ($key === 'admin') {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                session()->forget('maintenance_secret_url_admin');
                return ['status' => 'off', 'portal' => 'Admin Portal'];
            } else {
                Artisan::call('down', ['--secret' => $secretToken]);
                $bypassUrl = url('/' . $secretToken);
                session(['maintenance_secret_url_admin' => $bypassUrl]);
                return ['status' => 'on', 'secret' => $bypassUrl, 'portal' => 'Admin Portal'];
            }
        }

        $portalPath = $this->getPortalPath($key);
        $downFile = $portalPath . '/storage/framework/down';
        $portalNames = [
            'enrollment' => 'Enrollment Portal',
            'teacher' => 'Teacher Portal',
            'student' => 'Student Portal',
        ];
        $name = $portalNames[$key] ?? ucfirst($key) . ' Portal';

        if (file_exists($downFile)) {
            if (function_exists('exec')) {
                @exec("cd {$portalPath} && php artisan up 2>&1");
            }
            if (file_exists($downFile)) {
                @unlink($downFile);
            }
            return ['status' => 'off', 'portal' => $name];
        } else {
            if (function_exists('exec')) {
                @exec("cd {$portalPath} && php artisan down --secret={$secretToken} 2>&1");
            }
            if (!file_exists($downFile)) {
                $storageDir = dirname($downFile);
                if (!is_dir($storageDir)) {
                    @mkdir($storageDir, 0755, true);
                }
                $payload = json_encode([
                    'time' => time(),
                    'status' => 503,
                    'secret' => $secretToken,
                ]);
                @file_put_contents($downFile, $payload);
            }
            $bypassUrl = "https://{$key}.amis.edu.ph/" . $secretToken;
            return ['status' => 'on', 'secret' => $bypassUrl, 'portal' => $name];
        }
    }

    public function optimizeDatabaseTables(): void
    {
        $tables = ['students', 'enrollment_applicants', 'payments', 'users', 'sections', 'admin_audit_logs', 'student_sections'];
        $tableList = implode(', ', $tables);
        
        DB::statement("OPTIMIZE TABLE {$tableList}");
        DB::statement("ANALYZE TABLE {$tableList}");
    }

    public function toggleMaintenanceMode(): string
    {
        $res = $this->togglePortalMaintenance('admin');
        return $res['status'] === 'off' ? 'off' : ($res['secret'] ?? 'on');
    }

    public function retryFailedQueueJobs(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            Artisan::call('queue:retry', ['id' => ['all']]);
        }
    }

    public function flushFailedQueueJobs(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            Artisan::call('queue:flush');
        }
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
