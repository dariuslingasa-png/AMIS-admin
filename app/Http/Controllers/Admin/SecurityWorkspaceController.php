<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SecurityWorkspaceController extends Controller
{
    private function ensureSecurityAuthorized()
    {
        $user = auth()->user();
        if (! $user || (! $user->hasRole('super_admin') && ! $user->hasRole('admin'))) {
            abort(403, 'Unauthorized. Administrative Security privileges required.');
        }
    }

    // 1. Login Activity
    public function loginActivity(Request $request)
    {
        $this->ensureSecurityAuthorized();

        $search = $request->query('search');
        $tab = $request->query('tab', 'all');

        $query = AdminAuditLog::with('user')
            ->whereIn('event', [
                'login_success',
                'login_failed',
                'login_denied',
                'microsoft_login_success',
                'microsoft_login_denied',
            ]);

        // Filter by status/tab
        if ($tab === 'failed') {
            $query->where('successful', false);
        } elseif ($tab === 'success') {
            $query->where('successful', true);
        }

        // Apply Search
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        // Map logs and parse client details
        $logs->getCollection()->transform(function ($log) {
            $uaInfo = $this->parseUserAgent($log->user_agent);
            $log->browser = $uaInfo['browser'];
            $log->device = $uaInfo['device'];

            // Pair Logout Time
            $log->logout_time = null;
            if ($log->successful && in_array($log->event, ['login_success', 'microsoft_login_success'], true) && $log->user_id) {
                $nextLogin = AdminAuditLog::where('user_id', $log->user_id)
                    ->whereIn('event', ['login_success', 'microsoft_login_success'])
                    ->where('created_at', '>', $log->created_at)
                    ->oldest()
                    ->first();

                $logoutQuery = AdminAuditLog::where('user_id', $log->user_id)
                    ->where('event', 'logout')
                    ->where('created_at', '>', $log->created_at);

                if ($nextLogin) {
                    $logoutQuery->where('created_at', '<', $nextLogin->created_at);
                }

                $logoutLog = $logoutQuery->oldest()->first();
                if ($logoutLog) {
                    $log->logout_time = $logoutLog->created_at;
                }
            }

            return $log;
        });

        return view('admin.security.login-activity', compact('logs', 'search', 'tab'));
    }

    // 2. Active Sessions
    public function activeSessions(Request $request)
    {
        $this->ensureSecurityAuthorized();

        $rawSessions = DB::table('sessions')->get();
        $sessions = [];

        foreach ($rawSessions as $sess) {
            $user = null;
            if ($sess->user_id) {
                $user = User::find($sess->user_id);
                // Skip students sessions, only monitor admin/staff portal accounts
                if ($user && ! $user->hasAdminPortalAccess()) {
                    continue;
                }
            }

            $uaInfo = $this->parseUserAgent($sess->user_agent);

            $sessions[] = (object) [
                'id' => $sess->id,
                'user' => $user,
                'ip_address' => $sess->ip_address,
                'browser' => $uaInfo['browser'],
                'device' => $uaInfo['device'],
                'last_activity' => Carbon::createFromTimestamp($sess->last_activity),
                'is_current' => ($sess->id === $request->session()->getId()),
            ];
        }

        // Sort by last activity descending
        usort($sessions, function ($a, $b) {
            return $b->last_activity->timestamp - $a->last_activity->timestamp;
        });

        return view('admin.security.sessions.index', compact('sessions'));
    }

    public function revokeSession(Request $request)
    {
        $this->ensureSecurityAuthorized();

        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $validated['session_id'];

        // Prevent self-revocation from this route (must log out normally)
        if ($sessionId === $request->session()->getId()) {
            return back()->withErrors(['error' => 'You cannot revoke your current session. Please use the Sign Out button.']);
        }

        $sessionData = DB::table('sessions')->where('id', $sessionId)->first();

        if ($sessionData) {
            $user = $sessionData->user_id ? User::find($sessionData->user_id) : null;

            DB::table('sessions')->where('id', $sessionId)->delete();

            if ($user && $user->active_admin_session_id === $sessionId) {
                $user->update(['active_admin_session_id' => null]);
            }

            AdminAuditLog::record('security_session_force_revoked', true, 'Forced session revocation for: '.($user?->email ?: 'Unknown Account')." (IP: {$sessionData->ip_address})");

            return back()->with('success', 'Active session has been successfully revoked. User was forced logged out.');
        }

        return back()->withErrors(['error' => 'Session not found or already expired.']);
    }

    // 3. Security Events
    public function securityEvents(Request $request)
    {
        $this->ensureSecurityAuthorized();

        $search = $request->query('search');

        $query = AdminAuditLog::with('user')
            ->whereIn('event', [
                'administration_user_password_reset',
                'administration_user_status_updated',
                'access_control_permissions_synced',
                'access_control_user_roles_updated',
                'security_session_force_revoked',
            ]);

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $events = $query->latest()->paginate(25)->withQueryString();

        return view('admin.security.events.index', compact('events', 'search'));
    }

    // 4. Audit Logs
    public function auditLogs(Request $request)
    {
        $this->ensureSecurityAuthorized();

        $search = $request->query('search');

        $query = AdminAuditLog::with('user');

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        return view('admin.security.audit-logs', compact('logs', 'search'));
    }

    // Security Dashboard Metrics
    public function securityMetrics(Request $request)
    {
        $this->ensureSecurityAuthorized();

        $total429 = DB::table('admin_audit_logs')
            ->where('event', 'rate_limit_exceeded')
            ->count();

        $topOffendingIps = DB::table('admin_audit_logs')
            ->where('event', 'rate_limit_exceeded')
            ->select('ip_address', DB::raw('count(*) as count'))
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $totalFailedLogins = DB::table('admin_audit_logs')
            ->whereIn('event', ['login_failed', 'login_denied', 'microsoft_login_denied'])
            ->count();

        $mostTargetedEndpoints = DB::table('admin_audit_logs')
            ->where('event', 'rate_limit_exceeded')
            ->select(DB::raw('SUBSTRING_INDEX(message, ": ", -1) as endpoint_path'), DB::raw('count(*) as count'))
            ->groupBy('endpoint_path')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $recentEvents = DB::table('admin_audit_logs')
            ->whereIn('event', ['rate_limit_exceeded', 'login_failed', 'login_denied', 'microsoft_login_denied'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($event) {
                $uaInfo = $this->parseUserAgent($event->user_agent);
                $event->browser = $uaInfo['browser'];
                $event->device = $uaInfo['device'];

                return $event;
            });

        return view('admin.security.metrics', compact(
            'total429',
            'topOffendingIps',
            'totalFailedLogins',
            'mostTargetedEndpoints',
            'recentEvents'
        ));
    }

    // 5. Security Alerts
    public function securityAlerts()
    {
        $this->ensureSecurityAuthorized();

        // 1. Suspicious IP probing: any IP with > 5 failed logins in past 15 minutes
        $timeWindow = Carbon::now()->subMinutes(15);
        $suspiciousIps = AdminAuditLog::where('successful', false)
            ->whereIn('event', ['login_failed', 'login_denied', 'microsoft_login_denied'])
            ->where('created_at', '>=', $timeWindow)
            ->select('ip_address', DB::raw('count(*) as attempts'))
            ->groupBy('ip_address')
            ->having('attempts', '>', 5)
            ->get();

        // 2. Disabled/Locked users
        $lockedAccounts = User::whereIn('role', ['admin', 'finance', 'staff'])
            ->where('account_status', 'disabled')
            ->get();

        // 3. Alerts list
        $alerts = [];
        foreach ($suspiciousIps as $ipData) {
            $alerts[] = (object) [
                'type' => 'critical',
                'title' => 'Suspicious Probing Detected',
                'message' => "Client IP Address {$ipData->ip_address} has triggered {$ipData->attempts} failed login events in the last 15 minutes.",
                'timestamp' => Carbon::now(),
            ];
        }

        foreach ($lockedAccounts as $locked) {
            $alerts[] = (object) [
                'type' => 'warning',
                'title' => 'Administrative Account Locked',
                'message' => "The admin account for {$locked->name} ({$locked->email}) is currently in a disabled state.",
                'timestamp' => $locked->updated_at ?: Carbon::now(),
            ];
        }

        return view('admin.security.alerts.index', compact('alerts'));
    }

    private function parseUserAgent(?string $userAgent): array
    {
        if (blank($userAgent)) {
            return ['browser' => 'Unknown', 'device' => 'Desktop'];
        }

        $ua = strtolower($userAgent);

        $device = 'Desktop';
        if (str_contains($ua, 'mobi') || str_contains($ua, 'iphone') || str_contains($ua, 'ipod') || str_contains($ua, 'android') && ! str_contains($ua, 'tablet')) {
            $device = 'Mobile';
        } elseif (str_contains($ua, 'ipad') || str_contains($ua, 'playbook') || str_contains($ua, 'tablet')) {
            $device = 'Tablet';
        }

        $browser = 'Unknown';
        if (str_contains($ua, 'edge') || str_contains($ua, 'edg/')) {
            $browser = 'Microsoft Edge';
        } elseif (str_contains($ua, 'chrome') || str_contains($ua, 'crios')) {
            $browser = 'Google Chrome';
        } elseif (str_contains($ua, 'safari') && ! str_contains($ua, 'chrome') && ! str_contains($ua, 'chromium')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'firefox') || str_contains($ua, 'fxios')) {
            $browser = 'Mozilla Firefox';
        } elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr/')) {
            $browser = 'Opera';
        } elseif (str_contains($ua, 'msie') || str_contains($ua, 'trident')) {
            $browser = 'Internet Explorer';
        }

        return ['browser' => $browser, 'device' => $device];
    }
}
