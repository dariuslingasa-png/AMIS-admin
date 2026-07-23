<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApplicantEmailRegistryTest extends TestCase
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

    /** @test */
    public function guest_cannot_access_email_registry()
    {
        $response = $this->post(route('admin.applicants.email-registry'), [
            'recipient_email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function non_admin_cannot_access_email_registry()
    {
        $nonAdmin = User::factory()->create([
            'role' => 'applicant',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($nonAdmin)->postJson(route('admin.applicants.email-registry'), [
            'recipient_email' => 'test@example.com',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_send_full_registry_email()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        // Create some applicants with payments
        $user1 = User::factory()->create(['email' => 'parent1@example.com']);
        $applicant1 = EnrollmentApplicant::create([
            'user_id' => $user1->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'status' => 'submitted',
            'student_type' => 'new',
            'grade_level' => 'Grade 1',
            'mother_first_name' => 'Jane',
            'mother_last_name' => 'Doe',
            'parent_email' => 'parent1@example.com',
        ]);

        Payment::create([
            'user_id' => $user1->id,
            'enrollment_applicant_id' => $applicant1->id,
            'amount' => 5000.00,
            'status' => 'verified',
            'receipt_url' => 'receipts/test1.jpg',
        ]);

        $expectedId = str_pad($applicant1->id, 4, '0', STR_PAD_LEFT);

        Mail::shouldReceive('send')
            ->once()
            ->withArgs(function ($view, $data, $callback) {
                if ($view !== 'emails.applicants-registry') {
                    return false;
                }
                $html = view($view, $data)->render();

                return str_contains(strtolower($html), 'john doe');
            });

        $response = $this->actingAs($admin)->postJson(route('admin.applicants.email-registry'), [
            'recipient_email' => 'office@amis.edu.ph',
            'payment_filter' => 'all',
            'limit_count' => 5,
            'message_body' => 'Test message body',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /** @test */
    public function admin_can_send_registry_email_with_limits()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        // Create 3 families
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = User::factory()->create(['email' => "parent{$i}@example.com"]);
            $applicant = EnrollmentApplicant::create([
                'user_id' => $user->id,
                'first_name' => "Child{$i}",
                'last_name' => "Family{$i}",
                'status' => 'submitted',
                'student_type' => 'new',
                'grade_level' => 'Grade 1',
                'mother_first_name' => "Mother{$i}",
                'mother_last_name' => "Family{$i}",
                'parent_email' => "parent{$i}@example.com",
            ]);
            $ids[] = $applicant->id;

            Payment::create([
                'user_id' => $user->id,
                'enrollment_applicant_id' => $applicant->id,
                'amount' => 5000.00,
                'status' => 'verified',
                'receipt_url' => "receipts/test{$i}.jpg",
            ]);
        }

        $presentId1 = str_pad($ids[2], 4, '0', STR_PAD_LEFT);
        $presentId2 = str_pad($ids[1], 4, '0', STR_PAD_LEFT);
        $absentId = str_pad($ids[0], 4, '0', STR_PAD_LEFT);

        Mail::shouldReceive('send')
            ->twice()
            ->withArgs(function ($view, $data, $callback) {
                if ($view !== 'emails.applicants-registry') {
                    return false;
                }
                $html = view($view, $data)->render();

                return (str_contains(strtolower($html), 'family3') || str_contains(strtolower($html), 'family2'))
                    && ! str_contains(strtolower($html), 'family1');
            });

        // Send with limit = 2
        $response = $this->actingAs($admin)->postJson(route('admin.applicants.email-registry'), [
            'recipient_email' => 'office@amis.edu.ph',
            'payment_filter' => 'all',
            'limit_count' => 2,
            'message_body' => 'Test with limit 2',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
