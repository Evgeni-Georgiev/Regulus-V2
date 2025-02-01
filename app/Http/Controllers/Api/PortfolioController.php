<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PortfolioStoreRequest;
use App\Http\Requests\PortfolioUpdateRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use App\Services\CMCClient;
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
        // TODO: Optimize method logic - extract in service class.

        // Fetch coins data from API
        $coinData = $this->cmcClient->getCoinData();

        // Fetch transactions with their related coins for the given portfolio
        $transactions = $portfolio->transactions()->with('coin')->get();

        // Group transactions by coin and include related coin details
        $groupedTransactions = $transactions->groupBy('coin_id')
            ->map(function ($transactions) use ($coinData) {
            // Get the first transaction to fetch coin details
            $firstTransaction = $transactions->first();

            // If no coin is found, skip grouping for invalid data
            $coin = $firstTransaction->coin;
            if (!$coin) {
                return null;
            }

            // Check if the coin exists in the API data
            $apiCoin = $coinData[$coin->symbol] ?? null;
            if (!$apiCoin) {
                return null; // Skip coin if not found in API
            }

            // Filter transactions for the current coin
            $coinTransactions = $transactions->where('coin_id', $coin->id);

            // Calculate total quantity for the coin
            $totalQuantity = $coinTransactions->sum('quantity');

            // Calculate weighted average buy price
            $totalCost = $coinTransactions->reduce(function ($sum, $transaction) {
                return $sum + ($transaction->quantity * $transaction->buy_price);
            }, 0);
            $averageBuyPrice = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;

            $currentInvestmentCoinValue = $totalQuantity * $apiCoin['price'];

            return [
                'id' => $coin->id,
                'symbol' => $apiCoin['symbol'],
                'name' => $apiCoin['name'],
                'price' => $apiCoin['price'],
                'fiat_spent_on_quantity' => $currentInvestmentCoinValue,
                'total_holding_quantity' => $coinTransactions->sum('quantity'),
                'average_buy_price' => $averageBuyPrice,
                'transactions' => $coinTransactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_type' => $transaction->transaction_type,
                        'quantity' => $transaction->quantity,
                        'buy_price' => $transaction->buy_price,
                        'total_price' => $transaction->quantity * $transaction->buy_price,
                        'created_at' => $transaction->created_at->format('d-m-Y'),
                    ];
                }),
            ];
        })->filter(); // Remove null entries for invalid coins

        // Calculate the total value of the portfolio
        $totalPortfolioValue = $groupedTransactions->sum('fiat_spent_on_quantity');

        return response()->json([
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->name,
                'total_value' => $totalPortfolioValue,
                'coins' => $groupedTransactions->values(), // Reset array keys
            ],
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
