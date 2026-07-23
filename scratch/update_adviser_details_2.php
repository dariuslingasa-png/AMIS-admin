<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Repositories\TeacherRepository;
use Illuminate\Contracts\Console\Kernel;

$updates = [
    'teacher-monisa-gegare-balandan' => [
        'email' => 'mbalandan.amis@gmail.com',
        'contact_number' => '09306533937',
        'first_name' => 'Monisa',
        'middle_name' => '',
        'last_name' => 'Balandan',
    ],
    'teacher-jessa-mae-recla' => [
        'email' => 'rrecla.amis@gmail.com',
        'contact_number' => '09972208342',
        'first_name' => 'Jessa Mae',
        'middle_name' => '',
        'last_name' => 'Recla',
    ],
    'teacher-normylah-bangon' => [
        'email' => 'nbangon.amis@gmail.com',
        'contact_number' => '09677758867',
        'first_name' => 'Normylah',
        'middle_name' => '',
        'last_name' => 'Bangon',
    ],
    'teacher-sophia-macarimbang' => [
        'email' => 'smacarimbang.amis@gmail.com',
        'contact_number' => '09553455636',
        'first_name' => 'Sophia',
        'middle_name' => '',
        'last_name' => 'Macarimbang',
    ],
    'teacher-shirehan-lais' => [
        'email' => 'slaismagare.amis@gmail.com',
        'contact_number' => '09050324560',
        'first_name' => 'Shirehan',
        'middle_name' => 'S.',
        'last_name' => 'Lais',
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

    echo "Updating {$id}:\n";
    echo '  Email: '.($oldEmail ? "{$oldEmail} -> " : '')."{$data['email']}\n";
    echo "  Contact: {$data['contact_number']}\n";
    echo "  Name: {$data['first_name']} ".($data['middle_name'] ? $data['middle_name'].' ' : '')."{$data['last_name']}\n";

    // Find database user (check by old email, new email, or username/id)
    $user = null;
    if ($oldEmail) {
        $user = User::where('email', $oldEmail)->first();
    }
    if (! $user) {
        $user = User::where('email', $data['email'])->first();
    }
    if (! $user) {
        $user = User::where('username', $id)->first();
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
echo "\nDONE! Overrides and database updated successfully.\n";
