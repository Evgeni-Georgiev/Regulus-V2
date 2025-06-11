<?php

use App\Models\Coin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;

/**
 * @see \App\Http\Controllers\Api\CoinController
 */

uses(RefreshDatabase::class, WithFaker::class, AdditionalAssertions::class);

test('index behaves as expected', function () {
    $coins = Coin::factory()->count(3)->create();

    $response = $this->get(route('coins.index'));

    $response->assertOk();
    $response->assertJsonStructure([]);
});

test('store uses form request validation', function () {
    $this->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\CoinController::class,
        'store',
        \App\Http\Requests\CoinStoreRequest::class
    );
});

test('store saves', function () {
    $name = fake()->name();
    $symbol = fake()->unique()->lexify('???'); // Generate 3-letter symbol
    $price = fake()->randomFloat(2, 0.01, 100000); // Reasonable price range
    $market_cap = fake()->randomFloat(2, 1000, 1000000000); // Market cap range
    $percent_change_1h = fake()->randomFloat(2, -50, 50); // Percentage change range
    $percent_change_24h = fake()->randomFloat(2, -50, 50);
    $percent_change_7d = fake()->randomFloat(2, -50, 50);
    $volume_24h = fake()->randomFloat(2, 1000, 10000000); // Volume range

    $response = $this->post(route('coins.store'), [
        'name' => $name,
        'symbol' => $symbol,
        'price' => $price,
        'market_cap' => $market_cap,
        'percent_change_1h' => $percent_change_1h,
        'percent_change_24h' => $percent_change_24h,
        'percent_change_7d' => $percent_change_7d,
        'volume_24h' => $volume_24h,
    ]);

    $coins = Coin::query()
        ->where('name', $name)
        ->where('symbol', $symbol)
        ->where('price', $price)
        ->where('market_cap', $market_cap)
        ->where('percent_change_1h', $percent_change_1h)
        ->where('percent_change_24h', $percent_change_24h)
        ->where('percent_change_7d', $percent_change_7d)
        ->where('volume_24h', $volume_24h)
        ->get();
    expect($coins)->toHaveCount(1);
    $coin = $coins->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);
});

test('show behaves as expected', function () {
    $coin = Coin::factory()->create();

    $response = $this->get(route('coins.show', $coin));

    $response->assertOk();
    $response->assertJsonStructure([]);
});

test('update uses form request validation', function () {
    $this->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\CoinController::class,
        'update',
        \App\Http\Requests\CoinUpdateRequest::class
    );
});

test('update behaves as expected', function () {
    $coin = Coin::factory()->create();
    $name = fake()->name();
    $symbol = fake()->unique()->lexify('???'); // Generate 3-letter symbol
    $price = fake()->randomFloat(2, 0.01, 100000); // Reasonable price range
    $market_cap = fake()->randomFloat(2, 1000, 1000000000); // Market cap range
    $percent_change_1h = fake()->randomFloat(2, -50, 50); // Percentage change range
    $percent_change_24h = fake()->randomFloat(2, -50, 50);
    $percent_change_7d = fake()->randomFloat(2, -50, 50);
    $volume_24h = fake()->randomFloat(2, 1000, 10000000); // Volume range

    $response = $this->put(route('coins.update', $coin), [
        'name' => $name,
        'symbol' => $symbol,
        'price' => $price,
        'market_cap' => $market_cap,
        'percent_change_1h' => $percent_change_1h,
        'percent_change_24h' => $percent_change_24h,
        'percent_change_7d' => $percent_change_7d,
        'volume_24h' => $volume_24h,
    ]);

    $coin->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($coin->name)->toBe($name);
    expect($coin->symbol)->toBe($symbol);
    expect($coin->price)->toBe($price);
    expect($coin->market_cap)->toBe($market_cap);
    expect($coin->percent_change_1h)->toBe($percent_change_1h);
    expect($coin->percent_change_24h)->toBe($percent_change_24h);
    expect($coin->percent_change_7d)->toBe($percent_change_7d);
    expect($coin->volume_24h)->toBe($volume_24h);
});

test('destroy deletes and responds with no content', function () {
    $coin = Coin::factory()->create();

    $response = $this->delete(route('coins.destroy', $coin));

    $response->assertNoContent();

    $this->assertModelMissing($coin);
});
