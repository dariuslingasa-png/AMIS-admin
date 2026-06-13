<?php

namespace App\Console\Commands;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InsertStudentsFromList extends Command
{
    protected $signature = 'ms:insert-students';
    protected $description = 'Parse and insert student records from the school list into the database';

    public function handle(): int
    {
        $rawList = <<<LIST
K2	MURAJI, AYRAH JULHAMID	NEW ODL
1	ALIH, MARYAM KHAISA	NEW ODL
1	TULABIN, IBRAHIM TARANG	OLD ODL
1	LADDJABASSAL, ELHAM ABDURAJAK	OLD ODL
1	NAVEED, ZAIN AYYUB	OLD ODL
1	NATIVIDAD, MIKAEEL TEOXON	OLD ODL
1	MURAJI, AYMAAN JULHAMID	OLD ODL
1	MAGNO, KHALID MANGUDADATU	OLD ODL
1	MAKALUTANG, HOOR AL-AIN SALASAIN	OLD ODL
1	ARASAD, MALEEQA SALI	OLD ODL
2	CHAWAND WALA, ALI ELIZACH ABUBACAR	OLD ODL
2	KATOG, RADIYAH OLIMPAIN	NEW ODL
2	KATOG, RAYAH OLIMPAIN	NEW ODL
2	ARASAD, MALEEHA SALI	OLD ODL
2	ISMALI, ABDUL CARIM GABRIEL	OLD ODL
2	PANGANDAMAN, ASIYAH MUDAG	NEW ODL
2	AMPAC, MISHA NAILAH MUROD	OLD F2F
2	SEDOMAR, KENAN RYYAN NATIVIDAD	OLD F2F
2	JUHAN, AISHA FATIMA BALDOMERO	OLD F2F
3	BAYAN, AFEEFA BAROH	NEW ODL
3	ABDUL RAHMAN, ZAINAB HADJI ABUBACAR	OLD ODL
3	MILLS, SEBASTIAN CASSIEL LOZERO	OLD ODL
3	NAVEED, ANNIYA AYYUB	OLD ODL
3	NAVEED, OMER AYYUB	OLD ODL
3	NATIVIDAD, LAYYINA TEOXON	OLD ODL
3	ABBAS, AYEESHA CANACAN	OLD ODL
3	MAKALUTANG, IZZADDEEN SALASAIN	OLD ODL
3	APLAL, KHYLIE KEEM LUPON	OLD F2F
4	ABDUL RAHMAN, ZAREENAH HADJI ABUBACAR	OLD ODL
4	LADIMIR, SALMAN ABDURAJAK	OLD ODL
4	ABBAS, ZAHIR CANACAN	OLD ODL
4	MIRA-ATO, SHASMEEN	NEW ODL
4	MIRA-ATO, YUSUF MIRA-ATO	NEW ODL
4	MANGUDADATU, SHAMEEM SANGKI	OLD F2F
5	DIZON, PRINCE KHALIFA SANTOS	OLD ODL
5	SANTOS, MARYAM UTHMAN TOLENTINO	NEW ODL
5	UNDANG, ABDURRAHMAN MUKARRAMA	NEW ODL
6	SANTOS, SARAH UTHMAN TOLENTINO	NEW ODL
6	ARASAD, MESHAEL SALI	OLD ODL
6	ISMALI, ABDULAZIZ GABRIEL	OLD ODL
6	PANGANDAMAN, KHALIL MUDAG	NEW ODL
7	SAGAYNO, EID RHIAN CELESTIAL	OLD ODL
7	OLIS, GIULIANAH AGONIA	NEW ODL
7	ANDAYA, JOHANAH ABDUL MAULA	NEW ODL
7	SAID, RASHEED GUERERO	OLD ODL
8	ALZAHRANI, YARAH KUDARAT	OLD ODL
8	KATOG, ABDULRAHMAN OLIMPAIN	OLD ODL
8	JAMMANG, RAYYAN MANSARI	OLD ODL
8	TABAY, YANI VERA NOAH	OLD F2F
9	GANDAMRA, KHALILA ZOE DEBELEN	OLD ODL
10	ISSA, OMAR NASHAT LOCTON	OLD ODL
10	TABAY, JAN MATHEW ALEXANDER	OLD F2F
11	OLIS, KAMILAH CASSANDRA AGONIA	NEW ODL
11	MAGNO, RASHID MANGUDADATU	OLD ODL
11	TABAY, ALEXANDRA BRIELLE	NEW F2F
11	MAMA, HASANAH COMAYOG	OLD ODL
LIST;

        $lines = explode("\n", trim($rawList));
        $this->info("Found " . count($lines) . " students to insert.");

        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Split by tabs or double spaces
            $parts = preg_split('/\t+/', $line);
            if (count($parts) < 3) {
                $parts = preg_split('/\s{2,}/', $line);
            }

            if (count($parts) < 3) {
                $this->error("Skipped invalid line: {$line}");
                $skippedCount++;
                continue;
            }

            $gradeRaw = trim($parts[0]);
            $nameRaw = trim($parts[1]);
            $statusRaw = trim($parts[2]);

            // 1. Map Grade Level
            $grade = $gradeRaw;
            if ($gradeRaw === 'K2') {
                $grade = 'Kinder 2';
            } elseif (is_numeric($gradeRaw)) {
                $grade = 'Grade ' . $gradeRaw;
            }

            // 2. Map Status (NEW ODL, OLD F2F, etc.)
            $statusParts = explode(' ', $statusRaw);
            $type = 'New';
            if (isset($statusParts[0])) {
                $type = ucfirst(strtolower($statusParts[0]));
            }
            $mode = 'Flexible Online Learning';
            if (isset($statusParts[1]) && strtoupper($statusParts[1]) === 'F2F') {
                $mode = 'Face-to-Face';
            }

            // 3. Parse and Reorder Name
            $nameParts = explode(',', $nameRaw);
            $lastName = trim($nameParts[0]);
            $firstNamePart = isset($nameParts[1]) ? trim($nameParts[1]) : '';

            $firstNameWords = array_filter(explode(' ', $firstNamePart));
            if (count($firstNameWords) > 1) {
                $middleName = array_pop($firstNameWords);
                $firstName = implode(' ', $firstNameWords);
            } else {
                $firstName = $firstNamePart;
                $middleName = '';
            }

            // Uppercase formatting
            $firstName = mb_strtoupper($firstName, 'UTF-8');
            $lastName = mb_strtoupper($lastName, 'UTF-8');
            $middleName = mb_strtoupper($middleName, 'UTF-8');
            $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);

