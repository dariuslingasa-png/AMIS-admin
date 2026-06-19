<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherDirectorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get all base teachers from config
        $advisories = collect(config('class_advisories', []))
            ->flatMap(function (array $rows, string $departmentKey) {
                if ($departmentKey === 'elementary') {
                    $department = 'Elementary Department';
                } elseif ($departmentKey === 'high_school') {
                    $department = 'High School Department';
                } else {
                    $department = 'Islamic School and Arabic Language Department';
                }

                return collect($rows)->map(fn (array $row) => $row + ['dept' => $department]);
            })
            ->values();

        // 2. Extra ODL teachers not in the advisor list
        $extraTeachers = [
            ['name' => 'Tchr. Katrina', 'dept' => 'Elementary Department'],
            ['name' => 'Tchr. Arvin', 'dept' => 'Elementary Department'],
            ['name' => 'Ust. Abdiraheem', 'dept' => 'Islamic School and Arabic Language Department'],
            ['name' => 'Ust. Silfah', 'dept' => 'Islamic School and Arabic Language Department'],
            ['name' => 'Ust. Ersahad', 'dept' => 'Islamic School and Arabic Language Department'],
        ];

        $allTeachers = $advisories->map(fn ($t) => [
            'name' => $t['teacher'] ?? $t['name'] ?? '',
            'dept' => $t['dept'] ?? 'Elementary Department',
        ])
        ->concat($extraTeachers)
        ->filter(fn ($t) => !empty($t['name']))
        ->unique('name')
        ->values();

        // 3. Load existing overrides
        $overridesPath = storage_path('app/academic_teacher_overrides.json');
        $overrides = [];
        if (File::exists($overridesPath)) {
            $overrides = json_decode((string) File::get($overridesPath), true) ?: [];
        }

        foreach ($allTeachers as $teacherInfo) {
            $name = $teacherInfo['name'];
            $dept = $teacherInfo['dept'];
            $id = Str::slug($name);

            // Generate email address using Laravel/cPanel matching logic
            $email = $this->teacherEmail($name);

            // Check if user already exists
            $user = User::where('role', 'teacher')
                ->where(function ($query) use ($email, $name) {
                    $query->where('email', $email)
                          ->orWhere('name', $name);
                })
                ->first();

            $password = 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'username' => $id,
                    'password' => Hash::make($password),
                    'role' => 'teacher',
                    'account_status' => 'verified',
                    'email_verified_at' => now(),
                ]);
                $this->command->info("Created user: {$name} ({$email}) with password: {$password}");
            } else {
                $password = $overrides[$id]['temporary_password'] ?? 'ExistingPassword';
            }

            // Ensure override entry exists
            if (!isset($overrides[$id])) {
                $overrides[$id] = [
                    'name' => $name,
                    'email' => $email,
                    'dept' => $dept,
                    'sections' => null,
                    'status' => 'Active',
                    'license' => 'faculty_a1',
                    'photo' => null,
                    'first_name' => '',
                    'middle_name' => '',
                    'last_name' => '',
                    'gender' => 'Male',
                    'birthdate' => '',
                    'contact_number' => '',
                    'address' => '',
                    'password_changed' => 'No',
                    'temporary_password' => $password === 'ExistingPassword' ? null : $password,
                    'microsoft_sync' => false, // Set to false to avoid trying to call MS Graph in seeder
                    'id' => $id,
                    'subjects' => [],
                ];
            }
        }

        // Save overrides back to JSON
        File::ensureDirectoryExists(dirname($overridesPath));
        File::put($overridesPath, json_encode($overrides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->command->info("Teacher overrides updated successfully.");
    }

    private function teacherEmail(string $name): string
    {
        $cleanName = Str::of($name)
            ->replaceMatches('/^(teacher|ust\.|ustadz\.?|ustadh\.?|sir\.?|ma\'am\.?|maam\.?|ms\.?|mrs\.?|mr\.?)\s+/i', '')
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z\s]/', '')
            ->squish();
        $parts = explode(' ', (string) $cleanName);

        return count($parts) >= 2 
            ? 'tr.' . substr($parts[0], 0, 1) . end($parts) . '@amis.edu.ph' 
            : 'tr.' . $cleanName . '@amis.edu.ph';
    }
}
