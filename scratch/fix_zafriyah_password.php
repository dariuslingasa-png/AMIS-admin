<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Hash;

$email = '260399szafriyah@amis.edu.ph';
$student = Student::where('school_email', $email)->first();

if (!$student) {
    echo "Student not found!\n";
    exit(1);
}

// 1. Update local database
$student->update([
    'temp_password' => '12345',
    'password_changed_at' => null,
    'credentials_sent_at' => now(),
]);

$user = User::find($student->user_id);
if ($user) {
    $user->update([
        'password' => Hash::make('12345'),
        'role' => 'student',
        'account_status' => 'verified',
    ]);
    echo "Local database updated. Temp password: 12345. User account password hashed: 12345.\n";
} else {
    echo "Linked User account not found for student!\n";
}

// 2. Update Microsoft Graph (since Microsoft AD requires >= 8 chars, we set it to amis12345)
$msPassword = 'amis12345';
try {
    $graph = new MicrosoftGraphService();
    $response = $graph->resetPassword($email, $msPassword);
    if ($response->successful()) {
        echo "Microsoft Graph password successfully reset to: {$msPassword}\n";
    } else {
        echo "Microsoft Graph reset failed: " . ($response->json()['error']['message'] ?? 'Unknown Microsoft API error') . "\n";
    }
} catch (\Throwable $e) {
    echo "Microsoft Graph error: " . $e->getMessage() . "\n";
}
