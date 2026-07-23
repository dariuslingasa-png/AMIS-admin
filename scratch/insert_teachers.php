<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Repositories\TeacherRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;

$teachersData = [
    // Subject Teachers
    [
        'id' => 'tchr-katrina',
        'name' => 'Tchr. Katrina',
        'email' => 'tr.tkatrina@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Katrina',
        'middle_name' => '',
        'last_name' => 'Clapano',
    ],
    [
        'id' => 'tchr-arvin',
        'name' => 'Tchr. Arvin',
        'email' => 'tr.tarvin@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Arvin',
        'middle_name' => 'Limgas',
        'last_name' => 'Fonellera',
    ],
    [
        'id' => 'teacher-anna',
        'name' => 'Teacher Anna',
        'email' => 'tr.alatiban@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Anna Marie',
        'middle_name' => '',
        'last_name' => 'Latiban',
    ],
    [
        'id' => 'teacher-nof',
        'name' => 'Teacher Nof',
        'email' => 'tr.nlandas@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Nof',
        'middle_name' => '',
        'last_name' => 'Landas',
    ],
    [
        'id' => 'teacher-weng',
        'name' => 'Teacher Weng',
        'email' => 'tr.rfernandez@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Rowena',
        'middle_name' => '',
        'last_name' => 'Fernandez',
    ],
    [
        'id' => 'teacher-halnaisa',
        'name' => 'Teacher Halnaisa',
        'email' => 'tr.hpantaran@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Halnaisa',
        'middle_name' => '',
        'last_name' => 'Pantaran',
    ],
    [
        'id' => 'teacher-zarah',
        'name' => 'Teacher Zarah',
        'email' => 'tr.franain@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Franchette Zarah',
        'middle_name' => '',
        'last_name' => 'Ranain',
    ],
    [
        'id' => 'teacher-angeleni',
        'name' => 'Teacher Angeleni',
        'email' => 'tr.agecale@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Angeleni',
        'middle_name' => '',
        'last_name' => 'Gecale',
    ],
    [
        'id' => 'teacher-aniah',
        'name' => 'Teacher Aniah',
        'email' => 'tr.aodin@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Aniah',
        'middle_name' => '',
        'last_name' => 'Odin',
    ],
    [
        'id' => 'teacher-hannah',
        'name' => 'Teacher Hannah',
        'email' => 'tr.hannahpantaran@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Hannah',
        'middle_name' => '',
        'last_name' => 'Pantaran',
    ],
    [
        'id' => 'teacher-wardah',
        'name' => 'Teacher Wardah',
        'email' => 'tr.wpindaton@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Wardah',
        'middle_name' => '',
        'last_name' => 'Pindaton',
    ],
    [
        'id' => 'teacher-radzmia',
        'name' => 'Teacher Radzmia',
        'email' => 'tr.rbasillisco@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Radzmia',
        'middle_name' => '',
        'last_name' => 'Basillisco',
    ],
    [
        'id' => 'teacher-jairah',
        'name' => 'Teacher Jairah',
        'email' => 'tr.jsaripada@amis.edu.ph',
        'dept' => 'Elementary Department',
        'first_name' => 'Jairah',
        'middle_name' => '',
        'last_name' => 'Saripada',
    ],

    // Islamic Teachers
    [
        'id' => 'ust-silfah',
        'name' => 'Ustadha Silfah',
        'email' => 'tr.silfah@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Silfa',
        'middle_name' => '',
        'last_name' => 'Sacar-Negroprado',
    ],
    [
        'id' => 'ustadha-raslina',
        'name' => 'Ustadha Raslina',
        'email' => 'tr.ryahya@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Raslina',
        'middle_name' => '',
        'last_name' => 'Yahya',
    ],
    [
        'id' => 'ustadha-saliha',
        'name' => 'Ustadha Saliha',
        'email' => 'tr.saliha@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Saliha',
        'middle_name' => '',
        'last_name' => 'Mamonas',
    ],
    [
        'id' => 'ustadz-ali',
        'name' => 'Ustadz Ali',
        'email' => 'tr.msultan@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Muhammad Ali',
        'middle_name' => '',
        'last_name' => 'Sultan',
    ],
    [
        'id' => 'ustadz-obaydah',
        'name' => 'Ustadz Obaydah',
        'email' => 'tr.otini@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Obaydah',
        'middle_name' => '',
        'last_name' => 'Tini',
    ],
    [
        'id' => 'alim-abdulkarim',
        'name' => 'Alim Abdulkarim',
        'email' => 'tr.abustamante@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Abdul Karim',
        'middle_name' => '',
        'last_name' => 'Bustamante',
    ],
    [
        'id' => 'alim-samsuddin',
        'name' => 'Alim Samsuddin',
        'email' => 'tr.smustha@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Samsuddin',
        'middle_name' => '',
        'last_name' => 'mustha',
    ],
    [
        'id' => 'ustadh-jaisam',
        'name' => 'Ustadz Jasam',
        'email' => 'tr.jaisam@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Jaisam',
        'middle_name' => '',
        'last_name' => 'Mamentong',
    ],
    [
        'id' => 'ust-abdiraheem',
        'name' => 'Ustadz Abdi',
        'email' => 'tr.abdiraheem@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Abdiraheem',
        'middle_name' => 'Andayop',
        'last_name' => 'Gonzales',
    ],
    [
        'id' => 'ust-ersahad',
        'name' => 'Ustadz Ersahad',
        'email' => 'tr.ersahad@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Ersahad',
        'middle_name' => 'Ambulodto',
        'last_name' => 'Esmael',
    ],
    [
        'id' => 'alim-ahmad',
        'name' => 'Alim Ahmad',
        'email' => 'tr.amamonas@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Ahmad',
        'middle_name' => '',
        'last_name' => 'Mamonas',
    ],
    [
        'id' => 'alim-abdulwahab',
        'name' => 'Alim Abdulwahab',
        'email' => 'tr.adipatuan@amis.edu.ph',
        'dept' => 'Islamic School and Arabic Language Department',
        'first_name' => 'Abdulwahab',
        'middle_name' => '',
        'last_name' => 'Dipatuan',
    ],
];

