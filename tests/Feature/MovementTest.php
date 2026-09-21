<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_consult_own_movements(): void
    {
        $user = User::factory()->create();

        $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '1000.00',
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'type',
                        'amount',
                        'date',
                        'counterparty_cbu',
                    ],
                ],
                'per_page',
                'total',
                'last_page',
                'next_page_url',
                'prev_page_url',
                'links',
            ])
            ->assertJsonPath('data.0.type', Movement::TYPE_DEPOSIT)
            ->assertJsonPath('data.0.amount', '1000.00')
            ->assertJsonPath('data.0.counterparty_cbu', null);
    }

    public function test_authenticated_user_can_see_counterparty_cbu(): void
    {
        $user = User::factory()->create();

        $user->account->movements()->create([
            'type' => Movement::TYPE_TRANSFER_IN,
            'amount' => '5000.00',
            'counterparty_cbu' => '2850590940090412345678',
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.counterparty_cbu',
                '2850590940090412345678'
            );
    }

    public function test_movements_are_limited_to_authenticated_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '1000.00',
        ]);

        $otherUser->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '9999.00',
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', '1000.00');

        $response->assertJsonMissing([
            'amount' => '9999.00',
        ]);
    }

    public function test_movements_are_paginated_with_fifteen_items_by_default(): void
    {
        $user = User::factory()->create();

        Movement::factory()
            ->count(20)
            ->for($user->account)
            ->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('total', 20)
            ->assertJsonPath('last_page', 2);

        $this->assertCount(15, $response->json('data'));
    }

    public function test_user_can_choose_number_of_movements_per_page(): void
    {
        $user = User::factory()->create();

        Movement::factory()
            ->count(20)
            ->for($user->account)
            ->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?per_page=5');

        $response
            ->assertOk()
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('total', 20);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_per_page_cannot_exceed_one_hundred(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?per_page=101');

        $response->assertUnprocessable();
    }

    public function test_per_page_must_be_a_positive_integer(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?per_page=0');

        $response->assertUnprocessable();
    }

    public function test_page_must_be_a_positive_integer(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?page=0');

        $response->assertUnprocessable();
    }

    public function test_default_order_is_descending_by_date(): void
    {
        $user = User::factory()->create();

        $oldMovement = $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '100.00',
        ]);

        $newMovement = $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '200.00',
        ]);

        $oldMovement->update([
            'created_at' => now()->subDay(),
        ]);

        $newMovement->update([
            'created_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.amount', '200.00')
            ->assertJsonPath('data.1.amount', '100.00');
    }

    public function test_user_can_order_movements_ascending_by_date(): void
    {
        $user = User::factory()->create();

        $oldMovement = $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '100.00',
        ]);

        $newMovement = $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '200.00',
        ]);

        $oldMovement->update([
            'created_at' => now()->subDay(),
        ]);

        $newMovement->update([
            'created_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?order=asc');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.amount', '100.00')
            ->assertJsonPath('data.1.amount', '200.00');
    }

    public function test_order_must_be_asc_or_desc(): void
    {
        $user = User::factory()->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?order=random');

        $response->assertUnprocessable();
    }

    public function test_page_navigation_works(): void
    {
        $user = User::factory()->create();

        Movement::factory()
            ->count(20)
            ->for($user->account)
            ->create();

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements?page=2');

        $response
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('total', 20)
            ->assertJsonPath('last_page', 2);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_movements_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/movements');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $response = $this
            ->withToken('token-invalido')
            ->getJson('/api/v1/movements');

        $response
            ->assertUnauthorized()
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_private_account_information_is_not_exposed(): void
    {
        $user = User::factory()->create();

        $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '1000.00',
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.account_id')
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_movements_with_same_date_have_a_deterministic_order(): void
    {
        $user = User::factory()->create();

        $firstMovement = $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '100.00',
        ]);

        $secondMovement = $user->account->movements()->create([
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => '200.00',
        ]);

        $sameDate = now()->startOfSecond();

        $firstMovement->update([
            'created_at' => $sameDate,
        ]);

        $secondMovement->update([
            'created_at' => $sameDate,
        ]);

        $token = auth('api')->login($user);

        $response = $this
            ->withToken($token)
            ->getJson('/api/v1/movements');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.amount', '200.00')
            ->assertJsonPath('data.1.amount', '100.00');
    }
}
