<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('enrolled_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('school_year')->nullable();
            $table->timestamps();
        });

        Schema::create('microsoft_teams', function (Blueprint $table) {
            $table->id();
            $table->string('microsoft_team_id')->unique();
            $table->string('display_name')->index();
            $table->text('description')->nullable();
            $table->string('visibility')->nullable();
            $table->string('team_category')->nullable();
            $table->unsignedBigInteger('school_year_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('member_count')->default(0);
            $table->unsignedInteger('owner_count')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('microsoft_team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microsoft_team_local_id')->constrained('microsoft_teams')->cascadeOnDelete();
            $table->string('identity_key');
            $table->string('microsoft_membership_id')->nullable();
            $table->string('entra_user_id')->nullable()->index();
            $table->string('tenant_id')->nullable();
            $table->string('display_name');
            $table->string('email')->nullable()->index();
            $table->string('user_principal_name')->nullable();
            $table->string('team_role')->default('unknown');
            $table->foreignId('local_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('local_faculty_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('account_type')->default('unknown');
            $table->string('match_method')->nullable();
            $table->string('match_status')->default('unmatched');
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            $table->unique(['microsoft_team_local_id', 'identity_key'], 'ms_team_member_identity_unique');
        });

        Schema::create('microsoft_team_section_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('microsoft_team_local_id')->unique()->constrained('microsoft_teams')->cascadeOnDelete();
            $table->unsignedBigInteger('school_year_id')->nullable();
            $table->unsignedBigInteger('grade_level_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('shift')->nullable();
            $table->string('gender_group')->nullable();
            $table->string('program_type')->nullable();
            $table->string('mapping_status')->default('pending');
            $table->string('mapping_method')->default('manual');
            $table->boolean('not_official_class')->default(false);
            $table->json('detection_payload')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('microsoft_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type')->default('full');
            $table->string('status')->default('queued');
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
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

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'Microsoft Roster Management',
            'slug' => 'microsoft_roster_management',
            'category' => 'microsoft_integration',
            'description' => 'Manage Microsoft roster review data.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('roles')->whereIn('slug', ['super_admin', 'admin'])->pluck('id') as $roleId) {
            DB::table('role_permission')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('microsoft_sync_runs');
        Schema::dropIfExists('microsoft_team_section_mappings');
        Schema::dropIfExists('microsoft_team_memberships');
        Schema::dropIfExists('microsoft_teams');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('school_years');
    }
};
