<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Movement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movement>
 */
class MovementFactory extends Factory
{
    protected $model = Movement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'type' => Movement::TYPE_DEPOSIT,
            'amount' => fake()->randomFloat(2, 1, 10000),
        ];
    }
}