<?php

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
    $app = require_once __DIR__.'/../bootstrap/app.php';
}
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Student;
use App\Models\StudentSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "=== 1. SYNCING TEACHERS FROM OVERRIDES JSON ===\n";

$overridesPath = storage_path('app/academic_teacher_overrides.json');
if (!file_exists($overridesPath)) {
    $adminOverrides = base_path('../amis_admin/storage/app/academic_teacher_overrides.json');
    if (file_exists($adminOverrides)) {
        copy($adminOverrides, $overridesPath);
    }
}

$overrides = file_exists($overridesPath) ? (json_decode(file_get_contents($overridesPath), true) ?: []) : [];
echo "Loaded " . count($overrides) . " teacher profiles.\n";

foreach ($overrides as $key => $t) {
    $email = $t['email'] ?? null;
    $name = $t['name'] ?? $key;
    if (!$email) continue;
    
    $user = User::where('email', $email)->orWhere('username', $key)->first();
    if (!$user) {
        User::create([
            'name' => $name,
            'email' => $email,
            'username' => $key,
            'password' => Hash::make('Amis@Teacher2026'),
            'role' => 'teacher',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);
        echo "Created teacher user: {$name} ({$email})\n";
    } else {
        $user->update([
            'name' => $name,
            'role' => 'teacher',
            'account_status' => 'verified',
        ]);
    }
}

echo "\n=== 2. UPDATING STUDENT MON ZHAIREL LINGASA & SECTION ===\n";

$studentUser = User::where('email', 'sir_monlingasa@amis.edu.ph')->first();
if ($studentUser) {
    $student = Student::where('user_id', $studentUser->id)->first();
    if ($student) {
        // Ensure applicant learning_mode is Flexible Online Learning
        if ($student->applicant) {
            $student->applicant->update([
                'learning_mode' => 'Flexible Online Learning',
                'grade_level' => 'Grade 1',
            ]);
        }
        
        // Find or update Section
        $section = DB::table('sections')->where('name', 'G1-AL-MUNAWWARA')->first();
        if (!$section) {
            $sectionId = DB::table('sections')->insertGetId([
                'name' => 'G1-AL-MUNAWWARA',
                'grade_level' => 'Grade 1',
                'learning_mode' => 'Flexible Online Learning',
                'shift' => 'Morning',
                'gender' => 'merge',
                'academic_status' => 'active',
                'schedule_published' => 1,
                'ms_team_id' => 'team_g1_munawwara',
                'ms_team_url' => 'https://teams.microsoft.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $section = DB::table('sections')->where('id', $sectionId)->first();
        } else {
            DB::table('sections')->where('id', $section->id)->update([
                'learning_mode' => 'Flexible Online Learning',
                'schedule_published' => 1,
                'grade_level' => 'Grade 1',
                'updated_at' => now(),
            ]);
        }
        
        // Update student section link
        $student->update([
            'grade_level' => 'Grade 1',
            'section' => $section->name,
            'school_year' => '2026-2027',
        ]);
        
        $ss = StudentSection::where('student_id', $student->id)->first();
        if ($ss) {
            $ss->update([
                'section_id' => $section->id,
                'ms_status' => 'enrolled',
            ]);
        } else {
            StudentSection::create([
                'student_id' => $student->id,
                'section_id' => $section->id,
                'ms_status' => 'enrolled',
            ]);
        }
        
        echo "Updated student {$studentUser->name} -> Section {$section->name} (ID: {$section->id})\n";
        
        echo "\n=== 3. POPULATING CLASS SCHEDULES & SECTION SUBJECTS ===\n";
        
        // Source schedules from Grade 1 template (Section 133 or fallback array)
        $sourceScheds = DB::table('class_schedules')->where('section_id', 133)->get();
        if ($sourceScheds->isEmpty()) {
            $sourceScheds = DB::table('class_schedules')->where('section_id', 134)->get();
        }
        
        DB::table('class_schedules')->where('section_id', $section->id)->delete();
        DB::table('section_subjects')->where('section_id', $section->id)->delete();
        
        $officialScheduleItems = [
            // Sunday
            ['day' => 'Sunday', 'start_time' => '12:30:00', 'end_time' => '12:40:00', 'subject' => 'General Assembly', 'teacher_display' => 'AMIS Academic Team', 'teacher_key' => ''],
            ['day' => 'Sunday', 'start_time' => '12:40:00', 'end_time' => '13:20:00', 'subject' => 'GMRC', 'teacher_display' => 'Teacher Sahdia', 'teacher_key' => 'teacher-sahdia-landas'],
            ['day' => 'Sunday', 'start_time' => '13:20:00', 'end_time' => '13:30:00', 'subject' => 'Transition', 'teacher_display' => '', 'teacher_key' => ''],
            ['day' => 'Sunday', 'start_time' => '13:30:00', 'end_time' => '14:10:00', 'subject' => 'SHAF', 'teacher_display' => 'Alim Abdul Karim', 'teacher_key' => 'alim-abdulkarim'],
            ['day' => 'Sunday', 'start_time' => '14:10:00', 'end_time' => '14:20:00', 'subject' => 'Transition', 'teacher_display' => '', 'teacher_key' => ''],
            ['day' => 'Sunday', 'start_time' => '14:20:00', 'end_time' => '15:00:00', 'subject' => 'Math', 'teacher_display' => 'Teacher Joanna', 'teacher_key' => 'teacher-joanna-lafuente'],
            ['day' => 'Sunday', 'start_time' => '15:00:00', 'end_time' => '15:30:00', 'subject' => 'HOMEROOM GUIDANCE/ARAL MATH', 'teacher_display' => 'Teacher Joanna', 'teacher_key' => 'teacher-joanna-lafuente'],
            
            // Monday
            ['day' => 'Monday', 'start_time' => '12:40:00', 'end_time' => '13:20:00', 'subject' => 'Makabansa', 'teacher_display' => 'Teacher Norhydie', 'teacher_key' => 'teacher-norhydie-lingas'],
            ['day' => 'Monday', 'start_time' => '13:30:00', 'end_time' => '14:10:00', 'subject' => 'Language', 'teacher_display' => 'Teacher Sahdia', 'teacher_key' => 'teacher-sahdia-landas'],
            ['day' => 'Monday', 'start_time' => '14:20:00', 'end_time' => '15:00:00', 'subject' => 'Reading & Literacy', 'teacher_display' => 'Teacher Katrina', 'teacher_key' => 'tchr-katrina'],
            
            // Tuesday
            ['day' => 'Tuesday', 'start_time' => '12:40:00', 'end_time' => '13:20:00', 'subject' => 'GMRC', 'teacher_display' => 'Teacher Sahdia', 'teacher_key' => 'teacher-sahdia-landas'],
            ['day' => 'Tuesday', 'start_time' => '13:30:00', 'end_time' => '14:10:00', 'subject' => 'Language', 'teacher_display' => 'Teacher Sahdia', 'teacher_key' => 'teacher-sahdia-landas'],
            ['day' => 'Tuesday', 'start_time' => '14:20:00', 'end_time' => '15:00:00', 'subject' => 'Math', 'teacher_display' => 'Teacher Joanna', 'teacher_key' => 'teacher-joanna-lafuente'],
            
            // Wednesday
            ['day' => 'Wednesday', 'start_time' => '12:40:00', 'end_time' => '13:20:00', 'subject' => 'Makabansa', 'teacher_display' => 'Teacher Norhydie', 'teacher_key' => 'teacher-norhydie-lingas'],
            ['day' => 'Wednesday', 'start_time' => '13:30:00', 'end_time' => '14:10:00', 'subject' => 'SHAF', 'teacher_display' => 'Alim Abdul Karim', 'teacher_key' => 'alim-abdulkarim'],
            ['day' => 'Wednesday', 'start_time' => '14:20:00', 'end_time' => '15:00:00', 'subject' => 'Reading & Literacy', 'teacher_display' => 'Teacher Katrina', 'teacher_key' => 'tchr-katrina'],
            
            // Thursday
            ['day' => 'Thursday', 'start_time' => '12:40:00', 'end_time' => '13:20:00', 'subject' => 'Arabic', 'teacher_display' => 'Ustadha Hainur', 'teacher_key' => 'ustadha-hainur'],
            ['day' => 'Thursday', 'start_time' => '13:30:00', 'end_time' => '14:10:00', 'subject' => 'Qur\'an', 'teacher_display' => 'Ustadha Hainur', 'teacher_key' => 'ustadha-hainur'],
            ['day' => 'Thursday', 'start_time' => '14:20:00', 'end_time' => '15:00:00', 'subject' => 'ARAL Reading', 'teacher_display' => 'Teacher Katrina', 'teacher_key' => 'tchr-katrina'],
        ];
        
        foreach ($officialScheduleItems as $item) {
            $isSpecial = in_array($item['subject'], ['General Assembly', 'Transition', 'RECESS', 'HOMEROOM GUIDANCE/ARAL MATH']);
            
            DB::table('class_schedules')->insert([
                'section_id' => $section->id,
                'subject_id' => null,
                'room_id' => null,
                'subject_name' => $item['subject'],
                'spans_all_days' => 0,
                'is_special' => $isSpecial ? 1 : 0,
                'is_locked' => 0,
                'color_class' => $isSpecial ? 'special' : 'academic',
                'teacher_key' => $item['teacher_key'] ?: null,
                'teacher_display' => $item['teacher_display'],
                'teacher_status' => !empty($item['teacher_key']) ? 'matched' : 'unmatched',
                'day' => $item['day'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
                'mode' => 'online',
                'school_year' => '2026-2027',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // Populate section_subjects for display with photos & emails
        $uniqueSubjects = collect($officialScheduleItems)
            ->filter(fn($i) => !in_array($i['subject'], ['Transition', 'RECESS']))
            ->unique('subject');
            
        $hasTeacherCols = DB::getSchemaBuilder()->hasColumn('section_subjects', 'teacher_key');

        foreach ($uniqueSubjects as $us) {
            $tKey = $us['teacher_key'];
            $tData = $overrides[$tKey] ?? [];
            
            $insertData = [
                'section_id' => $section->id,
                'subject_name' => $us['subject'],
                'teacher_name' => $us['teacher_display'],
                'schedule' => null,
                'ms_channel_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasTeacherCols) {
                $insertData['teacher_key'] = $tKey ?: null;
                $insertData['teacher_photo'] = $tData['photo'] ?? null;
                $insertData['teacher_email'] = $tData['email'] ?? null;
            }

            DB::table('section_subjects')->insert($insertData);
        }
        
        echo "Successfully inserted " . count($officialScheduleItems) . " schedule items and " . count($uniqueSubjects) . " subjects into section {$section->name}!\n";
    }
}

echo "\n=== ALL DONE! ===\n";
