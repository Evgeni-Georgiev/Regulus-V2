<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Coin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\CoinController
 */
final class CoinControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $coins = Coin::factory()->count(3)->create();

        $response = $this->get(route('coins.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\CoinController::class,
            'store',
            \App\Http\Requests\CoinStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $name = $this->faker->name();
        $symbol = $this->faker->word();
        $price = $this->faker->randomFloat(/** decimal_attributes **/);
        $market_cap = $this->faker->randomFloat(/** decimal_attributes **/);
        $percent_change_1h = $this->faker->randomFloat(/** decimal_attributes **/);
        $percent_change_24h = $this->faker->randomFloat(/** decimal_attributes **/);
        $percent_change_7d = $this->faker->randomFloat(/** decimal_attributes **/);
        $volume_24h = $this->faker->randomFloat(/** decimal_attributes **/);

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
        $this->assertCount(1, $coins);
        $coin = $coins->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $coin = Coin::factory()->create();

        $response = $this->get(route('coins.show', $coin));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\CoinController::class,
            'update',
            \App\Http\Requests\CoinUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $coin = Coin::factory()->create();
        $name = $this->faker->name();
        $symbol = $this->faker->word();
        $price = $this->faker->randomFloat(/** decimal_attributes **/);
        $market_cap = $this->faker->randomFloat(/** decimal_attributes **/);
        $percent_change_1h = $this->faker->randomFloat(/** decimal_attributes **/);
        $percent_change_24h = $this->faker->randomFloat(/** decimal_attributes **/);
        $percent_change_7d = $this->faker->randomFloat(/** decimal_attributes **/);
        $volume_24h = $this->faker->randomFloat(/** decimal_attributes **/);

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

        $this->assertEquals($name, $coin->name);
        $this->assertEquals($symbol, $coin->symbol);
        $this->assertEquals($price, $coin->price);
        $this->assertEquals($market_cap, $coin->market_cap);
        $this->assertEquals($percent_change_1h, $coin->percent_change_1h);
        $this->assertEquals($percent_change_24h, $coin->percent_change_24h);
        $this->assertEquals($percent_change_7d, $coin->percent_change_7d);
        $this->assertEquals($volume_24h, $coin->volume_24h);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $coin = Coin::factory()->create();

        $response = $this->delete(route('coins.destroy', $coin));

        $response->assertNoContent();

        $this->assertModelMissing($coin);
    }
}
