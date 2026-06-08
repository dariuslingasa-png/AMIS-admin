<?php

namespace App\Console\Commands;

use App\Services\MicrosoftGraphService;
use App\Services\MsTeamsProvisioningService;
use Illuminate\Console\Command;

class ProvisionMsTeams extends Command
{
    protected $signature   = 'ms-teams:provision {--year= : School year to provision}';
    protected $description = 'Auto-create all K-12 grade Teams + Boys/Girls private channels in MS Teams';

    public function handle(): int
    {
        $schoolYear = $this->option('year') ?: config('services.school.year');

        $this->info("Provisioning MS Teams for all K-12 grades — SY {$schoolYear}");
        $this->info('This may take a few minutes due to MS Teams API provisioning delays...');
        $this->newLine();

        $service = new MsTeamsProvisioningService(new MicrosoftGraphService());

        $bar = $this->output->createProgressBar(14); // 14 grade levels
        $bar->start();

        $results = $service->provisionAllGradeTeams(
            $schoolYear,
            function (string $status, string $grade, string $error = '') use ($bar) {
                $bar->advance();
                if ($status === 'failed') {
                    $this->newLine();
                    $this->error("  ✗ {$grade}: {$error}");
                }
            }
        );

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Created:  ' . count($results['created']) . ' teams');
        $this->line('⏭  Skipped:  ' . count($results['skipped']) . ' (already exist)');

        if (!empty($results['failed'])) {
            $this->error('✗  Failed:   ' . count($results['failed']) . ' teams');
            foreach ($results['failed'] as $f) {
                $this->error("   - {$f['grade']}: {$f['error']}");
            }
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('All grade teams provisioned successfully!');
        return Command::SUCCESS;
    }
}
