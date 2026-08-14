<?php

namespace Tests\Unit;

use App\Models\SoaMonthlyBilling;
use App\Services\Finance\FinanceAllocationService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FinanceAllocationServiceTest extends TestCase
{
    #[Test]
    public function it_keeps_an_insufficient_oldest_payment_partial(): void
    {
        $plan = $this->plan([15000], 10000);

        $this->assertSame(10000.0, $plan['allocated_amount']);
        $this->assertSame(5000.0, $plan['allocations'][0]['remaining_after']);
        $this->assertSame(0.0, $plan['advance_credit']);
        $this->assertSame(5000.0, $plan['family_balance_after']);
    }

    #[Test]
    public function it_carries_payment_forward_in_oldest_first_order(): void
    {
        $plan = $this->plan([5000, 7000, 30000], 35000);

        $this->assertSame([5000.0, 7000.0, 23000.0], array_column($plan['allocations'], 'applied_amount'));
        $this->assertSame([0.0, 0.0, 7000.0], array_column($plan['allocations'], 'remaining_after'));
        $this->assertSame(7000.0, $plan['family_balance_after']);
    }

    #[Test]
    public function it_stores_excess_as_advance_credit(): void
    {
        $plan = $this->plan([5000, 7000], 15000);

        $this->assertSame(12000.0, $plan['allocated_amount']);
        $this->assertSame(3000.0, $plan['advance_credit']);
        $this->assertSame(0.0, $plan['family_balance_after']);
    }

    #[Test]
    public function it_never_allocates_more_than_a_child_balance_or_returns_negative_money(): void
    {
        $plan = $this->plan([3604.44, 3720.00, 3862.22, 4073.33], 18000);

        $this->assertSame([3604.44, 3720.0, 3862.22, 4073.33], array_column($plan['allocations'], 'applied_amount'));
        $this->assertSame([0.0, 0.0, 0.0, 0.0], array_column($plan['allocations'], 'remaining_after'));
        $this->assertSame(15259.99, $plan['allocated_amount']);
        $this->assertSame(2740.01, $plan['advance_credit']);
        $this->assertSame(0.0, $plan['family_balance_after']);
    }

    #[Test]
    public function it_treats_a_paid_billing_as_fully_settled_without_a_payment_row(): void
    {
        $billing = new SoaMonthlyBilling;
        $billing->amount_due = 4000;
        $billing->status = 'paid';
        $billing->verified_paid = 0;

        $method = new ReflectionMethod(FinanceAllocationService::class, 'effectiveBillingPaidCents');

        $this->assertSame(400000, $method->invoke(new FinanceAllocationService, $billing));
    }

    #[Test]
    public function it_applies_existing_family_credit_before_the_current_payment(): void
    {
        $plan = $this->creditPlan([3222.22, 5444.44], 333.34);

        $this->assertSame(8666.66, $plan['outstanding_before_credit']);
        $this->assertSame(333.34, $plan['credit_balance_before']);
        $this->assertSame(333.34, $plan['credit_applied']);
        $this->assertSame(8333.32, $plan['outstanding_after_credit']);
        $this->assertSame(0.0, $plan['remaining_credit']);
        $this->assertSame([2888.88, 5444.44], $plan['remaining_balances']->pluck('remaining')->all());
    }

    #[Test]
    public function it_preserves_credit_larger_than_the_current_bill(): void
    {
        $plan = $this->creditPlan([3000], 5000);

        $this->assertSame(3000.0, $plan['credit_applied']);
        $this->assertSame(0.0, $plan['outstanding_after_credit']);
        $this->assertSame(2000.0, $plan['remaining_credit']);
        $this->assertTrue($plan['remaining_balances']->isEmpty());
    }

    private function plan(array $balances, float $payment): array
    {
        $rows = collect($balances)->map(function ($remaining, $index) {
            $billing = new SoaMonthlyBilling;
            $billing->id = $index + 1;

            return [
                'billing' => $billing,
                'remaining_cents' => (int) round($remaining * 100),
            ];
        });

        $method = new ReflectionMethod(FinanceAllocationService::class, 'buildPlan');

        return $method->invoke(new FinanceAllocationService, $rows, $payment);
    }

    private function creditPlan(array $balances, float $credit): array
    {
        $rows = collect($balances)->map(function ($remaining, $index) {
            $billing = new SoaMonthlyBilling;
            $billing->id = $index + 1;

            return [
                'billing' => $billing,
                'remaining' => $remaining,
                'remaining_cents' => (int) round($remaining * 100),
            ];
        });

        $method = new ReflectionMethod(FinanceAllocationService::class, 'buildCreditPlan');

        return $method->invoke(new FinanceAllocationService, $rows, $credit);
    }
}
