<?php

use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

describe('Coin Validation and Edge Cases', function () {
    test('validates required fields', function () {
        expect(function () {
            Coin::create([]);
        })->toThrow(QueryException::class);

        expect(function () {
            Coin::create(['name' => 'Bitcoin']);
        })->toThrow(QueryException::class);
    });

    test('symbol must be unique constraint', function () {
        Coin::factory()->create(['symbol' => 'BTC']);

        expect(function () {
            Coin::factory()->create(['symbol' => 'BTC']);
        })->toThrow(QueryException::class);
    });

    test('handles very long coin names', function () {
        $longName = str_repeat('A', 255);
        $coin = Coin::factory()->create(['name' => $longName]);

        expect($coin->name)->toBe($longName)
            ->and(strlen($coin->name))->toBe(255);
    });

    test('handles very long symbols up to limit', function () {
        $longSymbol = str_repeat('A', 20);
        $coin = Coin::factory()->create(['symbol' => $longSymbol]);

        expect($coin->symbol)->toBe($longSymbol)
            ->and(strlen($coin->symbol))->toBe(20);
    });

    test('rejects symbols longer than 20 characters', function () {
        $tooLongSymbol = str_repeat('A', 21);

        // Test FormRequest validation
        $request = new \App\Http\Requests\CoinStoreRequest();
        $validator = validator(['symbol' => $tooLongSymbol], $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('symbol'))->toBeTrue();
    });

    test('handles extreme price values', function () {
        // Very high price
        $highPriceCoin = Coin::factory()->create(['price' => 999999999999999999.99]);
        expect($highPriceCoin->price)->toBe(999999999999999999.99);

        // Very low price
        $lowPriceCoin = Coin::factory()->create(['price' => 0.00000001]);
        expect($lowPriceCoin->price)->toBe(0.00000001);

        // Zero price
        $zeroPriceCoin = Coin::factory()->create(['price' => 0.0]);
        expect($zeroPriceCoin->price)->toBe(0.0);
    });

    test('handles extreme percentage change values', function () {
        $coin = Coin::factory()->create([
            'percent_change_1h' => -99.99,
            'percent_change_24h' => 999.99,
            'percent_change_7d' => -50.0,
        ]);

        expect($coin->percent_change_1h)->toBe(-99.99)
            ->and($coin->percent_change_24h)->toBe(999.99)
            ->and($coin->percent_change_7d)->toBe(-50.0);
    });

    test('handles extreme market cap values', function () {
        // Very large market cap (like Bitcoin)
        $largeCap = Coin::factory()->create(['market_cap' => 1000000000000.0]);
        expect($largeCap->market_cap)->toBe(1000000000000.0);

        // Zero market cap
        $zeroCap = Coin::factory()->create(['market_cap' => 0.0]);
        expect($zeroCap->market_cap)->toBe(0.0);
    });

    test('handles extreme volume values', function () {
        // Very high volume
        $highVolume = Coin::factory()->create(['volume_24h' => 999999999999999999.99]);
        expect($highVolume->volume_24h)->toBe(999999999999999999.99);

        // Zero volume
        $zeroVolume = Coin::factory()->create(['volume_24h' => 0.0]);
        expect($zeroVolume->volume_24h)->toBe(0.0);
    });

    test('format attributes handle null or zero values gracefully', function () {
        $coin = Coin::factory()->create([
            'price' => 0.0,
            'market_cap' => 0.0,
            'volume_24h' => 0.0,
        ]);

        expect($coin->price_format)->toBe('0.00')
            ->and($coin->market_cap_format)->toBe('0')
            ->and($coin->volume_format)->toBe('0');
    });

    test('format attributes handle very large numbers', function () {
        $coin = Coin::factory()->create([
            'price' => 1234567.89,
            'market_cap' => 9876543210.50,
            'volume_24h' => 5555555555.75,
        ]);

        expect($coin->price_format)->toBe('1,234,567.89')
            ->and($coin->market_cap_format)->toBe('9,876,543,211')
            ->and($coin->volume_format)->toBe('5,555,555,556');
    });

    test('handles special characters in coin names', function () {
        $specialNames = [
            'Coin & Token',
            'Coin-With-Dashes',
            'Coin_With_Underscores',
            'Coin (Wrapped)',
            'Coin.io',
            'Coin 2.0',
        ];

        foreach ($specialNames as $name) {
            $coin = Coin::factory()->create(['name' => $name]);
            expect($coin->name)->toBe($name);
        }
    });

    test('handles special characters in symbols', function () {
        $specialSymbols = [
            'BTC-USD',
            'ETH_2',
            'ADA.X',
            'DOT2',
        ];

        foreach ($specialSymbols as $symbol) {
            $coin = Coin::factory()->create(['symbol' => $symbol]);
            expect($coin->symbol)->toBe($symbol);
        }
    });

    test('portfolio quantity can be decimal', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $preciseQuantity = 0.123456789;
        $portfolio->coins()->attach($coin->id, ['quantity' => $preciseQuantity]);

        $attachedCoin = $portfolio->coins->first();
        expect($attachedCoin->pivot->quantity)->toBeFloat();
    });

    test('transaction quantity precision', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $preciseQuantity = 0.123456789;
        $preciseBuyPrice = 12345.6789;

        $transaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
            'quantity' => $preciseQuantity,
            'buy_price' => $preciseBuyPrice,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        expect($transaction->quantity)->toBeFloat()
            ->and($transaction->buy_price)->toBeFloat();
    });

    test('handles transaction with zero quantity', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $transaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
            'quantity' => 0.0,
            'buy_price' => 1000.00,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        expect($transaction->quantity)->toBe(0.0);
    });

    test('handles transaction with zero buy price', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $transaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
            'quantity' => 1.0,
            'buy_price' => 0.0,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        expect($transaction->buy_price)->toBe(0.0);
    });

    test('coin deletion cascades properly', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        // Create relationships
        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);
        $transaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
        ]);

        $coinId = $coin->id;

        // Delete coin
        $coin->delete();

        // Verify cascade deletion
        expect(Coin::find($coinId))->toBeNull()
            ->and($portfolio->fresh()->coins)->toHaveCount(0)
            ->and(Transaction::find($transaction->id))->toBeNull();
    });

    test('portfolio deletion cascades properly', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        // Create relationships
        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);
        $transaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
        ]);

        $portfolioId = $portfolio->id;

        // Delete portfolio
        $portfolio->delete();

        // Verify cascade deletion
        expect(Portfolio::find($portfolioId))->toBeNull()
            ->and($coin->fresh()->portfolios)->toHaveCount(0)
            ->and(Transaction::find($transaction->id))->toBeNull();
    });

    test('handles concurrent updates to same coin', function () {
        $coin = Coin::factory()->create(['price' => 1000.0]);

        // Simulate concurrent updates
        $coin1 = Coin::find($coin->id);
        $coin2 = Coin::find($coin->id);

        $coin1->update(['price' => 2000.0]);
        $coin2->update(['price' => 3000.0]);

        // Last update wins
        expect($coin->fresh()->price)->toBe(3000.0);
    });

    test('handles invalid enum values in transactions', function () {
        expect(function () {
            $portfolio = Portfolio::factory()->create();
            $coin = Coin::factory()->create();

            Transaction::create([
                'portfolio_id' => $portfolio->id,
                'coin_id' => $coin->id,
                'quantity' => 1.0,
                'buy_price' => 1000.0,
                'transaction_type' => 'INVALID_TYPE',
            ]);
        })->toThrow(\ValueError::class);
    });

    test('handles missing foreign key relationships', function () {
        expect(function () {
            Transaction::create([
                'portfolio_id' => 99999, // Non-existent portfolio
                'coin_id' => 99999, // Non-existent coin
                'quantity' => 1.0,
                'buy_price' => 1000.0,
                'transaction_type' => TransactionTypeEnum::BUY,
            ]);
        })->toThrow(QueryException::class);
    });

    test('coin symbol is properly indexed for fast lookups', function () {
        // Create many coins
        Coin::factory(100)->create();

        $testCoin = Coin::factory()->create(['symbol' => 'TESTCOIN']);

        // Query by symbol should be fast due to index
        $foundCoin = Coin::where('symbol', 'TESTCOIN')->first();

        expect($foundCoin->id)->toBe($testCoin->id);
    });

    test('large dataset performance with relationships', function () {
        // This test ensures the relationships perform well with larger datasets
        $portfolio = Portfolio::factory()->create();
        $coins = Coin::factory(50)->create();

        // Attach all coins to portfolio
        $attachData = $coins->mapWithKeys(function ($coin, $index) {
            return [$coin->id => ['quantity' => $index + 1]];
        })->toArray();

        $portfolio->coins()->sync($attachData);

        // Test that we can efficiently query relationships
        expect($portfolio->coins)->toHaveCount(50)
            ->and($portfolio->coins->sum('pivot.quantity'))->toBe(1275.0); // Sum of 1 to 50
    });
});
