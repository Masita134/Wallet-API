<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_a_third_party_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson("/api/v1/cbu/{$otherUser->account->cbu}/users/{$user->id}");

        $response->assertOk();

        $this->assertDatabaseHas('saved_accounts', [
            'user_id' => $user->id,
            'account_id' => $otherUser->account->id,
        ]);
    }
    public function test_cannot_save_a_non_existing_cbu(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson("/api/v1/cbu/9999999999999999999999/users/{$user->id}");

        $response->assertStatus(404);

        $response->assertJson([
            'message' => 'La cuenta no existe.',
        ]);

        $this->assertDatabaseCount('saved_accounts', 0);
    }
    public function test_cannot_save_own_account(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson("/api/v1/cbu/{$user->account->cbu}/users/{$user->id}");

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'No puedes guardar tu propia cuenta.',
        ]);

        $this->assertDatabaseCount('saved_accounts', 0);
    }
    public function test_cannot_save_the_same_account_twice(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $token = auth('api')->login($user);

        $this->withToken($token)
            ->postJson("/api/v1/cbu/{$otherUser->account->cbu}/users/{$user->id}")
            ->assertOk();

        $response = $this
            ->withToken($token)
            ->postJson("/api/v1/cbu/{$otherUser->account->cbu}/users/{$user->id}");

        $response->assertStatus(422);

        $response->assertJson([
            'message' => 'La cuenta ya está guardada.',
        ]);

        $this->assertDatabaseCount('saved_accounts', 1);
    }
    public function test_cannot_save_account_for_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $thirdUser = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson("/api/v1/cbu/{$thirdUser->account->cbu}/users/{$otherUser->id}");

        $response->assertStatus(403);

        $response->assertJson([
            'message' => 'No puedes modificar la lista de otro usuario.',
        ]);

        $this->assertDatabaseCount('saved_accounts', 0);
    }
}
