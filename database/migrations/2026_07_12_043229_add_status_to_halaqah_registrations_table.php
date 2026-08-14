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
        Schema::table('halaqah_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('halaqah_registrations', 'status')) {
                $table->string('status')->default('new')->after('message');
            }
            if (! Schema::hasColumn('halaqah_registrations', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('halaqah_registrations', function (Blueprint $table) {
            $table->dropColumn(['status', 'responded_at']);
        });
    }
};
