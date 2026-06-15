<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('roles')->updateOrInsert(
            ['slug' => 'teacher'],
            [
                'name' => 'Teacher',
                'description' => 'View-only access to enrollment applications and student records.',
                'hierarchy_level' => 10,
                'is_protected' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $roleId = DB::table('roles')->where('slug', 'teacher')->value('id');
        $viewOnlyPermissionId = DB::table('permissions')->where('slug', 'view_only')->value('id');

        if ($roleId && $viewOnlyPermissionId) {
            DB::table('role_permission')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $viewOnlyPermissionId,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleId = DB::table('roles')->where('slug', 'teacher')->value('id');

        if ($roleId && Schema::hasTable('role_permission')) {
            DB::table('role_permission')->where('role_id', $roleId)->delete();
        }

        if ($roleId && Schema::hasTable('role_user')) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
        }

        DB::table('roles')->where('slug', 'teacher')->delete();
    }
};
