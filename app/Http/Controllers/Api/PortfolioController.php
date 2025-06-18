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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PortfolioController extends Controller
{
    protected CMCClient $cmcClient;

    public function __construct(CMCClient $cmcClient)
    {
        $this->cmcClient = $cmcClient;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Portfolio::class);

        $portfolios = Portfolio::where('user_id', $request->user()->id)->get();

        return PortfolioResource::collection($portfolios);
    }

    public function store(PortfolioStoreRequest $request): PortfolioResource
    {
        $this->authorize('create', Portfolio::class);

        // Assign the portfolio to the authenticated user
        $portfolioData = $request->validated();
        $portfolioData['user_id'] = $request->user()->id;

        $portfolio = Portfolio::create($portfolioData);

        return new PortfolioResource($portfolio);
    }

    public function show(Portfolio $portfolio): JsonResponse
    {
        $this->authorize('view', $portfolio);

        // Fetch coins data from API
        $coinData = $this->cmcClient->getCoinData();
        $portfolioService = app(PortfolioService::class)->loadPortfolio($portfolio);

        return response()->json([
            'portfolio' => $portfolioService->getPortfolioDetails($coinData),
        ]);
    }

    public function history(Portfolio $portfolio): JsonResponse
    {
        $this->authorize('view', $portfolio);

        $portfolioHistory = PortfolioHistory::where('portfolio_id', $portfolio->id)->first();

        return response()->json([
            'history' => $portfolioHistory ? $portfolioHistory->orderBy('changed_at')->get() : [],
        ]);
    }

    public function update(PortfolioUpdateRequest $request, Portfolio $portfolio): PortfolioResource
    {
        $this->authorize('update', $portfolio);

        $portfolio->update($request->validated());

        return new PortfolioResource($portfolio);
    }

    public function destroy(Portfolio $portfolio): Response
    {
        $this->authorize('delete', $portfolio);

        $portfolio->delete();

        return response()->noContent();
    }
}
