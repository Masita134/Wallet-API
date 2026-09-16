<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_transfer_money(): void
    {
        $originUser = User::factory()->create();
        $destinationUser = User::factory()->create();

        $originAccount = $originUser->account;
        $destinationAccount = $destinationUser->account;

        $originAccount->update([
            'balance' => 10000,
        ]);

        $token = auth('api')->login($originUser);

        $response = $this->withToken($token)->postJson('/api/v1/transfers', [
            'destination_cbu' => $destinationAccount->cbu,
            'amount' => 2500,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Transferencia realizada correctamente.',
                'transfer' => [
                    'destination_cbu' => $destinationAccount->cbu,
                    'amount' => 2500,
                ],
                'balance' => '7500.00',
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $originAccount->id,
            'balance' => 7500,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $destinationAccount->id,
            'balance' => 2500,
        ]);

        $this->assertDatabaseHas('movements', [
            'account_id' => $originAccount->id,
            'type' => Movement::TYPE_TRANSFER_OUT,
            'amount' => 2500,
        ]);

        $this->assertDatabaseHas('movements', [
            'account_id' => $destinationAccount->id,
            'type' => Movement::TYPE_TRANSFER_IN,
            'amount' => 2500,
        ]);
    }

    public function test_transfer_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/transfers', [
            'destination_cbu' => '1234567890123456789012',
            'amount' => 1000,
        ]);

        $response->assertUnauthorized();
    }

    public function test_transfer_rejects_insufficient_balance(): void
    {
        $originUser = User::factory()->create();
        $destinationUser = User::factory()->create();

        $originAccount = $originUser->account;
        $destinationAccount = $destinationUser->account;

        $originAccount->update([
            'balance' => 500,
        ]);

        $token = auth('api')->login($originUser);

        $response = $this->withToken($token)->postJson('/api/v1/transfers', [
            'destination_cbu' => $destinationAccount->cbu,
            'amount' => 1000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Saldo insuficiente.',
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $originAccount->id,
            'balance' => 500,
        ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $destinationAccount->id,
            'balance' => 0,
        ]);
    }

    public function test_transfer_rejects_own_account(): void
    {
        $user = User::factory()->create();

        $account = $user->account;

        $account->update([
            'balance' => 5000,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/transfers', [
            'destination_cbu' => $account->cbu,
            'amount' => 1000,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'No puedes transferir dinero a tu propia cuenta.',
            ]);
    }

    public function test_transfer_rejects_nonexistent_destination(): void
    {
        $user = User::factory()->create();

        $user->account->update([
            'balance' => 5000,
        ]);

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->postJson('/api/v1/transfers', [
            'destination_cbu' => '9999999999999999999999',
            'amount' => 1000,
        ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'La cuenta destino no existe.',
            ]);
    }

    public function test_transfer_rejects_invalid_amount(): void
    {
        $originUser = User::factory()->create();
        $destinationUser = User::factory()->create();

        $token = auth('api')->login($originUser);

        $response = $this->withToken($token)->postJson('/api/v1/transfers', [
            'destination_cbu' => $destinationUser->account->cbu,
            'amount' => 0,
        ]);

        $response->assertStatus(422);
    }
}