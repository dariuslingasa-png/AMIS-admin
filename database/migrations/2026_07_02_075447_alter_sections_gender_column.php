<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sections MODIFY COLUMN gender ENUM('male', 'female', 'na', 'merge') NOT NULL DEFAULT 'male'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sections MODIFY COLUMN gender ENUM('male', 'female') NOT NULL DEFAULT 'male'");
        }
    }
};
