<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\ClassAdvisoryAssignment;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeacherAdminAccessTest extends TestCase
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

    #[Test]
    public function elevated_admin_roles_are_not_treated_as_view_only(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $superAdmin->roles()->sync([Role::where('slug', 'super_admin')->value('id')]);

        $this->assertFalse($superAdmin->fresh('roles.permissions')->isViewOnlyAccess());

        $this->actingAs($superAdmin)
            ->get(route('admin.administration.users.create'))
            ->assertOk()
            ->assertSeeText('Create Account');
    }

    #[Test]
    public function teacher_admin_role_can_only_view_enrollment_and_student_modules(): void
    {
        $teacher = User::factory()->create([
            'role' => 'staff',
            'account_status' => 'verified',
        ]);

        $teacher->roles()->sync([Role::where('slug', 'teacher')->value('id')]);

        $this->actingAs($teacher)
            ->get(route('admin.applications.enrollment'))
            ->assertOk()
            ->assertDontSeeText('Main Dashboard')
            ->assertDontSeeText('Applicant Review')
            ->assertDontSeeText('Approval Workflow');

        $this->actingAs($teacher)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertDontSeeText('Family Accounts')
            ->assertDontSeeText('Account Onboarding');

        $this->actingAs($teacher)
            ->get(route('admin.applications.review'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.students.families'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.students.accounts'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.students.index', ['print_credentials' => 1]))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.finance.dashboard'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.access-control.roles.index'))
            ->assertForbidden();
    }

    #[Test]
    public function teacher_admin_role_cannot_submit_write_actions(): void
    {
        $teacher = User::factory()->create([
            'role' => 'staff',
            'account_status' => 'verified',
        ]);

        $teacher->roles()->sync([Role::where('slug', 'teacher')->value('id')]);

        $parent = User::factory()->create([
            'role' => 'applicant',
        ]);

        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Amina',
            'last_name' => 'Teacher View',
            'gender' => 'female',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'Face-to-Face',
            'school_year' => '2026-2027',
            'status' => 'submitted',
        ]);

        $this->actingAs($teacher)
            ->patch(route('admin.applicants.status', $applicant), [
                'status' => 'approved',
            ])
            ->assertForbidden();

        $this->assertSame('submitted', $applicant->fresh()->status);
    }

    #[Test]
    public function teacher_admin_role_is_limited_to_assigned_grade_level(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher.grade1@amis.edu.ph',
            'username' => 'teacher.grade1',
            'role' => 'staff',
            'account_status' => 'verified',
        ]);

        $teacher->roles()->sync([Role::where('slug', 'teacher')->value('id')]);

        $section = Section::create([
            'name' => 'Grade 1 Advisory',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'Face-to-Face',
            'gender' => 'male',
        ]);

        ClassAdvisoryAssignment::create([
            'section_id' => $section->id,
            'teacher_key' => 'teacher.grade1',
            'teacher_name' => 'Teacher Grade One',
            'teacher_email' => 'teacher.grade1@amis.edu.ph',
            'school_year' => '2026-2027',
            'status' => 'active',
        ]);

        $parent = User::factory()->create(['role' => 'applicant']);

        $gradeOneApplicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Visible',
            'last_name' => 'Gradeone',
            'gender' => 'male',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'Face-to-Face',
            'school_year' => '2026-2027',
            'status' => 'submitted',
        ]);

        $gradeTwoApplicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Hidden',
            'last_name' => 'Gradetwo',
            'gender' => 'male',
            'student_type' => 'New',
            'grade_level' => 'Grade 2',
            'learning_mode' => 'Face-to-Face',
            'school_year' => '2026-2027',
            'status' => 'submitted',
        ]);

        $gradeOneStudent = Student::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $gradeOneApplicant->id,
            'student_number' => 'STU-G1',
            'school_email' => 'visible@amis.edu.ph',
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
        ]);

        $gradeTwoStudent = Student::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $gradeTwoApplicant->id,
            'student_number' => 'STU-G2',
            'school_email' => 'hidden@amis.edu.ph',
            'grade_level' => 'Grade 2',
            'school_year' => '2026-2027',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.applications.enrollment'))
            ->assertOk()
            ->assertSeeText('VISIBLE GRADEONE')
            ->assertDontSeeText('HIDDEN GRADETWO');

        $this->actingAs($teacher)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSeeText('VISIBLE GRADEONE')
            ->assertDontSeeText('HIDDEN GRADETWO');

        $this->actingAs($teacher)
            ->get(route('admin.applicants.show', $gradeTwoApplicant))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.students.show', $gradeTwoStudent))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.students.show', $gradeOneStudent))
            ->assertOk();
    }
}
