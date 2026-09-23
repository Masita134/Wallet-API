<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
        ]);
    }

    private function createUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_accounts(): void
    {
        $response = $this->getJson(
            '/api/v1/admin/accounts'
        );

        $response->assertUnauthorized();
    }

    public function test_regular_user_cannot_access_admin_accounts(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user, 'api')
            ->getJson('/api/v1/admin/accounts');

        $response->assertForbidden();
    }

    public function test_admin_can_list_accounts(): void
    {
        $admin = $this->createAdmin();

        User::factory()->count(5)->create();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/accounts');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'cbu',
                        'balance',
                        'type',
                        'currency',
                        'user',
                    ],
                ],
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_admin_can_create_account(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $payload = [
            'user_id' => $user->id,
            'cbu' => '1234567890123456789012',
            'balance' => 1500.50,
            'type' => 'checking',
            'currency' => 'USD',
        ];

        /*
         * El usuario creado por el factory ya tiene una cuenta.
         * Para este test necesitamos un usuario sin cuenta.
         */
        $user->account->delete();

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson(
                '/api/v1/admin/accounts',
                $payload
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Cuenta creada correctamente.'
            )
            ->assertJsonPath(
                'account.user_id',
                $user->id
            )
            ->assertJsonPath(
                'account.cbu',
                '1234567890123456789012'
            )
            ->assertJsonPath(
                'account.type',
                'checking'
            )
            ->assertJsonPath(
                'account.currency',
                'USD'
            );
    }

    public function test_admin_cannot_create_account_for_user_with_existing_account(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/accounts', [
                'user_id' => $user->id,
                'cbu' => '1234567890123456789012',
                'balance' => 0,
                'type' => 'savings',
                'currency' => 'ARS',
            ]);

        /*
         * La unicidad de user_id debe impedir una segunda cuenta.
         */
        $response->assertUnprocessable();
    }

    public function test_admin_cannot_create_account_with_duplicate_cbu(): void
    {
        $admin = $this->createAdmin();

        $userA = $this->createUser();
        $userB = $this->createUser();

        $userA->account->delete();

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson('/api/v1/admin/accounts', [
                'user_id' => $userA->id,
                'cbu' => $userB->account->cbu,
                'balance' => 0,
                'type' => 'savings',
                'currency' => 'ARS',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cbu']);
    }

    public function test_admin_can_show_account(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $account = $user->account;

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson(
                "/api/v1/admin/accounts/{$account->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'account.id',
                $account->id
            )
            ->assertJsonPath(
                'account.user_id',
                $user->id
            )
            ->assertJsonPath(
                'account.cbu',
                $account->cbu
            );
    }

    public function test_admin_can_update_account(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $account = $user->account;

        $response = $this
            ->actingAs($admin, 'api')
            ->putJson(
                "/api/v1/admin/accounts/{$account->id}",
                [
                    'type' => 'checking',
                    'currency' => 'USD',
                    'balance' => 2500.75,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'account.type',
                'checking'
            )
            ->assertJsonPath(
                'account.currency',
                'USD'
            )
            ->assertJsonPath(
                'account.balance',
                '2500.75'
            );
    }

    public function test_admin_can_change_account_user(): void
    {
        $admin = $this->createAdmin();

        $userA = $this->createUser();
        $userB = $this->createUser();

        $account = $userA->account;

        $userB->account->delete();

        $response = $this
            ->actingAs($admin, 'api')
            ->putJson(
                "/api/v1/admin/accounts/{$account->id}",
                [
                    'user_id' => $userB->id,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'account.user_id',
                $userB->id
            );
    }

    public function test_admin_can_delete_account(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();

        $account = $user->account;

        $response = $this
            ->actingAs($admin, 'api')
            ->deleteJson(
                "/api/v1/admin/accounts/{$account->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Cuenta eliminada correctamente.'
            );

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_invalid_account_data_returns_422(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'api')
            ->postJson(
                '/api/v1/admin/accounts',
                [
                    'user_id' => 999999,
                    'cbu' => '123',
                    'balance' => -100,
                    'type' => 'invalid',
                    'currency' => 'EUR',
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'cbu',
                'balance',
                'type',
                'currency',
            ]);
    }

    public function test_invalid_pagination_returns_422(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson(
                '/api/v1/admin/accounts?per_page=101'
            );

        $response->assertUnprocessable();
    }

    public function test_invalid_sort_returns_422(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin, 'api')
            ->getJson(
                '/api/v1/admin/accounts?sort=password'
            );

        $response->assertUnprocessable();
    }
}