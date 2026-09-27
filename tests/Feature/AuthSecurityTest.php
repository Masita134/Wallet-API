<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_register_does_not_expose_sensitive_user_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan',
            'email' => 'juan@example.com',
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
}
