<?php

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;

/**
 * @see \App\Http\Controllers\Api\PortfolioController
 */

uses(RefreshDatabase::class, WithFaker::class, AdditionalAssertions::class);

test('index behaves as expected', function () {
    $portfolios = Portfolio::factory()->count(3)->create();

    $response = $this->get(route('portfolios.index'));

    $response->assertOk();
    $response->assertJsonStructure([]);
});

test('store uses form request validation', function () {
    $this->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\PortfolioController::class,
        'store',
        \App\Http\Requests\PortfolioStoreRequest::class
    );
});

test('store saves', function () {
    $user = User::factory()->create();
    $name = fake()->name();

    $response = $this->post(route('portfolios.store'), [
        'user_id' => $user->id,
        'name' => $name,
    ]);

    $portfolios = Portfolio::query()
        ->where('user_id', $user->id)
        ->where('name', $name)
        ->get();
    expect($portfolios)->toHaveCount(1);
    $portfolio = $portfolios->first();

    $response->assertCreated();
    $response->assertJsonStructure([]);
});

test('show behaves as expected', function () {
    $portfolio = Portfolio::factory()->create();

    $response = $this->get(route('portfolios.show', $portfolio));

    $response->assertOk();
    $response->assertJsonStructure([]);
});

test('update uses form request validation', function () {
    $this->assertActionUsesFormRequest(
        \App\Http\Controllers\Api\PortfolioController::class,
        'update',
        \App\Http\Requests\PortfolioUpdateRequest::class
    );
});

test('update behaves as expected', function () {
    $portfolio = Portfolio::factory()->create();
    $user = User::factory()->create();
    $name = fake()->name();

    $response = $this->put(route('portfolios.update', $portfolio), [
        'user_id' => $user->id,
        'name' => $name,
    ]);

    $portfolio->refresh();

    $response->assertOk();
    $response->assertJsonStructure([]);

    expect($portfolio->user_id)->toBe($user->id);
    expect($portfolio->name)->toBe($name);
});

test('destroy deletes and responds with no content', function () {
    $portfolio = Portfolio::factory()->create();

    $response = $this->delete(route('portfolios.destroy', $portfolio));

    $response->assertNoContent();

    $this->assertModelMissing($portfolio);
});
