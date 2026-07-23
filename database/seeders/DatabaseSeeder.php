<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(['email' => 'admin@amis.edu.ph'], [
            'name' => 'AMIS Admin',
            'username' => 'admin',
            'password' => Hash::make('123'),
            'role' => 'admin',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'username' => 'testuser',
            'password' => Hash::make('password'),
            'role' => 'applicant',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);

        $this->call([
            SchoolFeesSeeder::class,
            WorkflowTestingSeeder::class,
            SubjectsSeeder::class,
        ]);
    }
}
