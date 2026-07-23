<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('zk_departments')) {
            Schema::create('zk_departments', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary(); // ZKTeco ID
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('zk_users')) {
            Schema::create('zk_users', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->primary(); // ZKTeco ID
                $table->string('name')->nullable();
                $table->unsignedInteger('department_id')->nullable()->index();
                $table->string('card_number', 100)->nullable();
                $table->unsignedTinyInteger('privilege')->default(0);
                $table->string('password', 100)->nullable();
                $table->unsignedTinyInteger('status')->default(0);
                $table->text('raw_bytes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('zk_attendance_logs')) {
            Schema::create('zk_attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id')->index();
                $table->dateTime('datetime')->index();
                $table->unsignedTinyInteger('verify_mode')->default(0);
                $table->unsignedTinyInteger('in_out_mode')->default(0);
                $table->unsignedInteger('work_code')->default(0);
                $table->unsignedInteger('reserved')->default(0);
                $table->timestamps();

                $table->unique(['employee_id', 'datetime'], 'zk_log_unique_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zk_attendance_logs');
        Schema::dropIfExists('zk_users');
        Schema::dropIfExists('zk_departments');
    }
};
