<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'portfolio_id' => Portfolio::factory(),
            'coin_id' => Coin::factory(),
            'quantity' => $this->faker->randomFloat(8, 0, 9999999999.99999999),
            'buy_price' => $this->faker->randomFloat(8, 0, 9999999999.99999999),
            'transaction_type' => $this->faker->randomElement(["buy","sell"]),
        ];
    }
}
