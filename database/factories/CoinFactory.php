<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Coin;

class CoinFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coin::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'symbol' => $this->faker->regexify('[A-Za-z0-9]{20}'),
            'price' => $this->faker->randomFloat(2, 0, 999999999999999999.99),
            'market_cap' => $this->faker->randomFloat(2, 0, 999999999999999999.99),
            'percent_change_1h' => $this->faker->randomFloat(2, 0, 999999.99),
            'percent_change_24h' => $this->faker->randomFloat(2, 0, 999999.99),
            'percent_change_7d' => $this->faker->randomFloat(2, 0, 999999.99),
            'volume_24h' => $this->faker->randomFloat(2, 0, 999999999999999999.99),
        ];
    }
}
