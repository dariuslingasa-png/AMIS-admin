<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

$table = 'enrollment_applicants';

if (! Schema::hasTable($table)) {
    echo "Table '{$table}' does not exist. Please run base migrations first.\n";
    exit(1);
}

Schema::table($table, function (Blueprint $tableGroup) use ($table) {
    if (! Schema::hasColumn($table, 'family_application_id')) {
        $tableGroup->unsignedBigInteger('family_application_id')->nullable()->after('user_id')->index();
        echo "Added 'family_application_id' column.\n";
    }
    if (! Schema::hasColumn($table, 'amis_student_id')) {
        $tableGroup->string('amis_student_id')->nullable()->after('student_type');
        echo "Added 'amis_student_id' column.\n";
    }
    if (! Schema::hasColumn($table, 'review_remarks')) {
        $tableGroup->text('review_remarks')->nullable()->after('document_statuses');
        echo "Added 'review_remarks' column.\n";
    }
    if (! Schema::hasColumn($table, 'sibling_order')) {
        $tableGroup->integer('sibling_order')->nullable()->after('last_step');
        echo "Added 'sibling_order' column.\n";
    }
    if (! Schema::hasColumn($table, 'discount_type')) {
        $tableGroup->string('discount_type')->nullable()->after('sibling_order');
        echo "Added 'discount_type' column.\n";
    }
    if (! Schema::hasColumn($table, 'discount_percentage')) {
        $tableGroup->decimal('discount_percentage', 5, 2)->nullable()->after('discount_type');
        echo "Added 'discount_percentage' column.\n";
    }
    if (! Schema::hasColumn($table, 'discount_amount')) {
        $tableGroup->decimal('discount_amount', 10, 2)->nullable()->after('discount_percentage');
        echo "Added 'discount_amount' column.\n";
    }
});

echo "Database columns check completed.\n";
