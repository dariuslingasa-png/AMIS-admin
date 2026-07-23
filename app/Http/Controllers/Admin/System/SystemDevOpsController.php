<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\SystemNotification;
use App\Services\System\SystemDevOpsService;
use Illuminate\Http\Request;

class SystemDevOpsController extends Controller
{
    protected SystemDevOpsService $devOpsService;

    public function __construct(SystemDevOpsService $devOpsService)
    {
        $this->devOpsService = $devOpsService;
    }

    private function ensureAdminAccess(): void
    {
        $role = auth()->user()?->role;
        if (! in_array($role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized. DevOps operations and Maintenance Mode controls are restricted to Administrators.');
        }
    }

    public function index()
    {
        $this->ensureAdminAccess();
        $metrics = $this->devOpsService->getDevOpsMetrics();

        return view('admin.system.devops.index', $metrics);
    }

    public function dbOptimize(Request $request)
    {
        $this->ensureAdminAccess();
        try {
            $this->devOpsService->optimizeDatabaseTables();
            AdminAuditLog::record('system_db_optimized', true, 'Executed OPTIMIZE & ANALYZE TABLE on core database tables.');

            return back()->with('success', 'Database tables optimized and defragmented successfully!');
        } catch (\Exception $e) {
            AdminAuditLog::record('system_db_optimize_failed', false, 'Failed to optimize database tables: '.$e->getMessage());

            return back()->withErrors(['error' => 'Database Optimization Failed: '.$e->getMessage()]);
        }
    }

    public function toggleMaintenanceMode(Request $request)
    {
        $this->ensureAdminAccess();
        $portal = $request->input('portal', 'admin');

        try {
            if ($portal === 'all_on') {
                foreach (['enrollment', 'teacher', 'student'] as $p) {
                    if (! $this->devOpsService->isPortalDown($p)) {
                        $this->devOpsService->togglePortalMaintenance($p);
                    }
                }
                AdminAuditLog::record('system_maintenance_all_enabled', true, 'Enabled Maintenance Mode on all public portals (Enrollment, Teacher, Student).');
                SystemNotification::notifyAdmin('All Public Portals Locked', 'Enrollment, Teacher, and Student portals were placed into Maintenance Mode.', 'warning', route('admin.system-management.devops.index'));

                return back()->with('success', 'All public portals (Enrollment, Teacher, Student) are now locked in Maintenance Mode!');
            }

            if ($portal === 'all_off') {
                foreach (['admin', 'enrollment', 'teacher', 'student'] as $p) {
                    if ($this->devOpsService->isPortalDown($p)) {
                        $this->devOpsService->togglePortalMaintenance($p);
                    }
                }
                AdminAuditLog::record('system_maintenance_all_disabled', true, 'Turned off Maintenance Mode across all portals.');
                SystemNotification::notifyAdmin('All Portals LIVE', 'All AMIS portals are now LIVE and accessible to users.', 'success', route('admin.system-management.devops.index'));

                return back()->with('success', 'All AMIS portals are now LIVE and accepting public traffic!');
            }

            $res = $this->devOpsService->togglePortalMaintenance($portal);
            if ($res['status'] === 'off') {
                AdminAuditLog::record('system_maintenance_disabled', true, "Turned off Maintenance Mode for {$res['portal']}.");
                SystemNotification::notifyAdmin("{$res['portal']} LIVE", "{$res['portal']} is now LIVE and accessible.", 'success', route('admin.system-management.devops.index'));

                return back()->with('success', "Maintenance Mode disabled for {$res['portal']}. It is now LIVE!");
            } else {
                AdminAuditLog::record('system_maintenance_enabled', true, "Enabled Maintenance Mode for {$res['portal']}.");
                SystemNotification::notifyAdmin("{$res['portal']} Locked", "{$res['portal']} is now locked in Maintenance Mode.", 'warning', route('admin.system-management.devops.index'));

                return back()->with('success', "Maintenance Mode ENABLED for {$res['portal']}! Public access is locked. Bypass Link: ".($res['secret'] ?? 'Active'));
            }
        } catch (\Exception $e) {
            AdminAuditLog::record('system_maintenance_toggle_failed', false, "Failed to toggle maintenance mode for {$portal}: ".$e->getMessage());

            return back()->withErrors(['error' => 'Failed to toggle maintenance mode: '.$e->getMessage()]);
        }
    }

    public function retryFailedJobs(Request $request)
    {
        $this->ensureAdminAccess();
        try {
            $this->devOpsService->retryFailedQueueJobs();
            AdminAuditLog::record('system_queue_failed_retried', true, 'Retried all failed background queue jobs.');

            return back()->with('success', 'Queued all failed jobs for retry!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to retry queue jobs: '.$e->getMessage()]);
        }
    }

    public function flushFailedJobs(Request $request)
    {
        $this->ensureAdminAccess();
        try {
            $this->devOpsService->flushFailedQueueJobs();
            AdminAuditLog::record('system_queue_failed_flushed', true, 'Flushed and cleared failed jobs table.');

            return back()->with('success', 'Cleared all failed queue jobs.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to flush queue jobs: '.$e->getMessage()]);
        }
    }
}
