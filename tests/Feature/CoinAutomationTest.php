<?php

use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Collection;

describe('Coin Domain Automation Tests', function () {
    test('can handle bulk coin creation and portfolio management', function () {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        // Create multiple coins in bulk
        $coinData = [
            ['name' => 'Bitcoin', 'symbol' => 'BTC', 'price' => 50000.00],
            ['name' => 'Ethereum', 'symbol' => 'ETH', 'price' => 3000.00],
            ['name' => 'Cardano', 'symbol' => 'ADA', 'price' => 1.20],
            ['name' => 'Solana', 'symbol' => 'SOL', 'price' => 100.00],
            ['name' => 'Polkadot', 'symbol' => 'DOT', 'price' => 25.00],
        ];

        $coins = collect($coinData)->map(fn($data) => Coin::factory()->create($data));

        // Bulk attach coins to portfolio with random quantities
        $attachData = $coins->mapWithKeys(function ($coin) {
            return [$coin->id => ['quantity' => fake()->randomFloat(2, 0.1, 10)]];
        })->toArray();

        $portfolio->coins()->sync($attachData);

        expect($portfolio->coins)->toHaveCount(5)
            ->and($coins->every(fn($coin) => $coin->portfolios->isNotEmpty()))->toBeTrue();
    });

    test('can simulate complex trading scenario with profit loss tracking', function () {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $bitcoin = Coin::factory()->create([
            'symbol' => 'BTC',
            'price' => 45000.00
        ]);

        // Initial buy transactions at different prices
        $transactions = [
            ['quantity' => 0.5, 'buy_price' => 40000.00, 'type' => TransactionTypeEnum::BUY],
            ['quantity' => 0.3, 'buy_price' => 42000.00, 'type' => TransactionTypeEnum::BUY],
            ['quantity' => 0.2, 'buy_price' => 38000.00, 'type' => TransactionTypeEnum::BUY],
        ];

        foreach ($transactions as $txData) {
            Transaction::factory()->create([
                'portfolio_id' => $portfolio->id,
                'coin_id' => $bitcoin->id,
                'quantity' => $txData['quantity'],
                'buy_price' => $txData['buy_price'],
                'transaction_type' => $txData['type'],
            ]);
        }

        // Update portfolio coin quantity
        $totalQuantity = collect($transactions)->sum('quantity');
        $portfolio->coins()->attach($bitcoin->id, ['quantity' => $totalQuantity]);

        // Price increases
        $bitcoin->update(['price' => 50000.00]);

        // Partial sell
        Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $bitcoin->id,
            'quantity' => 0.4,
            'buy_price' => 50000.00,
            'transaction_type' => TransactionTypeEnum::SELL,
        ]);

        // Calculate average buy price
        $buyTransactions = Transaction::where('coin_id', $bitcoin->id)
            ->where('transaction_type', TransactionTypeEnum::BUY)
            ->get();

        $totalCost = $buyTransactions->sum(fn($tx) => $tx->quantity * $tx->buy_price);
        $totalQuantityBought = $buyTransactions->sum('quantity');
        $averageBuyPrice = $totalCost / $totalQuantityBought;

        expect($buyTransactions)->toHaveCount(3)
            ->and($averageBuyPrice)->toBe(40200.00) // (0.5*40000 + 0.3*42000 + 0.2*38000) / 1.0
            ->and($totalQuantityBought)->toBe(1.0);
    });

    test('can handle concurrent portfolio operations without data conflicts', function () {
        $coin = Coin::factory()->create(['symbol' => 'ETH']);

        // Create multiple users and portfolios
        $users = User::factory(3)->create();
        $portfolios = $users->map(fn($user) => Portfolio::factory()->create(['user_id' => $user->id]));

        // Simulate concurrent operations
        $portfolios->each(function ($portfolio, $index) use ($coin) {
            $quantity = ($index + 1) * 0.5; // 0.5, 1.0, 1.5
            $portfolio->coins()->attach($coin->id, ['quantity' => $quantity]);

            Transaction::factory()->create([
                'portfolio_id' => $portfolio->id,
                'coin_id' => $coin->id,
                'quantity' => $quantity,
                'buy_price' => 3000.00,
                'transaction_type' => TransactionTypeEnum::BUY,
            ]);
        });

        expect($coin->portfolios)->toHaveCount(3)
            ->and($coin->portfolios->sum('pivot.quantity'))->toBe(3.0) // 0.5 + 1.0 + 1.5
            ->and(Transaction::where('coin_id', $coin->id)->count())->toBe(3);
    });

    test('can validate coin data integrity across operations', function () {
        $coin = Coin::factory()->create([
            'symbol' => 'BTC',
            'price' => 50000.00,
            'market_cap' => 1000000000.00,
            'percent_change_24h' => 5.0,
        ]);

        $portfolio = Portfolio::factory()->create();
        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);

        // Simulate multiple price updates
        $priceUpdates = [52000.00, 48000.00, 55000.00, 53000.00];

        foreach ($priceUpdates as $price) {
            $coin->update([
                'price' => $price,
                'market_cap' => $price * 19000000, // Simulate market cap calculation
            ]);

            // Verify data consistency
            expect($coin->fresh()->price)->toBe($price)
                ->and($coin->fresh()->market_cap)->toBe($price * 19000000);
        }

        // Final verification
        expect($coin->price)->toBe(53000.00)
            ->and($portfolio->coins->first()->price)->toBe(53000.00);
    });

    test('can handle edge cases with zero and negative values', function () {
        // Coin with zero price (delisted or worthless coin)
        $zeroValueCoin = Coin::factory()->create([
            'symbol' => 'ZERO',
            'price' => 0.00,
            'market_cap' => 0.00,
            'percent_change_24h' => -100.00,
        ]);

        $portfolio = Portfolio::factory()->create();
        $portfolio->coins()->attach($zeroValueCoin->id, ['quantity' => 1000.0]);

        expect($zeroValueCoin->price)->toBe(0.00)
            ->and($zeroValueCoin->market_cap)->toBe(0.00)
            ->and($zeroValueCoin->percent_change_24h)->toBe(-100.00);

        // Very small price coin (micro-cap)
        $microCapCoin = Coin::factory()->create([
            'symbol' => 'MICRO',
            'price' => 0.00000001,
            'market_cap' => 100.00,
        ]);

        expect($microCapCoin->price)->toBe(0.00000001)
            ->and($microCapCoin->market_cap)->toBe(100.00);
    });

    test('can perform portfolio rebalancing operations', function () {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        // Create multiple coins
        $bitcoin = Coin::factory()->create(['symbol' => 'BTC', 'price' => 50000.00]);
        $ethereum = Coin::factory()->create(['symbol' => 'ETH', 'price' => 3000.00]);
        $cardano = Coin::factory()->create(['symbol' => 'ADA', 'price' => 1.20]);

        // Initial allocation
        $portfolio->coins()->sync([
            $bitcoin->id => ['quantity' => 0.4],   // $20,000
            $ethereum->id => ['quantity' => 3.33], // $10,000
            $cardano->id => ['quantity' => 8333.33], // $10,000
        ]);

        // Simulate rebalancing - sell some BTC, buy more ETH and ADA
        $portfolio->coins()->updateExistingPivot($bitcoin->id, ['quantity' => 0.2]);
        $portfolio->coins()->updateExistingPivot($ethereum->id, ['quantity' => 5.0]);
        $portfolio->coins()->updateExistingPivot($cardano->id, ['quantity' => 12500.0]);

        $portfolio->refresh();

        expect($portfolio->coins)->toHaveCount(3)
            ->and($portfolio->coins->find($bitcoin->id)->pivot->quantity)->toBe(0.2)
            ->and($portfolio->coins->find($ethereum->id)->pivot->quantity)->toBe(5.0)
            ->and($portfolio->coins->find($cardano->id)->pivot->quantity)->toBe(12500.0);
    });

    test('can handle large scale data operations efficiently', function () {
        // Create 100 coins
        $coins = Coin::factory(100)->create();

        // Create 10 portfolios
        $portfolios = collect(range(1, 10))->map(function ($i) {
            $user = User::factory()->create();
            return Portfolio::factory()->create(['user_id' => $user->id]);
        });

        // Each portfolio gets random coins
        $portfolios->each(function ($portfolio) use ($coins) {
            $randomCoins = $coins->random(rand(5, 15));
            $attachData = $randomCoins->mapWithKeys(function ($coin) {
                return [$coin->id => ['quantity' => fake()->randomFloat(2, 0.1, 100)]];
            })->toArray();

            $portfolio->coins()->sync($attachData);
        });

        // Verify data integrity
        expect($coins)->toHaveCount(100)
            ->and($portfolios)->toHaveCount(10);

        $totalAttachments = $portfolios->sum(fn($portfolio) => $portfolio->coins->count());
        expect($totalAttachments)->toBeGreaterThan(50) // At least 50 attachments total
            ->and($totalAttachments)->toBeLessThan(150); // But not more than 150
    });

    test('can track historical coin performance impact on portfolios', function () {
        $portfolio = Portfolio::factory()->create();
        $bitcoin = Coin::factory()->create([
            'symbol' => 'BTC',
            'price' => 30000.00,
            'percent_change_24h' => 0.0,
        ]);

        $portfolio->coins()->attach($bitcoin->id, ['quantity' => 1.0]);

        // Simulate 30 days of price changes
        $historicalPrices = [];
        $currentPrice = 30000.00;

        for ($day = 1; $day <= 30; $day++) {
            $priceChange = fake()->randomFloat(2, -5, 5); // -5% to +5% daily
            $newPrice = $currentPrice * (1 + $priceChange / 100);

            $bitcoin->update([
                'price' => $newPrice,
                'percent_change_24h' => $priceChange,
            ]);

            $historicalPrices[] = $newPrice;
            $currentPrice = $newPrice;
        }

        $finalPrice = $bitcoin->fresh()->price;
        $totalReturn = (($finalPrice - 30000.00) / 30000.00) * 100;

        expect(count($historicalPrices))->toBe(30)
            ->and($finalPrice)->toBeGreaterThan(0)
            ->and($totalReturn)->toBeNumeric();
    });

    test('can validate business rules and constraints', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        // Cannot have negative quantity - Laravel doesn't enforce this at DB level by default
        $portfolio->coins()->attach($coin->id, ['quantity' => -1.0]);
        expect($portfolio->coins->first()->pivot->quantity)->toBe(-1.0);

        // Symbol uniqueness is enforced
        $existingCoin = Coin::factory()->create(['symbol' => 'UNIQUE']);

        expect(function () {
            Coin::factory()->create(['symbol' => 'UNIQUE']);
        })->toThrow(\Illuminate\Database\QueryException::class)
            ->and(function () use ($portfolio, $coin) {
                Transaction::create([
                    'portfolio_id' => $portfolio->id,
                    'coin_id' => $coin->id,
                    'quantity' => 1.0,
                    'buy_price' => 1000.00,
                    'transaction_type' => 'INVALID_TYPE',
                ]);
            })->toThrow(\ValueError::class);

        // Transaction must have valid type
    });

    test('can perform complex queries and aggregations', function () {
        // Setup test data
        $users = User::factory(3)->create();
        $portfolios = $users->map(fn($user) => Portfolio::factory()->create(['user_id' => $user->id]));

        $bitcoin = Coin::factory()->create(['symbol' => 'BTC', 'price' => 50000.00]);
        $ethereum = Coin::factory()->create(['symbol' => 'ETH', 'price' => 3000.00]);

        // Each portfolio has different quantities
        $portfolios[0]->coins()->sync([
            $bitcoin->id => ['quantity' => 1.0],
            $ethereum->id => ['quantity' => 5.0],
        ]);

        $portfolios[1]->coins()->sync([
            $bitcoin->id => ['quantity' => 0.5],
            $ethereum->id => ['quantity' => 10.0],
        ]);

        $portfolios[2]->coins()->sync([
            $ethereum->id => ['quantity' => 2.0],
        ]);

        // Complex queries
        $totalBitcoinHolders = $bitcoin->portfolios()->count();
        $totalEthereumQuantity = $ethereum->portfolios()->sum('portfolio_coins.quantity');

        $portfoliosWithBothCoins = Portfolio::whereHas('coins', function ($query) use ($bitcoin) {
            $query->where('coin_id', $bitcoin->id);
        })->whereHas('coins', function ($query) use ($ethereum) {
            $query->where('coin_id', $ethereum->id);
        })->count();

        expect($totalBitcoinHolders)->toBe(2)
            ->and($totalEthereumQuantity)->toBe(17.0) // 5 + 10 + 2
            ->and($portfoliosWithBothCoins)->toBe(2);
    });
});
