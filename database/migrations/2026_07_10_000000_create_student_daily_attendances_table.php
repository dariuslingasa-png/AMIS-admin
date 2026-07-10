<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_daily_attendances')) {
            Schema::create('student_daily_attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->date('date')->index();
                $table->time('time_in')->nullable();
                $table->time('time_out')->nullable();
                $table->string('status', 30)->default('PRESENT'); // PRESENT, LATE, ABSENT
                $table->string('remarks')->nullable();
                $table->timestamps();

                $table->unique(['student_id', 'date'], 'student_daily_attendance_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_daily_attendances');
    }
};
