<?php

namespace App\Console\Commands;

use App\Services\Finance\FinanceDemoDataService;
use Illuminate\Console\Command;

class ResetDemoFamilyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:reset-demo {family=zhairel.lingasa@gmail.com} {--all : Reset all demo families}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset demo family billing balances and payments back to initial July 2026 state';

    /**
     * Execute the console command.
     */
    public function handle(FinanceDemoDataService $demoService): int
    {
        $familiesToReset = [];

        if ($this->option('all')) {
            $familiesToReset = ['zhairel.lingasa@gmail.com', 'wcamsar.amis@gmail.com'];
        } else {
            $familiesToReset = [$this->argument('family')];
        }

        foreach ($familiesToReset as $familyKey) {
            $this->info("Resetting demo data for family: {$familyKey}...");
            $demoService->resetDemoFamily($familyKey);

            $family = $demoService->getFamily($familyKey);
            if ($family) {
                $balances = $demoService->getBalances($familyKey);
                $this->line("  ✓ Family: {$family->name} ({$family->email})");
                $this->line("  ✓ Status: Clean initial state restored (July 2026)");
                $this->line("  ✓ Outstanding Balance: ₱".number_format($balances->sum('remaining'), 2));
                foreach ($balances->take(3) as $row) {
                    $cName = $row['student']->full_name ?? $row['student']->name ?? 'Child';
                    $this->line("    • {$cName} ({$row['student']->grade_level}): ₱".number_format($row['remaining'], 2));
                }
            } else {
                $this->warn("  ⚠ Family {$familyKey} not recognized as a demo family.");
            }
        }

        $this->newLine();
        $this->info('Demo reset complete! You can now test new payments with clean ₱100 round-robin allocation.');

        return Command::SUCCESS;
    }
}
