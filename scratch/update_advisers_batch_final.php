<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Repositories\TeacherRepository;
use Illuminate\Contracts\Console\Kernel;

$updates = [
    'teacher-ayah-baguinsodon' => [
        'email' => 'abaguinsodon.amis@gmail.com',
        'contact_number' => '09364852486',
        'first_name' => 'Ayah',
        'middle_name' => 'Lingas',
        'last_name' => 'Baguinsodon',
        'photo' => 'images/teachers/ayah-baguinsodon.png',
    ],
    'teacher-joanna-lafuente' => [
        'email' => 'jlafuente.amis@gmail.com',
        'contact_number' => '09958340471',
        'first_name' => 'Joanna',
        'middle_name' => '',
        'last_name' => 'Lafuente',
        'photo' => 'images/teachers/joanna-lafuente.jpg',
    ],
    'teacher-sahdia-landas' => [
        'email' => 'slandas.amis@gmail.com',
        'contact_number' => '09150512302',
        'first_name' => 'Sahdia',
        'middle_name' => '',
        'last_name' => 'Landas',
        'photo' => 'images/teachers/sahdia-landas.png',
    ],
    'teacher-marham-dalano-lupon' => [
        'email' => 'mlupon.amis@gmail.com',
        'contact_number' => '09502176638',
        'first_name' => 'Marham',
        'middle_name' => '',
        'last_name' => 'Lupon',
        'photo' => 'images/teachers/marham-lupon.png',
    ],
    'teacher-jerlyn-mijares' => [
        'email' => 'jmijares.amis@gmail.com',
        'contact_number' => '09943356491',
        'first_name' => 'Jerlyn',
        'middle_name' => '',
        'last_name' => 'Mijares',
        'photo' => 'images/teachers/teacher-jerlyn-mijares.jpg',
    ],
    'teacher-monisa-gegare-balandan' => [
        'email' => 'mbalandan.amis@gmail.com',
        'contact_number' => '09306533937',
        'first_name' => 'Monisa',
        'middle_name' => '',
        'last_name' => 'Balandan',
        'photo' => 'images/teachers/monisa-gegare-balandan.png',
    ],
    'teacher-jessa-mae-recla' => [
        'email' => 'rrecla.amis@gmail.com',
        'contact_number' => '09972208342',
        'first_name' => 'Jessa Mae',
        'middle_name' => '',
        'last_name' => 'Recla',
        'photo' => 'images/teachers/jessa-mae-recla.png',
    ],
    'teacher-normylah-bangon' => [
        'email' => 'nbangon.amis@gmail.com',
        'contact_number' => '09677758867',
        'first_name' => 'Normylah',
        'middle_name' => '',
        'last_name' => 'Bangon',
        'photo' => 'images/teachers/normylah-bangon.png',
    ],
    'teacher-sophia-macarimbang' => [
        'email' => 'smacarimbang.amis@gmail.com',
        'contact_number' => '09553455636',
        'first_name' => 'Sophia',
        'middle_name' => '',
        'last_name' => 'Macarimbang',
        'photo' => 'images/teachers/sophia-macarimbang.png',
    ],
    'teacher-shirehan-lais' => [
        'email' => 'slaismagare.amis@gmail.com',
        'contact_number' => '09050324560',
        'first_name' => 'Shirehan',
        'middle_name' => 'S.',
        'last_name' => 'Lais',
        'photo' => 'images/teachers/shirehan-lais.png',
    ],
];

$repo = app(TeacherRepository::class);
$overrides = $repo->overrides();

foreach ($updates as $id => $data) {
    if (! isset($overrides[$id])) {
        echo "Warning: Override for {$id} not found in overrides file. Creating one.\n";
        $overrides[$id] = [
            'id' => $id,
            'name' => mb_strtoupper(str_replace('-', ' ', str_replace('teacher-', 'TEACHER ', $id)), 'UTF-8'),
            'dept' => 'Elementary Department',
            'sections' => null,
            'status' => 'Active',
            'license' => 'faculty_a1',
            'photo' => null,
            'gender' => 'Male',
            'birthdate' => '',
            'address' => '',
            'password_changed' => 'No',
            'temporary_password' => 'Amis@'.strtoupper(Str::random(5)).rand(10, 99),
            'microsoft_sync' => false,
            'subjects' => [],
        ];
    }

    $oldEmail = $overrides[$id]['email'] ?? null;

    // Update JSON override fields
    $overrides[$id]['email'] = $data['email'];
    $overrides[$id]['contact_number'] = $data['contact_number'];
    $overrides[$id]['first_name'] = $data['first_name'];
    $overrides[$id]['middle_name'] = $data['middle_name'];
    $overrides[$id]['last_name'] = $data['last_name'];
    $overrides[$id]['photo'] = $data['photo'];

    echo "Updating {$id}:\n";
    echo '  Email: '.($oldEmail ? "{$oldEmail} -> " : '')."{$data['email']}\n";
    echo "  Contact: {$data['contact_number']}\n";
    echo "  Photo: {$data['photo']}\n";
    echo "  Name: {$data['first_name']} ".($data['middle_name'] ? $data['middle_name'].' ' : '')."{$data['last_name']}\n";

    // Find database user (check by old email, new email, or username/id)
    $userByNewEmail = User::where('email', $data['email'])->first();
    $userByOldEmail = $oldEmail ? User::where('email', $oldEmail)->first() : null;
    $userByUsername = User::where('username', $id)->first();

    // Choose the best user record to keep
    $user = $userByNewEmail ?: ($userByUsername ?: $userByOldEmail);

    // Clean up duplicate user records if any exist
    if ($userByNewEmail && $userByOldEmail && $userByNewEmail->id !== $userByOldEmail->id) {
        echo "  -> Found duplicate user records. Deleting old record (ID: {$userByOldEmail->id}) and keeping new record (ID: {$userByNewEmail->id}).\n";
        $userByOldEmail->delete();
    }

    if ($user) {
        $user->update([
            'email' => $data['email'],
            'name' => $overrides[$id]['name'],
            'username' => $id,
        ]);
        echo "  -> Updated existing database user (ID: {$user->id})\n";
    } else {
        $tempPassword = $overrides[$id]['temporary_password'] ?? 'Amis@ABCDE12';
        $user = User::create([
            'email' => $data['email'],
            'name' => $overrides[$id]['name'],
            'username' => $id,
            'password' => Hash::make($tempPassword),
            'role' => 'teacher',
            'account_status' => 'verified',
            'email_verified_at' => now(),
        ]);
        echo "  -> Created new database user with temp password: {$tempPassword}\n";
    }
}

$repo->saveOverrides($overrides);
echo "\nDONE! Overrides, photos, and database updated successfully.\n";