$repo = app(TeacherRepository::class);
$overrides = $repo->overrides();

foreach ($teachersData as $item) {
    $id = $item['id'];
    $exists = isset($overrides[$id]);

    if ($exists) {
        echo "Updating existing override: {$id} ({$item['name']})\n";
        $overrides[$id]['name'] = $item['name'];
        $overrides[$id]['email'] = $item['email'];
        $overrides[$id]['dept'] = $item['dept'];
        $overrides[$id]['first_name'] = $item['first_name'];
        $overrides[$id]['middle_name'] = $item['middle_name'] ?? '';
        $overrides[$id]['last_name'] = $item['last_name'];
        // Ensure status is active and MS sync is false
        $overrides[$id]['status'] = $overrides[$id]['status'] ?? 'Active';
        $overrides[$id]['microsoft_sync'] = false;
    } else {
        echo "Creating new override: {$id} ({$item['name']})\n";
        $password = 'Amis@'.strtoupper(Str::random(5)).rand(10, 99);
        $overrides[$id] = [
            'id' => $id,
            'name' => $item['name'],
            'email' => $item['email'],
            'dept' => $item['dept'],
            'sections' => null,
            'status' => 'Active',
            'license' => 'faculty_a1',
            'photo' => null,
            'first_name' => $item['first_name'],
            'middle_name' => $item['middle_name'] ?? '',
            'last_name' => $item['last_name'],
            'gender' => 'Male',
            'birthdate' => '',
            'contact_number' => '',
            'address' => '',
            'password_changed' => 'No',
            'temporary_password' => $password,
            'microsoft_sync' => false,
            'subjects' => [],
        ];
    }

    // Update or create DB User
    $tempPassword = $overrides[$id]['temporary_password'] ?? 'Amis@ABCDE12';
    $user = User::where('email', $item['email'])
        ->orWhere('username', $id)
        ->first();

    if ($user) {
        $user->update([
            'name' => $item['name'],
            'email' => $item['email'],
            'username' => $id,
            'role' => 'teacher',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);
        echo "  -> Updated existing user in DB (email: {$item['email']})\n";
    } else {
        $user = User::create([
            'email' => $item['email'],
            'name' => $item['name'],
            'username' => $id,
            'password' => Hash::make($tempPassword),
            'role' => 'teacher',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);
        echo "  -> Created new user in DB (email: {$item['email']}, temp password: {$tempPassword})\n";
    }
}

// Save overrides to file
$repo->saveOverrides($overrides);
echo "\nDONE! JSON overrides saved successfully.\n";
