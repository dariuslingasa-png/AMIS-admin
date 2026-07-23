<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\System\SystemDevOpsService;
use Illuminate\Http\Request;

class SystemDevOpsController extends Controller
{
    protected SystemDevOpsService $devOpsService;

    public function __construct(SystemDevOpsService $devOpsService)
    {
        $this->devOpsService = $devOpsService;
    }

    private function ensureSuperOrAdmin(): void
    {
        $role = auth()->user()?->role;
        if (!in_array($role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized. DevOps operations are restricted to Administrators.');
        }
    }

    public function index()
    {
        $this->ensureSuperOrAdmin();
        $metrics = $this->devOpsService->getDevOpsMetrics();
        return view('admin.system.devops.index', $metrics);
    }

    public function dbOptimize(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $this->devOpsService->optimizeDatabaseTables();
            AdminAuditLog::record('system_db_optimized', true, "Executed OPTIMIZE & ANALYZE TABLE on core database tables.");
            return back()->with('success', 'Database tables optimized and defragmented successfully!');
        } catch (\Exception $e) {
            AdminAuditLog::record('system_db_optimize_failed', false, "Failed to optimize database tables: " . $e->getMessage());
            return back()->withErrors(['error' => 'Database Optimization Failed: ' . $e->getMessage()]);
        }
    }

    public function toggleMaintenanceMode(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $status = $this->devOpsService->toggleMaintenanceMode();
            if ($status === 'off') {
                AdminAuditLog::record('system_maintenance_disabled', true, "Turned off Maintenance Mode. Portal is now LIVE to public.");
                return back()->with('success', 'Maintenance Mode disabled. The portal is now LIVE and accessible to the public!');
            } else {
                AdminAuditLog::record('system_maintenance_enabled', true, "Enabled Maintenance Mode with secret bypass token.");
                return back()->with('success', "Maintenance Mode ENABLED! Public access is locked. Use your Secret Admin Bypass Link: {$status}");
            }
        } catch (\Exception $e) {
            AdminAuditLog::record('system_maintenance_toggle_failed', false, "Failed to toggle maintenance mode: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to toggle maintenance mode: ' . $e->getMessage()]);
        }
    }

    public function retryFailedJobs(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $this->devOpsService->retryFailedQueueJobs();
            AdminAuditLog::record('system_queue_failed_retried', true, "Retried all failed background queue jobs.");
            return back()->with('success', 'Queued all failed jobs for retry!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to retry queue jobs: ' . $e->getMessage()]);
        }
    }

    public function flushFailedJobs(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $this->devOpsService->flushFailedQueueJobs();
            AdminAuditLog::record('system_queue_failed_flushed', true, "Flushed and cleared failed jobs table.");
            return back()->with('success', 'Cleared all failed queue jobs.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to flush queue jobs: ' . $e->getMessage()]);
        }
    }
}
