<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enrollment_applicants')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $this->stringColumn($table, 'timezone', 'learning_mode');
                $this->stringColumn($table, 'ethnicity', 'religion');
                $this->stringColumn($table, 'state_province', 'country');
                $this->stringColumn($table, 'city', 'state_province');
                $this->stringColumn($table, 'street_address', 'city');
                $this->stringColumn($table, 'postal_code', 'street_address');
                $this->stringColumn($table, 'mobile_country_code', 'email', 12);
                $this->stringColumn($table, 'parent_country_code', 'mother_occupation', 12);
                $this->stringColumn($table, 'home_state_province', 'home_address');
                $this->stringColumn($table, 'home_city', 'home_state_province');
                $this->stringColumn($table, 'home_street_address', 'home_city');
                $this->stringColumn($table, 'home_postal_code', 'home_street_address');
                $this->booleanColumn($table, 'medical_has_concern', 'parent_email');
                $this->textColumn($table, 'allergies', 'medical_has_concern');
                $this->textColumn($table, 'current_medications', 'allergies');
                $this->textColumn($table, 'health_conditions', 'current_medications');
                $this->textColumn($table, 'emergency_instructions', 'health_conditions');
                $this->textColumn($table, 'medical_history', 'emergency_instructions');
                $this->stringColumn($table, 'affidavit_url', 'medical_record_url');
                $this->jsonColumn($table, 'document_statuses', 'affidavit_url');
            });

            if (DB::getDriverName() === 'mysql' && Schema::hasColumn('enrollment_applicants', 'status')) {
                DB::statement("ALTER TABLE enrollment_applicants MODIFY COLUMN status ENUM('draft','ready_for_submission','pending','submitted','under_review','approved','rejected') NOT NULL DEFAULT 'draft'");
            }
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $this->stringColumn($table, 'reference_no', 'method', 100);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'reference_no')) {
            Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('reference_no'));
        }

        if (Schema::hasTable('enrollment_applicants')) {
            $columns = [
                'timezone',
                'ethnicity',
                'state_province',
                'city',
                'street_address',
                'postal_code',
                'mobile_country_code',
                'parent_country_code',
                'home_state_province',
                'home_city',
                'home_street_address',
                'home_postal_code',
                'medical_has_concern',
                'allergies',
                'current_medications',
                'health_conditions',
                'emergency_instructions',
                'medical_history',
                'affidavit_url',
            ];

            Schema::table('enrollment_applicants', function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn('enrollment_applicants', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function stringColumn(Blueprint $table, string $name, string $after, int $length = 255): void
    {
        if (! Schema::hasColumn($table->getTable(), $name)) {
            $column = $table->string($name, $length)->nullable();
            if (Schema::hasColumn($table->getTable(), $after)) {
                $column->after($after);
            }
        }
    }

    private function textColumn(Blueprint $table, string $name, string $after): void
    {
        if (! Schema::hasColumn($table->getTable(), $name)) {
            $column = $table->text($name)->nullable();
            if (Schema::hasColumn($table->getTable(), $after)) {
                $column->after($after);
            }
        }
    }

    private function booleanColumn(Blueprint $table, string $name, string $after): void
    {
        if (! Schema::hasColumn($table->getTable(), $name)) {
            $column = $table->boolean($name)->nullable();
            if (Schema::hasColumn($table->getTable(), $after)) {
                $column->after($after);
            }
        }
    }

    private function jsonColumn(Blueprint $table, string $name, string $after): void
    {
        if (! Schema::hasColumn($table->getTable(), $name)) {
            $column = $table->json($name)->nullable();
            if (Schema::hasColumn($table->getTable(), $after)) {
                $column->after($after);
            }
        }
    }
};
