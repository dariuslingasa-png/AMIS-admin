<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Uppercase all user names
        if (Schema::hasTable('users')) {
            DB::table('users')->get()->each(function ($user) {
                if ($user->name) {
                    DB::table('users')->where('id', $user->id)->update([
                        'name' => mb_strtoupper($user->name, 'UTF-8'),
                    ]);
                }
            });
        }

        // Uppercase all enrollment applicant names
        if (Schema::hasTable('enrollment_applicants')) {
            DB::table('enrollment_applicants')->get()->each(function ($applicant) {
                $updates = [];
                if ($applicant->first_name) {
                    $updates['first_name'] = mb_strtoupper($applicant->first_name, 'UTF-8');
                }
                if ($applicant->middle_name) {
                    $updates['middle_name'] = mb_strtoupper($applicant->middle_name, 'UTF-8');
                }
                if ($applicant->last_name) {
                    $updates['last_name'] = mb_strtoupper($applicant->last_name, 'UTF-8');
                }

                if (! empty($updates)) {
                    DB::table('enrollment_applicants')->where('id', $applicant->id)->update($updates);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data formatting change
    }
};
