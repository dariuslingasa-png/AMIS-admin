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

        // 3. Maintenance Mode Status
        $isMaintenanceMode = app()->isDownForMaintenance();
        $maintenanceSecret = session('maintenance_secret_url');

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
            'isMaintenanceMode' => $isMaintenanceMode,
            'maintenanceSecret' => $maintenanceSecret,
            'pendingJobs' => $pendingJobs,
            'failedJobs' => $failedJobs,
            'activeSessionsCount' => $activeSessionsCount,
        ];
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
        if (app()->isDownForMaintenance()) {
            Artisan::call('up');
            session()->forget('maintenance_secret_url');
            return 'off';
        } else {
            $secretToken = 'amis_admin_override_' . Str::random(12);
            Artisan::call('down', [
                '--secret' => $secretToken,
            ]);

            $bypassUrl = url('/' . $secretToken);
            session(['maintenance_secret_url' => $bypassUrl]);
            return $bypassUrl;
        }
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
