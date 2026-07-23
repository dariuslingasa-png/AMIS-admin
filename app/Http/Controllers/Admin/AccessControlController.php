<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessControlController extends Controller
{
    private function ensureSuperAdmin()
    {
        if (! auth()->user() || ! auth()->user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized. Super Administrator role required.');
        }
    }

    // 1. Roles CRUD
    public function rolesIndex()
    {
        $this->ensureSuperAdmin();
        $roles = Role::orderBy('hierarchy_level', 'desc')->get();

        return view('admin.access-control.roles.index', compact('roles'));
    }

    public function rolesStore(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'hierarchy_level' => 'required|integer|min:0|max:99', // 100 reserved for super_admin
        ]);

        $slug = Str::slug($validated['name'], '_');

        Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'],
            'hierarchy_level' => $validated['hierarchy_level'],
            'is_protected' => false,
        ]);

        AdminAuditLog::record('access_control_role_created', true, "Created role: {$validated['name']} (slug: {$slug})");

        return back()->with('success', "Role '{$validated['name']}' created successfully.");
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        $this->ensureSuperAdmin();

        if ($role->isProtected() && $role->slug === 'super_admin') {
            return back()->withErrors(['error' => 'The Super Administrator role cannot be modified.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'description' => 'nullable|string',
            'hierarchy_level' => 'required|integer|min:0|max:99',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'hierarchy_level' => $role->slug === 'super_admin' ? 100 : $validated['hierarchy_level'],
        ]);

        AdminAuditLog::record('access_control_role_updated', true, "Updated role metadata for: {$role->slug}");

        return back()->with('success', "Role '{$role->name}' updated successfully.");
    }

    public function rolesDestroy(Role $role)
    {
        $this->ensureSuperAdmin();

        if ($role->isProtected()) {
            return back()->withErrors(['error' => 'Protected roles cannot be deleted.']);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete role because it is currently assigned to users.']);
        }

        $roleName = $role->name;
        $role->delete();

        AdminAuditLog::record('access_control_role_deleted', true, "Deleted role: {$roleName}");

        return back()->with('success', "Role '{$roleName}' has been removed.");
    }

    // 2. Permission Matrix
    public function permissionsIndex()
    {
        $this->ensureSuperAdmin();
        $roles = Role::with('permissions')->orderBy('hierarchy_level', 'desc')->get();
        $permissions = Permission::all()->groupBy('category');

        return view('admin.access-control.permissions.index', compact('roles', 'permissions'));
    }

    public function permissionsUpdate(Request $request)
    {
        $this->ensureSuperAdmin();

        $matrix = $request->input('matrix', []); // Format: [role_id => [permission_id => on]]

        DB::transaction(function () use ($matrix) {
            $roles = Role::all();
            foreach ($roles as $role) {
                // Skip super_admin permission sync (always gets all permissions)
                if ($role->slug === 'super_admin') {
                    $allPermissionIds = Permission::pluck('id')->toArray();
                    $role->permissions()->sync($allPermissionIds);

                    continue;
                }

                if ($role->slug === 'teacher') {
                    $viewOnlyPermissionId = Permission::where('slug', 'view_only')->value('id');
                    $role->permissions()->sync($viewOnlyPermissionId ? [$viewOnlyPermissionId] : []);

                    continue;
                }

                $selectedPermissionIds = isset($matrix[$role->id]) ? array_keys($matrix[$role->id]) : [];
                $role->permissions()->sync($selectedPermissionIds);
            }
        });

        AdminAuditLog::record('access_control_permissions_synced', true, 'Synchronized the role-permission mapping matrix.');

        return back()->with('success', 'Permission matrix updated successfully.');
    }

    // 3. Role Assignment
    public function assignmentIndex(Request $request)
    {
        $this->ensureSuperAdmin();

        $search = $request->query('search');

        $query = User::with('roles')
            ->where(function ($q) {
                $q->whereIn('role', ['admin', 'finance', 'staff'])
                    ->orWhereHas('roles', function ($r) {
                        $r->whereIn('slug', User::ADMIN_PORTAL_ROLE_SLUGS);
                    });
            });

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::orderBy('hierarchy_level', 'desc')->get();

        return view('admin.access-control.assignment.index', compact('users', 'roles', 'search'));
    }

    public function assignmentUpdate(Request $request, User $user)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Prevent self-modifying super_admin role
        if ($user->id === auth()->id() && ! in_array(Role::where('slug', 'super_admin')->first()?->id, $validated['role_ids'])) {
            return back()->withErrors(['error' => 'You cannot revoke your own Super Administrator privileges.']);
        }

        DB::transaction(function () use ($user, $validated) {
            $user->roles()->sync($validated['role_ids']);

            // Sync legacy role column (take the highest ranking role slug)
            $highestRole = Role::whereIn('id', $validated['role_ids'])
                ->orderBy('hierarchy_level', 'desc')
                ->first();

            if ($highestRole) {
                // Map super_admin back to admin for legacy role compatibility
                $legacySlug = match ($highestRole->slug) {
                    'super_admin' => 'admin',
                    'admin', 'finance', 'staff' => $highestRole->slug,
                    default => 'staff',
                };
                $user->update(['role' => $legacySlug]);
            }
        });

        AdminAuditLog::record('access_control_user_roles_updated', true, "Updated role mappings for user account: {$user->email}");

        return back()->with('success', "Access roles updated for {$user->name}.");
    }

    // 4. Access Policies
    public function policiesIndex()
    {
        $this->ensureSuperAdmin();

        return view('admin.access-control.policies.index');
    }
}
