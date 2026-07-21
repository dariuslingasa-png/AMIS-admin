<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('zk_attendance_remarks')) {
            Schema::create('zk_attendance_remarks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id')->index();
                $table->date('date')->index();
                $table->text('remark')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'date'], 'zk_remarks_unique_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zk_attendance_remarks');
    }
};
