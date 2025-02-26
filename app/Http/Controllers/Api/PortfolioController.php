<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioStoreRequest;
use App\Http\Requests\PortfolioUpdateRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use App\Models\PortfolioHistory;
use App\Services\CMCClient;
use App\Services\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortfolioController extends Controller
{
    protected CMCClient $cmcClient;

    public function __construct(CMCClient $cmcClient)
    {
        $this->cmcClient = $cmcClient;
    }

    public function index(Request $request)
    {
        $portfolios = Portfolio::all();

        return PortfolioResource::collection($portfolios);
    }

    public function store(PortfolioStoreRequest $request): PortfolioResource
    {
        $portfolio = Portfolio::create($request->validated());

        return new PortfolioResource($portfolio);
    }

    public function show(Request $request, Portfolio $portfolio)
    {
        // Fetch coins data from API
        $coinData = $this->cmcClient->getCoinData();
        $portfolioService = app(PortfolioService::class)
            ->loadPortfolio($portfolio);

        return response()->json([
            'portfolio' => $portfolioService->getPortfolioDetails($coinData),
        ]);
    }

    public function history(Portfolio $portfolio): JsonResponse
    {
        $portfolioHistory = PortfolioHistory::where('portfolio_id', $portfolio->id)->first();

        return response()->json([
            'history' => $portfolioHistory->orderBy('changed_at')->get(),
        ]);
    }

    public function update(PortfolioUpdateRequest $request, Portfolio $portfolio): PortfolioResource
    {
        $portfolio->update($request->validated());

        return new PortfolioResource($portfolio);
    }

    public function destroy(Request $request, Portfolio $portfolio): Response
    {
        $portfolio->delete();

        return response()->noContent();
    }
}
