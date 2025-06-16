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
        // Ensure all methods require authentication
//        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
//        dd($request);
        // Use policy authorization
        $this->authorize('viewAny', Portfolio::class);

        // Only return portfolios that belong to the authenticated user
        $portfolios = Portfolio::where('user_id', $request->user()->id)->get();

        return PortfolioResource::collection($portfolios);
    }

    public function store(PortfolioStoreRequest $request): PortfolioResource
    {
        // Use policy authorization
        $this->authorize('create', Portfolio::class);

        // Automatically assign the portfolio to the authenticated user
        $portfolioData = $request->validated();
        $portfolioData['user_id'] = $request->user()->id;

        $portfolio = Portfolio::create($portfolioData);

        return new PortfolioResource($portfolio);
    }

    public function show(Request $request, Portfolio $portfolio)
    {
//        dd($request);
        // Use policy authorization - this will check if user owns the portfolio
        $this->authorize('view', $portfolio);

        // Fetch coins data from API
        $coinData = $this->cmcClient->getCoinData();
        $portfolioService = app(PortfolioService::class)
            ->loadPortfolio($portfolio);

        return response()->json([
            'portfolio' => $portfolioService->getPortfolioDetails($coinData),
        ]);
    }

    public function history(Request $request, Portfolio $portfolio): JsonResponse
    {
        // Use policy authorization - this will check if user owns the portfolio
        $this->authorize('view', $portfolio);

        $portfolioHistory = PortfolioHistory::where('portfolio_id', $portfolio->id)->first();

        return response()->json([
            'history' => $portfolioHistory ? $portfolioHistory->orderBy('changed_at')->get() : [],
        ]);
    }

    public function update(PortfolioUpdateRequest $request, Portfolio $portfolio): PortfolioResource
    {
        // Use policy authorization - this will check if user owns the portfolio
        $this->authorize('update', $portfolio);

        $portfolio->update($request->validated());

        return new PortfolioResource($portfolio);
    }

    public function destroy(Request $request, Portfolio $portfolio): Response
    {
        // Use policy authorization - this will check if user owns the portfolio
        $this->authorize('delete', $portfolio);

        $portfolio->delete();

        return response()->noContent();
    }
}
