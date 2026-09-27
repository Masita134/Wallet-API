<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_admin_route(): void
    {
        $response = $this->getJson('/api/v1/admin/test');

        $response
            ->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_regular_user_cannot_access_admin_route(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/admin/test');

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para acceder a este recurso.',
            ]);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $token = auth('api')->login($admin);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/admin/test');

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Acceso administrativo autorizado.',
            ]);
    }

    public function test_user_admin_flag_is_cast_to_boolean(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->assertIsBool($user->is_admin);
        $this->assertTrue($user->is_admin);
        $this->assertTrue($user->isAdmin());
    }

    public function test_regular_user_admin_flag_is_false(): void
    {
        $user = User::factory()->create();

        $this->assertIsBool($user->is_admin);
        $this->assertFalse($user->is_admin);
        $this->assertFalse($user->isAdmin());
    }
    public function test_api_forbidden_without_accept_header_returns_json(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->get('/api/v1/admin/test');

        $response
            ->assertStatus(403)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'message' => 'No tiene permisos para acceder a este recurso.',
            ]);
    }
}
