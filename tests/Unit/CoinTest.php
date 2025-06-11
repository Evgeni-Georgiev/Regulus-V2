<?php

use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\TransactionTypeEnum;

describe('Coin Model', function () {
    test('can create a coin with all attributes', function () {
        $coin = Coin::factory()->create([
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'price' => 50000.00,
            'market_cap' => 1000000000.00,
            'percent_change_1h' => 1.5,
            'percent_change_24h' => -2.3,
            'percent_change_7d' => 5.7,
            'volume_24h' => 25000000.00,
        ]);

        expect($coin->name)->toBe('Bitcoin')
            ->and($coin->symbol)->toBe('BTC')
            ->and($coin->price)->toBe(50000.00)
            ->and($coin->market_cap)->toBe(1000000000.00)
            ->and($coin->percent_change_1h)->toBe(1.5)
            ->and($coin->percent_change_24h)->toBe(-2.3)
            ->and($coin->percent_change_7d)->toBe(5.7)
            ->and($coin->volume_24h)->toBe(25000000.00);
    });

    test('has correct fillable attributes', function () {
        $coin = new Coin();
        $fillable = $coin->getFillable();

        expect($fillable)->toContain('name')
            ->and($fillable)->toContain('symbol')
            ->and($fillable)->toContain('price')
            ->and($fillable)->toContain('market_cap')
            ->and($fillable)->toContain('percent_change_1h')
            ->and($fillable)->toContain('percent_change_24h')
            ->and($fillable)->toContain('percent_change_7d')
            ->and($fillable)->toContain('volume_24h');
    });

    test('casts attributes to correct types', function () {
        $coin = Coin::factory()->create([
            'price' => '45000.50',
            'market_cap' => '900000000.75',
            'percent_change_1h' => '2.5',
            'percent_change_24h' => '-1.8',
            'percent_change_7d' => '3.2',
            'volume_24h' => '15000000.25',
        ]);

        expect($coin->price)->toBeFloat()
            ->and($coin->market_cap)->toBeFloat()
            ->and($coin->percent_change_1h)->toBeFloat()
            ->and($coin->percent_change_24h)->toBeFloat()
            ->and($coin->percent_change_7d)->toBeFloat()
            ->and($coin->volume_24h)->toBeFloat()
            ->and($coin->id)->toBeInt();
    });

    test('symbol must be unique', function () {
        $coin = Coin::factory()->create(['symbol' => 'BTC']);

        expect(function () {
            Coin::factory()->create(['symbol' => 'BTC']);
        })->toThrow($coin->symbol);
    });

    test('has many-to-many relationship with portfolios', function () {
        $coin = Coin::factory()->create();
        $portfolio = Portfolio::factory()->create();

        $coin->portfolios()->attach($portfolio->id, ['quantity' => 2.5]);

        expect($coin->portfolios)->toHaveCount(1)
            ->and($coin->portfolios->first())->toBeInstanceOf(Portfolio::class)
            ->and($coin->portfolios->first()->pivot->quantity)->toBe(2.5);
    });

    test('can have multiple portfolios with different quantities', function () {
        $coin = Coin::factory()->create();
        $portfolio1 = Portfolio::factory()->create();
        $portfolio2 = Portfolio::factory()->create();

        $coin->portfolios()->attach($portfolio1->id, ['quantity' => 1.5]);
        $coin->portfolios()->attach($portfolio2->id, ['quantity' => 3.0]);

        expect($coin->portfolios)->toHaveCount(2)
            ->and($coin->portfolios->find($portfolio1->id)->pivot->quantity)->toBe(1.5)
            ->and($coin->portfolios->find($portfolio2->id)->pivot->quantity)->toBe(3.0);
    });

    test('has relationship with transactions through portfolio', function () {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $coin = Coin::factory()->create();

        $transaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
            'quantity' => 1.0,
            'buy_price' => 50000.00,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        expect($transaction->coin)->toBeInstanceOf(Coin::class)
            ->and($transaction->coin->id)->toBe($coin->id);
    });

    test('market cap format attribute returns formatted string', function () {
        $coin = Coin::factory()->create(['market_cap' => 1234567890.50]);

        expect($coin->market_cap_format)->toBe('1,234,567,891');
    });

    test('volume format attribute returns formatted string', function () {
        $coin = Coin::factory()->create(['volume_24h' => 123456789.75]);

        expect($coin->volume_format)->toBe('123,456,790');
    });

    test('price format attribute returns formatted string with decimals', function () {
        $coin = Coin::factory()->create(['price' => 45678.12345]);

        expect($coin->price_format)->toBe('45,678.12');
    });

    test('can handle negative percentage changes', function () {
        $coin = Coin::factory()->create([
            'percent_change_1h' => -5.25,
            'percent_change_24h' => -10.75,
            'percent_change_7d' => -15.50,
        ]);

        expect($coin->percent_change_1h)->toBe(-5.25)
            ->and($coin->percent_change_24h)->toBe(-10.75)
            ->and($coin->percent_change_7d)->toBe(-15.50);
    });

    test('can handle zero values', function () {
        $coin = Coin::factory()->create([
            'price' => 0.0,
            'market_cap' => 0.0,
            'percent_change_1h' => 0.0,
            'percent_change_24h' => 0.0,
            'percent_change_7d' => 0.0,
            'volume_24h' => 0.0,
        ]);

        expect($coin->price)->toBe(0.0)
            ->and($coin->market_cap)->toBe(0.0)
            ->and($coin->percent_change_1h)->toBe(0.0)
            ->and($coin->percent_change_24h)->toBe(0.0)
            ->and($coin->percent_change_7d)->toBe(0.0)
            ->and($coin->volume_24h)->toBe(0.0);
    });

    test('timestamps are automatically managed', function () {
        $coin = Coin::factory()->create();

        expect($coin->created_at)->not()->toBeNull()
            ->and($coin->updated_at)->not()->toBeNull();
    });

    test('can be mass assigned with fillable attributes', function () {
        $attributes = [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'price' => 3000.00,
            'market_cap' => 360000000.00,
            'percent_change_1h' => 0.5,
            'percent_change_24h' => 2.1,
            'percent_change_7d' => -1.8,
            'volume_24h' => 12000000.00,
        ];

        $coin = Coin::create($attributes);

        expect($coin->name)->toBe('Ethereum')
            ->and($coin->symbol)->toBe('ETH')
            ->and($coin->price)->toBe(3000.00);
    });
});
