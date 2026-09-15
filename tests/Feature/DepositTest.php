<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_make_a_deposit(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/deposits', [
                'amount' => 1000,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'balance' => '1000.00',
            ]);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '1000.00',
        ]);

        $this->assertDatabaseHas('movements', [
            'account_id' => $user->account->id,
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '1000.00',
        ]);

        $this->assertDatabaseCount('movements', 1);
    }

    public function test_deposit_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/deposits', [
            'amount' => 1000,
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_deposit_rejects_zero_amount_without_changing_balance_or_movements(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/deposits', [
                'amount' => 0,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '0.00',
        ]);

        $this->assertDatabaseCount('movements', 0);
    }

    public function test_deposit_rejects_negative_amount_without_changing_balance_or_movements(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/deposits', [
                'amount' => -100,
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '0.00',
        ]);

        $this->assertDatabaseCount('movements', 0);
    }

    public function test_deposit_rejects_non_numeric_amount_without_changing_balance_or_movements(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/deposits', [
                'amount' => 'no-es-un-numero',
            ]);

        $response->assertUnprocessable();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '0.00',
        ]);

        $this->assertDatabaseCount('movements', 0);
    }

    public function test_deposit_requires_amount_without_changing_balance_or_movements(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/deposits');

        $response->assertUnprocessable();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '0.00',
        ]);

        $this->assertDatabaseCount('movements', 0);
    }

    public function test_deposit_only_affects_the_authenticated_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->postJson('/api/v1/deposits', [
                'amount' => 500,
                'account_id' => $otherUser->account->id,
                'user_id' => $otherUser->id,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'balance' => '500.00',
            ]);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'balance' => '500.00',
        ]);

        $this->assertDatabaseHas('accounts', [
            'user_id' => $otherUser->id,
            'balance' => '0.00',
        ]);

        $this->assertDatabaseHas('movements', [
            'account_id' => $user->account->id,
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '500.00',
        ]);

        $this->assertDatabaseMissing('movements', [
            'account_id' => $otherUser->account->id,
        ]);

        $this->assertDatabaseCount('movements', 1);
    }

    public function test_deposit_rejects_invalid_token(): void
    {
        $response = $this
            ->withToken('token-invalido')
            ->postJson('/api/v1/deposits', [
                'amount' => 1000,
            ]);

        $response
            ->assertUnauthorized()
            ->assertJsonStructure([
                'message',
            ]);
    }
}