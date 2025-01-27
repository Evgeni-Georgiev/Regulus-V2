<?php

namespace Database\Seeders;

use App\Models\Coin;
use App\Models\Portfolio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortfolioCoinsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $portfolios = Portfolio::all();
        $coins = Coin::all();

        // Seed portfolio_coins with random data
        foreach ($portfolios as $portfolio) {
            // Attach random coins to each portfolio
            $portfolio->coins()->attach(
                $coins->random(rand(1, 5))->pluck('id')->toArray(),
                ['quantity' => fake()->randomFloat(8, 0.1, 100)]
            );
        }
    }
}
