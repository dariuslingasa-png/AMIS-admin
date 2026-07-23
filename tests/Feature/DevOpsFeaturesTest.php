<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevOpsFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing()
    {
        return [
            '--path' => 'database/migrations/testing',
            '--realpath' => false,
            '--drop-views' => false,
            '--drop-types' => false,
        ];
    }

    protected function tearDown(): void
    {
        if (app()->isDownForMaintenance()) {
            \Illuminate\Support\Facades\Artisan::call('up');
        }
        parent::tearDown();
    }

    private function createSuperAdmin()
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'verified',
        ]);
    }

    private function createRegularAdmin()
    {
        return User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);
    }

    /** @test */
    public function guests_cannot_access_devops_control_center()
    {
        $this->get(route('admin.system-management.devops.index'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.system-management.devops.db-optimize'))
            ->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function regular_admins_cannot_access_devops_control_center()
    {
        $admin = $this->createRegularAdmin();

        $this->actingAs($admin)->get(route('admin.system-management.devops.index'))
            ->assertStatus(403);

        $this->actingAs($admin)->post(route('admin.system-management.devops.db-optimize'))
            ->assertStatus(403);
    }

    /** @test */
    public function super_admins_can_view_devops_control_center()
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.system-management.devops.index'));

        $response->assertOk();
        $response->assertSeeText('DevOps Operations');
        $response->assertSeeText('Environment Config Inspector');
        $response->assertSeeText('Database Schema');
        $response->assertSeeText('System Maintenance Mode Switch');
    }

    /** @test */
    public function super_admins_can_run_database_table_optimization()
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.system-management.devops.db-optimize'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function super_admins_can_toggle_system_maintenance_mode()
    {
        $superAdmin = $this->createSuperAdmin();

        // Enable maintenance mode
        $response = $this->actingAs($superAdmin)->post(route('admin.system-management.devops.maintenance'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(app()->isDownForMaintenance());

        // Bring back up via Artisan
        \Illuminate\Support\Facades\Artisan::call('up');
        $this->assertFalse(app()->isDownForMaintenance());
    }

    /** @test */
    public function super_admins_can_retry_and_flush_queue_jobs()
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.system-management.devops.queue.retry'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $response = $this->actingAs($superAdmin)->post(route('admin.system-management.devops.queue.flush'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
