<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\System\SystemHealthService;
use Illuminate\Http\Request;

class SystemLogController extends Controller
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
            abort(403, 'Unauthorized. Log inspection is restricted to Administrators.');
        }
    }

    public function index()
    {
        $this->ensureSuperOrAdmin();
        $logPath = storage_path('logs/laravel.log');
        $logEntries = [];
        $logSize = 0;

        if (file_exists($logPath)) {
            $logSize = filesize($logPath);

            // Read the last 300 lines of laravel.log efficiently
            $lines = [];
            $fp = @fopen($logPath, 'r');
            if ($fp) {
                $buffer = [];
                while (($line = fgets($fp)) !== false) {
                    $buffer[] = $line;
                    if (count($buffer) > 500) {
                        array_shift($buffer);
                    }
                }
                fclose($fp);
                $lines = array_reverse($buffer);
            }

            $idx = count($lines);
            foreach ($lines as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s+([a-zA-Z0-9_\-]+)\.([A-Z]+):\s+(.*)$/', trim($line), $match)) {
                    $logEntries[] = [
                        'index' => $idx--,
                        'timestamp' => $match[1],
                        'env' => $match[2],
                        'level' => strtoupper($match[3]),
                        'message' => trim($match[4]),
                    ];
                } else {
                    $idx--;
                }
            }
        }

        $formattedLogSize = $this->healthService->formatBytes($logSize);
        return view('admin.system.logs.index', compact('logEntries', 'formattedLogSize', 'logPath'));
    }

    public function clearLogs(Request $request)
    {
        $this->ensureSuperOrAdmin();
        $logPath = storage_path('logs/laravel.log');

        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
            AdminAuditLog::record('system_logs_cleared', true, "Truncated and cleared laravel.log file.");
            return back()->with('success', 'System log file (laravel.log) cleared successfully.');
        }

        return back()->with('info', 'Log file was already empty or does not exist.');
    }

    public function integrationsIndex()
    {
        $this->ensureSuperOrAdmin();
        return view('admin.system.integrations.index');
    }
}
