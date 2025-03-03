<?php

namespace Database\Seeders;

use App\Models\PortfolioSnapshot;
use Illuminate\Database\Seeder;

class PortfolioSnapshotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PortfolioSnapshot::factory()->count(100000)->create();
    }
}
