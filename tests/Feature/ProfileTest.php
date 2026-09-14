<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_only_his_own_profile(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/v1/profile');

        $response
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->assertJsonStructure(['id', 'name', 'email'])
            ->assertJsonMissingPath('password');
    }

    public function test_profile_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_profile_rejects_an_invalid_token(): void
    {
        $response = $this->withToken('token-invalido')->getJson('/api/v1/profile');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }
}
