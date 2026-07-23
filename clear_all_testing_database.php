<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting clean of Enrollment, Students, and SOA database tables...\n";

try {
    Schema::disableForeignKeyConstraints();

    // Truncate Student & Section maps
    echo "Truncating student_sections...\n";
    DB::table('student_sections')->truncate();

    // Truncate Students
    echo "Truncating students...\n";
    DB::table('students')->truncate();

    // Truncate Payments
    echo "Truncating payments...\n";
    DB::table('payments')->truncate();

    // Truncate Enrollment Applicants
    echo "Truncating enrollment_applicants...\n";
    DB::table('enrollment_applicants')->truncate();

    // Truncate Invoices & Advance Payments if tables exist
    if (Schema::hasTable('advance_payment_applications')) {
        echo "Truncating advance_payment_applications...\n";
        DB::table('advance_payment_applications')->truncate();
    }
    if (Schema::hasTable('advance_payments')) {
        echo "Truncating advance_payments...\n";
        DB::table('advance_payments')->truncate();
    }
    if (Schema::hasTable('invoices')) {
        echo "Truncating invoices...\n";
        DB::table('invoices')->truncate();
    }

    // Truncate SOA tables
    echo "Truncating student_account_payments...\n";
    DB::table('student_account_payments')->truncate();

    echo "Truncating soa_monthly_billings...\n";
    DB::table('soa_monthly_billings')->truncate();

    echo "Truncating student_accounts...\n";
    DB::table('student_accounts')->truncate();

    // Clean up Users (Delete all except admin and test)
    echo "Deleting parent/student users (keeping admin)...\n";
    DB::table('users')
        ->whereNotIn('email', ['admin@amis.edu.ph', 'test@example.com'])
        ->delete();

    Schema::enableForeignKeyConstraints();

    echo "SUCCESS: All Enrollment, Student, Section links, and SOA tables successfully cleared! Database is completely empty and ready for fresh end-to-end testing.\n";
} catch (Exception $e) {
    echo 'ERROR during database cleanup: '.$e->getMessage()."\n";
}
