<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$graph = new MicrosoftGraphService;

use App\Models\Section;
use App\Models\StudentSection;
use App\Services\MicrosoftGraphService;

$sections = Section::whereNotNull('ms_team_id')->get();

echo 'Checking '.$sections->count()." sections with MS Teams...\n";
$totalDbEnrolled = 0;
$totalGraphMembers = 0;

foreach ($sections as $section) {
    $dbCount = StudentSection::where('section_id', $section->id)
        ->where('ms_status', 'enrolled')
        ->count();

    $totalDbEnrolled += $dbCount;

    try {
        $members = $graph->listTeamMembers($section->ms_team_id);
        // Exclude owners/teachers if needed. Let's see who is in the team.
        // We can count non-owners as students.
        $studentMembers = array_filter($members, function ($m) {
            return ! in_array('owner', $m['roles'] ?? []);
        });

        $graphCount = count($studentMembers);
        $totalGraphMembers += $graphCount;

        echo "Section: {$section->grade_level} - {$section->gender} ({$section->shift}) | DB Enrolled: {$dbCount} | Graph Members: {$graphCount}\n";

        if ($dbCount !== $graphCount) {
            echo "  --> MISMATCH! DB: {$dbCount}, Graph: {$graphCount}\n";
        }
    } catch (Exception $e) {
        echo "Section: {$section->grade_level} | Error: ".$e->getMessage()."\n";
    }
}

echo "\nSummary:\n";
echo "Total DB Enrolled: {$totalDbEnrolled}\n";
echo "Total Graph Members: {$totalGraphMembers}\n";
