<?php

namespace Database\Factories;

use App\Models\ApiFetchLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiFetchLog>
 */
class ApiFetchLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ApiFetchLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $success = $this->faker->boolean(85); // 85% success rate

        return [
            'type' => $this->faker->randomElement(['coin_prices', 'market_data', 'portfolio_sync', 'user_data']),
            'source' => $this->faker->randomElement(['coinmarketcap', 'coingecko', 'binance', 'internal_api']),
            'success' => $success,
            'error_message' => $success ? null : $this->faker->sentence(),
        ];
    }

    /**
     * Indicate that the API fetch was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => true,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the API fetch failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'error_message' => $this->faker->sentence(),
        ]);
    }
} 