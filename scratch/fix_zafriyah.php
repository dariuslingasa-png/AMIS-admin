<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Http;

$email = '260399szafriyah@amis.edu.ph';
$student = Student::where('school_email', $email)->first();

if (! $student) {
    echo "Student not found in database.\n";
    exit(1);
}

echo 'Student found: '.$student->student_number."\n";
echo 'Current temp password in DB: '.$student->temp_password."\n";

$graph = new MicrosoftGraphService;

try {
    // 1. Fetch user from Graph
    echo "Fetching user from Microsoft Graph...\n";
    $azUser = $graph->getUser($email, ['id', 'displayName', 'userPrincipalName', 'accountEnabled']);
    print_r($azUser);

    // 2. Reset password to zsalialam
    $newPassword = 'zsalialam';
    echo "Resetting password in Graph to: {$newPassword}\n";

    $token = (new ReflectionMethod($graph, 'getAccessToken'))->invoke($graph);
    $response = Http::withToken($token)
        ->patch("https://graph.microsoft.com/v1.0/users/{$email}", [
            'passwordProfile' => [
                'password' => $newPassword,
                'forceChangePasswordNextSignIn' => false,
            ],
        ]);

    if ($response->successful()) {
        echo "Successfully reset password in Graph!\n";
        $student->update([
            'temp_password' => $newPassword,
            'password_changed_at' => null,
        ]);
        echo "Updated database record.\n";
    } else {
        echo 'Error resetting password: '.$response->status()."\n";
        print_r($response->json());
    }
} catch (Exception $e) {
    echo 'Exception: '.$e->getMessage()."\n";
}
