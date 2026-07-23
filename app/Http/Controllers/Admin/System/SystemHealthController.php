<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\System\SystemHealthService;
use Illuminate\Http\Request;

class SystemHealthController extends Controller
{
    protected SystemHealthService $healthService;

    public function __construct(SystemHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    private function ensureSuperOrAdmin(): void
    {
        $role = auth()->user()?->role;
        if (! in_array($role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized. System Health actions are restricted to Administrators.');
        }
    }

    public function index()
    {
        $this->ensureSuperOrAdmin();
        $metrics = $this->healthService->getSystemHealthMetrics();

        return view('admin.system.health.index', $metrics);
    }

    public function sendTestEmail(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $this->healthService->sendTestEmail($request->email);
            AdminAuditLog::record('system_smtp_test_sent', true, "Sent diagnostic test email to {$request->email}");

            return back()->with('success', "Diagnostic test email sent successfully to {$request->email}!");
        } catch (\Exception $e) {
            AdminAuditLog::record('system_smtp_test_failed', false, "SMTP test email failed for {$request->email}: ".$e->getMessage());

            return back()->withErrors(['error' => 'SMTP Diagnostic Email Failed: '.$e->getMessage()]);
        }
    }

    public function pingDiagnostics(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $results = $this->healthService->runDiagnosticPings();

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'results' => $results,
        ]);
    }

    public function clearCache(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $this->healthService->clearCaches();
            AdminAuditLog::record('system_cache_cleared', true, 'Cleared all compiled framework and application caches.');

            return back()->with('success', 'All system caches cleared successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to clear system cache: '.$e->getMessage()]);
        }
    }

    public function warmupCache(Request $request)
    {
        $this->ensureSuperOrAdmin();
        try {
            $this->healthService->warmupCaches();
            AdminAuditLog::record('system_cache_warmed', true, 'Rebuilt and warmed configuration, route, and view caches.');

            return back()->with('success', 'System caches warmed up and optimized successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to warm up cache: '.$e->getMessage()]);
        }
    }
}
