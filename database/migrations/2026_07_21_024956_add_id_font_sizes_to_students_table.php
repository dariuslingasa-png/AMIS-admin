<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->float('id_last_name_font_size')->nullable()->after('student_id_url');
            $table->float('id_first_name_font_size')->nullable()->after('id_last_name_font_size');
            $table->float('id_grade_font_size')->nullable()->after('id_first_name_font_size');
            $table->float('id_num_font_size')->nullable()->after('id_grade_font_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'id_last_name_font_size',
                'id_first_name_font_size',
                'id_grade_font_size',
                'id_num_font_size',
            ]);
        });
    }
};
