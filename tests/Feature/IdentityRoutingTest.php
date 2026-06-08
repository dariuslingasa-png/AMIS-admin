<?php

namespace Tests\Feature;

use App\Models\LinkedIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdentityRoutingTest extends TestCase
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

    private function createMockJwt(array $claims): string
    {
        $header = base64_encode((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode((string) json_encode($claims));
        $header = str_replace(['+', '/', '='], ['-', '_', ''], $header);
        $payload = str_replace(['+', '/', '='], ['-', '_', ''], $payload);
        return "{$header}.{$payload}.signature";
    }

    /** @test */
    public function login_fails_if_email_not_registered()
    {
        $jwt = $this->createMockJwt([
            'email' => 'unregistered@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response = $this->postJson('/api/identity/login', [
            'token' => $jwt
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'ERR_ACCOUNT_NOT_REGISTERED'
        ]);
    }

    /** @test */
    public function login_succeeds_and_routes_teacher()
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'account_status' => 'verified',
        ]);

        LinkedIdentity::create([
            'user_id' => $user->id,
            'microsoft_email' => 'teacher@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'GENERATED',
        ]);

        $jwt = $this->createMockJwt([
            'email' => 'teacher@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response = $this->postJson('/api/identity/login', [
            'token' => $jwt
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'route',
            'user' => ['id', 'name', 'role', 'email']
        ]);
        $response->assertJson([
            'route' => '/portal/teacher/dashboard',
            'user' => [
                'role' => 'teacher',
                'email' => $user->email,
            ]
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_succeeds_and_routes_student()
    {
        $user = User::factory()->create([
            'role' => 'student',
            'account_status' => 'verified',
        ]);

        LinkedIdentity::create([
            'user_id' => $user->id,
            'microsoft_email' => 'student@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'GENERATED',
        ]);

        $jwt = $this->createMockJwt([
            'email' => 'student@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response = $this->postJson('/api/identity/login', [
            'token' => $jwt
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'route' => '/portal/student/dashboard',
            'user' => [
                'role' => 'student',
                'email' => $user->email,
            ]
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_fails_for_disabled_user()
    {
        $user = User::factory()->create([
            'role' => 'student',
            'account_status' => 'suspended',
        ]);

        LinkedIdentity::create([
            'user_id' => $user->id,
            'microsoft_email' => 'disabled@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'GENERATED',
        ]);

        $jwt = $this->createMockJwt([
            'email' => 'disabled@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response = $this->postJson('/api/identity/login', [
            'token' => $jwt
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'ERR_ACCOUNT_DISABLED'
        ]);
    }

    /** @test */
    public function link_requires_authentication()
    {
        $response = $this->postJson('/api/identity/link', [
            'email' => 'legacy@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function link_requires_existing_generated_identity()
    {
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        $response = $this->actingAs($user)->postJson('/api/identity/link', [
            'email' => 'legacy@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'ERR_GENERATED_IDENTITY_REQUIRED'
        ]);
    }

    /** @test */
    public function link_prevents_linking_duplicate_email()
    {
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        LinkedIdentity::create([
            'user_id' => $user->id,
            'microsoft_email' => 'generated@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'GENERATED',
        ]);

        // Create another identity linked to someone else
        $otherUser = User::factory()->create();
        LinkedIdentity::create([
            'user_id' => $otherUser->id,
            'microsoft_email' => 'legacy@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'LEGACY',
        ]);

        $jwt = $this->createMockJwt([
            'email' => 'legacy@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response = $this->actingAs($user)->postJson('/api/identity/link', [
            'token' => $jwt
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'ERR_EMAIL_ALREADY_LINKED'
        ]);
    }

    /** @test */
    public function link_successfully_adds_legacy_identity()
    {
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        LinkedIdentity::create([
            'user_id' => $user->id,
            'microsoft_email' => 'generated@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'GENERATED',
        ]);

        $jwt = $this->createMockJwt([
            'email' => 'legacy@amis.edu.ph',
            'tid' => 'mock-tenant-id'
        ]);

        $response = $this->actingAs($user)->postJson('/api/identity/link', [
            'token' => $jwt
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success'
        ]);

        $this->assertDatabaseHas('linked_identities', [
            'user_id' => $user->id,
            'microsoft_email' => 'legacy@amis.edu.ph',
            'tenant_id' => 'mock-tenant-id',
            'identity_type' => 'LEGACY',
        ]);
    }
}
