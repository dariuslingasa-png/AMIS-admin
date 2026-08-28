<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Services\Finance\FinanceHistoricalPaymentService;
use App\Services\SoaService;
use Illuminate\Console\Command;

class GenerateMissingSoas extends Command
{
    protected $signature = 'soa:generate-missing';

    protected $description = 'Generate SOA for all students who do not have one yet';

    public function handle(): void
    {
        $students = Student::with('applicant')
            ->whereDoesntHave('account')
            ->get();

        if ($students->isEmpty()) {
            $this->info('All students already have an SOA account.');

            return;
        }

        $soaService = new SoaService;
        $financeService = app(FinanceHistoricalPaymentService::class);
        $count = 0;

        foreach ($students as $student) {
            try {
                if ($student->applicant) {
                    $account = $soaService->generate($student, $student->applicant);
                } else {
                    $account = $financeService->ensureStudentAccount($student);
                }

                $name = $student->applicant
                    ? "{$student->applicant->last_name}, {$student->applicant->first_name}"
                    : ($student->full_name ?: "Student #{$student->id}");

                $this->info("✓ [{$student->student_number}] {$name} ({$student->grade_level}) — Remaining Balance: ₱".number_format($account->remaining_balance, 2));
                $count++;
            } catch (\Throwable $e) {
                // Fallback to financeService ensureStudentAccount
                try {
                    $account = $financeService->ensureStudentAccount($student);
                    $this->info("✓ [{$student->student_number}] (Fallback) — Remaining Balance: ₱".number_format($account->remaining_balance, 2));
                    $count++;
                } catch (\Throwable $ex) {
                    $this->error("✗ Student #{$student->id} ({$student->student_number}): ".$ex->getMessage());
                }
            }
        }

        $this->info("\nDone. {$count} student account(s) generated successfully.");
    }
}
