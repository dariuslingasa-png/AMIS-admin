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
        if (! in_array($role, ['super_admin', 'admin'])) {
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
            AdminAuditLog::record('system_logs_cleared', true, 'Truncated and cleared laravel.log file.');

            return back()->with('success', 'System log file (laravel.log) cleared successfully.');
        }

        return back()->with('info', 'Log file was already empty or does not exist.');
    }

    public function integrationsIndex()
    {
        $this->ensureSuperOrAdmin();

        $integrations = [
            'microsoft_entra' => [
                'description' => 'Single Sign-on identity configuration for MS365 accounts.',
                'configured' => filled(config('services.microsoft.client_id') ?? env('MICROSOFT_CLIENT_ID')),
                'client_id' => config('services.microsoft.client_id') ?? env('MICROSOFT_CLIENT_ID') ?? 'Not Set',
                'tenant_id' => config('services.microsoft.tenant_id') ?? env('MICROSOFT_TENANT_ID') ?? 'Not Set',
                'redirect_uri' => config('services.microsoft.redirect_uri') ?? env('MICROSOFT_REDIRECT_URI') ?? '/auth/microsoft/callback',
            ],
            'microsoft_graph' => [
                'description' => 'Active Directory and Microsoft Teams roster sync provisioning.',
                'configured' => filled(config('services.microsoft.client_secret') ?? env('MICROSOFT_CLIENT_SECRET')),
                'scopes' => 'User.Read, TeamMember.ReadWrite.All, Directory.Read.All',
            ],
            'google_drive' => [
                'description' => 'Cloud backup storage directory integration for database snapshots.',
                'configured' => filled(config('services.google_drive.folder_id') ?? env('GOOGLE_DRIVE_FOLDER_ID')),
                'folder_id' => config('services.google_drive.folder_id') ?? env('GOOGLE_DRIVE_FOLDER_ID') ?? 'Not Configured',
            ],
            'email' => [
                'description' => 'SMTP mail gateway and Multi-SMTP failover rotator.',
                'configured' => filled(config('mail.mailers.smtp.host') ?? env('MAIL_HOST')),
                'host' => config('mail.mailers.smtp.host') ?? env('MAIL_HOST') ?? 'mail.amis.edu.ph',
                'port' => config('mail.mailers.smtp.port') ?? env('MAIL_PORT') ?? 587,
                'encryption' => config('mail.mailers.smtp.encryption') ?? env('MAIL_ENCRYPTION') ?? 'SSL/TLS',
            ],
        ];

        return view('admin.system.integrations.index', compact('integrations'));
    }
}
