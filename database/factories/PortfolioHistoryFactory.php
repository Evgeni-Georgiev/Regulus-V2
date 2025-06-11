<?php

namespace Database\Factories;

use App\Enums\TransactionTypeEnum;
use App\Models\Portfolio;
use App\Models\PortfolioHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PortfolioHistory>
 */
class PortfolioHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PortfolioHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $previousValue = $this->faker->randomFloat(8, 100, 50000);
        $changeValue = $this->faker->randomFloat(8, -1000, 1000);
        $newValue = max(0, $previousValue + $changeValue);

        return [
            'portfolio_id' => Portfolio::factory(),
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'change_type' => $this->faker->randomElement(TransactionTypeEnum::values()),
            'change_value' => abs($changeValue),
            'changed_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
} 