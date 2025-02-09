<?php

namespace App\Http\Controllers\Api;

use App\Enums\Coin\CoinEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CoinStoreRequest;
use App\Http\Requests\CoinUpdateRequest;
use App\Http\Resources\CoinResource;
use App\Models\Coin;
use App\Services\CMCClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CoinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coinService = app(CMCClient::class);

        $filterCoin = $coinService->getCoinData()
            ->take(CoinEnum::PAGINATE_LIMIT_COIN_15);

        return response()->json([
            'coins' => $filterCoin->values()->toArray(),
            'dataSource' => $coinService->getDataSource()
        ]);
    }

    public function store(CoinStoreRequest $request): CoinResource
    {
        $coin = Coin::create($request->validated());

        return new CoinResource($coin);
    }

    public function show(Request $request, Coin $coin): CoinResource
    {
        return new CoinResource($coin);
    }

    public function update(CoinUpdateRequest $request, Coin $coin): CoinResource
    {
        $coin->update($request->validated());

        return new CoinResource($coin);
    }

    public function destroy(Request $request, Coin $coin): Response
    {
        $coin->delete();

        return response()->noContent();
    }
}
