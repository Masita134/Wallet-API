<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_has_one_account_with_an_initial_balance(): void
    {
        $user = User::factory()->create();
        $account = $user->account;

        $this->assertTrue($user->account->is($account));
        $this->assertSame('0.00', $account->fresh()->balance);
    }

    public function test_an_account_has_many_movements_and_each_movement_belongs_to_it(): void
    {
        $account = Account::factory()->create();
        $movement = Movement::factory()->for($account)->create([
            'type' => Movement::TYPE_TRANSFER_IN,
            'amount' => 125.50,
        ]);

        $this->assertTrue($account->movements->contains($movement));
        $this->assertTrue($movement->account->is($account));
        $this->assertSame('125.50', $movement->fresh()->amount);
    }
}