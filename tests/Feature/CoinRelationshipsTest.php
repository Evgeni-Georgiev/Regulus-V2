<?php

use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\TransactionTypeEnum;

describe('Coin Relationships Feature Tests', function () {
    test('can create complete coin portfolio ecosystem', function () {
        // Create user and portfolio
        $user = User::factory()->create(['name' => 'John Doe']);
        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Crypto Portfolio'
        ]);

        // Create coins
        $bitcoin = Coin::factory()->create([
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'price' => 50000.00
        ]);
        
        $ethereum = Coin::factory()->create([
            'name' => 'Ethereum', 
            'symbol' => 'ETH',
            'price' => 3000.00
        ]);

        // Add coins to portfolio
        $portfolio->coins()->attach($bitcoin->id, ['quantity' => 0.5]);
        $portfolio->coins()->attach($ethereum->id, ['quantity' => 2.0]);

        // Create transactions
        Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $bitcoin->id,
            'quantity' => 0.5,
            'buy_price' => 45000.00,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $ethereum->id,
            'quantity' => 2.0,
            'buy_price' => 2800.00,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        // Verify relationships
        expect($portfolio->coins)->toHaveCount(2)
            ->and($portfolio->coins->pluck('symbol')->toArray())->toContain('BTC', 'ETH')
            ->and($portfolio->transactions)->toHaveCount(2)
            ->and($bitcoin->portfolios->first()->name)->toBe('My Crypto Portfolio')
            ->and($ethereum->portfolios->first()->user->name)->toBe('John Doe');
    });

    test('can handle multiple portfolios owning same coin', function () {
        $bitcoin = Coin::factory()->create(['symbol' => 'BTC']);
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $portfolio1 = Portfolio::factory()->create(['user_id' => $user1->id]);
        $portfolio2 = Portfolio::factory()->create(['user_id' => $user2->id]);

        // Both portfolios own Bitcoin with different quantities
        $portfolio1->coins()->attach($bitcoin->id, ['quantity' => 1.5]);
        $portfolio2->coins()->attach($bitcoin->id, ['quantity' => 0.8]);

        expect($bitcoin->portfolios)->toHaveCount(2)
            ->and($bitcoin->portfolios->find($portfolio1->id)->pivot->quantity)->toBe(1.5)
            ->and($bitcoin->portfolios->find($portfolio2->id)->pivot->quantity)->toBe(0.8);
    });

    test('can track buy and sell transactions for same coin', function () {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $bitcoin = Coin::factory()->create(['symbol' => 'BTC']);

        // Buy transaction
        $buyTransaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $bitcoin->id,
            'quantity' => 1.0,
            'buy_price' => 40000.00,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        // Sell transaction
        $sellTransaction = Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $bitcoin->id,
            'quantity' => 0.5,
            'buy_price' => 50000.00,
            'transaction_type' => TransactionTypeEnum::SELL,
        ]);

        $coinTransactions = Transaction::where('coin_id', $bitcoin->id)->get();

        expect($coinTransactions)->toHaveCount(2)
            ->and($coinTransactions->where('transaction_type', TransactionTypeEnum::BUY)->first()->id)->toBe($buyTransaction->id)
            ->and($coinTransactions->where('transaction_type', TransactionTypeEnum::SELL)->first()->id)->toBe($sellTransaction->id);
    });

    test('can update coin quantities in portfolio through pivot table', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        // Initial attachment
        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);
        
        expect($portfolio->coins->first()->pivot->quantity)->toBe(1.0);

        // Update quantity
        $portfolio->coins()->updateExistingPivot($coin->id, ['quantity' => 2.5]);
        $portfolio->refresh();

        expect($portfolio->coins->first()->pivot->quantity)->toBe(2.5);
    });

    test('can detach coin from portfolio', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);
        expect($portfolio->coins)->toHaveCount(1);

        $portfolio->coins()->detach($coin->id);
        $portfolio->refresh();

        expect($portfolio->coins)->toHaveCount(0);
    });

    test('cascade delete removes portfolio coins when portfolio is deleted', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);
        
        expect($portfolio->coins)->toHaveCount(1);

        $portfolio->delete();

        // Coin should still exist but not be attached to any portfolio
        expect(Coin::find($coin->id))->not()->toBeNull()
            ->and($coin->fresh()->portfolios)->toHaveCount(0);
    });

    test('cascade delete removes portfolio coins when coin is deleted', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);
        
        expect($portfolio->coins)->toHaveCount(1);

        $coin->delete();

        // Portfolio should still exist but have no coins
        expect(Portfolio::find($portfolio->id))->not()->toBeNull()
            ->and($portfolio->fresh()->coins)->toHaveCount(0);
    });

    test('can sync coins in portfolio with different quantities', function () {
        $portfolio = Portfolio::factory()->create();
        $coin1 = Coin::factory()->create(['symbol' => 'BTC']);
        $coin2 = Coin::factory()->create(['symbol' => 'ETH']);
        $coin3 = Coin::factory()->create(['symbol' => 'ADA']);

        // Sync coins with quantities
        $portfolio->coins()->sync([
            $coin1->id => ['quantity' => 1.5],
            $coin2->id => ['quantity' => 3.0],
            $coin3->id => ['quantity' => 100.0],
        ]);

        expect($portfolio->coins)->toHaveCount(3)
            ->and($portfolio->coins->find($coin1->id)->pivot->quantity)->toBe(1.5)
            ->and($portfolio->coins->find($coin2->id)->pivot->quantity)->toBe(3.0)
            ->and($portfolio->coins->find($coin3->id)->pivot->quantity)->toBe(100.0);
    });

    test('can calculate total portfolio value based on coin prices and quantities', function () {
        $portfolio = Portfolio::factory()->create();
        
        $bitcoin = Coin::factory()->create([
            'symbol' => 'BTC',
            'price' => 50000.00
        ]);
        
        $ethereum = Coin::factory()->create([
            'symbol' => 'ETH', 
            'price' => 3000.00
        ]);

        $portfolio->coins()->attach($bitcoin->id, ['quantity' => 0.5]);  // 0.5 * 50000 = 25000
        $portfolio->coins()->attach($ethereum->id, ['quantity' => 2.0]); // 2.0 * 3000 = 6000

        $totalValue = $portfolio->coins->sum(function ($coin) {
            return $coin->price * $coin->pivot->quantity;
        });

        expect($totalValue)->toBe(31000.0);
    });

    test('can track coin price changes over time with portfolio impact', function () {
        $portfolio = Portfolio::factory()->create();
        $bitcoin = Coin::factory()->create([
            'symbol' => 'BTC',
            'price' => 45000.00,
            'percent_change_24h' => -5.0
        ]);

        $portfolio->coins()->attach($bitcoin->id, ['quantity' => 1.0]);

        // Simulate price update
        $bitcoin->update([
            'price' => 47250.00,
            'percent_change_24h' => 5.0
        ]);

        $updatedBitcoin = $portfolio->coins->first();
        
        expect($updatedBitcoin->price)->toBe(47250.00)
            ->and($updatedBitcoin->percent_change_24h)->toBe(5.0);
    });

    test('pivot table timestamps are managed correctly', function () {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();

        $portfolio->coins()->attach($coin->id, ['quantity' => 1.0]);

        $pivotRecord = $portfolio->coins->first()->pivot;

        expect($pivotRecord->created_at)->not()->toBeNull()
            ->and($pivotRecord->updated_at)->not()->toBeNull();
    });

    test('can filter transactions by coin and transaction type', function () {
        $user = User::factory()->create();
        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);
        $bitcoin = Coin::factory()->create(['symbol' => 'BTC']);
        $ethereum = Coin::factory()->create(['symbol' => 'ETH']);

        // Create multiple transactions
        Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $bitcoin->id,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $bitcoin->id,
            'transaction_type' => TransactionTypeEnum::SELL,
        ]);

        Transaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'coin_id' => $ethereum->id,
            'transaction_type' => TransactionTypeEnum::BUY,
        ]);

        $bitcoinBuyTransactions = Transaction::where('coin_id', $bitcoin->id)
            ->where('transaction_type', TransactionTypeEnum::BUY)
            ->get();

        $allBitcoinTransactions = Transaction::where('coin_id', $bitcoin->id)->get();

        expect($bitcoinBuyTransactions)->toHaveCount(1)
            ->and($allBitcoinTransactions)->toHaveCount(2);
    });
}); 