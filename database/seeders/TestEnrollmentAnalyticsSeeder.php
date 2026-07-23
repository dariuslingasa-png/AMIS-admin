<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TestEnrollmentAnalyticsSeeder extends Seeder
{
    private const SCHOOL_YEAR = '2026-2027';

    private const COUNT = 420;

    public function run(): void
    {
        $now = now();
        $grades = [
            'Kinder 1', 'Kinder 2',
            'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
        ];
        $modes = ['Face-to-Face', 'Flexible Online Learning - 1st Shift', 'Flexible Online Learning - 2nd Shift'];
        $countries = ['Philippines', 'Saudi Arabia', 'United Arab Emirates', 'Qatar', 'Kuwait', 'Malaysia'];
        $cities = ['Davao City', 'Cotabato City', 'Manila', 'Jeddah', 'Riyadh', 'Dubai', 'Doha', 'Kuala Lumpur'];
        $lastNames = ['Abdullah', 'Ali', 'Ampatuan', 'Bashier', 'Dimaporo', 'Ebrahim', 'Hassan', 'Ibrahim', 'Lingasa', 'Macarambon', 'Musa', 'Pindaton', 'Rasul', 'Sali'];
        $firstNames = ['Ahmad', 'Aisha', 'Ameer', 'Fatima', 'Hana', 'Ibrahim', 'Jamila', 'Khadija', 'Mariam', 'Mohammad', 'Nora', 'Omar', 'Sofia', 'Yusuf'];

        $this->removePreviousSeedData();
        $slotCounters = [];

        $i = 1;
        $familyNo = 1;

        while ($i <= self::COUNT) {
            $lastName = $lastNames[array_rand($lastNames)];
            $country = $countries[array_rand($countries)];
            $city = $cities[array_rand($cities)];
            $familyEmail = 'seed-family-'.str_pad((string) $familyNo, 3, '0', STR_PAD_LEFT).'@amis.test';
            $parentEmail = 'parent-family-'.str_pad((string) $familyNo, 3, '0', STR_PAD_LEFT).'@amis.test';
            $parentMobile = '900'.random_int(1000000, 9999999);
            $familySize = min($this->familySize($familyNo), self::COUNT - $i + 1);

            $userId = DB::table('users')->insertGetId([
                'name' => "Family {$lastName}",
                'email' => $familyEmail,
                'username' => 'seedfamily'.str_pad((string) $familyNo, 3, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'role' => 'applicant',
                'account_status' => 'verified',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $familyApplicationId = null;

            for ($child = 1; $child <= $familySize; $child++, $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $middleName = chr(64 + (($i % 26) ?: 26));
                $grade = $grades[($i - 1) % count($grades)];
                $mode = $modes[array_rand($modes)];
                $email = 'seed-applicant-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@amis.test';
                $studentType = $this->studentType($i, $child);

                $applicantId = DB::table('enrollment_applicants')->insertGetId($this->applicantPayload(
                    $userId,
                    $familyApplicationId,
                    $email,
                    $parentEmail,
                    $parentMobile,
                    $firstName,
                    $lastName,
                    $middleName,
                    $studentType,
                    $grade,
                    $mode,
                    $country,
                    $city,
                    $now
                ));

                if (! $familyApplicationId) {
                    $familyApplicationId = $applicantId;
                    DB::table('enrollment_applicants')
                        ->where('id', $applicantId)
                        ->update(['family_application_id' => $familyApplicationId]);
                }

                $slotCounters[$grade][$mode] = ($slotCounters[$grade][$mode] ?? 0) + 1;
            }

            $familyNo++;
        }

        $this->syncSlotCounts($slotCounters);

        $this->command?->info(self::COUNT.' test enrollment applicants created for analytics.');
    }

    private function applicantPayload(
        int $userId,
        ?int $familyApplicationId,
        string $email,
        string $parentEmail,
        string $parentMobile,
        string $firstName,
        string $lastName,
        string $middleName,
        string $studentType,
        string $grade,
        string $mode,
        string $country,
        string $city,
        $now
    ): array {
        return [
            'user_id' => $userId,
            'family_application_id' => $familyApplicationId,
            'student_type' => $studentType,
            'amis_student_id' => $studentType === 'Old' ? 'AMIS-'.self::SCHOOL_YEAR.'-'.random_int(1000, 9999) : null,
            'learning_mode' => $mode,
            'timezone' => $mode === 'Face-to-Face' ? null : 'Asia/Manila',
            'lrn' => 'NA',
            'grade_level' => $grade,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'gender' => $this->randomGender(),
            'date_of_birth' => now()->subYears(random_int(6, 17))->subDays(random_int(0, 300))->toDateString(),
            'place_of_birth' => $city,
            'religion' => 'Islam',
            'ethnicity' => 'MARANAO',
            'country' => $country,
            'state_province' => $country === 'Philippines' ? 'Davao del Sur' : $country,
            'city' => $city,
            'street_address' => 'Test Street',
            'postal_code' => '8000',
            'address' => "Test Street, {$city}, {$country}",
            'email' => $email,
            'mobile_country_code' => '+63',
            'mobile_number' => '900'.random_int(1000000, 9999999),
            'father_last_name' => $lastName,
            'father_first_name' => 'Mohammad',
            'father_middle_name' => 'A',
            'father_occupation' => 'Business Owner',
            'mother_last_name' => $lastName,
            'mother_first_name' => 'Aisha',
            'mother_middle_name' => 'B',
            'mother_occupation' => 'Parent',
            'parent_country_code' => '+63',
            'parent_mobile' => $parentMobile,
            'parent_email' => $parentEmail,
            'home_address' => "Home Street, {$city}, {$country}",
            'emergency_name' => 'Parent '.Str::title($lastName),
            'emergency_relationship' => 'Parent',
            'emergency_phone' => '900'.random_int(1000000, 9999999),
            'medical_has_concern' => false,
            'school_year' => self::SCHOOL_YEAR,
            'last_step' => 7,
            'status' => 'submitted',
            'created_at' => $now->copy()->subDays(random_int(0, 21))->subMinutes(random_int(0, 600)),
            'updated_at' => $now,
        ];
    }

    private function familySize(int $familyNo): int
    {
        if ($familyNo % 5 === 0) {
            return 3;
        }

        return $familyNo % 3 === 0 ? 2 : 1;
    }

    private function studentType(int $index, int $childOrder): string
    {
        return ($index % 4 === 0 || $childOrder > 1 && $index % 3 === 0) ? 'Old' : 'New';
    }

    private function randomGender(): string
    {
        return random_int(0, 1) === 1 ? 'male' : 'female';
    }

    private function removePreviousSeedData(): void
    {
        $userIds = DB::table('users')
            ->where('email', 'like', 'seed-applicant-%@amis.test')
            ->orWhere('email', 'like', 'seed-family-%@amis.test')
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('payments')->whereIn('user_id', $userIds)->delete();
        DB::table('enrollment_applicants')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    private function syncSlotCounts(array $slotCounters): void
    {
        if (! Schema::hasTable('grade_levels')) {
            return;
        }

        foreach ($slotCounters as $grade => $modes) {
            $faceToFaceCount = (int) ($modes['Face-to-Face'] ?? 0);

            DB::table('grade_levels')
                ->where('name', $grade)
                ->where('school_year', self::SCHOOL_YEAR)
                ->update(['enrolled_count' => $faceToFaceCount]);

            if (! Schema::hasTable('grade_shift_slots') || ! Schema::hasTable('enrollment_shifts')) {
                continue;
            }

            foreach (['1st Shift', '2nd Shift'] as $shiftName) {
                $shiftCount = (int) ($modes["Flexible Online Learning - {$shiftName}"] ?? 0);
                $gradeId = DB::table('grade_levels')
                    ->where('name', $grade)
                    ->where('school_year', self::SCHOOL_YEAR)
                    ->value('id');
                $shiftId = DB::table('enrollment_shifts')
                    ->where('name', $shiftName)
                    ->where('school_year', self::SCHOOL_YEAR)
                    ->value('id');

                if ($gradeId && $shiftId) {
                    DB::table('grade_shift_slots')
                        ->where('grade_level_id', $gradeId)
                        ->where('enrollment_shift_id', $shiftId)
                        ->where('school_year', self::SCHOOL_YEAR)
                        ->update(['enrolled_count' => $shiftCount]);
                }
            }
        }
    }
}
