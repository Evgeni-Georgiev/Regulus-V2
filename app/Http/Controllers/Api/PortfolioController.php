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
        // Fetch transactions with their related coins for the given portfolio
        $transactions = $portfolio->transactions()->with('coin')->get();

        // Group transactions by coin and include related coin details
        $groupedTransactions = $transactions->groupBy('coin_id')->map(function ($transactions) {
            // Get the first transaction to fetch coin details
            $firstTransaction = $transactions->first();

            // If no coin is found, skip grouping for invalid data
            $coin = $firstTransaction->coin;
            if (!$coin) {
                return null;
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

            $currentInvestmentCoinValue = $totalQuantity * $coin->price;

            return [
                'id' => $coin->id,
                'symbol' => $coin->symbol,
                'name' => $coin->name,
                'price' => $coin->price,
                'fiat_spent_on_quantity' => $currentInvestmentCoinValue,
                'total_holding_quantity' => $coinTransactions->sum('quantity'),
                'average_buy_price' => $averageBuyPrice, // Average buy price
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

        // Calculate the total value of the portfolio (sum of all transactions)
        $totalPortfolioValue = $groupedTransactions->reduce(function ($sum, $group) {
            return $sum + collect($group['transactions'])->sum('total_price');
        }, 0);

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
