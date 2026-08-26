<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('microsoft_teams', function (Blueprint $table) {
            $table->id();
            $table->string('microsoft_team_id')->unique();
            $table->string('display_name')->index();
            $table->text('description')->nullable();
            $table->string('visibility', 30)->nullable()->index();
            $table->string('team_category', 30)->nullable()->index();
            $table->unsignedBigInteger('school_year_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('member_count')->default(0);
            $table->unsignedInteger('owner_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('microsoft_team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microsoft_team_local_id')->constrained('microsoft_teams')->cascadeOnDelete();
            $table->string('identity_key');
            $table->string('microsoft_membership_id')->nullable()->index();
            $table->string('entra_user_id')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->string('display_name');
            $table->string('email')->nullable()->index();
            $table->string('user_principal_name')->nullable()->index();
            $table->string('team_role', 20)->default('unknown')->index();
            $table->foreignId('local_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('local_faculty_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('account_type', 20)->default('unknown')->index();
            $table->string('match_method', 40)->nullable();
            $table->string('match_status', 30)->default('unmatched')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('removed_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['microsoft_team_local_id', 'identity_key'], 'ms_team_member_identity_unique');
            $table->index(['is_active', 'match_status'], 'ms_member_active_match_index');
        });

        Schema::create('microsoft_team_section_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microsoft_team_local_id')->unique()->constrained('microsoft_teams')->cascadeOnDelete();
            $table->unsignedBigInteger('school_year_id')->nullable()->index();
            $table->unsignedBigInteger('grade_level_id')->nullable()->index();
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->string('shift', 30)->nullable();
            $table->string('gender_group', 20)->nullable();
            $table->string('program_type', 20)->nullable();
            $table->string('mapping_status', 20)->default('pending')->index();
            $table->string('mapping_method', 20)->default('manual');
            $table->boolean('not_official_class')->default(false);
            $table->json('detection_payload')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('microsoft_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type', 30)->default('full');
            $table->string('status', 30)->default('queued')->index();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('teams_discovered')->default(0);
            $table->unsignedInteger('teams_processed')->default(0);
            $table->unsignedInteger('members_discovered')->default(0);
            $table->unsignedInteger('matched_students')->default(0);
            $table->unsignedInteger('matched_faculty')->default(0);
            $table->unsignedInteger('unmatched_accounts')->default(0);
            $table->unsignedInteger('new_memberships')->default(0);
            $table->unsignedInteger('removed_memberships')->default(0);
            $table->unsignedInteger('failed_teams')->default(0);
            $table->text('error_summary')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'microsoft_roster_management'],
                [
                    'name' => 'Microsoft Roster Management',
                    'category' => 'microsoft_integration',
                    'description' => 'Review, synchronize, map, and export Microsoft Teams roster data.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $permissionId = DB::table('permissions')->where('slug', 'microsoft_roster_management')->value('id');
            $roleIds = DB::table('roles')->whereIn('slug', ['super_admin', 'admin'])->pluck('id');

            foreach ($roleIds as $roleId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionId = DB::table('permissions')->where('slug', 'microsoft_roster_management')->value('id');
            if ($permissionId && Schema::hasTable('role_permission')) {
                DB::table('role_permission')->where('permission_id', $permissionId)->delete();
            }
            DB::table('permissions')->where('slug', 'microsoft_roster_management')->delete();
        }

        Schema::dropIfExists('microsoft_sync_runs');
        Schema::dropIfExists('microsoft_team_section_mappings');
        Schema::dropIfExists('microsoft_team_memberships');
        Schema::dropIfExists('microsoft_teams');
    }
};
