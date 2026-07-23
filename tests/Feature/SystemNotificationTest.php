<?php

namespace Tests\Feature;

use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemNotificationTest extends TestCase
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
    public function system_notification_can_be_dispatched_and_fetched_via_api()
    {
        $admin = $this->createAdmin();

        SystemNotification::notifyAdmin(
            'Test Notification',
            'System backup snapshot created successfully.',
            'success',
            '/system-management/backups'
        );

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'unread_count' => 1,
        ]);
        $response->assertJsonFragment([
            'title' => 'Test Notification',
            'type' => 'success',
        ]);
    }

    /** @test */
    public function notification_can_be_marked_as_read_and_cleared()
    {
        $admin = $this->createAdmin();

        $notification = SystemNotification::create([
            'user_id' => $admin->id,
            'title' => 'Unread Alert',
            'message' => 'Something happened',
            'type' => 'info',
            'is_read' => false,
        ]);

        $this->actingAs($admin)->post(route('admin.notifications.read', $notification->id));

        $this->assertTrue($notification->fresh()->is_read);

        $this->actingAs($admin)->delete(route('admin.notifications.clear'));

        $this->assertDatabaseMissing('system_notifications', ['id' => $notification->id]);
    }
}
