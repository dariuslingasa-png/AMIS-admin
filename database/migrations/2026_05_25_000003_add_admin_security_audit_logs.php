<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'active_admin_session_id')) {
                $table->string('active_admin_session_id')->nullable()->after('access_permissions');
            }
            if (! Schema::hasColumn('users', 'last_admin_login_at')) {
                $table->timestamp('last_admin_login_at')->nullable()->after('active_admin_session_id');
            }
        });

        if (! Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('event');
                $table->string('email')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->boolean('successful')->default(false);
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_admin_login_at')) {
                $table->dropColumn('last_admin_login_at');
            }
            if (Schema::hasColumn('users', 'active_admin_session_id')) {
                $table->dropColumn('active_admin_session_id');
            }
        });
    }
};
