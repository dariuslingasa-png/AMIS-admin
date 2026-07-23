<?php

namespace App\Console\Commands;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateApplicant extends Command
{
    protected $signature = 'applicant:remove-duplicate {duplicate_id : The ID of the duplicate applicant to remove} {keep_id : The ID of the primary applicant to keep}';

    protected $description = 'Safely remove a duplicate applicant and reassign any associated payments to the primary applicant';

    public function handle(): int
    {
        $duplicateId = (int) $this->argument('duplicate_id');
        $keepId = (int) $this->argument('keep_id');

        $duplicate = EnrollmentApplicant::find($duplicateId);
        $keep = EnrollmentApplicant::find($keepId);

        if (! $duplicate) {
            $this->error("Duplicate applicant with ID {$duplicateId} not found.");

            return Command::FAILURE;
        }

        if (! $keep) {
            $this->error("Primary applicant to keep with ID {$keepId} not found.");

            return Command::FAILURE;
        }

        $this->info("Target to delete: #{$duplicate->id} - {$duplicate->full_name} (Status: {$duplicate->status}, Grade: {$duplicate->grade_level})");
        $this->info("Target to keep:   #{$keep->id} - {$keep->full_name} (Status: {$keep->status}, Grade: {$keep->grade_level})");

        if ($duplicate->student()->exists()) {
            $this->error("Cannot delete applicant #{$duplicateId} because a Student Record already exists for them. Clean up the Student Record first.");

            return Command::FAILURE;
        }

        if (! $this->confirm("Are you sure you want to delete applicant #{$duplicateId} and transfer any payments/dependencies to #{$keepId}?")) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        try {
            DB::transaction(function () use ($duplicate, $keep) {
                // Reassign any payments belonging to the duplicate to the keep applicant
                $paymentCount = Payment::where('enrollment_applicant_id', $duplicate->id)
                    ->update(['enrollment_applicant_id' => $keep->id]);

                if ($paymentCount > 0) {
                    $this->info("✓ Reassigned {$paymentCount} payment record(s) from #{$duplicate->id} to #{$keep->id}.");
                }

                // Delete the duplicate applicant
                $duplicate->delete();
            });

            $this->info("✓ Successfully deleted duplicate applicant #{$duplicateId}.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ An error occurred while removing the duplicate: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
