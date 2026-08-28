<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdministrationController extends Controller
{
    private function ensureSuperOrAdmin()
    {
        $user = auth()->user();
        if (! $user || (! $user->hasRole('super_admin') && ! $user->hasRole('admin'))) {
            abort(403, 'Unauthorized. Super Admin or Admin role required.');
        }
    }

    public function usersIndex(Request $request)
    {
        $this->ensureSuperOrAdmin();

        $search = $request->query('search');
        $status = $request->query('status');

        $query = User::with('roles')
            ->where(function ($q) {
                // Only load admin/portal roles or users that have admin/portal roles
                $q->whereIn('role', User::ADMIN_PORTAL_ROLES)
                    ->orWhereHas('roles', function ($r) {
                        $r->whereIn('slug', User::ADMIN_PORTAL_ROLE_SLUGS);
                    });
            });

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (filled($status)) {
            $query->where('account_status', $status);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        $adminRolesQuery = function ($q) {
            $q->whereIn('role', User::ADMIN_PORTAL_ROLE_SLUGS)
                ->orWhereHas('roles', function ($r) {
                    $r->whereIn('slug', User::ADMIN_PORTAL_ROLE_SLUGS);
                });
        };

        // Calculate stats
        $stats = [
            'total' => User::where($adminRolesQuery)->count(),
            'verified' => User::where($adminRolesQuery)->where('account_status', 'verified')->count(),
            'pending' => User::where($adminRolesQuery)->where('account_status', 'pending')->count(),
            'disabled' => User::where($adminRolesQuery)->where('account_status', 'disabled')->count(),
        ];

        return view('admin.administration.users.index', compact('users', 'search', 'status', 'stats'));
    }

    public function usersCreate()
    {
        $this->ensureSuperOrAdmin();
        $roles = Role::orderBy('hierarchy_level', 'desc')->get();

        return view('admin.administration.users.create', compact('roles'));
    }

    public function usersStore(Request $request)
    {
        $this->ensureSuperOrAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $role = Role::findOrFail($validated['role_id']);

        // Super admin protection: Only super admins can assign super admin role
        if ($role->slug === 'super_admin' && ! auth()->user()->hasRole('super_admin')) {
            return back()->withErrors(['role_id' => 'Only Super Administrators can create or assign Super Admin accounts.'])->withInput();
        }

        // Check if user already exists in database
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // Hierarchy check: Non-super_admin cannot overwrite a user with equal or higher hierarchy
            $currentUserMaxHierarchy = auth()->user()->roles()->max('hierarchy_level') ?: 80;
            $targetUserMaxHierarchy = $existingUser->roles()->max('hierarchy_level') ?: 10;

            if ($targetUserMaxHierarchy >= $currentUserMaxHierarchy && ! auth()->user()->hasRole('super_admin') && $existingUser->id !== auth()->id()) {
                return back()->withErrors(['email' => 'An account with this email already exists with equal or higher administrative privileges.'])->withInput();
            }

            DB::transaction(function () use ($existingUser, $validated, $role) {
                $existingUser->update([
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                    'role' => in_array($role->slug, ['admin', 'finance', 'staff', 'teacher'], true) ? $role->slug : 'staff',
                    'account_status' => 'verified',
                    'email_verified_at' => $existingUser->email_verified_at ?: now(),
                ]);

                $existingUser->roles()->sync([$role->id]);
            });

            AdminAuditLog::record('administration_user_upgraded', true, "Assigned administrative role {$role->name} to existing account: {$email} ({$validated['name']})");

            return redirect()->route('admin.administration.users.index')->with('success', "Existing account {$email} has been updated and granted administrative access as {$role->name}.");
        }

        // Create username
        $baseUsername = Str::slug($validated['name'], '');
        $username = $baseUsername ?: 'admin';
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername.$counter;
            $counter++;
        }

        DB::transaction(function () use ($validated, $role, $username, $email) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'username' => $username,
                'password' => Hash::make($validated['password']),
                'role' => in_array($role->slug, ['admin', 'finance', 'staff', 'teacher'], true) ? $role->slug : 'staff', // Sync for backward compatibility
                'account_status' => 'verified',
                'email_verified_at' => now(),
            ]);

            $user->roles()->sync([$role->id]);
        });

        AdminAuditLog::record('administration_user_created', true, "Created administrative account: {$email} with role: {$role->name}");

        return redirect()->route('admin.administration.users.index')->with('success', "Administrative user {$validated['name']} created successfully.");
    }

    public function usersStatus(Request $request, User $user)
    {
        $this->ensureSuperOrAdmin();

        $validated = $request->validate([
            'account_status' => 'required|in:verified,pending,disabled',
        ]);

        // Prevent modifying self
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot suspend or modify your own account status.']);
        }

        // Check hierarchy: Logged-in admin cannot demote super_admin or equal
        $currentUserMaxHierarchy = auth()->user()->roles()->max('hierarchy_level') ?: 80;
        $targetUserMaxHierarchy = $user->roles()->max('hierarchy_level') ?: 10;

        if ($targetUserMaxHierarchy >= $currentUserMaxHierarchy && ! auth()->user()->hasRole('super_admin')) {
            return back()->withErrors(['error' => 'Permission denied. You cannot modify status for a user with equal or higher role ranking.']);
        }

        $user->update([
            'account_status' => $validated['account_status'],
        ]);

        AdminAuditLog::record('administration_user_status_updated', true, "Updated account status for {$user->email} to {$validated['account_status']}");

        return back()->with('success', "Account status for {$user->name} was updated to {$validated['account_status']}.");
    }

    public function usersSecurity(Request $request, User $user)
    {
        $this->ensureSuperOrAdmin();

        // Check hierarchy
        $currentUserMaxHierarchy = auth()->user()->roles()->max('hierarchy_level') ?: 80;
        $targetUserMaxHierarchy = $user->roles()->max('hierarchy_level') ?: 10;

        if ($targetUserMaxHierarchy > $currentUserMaxHierarchy && ! auth()->user()->hasRole('super_admin')) {
            abort(403, 'Permission denied. Cannot view security configurations of a higher-ranking administrator.');
        }

        // Get user session attempts
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->get();

        $auditLogs = AdminAuditLog::where('user_id', $user->id)
            ->whereIn('event', ['login_success', 'login_failed', 'password_changed', 'logout'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.administration.users.security', compact('user', 'sessions', 'auditLogs'));
    }

    public function usersSecurityUpdate(Request $request, User $user)
    {
        $this->ensureSuperOrAdmin();

        // Check hierarchy
        $currentUserMaxHierarchy = auth()->user()->roles()->max('hierarchy_level') ?: 80;
        $targetUserMaxHierarchy = $user->roles()->max('hierarchy_level') ?: 10;

        if ($targetUserMaxHierarchy >= $currentUserMaxHierarchy && ! auth()->user()->hasRole('super_admin') && $user->id !== auth()->id()) {
            return back()->withErrors(['error' => 'Permission denied. Cannot modify credentials of a user with equal or higher role ranking.']);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AdminAuditLog::record('administration_user_password_reset', true, "Manually reset password credentials for {$user->email}");

        return back()->with('success', "Password credential for {$user->name} has been updated.");
    }

    public function usersDestroy(Request $request, User $user)
    {
        $this->ensureSuperOrAdmin();

        // Prevent deleting self
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Hierarchy check: Non-super_admin cannot delete equal or higher ranking user
        $currentUserMaxHierarchy = auth()->user()->roles()->max('hierarchy_level') ?: 80;
        $targetUserMaxHierarchy = $user->roles()->max('hierarchy_level') ?: 10;

        if ($targetUserMaxHierarchy >= $currentUserMaxHierarchy && ! auth()->user()->hasRole('super_admin')) {
            return back()->withErrors(['error' => 'Permission denied. You cannot delete an administrator with equal or higher role ranking.']);
        }

        $userName = $user->name ?: $user->email;
        $userEmail = $user->email;

        DB::transaction(function () use ($user) {
            // Detach roles
            $user->roles()->detach();
            // Delete active sessions
            DB::table('sessions')->where('user_id', $user->id)->delete();
            // Delete the user record
            $user->delete();
        });

        AdminAuditLog::record('administration_user_deleted', true, "Permanently deleted administrative account: {$userEmail} ({$userName})");

        return redirect()->route('admin.administration.users.index')->with('success', "User {$userName} has been successfully deleted.");
    }
}
