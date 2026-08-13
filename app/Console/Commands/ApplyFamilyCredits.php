<?php

namespace App\Console\Commands;

use App\Models\FamilyAdvanceCredit;
use App\Models\FinanceAdvanceCredit;
use App\Services\Finance\FinanceAllocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ApplyFamilyCredits extends Command
{
    protected $signature = 'finance:apply-family-credits {--family= : Apply credit for one family user ID only}';

    protected $description = 'Apply available family credit FIFO to school fees that are already payable';

    public function handle(FinanceAllocationService $allocation): int
    {
        $familyIds = collect();
        if ($this->option('family')) {
            $familyIds->push((int) $this->option('family'));
        } else {
            if (Schema::hasTable('family_advance_credits')) {
                $familyIds = $familyIds->merge(FamilyAdvanceCredit::query()
                    ->where('status', 'active')->where('remaining_amount', '>', 0)->pluck('user_id'));
            }
            if (Schema::hasTable('finance_advance_credits')) {
                $familyIds = $familyIds->merge(FinanceAdvanceCredit::query()
                    ->where('status', 'ACTIVE')->where('remaining_amount', '>', 0)->pluck('user_id'));
            }
        }

        $applied = 0.0;
        foreach ($familyIds->map(fn ($id) => (int) $id)->unique()->sort()->values() as $familyId) {
            $result = $allocation->applyAvailableCredit($familyId);
            $applied = round($applied + (float) $result['applied'], 2);
        }

        $this->info('Family credit applied: ₱'.number_format($applied, 2));

        return self::SUCCESS;
    }
}
