<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioStoreRequest;
use App\Http\Requests\PortfolioUpdateRequest;
use App\Http\Resources\PortfolioCollection;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
//        $portfolios = Portfolio::all();
        $portfolios = Portfolio::whereIn('user_id', [7, 8, 9])->paginate(10);

//        return new PortfolioResource($portfolios);
        return PortfolioResource::collection($portfolios);
    }

    public function store(PortfolioStoreRequest $request): PortfolioResource
    {
        $portfolio = Portfolio::create($request->validated());

        return new PortfolioResource($portfolio);
    }

    public function show(Request $request, Portfolio $portfolio): PortfolioResource
    {
        return new PortfolioResource($portfolio);
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
