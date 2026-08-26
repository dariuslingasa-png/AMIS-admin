<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // All fields that were NOT NULL but need to be nullable for draft saves
    private array $fields = [
        'student_type', 'learning_mode', 'grade_level',
        'first_name', 'last_name', 'middle_name',
        'gender', 'place_of_birth', 'religion', 'country',
        'mobile_number', 'parent_mobile',
        'emergency_name', 'emergency_relationship', 'emergency_phone',
    ];

    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            foreach ($this->fields as $field) {
                $table->string($field)->nullable()->change();
            }
            // date and text fields
            $table->date('date_of_birth')->nullable()->change();
            $table->text('address')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            foreach ($this->fields as $field) {
                $table->string($field)->nullable(false)->change();
            }
            $table->date('date_of_birth')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};
