<?php

namespace Tests\Feature;

use App\Mail\BackupSuccessMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomaticBackupSystemTest extends TestCase
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
    public function automated_backup_command_creates_backup_and_sends_email()
    {
        Mail::fake();

        $exitCode = Artisan::call('amis:backup');

        $this->assertEquals(0, $exitCode);

        Mail::assertSent(BackupSuccessMail::class, function ($mail) {
            return $mail->hasTo('darius.lingasa@gmail.com');
        });

        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/*.zip');
        $this->assertNotEmpty($files, 'ZIP backup file was not created in storage/app/backups.');
    }

    /** @test */
    public function super_admin_can_access_backup_dashboard()
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.system-management.backups.index'));

        $response->assertOk();
        $response->assertSeeText('Database & System Backup Center');
    }

    /** @test */
    public function regular_admin_cannot_download_or_restore_backups()
    {
        $admin = $this->createRegularAdmin();

        $response = $this->actingAs($admin)->post(route('admin.system-management.backups.restore'), [
            'filename' => 'amis_backup_test.zip',
            'confirmation' => 'RESTORE',
        ]);

        $response->assertStatus(403);
    }
}
