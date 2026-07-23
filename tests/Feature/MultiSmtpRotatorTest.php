<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiSmtpRotatorTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function ($table) {
                $table->id();
                $table->string('reference_number')->nullable();
                $table->string('full_name')->nullable();
                $table->string('email')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('fb_or_whatsapp')->nullable();
                $table->string('student_full_name')->nullable();
                $table->string('grade_level')->nullable();
                $table->string('amis_id')->nullable();
                $table->string('concern_type')->nullable();
                $table->string('subject')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('open');
                $table->string('screenshot_path')->nullable();
                $table->timestamps();
            });
        }
    }

    private function createAdmin()
    {
        return User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);
    }

    /** @test */
    public function smtp_rotator_returns_available_configured_mailers()
    {
        $service = new SmartSmtpRotatorService;
        $pool = $service->getMailerPool();

        $this->assertIsArray($pool);
        $this->assertNotEmpty($pool);
    }

    /** @test */
    public function smtp_rotator_tracks_and_increments_daily_counts()
    {
        $service = new SmartSmtpRotatorService;
        $mailer = 'smtp';
        $initial = $service->getDailyCount($mailer);

        $service->incrementDailyCount($mailer);
        $this->assertEquals($initial + 1, $service->getDailyCount($mailer));
    }

    /** @test */
    public function admin_can_send_reply_email_with_uploaded_image_attachment()
    {
        Mail::fake();
        Storage::fake('public');

        $admin = $this->createAdmin();
        $ticket = SupportTicket::create([
            'reference_number' => 'AMIS-20260723-0001',
            'full_name' => 'John Parent',
            'email' => 'john.parent@example.com',
            'concern_type' => 'Enrollment Concern',
            'subject' => 'Need help with enrollment receipt',
            'description' => 'Attached receipt missing confirmation number.',
            'status' => 'open',
        ]);

        $imageFile = UploadedFile::fake()->create('receipt_proof.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($admin)->post(route('admin.support.reply', $ticket), [
            'subject' => 'Re: Need help with enrollment receipt',
            'message' => 'Hello John, we verified your payment. Please see attached confirmation screenshot.',
            'attachment' => $imageFile,
            'status' => 'resolved',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);
    }
}
