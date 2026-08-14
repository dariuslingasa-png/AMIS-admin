<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinanceWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => 'database/migrations/testing',
            '--realpath' => false,
            '--drop-views' => false,
            '--drop-types' => false,
        ];
    }

    #[Test]
    public function finance_admin_can_open_the_new_finance_workspace(): void
    {
        $finance = User::factory()->create([
            'role' => 'finance',
            'account_status' => 'verified',
        ]);
        $finance->roles()->sync([Role::where('slug', 'finance')->value('id')]);

        $this->actingAs($finance)
            ->get(route('admin.finance.dashboard'))
            ->assertOk()
            ->assertSeeText('Finance Dashboard')
            ->assertSeeText('Payment Verification')
            ->assertSeeText('Record Onsite Payment')
            ->assertSeeText('Family Accounts / SOA')
            ->assertSeeText('Official Receipts');
    }

    #[Test]
    public function finance_admin_can_view_record_onsite_payment_search_and_selected_family_page(): void
    {
        $finance = User::factory()->create([
            'role' => 'finance',
            'account_status' => 'verified',
        ]);
        $finance->roles()->sync([Role::where('slug', 'finance')->value('id')]);

        // 1. Search Mode
        $this->actingAs($finance)
            ->get(route('admin.finance.onsite.create'))
            ->assertOk()
            ->assertSeeText('Step 1: Find the family account')
            ->assertSeeText('Parent or guardian')
            ->assertSeeText('Student name or ID')
            ->assertSeeText('Registered email');

        // 2. Selected Demo Family Mode
        $this->actingAs($finance)
            ->get(route('admin.finance.onsite.create', ['family' => 999001]))
            ->assertOk()
            ->assertSeeText('Step 1 · Selected Family')
            ->assertSeeText('Step 2: Enter payment details')
            ->assertSeeText('Step 3: Family billing schedule')
            ->assertSeeText('Automatic oldest-first allocation')
            ->assertSeeText('Total amount due')
            ->assertSeeText('Confirm payment');
    }
}
