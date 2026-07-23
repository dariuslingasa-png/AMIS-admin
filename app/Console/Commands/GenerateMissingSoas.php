<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\SoaService;
use Illuminate\Console\Command;

class GenerateMissingSoas extends Command
{
    protected $signature = 'soa:generate-missing';

    protected $description = 'Generate SOA for approved students who do not have one yet';

    public function handle(): void
    {
        $students = Student::with('applicant')
            ->whereDoesntHave('account')
            ->get();

        if ($students->isEmpty()) {
            $this->info('All students already have an SOA.');

            return;
        }

        $service = new SoaService;
        $count = 0;

        foreach ($students as $student) {
            if (! $student->applicant) {
                $this->warn("Student #{$student->id} has no applicant record — skipped.");

                continue;
            }
            try {
                $account = $service->generate($student, $student->applicant);
                $this->info("✓ {$student->student_number} — {$student->applicant->last_name}, {$student->applicant->first_name} — Balance: ₱".number_format($account->total_balance, 2));
                $count++;
            } catch (\Exception $e) {
                $this->error("✗ {$student->student_number}: ".$e->getMessage());
            }
        }

        $this->info("\nDone. {$count} SOA(s) generated.");
    }
}
