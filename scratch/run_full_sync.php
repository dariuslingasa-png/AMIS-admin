<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = new \App\Http\Controllers\AdminStudentDashboardController();
$request = new \Illuminate\Http\Request();
echo "Starting full sync from AdminStudentDashboardController::syncNow...\n";
$response = $controller->syncNow($request);
echo "Response status: " . $response->status() . "\n";
echo "Response body: " . $response->content() . "\n";

// Let's print out the summary of the database fields after the sync
$total = \App\Models\Student::count();
$withMsId = \App\Models\Student::whereNotNull('ms_user_id')->count();
$withActivity = \App\Models\Student::whereNotNull('ms_teams_last_activity_at')->count();
$withMeetings = \App\Models\Student::where('ms_teams_meetings_attended', '>', 0)->count();

echo "\nSummary of Sync:\n";
echo "  Total Students: {$total}\n";
echo "  Students with ms_user_id: {$withMsId}\n";
echo "  Students with Teams last activity: {$withActivity}\n";
echo "  Students with Teams meetings attended: {$withMeetings}\n";

if ($withMeetings > 0) {
    echo "\nSample students with meetings:\n";
    $samples = \App\Models\Student::where('ms_teams_meetings_attended', '>', 0)->limit(5)->get();
    foreach ($samples as $s) {
         echo "  Student: {$s->student_number} | Email: {$s->school_email} | Meetings: {$s->ms_teams_meetings_attended} | Last Activity: {$s->ms_teams_last_activity_at}\n";
    }
}
