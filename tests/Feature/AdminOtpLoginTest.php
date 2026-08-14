<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\SendAdminOtpCode;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminOtpLoginTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }

    #[Test]
    public function active_admin_can_request_and_use_a_one_time_email_code(): void
    {
        Notification::fake();
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'role' => 'admin',
            'account_status' => 'verified',
        ]);
        $admin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->postJson(route('admin.login.otp.send'), ['email' => $admin->email])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Notification::assertSentTo($admin, SendAdminOtpCode::class);
        $verification = VerificationCode::where('email', $admin->email)->latest('id')->firstOrFail();

        $this->postJson(route('admin.login.otp.verify'), [
            'email' => $admin->email,
            'code' => $verification->code,
        ])->assertOk()->assertJsonPath('redirectUrl', route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($verification->fresh()->used);
    }

    #[Test]
    public function applicant_accounts_cannot_request_admin_codes(): void
    {
        Notification::fake();
        $applicant = User::factory()->create([
            'email' => 'parent@example.test',
            'role' => 'applicant',
            'account_status' => 'verified',
        ]);

        $this->postJson(route('admin.login.otp.send'), ['email' => $applicant->email])
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error');

        Notification::assertNothingSent();
    }
}
