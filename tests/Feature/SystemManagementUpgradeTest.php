<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SystemManagementUpgradeTest extends TestCase
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

    private function createAdmin()
    {
        return User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);
    }

    /** @test */
    public function guests_cannot_access_system_management_upgrades()
    {
        $this->get(route('admin.system-management.logs.index'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.system-management.cache.clear'))
            ->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function admins_can_view_live_system_logs()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.system-management.logs.index'));

        $response->assertOk();
        $response->assertSeeText('Live System Error Log Viewer');
        $response->assertSeeText('laravel.log');
    }

    /** @test */
    public function admins_can_clear_system_caches()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.system-management.cache.clear'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admins_can_warmup_system_caches()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.system-management.cache.warmup'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admins_can_send_smtp_diagnostic_test_email()
    {
        Mail::fake();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.system-management.health.test-email'), [
            'email' => 'admin@amis.edu.ph',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admins_can_prune_old_backups()
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.system-management.backups.prune'), [
            'days' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admins_can_request_ping_diagnostics()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.system-management.health.ping'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'results' => [
                'mariadb',
                'gdrive',
                'm365',
            ],
            'timestamp',
        ]);
    }
}
