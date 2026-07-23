<?php

use App\Http\Controllers\Admin\EnrollmentReportController;
use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$controller = new EnrollmentReportController;
$request = new Request;
echo "Starting full sync from EnrollmentReportController::syncNow...\n";
$response = $controller->syncNow($request);
echo 'Response status: '.$response->status()."\n";
echo 'Response body: '.$response->content()."\n";

// Let's print out the summary of the database fields after the sync
$total = Student::count();
$withMsId = Student::whereNotNull('ms_user_id')->count();
$withActivity = Student::whereNotNull('ms_teams_last_activity_at')->count();
$withMeetings = Student::where('ms_teams_meetings_attended', '>', 0)->count();

echo "\nSummary of Sync:\n";
echo "  Total Students: {$total}\n";
echo "  Students with ms_user_id: {$withMsId}\n";
echo "  Students with Teams last activity: {$withActivity}\n";
echo "  Students with Teams meetings attended: {$withMeetings}\n";

if ($withMeetings > 0) {
    echo "\nSample students with meetings:\n";
    $samples = Student::where('ms_teams_meetings_attended', '>', 0)->limit(5)->get();
    foreach ($samples as $s) {
        echo "  Student: {$s->student_number} | Email: {$s->school_email} | Meetings: {$s->ms_teams_meetings_attended} | Last Activity: {$s->ms_teams_last_activity_at}\n";
    }
}
