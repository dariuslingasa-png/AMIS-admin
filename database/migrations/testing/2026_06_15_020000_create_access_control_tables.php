<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('hierarchy_level')->default(0);
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        // 2. Create Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('system');
            $table->timestamps();
        });

        // 3. Create Role_Permission Pivot Table
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Create Role_User Pivot Table
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->primary(['role_id', 'user_id']);
        });

        // 5. Seed Default Roles
        $roles = [
            [
                'name' => 'Super Administrator',
                'slug' => 'super_admin',
                'description' => 'Unrestricted portal control and security overrides.',
                'hierarchy_level' => 100,
                'is_protected' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'System Administrator',
                'slug' => 'admin',
                'description' => 'Full admin portal access, account status, and backups.',
                'hierarchy_level' => 80,
                'is_protected' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance Officer',
                'slug' => 'finance',
                'description' => 'Manage payments, SOAs, fee and discount parameters.',
                'hierarchy_level' => 50,
                'is_protected' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Staff Member',
                'slug' => 'staff',
                'description' => 'View-only operations access.',
                'hierarchy_level' => 10,
                'is_protected' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('roles')->insert($roles);

        // 6. Seed Default Permissions
        $permissions = [
            ['name' => 'Payment Approval', 'slug' => 'payment_review', 'category' => 'finance', 'description' => 'Approve or reject student payments.'],
            ['name' => 'Document Review', 'slug' => 'document_review', 'category' => 'applications', 'description' => 'Verify and review application attachments.'],
            ['name' => 'User Management', 'slug' => 'user_management', 'category' => 'administration', 'description' => 'Create, edit, and suspend administrative accounts.'],
            ['name' => 'Role Management', 'slug' => 'role_management', 'category' => 'access_control', 'description' => 'Manage role details and matrix access mappings.'],
            ['name' => 'Backup Operations', 'slug' => 'backup_management', 'category' => 'system', 'description' => 'Run manual database dumps and full system zip backups.'],
            ['name' => 'System Health Checks', 'slug' => 'system_health_check', 'category' => 'system', 'description' => 'Read diagnostic indicators for databases, storage, and APIs.'],
            ['name' => 'View-Only Mode', 'slug' => 'view_only', 'category' => 'general', 'description' => 'Disable all create, update, and delete actions across the portal.'],
        ];
        foreach ($permissions as &$perm) {
            $perm['created_at'] = now();
            $perm['updated_at'] = now();
        }
        DB::table('permissions')->insert($permissions);

        // 7. Sync Permissions to Roles
        $roleIds = DB::table('roles')->pluck('id', 'slug')->toArray();
        $permIds = DB::table('permissions')->pluck('id', 'slug')->toArray();

        // super_admin -> gets all permissions
        $pivotData = [];
        foreach ($permIds as $slug => $pid) {
            $pivotData[] = ['role_id' => $roleIds['super_admin'], 'permission_id' => $pid];
        }
        // admin -> gets all except payment_review & view_only
        $adminPerms = ['document_review', 'user_management', 'role_management', 'backup_management', 'system_health_check'];
        foreach ($adminPerms as $slug) {
            $pivotData[] = ['role_id' => $roleIds['admin'], 'permission_id' => $permIds[$slug]];
        }
        // finance -> gets payment_review
        $pivotData[] = ['role_id' => $roleIds['finance'], 'permission_id' => $permIds['payment_review']];
        // staff -> gets view_only
        $pivotData[] = ['role_id' => $roleIds['staff'], 'permission_id' => $permIds['view_only']];

        DB::table('role_permission')->insert($pivotData);

        // 8. Backfill existing users
        if (Schema::hasTable('users')) {
            $users = DB::table('users')->get();
            $userRolePivot = [];

            // Detect first admin as super admin
            $firstAdminId = null;

            foreach ($users as $user) {
                // Skip if role is empty or not in portal roles
                if (empty($user->role) || !in_array($user->role, ['admin', 'finance', 'staff'])) {
                    continue;
                }

                $mappedRole = $user->role;

                // Promote the first admin user to super_admin
                if ($user->role === 'admin') {
                    if ($firstAdminId === null) {
                        $firstAdminId = $user->id;
                        $mappedRole = 'super_admin';
                    }
                }

                if (isset($roleIds[$mappedRole])) {
                    $userRolePivot[] = [
                        'user_id' => $user->id,
                        'role_id' => $roleIds[$mappedRole]
                    ];
                }
            }

            if (!empty($userRolePivot)) {
                DB::table('role_user')->insert($userRolePivot);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
