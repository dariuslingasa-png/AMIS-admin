<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$graph = new App\Services\MicrosoftGraphService();

use App\Models\Student;

$teamId = '27e6f133-84bc-41f4-ad84-513661f9e217'; // Grade 8 - male (1st Shift)

try {
    $members = $graph->listTeamMembers($teamId);
    echo "Total members in Graph: " . count($members) . "\n";
    if (!empty($members)) {
        echo "Keys of member object:\n";
        print_r(array_keys($members[0]));
        echo "\nFull first member object:\n";
        print_r($members[0]);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
