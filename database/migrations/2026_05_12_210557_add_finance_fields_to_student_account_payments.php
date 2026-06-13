<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_account_payments', function (Blueprint $table) {
            $table->string('checked_by', 100)->nullable()->after('reference_no');       // e.g. "Sir Cabel"
            $table->string('account_received', 100)->nullable()->after('checked_by');   // e.g. "GCash 0917-xxx", "BDO xxxx"
        });
    }

    public function down(): void
    {
        Schema::table('student_account_payments', function (Blueprint $table) {
            $table->dropColumn(['checked_by', 'account_received']);
        });
    }
};
