<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RegenerateHashedPasswords extends Command
{
    protected $signature = 'student:recover-passwords {--run : Run the recovery (default is dry-run)} {--limit= : Limit the number of students to process}';

    protected $description = 'Recover hashed/lost temporary passwords by regenerating new readable passwords and syncing to Microsoft AD';

    public function handle()
    {
        $query = Student::where('temp_password', 'like', '$%');

        $limit = $this->option('limit');
        if (filled($limit)) {
            $query->limit((int) $limit);
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            $this->info('No students with hashed/lost temporary passwords found.');
            return 0;
        }

        $this->info("Processing {$students->count()} students with hashed/lost temporary passwords...");

        $run = $this->option('run');

        if (!$run) {
            $this->warn('DRY RUN: No database or Microsoft changes will be made. Run with --run to apply changes.');
        }

        $headers = ['Student #', 'Name', 'School Email', 'New Temp Pass', 'Microsoft Sync'];
        $rows = [];

        $graph = new MicrosoftGraphService();
        $total = $students->count();

        foreach ($students as $index => $student) {
            $applicant = $student->applicant;
            $fullName = $applicant ? "{$applicant->first_name} {$applicant->last_name}" : 'Unknown';
            $currentNum = $index + 1;

            $this->info("[{$currentNum}/{$total}] Processing: {$student->school_email}...");

            // Generate new temp password in the exact AMIS format: Amis@XXXXXxx
            $newTempPass = 'Amis@' . strtoupper(Str::random(5)) . rand(10, 99);

            $syncStatus = 'Pending';

            if ($run) {
                try {
                    if (filled($student->school_email)) {
                        $graph->resetPassword($student->school_email, $newTempPass);
                        $syncStatus = 'SUCCESS';
                    } else {
                        $syncStatus = 'NO EMAIL';
                    }
                } catch (\Throwable $e) {
                    $syncStatus = 'FAILED: ' . $e->getMessage();
                    Log::error("Failed to sync regenerated password for {$student->school_email}: " . $e->getMessage());
                }

                // Update database
                $student->update(['temp_password' => $newTempPass]);
            } else {
                $syncStatus = 'DRY RUN';
            }

            $rows[] = [
                $student->student_number,
                $fullName,
                $student->school_email,
                $newTempPass,
                $syncStatus
            ];
        }

        $this->table($headers, $rows);

        if ($run) {
            $this->info('Successfully regenerated passwords and updated Microsoft accounts / database.');
        }

        return 0;
    }
}
