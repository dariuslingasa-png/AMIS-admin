<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::whereIn('role', User::ADMIN_PORTAL_ROLES)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $admins->count(),
            'verified' => $admins->where('account_status', 'verified')->count(),
            'current' => auth()->user(),
        ];

        return view('admin.admins.index', compact('admins', 'stats'));
    }

    public function auditLogs(Request $request)
    {
        $tab = $request->query('tab', 'login');
        $search = $request->query('search');

        $query = AdminAuditLog::with('user');

        // Apply Tab Filter
        if ($tab === 'login') {
            $query->where(function ($q) {
                $q->whereIn('event', [
                    'login_success',
                    'login_failed',
                    'login_denied',
                    'microsoft_login_success',
                    'microsoft_login_denied',
                    'logout',
                    'previous_session_revoked',
                    'teacher_password_changed_onboarding',
                ]);
            })->whereNotNull('user_id');
        } elseif ($tab === 'unknown') {
            $query->where(function ($q) {
                $q->whereIn('event', [
                    'login_failed',
                    'login_denied',
                    'microsoft_login_denied',
                ]);
            })->whereNull('user_id');
        } elseif ($tab === 'approve') {
            $query->where(function ($q) {
                $q->whereIn('event', [
                    'application_approved',
                    'application_status_updated',
                    'onboarding_email_resent',
                    'section_verified',
                    'payment_approved',
                    'payment_rejected',
                    'payment_reminder_sent',
                ])->orWhere('event', 'like', 'document%');
            });
        } else { // 'system' tab
            $query->where(function ($q) {
                $q->whereNotIn('event', [
                    'login_success',
                    'login_failed',
                    'login_denied',
                    'microsoft_login_success',
                    'microsoft_login_denied',
                    'logout',
                    'previous_session_revoked',
                    'teacher_password_changed_onboarding',
                    'application_approved',
                    'application_status_updated',
                    'onboarding_email_resent',
                    'section_verified',
                    'payment_approved',
                    'payment_rejected',
                    'payment_reminder_sent',
                ])->where('event', 'not like', 'document%');
            });
        }

        // Apply Search
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        return view('admin.admins.audit-logs', compact('logs', 'tab', 'search'));
    }

    public function loginActivity(Request $request)
    {
        $search = $request->query('search');

        $query = AdminAuditLog::with('user')
            ->whereIn('event', [
                'login_success',
                'login_failed',
                'login_denied',
                'microsoft_login_success',
                'microsoft_login_denied',
            ]);

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

        $logs = $query->latest()->paginate(30)->withQueryString();

        // Map logs and transform details
        $logs->getCollection()->transform(function ($log) {
            // 1. User Agent Parsing
            $uaInfo = $this->parseUserAgent($log->user_agent);
            $log->browser = $uaInfo['browser'];
            $log->device = $uaInfo['device'];

            // 2. Pair Logout Time if login was successful
            $log->logout_time = null;
            if ($log->successful && in_array($log->event, ['login_success', 'microsoft_login_success'], true) && $log->user_id) {
                // Find the next login for this user
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

        return view('admin.admins.login-activity', compact('logs', 'search'));
    }

    private function parseUserAgent(?string $userAgent): array
    {
        if (blank($userAgent)) {
            return ['browser' => 'Unknown', 'device' => 'Desktop'];
        }

        $ua = strtolower($userAgent);

        // 1. Determine Device
        $device = 'Desktop';
        if (str_contains($ua, 'mobi') || str_contains($ua, 'iphone') || str_contains($ua, 'ipod') || str_contains($ua, 'android') && ! str_contains($ua, 'tablet')) {
            $device = 'Mobile';
        } elseif (str_contains($ua, 'ipad') || str_contains($ua, 'playbook') || str_contains($ua, 'tablet')) {
            $device = 'Tablet';
        }

        // 2. Determine Browser
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
        } elseif (str_contains($ua, 'guzzle') || str_contains($ua, 'curl') || str_contains($ua, 'postman')) {
            $browser = 'API Client';
        } elseif (str_contains($ua, 'artisan') || str_contains($ua, 'cron')) {
            $browser = 'System Console';
        }

        return ['browser' => $browser, 'device' => $device];
    }

    public function edit(User $user)
    {
        $this->ensureSystemAdmin();

        if (! in_array($user->role, User::ADMIN_PORTAL_ROLES, true)) {
            abort(404);
        }

        $permissions = $user->access_permissions ?: $user->defaultAccessPermissions();

        return view('admin.admins.edit', compact('user', 'permissions'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureSystemAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|in:admin,finance,staff',
            'account_status' => 'required|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (! in_array($user->role, User::ADMIN_PORTAL_ROLES, true)) {
            return back()->withErrors(['error' => 'User is not an admin portal account.']);
        }

        $permissions = [
            'payment_review' => $request->boolean('payment_review'),
            'document_review' => $request->boolean('document_review'),
            'view_only' => $request->boolean('view_only'),
        ];

        if ($permissions['view_only']) {
            $permissions['payment_review'] = false;
            $permissions['document_review'] = false;
        }

        if ($user->id === auth()->id() && ($validated['role'] !== 'admin' || $permissions['view_only'])) {
            return back()->withErrors(['error' => 'You cannot remove your own ADMIN access.']);
        }

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'account_status' => $validated['account_status'],
            'access_permissions' => $permissions,
        ];

        if (filled($validated['password'] ?? null)) {
            $updates['password'] = Hash::make($validated['password']);
        }

        $user->update($updates);

        return redirect()->route('admin.admins.index')->with('success', "{$user->name}'s account was updated.");
    }

    public function store(Request $request)
    {
        $this->ensureSystemAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|in:admin,finance,staff',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim((string) $request->email));
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $existingUser->update([
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'access_permissions' => $this->defaultPermissionsForRole($request->role),
                'account_status' => 'verified',
                'email_verified_at' => $existingUser->email_verified_at ?: now(),
            ]);

            AdminAuditLog::record('admin_account_upgraded', true, "Updated and granted {$request->role} access to {$email}");

            return back()->with('success', "Existing account for {$email} was successfully updated with {$request->role} access.");
        }

        User::create([
            'name' => $request->name,
            'email' => $email,
            'username' => $this->uniqueUsername($request->name),
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'access_permissions' => $this->defaultPermissionsForRole($request->role),
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);

        AdminAuditLog::record('admin_account_created', true, "Created admin portal account for {$email} ({$request->role})");

        return back()->with('success', "Portal account created for {$request->name}.");
    }

    public function destroy(User $user)
    {
        $this->ensureSystemAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        if (! in_array($user->role, User::ADMIN_PORTAL_ROLES, true)) {
            return back()->withErrors(['error' => 'User is not an admin portal account.']);
        }

        $user->delete();

        return back()->with('success', 'Admin account removed.');
    }

    public function updateRole(Request $request, User $user)
    {
        $this->ensureSystemAdmin();

        $request->validate([
            'role' => 'required|in:admin,finance,staff',
        ]);

        if (! in_array($user->role, User::ADMIN_PORTAL_ROLES, true)) {
            return back()->withErrors(['error' => 'User is not an admin portal account.']);
        }

        if ($user->id === auth()->id() && $request->role !== 'admin') {
            return back()->withErrors(['error' => 'You cannot remove your own ADMIN access.']);
        }

        $user->update([
            'role' => $request->role,
            'access_permissions' => $this->defaultPermissionsForRole($request->role),
        ]);

        return back()->with('success', "{$user->name}'s access role was updated.");
    }

    public function updateAccess(Request $request, User $user)
    {
        $this->ensureSystemAdmin();

        $request->validate([
            'role' => 'required|in:admin,finance,staff',
        ]);

        if (! in_array($user->role, User::ADMIN_PORTAL_ROLES, true)) {
            return back()->withErrors(['error' => 'User is not an admin portal account.']);
        }

        $permissions = [
            'payment_review' => $request->boolean('payment_review'),
            'document_review' => $request->boolean('document_review'),
            'view_only' => $request->boolean('view_only'),
        ];

        if ($permissions['view_only']) {
            $permissions['payment_review'] = false;
            $permissions['document_review'] = false;
        }

        if ($user->id === auth()->id() && ($request->role !== 'admin' || $permissions['view_only'])) {
            return back()->withErrors(['error' => 'You cannot remove your own ADMIN access.']);
        }

        $user->update([
            'role' => $request->role,
            'access_permissions' => $permissions,
        ]);

        return back()->with('success', "{$user->name}'s access permissions were updated.");
    }

    public function accept(User $user)
    {
        $this->ensureSystemAdmin();

        if (! in_array($user->role, User::ADMIN_PORTAL_ROLES, true)) {
            return back()->withErrors(['error' => 'User is not an admin portal account.']);
        }

        $user->update([
            'account_status' => 'verified',
        ]);

        return back()->with('success', "{$user->name}'s account has been verified and granted access.");
    }

    private function ensureSystemAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    private function defaultPermissionsForRole(string $role): array
    {
        return [
            'payment_review' => in_array($role, User::PAYMENT_REVIEW_ROLES, true),
            'document_review' => $role === 'admin',
            'view_only' => $role === 'staff',
        ];
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::of($name)->lower()->slug('.')->value() ?: 'admin';
        $username = $base;
        $counter = 2;

        while (User::where('username', $username)->exists()) {
            $username = "{$base}.{$counter}";
            $counter++;
        }

        return $username;
    }
}
