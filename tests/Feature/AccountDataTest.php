<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_account_data(): void
    {
        $user = User::factory()->create([
            'age' => 35,
            'image' => 'profile.jpg',
        ]);

        $account = Account::where('user_id', $user->id)->first();

        $account->update([
            'type' => 'savings',
            'currency' => 'ARS',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $token
        )->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJsonPath('account.cbu', $account->cbu)
            ->assertJsonPath('account.balance', '0.00')
            ->assertJsonPath('account.type', 'savings')
            ->assertJsonPath('account.currency', 'ARS');
    }

    public function test_account_has_default_type_and_currency(): void
    {
        $user = User::factory()->create();

        $account = Account::where('user_id', $user->id)->first();

        $this->assertNotNull($account);
        $this->assertEquals('savings', $account->type);
        $this->assertEquals('ARS', $account->currency);
    }

    public function test_user_can_store_age_and_image(): void
    {
        $user = User::factory()->create([
            'age' => 35,
            'image' => 'profile.jpg',
        ]);

        $this->assertEquals(35, $user->age);
        $this->assertEquals('profile.jpg', $user->image);
    }

    public function test_account_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/account');

        $response->assertStatus(401);
    }

    public function test_user_cannot_access_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAccount = $otherUser->account;

        $token = auth('api')->login($user);

        $response = $this->withToken($token)->getJson('/api/v1/account');

        $response
            ->assertStatus(200)
            ->assertJsonPath('account.cbu', $user->account->cbu)
            ->assertJsonPath('account.cbu', fn ($cbu) => $cbu !== $otherAccount->cbu);
    }
}