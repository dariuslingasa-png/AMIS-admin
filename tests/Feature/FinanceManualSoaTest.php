<?php

namespace Tests\Feature;

use App\Models\StudentManualSoa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceManualSoaTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_finance_can_upload_and_version_manual_soa(): void
    {
        $admin = User::factory()->create([
            'username' => 'test_admin_' . uniqid(),
            'role' => 'admin',
            'account_status' => 'verified',
            'active_admin_session_id' => null,
        ]);

        $file1 = UploadedFile::fake()->create('ahmad_july_soa_v1.pdf', 150, 'application/pdf');

        $response1 = $this->actingAs($admin)->post(route('admin.finance.manual-soa.upload', ['studentIdentifier' => 'AMIS-2026-DEMO-01']), [
            'soa_file' => $file1,
            'billing_month' => 'JULY 2026',
            'school_year' => '2026-2027',
            'student_name' => 'AHMAD Z. LINGASA',
            'family_email' => 'zhairel.lingasa@gmail.com',
            'grade_level' => 'Grade 1',
            'remarks' => 'Initial July SOA',
        ]);

        if ($response1->isRedirect() && session('errors')) {
            dump(session('errors')->all());
        }
        $response1->assertSessionHasNoErrors();
        $response1->assertRedirect();
        $this->assertDatabaseHas('student_manual_soas', [
            'student_identifier' => 'AMIS-2026-DEMO-01',
            'billing_month' => 'JULY 2026',
            'version' => 1,
            'is_current' => true,
        ]);

        // Upload Version 2 for the same student and month
        $file2 = UploadedFile::fake()->create('ahmad_july_soa_v2.pdf', 160, 'application/pdf');

        $response2 = $this->actingAs($admin)->post(route('admin.finance.manual-soa.upload', ['studentIdentifier' => 'AMIS-2026-DEMO-01']), [
            'soa_file' => $file2,
            'billing_month' => 'JULY 2026',
            'school_year' => '2026-2027',
            'student_name' => 'AHMAD Z. LINGASA',
            'family_email' => 'zhairel.lingasa@gmail.com',
            'grade_level' => 'Grade 1',
            'remarks' => 'Updated July SOA with revised discount',
        ]);

        $response2->assertRedirect();

        // Version 1 should now have is_current = false
        $this->assertDatabaseHas('student_manual_soas', [
            'student_identifier' => 'AMIS-2026-DEMO-01',
            'billing_month' => 'JULY 2026',
            'version' => 1,
            'is_current' => false,
        ]);

        // Version 2 should have is_current = true
        $this->assertDatabaseHas('student_manual_soas', [
            'student_identifier' => 'AMIS-2026-DEMO-01',
            'billing_month' => 'JULY 2026',
            'version' => 2,
            'is_current' => true,
        ]);

        $v2Record = StudentManualSoa::query()
            ->where('student_identifier', 'AMIS-2026-DEMO-01')
            ->where('billing_month', 'JULY 2026')
            ->where('version', 2)
            ->firstOrFail();

        // Test View & Download
        $viewResponse = $this->actingAs($admin)->get(route('admin.finance.manual-soa.view', $v2Record));
        $viewResponse->assertStatus(200);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.finance.manual-soa.download', $v2Record));
        $downloadResponse->assertStatus(200);

        // Delete Version 2 and verify Version 1 is restored to current
        $deleteResponse = $this->actingAs($admin)->delete(route('admin.finance.manual-soa.delete', $v2Record));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('student_manual_soas', [
            'id' => $v2Record->id,
        ]);

        $this->assertDatabaseHas('student_manual_soas', [
            'student_identifier' => 'AMIS-2026-DEMO-01',
            'billing_month' => 'JULY 2026',
            'version' => 1,
            'is_current' => true,
        ]);
    }

    public function test_family_payment_can_be_recorded_from_student_view(): void
    {
        $admin = User::factory()->create([
            'username' => 'test_admin_' . uniqid(),
            'role' => 'admin',
            'account_status' => 'verified',
            'active_admin_session_id' => null,
        ]);

        $paymentResponse = $this->actingAs($admin)->post(route('admin.finance.onsite.store'), [
            'user_id' => '999001', // Demo family ID
            'return_to' => 'family',
            'payment_method' => 'cash',
            'amount' => '3000.00',
            'remarks' => 'Settled cash payment at counter via Family SOA view.',
        ]);

        $paymentResponse->assertRedirect(route('admin.finance.families.show', ['family' => '999001']));
        $paymentResponse->assertSessionHas('success');
    }

    public function test_official_school_soa_can_be_viewed(): void
    {
        $admin = User::factory()->create([
            'username' => 'test_admin_' . uniqid(),
            'role' => 'admin',
            'account_status' => 'verified',
            'active_admin_session_id' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.students.official-soa', ['studentIdentifier' => 'AFPS-DEMO-2026-001-2']));
        $response->assertStatus(200);
        $response->assertSee('STATEMENT OF ACCOUNT SY 2026-2027');
        $response->assertSee('AHMAD Z. LINGASA');
    }
}
