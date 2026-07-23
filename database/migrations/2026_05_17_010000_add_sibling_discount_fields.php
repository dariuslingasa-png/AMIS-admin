<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('discount_settings')) {
            Schema::create('discount_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('second_child_percentage')->default(10);
                $table->unsignedTinyInteger('third_child_percentage')->default(15);
                $table->unsignedTinyInteger('fourth_child_percentage')->default(20);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('discount_settings')->insert([
                'second_child_percentage' => 10,
                'third_child_percentage' => 15,
                'fourth_child_percentage' => 20,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('enrollment_applicants') && ! Schema::hasColumn('enrollment_applicants', 'sibling_order')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->unsignedSmallInteger('sibling_order')->nullable()->after('last_step');
                $table->string('discount_type', 50)->nullable()->after('sibling_order');
                $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_type');
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percentage');
            });
        }

        if (Schema::hasTable('student_accounts') && ! Schema::hasColumn('student_accounts', 'sibling_order')) {
            Schema::table('student_accounts', function (Blueprint $table) {
                $table->unsignedSmallInteger('sibling_order')->nullable()->after('books_fee');
                $table->string('discount_type', 50)->nullable()->after('sibling_order');
                $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_type');
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percentage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_accounts') && Schema::hasColumn('student_accounts', 'sibling_order')) {
            Schema::table('student_accounts', function (Blueprint $table) {
                $table->dropColumn(['sibling_order', 'discount_type', 'discount_percentage', 'discount_amount']);
            });
        }

        if (Schema::hasTable('enrollment_applicants') && Schema::hasColumn('enrollment_applicants', 'sibling_order')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->dropColumn(['sibling_order', 'discount_type', 'discount_percentage', 'discount_amount']);
            });
        }

        Schema::dropIfExists('discount_settings');
    }
};