            // 4. Duplicate Check
            $alreadyExists = EnrollmentApplicant::where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->where('grade_level', $grade)
                ->exists();

            if ($alreadyExists) {
                $this->comment("Student already exists: {$fullName} ({$grade}) - Skipped");
                $skippedCount++;
                continue;
            }

            DB::transaction(function () use ($firstName, $lastName, $middleName, $fullName, $grade, $type, $mode, &$insertedCount) {
                // 5. Generate Student Number
                $num = 1;
                do {
                    $studentNumber = '26' . str_pad($num, 4, '0', STR_PAD_LEFT);
                    $existsNumber = Student::where('student_number', $studentNumber)->exists();
                    $num++;
                } while ($existsNumber);

                // 6. Generate Parent User (each student gets a unique parent shell account to avoid parent email duplicates)
                $lastNameClean = strtolower(preg_replace('/[^a-zA-Z]/', '', $lastName));
                $parentEmail = 'parent.' . $lastNameClean . '.' . rand(100, 999) . '@amis.test';
                $parentUsername = 'parent_' . $lastNameClean . '_' . rand(100, 999);

                while (User::where('email', $parentEmail)->orWhere('username', $parentUsername)->exists()) {
                    $parentEmail = 'parent.' . $lastNameClean . '.' . rand(100, 999) . '@amis.test';
                    $parentUsername = 'parent_' . $lastNameClean . '_' . rand(100, 999);
                }

                $parentId = DB::table('users')->insertGetId([
                    'name' => "FAMILY {$lastName}",
                    'email' => $parentEmail,
                    'username' => $parentUsername,
                    'password' => Hash::make('password'),
                    'role' => 'applicant',
                    'account_status' => 'verified',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 7. Generate School Email
                $firstLetterOfLastName = strtolower(substr(preg_replace('/[^a-zA-Z]/', '', $lastName), 0, 1));
                $firstGivenName = explode(' ', trim($firstName))[0];
                $firstNameClean = strtolower(preg_replace('/[^a-zA-Z]/', '', $firstGivenName));
                $mailNick = $studentNumber . $firstLetterOfLastName . $firstNameClean;
                $schoolEmail = $mailNick . '@amis.edu.ph';

                $suffix = 1;
                while (Student::where('school_email', $schoolEmail)->exists() || User::where('email', $schoolEmail)->exists()) {
                    $schoolEmail = $mailNick . $suffix . '@amis.edu.ph';
                    $suffix++;
                }

                // 8. Create EnrollmentApplicant
                $applicantId = DB::table('enrollment_applicants')->insertGetId([
                    'user_id' => $parentId,
                    'student_type' => $type,
                    'learning_mode' => $mode,
                    'lrn' => 'NA',
                    'grade_level' => $grade,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'middle_name' => $middleName,
                    'gender' => 'male', // default placeholder
                    'date_of_birth' => '2018-01-01',
                    'place_of_birth' => 'Davao City',
                    'religion' => 'Islam',
                    'country' => 'Philippines',
                    'address' => 'Davao City',
                    'mobile_number' => '+639000000000',
                    'parent_mobile' => '+639000000000',
                    'parent_email' => $parentEmail,
                    'emergency_name' => "FAMILY {$lastName}",
                    'emergency_relationship' => 'Parent',
                    'emergency_phone' => '+639000000000',
                    'school_year' => '2026-2027',
                    'last_step' => 7,
                    'status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 9. Generate Temp Password (following AMIS pattern: Amis@XXXXX99)
                $tempPassword = 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);

                // 10. Create Student
                Student::create([
                    'user_id' => $parentId,
                    'enrollment_applicant_id' => $applicantId,
                    'student_number' => $studentNumber,
                    'school_email' => $schoolEmail,
                    'ms_email' => $schoolEmail,
                    'temp_password' => $tempPassword,
                    'grade_level' => $grade,
                    'school_year' => '2026-2027',
                ]);

                $insertedCount++;
            });

            $this->info("Inserted student: {$fullName} ({$grade})");
        }

        $this->newLine();
        $this->info("Insertion complete: {$insertedCount} students inserted, {$skippedCount} skipped.");

        return Command::SUCCESS;
    }
}
