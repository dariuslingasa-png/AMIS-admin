<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyAccountsTest extends TestCase
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
    public function guests_and_non_admins_cannot_access_family_accounts()
    {
        $response = $this->get(route('admin.students.families'));
        $response->assertRedirect(route('admin.login'));

        $nonAdmin = User::factory()->create([
            'role' => 'student',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($nonAdmin)->get(route('admin.students.families'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admins_can_access_family_accounts_and_see_ledger_computations()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $parent = User::factory()->create([
            'name' => 'Parent Lingasa',
            'email' => 'parent.lingasa@gmail.com',
            'role' => 'applicant',
        ]);

        $applicant1 = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Fatima',
            'last_name' => 'Lingasa',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
            'status' => 'approved',
            'learning_mode' => 'flexible',
        ]);

        $student1 = Student::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant1->id,
            'student_number' => 'STU260003',
            'school_email' => 'fatima@amis.edu.ph',
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
        ]);

        $account1 = StudentAccount::create([
            'student_id' => $student1->id,
            'enrollment_applicant_id' => $applicant1->id,
            'school_year' => '2026-2027',
            'grade_level' => 'Grade 1',
            'tuition_fee' => 12000.00,
            'monthly_tuition' => 1200.00,
            'books_fee' => 3000.00,
            'gross_total' => 16900.00,
            'total_balance' => 12900.00,
            'amount_paid' => 4000.00,
            'remaining_balance' => 8000.00,
            'status' => 'partial',
        ]);

        $applicant2 = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Ahmad',
            'last_name' => 'Lingasa',
            'student_type' => 'New',
            'grade_level' => 'Grade 2',
            'school_year' => '2026-2027',
            'status' => 'approved',
            'learning_mode' => 'flexible',
        ]);

        $student2 = Student::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant2->id,
            'student_number' => 'STU260004',
            'school_email' => 'ahmad@amis.edu.ph',
            'grade_level' => 'Grade 2',
            'school_year' => '2026-2027',
        ]);

        $account2 = StudentAccount::create([
            'student_id' => $student2->id,
            'enrollment_applicant_id' => $applicant2->id,
            'school_year' => '2026-2027',
            'grade_level' => 'Grade 2',
            'tuition_fee' => 15000.00,
            'monthly_tuition' => 1500.00,
            'books_fee' => 3000.00,
            'gross_total' => 19900.00,
            'total_balance' => 15900.00,
            'amount_paid' => 15000.00,
            'remaining_balance' => 0.00,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.families'));

        $response->assertOk();
        $response->assertSeeText('Family Accounts');
        $response->assertSeeText('PARENT LINGASA');
        $response->assertSeeText('parent.lingasa@gmail.com');
        
        // Assert student links are rendered
        $response->assertSeeText('FATIMA LINGASA');
        $response->assertSeeText('AHMAD LINGASA');
        
        // Assert consolidated ledger calculations
        // Gross: 12000 + 15000 = 27000
        // Paid: 4000 + 15000 = 19000
        // Balance: 8000 + 0 = 8000
        $response->assertSeeText('₱27,000.00');
        $response->assertSeeText('₱19,000.00');
        $response->assertSeeText('₱8,000.00');
    }

    /** @test */
    public function admins_can_search_family_accounts()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $parent = User::factory()->create([
            'name' => 'Unique Family Name',
            'email' => 'unique@example.com',
            'role' => 'applicant',
        ]);

        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Zayd',
            'last_name' => 'Harithah',
            'student_type' => 'New',
            'grade_level' => 'Grade 3',
            'school_year' => '2026-2027',
            'status' => 'approved',
            'learning_mode' => 'flexible',
        ]);

        $student = Student::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'student_number' => 'STU260005',
            'school_email' => 'zayd@amis.edu.ph',
            'grade_level' => 'Grade 3',
            'school_year' => '2026-2027',
        ]);

        // Search for matching name
        $response = $this->actingAs($admin)->get(route('admin.students.families', ['search' => 'Unique Family']));
        $response->assertOk();
        $response->assertSeeText('UNIQUE FAMILY NAME');

        // Search for non-matching name
        $response = $this->actingAs($admin)->get(route('admin.students.families', ['search' => 'NonExistent']));
        $response->assertOk();
        $response->assertDontSeeText('UNIQUE FAMILY NAME');
        $response->assertSeeText('No family accounts found');
    }
}
