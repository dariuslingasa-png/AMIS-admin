<?php

if (php_sapi_name() === 'cli' || (isset($_GET['secret']) && $_GET['secret'] === 'amis_fix_9988')) {
    // Continue
} else {
    die("Access denied.");
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Student;
use App\Models\EnrollmentApplicant;
use App\Services\MicrosoftGraphService;

header('Content-Type: text/plain');
echo "=== Restoring Velasco's 2x2 Photo ===\n\n";

try {
    $graph = new MicrosoftGraphService();
    $oldUpn = 'g9.avelasco@amis.edu.ph';
    $newUpn = '260302velasco@amis.edu.ph';
    
    // 1. Fetch photo from old account
    echo "Downloading 2x2 photo from old account '{$oldUpn}'...\n";
    $photoData = $graph->getUserPhoto($oldUpn);
    
    if (!$photoData) {
        throw new \Exception("Could not retrieve photo from old Microsoft account '{$oldUpn}'. Please check if it still exists.");
    }
    
    echo "Photo downloaded successfully. Size: " . strlen($photoData['bytes']) . " bytes. Content-Type: " . $photoData['content_type'] . "\n\n";
    
    // 2. Find student and applicant records
    $student = Student::where('student_number', '260302')->first();
    if (!$student) {
        throw new \Exception("Student record for 260302 was not found. Please run restore_velasco_applicant.php first.");
    }
    
    $applicant = $student->applicant;
    if (!$applicant) {
        throw new \Exception("No linked applicant record found for this student. Please run restore_velasco_applicant.php first.");
    }
    
    $applicantId = $applicant->id;
    echo "Found student (ID: {$student->id}) and linked applicant (ID: {$applicantId}).\n";
    
    // 3. Save photo to local public storage
    $ext = ($photoData['content_type'] === 'image/png') ? 'png' : 'jpg';
    $relativeDir = "enrollment/{$applicantId}";
    $relativeFilePath = "{$relativeDir}/photo_2x2.{$ext}";
    
    $absoluteDir = storage_path("app/public/{$relativeDir}");
    $absoluteFilePath = storage_path("app/public/{$relativeFilePath}");
    
    if (!is_dir($absoluteDir)) {
        echo "Creating directory: {$absoluteDir}\n";
        mkdir($absoluteDir, 0755, true);
    }
    
    echo "Saving photo locally to: {$absoluteFilePath}\n";
    file_put_contents($absoluteFilePath, $photoData['bytes']);
    
    // 4. Update applicant photo url in database
    $applicant->update([
        'photo_2x2_url' => $relativeFilePath
    ]);
    echo "Updated applicant photo_2x2_url in database to '{$relativeFilePath}'.\n\n";
    
    // 5. Upload photo to new active Grade 10 account
    echo "Uploading photo to new active Grade 10 Microsoft account '{$newUpn}'...\n";
    $graph->uploadUserPhoto($newUpn, $photoData['bytes'], $photoData['content_type']);
    echo "Photo successfully uploaded to new Microsoft account.\n\n";
    
    echo "=== Photo Restoration Complete! ===\n";
    
} catch (\Throwable $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
