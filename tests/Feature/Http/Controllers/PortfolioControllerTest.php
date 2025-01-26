<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Api\PortfolioController
 */
final class PortfolioControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_behaves_as_expected(): void
    {
        $portfolios = Portfolio::factory()->count(3)->create();

        $response = $this->get(route('portfolios.index'));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\PortfolioController::class,
            'store',
            \App\Http\Requests\PortfolioStoreRequest::class
        );
    }

    #[Test]
    public function store_saves(): void
    {
        $user = User::factory()->create();
        $name = $this->faker->name();

        $response = $this->post(route('portfolios.store'), [
            'user_id' => $user->id,
            'name' => $name,
        ]);

        $portfolios = Portfolio::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->get();
        $this->assertCount(1, $portfolios);
        $portfolio = $portfolios->first();

        $response->assertCreated();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function show_behaves_as_expected(): void
    {
        $portfolio = Portfolio::factory()->create();

        $response = $this->get(route('portfolios.show', $portfolio));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }


    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\Api\PortfolioController::class,
            'update',
            \App\Http\Requests\PortfolioUpdateRequest::class
        );
    }

    #[Test]
    public function update_behaves_as_expected(): void
    {
        $portfolio = Portfolio::factory()->create();
        $user = User::factory()->create();
        $name = $this->faker->name();

        $response = $this->put(route('portfolios.update', $portfolio), [
            'user_id' => $user->id,
            'name' => $name,
        ]);

        $portfolio->refresh();

        $response->assertOk();
        $response->assertJsonStructure([]);

        $this->assertEquals($user->id, $portfolio->user_id);
        $this->assertEquals($name, $portfolio->name);
    }


    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $portfolio = Portfolio::factory()->create();

        $response = $this->delete(route('portfolios.destroy', $portfolio));

        $response->assertNoContent();

        $this->assertModelMissing($portfolio);
    }
}
