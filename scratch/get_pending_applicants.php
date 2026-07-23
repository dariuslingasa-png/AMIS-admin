<?php

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\EnrollmentApplicant;
use Illuminate\Contracts\Console\Kernel;

echo "Fetching pending applicants...\n";
$applicants = EnrollmentApplicant::where('status', 'submitted')
    ->orderBy('created_at', 'desc')
    ->get();

$totalCount = $applicants->count();
echo "Found {$totalCount} pending applicants.\n";

$markdown = "# Pending Enrollment Applicants List\n\n";
$markdown .= "Total Pending Applicants: **{$totalCount}**\n\n";
$markdown .= "This table lists all applicants who are still in the **submitted** status in AMIS Admin (sorted by newest submission).\n\n";

$markdown .= "| ID | Name | Grade Level | Type | Learning Mode | Parent Email | Submission Date |\n";
$markdown .= "|---|---|---|---|---|---|---|\n";

foreach ($applicants as $app) {
    $fullName = trim($app->first_name.' '.$app->middle_name.' '.$app->last_name);
    $fullName = strtoupper($fullName);
    $createdDate = $app->created_at ? $app->created_at->format('Y-m-d H:i:s') : 'N/A';

    $markdown .= "| {$app->id} | {$fullName} | {$app->grade_level} | {$app->student_type} | {$app->learning_mode} | ".($app->parent_email ?: 'N/A')." | {$createdDate} |\n";
}

$outputPath = '/home/tatsuya/.gemini/antigravity/brain/2d3e623e-0493-4375-a345-b076981341c8/pending_applicants.md';
file_put_contents($outputPath, $markdown);
echo "Successfully generated markdown list at {$outputPath}\n";
