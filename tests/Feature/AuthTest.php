<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'message' => 'Usuario registrado exitosamente.',
            ])
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'account' => [
                        'id',
                        'cbu',
                        'balance',
                    ],
                ],
            ])
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'is_admin' => false,
        ]);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $response->json('user.id'),
        ]);
    }

    public function test_a_user_cannot_register_with_an_existing_email(): void
    {
        User::factory()->create([
            'email' => 'juan@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Otro Juan',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_a_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'password-incorrecta',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => 'Credenciales inválidas.',
            ])
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('access_token');
    }

    public function test_an_authenticated_user_can_view_his_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ])
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'account' => [
                        'id',
                        'cbu',
                        'balance',
                    ],
                ],
            ])
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_an_unauthenticated_user_cannot_view_his_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_an_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->postJson('/api/v1/auth/logout');

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Sesión cerrada correctamente.',
            ]);
    }

    public function test_an_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_an_invalid_token_cannot_access_protected_route(): void
    {
        $response = $this->withHeader(
            'Authorization',
            'Bearer token-invalido'
        )->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_register_does_not_expose_sensitive_user_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan',
            'email' => 'juan-sensitive@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_authenticated_profile_does_not_expose_sensitive_user_data(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(200)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');
    }

    public function test_registration_cannot_create_an_admin(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Potential Admin',
            'email' => 'potential-admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => true,
        ]);

        $response
            ->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'potential-admin@example.com',
            'is_admin' => false,
        ]);
    }
    public function test_api_not_found_returns_clean_json_without_internal_details(): void
    {
        $response = $this->get('/api/v1/ruta-que-no-existe');

        $response
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'message' => 'Recurso no encontrado.',
            ])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('file')
            ->assertJsonMissingPath('line')
            ->assertJsonMissingPath('trace');
   
    }
    public function test_api_unauthorized_without_accept_header_returns_json(): void
    {
        $response = $this->get('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'message' => 'No autenticado.',
            ]);
    }
    public function test_api_validation_error_without_accept_header_returns_json(): void
    {
        $response = $this->post('/api/v1/auth/register', []);

        $response
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }
    public function test_login_is_limited_after_five_failed_attempts(): void
    {
        $email = 'ratelimit@example.com';

        User::factory()->create([
            'email' => $email,
            'password' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);

            $response->assertStatus(401);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(429)
            ->assertJsonStructure([
                'message',
                'retry_after',
            ])
            ->assertHeader('Retry-After');
    }
}
