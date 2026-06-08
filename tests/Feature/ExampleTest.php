<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_student_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_guest_cannot_access_student_dashboard(): void
    {
        $response = $this->get(route('student.dashboard'));

        $response->assertRedirect(route('student.login'));
    }
}
