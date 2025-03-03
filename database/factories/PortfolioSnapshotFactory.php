<?php

namespace Database\Factories;

use App\Models\Portfolio;
use App\Models\PortfolioSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PortfolioSnapshot>
 */
class PortfolioSnapshotFactory extends Factory
{
    protected $model = PortfolioSnapshot::class;
    protected $minutesCount = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Increment by 5 minutes each time
        $this->minutesCount += 5;

        // Generate a slightly random value that creates a realistic price chart
        // This creates smoother transitions between data points
        $baseValue = 5000; // Starting value
        $volatility = 0.02; // 2% volatility
        $randomFactor = fake()->randomFloat(2, -$baseValue * $volatility, $baseValue * $volatility);
        $trend = sin($this->minutesCount / 480 * 3.14159) * 500; // Slight sine wave pattern over time
        $totalValue = $baseValue + $randomFactor + $trend;

        return [
            'portfolio_id' => Portfolio::factory(),
            'total_portfolio_value' => max(100, $totalValue),
            'recorded_at' => now()->subMinutes($this->minutesCount),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
