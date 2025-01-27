<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\TransactionController
 */
final class TransactionControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $transactions = Transaction::factory()->count(3)->create();

        $response = $this->get(route('transactions.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\TransactionController::class,
            'store',
            \App\Http\Requests\TransactionStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();
        $quantity = $this->faker->randomFloat(/** decimal_attributes **/);
        $buy_price = $this->faker->randomFloat(/** decimal_attributes **/);
        $transaction_type = $this->faker->randomElement(/** enum_attributes **/);

        $response = $this->post(route('transactions.store'), [
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
            'quantity' => $quantity,
            'buy_price' => $buy_price,
            'transaction_type' => $transaction_type,
        ]);

        $transactions = Transaction::query()
            ->where('portfolio_id', $portfolio->id)
            ->where('coin_id', $coin->id)
            ->where('quantity', $quantity)
            ->where('buy_price', $buy_price)
            ->where('transaction_type', $transaction_type)
            ->get();
        $this->assertCount(1, $transactions);
        $transaction = $transactions->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->get(route('transactions.show', $transaction));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\TransactionController::class,
            'update',
            \App\Http\Requests\TransactionUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $transaction = Transaction::factory()->create();
        $portfolio = Portfolio::factory()->create();
        $coin = Coin::factory()->create();
        $quantity = $this->faker->randomFloat(/** decimal_attributes **/);
        $buy_price = $this->faker->randomFloat(/** decimal_attributes **/);
        $transaction_type = $this->faker->randomElement(/** enum_attributes **/);

        $response = $this->put(route('transactions.update', $transaction), [
            'portfolio_id' => $portfolio->id,
            'coin_id' => $coin->id,
            'quantity' => $quantity,
            'buy_price' => $buy_price,
            'transaction_type' => $transaction_type,
        ]);

        $transaction->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($portfolio->id, $transaction->portfolio_id);
        $this->assertEquals($coin->id, $transaction->coin_id);
        $this->assertEquals($quantity, $transaction->quantity);
        $this->assertEquals($buy_price, $transaction->buy_price);
        $this->assertEquals($transaction_type, $transaction->transaction_type);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $transaction = Transaction::factory()->create();

        $response = $this->delete(route('transactions.destroy', $transaction));

        $response->assertNoContent();

        $this->assertModelMissing($transaction);
    }
}
