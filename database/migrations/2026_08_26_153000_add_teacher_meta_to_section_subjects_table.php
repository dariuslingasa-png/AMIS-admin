<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('section_subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('section_subjects', 'teacher_key')) {
                $table->string('teacher_key')->nullable()->after('teacher_name');
            }
            if (!Schema::hasColumn('section_subjects', 'teacher_photo')) {
                $table->string('teacher_photo')->nullable()->after('teacher_key');
            }
            if (!Schema::hasColumn('section_subjects', 'teacher_email')) {
                $table->string('teacher_email')->nullable()->after('teacher_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('section_subjects', function (Blueprint $table) {
            $table->dropColumn(['teacher_key', 'teacher_photo', 'teacher_email']);
        });
    }
};
