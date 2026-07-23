<?php

namespace Database\Seeders;

use App\Models\SoaMonthlyBilling;
use App\Models\StudentAccount;
use App\Models\StudentAccountPayment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SoaMockPaymentSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key constraints to safely truncate
        Schema::disableForeignKeyConstraints();
        StudentAccountPayment::truncate();
        Schema::enableForeignKeyConstraints();

        // Reset all monthly billings to unpaid
        SoaMonthlyBilling::query()->update([
            'status' => 'unpaid',
            'paid_at' => null,
        ]);

        // Reset all student account balances
        $accounts = StudentAccount::all();
        foreach ($accounts as $account) {
            $account->update([
                'amount_paid' => 0.00,
                'remaining_balance' => $account->total_balance,
                'status' => 'unpaid',
            ]);
        }

        echo "Cleared existing SOA payments. Seeding new mock payments...\n";

        $methods = ['cash', 'gcash', 'maya', 'bdo'];
        $checkers = ['Sir Cabel', 'Admin Team', 'Finance Officer'];

        $count = 0;
        foreach ($accounts as $account) {
            // Decide payment tier:
            // 20% Fully Paid, 50% Partially Paid, 30% Unpaid
            $rand = rand(1, 100);

            if ($rand <= 30) {
                // Tier 1: 30% Unpaid - Keep as is (No payments)
                continue;
            }

            $billings = $account->monthlyBillings()->orderBy('month_number')->get();
            if ($billings->isEmpty()) {
                continue;
            }

            $monthlyAmount = (float) ($account->monthly_tuition ?: 4020.56);
            if ($monthlyAmount <= 0) {
                $monthlyAmount = 4020.56;
            }

            $paymentsToCreate = [];

            if ($rand > 30 && $rand <= 80) {
                // Tier 2: 50% Partially Paid (Randomly pay 1 to 6 months of installments)
                $monthsPaidCount = rand(1, 6);

                // Let's create multiple monthly payments to simulate sequential payment history!
                for ($i = 0; $i < $monthsPaidCount; $i++) {
                    $dueDate = $billings[$i]->due_date ?? Carbon::now()->subMonths($monthsPaidCount - $i);
                    $paidDate = Carbon::parse($dueDate)->subDays(rand(1, 5)); // paid a few days before due date

                    $paymentsToCreate[] = [
                        'amount' => $monthlyAmount,
                        'date' => $paidDate,
                        'purpose' => 'Tuition Fee - '.($billings[$i]->month_name ?: 'Monthly Installment'),
                    ];
                }
            } else {
                // Tier 3: 20% Fully Paid (Pay all outstanding billings!)
                // Simulate chronological payments over the last several months!
                foreach ($billings as $index => $billing) {
                    $dueDate = $billing->due_date ?? Carbon::now()->subMonths(count($billings) - $index);
                    $paidDate = Carbon::parse($dueDate)->subDays(rand(1, 5));

                    $paymentsToCreate[] = [
                        'amount' => (float) $billing->amount_due,
                        'date' => $paidDate,
                        'purpose' => 'Tuition Fee - '.($billing->month_name ?: 'Monthly Installment'),
                    ];
                }
            }

            // Write payments to DB
            foreach ($paymentsToCreate as $pData) {
                $method = $methods[array_rand($methods)];
                $checker = $checkers[array_rand($checkers)];

                StudentAccountPayment::create([
                    'student_account_id' => $account->id,
                    'student_id' => $account->student_id,
                    'method' => $method,
                    'reference_no' => $method === 'cash' ? null : ('REF'.rand(10000000, 99999999)),
                    'or_number' => '7010'.rand(1000, 9999),
                    'amount' => $pData['amount'],
                    'remarks' => $pData['purpose'],
                    'status' => 'verified',
                    'checked_by' => $checker,
                    'verified_at' => $pData['date'],
                    'paid_at' => $pData['date'],
                ]);
            }

            // Recalculate account totals & trigger waterfall allocation
            $account->recalculate();
            $count++;
        }

        echo "Successfully seeded mock payments for $count student accounts across all grades!\n";
    }
}
