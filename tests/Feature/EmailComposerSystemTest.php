<?php

namespace Tests\Feature;

use App\Models\BulkEmailCampaign;
use App\Models\EmailTemplate;
use App\Models\Student;
use App\Models\User;
use App\Services\Email\EmailComposerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailComposerSystemTest extends TestCase
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

        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('category')->default('announcement');
                $table->string('subject');
                $table->longText('body_html');
                $table->boolean('is_preset')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bulk_email_campaigns')) {
            Schema::create('bulk_email_campaigns', function ($table) {
                $table->id();
                $table->string('title');
                $table->string('subject');
                $table->longText('body_html');
                $table->string('sender_email')->nullable();
                $table->string('sender_name')->default('AMIS Information Technology');
                $table->text('cc_emails')->nullable();
                $table->text('bcc_emails')->nullable();
                $table->string('recipient_type')->default('students');
                $table->string('recipient_filter')->nullable();
                $table->integer('recipient_count')->default(0);
                $table->integer('sent_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->string('status')->default('draft');
                $table->json('attachments_json')->nullable();
                $table->text('error_log')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function ($table) {
                $table->id();
                $table->string('mailer')->nullable();
                $table->string('transport')->nullable();
                $table->string('from_address')->nullable();
                $table->text('to_addresses')->nullable();
                $table->text('cc_addresses')->nullable();
                $table->text('bcc_addresses')->nullable();
                $table->string('subject')->nullable();
                $table->string('status')->default('sent');
                $table->text('error_message')->nullable();
                $table->integer('attachments_count')->default(0);
                $table->timestamp('sent_at')->nullable();
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
    public function recipient_resolver_loads_registered_student_emails()
    {
        Student::create([
            'school_email' => 'sara@example.com',
            'grade_level' => 'Grade 10',
        ]);

        $service = new EmailComposerService();
        $recipients = $service->resolveRecipients('students', 'Grade 10');

        $this->assertCount(1, $recipients);
        $this->assertArrayHasKey('sara@example.com', $recipients);
    }

    /** @test */
    public function attachment_validator_blocks_dangerous_file_extensions()
    {
        $this->expectException(\InvalidArgumentException::class);

        $fakeExe = UploadedFile::fake()->create('malicious.exe', 100);
        $service = new EmailComposerService();
        $service->validateAttachments([$fakeExe]);
    }

    /** @test */
    public function admin_can_view_email_composer_dashboard()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.email-composer.index'));

        $response->assertOk();
        $response->assertSeeText('Email Composer');
        $response->assertSeeText('Sent Today');
    }

    /** @test */
    public function admin_can_send_test_email()
    {
        Mail::fake();

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.email-composer.send-test'), [
            'subject' => 'Test Email Subject',
            'body_html' => '<p>Hello Test World</p>',
            'test_email' => 'admin.test@amis.edu.ph',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_can_dispatch_bulk_email_campaign()
    {
        Student::create([
            'school_email' => 'ahmad@example.com',
            'grade_level' => 'Grade 11',
        ]);

        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.email-composer.send-bulk'), [
            'title' => 'Grade 11 Advisory',
            'subject' => 'Important Class Announcement',
            'body_html' => '<h2>Announcement</h2><p>Please review scheduled exams.</p>',
            'recipient_type' => 'students',
            'recipient_filter' => 'all',
        ]);

        $response->assertRedirect(route('admin.email-composer.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bulk_email_campaigns', [
            'title' => 'Grade 11 Advisory',
            'recipient_type' => 'students',
            'recipient_count' => 1,
        ]);
    }
}
